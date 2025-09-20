<?php
/**
 * 日志管理类
 * 专门处理error_logs, login_logs, operation_logs表的增删改查操作
 */

require_once 'Database.php';

class Logs {
    private $database;
    
    /**
 * 构造函数
 * 
 * @param Database|null $database 数据库实例
 */
public function __construct($database = null) {
    $this->database = $database ?: new Database();
}
    
    // ==================== 错误日志 (error_logs) ====================
    
    /**
     * 添加错误日志
     * 
     * @param string $level 错误级别
     * @param string $message 错误信息
     * @param string $file 文件名
     * @param int $line 行号
     * @param string $context 上下文
     * @param int $user_id 用户ID
     * @param string $ip_address IP地址
     * @param string $user_agent 用户代理
     * @return int 插入记录的ID
     */
    public function addErrorLog($level, $message, $file = null, $line = null, $context = null, $user_id = null, $ip_address = null, $user_agent = null) {
        $data = [
            'level' => $level,
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'context' => $context,
            'user_id' => $user_id,
            'ip_address' => $ip_address ?: ($_SERVER['REMOTE_ADDR'] ?? null),
            'user_agent' => $user_agent ?: ($_SERVER['HTTP_USER_AGENT'] ?? null)
        ];
        
        return $this->database->insert('error_logs', $data);
    }
    
    /**
     * 获取错误日志列表
     * 
     * @param int $limit 限制数量
     * @param int $offset 偏移量
     * @param string $order_by 排序字段
     * @param string $order_direction 排序方向
     * @return array 日志列表
     */
    public function getErrorLogs($limit = 50, $offset = 0, $order_by = 'created_at', $order_direction = 'DESC') {
        $sql = "SELECT * FROM error_logs ORDER BY {$order_by} {$order_direction} LIMIT :limit OFFSET :offset";
        $stmt = $this->database->getConnection()->prepare($sql);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * 根据ID获取错误日志
     * 
     * @param int $id 日志ID
     * @return array|null 日志信息
     */
    public function getErrorLogById($id) {
        $stmt = $this->database->query("SELECT * FROM error_logs WHERE id = ?", [$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * 删除错误日志
     * 
     * @param int $id 日志ID
     * @return int 受影响的行数
     */
    public function deleteErrorLog($id) {
        return $this->database->delete('error_logs', 'id = ?', [$id]);
    }
    
    /**
     * 清空错误日志
     * 
     * @return bool 是否成功
     */
    public function clearErrorLogs() {
        try {
            $this->database->getConnection()->exec("TRUNCATE TABLE error_logs");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    // ==================== 登录日志 (login_logs) ====================
    
    /**
     * 添加登录日志
     * 
     * @param int $user_id 用户ID
     * @param string $username 用户名
     * @param bool $success 是否登录成功
     * @param string $failure_reason 失败原因
     * @param string $ip_address IP地址
     * @param string $user_agent 用户代理
     * @return int 插入记录的ID
     */
    public function addLoginLog($user_id = null, $username = null, $success = true, $failure_reason = null, $ip_address = null, $user_agent = null) {
        $data = [
            'user_id' => $user_id,
            'username' => $username,
            'ip_address' => $ip_address ?: ($_SERVER['REMOTE_ADDR'] ?? null),
            'user_agent' => $user_agent ?: ($_SERVER['HTTP_USER_AGENT'] ?? null),
            'success' => $success ? 1 : 0,
            'failure_reason' => $failure_reason
        ];
        
        return $this->database->insert('login_logs', $data);
    }
    
    /**
     * 获取登录日志列表
     * 
     * @param int $limit 限制数量
     * @param int $offset 偏移量
     * @param string $order_by 排序字段
     * @param string $order_direction 排序方向
     * @return array 日志列表
     */
    public function getLoginLogs($limit = 50, $offset = 0, $order_by = 'login_time', $order_direction = 'DESC') {
        $sql = "SELECT * FROM login_logs ORDER BY {$order_by} {$order_direction} LIMIT :limit OFFSET :offset";
        $stmt = $this->database->getConnection()->prepare($sql);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * 根据ID获取登录日志
     * 
     * @param int $id 日志ID
     * @return array|null 日志信息
     */
    public function getLoginLogById($id) {
        $stmt = $this->database->query("SELECT * FROM login_logs WHERE id = ?", [$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * 删除登录日志
     * 
     * @param int $id 日志ID
     * @return int 受影响的行数
     */
    public function deleteLoginLog($id) {
        return $this->database->delete('login_logs', 'id = ?', [$id]);
    }
    
    /**
     * 清空登录日志
     * 
     * @return bool 是否成功
     */
    public function clearLoginLogs() {
        try {
            $this->database->getConnection()->exec("TRUNCATE TABLE login_logs");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    // ==================== 操作日志 (operation_logs) ====================
    
    /**
     * 添加操作日志（新规范）
     * 
     * @param array $logData 日志数据数组
     * @return int 插入记录的ID
     */
    public function addOperationLog($logData) {
        // 设置默认值
        $defaults = [
            'operation_time' => date('Y-m-d H:i:s.u'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'status' => '成功',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // 合并数据
        $data = array_merge($defaults, $logData);
        
        // 确保operation_details是JSON格式
        if (isset($data['operation_details']) && is_array($data['operation_details'])) {
            $data['operation_details'] = json_encode($data['operation_details'], JSON_UNESCAPED_UNICODE);
        }
        
        return $this->database->insert('operation_logs', $data);
    }
    
    /**
     * 快速添加操作日志（分类操作）
     * 
     * @param int $userid 用户ID
     * @param string $operation_type 操作类型：新增、删除、编辑
     * @param int $categorie_id 分类ID
     * @param string $categorie_name 分类名称
     * @param array $operation_details 操作详情
     * @param string $status 操作状态
     * @return int 插入记录的ID
     */
    public function addCategoryOperationLog($userid, $operation_type, $categorie_id, $categorie_name, $operation_details = [], $status = '成功') {
        return $this->addOperationLog([
            'userid' => $userid,
            'operation_module' => '分类',
            'operation_type' => $operation_type,
            'categorie_id' => $categorie_id,
            'categorie_name' => $categorie_name,
            'operation_details' => $operation_details,
            'status' => $status
        ]);
    }
    
    /**
     * 快速添加操作日志（链接操作）
     * 
     * @param int $userid 用户ID
     * @param string $operation_type 操作类型：新增、删除、编辑
     * @param int $link_id 链接ID
     * @param string $link_name 链接名称
     * @param array $operation_details 操作详情
     * @param string $status 操作状态
     * @return int 插入记录的ID
     */
    public function addLinkOperationLog($userid, $operation_type, $link_id, $link_name, $operation_details = [], $status = '成功') {
        return $this->addOperationLog([
            'userid' => $userid,
            'operation_module' => '链接',
            'operation_type' => $operation_type,
            'link_id' => $link_id,
            'link_name' => $link_name,
            'operation_details' => $operation_details,
            'status' => $status
        ]);
    }
    
    /**
     * 快速添加操作日志（用户操作）
     * 
     * @param int $userid 用户ID
     * @param string $operation_type 操作类型：新增、删除、编辑
     * @param int $operated_id 被操作用户ID
     * @param string $operated_name 被操作用户名称
     * @param array $operation_details 操作详情
     * @param string $status 操作状态
     * @return int 插入记录的ID
     */
    public function addUserOperationLog($userid, $operation_type, $operated_id, $operated_name, $operation_details = [], $status = '成功') {
        return $this->addOperationLog([
            'userid' => $userid,
            'operation_module' => '用户',
            'operation_type' => $operation_type,
            'operated_id' => $operated_id,
            'operated_name' => $operated_name,
            'operation_details' => $operation_details,
            'status' => $status
        ]);
    }
    
    /**
     * 快速添加操作日志（文件操作）
     * 
     * @param int $userid 用户ID
     * @param string $operation_desc 操作描述
     * @param string $files 文件路径
     * @param array $operation_details 操作详情
     * @param string $status 操作状态
     * @return int 插入记录的ID
     */
    public function addFileOperationLog($userid, $operation_desc, $files, $operation_details = [], $status = '成功') {
        // 根据操作描述确定操作类型（新增、删除、编辑）
        if (strpos($operation_desc, '新增') !== false || strpos($operation_desc, '添加') !== false) {
            $operation_type = '新增';
        } elseif (strpos($operation_desc, '删除') !== false) {
            $operation_type = '删除';
        } elseif (strpos($operation_desc, '编辑') !== false || strpos($operation_desc, '修改') !== false) {
            $operation_type = '编辑';
        } else {
            $operation_type = '编辑'; // 默认
        }
        
        return $this->addOperationLog([
            'userid' => $userid,
            'operation_module' => '文件',
            'operation_type' => $operation_type,
            'files' => $files,
            'operation_details' => array_merge($operation_details, ['operation_description' => $operation_desc]),
            'status' => $status
        ]);
    }
    
    /**
     * 获取操作日志列表（支持搜索和筛选）
     * 
     * @param int $limit 限制数量
     * @param int $offset 偏移量
     * @param string $order_by 排序字段
     * @param string $order_direction 排序方向
     * @param string $search 搜索关键词
     * @param string $operation_module 操作模块筛选
     * @param string $operation_type 操作类型筛选
     * @param string $date_from 开始日期
     * @param string $date_to 结束日期
     * @return array 日志列表
     */
    public function getOperationLogs($limit = 50, $offset = 0, $order_by = 'created_at', $order_direction = 'DESC', $search = '', $operation_module = '', $operation_type = '', $date_from = '', $date_to = '') {
        $where_conditions = [];
        $params = [];
        
        // 搜索条件
        if (!empty($search)) {
            $where_conditions[] = "(operation_details LIKE :search OR userid LIKE :search_userid)";
            $params[':search'] = '%' . $search . '%';
            $params[':search_userid'] = '%' . $search . '%';
        }
        
        // 操作模块筛选
        if (!empty($operation_module)) {
            $where_conditions[] = "operation_module = :operation_module";
            $params[':operation_module'] = $operation_module;
        }
        
        // 操作类型筛选
        if (!empty($operation_type)) {
            $where_conditions[] = "operation_type = :operation_type";
            $params[':operation_type'] = $operation_type;
        }
        
        // 日期范围筛选
        if (!empty($date_from)) {
            $where_conditions[] = "DATE(operation_time) >= :date_from";
            $params[':date_from'] = $date_from;
        }
        
        if (!empty($date_to)) {
            $where_conditions[] = "DATE(operation_time) <= :date_to";
            $params[':date_to'] = $date_to;
        }
        
        $where_sql = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $sql = "SELECT * FROM operation_logs {$where_sql} ORDER BY {$order_by} {$order_direction} LIMIT :limit OFFSET :offset";
        $stmt = $this->database->getConnection()->prepare($sql);
        
        // 绑定参数
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * 根据ID获取操作日志
     * 
     * @param int $id 日志ID
     * @return array|null 日志信息
     */
    public function getOperationLogById($id) {
        $stmt = $this->database->query("SELECT * FROM operation_logs WHERE id = ?", [$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * 删除操作日志
     * 
     * @param int $id 日志ID
     * @return int 受影响的行数
     */
    public function deleteOperationLog($id) {
        return $this->database->delete('operation_logs', 'id = ?', [$id]);
    }
    
    /**
     * 清空操作日志
     * 
     * @return bool 是否成功
     */
    public function clearOperationLogs() {
        try {
            $this->database->getConnection()->exec("TRUNCATE TABLE operation_logs");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    // ==================== 通用方法 ====================
    
    /**
     * 根据时间范围获取日志统计
     * 
     * @param string $table 表名
     * @param string $date_from 开始日期
     * @param string $date_to 结束日期
     * @return array 统计信息
     */
    public function getLogStats($table, $date_from = null, $date_to = null) {
        $where_conditions = [];
        $params = [];
        
        if ($date_from) {
            $where_conditions[] = "created_at >= :date_from";
            $params['date_from'] = $date_from . ' 00:00:00';
        }
        
        if ($date_to) {
            $where_conditions[] = "created_at <= :date_to";
            $params['date_to'] = $date_to . ' 23:59:59';
        }
        
        $where_sql = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        // 总记录数
        $count_sql = "SELECT COUNT(*) as total FROM {$table} {$where_sql}";
        $stmt = $this->database->getConnection()->prepare($count_sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * 获取操作日志统计信息
     * 
     * @param string $search 搜索关键词
     * @param string $operation_module 操作模块筛选
     * @param string $operation_type 操作类型筛选
     * @param string $date_from 开始日期
     * @param string $date_to 结束日期
     * @return array 统计信息
     */
    public function getOperationLogStats($search = '', $operation_module = '', $operation_type = '', $date_from = '', $date_to = '') {
        $where_conditions = [];
        $params = [];
        
        // 搜索条件
        if (!empty($search)) {
            $where_conditions[] = "(operation_details LIKE :search OR userid LIKE :search_userid)";
            $params[':search'] = '%' . $search . '%';
            $params[':search_userid'] = '%' . $search . '%';
        }
        
        // 操作模块筛选
        if (!empty($operation_module)) {
            $where_conditions[] = "operation_module = :operation_module";
            $params[':operation_module'] = $operation_module;
        }
        
        // 操作类型筛选
        if (!empty($operation_type)) {
            $where_conditions[] = "operation_type = :operation_type";
            $params[':operation_type'] = $operation_type;
        }
        
        // 日期范围筛选
        if (!empty($date_from)) {
            $where_conditions[] = "DATE(operation_time) >= :date_from";
            $params[':date_from'] = $date_from;
        }
        
        if (!empty($date_to)) {
            $where_conditions[] = "DATE(operation_time) <= :date_to";
            $params[':date_to'] = $date_to;
        }
        
        $where_sql = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $sql = "SELECT COUNT(*) as total FROM operation_logs {$where_sql}";
        $stmt = $this->database->getConnection()->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * 批量删除日志
     * 
     * @param string $table 表名
     * @param array $ids ID数组
     * @return int 受影响的行数
     */
    public function batchDeleteLogs($table, $ids) {
        if (empty($ids)) {
            return 0;
        }
        
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "DELETE FROM {$table} WHERE id IN ({$placeholders})";
        $stmt = $this->database->getConnection()->prepare($sql);
        $stmt->execute($ids);
        return $stmt->rowCount();
    }
}