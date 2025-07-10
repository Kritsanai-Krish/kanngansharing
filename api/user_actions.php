<?php
require_once '../core/initialize.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['status' => 'error', 'message' => 'Authentication is required.']);
    exit();
}

$action = $_POST['action'] ?? '';
$user_id = $_SESSION['user_id'];
$response = ['status' => 'error', 'message' => 'Invalid action specified.'];

try {
    if ($action === 'update_profile') {
        $pdo->beginTransaction();

        $update_fields = [];
        $params = [];

        // Handle profile information updates
        if (isset($_POST['full_name'])) {
            $update_fields[] = 'full_name = ?';
            $params[] = trim($_POST['full_name']);
        }
        if (isset($_POST['phone_number'])) {
            $update_fields[] = 'phone_number = ?';
            $params[] = trim($_POST['phone_number']);
        }
        if (isset($_POST['contact_other'])) {
            $update_fields[] = 'contact_other = ?';
            $params[] = trim($_POST['contact_other']);
        }

        // Handle password change
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';

        if (!empty($new_password)) {
            if (empty($current_password)) {
                throw new Exception('Please provide your current password to set a new one.');
            }

            // Verify current password
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($current_password, $user['password_hash'])) {
                throw new Exception('The current password you entered is incorrect.');
            }
            
            if (strlen($new_password) < 8) {
                 throw new Exception('New password must be at least 8 characters long.');
            }

            // Add new password hash to the update
            $update_fields[] = 'password_hash = ?';
            $params[] = password_hash($new_password, PASSWORD_DEFAULT);
        }

        if (!empty($update_fields)) {
            $sql = "UPDATE users SET " . implode(', ', $update_fields) . " WHERE id = ?";
            $params[] = $user_id;
            
            $update_stmt = $pdo->prepare($sql);
            $update_stmt->execute($params);

            create_log_entry($user_id, 'UPDATE_PROFILE', 'User updated their profile information.');
            $response = ['status' => 'success', 'message' => 'Your profile has been updated successfully.'];
        } else {
            $response = ['status' => 'info', 'message' => 'No new information was provided to update.'];
        }
        
        $pdo->commit();
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $response = ['status' => 'error', 'message' => 'A database error occurred during the update.'];
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $response = ['status' => 'error', 'message' => $e->getMessage()];
}

echo json_encode($response);