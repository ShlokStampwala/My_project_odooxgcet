<?php
session_start();
include 'db.php';
$uid = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Apply Logic
if (isset($_POST['apply'])) {
    $type = $_POST['type']; $start = $_POST['start']; $end = $_POST['end']; $reason = $_POST['reason'];
    $conn->query("INSERT INTO leaves (employee_id, leave_type, start_date, end_date, reason) VALUES ($uid, '$type', '$start', '$end', '$reason')");
}

// Admin Logic
if (isset($_POST['action']) && $role == 'admin') {
    $lid = $_POST['lid']; $status = $_POST['status'];
    $conn->query("UPDATE leaves SET status='$status' WHERE id=$lid");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Leaves</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; padding: 20px; background: #f8f9fa; }
        .container { max-width: 1000px; margin: auto; display: flex; gap: 20px; }
        .box { background: white; padding: 20px; border-radius: 8px; flex: 1; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        td, th { padding: 10px; border-bottom: 1px solid #eee; text-align: left; }
        .Pending { color: orange; font-weight:bold; } .Approved { color: green; font-weight:bold; } .Rejected { color: red; font-weight:bold; }
    </style>
</head>
<body>
    <div style="text-align:center; margin-bottom:20px;">
        <a href="dashboard.php" style="text-decoration:none; color:#333;">&larr; Back to Dashboard</a>
    </div>

    <div class="container">
        <div class="box">
            <h3>Request Time Off</h3>
            <form method="POST">
                <label>Type</label>
                <select name="type" style="width:100%; padding:8px; margin-bottom:10px;">
                    <option>Sick Leave</option><option>Casual Leave</option><option>Paid Time Off</option>
                </select>
                <label>From</label> <input type="date" name="start" required style="width:100%; padding:8px; margin-bottom:10px;">
                <label>To</label> <input type="date" name="end" required style="width:100%; padding:8px; margin-bottom:10px;">
                <label>Reason</label> <textarea name="reason" style="width:100%; padding:8px;" required></textarea>
                <button name="apply" style="width:100%; margin-top:10px; padding:10px; background:#6c5ce7; color:white; border:none; cursor:pointer;">Submit</button>
            </form>
        </div>

        <div class="box" style="flex:2;">
            <h3><?php echo ($role=='admin') ? 'All Requests (Admin View)' : 'My History'; ?></h3>
            <table>
                <tr><th>User</th><th>Type</th><th>Dates</th><th>Status</th><th>Action</th></tr>
                <?php
                $sql = ($role=='admin') ? "SELECT l.*, e.first_name FROM leaves l JOIN employees e ON l.employee_id=e.id" : "SELECT *, 'Me' as first_name FROM leaves WHERE employee_id=$uid";
                $res = $conn->query($sql);
                while($r = $res->fetch_assoc()):
                ?>
                <tr>
                    <td><?php echo $r['first_name']; ?></td>
                    <td><?php echo $r['leave_type']; ?></td>
                    <td><?php echo $r['start_date']; ?></td>
                    <td class="<?php echo $r['status']; ?>"><?php echo $r['status']; ?></td>
                    <td>
                        <?php if($role=='admin' && $r['status']=='Pending'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="lid" value="<?php echo $r['id']; ?>">
                                <button name="action" value="1" onclick="this.form.status.value='Approved'" style="color:green;">✔</button>
                                <button name="action" value="1" onclick="this.form.status.value='Rejected'" style="color:red;">✘</button>
                                <input type="hidden" name="status">
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>
</body>
</html>