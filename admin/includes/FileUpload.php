<?php
/**
 * FileUpload 类
 * 专门处理文件上传操作
 */

class FileUpload {
    private $uploadDir;
    private $allowedTypes;
    private $maxFileSize;
    private $uploadType;
    
    // 定义不同用途的上传目录
    private const UPLOAD_DIRS = [
        'categories' => 'categories',
        'links' => 'links',
        'backgrounds' => 'backgrounds',
        'settings' => 'settings'
    ];
    
    public function __construct($uploadType = 'general', $allowedTypes = [], $maxFileSize = 5242880) {
        $this->uploadType = $uploadType;
        $this->uploadDir = $this->getUploadDirByType($uploadType);
        $this->allowedTypes = $allowedTypes ?: ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $this->maxFileSize = $maxFileSize; // 默认5MB
    }
    
    /**
     * 根据上传类型获取上传目录
     * @param string $type 上传类型
     * @return string 上传目录路径
     */
    private function getUploadDirByType($type) {
        $baseDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
        
        // 如果是预定义的类型，使用对应的子目录
        if (isset(self::UPLOAD_DIRS[$type])) {
            return $baseDir . self::UPLOAD_DIRS[$type] . '/';
        }
        
        // 默认使用基础上传目录
        return $baseDir;
    }
    
    /**
     * 处理文件上传
     * @param array $file 上传的文件数组 ($_FILES['file'])
     * @param string $subDir 子目录名称（可选）
     * @return array 上传结果
     */
    public function upload($file, $subDir = '') {
        try {
            // 检查是否有上传错误
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception($this->getUploadErrorMessage($file['error']));
            }
            
            // 检查文件大小
            if ($file['size'] > $this->maxFileSize) {
                throw new Exception('文件大小超过限制');
            }
            
            // 检查文件类型
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($fileExtension, $this->allowedTypes)) {
                throw new Exception('不支持的文件类型');
            }
            
            // 创建上传目录
            $uploadPath = $this->uploadDir . ($subDir ? $subDir . '/' : '');
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            
            // 生成唯一文件名
            $fileName = uniqid() . '_' . time() . '.' . $fileExtension;
            $fullPath = $uploadPath . $fileName;
            
            // 移动上传文件
            if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
                throw new Exception('文件上传失败');
            }
            
            return [
                'success' => true,
                'file_name' => $fileName,
                'file_path' => $fullPath,
                'file_url' => '/uploads/' . ($subDir ? $subDir . '/' : '') . $fileName,
                'file_size' => $file['size'],
                'file_type' => $fileExtension
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 批量上传文件
     * @param array $files 上传的文件数组
     * @param string $subDir 子目录名称（可选）
     * @return array 上传结果数组
     */
    public function uploadMultiple($files, $subDir = '') {
        $results = [];
        
        // 重新组织文件数组
        $fileArray = $this->rearrayFiles($files);
        
        foreach ($fileArray as $file) {
            $results[] = $this->upload($file, $subDir);
        }
        
        return $results;
    }
    
    /**
     * 删除文件
     * @param string $filePath 文件路径
     * @return bool 是否删除成功
     */
    public function delete($filePath) {
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        return false;
    }
    
    /**
     * 重新组织多文件上传数组
     * @param array $filePost $_FILES数组
     * @return array 重新组织后的数组
     */
    private function rearrayFiles(&$filePost) {
        $fileArray = [];
        $fileCount = count($filePost['name']);
        $fileKeys = array_keys($filePost);
        
        for ($i = 0; $i < $fileCount; $i++) {
            foreach ($fileKeys as $key) {
                $fileArray[$i][$key] = $filePost[$key][$i];
            }
        }
        
        return $fileArray;
    }
    
    /**
     * 获取上传错误信息
     * @param int $errorCode 错误代码
     * @return string 错误信息
     */
    private function getUploadErrorMessage($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return '文件大小超过服务器限制';
            case UPLOAD_ERR_FORM_SIZE:
                return '文件大小超过表单限制';
            case UPLOAD_ERR_PARTIAL:
                return '文件只有部分被上传';
            case UPLOAD_ERR_NO_FILE:
                return '没有文件被上传';
            case UPLOAD_ERR_NO_TMP_DIR:
                return '找不到临时文件夹';
            case UPLOAD_ERR_CANT_WRITE:
                return '文件写入失败';
            case UPLOAD_ERR_EXTENSION:
                return '文件上传被扩展阻止';
            default:
                return '未知上传错误';
        }
    }
    
    /**
     * 设置允许的文件类型
     * @param array $types 允许的文件类型数组
     */
    public function setAllowedTypes($types) {
        $this->allowedTypes = $types;
    }
    
    /**
     * 设置最大文件大小
     * @param int $size 最大文件大小（字节）
     */
    public function setMaxFileSize($size) {
        $this->maxFileSize = $size;
    }
    
    /**
     * 设置上传目录
     * @param string $dir 上传目录
     */
    public function setUploadDir($dir) {
        $this->uploadDir = $dir;
    }
}