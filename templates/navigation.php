<nav class="main-nav">
    <ul>
        <?php if (is_logged_in()): ?>
            <li><a href="../groups.php">กลุ่มทั้งหมด</a></li>
            
            <?php if ($_SESSION['role'] === 'representative'): ?>
                <li><a href="../create_group.php">สร้างกลุ่มหาร</a></li>
            <?php endif; ?>

            <?php if ($_SESSION['role'] === 'admin'): ?>
                <li><a href="../admin/index.php">Admin Panel</a></li>
            <?php endif; ?>
            
            <li><a href="../profile.php">โปรไฟล์</a></li>
            <li><a href="../logout.php">ออกจากระบบ</a></li>
        <?php else: ?>
            <li><a href="../groups.php">กลุ่มทั้งหมด</a></li>
            <li><a href="../login.php">เข้าสู่ระบบ</a></li>
            <li><a href="../register.php" class="btn btn-primary">สมัครสมาชิก</a></li>
        <?php endif; ?>
    </ul>
</nav>