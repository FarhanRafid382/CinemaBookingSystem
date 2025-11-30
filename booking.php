<?php
require_once "includes/db.php";
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$errors = [];
$success = "";

/* ---------------------------
   LOAD MOVIES
-----------------------------*/
$movies = $pdo->query("SELECT * FROM movie ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);

/* ---------------------------
   HANDLE USER SELECTIONS
-----------------------------*/
$selected_movie = $_POST["movie_id"] ?? "";
$selected_showtime = $_POST["showtime_id"] ?? "";
$selected_seats = $_POST["seat_ids"] ?? [];

/* ---------------------------
   LOAD SHOWTIMES FOR MOVIE
-----------------------------*/
$showtimes = [];
if (!empty($selected_movie)) {
    $stmt = $pdo->prepare("SELECT * FROM showtime WHERE movie_id = ? ORDER BY show_date, show_time");
    $stmt->execute([$selected_movie]);
    $showtimes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* ---------------------------
   LOAD AVAILABLE SEATS
-----------------------------*/
$available_seats = [];

if (!empty($selected_showtime)) {

    $seat_sql = "
        SELECT s.id, s.seat_number, s.seat_type
        FROM seat s
        WHERE s.screen_id = (
            SELECT screen_id FROM showtime WHERE id = ?
        )
        AND s.id NOT IN (
            SELECT seat_id FROM bookedseat
            WHERE booking_id IN (
                SELECT id FROM booking WHERE showtime_id = ?
            )
        )
        ORDER BY s.seat_number
    ";

    $stmt = $pdo->prepare($seat_sql);
    $stmt->execute([$selected_showtime, $selected_showtime]);
    $available_seats = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* ---------------------------
   CONFIRM BOOKING
-----------------------------*/
if (isset($_POST["confirm_booking"])) {

    if (empty($selected_movie) || empty($selected_showtime)) {
        $errors[] = "Please select a movie and showtime.";
    }

    if (empty($selected_seats)) {
        $errors[] = "Please select at least one seat.";
    }

    if (empty($errors)) {

        $seat_count = count($selected_seats);
        $price_per_seat = 10;
        $total_price = $seat_count * $price_per_seat;

        // Insert booking
        $stmt = $pdo->prepare("
            INSERT INTO booking (user_id, showtime_id, booking_time, total_price)
            VALUES (?, ?, NOW(), ?)
        ");
        $stmt->execute([$user_id, $selected_showtime, $total_price]);

        $booking_id = $pdo->lastInsertId();

        // Insert seats
        $stmt = $pdo->prepare("
            INSERT INTO bookedseat (booking_id, seat_id) VALUES (?, ?)
        ");

        foreach ($selected_seats as $seat_id) {
            $stmt->execute([$booking_id, $seat_id]);
        }

        $success = "Booking successful! Your booking ID is: " . $booking_id;
        $selected_movie = $selected_showtime = "";
        $available_seats = [];
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Book Tickets</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include "includes/header.php"; ?>

<div class="container">

    <h2>Book Tickets</h2>

    <!-- Errors -->
    <?php foreach ($errors as $e): ?>
        <p style="color:red;"><?= $e ?></p>
    <?php endforeach; ?>

    <!-- Success -->
    <?php if (!empty($success)): ?>
        <p style="color:green;"><?= $success ?></p>
    <?php endif; ?>

    <form method="post">

        <!-- Movie -->
        <label>Select movie:</label>
        <select name="movie_id" onchange="this.form.submit()">
            <option value="">-- choose --</option>
            <?php foreach ($movies as $m): ?>
                <option value="<?= $m['id'] ?>" <?= ($selected_movie == $m['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($m['title']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <br><br>

        <!-- Showtime -->
        <?php if (!empty($showtimes)): ?>
            <label>Select showtime:</label>
            <select name="showtime_id" onchange="this.form.submit()">
                <option value="">-- choose --</option>
                <?php foreach ($showtimes as $st): ?>
                    <option value="<?= $st['id'] ?>" <?= ($selected_showtime == $st['id']) ? 'selected' : '' ?>>
                        <?= $st['show_date'] ?> at <?= $st['show_time'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <br><br>
        <?php endif; ?>

        <!-- Seats -->
        <?php if (!empty($selected_showtime)): ?>

            <h3>Select seats</h3>

            <?php if (empty($available_seats)): ?>
                <p style="color:red;">No available seats for this showtime.</p>
            <?php else: ?>

                <?php foreach ($available_seats as $s): ?>
                    <label>
                        <input type="checkbox" name="seat_ids[]" value="<?= $s['id'] ?>">
                        Seat <?= $s['seat_number'] ?> (<?= $s['seat_type'] ?>)
                    </label>
                    <br>
                <?php endforeach; ?>

                <br>
                <button type="submit" name="confirm_booking">Confirm Booking</button>

            <?php endif; ?>

        <?php endif; ?>

    </form>

</div>

<?php include "includes/footer.php"; ?>

</body>
</html>
