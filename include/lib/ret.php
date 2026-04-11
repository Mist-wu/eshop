<?php
/**
 * @package EMSHOP
 */

class Ret {
    public static function success($msg = 'success', $data = '') {
        header('Content-Type: application/json; charset=UTF-8');
        $result = [
            'code' => 200,
            'msg'  => $msg,
            'data' => $data
        ];
        die(json_encode($result, JSON_UNESCAPED_UNICODE));
    }


    public static function error($msg, $data = []) {
        header('Content-Type: application/json; charset=UTF-8');
        $result = [
            'code' => 400,
            'msg'  => $msg,
            'data' => $data
        ];
        die(json_encode($result, JSON_UNESCAPED_UNICODE));
    }


}
