<?php
/**
 * Application Configuration
 * 
 * Global application settings and constants.
 */

// Application settings
define('APP_NAME', 'AVEC System');
define('APP_URL', 'http://localhost/avec_online');
define('APP_TIMEZONE', 'Africa/Lubumbashi');
define('APP_DEBUG', true);

// Set timezone
date_default_timezone_set(APP_TIMEZONE);

// Session settings
define('SESSION_NAME', 'AVEC_SESSION');
define('SESSION_LIFETIME', 3600);

// Language settings
define('DEFAULT_LANG', 'en');