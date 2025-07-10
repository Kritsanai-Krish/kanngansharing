<?php
require_once '../core/initialize.php';

check_access_level('admin');

// --- Pagination & Search Logic ---
$search_term = $_GET['search'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$logs_per_page = 50;
$offset = ($page - 1) * $logs_per_page;

// Base query
$count_query = "SELECT COUNT(l.id) FROM action_logs l LEFT JOIN users u ON l.user_id = u.id";
$data_query = "SELECT l.id, l.timestamp, l.ip_address, l.action, l.details, u.username 
               FROM action_logs l LEFT JOIN users u ON l.user_id = u.id";

$params = [];
if (!empty($search_term)) {
    $where_clause = " WHERE u.username LIKE ? OR l.action LIKE ? OR l.details LIKE ?";
    $count_query .= $where_clause;
    $data_query .= $where_clause;
    $search_param = "%{$search_term}%";
    $params = [$search_param, $search_param, $search_param];
}

// Get total logs for pagination
$total_logs_stmt = $pdo->prepare($count_query);
$total_logs_stmt->execute($params);
$total_logs = $total_logs_stmt->fetchColumn();
$total_pages = ceil($total_logs / $logs_per_page);

// Fetch logs for the current page
$data_query .= " ORDER BY l.timestamp DESC LIMIT ? OFFSET ?";
$data_params = array_merge($params, [$logs_per_page, $offset]);

$logs_stmt = $pdo->prepare($data_query);
// PDO requires integer values for LIMIT and OFFSET to be bound as such
$logs_stmt->execute($data_params);

$logs = $logs_stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Action Logs";
require_once '../templates/header.php';
?>

<div class="admin-container">
    <header class="admin-header">
        <h1><i class="fas fa-history"></i> บันทึกการกระทำ (Action Logs)</h1>
        <p>ข้อมูลการกระทำทั้งหมดในระบบ ไม่สามารถแก้ไขหรือลบได้</p>
    </header>

    <section class="log-search">
        <form method="GET" action="view_logs.php">
            <div class="form-group-inline">
                <input type="text" name="search" class="form-control" placeholder="ค้นหาด้วย Username, Action, Details..." value="<?php echo htmlspecialchars($search_term); ?>">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> ค้นหา</button>
            </div>
        </form>
    </section>

    <section class="content-table">
        <table>
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Details</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="5" class="text-center">ไม่พบข้อมูล Log</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td class="log-timestamp"><?php echo htmlspecialchars($log['timestamp']); ?></td>
                            <td><?php echo htmlspecialchars($log['username'] ?? '[Deleted User]'); ?></td>
                            <td class="log-action"><?php echo htmlspecialchars($log['action']); ?></td>
                            <td class="log-details"><?php echo htmlspecialchars($log['details']); ?></td>
                            <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </section>

    <section class="pagination">
        <?php if ($total_pages > 1): ?>
            <div class="pagination-links">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search_term); ?>">« Previous</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search_term); ?>" class="<?php echo ($i == $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search_term); ?>">Next »</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <div class="pagination-summary">
            Page <?php echo $page; ?> of <?php echo $total_pages; ?> (Total logs: <?php echo $total_logs; ?>)
        </div>
    </section>
</div>

<?php
require_once '../templates/footer.php';
?>