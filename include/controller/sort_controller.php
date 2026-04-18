<?php
/**
 * sort
 *
 * @package ESHOP
 */

class Sort_Controller {
    function display($params) {

        $CACHE = Cache::getInstance();
        $options_cache = Option::getAll();
        extract($options_cache);

        $page = isset($params[4]) && $params[4] == 'page' ? abs((int)$params[5]) : 1;

        $sortid = '';
        if (!empty($params[2])) {
            if (is_numeric($params[2])) {
                $sortid = (int)$params[2];
            } else {
                $sort_cache = $CACHE->readCache('sort');
                foreach ($sort_cache as $key => $value) {
                    $alias = addslashes(urldecode(trim($params[2])));
                    if (array_search($alias, $value, true)) {
                        $sortid = $key;
                        break;
                    }
                }
            }
        }

        $options_cache = Option::getAll();
        extract($options_cache);
        $sortModel = new Sort_Model();

        $sort = $sortModel->getGoodsSortForHome();

        $goodsModel = new Goods_Model();
        $goods_list = $goodsModel->getGoodsForHome(UID, LEVEL, $sortid, null);



        $template = !empty($sort['template']) && file_exists(TEMPLATE_PATH . $sort['template'] . '.php') ? $sort['template'] : 'goods_list';

        include View::getCommonView('header');
        include View::getView($template);

        include View::getCommonView('footer');
    }
}
