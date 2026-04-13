<?php
defined('EM_ROOT') || exit('access denied!');

/*
 * 插件前台展示页面
 * 前台显示地址为：https://yourdomain/?plugin=alipay
 */

$out_trade_no = Input::getStrVar('out_trade_no');
$qr_code = Input::getStrVar('qr_code');

if (empty($out_trade_no)) {
    emMsg('非法请求');
}

if (strpos($out_trade_no, 'cz') === 0) {
    emMsg('充值功能已下线');
}
$orderModel = new Order_Model();
$order_info = $orderModel->getOrderInfo($out_trade_no);

if (empty($order_info)) {
    emMsg('订单不存在或已失效');
}

$detailUrl = ISLOGIN
    ? EM_URL . 'user/order.php?action=detail&out_trade_no=' . rawurlencode($order_info['out_trade_no'])
    : EM_URL . 'user/visitors.php';
$cancelUrl = ISLOGIN
    ? EM_URL . 'user/order.php?action=cancel&out_trade_no=' . rawurlencode($order_info['out_trade_no'])
    : '';


$isMobile = isMobile();
$home_icon = Option::get('home_icon');
$expireTimestamp = (int)($order_info['expire_time'] ?? 0);

?>
<!--some html code here-->
<!doctype html>
<html lang="zh-cn" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>支付宝扫码支付</title>
    <meta name="keywords" content=""/>
    <meta name="description" content=""/>
    <link href="<?= empty($home_icon) ? (empty(_g('favicon')) ? EM_URL . 'favicon.ico' : _g('favicon')) : $home_icon; ?>" rel="icon">
    <link rel="alternate" title="RSS" href="<?= EM_URL ?>rss.php" type="application/rss+xml"/>

    <script src="../../../admin/views/js/jquery.min.3.5.1.js"></script>
    <script src="../../../admin/views/js/bootstrap.bundle.min.4.6.js?t=<?= Option::EM_VERSION_TIMESTAMP ?>"></script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "PingFang SC", "Helvetica Neue", Arial, sans-serif;
        }

        body {
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 88vh;
            color: #333;
        }

        .payment-container {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            width: 320px;
            padding: 30px;
            text-align: center;
        }

        .logo {
            /*width: 40px;*/
            height: 50px;
            margin-bottom: 15px;
        }

        .title {
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 20px;
            color: #1677ff;
        }

        .amount {
            font-size: 25px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #ff4d4f ;
        }

        .qrcode-container {
            padding: 15px;
            margin: 0 auto 15px;
            border: 1px solid #eee;
            border-radius: 8px;
            display: inline-block;
            background-color: white;
        }

        .qrcode {
            width: 180px;
            height: 180px;
            background-color: #1677ff;
            margin: 0 auto;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            font-size: 14px;
        }
        .qrcode canvas{
            width: 100%;
            height: 100%;
        }

        .instructions {
            font-size: 14px;
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .payment-status {
            margin-bottom: 18px;
            padding: 10px 12px;
            border-radius: 8px;
            border: 1px solid #d9f7be;
            background: #f6ffed;
            color: #389e0d;
            font-size: 13px;
            line-height: 1.6;
        }

        .payment-status.is-warning {
            border-color: #ffd591;
            background: #fff7e6;
            color: #d46b08;
        }

        .payment-status.is-expired {
            border-color: #ffccc7;
            background: #fff2f0;
            color: #cf1322;
        }

        .footer {
            font-size: 12px;
            color: #999;
            margin-top: 20px;
        }

        .payment-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 18px;
        }

        .payment-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 110px;
            padding: 10px 16px;
            border-radius: 999px;
            border: 1px solid #d9d9d9;
            background: #fff;
            color: #333;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .payment-link.primary {
            background: #1677ff;
            border-color: #1677ff;
            color: #fff;
        }

        .payment-link.danger {
            color: #cf1322;
            border-color: rgba(207, 19, 34, 0.3);
            background: rgba(255, 77, 79, 0.06);
        }

        .payment-link:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.08);
        }

        .order-details {
            text-align: left;
            width: 80%;
            margin: 0 auto 20px;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 6px;
        }
        .order-details p {
            margin-bottom: 8px;
        }
        .qrcode img{
            width: 100%;
        }
    </style>
</head>
<body>
<div class="payment-container">
    <img src="../content/plugins/alipay/zhifubao.jpeg" alt="支付宝" class="logo">
    <!--    <h1 class="title">请使用手机【--><?php //= $order_info['pay_name'] ?><!--】扫码支付</h1>-->
    <div class="amount">¥ <?= $order_info['amount'] ?></div>

    <p class="order-info" style="font-size: 13px;">订单编号：<?= $order_info['out_trade_no'] ?></p>
    <p class="order-info" style="margin-left: -9px; font-size: 13px;">创建时间：<?= date("Y-m-d    H:i:s", $order_info['create_time']) ?></p>

    <div class="qrcode-container">
        <div class="qrcode" id="qrcode"></div>
    </div>
    <?php if($isMobile): ?>
    <a class="btn btn-primary" href="<?= $qr_code ?>" style="margin-bottom: 15px;">打开支付宝支付</a>
    <?php endif; ?>

    <p class="instructions">请打开支付宝扫一扫<br>扫描二维码完成支付</p>

    <div class="payment-status" id="payment-status">
        正在等待支付结果，请在订单有效期内完成支付。
    </div>

    <div class="footer">
        支付完成后，页面将自动跳转到订单页面
    </div>

    <div class="payment-actions">
        <a class="payment-link" href="<?= htmlspecialchars($detailUrl) ?>">退出</a>
        <?php if (!empty($cancelUrl) && empty($order_info['pay_time']) && (int)($order_info['status'] ?? 0) === 0): ?>
            <a class="payment-link danger" href="<?= htmlspecialchars($cancelUrl) ?>" onclick="return confirm('确认取消当前订单吗？');">取消订单</a>
        <?php endif; ?>
    </div>
</div>


<script src="<?= EM_URL ?>/content/static/js/qrcode.min.js?t=<?= Option::EM_VERSION_TIMESTAMP ?>"></script>

<script>
    new QRCode(document.getElementById("qrcode"), "<?= $qr_code ?>", {
        width: 220,
        height: 220,
    });

    var out_trade_no = '<?= $order_info['out_trade_no'] ?>';
    var paymentDeadline = <?= $expireTimestamp ?> * 1000;
    var paymentStatusEl = document.getElementById('payment-status');
    var paymentCountdownTimer = null;
    var paymentCheckTimer = null;
    var paymentWatchStopped = false;


    function padTime(num) {
        return num < 10 ? '0' + num : String(num);
    }

    function formatCountdown(totalSeconds) {
        var minutes = Math.floor(totalSeconds / 60);
        var seconds = totalSeconds % 60;
        return padTime(minutes) + ':' + padTime(seconds);
    }

    function setPaymentStatus(message, state) {
        if (!paymentStatusEl) {
            return;
        }
        paymentStatusEl.className = 'payment-status';
        if (state) {
            paymentStatusEl.classList.add(state);
        }
        paymentStatusEl.textContent = message;
    }

    function updatePaymentStatus() {
        if (!paymentDeadline) {
            setPaymentStatus('正在等待支付结果，请在订单有效期内完成支付。');
            return;
        }

        var remainingSeconds = Math.max(0, Math.ceil((paymentDeadline - Date.now()) / 1000));
        if (remainingSeconds === 0) {
            setPaymentStatus('支付等待时间已超过订单有效期，如未完成支付，请返回订单页重新发起。', 'is-expired');
            return;
        }

        if (remainingSeconds <= 60) {
            setPaymentStatus('支付即将超时，剩余 ' + formatCountdown(remainingSeconds) + '。超时后订单会自动取消。', 'is-warning');
            return;
        }

        setPaymentStatus('请在 ' + formatCountdown(remainingSeconds) + ' 内完成支付，支付完成后页面会自动跳转。');
    }

    function stopPaymentWatch() {
        paymentWatchStopped = true;
        if (paymentCountdownTimer) {
            clearInterval(paymentCountdownTimer);
            paymentCountdownTimer = null;
        }
        if (paymentCheckTimer) {
            clearTimeout(paymentCheckTimer);
            paymentCheckTimer = null;
        }
    }

    function scheduleNextCheck(delay) {
        if (paymentWatchStopped) {
            return;
        }
        paymentCheckTimer = setTimeout(function() {
            checkPay();
        }, delay);
    }

    updatePaymentStatus();
    paymentCountdownTimer = setInterval(updatePaymentStatus, 1000);

    function checkPay(){
        if (paymentWatchStopped) {
            return;
        }
        $.ajax({
            url: "?action=is_pay",
            type: "POST",
            data: { out_trade_no: out_trade_no },
            dataType: "json",
            success: function(e) {
                if(e.data.is_pay){
                    stopPaymentWatch();
                    location.href=e.data.url
                }else if(e.data.is_expired){
                    stopPaymentWatch();
                    alert('订单已超时，系统已自动取消');
                    location.href=e.data.url;
                }else{
                    scheduleNextCheck(5000);
                }
            },
            error: function() {
                scheduleNextCheck(5000);
            }
        });
    }
    scheduleNextCheck(3000);
</script>

</body>
</html>
