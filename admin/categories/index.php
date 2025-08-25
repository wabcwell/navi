<?php
require_once '../includes/init.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// 检查是否登录
if (!is_logged_in()) {
    header('Location: ../login.php');
    exit;
}

// 获取数据库连接
$pdo = get_db_connection();

// 处理删除请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = intval($_POST['delete_id']);
    
    try {
        // 检查是否有链接使用此分类
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM navigation_links WHERE category_id = ?");
        $stmt->execute([$delete_id]);
        $link_count = $stmt->fetchColumn();
        
        if ($link_count > 0) {
            $_SESSION['error'] = '无法删除：该分类下还有 ' . $link_count . ' 个链接，请先删除或转移这些链接。';
        } else {
            // 删除分类
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$delete_id]);
            
            if ($stmt->rowCount() > 0) {
                $_SESSION['success'] = '分类删除成功！';
            } else {
                $_SESSION['error'] = '分类删除失败：未找到该分类。';
            }
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
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE categories SET order_index = ? WHERE id = ?");
        
        foreach ($_POST['order_index'] as $id => $order_index) {
            $stmt->execute([intval($order_index), intval($id)]);
        }
        
        $pdo->commit();
        $_SESSION['success'] = '排序更新成功！';
        header('Location: index.php');
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = '排序更新失败：' . $e->getMessage();
        header('Location: index.php');
        exit;
    }
}

// 获取分页参数
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// 获取搜索参数
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// 构建查询条件
$where = [];
$params = [];

if ($search) {
    $where[] = "(name LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = $where ? "WHERE " . implode(" AND ", $where) : "";

// 获取总数
$count_sql = "SELECT COUNT(*) FROM categories $where_clause";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total = $stmt->fetchColumn();

// 计算总页数
$total_pages = ceil($total / $per_page);

// 获取分类列表，包含链接计数
$sql = "SELECT c.*, COALESCE(l.link_count, 0) as link_count 
        FROM categories c 
        LEFT JOIN (
            SELECT category_id, COUNT(*) as link_count 
            FROM navigation_links 
            GROUP BY category_id
        ) l ON c.id = l.category_id 
        $where_clause 
        ORDER BY c.order_index ASC, c.id DESC 
        LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../templates/header.php';
?>



<div class="mb-4">
    <a href="add.php" class="btn btn-primary">
        <i class="bi bi-plus"></i> 添加分类
    </a>
</div>

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

<!-- 搜索表单 -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-8">
                <input type="text" class="form-control" name="search" 
                       placeholder="搜索分类..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-search"></i> 搜索
                </button>
                <?php if ($search): ?>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x"></i> 清除
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- 分类列表 -->
<form method="POST">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">分类列表</h5>
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
                                    <?php if ($category['icon']): ?>
                                        <?php 
                                        // 检查是否为Font Awesome类名
                                        $icon = trim($category['icon']);
                                        if (preg_match('/^(fa-|fas\s|far\s|fab\s)/i', $icon)): 
                                            $displayIcon = $icon;
                                            if (!preg_match('/^(fas|far|fab)\s/i', $icon)) {
                                                $displayIcon = 'fas ' . $icon;
                                            }
                                        ?>
                                            <i class="<?php echo htmlspecialchars($displayIcon); ?>" style="font-size: 1.5rem; color: <?php echo htmlspecialchars($category['color']); ?>;"></i>
                                        <?php 
                                        // 检查是否为Bootstrap图标类名
                                        elseif (strpos($icon, 'bi-') === 0): ?>
                                            <i class="<?php echo htmlspecialchars($icon); ?>" style="font-size: 1.5rem; color: <?php echo htmlspecialchars($category['color']); ?>;"></i>
                                        <?php 
                                        // 否则当作图片文件处理
                                        else: ?>
                                            <img src="/uploads/categories/<?php echo $category['icon']; ?>" 
                                                 alt="<?php echo htmlspecialchars($category['name']); ?>" 
                                                 class="image-preview" style="width: 40px; height: 40px; border-radius: 4px;">
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="bg-secondary text-white d-flex align-items-center justify-content-center rounded" 
                                             style="width: 40px; height: 40px;">
                                            <i class="bi bi-folder" style="font-size: 1.2rem;"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                    <br>
                                    <small class="text-muted">别名: <?php echo htmlspecialchars($category['slug'] ?? $category['name']); ?></small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars(mb_substr($category['description'], 0, 50)); ?>
                                    <?php echo mb_strlen($category['description']) > 50 ? '...' : ''; ?>
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
                                                onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>')" 
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
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">
                        <i class="bi bi-chevron-left"></i> 上一页
                    </a>
                </li>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">
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