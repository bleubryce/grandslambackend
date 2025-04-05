<?php
// Authentication functions for Baseball Analytics System

// Start session if not already started
if (session_status() === PHP_SESSION_INATIVE) {
    session_start();
}

// User credentials (in a real application, these would be stored in a database)
$users = array(
    'admin' => array(
        'id' => 1,
        'name' => 'Administrator',
        'password' => '$2y$10$GKbLBwJlJRVxvHTKhOZ9qeQcUy.3jEwBJJy9yh9IMvQldY1QcH.Oe', // hashed 'admin123'
        'email' => 'admin@baseballanalytics.com',
        'role' => 'admin'
    ),
    'analyst' => array(
        'id' => 2,
        'name' => 'Baseball Analyst',
        'password' => '$2y$10$5QH.EK8oEZBwS.vCw7hNT.chCuYPHEWWlsOVRLm/VgCq3HuhGlzHi', // hashed 'analyst123'
        'email' => 'analyst@baseballanalytics.com',
        'role' => 'analyst'
    ),
    'scout' => array(
        'id' => 3,
        'name' => 'Baseball Scout',
        'password' => '$2y$10$ByZO5KH9oLMUQ9ahzFQSYOQA.jiFYG7XlCdTAWxOULGJqTFHxayMK', // hashed 'scout123'
        'email' => 'scout@baseballanalytics.com',
        'role' => 'scout'
    )
);

/**
 * Authenticate user with username and password
 * 
 * @param string $username Username
 * @param string $password Plain text password
 * @return bool True if authentication successful, false otherwise
 */
function authenticateUser($username, $password) {
    global $users;
    
    // Check if username exists
    if (!isset($users[$username])) {
        return false;
    }
    
    // Verify password
    if (password_verify($password, $users[$username]['password'])) {
        // Store user data in session
        $_SESSION['user'] = array(
            'id' => $users[$username]['id'],
            'username' => $username,
            'name' => $users[$username]['name'],
            'email' => $users[$username]['email'],
            'role' => $users[$username]['role'],
            'last_activity' => time()
        );
        
        return true;
    }
    
    return false;
}

/**
 * Check if user is authenticated
 * 
 * @return bool True if user is authenticated, false otherwise
 */
function isAuthenticated() {
    return isset($_SESSION['user']);
}

/**
 * Require authentication to access a page
 * If not authenticated, redirect to login page
 */
function requireAuth() {
    if (!isAuthenticated()) {
        header('Location: index.php');
        exit;
    }
    
    // Check for session timeout (30 minutes)
    if (time() - $_SESSION['user']['last_activity'] > 1800) {
        logout();
        header('Location: index.php?timeout=1');
        exit;
    }
    
    // Update last activity time
    $_SESSION['user']['last_activity'] = time();
}

/**
 * Get current authenticated user
 * 
 * @return array|null User data or null if not authenticated
 */
function getCurrentUser() {
    return $_SESSION['user'] ?? null;
}

/**
 * Check if current user has specified role
 * 
 * @param string|array $roles Role or array of roles to check
 * @return bool True if user has role, false otherwise
 */
function hasRole($roles) {
    if (!isAuthenticated()) {
        return false;
    }
    
    if (is_string($roles)) {
        $roles = array($roles);
    }
    
    return in_array($_SESSION['user']['role'], $roles);
}

/**
 * Logout current user
 */
function logout() {
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session
    session_destroy();
}

/**
 * Generate CSRF token
 * 
 * @return string CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * 
 * @param string $token Token to verify
 * @return bool True if token is valid, false otherwise
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
