// Dashboard JavaScript for CheatStore

document.addEventListener('DOMContentLoaded', function() {
    // Dashboard tabs
    const tabButtons = document.querySelectorAll('.dashboard-tab');
    const tabContents = document.querySelectorAll('.dashboard-content');
    
    if (tabButtons.length > 0 && tabContents.length > 0) {
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                const targetTab = this.getAttribute('data-tab');
                
                // Remove active class from all tabs and contents
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));
                
                // Add active class to current tab and content
                this.classList.add('active');
                const activeContent = document.querySelector(`.dashboard-content[data-tab="${targetTab}"]`);
                if (activeContent) {
                    activeContent.classList.add('active');
                }
            });
        });
    }
    
    // License key management
    const licenseKeys = document.querySelectorAll('.license-key-item');
    
    licenseKeys.forEach(keyItem => {
        const copyBtn = keyItem.querySelector('.copy-key');
        const activateBtn = keyItem.querySelector('.activate-key');
        const deactivateBtn = keyItem.querySelector('.deactivate-key');
        
        // Copy license key
        if (copyBtn) {
            copyBtn.addEventListener('click', function() {
                const keyElement = keyItem.querySelector('.key-value');
                const key = keyElement.textContent;
                
                navigator.clipboard.writeText(key).then(() => {
                    showNotification('License key copied to clipboard!', 'success');
                }).catch(() => {
                    // Fallback for older browsers
                    const textArea = document.createElement('textarea');
                    textArea.value = key;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                    showNotification('License key copied to clipboard!', 'success');
                });
            });
        }
        
        // Activate license key
        if (activateBtn) {
            activateBtn.addEventListener('click', function() {
                const keyId = keyItem.getAttribute('data-key-id');
                activateLicenseKey(keyId, keyItem);
            });
        }
        
        // Deactivate license key
        if (deactivateBtn) {
            deactivateBtn.addEventListener('click', function() {
                const keyId = keyItem.getAttribute('data-key-id');
                deactivateLicenseKey(keyId, keyItem);
            });
        }
    });
    
    // Order management
    const orderItems = document.querySelectorAll('.order-item');
    
    orderItems.forEach(orderItem => {
        const cancelBtn = orderItem.querySelector('.cancel-order');
        const downloadBtn = orderItem.querySelector('.download-software');
        
        // Cancel order
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function() {
                const orderId = orderItem.getAttribute('data-order-id');
                cancelOrder(orderId, orderItem);
            });
        }
        
        // Download software
        if (downloadBtn) {
            downloadBtn.addEventListener('click', function() {
                const productId = orderItem.getAttribute('data-product-id');
                downloadSoftware(productId);
            });
        }
    });
    
    // Profile form validation
    const profileForm = document.querySelector('.profile-form');
    
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            const email = this.querySelector('input[name="email"]').value;
            const currentPassword = this.querySelector('input[name="current_password"]').value;
            const newPassword = this.querySelector('input[name="new_password"]').value;
            const confirmPassword = this.querySelector('input[name="confirm_password"]').value;
            
            let isValid = true;
            
            // Clear previous errors
            clearFormErrors(this);
            
            // Validate email
            if (!isValidEmail(email)) {
                showFieldError(this.querySelector('input[name="email"]'), 'Please enter a valid email address');
                isValid = false;
            }
            
            // Validate password if changing
            if (newPassword) {
                if (newPassword.length < 8) {
                    showFieldError(this.querySelector('input[name="new_password"]'), 'Password must be at least 8 characters long');
                    isValid = false;
                }
                
                if (newPassword !== confirmPassword) {
                    showFieldError(this.querySelector('input[name="confirm_password"]'), 'Passwords do not match');
                    isValid = false;
                }
                
                if (!currentPassword) {
                    showFieldError(this.querySelector('input[name="current_password"]'), 'Current password is required to change password');
                    isValid = false;
                }
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    }
    
    // Statistics charts (if Chart.js is available)
    if (typeof Chart !== 'undefined') {
        initializeCharts();
    }
    
    // Search functionality
    const searchInput = document.querySelector('.dashboard-search');
    
    if (searchInput) {
        searchInput.addEventListener('input', debounce(function() {
            const searchTerm = this.value.toLowerCase();
            const searchableItems = document.querySelectorAll('.searchable-item');
            
            searchableItems.forEach(item => {
                const text = item.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        }, 300));
    }
    
    // Filter functionality
    const filterButtons = document.querySelectorAll('.filter-btn');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const filterType = this.getAttribute('data-filter');
            const filterableItems = document.querySelectorAll('.filterable-item');
            
            // Remove active class from all filter buttons
            filterButtons.forEach(btn => btn.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Apply filter
            filterableItems.forEach(item => {
                if (filterType === 'all' || item.getAttribute('data-type') === filterType) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
});

// Utility functions
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        </div>
        <button class="notification-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function showFieldError(field, message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.textContent = message;
    field.parentNode.appendChild(errorDiv);
    field.classList.add('error');
}

function clearFormErrors(form) {
    form.querySelectorAll('.field-error').forEach(error => error.remove());
    form.querySelectorAll('.error').forEach(field => field.classList.remove('error'));
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// API functions
async function activateLicenseKey(keyId, keyItem) {
    try {
        const response = await fetch('api/activate-license.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ key_id: keyId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('License key activated successfully!', 'success');
            keyItem.classList.add('activated');
            // Update UI to show activated state
        } else {
            showNotification(data.message || 'Failed to activate license key', 'error');
        }
    } catch (error) {
        showNotification('An error occurred while activating the license key', 'error');
    }
}

async function deactivateLicenseKey(keyId, keyItem) {
    try {
        const response = await fetch('api/deactivate-license.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ key_id: keyId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('License key deactivated successfully!', 'success');
            keyItem.classList.remove('activated');
            // Update UI to show deactivated state
        } else {
            showNotification(data.message || 'Failed to deactivate license key', 'error');
        }
    } catch (error) {
        showNotification('An error occurred while deactivating the license key', 'error');
    }
}

async function cancelOrder(orderId, orderItem) {
    if (!confirm('Are you sure you want to cancel this order?')) {
        return;
    }
    
    try {
        const response = await fetch('api/cancel-order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ order_id: orderId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Order cancelled successfully!', 'success');
            orderItem.classList.add('cancelled');
            // Update UI to show cancelled state
        } else {
            showNotification(data.message || 'Failed to cancel order', 'error');
        }
    } catch (error) {
        showNotification('An error occurred while cancelling the order', 'error');
    }
}

function downloadSoftware(productId) {
    // Redirect to download page or trigger download
    window.location.href = `download.php?product_id=${productId}`;
}

function initializeCharts() {
    // Usage statistics chart
    const usageCtx = document.getElementById('usage-chart');
    if (usageCtx) {
        new Chart(usageCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Active Licenses',
                    data: [12, 19, 3, 5, 2, 3],
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    // Revenue chart
    const revenueCtx = document.getElementById('revenue-chart');
    if (revenueCtx) {
        new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Revenue',
                    data: [65, 59, 80, 81, 56, 55],
                    backgroundColor: '#28a745'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
}

// CSS for dashboard
const dashboardStyles = `
<style>
/* Dashboard tabs */
.dashboard-tabs {
    display: flex;
    border-bottom: 1px solid #ddd;
    margin-bottom: 2rem;
}

.dashboard-tab {
    padding: 15px 25px;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: all 0.3s;
    background: none;
    border: none;
    font-size: 16px;
}

.dashboard-tab:hover {
    border-bottom-color: #007bff;
    color: #007bff;
}

.dashboard-tab.active {
    border-bottom-color: #007bff;
    color: #007bff;
    font-weight: 600;
}

.dashboard-content {
    display: none;
}

.dashboard-content.active {
    display: block;
}

/* License key items */
.license-key-item {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    transition: all 0.3s;
}

.license-key-item:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.license-key-item.activated {
    border-color: #28a745;
    background: #f8fff9;
}

.license-key-item.expired {
    border-color: #dc3545;
    background: #fff8f8;
}

.key-value {
    font-family: monospace;
    background: #f8f9fa;
    padding: 8px 12px;
    border-radius: 4px;
    margin: 10px 0;
    word-break: break-all;
}

/* Order items */
.order-item {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    transition: all 0.3s;
}

.order-item:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.order-item.cancelled {
    opacity: 0.6;
    background: #f8f9fa;
}

.order-status {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.order-status.pending {
    background: #fff3cd;
    color: #856404;
}

.order-status.paid {
    background: #d4edda;
    color: #155724;
}

.order-status.completed {
    background: #d1ecf1;
    color: #0c5460;
}

.order-status.cancelled {
    background: #f8d7da;
    color: #721c24;
}

/* Form validation */
.field-error {
    color: #dc3545;
    font-size: 12px;
    margin-top: 5px;
}

input.error {
    border-color: #dc3545;
}

/* Search and filter */
.dashboard-search {
    width: 100%;
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 20px;
}

.filter-buttons {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.filter-btn {
    padding: 8px 16px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.3s;
}

.filter-btn:hover,
.filter-btn.active {
    background: #007bff;
    color: white;
    border-color: #007bff;
}

/* Statistics cards */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
}

.stat-value {
    font-size: 2rem;
    font-weight: bold;
    color: #007bff;
    margin-bottom: 5px;
}

.stat-label {
    color: #666;
    font-size: 14px;
}

/* Charts */
.chart-container {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 2rem;
}

.chart-title {
    margin-bottom: 1rem;
    font-size: 1.2rem;
    font-weight: 600;
}

/* Responsive */
@media (max-width: 768px) {
    .dashboard-tabs {
        flex-direction: column;
    }
    
    .dashboard-tab {
        border-bottom: none;
        border-left: 2px solid transparent;
    }
    
    .dashboard-tab.active {
        border-bottom: none;
        border-left-color: #007bff;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>
`;

// Inject dashboard styles
document.head.insertAdjacentHTML('beforeend', dashboardStyles);
