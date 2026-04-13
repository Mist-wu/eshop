<?php
/**
 * 卡密库存模型
 *
 * 表结构：
 * - em_stock: 卡密库存表
 * - em_stock_usage: 卡密使用记录表
 *
 * @package EMSHOP
 */

class Stock_Model {

    private $db;
    private $table;
    private $table_usage;
    private $db_prefix;

    // 库存状态常量
    const STATUS_AVAILABLE = 0;  // 可用
    const STATUS_USED_UP = 1;    // 已用完
    const STATUS_DISABLED = 2;   // 禁用

    public function __construct() {
        $this->db = Database::getInstance();
        $this->db_prefix = DB_PREFIX;
        $this->table = DB_PREFIX . 'stock';
        $this->table_usage = DB_PREFIX . 'stock_usage';
    }

    /**
     * 添加库存（单条）
     *
     * @param int $goods_id 商品ID
     * @param int $sku_id SKU ID（单规格为0）
     * @param string $content 卡密内容
     * @param int $max_uses 最大使用次数（1=独立卡密，>1=限次，0=无限）
     * @param string|null $batch_no 批次号
     * @return int 插入的ID
     */
    public function addStock($goods_id, $sku_id, $content, $max_uses = 1, $batch_no = null) {
        $timestamp = time();
        $batch_no = $batch_no ? "'{$batch_no}'" : 'NULL';
        $content = addslashes($content);

        $sql = "INSERT INTO {$this->table} (goods_id, sku_id, content, max_uses, batch_no, create_time)
                VALUES ({$goods_id}, {$sku_id}, '{$content}', {$max_uses}, {$batch_no}, {$timestamp})";
        $this->db->query($sql);
        return $this->db->insert_id();
    }

    /**
     * 批量添加库存
     *
     * @param int $goods_id 商品ID
     * @param int $sku_id SKU ID
     * @param array $contents 卡密内容数组
     * @param int $max_uses 最大使用次数
     * @param string|null $batch_no 批次号
     * @return int 插入数量
     */
    public function addStockBatch($goods_id, $sku_id, $contents, $max_uses = 1, $batch_no = null) {
        if (empty($contents)) {
            return 0;
        }

        $timestamp = time();
        $batch_no_sql = $batch_no ? "'{$batch_no}'" : 'NULL';

        $values = [];
        foreach ($contents as $content) {
            $content = addslashes(trim($content));
            if (!empty($content)) {
                $values[] = "({$goods_id}, {$sku_id}, '{$content}', {$max_uses}, {$batch_no_sql}, {$timestamp})";
            }
        }

        if (empty($values)) {
            return 0;
        }

        // 分批插入，每批1000条
        $batches = array_chunk($values, 1000);
        $count = 0;
        foreach ($batches as $batch) {
            $sql = "INSERT INTO {$this->table} (goods_id, sku_id, content, max_uses, batch_no, create_time) VALUES " . implode(',', $batch);
            $this->db->query($sql);
            $count += count($batch);
        }

        return $count;
    }

    /**
     * 获取可用卡密（用于发货）
     *
     * @param int $goods_id 商品ID
     * @param int $sku_id SKU ID
     * @param int $quantity 需要数量
     * @return array 可用的卡密列表
     */
    public function getAvailableStock($goods_id, $sku_id, $quantity = 1) {
        $sql = "SELECT * FROM {$this->table}
                WHERE goods_id = {$goods_id}
                AND sku_id = {$sku_id}
                AND status = " . self::STATUS_AVAILABLE . "
                AND (max_uses = 0 OR used_count < max_uses)
                ORDER BY id ASC
                LIMIT {$quantity}";
        return $this->db->fetch_all($sql);
    }

    /**
     * 使用卡密（发货时调用）
     *
     * @param int $stock_id 库存ID
     * @param int $order_id 订单ID
     * @param int $order_list_id 子订单ID
     * @return bool 是否成功
     */
    public function useStock($stock_id, $order_id, $order_list_id) {
        $timestamp = time();

        $sql = "UPDATE {$this->table} SET
                used_count = used_count + 1,
                use_time = {$timestamp},
                status = CASE
                    WHEN max_uses > 0 AND used_count + 1 >= max_uses THEN " . self::STATUS_USED_UP . "
                    ELSE " . self::STATUS_AVAILABLE . "
                END
                WHERE id = {$stock_id}
                AND status = " . self::STATUS_AVAILABLE . "
                AND (max_uses = 0 OR used_count < max_uses)";
        $this->db->query($sql);
        if ((int)$this->db->affected_rows() < 1) {
            return false;
        }

        $sql = "INSERT INTO {$this->table_usage} (stock_id, order_id, order_list_id, create_time)
                VALUES ({$stock_id}, {$order_id}, {$order_list_id}, {$timestamp})";
        $this->db->query($sql);

        return true;
    }

    /**
     * 根据ID获取库存
     */
    public function getStockById($stock_id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = {$stock_id}";
        return $this->db->once_fetch_array($sql);
    }

    /**
     * 获取商品的库存列表（分页）
     *
     * @param int $goods_id 商品ID
     * @param int $sku_id SKU ID（-1 表示全部）
     * @param int $status 状态（-1 表示全部）
     * @param int $page 页码
     * @param int $limit 每页数量
     * @param string $keyword 关键词搜索
     * @return array ['list' => [], 'total' => 0]
     */
    public function getStockList($goods_id, $sku_id = -1, $status = -1, $page = 1, $limit = 20, $keyword = '') {
        $where = "goods_id = {$goods_id}";

        if ($sku_id >= 0) {
            $where .= " AND sku_id = {$sku_id}";
        }

        if ($status >= 0) {
            $where .= " AND status = {$status}";
        }

        if (!empty($keyword)) {
            $keyword = addslashes($keyword);
            $where .= " AND content LIKE '%{$keyword}%'";
        }

        // 获取总数
        $count_sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE {$where}";
//        echo $count_sql;die;
        $total = $this->db->once_fetch_array($count_sql)['total'];

        // 获取列表
        $offset = ($page - 1) * $limit;
        $sql = "SELECT * FROM {$this->table} WHERE {$where} ORDER BY id DESC LIMIT {$offset}, {$limit}";
        $list = $this->db->fetch_all($sql);

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 获取已售卡密列表（从使用记录表查询）
     *
     * @param int $goods_id 商品ID（0表示全部）
     * @param int $page 页码
     * @param int $limit 每页数量
     * @param string $keyword 关键词搜索
     * @return array ['list' => [], 'total' => 0]
     */
    public function getSoldStockList($goods_id = 0, $page = 1, $limit = 20, $keyword = '') {
        $where = "1=1";

        if ($goods_id > 0) {
            $where .= " AND s.goods_id = {$goods_id}";
        }

        if (!empty($keyword)) {
            $keyword = addslashes($keyword);
            $where .= " AND s.content LIKE '%{$keyword}%'";
        }

        // 获取总数
        $count_sql = "SELECT COUNT(*) as total FROM {$this->table_usage} u
                      INNER JOIN {$this->table} s ON u.stock_id = s.id
                      WHERE {$where}";
        $total = $this->db->once_fetch_array($count_sql)['total'];

        // 获取列表
        $offset = ($page - 1) * $limit;
        $sql = "SELECT u.*, s.goods_id, s.sku_id, s.content, s.max_uses, s.used_count,
                       g.title as goods_title, g.type as goods_type
                FROM {$this->table_usage} u
                INNER JOIN {$this->table} s ON u.stock_id = s.id
                LEFT JOIN {$this->db_prefix}goods g ON s.goods_id = g.id
                WHERE {$where}
                ORDER BY u.create_time DESC
                LIMIT {$offset}, {$limit}";
        $list = $this->db->fetch_all($sql);

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 根据订单获取发货的卡密
     *
     * @param int $order_list_id 子订单ID
     * @return array 卡密列表
     */
    public function getStockByOrder($order_list_id) {
        $sql = "SELECT s.*, u.create_time as use_time
                FROM {$this->table_usage} u
                INNER JOIN {$this->table} s ON u.stock_id = s.id
                WHERE u.order_list_id = {$order_list_id}
                ORDER BY u.id ASC";
        return $this->db->fetch_all($sql);
    }

    /**
     * 统计商品库存情况
     *
     * @param int $goods_id 商品ID
     * @return array 各状态的数量
     */
    public function getStockStats($goods_id) {
        $sql = "SELECT
                    sku_id,
                    COUNT(CASE WHEN status = 0 AND (max_uses = 0 OR used_count < max_uses) THEN 1 END) as available,
                    COUNT(CASE WHEN status = 1 THEN 1 END) as used_up,
                    COUNT(CASE WHEN status = 2 THEN 1 END) as disabled,
                    COUNT(*) as total
                FROM {$this->table}
                WHERE goods_id = {$goods_id}
                GROUP BY sku_id";
        return $this->db->fetch_all($sql);
    }



    /**
     * 删除库存
     *
     * @param int|string $ids 库存ID或ID列表（逗号分隔）
     * @return bool
     */
    public function deleteStock($ids) {
        if (is_array($ids)) {
            $ids = implode(',', array_map('intval', $ids));
        }
        $sql = "DELETE FROM {$this->table} WHERE id IN ({$ids})";
        return $this->db->query($sql);
    }

    /**
     * 更新库存状态
     *
     * @param int $stock_id 库存ID
     * @param int $status 状态
     * @return bool
     */
    public function updateStatus($stock_id, $status) {
        $sql = "UPDATE {$this->table} SET status = {$status} WHERE id = {$stock_id}";
        return $this->db->query($sql);
    }

    /**
     * 批量更新库存状态
     *
     * @param string $ids ID列表（逗号分隔）
     * @param int $status 状态
     * @return bool
     */
    public function batchUpdateStatus($ids, $status) {
        $sql = "UPDATE {$this->table} SET status = {$status} WHERE id IN ({$ids})";
        return $this->db->query($sql);
    }

    /**
     * 更新库存内容
     *
     * @param int $stock_id 库存ID
     * @param string $content 新内容
     * @return bool
     */
    public function updateContent($stock_id, $content) {
        $content = addslashes($content);
        $sql = "UPDATE {$this->table} SET content = '{$content}' WHERE id = {$stock_id}";
        return $this->db->query($sql);
    }

    /**
     * 获取可用次数总和（用于通用卡密）
     * 计算所有可用卡密的剩余使用次数总和
     *
     * @param int $goods_id 商品ID
     * @param int $sku_id SKU ID（-1 表示全部）
     * @return int 剩余可用次数总和
     */
    public function getAvailableTotalUses($goods_id, $sku_id = -1) {
        $where = "goods_id = {$goods_id} AND status = " . self::STATUS_AVAILABLE;

        if ($sku_id >= 0) {
            $where .= " AND sku_id = {$sku_id}";
        }

        // 只计算 max_uses > 0 的卡密的剩余可用次数
        $sql = "SELECT SUM(max_uses - used_count) as total
                FROM {$this->table}
                WHERE {$where} AND max_uses > 0 AND used_count < max_uses";
        $result = $this->db->once_fetch_array($sql);
        return (int)($result['total'] ?? 0);
    }

    /**
     * 获取商品可用库存数量
     *
     * @param int $goods_id 商品ID
     * @param int $sku_id SKU ID（-1 表示全部）
     * @return int 可用数量
     */
    public function getAvailableCount($goods_id, $sku_id = -1) {
        $where = "goods_id = {$goods_id} AND status = 0 AND (max_uses = 0 OR used_count < max_uses)";

        if ($sku_id >= 0) {
            $where .= " AND sku_id = {$sku_id}";
        }

        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE {$where}";
        return (int)$this->db->once_fetch_array($sql)['count'];
    }

    /**
     * 同步 SKU 库存数量到 product_sku 表
     *
     * @param int $goods_id 商品ID
     * @param int $sku_id SKU ID
     * @param bool $useTotal 是否使用可用次数总和（通用卡密使用）
     * @return int 最新库存数量
     */
    public function syncSkuStock($goods_id, $sku_id, $useTotal = false) {
        if ($useTotal) {
            $count = $this->getAvailableTotalUses($goods_id, $sku_id);
        } else {
            $count = $this->getAvailableCount($goods_id, $sku_id);
        }
        $sql = "UPDATE {$this->db_prefix}product_sku SET stock = {$count} WHERE id = {$sku_id}";
        $this->db->query($sql);
        return $count;
    }

    /**
     * 删除使用记录（软删除场景可用）
     *
     * @param int $usage_id 使用记录ID
     * @return bool
     */
    public function deleteUsage($usage_id) {
        $sql = "DELETE FROM {$this->table_usage} WHERE id = {$usage_id}";
        return $this->db->query($sql);
    }

    /**
     * 检查卡密是否重复
     *
     * @param int $goods_id 商品ID
     * @param string $content 卡密内容
     * @return bool 是否存在
     */
    public function isContentExists($goods_id, $content) {
        $content = addslashes($content);
        $sql = "SELECT id FROM {$this->table} WHERE goods_id = {$goods_id} AND content = '{$content}' LIMIT 1";
        $result = $this->db->once_fetch_array($sql);
        return !empty($result);
    }
}
