<?php

/**
 * Starts a session with secure settings.
 */
function start_secure_session() {
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => $cookieParams['lifetime'],
        'path' => $cookieParams['path'],
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => isset($_SERVER['HTTPS']), // Set to true if using HTTPS
        'httponly' => true,                  // Prevent JavaScript access to session cookie
        'samesite' => 'Lax'
    ]);
    session_start();
}

/**
 * Checks if a user is currently logged in.
 * @return bool True if logged in, false otherwise.
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Redirects the user to a specified URL and terminates the script.
 * @param string $url The URL to redirect to.
 */
function redirect($url) {
    header("Location: {$url}");
    exit();
}

/**
 * Checks if the current user has the required access level (role).
 * If not, redirects them to the login or home page.
 * @param string $required_role The role required to access the page (e.g., 'admin').
 */
function check_access_level($required_role) {
    if (!is_logged_in()) {
        redirect('../login.php');
    }
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $required_role) {
        // Optional: create a flash message saying "Access Denied"
        redirect('../index.php');
    }
}

/**
 * Gets the user's real IP address, considering proxies.
 * @return string The user's IP address.
 */
function get_ip_address() {
    $keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                    return $ip;
                }
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
}

/**
 * Creates a new entry in the action_logs table.
 * @param PDO $pdo The PDO database connection object.
 * @param int|null $user_id The ID of the user performing the action.
 * @param string $action A short code for the action (e.g., 'LOGIN', 'CREATE_GROUP').
 * @param string $details A detailed description of the action.
 */
function create_log_entry($user_id, $action, $details) {
    global $pdo; // Use the global PDO object from db_connect.php
    $ip_address = get_ip_address();
    
    $sql = "INSERT INTO action_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $action, $details, $ip_address]);
    } catch (PDOException $e) {
        // In a real application, handle this error more gracefully (e.g., log to a file).
        // For now, we let it fail silently to not interrupt user flow.
    }
}

/**
 * Retrieves a setting value from the database.
 * Uses a static cache to prevent multiple queries for the same setting in one request.
 * @param PDO $pdo The PDO database connection object.
 * @param string $setting_key The name of the setting to retrieve.
 * @return string|null The value of the setting, or null if not found.
 */
function get_setting($setting_key) {
    global $pdo;
    static $settings_cache = [];

    if (isset($settings_cache[$setting_key])) {
        return $settings_cache[$setting_key];
    }

    $sql = "SELECT setting_value FROM site_settings WHERE setting_key = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$setting_key]);
    $result = $stmt->fetchColumn();
    
    $settings_cache[$setting_key] = $result;
    return $result;
}

/**
 * Updates or creates a setting in the database.
 * @param PDO $pdo The PDO database connection object.
 * @param string $setting_key The name of the setting.
 * @param string $setting_value The new value for the setting.
 */
function update_setting($setting_key, $setting_value) {
    global $pdo;
    // This query will insert a new row if the key doesn't exist,
    // or update the existing row if the key already exists.
    // The 'setting_key' column MUST have a UNIQUE index for this to work.
    $sql = "INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE setting_value = ?";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$setting_key, $setting_value, $setting_value]);
}