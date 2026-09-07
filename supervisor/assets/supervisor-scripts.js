document.addEventListener('DOMContentLoaded', function() {
    // Sidebar toggle functionality
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mainContent = document.querySelector('.main-content');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const menuToggle = document.getElementById('menuToggle');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('sidebar-collapsed');
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
        const text = link.querySelector('span').textContent.trim();
        link.setAttribute('data-title', text);
    });
    
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
    
    // For backward compatibility with existing logout button
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            logout();
        });
    }
    
    // Check authentication on page load
    fetch('../backend/api/auth.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'check_auth'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success || data.role !== 'supervisor') {
            window.location.href = 'login.php';
        } else {
            // Update user info in the header
            const userNameElements = document.querySelectorAll('.user-name');
            userNameElements.forEach(el => {
                el.textContent = data.name || 'Supervisor';
            });
            
            // Load dashboard data if on dashboard page
            if (window.location.pathname.includes('dashboard.php') && typeof loadDashboardData === 'function') {
                loadDashboardData();
            }
            
            // Load review data if on review page
            if (window.location.pathname.includes('review.php') && typeof loadReviewData === 'function') {
                loadReviewData();
            }
        }
    })
    .catch(error => {
        console.error('Authentication error:', error);
        window.location.href = 'login.php';
    });
});

// Toast notification function
function showToast(message, type = 'success') {
    const toastContainer = document.getElementById('toastContainer') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `toast toast-${type} slide-in-up`;
    toast.innerHTML = `
        <div class="toast-icon">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
        </div>
        <div class="toast-content">${message}</div>
        <button class="toast-close">&times;</button>
    `;
    
    toastContainer.appendChild(toast);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        toast.classList.add('fade-out');
        setTimeout(() => {
            toast.remove();
            if (toastContainer.children.length === 0) {
                toastContainer.remove();
            }
        }, 300);
    }, 5000);
    
    // Close button functionality
    toast.querySelector('.toast-close').addEventListener('click', () => {
        toast.classList.add('fade-out');
        setTimeout(() => {
            toast.remove();
            if (toastContainer.children.length === 0) {
                toastContainer.remove();
            }
        }, 300);
    });
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container';
    document.body.appendChild(container);
    return container;
}