<?php

class Register {

    const EMKEY_LEN = 32;

    public static function isRegLocal() {
        return true;
    }

    public static function getRegType() {
        return 0;
    }

    public static function isRegServer() {
        return true;
    }

    public static function doReg($emkey) {
        return ['code' => 200, 'data' => 0];
    }

    public static function verifyEmKey($emkey) {
        return true;
    }

    public static function verifyDownload($plugin_id) {
        return 1;
    }

    public static function clean($emkey) {
        return true;
    }
}
