<?php
session_start();
include 'db.php';

// Security Check
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

$uid = $_SESSION['user_id'];
$role = $_SESSION['role'];
$today = date('Y-m-d');

// --- 1. ATTENDANCE MARKING LOGIC ---
if (isset($_POST['mark'])) {
    $type = $_POST['type'];
    $now = date('H:i:s');
    if ($type == 'in') {
        $conn->query("INSERT INTO attendance (employee_id, date, check_in) VALUES ($uid, '$today', '$now')");
    } else {
        $conn->query("UPDATE attendance SET check_out='$now' WHERE employee_id=$uid AND date='$today'");
    }
    header("Location: dashboard.php");
    exit();
}

// Fetch Current User's Attendance for Buttons
$my_att = $conn->query("SELECT * FROM attendance WHERE employee_id=$uid AND date='$today'")->fetch_assoc();

// --- 2. FETCH EMPLOYEES (With Search Logic) ---
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sql_emp = "SELECT * FROM employees";

if ($search) {
    // If searching, filter by name or code
    $sql_emp .= " WHERE first_name LIKE '%$search%' OR last_name LIKE '%$search%' OR employee_code LIKE '%$search%'";
}

$all_employees = $conn->query($sql_emp);

// Fetch Logged-in User Info (For Navbar)
$user_info = $conn->query("SELECT * FROM employees WHERE id=$uid")->fetch_assoc();

// Helper Functions
function getLogo($file) { return (!empty($file) && file_exists("uploads/".$file)) ? "uploads/".$file : "uploads/default_logo.png"; }
function getImg($file) { return (!empty($file) && file_exists("uploads/".$file)) ? "uploads/".$file : "uploads/default_user.png"; }
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Dayflow</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary: #6c5ce7; --bg: #f5f6fa; --dark: #2d3436; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); margin: 0; padding: 0; }
        
        /* --- NAVBAR --- */
        .navbar {
            background: white; padding: 0 30px; height: 70px; display: flex; align-items: center;
            justify-content: space-between; border-bottom: 2px solid #eee; box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .nav-left { display: flex; align-items: center; gap: 30px; }
        .logo-area { display: flex; align-items: center; gap: 10px; font-weight: bold; color: var(--dark); }
        .logo-area img { height: 40px; width: auto; }
        
        .nav-links a {
            text-decoration: none; color: #636e72; font-weight: 600; padding: 22px 5px;
            border-bottom: 3px solid transparent; transition: 0.3s;
        }
        .nav-links a.active { color: var(--primary); border-bottom-color: var(--primary); }
        .nav-links a:hover { color: var(--primary); }

        .nav-right { display: flex; align-items: center; gap: 20px; }
        .profile-icon { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #eee; }
        .red-dot { width: 12px; height: 12px; background: #ff7675; border-radius: 50%; }

        /* --- SUB-HEADER (Search & New) --- */
        .sub-header {
            max-width: 1200px; margin: 30px auto 20px auto; display: flex; justify-content: space-between; align-items: center;
        }
        .btn-new {
            background: #d6a2e8; /* Light Purple */
            color: white; padding: 10px 25px; border: 1px solid #000; border-radius: 5px; 
            font-weight: bold; cursor: pointer; text-decoration: none; display: inline-block;
            text-transform: uppercase; letter-spacing: 1px;
        }
        .search-box { position: relative; width: 300px; }
        .search-box input {
            width: 100%; padding: 10px 15px; border-radius: 20px; border: 1px solid #000; outline: none;
        }
        .search-box i { position: absolute; right: 15px; top: 12px; color: #666; }

        /* --- ATTENDANCE WIDGET --- */
        .attendance-widget {
            max-width: 1200px; margin: 0 auto 30px auto; background: white; padding: 20px;
            border-radius: 8px; display: flex; justify-content: space-between; align-items: center;
            border: 1px solid #000; /* Wireframe style border */
        }
        .btn-mark { padding: 10px 25px; border: 1px solid #000; border-radius: 5px; font-weight: bold; cursor: pointer; color: white; }

        /* --- GRID SYSTEM --- */
        .grid-container {
            max-width: 1200px; margin: 0 auto; display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; padding-bottom: 50px;
        }

        /* --- EMPLOYEE CARD --- */
        .card {
            background: white; border: 1px solid #000; 
            border-radius: 4px; padding: 20px; position: relative;
            display: flex; flex-direction: column; align-items: center; text-align: center;
        }
        
        /* Status Indicators */
        .status-badge { position: absolute; top: 15px; right: 15px; font-size: 18px; }
        .status-present { color: #00b894; }
        .status-leave { color: #0984e3; }
        .status-absent { color: #fdcb6e; }

        .card-img {
            width: 80px; height: 80px; background: #dfe6e9; 
            border: 1px solid #000; object-fit: cover; margin-bottom: 15px;
        }
        .card-name { font-weight: bold; font-size: 16px; margin-bottom: 5px; }
        .card-role { color: gray; font-size: 13px; }
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
                <a href="dashboard.php" class="active">Employees</a>
                <a href="attendance.php">Attendance</a>
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

    <div class="attendance-widget">
        <div>
            <h2 style="margin:0;">Hello, <?php echo $user_info['first_name']; ?> 👋</h2>
            <p style="margin:5px 0; color:gray;"><?php echo date('l, d F Y'); ?></p>
        </div>
        <form method="POST">
            <?php if (!$my_att): ?>
                <button name="mark" class="btn-mark" style="background:#00b894;">Check In</button>
                <input type="hidden" name="type" value="in">
            <?php elseif ($my_att['check_in'] && !$my_att['check_out']): ?>
                <span style="margin-right: 15px; font-weight:bold;">In: <?php echo date('H:i', strtotime($my_att['check_in'])); ?></span>
                <button name="mark" class="btn-mark" style="background:#d63031;">Check Out</button>
                <input type="hidden" name="type" value="out">
            <?php else: ?>
                <button type="button" class="btn-mark" style="background:#b2bec3; cursor:not-allowed;">Day Complete</button>
            <?php endif; ?>
        </form>
    </div>

    <div class="sub-header">
        <?php if ($role == 'admin'): ?>
            <a href="signup.php" class="btn-new">NEW +</a>
        <?php else: ?>
            <div></div> <?php endif; ?>

        <form method="GET" class="search-box">
            <input type="text" name="search" placeholder="Search employees..." value="<?php echo $search; ?>">
            <i class="fas fa-search"></i>
        </form>
    </div>

    <div class="grid-container">
        <?php
        if ($all_employees->num_rows > 0):
            while($row = $all_employees->fetch_assoc()):
                $eid = $row['employee_code']; 
                $db_id = $row['id'];
                
                // --- LOGIC: STATUS INDICATOR ---
                // 1. Check Attendance (Present)
                $is_present = $conn->query("SELECT * FROM attendance WHERE employee_id=$db_id AND date='$today'")->num_rows > 0;
                
                // 2. Check Leaves (On Leave)
                $leave_check = $conn->query("SELECT * FROM leaves WHERE employee_id=$db_id AND '$today' BETWEEN start_date AND end_date AND status='Approved'");
                $is_leave = $leave_check->num_rows > 0;

                // 3. Determine Icon & Class
                if ($is_leave) {
                    $status_icon = '<i class="fas fa-plane"></i>';
                    $status_class = 'status-leave';
                    $tooltip = "On Leave";
                } elseif ($is_present) {
                    $status_icon = '<i class="fas fa-circle" style="font-size:14px;"></i>';
                    $status_class = 'status-present';
                    $tooltip = "Present";
                } else {
                    $status_icon = '<i class="fas fa-circle" style="font-size:14px;"></i>';
                    $status_class = 'status-absent';
                    $tooltip = "Absent";
                }
        ?>
        
        <div class="card">
            <div class="status-badge <?php echo $status_class; ?>" title="<?php echo $tooltip; ?>">
                <?php echo $status_icon; ?>
            </div>
            
            <img src="<?php echo getImg($row['profile_pic']); ?>" class="card-img" alt="User">
            
            <div class="card-name"><?php echo $row['first_name'] . " " . $row['last_name']; ?></div>
            <div class="card-role"><?php echo $row['designation']; ?></div> 
            
            <a href="profile.php?id=<?php echo $db_id; ?>" style="margin-top:10px; font-size:12px; text-decoration:none; color:#6c5ce7; border:1px solid #ccc; padding:5px 10px; border-radius:4px;">View Profile</a>
        </div>

        <?php endwhile; 
        else: ?>
            <p style="text-align:center; width:100%;">No employees found.</p>
        <?php endif; ?>
    </div>

</body>
</html>