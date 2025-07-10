<?php
// Fetch dynamic site name, fallback to a default
$site_name = get_setting('site_name') ?: 'Kanngan Sharing';

// Basic security headers
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header("X-XSS-Protection: 1; mode=block");
header('Strict-Transport-Security: max-age=63072000; includeSubDomains');
header('Cache-Control: no-store');
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com https://fonts.googleapis.com; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com; font-src 'self' https://fonts.gstatic.com;");
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
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    animation: {
                        fade: 'fadeIn 0.5s ease-in-out'
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' }
                        }
                    }
                }
            }
        };
    </script>
</head>
<body class="min-h-screen flex flex-col">
    <header class="bg-gray-800 text-white">
        <div class="container mx-auto flex flex-wrap items-center justify-between p-4">
            <a class="flex items-center text-xl font-bold" href="/index.php">
                <i class="fas fa-users mr-2"></i> <?php echo htmlspecialchars($site_name); ?>
            </a>
            <nav>
                <ul class="flex flex-wrap space-x-4 items-center">
                    <li><a class="hover:underline" href="/index.php">หน้าแรก</a></li>
                    <li><a class="hover:underline" href="/groups.php">กลุ่มทั้งหมด</a></li>
                    <?php if (is_logged_in()): ?>
                        <?php if ($_SESSION['role'] === 'representative'): ?>
                            <li><a class="hover:underline" href="/create_group.php">สร้างกลุ่มหาร</a></li>
                        <?php endif; ?>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <li><a class="hover:underline" href="/admin/index.php">Admin Panel</a></li>
                        <?php endif; ?>
                        <li><a class="hover:underline" href="/profile.php">โปรไฟล์<?php if (isset($_SESSION['username'])) echo ' (' . htmlspecialchars($_SESSION['username']) . ')'; ?></a></li>
                        <li><a class="hover:underline" href="/logout.php">ออกจากระบบ</a></li>
                    <?php else: ?>
                        <li><a class="hover:underline" href="/login.php">เข้าสู่ระบบ</a></li>
                        <li><a class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700" href="/register.php">สมัครสมาชิก</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>