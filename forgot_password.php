<?php
session_start();
include 'db.php';

$step = isset($_SESSION['fp_step']) ? $_SESSION['fp_step'] : 1;
$msg = "";

// STEP 1: VERIFY EMAIL & GENERATE OTP
if (isset($_POST['send_otp'])) {
    $email = $_POST['email'];
    $check = $conn->query("SELECT * FROM employees WHERE email='$email'");
    
    if ($check->num_rows > 0) {
        $otp = rand(1000, 9999); // Generate 4 digit code
        $_SESSION['fp_email'] = $email;
        $_SESSION['fp_otp'] = $otp; // Store in session for easy check (Simpler for basic projects)
        $_SESSION['fp_step'] = 2;
        
        // --- SIMULATE EMAIL SENDING ---
        // In a real project, use mail($email, "OTP", $otp);
        // Here, we show it via JS alert so you can test it easily.
        echo "<script>alert('Your OTP Code is: $otp');</script>";
        header("Refresh:0");
    } else {
        $msg = "Email not found in our system!";
    }
}

// STEP 2: VERIFY OTP
if (isset($_POST['verify_otp'])) {
    $user_otp = $_POST['otp_code'];
    if ($user_otp == $_SESSION['fp_otp']) {
        $_SESSION['fp_step'] = 3;
        header("Refresh:0");
    } else {
        $msg = "Invalid OTP Code!";
    }
}

// STEP 3: CHANGE PASSWORD
if (isset($_POST['change_pass'])) {
    $new_pass = $_POST['new_pass'];
    $email = $_SESSION['fp_email'];
    
    // Update DB
    $conn->query("UPDATE employees SET password='$new_pass' WHERE email='$email'");
    
    // Cleanup
    session_destroy();
    session_start();
    $_SESSION['success'] = "Password Changed Successfully! Please Login.";
    header("Location: index.php");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #e0e0e0; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: white; padding: 30px; width: 350px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); text-align: center; }
        input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; text-align: center; }
        button { width: 100%; padding: 12px; background: #6c5ce7; color: white; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; }
        h2 { color: #333; margin-top: 0; }
        .back-link { display: block; margin-top: 15px; color: #666; text-decoration: none; font-size: 14px; }
    </style>
</head>
<body>

    <div class="card">
        <?php if ($msg): ?>
            <p style="color: red; background: #ffeaa7; padding: 5px; border-radius: 3px;"><?php echo $msg; ?></p>
        <?php endif; ?>

        <?php if ($step == 1): ?>
            <h2>Forgot Password?</h2>
            <p style="color:gray; font-size:14px;">Enter your email to receive a code.</p>
            <form method="POST">
                <input type="email" name="email" placeholder="Enter your email" required>
                <button type="submit" name="send_otp">Send Verification Code</button>
            </form>

        <?php elseif ($step == 2): ?>
            <h2>Verify Identity</h2>
            <p style="color:gray; font-size:14px;">Enter the 4-digit code sent to<br><b><?php echo $_SESSION['fp_email']; ?></b></p>
            <form method="POST">
                <input type="number" name="otp_code" placeholder="XXXX" required style="font-size: 20px; letter-spacing: 5px;">
                <button type="submit" name="verify_otp">Verify Code</button>
            </form>

        <?php elseif ($step == 3): ?>
            <h2>New Password</h2>
            <p style="color:gray; font-size:14px;">Create a strong new password.</p>
            <form method="POST">
                <input type="password" name="new_pass" placeholder="New Password" required>
                <button type="submit" name="change_pass">Update Password</button>
            </form>
        <?php endif; ?>

        <a href="index.php" class="back-link">Back to Login</a>
    </div>

</body>
</html>