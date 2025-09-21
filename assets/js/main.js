// Main JavaScript file for CheatStore

// Utility functions
const Utils = {
    // Format currency
    formatCurrency: (amount) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(amount);
    },

    // Format date
    formatDate: (date) => {
        return new Date(date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    },

    // Show notification
    showNotification: (message, type = 'info') => {
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
    },

    // Copy to clipboard
    copyToClipboard: async (text) => {
        try {
            await navigator.clipboard.writeText(text);
            Utils.showNotification('Copied to clipboard!', 'success');
        } catch (err) {
            Utils.showNotification('Failed to copy to clipboard', 'error');
        }
    },

    // Debounce function
    debounce: (func, wait) => {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    // Validate email
    validateEmail: (email) => {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    },

    // Validate password strength
    validatePassword: (password) => {
        const errors = [];
        
        if (password.length < 8) {
            errors.push('Password must be at least 8 characters long');
        }
        
        if (!/[A-Z]/.test(password)) {
            errors.push('Password must contain at least one uppercase letter');
        }
        
        if (!/[a-z]/.test(password)) {
            errors.push('Password must contain at least one lowercase letter');
        }
        
        if (!/[0-9]/.test(password)) {
            errors.push('Password must contain at least one number');
        }
        
        return errors;
    }
};

// Form validation
const FormValidator = {
    // Validate form fields
    validateForm: (form) => {
        const errors = [];
        const fields = form.querySelectorAll('[data-validate]');
        
        fields.forEach(field => {
            const value = field.value.trim();
            const rules = field.dataset.validate.split('|');
            
            rules.forEach(rule => {
                const [ruleName, ruleValue] = rule.split(':');
                
                switch (ruleName) {
                    case 'required':
                        if (!value) {
                            errors.push(`${field.dataset.label || field.name} is required`);
                        }
                        break;
                        
                    case 'email':
                        if (value && !Utils.validateEmail(value)) {
                            errors.push('Please enter a valid email address');
                        }
                        break;
                        
                    case 'min':
                        if (value && value.length < parseInt(ruleValue)) {
                            errors.push(`${field.dataset.label || field.name} must be at least ${ruleValue} characters`);
                        }
                        break;
                        
                    case 'max':
                        if (value && value.length > parseInt(ruleValue)) {
                            errors.push(`${field.dataset.label || field.name} must be no more than ${ruleValue} characters`);
                        }
                        break;
                        
                    case 'password':
                        const passwordErrors = Utils.validatePassword(value);
                        errors.push(...passwordErrors);
                        break;
                }
            });
        });
        
        return errors;
    },

    // Show form errors
    showErrors: (form, errors) => {
        // Clear previous errors
        form.querySelectorAll('.form-error').forEach(el => el.remove());
        
        // Show new errors
        errors.forEach(error => {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'form-error';
            errorDiv.textContent = error;
            form.appendChild(errorDiv);
        });
    }
};

// API functions
const API = {
    // Make API request
    request: async (url, options = {}) => {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        
        const config = { ...defaultOptions, ...options };
        
        try {
            const response = await fetch(url, config);
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || 'Request failed');
            }
            
            return data;
        } catch (error) {
            console.error('API request failed:', error);
            throw error;
        }
    },

    // Get products
    getProducts: async (filters = {}) => {
        const params = new URLSearchParams(filters);
        return await API.request(`/api/products.php?${params}`);
    },

    // Get product details
    getProduct: async (slug) => {
        return await API.request(`/api/products.php?slug=${slug}`);
    },

    // Create order
    createOrder: async (orderData) => {
        return await API.request('/api/orders.php', {
            method: 'POST',
            body: JSON.stringify(orderData)
        });
    },

    // Get user orders
    getUserOrders: async () => {
        return await API.request('/api/orders.php');
    },

    // Get user license keys
    getUserKeys: async () => {
        return await API.request('/api/keys.php');
    }
};

// UI Components
const UI = {
    // Initialize tooltips
    initTooltips: () => {
        const tooltips = document.querySelectorAll('[data-tooltip]');
        
        tooltips.forEach(element => {
            element.addEventListener('mouseenter', (e) => {
                const tooltip = document.createElement('div');
                tooltip.className = 'tooltip';
                tooltip.textContent = e.target.dataset.tooltip;
                document.body.appendChild(tooltip);
                
                const rect = e.target.getBoundingClientRect();
                tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
                tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + 'px';
                
                e.target._tooltip = tooltip;
            });
            
            element.addEventListener('mouseleave', (e) => {
                if (e.target._tooltip) {
                    e.target._tooltip.remove();
                    e.target._tooltip = null;
                }
            });
        });
    },

    // Initialize modals
    initModals: () => {
        const modalTriggers = document.querySelectorAll('[data-modal]');
        
        modalTriggers.forEach(trigger => {
            trigger.addEventListener('click', (e) => {
                e.preventDefault();
                const modalId = trigger.dataset.modal;
                const modal = document.getElementById(modalId);
                
                if (modal) {
                    modal.classList.add('modal-active');
                    document.body.style.overflow = 'hidden';
                }
            });
        });
        
        // Close modal on backdrop click
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('modal-active');
                document.body.style.overflow = '';
            }
        });
        
        // Close modal on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const activeModal = document.querySelector('.modal-active');
                if (activeModal) {
                    activeModal.classList.remove('modal-active');
                    document.body.style.overflow = '';
                }
            }
        });
    },

    // Initialize dropdowns
    initDropdowns: () => {
        const dropdowns = document.querySelectorAll('.dropdown');
        
        dropdowns.forEach(dropdown => {
            const trigger = dropdown.querySelector('.dropdown-trigger');
            const menu = dropdown.querySelector('.dropdown-menu');
            
            if (trigger && menu) {
                trigger.addEventListener('click', (e) => {
                    e.preventDefault();
                    dropdown.classList.toggle('dropdown-active');
                });
            }
        });
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown-active').forEach(dropdown => {
                    dropdown.classList.remove('dropdown-active');
                });
            }
        });
    },

    // Show loading spinner
    showLoading: (element) => {
        const spinner = document.createElement('div');
        spinner.className = 'loading-spinner';
        spinner.innerHTML = '<div class="spinner"></div>';
        element.appendChild(spinner);
        element.style.position = 'relative';
    },

    // Hide loading spinner
    hideLoading: (element) => {
        const spinner = element.querySelector('.loading-spinner');
        if (spinner) {
            spinner.remove();
        }
    }
};

// Custom Right-Click Context Menu
const CustomContextMenu = {
    menu: null,
    isVisible: false,

    init() {
        this.createMenu();
        this.bindEvents();
    },

    createMenu() {
        // Create menu container
        this.menu = document.createElement('div');
        this.menu.id = 'custom-context-menu';
        this.menu.className = 'custom-context-menu';
        this.menu.innerHTML = `
            <div class="context-menu-item">
                <i class="fas fa-code"></i>
                <span>Made by Conquestor</span>
            </div>
            <div class="context-menu-item">
                <i class="fas fa-heart"></i>
                <span>Made with love</span>
            </div>
            <div class="context-menu-divider"></div>
            <div class="context-menu-item" data-action="view-source">
                <i class="fas fa-eye"></i>
                <span>View Page Source</span>
            </div>
            <div class="context-menu-item" data-action="inspect">
                <i class="fas fa-search"></i>
                <span>Inspect Element</span>
            </div>
        `;
        
        document.body.appendChild(this.menu);
    },

    bindEvents() {
        // Prevent default context menu on right click
        document.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            this.showMenu(e);
        });

        // Hide menu on left click
        document.addEventListener('click', (e) => {
            if (!this.menu.contains(e.target)) {
                this.hideMenu();
            }
        });

        // Hide menu on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.hideMenu();
            }
        });

        // Handle menu item clicks
        this.menu.addEventListener('click', (e) => {
            const item = e.target.closest('.context-menu-item');
            if (item && item.dataset.action) {
                this.handleAction(item.dataset.action);
            }
        });

        // Prevent menu from showing on specific elements
        document.addEventListener('contextmenu', (e) => {
            const target = e.target;
            
            // Don't show custom menu on form inputs, textareas, and code elements
            if (target.tagName === 'INPUT' || 
                target.tagName === 'TEXTAREA' || 
                target.tagName === 'SELECT' ||
                target.contentEditable === 'true' ||
                target.closest('.code-editor') ||
                target.closest('.ace_editor')) {
                return; // Allow default context menu
            }
        });
    },

    showMenu(e) {
        const target = e.target;
        
        // Don't show custom menu on form inputs, textareas, and code elements
        if (target.tagName === 'INPUT' || 
            target.tagName === 'TEXTAREA' || 
            target.tagName === 'SELECT' ||
            target.contentEditable === 'true' ||
            target.closest('.code-editor') ||
            target.closest('.ace_editor')) {
            return; // Allow default context menu
        }

        // Position menu
        const x = e.clientX;
        const y = e.clientY;
        
        this.menu.style.left = x + 'px';
        this.menu.style.top = y + 'px';
        
        // Adjust position if menu goes off screen
        const rect = this.menu.getBoundingClientRect();
        const windowWidth = window.innerWidth;
        const windowHeight = window.innerHeight;
        
        if (rect.right > windowWidth) {
            this.menu.style.left = (x - rect.width) + 'px';
        }
        
        if (rect.bottom > windowHeight) {
            this.menu.style.top = (y - rect.height) + 'px';
        }
        
        this.menu.classList.add('active');
        this.isVisible = true;
    },

    hideMenu() {
        this.menu.classList.remove('active');
        this.isVisible = false;
    },

    handleAction(action) {
        switch (action) {
            case 'view-source':
                window.open('view-source:' + window.location.href, '_blank');
                break;
            case 'inspect':
                // This will be handled by browser's developer tools
                // We can't programmatically open dev tools for security reasons
                Utils.showNotification('Use F12 or Ctrl+Shift+I to open Developer Tools', 'info');
                break;
        }
        this.hideMenu();
    }
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Initialize UI components
    UI.initTooltips();
    UI.initModals();
    UI.initDropdowns();
    
    // Initialize form validation
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
        form.addEventListener('submit', (e) => {
            const errors = FormValidator.validateForm(form);
            
            if (errors.length > 0) {
                e.preventDefault();
                FormValidator.showErrors(form, errors);
            }
        });
    });
    
    // Initialize password strength meter
    const passwordFields = document.querySelectorAll('input[type="password"][data-strength]');
    passwordFields.forEach(field => {
        field.addEventListener('input', Utils.debounce(() => {
            const errors = Utils.validatePassword(field.value);
            const strengthMeter = field.parentElement.querySelector('.password-strength');
            
            if (strengthMeter) {
                if (errors.length === 0) {
                    strengthMeter.className = 'password-strength strong';
                    strengthMeter.textContent = 'Strong password';
                } else if (errors.length <= 2) {
                    strengthMeter.className = 'password-strength medium';
                    strengthMeter.textContent = 'Medium strength';
                } else {
                    strengthMeter.className = 'password-strength weak';
                    strengthMeter.textContent = 'Weak password';
                }
            }
        }, 300));
    });
    
    // Initialize copy buttons
    const copyButtons = document.querySelectorAll('[data-copy]');
    copyButtons.forEach(button => {
        button.addEventListener('click', () => {
            const text = button.dataset.copy;
            Utils.copyToClipboard(text);
        });
    });
    
    // Initialize search functionality
    const searchInput = document.querySelector('#search');
    if (searchInput) {
        searchInput.addEventListener('input', Utils.debounce(() => {
            const query = searchInput.value.trim();
            if (query.length >= 2) {
                // Implement search functionality
                console.log('Searching for:', query);
            }
        }, 500));
    }

    // Initialize custom context menu
    CustomContextMenu.init();
});

// Global error handler
window.addEventListener('error', (e) => {
    console.error('Global error:', e.error);
    Utils.showNotification('An error occurred. Please try again.', 'error');
});

// Export for use in other modules
window.CheatStore = {
    Utils,
    FormValidator,
    API,
    UI
};

// Export for global access
window.CustomContextMenu = CustomContextMenu;
