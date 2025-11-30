<?php
// login.php
session_start();
require 'includes/db.php';
require 'includes/header.php';

// If already logged in, redirect accordingly
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') header('Location: admin_dashboard.php');
    else header('Location: profile.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Try User table first
    $stmt = $pdo->prepare("SELECT id, name, email, password_hash FROM `User` WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $ok = false;
        // Support hashed passwords and legacy plain/text placeholders
        if (!empty($user['password_hash']) && password_verify($password, $user['password_hash'])) $ok = true;
        elseif ($user['password_hash'] === $password) $ok = true; // fallback for non-hashed sample data
        if ($ok) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role'] = 'user';
            // Log action
            $log = $pdo->prepare("INSERT INTO `Log` (user_id, admin_id, action_time) VALUES (?, NULL, NOW())");
            $log->execute([$user['id']]);
            header('Location: profile.php');
            exit;
        }
    }

    // Try Admin
    $stmt = $pdo->prepare("SELECT id, email, password_hash FROM `Admin` WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();
    if ($admin) {
        $ok = false;
        if (!empty($admin['password_hash']) && password_verify($password, $admin['password_hash'])) $ok = true;
        elseif ($admin['password_hash'] === $password) $ok = true;
        if ($ok) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['user_name'] = $admin['email'];
            $_SESSION['role'] = 'admin';
            // Log action
            $log = $pdo->prepare("INSERT INTO `Log` (admin_id, user_id, action_time) VALUES (?, NULL, NOW())");
            $log->execute([$admin['id']]);
            header('Location: admin_dashboard.php');
            exit;
        }
    }

    $error = "Invalid email or password.";
}
?>

<div class="card" style="max-width:420px; margin:auto;">
    <h2>Login</h2>
    <?php if($error): ?><p style="color:red;"><?=htmlspecialchars($error)?></p><?php endif; ?>
    <form method="post">
        <label>Email</label>
        <input type="email" name="email" required>
        <label>Password</label>
        <input type="password" name="password" required>
        <button type="submit">Login</button>
    </form>
    <p style="margin-top:10px;">No account? Ask admin to create you, or register via provided SQL.</p>
</div>
</div>
</body>
</html>
