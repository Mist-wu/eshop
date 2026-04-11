<?php
/**
 * Service: Api
 */

class Api {

    private static $resp = '';
    private static $post = [];

    private static $db = null;
    private static $db_prefix = null;

    private static $user_id = 0;
    private static $user = [];

    private static $timestamp = null;

    public static function init(){
        self::$db = Database::getInstance();
        self::$db_prefix = DB_PREFIX;
        self::$timestamp = time();
    }

    public static function api_init(){
        self::init();
        self::$resp = 'api';
    }

    public static function local_init(){
        self::init();
        self::$resp = 'local';
        self::$user_id = UID;
        $db_prefix = self::$db_prefix;
        if(self::$user_id > 0){
            $sql = "select * from {$db_prefix}user where uid=" . UID . " limit 1";
            self::$user = self::$db->once_fetch_array($sql);
        }

    }




    /**
     * 获取商品分类
     */
    public static function getSortAll() {
        $sortModel = new Sort_Model();
        $res = $sortModel->getHomeAllGoodsSort();
        return self::success($res);
    }



    /**
     * 返回数据
     */
    private static function success($data){
        if(self::$resp == 'api'){
            Ret::success('success', $data);
        }else{
            return $data;
        }
    }
    private static function error($msg){
        if(self::$resp == 'api'){
            Ret::error($msg);
        }else{
            emMsg($msg);
        }
    }
}
