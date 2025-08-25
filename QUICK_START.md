# 导航网站管理后台 - 快速开始指南

## 🎯 当前状态
✅ 管理后台文件已完整配置
✅ PHP服务器正在运行 (http://localhost:8000)
✅ 所有管理后台文件存在且配置正确
⚠️  需要配置MySQL数据库

## 📋 下一步操作

### 1. 启动MySQL服务
确保MySQL服务正在运行：
- Windows: 打开服务管理器，启动MySQL服务
- XAMPP/WAMP: 启动控制面板中的MySQL

### 2. 创建数据库和用户
```sql
-- 登录MySQL (使用您的root密码)
mysql -u root -p

-- 创建数据库
CREATE DATABASE navi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 创建用户并授权
CREATE USER 'navi'@'localhost' IDENTIFIED BY 'navi123';
GRANT ALL PRIVILEGES ON navi.* TO 'navi'@'localhost';
FLUSH PRIVILEGES;

-- 使用数据库
USE navi;

-- 创建表结构（从install.php复制）
-- 或直接运行：
SOURCE install.php
```

### 3. 验证安装
运行测试脚本：
```bash
cd e:\Trae\navi\admin
php test-complete.php
```

### 4. 访问管理后台
- **登录地址**: http://localhost:8000/admin/index.php
- **默认账号**: admin
- **默认密码**: admin123

## 🚀 一键启动
使用以下命令快速启动：
```bash
# 启动PHP开发服务器
php -S localhost:8000 -t e:\Trae\navi

# 然后在浏览器中访问
# http://localhost:8000/admin/index.php
```

## 📁 管理后台功能
- ✅ 分类管理 (添加/编辑/删除)
- ✅ 链接管理 (添加/编辑/删除)
- ✅ 文件上传管理
- ✅ 用户管理
- ✅ 系统设置

- ✅ 操作日志

## 🔧 配置文件说明
- `config.php` - 主配置文件
- `admin/includes/init.php` - 管理后台初始化
- `admin/INSTALL_GUIDE.md` - 详细安装指南

## 📞 遇到问题？
1. 检查MySQL服务是否运行
2. 确认数据库用户权限
3. 查看 `admin/check-install.php` 获取诊断信息
4. 参考 `admin/INSTALL_GUIDE.md` 完整安装指南