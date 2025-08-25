@echo off
echo 正在停止Nginx服务器...
cd /d d:\nginx
nginx.exe -s stop
echo Nginx服务器已停止！
echo 按任意键继续...
pause