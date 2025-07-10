<?php
require_once '../core/initialize.php';

check_access_level('admin');

// Handle Delete Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_group'])) {
    $group_id_to_delete = $_POST['group_id'];

    if (empty($group_id_to_delete)) {
        // Optional: Set an error message in session
    } else {
        // For a real-world app, you would also delete related data:
        // - group_members
        // - chat_messages
        // - reports
        // - reviews
        // Using a database transaction is highly recommended here.

        $delete_stmt = $pdo->prepare("DELETE FROM groups WHERE id = ?");
        $delete_stmt->execute([$group_id_to_delete]);

        // Create a log entry for this action
        create_log_entry($_SESSION['user_id'], 'DELETE_GROUP', "Admin deleted group ID: {$group_id_to_delete}");

        // Redirect to the same page to prevent form resubmission
        header("Location: manage_groups.php");
        exit();
    }
}


// Fetch all groups with creator's username
$stmt = $pdo->query("
    SELECT
        g.id,
        g.ai_name,
        g.status,
        g.total_slots,
        g.expiry_date,
        u.username AS creator_username,
        (SELECT COUNT(id) FROM group_members WHERE group_id = g.id AND join_status = 'paid') AS current_members
    FROM
        groups g
    JOIN
        users u ON g.creator_id = u.id
    ORDER BY
        g.id DESC
");

$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);


$page_title = "Manage Groups";
require_once '../templates/header.php';
?>

<div class="admin-container">
    <header class="admin-header">
        <h1><i class="fas fa-list-alt"></i> จัดการกลุ่มหารทั้งหมด</h1>
        <p>คุณสามารถดู, แก้ไข, หรือลบกลุ่มทั้งหมดในระบบได้จากที่นี่</p>
    </header>

    <section class="content-table">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ชื่อ AI</th>
                    <th>ผู้สร้าง (ตัวแทน)</th>
                    <th>สมาชิก</th>
                    <th>สถานะ</th>
                    <th>หมดอายุ</th>
                    <th>การกระทำ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($groups)): ?>
                    <tr>
                        <td colspan="7" class="text-center">ยังไม่มีกลุ่มในระบบ</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($groups as $group): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($group['id']); ?></td>
                            <td><?php echo htmlspecialchars($group['ai_name']); ?></td>
                            <td><?php echo htmlspecialchars($group['creator_username']); ?></td>
                            <td><?php echo htmlspecialchars($group['current_members']); ?> / <?php echo htmlspecialchars($group['total_slots']); ?></td>
                            <td><span class="status-badge status-<?php echo htmlspecialchars($group['status']); ?>"><?php echo ucfirst(htmlspecialchars($group['status'])); ?></span></td>
                            <td><?php echo date("d M Y", strtotime($group['expiry_date'])); ?></td>
                            <td class="action-links">
                                <a href="../group.php?id=<?php echo $group['id']; ?>" class="action-btn view" title="ดูหน้ารายละเอียดกลุ่ม"><i class="fas fa-eye"></i></a>
                                <a href="edit_group.php?id=<?php echo $group['id']; ?>" class="action-btn edit" title="แก้ไขข้อมูลกลุ่ม"><i class="fas fa-edit"></i></a>
                                <form method="POST" action="manage_groups.php" onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบกลุ่มนี้? การกระทำนี้ไม่สามารถย้อนกลับได้');" style="display:inline;">
                                    <input type="hidden" name="group_id" value="<?php echo $group['id']; ?>">
                                    <button type="submit" name="delete_group" class="action-btn delete" title="ลบกลุ่ม">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
</div>

<?php
require_once '../templates/footer.php';
?>