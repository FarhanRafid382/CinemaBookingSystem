<?php
// booking.php
session_start();
require 'includes/db.php';
require 'includes/header.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

// Preselected showtime (optional)
$selected_showtime = isset($_GET['showtime_id']) ? (int)$_GET['showtime_id'] : null;

// Fetch movies/showtimes for dropdowns
$shows = $pdo->query("
SELECT st.id, m.title, t.name AS theater_name, sc.screen_number, st.show_date, st.show_time
FROM Showtime st
JOIN Movie m ON m.id = st.movie_id
JOIN Screen sc ON sc.id = st.screen_id
JOIN Theater t ON sc.theater_id = t.id
ORDER BY st.show_date, st.show_time
")->fetchAll();

// If showtime selected, fetch seats for that screen and mark booked seats
$availableSeats = [];
$showDetails = null;
if ($selected_showtime) {
    $stmt = $pdo->prepare("SELECT st.*, m.title, sc.id AS screen_id, sc.total_seats, sc.screen_number, t.name AS theater_name
                           FROM Showtime st
                           JOIN Movie m ON m.id = st.movie_id
                           JOIN Screen sc ON sc.id = st.screen_id
                           JOIN Theater t ON t.id = sc.theater_id
                           WHERE st.id = ?");
    $stmt->execute([$selected_showtime]);
    $showDetails = $stmt->fetch();

    // all seats for the screen
    $seatStmt = $pdo->prepare("SELECT * FROM Seat WHERE screen_id = ? ORDER BY seat_number ASC");
    $seatStmt->execute([$showDetails['screen_id']]);
    $allSeats = $seatStmt->fetchAll();

    // booked seats for this showtime
    $bookedStmt = $pdo->prepare("
      SELECT se.id FROM BookedSeat bs
      JOIN Booking b ON b.id = bs.booking_id
      JOIN Seat se ON se.id = bs.seat_id
      WHERE b.showtime_id = ?
    ");
    $bookedStmt->execute([$selected_showtime]);
    $booked = array_column($bookedStmt->fetchAll(), 'id');

    foreach ($allSeats as $s) {
        $s['booked'] = in_array($s['id'], $booked);
        $availableSeats[] = $s;
    }
}

// Handle booking submission
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_now'])) {
    $sid = (int)$_POST['showtime_id'];
    $selectedSeatIds = isset($_POST['seat_ids']) ? $_POST['seat_ids'] : [];
    if (empty($selectedSeatIds)) {
        $msg = "Please select at least one seat.";
    } else {
        // Simple pricing: assume 200 per seat (or seat_type based)
        $pricePerSeat = 200;
        // Begin transaction
        try {
            $pdo->beginTransaction();

            // Double-check seat availability
            $placeholders = implode(',', array_fill(0, count($selectedSeatIds), '?'));
            $checkStmt = $pdo->prepare("
                SELECT se.id FROM BookedSeat bs
                JOIN Booking b ON b.id = bs.booking_id
                JOIN Seat se ON se.id = bs.seat_id
                WHERE b.showtime_id = ? AND se.id IN ($placeholders)
            ");
            $params = array_merge([$sid], $selectedSeatIds);
            $checkStmt->execute($params);
            $conflicts = $checkStmt->fetchAll();
            if (count($conflicts) > 0) {
                $pdo->rollBack();
                $msg = "One or more selected seats are already booked. Please choose different seats.";
            } else {
                // Create booking
                $total_price = $pricePerSeat * count($selectedSeatIds);
                $ins = $pdo->prepare("INSERT INTO Booking (user_id, showtime_id, booking_time, total_price) VALUES (?, ?, NOW(), ?)");
                $ins->execute([$user_id, $sid, $total_price]);
                $booking_id = $pdo->lastInsertId();

                // Insert booked seats
                $bsIns = $pdo->prepare("INSERT INTO BookedSeat (booking_id, seat_id) VALUES (?, ?)");
                foreach ($selectedSeatIds as $seatId) {
                    $bsIns->execute([$booking_id, $seatId]);
                }

                // Log
                $log = $pdo->prepare("INSERT INTO `Log` (user_id, admin_id, action_time) VALUES (?, NULL, NOW())");
                $log->execute([$user_id]);

                $pdo->commit();
                header("Location: profile.php");
                exit;
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $msg = "Booking failed: " . $e->getMessage();
        }
    }
}
?>

<h1>Book Tickets</h1>

<?php if($msg): ?><p style="color:red"><?=htmlspecialchars($msg)?></p><?php endif; ?>

<form method="get" style="margin-bottom:15px;">
    <label>Select showtime</label>
    <select onchange="this.form.submit()" name="showtime_id">
        <option value="">-- choose showtime --</option>
        <?php foreach($shows as $sh): ?>
            <option value="<?= $sh['id'] ?>" <?= $selected_showtime == $sh['id'] ? 'selected' : '' ?>>
                <?=htmlspecialchars($sh['title'] . " — ".$sh['theater_name']." (Screen ".$sh['screen_number'].") ".$sh['show_date']." ".substr($sh['show_time'],0,5))?>
            </option>
        <?php endforeach; ?>
    </select>
</form>

<?php if($showDetails): ?>
    <div class="card">
        <h3><?=htmlspecialchars($showDetails['title'])?></h3>
        <p><?=htmlspecialchars($showDetails['theater_name'])." — Screen ".htmlspecialchars($showDetails['screen_number'])?></p>
        <p>Date: <?=htmlspecialchars($showDetails['show_date'])?> Time: <?=htmlspecialchars(substr($showDetails['show_time'],0,5))?></p>

        <form method="post">
            <input type="hidden" name="showtime_id" value="<?= $selected_showtime ?>">
            <label>Select seats (available shown unchecked)</label>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <?php foreach($availableSeats as $s): ?>
                    <label style="border:1px solid #ddd; padding:6px; border-radius:4px;">
                        <input type="checkbox" name="seat_ids[]" value="<?=$s['id']?>" <?= $s['booked'] ? 'disabled' : '' ?>>
                        Seat <?=htmlspecialchars($s['seat_number'])?> <?= $s['booked'] ? '(Booked)' : '(' . htmlspecialchars($s['seat_type']) . ')' ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <br>
            <button type="submit" name="book_now">Confirm booking</button>
        </form>
    </div>
<?php else: ?>
    <p>Select a showtime to see seats.</p>
<?php endif; ?>

</div>
</body>
</html>
