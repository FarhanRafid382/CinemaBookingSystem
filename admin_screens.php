<?php
// admin_screens.php
session_start();
require 'includes/db.php';
require 'includes/header.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
$admin_id = $_SESSION['admin_id'] ?? null;

// load theaters for dropdown
$theaters = $pdo->query("SELECT id, name FROM Theater ORDER BY name")->fetchAll();

// handle add/update/delete
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_screen'])) {
        $theater_id = (int)$_POST['theater_id'];
        $screen_number = (int)$_POST['screen_number'];
        $total_seats = (int)$_POST['total_seats'];

        if (!$theater_id) $errors[] = "Select theater.";
        if ($screen_number <= 0) $errors[] = "Screen number required.";
        if ($total_seats <= 0) $errors[] = "Total seats required.";

        if (empty($errors)) {
            $ins = $pdo->prepare("INSERT INTO Screen (theater_id, screen_number, total_seats) VALUES (?, ?, ?)");
            $ins->execute([$theater_id, $screen_number, $total_seats]);
            $log = $pdo->prepare("INSERT INTO `Log` (admin_id, user_id, action_time) VALUES (?, NULL, NOW())");
            $log->execute([$admin_id]);
            header('Location: admin_screens.php');
            exit;
        }
    }

    if (isset($_POST['edit_screen'])) {
        $id = (int)$_POST['screen_id'];
        $theater_id = (int)$_POST['theater_id'];
        $screen_number = (int)$_POST['screen_number'];
        $total_seats = (int)$_POST['total_seats'];

        if (!$theater_id) $errors[] = "Select theater.";
        if ($screen_number <= 0) $errors[] = "Screen number required.";
        if ($total_seats <= 0) $errors[] = "Total seats required.";

        if (empty($errors)) {
            $up = $pdo->prepare("UPDATE Screen SET theater_id = ?, screen_number = ?, total_seats = ? WHERE id = ?");
            $up->execute([$theater_id, $screen_number, $total_seats, $id]);
            $log = $pdo->prepare("INSERT INTO `Log` (admin_id, user_id, action_time) VALUES (?, NULL, NOW())");
            $log->execute([$admin_id]);
            header('Location: admin_screens.php');
            exit;
        }
    }

    if (isset($_POST['delete_screen'])) {
        $id = (int)$_POST['delete_id'];
        $del = $pdo->prepare("DELETE FROM Screen WHERE id = ?");
        $del->execute([$id]);
        $log = $pdo->prepare("INSERT INTO `Log` (admin_id, user_id, action_time) VALUES (?, NULL, NOW())");
        $log->execute([$admin_id]);
        header('Location: admin_screens.php');
        exit;
    }
}

// editing
$editing = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $stm = $pdo->prepare("SELECT * FROM Screen WHERE id = ?");
    $stm->execute([$eid]);
    $editing = $stm->fetch();
}

// list screens with theater name
$screens = $pdo->query("SELECT sc.*, t.name AS theater_name FROM Screen sc JOIN Theater t ON sc.theater_id = t.id ORDER BY t.name, sc.screen_number")->fetchAll();
?>

<h1>Manage Screens</h1>

<?php if ($errors): foreach($errors as $e): ?>
    <p class="error"><?=htmlspecialchars($e)?></p>
<?php endforeach; endif; ?>

<div class="grid-2">
    <div class="card">
        <h3><?= $editing ? 'Edit Screen' : 'Add Screen' ?></h3>
        <form method="post">
            <input type="hidden" name="screen_id" value="<?= $editing ? (int)$editing['id'] : '' ?>">

            <label>Theater</label>
            <select name="theater_id" required>
                <option value="">-- select theater --</option>
                <?php foreach($theaters as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= $editing && $editing['theater_id'] == $t['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Screen number</label>
            <input type="number" name="screen_number" value="<?= $editing ? (int)$editing['screen_number'] : '' ?>" required>

            <label>Total seats</label>
            <input type="number" name="total_seats" value="<?= $editing ? (int)$editing['total_seats'] : '' ?>" required>

            <?php if ($editing): ?>
                <button type="submit" name="edit_screen">Update Screen</button>
                <a class="btn" href="admin_screens.php">Cancel</a>
            <?php else: ?>
                <button type="submit" name="add_screen">Add Screen</button>
            <?php endif; ?>
        </form>
    </div>

    <div class="card">
        <h3>Existing Screens</h3>
        <?php if (count($screens) === 0): ?>
            <p>No screens found.</p>
        <?php else: ?>
            <table>
                <thead><tr><th>Theater</th><th>Screen #</th><th>Total seats</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach($screens as $s): ?>
                    <tr>
                        <td><?=htmlspecialchars($s['theater_name'])?></td>
                        <td><?= (int)$s['screen_number'] ?></td>
                        <td><?= (int)$s['total_seats'] ?></td>
                        <td>
                            <a href="admin_screens.php?edit=<?= $s['id'] ?>">Edit</a>
                            <form method="post" style="display:inline" onsubmit="return confirm('Delete screen?');">
                                <input type="hidden" name="delete_id" value="<?= $s['id'] ?>">
                                <button type="submit" name="delete_screen">Delete</button>
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
