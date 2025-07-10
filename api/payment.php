<?php
require_once '../core/initialize.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['status' => 'error', 'message' => 'Authentication is required.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$group_id = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

if ($group_id === 0 || !isset($_FILES['payment_slip'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing group ID or payment slip file.']);
    exit();
}

try {
    // 1. Authorize: Check if the user has a pending spot in the group
    $stmt = $pdo->prepare("SELECT join_status FROM group_members WHERE group_id = ? AND user_id = ?");
    $stmt->execute([$group_id, $user_id]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$member) {
        throw new Exception('You are not a member of this group.');
    }
    if (!in_array($member['join_status'], ['reserved', 'pending_approval'])) {
        throw new Exception('No pending payment slot found, or payment has already been approved.');
    }

    // 2. Handle File Upload
    $file = $_FILES['payment_slip'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error during file upload. Please try again.');
    }

    $max_file_size = 5 * 1024 * 1024; // 5 MB
    if ($file['size'] > $max_file_size) {
        throw new Exception('File is too large. Maximum size is 5 MB.');
    }

    $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];
    $file_mime_type = mime_content_type($file['tmp_name']);
    if (!in_array($file_mime_type, $allowed_mime_types)) {
        throw new Exception('Invalid file type. Only JPG, PNG, and GIF are allowed.');
    }

    $upload_dir = '../uploads/slips/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = 'slip_' . $group_id . '_' . $user_id . '_' . time() . '.' . $file_extension;
    $target_path = $upload_dir . $new_filename;

    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
        throw new Exception('Failed to save the uploaded file.');
    }

    // 3. Update Database
    $pdo->beginTransaction();

    $update_stmt = $pdo->prepare(
        "UPDATE group_members 
         SET payment_slip = ?, join_status = 'pending_approval', reserved_until = NULL 
         WHERE group_id = ? AND user_id = ?"
    );
    
    $update_stmt->execute([$new_filename, $group_id, $user_id]);

    create_log_entry($user_id, 'UPLOAD_SLIP', "User uploaded payment slip '{$new_filename}' for group ID: {$group_id}");
    
    $pdo->commit();

    $response = ['status' => 'success', 'message' => 'Payment slip uploaded successfully. Please wait for approval from the group leader.'];

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // In production, log the detailed error, don't show it to the user.
    $response = ['status' => 'error', 'message' => 'A database error occurred.'];

} catch (Exception $e) {
     if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response = ['status' => 'error', 'message' => $e->getMessage()];
}

echo json_encode($response);