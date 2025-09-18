<?php
require_once '../includes/load.php';

// 检查登录状态
if (!User::checkLogin()) {
    header('Location: login.php');
    exit();
}

// 获取导航链接管理实例
$linkManager = get_navigation_link_manager();
// 获取数据库实例用于分类查询
$database = get_database();
$pdo = $database->getConnection();

// 处理删除操作
if (isset($_POST['delete_id'])) {
    $id = intval($_POST['delete_id']);
    
    // 获取链接信息
    $link = $linkManager->getLinkById($id);
    
    if ($link) {
        // 删除链接图标
        // 注意：这里需要根据实际的字段名进行调整
        $iconField = '';
        if (!empty($link['icon_upload'])) {
            $iconField = $link['icon_upload'];
        } elseif (!empty($link['icon'])) {
            $iconField = $link['icon'];
        }
        
        if ($iconField) {
            $icon_path = '../../uploads/links/' . $iconField;
            if (file_exists($icon_path)) {
                unlink($icon_path);
            }
        }
        
        // 使用 NavigationLink 类删除链接
        $result = $linkManager->deleteLink($id);
        if ($result) {
            $_SESSION['success'] = '链接删除成功';
        } else {
            $_SESSION['error'] = '链接删除失败';
        }
    }
    
    header('Location: index.php');
    exit();
}

// 处理批量操作
if (isset($_POST['batch_action']) && isset($_POST['link_ids'])) {
    $action = $_POST['batch_action'];
    $ids = array_map('intval', $_POST['link_ids']);
    
    $successCount = 0;
    switch ($action) {
        case 'activate':
            foreach ($ids as $id) {
                $data = ['is_active' => 1];
                if ($linkManager->updateLink($id, $data)) {
                    $successCount++;
                }
            }
            $_SESSION['success'] = '已启用 ' . $successCount . ' 个链接';
            break;
        case 'deactivate':
            foreach ($ids as $id) {
                $data = ['is_active' => 0];
                if ($linkManager->updateLink($id, $data)) {
                    $successCount++;
                }
            }
            $_SESSION['success'] = '已禁用 ' . $successCount . ' 个链接';
            break;
        case 'delete':
            // 删除图标文件并删除链接
            foreach ($ids as $id) {
                // 获取链接信息以删除图标
                $link = $linkManager->getLinkById($id);
                if ($link) {
                    // 删除链接图标
                    $iconField = '';
                    if (!empty($link['icon_upload'])) {
                        $iconField = $link['icon_upload'];
                    } elseif (!empty($link['icon'])) {
                        $iconField = $link['icon'];
                    }
                    
                    if ($iconField) {
                        $icon_path = '../../uploads/links/' . $iconField;
                        if (file_exists($icon_path)) {
                            unlink($icon_path);
                        }
                    }
                }
                
                // 删除链接
                if ($linkManager->deleteLink($id)) {
                    $successCount++;
                }
            }
            $_SESSION['success'] = '已删除 ' . $successCount . ' 个链接';
            break;
    }
    
    header('Location: index.php');
    exit();
}

// 获取分类列表
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY order_index ASC, name ASC")
                  ->fetchAll(PDO::FETCH_ASSOC);

// 搜索和筛选
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$category_id = intval($_GET['category_id'] ?? 0);
$status = $_GET['status'] ?? '';

$where = [];
$params = [];

if ($search) {
    $where[] = '(l.title LIKE ? OR l.description LIKE ? OR l.url LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category_id) {
    $where[] = 'l.category_id = ?';
    $params[] = $category_id;
}

if ($status !== '') {
    $where[] = 'l.is_active = ?';
    $params[] = $status;
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// 获取总数
$total = $linkManager->getLinksCount($where_sql, $params);
$total_pages = ceil($total / $limit);

// 获取链接数据
$links = $linkManager->getLinks($limit, $offset, $where_sql, $params);

$page_title = '链接管理';
include '../templates/header.php';
?>



<div class="mb-4">
    <a href="add.php" class="btn btn-primary">
        <i class="bi bi-plus"></i> 添加链接
    </a>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- 搜索和筛选 -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <input type="text" class="form-control" name="search" 
                       placeholder="搜索链接..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <select class="form-select" name="category_id">
                    <option value="">所有分类</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" 
                                <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="status">
                    <option value="">所有状态</option>
                    <option value="1" <?php echo $status === '1' ? 'selected' : ''; ?>>启用</option>
                    <option value="0" <?php echo $status === '0' ? 'selected' : ''; ?>>禁用</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-search"></i> 搜索
                </button>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x"></i> 清除
                </a>
            </div>
        </form>
    </div>
</div>

<!-- 链接列表 -->
<form method="POST" id="batchForm">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">链接列表 (<?php echo $total; ?>)</h5>
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-success" 
                        onclick="batchAction('activate')" <?php echo empty($links) ? 'disabled' : ''; ?>>
                    <i class="bi bi-check-circle"></i> 启用
                </button>
                <button type="button" class="btn btn-outline-warning" 
                        onclick="batchAction('deactivate')" <?php echo empty($links) ? 'disabled' : ''; ?>>
                    <i class="bi bi-x-circle"></i> 禁用
                </button>
                <button type="button" class="btn btn-outline-danger" 
                        onclick="batchAction('delete')" <?php echo empty($links) ? 'disabled' : ''; ?>>
                    <i class="bi bi-trash"></i> 删除
                </button>
            </div>
        </div>
        
        <?php if ($links): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th width="30">
                                <input type="checkbox" id="selectAll" onclick="toggleAll(this)">
                            </th>
                            <th>图标</th>
                            <th>标题</th>
                            <th>URL</th>
                            <th>分类</th>
                            <th>点击数</th>
                            <th>状态</th>
                            <th width="150">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($links as $link): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="link_ids[]" value="<?php echo $link['id']; ?>">
                                </td>
                                <td>
                                    <?php 
                                    // 根据icon_type字段的值来显示不同类型的图标
                                    $iconType = $link['icon_type'] ?? 'none';
                                    
                                    switch ($iconType) {
                                        case 'fontawesome':
                                            // 显示Font Awesome图标
                                            $iconClass = $link['icon_fontawesome'] ?? '';
                                            $iconColor = $link['icon_fontawesome_color'] ?? '#007bff';
                                            
                                            if ($iconClass) {
                                                // 如果没有前缀，添加fas前缀
                                                if (!preg_match('/^(fas|far|fab)\s/i', $iconClass)) {
                                                    $iconClass = 'fas ' . $iconClass;
                                                }
                                                ?>
                                                <i class="<?php echo htmlspecialchars($iconClass); ?>" 
                                                   style="font-size: 1.5rem; color: <?php echo htmlspecialchars($iconColor); ?>;"></i>
                                                <?php
                                            } else {
                                                // 没有图标时显示默认图标
                                                ?>
                                                <div class="bg-secondary text-white d-flex align-items-center justify-content-center rounded" 
                                                     style="width: 32px; height: 32px;">
                                                    <i class="bi bi-link-45deg" style="font-size: 1.2rem;"></i>
                                                </div>
                                                <?php
                                            }
                                            break;
                                            
                                        case 'upload':
                                            // 显示上传的图片
                                            $iconUpload = $link['icon_upload'] ?? '';
                                            
                                            if ($iconUpload) {
                                                ?>
                                                <img src="<?php echo htmlspecialchars($iconUpload); ?>" 
                                                     alt="<?php echo htmlspecialchars($link['title']); ?>" 
                                                     class="image-preview" style="width: 32px; height: 32px; border-radius: 4px;">
                                                <?php
                                            } else {
                                                // 没有图标时显示默认图标
                                                ?>
                                                <div class="bg-secondary text-white d-flex align-items-center justify-content-center rounded" 
                                                     style="width: 32px; height: 32px;">
                                                    <i class="bi bi-link-45deg" style="font-size: 1.2rem;"></i>
                                                </div>
                                                <?php
                                            }
                                            break;
                                            
                                        case 'url':
                                            // 显示URL图片
                                            $iconUrl = $link['icon_url'] ?? '';
                                            
                                            if ($iconUrl) {
                                                ?>
                                                <img src="<?php echo filter_var($iconUrl, FILTER_VALIDATE_URL) ? $iconUrl : '/uploads/links/' . $iconUrl; ?>" 
                                                     alt="<?php echo htmlspecialchars($link['title']); ?>" 
                                                     class="image-preview" style="width: 32px; height: 32px; border-radius: 4px;">
                                                <?php
                                            } else {
                                                // 没有图标时显示默认图标
                                                ?>
                                                <div class="bg-secondary text-white d-flex align-items-center justify-content-center rounded" 
                                                     style="width: 32px; height: 32px;">
                                                    <i class="bi bi-link-45deg" style="font-size: 1.2rem;"></i>
                                                </div>
                                                <?php
                                            }
                                            break;
                                            
                                        case 'iconfont':
                                            // 显示iconfont图标
                                            if (!empty($link['icon_iconfont'])): 
                                                $iconColor = $link['icon_iconfont_color'] ?? '#007bff';
                                                ?>
                                                <svg class="icon" aria-hidden="true" style="width: 1.5rem; height: 1.5rem; fill: <?php echo htmlspecialchars($iconColor); ?>;">
                                                    <use xlink:href="#<?php echo htmlspecialchars($link['icon_iconfont']); ?>"></use>
                                                </svg>
                                            <?php else: ?>
                                                <div class="bg-secondary text-white d-flex align-items-center justify-content-center rounded" 
                                                     style="width: 32px; height: 32px;">
                                                    <i class="bi bi-link-45deg" style="font-size: 1.2rem;"></i>
                                                </div>
                                            <?php endif;
                                            break;
                                            
                                        case 'none':
                                        default:
                                            // 显示默认图标
                                            ?>
                                            <div class="bg-secondary text-white d-flex align-items-center justify-content-center rounded" 
                                                 style="width: 32px; height: 32px;">
                                                <i class="bi bi-link-45deg" style="font-size: 1.2rem;"></i>
                                            </div>
                                            <?php
                                            break;
                                    }
                                    ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($link['title']); ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars(mb_substr($link['description'], 0, 30)); ?>
                                        <?php echo mb_strlen($link['description']) > 30 ? '...' : ''; ?>
                                    </small>
                                </td>
                                <td>
                                    <a href="<?php echo $link['url']; ?>" target="_blank" class="text-decoration-none">
                                        <small><?php echo htmlspecialchars(mb_substr($link['url'], 0, 30)); ?>...</small>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($link['category_name']); ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo $link['click_count']; ?></span>
                                </td>
                                <td>
                                    <?php if ($link['is_active']): ?>
                                        <span class="badge bg-success">启用</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">禁用</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="edit.php?id=<?php echo $link['id']; ?>" 
                                           class="btn btn-outline-primary" title="编辑">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger" 
                                                onclick="deleteLink(<?php echo $link['id']; ?>, '<?php echo htmlspecialchars($link['title']); ?>')" 
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
                <i class="bi bi-link-45deg" style="font-size: 3rem; color: #dee2e6;"></i>
                <h5 class="text-muted mt-3">暂无链接</h5>
                <a href="add.php" class="btn btn-primary mt-3">
                    <i class="bi bi-plus"></i> 添加第一个链接
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
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category_id=<?php echo $category_id; ?>&status=<?php echo $status; ?>">
                        <i class="bi bi-chevron-left"></i> 上一页
                    </a>
                </li>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category_id=<?php echo $category_id; ?>&status=<?php echo $status; ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category_id=<?php echo $category_id; ?>&status=<?php echo $status; ?>">
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
function toggleAll(checkbox) {
    var checkboxes = document.querySelectorAll('input[name="link_ids[]"]');
    checkboxes.forEach(function(cb) {
        cb.checked = checkbox.checked;
    });
}

function batchAction(action) {
    var form = document.getElementById('batchForm');
    var checkboxes = document.querySelectorAll('input[name="link_ids[]"]:checked');
    
    if (checkboxes.length === 0) {
        alert('请选择要操作的链接');
        return;
    }
    
    var message = '';
    switch (action) {
        case 'activate':
            message = '确定要启用选中的链接吗？';
            break;
        case 'deactivate':
            message = '确定要禁用选中的链接吗？';
            break;
        case 'delete':
            message = '确定要删除选中的链接吗？此操作不可恢复！';
            break;
    }
    
    if (confirm(message)) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'batch_action';
        input.value = action;
        form.appendChild(input);
        form.submit();
    }
}

function deleteLink(id, title) {
    if (confirm('确定要删除链接 "' + title + '" 吗？此操作不可恢复！')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php include '../templates/footer.php'; ?>