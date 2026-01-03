<?php
session_start();
include 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

// Determine whose profile to show (URL id or Logged in User)
$target_id = isset($_GET['id']) ? $_GET['id'] : $_SESSION['user_id'];
$viewer_role = $_SESSION['role'];

// Fetch Data
$stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->bind_param("i", $target_id);
$stmt->execute();
$emp = $stmt->get_result()->fetch_assoc();

// Salary Calc
$total = $emp['basic_salary'] + $emp['hra'] + $emp['allowances'] - $emp['pf'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Profile</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f8f9fa; padding: 20px; }
        .container { max-width: 800px; margin: auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .header { background: #6c5ce7; color: white; padding: 30px; display: flex; align-items: center; }
        .header img { width: 100px; height: 100px; border-radius: 50%; border: 4px solid white; margin-right: 20px; }
        .tabs { display: flex; background: #eee; }
        .tab { flex: 1; padding: 15px; text-align: center; cursor: pointer; border-bottom: 3px solid transparent; font-weight: bold; }
        .tab.active { background: white; border-bottom: 3px solid #6c5ce7; color: #6c5ce7; }
        .content { padding: 30px; display: none; }
        .content.active { display: block; }
        .row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f1f1f1; }
        .back-btn { display: inline-block; margin-bottom: 15px; text-decoration: none; color: #333; }
    </style>
    <script>
        function openTab(name) {
            document.querySelectorAll('.content').forEach(d => d.classList.remove('active'));
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.getElementById(name).classList.add('active');
            event.currentTarget.classList.add('active');
        }
    </script>
</head>
<body>
    <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
    
    <div class="container">
        <div class="header">
            <img src="<?php echo $emp['image']; ?>">
            <div>
                <h1><?php echo $emp['name']; ?></h1>
                <p><?php echo $emp['designation']; ?></p>
            </div>
        </div>

        <div class="tabs">
            <div class="tab active" onclick="openTab('personal')">Personal Info</div>
            
            <?php if($viewer_role == 'admin'): ?>
                <div class="tab" onclick="openTab('salary')">Salary Structure</div>
            <?php endif; ?>
        </div>

        <div id="personal" class="content active">
            <div class="row"><b>Email:</b> <span><?php echo $emp['email']; ?></span></div>
            <div class="row"><b>Phone:</b> <span><?php echo $emp['phone']; ?></span></div>
            <div class="row"><b>Address:</b> <span><?php echo $emp['address']; ?></span></div>
            <div class="row"><b>Joining Date:</b> <span><?php echo $emp['joining_date']; ?></span></div>
        </div>

        <?php if($viewer_role == 'admin'): ?>
        <div id="salary" class="content">
            <h3 style="color: #6c5ce7;">Earnings</h3>
            <div class="row"><span>Basic Salary</span> <span>+ <?php echo $emp['basic_salary']; ?></span></div>
            <div class="row"><span>HRA</span> <span>+ <?php echo $emp['hra']; ?></span></div>
            <div class="row"><span>Allowances</span> <span>+ <?php echo $emp['allowances']; ?></span></div>
            
            <h3 style="color: red;">Deductions</h3>
            <div class="row"><span>Provident Fund</span> <span>- <?php echo $emp['pf']; ?></span></div>
            
            <div class="row" style="background: #f8f9fa; font-weight: bold; margin-top: 20px; padding: 15px;">
                <span>NET SALARY</span>
                <span style="font-size: 1.2em;"><?php echo $total; ?> / month</span>
            </div>
        </div>
        <?php endif; ?>

    </div>
</body>
</html>