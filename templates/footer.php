</div> 
<footer class="site-footer">
    <div class="container">
        <nav class="footer-nav">
            <a href="/index.php">หน้าแรก</a> |
            <a href="/groups.php">กลุ่มทั้งหมด</a> |
            <a href="/profile.php">โปรไฟล์</a> |
            <a href="mailto:support@kanngan.com">ติดต่อเรา</a>
        </nav>
        <p>&copy; <?php echo date('Y'); ?> Kanngan Sharing. All Rights Reserved.</p>
    </div>
</footer>
<script src="/assets/js/main.js"></script>
<?php
// Conditionally load the admin JavaScript only on admin pages
if (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false) {
    echo '<script src="/assets/js/admin.js"></script>';
}
?>
</body>
</html>