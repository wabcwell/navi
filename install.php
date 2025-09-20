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
            // 基本网站信息
            'site_name' => $site_name,
            'site_description' => $site_description,
            'site_keywords' => '导航,工具,网站',
            'site_url' => '',
            
            // 显示设置
            'items_per_page' => 20,
            'maintenance_mode' => 0,
            'maintenance_message' => '网站正在维护中，请稍后再访问',
            
            // Logo和图标设置
            'site_icon' => '',
            'site_logo_type' => 'image',
            'site_logo_image' => '',
            'site_logo_icon' => 'fa-home',
            'site_logo_color' => '#007bff',
            'site_logo_iconfont' => '',
            'iconfont' => '', // Iconfont图标库地址
            
            // 背景设置
            'background_type' => 'color',
            'background_image' => '',
            'background_color' => '#f8f9fa',
            'background_api' => 'https://www.dmoe.cc/random.php',
            
            // 页脚设置
            'footer_content' => '© ' . date('Y') . ' ' . $site_name . '. All rights reserved.',
            'show_footer' => 1,
            
            // 上传设置
            'upload_max_size' => 10,
            'upload_allowed_types' => 'jpg,jpeg,png,gif,svg,webp,pdf,doc,docx,xls,xlsx,txt,zip,rar',
            
            // 透明度设置
            'bg-overlay' => 0.25,
            'header_bg_transparency' => 0.85,
            'category_bg_transparency' => 0.85,
            'links_area_transparency' => 0.85,
            'link_card_transparency' => 0.85,
            
            // 系统设置
            'timezone' => 'Asia/Shanghai',
            'date_format' => 'Y-m-d H:i:s',
            
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
                'icon_fontawesome' => 'fa-search',
                'icon_fontawesome_color' => '#4285f4',
                'color' => '#4285f4',
                'order_index' => 1,
                'is_active' => 1
            ],
            [
                'name' => '社交媒体',
                'description' => '主流社交平台',
				'icon_type' => 'fontawesome', 
                'icon_fontawesome' => 'fa-users',
                'icon_fontawesome_color' => '#1877f2',
                'color' => '#1877f2',
                'order_index' => 2,
                'is_active' => 1
            ],
            [
                'name' => '开发工具',
                'description' => '程序员必备开发工具',
				'icon_type' => 'fontawesome', 
                'icon_fontawesome' => 'fa-code',
                'icon_fontawesome_color' => '#24292e',
                'color' => '#24292e',
                'order_index' => 3,
                'is_active' => 1
            ],
            [
                'name' => '设计资源',
                'description' => '设计素材和工具网站',
				'icon_type' => 'fontawesome', 
                'icon_fontawesome' => 'fa-palette',
                'icon_fontawesome_color' => '#ff6b35',
                'color' => '#ff6b35',
                'order_index' => 4,
                'is_active' => 1
            ],
            [
                'name' => '学习平台',
                'description' => '在线学习和技术教程',
				'icon_type' => 'fontawesome', 
                'icon_fontawesome' => 'fa-graduation-cap',
                'icon_fontawesome_color' => '#0f997f',
                'color' => '#0f997f',
                'order_index' => 5,
                'is_active' => 1
            ],
            [
                'name' => '新闻资讯',
                'description' => '科技新闻和资讯网站',
				'icon_type' => 'fontawesome', 
                'icon_fontawesome' => 'fa-newspaper',
                'icon_fontawesome_color' => '#ff6600',
                'color' => '#ff6600',
                'order_index' => 6,
                'is_active' => 1
            ],
            [
                'name' => '云服务',
                'description' => '云计算和存储服务',
				'icon_type' => 'fontawesome', 
                'icon_fontawesome' => 'fa-cloud',
                'icon_fontawesome_color' => '#00a1f1',
                'color' => '#00a1f1',
                'order_index' => 7,
                'is_active' => 1
            ],
            [
                'name' => '娱乐休闲',
                'description' => '休闲娱乐和视频网站',
				'icon_type' => 'fontawesome', 
                'icon_fontawesome' => 'fa-gamepad',
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

        // 插入示例链接（基于db-init-sample-data.php的80个链接）
        $links = [
            // 搜索引擎 (10个)
            ['title' => 'Google', 'url' => 'https://www.google.com', 'description' => '全球最大的搜索引擎', 'category' => '搜索引擎', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-google'],
            ['title' => '百度', 'url' => 'https://www.baidu.com', 'description' => '国内最大的搜索引擎', 'category' => '搜索引擎', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-search'],
            ['title' => '必应', 'url' => 'https://www.bing.com', 'description' => '微软的搜索引擎', 'category' => '搜索引擎', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-microsoft'],
            ['title' => 'DuckDuckGo', 'url' => 'https://duckduckgo.com', 'description' => '隐私保护的搜索引擎', 'category' => '搜索引擎', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-shield-alt'],
            ['title' => 'Yandex', 'url' => 'https://yandex.com', 'description' => '俄罗斯搜索引擎', 'category' => '搜索引擎', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-yandex'],
            ['title' => '搜狗', 'url' => 'https://www.sogou.com', 'description' => '搜狗搜索引擎', 'category' => '搜索引擎', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-search'],
            ['title' => '360搜索', 'url' => 'https://www.so.com', 'description' => '360搜索引擎', 'category' => '搜索引擎', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-shield-alt'],
            ['title' => 'Ecosia', 'url' => 'https://www.ecosia.org', 'description' => '环保搜索引擎', 'category' => '搜索引擎', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-leaf'],
            ['title' => 'Startpage', 'url' => 'https://www.startpage.com', 'description' => '隐私搜索引擎', 'category' => '搜索引擎', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-user-secret'],
            ['title' => 'Qwant', 'url' => 'https://www.qwant.com', 'description' => '欧洲隐私搜索引擎', 'category' => '搜索引擎', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-search'],
            
            // 社交媒体 (10个)
            ['title' => '微博', 'url' => 'https://weibo.com', 'description' => '中国主流社交媒体平台', 'category' => '社交媒体', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-weibo'],
            ['title' => 'Twitter', 'url' => 'https://twitter.com', 'description' => '全球社交媒体平台', 'category' => '社交媒体', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-twitter'],
            ['title' => 'Facebook', 'url' => 'https://facebook.com', 'description' => '全球最大的社交网络', 'category' => '社交媒体', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-facebook'],
            ['title' => 'Instagram', 'url' => 'https://instagram.com', 'description' => '图片分享社交平台', 'category' => '社交媒体', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-instagram'],
            ['title' => 'LinkedIn', 'url' => 'https://linkedin.com', 'description' => '职业社交平台', 'category' => '社交媒体', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-linkedin'],
            ['title' => 'Reddit', 'url' => 'https://reddit.com', 'description' => '社区讨论平台', 'category' => '社交媒体', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-reddit'],
            ['title' => '知乎', 'url' => 'https://www.zhihu.com', 'description' => '中文问答社区', 'category' => '社交媒体', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-question-circle'],
            ['title' => '豆瓣', 'url' => 'https://www.douban.com', 'description' => '文化娱乐社区', 'category' => '社交媒体', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-book'],
            ['title' => '小红书', 'url' => 'https://www.xiaohongshu.com', 'description' => '生活方式分享平台', 'category' => '社交媒体', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-shopping-bag'],
            ['title' => 'TikTok', 'url' => 'https://www.tiktok.com', 'description' => '短视频社交平台', 'category' => '社交媒体', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-tiktok'],
            
            // 开发工具 (10个)
            ['title' => 'GitHub', 'url' => 'https://github.com', 'description' => '代码托管和协作平台', 'category' => '开发工具', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-github'],
            ['title' => 'GitLab', 'url' => 'https://gitlab.com', 'description' => 'DevOps平台', 'category' => '开发工具', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-gitlab'],
            ['title' => 'VS Code', 'url' => 'https://code.visualstudio.com', 'description' => '微软代码编辑器', 'category' => '开发工具', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-code'],
            ['title' => 'Stack Overflow', 'url' => 'https://stackoverflow.com', 'description' => '程序员问答社区', 'category' => '开发工具', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-stack-overflow'],
            ['title' => 'MDN Web Docs', 'url' => 'https://developer.mozilla.org', 'description' => 'Web开发文档', 'category' => '开发工具', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-firefox'],
            ['title' => 'npm', 'url' => 'https://www.npmjs.com', 'description' => 'Node.js包管理器', 'category' => '开发工具', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-npm'],
            ['title' => 'Docker Hub', 'url' => 'https://hub.docker.com', 'description' => '容器镜像仓库', 'category' => '开发工具', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-docker'],
            ['title' => 'Postman', 'url' => 'https://www.postman.com', 'description' => 'API测试工具', 'category' => '开发工具', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-vial'],
            ['title' => 'CodePen', 'url' => 'https://codepen.io', 'description' => '前端代码在线编辑器', 'category' => '开发工具', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-codepen'],
            ['title' => 'JSFiddle', 'url' => 'https://jsfiddle.net', 'description' => '在线代码测试工具', 'category' => '开发工具', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-code'],
            
            // 设计资源 (10个)
            ['title' => 'Dribbble', 'url' => 'https://dribbble.com', 'description' => '设计师作品展示平台', 'category' => '设计资源', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-dribbble'],
            ['title' => 'Behance', 'url' => 'https://behance.net', 'description' => '创意设计作品展示', 'category' => '设计资源', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-behance'],
            ['title' => 'Figma', 'url' => 'https://figma.com', 'description' => '在线设计协作工具', 'category' => '设计资源', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-figma'],
            ['title' => 'Adobe Creative Cloud', 'url' => 'https://creativecloud.com', 'description' => 'Adobe创意套件', 'category' => '设计资源', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-adobe'],
            ['title' => 'Canva', 'url' => 'https://www.canva.com', 'description' => '在线设计平台', 'category' => '设计资源', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-palette'],
            ['title' => 'Unsplash', 'url' => 'https://unsplash.com', 'description' => '免费高质量图片', 'category' => '设计资源', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-camera'],
            ['title' => 'Pexels', 'url' => 'https://www.pexels.com', 'description' => '免费图片和视频', 'category' => '设计资源', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-camera'],
            ['title' => 'Freepik', 'url' => 'https://www.freepik.com', 'description' => '免费设计素材', 'category' => '设计资源', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-palette'],
            ['title' => 'Iconfont', 'url' => 'https://www.iconfont.cn', 'description' => '阿里巴巴矢量图标库', 'category' => '设计资源', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-icons'],
            ['title' => 'LottieFiles', 'url' => 'https://lottiefiles.com', 'description' => '动画设计资源', 'category' => '设计资源', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-play-circle'],
            
            // 学习平台 (10个)
            ['title' => 'Coursera', 'url' => 'https://www.coursera.org', 'description' => '在线课程平台', 'category' => '学习平台', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-graduation-cap'],
            ['title' => 'edX', 'url' => 'https://www.edx.org', 'description' => '在线学习平台', 'category' => '学习平台', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-university'],
            ['title' => 'Udemy', 'url' => 'https://www.udemy.com', 'description' => '在线课程市场', 'category' => '学习平台', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-laptop'],
            ['title' => 'Khan Academy', 'url' => 'https://www.khanacademy.org', 'description' => '免费教育平台', 'category' => '学习平台', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-book-open'],
            ['title' => 'Codecademy', 'url' => 'https://www.codecademy.com', 'description' => '编程学习平台', 'category' => '学习平台', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-code'],
            ['title' => 'LeetCode', 'url' => 'https://leetcode.com', 'description' => '算法练习平台', 'category' => '学习平台', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-code'],
            ['title' => '慕课网', 'url' => 'https://www.imooc.com', 'description' => 'IT技能学习平台', 'category' => '学习平台', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-laptop-code'],
            ['title' => '极客时间', 'url' => 'https://time.geekbang.org', 'description' => 'IT知识服务', 'category' => '学习平台', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-brain'],
            ['title' => '网易云课堂', 'url' => 'https://study.163.com', 'description' => '在线学习平台', 'category' => '学习平台', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-graduation-cap'],
            ['title' => '腾讯课堂', 'url' => 'https://ke.qq.com', 'description' => '在线教育平台', 'category' => '学习平台', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-chalkboard-teacher'],
            
            // 新闻资讯 (10个)
            ['title' => 'TechCrunch', 'url' => 'https://techcrunch.com', 'description' => '科技新闻网站', 'category' => '新闻资讯', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-newspaper'],
            ['title' => 'The Verge', 'url' => 'https://www.theverge.com', 'description' => '科技新闻和评论', 'category' => '新闻资讯', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-laptop'],
            ['title' => 'Ars Technica', 'url' => 'https://arstechnica.com', 'description' => '科技新闻和分析', 'category' => '新闻资讯', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-microchip'],
            ['title' => 'Engadget', 'url' => 'https://engadget.com', 'description' => '科技新闻和评测', 'category' => '新闻资讯', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-mobile-alt'],
            ['title' => '36氪', 'url' => 'https://36kr.com', 'description' => '科技创业资讯', 'category' => '新闻资讯', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-rocket'],
            ['title' => '虎嗅', 'url' => 'https://www.huxiu.com', 'description' => '科技商业资讯', 'category' => '新闻资讯', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-chart-line'],
            ['title' => '品玩', 'url' => 'https://www.pingwest.com', 'description' => '科技生活方式', 'category' => '新闻资讯', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-globe'],
            ['title' => '爱范儿', 'url' => 'https://www.ifanr.com', 'description' => '科技媒体', 'category' => '新闻资讯', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-tablet-alt'],
            ['title' => '少数派', 'url' => 'https://sspai.com', 'description' => '数字生活指南', 'category' => '新闻资讯', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-keyboard'],
            ['title' => 'CSDN', 'url' => 'https://www.csdn.net', 'description' => 'IT技术社区', 'category' => '新闻资讯', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-code'],
            
            // 云服务 (10个)
            ['title' => 'AWS', 'url' => 'https://aws.amazon.com', 'description' => '亚马逊云服务', 'category' => '云服务', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-aws'],
            ['title' => 'Microsoft Azure', 'url' => 'https://azure.microsoft.com', 'description' => '微软云服务', 'category' => '云服务', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-microsoft'],
            ['title' => 'Google Cloud', 'url' => 'https://cloud.google.com', 'description' => '谷歌云服务', 'category' => '云服务', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-google'],
            ['title' => '阿里云', 'url' => 'https://www.aliyun.com', 'description' => '阿里巴巴云服务', 'category' => '云服务', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-cloud'],
            ['title' => '腾讯云', 'url' => 'https://cloud.tencent.com', 'description' => '腾讯云服务', 'category' => '云服务', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-cloud'],
            ['title' => '华为云', 'url' => 'https://www.huaweicloud.com', 'description' => '华为云服务', 'category' => '云服务', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-cloud'],
            ['title' => '百度云', 'url' => 'https://cloud.baidu.com', 'description' => '百度云服务', 'category' => '云服务', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-cloud'],
            ['title' => 'DigitalOcean', 'url' => 'https://www.digitalocean.com', 'description' => '云服务器提供商', 'category' => '云服务', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-server'],
            ['title' => 'Vercel', 'url' => 'https://vercel.com', 'description' => '前端部署平台', 'category' => '云服务', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-rocket'],
            ['title' => 'Netlify', 'url' => 'https://www.netlify.com', 'description' => '静态网站托管', 'category' => '云服务', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-globe'],
            
            // 娱乐休闲 (10个)
            ['title' => 'YouTube', 'url' => 'https://www.youtube.com', 'description' => '全球最大的视频网站', 'category' => '娱乐休闲', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-youtube'],
            ['title' => 'Bilibili', 'url' => 'https://www.bilibili.com', 'description' => '哔哩哔哩视频网站', 'category' => '娱乐休闲', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-play-circle'],
            ['title' => 'Netflix', 'url' => 'https://www.netflix.com', 'description' => '在线流媒体平台', 'category' => '娱乐休闲', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-film'],
            ['title' => 'Spotify', 'url' => 'https://www.spotify.com', 'description' => '音乐流媒体平台', 'category' => '娱乐休闲', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-spotify'],
            ['title' => 'Steam', 'url' => 'https://store.steampowered.com', 'description' => '游戏购买平台', 'category' => '娱乐休闲', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-steam'],
            ['title' => 'Epic Games', 'url' => 'https://www.epicgames.com', 'description' => '游戏平台', 'category' => '娱乐休闲', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-gamepad'],
            ['title' => '网易云音乐', 'url' => 'https://music.163.com', 'description' => '音乐流媒体', 'category' => '娱乐休闲', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-music'],
            ['title' => 'QQ音乐', 'url' => 'https://y.qq.com', 'description' => '音乐流媒体', 'category' => '娱乐休闲', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-music'],
            ['title' => '抖音', 'url' => 'https://www.douyin.com', 'description' => '短视频平台', 'category' => '娱乐休闲', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-video'],
            ['title' => '快手', 'url' => 'https://www.kuaishou.com', 'description' => '短视频平台', 'category' => '娱乐休闲', 'icon_type' => 'fontawesome', 'icon_fontawesome_color' => '#007bff', 'icon_fontawesome' => 'fa-video']
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