# 管理后台安装指南

## 系统要求

### PHP扩展要求
确保已安装以下PHP扩展：
- `pdo` - PHP数据对象
- `pdo_mysql` - MySQL驱动
- `gd` - 图像处理（用于图标上传）
- `mbstring` - 多字节字符串处理
- `json` - JSON支持

### Windows系统安装扩展

1. **打开php.ini文件**（通常位于PHP安装目录）
2. **取消以下行的注释**（删除前面的分号）：
   ```ini
   extension=pdo_mysql
   extension=gd
   extension=mbstring
   extension=json
   ```
3. **重启Web服务器**

### 验证扩展安装
运行以下命令检查扩展：
```bash
php -m | findstr -E "(pdo|mysql|gd|mbstring|json)"
```

## 安装步骤

### 1. 数据库初始化
```bash
# 运行数据库初始化脚本
cd e:\Trae\navi
php db-init.php
```

### 2. 访问管理后台
打开浏览器访问：http://localhost:8000/admin/

### 3. 首次登录
- **用户名**: admin
- **密码**: admin123

**重要**：首次登录后请立即修改密码！

## 目录权限

确保以下目录具有写入权限：
- `uploads/categories/` - 分类图标
- `uploads/links/` - 链接图标
- `uploads/files/` - 文件上传


## 故障排除

### 常见错误及解决方案

#### 1. "Undefined constant SITE_URL"
- 确保 `config.php` 文件存在且包含必要的常量定义
- 检查文件路径是否正确

#### 2. "缺少PHP扩展"
- 安装缺失的扩展（见上文系统要求）
- 重启Web服务器

#### 3. "数据库连接失败"
- 检查数据库配置是否正确
- 确保MySQL服务正在运行
- 验证用户名和密码

#### 4. "目录不可写"
- 在Windows上，确保IIS或Apache有写入权限
- 手动创建缺失的目录

### 调试模式
在开发环境中，可以在 `config.php` 中启用调试模式：
```php
define('DEBUG_MODE', true);
```

## 验证安装

运行安装检查脚本：
```bash
cd e:\Trae\navi\admin
php check-install.php
```

## 生产环境部署

### 安全建议
1. 修改默认管理员密码
2. 设置强密码策略
3. 启用HTTPS
4. 限制IP访问（可选）
5. 定期备份数据

### 性能优化
1. 启用OPcache
2. 设置适当的PHP内存限制
3. 配置数据库连接池

## 技术支持

如果遇到问题，请检查：
1. 服务器日志
2. PHP错误日志
3. 浏览器开发者工具
4. 数据库连接状态

## 快速开始

1. **启动服务器**：
   ```bash
   php -S localhost:8000 -t e:\Trae\navi
   ```

2. **访问后台**：http://localhost:8000/admin/

3. **登录**：admin / admin123

4. **开始使用**：按照MANUAL.md中的指南操作