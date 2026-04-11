<?php
/**
 * @package EMSHOP
 */

class Api_Controller {

    private $authReqTime = null;
    private $authReqSign = null;
    private $authReqToken = null;
    private $userInfo = [];

    private $userModel;
    private $goodsModel;
    private $sortModel;


    function starter($params) {
        $_func = isset($_GET['rest-api']) ? addslashes($_GET['rest-api']) : '';
        if (empty($_func)) {
            Ret::error('error router');
        }

        if (Option::get('is_openapi') === 'n') {
//            Ret::error('API已关闭');
        }

        if (method_exists($this, $_func)) {
            $this->userModel = new User_Model();
            $this->goodsModel = new Goods_Model();
            $this->sortModel = new Sort_Model();
            $this->$_func();
        } else {
            Ret::error('无效的API地址');
        }
    }

    /**
     * 获取商品分类
     */
    private function getGoodsSort(){
        $this->auth();
        $post = [
            // 'includeEmpty' => false,
        ];
        $data = $this->sortModel->getGoodsSortForHome($post);
        Ret::success('success', $data);
    }

    /**
     * 获取规格模板详情
     */
    private function getSkuTemplateInfo(){
        $this->auth();
        $goods_id = Input::postIntVar('goods_id');
        $goods_type_id = Input::postIntVar('goods_type_id');
        if ($goods_type_id <= 0) {
            Ret::success('success', ['spec' => []]);
        }

        $skuModel = new Sku_Model();
        $spec = $skuModel->getDetail($goods_type_id);

        $list = [];
        foreach ($spec as $item) {
            $values = [];
            foreach ($item['value'] as $val) {
                $values[] = [
                    'id' => $val['id'],
                    'name' => $val['name']
                ];
            }
            $list[] = [
                'sku_attr_id' => $item['id'],
                'title' => $item['name'],
                'sku_values' => $values
            ];
        }

        Ret::success('success', [
            'goods_id' => $goods_id,
            'goods_type_id' => $goods_type_id,
            'spec' => $list
        ]);
    }

    /**
     * 获取规格模板
     */
    private function getSkuTemplate(){
        $this->auth();
        $skuModel = new Sku_Model();
        $data = $skuModel->getSkus();
        Ret::success('success', $data);
    }

    /**
     * 提交订单
     */
    private function submitOrder(){
        $this->auth();
        $goods_id = Input::postIntVar('goods_id');
        $quantity = Input::postIntVar('quantity', 1);
        $quantity = max(1, (int)$quantity);
        $sku_ids = Input::postStrArray('sku_ids', []);
        if (empty($sku_ids)) {
            $sku_str = Input::postStrVar('sku_ids', '');
            $sku_str = trim($sku_str);
            if ($sku_str !== '' && $sku_str !== '0') {
                $sku_ids = array_filter(explode('-', $sku_str), 'strlen');
            }
        }

        $attach = Input::postStrArray('attach', []);
        $required = Input::postStrArray('required', []);
        $config = Input::postStrArray('config', []);
        if (empty($config) && (!empty($attach) || !empty($required))) {
            $config = [
                'input' => array_merge($attach, $required)
            ];
        }

        $contact = Input::postStrVar('contact', '');
        $payment_plugin = Input::postStrVar('payment_plugin', '');
        $payment_name = Input::postStrVar('payment_name', '');
        $payments = array_values(getPayment(false));
        if (empty($payments)) {
            Ret::error('当前站点未启用可用支付方式');
        }
        if ($payment_plugin === '') {
            $payment_plugin = $payments[0]['plugin_name'] ?? '';
            $payment_name = $payments[0]['name'] ?? ($payments[0]['title'] ?? '');
        }
        if ($payment_plugin === 'balance') {
            Ret::error('余额支付已下线');
        }
        $allowedPayments = array_column($payments, 'plugin_name');
        if (!in_array($payment_plugin, $allowedPayments, true)) {
            Ret::error('当前支付方式未启用');
        }
        if ($payment_name === '') {
            foreach ($payments as $payment) {
                if (($payment['plugin_name'] ?? '') === $payment_plugin) {
                    $payment_name = $payment['name'] ?? ($payment['title'] ?? $payment_plugin);
                    break;
                }
            }
        }
        $post = [
            'payment_plugin' => $payment_plugin,
            'payment_name' => $payment_name,
        ];
        $orderModel = new Order_Model();
        $res = $orderModel->createOrder(
            $post,
            $goods_id,
            $quantity,
            $this->userInfo['uid'],
            $this->userInfo['level'],
            $config,
            ['contact' => $contact],
            $sku_ids
        );

        if (is_array($res)) {
            if (isset($res['code']) && $res['code'] !== 0) {
                Ret::error($res['msg'] ?? '下单失败');
            }
            if (isset($res['out_trade_no'])) {
                Ret::success('success', [
                    'out_trade_no' => $res['out_trade_no'],
                    'pay_url' => EM_URL . '?action=pay&out_trade_no=' . $res['out_trade_no'],
                ]);
            }
            Ret::success('success', $res);
        }
        Ret::error('下单失败');
    }

    /**
     * 获取商品信息
     */
    private function getGoodsInfo(){
        $this->auth();
        $goods_id = Input::postIntVar('goods_id');
        if (empty($goods_id)) {
            Ret::error('商品ID不能为空');
        }
        $sku_ids = Input::postStrArray('sku_ids', []);
        if (empty($sku_ids)) {
            $sku_str = Input::postStrVar('sku_ids', '');
            $sku_str = trim($sku_str);
            if ($sku_str !== '' && $sku_str !== '0') {
                $sku_ids = array_filter(explode('-', $sku_str), 'strlen');
            }
        }
        $quantity = Input::postIntVar('quantity', 1);
        $quantity = max(1, (int)$quantity);

        $goods = $this->goodsModel->getOneGoodsForHome(
            $goods_id,
            $this->userInfo['uid'],
            $this->userInfo['level'],
            $sku_ids,
            $quantity
        );
        if (empty($goods)) {
            Ret::error('商品已下架或被删除');
        }

        Ret::success('success', $goods);
    }

    /**
     * 获取商品列表
     */
    private function getGoodsList(){
        $this->auth();
        $sort_id = Input::postIntVar('sort_id', 0);
        $keyword = Input::postStrVar('keyword', '');
        $goods = $this->goodsModel->getGoodsForHome(
            $this->userInfo['uid'],
            $this->userInfo['level'],
            $sort_id,
            $keyword
        );

        $list = [];
        foreach ($goods as $item) {
            $list[] = [
                'id' => (int)$item['id'],
                'goods_id' => (int)$item['id'],
                'title' => $item['title'],
                'price' => $item['price'],
                'cover' => $item['cover'] ?? '',
                'stock' => (int)($item['stock'] ?? 0),
                'sales' => (int)($item['sales'] ?? 0),
                'sort_id' => (int)($item['sort_id'] ?? 0),
                'type' => $item['type'] ?? '',
                'is_sku' => $item['is_sku'] ?? 'n'
            ];
        }

        Ret::success('success', $list);
    }

    /**
     * 获取用户信息
     */
    private function getUserInfo(){
        $this->auth();
        Ret::success('success', $this->userInfo);
    }

    /**
     * 获取店铺信息
     */
    private function getEmInfo(){
        $this->auth();
        $data = [
            'site_name' => Option::get('blogname'),
        ];
        Ret::success('success', $data);
    }

    /**
     * 获取EM站点列表
     */
    private function getEmSites(){
        $this->auth();
        if (!class_exists('EmApi')) {
            require_once EM_ROOT . '/content/plugins/goods_em/lib/EmApi.php';
        }

        if (!function_exists('emGetSiteList')) {
            Ret::error('EM对接插件未启用');
        }

        $sites = emGetSiteList();
        Ret::success('success', $sites);
    }

    /**
     * 添加/更新EM站点
     */
    private function saveEmSite(){
        $this->auth();
        $post = [
            'id' => Input::postIntVar('id'),
            'domain' => Input::postStrVar('domain'),
            'app_id' => Input::postStrVar('app_id'),
            'app_key' => Input::postStrVar('app_key'),
        ];

        if (!class_exists('EmApi')) {
            require_once EM_ROOT . '/content/plugins/goods_em/lib/EmApi.php';
        }

        if (!function_exists('emSaveSite')) {
            Ret::error('EM对接插件未启用');
        }

        $result = emSaveSite($post);
        if ($result['success']) {
            Ret::success($result['message'], ['id' => $result['id']]);
        } else {
            Ret::error($result['message']);
        }
    }

    /**
     * 删除EM站点
     */
    private function deleteEmSite(){
        $this->auth();
        $siteId = Input::postIntVar('site_id');

        if (!class_exists('EmApi')) {
            require_once EM_ROOT . '/content/plugins/goods_em/lib/EmApi.php';
        }

        if (!function_exists('emDeleteSite')) {
            Ret::error('EM对接插件未启用');
        }

        $result = emDeleteSite($siteId);
        if ($result) {
            Ret::success('删除成功');
        } else {
            Ret::error('删除失败，该站点可能有关联商品');
        }
    }

    /**
     * 测试EM站点连接
     */
    private function testEmConnection(){
        $this->auth();
        $post = [
            'domain' => Input::postStrVar('domain'),
            'app_id' => Input::postStrVar('app_id'),
            'app_key' => Input::postStrVar('app_key'),
        ];

        if (!class_exists('EmApi')) {
            require_once EM_ROOT . '/content/plugins/goods_em/lib/EmApi.php';
        }

        $api = new EmApi($post['domain'], $post['app_id'], $post['app_key']);
        $result = $api->connect();

        if ($result) {
            Ret::success('连接成功', $result);
        } else {
            Ret::error('连接失败：' . $api->getLastError());
        }
    }

    /**
     * 获取EM商品列表
     */
    private function getEmGoodsList(){
        $this->auth();
        $siteId = Input::postIntVar('site_id');

        if (!class_exists('EmApi')) {
            require_once EM_ROOT . '/content/plugins/goods_em/lib/EmApi.php';
        }

        if (!function_exists('emGetSite')) {
            Ret::error('EM对接插件未启用');
        }

        $site = emGetSite($siteId);
        if (!$site) {
            Ret::error('站点不存在');
        }

        $api = EmApi::fromSite($site);
        $items = $api->getItems();

        if ($items !== false) {
            Ret::success('success', $items);
        } else {
            Ret::error('获取商品列表失败：' . $api->getLastError());
        }
    }

    /**
     * 导入EM商品
     */
    private function importEmGoods(){
        $this->auth();
        $post = [
            'site_id' => Input::postIntVar('site_id'),
            'goods_ids' => $_POST['goods_ids'] ?? [],
            'sort_id' => Input::postIntVar('sort_id'),
            'raise_type' => Input::postStrVar('raise_type', 'percent'),
            'raise_value' => floatval($_POST['raise_value'] ?? 10),
        ];

        if (!class_exists('EmApi')) {
            require_once EM_ROOT . '/content/plugins/goods_em/lib/EmApi.php';
        }

        if (!function_exists('emImportGoods')) {
            Ret::error('EM对接插件未启用');
        }

        $result = emImportGoods($post);
        Ret::success('导入完成', $result);
    }

    private function article_post() {
        $title = Input::postStrVar('title');
        $content = Input::postStrVar('content');
        $excerpt = Input::postStrVar('excerpt');
        $author_uid = Input::postIntVar('author_uid', 1);
        $post_date = Input::postStrVar('post_date');
        $sort_id = Input::postIntVar('sort_id', -1);
        $tags = strip_tags(Input::postStrVar('tags'));
        $cover = Input::postStrVar('cover');
        $draft = Input::postStrVar('draft', 'n');
        $alias = Input::postStrVar('alias');
        $top = Input::postStrVar('top', 'n');
        $sortop = Input::postStrVar('sortop', 'n');
        $allow_remark = Input::postStrVar('allow_remark', 'n');
        $password = Input::postStrVar('password');
        $template = Input::postStrVar('template');

        $this->auth();

        if (empty($title) || empty($content)) {
            Output::error('parameter error');
        }

        if ($this->curUid) {
            $author_uid = $this->curUid;
        }

        $logData = [
            'title'        => $title,
            'content'      => $content,
            'excerpt'      => $excerpt,
            'author'       => $author_uid,
            'sortid'       => $sort_id,
            'cover'        => $cover,
            'date'         => strtotime($post_date ?: date('Y-m-d H:i:s')),
            'hide'         => $draft === 'y' ? 'y' : 'n',
            'alias'        => $alias,
            'top '         => $top,
            'sortop '      => $sortop,
            'allow_remark' => $allow_remark,
            'password'     => $password,
            'template'     => $template,
        ];

        $article_id = $this->Log_Model->addlog($logData);
        $this->Tag_Model->addTag($tags, $article_id);
        $this->Cache->updateCache();

        doAction('save_log', $article_id, '', $logData);

        output::ok(['article_id' => $article_id,]);
    }

    private function article_update() {
        $id = Input::postIntVar('id');
        $title = Input::postStrVar('title');
        $content = Input::postStrVar('content');
        $excerpt = Input::postStrVar('excerpt');
        $post_date = isset($_POST['post_date']) ? trim($_POST['post_date']) : '';
        $sort_id = Input::postIntVar('sort_id', -1);
        $cover = Input::postStrVar('cover');
        $tags = isset($_POST['tags']) ? strip_tags(addslashes(trim($_POST['tags']))) : '';
        $author_uid = isset($_POST['author_uid']) ? (int)trim($_POST['author_uid']) : 1;
        $draft = Input::postStrVar('draft', 'n');

        $this->auth();

        if (empty($id) || empty($title)) {
            Output::error('parameter error');
        }

        if ($this->curUid) {
            $author_uid = $this->curUid;
        }

        $logData = [
            'title'   => $title,
            'content' => $content,
            'excerpt' => $excerpt,
            'sortid'  => $sort_id,
            'cover'   => $cover,
            'author'  => $author_uid,
            'date'    => strtotime($post_date ?: date('Y-m-d H:i:s')),
            'hide'    => $draft === 'y' ? 'y' : 'n',
        ];

        $this->Log_Model->updateLog($logData, $id, $author_uid);
        $this->Tag_Model->updateTag($tags, $id);
        $this->Cache->updateCache();

        doAction('save_log', $id);

        output::ok();
    }

    private function article_list() {
        $page = isset($_GET['page']) ? (int)trim($_GET['page']) : 1;
        $count = isset($_GET['count']) ? (int)trim($_GET['count']) : Option::get('index_lognum');
        $sort_id = isset($_GET['sort_id']) ? (int)trim($_GET['sort_id']) : 0;
        $keyword = isset($_GET['keyword']) ? addslashes(htmlspecialchars(urldecode(trim($_GET['keyword'])))) : '';
        $keyword = str_replace(['%', '_'], ['\%', '\_'], $keyword);
        $tag = isset($_GET['tag']) ? addslashes(urldecode(trim($_GET['tag']))) : '';
        $order = Input::getStrVar('order');

        $sub = '';
        if ($sort_id) {
            $sub .= ' and sortid = ' . $sort_id;
        }
        if ($keyword) {
            $sub .= " and title like '%{$keyword}%'";
        }
        if ($tag) {
            $blogIdStr = $this->Tag_Model->getTagByName($tag);
            if ($blogIdStr) {
                $sub .= "and gid IN ($blogIdStr)";
            }
        }

        $sub2 = ' ORDER BY ';
        switch ($order) {
            case 'views':
                $sub2 .= 'views DESC';
                break;
            case 'comnum':
                $sub2 .= 'comnum DESC';
                break;
            default:
                $sub2 .= 'top DESC, sortop DESC, date DESC';
                break;
        }

        $r = $this->Log_Model->getLogsForHome($sub . $sub2, $page, $count);
        $sort_cache = $this->Cache->readCache('sort');
        $articles = [];
        foreach ($r as $value) {
            $articles[] = [
                'id'          => (int)$value['gid'],
                'title'       => $value['title'],
                'cover'       => $value['log_cover'],
                'url'         => $value['log_url'],
                'description' => $value['log_description'],
                'date'        => date('Y-m-d H:i:s', $value['date']),
                'author_id'   => (int)$value['author'],
                'author_name' => $this->getAuthorName($value['author']),
                'sort_id'     => (int)$value['sortid'],
                'sort_name'   => isset($sort_cache[$value['sortid']]['sortname']) ? $sort_cache[$value['sortid']]['sortname'] : '',
                'views'       => (int)$value['views'],
                'comnum'      => (int)$value['comnum'],
                'top'         => $value['top'],
                'sortop'      => $value['sortop'],
                'tags'        => $this->getTags((int)$value['gid']),
            ];
        }

        output::ok(['articles' => $articles,]);
    }

    private function article_detail() {
        $id = isset($_GET['id']) ? (int)trim($_GET['id']) : 0;

        $r = $this->Log_Model->getOneLogForHome($id);
        $sort_cache = $this->Cache->readCache('sort');
        $article = '';
        if (empty($r)) {
            output::ok(['article' => $article,]);
        }

        if (!empty($r['password'])) {
            Output::error('This article is private');
        }

        $user_info = $this->User_Model->getOneUser($r['author']);
        $author_name = isset($user_info['nickname']) ? $user_info['nickname'] : '';

        $article = [
            'title'       => $r['log_title'],
            'date'        => date('Y-m-d H:i:s', $r['date']),
            'id'          => (int)$r['logid'],
            'sort_id'     => (int)$r['sortid'],
            'sort_name'   => isset($sort_cache[$r['sortid']]['sortname']) ? $sort_cache[$r['sortid']]['sortname'] : '',
            'type'        => $r['type'],
            'author_id'   => (int)$r['author'],
            'author_name' => $author_name,
            'content'     => $r['log_content'],
            'excerpt'     => $r['excerpt'],
            'cover'       => $r['log_cover'],
            'views'       => (int)$r['views'],
            'comnum'      => (int)$r['comnum'],
            'top'         => $r['top'],
            'sortop'      => $r['sortop'],
            'tags'        => $this->getTags($id),
        ];

        output::ok(['article' => $article,]);
    }

    private function sort_list() {
        $sort_cache = $this->Cache->readCache('sort');
        $data = [];
        foreach ($sort_cache as $sort_id => $value) {
            unset($value['children']);
            if ($value['pid'] === 0) {
                $data[$sort_id] = $value;
            } else {
                $data[$value['pid']]['children'][] = $value;
            }
        }
        sort($data);
        output::ok(['sorts' => $data,]);
    }

    private function note_post() {
        $t = Input::postStrVar('t');
        $private = Input::postStrVar('private', 'n');
        $author_uid = Input::postIntVar('author_uid', 1);

        $this->auth();

        if (empty($t)) {
            Output::error('parameter error');
        }

        if ($private !== 'y') {
            $private = 'n';
        }

        if ($this->curUid) {
            $author_uid = $this->curUid;
        }

        $data = [
            'content' => $t,
            'author'  => $author_uid,
            'private' => $private,
            'date'    => time(),
        ];

        $id = $this->Twitter_Model->addTwitter($data);
        $this->Cache->updateCache('sta');
        doAction('post_note', $data, $id);
        output::ok(['note_id' => $id,]);
    }

    private function note_list() {
        $page = Input::getIntVar('page', 1);
        $author_uid = Input::getIntVar('author_uid');
        $count = Input::getIntVar('count', 20);

        $this->auth();

        if ($this->curUid) {
            $author_uid = $this->curUid;
        }

        $r = $this->Twitter_Model->getTwitters($author_uid, $page, $count);

        $notes = [];
        foreach ($r as $value) {
            $notes[] = [
                'id'          => (int)$value['id'],
                't'           => $value['t'],
                't_raw'       => $value['t_raw'],
                'date'        => $value['date'],
                'author_id'   => (int)$value['author'],
                'author_name' => $this->getAuthorName($value['author']),
            ];
        }
        output::ok(['notes' => $notes,]);
    }

    public function userinfo() {
        $this->checkAuthCookie();

        $data = [
            'uid'         => (int)$this->curUserInfo['uid'],
            'nickname'    => htmlspecialchars($this->curUserInfo['nickname']),
            'role'        => $this->curUserInfo['role'],
            'photo'       => $this->curUserInfo['photo'],
            'avatar'      => $this->curUserInfo['photo'] ? EM_URL . str_replace("../", '', $this->curUserInfo['photo']) : '',
            'email'       => $this->curUserInfo['email'],
            'description' => htmlspecialchars($this->curUserInfo['description']),
            'ip'          => $this->curUserInfo['ip'],
            'create_time' => (int)$this->curUserInfo['create_time'],
        ];

        output::ok(['userinfo' => $data]);
    }

    public function upload() {
        $sid = Input::postIntVar('sid');
        $author_uid = Input::postIntVar('author_uid', 1);
        $attach = isset($_FILES['file']) ? $_FILES['file'] : '';

        $this->checkApiKey();

        if (!$attach || $attach['error'] === 4) {
            Output::error('Upload error');
        }

        $ret = '';
        addAction('upload_media', 'upload2local');
        doOnceAction('upload_media', $attach, $ret);

        if (empty($ret['success']) || !isset($ret['file_info'])) {
            Output::error($ret['message']);
        }

        $aid = $this->Media_Model->addMedia($ret['file_info'], $sid, $author_uid);

        Output::ok(['media_id' => $aid, 'url' => $ret['url'], 'file_info' => $ret['file_info']]);
    }

    private function comment_list() {
        $id = Input::getIntVar('id');
        $page = Input::getIntVar('page', 1);

        $comments = $this->Comment_Model->getComments($id, 'n', $page);
        output::ok($comments);
    }

    private function getTags($id) {
        $tag_ids = $this->Tag_Model->getTagIdsFromBlogId($id);
        $tag_names = $this->Tag_Model->getNamesFromIds($tag_ids);
        $tags = [];
        if (!empty($tag_names)) {
            foreach ($tag_names as $value) {
                $tags[] = [
                    'name' => htmlspecialchars($value),
                    'url'  => Url::tag(rawurlencode($value)),
                ];
            }
        }
        return $tags;
    }

    private function getAuthorName($uid) {
        $userInfo = $this->User_Model->getOneUser($uid);
        return isset($userInfo['nickname']) ? $userInfo['nickname'] : '';
    }

    private function auth() {
        if (isset($_COOKIE[AUTH_COOKIE_NAME])) {
            $this->checkAuthCookie();
        } else {
            $this->checkApiKey();
        }
    }

    private function checkApiKey() {
        $this->authReqSign = Input::requestStrVar('req_sign');
        $this->authReqTime = Input::requestStrVar('req_time');
        $this->authReqToken = Input::requestStrVar('req_token');
        $user_id = Input::requestStrVar('user_id');

        if (empty($this->authReqSign) || empty($this->authReqTime)) {
            Ret::error('验证参数错误');
        }
        $sign = md5($this->authReqTime . $this->authReqToken);
        if ($sign !== $this->authReqSign) {
            Ret::error('签名验证失败');
        }
        $userInfo = $this->userModel->getOneUser($user_id);
        if (empty($userInfo)) {
            Ret::error('商户不存在。');
        }
        if($this->authReqToken != $userInfo['token']){
            Ret::error('商户不存在。' . $this->authReqToken);
        }
        $this->userInfo = $userInfo;
    }

    private function checkAuthCookie() {
        if (!isset($_COOKIE[AUTH_COOKIE_NAME])) {
            Output::authError('auth cookie error');
        }
        $userInfo = loginauth::validateAuthCookie($_COOKIE[AUTH_COOKIE_NAME]);
        if (!$userInfo) {
            Output::authError('auth cookie error');
        }
        $this->curUserInfo = $userInfo;
        $this->curUid = (int)$userInfo['uid'];
    }

    /**
     * 规格结构规范化
     */
    private function normalizeSpec($optionName) {
        $spec = [];
        if (empty($optionName)) {
            return $spec;
        }
        foreach ($optionName as $index => $group) {
            $values = [];
            $skuValues = $group['sku_values'] ?? [];
            foreach ($skuValues as $val) {
                if (!isset($val['option_id'])) {
                    continue;
                }
                $values[] = [
                    'id' => $val['option_id'],
                    'name' => $val['option_name'] ?? $val['option_id']
                ];
            }
            $spec[] = [
                'sku_attr_id' => $index,
                'title' => $group['title'] ?? '规格',
                'sku_values' => $values
            ];
        }
        return $spec;
    }


    /**
     * 获取分类下的商品
     */
    public function getCategoryProducts() {
        $sort_id = Input::getIntVar('sort_id');
        $keyword = Input::getStrVar('q');
        $goodsModel = new Goods_Model();
        $goods_list = $goodsModel->getGoodsForHome(UID, LEVEL, $sort_id, $keyword);
        Ret::success('ok', [
            'goods_list' => $goods_list
        ]);
    }

}
