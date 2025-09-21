<?php
require_once 'includes/load.php';

// 检查是否登录
if (!User::checkLogin()) {
    header('Location: login.php');
    exit();
}

// 获取数据库连接
$database = new Database();
$pdo = $database->getConnection();

// 分类统计
$stmt = $pdo->query("SELECT COUNT(*) FROM categories");
$category_count = $stmt->fetchColumn();

// 链接统计
$stmt = $pdo->query("SELECT COUNT(*) FROM navigation_links");
$link_count = $stmt->fetchColumn();



// 文件统计
$upload_dir = '../uploads';
$file_count = 0;
if (is_dir($upload_dir)) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($upload_dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $file_count++;
        }
    }
}

// 今日访问统计 - 如果visit_logs表不存在则返回0
$today = date('Y-m-d');
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM visit_logs WHERE DATE(visit_time) = ?");
    $stmt->execute([$today]);
    $today_visits = $stmt->fetchColumn();
} catch (PDOException $e) {
    $today_visits = 0; // 如果表不存在则返回0
}

// 最近登录日志
$stmt = $pdo->query("SELECT * FROM login_logs ORDER BY login_time DESC LIMIT 5");
$login_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 最近操作活动（从operation_logs获取分类和链接的新增操作）
$stmt = $pdo->prepare("
    SELECT 
        operation_module,
        operation_type,
        operation_time,
        operation_details,
        userid,
        u.username
    FROM operation_logs ol
    LEFT JOIN users u ON ol.userid = u.id
    WHERE operation_module IN ('分类', '链接') 
    AND operation_type = '新增'
    ORDER BY operation_time DESC 
    LIMIT 10
");
$stmt->execute();
$recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = '仪表盘';
include 'templates/header.php';
?>



<!-- 统计卡片 -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h3 class="card-title"><?php echo $category_count; ?></h3>
                        <p class="card-text">分类数量</p>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-folder" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h3 class="card-title"><?php echo $link_count; ?></h3>
                        <p class="card-text">链接数量</p>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-link-45deg" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    

    
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h3 class="card-title"><?php echo $file_count; ?></h3>
                        <p class="card-text">文件数量</p>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-file-earmark" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h3 class="card-title"><?php echo $today_visits; ?></h3>
                        <p class="card-text">今日访问</p>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-eye" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- 最近活动 -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-clock-history"></i> 最近活动
                </h5>
            </div>
            <div class="card-body">
                <h6>最近操作活动</h6>
                <?php if ($recent_activities): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recent_activities as $activity): ?>
                            <?php
                            // 解析操作详情
                            $details = json_decode($activity['operation_details'], true) ?? [];
                            $description = '';
                            
                            if ($activity['operation_module'] === '分类') {
                                // 从多个可能的字段中获取分类名称
                                if (isset($details['name']) && !empty($details['name'])) {
                                    $description = htmlspecialchars($details['name']);
                                } elseif (isset($details['category_name']) && !empty($details['category_name'])) {
                                    $description = htmlspecialchars($details['category_name']);
                                } elseif (isset($details['after']['name']) && !empty($details['after']['name'])) {
                                    $description = htmlspecialchars($details['after']['name']);
                                } elseif (isset($details['link_data']['name']) && !empty($details['link_data']['name'])) {
                                    $description = htmlspecialchars($details['link_data']['name']);
                                } else {
                                    $description = '新分类';
                                }
                            } elseif ($activity['operation_module'] === '链接') {
                                // 从多个可能的字段中获取链接名称
                                if (isset($details['title']) && !empty($details['title'])) {
                                    $description = htmlspecialchars($details['title']);
                                } elseif (isset($details['link_name']) && !empty($details['link_name'])) {
                                    $description = htmlspecialchars($details['link_name']);
                                } elseif (isset($details['after']['title']) && !empty($details['after']['title'])) {
                                    $description = htmlspecialchars($details['after']['title']);
                                } elseif (isset($details['link_data']['title']) && !empty($details['link_data']['title'])) {
                                    $description = htmlspecialchars($details['link_data']['title']);
                                } else {
                                    $description = '新链接';
                                }
                            }
                            
                            // 获取操作图标（只显示新增操作）
                            $icon = 'bi-plus-circle text-success';
                            ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center mb-1">
                                            <i class="bi <?php echo $icon; ?> me-2"></i>
                                            <strong><?php echo $activity['operation_type'] . ' ' . $activity['operation_module']; ?></strong>
                                            <span class="mx-1">-</span>
                                            <span class="text-truncate"><?php echo $description; ?></span>
                                        </div>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($activity['username'] ?? '系统'); ?> • 
                                            <?php echo date('Y-m-d H:i', strtotime($activity['operation_time'])); ?>
                                        </small>
                                    </div>
                                    <div class="ms-2">
                                        <?php if ($activity['operation_module'] === '链接' && isset($details['url'])): ?>
                                            <a href="<?php echo htmlspecialchars($details['url']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted">暂无最近操作记录</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- 登录日志 -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-shield-check"></i> 登录日志
                </h5>
            </div>
            <div class="card-body">
                <?php if ($login_logs): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>用户</th>
                                    <th>IP地址</th>
                                    <th>时间</th>
                                    <th>状态</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($login_logs as $log): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($log['username']); ?></td>
                                        <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($log['login_time'])); ?></td>
                                        <td>
                                            <?php if ($log['success']): ?>
                                                <span class="badge bg-success">成功</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">失败</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">暂无登录记录</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>