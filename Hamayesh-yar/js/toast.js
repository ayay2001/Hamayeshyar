// =========================================================
// TOAST.JS - سیستم اعلان‌های توست
// =========================================================

// ایجاد کانتینر توست اگر وجود نداشته باشد
function getToastContainer() {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        container.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 400px;
            width: 100%;
        `;
        document.body.appendChild(container);
    }
    return container;
}

// نمایش توست
function showToast(message, type = 'info', duration = 3000) {
    const container = getToastContainer();

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    // آیکون بر اساس نوع
    const icons = {
        'success': '✅',
        'error': '❌',
        'warning': '⚠️',
        'info': 'ℹ️'
    };
    
    toast.style.cssText = `
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 14px 18px;
        box-shadow: var(--shadow-md);
        display: flex;
        align-items: center;
        gap: 12px;
        animation: slideInRight 0.3s ease;
        color: var(--text-primary);
        font-size: 14px;
        border-right: 4px solid var(--primary-500);
    `;

    // رنگ بورد بر اساس نوع
    const colors = {
        'success': '#22c55e',
        'error': '#ef4444',
        'warning': '#f59e0b',
        'info': '#3b82f6'
    };
    toast.style.borderRightColor = colors[type] || colors.info;

    toast.innerHTML = `
        <span>${icons[type] || 'ℹ️'}</span>
        <span>${message}</span>
        <button onclick="this.closest('.toast').remove()" style="
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 18px;
            margin-right: auto;
            padding: 0 4px;
        ">×</button>
    `;

    container.appendChild(toast);

    // حذف خودکار بعد از مدت زمان
    setTimeout(() => {
        toast.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

// اضافه کردن انیمیشن‌های CSS به صورت پویا
(function addToastStyles() {
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
})();

// =========================================================
// راه‌اندازی اولیه
// =========================================================
function initToast() {
    console.log('✅ سیستم اعلان (Toast) آماده است');
}