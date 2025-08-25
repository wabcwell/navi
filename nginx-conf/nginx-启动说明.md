# nginx启动指南

## 🚀 快速启动

### 方法1：一键启动（推荐）
双击运行：`start-nginx-quick.bat`

### 方法2：PowerShell启动
```powershell
.\nginx-quick-start.ps1
```

### 方法3：手动启动
```powershell
# 1. 复制配置文件
copy e:\Trae\navi\nginx-no80.conf D:\nginx\conf\nginx.conf

# 2. 启动nginx
cd D:\nginx
start nginx.exe
```

## 📋 配置信息

| 项目 | 值 |
|------|-----|
| **项目目录** | e:/Trae/navi |
| **nginx目录** | D:/nginx |
| **监听端口** | 55555 |
| **访问地址** | http://localhost:55555 |

## 🔧 文件说明

- `nginx-no80.conf` - nginx配置文件（55555端口）
- `nginx-quick-start.ps1` - 一键启动PowerShell脚本
- `start-nginx-quick.bat` - 双击启动批处理文件

## 🚨 常见问题

1. **端口被占用**：检查55555端口是否被其他程序使用
2. **权限问题**：确保有权限访问D:\nginx目录
3. **配置文件错误**：检查nginx-no80.conf格式是否正确

## 📊 验证服务

启动后，在浏览器访问：
```
http://localhost:55555
```

如果看到导航网页，说明服务启动成功！