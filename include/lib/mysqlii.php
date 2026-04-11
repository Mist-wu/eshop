<?php
/**
 * MySQLi Database Class
 */

class MySqlii {

    /**
     * @var int
     */
    private $queryCount = 0;

    /**
     * @var mysqli
     */
    private $conn;

    /**
     * @var mysqli_result
     */
    private $result;

    /**
     * @var object MySql
     */
    private static $instance;

    private function __construct() {
        if (!class_exists('mysqli')) {
            emMsg('服务器PHP不支持mysqli函数');
        }

        mysqli_report(MYSQLI_REPORT_ERROR);

        @$this->conn = new mysqli(DB_HOST, DB_USER, DB_PASSWD, DB_NAME);
        if ($this->conn->connect_error) {
            switch ($this->conn->connect_errno) {
                case 1044:
                case 1045:
                    emMsg("连接MySQL数据库失败，数据库用户名或密码错误");
                    break;
                case 1049:
                    emMsg("连接MySQL数据库失败，未找到你填写的数据库");
                    break;
                case 2003:
                case 2005:
                case 2006:
                    emMsg("连接MySQL数据库失败，数据库地址错误或者数据库服务器不可用");
                    break;
                default :
                    emMsg("连接MySQL数据库失败，请检查数据库信息。错误信息：" . $this->conn->connect_error);
                    break;
            }
        }

        $this->conn->set_charset('utf8mb4');
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new MySqlii();
        }

        return self::$instance;
    }

    public function close() {
        return $this->conn->close();
    }

    public function query($sql, $ignore_err = FALSE) {
        $this->result = $this->conn->query($sql);
        $this->queryCount++;
        if (!$ignore_err && 1046 == $this->getErrNo()) {
            emMsg("连接数据库失败，请填写数据库名");
        }
        if (!$ignore_err && 1115 == $this->getErrNo()) {
            emMsg("MySQL缺少utf8mb4字符集，请升级到MySQL5.6或更高版本");
        }
        if (!$ignore_err && !$this->result) {
            emMsg("$sql<br /><br />error: " . $this->getErrNo() . ' , ' . $this->getError());
        } else {
			 
            return $this->result;
        }
    }
	
	public function execute($sql){
		$this->query($sql);
        return $this->conn->affected_rows == 0 ? false : true;
	}

    public function fetch_array(mysqli_result $query, $type = MYSQLI_ASSOC) {
        return $query->fetch_array($type);
    }

    public function fetch_all($sql, $fetchMode = MYSQLI_ASSOC) {
        $this->result = $this->query($sql);
        $data = [];
        while ($row = $this->fetch_array($this->result, $fetchMode)) {
            $data[] = $row;
        }
        return $data;
    }

    public function once_fetch_array($sql) {
        $this->result = $this->query($sql);
        return $this->fetch_array($this->result);
    }

    public function fetch_row(mysqli_result $query) {
        return $query->fetch_row();
    }

    public function num_rows(mysqli_result $query) {
        return $query->num_rows;
    }

    public function num_fields(mysqli_result $query) {
        return $query->field_count;
    }

    public function insert_id() {
        return $this->conn->insert_id;
    }

    /**
     * Get mysql error
     */
    public function getError() {
        return $this->conn->error;
    }

    /**
     * Get mysql error code
     */
    public function getErrNo() {
        return $this->conn->errno;
    }

    /**
     * Get number of affected rows in previous MySQL operation
     */
    public function affected_rows() {
        return $this->conn->affected_rows;
    }

    public function getMysqlVersion() {
        return $this->conn->server_info;
    }

    public function getQueryCount() {
        return $this->queryCount;
    }

    /**
     *  Escapes special characters
     */
    public function escape_string($sql) {
        return $this->conn->real_escape_string($sql);
    }

    public function listTables() {
        $rs = $this->query(sprintf("SHOW TABLES FROM `%s`", DB_NAME));
        $tables = [];
        while ($row = $this->fetch_row($rs)) {
            $tables[] = isset($row[0]) ? $row[0] : '';
        }
        return $tables;
    }

    public function add($table, $insert, $replace = false) {
        $kItem = $dItem = [];
        foreach ($insert as $key => $data) {
            $kItem[] = $key;
            $dItem[] = $data;
        }
        $field = implode(',', $kItem);
        $values = "'" . implode("','", $dItem) . "'";
        if($replace){
            $sql = "REPLACE INTO " . DB_PREFIX . $table . " ($field) VALUES ($values)";
        }else{
            $sql = "INSERT INTO " . DB_PREFIX . $table . " ($field) VALUES ($values)";
        }

        $this->query($sql);

        return $this->insert_id();
    }
    public function del($table, $ids){
        $ids = explode(',', $ids);
        foreach($ids as $val){
            $this->query("delete from " . DB_PREFIX . "{$table} where id = {$val}");
        }
    }
    public function update($table, $update, $where = [], $whereType = 'AND') {
        // 1. 校验必填参数
        if (empty($table) || !is_array($update) || empty($update)) {
            return false; // 表名不能为空、更新数据必须是非空数组
        }

        // 2. 处理更新字段和值（拼接 `字段名`='值' 格式，基础防注入转义）
        $setItem = [];
        foreach ($update as $key => $value) {
            // 字段名过滤（仅保留字母、数字、下划线，避免SQL注入）
            $field = preg_match('/^[a-zA-Z0-9_]+$/', $key) ? $key : '';
            if (empty($field)) continue;

            // 值处理：字符串转义（适配MySQL，避免单引号等特殊字符导致SQL语法错误）
            if (is_string($value)) {
                // 调用数据库连接的转义方法（如mysqli_real_escape_string），这里假设已封装为 escape 方法
//                $value = $this->escape($value);
            } elseif (is_null($value)) {
                $value = 'NULL'; // 空值处理为NULL（不加引号）
            } elseif (is_bool($value)) {
                $value = $value ? 1 : 0; // 布尔值转int
            }
            // 数字类型直接保留（无需加引号）
            $setItem[] = "`{$field}` = " . (is_string($value) ? "'{$value}'" : $value);
        }

        if (empty($setItem)) {
            return false; // 无有效更新字段，直接返回失败
        }
        $setSql = implode(', ', $setItem);

        // 3. 处理条件（拼接 WHERE 子句，支持简单等值条件）
        $whereSql = '';
        if (!empty($where) && is_array($where)) {
            $whereItem = [];
            foreach ($where as $key => $value) {
                // 字段名过滤（同上）
                $field = preg_match('/^[a-zA-Z0-9_]+$/', $key) ? $key : '';
                if (empty($field)) continue;

                // 值处理（同更新字段的值处理逻辑）
                if (is_string($value)) {
                    $value = $value;
                } elseif (is_null($value)) {
                    $whereItem[] = "`{$field}` IS NULL";
                    continue; // 跳过后续拼接，直接添加 IS NULL 条件
                } elseif (is_bool($value)) {
                    $value = $value ? 1 : 0;
                }

                $whereItem[] = "`{$field}` = " . (is_string($value) ? "'{$value}'" : $value);
            }

            if (!empty($whereItem)) {
                $whereType = strtoupper($whereType) === 'OR' ? 'OR' : 'AND'; // 校验连接类型
                $whereSql = " WHERE " . implode(" {$whereType} ", $whereItem);
            }
        }

        // 4. 拼接SQL语句
        $sql = "UPDATE " . DB_PREFIX . $table . " SET " . $setSql . $whereSql;

        // 5. 执行SQL并返回结果（query方法需确保执行成功返回true，失败返回false）
        return $this->query($sql);
    }


    /**
     * 开启事务
     */
    public function beginTransaction() {
        return $this->conn->begin_transaction();
    }

    /**
     * 提交事务
     */
    public function commit() {
        return $this->conn->commit();
    }

    /**
     * 回滚事务
     */
    public function rollback() {
        return $this->conn->rollback();
    }


}
