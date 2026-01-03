<?php
require 'db.php';
require 'auth.php';
requireLogin();

$currentUserRole = $_SESSION['role'];
$currentUserId = $_SESSION['user_id'];
$fullName = $_SESSION['full_name'] ?? 'User';

// --- DATA FETCHING FOR WIDGETS ---

// 1. Total Employees
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role != 'admin'");
$totalEmployees = $stmt->fetchColumn();

// 2. Present Today
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE date = ? AND clock_in IS NOT NULL");
$stmt->execute([$today]);
$presentToday = $stmt->fetchColumn();

// 3. Pending Leaves
$stmt = $pdo->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'Pending'");
$pendingLeaves = $stmt->fetchColumn();

// 4. Calculate Attendance Rate (Mock logic for demo)
$attendanceRate = ($totalEmployees > 0) ? round(($presentToday / $totalEmployees) * 100) : 0;

// --- FETCH TEAM MEMBERS (For the Grid) ---
$stmt = $pdo->query("SELECT u.id, u.full_name, u.role, ed.position, ed.department, u.created_at 
                     FROM users u 
                     LEFT JOIN employee_details ed ON u.id = ed.user_id 
                     LIMIT 4"); // Limiting to 4 for the UI demo
$teamMembers = $stmt->fetchAll();

// --- FETCH LEAVE REQUESTS (For the Sidebar) ---
if(isAdminOrHr()){
    $stmt = $pdo->query("SELECT lr.*, u.full_name FROM leave_requests lr JOIN users u ON lr.user_id = u.id WHERE lr.status = 'Pending' LIMIT 5");
} else {
    $stmt = $pdo->prepare("SELECT *, 'You' as full_name FROM leave_requests WHERE user_id = ? ORDER BY applied_on DESC LIMIT 5");
    $stmt->execute([$currentUserId]);
}
$leaveRequests = $stmt->fetchAll();

// Helper for Initials
function getInitials($name) {
    $parts = explode(" ", $name);
    return strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HRMS Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        .sidebar-active { background-color: #0d9488; color: white; } /* Teal-600 */
        .sidebar-link:hover:not(.sidebar-active) { background-color: #374151; color: white; }
    </style>
</head>
<body class="flex h-screen overflow-hidden">

    <aside class="w-64 bg-[#1a202c] text-gray-400 flex flex-col transition-all duration-300 hidden md:flex">
        <div class="h-20 flex items-center px-8 border-b border-gray-700">
            <div class="flex items-center gap-3">
                <div class="bg-teal-500 p-2 rounded-lg">
                    <i class="fas fa-briefcase text-white"></i>
                </div>
                <div>
                    <h1 class="text-white font-bold text-lg leading-tight">HRMS</h1>
                    <p class="text-xs text-gray-500">Management System</p>
                </div>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto py-6 space-y-2 px-4">
            <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-lg sidebar-active transition-colors">
                <i class="fas fa-grid-2 text-lg w-6 text-center"></i>
                <span class="font-medium">Dashboard</span>
            </a>
            
            <?php if(isAdminOrHr()): ?>
            <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-lg sidebar-link transition-colors">
                <i class="fas fa-users text-lg w-6 text-center"></i>
                <span class="font-medium">Employees</span>
            </a>
            <?php endif; ?>

            <a href="attendance.php" class="flex items-center gap-3 px-4 py-3 rounded-lg sidebar-link transition-colors">
                <i class="far fa-clock text-lg w-6 text-center"></i>
                <span class="font-medium">Attendance</span>
            </a>
            <a href="leave.php" class="flex items-center gap-3 px-4 py-3 rounded-lg sidebar-link transition-colors">
                <i class="far fa-calendar-alt text-lg w-6 text-center"></i>
                <span class="font-medium">Leave</span>
            </a>
            <a href="profile.php" class="flex items-center gap-3 px-4 py-3 rounded-lg sidebar-link transition-colors">
                <i class="far fa-user text-lg w-6 text-center"></i>
                <span class="font-medium">My Profile</span>
            </a>
            <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-lg sidebar-link transition-colors">
                <i class="fas fa-cog text-lg w-6 text-center"></i>
                <span class="font-medium">Settings</span>
            </a>
        </nav>

        <div class="p-4 border-t border-gray-700">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-teal-500 flex items-center justify-center text-white font-bold">
                    <?= getInitials($fullName) ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-white truncate"><?= htmlspecialchars($fullName) ?></p>
                    <p class="text-xs text-gray-500 truncate"><?= ucfirst($currentUserRole) ?></p>
                </div>
                <a href="logout.php" class="text-gray-400 hover:text-white"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        </div>
    </aside>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-8 z-10 md:hidden">
            <button class="text-gray-600 focus:outline-none"><i class="fas fa-bars text-xl"></i></button>
            <span class="font-bold text-gray-800">Dashboard</span>
            <div class="w-8"></div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 md:p-8">
            
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-800">Welcome back, <?= explode(' ', $fullName)[0] ?>! 👋</h2>
                <p class="text-gray-500 mt-1">Here's what's happening with your team today.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-teal-50 rounded-xl p-6 border border-teal-100">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm font-medium text-teal-600 mb-1">Total Employees</p>
                            <h3 class="text-3xl font-bold text-gray-800"><?= $totalEmployees ?></h3>
                            <p class="text-xs text-teal-600 mt-2 font-medium">Active team members</p>
                        </div>
                        <div class="p-2 bg-teal-200 rounded-lg text-teal-700">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-emerald-50 rounded-xl p-6 border border-emerald-100">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm font-medium text-emerald-600 mb-1">Present Today</p>
                            <h3 class="text-3xl font-bold text-gray-800"><?= $presentToday ?></h3>
                            <p class="text-xs text-emerald-600 mt-2 font-medium">Out of active employees</p>
                        </div>
                        <div class="p-2 bg-emerald-200 rounded-lg text-emerald-700">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-orange-50 rounded-xl p-6 border border-orange-100">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm font-medium text-orange-600 mb-1">Pending Leaves</p>
                            <h3 class="text-3xl font-bold text-gray-800"><?= $pendingLeaves ?></h3>
                            <p class="text-xs text-orange-600 mt-2 font-medium">Awaiting approval</p>
                        </div>
                        <div class="p-2 bg-orange-200 rounded-lg text-orange-700">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                    </div>
                </div>

                 <div class="bg-blue-50 rounded-xl p-6 border border-blue-100">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm font-medium text-blue-600 mb-1">Attendance Rate</p>
                            <h3 class="text-3xl font-bold text-gray-800"><?= $attendanceRate ?>%</h3>
                            <p class="text-xs text-blue-600 mt-2 font-medium text-green-600">+2% vs last month</p>
                        </div>
                        <div class="p-2 bg-blue-200 rounded-lg text-blue-700">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <div class="lg:col-span-2">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold text-gray-800">Team Members</h3>
                        <a href="#" class="text-teal-600 text-sm font-medium hover:underline">View All</a>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php foreach($teamMembers as $member): ?>
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow">
                            <div class="h-2 bg-teal-500"></div>
                            <div class="p-6">
                                <div class="flex justify-between items-start mb-4">
                                    <div class="w-12 h-12 rounded-full bg-orange-400 text-white flex items-center justify-center font-bold text-lg">
                                        <?= getInitials($member['full_name']) ?>
                                    </div>
                                    <span class="bg-green-100 text-green-700 text-xs font-bold px-2 py-1 rounded-full">active</span>
                                </div>
                                <h4 class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($member['full_name']) ?></h4>
                                <p class="text-gray-500 text-sm mb-4"><?= htmlspecialchars($member['role']) ?></p>
                                
                                <div class="space-y-2 text-sm text-gray-600">
                                    <div class="flex items-center gap-3">
                                        <i class="far fa-building text-gray-400 w-4"></i>
                                        <span><?= htmlspecialchars($member['department'] ?? 'General') ?></span>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <i class="far fa-envelope text-gray-400 w-4"></i>
                                        <span class="truncate">user<?= $member['id'] ?>@hrms.com</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 h-full">
                        <div class="flex items-center gap-2 mb-6 text-teal-700">
                            <i class="far fa-calendar-check text-xl"></i>
                            <h3 class="text-xl font-bold text-gray-800">Leave Requests</h3>
                        </div>

                        <div class="space-y-6">
                            <?php if(empty($leaveRequests)): ?>
                                <p class="text-gray-500 text-center py-4">No pending requests.</p>
                            <?php else: ?>
                                <?php foreach($leaveRequests as $req): ?>
                                <div class="flex gap-4 p-3 hover:bg-gray-50 rounded-lg transition-colors">
                                    <div class="w-10 h-10 rounded-full bg-teal-100 text-teal-600 flex items-center justify-center font-bold shrink-0">
                                        <?= getInitials($req['full_name']) ?>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <h5 class="font-bold text-gray-800 text-sm"><?= htmlspecialchars($req['full_name']) ?></h5>
                                                <span class="inline-block bg-blue-100 text-blue-700 text-[10px] px-2 py-0.5 rounded-full mb-1">
                                                    <?= htmlspecialchars($req['leave_type']) ?>
                                                </span>
                                            </div>
                                            <?php if(isAdminOrHr()): ?>
                                            <div class="flex gap-1">
                                                <button class="w-6 h-6 rounded bg-green-500 text-white text-xs hover:bg-green-600 flex items-center justify-center"><i class="fas fa-check"></i></button>
                                                <button class="w-6 h-6 rounded bg-red-500 text-white text-xs hover:bg-red-600 flex items-center justify-center"><i class="fas fa-times"></i></button>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="mt-2 text-xs text-gray-500">
                                            <div class="flex items-center gap-1 mb-1">
                                                <i class="far fa-clock"></i>
                                                <span><?= date('M d', strtotime($req['start_date'])) ?> - <?= date('M d', strtotime($req['end_date'])) ?></span>
                                            </div>
                                            <p class="italic text-gray-400 truncate">"<?= htmlspecialchars($req['reason']) ?>"</p>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <button class="w-full mt-6 py-2 border border-gray-200 text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                            View All Requests
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </main>

</body>
</html>