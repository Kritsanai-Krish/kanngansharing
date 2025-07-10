<?php
require_once '../core/initialize.php';

check_access_level('admin');

try {
    $total_users_stmt = $pdo->query("SELECT COUNT(id) FROM users");
    $total_users = $total_users_stmt->fetchColumn();

    $pending_users_stmt = $pdo->query("SELECT COUNT(id) FROM users WHERE status = 'pending'");
    $pending_users = $pending_users_stmt->fetchColumn();

    $active_groups_stmt = $pdo->query("SELECT COUNT(id) FROM groups WHERE status = 'open'");
    $active_groups = $active_groups_stmt->fetchColumn();

    $new_reports_stmt = $pdo->query("SELECT COUNT(id) FROM reports WHERE status = 'new'");
    $new_reports = $new_reports_stmt->fetchColumn();

} catch (PDOException $e) {
    die("Error fetching dashboard data: " . $e->getMessage());
}

$page_title = "Admin Dashboard";
require_once '../templates/header.php';
?>

<div class="admin-container">
    <header class="admin-header">
        <h1><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
        <p>ยินดีต้อนรับ, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
    </header>

    <section class="dashboard-stats">
        <div class="stat-card">
            <i class="fas fa-users"></i>
            <div class="stat-info">
                <h3>ผู้ใช้ทั้งหมด</h3>
                <p><?php echo $total_users; ?></p>
            </div>
        </div>
        <div class="stat-card pending">
            <i class="fas fa-user-clock"></i>
            <div class="stat-info">
                <h3>รออนุมัติ</h3>
                <p><?php echo $pending_users; ?></p>
            </div>
        </div>
        <div class="stat-card active-groups">
            <i class="fas fa-object-group"></i>
            <div class="stat-info">
                <h3>กลุ่มที่เปิดอยู่</h3>
                <p><?php echo $active_groups; ?></p>
            </div>
        </div>
        <div class="stat-card reports">
            <i class="fas fa-flag"></i>
            <div class="stat-info">
                <h3>เรื่องร้องเรียนใหม่</h3>
                <p><?php echo $new_reports; ?></p>
            </div>
        </div>
    </section>

    <section class="admin-menu">
        <h2>เมนูการจัดการ</h2>
        <div class="menu-grid">
            <a href="manage_users.php" class="menu-item">
                <i class="fas fa-users-cog"></i>
                <span>จัดการผู้ใช้</span>
            </a>
            <a href="manage_groups.php" class="menu-item">
                <i class="fas fa-list-alt"></i>
                <span>จัดการกลุ่มหาร</span>
            </a>
            <a href="view_logs.php" class="menu-item">
                <i class="fas fa-history"></i>
                <span>ดูบันทึก Log</span>
            </a>
            <a href="site_settings.php" class="menu-item">
                <i class="fas fa-cogs"></i>
                <span>ตั้งค่าเว็บไซต์</span>
            </a>
             <a href="../index.php" class="menu-item">
                <i class="fas fa-home"></i>
                <span>กลับสู่หน้าหลัก</span>
            </a>
            <a href="../logout.php" class="menu-item logout">
                <i class="fas fa-sign-out-alt"></i>
                <span>ออกจากระบบ</span>
            </a>
        </div>
    </section>

</div>

<?php
require_once '../templates/footer.php';
?>