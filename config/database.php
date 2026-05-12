<?php
/**
 * Database Configuration
 * 
 * Configure your database connection settings here.
 */

// Database credentials
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'avec_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// PDO options for better performance and security
define('DB_OPTIONS', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false
]);