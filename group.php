<?php
require_once 'core/initialize.php';

// 1. Get Group ID and fetch data
$group_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($group_id === 0) {
    redirect('groups.php');
}

$stmt = $pdo->prepare("
    SELECT g.*, u.username AS creator_username 
    FROM groups g 
    JOIN users u ON g.creator_id = u.id 
    WHERE g.id = ?
");
$stmt->execute([$group_id]);
$group = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$group) {
    // Group not found
    redirect('groups.php');
}

// 2. Determine user's status relative to the group
$user_status = [
    'is_logged_in' => is_logged_in(),
    'is_creator' => false,
    'join_status' => null, // e.g., 'paid', 'pending_approval', 'reserved'
    'payment_slip' => null
];

if ($user_status['is_logged_in']) {
    $user_id = $_SESSION['user_id'];
    $user_status['is_creator'] = ($group['creator_id'] == $user_id);

    $member_stmt = $pdo->prepare("SELECT join_status, payment_slip FROM group_members WHERE group_id = ? AND user_id = ?");
    $member_stmt->execute([$group_id, $user_id]);
    $member_info = $member_stmt->fetch(PDO::FETCH_ASSOC);
    if ($member_info) {
        $user_status['join_status'] = $member_info['join_status'];
        $user_status['payment_slip'] = $member_info['payment_slip'];
    }
}

// 3. Fetch member list (visible only to paid members and creator)
$members = [];
if ($user_status['is_creator'] || $user_status['join_status'] === 'paid') {
    $members_stmt = $pdo->prepare("
        SELECT u.id, u.username, gm.join_status, gm.payment_slip
        FROM group_members gm
        JOIN users u ON gm.user_id = u.id
        WHERE gm.group_id = ?
        ORDER BY gm.joined_at ASC
    ");
    $members_stmt->execute([$group_id]);
    $members = $members_stmt->fetchAll(PDO::FETCH_ASSOC);
}


$page_title = htmlspecialchars($group['ai_name']);
require_once 'templates/header.php';

// Load security functions for decryption
require_once 'core/security.php';
?>

<div class="container group-page">
    <div class="group-main-content">
        <header class="group-header">
            <h1><?php echo htmlspecialchars($group['ai_name']); ?></h1>
            <p class="group-creator">สร้างโดย: <strong><?php echo htmlspecialchars($group['creator_username']); ?></strong></p>
        </header>

        <section class="group-details-grid">
            <div class="detail-card">
                <h3><i class="fas fa-info-circle"></i> รายละเอียด</h3>
                <p><?php echo nl2br(htmlspecialchars($group['description'])); ?></p>
            </div>
            <div class="detail-card">
                <h3><i class="fas fa-money-bill-wave"></i> ราคา</h3>
                <p class="price">฿<?php echo number_format($group['price_per_slot'], 2); ?> / เดือน</p>
                <?php if ($group['pricing_model'] === 'pro_rata'): ?>
                    <small class="pro-rata-note">ราคาสามารถจ่ายตามวันที่เหลือได้</small>
                <?php endif; ?>
            </div>
            <div class="detail-card">
                <h3><i class="fas fa-users"></i> สมาชิก</h3>
                <p><?php echo count($members); ?> / <?php echo htmlspecialchars($group['total_slots']); ?> คน</p>
            </div>
             <div class="detail-card">
                <h3><i class="fas fa-calendar-times"></i> หมดอายุ</h3>
                <p><?php echo date("d F Y", strtotime($group['expiry_date'])); ?></p>
            </div>
        </section>

        <?php if ($user_status['is_creator'] || $user_status['join_status'] === 'paid'): ?>
        <section class="credentials-box">
            <h2><i class="fas fa-key"></i> ข้อมูลสำหรับเข้าใช้งาน</h2>
            <?php
                $ai_user = decrypt_data($group['ai_username']);
                $ai_pass = decrypt_data($group['ai_password_encrypted']);
            ?>
            <div class="credential-item">
                <strong>Login URL:</strong> <a href="<?php echo htmlspecialchars($group['ai_login_url']); ?>" target="_blank"><?php echo htmlspecialchars($group['ai_login_url']); ?></a>
            </div>
            <div class="credential-item">
                <strong>Username:</strong> <span><?php echo htmlspecialchars($ai_user); ?></span>
            </div>
            <div class="credential-item">
                <strong>Password:</strong> <span><?php echo htmlspecialchars($ai_pass); ?></span>
            </div>
        </section>
        <?php else: ?>
        <section class="credentials-locked">
             <h2><i class="fas fa-lock"></i> ข้อมูลสำหรับเข้าใช้งาน</h2>
             <p>กรุณาเข้าร่วมและชำระเงินเพื่อดูข้อมูลสำหรับเข้าใช้งาน</p>
        </section>
        <?php endif; ?>

        <?php if (!empty($members)): ?>
        <section class="member-list-box">
            <h2><i class="fas fa-users"></i> รายชื่อสมาชิก</h2>
            <div class="content-table">
                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Status</th>
                            <?php if ($user_status['is_creator']) echo '<th>Action</th>'; ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($members as $member): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($member['username']); ?></td>
                            <td><span class="status-badge status-<?php echo $member['join_status']; ?>"><?php echo htmlspecialchars($member['join_status']); ?></span></td>
                            <?php if ($user_status['is_creator']): ?>
                            <td class="action-links">
                                <?php if ($member['join_status'] === 'pending_approval' && $member['id'] != $user_id): ?>
                                    <button class="btn btn-success btn-sm" data-action="approve_payment" data-group-id="<?php echo $group_id; ?>" data-member-id="<?php echo $member['id']; ?>">อนุมัติ</button>
                                <?php endif; ?>
                                <?php if ($member['id'] != $user_id): ?>
                                     <button class="btn btn-danger btn-sm" data-action="kick_member" data-group-id="<?php echo $group_id; ?>" data-member-id="<?php echo $member['id']; ?>">เตะ</button>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php endif; ?>

    </div>

    <aside class="group-sidebar">
        <div class="sidebar-box action-box">
            <h3>Action</h3>
            <?php if (!$user_status['is_logged_in']): ?>
                <a href="/login.php?redirect=group.php?id=<?php echo $group_id; ?>" class="btn btn-primary btn-block">เข้าสู่ระบบเพื่อเข้าร่วม</a>
            <?php elseif ($user_status['is_creator']): ?>
                <p>คุณคือผู้สร้างกลุ่มนี้</p>
                <a href="/edit_group.php?id=<?php echo $group_id; ?>" class="btn btn-secondary btn-block">แก้ไขกลุ่ม</a>
            <?php elseif ($user_status['join_status'] === 'paid'): ?>
                <p>คุณเป็นสมาชิกของกลุ่มนี้แล้ว</p>
                <button class="btn btn-danger btn-block" data-action="leave_group" data-group-id="<?php echo $group_id; ?>">ออกจากกลุ่ม</button>
            <?php elseif (in_array($user_status['join_status'], ['reserved', 'pending_approval'])): ?>
                 <p>คุณได้จองคิวแล้ว กรุณาชำระเงิน</p>
            <?php elseif ($group['status'] === 'open'): ?>
                 <button class="btn btn-primary btn-block" data-action="join_group" data-group-id="<?php echo $group_id; ?>">เข้าร่วมกลุ่ม</button>
            <?php else: ?>
                 <p class="text-muted">กลุ่มนี้เต็มหรือปิดรับแล้ว</p>
            <?php endif; ?>
        </div>

        <?php if ($user_status['is_logged_in'] && !$user_status['is_creator'] && in_array($user_status['join_status'], ['reserved', 'pending_approval'])): ?>
        <div class="sidebar-box payment-box">
            <h3><i class="fas fa-qrcode"></i> Payment</h3>
            <p>โอนเงินมาที่: <strong><?php echo htmlspecialchars($group['payment_account_name']); ?></strong></p>
            <img src="uploads/qrcodes/<?php echo htmlspecialchars($group['payment_qr_code']); ?>" alt="QR Code">
            
            <?php if (empty($user_status['payment_slip'])): ?>
            <form id="payment-slip-form" class="payment-form" enctype="multipart/form-data">
                <input type="hidden" name="action" value="submit_payment_slip">
                <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
                <div class="form-group">
                    <label for="payment_slip">อัปโหลดสลิป</label>
                    <input type="file" name="payment_slip" id="payment_slip" class="form-control-file" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">ยืนยันการชำระเงิน</button>
            </form>
            <?php else: ?>
                <div class="alert alert-success">คุณอัปโหลดสลิปแล้ว รอการอนุมัติ</div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($user_status['is_creator'] || $user_status['join_status'] === 'paid'): ?>
        <div class="sidebar-box chat-container">
             <h3><i class="fas fa-comments"></i> Group Chat</h3>
             <div id="chat-box" class="chat-box" data-group-id="<?php echo $group_id; ?>" data-user-id="<?php echo $_SESSION['user_id']; ?>">
                 </div>
             <form id="chat-form" class="chat-form">
                 <textarea id="chat-message-input" placeholder="Type your message..." required></textarea>
                 <button type="submit"><i class="fas fa-paper-plane"></i></button>
             </form>
        </div>
        <?php endif; ?>
    </aside>
</div>

<?php
require_once 'templates/footer.php';
?>