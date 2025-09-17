<?php
/**
 * 日志管理类
 * 专门处理admin_logs, error_logs, login_logs, operation_logs表的增删改查操作
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
    
    // ==================== 管理员操作日志 (admin_logs) ====================
    
    /**
     * 添加管理员操作日志
     * 
     * @param int $user_id 用户ID
     * @param string $action 操作类型
     * @param string $details 操作详情
     * @param string $ip_address IP地址
     * @param string $user_agent 用户代理
     * @return int 插入记录的ID
     */
    public function addAdminLog($user_id, $action, $details = '', $ip_address = null, $user_agent = null) {
        $data = [
            'user_id' => $user_id,
            'action' => $action,
            'details' => $details,
            'ip_address' => $ip_address ?: ($_SERVER['REMOTE_ADDR'] ?? null),
            'user_agent' => $user_agent ?: ($_SERVER['HTTP_USER_AGENT'] ?? null)
        ];
        
        return $this->database->insert('admin_logs', $data);
    }
    
    /**
     * 获取管理员操作日志列表
     * 
     * @param int $limit 限制数量
     * @param int $offset 偏移量
     * @param string $order_by 排序字段
     * @param string $order_direction 排序方向
     * @return array 日志列表
     */
    public function getAdminLogs($limit = 50, $offset = 0, $order_by = 'created_at', $order_direction = 'DESC') {
        $sql = "SELECT * FROM admin_logs ORDER BY {$order_by} {$order_direction} LIMIT :limit OFFSET :offset";
        $stmt = $this->database->getConnection()->prepare($sql);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * 根据ID获取管理员操作日志
     * 
     * @param int $id 日志ID
     * @return array|null 日志信息
     */
    public function getAdminLogById($id) {
        $stmt = $this->database->query("SELECT * FROM admin_logs WHERE id = ?", [$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * 删除管理员操作日志
     * 
     * @param int $id 日志ID
     * @return int 受影响的行数
     */
    public function deleteAdminLog($id) {
        return $this->database->delete('admin_logs', 'id = ?', [$id]);
    }
    
    /**
     * 清空管理员操作日志
     * 
     * @return bool 是否成功
     */
    public function clearAdminLogs() {
        try {
            $this->database->getConnection()->exec("TRUNCATE TABLE admin_logs");
            return true;
        } catch (Exception $e) {
            return false;
        }
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
     * 添加操作日志
     * 
     * @param int $user_id 用户ID
     * @param string $username 用户名
     * @param string $action 操作类型
     * @param string $target_type 目标类型
     * @param int $target_id 目标ID
     * @param string $old_values 旧值
     * @param string $new_values 新值
     * @param string $ip_address IP地址
     * @return int 插入记录的ID
     */
    public function addOperationLog($user_id = null, $username = null, $action = '', $target_type = null, $target_id = null, $old_values = null, $new_values = null, $ip_address = null) {
        $data = [
            'user_id' => $user_id,
            'username' => $username,
            'action' => $action,
            'target_type' => $target_type,
            'target_id' => $target_id,
            'old_values' => $old_values,
            'new_values' => $new_values,
            'ip_address' => $ip_address ?: ($_SERVER['REMOTE_ADDR'] ?? null)
        ];
        
        return $this->database->insert('operation_logs', $data);
    }
    
    /**
     * 获取操作日志列表
     * 
     * @param int $limit 限制数量
     * @param int $offset 偏移量
     * @param string $order_by 排序字段
     * @param string $order_direction 排序方向
     * @return array 日志列表
     */
    public function getOperationLogs($limit = 50, $offset = 0, $order_by = 'created_at', $order_direction = 'DESC') {
        $sql = "SELECT * FROM operation_logs ORDER BY {$order_by} {$order_direction} LIMIT :limit OFFSET :offset";
        $stmt = $this->database->getConnection()->prepare($sql);
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
        $count_sql = "SELECT COUNT(*) FROM {$table} {$where_sql}";
        $stmt = $this->database->getConnection()->prepare($count_sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        $stmt->execute();
        $total = $stmt->fetchColumn();
        
        return [
            'total' => $total
        ];
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