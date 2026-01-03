<?php
session_start();
include 'db.php';

// Security Check
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

$uid = $_SESSION['user_id'];
$role = $_SESSION['role'];

// --- HELPER FUNCTION: Calculate Hours ---
function calculateHours($in, $out) {
    if ($in && $out) {
        $start = strtotime($in);
        $end = strtotime($out);
        $diff = $end - $start;
        $hours = floor($diff / 3600);
        $minutes = floor(($diff % 3600) / 60);
        
        $work_hours = sprintf("%02d:%02d", $hours, $minutes);
        
        $extra_seconds = $diff - (9 * 3600);
        if ($extra_seconds > 0) {
            $ex_h = floor($extra_seconds / 3600);
            $ex_m = floor(($extra_seconds % 3600) / 60);
            $extra_hours = sprintf("%02d:%02d", $ex_h, $ex_m);
        } else {
            $extra_hours = "00:00";
        }
        return ['work' => $work_hours, 'extra' => $extra_hours];
    }
    return ['work' => '-', 'extra' => '-'];
}

// --- DATA FETCHING LOGIC ---

// 1. ADMIN LOGIC
if ($role == 'admin') {
    $selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
    $prev_date = date('Y-m-d', strtotime($selected_date . ' -1 day'));
    $next_date = date('Y-m-d', strtotime($selected_date . ' +1 day'));
    $display_day = date('l', strtotime($selected_date)); 

    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $sql = "SELECT a.*, e.first_name, e.last_name 
            FROM attendance a 
            JOIN employees e ON a.employee_id = e.id 
            WHERE a.date = '$selected_date'";
    
    if ($search) {
        $sql .= " AND (e.first_name LIKE '%$search%' OR e.last_name LIKE '%$search%')";
    }
    $attendance_data = $conn->query($sql);
} 

// 2. EMPLOYEE LOGIC (ERROR FIXED HERE)
else {
    $current_month = date('m');
    $current_year = date('Y');
    
    // FIX: Removed "AND status='Present'" because the column doesn't exist.
    // If a row exists, the employee is Present.
    $present_sql = "SELECT COUNT(*) as count FROM attendance WHERE employee_id=$uid AND MONTH(date)='$current_month'";
    $present_count = $conn->query($present_sql)->fetch_assoc()['count'];

    // Count Leaves (This table DOES have status, so this is fine)
    $leave_sql = "SELECT COUNT(*) as count FROM leaves WHERE employee_id=$uid AND MONTH(start_date)='$current_month' AND status='Approved'";
    $leave_count = $conn->query($leave_sql)->fetch_assoc()['count'];

    $total_working_days = 26; 

    $history_sql = "SELECT * FROM attendance WHERE employee_id=$uid ORDER BY date DESC";
    $attendance_data = $conn->query($history_sql);
}

// Helper for Images
function getLogo($file) { return (!empty($file) && file_exists("uploads/".$file)) ? "uploads/".$file : "uploads/default_logo.png"; }
function getImg($file) { return (!empty($file) && file_exists("uploads/".$file)) ? "uploads/".$file : "uploads/default_user.png"; }

$user_info = $conn->query("SELECT * FROM employees WHERE id=$uid")->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Attendance View</title>
    <link rel="icon" type="image/png" href="uploads/logo.jpg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary: #6c5ce7; --bg: #f5f6fa; --dark: #2d3436; }
        body { font-family: 'Segoe UI', sans-serif; margin: 0; background: var(--bg); }
        
        /* NAVBAR */
        .navbar { background: white; padding: 0 30px; height: 70px; display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #eee; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .nav-left { display: flex; align-items: center; gap: 30px; }
        .logo-area { display: flex; align-items: center; gap: 10px; font-weight: bold; color: var(--dark); }
        .logo-area img { height: 40px; width: auto; }
        .nav-links a { text-decoration: none; color: #636e72; font-weight: 600; padding: 22px 5px; border-bottom: 3px solid transparent; transition: 0.3s; }
        .nav-links a.active { color: var(--primary); border-bottom-color: var(--primary); }
        .nav-right { display: flex; align-items: center; gap: 20px; }
        .profile-icon { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #eee; }
        .red-dot { width: 12px; height: 12px; background: #ff7675; border-radius: 50%; }
        
        /* CONTAINER */
        .container { max-width: 1200px; margin: 30px auto; background: white; border: 1px solid #999; border-radius: 4px; padding: 20px; min-height: 600px; }
        
        .header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .page-title { font-size: 24px; font-weight: bold; color: #333; }
        
        .admin-controls { display: flex; gap: 10px; align-items: center; }
        .search-bar { padding: 8px; border: 1px solid #000; border-radius: 20px; width: 250px; outline: none; }
        .nav-btn { padding: 5px 15px; border: 1px solid #000; background: white; cursor: pointer; font-weight: bold; text-decoration: none; color: black; }
        .date-display { padding: 5px 20px; border: 1px solid #000; font-weight: bold; min-width: 120px; text-align: center; }
        
        .stats-row { display: flex; gap: 10px; }
        .stat-box { border: 1px solid #000; padding: 5px 15px; text-align: center; border-radius: 4px; min-width: 100px; }
        .stat-label { font-size: 12px; color: #666; display: block; }
        .stat-val { font-weight: bold; font-size: 16px; }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { text-align: left; padding: 12px; border-bottom: 2px solid #000; border-top: 2px solid #000; font-weight: bold; color: #444; }
        td { padding: 12px; border-bottom: 1px solid #ccc; border-right: 1px solid #eee; }
        td:last-child { border-right: none; }
        tr:nth-child(even) { background-color: #fafafa; }
    </style>
    <link rel="stylesheet" href="global.css">
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
                <a href="attendance.php" class="active">Attendance</a>
                <a href="leaves.php">Time Off</a>
            </div>
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
        
        <div class="header-row">
            <div class="page-title">Attendance</div>
            
            <?php if ($role == 'admin'): ?>
                <form method="GET">
                    <input type="text" name="search" class="search-bar" placeholder="Search employee..." value="<?php echo isset($_GET['search']) ? $_GET['search'] : ''; ?>">
                    <input type="hidden" name="date" value="<?php echo $selected_date; ?>">
                </form>
            <?php else: ?>
                <div class="stats-row">
                    <div class="stat-box">
                        <span class="stat-label">Count of days present</span>
                        <span class="stat-val"><?php echo $present_count; ?></span>
                    </div>
                    <div class="stat-box">
                        <span class="stat-label">Leaves count</span>
                        <span class="stat-val"><?php echo $leave_count; ?></span>
                    </div>
                    <div class="stat-box">
                        <span class="stat-label">Total working days</span>
                        <span class="stat-val"><?php echo $total_working_days; ?></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($role == 'admin'): ?>
            <div class="admin-controls" style="margin-bottom: 20px;">
                <a href="?date=<?php echo $prev_date; ?>" class="nav-btn">&lt;-</a>
                <a href="?date=<?php echo $next_date; ?>" class="nav-btn">-&gt;</a>
                
                <div class="date-display"><?php echo date('d, F Y', strtotime($selected_date)); ?></div>
                <div class="date-display" style="background:#eee;"><?php echo $display_day; ?></div>
            </div>
        <?php else: ?>
             <div class="admin-controls" style="margin-bottom: 20px;">
                <button class="nav-btn">&lt;-</button>
                <button class="nav-btn">-&gt;</button>
                <div class="date-display"><?php echo date('F Y'); ?> v</div>
            </div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <?php if ($role == 'admin'): ?>
                        <th style="width:200px;">Emp</th> 
                    <?php else: ?>
                        <th style="width:150px;">Date</th> 
                    <?php endif; ?>
                    
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th>Work Hours</th>
                    <th>Extra Hours</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($attendance_data->num_rows > 0): ?>
                    <?php while($row = $attendance_data->fetch_assoc()): 
                        $time_data = calculateHours($row['check_in'], $row['check_out']);
                    ?>
                    <tr>
                        <?php if ($role == 'admin'): ?>
                            <td>
                                <div style="font-weight:bold;"><?php echo $row['first_name'] . " " . $row['last_name']; ?></div>
                            </td>
                        <?php else: ?>
                            <td><?php echo date('d/m/Y', strtotime($row['date'])); ?></td>
                        <?php endif; ?>

                        <td><?php echo date('H:i', strtotime($row['check_in'])); ?></td>
                        
                        <td>
                            <?php echo ($row['check_out']) ? date('H:i', strtotime($row['check_out'])) : '--:--'; ?>
                        </td>
                        
                        <td><?php echo $time_data['work']; ?></td>
                        <td><?php echo $time_data['extra']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align:center; padding:30px; color:gray;">No records found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

    </div>

</body>
</html>