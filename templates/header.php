<?php
// Fetch dynamic site name, fallback to a default
$site_name = get_setting('site_name') ?: 'Kanngan Sharing';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="แพลตฟอร์มสำหรับหาคนหารค่าบริการ AI ที่ปลอดภัยและเชื่อถือได้">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' | ' : ''; echo htmlspecialchars($site_name); ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header class="site-header">
        <div class="logo">
            <a href="/index.php"><i class="fas fa-users"></i> <?php echo htmlspecialchars($site_name); ?></a>
        </div>
        <nav class="main-nav">
            <ul>
                <li><a href="/index.php">หน้าแรก</a></li>
                <li><a href="/groups.php">กลุ่มทั้งหมด</a></li>
                <?php if (is_logged_in()): ?>
                    <?php if ($_SESSION['role'] === 'representative'): ?>
                        <li><a href="/create_group.php">สร้างกลุ่มหาร</a></li>
                    <?php endif; ?>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <li><a href="/admin/index.php">Admin Panel</a></li>
                    <?php endif; ?>
                    <li><a href="/profile.php">โปรไฟล์<?php if (isset($_SESSION['username'])) echo ' (' . htmlspecialchars($_SESSION['username']) . ')'; ?></a></li>
                    <li><a href="/logout.php">ออกจากระบบ</a></li>
                <?php else: ?>
                    <li><a href="/login.php">เข้าสู่ระบบ</a></li>
                    <li><a href="/register.php" class="btn btn-primary">สมัครสมาชิก</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>