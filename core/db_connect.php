<?php
// --- Database Configuration ---
// IMPORTANT: Change these values to match your database settings.
define('DB_HOST', 'localhost');
define('DB_NAME', 'avas'); // The database name from our schema plan.
define('DB_USER', 'root');             // Your MySQL username.
define('DB_PASS', '');                 // Your MySQL password.

// --- PDO Connection Options ---
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";

$options = [
    // Throw an exception if an error occurs.
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    // Fetch rows as associative arrays.
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    // Use native prepared statements for better security.
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// --- Create PDO Instance ---
try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // If the connection fails, stop the script and show an error.
    // In a real production environment, you would log this error and show a generic message.
    http_response_code(500);
    die("Database connection failed. Please check your configuration. Error: " . $e->getMessage());
}