<?php
session_start();
include 'db.php';

if (isset($_POST['login'])) {
    $input = $_POST['login_id'];
    $pass  = $_POST['password'];

    // Query checks if input matches Email OR Employee Code
    $sql = "SELECT * FROM employees WHERE (email='$input' OR employee_code='$input') AND password='$pass'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['role'] = $row['role'];
        $_SESSION['name'] = $row['first_name'] . ' ' . $row['last_name'];
        header("Location: dashboard.php");
    } else {
        $error = "Invalid Employee ID/Email or Password!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - Dayflow</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .box { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); width: 350px; text-align: center; }
        input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #6c5ce7; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; }
        .success-msg { background: #d4edda; color: #155724; padding: 10px; border-radius: 6px; margin-bottom: 15px; text-align: left; font-size: 14px; border: 1px solid #c3e6cb; }
    </style>
</head>
<body>
    <div class="box">
        <h2 style="color:#6c5ce7;">Dayflow HRMS</h2>
        
        <?php if(isset($_SESSION['success'])): ?>
            <div class="success-msg"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if(isset($error)) echo "<p style='color:red;'>$error</p>"; ?>

        <form method="POST">
            <input type="text" name="login_id" placeholder="Email OR Employee ID" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="login">Sign In</button>
        </form>

        <div style="margin-top:20px; font-size:14px; display:flex; justify-content:space-between;">
            <a href="#" style="color:#666; text-decoration:none;">Forgot Password?</a>
            <a href="signup.php" style="color:#6c5ce7; font-weight:bold; text-decoration:none;">Create Account</a>
        </div>
    </div>
</body>
</html>