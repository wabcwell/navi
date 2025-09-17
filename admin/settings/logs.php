<?php
require_once 'includes/load.php';

// 检查登录状态
if (!is_logged_in()) {
    header('Location: ../login.php');
    exit();
}

$pdo = get_db_connection();

// 获取日志类型
$log_type = $_GET['type'] ?? 'admin';

// 获取分页参数
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 50;
$offset = ($page - 1) * $per_page;

// 搜索参数
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// 构建查询条件
$where_conditions = [];
$params = [];

switch ($log_type) {
    case 'login':
        $table = 'login_logs';
        break;
    case 'error':
        $table = 'error_logs';
        break;
    case 'operation':
        $table = 'operation_logs';
        break;
    default:
        $table = 'admin_logs';
        break;
}

if ($search) {
    $where_conditions[] = "(message LIKE :search OR username LIKE :search2)";
    $params['search'] = '%' . $search . '%';
    $params['search2'] = '%' . $search . '%';
}

if ($date_from) {
    $where_conditions[] = "created_at >= :date_from";
    $params['date_from'] = $date_from . ' 00:00:00';
}

if ($date_to) {
    $where_conditions[] = "created_at <= :date_to";
    $params['date_to'] = $date_to . ' 23:59:59';
}

$where_sql = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// 获取总记录数
$count_sql = "SELECT COUNT(*) FROM {$table} {$where_sql}";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_records = $stmt->fetchColumn();
$total_pages = max(1, ceil($total_records / $per_page));

// 获取日志列表
$sql = "SELECT * FROM {$table} {$where_sql} ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
$params['limit'] = $per_page;
$params['offset'] = $offset;
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 处理批量删除
if (isset($_POST['batch_delete'])) {
    $ids = $_POST['ids'] ?? [];
    
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $delete_sql = "DELETE FROM {$table} WHERE id IN ({$placeholders})";
        $stmt = $pdo->prepare($delete_sql);
        $stmt->execute($ids);
        
        $_SESSION['success'] = '已删除 ' . count($ids) . ' 条日志';
        header("Location: logs.php?type={$log_type}");
        exit();
    }
}

// 处理清空日志
if (isset($_POST['clear_logs'])) {
    $pdo->exec("TRUNCATE TABLE {$table}");
    $_SESSION['success'] = '日志已清空';
    header("Location: logs.php?type={$log_type}");
    exit();
}

// 获取日志统计
$stats = [
    'today' => 0,
    'week' => 0,
    'month' => 0,
    'total' => $total_records
];

foreach ($stats as $period => &$count) {
    if ($period !== 'total') {
        $date_condition = match($period) {
            'today' => 'DATE(created_at) = CURDATE()',
            'week' => 'created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)',
            'month' => 'created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)',
            default => '1=1'
        };
        
        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$date_condition}";
        $count = $pdo->query($sql)->fetchColumn();
    }
}

$page_title = '系统日志';
include '../templates/header.php';
?>

<div class="page-header">
    <h1>系统日志</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../dashboard.php">仪表盘</a></li>
            <li class="breadcrumb-item"><a href="general.php">网站设置</a></li>
            <li class="breadcrumb-item active">系统日志</li>
        </ol>
    </nav>
</div>

<div class="mb-4">
    <button type="button" class="btn btn-danger" onclick="clearAllLogs()">
        <i class="bi bi-trash"></i> 清空日志
    </button>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- 日志类型切换 -->
<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link <?php echo $log_type === 'admin' ? 'active' : ''; ?>" 
           href="?type=admin">管理员操作日志</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $log_type === 'login' ? 'active' : ''; ?>" 
           href="?type=login">登录日志</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $log_type === 'error' ? 'active' : ''; ?>" 
           href="?type=error">错误日志</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $log_type === 'operation' ? 'active' : ''; ?>" 
           href="?type=operation">操作日志</a>
    </li>
</ul>

<!-- 统计信息 -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title"><?php echo $stats['today']; ?></h5>
                <p class="card-text text-muted">今日记录</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title"><?php echo $stats['week']; ?></h5>
                <p class="card-text text-muted">本周记录</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title"><?php echo $stats['month']; ?></h5>
                <p class="card-text text-muted">本月记录</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title"><?php echo $stats['total']; ?></h5>
                <p class="card-text text-muted">总记录数</p>
            </div>
        </div>
    </div>
</div>

<!-- 搜索和筛选 -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <input type="hidden" name="type" value="<?php echo $log_type; ?>">
            
            <div class="col-md-4">
                <label class="form-label">搜索内容</label>
                <input type="text" class="form-control" name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="搜索日志内容或用户名">
            </div>
            
            <div class="col-md-3">
                <label class="form-label">开始日期</label>
                <input type="date" class="form-control" name="date_from" 
                       value="<?php echo htmlspecialchars($date_from); ?>">
            </div>
            
            <div class="col-md-3">
                <label class="form-label">结束日期</label>
                <input type="date" class="form-control" name="date_to" 
                       value="<?php echo htmlspecialchars($date_to); ?>">
            </div>
            
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-search"></i> 搜索
                </button>
                <a href="?type=<?php echo $log_type; ?>" class="btn btn-secondary">
                    <i class="bi bi-arrow-clockwise"></i> 重置
                </a>
            </div>
        </form>
    </div>
</div>

<!-- 日志列表 -->
<form method="POST" id="batchForm">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <button type="submit" name="batch_delete" class="btn btn-danger btn-sm" 
                    onclick="return confirm('确定要删除选中的日志吗？')" 
                    style="display: none;" id="batchDeleteBtn">
                <i class="bi bi-trash"></i> 批量删除
            </button>
        </div>
        
        <?php if ($logs): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th width="50">
                                <input type="checkbox" id="selectAll" class="form-check-input">
                            </th>
                            <th>时间</th>
                            <th>用户</th>
                            <th>内容</th>
                            <th>IP地址</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="ids[]" value="<?php echo $log['id']; ?>" 
                                           class="form-check-input log-checkbox">
                                </td>
                                <td>
                                    <small><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($log['username'] ?? '系统'); ?></span>
                                </td>
                                <td>
                                    <div style="max-width: 400px; overflow: hidden; text-overflow: ellipsis;">
                                        <?php echo htmlspecialchars($log['message'] ?? $log['action'] ?? ''); ?>
                                    </div>
                                </td>
                                <td>
                                    <small class="text-muted"><?php echo htmlspecialchars($log['ip_address'] ?? ''); ?></small>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            onclick="deleteLog(<?php echo $log['id']; ?>)" 
                                            title="删除">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- 分页 -->
            <?php if ($total_pages > 1): ?>
                <div class="card-footer">
                    <nav>
                        <ul class="pagination justify-content-center mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">上一页</a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">下一页</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="card-body text-center py-5">
                <i class="bi bi-search" style="font-size: 3rem; color: #dee2e6;"></i>
                <h5 class="text-muted mt-3">没有找到相关日志</h5>
                <p class="text-muted">尝试调整搜索条件或清空筛选</p>
            </div>
        <?php endif; ?>
    </div>
</form>

<!-- 删除单条日志表单 -->
<form method="POST" id="deleteLogForm" style="display: none;">
    <input type="hidden" name="delete_log" value="1">
    <input type="hidden" name="log_id" id="logId">
</form>

<script>
// 全选/取消全选
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.log-checkbox');
    checkboxes.forEach(checkbox => checkbox.checked = this.checked);
    updateBatchDeleteButton();
});

// 监听单个复选框变化
document.querySelectorAll('.log-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', updateBatchDeleteButton);
});

// 更新批量删除按钮状态
function updateBatchDeleteButton() {
    const checkedBoxes = document.querySelectorAll('.log-checkbox:checked');
    const batchDeleteBtn = document.getElementById('batchDeleteBtn');
    batchDeleteBtn.style.display = checkedBoxes.length > 0 ? 'block' : 'none';
}

// 删除单条日志
function deleteLog(logId) {
    if (confirm('确定要删除这条日志吗？')) {
        document.getElementById('logId').value = logId;
        document.getElementById('deleteLogForm').submit();
    }
}

// 清空所有日志
function clearAllLogs() {
    if (confirm('确定要清空所有日志吗？此操作不可恢复！')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="clear_logs" value="1">';
        document.body.appendChild(form);
        form.submit();
    }
}

// 初始化
updateBatchDeleteButton();
</script>

<?php include '../templates/footer.php'; ?>