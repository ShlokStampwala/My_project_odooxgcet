<?php
session_start();
include 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

// --- 1. IDENTIFY VIEWER & TARGET ---
$logged_in_id = $_SESSION['user_id'];
$viewer_role = $_SESSION['role']; 

// Determine whose profile to show
$target_id = isset($_GET['id']) ? $_GET['id'] : $logged_in_id;

// --- 2. PERMISSION LOGIC ---
$is_admin = ($viewer_role == 'admin');
$is_own_profile = ($logged_in_id == $target_id);

// EDIT PERMISSION: 
// You can edit if you are Admin OR if it's your own profile.
// If viewing someone else, you cannot edit.
$can_edit = ($is_admin || $is_own_profile);
$input_status = $can_edit ? "" : "disabled";

// SALARY VISIBILITY:
// Only Admin sees the Salary Tab. Employees (even on their own profile) do not see it.
$show_salary_tab = $is_admin; 

// --- 3. HANDLE UPDATES ---
$msg = "";
if (isset($_POST['update_profile'])) {
    // Backend Security Check: Stop if trying to edit someone else without permission
    if (!$can_edit) {
        die("You are not authorized to edit this profile.");
    }

    // Basic Info Update
    $dob = $_POST['dob']; $addr = $_POST['address']; $nat = $_POST['nationality'];
    $p_email = $_POST['personal_email']; $gen = $_POST['gender']; $mar = $_POST['marital'];
    $bank = $_POST['bank_name']; $acc = $_POST['account_number']; $ifsc = $_POST['ifsc_code'];
    $pan = $_POST['pan_number']; $uan = $_POST['uan_number'];
    
    // Manager/Location (Usually Admin only, but allowing edit if $can_edit for now)
    $manager = $_POST['manager']; $loc = $_POST['location'];
    
    $stmt = $conn->prepare("UPDATE employees SET dob=?, address=?, nationality=?, personal_email=?, gender=?, marital_status=?, bank_name=?, account_number=?, ifsc_code=?, pan_number=?, uan_number=?, manager=?, location=? WHERE id=?");
    $stmt->bind_param("sssssssssssssi", $dob, $addr, $nat, $p_email, $gen, $mar, $bank, $acc, $ifsc, $pan, $uan, $manager, $loc, $target_id);
    $stmt->execute();
    
    // Update Salary (Strictly Admin Only)
    if ($is_admin) {
        $mw = $_POST['month_wage']; $yw = $_POST['yearly_wage']; $wd = $_POST['working_days'];
        $bt = $_POST['break_time']; $bs = $_POST['basic_salary']; $hra = $_POST['hra'];
        $sa = $_POST['standard_allowance']; $pb = $_POST['performance_bonus']; $lta = $_POST['lta'];
        $fa = $_POST['fixed_allowance']; $pfe = $_POST['pf_employee']; $pfr = $_POST['pf_employer'];
        $pt = $_POST['professional_tax'];
        
        $sql_sal = "UPDATE employees SET month_wage='$mw', yearly_wage='$yw', working_days='$wd', break_time='$bt', basic_salary='$bs', hra='$hra', standard_allowance='$sa', performance_bonus='$pb', lta='$lta', fixed_allowance='$fa', pf_employee='$pfe', pf_employer='$pfr', professional_tax='$pt' WHERE id=$target_id";
        $conn->query($sql_sal);
    }
    $msg = "Profile Updated Successfully! ✅";
}

// Fetch User Data
$emp = $conn->query("SELECT * FROM employees WHERE id = $target_id")->fetch_assoc();
// Fetch Logged in user info for Navbar
$current_user = $conn->query("SELECT * FROM employees WHERE id = $logged_in_id")->fetch_assoc();

// Helper for Image
function getImg($file) { return (!empty($file) && file_exists("uploads/".$file)) ? "uploads/".$file : "uploads/default_user.png"; }
function getLogo($file) { return (!empty($file) && file_exists("uploads/".$file)) ? "uploads/".$file : "uploads/default_logo.png"; }
?>

<!DOCTYPE html>
<html>
<head>
    <title>Profile - Dayflow</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f8f9fa; margin: 0; }
        .navbar { background: white; padding: 0 30px; height: 70px; display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #ddd; }
        .nav-links a { text-decoration: none; color: #333; font-weight: 600; padding: 22px 15px; margin: 0 5px; }
        .nav-links a:hover, .nav-links a.active { color: #6c5ce7; border-bottom: 3px solid #6c5ce7; }
        
        .container { max-width: 1100px; margin: 30px auto; background: white; border: 1px solid #333; border-radius: 5px; overflow: hidden; }
        
        .header { padding: 30px; display: grid; grid-template-columns: 140px 1fr 1fr; gap: 30px; border-bottom: 2px solid #333; }
        .img-circle { width: 120px; height: 120px; border-radius: 50%; border: 2px solid #333; object-fit: cover; background: #eee; }
        
        .header-col input { border: none; border-bottom: 1px solid #333; width: 100%; padding: 5px; background: transparent; margin-bottom: 8px; font-size: 14px; }
        .header-col label { font-size: 11px; color: #666; display: block; text-transform: uppercase; font-weight: bold; }
        
        .tabs { display: flex; background: #eee; border-bottom: 2px solid #333; }
        .tab { padding: 15px 30px; cursor: pointer; border-right: 1px solid #ccc; font-weight: bold; color: #555; }
        .tab.active { background: white; border-bottom: 3px solid #333; color: black; margin-bottom: -2px; }
        
        .content { padding: 40px; display: none; }
        .content.active { display: block; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 60px; }
        
        /* Form Styling */
        .form-row { display: flex; justify-content: space-between; margin-bottom: 12px; align-items: center; }
        .form-row label { font-weight: 500; font-size: 14px; min-width: 140px; color: #333; }
        .form-row input, .form-row select { border: none; border-bottom: 2px solid #333; width: 100%; padding: 5px; outline: none; background: transparent; }
        
        /* Disabled Input Style (Grayed out) */
        .form-row input:disabled, .form-row select:disabled { color: #777; border-bottom: 1px dashed #ccc; cursor: not-allowed; background: #f9f9f9; }
        
        .section-title { font-weight: bold; border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-bottom: 15px; color: #6c5ce7; }
        .btn-save { float: right; padding: 12px 30px; background: #6c5ce7; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; margin-top: 20px; }
    </style>
</head>
<body>

    <div class="navbar">
        <div style="font-weight:bold; font-size:20px; display:flex; align-items:center; gap:10px;">
            <img src="<?php echo getLogo($current_user['company_logo']); ?>" height="30">
            <?php echo $current_user['company_name']; ?>
        </div>
        <div class="nav-links">
            <a href="dashboard.php">Employees</a>
            <a href="attendance.php">Attendance</a>
            <a href="leaves.php">Time Off</a>
        </div>
        <div><a href="logout.php" style="color:red; text-decoration:none; font-weight:bold;">Logout</a></div>
    </div>

    <div class="container">
        
        <?php if(!$can_edit): ?>
            <div style="background:#fff3cd; color:#856404; padding:10px; text-align:center; border-bottom:1px solid #ffeeba;">
                <i class="fas fa-eye"></i> You are viewing <b><?php echo $emp['first_name']; ?>'s</b> profile (Read-Only)
            </div>
        <?php endif; ?>

        <form method="POST">
            
            <div class="header">
                <div style="text-align:center;">
                    <img src="<?php echo getImg($emp['profile_pic']); ?>" class="img-circle">
                </div>
                <div class="header-col">
                    <h2 style="margin:0 0 15px 0;"><?php echo $emp['first_name'] . ' ' . $emp['last_name']; ?></h2>
                    <label>Designation</label> <input type="text" value="<?php echo $emp['designation']; ?>" disabled>
                    <label>Work Email</label> <input type="text" value="<?php echo $emp['email']; ?>" disabled>
                    <label>Mobile</label> <input type="text" value="<?php echo $emp['phone']; ?>" disabled>
                </div>
                <div class="header-col">
                    <label>Company</label> <input type="text" value="<?php echo $emp['company_name']; ?>" disabled>
                    <label>Department</label> <input type="text" value="<?php echo $emp['department']; ?>" disabled>
                    <label>Manager</label> <input type="text" name="manager" value="<?php echo $emp['manager']; ?>" <?php echo $input_status; ?>>
                    <label>Location</label> <input type="text" name="location" value="<?php echo $emp['location']; ?>" <?php echo $input_status; ?>>
                </div>
            </div>

            <div class="tabs">
                <div class="tab" onclick="openTab('resume')">Resume</div>
                <div class="tab active" onclick="openTab('private')">Private Info</div>
                
                <?php if ($show_salary_tab): ?>
                    <div class="tab" onclick="openTab('salary')">Salary Info 🔒</div>
                <?php endif; ?>
                
                <div class="tab" onclick="openTab('security')">Security</div>
            </div>

            <div id="private" class="content active">
                <?php if($msg) echo "<p style='color:green; font-weight:bold; text-align:center;'>$msg</p>"; ?>
                <div class="grid-2">
                    <div>
                        <div class="form-row"><label>Date of Birth</label> <input type="date" name="dob" value="<?php echo $emp['dob']; ?>" <?php echo $input_status; ?>></div>
                        <div class="form-row"><label>Address</label> <input type="text" name="address" value="<?php echo $emp['address']; ?>" <?php echo $input_status; ?>></div>
                        <div class="form-row"><label>Nationality</label> <input type="text" name="nationality" value="<?php echo $emp['nationality']; ?>" <?php echo $input_status; ?>></div>
                        <div class="form-row"><label>Personal Email</label> <input type="text" name="personal_email" value="<?php echo $emp['personal_email']; ?>" <?php echo $input_status; ?>></div>
                        <div class="form-row"><label>Gender</label> 
                            <select name="gender" <?php echo $input_status; ?>><option><?php echo $emp['gender']; ?></option><option>Male</option><option>Female</option></select>
                        </div>
                        <div class="form-row"><label>Marital Status</label> 
                            <select name="marital" <?php echo $input_status; ?>><option><?php echo $emp['marital_status']; ?></option><option>Single</option><option>Married</option></select>
                        </div>
                        <div class="form-row"><label>Joining Date</label> <input type="date" value="<?php echo $emp['joining_date']; ?>" disabled></div>
                    </div>
                    <div>
                        <div class="section-title">Bank & Tax Details</div>
                        <div class="form-row"><label>Account No</label> <input type="text" name="account_number" value="<?php echo $emp['account_number']; ?>" <?php echo $input_status; ?>></div>
                        <div class="form-row"><label>Bank Name</label> <input type="text" name="bank_name" value="<?php echo $emp['bank_name']; ?>" <?php echo $input_status; ?>></div>
                        <div class="form-row"><label>IFSC Code</label> <input type="text" name="ifsc_code" value="<?php echo $emp['ifsc_code']; ?>" <?php echo $input_status; ?>></div>
                        <div class="form-row"><label>PAN No</label> <input type="text" name="pan_number" value="<?php echo $emp['pan_number']; ?>" <?php echo $input_status; ?>></div>
                        <div class="form-row"><label>UAN No</label> <input type="text" name="uan_number" value="<?php echo $emp['uan_number']; ?>" <?php echo $input_status; ?>></div>
                        <div class="form-row"><label>Emp Code</label> <input type="text" value="<?php echo $emp['employee_code']; ?>" disabled></div>
                    </div>
                </div>
                
                <?php if($can_edit): ?>
                    <button type="submit" name="update_profile" class="btn-save">Save Changes</button>
                <?php endif; ?>
            </div>

            <?php if ($show_salary_tab): ?>
            <div id="salary" class="content">
                <div class="grid-2">
                    <div>
                        <div class="section-title">Overview</div>
                        <div class="form-row"><label>Month Wage</label> <input type="text" name="month_wage" value="<?php echo $emp['month_wage']; ?>"></div>
                        <div class="form-row"><label>Yearly Wage</label> <input type="text" name="yearly_wage" value="<?php echo $emp['yearly_wage']; ?>"></div>
                        <div class="form-row"><label>Working Days</label> <input type="text" name="working_days" value="<?php echo $emp['working_days']; ?>"></div>
                        <div class="form-row"><label>Break Time</label> <input type="text" name="break_time" value="<?php echo $emp['break_time']; ?>"></div>
                    </div>
                    <div>
                        <div class="section-title">Earnings</div>
                        <div class="form-row"><label>Basic Salary</label> <input type="text" name="basic_salary" value="<?php echo $emp['basic_salary']; ?>"></div>
                        <div class="form-row"><label>HRA</label> <input type="text" name="hra" value="<?php echo $emp['hra']; ?>"></div>
                        <div class="form-row"><label>Standard Allow.</label> <input type="text" name="standard_allowance" value="<?php echo $emp['standard_allowance']; ?>"></div>
                        <div class="form-row"><label>Perf. Bonus</label> <input type="text" name="performance_bonus" value="<?php echo $emp['performance_bonus']; ?>"></div>
                        <div class="form-row"><label>LTA</label> <input type="text" name="lta" value="<?php echo $emp['lta']; ?>"></div>
                        
                        <div class="section-title" style="margin-top:15px;">Deductions</div>
                        <div class="form-row"><label>PF (Employee)</label> <input type="text" name="pf_employee" value="<?php echo $emp['pf_employee']; ?>"></div>
                        <div class="form-row"><label>PF (Employer)</label> <input type="text" name="pf_employer" value="<?php echo $emp['pf_employer']; ?>"></div>
                        <div class="form-row"><label>Prof. Tax</label> <input type="text" name="professional_tax" value="<?php echo $emp['professional_tax']; ?>"></div>
                    </div>
                </div>
                <button type="submit" name="update_profile" class="btn-save">Update Salary</button>
            </div>
            <?php endif; ?>

            <div id="resume" class="content"><h3>Resume Upload (Coming Soon)</h3></div>
            <div id="security" class="content"><h3>Password Settings (Coming Soon)</h3></div>

        </form>
    </div>

    <script>
        function openTab(name) {
            var i, x, tablinks;
            x = document.getElementsByClassName("content");
            for (i = 0; i < x.length; i++) { x[i].style.display = "none"; }
            tablinks = document.getElementsByClassName("tab");
            for (i = 0; i < tablinks.length; i++) { tablinks[i].classList.remove("active"); }
            document.getElementById(name).style.display = "block";
            event.currentTarget.classList.add("active");
        }
    </script>
</body>
</html>