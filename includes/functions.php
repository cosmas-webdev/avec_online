<?php
/**
 * Helper Functions
 * 
 * Utility functions for the application.
 * 
 * @author Ir. Cosmas MUSAFIRI MUGONGO
 * @version 1.0.0
 */

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/session.php';

/**
 * Generate a reference number
 * 
 * @param string $prefix The prefix for the reference
 * @return string The generated reference
 */
function generateReference($prefix) {
    return $prefix . '-' . date('Ymd') . '-' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Format a date
 * 
 * @param string $date The date to format
 * @param string $format The format to use
 * @return string The formatted date
 */
function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

/**
 * Format a date with time
 * 
 * @param string $date The date to format
 * @return string The formatted date with time
 */
function formatDateTime($date) {
    return date('M d, Y H:i', strtotime($date));
}

/**
 * Format money amount
 * 
 * @param float $amount The amount to format
 * @param string $currency The currency symbol
 * @return string The formatted amount
 */
function formatMoney($amount, $currency = 'CDF') {
    return number_format($amount, 0, ',', ' ') . ' ' . $currency;
}

/**
 * Escape data for HTML output
 * 
 * @param string $data The data to escape
 * @return string The escaped data
 */
function escape($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Get status badge HTML with TailwindCSS classes
 * 
 * @param string $status The status value
 * @return string The badge HTML
 */
function getStatusBadge($status) {
    $badges = [
        'active' => 'bg-green-100 text-green-800',
        'inactive' => 'bg-gray-100 text-gray-800',
        'suspended' => 'bg-yellow-100 text-yellow-800',
        'left' => 'bg-red-100 text-red-800',
        'pending' => 'bg-yellow-100 text-yellow-800',
        'approved' => 'bg-green-100 text-green-800',
        'disbursed' => 'bg-blue-100 text-blue-800',
        'repaid' => 'bg-green-100 text-green-800',
        'defaulted' => 'bg-red-100 text-red-800',
        'rejected' => 'bg-red-100 text-red-800',
        'completed' => 'bg-green-100 text-green-800',
        'overdue' => 'bg-red-100 text-red-800'
    ];
    $class = $badges[$status] ?? 'bg-gray-100 text-gray-800';
    return '<span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ' . $class . '">' . ucfirst($status) . '</span>';
}

/**
 * Log an action to audit log
 * 
 * @param string $action The action performed
 * @param string|null $table The table affected
 * @param int|null $record_id The record ID affected
 * @param string|null $changes The changes made
 * @return void
 */
function logAction($action, $table = null, $record_id = null, $changes = null) {
    $db = db();
    $user_id = session_get('user_id');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $stmt = $db->prepare("
        INSERT INTO audit_logs (user_id, action, table_name, record_id, changes, ip_address, user_agent)
        VALUES (:user_id, :action, :table_name, :record_id, :changes, :ip_address, :user_agent)
    ");
    
    $stmt->execute([
        'user_id' => $user_id,
        'action' => $action,
        'table_name' => $table,
        'record_id' => $record_id,
        'changes' => $changes,
        'ip_address' => $ip,
        'user_agent' => $user_agent
    ]);
}

/**
 * Redirect to a URL with a flash message
 * 
 * @param string $url The URL to redirect to
 * @param string|null $message The flash message
 * @param string $type The message type (success, error, warning, info)
 * @return void
 */
function redirect($url, $message = null, $type = 'success') {
    // Check if headers have already been sent
    if (headers_sent()) {
        // If headers are already sent, use JavaScript redirect
        echo '<script>window.location.href = "' . $url . '";</script>';
        echo '<noscript>';
        echo '<meta http-equiv="refresh" content="0;url=' . $url . '">';
        echo '</noscript>';
        exit();
    }
    
    if ($message) {
        flash_set('message', $message);
        flash_set('message_type', $type);
    }
    header('Location: ' . $url);
    exit();
}

/**
 * Paginate results
 * 
 * @param int $total Total number of items
 * @param int $limit Items per page
 * @param int $page Current page
 * @return array Pagination data
 */
function paginate($total, $limit, $page) {
    $total_pages = ceil($total / $limit);
    $page = max(1, min($page, $total_pages));
    $offset = ($page - 1) * $limit;
    
    return [
        'total' => $total,
        'total_pages' => $total_pages,
        'current_page' => $page,
        'limit' => $limit,
        'offset' => $offset,
        'prev_page' => $page > 1 ? $page - 1 : null,
        'next_page' => $page < $total_pages ? $page + 1 : null
    ];
}

/**
 * Generate a random token
 * 
 * @param int $length The length of the token
 * @return string The generated token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Check if a string is valid email
 * 
 * @param string $email The email to validate
 * @return bool True if valid, false otherwise
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Sanitize input data
 * 
 * @param string $data The data to sanitize
 * @return string The sanitized data
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Get the current URL
 * 
 * @return string The current URL
 */
function getCurrentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $uri = $_SERVER['REQUEST_URI'];
    return $protocol . '://' . $host . $uri;
}

/**
 * Check if the request is AJAX
 * 
 * @return bool True if AJAX request, false otherwise
 */
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}