<?php
require_once 'core/initialize.php';

// 1. Authentication: Redirect if not logged in
if (!is_logged_in()) {
    redirect('login.php?redirect=profile.php');
}

$user_id = $_SESSION['user_id'];

// 2. Fetch current user's data
$user_stmt = $pdo->prepare("SELECT username, email, full_name, phone_number, contact_other FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

// If for some reason the user isn't found in DB, log them out.
if (!$user) {
    redirect('logout.php');
}

// 3. Fetch all groups the user is a member of
$groups_stmt = $pdo->prepare("
    SELECT 
        g.id, 
        g.ai_name, 
        g.expiry_date, 
        gm.join_status 
    FROM groups g 
    JOIN group_members gm ON g.id = gm.group_id 
    WHERE gm.user_id = ? 
    ORDER BY g.expiry_date DESC
");
$groups_stmt->execute([$user_id]);
$my_groups = $groups_stmt->fetchAll(PDO::FETCH_ASSOC);


$page_title = "My Profile";
require_once 'templates/header.php';
?>

<div class="container">
    <header class="page-header">
        <h1><i class="fas fa-user-circle"></i> My Profile</h1>
        <p>Welcome, <?php echo htmlspecialchars($user['username']); ?>! Manage your information and groups here.</p>
    </header>

    <div class="profile-layout">
        
        <div class="profile-form-container">
            <h2>Edit Information</h2>
            <form id="profile-update-form" method="POST" action="api/user_actions.php">
                <input type="hidden" name="action" value="update_profile">

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                    <small>Usernames cannot be changed.</small>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                    <small>Email addresses cannot be changed.</small>
                </div>
                
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="phone_number">Phone Number</label>
                    <input type="tel" id="phone_number" name="phone_number" class="form-control" value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="contact_other">Other Contacts (Facebook, Line, etc.)</label>
                    <textarea id="contact_other" name="contact_other" class="form-control" rows="3"><?php echo htmlspecialchars($user['contact_other'] ?? ''); ?></textarea>
                </div>
                
                <hr>

                <h3>Change Password</h3>
                <p><small>Leave these fields blank to keep your current password.</small></p>

                 <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" class="form-control">
                </div>

                 <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" class="form-control">
                </div>

                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
            </form>
        </div>

        <div class="my-groups-container">
            <h2>My Groups</h2>
            <?php if (empty($my_groups)): ?>
                <div class="no-groups-info">
                    <p>You haven't joined any groups yet.</p>
                    <a href="/groups.php" class="btn btn-secondary">Browse Groups</a>
                </div>
            <?php else: ?>
                <div class="my-groups-list">
                    <?php foreach ($my_groups as $group): ?>
                        <div class="my-group-card">
                            <div class="card-content">
                                <h3><?php echo htmlspecialchars($group['ai_name']); ?></h3>
                                <p>Expires on: <?php echo date("d M Y", strtotime($group['expiry_date'])); ?></p>
                                <span class="status-badge status-<?php echo htmlspecialchars($group['join_status']); ?>">
                                    Status: <?php echo ucfirst(str_replace('_', ' ', $group['join_status'])); ?>
                                </span>
                            </div>
                            <div class="card-action">
                                <a href="/group.php?id=<?php echo $group['id']; ?>" class="btn btn-sm btn-outline">View</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
require_once 'templates/footer.php';
?>