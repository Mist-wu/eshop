<?php
/**
 * search
 *
 * @package ESHOP
 */

class Search_Controller {
    function display($params) {
        $Goods_Model = new Goods_Model();
        $options_cache = Option::getAll();
        extract($options_cache);

        $sortModel = new Sort_Model();
        $sorts = $sortModel->getSorts();

        $page = isset($params[4]) && $params[4] == 'page' ? abs((int)$params[5]) : 1;
        $keyword = isset($params[1]) && $params[1] == 'keyword' ? trim($params[2]) : '';
        $keyword = addslashes(htmlspecialchars(urldecode($keyword)));
        $keyword = str_replace(array('%', '_'), array('\%', '\_'), $keyword);

        $pageurl = '';

        $sqlSegment = " title like '%$keyword%'";
        $orderBy = ' order by date desc';
        $lognum = $Goods_Model->getGoodsNum('n', $sqlSegment);
        $total_pages = ceil($lognum / $index_lognum);
        if ($page > $total_pages) {
            $page = $total_pages;
        }
        $pageurl .= EM_URL . '?keyword=' . urlencode($keyword) . '&page=';

        $goods = $Goods_Model->getGoodsForHome($sqlSegment, $page, $index_lognum);
        $page_url = pagination($lognum, $index_lognum, $page, $pageurl);

        include View::getView('header');
        include View::getView('goods_list');
    }
}
