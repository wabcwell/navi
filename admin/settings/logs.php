<?php
require_once '../includes/load.php';

// 检查登录状态
if (!User::checkLogin()) {
    header('Location: login.php');
    exit();
}

$logsManager = get_logs_manager();

// 获取日志类型
$log_type = $_GET['type'] ?? 'operation';

// 获取分页参数
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 50;
$offset = ($page - 1) * $per_page;

// 搜索参数
$search = $_GET['search'] ?? '';
$selected_month = $_GET['selected_month'] ?? '';
$date_from = '';
$date_to = '';

// 如果选择了月份，设置对应的开始和结束日期
if ($selected_month) {
    $date_from = $selected_month . '-01';
    // 获取该月的最后一天
    $last_day = date('Y-m-t', strtotime($date_from));
    $date_to = $last_day;
}

$operation_module = $_GET['operation_module'] ?? '';
$operation_type = $_GET['operation_type'] ?? '';

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
        $table = 'operation_logs';
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
try {
    $logsManager = get_logs_manager();
    if ($log_type === 'operation') {
        $total_records = $logsManager->getOperationLogStats($search, $operation_module, $operation_type, $date_from, $date_to)['total'];
    } else {
        $total_records = $logsManager->getLogStats($table, $date_from, $date_to)['total'];
    }
    $total_pages = max(1, ceil($total_records / $per_page));
} catch (Exception $e) {
    $total_records = 0;
    $total_pages = 1;
}

// 获取日志列表
try {
    $logsManager = get_logs_manager();
    switch ($log_type) {
        case 'login':
            $logs = $logsManager->getLoginLogs($per_page, $offset, 'login_time', 'DESC');
            break;
        case 'error':
            $logs = $logsManager->getErrorLogs($per_page, $offset, 'created_at', 'DESC');
            break;
        case 'operation':
            $logs = $logsManager->getOperationLogs($per_page, $offset, 'operation_time', 'DESC', $search, $operation_module, $operation_type, $date_from, $date_to);
            break;
        default:
            $logs = $logsManager->getOperationLogs($per_page, $offset, 'operation_time', 'DESC', $search, $operation_module, $operation_type, $date_from, $date_to);
    }
} catch (Exception $e) {
    $logs = [];
}

// 处理批量删除
if (isset($_POST['batch_delete'])) {
    $ids = $_POST['ids'] ?? [];
    
    if (!empty($ids)) {
        try {
            $logsManager = get_logs_manager();
            $deleted_count = $logsManager->batchDeleteLogs($table, $ids);
            
            $_SESSION['success'] = '已删除 ' . $deleted_count . ' 条日志';
            
            // 记录操作日志
            $logsManager = get_logs_manager();
            $logsManager->addOperationLog([
                'userid' => $_SESSION['user_id'] ?? 0,
                'operation_module' => '日志',
                'operation_type' => '删除',
                'operation_details' => [
                    'log_type' => $log_type,
                    'deleted_count' => $deleted_count,
                    'deleted_ids' => $ids,
                    'deleted_at' => date('Y-m-d H:i:s'),
                    'target' => '批量删除'
                ],
                'status' => '成功'
            ]);
        } catch (Exception $e) {
            $_SESSION['error'] = '删除日志时出错: ' . $e->getMessage();
        }
        
        header("Location: logs.php?type={$log_type}");
        exit();
    }
}

// 处理清空日志
if (isset($_POST['clear_logs'])) {
    try {
        $logsManager = get_logs_manager();
        $result = false;
        
        switch ($log_type) {
            case 'login':
                $result = $logsManager->clearLoginLogs();
                break;
            case 'error':
                $result = $logsManager->clearErrorLogs();
                break;
            case 'operation':
                $result = $logsManager->clearOperationLogs();
                break;
            default:
                $result = $logsManager->clearOperationLogs();
                break;
        }
        
        if ($result) {
            // 记录操作日志
            $logsManager = get_logs_manager();
            $logsManager->addOperationLog([
                'userid' => $_SESSION['user_id'] ?? 0,
                'operation_module' => '日志',
                'operation_type' => '清空',
                'operation_details' => [
                    'log_type' => $log_type,
                    'cleared_at' => date('Y-m-d H:i:s'),
                    'target' => $log_type
                ],
                'status' => '成功'
            ]);
            
            $_SESSION['success'] = '日志已清空';
        } else {
            $_SESSION['error'] = '清空日志失败';
        }
    } catch (Exception $e) {
        $_SESSION['error'] = '清空日志时出错: ' . $e->getMessage();
    }
    
    header("Location: logs.php?type={$log_type}");
    exit();
}



$page_title = '系统日志';
include '../templates/header.php';
?>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- 日志类型切换 -->
<div class="row mb-4">
    <div class="col-12">
        <div class="nav nav-pills">
            <a class="nav-link <?php echo $log_type === 'login' ? 'active' : ''; ?>" 
               href="?type=login">
                <i class="bi bi-box-arrow-in-right"></i> 登录日志
            </a>
            <a class="nav-link <?php echo $log_type === 'error' ? 'active' : ''; ?>" 
               href="?type=error">
                <i class="bi bi-exclamation-triangle"></i> 错误日志
            </a>
            <a class="nav-link <?php echo $log_type === 'operation' ? 'active' : ''; ?>" 
               href="?type=operation">
                <i class="bi bi-gear"></i> 操作日志
            </a>
        </div>
    </div>
</div>



<!-- 搜索和筛选 -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <input type="hidden" name="type" value="<?php echo $log_type; ?>">
            
            <div class="col-md-3">
                <input type="text" class="form-control" name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="<?php echo $log_type === 'operation' ? '搜索操作详情或用户ID' : '搜索日志内容或用户名'; ?>">
            </div>
            
            <?php if ($log_type === 'operation'): ?>
            <div class="col-md-2">
                <select class="form-select" name="operation_module">
                    <option value="">全部模块</option>
                    <option value="分类" <?php echo (isset($_GET['operation_module']) && $_GET['operation_module'] === '分类') ? 'selected' : ''; ?>>分类</option>
                    <option value="链接" <?php echo (isset($_GET['operation_module']) && $_GET['operation_module'] === '链接') ? 'selected' : ''; ?>>链接</option>
                    <option value="用户" <?php echo (isset($_GET['operation_module']) && $_GET['operation_module'] === '用户') ? 'selected' : ''; ?>>用户</option>
                    <option value="文件" <?php echo (isset($_GET['operation_module']) && $_GET['operation_module'] === '文件') ? 'selected' : ''; ?>>文件</option>
                </select>
            </div>
            <?php endif; ?>
            
            <div class="col-md-2">
                <select class="form-select" name="selected_month">
                    <option value="">全部月份</option>
                    <?php
                    // 生成最近12个月的选项
                    for ($i = 0; $i < 12; $i++) {
                        $month = date('Y-m', strtotime("-$i months"));
                        $month_text = date('Y年m月', strtotime("-$i months"));
                        $selected = ($selected_month === $month) ? 'selected' : '';
                        echo "<option value=\"$month\" $selected>$month_text</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="col-md-auto">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> 搜索
                </button>
            </div>
            <div class="col-md-auto">
                <a href="?type=<?php echo $log_type; ?>" class="btn btn-secondary">
                    <i class="bi bi-arrow-clockwise"></i> 重置
                </a>
            </div>
            <div class="col-md">
                <!-- 空白填充列 -->
            </div>
            <div class="col-md-auto">
                <button type="button" class="btn btn-danger" onclick="clearAllLogs()">
                    <i class="bi bi-trash"></i> 清空
                </button>
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
                                    <small><?php 
                                        $time_field = 'created_at';
                                        if ($log_type === 'login') {
                                            $time_field = 'login_time';
                                        }
                                        echo date('Y-m-d H:i:s', strtotime($log[$time_field] ?? 'now')); 
                                    ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?php 
                                        if ($log_type === 'operation') {
                                            // 新操作日志格式，需要通过userid获取用户名
                                            echo htmlspecialchars($log['userid'] ? '用户' . $log['userid'] : '系统');
                                        } else {
                                            // 其他日志格式
                                            echo htmlspecialchars($log['username'] ?? '系统');
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="max-width: 400px; overflow: hidden; text-overflow: ellipsis;">
                                        <?php 
                                        if ($log_type === 'operation') {
                                            // 新操作日志格式
                                            $module = $log['operation_module'] ?? '';
                                            $type = $log['operation_type'] ?? '';
                                            $details = [];
                                            
                                            if ($module === '分类' && !empty($log['categorie_name'])) {
                                                $details[] = "分类: " . htmlspecialchars($log['categorie_name']);
                                            } elseif ($module === '链接' && !empty($log['link_name'])) {
                                                $details[] = "链接: " . htmlspecialchars($log['link_name']);
                                            } elseif ($module === '用户' && !empty($log['operated_name'])) {
                                                $details[] = "用户: " . htmlspecialchars($log['operated_name']);
                                            } elseif ($module === '文件' && !empty($log['files'])) {
                                                $details[] = "文件: " . htmlspecialchars(basename($log['files']));
                                            }
                                            
                                            $status_badge = ($log['status'] ?? '成功') === '成功' ? 'bg-success' : 'bg-danger';
                                            echo htmlspecialchars($module) . ' - ' . htmlspecialchars($type);
                                            if (!empty($details)) {
                                                echo ' (' . implode(', ', $details) . ')';
                                            }
                                            echo ' <span class="badge ' . $status_badge . '">' . htmlspecialchars($log['status'] ?? '成功') . '</span>';
                                        } else {
                                            // 其他日志格式
                                            echo htmlspecialchars($log['message'] ?? $log['action'] ?? '');
                                        }
                                        ?>
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
const selectAllCheckbox = document.getElementById('selectAll');
if (selectAllCheckbox) {
    selectAllCheckbox.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.log-checkbox');
        checkboxes.forEach(checkbox => checkbox.checked = this.checked);
        updateBatchDeleteButton();
    });
}

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