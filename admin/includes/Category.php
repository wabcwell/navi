<?php
/**
 * Category 类
 * 专门处理 categories 表的增删改查操作
 */

class Category {
    private $database;
    
    public function __construct($database) {
        $this->database = $database;
    }
    
    /**
     * 获取所有分类
     * @param bool $onlyActive 是否只获取激活的分类
     * @return array 分类列表
     */
    public function getAll($onlyActive = true) {
        try {
            $sql = "SELECT * FROM categories";
            if ($onlyActive) {
                $sql .= " WHERE is_active = 1";
            }
            $sql .= " ORDER BY order_index ASC, id ASC";
            
            $stmt = $this->database->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("获取分类列表失败: " . $e->getMessage());
        }
    }
    
    /**
     * 根据ID获取分类
     * @param int $id 分类ID
     * @return array|null 分类信息或null
     */
    public function getById($id) {
        try {
            $stmt = $this->database->query("SELECT * FROM categories WHERE id = ?", [$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("获取分类失败: " . $e->getMessage());
        }
    }
    
    /**
     * 根据slug获取分类
     * @param string $slug 分类slug
     * @return array|null 分类信息或null
     */
    public function getBySlug($slug) {
        try {
            $stmt = $this->database->query("SELECT * FROM categories WHERE slug = ? AND is_active = 1", [$slug]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("获取分类失败: " . $e->getMessage());
        }
    }
    
    /**
     * 创建新分类
     * @param array $data 分类数据
     * @return int 新创建的分类ID
     */
    public function create($data) {
        try {
            // 验证必需字段
            if (empty($data['name'])) {
                throw new Exception("分类名称不能为空");
            }
            
            // 自动生成slug（如果未提供）
            if (empty($data['slug'])) {
                $data['slug'] = $this->generateSlug($data['name']);
            }
            
            // 设置默认值
            $data['color'] = $data['color'] ?? '#007bff';
            $data['icon_fontawesome'] = $data['icon_fontawesome'] ?? null;
            $data['icon_fontawesome_color'] = $data['icon_fontawesome_color'] ?? null;
            $data['icon_color_upload'] = $data['icon_color_upload'] ?? null;
            $data['icon_color_url'] = $data['icon_color_url'] ?? null;
            $data['icon_type'] = $data['icon_type'] ?? 'fontawesome';
            $data['order_index'] = $data['order_index'] ?? 0;
            $data['is_active'] = $data['is_active'] ?? 1;
            
            // 插入数据
            $sql = "INSERT INTO categories (
                name, slug, description, color, 
                icon_fontawesome, icon_fontawesome_color, 
                icon_color_upload, icon_color_url, icon_type,
                order_index, is_active
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->database->query($sql, [
                $data['name'],
                $data['slug'],
                $data['description'] ?? null,
                $data['color'],
                $data['icon_fontawesome'],
                $data['icon_fontawesome_color'],
                $data['icon_color_upload'],
                $data['icon_color_url'],
                $data['icon_type'],
                $data['order_index'],
                $data['is_active']
            ]);
            
            // 获取最后插入的ID
            return $this->database->getConnection()->lastInsertId();
        } catch (Exception $e) {
            throw new Exception("创建分类失败: " . $e->getMessage());
        }
    }
    
    /**
     * 更新分类
     * @param int $id 分类ID
     * @param array $data 更新数据
     * @return bool 是否更新成功
     */
    public function update($id, $data) {
        try {
            // 如果提供了名称，重新生成slug（如果未提供slug）
            if (!empty($data['name']) && empty($data['slug'])) {
                $data['slug'] = $this->generateSlug($data['name']);
            }
            
            // 构建更新SQL
            $fields = [];
            $params = [];
            
            foreach ($data as $key => $value) {
                // 只处理允许的字段
                $allowedFields = [
                    'name', 'slug', 'description', 'color', 
                    'icon_fontawesome', 'icon_fontawesome_color',
                    'icon_color_upload', 'icon_color_url', 'icon_type',
                    'order_index', 'is_active'
                ];
                
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
            $sql = "UPDATE categories SET " . implode(', ', $fields) . " WHERE id = ?";
            $stmt = $this->database->query($sql, $params);
            
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            throw new Exception("更新分类失败: " . $e->getMessage());
        }
    }
    
    /**
     * 删除分类
     * @param int $id 分类ID
     * @return bool 是否删除成功
     */
    public function delete($id) {
        try {
            // 检查是否有链接使用此分类
            $stmt = $this->database->query("SELECT COUNT(*) FROM links WHERE category_id = ?", [$id]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                throw new Exception("无法删除该分类，因为还有 {$count} 个链接使用此分类");
            }
            
            // 执行删除
            $stmt = $this->database->query("DELETE FROM categories WHERE id = ?", [$id]);
            
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            throw new Exception("删除分类失败: " . $e->getMessage());
        }
    }
    
    /**
     * 切换分类激活状态
     * @param int $id 分类ID
     * @return bool 是否切换成功
     */
    public function toggleActive($id) {
        try {
            $stmt = $this->database->query("UPDATE categories SET is_active = NOT is_active WHERE id = ?", [$id]);
            
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            throw new Exception("切换分类状态失败: " . $e->getMessage());
        }
    }
    
    /**
     * 更新分类排序
     * @param array $order 排序数组，格式为 [id => order_index, ...]
     * @return bool 是否更新成功
     */
    public function updateOrder($order) {
        try {
            $this->database->beginTransaction();
            
            foreach ($order as $id => $orderIndex) {
                $this->database->query("UPDATE categories SET order_index = ? WHERE id = ?", [$orderIndex, $id]);
            }
            
            $this->database->commit();
            return true;
        } catch (Exception $e) {
            $this->database->rollback();
            throw new Exception("更新分类排序失败: " . $e->getMessage());
        }
    }
    
    /**
     * 生成分类slug
     * @param string $name 分类名称
     * @return string 生成的slug
     */
    private function generateSlug($name) {
        // 转换为小写
        $slug = strtolower($name);
        
        // 替换空格和特殊字符为连字符
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        
        // 移除开头和结尾的连字符
        $slug = trim($slug, '-');
        
        // 确保slug唯一
        $originalSlug = $slug;
        $counter = 1;
        
        while ($this->slugExists($slug)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    /**
     * 检查slug是否已存在
     * @param string $slug 要检查的slug
     * @return bool 是否存在
     */
    private function slugExists($slug) {
        try {
            $stmt = $this->database->query("SELECT COUNT(*) FROM categories WHERE slug = ?", [$slug]);
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
}