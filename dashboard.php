<?php
session_start();
include 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

$uid = $_SESSION['user_id'];
$today = date('Y-m-d');

// Fetch user info
$user = $conn->query("SELECT * FROM employees WHERE id=$uid")->fetch_assoc();

// Attendance Logic
if (isset($_POST['mark'])) {
    $type = $_POST['type'];
    $now = date('H:i:s');
    if ($type == 'in') {
        $conn->query("INSERT INTO attendance (employee_id, date, check_in) VALUES ($uid, '$today', '$now')");
    } else {
        $conn->query("UPDATE attendance SET check_out='$now' WHERE employee_id=$uid AND date='$today'");
    }
    header("Location: dashboard.php");
}
$att = $conn->query("SELECT * FROM attendance WHERE employee_id=$uid AND date='$today'")->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f8f9fa; margin: 0; }
        .nav { background: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #ddd; }
        .nav a { text-decoration: none; color: #333; margin-left: 20px; font-weight: 500; }
        .container { max-width: 1000px; margin: 30px auto; padding: 20px; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; color: white; cursor: pointer; font-weight: bold; }
        .btn-in { background: #00b894; } .btn-out { background: #d63031; } .btn-done { background: #b2bec3; cursor: not-allowed; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
    </style>
</head>
<body>
    <div class="nav">
        <div style="font-weight:bold; color:#6c5ce7; font-size:20px;">Dayflow</div>
        <div>
            <a href="dashboard.php" style="color:#6c5ce7;">Dashboard</a>
            <a href="profile.php">My Profile</a>
            <a href="leaves.php">Leaves</a>
            <a href="logout.php" style="color:red;">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="card" style="display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h1>Welcome, <?php echo $user['first_name']; ?> 👋</h1>
                <p>Company: <b><?php echo $user['company_name']; ?></b></p>
                <p>Employee ID: <b style="color:#6c5ce7;"><?php echo $user['employee_code']; ?></b></p>
            </div>
            <div style="text-align:right;">
                <p>Date: <?php echo date('d M Y'); ?></p>
                <form method="POST">
                    <?php if (!$att): ?>
                        <button name="mark" class="btn btn-in">Check In</button>
                        <input type="hidden" name="type" value="in">
                    <?php elseif (!$att['check_out']): ?>
                        <p style="margin:5px 0;">In: <?php echo $att['check_in']; ?></p>
                        <button name="mark" class="btn btn-out">Check Out</button>
                        <input type="hidden" name="type" value="out">
                    <?php else: ?>
                        <button type="button" class="btn btn-done" disabled>Attendance Completed</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <h3>Team Directory</h3>
        <div class="grid">
            <?php
            $res = $conn->query("SELECT * FROM employees LIMIT 8");
            while($row = $res->fetch_assoc()):
            ?>
            <div class="card" style="text-align:center;">
                <img src="uploads/<?php echo $row['image']; ?>" style="width:60px; height:60px; border-radius:50%; object-fit:cover;">
                <h4><?php echo $row['first_name'] . " " . $row['last_name']; ?></h4>
                <small style="color:gray;"><?php echo $row['designation']; ?></small><br>
                <small style="color:#6c5ce7; font-weight:bold;"><?php echo $row['employee_code']; ?></small>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</body>
</html>