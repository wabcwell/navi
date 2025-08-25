# 导航网站 (纯PHP版本)

一个简洁的网址导航网站，使用PHP + MySQL开发，无需Node.js环境，开箱即用。

## 🚀 快速开始

### 环境要求
- PHP 7.4 或更高版本
- MySQL 5.7+ 或 MariaDB 10.2+
- pdo_mysql PHP扩展

### 一键启动
1. **初始化数据库**
   ```bash
   D:\php\php.exe db-init.php
   ```

2. **启动服务器**
   ```bash
   # 双击运行 start-smart.bat
   # 或手动启动:
   D:\php\php.exe -S localhost:8000 -t .
   ```

3. **访问网站**
   打开浏览器访问: http://localhost:8000

## 📁 项目结构

```
navi/
├── index.php              # 主页入口 (MySQL版本)
├── config.php             # MySQL数据库配置
├── server.php             # PHP内置服务器启动脚本
├── db-init.php            # MySQL数据库初始化
├── db-test.php            # 数据库连接测试
├── api.php                # RESTful API接口
├── views/
│   └── home.php           # 主页视图
├── css/
│   └── style.css          # 样式文件
├── js/
│   └── script.js          # JavaScript文件
├── img/                   # 图片资源
├── nginx-conf/            # Nginx配置
├── start-*.bat            # 各种启动脚本
└── README.md              # 项目说明
```

## 🛠️ 配置说明

### 数据库配置
编辑 `config.php` 文件修改数据库连接信息：
```php
<?php
// 数据库配置
return [
    'host' => 'localhost',      // 数据库主机
    'username' => 'navi',       // 数据库用户名
    'password' => 'qwer123',    // 数据库密码
    'database' => 'navi',       // 数据库名称
    'port' => 3306              // 数据库端口
];
?>
```

### 环境配置
支持多环境配置，通过修改 `config.php` 适配不同环境：
- **开发环境**：本地MySQL
- **测试环境**：测试服务器
- **生产环境**：生产服务器

### 配置文件结构
```
config.php              # 主配置文件
db-init.php             # 数据库初始化
├── 创建分类表
├── 创建导航链接表
├── 创建用户偏好表
└── 插入示例数据
```

## 🎯 使用命令

### 基础命令
```bash
# 测试数据库连接
php db-test.php

# 初始化数据库
php db-init.php

# 重置数据库（谨慎使用）
php db-init.php --reset

# 检查数据库状态
php db-init.php --status
```

### 高级命令
```bash
# 显示配置摘要
php db-test.php --summary

# 显示表结构
php db-test.php --schema

# 显示初始化SQL
php db-test.php --sql

# 显示帮助信息
php db-test.php --help
```

### 使用示例
```bash
# 完整流程
php db-test.php          # 检查连接
php db-init.php          # 初始化数据库
php db-test.php --summary # 验证数据
```

## 🔌 API接口

### 获取所有数据
```
GET /api.php
```

### 获取分类列表
```
GET /api.php?action=categories
```

### 获取链接列表
```
GET /api.php?action=links
GET /api.php?action=links&category_id=1
```

### 搜索链接
```
POST /api.php?action=search
Content-Type: application/json

{"query": "搜索关键词"}
```

## 🌐 Nginx部署

如果使用Nginx，请参考 `nginx-conf/` 目录中的配置示例。