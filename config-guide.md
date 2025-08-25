# 网站配置信息梳理文档

## 概述
本文档梳理了导航网站的所有配置信息，包括`config.php`配置、数据库设置、后台配置之间的关系和使用方式。

## 📁 配置文件结构

### 1. config.php（核心配置文件）
**位置**: 根目录 `config.php`
**作用**: 系统级配置，数据库连接等核心设置

#### 必需配置项
```php
// 数据库配置
define('DB_HOST', 'localhost');      // 数据库服务器
define('DB_PORT', '3306');          // 数据库端口
define('DB_NAME', 'navi_db');      // 数据库名称
define('DB_USER', 'root');         // 数据库用户名
define('DB_PASS', 'password');     // 数据库密码
define('DB_CHARSET', 'utf8mb4');   // 数据库字符集

// 网站基本信息
define('SITE_NAME', '我的导航网站');
define('SITE_DESCRIPTION', '一个简洁实用的导航网站');
define('SITE_KEYWORDS', '导航,工具,网站,收藏');
define('SITE_URL', 'https://yoursite.com');

// 管理员配置
define('ADMIN_PASSWORD_HASH', '$2y$10$...'); // 使用 password_hash() 生成

// 系统状态
define('SITE_INSTALLED', true);     // 安装完成后设为true
define('DEBUG_MODE', false);        // 生产环境设为false
```

#### 可选配置项
```php
// 时区和本地化
define('TIMEZONE', 'Asia/Shanghai');

// 上传配置
define('UPLOAD_MAX_SIZE', 2097152); // 2MB
define('UPLOAD_ALLOWED_TYPES', ['jpg','jpeg','png','gif','webp','svg']);

// 分页配置
define('LINKS_PER_PAGE', 20);
```

### 2. 数据库设置表（settings）
**位置**: 数据库 `settings` 表
**作用**: 存储网站运行时配置，可通过后台修改

#### 设置项分类

##### 🔧 网站基本信息
| 设置键 | 默认值 | 说明 |
|--------|--------|------|
| `site_name` | "我的导航网站" | 网站名称 |
| `site_description` | "一个简洁美观的导航网站" | 网站描述 |
| `site_keywords` | "导航,工具,网站,收藏" | SEO关键词 |
| `site_author` | "网站管理员" | 网站作者 |
| `site_url` | "" | 网站完整URL |

##### 🎨 显示设置
| 设置键 | 默认值 | 说明 |
|--------|--------|------|
| `site_logo_type` | "none" | Logo类型: none, image, icon |
| `site_logo_image` | "" | Logo图片URL |
| `site_logo_icon` | "fas fa-home" | Font Awesome图标类 |
| `site_logo_show` | "1" | 是否显示Logo |
| `site_favicon` | "" | 网站图标URL |
| `site_apple_touch_icon` | "" | Apple Touch图标 |

##### 🖼️ 背景设置
| 设置键 | 默认值 | 说明 |
|--------|--------|------|
| `background_type` | "api" | 背景类型: none, image, color, api |
| `background_image` | "" | 背景图片URL |
| `background_color` | "#f8f9fa" | 背景颜色值 |
| `background_api` | "https://www.dmoe.cc/random.php" | 背景API地址 |
| `background_api_key` | "" | API密钥（如需） |

##### 📄 页脚设置
| 设置键 | 默认值 | 说明 |
|--------|--------|------|
| `footer_content` | "© 2024 我的导航站. 保留所有权利." | 页脚内容 |
| `footer_show` | "1" | 是否显示页脚 |

##### 📤 上传设置
| 设置键 | 默认值 | 说明 |
|--------|--------|------|
| `max_upload_size` | "2097152" | 最大上传大小（字节） |
| `allowed_extensions` | "jpg,jpeg,png,gif,svg,webp" | 允许的文件类型 |

##### 🔍 透明度设置
| 设置键 | 默认值 | 说明 |
|--------|--------|------|
| `header_opacity` | "0.9" | 标题区域透明度 |
| `category_opacity` | "0.8" | 分类区域透明度 |
| `links_opacity` | "0.9" | 链接区域透明度 |
| `link_card_opacity` | "0.7" | 链接卡片透明度 |

##### ⚙️ 系统设置
| 设置键 | 默认值 | 说明 |
|--------|--------|------|
| `maintenance_mode` | "0" | 维护模式开关 |
| `items_per_page` | "20" | 每页显示数量 |
| `timezone` | "Asia/Shanghai" | 时区设置 |
| `date_format` | "Y-m-d H:i:s" | 日期格式 |

### 3. 文件配置优先级

#### 配置加载顺序
1. **config.php** - 系统级配置（必需）
2. **数据库settings表** - 用户可修改配置
3. **默认值** - 当配置不存在时的回退值

#### 配置覆盖规则
- 数据库设置 > 配置文件设置 > 默认值
- 用户通过后台修改的设置会存储在数据库中
- config.php中的配置作为系统级设置不可被用户修改

## 🚀 配置使用示例

### 在前台获取配置
```php
// 获取网站名称
$site_name = get_site_setting('site_name', '默认名称');

// 获取背景设置
$background_type = get_site_setting('background_type', 'color');
```

### 在后台修改配置
```php
// 更新网站名称
set_site_setting('site_name', '新的网站名称');

// 更新背景API
set_site_setting('background_api', 'https://new-api.com/image');
```

## 🔧 安装时配置初始化

### install.php中的配置初始化
安装程序会自动创建以下设置：

1. **创建settings表** - 存储所有可配置项
2. **插入默认设置** - 23个完整配置项
3. **设置初始值** - 与后台设置页面保持一致

### 配置验证
安装程序会验证：
- config.php文件是否存在
- 数据库连接是否成功
- 必要配置项是否完整

## 📋 配置文件检查清单

### 首次部署时
- [ ] 复制 `config-sample.php` 为 `config.php`
- [ ] 填写数据库连接信息
- [ ] 设置管理员密码哈希
- [ ] 配置网站基本信息
- [ ] 设置正确的网站URL
- [ ] 将 `SITE_INSTALLED` 设为 `true`

### 生产环境
- [ ] 关闭 `DEBUG_MODE`
- [ ] 使用强密码
- [ ] 配置HTTPS
- [ ] 设置正确的文件权限
- [ ] 配置定期备份

### 开发环境
- [ ] 开启 `DEBUG_MODE`
- [ ] 使用本地数据库
- [ ] 配置开发用域名
- [ ] 设置宽松的文件权限

## 🔄 配置更新策略

### 新增配置项
1. 在 `install.php` 中添加默认设置
2. 在后台设置页面添加对应选项
3. 在前台代码中添加使用逻辑

### 修改配置项
1. 更新 `config-sample.php` 模板
2. 更新数据库中的默认值
3. 确保向后兼容性

### 删除配置项
1. 从 `install.php` 中移除
2. 提供数据迁移脚本
3. 更新相关代码

## 📝 最佳实践

### 安全配置
- 使用环境变量存储敏感信息
- 定期更新密码
- 限制文件权限
- 使用HTTPS

### 性能优化
- 缓存常用配置
- 减少数据库查询
- 使用CDN加速静态资源

### 维护建议
- 定期备份配置文件
- 使用版本控制管理配置变更
- 建立配置文档
- 测试配置变更的影响