# PowerShell一键启动nginx脚本
# 项目：e:/Trae/navi
# 端口：55555
# 作者：自动生成

Write-Host "🚀 nginx一键启动脚本" -ForegroundColor Cyan
Write-Host "项目目录：e:/Trae/navi" -ForegroundColor Green
Write-Host "访问地址：http://localhost:55555" -ForegroundColor Yellow
Write-Host "========================================" -ForegroundColor Gray

# 检查nginx是否已运行
$nginxRunning = Get-Process nginx -ErrorAction SilentlyContinue
if ($nginxRunning) {
    Write-Host "⚠️  nginx已在运行，正在重启..." -ForegroundColor Yellow
    Stop-Process -Name nginx -Force -ErrorAction SilentlyContinue
    Start-Sleep -Seconds 2
}

# 复制最新配置文件
Write-Host "📋 正在更新配置文件..." -ForegroundColor Blue
Copy-Item "e:\Trae\navi\nginx-conf\nginx-no80.conf" "D:\nginx\conf\nginx.conf" -Force

# 启动nginx服务
Write-Host "🔧 正在启动nginx服务..." -ForegroundColor Blue
Set-Location "D:\nginx"
Start-Process -FilePath ".\nginx.exe" -WorkingDirectory "D:\nginx" -WindowStyle Hidden

# 等待服务启动
Start-Sleep -Seconds 3

# 验证服务状态
$nginxProcesses = Get-Process nginx -ErrorAction SilentlyContinue
if ($nginxProcesses) {
    Write-Host "✅ nginx启动成功！" -ForegroundColor Green
    Write-Host "访问地址：http://localhost:55555" -ForegroundColor Yellow
    Write-Host "进程数量：$($nginxProcesses.Count)" -ForegroundColor Cyan
    
    # 测试端口
    try {
        $response = Invoke-WebRequest -Uri "http://localhost:55555" -UseBasicParsing -TimeoutSec 5
        if ($response.StatusCode -eq 200) {
            Write-Host "🌐 端口55555响应正常" -ForegroundColor Green
        }
    } catch {
        Write-Host "⚠️  端口测试失败，请检查配置" -ForegroundColor Red
    }
} else {
    Write-Host "❌ nginx启动失败，请检查错误日志" -ForegroundColor Red
    Write-Host "日志位置：D:\nginx\logs\error.log" -ForegroundColor Yellow
}

Write-Host "========================================" -ForegroundColor Gray
Write-Host "按任意键退出..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")