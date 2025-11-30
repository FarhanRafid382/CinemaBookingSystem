<?php
// admin_dashboard.php
session_start();
require 'includes/db.php';
require 'includes/header.php';

// logout via ?logout=1
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

// ensure admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
$admin_id = $_SESSION['admin_id'] ?? null;

// summary counts
$counts = [];
$tables = ['Movie','Showtime','Screen','Theater','User','Booking','BookedSeat','Admin','Log'];
foreach ($tables as $t) {
    $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM `$t`");
    $stmt->execute();
    $counts[$t] = $stmt->fetchColumn();
}

// optional simple chart data (no JS)
?>
<h1>Admin Dashboard</h1>

<div class="dashboard-grid">
    <div class="card">
        <h3>Movies</h3>
        <p><?= (int)$counts['Movie'] ?> total</p>
        <p><a class="btn" href="admin_movies.php">Manage Movies</a></p>
    </div>

    <div class="card">
        <h3>Showtimes</h3>
        <p><?= (int)$counts['Showtime'] ?> total</p>
        <p><a class="btn" href="admin_showtimes.php">Manage Showtimes</a></p>
    </div>

    <div class="card">
        <h3>Screens</h3>
        <p><?= (int)$counts['Screen'] ?> total</p>
        <p><a class="btn" href="admin_screens.php">Manage Screens</a></p>
    </div>

    <div class="card">
        <h3>Theaters</h3>
        <p><?= (int)$counts['Theater'] ?> total</p>
    </div>

    <div class="card">
        <h3>Users</h3>
        <p><?= (int)$counts['User'] ?> total</p>
    </div>

    <div class="card">
        <h3>Bookings</h3>
        <p><?= (int)$counts['Booking'] ?> total</p>
    </div>

    <div class="card">
        <h3>Admins</h3>
        <p><?= (int)$counts['Admin'] ?> total</p>
    </div>

    <div class="card">
        <h3>Logs</h3>
        <p><?= (int)$counts['Log'] ?> total</p>
    </div>
</div>

<p>
    <a class="btn" href="admin_movies.php">Movies</a>
    <a class="btn" href="admin_showtimes.php">Showtimes</a>
    <a class="btn" href="admin_screens.php">Screens</a>
    <a class="btn" href="?logout=1">Logout</a>
</p>

</div>
</body>
</html>
