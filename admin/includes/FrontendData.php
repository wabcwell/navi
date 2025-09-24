<?php
/**
 * FrontendData 类
 * 专门处理前台页面需要的数据获取
 * 提供统一的接口获取分类、链接等前台展示数据
 */

class FrontendData {
    private $categoryManager;
    private $navigationLinkManager;
    private $settingsManager;
    
    public function __construct() {
        $this->categoryManager = get_category_manager();
        $this->navigationLinkManager = get_navigation_link_manager();
        $this->settingsManager = get_settings_manager();
    }
    
    /**
     * 获取前台首页需要的数据
     * 包括分类、链接、设置等
     * 
     * @return array 包含所有前台数据的数组
     */
    public function getHomePageData() {
        try {
            // 获取所有启用的分类
            $categories = $this->categoryManager->getAll(true);
            
            // 获取所有启用的链接
            $links = $this->navigationLinkManager->getAllLinks(true);
            
            // 按分类分组链接
            $linksByCategory = [];
            foreach ($links as $link) {
                if (!isset($linksByCategory[$link['category_id']])) {
                    $linksByCategory[$link['category_id']] = [];
                }
                $linksByCategory[$link['category_id']][] = $link;
            }
            
            // 获取每个分类的链接数量
            foreach ($categories as &$category) {
                $category['link_count'] = count($linksByCategory[$category['id']] ?? []);
            }
            unset($category);
            
            // 获取网站设置
            $settings = $this->getSiteSettings();
            
            return [
                'categories' => $categories,
                'linksByCategory' => $linksByCategory,
                'settings' => $settings,
                'total_categories' => count($categories),
                'total_links' => count($links)
            ];
            
        } catch (Exception $e) {
            // 记录错误日志
            error_log("获取前台数据失败: " . $e->getMessage());
            
            // 返回空数据，避免前台显示错误
            return [
                'categories' => [],
                'linksByCategory' => [],
                'settings' => $this->getDefaultSettings(),
                'total_categories' => 0,
                'total_links' => 0
            ];
        }
    }
    
    /**
     * 获取网站设置
     * 
     * @return array 网站设置数组
     */
    private function getSiteSettings() {
        return [
            'site_name' => $this->settingsManager->get('site_name', '导航'),
            'site_description' => $this->settingsManager->get('site_description', '我的导航网站'),
            'site_logo_type' => $this->settingsManager->get('site_logo_type', 'image'),
            'site_logo_color' => $this->settingsManager->get('site_logo_color', '#007bff'),
            'site_logo_image' => $this->settingsManager->get('site_logo_image', ''),
            'site_logo_iconfont' => $this->settingsManager->get('site_logo_iconfont', ''),
            'site_logo_icon' => $this->settingsManager->get('site_logo_icon', 'fas fa-home'),
            'site_icon' => $this->settingsManager->get('site_icon', ''),
            'background_type' => $this->settingsManager->get('background_type', 'color'),
            'background_color' => $this->settingsManager->get('background_color', '#f8fafc'),
            'background_image' => $this->settingsManager->get('background_image', ''),
            'background_api' => $this->settingsManager->get('background_api', 'https://picsum.photos/1920/1080'),
            'background_opacity' => $this->settingsManager->get('background_opacity', '1'),
            'header_bg_transparency' => $this->settingsManager->get('header_bg_transparency', '0.85'),
            'category_bg_transparency' => $this->settingsManager->get('category_bg_transparency', '0.85'),
            'links_area_transparency' => $this->settingsManager->get('links_area_transparency', '0.85'),
            'link_card_transparency' => $this->settingsManager->get('link_card_transparency', '0.85'),
            'footer_bg_transparency' => $this->settingsManager->get('footer_bg_transparency', '0.85'),
            'bg_overlay' => $this->settingsManager->get('bg_overlay', '0.2'),
            'show_footer' => $this->settingsManager->get('show_footer', '1') == '1',
            'footer_content' => $this->settingsManager->get('footer_content', '&copy; 2024 导航网站. All rights reserved.'),
            'iconfont' => $this->settingsManager->get('iconfont', '')
        ];
    }
    
    /**
     * 获取默认设置（用于错误情况）
     * 
     * @return array 默认设置数组
     */
    private function getDefaultSettings() {
        return [
            'site_name' => '导航',
            'site_description' => '我的导航网站',
            'site_logo_type' => 'image',
            'site_logo_color' => '#007bff',
            'site_logo_image' => '',
            'site_logo_iconfont' => '',
            'site_logo_icon' => 'fas fa-home',
            'site_icon' => '',
            'background_type' => 'color',
            'background_color' => '#f8fafc',
            'background_image' => '',
            'background_api' => 'https://picsum.photos/1920/1080',
            'background_opacity' => '1',
            'header_bg_transparency' => '0.85',
            'category_bg_transparency' => '0.85',
            'links_area_transparency' => '0.85',
            'link_card_transparency' => '0.85',
            'footer_bg_transparency' => '0.85',
            'bg_overlay' => '0.2',
            'show_footer' => true,
            'footer_content' => '&copy; 2024 导航网站. All rights reserved.',
            'iconfont' => ''
        ];
    }
    
    /**
     * 根据搜索关键词获取链接
     * 
     * @param string $keyword 搜索关键词
     * @return array 匹配的链接数组
     */
    public function searchLinks($keyword) {
        try {
            // 使用 NavigationLink 类的方法来搜索链接
            return $this->navigationLinkManager->searchLinks($keyword);
        } catch (Exception $e) {
            error_log("搜索链接失败: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 增加链接点击次数
     * 
     * @param int $linkId 链接ID
     * @return bool 是否成功
     */
    public function incrementLinkClick($linkId) {
        try {
            return $this->navigationLinkManager->incrementClickCount($linkId);
        } catch (Exception $e) {
            error_log("增加链接点击次数失败: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * 获取前台数据管理实例
 * 
 * @return FrontendData 前台数据管理实例
 */
function get_frontend_data_manager() {
    static $frontendData = null;
    if ($frontendData === null) {
        $frontendData = new FrontendData();
    }
    return $frontendData;
}
?>