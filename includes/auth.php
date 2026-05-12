<?php
/**
 * Authentication System
 * 
 * Handles user authentication, authorization, and session management.
 */

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(__DIR__) . '/includes/functions.php';

/**
 * Check if user is logged in
 * 
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn() {
    return session_has('user_id');
}

/**
 * Check if user has a specific role
 * 
 * @param string|array $role The role(s) to check
 * @return bool True if user has the role, false otherwise
 */
function hasRole($role) {
    if (!isLoggedIn()) return false;
    $userRole = session_get('role_name');
    if (is_array($role)) {
        return in_array($userRole, $role);
    }
    return $userRole === $role;
}

/**
 * Get current user information
 * 
 * @return array|null User data or null if not logged in
 */
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return [
        'id' => session_get('user_id'),
        'name' => session_get('user_name'),
        'email' => session_get('user_email'),
        'role_id' => session_get('role_id'),
        'role_name' => session_get('role_name'),
        'group_id' => session_get('group_id')
    ];
}

/**
 * Authenticate a user
 * 
 * @param string $email User email
 * @param string $password User password
 * @return bool True if authenticated, false otherwise
 */
function authenticateUser($email, $password) {
    $db = db();
    $stmt = $db->prepare("
        SELECT u.*, r.name as role_name 
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.id
        WHERE u.email = :email AND u.is_active = 1
    ");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();
    
    if (!$user) return false;
    if ($password !== $user['password']) return false;
    
    $db->prepare("UPDATE users SET last_login = NOW() WHERE id = :id")
       ->execute(['id' => $user['id']]);
    
    session_set('user_id', $user['id']);
    session_set('user_name', $user['name']);
    session_set('user_email', $user['email']);
    session_set('role_id', $user['role_id']);
    session_set('role_name', $user['role_name']);
    session_set('group_id', $user['group_id']);
    
    logAction('LOGIN', 'users', $user['id']);
    return true;
}

/**
 * Logout the current user
 * 
 * @return void
 */
function logoutUser() {
    logAction('LOGOUT', 'users', session_get('user_id'));
    session_destroy_all();
}

/**
 * Require user to be logged in
 * 
 * @return void
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

/**
 * Require user to have a specific role
 * 
 * @param string|array $role The role(s) required
 * @return void
 */
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header('Location: dashboard.php?error=access_denied');
        exit();
    }
}