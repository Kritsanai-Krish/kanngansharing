<?php
require_once '../core/initialize.php';

check_access_level('admin');

// Helper function to handle file uploads
function handle_file_upload($file_key, $setting_name, $pdo) {
    if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES[$file_key];
        $upload_dir = '../assets/images/site/';
        
        // Basic validation
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowed_types)) {
            // Optional: Set an error message
            return;
        }
        
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_filename = $setting_name . '_' . time() . '.' . $file_extension;
        $target_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            // Delete old file if it exists
            $old_filename = get_setting($setting_name);
            if (!empty($old_filename) && file_exists($upload_dir . $old_filename)) {
                unlink($upload_dir . $old_filename);
            }
            // Update setting in DB
            update_setting($setting_name, $new_filename, $pdo);
        }
    }
}


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update text-based settings
    if (isset($_POST['site_name'])) {
        update_setting('site_name', $_POST['site_name'], $pdo);
    }
    if (isset($_POST['site_announcement'])) {
        update_setting('site_announcement', $_POST['site_announcement'], $pdo);
    }

    // Handle file uploads
    handle_file_upload('site_logo', 'site_logo_filename', $pdo);
    handle_file_upload('site_cover', 'site_cover_filename', $pdo);
    
    create_log_entry($_SESSION['user_id'], 'UPDATE_SITE_SETTINGS', 'Admin updated site settings');
    
    header("Location: site_settings.php?success=1");
    exit();
}


// Fetch current settings for display
$site_name = get_setting('site_name');
$site_announcement = get_setting('site_announcement');
$site_logo_filename = get_setting('site_logo_filename');
$site_cover_filename = get_setting('site_cover_filename');

$page_title = "Site Settings";
require_once '../templates/header.php';
?>

<div class="admin-container">
    <header class="admin-header">
        <h1><i class="fas fa-cogs"></i> ตั้งค่าเว็บไซต์</h1>
        <p>จัดการชื่อเว็บ, ประกาศ, และรูปภาพหลักของระบบ</p>
    </header>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">บันทึกการตั้งค่าเรียบร้อยแล้ว</div>
    <?php endif; ?>

    <section class="settings-form">
        <form method="POST" action="site_settings.php" enctype="multipart/form-data">
            
            <div class="form-group">
                <label for="site_name">ชื่อเว็บไซต์</label>
                <input type="text" id="site_name" name="site_name" class="form-control" value="<?php echo htmlspecialchars($site_name); ?>">
            </div>

            <div class="form-group">
                <label for="site_announcement">ประกาศบนหน้าหลัก (Global Announcement)</label>
                <textarea id="site_announcement" name="site_announcement" class="form-control" rows="4"><?php echo htmlspecialchars($site_announcement); ?></textarea>
            </div>

            <hr>

            <div class="form-group">
                <label for="site_logo">โลโก้เว็บไซต์</label>
                <div class="current-image">
                    <?php if (!empty($site_logo_filename) && file_exists('../assets/images/site/' . $site_logo_filename)): ?>
                        <img src="../assets/images/site/<?php echo htmlspecialchars($site_logo_filename); ?>" alt="Current Logo">
                    <?php else: ?>
                        <p>ยังไม่มีโลโก้</p>
                    <?php endif; ?>
                </div>
                <input type="file" id="site_logo" name="site_logo" class="form-control-file">
                <small class="form-text text-muted">แนะนำขนาด 200x50 px, ไฟล์ .png, .jpg, .gif</small>
            </div>

            <hr>

            <div class="form-group">
                <label for="site_cover">รูปปก (Cover Photo)</label>
                 <div class="current-image wide">
                    <?php if (!empty($site_cover_filename) && file_exists('../assets/images/site/' . $site_cover_filename)): ?>
                        <img src="../assets/images/site/<?php echo htmlspecialchars($site_cover_filename); ?>" alt="Current Cover">
                    <?php else: ?>
                        <p>ยังไม่มีรูปปก</p>
                    <?php endif; ?>
                </div>
                <input type="file" id="site_cover" name="site_cover" class="form-control-file">
                <small class="form-text text-muted">แนะนำขนาด 1200x400 px, ไฟล์ .jpg, .png</small>
            </div>

            <hr>

            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> บันทึกการเปลี่ยนแปลง</button>
        </form>
    </section>
</div>

<?php
require_once '../templates/footer.php';
?>