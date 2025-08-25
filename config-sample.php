<?php
/**
 * 网站配置文件示例
 * 复制此文件为 config.php 并填写您的实际配置信息
 * 
 * 安装说明：
 * 1. 复制此文件：cp config-sample.php config.php
 * 2. 编辑 config.php 文件，填入您的实际配置
 * 3. 确保 config.php 不被版本控制跟踪
 */

// ==================================================
// 数据库配置
// ==================================================

// 数据库服务器地址（通常是 localhost 或 127.0.0.1）
define('DB_HOST', 'localhost');

// 数据库端口（MySQL 默认 3306）
define('DB_PORT', '3306');

// 数据库名称（请替换为您的数据库名）
define('DB_NAME', 'your_database_name');

// 数据库用户名
define('DB_USER', 'your_username');

// 数据库密码
define('DB_PASS', 'your_password');

// 数据库字符集（建议使用 utf8mb4）
define('DB_CHARSET', 'utf8mb4');

// ==================================================
// 网站基本信息
// ==================================================

// 网站名称
define('SITE_NAME', '我的导航网站');

// 网站描述（用于SEO和页面头部）
define('SITE_DESCRIPTION', '一个简洁实用的导航网站，收集常用工具和网站');

// 网站关键词（用于SEO，逗号分隔）
define('SITE_KEYWORDS', '导航,工具,网站,收藏,实用');

// ==================================================
// 管理员配置
// ==================================================

// 管理后台密码
// 重要：请使用强密码！可以使用以下命令生成密码哈希：
// php -r "echo password_hash('您的密码', PASSWORD_BCRYPT);"
define('ADMIN_PASSWORD_HASH', '$2y$10$请替换为您的密码哈希');

// ==================================================
// 网站设置
// ==================================================

// 默认背景图片URL
define('DEFAULT_BACKGROUND', 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=1920&h=1080&fit=crop');

// 自定义CSS（可选）
define('CUSTOM_CSS', '');

// 页脚文本
define('FOOTER_TEXT', '© 2024 我的导航网站. All rights reserved.');

// ==================================================
// 功能开关
// ==================================================

// 是否启用用户自定义背景
define('ENABLE_CUSTOM_BACKGROUND', true);

// 是否启用点击统计
define('ENABLE_CLICK_TRACKING', true);

// 每页显示链接数量
define('LINKS_PER_PAGE', 50);

// ==================================================
// 系统配置
// ==================================================

// 安装状态标记（安装完成后自动设为true）
define('SITE_INSTALLED', false);

// 调试模式（开发环境可设为true）
define('DEBUG_MODE', false);

// 时区设置
define('TIMEZONE', 'Asia/Shanghai');

// ==================================================
// 数据库连接函数
// ==================================================

/**
 * 获取数据库连接
 * @return PDO
 */
function getDBConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                $options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
            }
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            // 设置时区
            $pdo->exec("SET time_zone = '+8:00'");
            
        } catch (PDOException $e) {
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                die('数据库连接失败: ' . $e->getMessage());
            } else {
                die('数据库连接失败，请检查配置信息');
            }
        }
    }
    
    return $pdo;
}

// ==================================================
// 配置验证函数
// ==================================================

/**
 * 检查配置是否完整
 * @return array 返回配置状态数组
 */
function checkConfiguration() {
    $status = [
        'database' => [
            'host' => defined('DB_HOST') && DB_HOST !== 'localhost',
            'name' => defined('DB_NAME') && DB_NAME !== 'your_database_name',
            'user' => defined('DB_USER') && DB_USER !== 'your_username',
            'pass' => defined('DB_PASS') && DB_PASS !== 'your_password',
        ],
        'site' => [
            'name' => defined('SITE_NAME') && SITE_NAME !== '我的导航网站',
            'description' => defined('SITE_DESCRIPTION') && SITE_DESCRIPTION !== '一个简洁实用的导航网站，收集常用工具和网站',
        ],
        'admin' => [
            'password' => defined('ADMIN_PASSWORD_HASH') && strpos(ADMIN_PASSWORD_HASH, '请替换为') === false,
        ],
        'installed' => defined('SITE_INSTALLED') && SITE_INSTALLED === true,
    ];
    
    $status['all_complete'] = 
        $status['database']['host'] && 
        $status['database']['name'] && 
        $status['database']['user'] && 
        $status['database']['pass'] &&
        $status['site']['name'] &&
        $status['admin']['password'];
    
    return $status;
}

/**
 * 显示配置状态
 */
function showConfigurationStatus() {
    $status = checkConfiguration();
    
    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>配置状态检查</h3>";
    echo "<ul style='list-style: none;'>";
    
    foreach ($status as $section => $items) {
        if (is_array($items)) {
            foreach ($items as $key => $value) {
                $color = $value ? '#28a745' : '#dc3545';
                $icon = $value ? '✅' : '❌';
                echo "<li style='color: {$color};'>{$icon} {$section}.{$key}</li>";
            }
        }
    }
    
    if (!$status['all_complete']) {
        echo "<li style='color: #dc3545; font-weight: bold;'>❌ 请完成所有必要配置</li>";
    } else {
        echo "<li style='color: #28a745; font-weight: bold;'>✅ 配置完成，可以开始使用</li>";
    }
    
    echo "</ul>";
    echo "</div>";
}

// ==================================================
// 使用说明
// ==================================================

/*
使用步骤：
1. 复制此文件：
   Windows: copy config-sample.php config.php
   Linux/Mac: cp config-sample.php config.php

2. 编辑 config.php：
   - 修改数据库连接信息
   - 设置网站名称和描述
   - 生成管理员密码哈希
   - 设置 SITE_INSTALLED 为 true

3. 运行安装：
   - 访问网站根目录
   - 或使用 php db-init.php 初始化数据库

4. 开始使用：
   - 前台：index.php
   - 后台：admin/index.php
*/

// 如果需要检查配置状态，可以取消下面的注释
// showConfigurationStatus();