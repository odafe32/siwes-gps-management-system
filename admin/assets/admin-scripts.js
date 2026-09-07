// Admin Scripts - Shared functionality for all admin pages

document.addEventListener('DOMContentLoaded', function() {
    // Sidebar functionality
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const menuToggle = document.getElementById('menuToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const mainContent = document.querySelector('.main-content');
    
    // Check if sidebar should be collapsed by default on desktop
    const isDesktop = window.innerWidth > 1024;
    const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    
    if (isDesktop && sidebarCollapsed) {
        sidebar.classList.add('collapsed');
        mainContent.classList.add('sidebar-collapsed');
    }
    
    // Sidebar collapse toggle (desktop)
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('sidebar-collapsed');
            
            // Save state to localStorage
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        });
    }
    
    // Mobile menu toggle
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.add('open');
            sidebarOverlay.classList.add('active');
        });
    }
    
    // Close sidebar on overlay click
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('open');
            sidebarOverlay.classList.remove('active');
        });
    }
    
    // Close sidebar on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            sidebar.classList.remove('open');
            sidebarOverlay.classList.remove('active');
        }
    });
    
    // Add tooltips to nav links in collapsed state
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        const text = link.textContent.trim();
        link.setAttribute('data-title', text);
    });
    
    // Responsive behavior
    function handleResize() {
        if (window.innerWidth <= 1024) {
            sidebar.classList.remove('collapsed');
            mainContent.classList.remove('sidebar-collapsed');
        }
    }
    
    window.addEventListener('resize', handleResize);
    
    // Logout functionality
    window.logout = function() {
        if (confirm('Are you sure you want to logout?')) {
            fetch('../backend/api/auth.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'logout'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = '../index.php';
                } else {
                    window.location.href = '../index.php';
                }
            })
            .catch(error => {
                console.error('Logout error:', error);
                window.location.href = '../index.php';
            });
        }
    };
    
    // Add animation delays
    const elements = document.querySelectorAll('.fade-in-up');
    elements.forEach((element, index) => {
        element.style.animationDelay = `${index * 0.1}s`;
    });
    
    // Form validation helpers
    window.validateForm = function(formId) {
        const form = document.getElementById(formId);
        if (!form) return true;
        
        const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
        let isValid = true;
        
        inputs.forEach(input => {
            if (!input.value.trim()) {
                input.classList.add('is-invalid');
                isValid = false;
            } else {
                input.classList.remove('is-invalid');
            }
        });
        
        return isValid;
    };
    
    // Show/hide loading states
    window.showLoading = function(element) {
        if (element) {
            element.disabled = true;
            element.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
        }
    };
    
    window.hideLoading = function(element, originalText) {
        if (element) {
            element.disabled = false;
            element.innerHTML = originalText;
        }
    };
    
    // Toast notification system
    window.showToast = function(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <div class="toast-content">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 300);
        }, 3000);
    };
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });
    
    // Initialize tooltips for Bootstrap components
    if (typeof bootstrap !== 'undefined') {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
});

// Utility functions
window.formatDate = function(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

window.formatCurrency = function(amount) {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN'
    }).format(amount);
};

window.debounce = function(func, wait) {
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