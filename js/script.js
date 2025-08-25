// 现代化紧凑设计的搜索功能
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    let linkCards = document.querySelectorAll('.link-card');
    let categorySections = document.querySelectorAll('.category-section');

    // 搜索功能 - 优化版
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        
        // 重新获取元素（防止动态加载）
        linkCards = document.querySelectorAll('.link-card');
        categorySections = document.querySelectorAll('.category-section');
        
        let hasResults = false;
        
        linkCards.forEach(card => {
            const title = card.querySelector('.link-content h3').textContent.toLowerCase();
            const desc = card.querySelector('.link-content p').textContent.toLowerCase();
            
            if (searchTerm === '' || title.includes(searchTerm) || desc.includes(searchTerm)) {
                card.style.display = 'flex';
                card.style.order = searchTerm && (title.includes(searchTerm) ? -1 : 0);
                if (searchTerm !== '') hasResults = true;
            } else {
                card.style.display = 'none';
            }
        });

        // 隐藏没有匹配链接的分类
        categorySections.forEach(section => {
            const visibleCards = section.querySelectorAll('.link-card:not([style*="display: none"])');
            const categoryName = section.querySelector('.category-header').textContent.toLowerCase();
            
            if (visibleCards.length === 0 && searchTerm !== '' && !categoryName.includes(searchTerm)) {
                section.style.display = 'none';
            } else {
                section.style.display = 'block';
                if (searchTerm !== '' && (visibleCards.length > 0 || categoryName.includes(searchTerm))) {
                    hasResults = true;
                }
            }
        });

        // 高亮搜索词
        if (searchTerm) {
            highlightSearchTerm(searchTerm);
        } else {
            removeHighlight();
        }
    });

    // 搜索词高亮
    function highlightSearchTerm(term) {
        linkCards.forEach(card => {
            const titleEl = card.querySelector('.link-content h3');
            const descEl = card.querySelector('.link-content p');
            
            [titleEl, descEl].forEach(el => {
                if (el && el.textContent.toLowerCase().includes(term)) {
                    const text = el.textContent;
                    const regex = new RegExp(`(${term})`, 'gi');
                    el.innerHTML = text.replace(regex, '<mark class="search-highlight">$1</mark>');
                }
            });
        });
    }

    function removeHighlight() {
        document.querySelectorAll('.search-highlight').forEach(mark => {
            mark.outerHTML = mark.textContent;
        });
    }

    // ESC键清空搜索
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('input'));
            searchInput.blur();
        }
    });

    // 键盘导航
    let currentFocus = -1;
    searchInput.addEventListener('keydown', function(e) {
        const visibleCards = Array.from(document.querySelectorAll('.link-card:not([style*="display: none"])'));
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            currentFocus = Math.min(currentFocus + 1, visibleCards.length - 1);
            focusCard(visibleCards[currentFocus]);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            currentFocus = Math.max(currentFocus - 1, -1);
            if (currentFocus >= 0) {
                focusCard(visibleCards[currentFocus]);
            } else {
                searchInput.focus();
            }
        } else if (e.key === 'Enter' && currentFocus >= 0) {
            e.preventDefault();
            visibleCards[currentFocus]?.click();
        }
    });

    function focusCard(card) {
        if (card) {
            card.focus();
            card.style.outline = '2px solid var(--accent-color)';
            card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    // 点击其他地方清除焦点
    document.addEventListener('click', function() {
        document.querySelectorAll('.link-card').forEach(card => {
            card.style.outline = '';
        });
        currentFocus = -1;
    });
});

// 添加一些实用的快捷键
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + K 聚焦搜索框
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        document.getElementById('searchInput').focus();
    }
    
    // Ctrl/Cmd + / 显示帮助
    if ((e.ctrlKey || e.metaKey) && e.key === '/') {
        e.preventDefault();
        showHelp();
    }
});

// 显示帮助信息
function showHelp() {
    const helpText = `
快捷键：
• Ctrl+K 或 Cmd+K：聚焦搜索框
• ESC：清空搜索
• Ctrl+/：显示此帮助

使用提示：
• 输入关键词快速筛选网站
• 点击任意卡片直接访问
• 支持模糊搜索
    `;
    alert(helpText);
}

// 添加页面性能监控
window.addEventListener('load', function() {
    if ('performance' in window) {
        const loadTime = performance.timing.loadEventEnd - performance.timing.navigationStart;
        console.log(`页面加载时间: ${loadTime}ms`);
    }
});

// 添加在线状态检测
window.addEventListener('online', function() {
    console.log('网络已连接');
});

window.addEventListener('offline', function() {
    console.log('网络已断开');
    alert('网络连接已断开，部分网站可能无法访问');
});