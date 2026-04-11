<?php
/**
 * article and page model
 *
 * @package EMLOG
 * @link https://www.emlog.net
 */

class Cart_Model {

    private $db;
    private $Parsedown;
    private $table;
    private $table_user;
    private $table_sort;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->table = DB_PREFIX . 'cart';
        $this->table_user = DB_PREFIX . 'user';
        $this->table_sort = DB_PREFIX . 'sort';
        $this->Parsedown = new Parsedown();
        $this->Parsedown->setBreaksEnabled(true); //automatic line wrapping
    }



    // ================================================================================================================================

    /**
     * 获取我的购物车列表
     */
    public function getMyCartsForHome() {
        if(ISLOGIN){
            $where = "c.EM_LOCAL='" . EM_LOCAL . "' or c.user_id=" . UID;
        }else{
            $where = "c.is_local=1 and c.EM_LOCAL='" . EM_LOCAL . "'";
        }
        $prefix = DB_PREFIX;
        $sql = <<<sql
                        SELECT 
                            c.id cart_id, c.goods_id, g.title, c.sku, gs.price, count(gs.specification) as sku_exist
                        FROM 
                            {$this->table} as c 
                        left join 
                                {$prefix}goods as g on c.goods_id=g.id 
                        left join 
                                {$prefix}goods_sku as gs on c.goods_id=gs.goods_id and c.sku=gs.specification
                        WHERE 
                            {$where} 
                        GROUP BY 
                            c.id, c.goods_id
                        ORDER BY 
                            c.id desc
sql;
        $res = $this->db->query($sql);
        $carts = [];
        $sku_value_ids = [];
        $sku_value = [];
        while ($row = $this->db->fetch_array($res)) {
            $sku_temp = explode('-', $row['sku']);
            $sku_value_ids = array_unique(array_merge($sku_value_ids, $sku_temp));
            $row['price'] = $row['price'] / 100;
            $carts[] = $row;
        }
//        d($carts);die;
        $sku_value_ids = implode(',', $sku_value_ids);
        $sql = "select * from " . DB_PREFIX . "sku_value where id in({$sku_value_ids})";
        $res = $this->db->query($sql);
        while ($row = $this->db->fetch_array($res)) {
            $sku_value[] = $row;
        }
        $sku_attr_ids = array_unique(array_column($sku_value, 'attr_id'));
        $sku_attr_ids = implode(',', $sku_attr_ids);
        $sku_attr = [];
        $sql = "select * from " . DB_PREFIX . "sku_attr where id in({$sku_attr_ids})";
        $res = $this->db->query($sql);
        while ($row = $this->db->fetch_array($res)) {
            $sku_attr[] = $row;
        }

        foreach($sku_value as $key => $val){
            foreach($sku_attr as $v){
                if($val['attr_id'] == $v['id']){
                    $sku_value[$key]['attr'] = $v['title'];
                }
            }
        }
        foreach($carts as $key => $val){
            $val_sku = explode('-', $val['sku']);
            $attr_sku = "";
            foreach($val_sku as $v){
                foreach($sku_value as $vs){
                    if($v == $vs['id']){
                        $attr_sku .= $vs['attr'] . "：" . $vs['name'] . "；";
                    }
                }
            }
            $carts[$key]['attr_sku'] = $attr_sku;
        }
//        d($carts);die;

//        d($sku_attr);

//        d($sku_value);
//        d($carts);
        return $carts;
    }


    /**
     * 加入购物车
     */
    public function insertCart($data){
        $sql = "insert into ". $this->table . " (is_local, EM_LOCAL, user_id, goods_id, sku, quantity, create_time) values ";
        foreach($data as $val){
            $sql .= "({$val['is_local']}, '{$val['EM_LOCAL']}', {$val['user_id']}, {$val['goods_id']}, '{$val['sku']}', {$val['quantity']}, '{$val['create_time']}'),";
        }
        $sql = rtrim($sql, ',');
        $this->db->query($sql);
    }

    /**
     * 修改购物车商品的规格
     */
    public function updateCart($id, $sku){
        $timestamp = time();
        $sql = "UPDATE `" . DB_PREFIX . "cart` SET `sku` = '{$sku}', `update_time` = '{$timestamp}' WHERE `id` = {$id}";
        $this->db->query($sql);
    }





    // ================================================================================================================================





}
