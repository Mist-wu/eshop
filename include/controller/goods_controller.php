<?php


class Goods_Controller {

    function display($params) {
        $options_cache = Option::getAll();
        extract($options_cache);
        $sort_id = Input::getStrVar('sort_id');
        $keyword = Input::getStrVar('q');

        $goodsModel = new Goods_Model();
        $sortModel = new Sort_Model();

        $sort = $sortModel->getGoodsSortForHome();
        $goods_list = $goodsModel->getGoodsForHome(UID, LEVEL, $sort_id, $keyword);

        doAction('home_goods_list_control');

        $templateModel = new Template_Model();
        $templateInfo = $templateModel->getCurrentTemplate();
        
        if(empty($templateInfo)) emMsg('当前使用的模板已被删除或损坏，请登录后台更换其他模板。');

        if($templateInfo['header_and_footer'] == 'common'){
            include View::getCommonView('header');
            include View::getView('goods_list');
            include View::getCommonView('footer');
        }else{
            include View::getHeaderView('header');
            include View::getView('goods_list');
            include View::getFooterView('footer');
        }
    }

    function displayContent($params) {

        $goodsModel = new Goods_Model();
        $CACHE = Cache::getInstance();

        $options_cache = $CACHE->readCache('options');
        extract($options_cache);

        $goods_id = 0;
        if (isset($params[1])) {
            if ($params[1] == 'post' || $params[1] == 'buy') {
                $goods_id = isset($params[2]) ? (int)$params[2] : 0;
            } elseif (is_numeric($params[1])) {
                $goods_id = (int)$params[1];
            } else {
                $goods_alias_cache = $CACHE->readCache('goods_alias');
                if (!empty($goods_alias_cache)) {
                    $alias = addslashes(urldecode(trim($params[1])));
                    $goods_id = array_search($alias, $goods_alias_cache);
                    if (!$goods_id) {
                        show_404_page();
                    }
                }
            }
        }
        $goods = $goodsModel->getOneGoodsForHome($goods_id, UID, LEVEL, [], 0);
        if(empty($goods)){
            $em_url = EM_URL;
            emMsg('该商品已下架或被删除', "javascript:location.replace('{$em_url}');");
        }

        $payment = getPayment(true, $goods['payment']);

        $visitor_input = $this->getVisitorRequired();

        // d($visitor_input);die;



        // doMultiAction('goods_content_echo', $goods, $goods);
        
        // tdk
        $site_title = $this->setSiteTitle($log_title_style, $goods['title'], $blogname, $site_title, $goods_id);
        $site_description = $this->setSiteDes($site_description, $goods['content'], $goods['id']);

        $meta = [
            'goods_id' => $goods_id,
            'site_title' => $site_title,
            'site_key' => $site_key,
            'site_description' => $site_description,
        ];
        doOnceAction('goods_meta', $meta, $meta);
        extract($meta);

        $templateModel = new Template_Model();
        $templateInfo = $templateModel->getCurrentTemplate();

        if(empty($templateInfo)) emMsg('当前使用的模板已被删除或损坏，请登录后台更换其他模板。');

        $template = !empty($template) && file_exists(TEMPLATE_PATH . $template . '.php') ? $template : 'goods';
        if($templateInfo['header_and_footer'] == 'common'){
            include View::getCommonView('header');
            include View::getView($template);
        }else{
            include View::getHeaderView('header');
            include View::getView($template);
        }
    }

    public function getVisitorRequired(){

        if(ISLOGIN) return false;

        $data = [];

        $guest_query_contact_switch = Option::get('guest_query_contact_switch');
        $guest_query_contact_switch = $guest_query_contact_switch != 'n' ? 'y' : 'n';

        $guest_query_password_switch = Option::get('guest_query_password_switch');
        $guest_query_password_switch = $guest_query_password_switch == 'y' ? 'y' : 'n';

        // 查询最近的订单
        $db = Database::getInstance();
        $prefix = DB_PREFIX;
        $em_local = $db->escape_string(EM_LOCAL);
        $sql = "SELECT contact, pwd FROM {$prefix}order WHERE em_local = '{$em_local}' AND pay_time IS NOT NULL and delete_time is null ORDER BY id DESC LIMIT 1";
        $latest_order = $db->once_fetch_array($sql);

        $contact_value = !empty($latest_order['contact']) ? $latest_order['contact'] : '';
        $pwd_value = !empty($latest_order['pwd']) ? $latest_order['pwd'] : '';

        if($guest_query_contact_switch == 'y'){
            $contact_type = Option::get('guest_query_contact_type') ?: 'any';
            $title_map = [
                'any' => '联系方式',
                'qq' => 'QQ号码',
                'email' => '邮箱地址',
                'phone' => '手机号码'
            ];
            $contact_placeholder_order = Option::get('guest_query_contact_placeholder_order');
            if (empty($contact_placeholder_order) || $contact_placeholder_order === '请输入您的联系方式') {
                $contact_placeholder_order = '请输入联系方式(用于订单查询)';
            }

            $data['contact'] = [
                'type' => 'contact',
                'title' => $title_map[$contact_type] ?? '联系方式',
                'value' => $contact_value,
                'contact_type' => $contact_type,
                'placeholder_order' => $contact_placeholder_order,
                'placeholder_query' => Option::get('guest_query_contact_placeholder_query') ?: '请输入您下单时填写的联系方式'
            ];
        }

        if($guest_query_password_switch == 'y'){
            $data['password'] = [
                'title' => '订单密码',
                'value' => $pwd_value,
                'type' => 'password',
                'placeholder_order' => Option::get('guest_query_password_placeholder_order') ?: '请设置订单密码',
                'placeholder_query' => Option::get('guest_query_password_placeholder_query') ?: '请输入您设置的订单密码'
            ];
        }

        return $data;

    }

    private function setSiteDes($siteDescription, $logContent, $goods_id) {
        if ($this->isHomePage($goods_id)) {
            return $siteDescription;
        }

        return extractHtmlData($logContent, 90);
    }

    private function setSiteKey($tagIdStr, $siteKey, $goods_id) {
        if ($this->isHomePage($goods_id)) {
            return $siteKey;
        }

        if (empty($tagIdStr)) {
            return $siteKey;
        }

        $tagNames = '';
        $tagModel = new Tag_Model();
        $ids = explode(',', $tagIdStr);

        if ($ids) {
            $tags = $tagModel->getNamesFromIds($ids);
            $tagNames = implode(',', $tags);
        }

        return $tagNames;
    }

    private function setSiteTitle($logTitleStyle, $logTitle, $blogName, $siteTitle, $goods_id) {
        if ($this->isHomePage($goods_id)) {
            return $siteTitle ?: $blogName;
        }

        switch ($logTitleStyle) {
            case '0':
                $articleSeoTitle = $logTitle;
                break;
            case '1':
                $articleSeoTitle = $logTitle . ' - ' . $blogName;
                break;
            case '2':
            default:
                $articleSeoTitle = $logTitle . ' - ' . $siteTitle;
                break;
        }

        return $articleSeoTitle;
    }

    private function isHomePage($goods_id) {
        $homePageId = Option::get('home_page_id');
        if ($homePageId && $homePageId == $goods_id) {
            return true;
        }
        return false;
    }

    

}
