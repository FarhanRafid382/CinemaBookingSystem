<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>News Portal</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<nav>
    <a href="index.php">HOME</a>
    <div class="right">
        <?php if(isset($_SESSION['user_id'])): ?>
            <span style="font-size:0.9em; margin-right:10px; color:#ccc;">
                Hello, <?php echo htmlspecialchars($_SESSION['user_name']); ?> (<?php echo $_SESSION['role']; ?>)
            </span>
            <?php if($_SESSION['role'] == 'journalist'): ?>
                <a href="journalist_dashboard.php">Write Article</a>
            <?php endif; ?>
            <?php if($_SESSION['role'] == 'admin'): ?>
                <a href="admin_panel.php" style="color:#ffaaaa;">Admin Panel</a>
            <?php endif; ?>
            <?php if($_SESSION['role'] == 'reader'): ?>
                <a href="my_bookmarks.php">My Bookmarks</a>
            <?php endif; ?>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php">Reader Register</a>
        <?php endif; ?>
    </div>
</nav>
<div class="container">