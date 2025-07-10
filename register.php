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
                VALUES (?, ?, ?, ?, ?, 'member', 'active')";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $input['username'],
            $input['email'],
            $password_hash,
            $input['full_name'],
            $input['phone_number']
        ]);

        $new_user_id = $pdo->lastInsertId();
        create_log_entry($new_user_id, 'REGISTER', 'New user registered.');

        // Auto login after successful registration
        $_SESSION['user_id'] = $new_user_id;
        $_SESSION['username'] = $input['username'];
        $_SESSION['role'] = 'member';

        redirect('profile.php');
    }
}

$page_title = "Register";
require_once 'templates/header.php';
?>

<div class="animate-fade max-w-md mx-auto my-10 p-6 bg-white rounded shadow">
    <form method="POST" action="register.php">
        <h1 class="text-2xl font-semibold mb-4 flex items-center space-x-2"><i class="fas fa-user-plus"></i><span>Create Account</span></h1>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="mb-4">
            <label for="username" class="block mb-1">Username</label>
            <input type="text" id="username" name="username" class="w-full px-3 py-2 border rounded" value="<?php echo htmlspecialchars($input['username'] ?? ''); ?>" required>
        </div>

        <div class="mb-4">
            <label for="email" class="block mb-1">Email Address</label>
            <input type="email" id="email" name="email" class="w-full px-3 py-2 border rounded" value="<?php echo htmlspecialchars($input['email'] ?? ''); ?>" required>
        </div>
        
        <div class="mb-4">
            <label for="full_name" class="block mb-1">Full Name (ชื่อ-นามสกุล)</label>
            <input type="text" id="full_name" name="full_name" class="w-full px-3 py-2 border rounded" value="<?php echo htmlspecialchars($input['full_name'] ?? ''); ?>" required>
        </div>

        <div class="mb-4">
            <label for="phone_number" class="block mb-1">Phone Number</label>
            <input type="tel" id="phone_number" name="phone_number" class="w-full px-3 py-2 border rounded" value="<?php echo htmlspecialchars($input['phone_number'] ?? ''); ?>">
        </div>

        <hr class="my-4">

        <div class="mb-4">
            <label for="password" class="block mb-1">Password</label>
            <input type="password" id="password" name="password" class="w-full px-3 py-2 border rounded" required>
        </div>

        <div class="mb-4">
            <label for="password_confirm" class="block mb-1">Confirm Password</label>
            <input type="password" id="password_confirm" name="password_confirm" class="w-full px-3 py-2 border rounded" required>
        </div>

        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded">Register</button>

        <p class="text-center text-sm mt-4">
            Already have an account? <a class="text-blue-600 hover:underline" href="login.php">Login here</a>
        </p>
    </form>
</div>

<?php
require_once 'templates/footer.php';
?>