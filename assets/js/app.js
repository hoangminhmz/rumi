/**
 * RUMI - Main Application JavaScript
 * Common functionality và utilities
 */

// Global RUMI object
window.RUMI = window.RUMI || {};

/**
 * Show toast notification
 */
RUMI.showToast = function(message, type = 'success') {
    const toastContainer = document.querySelector('.toast-container') || createToastContainer();

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `<div>${message}</div>`;

    toastContainer.appendChild(toast);

    // Auto remove after 3 seconds
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
};

function createToastContainer() {
    const container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
    return container;
}

/**
 * Show loading spinner
 */
RUMI.showLoading = function(element) {
    if (typeof element === 'string') {
        element = document.querySelector(element);
    }

    if (element) {
        const spinner = document.createElement('div');
        spinner.className = 'spinner';
        spinner.setAttribute('data-loading', 'true');
        element.appendChild(spinner);
    }
};

/**
 * Hide loading spinner
 */
RUMI.hideLoading = function(element) {
    if (typeof element === 'string') {
        element = document.querySelector(element);
    }

    if (element) {
        const spinner = element.querySelector('[data-loading="true"]');
        if (spinner) {
            spinner.remove();
        }
    }
};

/**
 * Make API request
 */
RUMI.api = async function(endpoint, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json'
        }
    };

    if (data && method !== 'GET') {
        options.body = JSON.stringify(data);
    }

    try {
        const response = await fetch(endpoint, options);
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
};

/**
 * Format price to VND
 */
RUMI.formatPrice = function(price) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(price);
};

/**
 * Time ago helper
 */
RUMI.timeAgo = function(datetime) {
    const timestamp = new Date(datetime).getTime();
    const now = Date.now();
    const diff = Math.floor((now - timestamp) / 1000);

    if (diff < 60) return 'Vừa xong';
    if (diff < 3600) return Math.floor(diff / 60) + ' phút trước';
    if (diff < 86400) return Math.floor(diff / 3600) + ' giờ trước';
    if (diff < 604800) return Math.floor(diff / 86400) + ' ngày trước';
    if (diff < 2592000) return Math.floor(diff / 604800) + ' tuần trước';

    return new Date(timestamp).toLocaleDateString('vi-VN');
};

/**
 * Debounce function
 */
RUMI.debounce = function(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
};

/**
 * Initialize app
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('🏠 RUMI App Initialized');

    // Auto-hide flash messages
    const flashMessages = document.querySelectorAll('.toast');
    flashMessages.forEach(toast => {
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => {
                const container = toast.parentElement;
                toast.remove();
                if (container && container.children.length === 0) {
                    container.remove();
                }
            }, 300);
        }, 3000);
    });

    // Active nav link highlighting
    const currentPage = window.location.pathname.split('/').pop().replace('.php', '');
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href && href.includes(currentPage)) {
            link.classList.add('active');
        }
    });
});

// Export to global scope
window.RUMI = RUMI;
