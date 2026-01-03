<?php
session_start();
include 'db.php';

// Security Check
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

$uid = $_SESSION['user_id'];
$role = $_SESSION['role'];

// --- 1. HANDLE FORM SUBMISSIONS ---

// Employee: Apply for Leave
if (isset($_POST['apply'])) {
    $type = $_POST['type'];
    $start = $_POST['start'];
    $end = $_POST['end'];
    $reason = $_POST['reason'];
    // Insert with status 'Pending'
    $conn->query("INSERT INTO leaves (employee_id, leave_type, start_date, end_date, reason, status) VALUES ($uid, '$type', '$start', '$end', '$reason', 'Pending')");
    header("Location: leaves.php"); // Refresh to prevent resubmission
    exit();
}

// Admin: Approve/Reject
if (isset($_POST['action']) && $role == 'admin') {
    $lid = $_POST['lid'];
    $status = $_POST['status']; // 'Approved' or 'Rejected'
    $conn->query("UPDATE leaves SET status='$status' WHERE id=$lid");
    header("Location: leaves.php");
    exit();
}

// --- 2. DATA FETCHING ---

// Fetch User Info for Navbar
$user_info = $conn->query("SELECT * FROM employees WHERE id=$uid")->fetch_assoc();

// FETCH LEAVES BASED ON ROLE
if ($role == 'admin') {
    // Admin: Search Logic
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $sql = "SELECT l.*, e.first_name, e.last_name, e.profile_pic 
            FROM leaves l 
            JOIN employees e ON l.employee_id = e.id ";
    
    if ($search) {
        $sql .= " WHERE e.first_name LIKE '%$search%' OR e.last_name LIKE '%$search%' OR l.leave_type LIKE '%$search%'";
    }
    $sql .= " ORDER BY l.start_date DESC";
    $leaves_data = $conn->query($sql);
} else {
    // Employee: Only own history
    $leaves_data = $conn->query("SELECT * FROM leaves WHERE employee_id = $uid ORDER BY start_date DESC");
    
    // Employee: Calculate Stats (Mock Logic)
    // Total Allocation: 24 Paid, 7 Sick (Hardcoded for now, or fetch from DB if you add columns)
    $total_paid = 24;
    $total_sick = 7;
    
    // Count Used
    $used_paid = $conn->query("SELECT COUNT(*) as c FROM leaves WHERE employee_id=$uid AND leave_type='Paid Time Off' AND status='Approved'")->fetch_assoc()['c'];
    $used_sick = $conn->query("SELECT COUNT(*) as c FROM leaves WHERE employee_id=$uid AND leave_type='Sick Leave' AND status='Approved'")->fetch_assoc()['c'];
    
    $avail_paid = $total_paid - $used_paid;
    $avail_sick = $total_sick - $used_sick;
}

// Helper
function getLogo($file) { return (!empty($file) && file_exists("uploads/".$file)) ? "uploads/".$file : "uploads/default_logo.png"; }
function getImg($file) { return (!empty($file) && file_exists("uploads/".$file)) ? "uploads/".$file : "uploads/default_user.png"; }
?>

<!DOCTYPE html>
<html>
<head>
    <title>Time Off - Dayflow</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary: #6c5ce7; --bg: #f5f6fa; --dark: #2d3436; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); margin: 0; }

        /* --- NAVBAR (Same as Dashboard) --- */
        .navbar { background: white; padding: 0 30px; height: 70px; display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #ddd; }
        .nav-left { display: flex; align-items: center; gap: 30px; }
        .logo-area { display: flex; align-items: center; gap: 10px; font-weight: bold; color: var(--dark); }
        .logo-area img { height: 40px; width: auto; }
        .nav-links a { text-decoration: none; color: #636e72; font-weight: 600; padding: 22px 5px; border-bottom: 3px solid transparent; transition: 0.3s; }
        .nav-links a.active { color: var(--primary); border-bottom-color: var(--primary); }
        .nav-links a:hover { color: var(--primary); }
        .nav-right { display: flex; align-items: center; gap: 20px; }
        .profile-icon { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #eee; }
        .red-dot { width: 12px; height: 12px; background: #ff7675; border-radius: 50%; }

        /* --- CONTAINER --- */
        .container { max-width: 1200px; margin: 30px auto; background: white; border: 1px solid #000; border-radius: 4px; padding: 20px; min-height: 600px; }

        /* --- CONTROLS SECTION --- */
        .controls { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #ccc; padding-bottom: 15px; margin-bottom: 20px; }
        .tab-btn { background: #ffb8b8; padding: 8px 20px; border: 1px solid #000; font-weight: bold; margin-right: 10px; cursor: pointer; }
        .btn-new { background: #d6a2e8; color: white; padding: 8px 25px; border: 1px solid #000; font-weight: bold; cursor: pointer; border-radius: 4px; }
        
        .search-box { position: relative; }
        .search-input { padding: 8px 30px 8px 10px; border: 1px solid #000; border-radius: 20px; outline: none; width: 250px; }
        .search-icon { position: absolute; right: 10px; top: 9px; color: #666; }

        /* --- STATS ROW (Employee) --- */
        .stats-row { display: flex; justify-content: space-around; margin-bottom: 20px; text-align: center; }
        .stat-item h3 { margin: 0; color: #0984e3; }
        .stat-item p { margin: 0; font-size: 14px; color: #666; }

        /* --- TABLE --- */
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; border-bottom: 2px solid #000; border-top: 2px solid #000; }
        td { padding: 12px; border-bottom: 1px solid #ccc; border-right: 1px solid #eee; }
        td:last-child { border-right: none; }
        
        /* Status Badges */
        .badge { padding: 5px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .b-Pending { color: orange; }
        .b-Approved { color: green; }
        .b-Rejected { color: red; }

        /* --- ACTION BUTTONS (Admin) --- */
        .btn-act { border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; color: white; font-size: 12px; }
        .btn-approve { background: #00b894; }
        .btn-reject { background: #ff7675; }

        /* --- MODAL FORM (Hidden by default) --- */
        #leaveForm { display: none; margin-bottom: 20px; padding: 20px; border: 1px solid #000; background: #fafafa; border-radius: 8px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        input, select, textarea { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
    </style>
</head>
<body>

    <div class="navbar">
        <div class="nav-left">
            <div class="logo-area">
                <img src="<?php echo getLogo($user_info['company_logo']); ?>" alt="Logo">
                <span><?php echo $user_info['company_name']; ?></span>
            </div>
            <div class="nav-links">
                <a href="dashboard.php">Employees</a>
                <a href="attendance.php">Attendance</a>
                <a href="leaves.php" class="active">Time Off</a> </div>
        </div>
        <div class="nav-right">
            <div class="red-dot"></div> 
            <a href="profile.php">
                <img src="<?php echo getImg($user_info['profile_pic']); ?>" class="profile-icon">
            </a>
            <a href="logout.php" style="color:red; font-size:12px; text-decoration:none;">Logout</a>
        </div>
    </div>

    <div class="container">
        
        <div class="controls">
            <div>
                <button class="tab-btn" style="background:#ffb8b8;">Time Off</button>
                <?php if ($role == 'admin'): ?>
                    <button class="tab-btn" style="background:white; border:none;">Allocation</button>
                <?php endif; ?>
            </div>

            <?php if ($role == 'admin'): ?>
                <form method="GET" class="search-box">
                    <input type="text" name="search" class="search-input" placeholder="Searchbar" value="<?php echo isset($_GET['search']) ? $_GET['search'] : ''; ?>">
                    <i class="fas fa-search search-icon"></i>
                </form>
            <?php else: ?>
                <button onclick="toggleForm()" class="btn-new">NEW +</button>
            <?php endif; ?>
        </div>

        <div id="leaveForm">
            <h3 style="margin-top:0;">Request Time Off</h3>
            <form method="POST">
                <div class="form-grid">
                    <div>
                        <label>Time Off Type</label>
                        <select name="type">
                            <option>Paid Time Off</option>
                            <option>Sick Leave</option>
                            <option>Unpaid Leave</option>
                        </select>
                    </div>
                    <div>
                        <label>Reason</label>
                        <input type="text" name="reason" placeholder="Brief reason...">
                    </div>
                </div>
                <div class="form-grid">
                    <div><label>Start Date</label><input type="date" name="start" required></div>
                    <div><label>End Date</label><input type="date" name="end" required></div>
                </div>
                <button type="submit" name="apply" class="btn-new" style="width:100%;">Submit Request</button>
            </form>
        </div>

        <?php if ($role != 'admin'): ?>
        <div class="stats-row">
            <div class="stat-item">
                <h3>Paid Time Off</h3>
                <p><?php echo $avail_paid; ?> Days Available</p>
            </div>
            <div class="stat-item">
                <h3>Sick Time Off</h3>
                <p><?php echo $avail_sick; ?> Days Available</p>
            </div>
        </div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <?php if ($role == 'admin'): ?>
                    <th>Name</th>
                    <?php endif; ?>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Time off Type</th>
                    <th>Status</th>
                    <?php if ($role == 'admin'): ?>
                    <th>Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $leaves_data->fetch_assoc()): ?>
                <tr>
                    <?php if ($role == 'admin'): ?>
                    <td style="font-weight:bold;">
                        <?php echo $row['first_name'] . " " . $row['last_name']; ?>
                    </td>
                    <?php endif; ?>

                    <td><?php echo date('d/m/Y', strtotime($row['start_date'])); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($row['end_date'])); ?></td>
                    <td style="color:#0984e3;"><?php echo $row['leave_type']; ?></td>
                    
                    <td class="badge b-<?php echo $row['status']; ?>">
                        <?php echo $row['status']; ?>
                    </td>

                    <?php if ($role == 'admin'): ?>
                    <td>
                        <?php if($row['status'] == 'Pending'): ?>
                        <form method="POST" style="display:flex; gap:5px;">
                            <input type="hidden" name="lid" value="<?php echo $row['id']; ?>">
                            <button name="action" onclick="this.form.status.value='Approved'" class="btn-act btn-approve">✔</button>
                            <button name="action" onclick="this.form.status.value='Rejected'" class="btn-act btn-reject">✘</button>
                            <input type="hidden" name="status" value="">
                        </form>
                        <?php else: ?>
                            <span style="color:#ccc;">-</span>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

    </div>

    <script>
        function toggleForm() {
            var x = document.getElementById("leaveForm");
            if (x.style.display === "block") {
                x.style.display = "none";
            } else {
                x.style.display = "block";
            }
        }
    </script>

</body>
</html>