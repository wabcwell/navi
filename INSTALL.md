# 网站安装指南

## 🚀 快速安装

### 方法一：使用安装向导（推荐）

1. **访问安装页面**
   打开浏览器访问：http://localhost:8000/install.php

2. **填写信息**
   - 网站名称：您的网站标题
   - 网站描述：网站的简短描述
   - 管理密码：后台登录密码（至少6位）
   - 数据库信息：MySQL连接信息

3. **完成安装**
   点击"开始安装"按钮，系统将自动完成配置

### 方法二：手动安装

#### 1. 创建数据库
```sql
CREATE DATABASE navigation CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

#### 2. 配置数据库连接
复制 `config.example.php` 为 `config.php`，并填写您的数据库信息：

```php
<?php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'navigation');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_CHARSET', 'utf8mb4');

define('SITE_NAME', '我的导航站');
define('SITE_DESCRIPTION', '一个简洁实用的导航网站');
define('ADMIN_PASSWORD_HASH', '$2y$10$...'); // 使用 password_hash() 生成的密码哈希

define('SITE_INSTALLED', true);
?>
```

#### 3. 初始化数据库
运行以下命令初始化数据库：
```bash
php db-init.php
```

## 📋 系统要求

- **PHP版本**: 7.4.0 或更高
- **数据库**: MySQL 5.7+ 或 MariaDB 10.2+
- **必需扩展**: PDO, PDO_MySQL, JSON
- **Web服务器**: Apache/Nginx/PHP内置服务器

## 🔧 配置说明

### 数据库配置
- **主机**: 通常是 localhost
- **端口**: 默认 3306
- **数据库名**: 您创建的数据库名称
- **用户名**: 数据库用户名
- **密码**: 数据库密码

### 管理员设置
- 安装后使用密码登录后台：/admin/index.php
- 首次登录后会提示修改密码

## 🎯 安装后操作

1. **删除安装文件**（安全建议）
   ```bash
   rm install.php
   ```

2. **访问网站**
   - 前台：http://localhost:8000
   - 后台：http://localhost:8000/admin/index.php

3. **开始使用**
   - 添加分类：在后台创建导航分类
   - 添加链接：为每个分类添加网站链接
   - 自定义：设置网站背景、标题等

## 🐛 常见问题

### 数据库连接失败
- 检查数据库服务器是否运行
- 确认用户名和密码正确
- 检查防火墙设置

### 权限错误
- 确保 web 服务器有写入权限
- Linux: `chmod 755` 相关目录
- Windows: 检查文件夹权限

### PHP扩展缺失
- 安装缺失的扩展
- 重启 web 服务器

## 📞 技术支持

如果遇到问题，请检查：
1. PHP错误日志
2. 浏览器开发者工具
3. 数据库连接测试文件：check-db.php

## 🔐 安全建议

1. 使用强密码
2. 定期更新系统
3. 限制数据库用户权限
4. 使用HTTPS（生产环境）
5. 定期维护数据库