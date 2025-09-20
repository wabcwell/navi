-- 重建 operation_logs 表
-- 使用前请备份现有数据

-- 禁用外键检查
SET FOREIGN_KEY_CHECKS = 0;

-- 删除现有 operation_logs 表（如果存在）
DROP TABLE IF EXISTS operation_logs;

-- 重新启用外键检查
SET FOREIGN_KEY_CHECKS = 1;

-- 创建 operation_logs 表
CREATE TABLE operation_logs (
    id INT NOT NULL AUTO_INCREMENT,
    userid INT NOT NULL,
    operation_module ENUM('分类', '链接', '用户', '文件') NOT NULL,
    operation_type ENUM('新增', '删除', '编辑') NOT NULL,
    categorie_id INT DEFAULT NULL,
    categorie_name VARCHAR(255) DEFAULT NULL,
    link_id INT DEFAULT NULL,
    link_name VARCHAR(255) DEFAULT NULL,
    files TEXT DEFAULT NULL,
    operated_id INT DEFAULT NULL,
    operated_name VARCHAR(255) DEFAULT NULL,
    operation_time TIMESTAMP(3) DEFAULT CURRENT_TIMESTAMP(3),
    ip_address VARCHAR(45) DEFAULT NULL,
    operation_details JSON DEFAULT NULL,
    status ENUM('成功', '失败') DEFAULT '成功',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`) USING BTREE,
    INDEX `idx_userid`(`userid` ASC) USING BTREE,
    INDEX `idx_operation_module`(`operation_module` ASC) USING BTREE,
    INDEX `idx_operation_type`(`operation_type` ASC) USING BTREE,
    INDEX `idx_operation_time`(`operation_time` ASC) USING BTREE,
    INDEX `idx_categorie_id`(`categorie_id` ASC) USING BTREE,
    INDEX `idx_link_id`(`link_id` ASC) USING BTREE,
    INDEX `idx_operated_id`(`operated_id` ASC) USING BTREE,
    CONSTRAINT `operation_logs_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 添加表注释
ALTER TABLE operation_logs COMMENT = '操作日志表 - 记录用户操作历史';