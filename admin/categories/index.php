<?php
require_once __DIR__ . '../includes/load.php';

// 检查是否登录
if (!User::checkLogin()) {
    header('Location: login.php');
    exit();
}

// 获取分类管理实例
$categoryManager = get_category_manager();

// 处理删除请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = intval($_POST['delete_id']);
    
    try {
        // 获取分类信息用于日志记录
        $category_info = $categoryManager->getById($delete_id);
        
        // 尝试删除分类
        $result = $categoryManager->delete($delete_id);
        
        if ($result) {
            // 记录操作日志
            $logsManager = get_logs_manager();
            $logsManager->addCategoryOperationLog(
                $_SESSION['user_id'] ?? 0,
                '删除',
                $delete_id,
                $category_info['name'] ?? '未知分类',
                [
                    'deleted_category' => $category_info
                ]
            );
            
            $_SESSION['success'] = '分类删除成功！';
        } else {
            $_SESSION['error'] = '分类删除失败：未找到该分类。';
        }
        
        header('Location: index.php');
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error'] = '删除失败：' . $e->getMessage();
        header('Location: index.php');
        exit;
    }
}

// 处理排序更新
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order']) && isset($_POST['order_index'])) {
    try {
        $result = $categoryManager->updateOrder($_POST['order_index']);
        
        if ($result) {
            $_SESSION['success'] = '排序更新成功！';
        } else {
            $_SESSION['error'] = '排序更新失败';
        }
        
        header('Location: index.php');
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error'] = '排序更新失败：' . $e->getMessage();
        header('Location: index.php');
        exit;
    }
}

// 获取分页参数
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;

// 获取搜索参数
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : ''; // all, active, inactive

// 获取所有分类（包含链接计数）
try {
    // 获取所有分类
    $allCategories = $categoryManager->getAll(false); // false表示获取所有分类（包括非激活的）
    
    // 过滤搜索结果
    if ($search) {
        $allCategories = array_filter($allCategories, function($category) use ($search) {
            return stripos($category['name'], $search) !== false || 
                   stripos($category['description'], $search) !== false;
       });
    }
    
    // 状态筛选
    if ($status_filter && $status_filter !== 'all') {
        $allCategories = array_filter($allCategories, function($category) use ($status_filter) {
            if ($status_filter === 'active') {
                return !empty($category['is_active']);
            } elseif ($status_filter === 'inactive') {
                return empty($category['is_active']);
            }
            return true;
        });
    }
    
    // 计算总数和分页
    $total = count($allCategories);
    $total_pages = ceil($total / $per_page);
    $offset = ($page - 1) * $per_page;
    
    // 获取当前页的分类
    $categories = array_slice($allCategories, $offset, $per_page);
    
    // 为每个分类添加链接计数
    $database = get_database();
    foreach ($categories as $key => $category) {
        $stmt = $database->query(
            "SELECT COUNT(*) FROM navigation_links WHERE category_id = ?", 
            [$category['id']]
        );
        $categories[$key]['link_count'] = $stmt->fetchColumn();
    }
}

catch (Exception $e) {
    $categories = [];
    $total = 0;
    $total_pages = 1;
    $_SESSION['error'] = '获取分类列表失败：' . $e->getMessage();
}

// 设置页面标题
$page_title = '分类管理';
include '../templates/header.php';

if (isset($_SESSION['success'])): ?>
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

<!-- 搜索表单 -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <input type="text" class="form-control" name="search" 
                       placeholder="搜索分类..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-2">
                <select class="form-select" name="status">
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>全部状态</option>
                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>启用</option>
                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>禁用</option>
                </select>
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-search"></i> 搜索
                </button>
            </div>
            <div class="col-md-auto">
                <?php if ($search || $status_filter !== 'all'): ?>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x"></i> 清除
                    </a>
                <?php endif; ?>
            </div>
            <div class="col-md">
                <!-- 空白填充列 -->
            </div>
            <div class="col-md-2">
                <a href="add.php" class="btn btn-success w-100">
                    <i class="bi bi-plus"></i> 添加分类
                </a>
            </div>
        </form>
    </div>
</div>

<!-- 分类列表 -->
<form method="POST">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">分类列表 (<?php echo $total; ?>)</h5>
            <button type="submit" name="update_order" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-arrow-down-up"></i> 更新排序
            </button>
        </div>
        
        <?php if ($categories): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th width="50">排序</th>
                            <th>图标</th>
                            <th>名称</th>
                            <th>描述</th>
                            <th>链接数</th>
                            <th>状态</th>
                            <th width="150">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td>
                                    <input type="number" class="form-control form-control-sm" 
                                           name="order_index[<?php echo $category['id']; ?>]" 
                                           value="<?php echo $category['order_index']; ?>" 
                                           min="0" style="width: 60px;">
                                </td>
                                <td>
                                    <?php 
                                    // 根据icon_type字段显示不同类型的图标
                                    $iconType = $category['icon_type'] ?? 'fontawesome';
                                    
                                    switch ($iconType) {
                                        case 'fontawesome':
                                            // 显示Font Awesome图标
                                            if (!empty($category['icon_fontawesome'])):
                                                $iconClass = trim($category['icon_fontawesome']);
                                                $iconColor = $category['icon_fontawesome_color'] ?? $category['color'] ?? '#007bff';
                                                ?>
                                                <i class="<?php echo htmlspecialchars($iconClass); ?>" 
                                                   style="font-size: 1.5rem; color: <?php echo htmlspecialchars($iconColor); ?>;"></i>
                                            <?php else: ?>
                                                <div class="bg-secondary text-white d-flex align-items-center justify-content-center rounded" 
                                                     style="width: 40px; height: 40px;">
                                                    <i class="bi bi-folder" style="font-size: 1.2rem;"></i>
                                                </div>
                                            <?php endif;
                                            break;
                                            
                                        case 'upload':
                                            // 显示上传的图片
                                            if (!empty($category['icon_upload'])): 
                                                // 检查icon_upload是否已经包含/uploads/categories/前缀
                                                if (strpos($category['icon_upload'], '/uploads/categories/') === 0) {
                                                    $iconSrc = htmlspecialchars($category['icon_upload']);
                                                } else {
                                                    $iconSrc = '/uploads/categories/' . htmlspecialchars($category['icon_upload']);
                                                }
                                                ?>
                                                <img src="<?php echo $iconSrc; ?>" 
                                                     alt="<?php echo htmlspecialchars($category['name'] ?? ''); ?>" 
                                                     class="image-preview" style="width: 40px; height: 40px; border-radius: 4px;">
                                            <?php else: ?>
                                                <div class="bg-secondary text-white d-flex align-items-center justify-content-center rounded" 
                                                     style="width: 40px; height: 40px;">
                                                    <i class="bi bi-folder" style="font-size: 1.2rem;"></i>
                                                </div>
                                            <?php endif;
                                            break;
                                            
                                        case 'url':
                                            // 显示URL图片
                                            if (!empty($category['icon_url'])): ?>
                                                <img src="<?php echo htmlspecialchars($category['icon_url']); ?>" 
                                                     alt="<?php echo htmlspecialchars($category['name'] ?? ''); ?>" 
                                                     class="image-preview" style="width: 40px; height: 40px; border-radius: 4px;">
                                            <?php else: ?>
                                                <div class="bg-secondary text-white d-flex align-items-center justify-content-center rounded" 
                                                     style="width: 40px; height: 40px;">
                                                    <i class="bi bi-folder" style="font-size: 1.2rem;"></i>
                                                </div>
                                            <?php endif;
                                            break;
                                            
                                        case 'iconfont':
                                            // 显示iconfont图标
                                            if (!empty($category['icon_iconfont'])): 
                                                $iconColor = $category['icon_iconfont_color'] ?? $category['color'] ?? '#007bff';
                                                ?>
                                                <svg class="icon" aria-hidden="true" style="width: 1.5rem; height: 1.5rem; fill: <?php echo htmlspecialchars($iconColor); ?>;">
                                                    <use xlink:href="#<?php echo htmlspecialchars($category['icon_iconfont']); ?>"></use>
                                                </svg>
                                            <?php else: ?>
                                                <div class="bg-secondary text-white d-flex align-items-center justify-content-center rounded" 
                                                     style="width: 40px; height: 40px;">
                                                    <i class="bi bi-folder" style="font-size: 1.2rem;"></i>
                                                </div>
                                            <?php endif;
                                            break;
                                            
                                        default:
                                            // 默认显示Font Awesome图标
                                            if (!empty($category['icon_fontawesome'])):
                                                $iconClass = trim($category['icon_fontawesome']);
                                                $iconColor = $category['icon_fontawesome_color'] ?? $category['color'] ?? '#007bff';
                                                ?>
                                                <i class="<?php echo htmlspecialchars($iconClass); ?>" 
                                                   style="font-size: 1.5rem; color: <?php echo htmlspecialchars($iconColor); ?>;"></i>
                                            <?php else: ?>
                                                <div class="bg-secondary text-white d-flex align-items-center justify-content-center rounded" 
                                                     style="width: 40px; height: 40px;">
                                                    <i class="bi bi-folder" style="font-size: 1.2rem;"></i>
                                                </div>
                                            <?php endif;
                                            break;
                                    }
                                    ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($category['name'] ?? ''); ?></strong>
                                    <br>
                                    <small class="text-muted">别名: <?php echo htmlspecialchars($category['slug'] ?? $category['name'] ?? ''); ?></small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars(mb_substr($category['description'] ?? '', 0, 50)); ?>
                                    <?php echo mb_strlen($category['description'] ?? '') > 50 ? '...' : ''; ?>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?php echo $category['link_count']; ?></span>
                                </td>
                                <td>
                                    <?php if ($category['is_active']): ?>
                                        <span class="badge bg-success">启用</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">禁用</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="edit.php?id=<?php echo $category['id']; ?>" 
                                           class="btn btn-outline-primary" title="编辑">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger" 
                                                onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name'] ?? '') ?>')" 
                                                title="删除">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="card-body text-center py-5">
                <i class="bi bi-folder-x" style="font-size: 3rem; color: #dee2e6;"></i>
                <h5 class="text-muted mt-3">暂无分类</h5>
                <a href="add.php" class="btn btn-primary mt-3">
                    <i class="bi bi-plus"></i> 添加第一个分类
                </a>
            </div>
        <?php endif; ?>
    </div>
</form>

<!-- 分页 -->
<?php if ($total_pages > 1): ?>
    <nav aria-label="分页">
        <ul class="pagination justify-content-center mt-4">
            <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">
                        <i class="bi bi-chevron-left"></i> 上一页
                    </a>
                </li>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">
                        下一页 <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
<?php endif; ?>

<!-- 删除确认表单 -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="delete_id" id="deleteId">
</form>

<script>
function deleteCategory(id, name) {
    if (confirm('确定要删除分类 "' + name + '" 吗？此操作不可恢复！')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php include '../templates/footer.php'; ?>