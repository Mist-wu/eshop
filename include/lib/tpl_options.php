<?php
/*
Plugin Name: 模板设置
Version: 1.0.0
Plugin URL:
Description: 为模板增加丰富的设置功能，详见官网文档-模板开发。
Author: 驳手
Author URL:
*/

defined('EM_ROOT') || exit('access denied!');

/**
 * 模板设置类
 */
class TplOptions
{

    //插件标识
    const ID = 'tpl_options';
    const NAME = '模板设置';
    const VERSION = '1.0.0';

    //数据表前缀
    private $_prefix = 'tpl_options_';

    //数据表
    private $_tables = array(
        'data',
    );


    //实例
    private static $_instance;

    //是否初始化
    private $_inited = false;

    //模板参数
    private $_templateOptions;

    

    //数据库连接实例
    private $_db;

    //插件模板目录
    private $_view;

 

    //页面
    private $_pages;

    //文章
    private $_posts;

    /**
     * 单例入口
     * @return TplOptions
     */
    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * 私有构造函数，保证单例
     */
    private function __construct() {}

    /**
     * 初始化函数
     * @return void
     */
    public function init()
    {
        if ($this->_inited === true) {
            return;
        }
        $this->_inited = true;

        //初始化各个数据表名
        $tables = array();
        foreach ($this->_tables as $name => $table) {
            $tables[$table] = $this->getTableName($table);
        }
        $this->_tables = $tables;

        
    }

    

    

    /**
     * 获取数据表
     * @param mixed $table 表名缩写，可选，若不设置则返回所有表，否则返回对应表
     * @return mixed 返回数组或字符串
     */
    public function getTable($table = null)
    {
        return $table === null ? $this->_tables : (isset($this->_tables[$table]) ? $this->_tables[$table] : '');
    }

    /**
     * 获取数据表名
     * @param string $table 表名缩写
     * @return string 表全名
     */
    private function getTableName($table)
    {
        return DB_PREFIX . $this->_prefix . $table;
    }

    /**
     * 获取模板参数数据，默认获取当前模板
     * @param mixed $template 模板名称，可选
     * @return array 模板参数
     */
    public function getTemplateOptions($template = null) {


            if ($template === null) {
                $template = isMobile() ? Option::get('nonce_templet_tel') : Option::get('nonce_templet');
            }

            if (isset($this->_templateOptions[$template])) {
                return $this->_templateOptions[$template];
            }
            $_data = $this->queryAll('data', array(
                'template' => $template,
            ));
            $templateOptions = array();

            foreach ($_data as $row) {
                extract($row);
                $data = unserialize($data);
                $templateOptions[$name] = $data;
            }

        return $templateOptions;
    }

    /**
     * 设置模板参数数据
     * @param string $template 模板名称
     * @param array $options 模板参数
     * @return boolean
     */
    public function setTemplateOptions($template, $options)
    {
        if ($options === array()) {
            return true;
        }
        $data = array();
        foreach ($options as $name => $option) {
            $data[] = array(
                'template' => $template,
                'name'     => $name,
                'depend'   => $option['depend'],
                'data'     => serialize($option['data']),
            );
        }
        return $this->insert('data', $data, true);
    }

    

    /**
     * 获取数据库连接
     */
    public function getDb()
    {
        if ($this->_db !== null) {
            return $this->_db;
        }
        $this->_db = Database::getInstance();
        return $this->_db;
    }

    /**
     * 从表中查询出所有数据
     * @param string $table 表名缩写
     * @param mixed $condition 字符串或数组条件
     * @return array 结果数据
     */
    private function queryAll($table, $condition = '', $select = '*')
    {
        $table = $this->getTable($table) ? $this->getTable($table) : DB_PREFIX . $table;
        $subSql = $this->buildQuerySql($condition);
        $sql = "SELECT $select FROM `$table`";
        if ($subSql) {
            $sql .= " WHERE $subSql";
        }
        $query = $this->getDb()->query($sql);
        $data = array();
        while ($row = $this->getDb()->fetch_array($query)) {
            $data[] = $row;
        }
        return $data;
    }

    /**
     * 将数据插入数据表
     * @param string $table 表名缩写
     * @param array $data 数据
     * @return bool 结果数据
     */
    public function insert($table, $data, $replace = false)
    {
        $table = $this->getTable($table);
        $subSql = $this->buildInsertSql($data);
        if ($replace) {
            $sql = "REPLACE INTO `$table`";
        } else {
            $sql = "INSERT INTO `$table`";
        }
        $sql .= $subSql;
        return $this->getDb()->query($sql) !== false;
    }

    /**
     * 根据条件构造WHERE子句
     * @param mixed $condition 字符串或数组条件
     * @return string 根据条件构造的查询子句
     */
    private function buildQuerySql($condition)
    {
        if (is_string($condition)) {
            return $condition;
        }
        $subSql = array();
        foreach ($condition as $key => $value) {
            if (is_string($value)) {
                if (class_exists('mysqli', FALSE)) {
                    $value = $this->getDb()->escape_string($value);
                }
                $subSql[] = "(`$key`='$value')";
            } elseif (is_array($value)) {
                $subSql[] = "(`$key` IN (" . $this->implodeSqlArray($value) . '))';
            }
        }
        return implode(' AND ', $subSql);
    }

    /**
     * 根据数据构造INSERT/REPLACE INTO子句
     * @param array $data 数据
     * @return string 根据数据构造的子句
     */
    private function buildInsertSql($data)
    {
        $subSql = array();
        if (array_key_exists(0, $data)) {
            $keys = array_keys($data[0]);
        } else {
            $keys = array_keys($data);
            $data = array(
                $data
            );
        }
        foreach ($data as $key => $value) {
            $subSql[] = '(' . $this->implodeSqlArray($value) . ')';
        }
        $subSql = implode(',', $subSql);
        $keys = '(`' . implode('`,`', $keys) . '`)';
        $subSql = "$keys VALUES $subSql";
        return $subSql;
    }

    /**
     * 将数组展开为可以供SQL使用的字符串
     * @param array $data 数据
     * @return string 形如('value1', 'value2')的字符串
     */
    private function implodeSqlArray($data)
    {
        return implode(',', array_map(function ($val) {
            if (class_exists('mysqli', FALSE)) {
                $val = $this->getDb()->escape_string($val);
            }
            return "'" . $val . "'";
        }, $data));
    }

    
    

    private function buildImageUrl($path)
    {
        if (empty($path)) {
            return '';
        }
        if (is_array($path)) {
            return array_map(array(
                $this,
                'buildImageUrl'
            ), $path);
        }
        return preg_match('{(https?|ftp)://}i', $path) ? $path : EM_URL . $path;
    }

    /**
     * 获取模板文件
     * @param string $view 模板名字
     * @param string $ext 模板后缀，默认为.php
     * @return string 模板文件全路径
     */
    public function view($view, $ext = '.php')
    {
        return $this->_view . $view . $ext;
    }

    /**
     * 根据参数构造url
     * @param array $params
     * @return string
     */
    public function url($params = array())
    {
        $baseUrl = './plugin.php?plugin=' . self::ID;
        $url = http_build_query($params);
        if ($url === '') {
            return $baseUrl;
        } else {
            return $baseUrl . '&' . $url;
        }
    }

    /**
     * 以json输出数据并结束
     * @param mixed $data
     * @return void
     */
    public function jsonReturn($data)
    {
        ob_clean();
        echo json_encode($data);
        exit;
    }

    /**
     * 从数组里取出数据，支持key.subKey的方式
     * @param array $array
     * @param string $key
     * @param mixed $default 默认值
     * @return mixed
     */
    public function arrayGet($array, $key, $default = null)
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }
        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }
        return $array;
    }

    /**
     * 魔术方法，用以获取模板设置
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        $object = new stdClass();
        $object->name = $name;
        $object->data = $this->arrayGet($this->getTemplateOptions(), $name);
        doAction('tpl_options_get', $object);
        return $object->data;

    }
}

function _g($name = null) {

    if ($name === null) {
        return TplOptions::getInstance()->getTemplateOptions();
    } else {

        return TplOptions::getInstance()->$name;
    }
}

function _em($name = null)
{
    if ($name === null) {
        return TplOptions::getInstance()->getTemplateOptions();
    } else {
        return TplOptions::getInstance()->$name;
    }
}

function _getBlock($name = null, $type = 'content')
{
    $target = TplOptions::getInstance()->$name;
    $arr = [];
    if (!is_array($target))
        return $arr;
    if (empty($target[trim($type)]))
        return $arr;
    if (trim($type) != 'title' && trim($type) != 'content')
        return $arr;
    $result = array_filter($target, 'is_array');
    if (count($result) == count($target)) {
        foreach ($target[$type] as $val) {
            $arr[] = $val;
        }
    }
    return $arr;
}


