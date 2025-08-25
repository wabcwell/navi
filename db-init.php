<?php
// PHP版本的数据库初始化脚本

require_once 'config.php';

echo "=== 数据库初始化 (PHP版本) ===\n";

// 获取数据库配置
$config = Config::getDatabaseConfig();

try {
    // 创建数据库连接（不指定数据库）
    $dsn = sprintf('mysql:host=%s;port=%d;charset=%s', 
        $config['host'], 
        $config['port'], 
        $config['charset']
    );
    
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "✅ 已连接到MySQL服务器\n";
    
    // 创建数据库
    $databaseName = $config['database'];
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ 数据库已创建: {$databaseName}\n";
    
    // 连接到指定数据库
    $pdo->exec("USE `{$databaseName}`");
    echo "✅ 已连接到数据库: {$databaseName}\n";
    
    // 创建分类表
    $createCategoriesTable = "
    CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        description TEXT,
        color VARCHAR(7) DEFAULT '#007bff',
        icon VARCHAR(50),
        order_index INT DEFAULT 0,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($createCategoriesTable);
    echo "✅ 分类表已创建\n";
    
    // 创建导航链接表
    $createLinksTable = "
    CREATE TABLE IF NOT EXISTS navigation_links (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        url VARCHAR(500) NOT NULL,
        description TEXT,
        category_id INT NOT NULL,
        icon_url VARCHAR(500),
        order_index INT DEFAULT 0,
        is_active BOOLEAN DEFAULT TRUE,
        click_count INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($createLinksTable);
    echo "✅ 导航链接表已创建\n";
    
    // 创建用户偏好设置表
    $createPreferencesTable = "
    CREATE TABLE IF NOT EXISTS user_preferences (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(100) NOT NULL,
        preference_key VARCHAR(100) NOT NULL,
        preference_value TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_preference (user_id, preference_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($createPreferencesTable);
    echo "✅ 用户偏好设置表已创建\n";
    
    // 插入示例分类数据
    $categories = [
        ['搜索引擎', '常用搜索引擎集合', '#4285f4', 'search'],
        ['开发工具', '开发者必备工具', '#0f9d58', 'code'],
        ['学习平台', '在线学习资源', '#db4437', 'school'],
        ['实用工具', '日常实用工具', '#f4b400', 'build']
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO categories (name, description, color, icon, order_index) VALUES (?, ?, ?, ?, ?)");
    foreach ($categories as $index => $category) {
        $stmt->execute([$category[0], $category[1], $category[2], $category[3], $index]);
    }
    echo "✅ 示例分类数据已插入\n";
    
    // 插入示例导航链接数据
    $links = [
        ['Google', 'https://www.google.com', '全球最大的搜索引擎', 1, 'https://www.google.com/favicon.ico', 1],
        ['百度', 'https://www.baidu.com', '中文搜索引擎', 1, 'https://www.baidu.com/favicon.ico', 2],
        ['GitHub', 'https://github.com', '全球最大的代码托管平台', 2, 'https://github.com/favicon.ico', 1],
        ['Stack Overflow', 'https://stackoverflow.com', '程序员问答社区', 2, 'https://stackoverflow.com/favicon.ico', 2],
        ['LeetCode', 'https://leetcode.com', '算法学习平台', 3, 'https://leetcode.com/favicon.ico', 1],
        ['Coursera', 'https://www.coursera.org', '在线课程平台', 3, 'https://www.coursera.org/favicon.ico', 2],
        ['在线JSON格式化', 'https://jsonformatter.org', 'JSON数据格式化工具', 4, 'https://jsonformatter.org/favicon.ico', 1],
        ['图片压缩', 'https://tinypng.com', '在线图片压缩工具', 4, 'https://tinypng.com/favicon.ico', 2]
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO navigation_links (title, url, description, category_id, icon_url, order_index) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($links as $link) {
        $stmt->execute($link);
    }
    echo "✅ 示例导航链接数据已插入\n";
    
    // 验证数据
    $categoryCount = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    $linkCount = $pdo->query("SELECT COUNT(*) FROM navigation_links")->fetchColumn();
    
    echo "\n📊 验证数据...\n";
    echo "📋 分类数量: {$categoryCount}\n";
    echo "🔗 导航链接数量: {$linkCount}\n";
    
    // 显示分类列表
    $stmt = $pdo->query("SELECT name FROM categories ORDER BY order_index");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "📂 分类列表: " . implode(', ', $categories) . "\n";
    
    echo "\n🎉 数据库初始化完成！\n";
    echo "💡 现在可以运行: php db-test.php 来测试连接\n";
    
} catch (PDOException $e) {
    echo "❌ 数据库操作失败: " . $e->getMessage() . "\n";
    exit(1);
}
?>