<?php
/**
 * article sort model
 * @package EMLOG
 * @link https://www.emlog.net
 */

class Sku_Model {

    private $db;
    private $table;
    private $table_blog;

    function __construct() {
        $this->table = DB_PREFIX . 'sort';
        $this->table_blog = DB_PREFIX . 'blog';
        $this->db = Database::getInstance();
    }

    function addGoodsType($data){
        $kItem = $dItem = [];
        foreach ($data as $key => $val) {
            $kItem[] = $key;
            $dItem[] = $val;
        }
        $field = implode(',', $kItem);
        $values = "'" . implode("','", $dItem) . "'";
        $this->db->query("INSERT INTO " . DB_PREFIX . "attribute_group ($field) VALUES ($values)");
        return $this->db->insert_id();
    }

    function addSkuAttr($data){
        $kItem = $dItem = [];
        foreach ($data as $key => $val) {
            $kItem[] = $key;
            $dItem[] = $val;
        }
        $field = implode(',', $kItem);
        $values = "'" . implode("','", $dItem) . "'";
        $this->db->query("INSERT INTO " . DB_PREFIX . "specification ($field) VALUES ($values)");
        return $this->db->insert_id();
    }



    function getSkus() {
        $db_prefix = DB_PREFIX;
        $sql = "SELECT t.*, a.spec_name attr_title, a.id sku_attr_id from {$db_prefix}attribute_group as t
        left join {$db_prefix}specification as a on a.group_id = t.id and a.delete_time is null
        where t.hide = 'n' and t.delete_time is null order by id asc" ;

        $rows = $this->db->fetch_all($sql);

        $result = [];
        foreach ($rows as $row) {
            $goodsTypeId = $row['id'];  // 主表ID作为分组键

            // 首次处理该 goods_type 时，初始化主表数据
            if (!isset($result[$goodsTypeId])) {
                $result[$goodsTypeId] = [
                    'id' => $row['id'],
                    'name' => $row['group_name'],  // 主表其他字段
                    'sku_attrs' => []  // 用于存放关联的sku_attr数组
                ];
            }

            // 若存在关联的 sku_attr 数据（非NULL），则添加到子数组
            if ($row['sku_attr_id'] !== null) {
                $result[$goodsTypeId]['sku_attrs'][] = [
                    'title' => $row['attr_title'],
                    // 可添加其他sku_attr字段
                ];
            }
        }

// 可选：重置数组索引（从0开始连续）
        $result = array_values($result);

        return $result;
    }

    function getCate($type_id) {
        $sql = "select * from " . DB_PREFIX . "attribute_group where id=$type_id";
        $res = $this->db->query($sql);
        $row = $this->db->fetch_array($res);
        return $row;
    }

    function getDetail($type_id) {
        $sql = "SELECT a.id, a.spec_name as title, v.id value_id, v.option_name as name from " . DB_PREFIX . "specification as a
        left join " . DB_PREFIX . "spec_option v on a.id=v.spec_id and v.delete_time is null
         where a.group_id={$type_id} and a.delete_time is null";
        $query = $this->db->query($sql);
        $data = [];
        while ($row = $this->db->fetch_array($query)) {
            if(array_key_exists($row['id'], $data)){
                $data[$row['id']]['value'][] = [
                    'id' => $row['value_id'], 'name' => $row['name']
                ];

            }else{
                $data[$row['id']] = [
                    'id' => $row['id'],
                    'name' => $row['title'],
                    'value' => []
                ];
                if($row['value_id']){
                    $data[$row['id']]['value'][] = ['id' => $row['value_id'], 'name' => $row['name']];
                }
            }
        }
        return $data;
    }

    function deleteSkuAttr($id){
        $timestamp = time();
        $sql = "UPDATE " . DB_PREFIX . "specification set delete_time={$timestamp} where id=$id";
        $this->db->query($sql);
    }

    function deleteSkuCate($id){
        $timestamp = time();
        $sql = "UPDATE " . DB_PREFIX . "attribute_group set delete_time={$timestamp} where id=$id";
        $this->db->query($sql);
    }

    function deleteSkuValue($value_id){
        $timestamp = time();
        $sql = "UPDATE " . DB_PREFIX . "spec_option set delete_time={$timestamp} where id=$value_id";
        $this->db->query($sql);

        $sql = "select * from " . DB_PREFIX . "spec_option where id=$value_id";
        $res = $this->db->query($sql);
        $sku_value = $this->db->fetch_array($res);

        $sql = "select * from " . DB_PREFIX . "specification where id={$sku_value['spec_id']}";
        $res = $this->db->query($sql);
        $sku_attr = $this->db->fetch_array($res);
        return $sku_attr['group_id'];
    }

    function editSkuValue($id, $content) {
        $this->db->query("update " . DB_PREFIX . "spec_option set option_name='{$content}' where id=$id");
    }

    function updateSkuAttr($id, $content) {
        $this->db->query("update " . DB_PREFIX . "specification set spec_name='{$content}' where id=$id");
    }

    function updateSku($id, $content) {
        $this->db->query("update " . DB_PREFIX . "attribute_group set group_name='{$content}' where id=$id");
    }

    function addSkuValue($id, $content) {
        $sql = "INSERT INTO " . DB_PREFIX . "spec_option (`spec_id`, `option_name`) VALUES ($id, '{$content}')";
        $this->db->query($sql);
    }


    function getAllSkus(){
        $sql = "select * from " . DB_PREFIX . "product_sku order by guest_price asc";
        return $this->db->fetch_all($sql);
    }

    function getAllMinSkus(){
        $table = DB_PREFIX . "product_sku";

        // 第一步：创建临时表存储每个goods_id的最低价格
        $createTempTableSql = "CREATE TEMPORARY TABLE temp_min_prices 
                               SELECT goods_id, MIN(guest_price) as min_price
                               FROM {$table}
                               GROUP BY goods_id";

        $this->db->query($createTempTableSql);

        // 第二步：获取每个最低价格对应的最小id
        $getMinIdsSql = "CREATE TEMPORARY TABLE temp_min_ids 
                         SELECT t.goods_id, t.min_price, MIN(s.id) as min_id
                         FROM {$table} s
                         INNER JOIN temp_min_prices t ON s.goods_id = t.goods_id AND s.guest_price = t.min_price
                         GROUP BY t.goods_id, t.min_price";

        $this->db->query($getMinIdsSql);

        // 第三步：获取完整数据
        $sql = "SELECT s.* 
                FROM {$table} s
                INNER JOIN temp_min_ids t ON s.id = t.min_id";

        $res = $this->db->fetch_all($sql);

        // 清理临时表（可选）
        $this->db->query("DROP TEMPORARY TABLE IF EXISTS temp_min_prices");
        $this->db->query("DROP TEMPORARY TABLE IF EXISTS temp_min_ids");

        return $res;
    }


//    -------------------------------------



    function updateSort($sortData, $sid) {
        $Item = [];
        foreach ($sortData as $key => $data) {
            $Item[] = "$key='$data'";
        }
        $upStr = implode(',', $Item);
        $this->db->query("update $this->table set $upStr where sid=$sid");
    }

    public function addSort($data) {
        $kItem = $dItem = [];
        foreach ($data as $key => $val) {
            $kItem[] = $key;
            $dItem[] = $val;
        }
        $field = implode(',', $kItem);
        $values = "'" . implode("','", $dItem) . "'";
        $this->db->query("INSERT INTO $this->table ($field) VALUES ($values)");
        return $this->db->insert_id();
    }

    function deleteSort($sid) {
        $this->db->query("update $this->table_blog set sortid=-1 where sortid=$sid");
        $this->db->query("update $this->table set pid=0 where pid=$sid");
        $this->db->query("DELETE FROM $this->table where sid=$sid");
    }

    function getOneSortById($sid) {
        $sql = "select * from $this->table where sid=$sid";
        $res = $this->db->query($sql);
        $row = $this->db->fetch_array($res);
        $sortData = [];
        if ($row) {
            $sortData = array(
                'sortname'     => htmlspecialchars(trim($row['sortname'])),
                'alias'        => $row['alias'],
                'pid'          => $row['pid'],
                'title_origin' => $row['title'],
                'title'        => htmlspecialchars(Sort::formatSortTitle($row['title'], $row['sortname'])),
                'kw'           => htmlspecialchars($row['kw']),
                'description'  => htmlspecialchars(trim($row['description'])),
                'template'     => !empty($row['template']) ? htmlspecialchars(trim($row['template'])) : 'log_list',
                'sortimg'      => htmlspecialchars(trim($row['sortimg'])),
            );
        }
        return $sortData;
    }

    function getSortByAlias($alias) {
        if (empty($alias)) {
            return [];
        }
        $alias = addslashes($alias);
        $res = $this->db->query("SELECT * FROM $this->table WHERE alias = '$alias'");
        $row = $this->db->fetch_array($res);
        return $row;
    }

    function getSortName($sid) {
        if ($sid > 0) {
            $res = $this->db->query("SELECT sortname FROM $this->table WHERE sid = $sid");
            $row = $this->db->fetch_array($res);
            $sortName = htmlspecialchars($row['sortname']);
        } else {
            $sortName = '未分类';
        }
        return $sortName;
    }
}
