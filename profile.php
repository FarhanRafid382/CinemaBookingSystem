<?php
// profile.php
session_start();
require 'includes/db.php';
require 'includes/header.php';

// only users
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

// fetch user info
$stmt = $pdo->prepare("SELECT id, name, phone, email FROM `User` WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// fetch bookings with booked seats and movie/showtime info
$sql = "
SELECT b.id AS booking_id, b.booking_time, b.total_price,
       s.id AS showtime_id, s.show_date, s.show_time,
       m.title AS movie_title, m.genre,
       sc.screen_number, t.name AS theater_name, t.location
FROM Booking b
JOIN Showtime s ON b.showtime_id = s.id
JOIN Movie m ON s.movie_id = m.id
JOIN Screen sc ON s.screen_id = sc.id
JOIN Theater t ON sc.theater_id = t.id
WHERE b.user_id = ?
ORDER BY b.booking_time DESC
";
$stm = $pdo->prepare($sql);
$stm->execute([$user_id]);
$bookings = $stm->fetchAll();

// fetch booked seats per booking
$seatStmt = $pdo->prepare("
SELECT bs.booking_id, se.seat_number, se.seat_type
FROM BookedSeat bs
JOIN Seat se ON se.id = bs.seat_id
WHERE bs.booking_id = ?
");

?>

<h1>Your Profile</h1>

<div class="card">
    <h3><?=htmlspecialchars($user['name'])?></h3>
    <p><strong>Email:</strong> <?=htmlspecialchars($user['email'])?></p>
    <p><strong>Phone:</strong> <?=htmlspecialchars($user['phone'])?></p>
</div>

<h2>Your Bookings</h2>

<?php if(count($bookings) === 0): ?>
    <p>You have no bookings yet. <a href="index.php">Browse showtimes</a></p>
<?php else: ?>
    <?php foreach($bookings as $b): ?>
        <div class="card">
            <strong><?=htmlspecialchars($b['movie_title'])?></strong>
            <p><?=htmlspecialchars($b['theater_name'])." — Screen ".$b['screen_number']?></p>
            <p>Date: <?=htmlspecialchars($b['show_date'])?> Time: <?=htmlspecialchars(substr($b['show_time'],0,5))?></p>
            <p>Booked at: <?=htmlspecialchars($b['booking_time'])?> — Price: <?=htmlspecialchars($b['total_price'])?></p>
            <p>Seats:
                <?php
                $seatStmt->execute([$b['booking_id']]);
                $seats = $seatStmt->fetchAll();
                $seatList = array_map(function($s){ return htmlspecialchars($s['seat_number']." (".$s['seat_type'].")"); }, $seats);
                echo implode(", ", $seatList);
                ?>
            </p>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

</div>
</body>
</html>
