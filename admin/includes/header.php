<?php
if (!defined('BOGF_ACCESS')) {
    die('Direct access not permitted');
}

$admin = getCurrentAdmin();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' | ' : ''; ?>Admin Panel | Business of Ghanaian Fashion</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <?php if (isset($additionalHead)): ?>
        <?php echo $additionalHead; ?>
    <?php endif; ?>
</head>
<body class="admin-body">
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <h2>BoGF Admin</h2>
                <p>Fashion Nexus Ghana</p>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li>
                        <a href="dashboard.php" class="<?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                            <i data-lucide="layout-dashboard"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="speakers.php" class="<?php echo $currentPage === 'speakers' ? 'active' : ''; ?>">
                            <i data-lucide="users"></i>
                            <span>Speakers</span>
                        </a>
                    </li>
                    <li>
                        <a href="applications.php" class="<?php echo $currentPage === 'applications' ? 'active' : ''; ?>">
                            <i data-lucide="file-text"></i>
                            <span>Applications</span>
                        </a>
                    </li>
                    <li>
                        <a href="messages.php" class="<?php echo $currentPage === 'messages' ? 'active' : ''; ?>">
                            <i data-lucide="message-circle"></i>
                            <span>Messages</span>
                        </a>
                    </li>
                    <li>
                        <a href="partnerships.php" class="<?php echo $currentPage === 'partnerships' ? 'active' : ''; ?>">
                            <i data-lucide="handshake"></i>
                            <span>Partnerships</span>
                        </a>
                    </li>
                    <li>
                        <a href="newsletter.php" class="<?php echo $currentPage === 'newsletter' ? 'active' : ''; ?>">
                            <i data-lucide="mail"></i>
                            <span>Newsletter</span>
                        </a>
                    </li>
                    <li>
                        <a href="analytics.php" class="<?php echo $currentPage === 'analytics' ? 'active' : ''; ?>">
                            <i data-lucide="bar-chart-3"></i>
                            <span>Analytics</span>
                        </a>
                    </li>
                    <li>
                        <a href="settings.php" class="<?php echo $currentPage === 'settings' ? 'active' : ''; ?>">
                            <i data-lucide="settings"></i>
                            <span>Settings</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="admin-main">
            <!-- Header -->
            <header class="admin-header">
                <h1><?php echo isset($pageTitle) ? $pageTitle : 'Dashboard'; ?></h1>
                
                <div class="admin-user-menu">
                    <button class="user-menu-toggle" id="userMenuToggle">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($admin['first_name'], 0, 1) . substr($admin['last_name'], 0, 1)); ?>
                        </div>
                        <div class="user-info">
                            <span class="user-name"><?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?></span>
                            <small class="user-role"><?php echo ucfirst(str_replace('_', ' ', $admin['role'])); ?></small>
                        </div>
                        <i data-lucide="chevron-down"></i>
                    </button>
                    
                    <div class="user-menu-dropdown" id="userMenuDropdown">
                        <a href="profile.php">
                            <i data-lucide="user"></i>
                            Profile
                        </a>
                        <a href="settings.php">
                            <i data-lucide="settings"></i>
                            Settings
                        </a>
                        <hr>
                        <a href="../index.html" target="_blank">
                            <i data-lucide="external-link"></i>
                            View Website
                        </a>
                        <hr>
                        <a href="logout.php" onclick="return confirm('Are you sure you want to logout?')">
                            <i data-lucide="log-out"></i>
                            Logout
                        </a>
                    </div>
                </div>
            </header>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    
    // User menu toggle
    const userMenuToggle = document.getElementById('userMenuToggle');
    const userMenuDropdown = document.getElementById('userMenuDropdown');
    
    if (userMenuToggle && userMenuDropdown) {
        userMenuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            userMenuDropdown.classList.toggle('show');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!userMenuToggle.contains(e.target) && !userMenuDropdown.contains(e.target)) {
                userMenuDropdown.classList.remove('show');
            }
        });
    }
    
    // Auto-logout warning
    let logoutTimer;
    let warningTimer;
    
    function resetTimer() {
        clearTimeout(logoutTimer);
        clearTimeout(warningTimer);
        
        // Warn 5 minutes before logout
        warningTimer = setTimeout(function() {
            if (confirm('Your session will expire in 5 minutes due to inactivity. Click OK to stay logged in.')) {
                // Ping the server to refresh session
                fetch('api/ping.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'same-origin'
                });
            }
        }, <?php echo (SESSION_TIMEOUT - 300) * 1000; ?>); // 5 minutes before timeout
        
        // Auto logout
        logoutTimer = setTimeout(function() {
            alert('Your session has expired. You will be redirected to the login page.');
            window.location.href = 'logout.php';
        }, <?php echo SESSION_TIMEOUT * 1000; ?>);
    }
    
    // Reset timer on user activity
    ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'].forEach(function(event) {
        document.addEventListener(event, resetTimer, true);
    });
    
    // Initialize timer
    resetTimer();
});

// Global admin functions
window.AdminPanel = {
    // Show notification
    showNotification: function(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `admin-notification ${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i data-lucide="${this.getNotificationIcon(type)}"></i>
                <span>${message}</span>
                <button class="notification-close">&times;</button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Initialize icon
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
        
        // Show notification
        setTimeout(() => notification.classList.add('show'), 100);
        
        // Auto remove
        setTimeout(() => this.removeNotification(notification), 5000);
        
        // Manual close
        notification.querySelector('.notification-close').addEventListener('click', () => {
            this.removeNotification(notification);
        });
    },
    
    getNotificationIcon: function(type) {
        const icons = {
            success: 'check-circle',
            error: 'x-circle',
            warning: 'alert-triangle',
            info: 'info'
        };
        return icons[type] || 'info';
    },
    
    removeNotification: function(notification) {
        notification.classList.add('hide');
        setTimeout(() => {
            if (notification && notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    },
    
    // Confirm action
    confirm: function(message, callback) {
        if (window.confirm(message)) {
            callback();
        }
    },
    
    // AJAX helper
    ajax: function(url, options = {}) {
        const defaults = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        };
        
        const config = { ...defaults, ...options };
        
        return fetch(url, config)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .catch(error => {
                console.error('AJAX Error:', error);
                this.showNotification('An error occurred. Please try again.', 'error');
                throw error;
            });
    }
};
</script>

<style>
.user-info {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    margin: 0 var(--spacing-sm);
}

.user-name {
    font-weight: 500;
    font-size: 0.9rem;
    color: var(--dark-gray);
}

.user-role {
    font-size: 0.75rem;
    color: var(--medium-gray);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.admin-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 10001;
    max-width: 400px;
    padding: 16px 20px;
    border-radius: 8px;
    box-shadow: var(--shadow-lg);
    transform: translateX(100%);
    transition: transform 0.3s ease;
}

.admin-notification.show {
    transform: translateX(0);
}

.admin-notification.hide {
    transform: translateX(100%);
}

.admin-notification.success {
    background: #D1FAE5;
    color: #065F46;
    border-left: 4px solid #10B981;
}

.admin-notification.error {
    background: #FEE2E2;
    color: #991B1B;
    border-left: 4px solid #EF4444;
}

.admin-notification.warning {
    background: #FEF3C7;
    color: #92400E;
    border-left: 4px solid #F59E0B;
}

.admin-notification.info {
    background: #DBEAFE;
    color: #1E40AF;
    border-left: 4px solid #3B82F6;
}

.notification-content {
    display: flex;
    align-items: center;
    gap: 12px;
}

.notification-close {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    opacity: 0.7;
    margin-left: auto;
    padding: 0;
    line-height: 1;
}

.notification-close:hover {
    opacity: 1;
}

@media (max-width: 768px) {
    .admin-notification {
        right: 10px;
        left: 10px;
        max-width: none;
        transform: translateY(-100%);
    }
    
    .admin-notification.show {
        transform: translateY(0);
    }
    
    .admin-notification.hide {
        transform: translateY(-100%);
    }
}
</style>