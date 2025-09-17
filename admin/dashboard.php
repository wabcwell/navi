<?php
require_once 'includes/load.php';

// 检查登录状态
if (!is_logged_in()) {
    header('Location: index.php');
    exit();
}

// 获取统计信息
$pdo = get_db_connection();

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

// 最近添加的链接
$stmt = $pdo->query("SELECT l.*, c.name as category_name FROM navigation_links l 
                    LEFT JOIN categories c ON l.category_id = c.id 
                    ORDER BY l.created_at DESC LIMIT 5");
$recent_links = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                <h6>最近添加的链接</h6>
                <?php if ($recent_links): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recent_links as $link): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <strong><?php echo htmlspecialchars($link['title']); ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($link['category_name']); ?> • 
                                            <?php echo date('Y-m-d H:i', strtotime($link['created_at'])); ?>
                                        </small>
                                    </div>
                                    <div>
                                        <a href="<?php echo $link['url']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted">暂无最近添加的链接</p>
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