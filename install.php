<?php
/**
 * 网站安装向导
 * 允许用户通过浏览器配置网站基础信息、管理密码和数据库连接
 */

// 定义配置文件路径
$configFile = __DIR__ . '/admin/config.php';
$installed = false;
$error = '';
$success = '';

// 检查是否已安装
if (file_exists($configFile)) {
    require_once $configFile;
    if (defined('SITE_INSTALLED') && SITE_INSTALLED) {
        // 安装完成后，直接阻止访问
        header('HTTP/1.1 403 Forbidden');
        header('Content-Type: text/html; charset=utf-8');
        
        // 显示友好的错误页面
        echo '<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>安装已完成</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        .message-box {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 100%;
            padding: 40px;
            text-align: center;
        }
        .icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        h1 {
            color: #333;
            margin-bottom: 15px;
            font-size: 28px;
        }
        p {
            color: #666;
            margin-bottom: 25px;
            font-size: 16px;
            line-height: 1.6;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin: 5px;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            border: 1px solid #ffeaa7;
        }
    </style>
</head>
<body>
    <div class="message-box">
        <div class="icon">✅</div>
        <h1>网站已成功安装</h1>
        <p>您的导航网站已经安装完成！现在可以开始使用网站了。</p>
        
        <div>
            <a href="index.php" class="btn">访问网站</a>
            <a href="admin/index.php" class="btn">管理后台</a>
        </div>
        
        <div class="warning">
            <strong>安全提醒：</strong><br>
            为了安全起见，建议立即删除或重命名 <strong>install.php</strong> 文件。
        </div>
    </div>
</body>
</html>';
        exit();
    }
}

// 处理安装表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$installed) {
    try {
        // 验证输入
        $site_name = trim($_POST['site_name'] ?? '');
        $site_description = trim($_POST['site_description'] ?? '');
        $admin_password = $_POST['admin_password'] ?? '';
        $db_host = trim($_POST['db_host'] ?? 'localhost');
        $db_port = trim($_POST['db_port'] ?? '3306');
        $db_name = trim($_POST['db_name'] ?? '');
        $db_user = trim($_POST['db_user'] ?? '');
        $db_pass = $_POST['db_pass'] ?? '';
        $db_charset = 'utf8mb4';

        if (empty($site_name)) {
            throw new Exception('网站名称不能为空');
        }
        if (empty($admin_password) || strlen($admin_password) < 6) {
            throw new Exception('管理密码不能为空且至少6位');
        }
        if (empty($db_name) || empty($db_user)) {
            throw new Exception('数据库信息不能为空');
        }

        // 测试数据库连接
        $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset={$db_charset}";
        $pdo = new PDO($dsn, $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        // 创建数据表
        $sql = "
        CREATE TABLE IF NOT EXISTS categories (
            id INT NOT NULL AUTO_INCREMENT,
            name VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            slug VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
            description TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
            color VARCHAR(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '#007bff',
            icon_fontawesome VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
            icon_fontawesome_color VARCHAR(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
            icon_upload VARCHAR(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
            icon_url VARCHAR(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
            icon_type ENUM('fontawesome','upload','url','none','iconfont') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'fontawesome',
            icon_iconfont VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
            order_index INT NULL DEFAULT 0,
            is_active TINYINT(1) NULL DEFAULT 1,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`) USING BTREE,
            UNIQUE INDEX `name`(`name` ASC) USING BTREE,
            UNIQUE INDEX `unique_name`(`name` ASC) USING BTREE,
            UNIQUE INDEX `slug`(`slug` ASC) USING BTREE
        ) ENGINE=InnoDB AUTO_INCREMENT = 72 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

        CREATE TABLE IF NOT EXISTS navigation_links (
            id INT NOT NULL AUTO_INCREMENT,
            title VARCHAR(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            url VARCHAR(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            description TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
            category_id INT NOT NULL,
            icon_url VARCHAR(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
            icon_type ENUM('fontawesome','upload','url','none','iconfont') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'none',
            icon_iconfont VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
            icon_upload VARCHAR(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
            icon_fontawesome_color VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
            icon_fontawesome VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
            order_index INT NULL DEFAULT 0,
            is_active TINYINT(1) NULL DEFAULT 1,
            click_count INT NULL DEFAULT 0,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`) USING BTREE,
            INDEX `category_id`(`category_id` ASC) USING BTREE,
            CONSTRAINT `navigation_links_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
        ) ENGINE=InnoDB AUTO_INCREMENT = 187 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

        CREATE TABLE IF NOT EXISTS settings (
            id INT NOT NULL AUTO_INCREMENT,
            setting_key VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            setting_value TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
            setting_type VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'string',
            description VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`) USING BTREE,
            UNIQUE INDEX `setting_key`(`setting_key` ASC) USING BTREE
        ) ENGINE=InnoDB AUTO_INCREMENT = 2062 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

        CREATE TABLE IF NOT EXISTS users (
            id INT NOT NULL AUTO_INCREMENT,
            username VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            email VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            password VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            real_name VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
            role ENUM('user','editor','admin') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'user',
            status TINYINT(1) NULL DEFAULT 1,
            login_count INT NULL DEFAULT 0,
            last_login DATETIME NULL DEFAULT NULL,
            last_ip VARCHAR(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`) USING BTREE,
            UNIQUE INDEX `username`(`username` ASC) USING BTREE,
            UNIQUE INDEX `email`(`email` ASC) USING BTREE
        ) ENGINE=InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

        CREATE TABLE IF NOT EXISTS error_logs (
            id INT NOT NULL AUTO_INCREMENT,
            level VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            message TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            file VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
            line INT NULL DEFAULT NULL,
            context TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
            user_id INT NULL DEFAULT NULL,
            ip_address VARCHAR(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
            user_agent TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`) USING BTREE
        ) ENGINE=InnoDB AUTO_INCREMENT = 4 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

        CREATE TABLE IF NOT EXISTS login_logs (
            id INT NOT NULL AUTO_INCREMENT,
            user_id INT NULL DEFAULT NULL,
            username VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
            ip_address VARCHAR(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
            user_agent TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
            login_time TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            success TINYINT(1) NULL DEFAULT 0,
            failure_reason VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
            PRIMARY KEY (`id`) USING BTREE
        ) ENGINE=InnoDB AUTO_INCREMENT = 43 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

        CREATE TABLE IF NOT EXISTS operation_logs (
            id INT NOT NULL AUTO_INCREMENT,
            userid INT NOT NULL,
            operation_module VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            operation_type VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            categorie_id INT NULL DEFAULT NULL,
            categorie_name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
            link_id INT NULL DEFAULT NULL,
            link_name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
            files TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
            operated_id INT NULL DEFAULT NULL,
            operated_name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
            operation_time TIMESTAMP(3) NULL DEFAULT CURRENT_TIMESTAMP(3),
            ip_address VARCHAR(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
            operation_details JSON NULL,
            status ENUM('成功','失败') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '成功',
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`) USING BTREE,
            INDEX `idx_userid`(`userid` ASC) USING BTREE,
            INDEX `idx_operation_module`(`operation_module` ASC) USING BTREE,
            INDEX `idx_operation_type`(`operation_type` ASC) USING BTREE,
            INDEX `idx_operation_time`(`operation_time` ASC) USING BTREE,
            INDEX `idx_categorie_id`(`categorie_id` ASC) USING BTREE,
            INDEX `idx_link_id`(`link_id` ASC) USING BTREE,
            INDEX `idx_operated_id`(`operated_id` ASC) USING BTREE,
            CONSTRAINT `operation_logs_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
        ) ENGINE=InnoDB AUTO_INCREMENT = 10 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '操作日志表 - 记录用户操作历史' ROW_FORMAT = Dynamic;


        ";

        $pdo->exec($sql);

        // 插入默认数据 - 与后台设置页面保持一致
        $stmt = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
        $settings = [
            // 安全设置
            'enable_registration' => '0',
            'enable_comments' => '1',
            'max_login_attempts' => '5',
            'lockout_duration' => '30',
            'session_timeout' => '1800',
            'enable_captcha' => '1',
            'enable_2fa' => '0',
            'ip_whitelist' => '',
            'ip_blacklist' => '',
            
            // 系统设置
            'timezone' => 'Asia/Shanghai',
            'date_format' => 'Y-m-d H:i:s',
            'footer_style' => 'centered',
            
            // 基本网站信息
            'site_name' => '我的导航网站',
            'site_description' => '一个简洁美观的导航网站',
            'site_keywords' => '',
            'site_url' => '',
            
            // 显示设置
            'items_per_page' => '20',
            'maintenance_mode' => '0',
            'maintenance_message' => '网站正在维护中，请稍后再访问。',
            
            // Logo和图标设置
            'site_icon' => '',
            'site_logo_type' => 'fontawesome',
            'site_logo_image' => '',
            'site_logo_icon' => 'fas fa-compass',
            'site_logo_color' => '#000000',
            'site_logo_iconfont' => '',
            'iconfont' => '',
            
            // 背景设置
            'background_type' => 'api',
            'background_image' => '',
            'background_color' => '#ffffff',
            'background_api' => 'https://www.dmoe.cc/random.php',
            
            // 页脚设置
            'footer_content' => '© 2025 导航站 All rights reserved.&nbsp;备案号：京ICP备12345678号',
            'show_footer' => '1',
            
            // 上传设置
            'upload_max_size' => '10',
            'upload_allowed_types' => 'jpg,jpeg,png,gif,svg,webp,pdf,doc,docx,xls,xlsx,txt,zip,rar',
            
            // 透明度设置
            'header_bg_transparency' => '0.65',
            'category_bg_transparency' => '0.65',
            'links_area_transparency' => '0.85',
            'link_card_transparency' => '0.65',
            'bg_overlay' => '0.35',
            'footer_bg_transparency' => '0.8',
            
            // Logo类型设置
            'logo_type' => '',
            'logo_icon_class' => '',
            'logo_icon_color' => '',
            
            // 其他设置
            'custom_css' => ''
        ];

        foreach ($settings as $key => $value) {
            $stmt->execute([$key, $value]);
        }

        // 插入示例分类（与db-init-sample-data.php完全一致的结构）
        $categories = [
            [
                'name' => '搜索引擎',
                'description' => '常用搜索引擎集合',
				'icon_type' => 'fontawesome', 
                'icon_fontawesome' => 'fas fa-search',
                'icon_fontawesome_color' => '#4285f4',
                'color' => '#4285f4',
                'order_index' => 1,
                'is_active' => 1
            ],
            [
                'name' => '社交媒体',
                'description' => '主流社交平台',
				'icon_type' => 'fontawesome', 
                'icon_fontawesome' => 'fas fa-users',
                'icon_fontawesome_color' => '#f2a218',
                'color' => '#f2a218',
                'order_index' => 2,
                'is_active' => 1
            ],
            [
                'name' => '开发工具',
                'description' => '程序员必备开发工具',
				'icon_type' => 'fontawesome', 
                'icon_fontawesome' => 'fas fa-code',
                'icon_fontawesome_color' => '#24292e',
                'color' => '#24292e',
                'order_index' => 3,
                'is_active' => 1
            ],
            [
                'name' => '设计资源',
                'description' => '设计素材和工具网站',
				'icon_type' => 'fontawesome', 
                'icon_fontawesome' => 'fas fa-palette',
                'icon_fontawesome_color' => '#ff6b35',
                'color' => '#ff6b35',
                'order_index' => 4,
                'is_active' => 1
            ],
            [
                'name' => '学习平台',
                'description' => '在线学习和技术教程',
				'icon_type' => 'fontawesome', 
                'icon_fontawesome' => 'fas fa-graduation-cap',
                'icon_fontawesome_color' => '#0f997f',
                'color' => '#0f997f',
                'order_index' => 5,
                'is_active' => 1
            ],
            [
                'name' => '新闻资讯',
                'description' => '科技新闻和资讯网站',
				'icon_type' => 'fontawesome', 
                'icon_fontawesome' => 'fas fa-newspaper',
                'icon_fontawesome_color' => '#ff6600',
                'color' => '#ff6600',
                'order_index' => 6,
                'is_active' => 1
            ],
            [
                'name' => '云服务',
                'description' => '云计算和存储服务',
				'icon_type' => 'fontawesome', 
                'icon_fontawesome' => 'fas fa-cloud',
                'icon_fontawesome_color' => '#00a1f1',
                'color' => '#00a1f1',
                'order_index' => 7,
                'is_active' => 1
            ],
            [
                'name' => '娱乐休闲',
                'description' => '休闲娱乐和视频网站',
				'icon_type' => 'fontawesome', 
                'icon_fontawesome' => 'fas fa-gamepad',
                'icon_fontawesome_color' => '#ff0000',
                'color' => '#ff0000',
                'order_index' => 8,
                'is_active' => 1
            ]
        ];

        $stmt = $pdo->prepare("INSERT IGNORE INTO categories (name, description, icon_type, icon_fontawesome, icon_fontawesome_color, color, order_index, is_active, icon_iconfont) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($categories as $category) {
            $stmt->execute([
                $category['name'],
                $category['description'],
                $category['icon_type'],
                $category['icon_fontawesome'],
                $category['icon_fontawesome_color'],
                $category['color'],
                $category['order_index'],
                $category['is_active'],
                $category['icon_iconfont'] ?? null
            ]);
        }

        // 插入示例链接（基于import_chinese.php的中文数据）
        $links = [
            // 搜索引擎 (10个) - category_id: 72
            ['title' => '百度', 'url' => 'https://www.baidu.com', 'description' => '国内最大的搜索引擎', 'category' => '搜索引擎', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fas fa-search'],
            ['title' => '搜狗搜索', 'url' => 'https://www.sogou.com', 'description' => '搜狗搜索引擎', 'category' => '搜索引擎', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fas fa-search'],
            ['title' => '360搜索', 'url' => 'https://www.so.com', 'description' => '360搜索引擎', 'category' => '搜索引擎', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fas fa-shield-alt'],
            ['title' => '神马搜索', 'url' => 'https://m.sm.cn', 'description' => 'UC旗下的移动搜索引擎', 'category' => '搜索引擎', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fas fa-mobile-alt'],
            ['title' => '中国搜索', 'url' => 'https://www.chinaso.com', 'description' => '国家级搜索引擎', 'category' => '搜索引擎', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fas fa-flag'],
            ['title' => '必应', 'url' => 'https://www.bing.com', 'description' => '微软的搜索引擎', 'category' => '搜索引擎', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fas fa-window-maximize'],
            ['title' => 'Google', 'url' => 'https://www.google.com', 'description' => '全球最大的搜索引擎', 'category' => '搜索引擎', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fab fa-google'],
            ['title' => 'DuckDuckGo', 'url' => 'https://duckduckgo.com', 'description' => '隐私保护的搜索引擎', 'category' => '搜索引擎', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fas fa-user-secret'],
            ['title' => 'Yandex', 'url' => 'https://yandex.com', 'description' => '俄罗斯搜索引擎', 'category' => '搜索引擎', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fas fa-globe'],
            ['title' => 'Ecosia', 'url' => 'https://www.ecosia.org', 'description' => '环保搜索引擎', 'category' => '搜索引擎', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fas fa-leaf'],
            
            // 社交媒体 (10个)
            ['title' => '微信', 'url' => 'https://weixin.qq.com', 'description' => '腾讯社交 messaging 应用', 'category' => '社交媒体', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#28a745', 'icon_fontawesome' => 'fab fa-weixin'],
            ['title' => '微博', 'url' => 'https://weibo.com', 'description' => '中国社交媒体平台', 'category' => '社交媒体', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#28a745', 'icon_fontawesome' => 'fab fa-weibo'],
            ['title' => 'QQ', 'url' => 'https://im.qq.com', 'description' => '即时通讯平台', 'category' => '社交媒体', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#28a745', 'icon_fontawesome' => 'fab fa-qq'],
            ['title' => '知乎', 'url' => 'https://www.zhihu.com', 'description' => '问答社交平台', 'category' => '社交媒体', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#28a745', 'icon_fontawesome' => 'fas fa-question-circle'],
            ['title' => '抖音', 'url' => 'https://www.douyin.com', 'description' => '短视频平台', 'category' => '社交媒体', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#28a745', 'icon_fontawesome' => 'fas fa-video'],
            ['title' => '哔哩哔哩', 'url' => 'https://www.bilibili.com', 'description' => '视频分享平台', 'category' => '社交媒体', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#28a745', 'icon_fontawesome' => 'fas fa-tv'],
            ['title' => '小红书', 'url' => 'https://www.xiaohongshu.com', 'description' => '生活方式分享平台', 'category' => '社交媒体', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#28a745', 'icon_fontawesome' => 'fas fa-book-open'],
            ['title' => '快手', 'url' => 'https://www.kuaishou.com', 'description' => '短视频平台', 'category' => '社交媒体', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#28a745', 'icon_fontawesome' => 'fas fa-video'],
            ['title' => '百度贴吧', 'url' => 'https://tieba.baidu.com', 'description' => '社区论坛平台', 'category' => '社交媒体', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#28a745', 'icon_fontawesome' => 'fas fa-comments'],
            ['title' => '豆瓣', 'url' => 'https://www.douban.com', 'description' => '文化生活社区', 'category' => '社交媒体', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#28a745', 'icon_fontawesome' => 'fas fa-film'],
            
            // 开发工具 (10个)
            ['title' => '码云 Gitee', 'url' => 'https://gitee.com', 'description' => '国内代码托管平台', 'category' => '开发工具', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#6c757d', 'icon_fontawesome' => 'fas fa-code-branch'],
            ['title' => '掘金', 'url' => 'https://juejin.cn', 'description' => '技术社区平台', 'category' => '开发工具', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#6c757d', 'icon_fontawesome' => 'fas fa-laptop-code'],
            ['title' => 'CSDN', 'url' => 'https://www.csdn.net', 'description' => '开发者社区', 'category' => '开发工具', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#6c757d', 'icon_fontawesome' => 'fas fa-blog'],
            ['title' => '博客园', 'url' => 'https://www.cnblogs.com', 'description' => '开发者博客平台', 'category' => '开发工具', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#6c757d', 'icon_fontawesome' => 'fas fa-blog'],
            ['title' => '开源中国', 'url' => 'https://www.oschina.net', 'description' => '开源技术社区', 'category' => '开发工具', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#6c757d', 'icon_fontawesome' => 'fas fa-code'],
            ['title' => 'SegmentFault', 'url' => 'https://segmentfault.com', 'description' => '开发者问答平台', 'category' => '开发工具', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#6c757d', 'icon_fontawesome' => 'fas fa-question-circle'],
            ['title' => 'GitHub', 'url' => 'https://github.com', 'description' => '代码托管平台', 'category' => '开发工具', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#6c757d', 'icon_fontawesome' => 'fab fa-github'],
            ['title' => 'GitLab', 'url' => 'https://gitlab.com', 'description' => 'DevOps 平台', 'category' => '开发工具', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#6c757d', 'icon_fontawesome' => 'fab fa-gitlab'],
            ['title' => 'VS Code', 'url' => 'https://code.visualstudio.com', 'description' => '代码编辑器', 'category' => '开发工具', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#6c757d', 'icon_fontawesome' => 'fas fa-code'],
            ['title' => 'Stack Overflow', 'url' => 'https://stackoverflow.com', 'description' => '开发者问答平台', 'category' => '开发工具', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#6c757d', 'icon_fontawesome' => 'fab fa-stack-overflow'],
            
            // 设计资源 (10个)
            ['title' => '站酷', 'url' => 'https://www.zcool.com.cn', 'description' => '设计师社区平台', 'category' => '设计资源', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#e83e8c', 'icon_fontawesome' => 'fas fa-palette'],
            ['title' => '花瓣网', 'url' => 'https://huaban.com', 'description' => '图片分享平台', 'category' => '设计资源', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#e83e8c', 'icon_fontawesome' => 'fas fa-images'],
            ['title' => 'UI中国', 'url' => 'https://www.ui.cn', 'description' => 'UI设计师平台', 'category' => '设计资源', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#e83e8c', 'icon_fontawesome' => 'fas fa-paint-brush'],
            ['title' => '优设网', 'url' => 'https://www.uisdc.com', 'description' => '设计师学习平台', 'category' => '设计资源', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#e83e8c', 'icon_fontawesome' => 'fas fa-graduation-cap'],
            ['title' => '腾讯设计', 'url' => 'https://idesign.qq.com', 'description' => '腾讯设计平台', 'category' => '设计资源', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#e83e8c', 'icon_fontawesome' => 'fas fa-pencil-alt'],
            ['title' => '阿里巴巴矢量图标库', 'url' => 'https://www.iconfont.cn', 'description' => '图标字体平台', 'category' => '设计资源', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#e83e8c', 'icon_fontawesome' => 'fas fa-icons'],
            ['title' => '千图网', 'url' => 'https://www.58pic.com', 'description' => '免费素材下载', 'category' => '设计资源', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#e83e8c', 'icon_fontawesome' => 'fas fa-download'],
            ['title' => '包图网', 'url' => 'https://ibaotu.com', 'description' => '商业设计素材', 'category' => '设计资源', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#e83e8c', 'icon_fontawesome' => 'fas fa-box-open'],
            ['title' => '摄图网', 'url' => 'https://699pic.com', 'description' => '摄影图片素材', 'category' => '设计资源', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#e83e8c', 'icon_fontawesome' => 'fas fa-camera'],
            ['title' => '字由', 'url' => 'https://www.hellofont.cn', 'description' => '字体管理平台', 'category' => '设计资源', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#e83e8c', 'icon_fontawesome' => 'fas fa-font'],
            
            // 学习平台 (10个)
            ['title' => '中国大学MOOC', 'url' => 'https://www.icourse163.org', 'description' => '中文慕课平台', 'category' => '学习平台', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#17a2b8', 'icon_fontawesome' => 'fas fa-university'],
            ['title' => '学堂在线', 'url' => 'https://www.xuetangx.com', 'description' => '清华大学慕课平台', 'category' => '学习平台', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#17a2b8', 'icon_fontawesome' => 'fas fa-graduation-cap'],
            ['title' => '网易云课堂', 'url' => 'https://study.163.com', 'description' => '职业技能学习平台', 'category' => '学习平台', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#17a2b8', 'icon_fontawesome' => 'fas fa-chalkboard-teacher'],
            ['title' => '腾讯课堂', 'url' => 'https://ke.qq.com', 'description' => '在线教育平台', 'category' => '学习平台', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#17a2b8', 'icon_fontawesome' => 'fas fa-laptop'],
            ['title' => '百度传课', 'url' => 'https://chuanke.baidu.com', 'description' => '在线教育平台', 'category' => '学习平台', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#17a2b8', 'icon_fontawesome' => 'fas fa-book'],
            ['title' => '慕课网', 'url' => 'https://www.imooc.com', 'description' => 'IT技能学习平台', 'category' => '学习平台', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#17a2b8', 'icon_fontawesome' => 'fas fa-code'],
            ['title' => '极客时间', 'url' => 'https://time.geekbang.org', 'description' => '技术学习平台', 'category' => '学习平台', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#17a2b8', 'icon_fontawesome' => 'fas fa-laptop-code'],
            ['title' => '知乎盐选', 'url' => 'https://www.zhihu.com/yanxuan', 'description' => '知识付费平台', 'category' => '学习平台', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#17a2b8', 'icon_fontawesome' => 'fas fa-lightbulb'],
            ['title' => '得到', 'url' => 'https://www.dedao.cn', 'description' => '知识付费平台', 'category' => '学习平台', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#17a2b8', 'icon_fontawesome' => 'fas fa-headphones'],
            ['title' => '喜马拉雅', 'url' => 'https://www.ximalaya.com', 'description' => '音频学习平台', 'category' => '学习平台', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#17a2b8', 'icon_fontawesome' => 'fas fa-microphone'],
            
            // 新闻资讯 (10个)
            ['title' => '新浪新闻', 'url' => 'https://news.sina.com.cn', 'description' => '综合新闻门户', 'category' => '新闻资讯', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#dc3545', 'icon_fontawesome' => 'fas fa-newspaper'],
            ['title' => '网易新闻', 'url' => 'https://news.163.com', 'description' => '综合新闻平台', 'category' => '新闻资讯', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#dc3545', 'icon_fontawesome' => 'fas fa-newspaper'],
            ['title' => '腾讯新闻', 'url' => 'https://news.qq.com', 'description' => '综合新闻平台', 'category' => '新闻资讯', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#dc3545', 'icon_fontawesome' => 'fas fa-newspaper'],
            ['title' => '搜狐新闻', 'url' => 'https://news.sohu.com', 'description' => '综合新闻平台', 'category' => '新闻资讯', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#dc3545', 'icon_fontawesome' => 'fas fa-newspaper'],
            ['title' => '凤凰网', 'url' => 'https://www.ifeng.com', 'description' => '综合新闻门户', 'category' => '新闻资讯', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#dc3545', 'icon_fontawesome' => 'fas fa-globe'],
            ['title' => '人民网', 'url' => 'http://www.people.com.cn', 'description' => '官方新闻网站', 'category' => '新闻资讯', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#dc3545', 'icon_fontawesome' => 'fas fa-flag'],
            ['title' => '新华网', 'url' => 'http://www.xinhuanet.com', 'description' => '官方新闻网站', 'category' => '新闻资讯', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#dc3545', 'icon_fontawesome' => 'fas fa-flag'],
            ['title' => '今日头条', 'url' => 'https://www.toutiao.com', 'description' => '个性化新闻推荐', 'category' => '新闻资讯', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#dc3545', 'icon_fontawesome' => 'fas fa-newspaper'],
            ['title' => '澎湃新闻', 'url' => 'https://www.thepaper.cn', 'description' => '深度新闻报道', 'category' => '新闻资讯', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#dc3545', 'icon_fontawesome' => 'fas fa-microscope'],
            ['title' => '界面新闻', 'url' => 'https://www.jiemian.com', 'description' => '商业新闻平台', 'category' => '新闻资讯', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#dc3545', 'icon_fontawesome' => 'fas fa-chart-line'],
            
            // 云服务 (10个)
            ['title' => '阿里云', 'url' => 'https://www.aliyun.com', 'description' => '阿里巴巴云计算', 'category' => '云服务', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#ffc107', 'icon_fontawesome' => 'fas fa-cloud'],
            ['title' => '腾讯云', 'url' => 'https://cloud.tencent.com', 'description' => '腾讯云计算', 'category' => '云服务', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#ffc107', 'icon_fontawesome' => 'fas fa-cloud'],
            ['title' => '华为云', 'url' => 'https://www.huaweicloud.com', 'description' => '华为云计算', 'category' => '云服务', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#ffc107', 'icon_fontawesome' => 'fas fa-cloud'],
            ['title' => '百度智能云', 'url' => 'https://cloud.baidu.com', 'description' => '百度云计算', 'category' => '云服务', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#ffc107', 'icon_fontawesome' => 'fas fa-brain'],
            ['title' => '京东云', 'url' => 'https://www.jdcloud.com', 'description' => '京东云计算', 'category' => '云服务', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#ffc107', 'icon_fontawesome' => 'fas fa-shopping-cart'],
            ['title' => '网易云', 'url' => 'https://www.163yun.com', 'description' => '网易云计算', 'category' => '云服务', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#ffc107', 'icon_fontawesome' => 'fas fa-cloud'],
            ['title' => '金山云', 'url' => 'https://www.ksyun.com', 'description' => '金山云计算', 'category' => '云服务', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#ffc107', 'icon_fontawesome' => 'fas fa-cloud'],
            ['title' => 'UCloud', 'url' => 'https://www.ucloud.cn', 'description' => '优刻得云计算', 'category' => '云服务', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#ffc107', 'icon_fontawesome' => 'fas fa-server'],
            ['title' => '青云', 'url' => 'https://www.qingcloud.com', 'description' => '青云云计算', 'category' => '云服务', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#ffc107', 'icon_fontawesome' => 'fas fa-cloud'],
            ['title' => 'Google Cloud', 'url' => 'https://cloud.google.com', 'description' => 'Google 云计算服务', 'category' => '云服务', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#ffc107', 'icon_fontawesome' => 'fab fa-google'],
            
            // 娱乐休闲 (10个)
            ['title' => '爱奇艺', 'url' => 'https://www.iqiyi.com', 'description' => '视频娱乐平台', 'category' => '娱乐休闲', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#6f42c1', 'icon_fontawesome' => 'fas fa-tv'],
            ['title' => '腾讯视频', 'url' => 'https://v.qq.com', 'description' => '视频娱乐平台', 'category' => '娱乐休闲', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#6f42c1', 'icon_fontawesome' => 'fas fa-tv'],
            ['title' => '优酷', 'url' => 'https://www.youku.com', 'description' => '视频娱乐平台', 'category' => '娱乐休闲', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#6f42c1', 'icon_fontawesome' => 'fas fa-tv'],
            ['title' => '芒果TV', 'url' => 'https://www.mgtv.com', 'description' => '湖南广电旗下视频平台', 'category' => '娱乐休闲', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#6f42c1', 'icon_fontawesome' => 'fas fa-tv'],
            ['title' => '网易云音乐', 'url' => 'https://music.163.com', 'description' => '音乐流媒体平台', 'category' => '娱乐休闲', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#6f42c1', 'icon_fontawesome' => 'fas fa-music'],
            ['title' => 'QQ音乐', 'url' => 'https://y.qq.com', 'description' => '音乐流媒体平台', 'category' => '娱乐休闲', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#6f42c1', 'icon_fontawesome' => 'fas fa-music'],
            ['title' => '酷狗音乐', 'url' => 'https://www.kugou.com', 'description' => '音乐流媒体平台', 'category' => '娱乐休闲', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#6f42c1', 'icon_fontawesome' => 'fas fa-music'],
            ['title' => '喜马拉雅', 'url' => 'https://www.ximalaya.com', 'description' => '音频娱乐平台', 'category' => '娱乐休闲', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#6f42c1', 'icon_fontawesome' => 'fas fa-microphone'],
            ['title' => '蜻蜓FM', 'url' => 'https://www.qingting.fm', 'description' => '音频娱乐平台', 'category' => '娱乐休闲', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#6f42c1', 'icon_fontawesome' => 'fas fa-broadcast-tower'],
            ['title' => '懒人听书', 'url' => 'https://www.lrts.me', 'description' => '有声读物平台', 'category' => '娱乐休闲', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#6f42c1', 'icon_fontawesome' => 'fas fa-headphones']
        ];

        // 获取分类ID映射
        $categoryMap = [];
        $stmt = $pdo->query("SELECT id, name FROM categories");
        while ($row = $stmt->fetch()) {
            $categoryMap[$row['name']] = $row['id'];
        }

        $stmt = $pdo->prepare("INSERT IGNORE INTO navigation_links (title, url, description, icon_type, icon_fontawesome_color, icon_fontawesome, category_id, order_index, is_active, click_count, icon_iconfont) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 0, ?)");
        foreach ($links as $index => $link) {
            $categoryId = $categoryMap[$link['category']] ?? null;
            $stmt->execute([
                $link['title'], 
                $link['url'], 
                $link['description'], 
                $link['icon_type'], 
                $link['icon_fontawesome_color'], 
                $link['icon_fontawesome'] ?? 'fa-link',
                $categoryId, 
                $index + 1,
                $link['icon_iconfont'] ?? null
            ]);
        }

        // 创建管理员用户
        $passwordHash = password_hash($admin_password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, real_name, role, status) VALUES (?, ?, ?, ?, 'admin', 1)");
        $stmt->execute(['admin', 'admin@example.com', $passwordHash, '系统管理员']);

        // 创建配置文件
        $configContent = '<?php
/**
 * 网站配置文件
 * 由安装向导自动生成
 */

// 数据库配置
define(\'DB_HOST\', \'' . addslashes($db_host) . '\');
define(\'DB_PORT\', \'' . addslashes($db_port) . '\');
define(\'DB_NAME\', \'' . addslashes($db_name) . '\');
define(\'DB_USER\', \'' . addslashes($db_user) . '\');
define(\'DB_PASS\', \'' . addslashes($db_pass) . '\');
define(\'DB_CHARSET\', \'utf8mb4\');

// 安装状态
define(\'SITE_INSTALLED\', true);
?>';

        if (file_put_contents($configFile, $configContent) === false) {
            throw new Exception('无法创建配置文件，请检查目录权限');
        }

        $success = '安装成功！您可以：<br>
                   1. <a href="index.php">访问网站首页</a><br>
                   2. <a href="admin/index.php">进入管理后台</a><br>
                   3. <strong style="color: red;">删除此 install.php 文件以提高安全性</strong>';
        $installed = true;

    } catch (Exception $e) {
        $error = '安装失败：' . $e->getMessage();
    }
}

// 检查PHP版本
$phpVersion = PHP_VERSION;
$phpOk = version_compare($phpVersion, '7.4.0', '>=');

// 检查必需扩展
$extensions = ['pdo', 'pdo_mysql', 'json'];
$missingExtensions = [];
foreach ($extensions as $ext) {
    if (!extension_loaded($ext)) {
        $missingExtensions[] = $ext;
    }
}

// 检查目录权限
$writable = is_writable(__DIR__);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>网站安装向导</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .install-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }
        
        .install-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .install-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .install-header p {
            color: #666;
            font-size: 16px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus, .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .requirements {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .requirements h3 {
            margin-bottom: 15px;
            color: #333;
        }
        
        .requirements ul {
            list-style: none;
        }
        
        .requirements li {
            padding: 5px 0;
            display: flex;
            align-items: center;
        }
        
        .requirements .status {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 10px;
        }
        
        .status.success {
            background: #28a745;
        }
        
        .status.error {
            background: #dc3545;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin: 30px 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #e1e5e9;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-header">
            <h1>🚀 网站安装向导</h1>
            <p>欢迎使用导航网站安装程序，请按照以下步骤完成配置</p>
        </div>

        <?php if ($installed): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="requirements">
                <h3>系统检查</h3>
                <ul>
                    <li>
                        <span class="status <?php echo $phpOk ? 'success' : 'error'; ?>"></span>
                        PHP版本: <?php echo $phpVersion; ?> (需要 ≥ 7.4.0)
                    </li>
                    <li>
                        <span class="status <?php echo empty($missingExtensions) ? 'success' : 'error'; ?>"></span>
                        PHP扩展: <?php echo empty($missingExtensions) ? '全部可用' : '缺少: ' . implode(', ', $missingExtensions); ?>
                    </li>
                    <li>
                        <span class="status <?php echo $writable ? 'success' : 'error'; ?>"></span>
                        目录权限: <?php echo $writable ? '可写入' : '不可写入，请修改权限'; ?>
                    </li>
                </ul>
            </div>

            <?php if ($phpOk && empty($missingExtensions) && $writable): ?>
                <form method="post" id="installForm">
                    <div class="section-title">网站信息</div>
                    
                    <div class="form-group">
                        <label for="site_name">网站名称 *</label>
                        <input type="text" id="site_name" name="site_name" placeholder="例如：我的导航站" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="site_description">网站描述</label>
                        <textarea id="site_description" name="site_description" placeholder="简单描述您的网站"></textarea>
                    </div>

                    <div class="section-title">管理员设置</div>
                    
                    <div class="form-group">
                        <label for="admin_password">管理密码 *</label>
                        <input type="password" id="admin_password" name="admin_password" placeholder="至少6位密码" required minlength="6">
                    </div>

                    <div class="section-title">数据库配置</div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="db_host">数据库服务器 *</label>
                            <input type="text" id="db_host" name="db_host" value="localhost" required>
                        </div>
                        <div class="form-group">
                            <label for="db_port">端口 *</label>
                            <input type="text" id="db_port" name="db_port" value="3306" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_name">数据库名 *</label>
                        <input type="text" id="db_name" name="db_name" placeholder="例如：navigation" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="db_user">用户名 *</label>
                            <input type="text" id="db_user" name="db_user" placeholder="数据库用户名" required>
                        </div>
                        <div class="form-group">
                            <label for="db_pass">密码</label>
                            <input type="password" id="db_pass" name="db_pass" placeholder="数据库密码">
                        </div>
                    </div>

                    <button type="submit" class="btn">
                        开始安装
                    </button>
                </form>
            <?php else: ?>
                <div class="alert alert-warning">
                    请解决上述系统要求问题后再继续安装
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script>
        // 简单的表单验证
        document.getElementById('installForm')?.addEventListener('submit', function(e) {
            const password = document.getElementById('admin_password').value;
            if (password.length < 6) {
                alert('管理密码至少需要6位');
                e.preventDefault();
                return false;
            }
            
            // 禁用提交按钮防止重复提交
            const submitBtn = e.target.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = '安装中...';
        });
    </script>
</body>
</html>