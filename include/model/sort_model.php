<?php
/**
 * @package ESHOP
 */

class Sort_Model {

    private $db;
    private $table;
    private $table_blog;
    private $table_goods;

    function __construct() {
        $this->table = DB_PREFIX . 'sort';
        $this->table_blog = DB_PREFIX . 'blog';
        $this->table_goods = DB_PREFIX . 'goods';
        $this->db = Database::getInstance();
    }

    /**
     * 前台 - 获取全部商品分类
     */
    function getHomeAllGoodsSort(){
        $field = "s.sid, s.sortname, COUNT(g.id) AS goods_count, s.sortimg";
        $group_by = "group by s.sid";
        $order_by = "order by s.taxis desc, sid asc";
        $where = "where s.type='goods'";

        $sql = "select {$field} from {$this->table} s 
                LEFT JOIN {$this->table_goods} g ON s.sid = g.sort_id and g.delete_time is null and g.is_on_shelf = 1";
        $join = "";
        $sql .= " {$join} {$where} {$group_by} {$order_by}";
//        echo $sql;
        $res = $this->db->fetch_all($sql);
        foreach($res as &$val){
            $val['sort_url'] = Url::sort($val['sid']);
        }
        return $res;
    }

    function getSorts($type) {
        $sorts = [];
        $rows = [];
        $query = $this->db->query("SELECT * FROM $this->table where `type`='{$type}' ORDER BY taxis desc, sid asc");
        while ($row = $this->db->fetch_array($query)) {
            $rows[] = $row;
            $data = $this->db->once_fetch_array("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "blog WHERE sortid=" . $row['sid'] . " AND hide='n' AND checked='y' AND type='blog'");
            $logNum = $data['total'];
            $sortData = array(
                'type' => $row['type'],
                'lognum'       => $logNum,
                'sortname'     => htmlspecialchars($row['sortname']),
                'alias'        => $row['alias'],
                'description'  => htmlspecialchars($row['description']),
                'kw'           => htmlspecialchars($row['kw']),
                'title_origin' => $row['title'],
                'title'        => htmlspecialchars(Sort::formatSortTitle($row['title'], $row['sortname'])),
                'sid'          => (int)$row['sid'],
                'taxis'        => (int)$row['taxis'],
                'pid'          => (int)$row['pid'],
                'template'     => htmlspecialchars($row['template']),
                'sortimg'      => htmlspecialchars($row['sortimg'])
            );
            if ($sortData['pid'] == 0) {
                $sortData['children'] = [];
            }
            $sorts[$row['sid']] = $sortData;
        }
        foreach ($rows as $row) {
            $pid = (int)$row['pid'];
            if ($pid === 0) {
                continue;
            }
            if (!isset($sorts[$pid])) {
                continue;
            }
            if (!isset($sorts[$pid]['children']) || !is_array($sorts[$pid]['children'])) {
                $sorts[$pid]['children'] = [];
            }
            $sorts[$pid]['children'][] = (int)$row['sid'];
        }
        return $sorts;
    }

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
        $this->db->query("update $this->table_goods set sort_id=-1 where sort_id={$sid}");
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

    /**
     * 获取下级分类
     */
    function getChildren($sid) {
        $children = [];
        $query = $this->db->query("SELECT * FROM $this->table WHERE pid = $sid");
        while ($row = $this->db->fetch_array($query)) {
            $children[] = $row;
        }
        return $children;
    }


    /**
     * 用户获取商品分类
     */
    function getGoodsSortForHome($post = []){
        $goods_title = isset($post['goods_title']) ? trim($post['goods_title']) : '';
        $include_empty = isset($post['includeEmpty']) ? filter_var($post['includeEmpty'], FILTER_VALIDATE_BOOLEAN) : true;

        $goods_where = "g.delete_time IS NULL AND g.is_on_shelf = 1";
        if (!empty($goods_title)) {
            $goods_title = $this->db->escape_string($goods_title);
            $goods_where .= " AND g.title LIKE '" . $goods_title . "%'";
        }

        $goods_count_sql = "SELECT g.sort_id, COUNT(g.id) AS goods_count
                            FROM {$this->table_goods} g
                            WHERE {$goods_where}
                            GROUP BY g.sort_id";

        $sql = "SELECT 
                    s.sid AS sort_id, s.pid,
                    s.sortname,
                    s.sortimg,
                    (COALESCE(MAX(sc.goods_count), 0) + COALESCE(SUM(cc.goods_count), 0)) AS goods_count
                FROM {$this->table} s
                LEFT JOIN ({$goods_count_sql}) sc ON sc.sort_id = s.sid
                LEFT JOIN {$this->table} c ON c.pid = s.sid AND c.type = 'goods'
                LEFT JOIN ({$goods_count_sql}) cc ON cc.sort_id = c.sid
                WHERE s.type = 'goods'
                GROUP BY s.sid
                ORDER BY s.taxis DESC, s.sid ASC";

        if (!$include_empty) {
            $sql = str_replace("GROUP BY s.sid", "GROUP BY s.sid HAVING goods_count > 0", $sql);
        }

        $data = $this->db->fetch_all($sql);
        foreach ($data as &$item) {
            $item['goods_count'] = (int)$item['goods_count'];
        }
        return $data;
    }

}
