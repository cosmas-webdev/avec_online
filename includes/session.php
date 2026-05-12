<?php
/**
 * Session Management
 * 
 * Handles session initialization, data management, and flash messages.
 * Default language: English
 */

require_once dirname(__DIR__) . '/config/app.php';

// Initialize session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

/**
 * Get a session value
 * 
 * @param string $key The session key
 * @param mixed $default Default value if key doesn't exist
 * @return mixed The session value or default
 */
function session_get($key, $default = null) {
    return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
}

/**
 * Set a session value
 * 
 * @param string $key The session key
 * @param mixed $value The value to store
 * @return void
 */
function session_set($key, $value) {
    $_SESSION[$key] = $value;
}

/**
 * Remove a session key
 * 
 * @param string $key The session key to remove
 * @return void
 */
function session_remove($key) {
    unset($_SESSION[$key]);
}

/**
 * Check if a session key exists
 * 
 * @param string $key The session key to check
 * @return bool True if key exists, false otherwise
 */
function session_has($key) {
    return isset($_SESSION[$key]);
}

/**
 * Destroy all session data
 * 
 * @return void
 */
function session_destroy_all() {
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    
    session_destroy();
}

/**
 * Get a flash message and remove it from session
 * 
 * @param string $key The flash message key
 * @return string|null The flash message or null if not found
 */
function flash_get($key) {
    if (isset($_SESSION['flash_' . $key])) {
        $message = $_SESSION['flash_' . $key];
        unset($_SESSION['flash_' . $key]);
        return $message;
    }
    return null;
}

/**
 * Set a flash message
 * 
 * @param string $key The flash message key
 * @param string $message The message to store
 * @return void
 */
function flash_set($key, $message) {
    $_SESSION['flash_' . $key] = $message;
}

/**
 * Get the current application language
 * 
 * @return string The language code (en, fr, es, sw)
 */
function get_language() {
    return isset($_SESSION['language']) ? $_SESSION['language'] : DEFAULT_LANG;
}

/**
 * Set the application language
 * 
 * @param string $lang The language code (en, fr, es, sw)
 * @return void
 */
function set_language($lang) {
    $allowed_languages = array('en', 'fr', 'es', 'sw');
    if (in_array($lang, $allowed_languages)) {
        $_SESSION['language'] = $lang;
    }
}

/**
 * Translate a string based on current language
 * 
 * @param string $key The translation key
 * @param array $params Optional parameters for translation
 * @return string The translated string
 */
function __($key, $params = array()) {
    $lang = get_language();
    $translations = include dirname(__DIR__) . "/lang/{$lang}.php";
    $text = isset($translations[$key]) ? $translations[$key] : $key;
    foreach ($params as $param => $value) {
        $text = str_replace("{{$param}}", $value, $text);
    }
    return $text;
}