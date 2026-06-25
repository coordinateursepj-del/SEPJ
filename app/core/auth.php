<?php
/**
 * Authentication System - SEPJ Gabès
 * 
 * Session-based authentication with PDO and password_verify.
 */

/**
 * Start session with secure settings
 */
function session_start_secure(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    
    $cookieParams = [
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'domain'   => '',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax',
    ];
    
    session_set_cookie_params($cookieParams);
    session_start();
    
    // Regenerate session ID periodically
    if (!isset($_SESSION['_last_regenerated'])) {
        $_SESSION['_last_regenerated'] = time();
    } elseif (time() - $_SESSION['_last_regenerated'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['_last_regenerated'] = time();
    }
}

/**
 * Attempt to log in a user
 */
function login(string $email, string $password): array
{
    $result = [
        'success' => false,
        'message' => '',
    ];
    
    // Rate limiting: check login attempts
    $attempts = $_SESSION['login_attempts'] ?? 0;
    $lastAttempt = $_SESSION['login_last_attempt'] ?? 0;
    
    if ($attempts >= 5 && (time() - $lastAttempt) < 300) {
        $wait = 300 - (time() - $lastAttempt);
        $result['message'] = "محاولات كثيرة. الرجاء الانتظار {$wait} ثانية.<br>Trop de tentatives. Veuillez attendre {$wait} secondes.<br>Too many attempts. Please wait {$wait} seconds.";
        return $result;
    }
    
    try {
        $stmt = db()->prepare("
            SELECT id, name, email, password_hash, role, status 
            FROM users 
            WHERE email = :email 
            LIMIT 1
        ");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        
        // Use generic error message to prevent username enumeration
        $genericError = 'البريد الإلكتروني أو كلمة المرور غير صحيحة.<br>Email ou mot de passe incorrect.<br>Invalid email or password.';
        
        if ($user && $user['status'] === 'active' && password_verify($password, $user['password_hash'])) {
            // Login successful
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            $_SESSION['_last_regenerated'] = time();
            
            // Reset login attempts
            unset($_SESSION['login_attempts']);
            unset($_SESSION['login_last_attempt']);
            
            // Log audit
            log_audit($user['id'], 'login', 'user', $user['id']);
            
            $result['success'] = true;
            $result['message'] = 'مرحباً بك';
        } else {
            // Login failed - use generic error to prevent username enumeration
            $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
            $_SESSION['login_last_attempt'] = time();
            $result['message'] = $genericError;
        }
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        $result['message'] = 'حدث خطأ. الرجاء المحاولة لاحقاً.<br>Erreur. Veuillez réessayer plus tard.<br>Error. Please try again later.';
    }
    
    return $result;
}

/**
 * Check if user is logged in
 */
function is_logged_in(): bool
{
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Require authentication - redirect to login if not authenticated
 */
function require_login(): void
{
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        // Use absolute ADMIN_URL so this works from any nesting depth
        redirect(ADMIN_URL . '/login.php');
    }
}

/**
 * Require specific role(s)
 */
function require_role($roles): void
{
    require_login();

    if (!is_array($roles)) {
        $roles = [$roles];
    }

    if (!in_array($_SESSION['user_role'], $roles)) {
        set_flash('error', 'ليس لديك صلاحية للوصول إلى هذه الصفحة. / Vous n\'avez pas la permission. / You do not have permission.');
        redirect(ADMIN_URL . '/dashboard.php');
    }
}

/**
 * Get current user data from session
 */
function current_user(): ?array
{
    if (!is_logged_in()) {
        return null;
    }
    
    return [
        'id'    => $_SESSION['user_id'],
        'name'  => $_SESSION['user_name'],
        'email' => $_SESSION['user_email'],
        'role'  => $_SESSION['user_role'],
    ];
}

/**
 * Log out the current user
 */
function logout(): void
{
    if (is_logged_in()) {
        log_audit($_SESSION['user_id'], 'logout', 'user', $_SESSION['user_id']);
    }
    
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

/**
 * Check if current user is admin
 */
function is_admin(): bool
{
    return is_logged_in() && $_SESSION['user_role'] === 'admin';
}

/**
 * Check if current user is editor or above
 */
function is_editor(): bool
{
    return is_logged_in() && ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'editor');
}