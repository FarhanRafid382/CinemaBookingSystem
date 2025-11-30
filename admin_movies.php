<?php
// admin_movies.php
session_start();
require 'includes/db.php';
require 'includes/header.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
$admin_id = $_SESSION['admin_id'] ?? null;

// handle create
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_movie'])) {
        $title = trim($_POST['title']);
        $genre = trim($_POST['genre']);
        $duration = (int)$_POST['duration_minutes'];
        $release_date = $_POST['release_date'] ?: null;

        if ($title === '') $errors[] = "Title is required.";

        if (empty($errors)) {
            $ins = $pdo->prepare("INSERT INTO Movie (title, genre, duration_minutes, release_date) VALUES (?, ?, ?, ?)");
            $ins->execute([$title, $genre ?: null, $duration > 0 ? $duration : null, $release_date]);
            // log
            $log = $pdo->prepare("INSERT INTO `Log` (admin_id, user_id, action_time) VALUES (?, NULL, NOW())");
            $log->execute([$admin_id]);
            header('Location: admin_movies.php');
            exit;
        }
    }

    // handle update
    if (isset($_POST['edit_movie'])) {
        $id = (int)$_POST['movie_id'];
        $title = trim($_POST['title']);
        $genre = trim($_POST['genre']);
        $duration = (int)$_POST['duration_minutes'];
        $release_date = $_POST['release_date'] ?: null;

        if ($title === '') $errors[] = "Title is required.";

        if (empty($errors)) {
            $up = $pdo->prepare("UPDATE Movie SET title = ?, genre = ?, duration_minutes = ?, release_date = ? WHERE id = ?");
            $up->execute([$title, $genre ?: null, $duration > 0 ? $duration : null, $release_date, $id]);
            $log = $pdo->prepare("INSERT INTO `Log` (admin_id, user_id, action_time) VALUES (?, NULL, NOW())");
            $log->execute([$admin_id]);
            header('Location: admin_movies.php');
            exit;
        }
    }

    // handle delete
    if (isset($_POST['delete_movie'])) {
        $id = (int)$_POST['delete_id'];
        $del = $pdo->prepare("DELETE FROM Movie WHERE id = ?");
        $del->execute([$id]);
        $log = $pdo->prepare("INSERT INTO `Log` (admin_id, user_id, action_time) VALUES (?, NULL, NOW())");
        $log->execute([$admin_id]);
        header('Location: admin_movies.php');
        exit;
    }
}

// for edit form
$editing = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $stm = $pdo->prepare("SELECT * FROM Movie WHERE id = ?");
    $stm->execute([$eid]);
    $editing = $stm->fetch();
}

// list movies
$movies = $pdo->query("SELECT * FROM Movie ORDER BY release_date DESC, title ASC")->fetchAll();
?>

<h1>Manage Movies</h1>

<?php if($errors): ?>
    <div class="errors">
        <?php foreach($errors as $e): ?><p><?=htmlspecialchars($e)?></p><?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="grid-2">
    <div class="card">
        <h3><?= $editing ? 'Edit Movie' : 'Add Movie' ?></h3>
        <form method="post">
            <input type="hidden" name="movie_id" value="<?= $editing ? (int)$editing['id'] : '' ?>">
            <label>Title</label>
            <input name="title" required value="<?= $editing ? htmlspecialchars($editing['title']) : '' ?>">
            <label>Genre</label>
            <input name="genre" value="<?= $editing ? htmlspecialchars($editing['genre']) : '' ?>">
            <label>Duration (minutes)</label>
            <input type="number" name="duration_minutes" value="<?= $editing ? (int)$editing['duration_minutes'] : '' ?>">
            <label>Release date</label>
            <input type="date" name="release_date" value="<?= $editing ? htmlspecialchars($editing['release_date']) : '' ?>">
            <?php if ($editing): ?>
                <button type="submit" name="edit_movie">Update Movie</button>
                <a class="btn" href="admin_movies.php">Cancel</a>
            <?php else: ?>
                <button type="submit" name="add_movie">Add Movie</button>
            <?php endif; ?>
        </form>
    </div>

    <div class="card">
        <h3>Existing Movies</h3>
        <?php if (count($movies) === 0): ?>
            <p>No movies found.</p>
        <?php else: ?>
            <table>
                <thead><tr><th>Title</th><th>Genre</th><th>Duration</th><th>Release</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach($movies as $m): ?>
                    <tr>
                        <td><?=htmlspecialchars($m['title'])?></td>
                        <td><?=htmlspecialchars($m['genre'])?></td>
                        <td><?= (int)$m['duration_minutes'] ?></td>
                        <td><?= htmlspecialchars($m['release_date']) ?></td>
                        <td>
                            <a href="admin_movies.php?edit=<?= $m['id'] ?>">Edit</a>
                            <form method="post" style="display:inline" onsubmit="return confirm('Delete movie?');">
                                <input type="hidden" name="delete_id" value="<?= $m['id'] ?>">
                                <button type="submit" name="delete_movie">Delete</button>
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
