</div>

    <!-- Bootstrap JS -->
<script src="<?php echo ADMIN_URL; ?>/assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- 左侧导航JS -->
    <script>
    // 全局配置
    const ADMIN_URL = '<?php echo ADMIN_URL; ?>';
    
    // 移动端侧边栏切换
    function toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        
        if (window.innerWidth <= 768) {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        }
    }
    
    // 关闭侧边栏
    function closeSidebar() {
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
    }
    
    // 确认删除函数
    function confirmDelete(message = '确定要删除吗？此操作不可恢复！') {
        return confirm(message);
    }
    
    // 显示成功消息
    function showSuccess(message) {
        Swal.fire({
            icon: 'success',
            title: '成功',
            text: message,
            timer: 2000,
            showConfirmButton: false
        });
    }
    
    // 显示错误消息
    function showError(message) {
        Swal.fire({
            icon: 'error',
            title: '错误',
            text: message
        });
    }
    
    // 响应式处理
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            closeSidebar();
        }
    });
    
    // 点击遮罩关闭侧边栏
    document.getElementById('sidebarOverlay')?.addEventListener('click', closeSidebar);
    </script>
</body>
</html>