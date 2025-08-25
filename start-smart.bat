@echo off
echo === 智能导航网站启动器 ===
echo.

REM 检查PHP
echo 正在检查PHP环境...
if not exist "D:\php\php.exe" (
    echo ❌ 未找到PHP，路径: D:\php\php.exe
    echo 请确认PHP安装路径
    pause
    exit /b
)

echo ✅ PHP已找到: D:\php\php.exe
D:\php\php.exe -v
echo.

REM 检查MySQL扩展
echo 正在检查MySQL扩展...
D:\php\php.exe -r "extension_loaded('pdo_mysql') ? exit(0) : exit(1);"
if %errorlevel%==0 (
    echo ✅ MySQL扩展已启用
    echo 启动完整版...
    timeout /t 2 /nobreak > nul
    echo.
    echo 访问地址: http://localhost:8000
    D:\php\php.exe -S localhost:8000 -t .
) else (
    echo ❌ 错误: 未检测到pdo_mysql扩展！
    echo.
    echo 请先安装并启用MySQL扩展：
    echo 1. 编辑 php.ini 取消 extension=pdo_mysql 注释
    echo 2. 重启Web服务器
    echo.
    echo 或运行 fix-mysql-driver.bat 获取详细帮助
    echo.
    pause
    exit /b 1
)

pause