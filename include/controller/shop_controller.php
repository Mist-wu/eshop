<?php


class Shop_Controller {

    private $db;
    private $db_prefix;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->db_prefix = DB_PREFIX;
    }

    /**
     * 获取所有商品分类
     */
    public function getAllCategory(){

        $q = Input::getStrVar('q');
        $where = "";
        if(!empty($q)){
            $where .= " and g.title like '%{$q}%'";
        }

        $sql = "
SELECT 
    s.sid, s.sortname, COUNT(g.id) AS goods_count, s.sortimg
FROM 
    {$this->db_prefix}sort s
LEFT JOIN 
    {$this->db_prefix}goods g ON s.sid = g.sort_id and g.delete_time is null and g.is_on_shelf = 1 {$where}
WHERE 
    s.type = 'goods'
GROUP BY  s.sid, s.sortname
ORDER BY 
    s.taxis desc, s.sid asc;";
//        echo $sql;die;

        $res = $this->db->fetch_all($sql);
//d($res);die;
        $sql = "select count(id) as goods_count from {$this->db_prefix}goods g where g.sort_id = -1 and g.delete_time is null and g.is_on_shelf = 1 {$where}";
        $noCategoryGoods = $this->db->once_fetch_array($sql);
        if($noCategoryGoods['goods_count'] > 0){
            $res[] = [
                'sid' => -1,
                'sortname' => '未分类',
                'goods_count' => $noCategoryGoods['goods_count'],
                'sortimg' => './content/common/img/wu.png',
                'pid' => 0,
            ];
        }
//        d($res);die;
        return $res;
    }

    public function getAllGoods($sort_id = 0){
        $q = Input::getStrVar('q');
        $where = "";
        if(!empty($q)){
            $where .= " and g.title like '%{$q}%'";
        }

        if($sort_id != 0){
            $where .= " and g.sort_id={$sort_id}";
        }
        $sql = "SELECT
            g.title, g.cover, g.des, g.type, g.id goods_id, g.des, g.sales, g.sort_id, g.id goods_id,
            sku.guest_price, sku.user_price, g.stock stock_num, g.stock,
            mp.tier_id mp_level, mp.price mp_price
        FROM {$this->db_prefix}goods g
        LEFT JOIN {$this->db_prefix}skus sku ON sku.goods_id = g.id
        LEFT JOIN {$this->db_prefix}tier_price mp ON mp.sku_id IN (SELECT id FROM {$this->db_prefix}product_sku WHERE goods_id = g.id)
        WHERE g.is_on_shelf = 1 AND g.delete_time IS NULL {$where}
        GROUP BY sku.sku, sku.goods_id, mp.tier_id
        ORDER BY g.index_top DESC, g.sort_top DESC, g.sort_num DESC, g.id asc";

//        echo $sql;die;

        $data = $this->db->fetch_all($sql);
        $goods = [];
        foreach($data as $val){

            if(isset($goods[$val['goods_id']])){
                $goods[$val['goods_id']]['mp'][$val['mp_level']] = $val['mp_price'];
            }else{
                $goods[$val['goods_id']] = $val;
                $goods[$val['goods_id']]['url'] = Url::goods($val['goods_id']);
                $goods[$val['goods_id']]['mp'][$val['mp_level']] = $val['mp_price'];
            }
        }
//        d($data);die;
        foreach($goods as $key => $val){
            if(LEVEL == -1){
                $goods[$key]['price'] = $val['guest_price'];
            }else if(LEVEL == 0){
                $goods[$key]['price'] = $val['user_price'];
            }else{
                if(!empty($val['mp'])){
                    foreach($val['mp'] as $k => $v){
                        if($k == LEVEL){
                            $goods[$key]['price'] = $v;
                        }
                    }
                    if(!isset($goods[$key]['price'])){
                        $goods[$key]['price'] = $val['user_price'];
                    }
                }else{
                    $goods[$key]['price'] = $val['user_price'];
                }
            }
            $goods[$key]['price'] = number_format ($goods[$key]['price'] / 100, 2);
        }

        doMultiAction('home_goods_list', $goods, $goods);

//        d($goods);die;
        return $goods;


    }

}
