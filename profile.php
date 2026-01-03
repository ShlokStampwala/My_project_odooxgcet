<?php
require 'db.php';
require 'auth.php';
requireLogin();

$currentUserId = $_SESSION['user_id'];
$fullName = $_SESSION['full_name'] ?? 'User';
$role = $_SESSION['role'] ?? 'Employee';

// --- FETCH USER DETAILS ---
// We join 'users' with 'employee_details' to get all profile info
$stmt = $pdo->prepare("
    SELECT u.*, ed.phone, ed.department, ed.position, ed.employee_id, ed.location, ed.dob 
    FROM users u 
    LEFT JOIN employee_details ed ON u.id = ed.user_id 
    WHERE u.id = ?
");
$stmt->execute([$currentUserId]);
$user = $stmt->fetch();

// Helper for Initials
function getInitials($name) {
    return strtoupper(substr($name, 0, 1));
}

// Format Date Joined
$joinedDate = isset($user['created_at']) ? date('M d, Y', strtotime($user['created_at'])) : 'N/A';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #F3F4F6; }
        /* Tab Active State */
        .tab-btn.active { background-color: white; color: #111827; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1); }
    </style>
</head>
<body class="flex flex-col min-h-screen text-gray-800">

    <nav class="bg-gray-900 text-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-3">
                    <div class="bg-teal-500 w-8 h-8 rounded flex items-center justify-center">
                        <i class="fas fa-layer-group text-white text-xs"></i>
                    </div>
                    <span class="font-bold text-lg tracking-wide">HRMS</span>
                </div>
                <div class="hidden md:flex space-x-8">
                    <a href="dashboard.php" class="text-gray-300 hover:text-white px-1 py-4 text-sm font-medium transition-colors">Dashboard</a>
                    <a href="attendance.php" class="text-gray-300 hover:text-white px-1 py-4 text-sm font-medium transition-colors">Attendance</a>
                    <a href="leave.php" class="text-gray-300 hover:text-white px-1 py-4 text-sm font-medium transition-colors">Leaves</a>
                    <a href="profile.php" class="text-white border-b-2 border-teal-500 px-1 py-4 text-sm font-medium">My Profile</a>
                </div>
                <div class="flex items-center gap-4">
                     <div class="h-10 w-10 rounded-full bg-teal-600 flex items-center justify-center font-bold text-white border-2 border-gray-700">
                        <?= getInitials($fullName) ?>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-10">
        
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">My Profile</h1>
                <p class="text-gray-500 mt-1">Manage your personal information and settings</p>
            </div>
            <button class="flex items-center gap-2 bg-white border border-teal-600 text-teal-600 hover:bg-teal-50 font-medium px-4 py-2 rounded-lg transition">
                <i class="far fa-edit"></i> Edit Profile
            </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="p-8 flex flex-col items-center border-b border-gray-100">
                        <div class="w-24 h-24 rounded-full bg-teal-100 text-teal-600 flex items-center justify-center text-3xl font-bold mb-4">
                            <?= getInitials($user['full_name']) ?>
                        </div>
                        <h2 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($user['full_name']) ?></h2>
                        <p class="text-gray-500 font-medium"><?= htmlspecialchars($user['position'] ?? 'Employee') ?></p>
                        <span class="mt-3 bg-teal-600 text-white text-xs px-3 py-1 rounded-full font-medium uppercase tracking-wide">
                            <?= htmlspecialchars($user['role']) ?>
                        </span>
                    </div>
                    
                    <div class="p-6 space-y-4">
                        <div class="flex items-center gap-3 text-gray-600">
                            <div class="w-8 flex justify-center"><i class="far fa-envelope text-gray-400"></i></div>
                            <span class="text-sm truncate"><?= htmlspecialchars($user['username']) ?></span> </div>
                        <div class="flex items-center gap-3 text-gray-600">
                            <div class="w-8 flex justify-center"><i class="fas fa-phone text-gray-400"></i></div>
                            <span class="text-sm"><?= htmlspecialchars($user['phone'] ?? 'N/A') ?></span>
                        </div>
                        <div class="flex items-center gap-3 text-gray-600">
                            <div class="w-8 flex justify-center"><i class="far fa-building text-gray-400"></i></div>
                            <span class="text-sm"><?= htmlspecialchars($user['department'] ?? 'General') ?></span>
                        </div>
                        <div class="flex items-center gap-3 text-gray-600">
                            <div class="w-8 flex justify-center"><i class="far fa-calendar-alt text-gray-400"></i></div>
                            <div class="flex flex-col">
                                <span class="text-xs text-gray-400">Joined</span>
                                <span class="text-sm"><?= $joinedDate ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 h-full">
                    
                    <div class="flex border-b border-gray-200 bg-gray-50/50 rounded-t-xl p-2 gap-2">
                        <button onclick="switchTab('personal')" id="tab-personal" class="tab-btn active flex-1 py-2 text-sm font-medium text-gray-600 rounded-lg transition-all text-center">
                            Personal Info
                        </button>
                        <button onclick="switchTab('leave')" id="tab-leave" class="tab-btn flex-1 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 rounded-lg transition-all text-center">
                            Leave Balance
                        </button>
                    </div>

                    <div id="content-personal" class="p-8">
                        <form>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Full Name</label>
                                    <input type="text" value="<?= htmlspecialchars($user['full_name']) ?>" class="w-full px-4 py-2 rounded-lg border border-gray-300 bg-gray-50 text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-500 transition" readonly>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Email Address</label>
                                    <input type="email" value="<?= htmlspecialchars($user['username']) ?>" class="w-full px-4 py-2 rounded-lg border border-gray-300 bg-gray-50 text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-500 transition" readonly>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Phone Number</label>
                                    <input type="text" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" class="w-full px-4 py-2 rounded-lg border border-gray-300 bg-gray-50 text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-500 transition" readonly>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Department</label>
                                    <input type="text" value="<?= htmlspecialchars($user['department'] ?? '') ?>" class="w-full px-4 py-2 rounded-lg border border-gray-300 bg-gray-50 text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-500 transition" readonly>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Position</label>
                                    <input type="text" value="<?= htmlspecialchars($user['position'] ?? '') ?>" class="w-full px-4 py-2 rounded-lg border border-gray-300 bg-gray-50 text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-500 transition" readonly>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Employee ID</label>
                                    <input type="text" value="<?= htmlspecialchars($user['employee_id'] ?? '') ?>" class="w-full px-4 py-2 rounded-lg border border-gray-300 bg-gray-50 text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-500 transition" readonly>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div id="content-leave" class="p-8 hidden">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                             <div class="bg-blue-50 border border-blue-100 p-4 rounded-xl text-center">
                                 <h4 class="text-blue-800 font-bold text-2xl">12</h4>
                                 <p class="text-blue-600 text-sm">Total Leaves</p>
                             </div>
                             <div class="bg-orange-50 border border-orange-100 p-4 rounded-xl text-center">
                                 <h4 class="text-orange-800 font-bold text-2xl">2</h4>
                                 <p class="text-orange-600 text-sm">Used</p>
                             </div>
                             <div class="bg-green-50 border border-green-100 p-4 rounded-xl text-center">
                                 <h4 class="text-green-800 font-bold text-2xl">10</h4>
                                 <p class="text-green-600 text-sm">Remaining</p>
                             </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </main>

    <script>
        function switchTab(tabName) {
            // Hide all contents
            document.getElementById('content-personal').classList.add('hidden');
            document.getElementById('content-leave').classList.add('hidden');
            
            // Remove active style from all buttons
            document.getElementById('tab-personal').classList.remove('active', 'bg-white', 'text-gray-900', 'shadow');
            document.getElementById('tab-leave').classList.remove('active', 'bg-white', 'text-gray-900', 'shadow');
            
            // Show selected content
            document.getElementById('content-' + tabName).classList.remove('hidden');
            
            // Add active style to selected button
            document.getElementById('tab-' + tabName).classList.add('active');
        }
    </script>
</body>
</html>