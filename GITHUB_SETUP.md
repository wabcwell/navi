# GitHub集成指南

## 已完成的步骤
✅ Git仓库已初始化
✅ 初始提交已完成
✅ .gitignore文件已创建

## 连接到GitHub

### 方法1：使用HTTPS（推荐新手）
```bash
git remote add origin https://github.com/YOUR_USERNAME/navi.git
git branch -M main
git push -u origin main
```

### 方法2：使用SSH（推荐）
1. 生成SSH密钥：
```bash
ssh-keygen -t ed25519 -C "your.email@example.com"
```

2. 添加SSH密钥到GitHub：
- 复制公钥内容：`cat ~/.ssh/id_ed25519.pub`
- GitHub → Settings → SSH and GPG keys → New SSH key

3. 添加远程仓库：
```bash
git remote add origin git@github.com:YOUR_USERNAME/navi.git
git branch -M main
git push -u origin main
```

## 日常使用命令
```bash
# 查看状态
git status

# 添加更改
git add .

# 提交更改
git commit -m "描述你的更改"

# 推送到GitHub
git push

# 拉取更新
git pull
```

## Trae IDE快捷操作
- `Ctrl+Shift+G`：打开Git面板
- 文件修改后：点击源代码管理图标 → 输入提交消息 → 点击✓提交
- 右键文件 → 源代码管理 → 查看更改