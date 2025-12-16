<?php
/**
 * Database Connection Setup (PDO)
 * Configuration for connecting to the MySQL database.
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'e_grampanchayat');
define('DB_USER', 'root'); // <-- Corrected syntax here
define('DB_PASS', '');     // <-- Often an empty string for local setups

// Connection parameters
$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Stop execution and display a connection error message
    die("ERROR: Could not connect to the database. " . $e->getMessage());
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}