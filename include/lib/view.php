<?php
/**
 * View control
 * @package ESHOP
 */

class View {
    public static function getView($template, $ext = '.php') {
        $nonce_templet = isMobile() ? Option::get('nonce_templet_tel') : Option::get('nonce_templet');
        if (strpos(TEMPLATE_PATH, 'em_null_tpl') || empty($nonce_templet)) {
            emMsg('当前未启用任何模板，请登录后台启用模板。 错误码：em_template', EM_URL . 'admin/template.php');
        }
        if (!is_dir(TEMPLATE_PATH)) {
            emMsg('当前使用的模板已被删除或损坏，请登录后台更换其他模板。 错误码：em_one_template', EM_URL . 'admin/template.php');
        }
        return TEMPLATE_PATH . $template . $ext;

    }

    public static function getHeaderView($template, $ext = 'header.php') {
        $template_dir = $template == 'common' ? COMMON_TEMPLATE_PATH : TEMPLATE_PATH;
        // echo $template_dir;die;
        if (!is_dir($template_dir)) {
            emMsg('当前使用的模板已被删除或损坏，请登录后台更换其他模板。 错误码：em_one_header', EM_URL . 'admin/template.php');
        }
        return $template_dir . $ext;
    }

    public static function getFooterView($template, $ext = 'footer.php') {
        $template_dir = $template == 'common' ? COMMON_TEMPLATE_PATH : TEMPLATE_PATH;
        if (!is_dir($template_dir)) {
            emMsg('当前使用的模板已被删除或损坏，请登录后台更换其他模板。 错误码：em_one_footer', EM_URL . 'admin/template.php');
        }
        return $template_dir . $ext;
    }

    public static function getCommonView($template, $ext = '.php') {
        if (!is_dir(COMMON_TEMPLATE_PATH)) {
            emMsg('当前使用的模板已被删除或损坏，请登录后台更换其他模板。 错误码：em_common_template', EM_URL . 'admin/template.php');
        }
        return COMMON_TEMPLATE_PATH . $template . $ext;
    }

    public static function getBlogView($template, $ext = '.php') {
        if (!is_dir(BLOG_TEMPLATE_PATH)) {
            emMsg('当前使用的博客模板已被删除或损坏，请登录后台更换其他模板。');
        }
        return BLOG_TEMPLATE_PATH . $template . $ext;
    }

    public static function getAdmView($template, $ext = '.php') {
        if (!is_dir(ADMIN_TEMPLATE_PATH)) {
            emMsg('后台模板已损坏', EM_URL);
        }
        return ADMIN_TEMPLATE_PATH . $template . $ext;
    }

    public static function getUserView($template, $ext = '.php') {
        if (!is_dir(USER_TEMPLATE_PATH)) {
            emMsg('后台模板已损坏', EM_URL);
        }
        return USER_TEMPLATE_PATH . $template . $ext;
    }

    public static function isTplExist($template, $ext = '.php') {
        if (file_exists(TEMPLATE_PATH . $template . $ext)) {
            return true;
        }
        return false;
    }

    public static function output() {
        $content = ob_get_clean();
        ob_start();
        echo $content;
        ob_end_flush();
        exit;
    }

}
