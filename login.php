<?php
require_once 'core/initialize.php';

// If user is already logged in, redirect them away from the login page.
if (is_logged_in()) {
    redirect('profile.php');
}

$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error_message = 'Please enter both username and password.';
    } else {
        // Look up the user in the database
        $stmt = $pdo->prepare("SELECT id, username, password_hash, role, status FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify user exists and password is correct
        if ($user && password_verify($password, $user['password_hash'])) {
            
            // Check user account status
            if ($user['status'] === 'active') {
                // --- Login Successful ---
                
                // Regenerate session ID to prevent session fixation attacks
                session_regenerate_id(true);

                // Store user data in the session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // Create a log entry for the login
                create_log_entry($user['id'], 'LOGIN_SUCCESS', 'User logged in successfully.');

                // Redirect to the intended page or profile
                $redirect_to = $_GET['redirect'] ?? 'profile.php';
                redirect($redirect_to);

            } elseif ($user['status'] === 'pending') {
                $error_message = 'Your account is currently awaiting admin approval.';
            } elseif ($user['status'] === 'banned') {
                $error_message = 'Your account has been banned. Please contact support.';
            } else {
                $error_message = 'Invalid account status. Please contact support.';
            }

        } else {
            // --- Login Failed ---
            create_log_entry(null, 'LOGIN_FAIL', "Failed login attempt for username: {$username}");
            $error_message = 'Invalid username or password.';
        }
    }
}

$page_title = "Login";
require_once 'templates/header.php';
?>

<div class="animate-fade max-w-md mx-auto my-10 p-6 bg-white rounded shadow">
    <form method="POST" action="login.php<?php echo isset($_GET['redirect']) ? '?redirect=' . htmlspecialchars($_GET['redirect']) : ''; ?>">
        <h1 class="text-2xl font-semibold mb-4 flex items-center space-x-2"><i class="fas fa-sign-in-alt"></i><span>Login</span></h1>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="mb-4">
            <label for="username" class="block mb-1">Username</label>
            <input type="text" id="username" name="username" class="w-full px-3 py-2 border rounded" required>
        </div>

        <div class="mb-4">
            <label for="password" class="block mb-1">Password</label>
            <input type="password" id="password" name="password" class="w-full px-3 py-2 border rounded" required>
        </div>

        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded">Login</button>

        <p class="text-center text-sm mt-4">
            Don't have an account? <a class="text-blue-600 hover:underline" href="register.php">Register here</a>
        </p>
    </form>
</div>

<?php
require_once 'templates/footer.php';
?>