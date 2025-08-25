// 管理后台JavaScript功能

// 初始化
document.addEventListener('DOMContentLoaded', function() {
    // 初始化工具提示
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // 初始化弹出框
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // 自动隐藏提示消息
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert-dismissible');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});

// 图片预览功能
function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var preview = document.getElementById(previewId);
            if (preview) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// 拖放上传功能
function setupDragDropUpload(uploadAreaId, inputId) {
    var uploadArea = document.getElementById(uploadAreaId);
    var fileInput = document.getElementById(inputId);
    
    if (!uploadArea || !fileInput) return;
    
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });
    
    uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
    });
    
    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        
        var files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            var event = new Event('change', { bubbles: true });
            fileInput.dispatchEvent(event);
        }
    });
    
    uploadArea.addEventListener('click', function() {
        fileInput.click();
    });
}

// AJAX表单提交
function submitForm(form, successCallback, errorCallback) {
    var formData = new FormData(form);
    
    fetch(form.action, {
        method: form.method,
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (successCallback) successCallback(data);
            else showSuccess(data.message || '操作成功');
        } else {
            if (errorCallback) errorCallback(data);
            else showError(data.message || '操作失败');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('网络错误，请稍后重试');
    });
}

// 批量操作
function handleBatchAction(formSelector, actionUrl, confirmMessage) {
    var form = document.querySelector(formSelector);
    var checkboxes = form.querySelectorAll('input[type="checkbox"]:checked');
    
    if (checkboxes.length === 0) {
        showError('请选择要操作的项');
        return;
    }
    
    if (!confirm(confirmMessage)) return;
    
    var formData = new FormData();
    checkboxes.forEach(function(checkbox) {
        formData.append('ids[]', checkbox.value);
    });
    
    fetch(actionUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess(data.message || '操作成功');
            setTimeout(function() {
                location.reload();
            }, 1500);
        } else {
            showError(data.message || '操作失败');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('网络错误，请稍后重试');
    });
}

// 排序功能
function sortTable(tableId, columnIndex, ascending = true) {
    var table = document.getElementById(tableId);
    var tbody = table.querySelector('tbody');
    var rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort(function(a, b) {
        var aValue = a.cells[columnIndex].textContent.trim();
        var bValue = b.cells[columnIndex].textContent.trim();
        
        // 数字排序
        if (!isNaN(aValue) && !isNaN(bValue)) {
            return ascending ? aValue - bValue : bValue - aValue;
        }
        
        // 文本排序
        return ascending ? aValue.localeCompare(bValue) : bValue.localeCompare(aValue);
    });
    
    rows.forEach(function(row) {
        tbody.appendChild(row);
    });
}

// 搜索功能
function searchTable(inputId, tableId) {
    var input = document.getElementById(inputId);
    var table = document.getElementById(tableId);
    var filter = input.value.toLowerCase();
    var rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(function(row) {
        var text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
}

// 全选/取消全选
function toggleCheckboxes(checkbox, name) {
    var checkboxes = document.querySelectorAll('input[name="' + name + '[]"]');
    checkboxes.forEach(function(cb) {
        cb.checked = checkbox.checked;
    });
}

// 实时预览
function livePreview(inputId, previewId) {
    var input = document.getElementById(inputId);
    var preview = document.getElementById(previewId);
    
    if (!input || !preview) return;
    
    input.addEventListener('input', function() {
        preview.textContent = this.value;
    });
}

// 字符计数器
function setupCharCounter(inputId, counterId, maxLength) {
    var input = document.getElementById(inputId);
    var counter = document.getElementById(counterId);
    
    if (!input || !counter) return;
    
    function updateCounter() {
        var length = input.value.length;
        counter.textContent = length + '/' + maxLength;
        
        if (length > maxLength) {
            counter.classList.add('text-danger');
        } else {
            counter.classList.remove('text-danger');
        }
    }
    
    input.addEventListener('input', updateCounter);
    updateCounter();
}

// 颜色选择器
function setupColorPicker(inputId, previewId) {
    var input = document.getElementById(inputId);
    var preview = document.getElementById(previewId);
    
    if (!input || !preview) return;
    
    input.addEventListener('input', function() {
        preview.style.backgroundColor = this.value;
    });
}

// 加载状态
function showLoading(elementId) {
    var element = document.getElementById(elementId);
    if (element) {
        element.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">加载中...</span></div></div>';
    }
}

// 平滑滚动
function smoothScroll(target) {
    document.querySelector(target).scrollIntoView({
        behavior: 'smooth'
    });
}

// 确认对话框
function confirmAction(message, callback) {
    Swal.fire({
        title: '确认操作',
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: '确认',
        cancelButtonText: '取消'
    }).then((result) => {
        if (result.isConfirmed && callback) {
            callback();
        }
    });
}