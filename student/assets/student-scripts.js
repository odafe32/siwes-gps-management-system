// Student Scripts - Shared functionality for all student pages

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
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (window.innerWidth > 1024) {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('sidebar-collapsed');
                
                // Save state to localStorage
                localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
                
                // Add animation class for smooth transition
                sidebar.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
                mainContent.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
            }
        });
    }
    
    // Mobile menu toggle
    if (menuToggle) {
        menuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            sidebar.classList.add('open');
            sidebarOverlay.classList.add('active');
            document.body.style.overflow = 'hidden'; // Prevent background scroll
        });
    }
    
    // Close sidebar on overlay click
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            closeSidebar();
        });
    }
    
    // Close sidebar on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeSidebar();
        }
    });
    
    // Close sidebar function
    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('open');
        if (sidebarOverlay) sidebarOverlay.classList.remove('active');
        document.body.style.overflow = ''; // Restore scroll
    }
    
    // Add tooltips to nav links in collapsed state
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        const text = link.getAttribute('data-title') || link.textContent.trim();
        link.setAttribute('data-title', text);
        
        // Add touch feedback for mobile
        link.addEventListener('touchstart', function() {
            this.style.transform = 'scale(0.95)';
        });
        
        link.addEventListener('touchend', function() {
            this.style.transform = '';
        });
    });
    
    // Enhanced responsive behavior
    function handleResize() {
        const width = window.innerWidth;
        
        // Check if elements exist before manipulating them
        if (!sidebar || !mainContent) return;
        
        if (width <= 1024) {
            // Mobile/tablet behavior
            sidebar.classList.remove('collapsed');
            mainContent.classList.remove('sidebar-collapsed');
            
            // Close sidebar if open on resize
            if (sidebar.classList.contains('open')) {
                closeSidebar();
            }
        } else {
            // Desktop behavior
            document.body.style.overflow = ''; // Restore scroll on desktop
            if (sidebar) sidebar.classList.remove('open');
            if (sidebarOverlay) sidebarOverlay.classList.remove('active');
            
            // Restore collapsed state if it was saved
            if (localStorage.getItem('sidebarCollapsed') === 'true') {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('sidebar-collapsed');
            }
        }
    }
    
    // Debounced resize handler
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(handleResize, 250);
    });
    
    // Initial resize check
    handleResize();
    
    // Add smooth scrolling to sidebar
    if (sidebar) {
        sidebar.style.scrollBehavior = 'smooth';
    }
    
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
    
    // Add animation delays for staggered animations
    const elements = document.querySelectorAll('.fade-in-up');
    elements.forEach((element, index) => {
        element.style.animationDelay = `${index * 0.1}s`;
    });
    
    // Toast notification system
    window.showToast = function(message, type = 'info', duration = 5000) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <div class="toast-content">
                <i class="fas fa-${getToastIcon(type)}"></i>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(toast);
        
        // Trigger animation
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);
        
        // Auto remove
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }, duration);
    };
    
    function getToastIcon(type) {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-triangle',
            warning: 'exclamation-circle',
            info: 'info-circle'
        };
        return icons[type] || 'info-circle';
    }
    
    // Form validation helpers
    window.validateForm = function(formElement) {
        const inputs = formElement.querySelectorAll('input[required], textarea[required], select[required]');
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
    
    // Loading state helpers
    window.setLoadingState = function(button, isLoading, loadingText = 'Loading...', originalText = null) {
        if (isLoading) {
            if (!originalText) {
                originalText = button.innerHTML;
                button.setAttribute('data-original-text', originalText);
            }
            button.disabled = true;
            button.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${loadingText}`;
        } else {
            const original = button.getAttribute('data-original-text') || originalText;
            if (original) {
                button.innerHTML = original;
            }
            button.disabled = false;
        }
    };
    
    // Utility functions
    window.formatDate = function(date) {
        return new Date(date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    };
    
    window.formatCurrency = function(amount) {
        return new Intl.NumberFormat('en-NG', {
            style: 'currency',
            currency: 'NGN'
        }).format(amount);
    };
    
    // Debounce utility
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
});
