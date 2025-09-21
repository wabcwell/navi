<?php
session_start();
require_once '../includes/load.php';

// 检查是否登录
if (!User::checkLogin()) {
    header('Location: ../login.php');
    exit();
}

// 设置上传目录
$base_upload_dir = '../../uploads';

// 处理文件删除
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_file'])) {
    $file_path = $_POST['file_path'];
    $full_path = realpath($base_upload_dir . '/' . $file_path);
    
    // 安全检查：确保文件在uploads目录内
    if ($full_path && strpos($full_path, realpath($base_upload_dir)) === 0) {
        if (is_file($full_path)) {
            // 获取文件信息（在删除前）
            $file_info = [
                'file_name' => basename($file_path),
                'file_size' => file_exists($full_path) ? format_size(filesize($full_path)) : '未知',
                'deleted_at' => date('Y-m-d H:i:s')
            ];
            
            if (unlink($full_path)) {
                // 记录操作日志
                $logsManager = get_logs_manager();
                $logsManager->addFileOperationLog(
                    $_SESSION['user_id'] ?? 0,
                    '删除文件',
                    $file_path,
                    $file_info
                );
                
                $_SESSION['success'] = '文件删除成功';
            } else {
                $_SESSION['error'] = '文件删除失败';
            }
        } elseif (is_dir($full_path)) {
            if (rmdir($full_path)) {
                // 记录操作日志
                $logsManager = get_logs_manager();
                $logsManager->addFileOperationLog(
                    $_SESSION['user_id'] ?? 0,
                    '删除目录',
                    $file_path,
                    [
                        'dir_name' => basename($file_path),
                        'deleted_at' => date('Y-m-d H:i:s')
                    ]
                );
                
                $_SESSION['success'] = '目录删除成功';
            } else {
                $_SESSION['error'] = '目录删除失败（请确保目录为空）';
            }
        }
    } else {
        $_SESSION['error'] = '无效的文件路径';
    }
    
    header('Location: index.php?dir=' . urlencode($_POST['current_dir'] ?? ''));
    exit;
}

// 获取当前目录
$current_dir = $_GET['dir'] ?? '';
$current_dir = trim($current_dir, '/');

// 获取搜索参数
$search = $_GET['search'] ?? '';

// 格式化文件大小
function format_size($bytes) {
    if ($bytes >= 1073741824) {
        return round($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return round($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return round($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' B';
    }
}

// 获取目录树
function get_directory_tree($base_dir, $relative_path = '') {
    $tree = [];
    $full_path = $base_dir . '/' . $relative_path;
    
    if (is_dir($full_path)) {
        $items = scandir($full_path);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $item_path = $relative_path . '/' . $item;
            $item_full_path = $base_dir . '/' . $item_path;
            
            if (is_dir($item_full_path)) {
                $tree[] = [
                    'name' => $item,
                    'path' => trim($item_path, '/'),
                    'children' => get_directory_tree($base_dir, $item_path)
                ];
            }
        }
    }
    
    return $tree;
}

// 获取文件和目录列表
function get_files_and_dirs($base_dir, $relative_path = '') {
    $dirs = [];
    $files = [];
    $full_path = $base_dir . '/' . $relative_path;
    
    if (is_dir($full_path)) {
        $items = scandir($full_path);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $item_path = $relative_path . '/' . $item;
            $item_full_path = $base_dir . '/' . $item_path;
            
            if (is_dir($item_full_path)) {
                $dirs[] = [
                    'name' => $item,
                    'path' => trim($item_path, '/'),
                    'modified' => filemtime($item_full_path)
                ];
            } else {
                $files[] = [
                    'name' => $item,
                    'path' => trim($item_path, '/'),
                    'size' => filesize($item_full_path),
                    'modified' => filemtime($item_full_path),
                    'url' => '../../uploads/' . str_replace(' ', '%20', trim($item_path, '/')),
                    'is_image' => preg_match('/\.(jpg|jpeg|png|gif|webp|svg)$/i', $item)
                ];
            }
        }
    }
    
    return ['dirs' => $dirs, 'files' => $files];
}

// 搜索文件
function search_files_recursive($base_dir, $relative_path = '', $search_term = '', &$results = []) {
    $full_path = $base_dir . '/' . $relative_path;
    
    if (is_dir($full_path)) {
        $items = scandir($full_path);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $item_path = $relative_path . '/' . $item;
            $item_full_path = $base_dir . '/' . $item_path;
            
            if (is_dir($item_full_path)) {
                search_files_recursive($base_dir, $item_path, $search_term, $results);
            } else {
                if (stripos($item, $search_term) !== false) {
                    $results[] = [
                        'name' => $item,
                        'path' => trim($item_path, '/'),
                        'size' => filesize($item_full_path),
                        'modified' => filemtime($item_full_path),
                        'url' => '../../uploads/' . str_replace(' ', '%20', trim($item_path, '/')),
                        'is_image' => preg_match('/\.(jpg|jpeg|png|gif|webp|svg)$/i', $item)
                    ];
                }
            }
        }
    }
}

// 生成面包屑导航
function generate_breadcrumbs($path) {
    $breadcrumbs = [];
    $parts = array_filter(explode('/', $path));
    $current = '';
    
    foreach ($parts as $part) {
        $current .= '/' . $part;
        $breadcrumbs[] = [
            'name' => $part,
            'path' => trim($current, '/')
        ];
    }
    
    return $breadcrumbs;
}

// 获取数据
$directory_tree = get_directory_tree($base_upload_dir);

$page_title = '文件管理';

if ($search) {
    $all_files = [];
    search_files_recursive($base_upload_dir, '', $search, $all_files);
    $files = $all_files;
    $dirs = [];
    $breadcrumbs = [];
} else {
    $result = get_files_and_dirs($base_upload_dir, $current_dir);
    $dirs = $result['dirs'];
    $files = $result['files'];
    $breadcrumbs = generate_breadcrumbs($current_dir);
}

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

<div class="row">
    <!-- 左侧目录树 -->
    <div class="col-md-3">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">目录结构</h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <a href="index.php" class="list-group-item list-group-item-action <?php echo empty($current_dir) ? 'active' : ''; ?>">
                        <i class="bi bi-folder"></i> uploads/
                    </a>
                    
                    <?php
                    function render_directory_tree($dirs, $level = 0) {
                        foreach ($dirs as $dir) {
                            $indent = str_repeat('&nbsp;', $level * 4);
                            echo '<a href="index.php?dir=' . urlencode($dir['path']) . '" class="list-group-item list-group-item-action">';
                            echo $indent . '<i class="bi bi-folder"></i> ' . htmlspecialchars($dir['name']);
                            echo '</a>';
                            
                            if (!empty($dir['children'])) {
                                render_directory_tree($dir['children'], $level + 1);
                            }
                        }
                    }
                    
                    render_directory_tree($directory_tree);
                    ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 右侧文件列表 -->
    <div class="col-md-9">
        <!-- 面包屑导航 -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">uploads</a></li>
                <?php foreach ($breadcrumbs as $crumb): ?>
                    <li class="breadcrumb-item">
                        <a href="index.php?dir=<?php echo urlencode($crumb['path']); ?>">
                            <?php echo htmlspecialchars($crumb['name']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ol>
        </nav>
        
        <!-- 搜索框 -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-2">
                    <div class="col-md-10">
                        <input type="text" class="form-control" name="search" 
                               placeholder="搜索文件名..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                    <?php if ($current_dir): ?>
                        <input type="hidden" name="dir" value="<?php echo htmlspecialchars($current_dir); ?>">
                    <?php endif; ?>
                </form>
            </div>
        </div>
        
        <!-- 统计信息 -->
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo count($dirs); ?></h5>
                        <p class="card-text text-muted">目录数</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo count($files); ?></h5>
                        <p class="card-text text-muted">文件数</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo format_size(array_sum(array_column($files, 'size'))); ?></h5>
                        <p class="card-text text-muted">总大小</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 文件列表 -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <?php echo $search ? '搜索结果' : ($current_dir ? '目录内容' : '根目录'); ?>
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($dirs) && empty($files)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-folder-x" style="font-size: 3rem; color: #ccc;"></i>
                        <p class="text-muted mt-3">
                            <?php echo $search ? '没有找到匹配的文件' : '目录为空'; ?>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th style="width: 40px;"></th>
                                    <th>名称</th>
                                    <th>大小</th>
                                    <th>修改时间</th>
                                    <?php if (!$search): ?>
                                        <th>操作</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- 目录列表 -->
                                <?php foreach ($dirs as $dir): ?>
                                    <tr>
                                        <td>
                                            <i class="bi bi-folder-fill text-warning"></i>
                                        </td>
                                        <td>
                                            <a href="index.php?dir=<?php echo urlencode($dir['path']); ?>" 
                                               class="text-decoration-none">
                                                <?php echo htmlspecialchars($dir['name']); ?>
                                            </a>
                                        </td>
                                        <td>-</td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo date('Y-m-d H:i', $dir['modified']); ?>
                                            </small>
                                        </td>
                                        <?php if (!$search): ?>
                                            <td>-</td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                                
                                <!-- 文件列表 -->
                                <?php foreach ($files as $file): ?>
                                    <tr>
                                        <td>
                                            <?php if ($file['is_image']): ?>
                                                <img src="<?php echo $file['url']; ?>" 
                                                     alt="" style="width: 30px; height: 30px; object-fit: cover; border-radius: 4px;">
                                            <?php else: ?>
                                                <i class="bi bi-file-earmark-fill text-secondary"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo $file['url']; ?>" target="_blank" class="text-decoration-none">
                                                <?php echo htmlspecialchars($file['name']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?php echo format_size($file['size']); ?></small>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo date('Y-m-d H:i', $file['modified']); ?>
                                            </small>
                                        </td>
                                        <?php if (!$search): ?>
                                            <td>
                                                <form method="POST" style="display: inline;" 
                                                      onsubmit="return confirm('确定要删除此文件吗？');">
                                                    <input type="hidden" name="delete_file" value="1">
                                                    <input type="hidden" name="file_path" value="<?php echo htmlspecialchars($file['path']); ?>">
                                                    <input type="hidden" name="current_dir" value="<?php echo htmlspecialchars($current_dir); ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>