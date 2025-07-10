<?php
require_once '../core/initialize.php';

check_access_level('admin');

// Handle user actions from POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = $_POST['user_id'] ?? 0;

    // Prevent admin from modifying their own account
    if ($user_id == $_SESSION['user_id']) {
        // You can set a session flash message here to inform the admin
        header("Location: manage_users.php");
        exit();
    }

    switch ($action) {
        case 'change_status':
            $new_status = $_POST['status'];
            $allowed_statuses = ['pending', 'active', 'banned'];
            if (in_array($new_status, $allowed_statuses)) {
                $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
                $stmt->execute([$new_status, $user_id]);
                create_log_entry($_SESSION['user_id'], 'CHANGE_USER_STATUS', "Admin changed user ID {$user_id} status to '{$new_status}'");
            }
            break;

        case 'change_role':
            $new_role = $_POST['role'];
            $allowed_roles = ['member', 'representative']; // Admin role cannot be set from here
            if (in_array($new_role, $allowed_roles)) {
                $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                $stmt->execute([$new_role, $user_id]);
                create_log_entry($_SESSION['user_id'], 'CHANGE_USER_ROLE', "Admin changed user ID {$user_id} role to '{$new_role}'");
            }
            break;

        case 'delete_user':
            // This is a destructive action. In a real system, consider soft deletes.
            // You might also need to handle cascading deletes for user's content (groups, etc.)
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            create_log_entry($_SESSION['user_id'], 'DELETE_USER', "Admin permanently deleted user ID: {$user_id}");
            break;
    }

    header("Location: manage_users.php");
    exit();
}

// Fetch all users from the database
$users_stmt = $pdo->query("SELECT id, username, email, full_name, role, status, created_at FROM users ORDER BY id ASC");
$users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Manage Users";
require_once '../templates/header.php';
?>

<div class="admin-container">
    <header class="admin-header">
        <h1><i class="fas fa-users-cog"></i> จัดการผู้ใช้</h1>
        <p>อนุมัติ, แบน, เปลี่ยนยศ หรือลบผู้ใช้ในระบบ</p>
    </header>

    <section class="content-table">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Registered</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr class="<?php echo ($user['id'] == $_SESSION['user_id']) ? 'current-admin-row' : ''; ?>">
                        <td><?php echo htmlspecialchars($user['id']); ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <?php if ($user['role'] === 'admin'): ?>
                                <span class="role-badge role-admin">Admin</span>
                            <?php else: ?>
                                <form method="POST" action="manage_users.php" class="action-form">
                                    <input type="hidden" name="action" value="change_role">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <select name="role" onchange="this.form.submit()">
                                        <option value="member" <?php echo ($user['role'] === 'member') ? 'selected' : ''; ?>>Member</option>
                                        <option value="representative" <?php echo ($user['role'] === 'representative') ? 'selected' : ''; ?>>Representative</option>
                                    </select>
                                </form>
                            <?php endif; ?>
                        </td>
                        <td>
                           <?php if ($user['role'] === 'admin'): ?>
                                <span class="status-badge status-active">Active</span>
                           <?php else: ?>
                                <form method="POST" action="manage_users.php" class="action-form">
                                    <input type="hidden" name="action" value="change_status">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <select name="status" onchange="this.form.submit()" class="status-select status-<?php echo $user['status']; ?>">
                                        <option value="pending" <?php echo ($user['status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                                        <option value="active" <?php echo ($user['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                        <option value="banned" <?php echo ($user['status'] === 'banned') ? 'selected' : ''; ?>>Banned</option>
                                    </select>
                                </form>
                           <?php endif; ?>
                        </td>
                        <td><?php echo date("d M Y", strtotime($user['created_at'])); ?></td>
                        <td class="action-links">
                            <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                <span>(You)</span>
                            <?php else: ?>
                                <form method="POST" action="manage_users.php" onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบผู้ใช้นี้อย่างถาวร?');" style="display:inline;">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="action-btn delete" title="ลบผู้ใช้">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</div>

<?php
require_once '../templates/footer.php';
?>