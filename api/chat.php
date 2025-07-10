<?php
require_once '../core/initialize.php';

// Set header to return JSON content
header('Content-Type: application/json');

// All chat actions require a user to be logged in
if (!is_logged_in()) {
    echo json_encode(['status' => 'error', 'message' => 'Authentication required.']);
    exit();
}

$action = $_POST['action'] ?? '';
$user_id = $_SESSION['user_id'];
$response = ['status' => 'error', 'message' => 'Invalid action.'];

try {
    switch ($action) {
        case 'send':
            $message = trim($_POST['message'] ?? '');
            $group_id = !empty($_POST['group_id']) ? (int)$_POST['group_id'] : null;

            if (empty($message)) {
                $response['message'] = 'Message cannot be empty.';
                break;
            }

            // Authorization: Check if user is in the group before allowing to send
            if ($group_id !== null) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM group_members WHERE group_id = ? AND user_id = ? AND join_status = 'paid'");
                $stmt->execute([$group_id, $user_id]);
                if ($stmt->fetchColumn() == 0) {
                    $response['message'] = 'Access denied to this group chat.';
                    break;
                }
            }
            
            // Insert the message
            $stmt = $pdo->prepare("INSERT INTO chat_messages (group_id, user_id, message_text) VALUES (?, ?, ?)");
            if ($stmt->execute([$group_id, $user_id, $message])) {
                $response = ['status' => 'success'];
            } else {
                $response['message'] = 'Failed to send message.';
            }
            break;

        case 'fetch':
            $group_id = !empty($_POST['group_id']) ? (int)$_POST['group_id'] : null;
            $last_message_id = isset($_POST['last_message_id']) ? (int)$_POST['last_message_id'] : 0;

            // Authorization: Check if user can view the chat
            if ($group_id !== null) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM group_members WHERE group_id = ? AND user_id = ? AND join_status = 'paid'");
                $stmt->execute([$group_id, $user_id]);
                if ($stmt->fetchColumn() == 0) {
                    $response['message'] = 'Access denied to this group chat.';
                    break;
                }
            }

            // Prepare query to fetch new messages
            $sql = "SELECT c.id, c.message_text, c.sent_at, u.username, u.role 
                    FROM chat_messages c 
                    JOIN users u ON c.user_id = u.id 
                    WHERE c.id > ?";

            $params = [$last_message_id];

            if ($group_id === null) {
                $sql .= " AND c.group_id IS NULL";
            } else {
                $sql .= " AND c.group_id = ?";
                $params[] = $group_id;
            }

            $sql .= " ORDER BY c.sent_at ASC LIMIT 100";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response = ['status' => 'success', 'messages' => $messages];
            break;
    }
} catch (PDOException $e) {
    // In a production environment, you would log this error instead of echoing it.
    $response = ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
}

echo json_encode($response);