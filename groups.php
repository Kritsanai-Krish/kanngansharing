<?php
require_once 'core/initialize.php';

// Handle search functionality
$search_term = $_GET['search'] ?? '';
$sql_where = "WHERE g.status != 'closed'";
$params = [];

if (!empty($search_term)) {
    $sql_where .= " AND g.ai_name LIKE ?";
    $params[] = "%{$search_term}%";
}

// Fetch all visible groups from the database
$stmt = $pdo->prepare("
    SELECT
        g.id,
        g.ai_name,
        g.description,
        g.price_per_slot,
        g.total_slots,
        g.status,
        u.username AS creator_username,
        (SELECT COUNT(id) FROM group_members WHERE group_id = g.id AND join_status = 'paid') AS member_count
    FROM
        groups g
    JOIN
        users u ON g.creator_id = u.id
    {$sql_where}
    ORDER BY
        CASE g.status
            WHEN 'open' THEN 1
            WHEN 'full' THEN 2
            ELSE 3
        END,
        g.created_at DESC
");
$stmt->execute($params);
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "All Groups";
require_once 'templates/header.php';
?>

<div class="container">
    <header class="page-header">
        <h1><i class="fas fa-search"></i> Find a Sharing Group</h1>
        <p>Browse available groups to join. Open groups are listed first.</p>
    </header>

    <div class="toolbar">
        <form class="search-form" method="GET" action="/groups.php">
            <input type="text" name="search" placeholder="Search by AI name..." value="<?php echo htmlspecialchars($search_term); ?>">
            <button type="submit" class="btn"><i class="fas fa-search"></i></button>
        </form>

        <?php if (is_logged_in() && $_SESSION['role'] === 'representative'): ?>
            <a href="/create_group.php" class="btn btn-primary create-group-btn">
                <i class="fas fa-plus-circle"></i> Create New Group
            </a>
        <?php endif; ?>
    </div>


    <div class="group-grid">
        <?php if (empty($groups)): ?>
            <div class="no-groups-found">
                <h2>No groups found.</h2>
                <p>There are currently no groups matching your search criteria.</p>
            </div>
        <?php else: ?>
            <?php foreach ($groups as $group): ?>
                <div class="group-card status-<?php echo htmlspecialchars($group['status']); ?>">
                    <div class="card-header">
                        <span class="status-badge status-<?php echo htmlspecialchars($group['status']); ?>">
                            <?php echo ucfirst(htmlspecialchars($group['status'])); ?>
                        </span>
                        <h3><?php echo htmlspecialchars($group['ai_name']); ?></h3>
                    </div>
                    <div class="card-body">
                        <p class="card-creator">By: <?php echo htmlspecialchars($group['creator_username']); ?></p>
                        <p class="card-description">
                            <?php echo htmlspecialchars(mb_substr($group['description'], 0, 100)) . (mb_strlen($group['description']) > 100 ? '...' : ''); ?>
                        </p>
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
        <?php endif; ?>
    </div>
</div>

<?php
require_once 'templates/footer.php';
?>