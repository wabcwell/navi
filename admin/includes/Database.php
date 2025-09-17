<?php
/**
 * 数据库类
 * 专门处理数据库连接和基本操作
 */

class Database {
    private $host;
    private $port;
    private $dbname;
    private $username;
    private $password;
    private $charset;
    private $pdo;
    
    /**
     * 构造函数
     * 
     * @param string $host 数据库主机
     * @param string $port 数据库端口
     * @param string $dbname 数据库名称
     * @param string $username 数据库用户名
     * @param string $password 数据库密码
     * @param string $charset 数据库字符集
     */
    public function __construct($host = DB_HOST, $port = DB_PORT, $dbname = DB_NAME, $username = DB_USER, $password = DB_PASS, $charset = DB_CHARSET) {
        $this->host = $host;
        $this->port = $port;
        $this->dbname = $dbname;
        $this->username = $username;
        $this->password = $password;
        $this->charset = $charset;
    }
    
    /**
     * 获取数据库连接
     * 
     * @return PDO 数据库连接对象
     */
    public function getConnection() {
        if ($this->pdo === null) {
            try {
                $dsn = 'mysql:host=' . $this->host . ';port=' . $this->port . ';dbname=' . $this->dbname . ';charset=' . $this->charset;
                $this->pdo = new PDO(
                    $dsn,
                    $this->username,
                    $this->password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
            } catch (PDOException $e) {
                throw new Exception('数据库连接失败: ' . $e->getMessage());
            }
        }
        return $this->pdo;
    }
    
    /**
     * 执行查询语句
     * 
     * @param string $sql SQL查询语句
     * @param array $params 参数数组
     * @return PDOStatement 查询结果
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception('查询执行失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 执行插入语句
     * 
     * @param string $table 表名
     * @param array $data 插入的数据
     * @return int 插入记录的ID
     */
    public function insert($table, $data) {
        try {
            $columns = implode(',', array_keys($data));
            $placeholders = ':' . implode(', :', array_keys($data));
            $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
            
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($data);
            
            return $this->getConnection()->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception('插入数据失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 执行更新语句
     * 
     * @param string $table 表名
     * @param array $data 更新的数据
     * @param string $where WHERE条件
     * @param array $params WHERE条件参数
     * @return int 受影响的行数
     */
    public function update($table, $data, $where = '1=0', $params = []) {
        try {
            $set = [];
            foreach (array_keys($data) as $column) {
                $set[] = "{$column} = ?";
            }
            $setClause = implode(', ', $set);
            
            // 准备参数数组
            $updateParams = array_values($data);
            
            $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
            $stmt = $this->getConnection()->prepare($sql);
            
            // 合并更新参数和WHERE参数
            $allParams = array_merge($updateParams, $params);
            $stmt->execute($allParams);
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new Exception('更新数据失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 执行删除语句
     * 
     * @param string $table 表名
     * @param string $where WHERE条件
     * @param array $params WHERE条件参数
     * @return int 受影响的行数
     */
    public function delete($table, $where = '1=0', $params = []) {
        try {
            $sql = "DELETE FROM {$table} WHERE {$where}";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new Exception('删除数据失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 开始事务
     * 
     * @return void
     */
    public function beginTransaction() {
        $this->getConnection()->beginTransaction();
    }
    
    /**
     * 提交事务
     * 
     * @return void
     */
    public function commit() {
        $this->getConnection()->commit();
    }
    
    /**
     * 回滚事务
     * 
     * @return void
     */
    public function rollback() {
        $this->getConnection()->rollback();
    }
    
    /**
     * 检查是否在事务中
     * 
     * @return bool
     */
    public function inTransaction() {
        return $this->getConnection()->inTransaction();
    }
}