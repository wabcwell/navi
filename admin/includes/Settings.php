<?php
/**
 * Settings 类
 * 专门处理 settings 表的增删改查操作
 */

class Settings {
    private $database;
    private static $instance = null;
    
    public function __construct($database = null) {
        if ($database === null) {
            $this->database = new Database();
        } else {
            $this->database = $database;
        }
    }
    
    /**
     * 获取Settings单例实例
     * @return Settings
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 获取网站URL
     * @return string
     */
    public static function getSiteUrl() {
        if (!defined('SITE_URL')) {
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            return $protocol . '://' . $host;
        }
        return SITE_URL;
    }
    
    /**
     * 获取后台URL
     * @return string
     */
    public static function getAdminUrl() {
        return self::getSiteUrl() . '/admin';
    }
    
    /**
     * 获取所有设置
     * @return array 设置列表
     */
    public function getAll() {
        try {
            $stmt = $this->database->query("SELECT * FROM settings ORDER BY setting_key ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("获取设置列表失败: " . $e->getMessage());
        }
    }
    
    /**
     * 获取设置（兼容原函数）
     * @param string $key 设置键名
     * @param mixed $default 默认值
     * @return mixed 设置值
     */
    public function get($key, $default = null) {
        try {
            $stmt = $this->database->query("SELECT value FROM settings WHERE key_name = ?", [$key]);
            $result = $stmt->fetch();
            return $result ? $result['value'] : $default;
        } catch (Exception $e) {
            return $default;
        }
    }
    
    /**
     * 根据键名获取设置项完整信息
     * @param string $key 设置键名
     * @return array|null 设置项信息或null
     */
    public function getByKey($key) {
        try {
            $stmt = $this->database->query("SELECT * FROM settings WHERE setting_key = ?", [$key]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("获取设置项失败: " . $e->getMessage());
        }
    }
    
    /**
     * 设置配置项（兼容原函数）
     * @param string $key 设置键名
     * @param mixed $value 设置值
     * @return bool 是否成功
     */
    public function set($key, $value) {
        try {
            // 首先检查记录是否存在
            $existing = $this->get($key);
            
            if ($existing !== null) {
                // 更新现有记录
                return $this->database->update('settings', ['value' => $value], 'key_name = ?', [$key]) > 0;
            } else {
                // 插入新记录
                return $this->database->insert('settings', ['key_name' => $key, 'value' => $value]) > 0;
            }
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * 获取设置（静态方法，兼容原函数）
     * @param string $key 设置键名
     * @param mixed $default 默认值
     * @return mixed 设置值
     */
    public static function getSiteSetting($key, $default = null) {
        return self::getInstance()->get($key, $default);
    }
    
    /**
     * 设置配置项（静态方法，兼容原函数）
     * @param string $key 设置键名
     * @param mixed $value 设置值
     * @return bool 是否成功
     */
    public static function setSiteSetting($key, $value) {
        return self::getInstance()->set($key, $value);
    }
    
    /**
     * 批量设置配置项
     * @param array $settings 设置项数组
     * @return bool 是否全部设置成功
     */
    public function setBatch($settings) {
        try {
            $this->database->beginTransaction();
            
            foreach ($settings as $key => $value) {
                $stmt = $this->database->query(
                    "REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)",
                    [$key, $value]
                );
            }
            
            $this->database->commit();
            return true;
        } catch (Exception $e) {
            $this->database->rollback();
            throw new Exception("批量设置配置项失败: " . $e->getMessage());
        }
    }
    
    /**
     * 更新设置项
     * @param int $id 设置项ID
     * @param array $data 更新数据
     * @return bool 是否更新成功
     */
    public function update($id, $data) {
        try {
            // 构建更新SQL
            $fields = [];
            $params = [];
            
            foreach ($data as $key => $value) {
                // 只处理允许的字段
                $allowedFields = ['setting_key', 'setting_value', 'setting_type', 'description'];
                
                if (in_array($key, $allowedFields)) {
                    $fields[] = "$key = ?";
                    $params[] = $value;
                }
            }
            
            // 如果没有要更新的字段
            if (empty($fields)) {
                return false;
            }
            
            // 添加ID到参数
            $params[] = $id;
            
            // 执行更新
            $sql = "UPDATE settings SET " . implode(', ', $fields) . " WHERE id = ?";
            $stmt = $this->database->query($sql, $params);
            
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            throw new Exception("更新设置项失败: " . $e->getMessage());
        }
    }
    
    /**
     * 删除设置项
     * @param string $key 设置键名
     * @return bool 是否删除成功
     */
    public function delete($key) {
        try {
            $stmt = $this->database->query("DELETE FROM settings WHERE setting_key = ?", [$key]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            throw new Exception("删除设置项失败: " . $e->getMessage());
        }
    }
    
    /**
     * 根据ID删除设置项
     * @param int $id 设置项ID
     * @return bool 是否删除成功
     */
    public function deleteById($id) {
        try {
            $stmt = $this->database->query("DELETE FROM settings WHERE id = ?", [$id]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            throw new Exception("删除设置项失败: " . $e->getMessage());
        }
    }
    
    /**
     * 检查设置项是否存在
     * @param string $key 设置键名
     * @return bool 是否存在
     */
    public function exists($key) {
        try {
            $stmt = $this->database->query("SELECT COUNT(*) FROM settings WHERE setting_key = ?", [$key]);
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
}