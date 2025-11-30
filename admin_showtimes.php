<?php
// admin_showtimes.php
session_start();
require 'includes/db.php';
require 'includes/header.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
$admin_id = $_SESSION['admin_id'] ?? null;

// load lists for dropdowns
$movies = $pdo->query("SELECT id, title FROM Movie ORDER BY title")->fetchAll();
$screens = $pdo->query("
    SELECT sc.id, sc.screen_number, t.name AS theater_name
    FROM Screen sc
    JOIN Theater t ON sc.theater_id = t.id
    ORDER BY t.name, sc.screen_number
")->fetchAll();

// handle add/update/delete
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_showtime'])) {
        $movie_id = (int)$_POST['movie_id'];
        $screen_id = (int)$_POST['screen_id'];
        $show_date = $_POST['show_date'] ?: null;
        $show_time = $_POST['show_time'] ?: null;
        if (!$movie_id || !$screen_id || !$show_date || !$show_time) $errors[] = "All fields required.";

        if (empty($errors)) {
            $ins = $pdo->prepare("INSERT INTO Showtime (movie_id, screen_id, show_date, show_time) VALUES (?, ?, ?, ?)");
            $ins->execute([$movie_id, $screen_id, $show_date, $show_time]);
            $log = $pdo->prepare("INSERT INTO `Log` (admin_id, user_id, action_time) VALUES (?, NULL, NOW())");
            $log->execute([$admin_id]);
            header('Location: admin_showtimes.php');
            exit;
        }
    }

    if (isset($_POST['edit_showtime'])) {
        $id = (int)$_POST['showtime_id'];
        $movie_id = (int)$_POST['movie_id'];
        $screen_id = (int)$_POST['screen_id'];
        $show_date = $_POST['show_date'] ?: null;
        $show_time = $_POST['show_time'] ?: null;
        if (!$movie_id || !$screen_id || !$show_date || !$show_time) $errors[] = "All fields required.";

        if (empty($errors)) {
            $up = $pdo->prepare("UPDATE Showtime SET movie_id = ?, screen_id = ?, show_date = ?, show_time = ? WHERE id = ?");
            $up->execute([$movie_id, $screen_id, $show_date, $show_time, $id]);
            $log = $pdo->prepare("INSERT INTO `Log` (admin_id, user_id, action_time) VALUES (?, NULL, NOW())");
            $log->execute([$admin_id]);
            header('Location: admin_showtimes.php');
            exit;
        }
    }

    if (isset($_POST['delete_showtime'])) {
        $id = (int)$_POST['delete_id'];
        $del = $pdo->prepare("DELETE FROM Showtime WHERE id = ?");
        $del->execute([$id]);
        $log = $pdo->prepare("INSERT INTO `Log` (admin_id, user_id, action_time) VALUES (?, NULL, NOW())");
        $log->execute([$admin_id]);
        header('Location: admin_showtimes.php');
        exit;
    }
}

// editing
$editing = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $stm = $pdo->prepare("SELECT * FROM Showtime WHERE id = ?");
    $stm->execute([$eid]);
    $editing = $stm->fetch();
}

// list showtimes with joins
$list = $pdo->query("
    SELECT st.*, m.title AS movie_title, sc.screen_number, t.name AS theater_name
    FROM Showtime st
    JOIN Movie m ON st.movie_id = m.id
    JOIN Screen sc ON st.screen_id = sc.id
    JOIN Theater t ON sc.theater_id = t.id
    ORDER BY st.show_date ASC, st.show_time ASC
")->fetchAll();
?>

<h1>Manage Showtimes</h1>

<?php if ($errors): foreach($errors as $e): ?>
    <p class="error"><?=htmlspecialchars($e)?></p>
<?php endforeach; endif; ?>

<div class="grid-2">
    <div class="card">
        <h3><?= $editing ? 'Edit Showtime' : 'Add Showtime' ?></h3>
        <form method="post">
            <input type="hidden" name="showtime_id" value="<?= $editing ? (int)$editing['id'] : '' ?>">
            <label>Movie</label>
            <select name="movie_id" required>
                <option value="">-- select movie --</option>
                <?php foreach($movies as $m): ?>
                    <option value="<?= $m['id'] ?>" <?= $editing && $editing['movie_id'] == $m['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Screen (Theater)</label>
            <select name="screen_id" required>
                <option value="">-- select screen --</option>
                <?php foreach($screens as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $editing && $editing['screen_id'] == $s['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['theater_name'] . " — Screen " . $s['screen_number']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Date</label>
            <input type="date" name="show_date" value="<?= $editing ? htmlspecialchars($editing['show_date']) : '' ?>" required>
            <label>Time</label>
            <input type="time" name="show_time" value="<?= $editing ? htmlspecialchars($editing['show_time']) : '' ?>" required>

            <?php if ($editing): ?>
                <button type="submit" name="edit_showtime">Update Showtime</button>
                <a class="btn" href="admin_showtimes.php">Cancel</a>
            <?php else: ?>
                <button type="submit" name="add_showtime">Add Showtime</button>
            <?php endif; ?>
        </form>
    </div>

    <div class="card">
        <h3>Existing Showtimes</h3>
        <?php if (count($list) === 0): ?>
            <p>No showtimes found.</p>
        <?php else: ?>
            <table>
                <thead><tr><th>Movie</th><th>Theater / Screen</th><th>Date</th><th>Time</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($list as $row): ?>
                    <tr>
                        <td><?=htmlspecialchars($row['movie_title'])?></td>
                        <td><?=htmlspecialchars($row['theater_name']).' / Screen '.htmlspecialchars($row['screen_number'])?></td>
                        <td><?=htmlspecialchars($row['show_date'])?></td>
                        <td><?=substr(htmlspecialchars($row['show_time']),0,5)?></td>
                        <td>
                            <a href="admin_showtimes.php?edit=<?= $row['id'] ?>">Edit</a>
                            <form method="post" style="display:inline" onsubmit="return confirm('Delete showtime?');">
                                <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                                <button type="submit" name="delete_showtime">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<p><a class="btn" href="admin_dashboard.php">Back to Dashboard</a></p>

</div>
</body>
</html>
