<?php
/**
 * 管理后台公共函数库 - 核心安全函数
 * 
 * 本文件仅包含项目中最基础的安全相关函数，避免功能重复
 * 其他功能请使用相应的管理类（如Category、NavigationLink等）
 */

/**
 * 安全输出HTML - 防止XSS攻击
 * @param string $string 要输出的字符串
 * @return string 转义后的HTML安全字符串
 */
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * 安全输出URL - 对URL参数进行编码
 * @param string $string 要编码的URL参数
 * @return string URL编码后的字符串
 */
function u($string) {
    return urlencode($string);
}

/**
 * 生成URL友好的别名
 * @param string $text 输入文本
 * @return string URL友好的别名（大写格式）
 */
function generate_slug($text) {
    // 替换非字母数字字符为连字符
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    // 转换为大写
    $text = mb_strtoupper($text, 'UTF-8');
    // 去除首尾连字符
    $text = trim($text, '-');
    
    return $text;
}