<?php
session_start();
include 'db.php';

// --- THE SMART ID LOGIC ENGINE ---
function generateSmartID($conn, $f_name, $l_name, $comp_name) {
    // 1. Company Initials: First char of First Word + First char of Last Word
    // Example: "Odoo India" -> O + I = OI
    $words = explode(" ", trim($comp_name));
    $c_first = strtoupper(substr($words[0], 0, 1));
    $c_last  = (count($words) > 1) ? strtoupper(substr(end($words), 0, 1)) : $c_first;
    $comp_code = $c_first . $c_last;

    // 2. Person Initials: First 2 chars of First Name + First 2 chars of Last Name
    // Example: "Raju Rastogi" -> RA + RA = RARA
    $p_code = strtoupper(substr(trim($f_name), 0, 2) . substr(trim($l_name), 0, 2));

    // 3. Year
    $year = date("Y"); // 2026

    // 4. Series (Auto Increment 0001)
    $prefix = $comp_code . $p_code . $year;
    
    // Check Database for the last ID with this exact prefix
    $sql = "SELECT employee_code FROM employees WHERE employee_code LIKE '$prefix%' ORDER BY employee_code DESC LIMIT 1";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $last_code = $result->fetch_assoc()['employee_code'];
        $last_num = intval(substr($last_code, -4)); // Extract last 4 digits
        $new_num = str_pad($last_num + 1, 4, '0', STR_PAD_LEFT);
    } else {
        $new_num = "0001";
    }

    return $prefix . $new_num;
}

// --- HANDLE SUBMISSION ---
if (isset($_POST['register'])) {
    $cname = $_POST['company'];
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $pass  = $_POST['pass'];
    $cpass = $_POST['cpass'];

    if ($pass !== $cpass) {
        $error = "Passwords do not match!";
    } else {
        // Generate the ID
        $smart_id = generateSmartID($conn, $fname, $lname, $cname);

        // Insert into Database
        $stmt = $conn->prepare("INSERT INTO employees (employee_code, company_name, first_name, last_name, email, phone, password, role) VALUES (?, ?, ?, ?, ?, ?, ?, 'employee')");
        $stmt->bind_param("sssssss", $smart_id, $cname, $fname, $lname, $email, $phone, $pass);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Account Created! <br> Your Employee ID is: <b>$smart_id</b> <br> Please Login.";
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
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .box { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); width: 400px; }
        input { width: 100%; padding: 12px; margin: 8px 0; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #6c5ce7; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 15px; }
        h2 { text-align: center; color: #6c5ce7; margin-top: 0; }
        .half { display: flex; gap: 10px; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Dayflow Registration</h2>
        <?php if(isset($error)) echo "<p style='color:red; text-align:center;'>$error</p>"; ?>
        
        <form method="POST">
            <label>Company Details</label>
            <input type="text" name="company" placeholder="Company Name (e.g. Odoo India)" required>
            
            <label>Personal Details</label>
            <div class="half">
                <input type="text" name="fname" placeholder="First Name" required>
                <input type="text" name="lname" placeholder="Last Name" required>
            </div>
            
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="text" name="phone" placeholder="Phone Number" required>
            
            <label>Security</label>
            <input type="password" name="pass" placeholder="Password" required>
            <input type="password" name="cpass" placeholder="Confirm Password" required>
            
            <button type="submit" name="register">Generate ID & Sign Up</button>
        </form>
        <p style="text-align:center; font-size:14px;">Already have an ID? <a href="index.php" style="color:#6c5ce7;">Login</a></p>
    </div>
</body>
</html>