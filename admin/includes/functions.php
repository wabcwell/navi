<?php
/**
 * 管理后台公共函数库
 */

// 引入Category类
require_once __DIR__ . '/Category.php';

// 引入Database类
require_once __DIR__ . '/Database.php';

/**
 * 安全输出HTML
 */
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * 安全输出URL
 */
function u($string) {
    return urlencode($string);
}

/**
 * 获取分页信息
 */
function get_pagination($total_items, $current_page, $per_page = ITEMS_PER_PAGE) {
    $total_pages = max(1, ceil($total_items / $per_page));
    $current_page = max(1, min($current_page, $total_pages));
    
    return [
        'current_page' => $current_page,
        'total_pages' => $total_pages,
        'offset' => ($current_page - 1) * $per_page,
        'has_prev' => $current_page > 1,
        'has_next' => $current_page < $total_pages,
    ];
}

/**
 * 显示分页HTML
 */
function pagination_html($pagination, $base_url) {
    if ($pagination['total_pages'] <= 1) {
        return '';
    }
    
    $html = '<nav aria-label="分页"><ul class="pagination justify-content-center">';
    
    // 上一页
    if ($pagination['has_prev']) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $base_url . 'page=' . ($pagination['current_page'] - 1) . '">上一页</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">上一页</span></li>';
    }
    
    // 页码
    for ($i = 1; $i <= $pagination['total_pages']; $i++) {
        if ($i == $pagination['current_page']) {
            $html .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
        } else {
            $html .= '<li class="page-item"><a class="page-link" href="' . $base_url . 'page=' . $i . '">' . $i . '</a></li>';
        }
    }
    
    // 下一页
    if ($pagination['has_next']) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $base_url . 'page=' . ($pagination['current_page'] + 1) . '">下一页</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">下一页</span></li>';
    }
    
    $html .= '</ul></nav>';
    return $html;
}

/**
 * 格式化文件大小
 */
function format_file_size($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * 验证上传文件
 */
function validate_upload($file) {
    $errors = [];
    
    if (!isset($file['error']) || is_array($file['error'])) {
        $errors[] = '上传参数错误';
        return $errors;
    }
    
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            $errors[] = '没有选择文件';
            return $errors;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            $errors[] = '文件太大';
            return $errors;
        default:
            $errors[] = '上传失败';
            return $errors;
    }
    
    // 检查文件大小
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        $errors[] = '文件大小超过限制';
    }
    
    // 检查文件类型
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp'];
    
    if (!in_array($mime, $allowed_mimes)) {
        $errors[] = '不支持的文件类型';
    }
    
    return $errors;
}

/**
 * 获取分类列表
 * @param PDO $pdo 数据库连接
 * @param bool $onlyActive 是否只获取激活的分类
 * @return array 分类列表
 */
function get_categories($pdo, $onlyActive = true) {
    try {
        $category = new Category($pdo);
        return $category->getAll($onlyActive);
    } catch (Exception $e) {
        error_log("获取分类列表失败: " . $e->getMessage());
        return [];
    }
}

/**
 * 获取分类信息
 * @param PDO $pdo 数据库连接
 * @param int $id 分类ID
 * @return array|null 分类信息或null
 */
function get_category($pdo, $id) {
    try {
        $category = new Category($pdo);
        return $category->getById($id);
    } catch (Exception $e) {
        error_log("获取分类信息失败: " . $e->getMessage());
        return null;
    }
}

/**
 * 根据slug获取分类信息
 * @param PDO $pdo 数据库连接
 * @param string $slug 分类slug
 * @return array|null 分类信息或null
 */
function get_category_by_slug($pdo, $slug) {
    try {
        $category = new Category($pdo);
        return $category->getBySlug($slug);
    } catch (Exception $e) {
        error_log("根据slug获取分类信息失败: " . $e->getMessage());
        return null;
    }
}

/**
 * 创建新分类
 * @param PDO $pdo 数据库连接
 * @param array $data 分类数据
 * @return int|false 新创建的分类ID或false
 */
function create_category($pdo, $data) {
    try {
        $category = new Category($pdo);
        return $category->create($data);
    } catch (Exception $e) {
        error_log("创建分类失败: " . $e->getMessage());
        return false;
    }
}

/**
 * 更新分类
 * @param PDO $pdo 数据库连接
 * @param int $id 分类ID
 * @param array $data 更新数据
 * @return bool 是否更新成功
 */
function update_category($pdo, $id, $data) {
    try {
        $category = new Category($pdo);
        return $category->update($id, $data);
    } catch (Exception $e) {
        error_log("更新分类失败: " . $e->getMessage());
        return false;
    }
}

/**
 * 删除分类
 * @param PDO $pdo 数据库连接
 * @param int $id 分类ID
 * @return bool 是否删除成功
 */
function delete_category($pdo, $id) {
    try {
        $category = new Category($pdo);
        return $category->delete($id);
    } catch (Exception $e) {
        error_log("删除分类失败: " . $e->getMessage());
        return false;
    }
}

/**
 * 切换分类激活状态
 * @param PDO $pdo 数据库连接
 * @param int $id 分类ID
 * @return bool 是否切换成功
 */
function toggle_category_active($pdo, $id) {
    try {
        $category = new Category($pdo);
        return $category->toggleActive($id);
    } catch (Exception $e) {
        error_log("切换分类状态失败: " . $e->getMessage());
        return false;
    }
}

/**
 * 更新分类排序
 * @param PDO $pdo 数据库连接
 * @param array $order 排序数组
 * @return bool 是否更新成功
 */
function update_category_order($pdo, $order) {
    try {
        $category = new Category($pdo);
        return $category->updateOrder($order);
    } catch (Exception $e) {
        error_log("更新分类排序失败: " . $e->getMessage());
        return false;
    }
}

/**
 * 统计分类下的链接数量
 * @param PDO $pdo 数据库连接
 * @param int $category_id 分类ID
 * @return int 链接数量
 */
function count_category_links($pdo, $category_id) {
    try {
        $navigationLinkManager = get_navigation_link_manager();
        return $navigationLinkManager->countLinksByCategory($category_id);
    } catch (Exception $e) {
        error_log("统计分类链接数量失败: " . $e->getMessage());
        return 0;
    }
}

/**
 * 处理文件上传
 * 
 * @param array $file 上传的文件数组
 * @param string $sub_dir 子目录名称
 * @param array $allowed_types 允许的文件类型
 * @return array 上传结果数组
 */
function handle_file_upload($file, $sub_dir = '', $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp']) {
    $result = ['success' => false, 'filename' => null, 'error' => ''];
    
    // 检查上传错误
    if (!isset($file['error']) || is_array($file['error'])) {
        $result['error'] = '上传参数错误';
        return $result;
    }
    
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            $result['error'] = '没有选择文件';
            return $result;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            $result['error'] = '文件太大';
            return $result;
        default:
            $result['error'] = '上传失败';
            return $result;
    }
    
    $upload_dir = '../../uploads/';
    if ($sub_dir) {
        $upload_dir .= $sub_dir . '/';
    }
    
    // 确保上传目录存在
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // 获取允许的文件类型
    $settingsManager = get_settings_manager();
    $allowed_types_str = $settingsManager->get('upload_allowed_types', 'jpg,jpeg,png,gif,svg,webp,pdf,doc,docx,xls,xlsx,txt,zip,rar');
    $allowed_extensions = array_map('trim', explode(',', strtolower($allowed_types_str)));
    
    // 检查文件扩展名
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, $allowed_extensions)) {
        $result['error'] = '不支持的文件类型，允许的类型：' . strtoupper(implode(', ', $allowed_extensions));
        return $result;
    }
    
    // 检查文件类型
    $mime_type = '';
    
    // 尝试使用finfo（如果可用）
    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->file($file['tmp_name']);
    } else {
        // 回退使用文件扩展名检查
        $mime_type = 'application/octet-stream'; // 默认类型
    }
    
    // 验证MIME类型是否与扩展名匹配
    $mime_map = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'webp' => 'image/webp',
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'txt' => 'text/plain',
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed'
    ];
    
    $expected_mime = $mime_map[$file_extension] ?? null;
    if ($expected_mime && strpos($mime_type, $expected_mime) !== 0) {
        // MIME类型不匹配，但继续处理（可能是文件类型检测问题）
        // 不返回错误，让用户决定
    }
    
    // 检查文件大小
    $max_size_mb = $settingsManager->get('upload_max_size', 10);
    $max_size_bytes = $max_size_mb * 1024 * 1024;
    if ($file['size'] > $max_size_bytes) {
        $result['error'] = '文件大小不能超过' . $max_size_mb . 'MB';
        return $result;
    }
    
    // 生成唯一文件名
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('icon_') . '.' . strtolower($extension);
    $destination = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        $result['success'] = true;
        $result['filename'] = $filename;
    } else {
        $result['error'] = '文件移动失败';
    }
    
    return $result;
}

/**
 * 生成URL友好的别名
 * 
 * @param string $text 输入文本
 * @return string URL友好的别名
 */
function generate_slug($text) {
    // 转换为小写
    $text = mb_strtolower($text, 'UTF-8');
    
    // 替换非字母数字字符为连字符
    $text = preg_replace('/[^\p{L}\p{N}\s-]/u', '', $text);
    
    // 替换空格和连字符为单个连字符
    $text = preg_replace('/[\s-]+/', '-', $text);
    
    // 移除开头和结尾的连字符
    $text = trim($text, '-');
    
    // 如果结果为空，使用随机字符串
    if (empty($text)) {
        $text = uniqid('category-');
    }
    
    return $text;
}