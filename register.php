<?php
require_once 'core/initialize.php';

// If user is already logged in, redirect them away from the registration page.
if (is_logged_in()) {
    redirect('profile.php');
}

$errors = [];
$input = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input['username'] = trim($_POST['username'] ?? '');
    $input['email'] = trim($_POST['email'] ?? '');
    $input['password'] = $_POST['password'] ?? '';
    $input['password_confirm'] = $_POST['password_confirm'] ?? '';
    $input['full_name'] = trim($_POST['full_name'] ?? '');
    $input['phone_number'] = trim($_POST['phone_number'] ?? '');

    // --- Validation ---
    if (empty($input['username'])) { $errors[] = 'Username is required.'; }
    if (empty($input['email'])) { $errors[] = 'Email is required.'; }
    if (empty($input['password'])) { $errors[] = 'Password is required.'; }
    if (empty($input['full_name'])) { $errors[] = 'Full Name is required.'; }

    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) { $errors[] = 'Please enter a valid email address.'; }
    if (strlen($input['password']) < 8) { $errors[] = 'Password must be at least 8 characters long.'; }
    if ($input['password'] !== $input['password_confirm']) { $errors[] = 'Passwords do not match.'; }

    // --- Check for uniqueness if there are no other errors ---
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$input['username']]);
        if ($stmt->fetch()) {
            $errors[] = 'This username is already taken.';
        }

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$input['email']]);
        if ($stmt->fetch()) {
            $errors[] = 'This email address is already registered.';
        }
    }

    // --- Process Registration if all checks pass ---
    if (empty($errors)) {
        $password_hash = password_hash($input['password'], PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (username, email, password_hash, full_name, phone_number, role, status) 
                VALUES (?, ?, ?, ?, ?, 'member', 'pending')";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $input['username'],
            $input['email'],
            $password_hash,
            $input['full_name'],
            $input['phone_number']
        ]);

        $new_user_id = $pdo->lastInsertId();
        create_log_entry($new_user_id, 'REGISTER', 'New user registered and is pending approval.');
        
        // Redirect to login page with a success message
        redirect('login.php?registration=success');
    }
}

$page_title = "Register";
require_once 'templates/header.php';
?>

<div class="form-container">
    <form method="POST" action="register.php">
        <h1><i class="fas fa-user-plus"></i> Create Account</h1>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($input['username'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($input['email'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="full_name">Full Name (ชื่อ-นามสกุล)</label>
            <input type="text" id="full_name" name="full_name" class="form-control" value="<?php echo htmlspecialchars($input['full_name'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
            <label for="phone_number">Phone Number</label>
            <input type="tel" id="phone_number" name="phone_number" class="form-control" value="<?php echo htmlspecialchars($input['phone_number'] ?? ''); ?>">
        </div>

        <hr>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="password_confirm">Confirm Password</label>
            <input type="password" id="password_confirm" name="password_confirm" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Register</button>

        <p class="form-footer-text">
            Already have an account? <a href="login.php">Login here</a>
        </p>
    </form>
</div>

<?php
// Add a check for the success message on the login page
if (isset($_GET['registration']) && $_GET['registration'] === 'success') {
    echo "<script>
        document.addEventListener('DOMContentLoaded', () => {
            const formContainer = document.querySelector('.form-container');
            const successAlert = document.createElement('div');
            successAlert.className = 'alert alert-success';
            successAlert.innerHTML = 'Registration successful! Your account is now awaiting admin approval.';
            formContainer.insertBefore(successAlert, formContainer.firstChild);
        });
    </script>";
}
require_once 'templates/footer.php';
?>