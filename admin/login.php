<?php
/**
 * Admin Login Page
 */

define('BOGF_ACCESS', true);
require_once '../includes/config.php';

startSecureSession();

// Redirect if already logged in
if (isAdminLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$loginAttempts = $_SESSION['login_attempts'] ?? 0;
$lastAttemptTime = $_SESSION['last_attempt_time'] ?? 0;

// Check if user is locked out
$isLockedOut = $loginAttempts >= MAX_LOGIN_ATTEMPTS && (time() - $lastAttemptTime) < LOGIN_LOCKOUT_TIME;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isLockedOut) {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        try {
            $db = Database::getInstance()->getConnection();
            
            $stmt = $db->prepare("
                SELECT id, username, email, password_hash, first_name, last_name, role, is_active 
                FROM admin_users 
                WHERE (username = ? OR email = ?) AND is_active = 1
            ");
            $stmt->execute([$username, $username]);
            $admin = $stmt->fetch();
            
            if ($admin && verifyPassword($password, $admin['password_hash'])) {
                // Successful login
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_role'] = $admin['role'];
                $_SESSION['login_time'] = time();
                
                // Reset login attempts
                unset($_SESSION['login_attempts']);
                unset($_SESSION['last_attempt_time']);
                
                // Update last login
                $updateStmt = $db->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
                $updateStmt->execute([$admin['id']]);
                
                // Log successful login
                logAdminActivity($admin['id'], 'login_success');
                
                header('Location: dashboard.php');
                exit;
            } else {
                // Failed login
                $_SESSION['login_attempts'] = $loginAttempts + 1;
                $_SESSION['last_attempt_time'] = time();
                
                if ($_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
                    $error = 'Too many failed attempts. Please try again in ' . (LOGIN_LOCKOUT_TIME / 60) . ' minutes.';
                } else {
                    $error = 'Invalid username or password';
                }
                
                // Log failed login attempt
                if ($admin) {
                    logAdminActivity($admin['id'], 'login_failed');
                }
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $error = 'Login system temporarily unavailable. Please try again later.';
        }
    }
}

$lockoutTimeRemaining = $isLockedOut ? LOGIN_LOCKOUT_TIME - (time() - $lastAttemptTime) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Business of Ghanaian Fashion</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body class="admin-body">
    <div class="admin-login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo">
                    <h2>Fashion Nexus <span class="highlight">Ghana</span></h2>
                    <p>Admin Portal</p>
                </div>
            </div>
            
            <div class="login-content">
                <h3>Welcome Back</h3>
                <p class="login-subtitle">Sign in to manage the BoGF platform</p>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i data-lucide="alert-circle"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($isLockedOut): ?>
                    <div class="alert alert-warning">
                        <i data-lucide="clock"></i>
                        <span>Account temporarily locked. Please try again in <span id="countdown"><?php echo $lockoutTimeRemaining; ?></span> seconds.</span>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="login-form" <?php echo $isLockedOut ? 'style="display:none;"' : ''; ?>>
                    <div class="form-group">
                        <label for="username">Username or Email</label>
                        <div class="input-with-icon">
                            <i data-lucide="user"></i>
                            <input 
                                type="text" 
                                id="username" 
                                name="username" 
                                value="<?php echo htmlspecialchars($username ?? ''); ?>"
                                required 
                                autocomplete="username"
                                placeholder="Enter your username or email"
                            >
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-with-icon">
                            <i data-lucide="lock"></i>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                required 
                                autocomplete="current-password"
                                placeholder="Enter your password"
                            >
                            <button type="button" class="password-toggle" id="passwordToggle">
                                <i data-lucide="eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="remember_me" value="1">
                            <span class="checkmark"></span>
                            Remember me for 30 days
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-full">
                        <i data-lucide="log-in"></i>
                        Sign In
                    </button>
                </form>
                
                <div class="login-footer">
                    <p>Forgot your password? <a href="forgot-password.php">Reset it here</a></p>
                </div>
            </div>
        </div>
        
        <div class="login-background">
            <div class="background-pattern"></div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Lucide icons
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
            
            // Password toggle functionality
            const passwordInput = document.getElementById('password');
            const passwordToggle = document.getElementById('passwordToggle');
            
            if (passwordToggle) {
                passwordToggle.addEventListener('click', function() {
                    const type = passwordInput.type === 'password' ? 'text' : 'password';
                    passwordInput.type = type;
                    
                    const icon = type === 'password' ? 'eye' : 'eye-off';
                    passwordToggle.innerHTML = `<i data-lucide="${icon}"></i>`;
                    lucide.createIcons();
                });
            }
            
            // Countdown timer for lockout
            <?php if ($isLockedOut): ?>
            let timeRemaining = <?php echo $lockoutTimeRemaining; ?>;
            const countdownElement = document.getElementById('countdown');
            const form = document.querySelector('.login-form');
            
            const countdown = setInterval(function() {
                timeRemaining--;
                if (countdownElement) {
                    countdownElement.textContent = timeRemaining;
                }
                
                if (timeRemaining <= 0) {
                    clearInterval(countdown);
                    location.reload();
                }
            }, 1000);
            <?php endif; ?>
            
            // Auto-focus on username field
            const usernameInput = document.getElementById('username');
            if (usernameInput && !usernameInput.value) {
                usernameInput.focus();
            } else {
                const passwordInput = document.getElementById('password');
                if (passwordInput) {
                    passwordInput.focus();
                }
            }
        });
    </script>
</body>
</html>