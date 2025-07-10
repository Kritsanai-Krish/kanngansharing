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

<div class="container-fluid no-padding">
    <section class="hero-section">
        <div class="hero-content">
            <h1>หาร AI อย่างปลอดภัยและมั่นใจ</h1>
            <p>แพลตฟอร์มกลางสำหรับหาเพื่อนหารค่าบริการ AI ที่น่าเชื่อถือ มีแอดมินคอยดูแลและระบบที่ปลอดภัย</p>
            <a href="/groups.php" class="btn btn-primary btn-lg">
                <i class="fas fa-search"></i> ค้นหากลุ่มเลยตอนนี้
            </a>
        </div>
    </section>
</div>

<div class="container">
    <?php if (!empty($announcement)): ?>
    <section class="global-announcement">
        <i class="fas fa-bullhorn"></i>
        <div class="announcement-content">
            <strong>ประกาศจากแอดมิน:</strong>
            <p><?php echo htmlspecialchars($announcement); ?></p>
        </div>
    </section>
    <?php endif; ?>

    <section class="how-it-works">
        <h2>มันทำงานอย่างไร?</h2>
        <div class="steps-grid">
            <div class="step">
                <div class="step-icon"><i class="fas fa-search-plus"></i></div>
                <h3>1. ค้นหากลุ่ม</h3>
                <p>เลือกดูจากกลุ่มหาร AI ที่มีอยู่หลากหลายรายการตามที่คุณต้องการใช้งาน</p>
            </div>
            <div class="step">
                <div class="step-icon"><i class="fas fa-money-check-alt"></i></div>
                <h3>2. เข้าร่วมและชำระเงิน</h3>
                <p>กดเข้าร่วมกลุ่มและชำระเงินผ่านระบบ QR Code ที่ปลอดภัย พร้อมแนบสลิปยืนยัน</p>
            </div>
            <div class="step">
                <div class="step-icon"><i class="fas fa-key"></i></div>
                <h3>3. รับข้อมูลและใช้งาน</h3>
                <p>เมื่อหัวหน้ากลุ่มอนุมัติ คุณจะสามารถเห็นข้อมูลและเข้าใช้งาน AI ได้ทันที</p>
            </div>
        </div>
    </section>

    <?php if (!empty($featured_groups)): ?>
    <section class="featured-groups">
        <h2>กลุ่มล่าสุดที่ยังเปิดรับ</h2>
        <div class="group-grid">
            <?php foreach ($featured_groups as $group): ?>
                <div class="group-card status-<?php echo htmlspecialchars($group['status']); ?>">
                    <div class="card-header">
                        <span class="status-badge status-<?php echo htmlspecialchars($group['status']); ?>">
                            <?php echo ucfirst(htmlspecialchars($group['status'])); ?>
                        </span>
                        <h3><?php echo htmlspecialchars($group['ai_name']); ?></h3>
                    </div>
                    <div class="card-body">
                         <p class="card-creator">By: <?php echo htmlspecialchars($group['creator_username']); ?></p>
                         <div class="card-price">
                            ฿<?php echo number_format($group['price_per_slot'], 2); ?>
                            <span>/ month</span>
                        </div>
                        <div class="card-members">
                             <div class="member-count">
                                <i class="fas fa-users"></i>
                                <span><?php echo $group['member_count']; ?> / <?php echo $group['total_slots']; ?> Members</span>
                            </div>
                            <progress value="<?php echo $group['member_count']; ?>" max="<?php echo $group['total_slots']; ?>"></progress>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="/group.php?id=<?php echo $group['id']; ?>" class="btn btn-secondary btn-block">View Details</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

</div>

<?php
require_once 'templates/footer.php';
?>