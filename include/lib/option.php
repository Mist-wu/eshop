<?php

class Option {

    const EM_VERSION = '1.2.72';
    const EM_VERSION_TIMESTAMP = 1272;
    const UPLOADFILE_PATH = '../content/uploadfile/';
    const UPLOADFILE_FULL_PATH = EM_ROOT . '/content/uploadfile/';

    static function get($option) {
        $CACHE = Cache::getInstance();
        $options_cache = $CACHE->readCache('options');

        switch ($option) {
            case 'active_plugins':
            case 'widget_title':
            case 'custom_widget':
            case 'widgets1':
                if (!empty($options_cache[$option])) {
                    return @unserialize($options_cache[$option]);
                }
                return [];
            case 'blogurl':
                if ($options_cache['detect_url'] == 'y') {
                    return realUrl();
                }
                return $options_cache['blogurl'];
            case 'posts_name':
                if (empty($options_cache['posts_name'])) {
                    return '文章';
                }
            case 'home_bulletin':
            case 'roll_bulletin':
                return isset($options_cache[$option]) ? $options_cache[$option] : '';
            default:
                return isset($options_cache[$option]) ? $options_cache[$option] : '';
        }
    }

    static function getRoutingTable() {
        return [
            [
                'model'  => 'calendar',
                'method' => 'generate',
                'reg_0'  => '|^.*/\?action=cal|',
            ],
            [
                'model'  => 'log_Controller',
                'method' => 'displayContent',
                'reg_0'  => '|^.*/\?(blog)=(\d+)(&(comment-page)=(\d+))?([\?&].*)?$|',
                'reg_1'  => '|^.*/(blog)-(\d+)\.html(/(comment-page)-(\d+))?/?([\?&].*)?$|',
                'reg_2'  => '|^.*/(blog)/(\d+)(/(comment-page)-(\d+))?/?$|',
                'reg_3'  => '|^.*/(blog)/(\d+)(/(comment-page)-(\d+))?/?$|',
                'reg_4'  => '|^/?!/blogs([^\./\?=]+)(\.html)?(/(comment-page)-(\d+))?/?([\?&].*)?$|',
            ],
            [
                'model'  => 'Cart_Controller',
                'method' => 'display',
                'reg_0'  => '|^.*/\?action=cart|',
            ],
            [
                'model'  => 'Pay_Controller',
                'method' => 'index',
                'reg_0'  => '|^.*/\?action=pay|',
                'reg_1'  => '|/post-\d+\.html\?action=pay|',
                'reg_2'  => '|/post/\d+\?action=pay|',
            ],
            [
                'model'  => 'Pay_Controller',
                'method' => 'isPay',
                'reg_0'  => '|^.*/\?action=is_pay|',
            ],
            [
                'model'  => 'Pay_Controller',
                'method' => 'notify',
                'reg_0'  => '|^.*/action\/notify|',
            ],
            [
                'model'  => 'Pay_Controller',
                'method' => '_return',
                'reg_0'  => '|^.*/action\/return|',
            ],
            [
                'model'  => 'Goods_Controller',
                'method' => 'displayContent',
                'reg_0'  => '|^.*/\?(post)=(\d+)(&(comment-page)=(\d+))?([\?&].*)?$|',
                'reg_1'  => '|^.*/(post)-(\d+)\.html(/(comment-page)-(\d+))?/?([\?&].*)?$|',
                'reg_2'  => '|^.*/(post)/(\d+)(/(comment-page)-(\d+))?/?$|',
                'reg_3'  => '|^.*/(buy)/(\d+)(/(comment-page)-(\d+))?/?$|',
                'reg_4'  => '|^/?!/posts([^\./\?=]+)(\.html)?(/(comment-page)-(\d+))?/?([\?&].*)?$|',
            ],
            [
                'model'  => 'Record_Controller',
                'method' => 'display',
                'reg_0'  => '|^.*/\?(record)=(\d{6,8})(&(page)=(\d+))?([\?&].*)?$|',
                'reg'    => '|^.*/(record)/(\d{6,8})/?((page)/(\d+))?/?([\?&].*)?$|',
            ],
            [
                'model'  => 'Sort_Controller',
                'method' => 'display',
                'reg_0'  => '|^.*/\?(sort)=(\d+)(&(page)=(\d+))?([\?&].*)?$|',
                'reg'    => '|^.*/(sort)/([^\./\?=]+)/?((page)/(\d+))?/?([\?&].*)?$|',
            ],
            [
                'model'  => 'Blogsort_Controller',
                'method' => 'display',
                'reg_0'  => '|^.*/\?(blogsort)=(\d+)(&(page)=(\d+))?([\?&].*)?$|',
                'reg'    => '|^.*/(blogsort)/([^\./\?=]+)/?((page)/(\d+))?/?([\?&].*)?$|',
            ],
            [
                'model'  => 'Tag_Controller',
                'method' => 'display',
                'reg_0'  => '|^.*/\?(tag)=([^&]+)(&(page)=(\d+))?([\?&].*)?$|',
                'reg'    => '|^.*/(tag)/([^/?]+)/?((page)/(\d+))?/?([\?&].*)?$|',
            ],
            [
                'model'  => 'Author_Controller',
                'method' => 'display',
                'reg_0'  => '|^.*/\?(author)=(\d+)(&(page)=(\d+))?([\?&].*)?$|',
                'reg'    => '|^.*/(author)/(\d+)/?((page)/(\d+))?/?([\?&].*)?$|',
            ],
            [
                'model'  => 'Goods_Controller',
                'method' => 'display',
                'reg_0'  => '|^.*/\?(page)=(\d+)([\?&].*)?$|',
                'reg'    => '|^.*/(page)/(\d+)/?([\?&].*)?$|',
            ],
            [
                'model'  => 'Search_Controller',
                'method' => 'display',
                'reg_0'  => '|^.*/\?(keyword)=([^/&]+)(&(page)=(\d+))?([\?&].*)?$|',
            ],
            [
                'model'  => 'Comment_Controller',
                'method' => 'addComment',
                'reg_0'  => '|^.*/\?(action)=(addcom)([\?&].*)?$|',
            ],
            [
                'model'  => 'Plugin_Controller',
                'method' => 'loadPluginShow',
                'reg_0'  => '|^.*/\?(plugin)=([\w\-]+).*([\?&].*)?$|',
            ],
            [
                'model'  => 'Plugin_Controller',
                'method' => 'loadPluginShow',
                'reg_0'  => '|\/(plugin)/([\w\-]+)|',
            ],
            [
                'model'  => 'Api_Controller',
                'method' => 'starter',
                'reg_0'  => '|^.*/\?(rest-api)=(\w+)([\?&].*)?$|',
            ],
            [
                'model'  => 'Download_Controller',
                'method' => 'index',
                'reg_0'  => '|^.*/\?(resource_alias)=(\w+)$|',
            ],
            [
                'model'  => 'Goods_Controller',
                'method' => 'display',
                'reg_0'  => '|^/?([\?&].*)?$|',
            ],
            [
                'model'  => 'Log_Controller',
                'method' => 'display',
                'reg_0'  => '|\/(blog)(?:\/([\w\-]+))?|'
            ]
        ];
    }

    static function getAll() {
        $CACHE = Cache::getInstance();
        $options_cache = $CACHE->readCache('options');
        $options_cache['site_title'] = $options_cache['site_title'] ?: $options_cache['blogname'];
        $options_cache['site_description'] = $options_cache['site_description'] ?: $options_cache['bloginfo'];

        return $options_cache;
    }

    static function getAttType() {
//        d(explode(',', self::get('att_type')));
        return explode(',', self::get('att_type'));
    }

    static function getAttMaxSize() {
        return self::get('att_maxsize') * 1024;
    }

    static function getAdminAttType() {
        if (defined('UPLOAD_ATT_TYPE')) {
            return explode(',', UPLOAD_ATT_TYPE);
        } else {
            return [
                'rar', 'zip', '7z', 'gz',
                'gif', 'jpg', 'jpeg', 'png', 'webp',
                'txt', 'pdf', 'docx', 'doc', 'xls', 'xlsx', 'key', 'ppt', 'pptx',
                'mp4', 'mp3', 'mkv', 'webm', 'avi',
            ];
        }
    }

    static function getAdminAttMaxSize() {
        return (defined('UPLOAD_MAX_SIZE') ? UPLOAD_MAX_SIZE : 2097152) * 1024;
    }

    static function getWidgetTitle() {
        return [
            'blogger'     => '个人资料',
            'calendar'    => '日历',
            'tag'         => '标签',
            'twitter'     => '微语',
            'sort'        => '分类',
            'archive'     => '存档',
            'newcomm'     => '最新评论',
            'newlog'      => '最新文章',
            'hotlog'      => '热门文章',
            'link'        => '友情链接',
            'search'      => '搜索',
            'custom_text' => '自定义组件'
        ];
    }

    static function getDefWidget() {
        return ['blogger', 'newcomm', 'link', 'search'];
    }

    static function getDefPlugin() {
        return [
            'tips/tips.php',
            'adm_home/adm_home.php',
            'goods_once/goods_once.php',
            'goods_general/goods_general.php',
            'goods_service/goods_service.php',
        ];
    }

    /**
     * Update configuration options
     * @param $name
     * @param $value
     * @param $isSyntax is the update value is an expression
     */
    static function updateOption($name, $value, $isSyntax = false) {
        $DB = Database::getInstance();
        $value = $isSyntax ? $value : "'$value'";
        $sql = 'INSERT INTO ' . DB_PREFIX . "options (option_name, option_value) values ('$name', $value) ON DUPLICATE KEY UPDATE option_value=$value, option_name='$name'";
        $DB->query($sql);
    }
}
