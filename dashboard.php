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

// Fetch Current User's Attendance (For Widget & Navbar Dot)
$my_att = $conn->query("SELECT * FROM attendance WHERE employee_id=$uid AND date='$today'")->fetch_assoc();

// --- LOGIC: NAVBAR STATUS DOT ---
// If row exists ($my_att is true), User is Checked In (Green). Else, Not Checked In (Red).
$navbar_dot_color = ($my_att) ? "#00b894" : "#ff7675"; 

// --- 2. FETCH EMPLOYEES (With Search Logic) ---
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sql_emp = "SELECT * FROM employees";

if ($search) {
    $sql_emp .= " WHERE first_name LIKE '%$search%' OR last_name LIKE '%$search%' OR employee_code LIKE '%$search%'";
}

$all_employees = $conn->query($sql_emp);

// Fetch Logged-in User Info
$user_info = $conn->query("SELECT * FROM employees WHERE id=$uid")->fetch_assoc();

// Helper Functions
function getLogo($file) { return (!empty($file) && file_exists("uploads/".$file)) ? "uploads/".$file : "uploads/default_logo.png"; }
function getImg($file) { return (!empty($file) && file_exists("uploads/".$file)) ? "uploads/".$file : "uploads/default_user.png"; }
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Dayflow</title>
    <link rel="stylesheet" href="global.css">
    <link rel="icon" type="image/png" href="uploads/default_logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root { --primary: #6c5ce7; --bg: #f5f6fa; --dark: #2d3436; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); margin: 0; padding: 0; }
        
        /* --- NAVBAR --- */
        .navbar {
            background: white; padding: 0 30px; height: 70px; display: flex; align-items: center;
            justify-content: space-between; border-bottom: 2px solid #eee; box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            position: relative; z-index: 100;
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

        .nav-right { display: flex; align-items: center; gap: 15px; position: relative; }
        .profile-icon { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #eee; cursor: pointer; }
        
        /* Navbar Dot */
        .status-dot { width: 12px; height: 12px; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 2px #ccc; }

        /* DROPDOWN MENU */
        .dropdown { position: relative; display: inline-block; }
        .dropdown-content {
            display: none; position: absolute; right: 0; top: 50px;
            background-color: white; min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.1); z-index: 1;
            border-radius: 5px; border: 1px solid #ddd;
        }
        .dropdown-content a {
            color: black; padding: 12px 16px; text-decoration: none; display: block; font-size: 16px; border-bottom: 1px solid #eee;
        }
        .dropdown-content a:last-child { border-bottom: none; }
        .dropdown-content a:hover { background-color: #f1f1f1; }
        .dropdown:hover .dropdown-content { display: block; }

        /* --- WIDGET --- */
        .attendance-widget {
            max-width: 1200px; margin: 30px auto; background: white; padding: 20px;
            border-radius: 8px; display: flex; justify-content: space-between; align-items: center;
            border: 1px solid #000;
        }
        .btn-mark { padding: 10px 25px; border: 1px solid #000; border-radius: 5px; font-weight: bold; cursor: pointer; color: white; }

        /* --- CONTROLS --- */
        .sub-header {
            max-width: 1200px; margin: 0 auto 20px auto; display: flex; justify-content: space-between; align-items: center;
        }
        .btn-new {
            background: #d6a2e8; color: white; padding: 10px 25px; border: 1px solid #000; border-radius: 5px; 
            font-weight: bold; text-decoration: none; text-transform: uppercase;
        }
        .search-box { position: relative; width: 300px; }
        .search-box input { width: 100%; padding: 10px 15px; border-radius: 20px; border: 1px solid #000; outline: none; }
        .search-box i { position: absolute; right: 15px; top: 12px; color: #666; }

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
        
        /* STATUS ICONS */
        .card-badge { position: absolute; top: 15px; right: 15px; font-size: 20px; }
        
        .st-present { color: #00b894; } /* Green Dot */
        .st-leave { color: #0984e3; }   /* Blue Airplane */
        .st-absent { color: #fdcb6e; }  /* Yellow Dot */

        .card-img {
            width: 80px; height: 80px; background: #dfe6e9; 
            border: 1px solid #000; object-fit: cover; margin-bottom: 15px;
        }
        .card-name { font-weight: bold; font-size: 16px; margin-bottom: 5px; }
        .card-role { color: gray; font-size: 13px; }
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
                <a href="dashboard.php" class="active">Employees</a>
                <a href="attendance.php">Attendance</a>
                <a href="leaves.php">Time Off</a>
            </div>
        </div>
        <div class="nav-right">
            <div class="status-dot" style="background: <?php echo $navbar_dot_color; ?>;" title="Your Status"></div> 
            
            <div class="dropdown">
                <img src="<?php echo getImg($user_info['profile_pic']); ?>" class="profile-icon">
                <div class="dropdown-content">
                    <a href="profile.php">My Profile</a>
                    <a href="logout.php" style="color:red;">Log Out</a>
                </div>
            </div>
        </div>
    </div>

    <div class="attendance-widget">
        <div>
            <h2 style="margin:0;">Hello, <?php echo $user_info['first_name']; ?> 👋</h2>
            <p style="margin:5px 0; color:gray;"><?php echo date('l, d F Y'); ?></p>
        </div>
        <form method="POST">
            <?php if (!$my_att): ?>
                <button name="mark" class="btn-mark" style="background:#00b894;">Check In -></button>
                <input type="hidden" name="type" value="in">
            <?php elseif ($my_att['check_in'] && !$my_att['check_out']): ?>
                <span style="margin-right: 15px; font-weight:bold;">In: <?php echo date('H:i', strtotime($my_att['check_in'])); ?></span>
                <button name="mark" class="btn-mark" style="background:#d63031;">Check Out -></button>
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
            <div></div>
        <?php endif; ?>

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
                
                // --- STATUS LOGIC ---
                
                // 1. CHECK LEAVES (Priority 1)
                $leave_check = $conn->query("SELECT * FROM leaves WHERE employee_id=$db_id AND '$today' BETWEEN start_date AND end_date AND status='Approved'");
                $is_leave = $leave_check->num_rows > 0;

                // 2. CHECK ATTENDANCE (Priority 2)
                $att_check = $conn->query("SELECT * FROM attendance WHERE employee_id=$db_id AND date='$today'");
                $is_present = $att_check->num_rows > 0;

                // 3. ASSIGN ICON & COLOR
                if ($is_leave) {
                    // Airplane (Blue)
                    $icon = '<i class="fas fa-plane"></i>';
                    $class = 'st-leave';
                    $tooltip = "On Leave";
                } elseif ($is_present) {
                    // Circle (Green)
                    $icon = '<i class="fas fa-circle" style="font-size:16px;"></i>';
                    $class = 'st-present';
                    $tooltip = "Present";
                } else {
                    // Circle (Yellow)
                    $icon = '<i class="fas fa-circle" style="font-size:16px;"></i>';
                    $class = 'st-absent';
                    $tooltip = "Absent";
                }
        ?>
        
        <div class="card">
            <div class="card-badge <?php echo $class; ?>" title="<?php echo $tooltip; ?>">
                <?php echo $icon; ?>
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