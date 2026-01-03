<?php
session_start();
include 'db.php';

// --- SMART ID LOGIC (Your existing perfect logic) ---
function generateSmartID($conn, $f_name, $l_name, $comp_name) {
    $words = explode(" ", trim($comp_name));
    $c_first = strtoupper(substr($words[0], 0, 1));
    $c_last  = (count($words) > 1) ? strtoupper(substr(end($words), 0, 1)) : $c_first;
    $comp_code = $c_first . $c_last;

    $p_code = strtoupper(substr(trim($f_name), 0, 2) . substr(trim($l_name), 0, 2));
    $year = date("Y"); 

    $sql = "SELECT CAST(RIGHT(employee_code, 4) AS UNSIGNED) as max_seq 
            FROM employees 
            WHERE employee_code LIKE '%$year%' 
            ORDER BY max_seq DESC LIMIT 1";
            
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $new_seq = $row['max_seq'] + 1;
    } else {
        $new_seq = 1; 
    }

    $prefix = $comp_code . $p_code . $year;
    return $prefix . str_pad($new_seq, 4, '0', STR_PAD_LEFT);
}

// --- HANDLE SUBMISSION ---
if (isset($_POST['register'])) {
    $role  = $_POST['role']; // <--- 1. GET USER TYPE
    $cname = $_POST['company'];
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $pass  = $_POST['pass'];
    $cpass = $_POST['cpass'];

    $pattern = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/";
    
    if ($pass !== $cpass) {
        $error = "Passwords do not match!";
    } elseif (!preg_match($pattern, $pass)) {
        $error = "Password is too weak!";
    } else {
        $new_id = generateSmartID($conn, $fname, $lname, $cname);

        // Handle Files
        $profile_pic = "default_user.png";
        $company_logo = "default_logo.png";
        $upload_dir = "uploads/";

        if (!empty($_FILES['profile_pic']['name'])) {
            $ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
            $profile_name = $new_id . "_profile." . $ext; 
            move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_dir . $profile_name);
            $profile_pic = $profile_name;
        }

        if (!empty($_FILES['company_logo']['name'])) {
            $ext = pathinfo($_FILES['company_logo']['name'], PATHINFO_EXTENSION);
            $logo_name = $new_id . "_logo." . $ext; 
            move_uploaded_file($_FILES['company_logo']['tmp_name'], $upload_dir . $logo_name);
            $company_logo = $logo_name;
        }

        // --- 2. UPDATE SQL TO INSERT ROLE ---
        // Changed 'employee' string to ? (placeholder)
        $stmt = $conn->prepare("INSERT INTO employees (employee_code, company_name, first_name, last_name, email, phone, password, role, profile_pic, company_logo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        // Added $role to bind_param (10 's' characters now)
        $stmt->bind_param("ssssssssss", $new_id, $cname, $fname, $lname, $email, $phone, $pass, $role, $profile_pic, $company_logo);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Account Created as <b>$role</b>! <br> ID: <b>$new_id</b>";
            header("Location: index.php");
            exit();
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>

    <title>Sign Up - Dayflow</title>
    <link rel="icon" type="image/png" href="uploads/logo.jpg">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .box { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); width: 450px; }
        input, select { width: 100%; padding: 12px; margin: 8px 0; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #6c5ce7; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 15px; }
        .half { display: flex; gap: 10px; }
        .file-group { margin: 10px 0; font-size: 14px; }
        .file-group label { font-weight: bold; display: block; margin-bottom: 5px; }
        
        .password-container { position: relative; width: 100%; }
        .password-container input { padding-right: 40px; } 
        .toggle-eye {
            position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
            cursor: pointer; font-size: 18px; background: none; border: none; color: #666;
        }
        #strength-msg { font-size: 12px; margin-top: -5px; margin-bottom: 10px; display: block; }
    </style>
    <link rel="stylesheet" href="global.css">
</head>
<body>
    <div class="box">
         <img src="uploads/logo.jpg" alt="Logo" class="logo-img" style= 'width: 150px; margin-bottom: 10px; display:block; margin-left:auto; margin-right:auto;'>
        <h2 style="text-align:center; color:#6c5ce7;">Dayflow Registration</h2>
        <?php if(isset($error)) echo "<p style='color:red; text-align:center;'>$error</p>"; ?>
        
        <form method="POST" enctype="multipart/form-data">
            
            <label><b>I am a:</b></label>
            <select name="role" required style="border: 2px solid #6c5ce7; background: #f9f9f9;">
                <option value="employee">Employee</option>
                <option value="admin">HR Manager / Admin</option>
            </select>

            <label><b>Company Info</b></label>
            <input type="text" name="company" placeholder="Company Name" required>
            <div class="file-group">
                <label>Company Logo (Optional)</label>
                <input type="file" name="company_logo" accept="image/*">
            </div>

            <label><b>Personal Info</b></label>
            <div class="half">
                <input type="text" name="fname" placeholder="First Name" required>
                <input type="text" name="lname" placeholder="Last Name" required>
            </div>
            <div class="file-group">
                <label>Profile Picture (Optional)</label>
                <input type="file" name="profile_pic" accept="image/*">
            </div>
            
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="text" name="phone" placeholder="Phone Number" required>
            
            <label><b>Security</b></label>
            <div class="password-container">
                <input type="password" name="pass" id="passInput" placeholder="Password" onkeyup="checkStrength()" required>
                <span class="toggle-eye" onclick="togglePassword()">👁️</span>
            </div>

            <button type="button" onclick="generatePass()" style="width: 100%; margin: 5px 0 10px 0; background:#fab1a0; color:black;">
                Generate Strong Password
            </button>
            <span id="strength-msg" style="color:red;">Min 8 chars, 1 Upper, 1 Lower, 1 Number, 1 Symbol</span>
            
            <input type="password" name="cpass" placeholder="Confirm Password" required>
            
            <button type="submit" name="register">Create Account</button>
        </form>
        <p style="text-align:center; font-size:14px;">Already have an ID? <a href="index.php" style="color:#6c5ce7;">Login</a></p>
    </div>

    <script>
        function generatePass() {
            const chars = "ABCDEFGHIJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz123456789!@#$%&*";
            let pass = "";
            for (let i = 0; i < 12; i++) { pass += chars.charAt(Math.floor(Math.random() * chars.length)); }
            const input = document.getElementById("passInput");
            input.value = pass; input.type = "text";
            checkStrength(); 
        }

        function togglePassword() {
            const input = document.getElementById("passInput");
            const icon = document.querySelector(".toggle-eye");
            if (input.type === "password") { input.type = "text"; icon.textContent = "🙈"; } 
            else { input.type = "password"; icon.textContent = "👁️"; }
        }

        function checkStrength() {
            const val = document.getElementById("passInput").value;
            const msg = document.getElementById("strength-msg");
            const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
            if (regex.test(val)) { msg.style.color = "green"; msg.innerHTML = "Strong Password! ✅"; } 
            else { msg.style.color = "red"; msg.innerHTML = "Weak: Need Upper, Lower, Num, Symbol & 8+ chars"; }
        }
    </script>
</body>
</html>