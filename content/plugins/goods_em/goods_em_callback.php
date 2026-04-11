<?php
/**
 * EMSHOP 同系统对接插件 - 生命周期回调
 */

defined('EM_ROOT') || exit('access denied!');

/**
 * 开启插件时执行该函数
 */
function callback_init()
{
    $db = Database::getInstance();
    $prefix = DB_PREFIX;

    // 创建站点配置表
    $sql = "CREATE TABLE IF NOT EXISTS `{$prefix}em_site` (
        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '站点ID',
        `sitename` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '站点名称',
        `domain` VARCHAR(255) NOT NULL COMMENT '站点域名',
        `app_id` VARCHAR(100) NOT NULL COMMENT '用户ID（对方系统的用户ID）',
        `app_key` VARCHAR(255) NOT NULL COMMENT '用户Token',
        `balance` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT '账户余额',
        `create_time` BIGINT(16) NULL DEFAULT NULL COMMENT '创建时间',
        `update_time` BIGINT(16) NULL DEFAULT NULL COMMENT '更新时间',
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_domain_appid` (`domain`(191), `app_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='EM同系统对接站点表';";
    $db->query($sql);

    // 创建商品映射表
    $sql = "CREATE TABLE IF NOT EXISTS `{$prefix}em_goods` (
        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '映射ID',
        `site_id` INT(11) UNSIGNED NOT NULL COMMENT '站点ID',
        `goods_id` INT(11) UNSIGNED NOT NULL COMMENT '本地商品ID',
        `remote_goods_id` INT(11) UNSIGNED NOT NULL COMMENT '远程商品ID',
        `name` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '商品名称',
        `remote_type` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '远程商品类型',
        `config` TEXT NULL COMMENT '商品配置（JSON）',
        `create_time` BIGINT(16) NULL DEFAULT NULL COMMENT '创建时间',
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_site_remote` (`site_id`, `remote_goods_id`),
        KEY `idx_goods_id` (`goods_id`),
        KEY `idx_site_id` (`site_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='EM同系统对接商品映射表';";
    $db->query($sql);

}

/**
 * 删除插件时执行该函数
 */
function callback_rm() {

}

/**
 * 更新插件时执行该函数
 */
function callback_up() {
    // 执行初始化确保表结构是最新的
    callback_init();
}
