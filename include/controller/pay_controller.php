<?php
/**
 * @package ESHOP
 */

class Pay_Controller {

    /**
     * 发起支付
     */
    function index() {
        $out_trade_no = Input::getStrVar('out_trade_no');
        if (empty($out_trade_no)) {
            emMsg('非法请求');
        }

        $orderModel = $this->createOrderModel();
        $db = Database::getInstance();
        $db_prefix = DB_PREFIX;

        $order = $orderModel->getOrderInfoRaw($out_trade_no);
        if (empty($order)) {
            emMsg('非法请求');
        }
        if ((int)($order['pay_status'] ?? 0) !== 1 && (int)($order['status'] ?? 0) < 0) {
            emMsg('订单' . orderStatusText((int)$order['status']) . '，请重新下单', $this->buildPaidRedirectUrl($out_trade_no));
        }

        $sql = "select * from {$db_prefix}order_list where order_id={$order['id']}";
        $child_order = $db->fetch_all($sql);

        $pay_func = "pay_{$order['pay_plugin']}";
        if ($order['amount'] != 0 && !function_exists($pay_func)) {
            emMsg('当前支付方式未启用或未配置，请返回订单页重新选择支付方式');
        }

        if ($order['amount'] == 0) {
            $order_info = $orderModel->getOrderInfo($out_trade_no, true);
            if (empty($order_info)) {
                emMsg('订单已失效，请重新下单', ISLOGIN ? EM_URL . 'user/order.php' : EM_URL . 'user/visitors.php');
            }

            if ((int)($order_info['pay_status'] ?? 0) != 1) {
                $paymentName = empty($order_info['payment']) ? '免费商品' : $order_info['payment'];
                $result = $orderModel->processConfirmedPayment($out_trade_no, [
                    'pay_time' => time(),
                    'payment' => $paymentName,
                ], ['source' => 'free_order']);
                if (empty($result['ok'])) {
                    emMsg('订单状态已变更，请刷新后重试', ISLOGIN ? EM_URL . 'user/order.php' : EM_URL . 'user/visitors.php');
                }
            }

            header('location: ' . $this->buildPaidRedirectUrl($out_trade_no));
            die;
        }

        $pay_func($order, $child_order);
        die('发起支付中');
    }

    /**
     * 同步通知
     */
    public function _return() {
        Log::info('同步回调 - 开始');

        $checkSign = $this->resolveCallbackPayload();
        if (!$checkSign) {
            Log::info('同步回调 - 验签失败');
            echo '验签失败';
            return;
        }

        Log::info('同步回调 - 验签通过');
        $result = $this->processPaymentCallback($checkSign, 'return');
        if (empty($result['ok'])) {
            Log::warning('同步回调站内处理失败：' . ($checkSign['out_trade_no'] ?? '') . ' - ' . ($result['msg'] ?? 'unknown'));
            emMsg('支付结果已收到，但站内处理失败，请联系管理员核实订单', $this->buildPaidRedirectUrl((string)($checkSign['out_trade_no'] ?? '')));
        }

        header('location: ' . $this->buildPaidRedirectUrl((string)$checkSign['out_trade_no']));
        die;
    }

    /**
     * 异步通知
     */
    public function notify() {
        Log::info('异步回调 - 开始');

        $checkSign = $this->resolveCallbackPayload();
        if (!$checkSign) {
            Log::info('异步回调 - 验签失败');
            http_response_code(400);
            echo '验签失败';
            return;
        }

        Log::info('异步回调 - 验签通过');
        $result = $this->processPaymentCallback($checkSign, 'notify');
        if (empty($result['ok'])) {
            Log::warning('异步回调站内处理失败：' . ($checkSign['out_trade_no'] ?? '') . ' - ' . ($result['msg'] ?? 'unknown'));
            http_response_code(500);
            echo 'fail';
            die;
        }

        echo 'success';
        die;
    }

    /**
     * 补单
     */
    public function repay($out_trade_no) {
        $orderModel = $this->createOrderModel();
        $order_info = $orderModel->getOrderInfo($out_trade_no, true);
        if (empty($order_info)) {
            Ret::error('订单不存在或已失效');
        }
        if (!empty($order_info['delete_time']) || (int)($order_info['status'] ?? 0) < 0) {
            Ret::error('订单已取消、过期、删除或处于异常履约状态，不支持补单');
        }

        $payment_name = empty($order_info['payment']) ? '后台补单' : $order_info['payment'];
        $result = $orderModel->processConfirmedPayment($out_trade_no, [
            'pay_time' => time(),
            'payment' => $payment_name,
        ], ['source' => 'admin_repay']);

        if (empty($result['ok'])) {
            Ret::error($result['msg'] ?? '当前订单无法补单');
        }

        Ret::success($result);
    }

    /**
     * 验证订单支付状态
     */
    public function isPay() {
        $out_trade_no = Input::postStrVar('out_trade_no');

        $orderModel = $this->createOrderModel();
        $order_info = $orderModel->getOrderInfo($out_trade_no, true);
        if (empty($order_info)) {
            $url = ISLOGIN ? EM_URL . 'user/order.php' : EM_URL . 'user/visitors.php';
            die(json_encode([
                'code' => 200,
                'msg' => 'Expired',
                'data' => [
                    'is_pay' => false,
                    'is_expired' => true,
                    'url' => $url,
                ],
            ]));
        }

        if ((int)($order_info['pay_status'] ?? 0) !== 1 && strpos((string)($order_info['pay_plugin'] ?? ''), 'yifut_') === 0) {
            $reconcile = $orderModel->reconcileOrderPayment($out_trade_no);
            if (!empty($reconcile['ok'])) {
                $order_info = $orderModel->getOrderInfo($out_trade_no, true);
            }
        }

        if (!empty($order_info['pay_time']) || (int)($order_info['pay_status'] ?? 0) === 1) {
            $url = $this->buildPaidRedirectUrl($out_trade_no);
            die(json_encode([
                'code' => 200,
                'msg' => 'Paid',
                'data' => [
                    'is_pay' => true,
                    'url' => $url,
                ],
            ]));
        }

        if ((int)($order_info['status'] ?? 0) < 0) {
            $url = ISLOGIN ? EM_URL . 'user/order.php' : EM_URL . 'user/visitors.php';
            die(json_encode([
                'code' => 200,
                'msg' => 'Expired',
                'data' => [
                    'is_pay' => false,
                    'is_expired' => true,
                    'url' => $url,
                ],
            ]));
        }

        die(json_encode([
            'code' => 200,
            'msg' => 'Unpaid',
            'data' => [
                'is_pay' => false,
            ],
        ]));
    }

    private function resolveCallbackPayload() {
        $url = $_SERVER['REQUEST_URI'];
        $path = parse_url($url, PHP_URL_PATH);
        $parts = explode('/', trim($path, '/'));
        $plugin = $parts[2] ?? '';
        $checkFunc = $plugin . 'CheckSign';
        if (!function_exists($checkFunc)) {
            Log::warning('支付回调未找到验签函数：' . $checkFunc);
            return false;
        }

        return $checkFunc(strpos($path, '/action/return/') !== false ? 'return' : 'notify');
    }

    protected function processPaymentCallback($checkSign, $source, $options = []) {
        $orderModel = $this->createOrderModel();
        $outTradeNo = trim((string)($checkSign['out_trade_no'] ?? ''));
        $result = $orderModel->processConfirmedPayment($outTradeNo, $checkSign, ['source' => $source]);
        if (!empty($result['ok'])) {
            return $result;
        }

        if (!empty($options['allow_reconcile_retry']) && $outTradeNo !== '') {
            $retry = $orderModel->reconcileOrderPayment($outTradeNo);
            if (!empty($retry['ok'])) {
                return $retry;
            }
        }

        return $result;
    }

    private function buildPaidRedirectUrl($outTradeNo) {
        $pay_redirect = Option::get('pay_redirect') ? Option::get('pay_redirect') : 'kami';
        if ($pay_redirect !== 'list' && $outTradeNo !== '') {
            $detailUrl = $this->buildOrderDetailRedirectUrl($outTradeNo);
            if ($detailUrl !== '') {
                return $detailUrl;
            }
        }

        return $this->buildOrderListRedirectUrl();
    }

    private function buildOrderDetailRedirectUrl($outTradeNo) {
        if ($outTradeNo === '') {
            return '';
        }

        if (ISLOGIN) {
            return EM_URL . 'user/order.php?action=detail&out_trade_no=' . rawurlencode($outTradeNo);
        }

        $order = $this->createOrderModel()->getOrderByOrderNo($outTradeNo, true);
        if (empty($order) || !$this->canVisitorAccessOrderDetail($order)) {
            return '';
        }

        return EM_URL . 'user/visitors.php?action=visitors_order&out_trade_no=' . rawurlencode($outTradeNo);
    }

    private function buildOrderListRedirectUrl() {
        return ISLOGIN ? EM_URL . 'user/order.php' : EM_URL . 'user/visitors.php';
    }

    private function canVisitorAccessOrderDetail($order) {
        $orderId = (int)($order['id'] ?? 0);
        $outTradeNo = (string)($order['out_trade_no'] ?? '');
        if ($orderId <= 0 || $outTradeNo === '') {
            return false;
        }

        $localToken = trim((string)($_COOKIE['EM_LOCAL'] ?? ''));
        if ($localToken !== '' && !empty($order['em_local']) && hash_equals((string)$order['em_local'], $localToken)) {
            return true;
        }

        $authorized = $this->getAuthorizedVisitorOrders();
        if (empty($authorized[$orderId]['out_trade_no'])) {
            return false;
        }

        return hash_equals((string)$authorized[$orderId]['out_trade_no'], $outTradeNo);
    }

    private function getAuthorizedVisitorOrders() {
        $this->ensureVisitorSession();

        $key = 'eshop_visitor_authorized_orders';
        $legacyKey = substr_replace($key, 'm', 1, 0);
        $current = isset($_SESSION[$key]) && is_array($_SESSION[$key]) ? $_SESSION[$key] : [];
        if (empty($current) && isset($_SESSION[$legacyKey]) && is_array($_SESSION[$legacyKey])) {
            $current = $_SESSION[$legacyKey];
        }
        $now = time();
        $authorized = [];

        foreach ($current as $orderId => $row) {
            $orderId = (int)$orderId;
            $expiresAt = (int)($row['expires_at'] ?? 0);
            $outTradeNo = (string)($row['out_trade_no'] ?? '');
            if ($orderId <= 0 || $expiresAt <= $now || $outTradeNo === '') {
                continue;
            }

            $authorized[$orderId] = [
                'out_trade_no' => $outTradeNo,
                'expires_at' => $expiresAt,
            ];
        }

        $_SESSION[$key] = $authorized;
        unset($_SESSION[$legacyKey]);
        return $authorized;
    }

    private function ensureVisitorSession() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
    }

    protected function createOrderModel() {
        return new Order_Model();
    }
}
