# 简易版本控制系统
param(
    [Parameter(Mandatory=$true)]
    [string]$Action,
    [string]$Message = "",
    [string]$File = ""
)

$BackupDir = "e:\Trae\navi\backups"
$LogFile = "$BackupDir\version-log.txt"
$Timestamp = Get-Date -Format "yyyyMMdd_HHmmss"

# 创建备份目录
if (!(Test-Path $BackupDir)) {
    New-Item -ItemType Directory -Path $BackupDir -Force
}

function Backup-File {
    param([string]$SourceFile)
    if (Test-Path $SourceFile) {
        $FileName = [System.IO.Path]::GetFileName($SourceFile)
        $BackupFile = "$BackupDir\${Timestamp}_${FileName}"
        Copy-Item $SourceFile $BackupFile
        
        # 记录日志
        $LogEntry = "$Timestamp - 备份: $FileName - 消息: $Message - 文件: $BackupFile"
        Add-Content $LogFile $LogEntry
        
        Write-Host "已备份: $BackupFile" -ForegroundColor Green
    }
}

function Restore-File {
    param([string]$BackupFile)
    if (Test-Path "$BackupDir\$BackupFile") {
        $OriginalFile = [System.IO.Path]::GetFileName($BackupFile.Substring(16)) # 移除时间戳
        $OriginalPath = "e:\Trae\navi\admin\settings\$OriginalFile"
        Copy-Item "$BackupDir\$BackupFile" $OriginalPath -Force
        Write-Host "已恢复: $OriginalPath" -ForegroundColor Yellow
    }
}

function List-Backups {
    Write-Host "备份历史:" -ForegroundColor Cyan
    if (Test-Path $LogFile) {
        Get-Content $LogFile
    }
    Write-Host ""
    Write-Host "备份文件:" -ForegroundColor Cyan
    Get-ChildItem $BackupDir -Filter "*.php" | Sort-Object LastWriteTime -Descending | Select-Object Name, LastWriteTime
}

switch ($Action) {
    "backup" {
        if ($File) {
            Backup-File $File
        } else {
            # 备份重要文件
            Backup-File "e:\Trae\navi\admin\settings\general.php"
        }
    }
    "restore" {
        if ($File) {
            Restore-File $File
        } else {
            Write-Host "请指定要恢复的备份文件名" -ForegroundColor Red
        }
    }
    "list" {
        List-Backups
    }
    default {
        Write-Host "使用方法:"
        Write-Host "  备份: .\backup-system.ps1 -Action backup -Message '修改描述'"
        Write-Host "  恢复: .\backup-system.ps1 -Action restore -File '备份文件名'"
        Write-Host "  列表: .\backup-system.ps1 -Action list"
    }
}