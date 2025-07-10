<?php
require_once 'core/initialize.php';

// --- Fetch data for the page ---

// 1. Get the global announcement from site settings
$announcement = get_setting('site_announcement');

// 2. Fetch a few of the newest 'open' groups to feature on the homepage
$stmt = $pdo->query("
    SELECT
        g.id,
        g.ai_name,
        g.price_per_slot,
        g.total_slots,
        g.status,
        u.username AS creator_username,
        (SELECT COUNT(id) FROM group_members WHERE group_id = g.id AND join_status = 'paid') AS member_count
    FROM
        groups g
    JOIN
        users u ON g.creator_id = u.id
    WHERE
        g.status = 'open'
    ORDER BY
        g.created_at DESC
    LIMIT 4
");
$featured_groups = $stmt->fetchAll(PDO::FETCH_ASSOC);


$page_title = "Home";
require_once 'templates/header.php';
?>

<div class="bg-blue-600 text-white">
    <section class="container mx-auto py-20 text-center">
        <div class="animate-fade space-y-4">
            <h1 class="text-3xl font-bold">หาร AI อย่างปลอดภัยและมั่นใจ</h1>
            <p>แพลตฟอร์มกลางสำหรับหาเพื่อนหารค่าบริการ AI ที่น่าเชื่อถือ มีแอดมินคอยดูแลและระบบที่ปลอดภัย</p>
            <a href="/groups.php" class="inline-block px-6 py-3 bg-white text-blue-600 font-semibold rounded hover:bg-gray-200">
                <i class="fas fa-search"></i> ค้นหากลุ่มเลยตอนนี้
            </a>
        </div>
    </section>
</div>

<div class="container mx-auto px-4">
    <?php if (!empty($announcement)): ?>
    <section class="bg-yellow-100 border-l-4 border-yellow-500 p-4 my-6">
        <i class="fas fa-bullhorn"></i>
        <div class="announcement-content">
            <strong>ประกาศจากแอดมิน:</strong>
            <p><?php echo htmlspecialchars($announcement); ?></p>
        </div>
    </section>
    <?php endif; ?>

    <section class="my-10">
        <h2 class="text-center text-2xl font-bold mb-6">มันทำงานอย่างไร?</h2>
        <div class="grid md:grid-cols-3 gap-6">
            <div class="text-center p-4 bg-white rounded shadow">
                <div class="text-blue-600 text-3xl mb-2"><i class="fas fa-search-plus"></i></div>
                <h3 class="font-semibold">1. ค้นหากลุ่ม</h3>
                <p>เลือกดูจากกลุ่มหาร AI ที่มีอยู่หลากหลายรายการตามที่คุณต้องการใช้งาน</p>
            </div>
            <div class="text-center p-4 bg-white rounded shadow">
                <div class="text-blue-600 text-3xl mb-2"><i class="fas fa-money-check-alt"></i></div>
                <h3 class="font-semibold">2. เข้าร่วมและชำระเงิน</h3>
                <p>กดเข้าร่วมกลุ่มและชำระเงินผ่านระบบ QR Code ที่ปลอดภัย พร้อมแนบสลิปยืนยัน</p>
            </div>
            <div class="text-center p-4 bg-white rounded shadow">
                <div class="text-blue-600 text-3xl mb-2"><i class="fas fa-key"></i></div>
                <h3 class="font-semibold">3. รับข้อมูลและใช้งาน</h3>
                <p>เมื่อหัวหน้ากลุ่มอนุมัติ คุณจะสามารถเห็นข้อมูลและเข้าใช้งาน AI ได้ทันที</p>
            </div>
        </div>
    </section>

    <?php if (!empty($featured_groups)): ?>
    <section class="my-10">
        <h2 class="text-center text-2xl font-bold mb-6">กลุ่มล่าสุดที่ยังเปิดรับ</h2>
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($featured_groups as $group): ?>
                <div class="border rounded shadow p-4 status-<?php echo htmlspecialchars($group['status']); ?>">
                    <div class="flex items-center justify-between mb-2">
                        <span class="px-2 py-1 text-xs rounded bg-blue-600 text-white status-badge status-<?php echo htmlspecialchars($group['status']); ?>">
                            <?php echo ucfirst(htmlspecialchars($group['status'])); ?>
                        </span>
                        <h3 class="font-semibold text-sm"><?php echo htmlspecialchars($group['ai_name']); ?></h3>
                    </div>
                    <p class="text-sm mb-2">By: <?php echo htmlspecialchars($group['creator_username']); ?></p>
                    <div class="mb-2 text-lg font-bold">฿<?php echo number_format($group['price_per_slot'], 2); ?> <span class="text-sm font-normal">/ month</span></div>
                    <div class="mb-2 text-sm flex items-center justify-between">
                        <span><i class="fas fa-users"></i> <?php echo $group['member_count']; ?> / <?php echo $group['total_slots']; ?></span>
                        <progress class="flex-grow ml-2" value="<?php echo $group['member_count']; ?>" max="<?php echo $group['total_slots']; ?>"></progress>
                    </div>
                    <a href="/group.php?id=<?php echo $group['id']; ?>" class="block text-center mt-2 px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">View Details</a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

</div>

<?php
require_once 'templates/footer.php';
?>