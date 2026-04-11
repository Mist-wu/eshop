<?php
/**
 * 会员等级模型
 */

class User_Tier_Model {

    private $db;
    private $table;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->table = DB_PREFIX . 'user_tier';
    }

    public function getTiers($page = 1) {
        if ($page) {
            $perpage_num = Option::get('admin_article_perpage_num');
            $startId = ($page - 1) * $perpage_num;
            $limit = "LIMIT $startId, " . $perpage_num;
        }
        $res = $this->db->query("SELECT * FROM $this->table order by id asc $limit");
        $tiers = [];
        while ($row = $this->db->fetch_array($res)) {
            $tiers[] = $row;
        }
        return $tiers;
    }

    public function getTiersAll() {
        $res = $this->db->query("SELECT * FROM $this->table order by id asc");
        $tiers = [];
        while ($row = $this->db->fetch_array($res)) {
            $tiers[] = $row;
        }
        return $tiers;
    }

    public function getTierById($id) {
        $sql = "SELECT * FROM $this->table WHERE id = " . intval($id);
        return $this->db->once_fetch_array($sql);
    }

    public function add($tier_name, $discount = 0) {
        $tier_name = addslashes($tier_name);
        $sql = "INSERT INTO $this->table (tier_name, discount) VALUES('{$tier_name}', {$discount})";
        $this->db->query($sql);
        return $this->db->insert_id();
    }

    public function edit($id, $tier_name, $discount = 0) {
        $tier_name = addslashes($tier_name);
        $this->db->query("UPDATE $this->table SET tier_name='{$tier_name}', discount={$discount} WHERE id=$id");
    }

    public function del($id) {
        $this->db->query("DELETE FROM $this->table WHERE id=$id");
    }

    public function getTierCount() {
        $sql = "SELECT COUNT(*) AS total FROM {$this->table}";
        $res = $this->db->once_fetch_array($sql);
        return $res['total'];
    }

    /**
     * 兼容旧方法名
     */
    public function getMembersAll() {
        return $this->getTiersAll();
    }

    public function getMembers($page = 1) {
        return $this->getTiers($page);
    }

    public function getMemberCount() {
        return $this->getTierCount();
    }
}
