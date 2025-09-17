<?php
/**
 * User 类
 * 专门处理 users 表的增删改查操作
 */

require_once 'Database.php';

class User {
    private $db;
    
    /**
 * 构造函数
 * 
 * @param Database|null $database 数据库实例
 */
public function __construct($database = null) {
    $this->db = $database ?: new Database();
}
    
    /**
     * 获取所有用户
     * 
     * @return array 用户数组
     */
    public function getAllUsers() {
        $sql = "SELECT * FROM users ORDER BY id ASC";
        
        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception('获取用户列表失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 根据ID获取用户
     * 
     * @param int $id 用户ID
     * @return array|null 用户数据或null
     */
    public function getUserById($id) {
        $sql = "SELECT * FROM users WHERE id = ?";
        
        try {
            $stmt = $this->db->query($sql, [$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            throw new Exception('获取用户失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 根据用户名获取用户
     * 
     * @param string $username 用户名
     * @return array|null 用户数据或null
     */
    public function getUserByUsername($username) {
        $sql = "SELECT * FROM users WHERE username = ?";
        
        try {
            $stmt = $this->db->query($sql, [$username]);
            return $stmt->fetch();
        } catch (Exception $e) {
            throw new Exception('获取用户失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 根据邮箱获取用户
     * 
     * @param string $email 邮箱
     * @return array|null 用户数据或null
     */
    public function getUserByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = ?";
        
        try {
            $stmt = $this->db->query($sql, [$email]);
            return $stmt->fetch();
        } catch (Exception $e) {
            throw new Exception('获取用户失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 创建新用户
     * 
     * @param array $data 用户数据
     * @return int 新创建的用户ID
     */
    public function createUser($data) {
        // 验证必需字段
        if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
            throw new Exception('用户名、邮箱和密码是必需的');
        }
        
        // 检查用户名是否已存在
        if ($this->getUserByUsername($data['username'])) {
            throw new Exception('用户名已存在');
        }
        
        // 检查邮箱是否已存在
        if ($this->getUserByEmail($data['email'])) {
            throw new Exception('邮箱已存在');
        }
        
        // 设置默认值
        $defaultData = [
            'username' => '',
            'email' => '',
            'password' => '',
            'real_name' => '',
            'role' => 'user',
            'login_count' => 0,
            'last_login' => null,
            'last_ip' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // 合并数据
        $userData = array_merge($defaultData, $data);
        
        // 密码加密
        $userData['password'] = password_hash($userData['password'], PASSWORD_DEFAULT);
        
        try {
            return $this->db->insert('users', $userData);
        } catch (Exception $e) {
            throw new Exception('创建用户失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 更新用户
     * 
     * @param int $id 用户ID
     * @param array $data 更新数据
     * @return bool 是否更新成功
     */
    public function updateUser($id, $data) {
        // 移除ID字段（不能更新）
        unset($data['id']);
        
        // 更新时间戳
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        // 如果有密码字段，进行加密
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        } elseif (isset($data['password'])) {
            // 如果密码为空，移除密码字段
            unset($data['password']);
        }
        
        try {
            $affectedRows = $this->db->update('users', $data, 'id = ?', [$id]);
            return $affectedRows > 0;
        } catch (Exception $e) {
            throw new Exception('更新用户失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 删除用户
     * 
     * @param int $id 用户ID
     * @return bool 是否删除成功
     */
    public function deleteUser($id) {
        // 不能删除ID为1的管理员用户
        if ($id == 1) {
            throw new Exception('不能删除管理员用户');
        }
        
        try {
            $affectedRows = $this->db->delete('users', 'id = ?', [$id]);
            return $affectedRows > 0;
        } catch (Exception $e) {
            throw new Exception('删除用户失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 用户登录验证
     * 
     * @param string $username 用户名或邮箱
     * @param string $password 密码
     * @param string $ip IP地址
     * @return array|false 用户数据或false
     */
    public function authenticate($username, $password, $ip = null) {
        // 根据用户名或邮箱查找用户
        $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
        
        try {
            $stmt = $this->db->query($sql, [$username, $username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // 更新登录信息
                $updateData = [
                    'login_count' => $user['login_count'] + 1,
                    'last_login' => date('Y-m-d H:i:s')
                ];
                
                if ($ip) {
                    $updateData['last_ip'] = $ip;
                }
                
                $this->updateUser($user['id'], $updateData);
                
                // 移除密码字段再返回
                unset($user['password']);
                return $user;
            }
            
            return false;
        } catch (Exception $e) {
            throw new Exception('用户验证失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 检查用户名是否已存在
     * 
     * @param string $username 用户名
     * @param int|null $excludeId 排除的用户ID
     * @return bool 用户名是否存在
     */
    public function isUsernameExists($username, $excludeId = null) {
        $sql = "SELECT id FROM users WHERE username = ?";
        $params = [$username];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        try {
            $stmt = $this->db->query($sql, $params);
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            throw new Exception('检查用户名失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 检查邮箱是否已存在
     * 
     * @param string $email 邮箱
     * @param int|null $excludeId 排除的用户ID
     * @return bool 邮箱是否存在
     */
    public function isEmailExists($email, $excludeId = null) {
        $sql = "SELECT id FROM users WHERE email = ?";
        $params = [$email];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        try {
            $stmt = $this->db->query($sql, $params);
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            throw new Exception('检查邮箱失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 获取用户统计信息
     * 
     * @return array 统计信息
     */
    public function getStats() {
        $sql = "SELECT 
                    COUNT(*) as total_users,
                    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_count,
                    SUM(CASE WHEN role = 'editor' THEN 1 ELSE 0 END) as editor_count,
                    SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) as user_count
                FROM users";
        
        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetch();
        } catch (Exception $e) {
            throw new Exception('获取统计信息失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 更新用户最后登录时间
     * 
     * @param int $id 用户ID
     * @param string $ip IP地址
     * @return bool 是否更新成功
     */
    public function updateLastLogin($id, $ip = null) {
        $data = [
            'login_count' => 'login_count + 1',
            'last_login' => date('Y-m-d H:i:s')
        ];
        
        if ($ip) {
            $data['last_ip'] = $ip;
        }
        
        try {
            $sql = "UPDATE users SET login_count = login_count + 1, last_login = ?, updated_at = ?";
            $params = [date('Y-m-d H:i:s'), date('Y-m-d H:i:s')];
            
            if ($ip) {
                $sql .= ", last_ip = ?";
                $params[] = $ip;
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $id;
            
            $stmt = $this->db->query($sql, $params);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            throw new Exception('更新登录信息失败: ' . $e->getMessage());
        }
    }
}