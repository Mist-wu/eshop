<?php
/**
 * 设备类型检测类
 * 提供更详细的设备类型检测能力
 */
class DeviceDetector {
    
    /**
     * 检测设备类型
     * 
     * @return string 返回设备类型：desktop, tablet, mobile, tv, wearable, console, bot
     */
    public static function getDeviceType() {
        $user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        
        // 1. 爬虫/机器人检测
        if (self::isBot($user_agent)) {
            return 'bot';
        }
        
        // 2. 电视设备检测
        if (self::isTV($user_agent)) {
            return 'tv';
        }
        
        // 3. 游戏主机检测
        if (self::isConsole($user_agent)) {
            return 'console';
        }
        
        // 4. 可穿戴设备检测
        if (self::isWearable($user_agent)) {
            return 'wearable';
        }
        
        // 5. 平板设备检测
        if (self::isTablet($user_agent)) {
            return 'tablet';
        }
        
        // 6. 手机设备检测
        if (self::isMobile($user_agent)) {
            return 'mobile';
        }
        
        // 7. 默认为桌面设备
        return 'desktop';
    }
    
    /**
     * 检测是否为手机设备
     */
    private static function isMobile($user_agent) {
        // 调用现有的 isMobile 函数逻辑
        return isMobile();
    }
    
    /**
     * 检测是否为平板设备
     */
    private static function isTablet($user_agent) {
        $tablet_keywords = ['ipad', 'tablet', 'kindle', 'xoom', 'nook', 'transformer'];
        
        foreach ($tablet_keywords as $keyword) {
            if (strpos($user_agent, $keyword) !== false) {
                return true;
            }
        }
        
        // Android 平板检测（不包含 'mobile' 关键字的 Android 设备）
        if (strpos($user_agent, 'android') !== false && strpos($user_agent, 'mobile') === false) {
            return true;
        }
        
        return false;
    }
    
    /**
     * 检测是否为电视设备
     */
    private static function isTV($user_agent) {
        $tv_keywords = ['tv', 'smart-tv', 'television', 'roku', 'apple tv', 'chromecast', 'fire tv'];
        
        foreach ($tv_keywords as $keyword) {
            if (strpos($user_agent, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 检测是否为游戏主机
     */
    private static function isConsole($user_agent) {
        $console_keywords = ['playstation', 'xbox', 'nintendo', 'wii', 'switch', 'ps4', 'ps5', 'xbox one'];
        
        foreach ($console_keywords as $keyword) {
            if (strpos($user_agent, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 检测是否为可穿戴设备
     */
    private static function isWearable($user_agent) {
        $wearable_keywords = ['watch', 'wearable', 'gear', 'fitbit', 'apple watch'];
        
        foreach ($wearable_keywords as $keyword) {
            if (strpos($user_agent, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 检测是否为爬虫/机器人
     */
    private static function isBot($user_agent) {
        $bot_keywords = ['bot', 'crawler', 'spider', 'scraper', 'curl', 'wget', 'googlebot', 'bingbot', 'slurp'];
        
        foreach ($bot_keywords as $keyword) {
            if (strpos($user_agent, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 获取设备类型的中文描述
     */
    public static function getDeviceTypeName($device_type) {
        $names = [
            'desktop' => '桌面电脑',
            'tablet' => '平板电脑', 
            'mobile' => '手机',
            'tv' => '智能电视',
            'console' => '游戏主机',
            'wearable' => '可穿戴设备',
            'bot' => '网络爬虫'
        ];
        
        return $names[$device_type] ?? '未知设备';
    }
}

// 使用示例：
/*
$device_type = DeviceDetector::getDeviceType();
$device_name = DeviceDetector::getDeviceTypeName($device_type);
echo "当前设备类型：$device_name ($device_type)";
*/