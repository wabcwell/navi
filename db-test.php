<?php
// PHP版本的数据库连接测试脚本

require_once 'config.php';

// 处理命令行参数
$options = getopt('', ['summary', 'schema', 'sql', 'help']);

if (isset($options['help'])) {
    echo "=== 数据库测试脚本使用说明 ===\n";
    echo "php db-test.php              # 测试数据库连接\n";
    echo "php db-test.php --summary    # 显示配置摘要\n";
    echo "php db-test.php --schema     # 显示数据库表结构\n";
    echo "php db-test.php --sql        # 显示初始化SQL\n";
    echo "php db-test.php --help       # 显示此帮助信息\n";
    exit(0);
}

if (isset($options['sql'])) {
    echo "=== 初始化SQL语句 ===\n\n";
    
    $createDatabaseSQL = "CREATE DATABASE IF NOT EXISTS `navi` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
    echo "-- 创建数据库\n" . $createDatabaseSQL . "\n\n";
    
    $createCategoriesSQL = "CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    color VARCHAR(7) DEFAULT '#007bff',
    icon VARCHAR(50),
    order_index INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    echo "-- 创建分类表\n" . $createCategoriesSQL . "\n\n";
    
    $createLinksSQL = "CREATE TABLE IF NOT EXISTS navigation_links (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    echo "-- 创建导航链接表\n" . $createLinksSQL . "\n\n";
    
    $createPreferencesSQL = "CREATE TABLE IF NOT EXISTS user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(100) NOT NULL,
    preference_key VARCHAR(100) NOT NULL,
    preference_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_preference (user_id, preference_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    echo "-- 创建用户偏好设置表\n" . $createPreferencesSQL . "\n\n";
    
    exit(0);
}

if (isset($options['schema'])) {
    echo "=== 数据库表结构 ===\n\n";
    
    $tables = [
        'categories' => [
            'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
            'name' => 'VARCHAR(100) NOT NULL UNIQUE',
            'description' => 'TEXT',
            'color' => 'VARCHAR(7) DEFAULT "#007bff"',
            'icon' => 'VARCHAR(50)',
            'order_index' => 'INT DEFAULT 0',
            'is_active' => 'BOOLEAN DEFAULT TRUE',
            'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ],
        'navigation_links' => [
            'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
            'title' => 'VARCHAR(200) NOT NULL',
            'url' => 'VARCHAR(500) NOT NULL',
            'description' => 'TEXT',
            'category_id' => 'INT NOT NULL',
            'icon_url' => 'VARCHAR(500)',
            'order_index' => 'INT DEFAULT 0',
            'is_active' => 'BOOLEAN DEFAULT TRUE',
            'click_count' => 'INT DEFAULT 0',
            'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ],
        'user_preferences' => [
            'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
            'user_id' => 'VARCHAR(100) NOT NULL',
            'preference_key' => 'VARCHAR(100) NOT NULL',
            'preference_value' => 'TEXT',
            'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ]
    ];
    
    foreach ($tables as $tableName => $columns) {
        echo "表名: {$tableName}\n";
        echo "字段:\n";
        foreach ($columns as $column => $type) {
            echo "  {$column}: {$type}\n";
        }
        echo "\n";
    }
    
    exit(0);
}

if (isset($options['summary'])) {
    echo "=== 配置摘要 ===\n";
    echo "当前环境: " . Config::getCurrentEnv() . "\n";
    
    $config = Config::getDatabaseConfig();
    echo "主机: {$config['host']}:{$config['port']}\n";
    echo "数据库: {$config['database']}\n";
    echo "用户: {$config['username']}\n";
    echo "字符集: {$config['charset']}\n";
    echo "DSN: " . Config::getDsn() . "\n\n";
    
    echo "=== 支持的环境 ===\n";
    $environments = ['development', 'production', 'test'];
    foreach ($environments as $env) {
        $config = Config::getDatabaseConfig($env);
        echo "- {$env}: {$config['host']}:{$config['port']}/{$config['database']}\n";
    }
    
    exit(0);
}

// 默认：测试数据库连接
echo "=== 数据库连接测试 ===\n";

try {
    $db = Database::getInstance();
    
    if ($db->testConnection()) {
        echo "✅ 连接成功！\n";
        
        // 显示配置信息
        $config = Config::getDatabaseConfig();
        echo "📊 配置信息：\n";
        echo "   主机: {$config['host']}:{$config['port']}\n";
        echo "   数据库: {$config['database']}\n";
        echo "   用户: {$config['username']}\n";
        echo "   环境: " . Config::getCurrentEnv() . "\n";
        echo "\n🔗 DSN: " . Config::getDsn() . "\n";
        
        // 测试查询
        $pdo = $db->getConnection();
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM categories");
        $result = $stmt->fetch();
        echo "📋 分类数量: {$result['count']}\n";
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM navigation_links");
        $result = $stmt->fetch();
        echo "🔗 导航链接数量: {$result['count']}\n";
        
    } else {
        echo "❌ 连接失败！\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "❌ 连接失败: " . $e->getMessage() . "\n";
    exit(1);
}
?>