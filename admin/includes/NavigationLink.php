<?php
/**
 * NavigationLink 类
 * 专门处理 navigation_links 表的增删改查操作
 */

require_once 'Database.php';

class NavigationLink {
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
     * 获取所有链接
     * 
     * @param bool $activeOnly 是否只获取激活的链接
     * @return array 链接数组
     */
    public function getAllLinks($activeOnly = true) {
        $sql = "SELECT * FROM navigation_links";
        $params = [];
        
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        
        $sql .= " ORDER BY order_index ASC, id ASC";
        
        try {
            $stmt = $this->db->query($sql, $params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception('获取链接列表失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 根据ID获取链接
     * 
     * @param int $id 链接ID
     * @return array|null 链接数据或null
     */
    public function getLinkById($id) {
        $sql = "SELECT * FROM navigation_links WHERE id = ?";
        
        try {
            $stmt = $this->db->query($sql, [$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            throw new Exception('获取链接失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 根据分类ID获取链接
     * 
     * @param int $categoryId 分类ID
     * @param bool $activeOnly 是否只获取激活的链接
     * @return array 链接数组
     */
    public function getLinksByCategory($categoryId, $activeOnly = true) {
        $sql = "SELECT * FROM navigation_links WHERE category_id = ?";
        $params = [$categoryId];
        
        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }
        
        $sql .= " ORDER BY order_index ASC, id ASC";
        
        try {
            $stmt = $this->db->query($sql, $params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception('获取分类链接失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 创建新链接
     * 
     * @param array $data 链接数据
     * @return int 新创建的链接ID
     */
    public function createLink($data) {
        // 验证必需字段
        if (empty($data['title']) || empty($data['url']) || empty($data['category_id'])) {
            throw new Exception('标题、URL和分类是必需的');
        }
        
        // 设置默认值
        $defaultData = [
            'title' => '',
            'url' => '',
            'description' => '',
            'category_id' => 0,
            'icon_type' => 'none',
            'icon_fontawesome' => '',
            'icon_fontawesome_color' => '',
            'icon_color_url' => '',
            'icon_color_upload' => '',
            'order_index' => 0,
            'is_active' => 1,
            'click_count' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // 合并数据
        $linkData = array_merge($defaultData, $data);
        
        try {
            return $this->db->insert('navigation_links', $linkData);
        } catch (Exception $e) {
            throw new Exception('创建链接失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 更新链接
     * 
     * @param int $id 链接ID
     * @param array $data 更新数据
     * @return bool 是否更新成功
     */
    public function updateLink($id, $data) {
        // 移除ID字段（不能更新）
        unset($data['id']);
        
        // 更新时间戳
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        try {
            $affectedRows = $this->db->update('navigation_links', $data, 'id = ?', [$id]);
            return $affectedRows > 0;
        } catch (Exception $e) {
            throw new Exception('更新链接失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 删除链接
     * 
     * @param int $id 链接ID
     * @return bool 是否删除成功
     */
    public function deleteLink($id) {
        try {
            $affectedRows = $this->db->delete('navigation_links', 'id = ?', [$id]);
            return $affectedRows > 0;
        } catch (Exception $e) {
            throw new Exception('删除链接失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 增加链接点击次数
     * 
     * @param int $id 链接ID
     * @return bool 是否更新成功
     */
    public function incrementClickCount($id) {
        $sql = "UPDATE navigation_links SET click_count = click_count + 1 WHERE id = ?";
        
        try {
            $stmt = $this->db->query($sql, [$id]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            throw new Exception('更新点击次数失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 检查URL是否已存在
     * 
     * @param string $url URL地址
     * @param int|null $excludeId 排除的链接ID
     * @return bool URL是否存在
     */
    public function isUrlExists($url, $excludeId = null) {
        $sql = "SELECT id FROM navigation_links WHERE url = ?";
        $params = [$url];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        try {
            $stmt = $this->db->query($sql, $params);
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            throw new Exception('检查URL失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 获取链接统计信息
     * 
     * @return array 统计信息
     */
    public function getStats() {
        $sql = "SELECT 
                    COUNT(*) as total_links,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_links,
                    SUM(click_count) as total_clicks
                FROM navigation_links";
        
        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetch();
        } catch (Exception $e) {
            throw new Exception('获取统计信息失败: ' . $e->getMessage());
        }
    }
}