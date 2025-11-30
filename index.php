<?php
// index.php
require 'includes/db.php';
require 'includes/header.php';

// Fetch upcoming showtimes with movie, theater, screen and available seats
$sql = "
SELECT
  s.id AS showtime_id,
  m.id AS movie_id, m.title, m.genre, m.duration_minutes, m.release_date,
  sc.id AS screen_id, sc.screen_number, sc.total_seats,
  t.id AS theater_id, t.name AS theater_name, t.location,
  s.show_date, s.show_time,
  (sc.total_seats - IFNULL(bs.booked_count,0)) AS seats_available,
  IFNULL(bs.booked_count,0) AS seats_booked
FROM Showtime s
JOIN Movie m ON s.movie_id = m.id
JOIN Screen sc ON s.screen_id = sc.id
JOIN Theater t ON sc.theater_id = t.id
LEFT JOIN (
    SELECT st.id AS stid, st.screen_id, COUNT(bs.id) AS booked_count
    FROM Showtime st
    JOIN Booking b ON b.showtime_id = st.id
    JOIN BookedSeat bs ON bs.booking_id = b.id
    GROUP BY st.id
) bs ON bs.stid = s.id
ORDER BY s.show_date ASC, s.show_time ASC
LIMIT 100
";
$stmt = $pdo->query($sql);
$showtimes = $stmt->fetchAll();
?>

<h1>Now Showing / Upcoming</h1>

<?php if(count($showtimes) === 0): ?>
    <p>No showtimes found.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Movie</th>
                <th>Genre</th>
                <th>Theater / Screen</th>
                <th>Date</th>
                <th>Time</th>
                <th>Available Seats</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($showtimes as $st): ?>
            <tr>
                <td><?=htmlspecialchars($st['title'])?></td>
                <td><?=htmlspecialchars($st['genre'])?></td>
                <td><?=htmlspecialchars($st['theater_name'])." / Screen ".$st['screen_number']?></td>
                <td><?=htmlspecialchars($st['show_date'])?></td>
                <td><?=htmlspecialchars(substr($st['show_time'],0,5))?></td>
                <td><?= (int)$st['seats_available'] ?> (booked: <?= (int)$st['seats_booked'] ?>)</td>
                <td>
                    <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'user'): ?>
                        <a href="booking.php?showtime_id=<?= $st['showtime_id'] ?>">Book</a>
                    <?php else: ?>
                        <a href="login.php">Login to book</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

</div>
</body>
</html>
