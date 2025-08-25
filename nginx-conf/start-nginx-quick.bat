@echo off
title nginx快速启动 - e:/Trae/navi
color 0A
echo.
echo ========================================
echo 🚀 nginx一键启动脚本
echo 项目：e:/Trae/navi
echo 端口：55555
echo ========================================
echo.

:: 切换到PowerShell执行启动脚本
powershell -ExecutionPolicy Bypass -File "%~dp0nginx-quick-start.ps1"

echo.
echo 按任意键退出...
pause >nul