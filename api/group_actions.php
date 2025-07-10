<?php
require_once '../core/initialize.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['status' => 'error', 'message' => 'จำเป็นต้องเข้าสู่ระบบก่อน']);
    exit();
}

$action = $_POST['action'] ?? '';
$user_id = $_SESSION['user_id'];
$group_id = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
$response = ['status' => 'error', 'message' => 'การกระทำไม่ถูกต้อง'];

if ($group_id === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Group ID ไม่ถูกต้อง']);
    exit();
}

try {
    $pdo->beginTransaction();

    switch ($action) {
        case 'join_group':
            // Lock the group row to prevent race conditions
            $group_stmt = $pdo->prepare("SELECT * FROM groups WHERE id = ? FOR UPDATE");
            $group_stmt->execute([$group_id]);
            $group = $group_stmt->fetch(PDO::FETCH_ASSOC);

            if (!$group) {
                $response['message'] = 'ไม่พบกลุ่มที่ต้องการ';
                break;
            }
            if ($group['status'] !== 'open') {
                $response['message'] = 'กลุ่มนี้เต็มหรือปิดรับแล้ว';
                break;
            }

            // Check if user is already in the group
            $member_stmt = $pdo->prepare("SELECT COUNT(*) FROM group_members WHERE group_id = ? AND user_id = ?");
            $member_stmt->execute([$group_id, $user_id]);
            if ($member_stmt->fetchColumn() > 0) {
                $response['message'] = 'คุณเป็นสมาชิกของกลุ่มนี้อยู่แล้ว';
                break;
            }
            
            // Check if group is full
            $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM group_members WHERE group_id = ? AND join_status IN ('paid', 'reserved')");
            $count_stmt->execute([$group_id]);
            $current_members = $count_stmt->fetchColumn();

            if ($current_members >= $group['total_slots']) {
                 $response['message'] = 'ขออภัย กลุ่มนี้เต็มแล้ว';
                 break;
            }

            // Add user to group
            $join_status = $group['auto_approve'] ? 'reserved' : 'pending_approval';
            $reserved_until = $group['auto_approve'] ? date('Y-m-d H:i:s', time() + 300) : null; // 5 minutes reservation

            $insert_stmt = $pdo->prepare("INSERT INTO group_members (group_id, user_id, join_status, reserved_until) VALUES (?, ?, ?, ?)");
            $insert_stmt->execute([$group_id, $user_id, $join_status, $reserved_until]);
            
            // If group is now full, update its status
            if (($current_members + 1) >= $group['total_slots']) {
                $update_group_stmt = $pdo->prepare("UPDATE groups SET status = 'full' WHERE id = ?");
                $update_group_stmt->execute([$group_id]);
            }
            
            create_log_entry($user_id, 'JOIN_GROUP', "User requested to join/reserved group ID: {$group_id}");
            $response = ['status' => 'success', 'message' => 'คุณได้จองคิวในกลุ่มเรียบร้อยแล้ว! กรุณาชำระเงินและอัปโหลดสลิป', 'join_status' => $join_status];
            break;

        case 'leave_group':
            $delete_stmt = $pdo->prepare("DELETE FROM group_members WHERE group_id = ? AND user_id = ?");
            $deleted = $delete_stmt->execute([$group_id, $user_id]);

            if ($deleted) {
                // Check if the group was full and now has a spot
                $update_group_stmt = $pdo->prepare("UPDATE groups SET status = 'open' WHERE id = ? AND status = 'full'");
                $update_group_stmt->execute([$group_id]);
                create_log_entry($user_id, 'LEAVE_GROUP', "User left group ID: {$group_id}");
                $response = ['status' => 'success', 'message' => 'คุณได้ออกจากกลุ่มเรียบร้อยแล้ว'];
            } else {
                $response['message'] = 'ไม่สามารถออกจากกลุ่มได้';
            }
            break;

        case 'approve_payment':
            $member_user_id = isset($_POST['member_id']) ? (int)$_POST['member_id'] : 0;
            // Authorization: check if current user is the group creator
            $group_creator_stmt = $pdo->prepare("SELECT creator_id FROM groups WHERE id = ?");
            $group_creator_stmt->execute([$group_id]);
            $creator_id = $group_creator_stmt->fetchColumn();

            if ($creator_id != $user_id) {
                $response['message'] = 'คุณไม่มีสิทธิ์อนุมัติสมาชิกในกลุ่มนี้';
                break;
            }

            $update_stmt = $pdo->prepare("UPDATE group_members SET join_status = 'paid', reserved_until = NULL WHERE group_id = ? AND user_id = ? AND join_status = 'pending_approval'");
            $updated = $update_stmt->execute([$group_id, $member_user_id]);

            if ($updated) {
                 create_log_entry($user_id, 'APPROVE_PAYMENT', "Representative approved payment for user ID {$member_user_id} in group ID {$group_id}");
                 $response = ['status' => 'success', 'message' => 'อนุมัติการชำระเงินเรียบร้อยแล้ว'];
            } else {
                 $response['message'] = 'ไม่สามารถอนุมัติได้ อาจมีการอนุมัติไปแล้วหรือข้อมูลไม่ถูกต้อง';
            }
            break;

        case 'kick_member':
            $member_user_id = isset($_POST['member_id']) ? (int)$_POST['member_id'] : 0;
            // Authorization: check if current user is the group creator
            $group_creator_stmt = $pdo->prepare("SELECT creator_id FROM groups WHERE id = ?");
            $group_creator_stmt->execute([$group_id]);
            $creator_id = $group_creator_stmt->fetchColumn();

             if ($creator_id != $user_id) {
                $response['message'] = 'คุณไม่มีสิทธิ์เตะสมาชิกในกลุ่มนี้';
                break;
            }

            $delete_stmt = $pdo->prepare("DELETE FROM group_members WHERE group_id = ? AND user_id = ?");
            $deleted = $delete_stmt->execute([$group_id, $member_user_id]);
            
            if ($deleted) {
                $update_group_stmt = $pdo->prepare("UPDATE groups SET status = 'open' WHERE id = ? AND status = 'full'");
                $update_group_stmt->execute([$group_id]);
                create_log_entry($user_id, 'KICK_MEMBER', "Representative kicked user ID {$member_user_id} from group ID {$group_id}");
                $response = ['status' => 'success', 'message' => 'ลบสมาชิกออกจากกลุ่มแล้ว'];
            } else {
                $response['message'] = 'ไม่สามารถลบสมาชิกได้';
            }
            break;
    }

    $pdo->commit();

} catch (PDOException $e) {
    $pdo->rollBack();
    $response = ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
}

echo json_encode($response);