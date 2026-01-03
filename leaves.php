<?php
require 'db.php';
require 'auth.php';
requireLogin();

$userId   = $_SESSION['user_id'];
$fullName = $_SESSION['full_name'] ?? 'User';

// --- 1. HANDLE FORM SUBMISSION ---
$message = '';
$activeTab = 'balance'; // Default tab

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['apply_leave'])) {
    $type   = $_POST['leave_type'];
    $start  = $_POST['start_date'];
    $end    = $_POST['end_date'];
    $reason = $_POST['reason'];

    // 1. Validation: End date before Start date
    if (strtotime($end) < strtotime($start)) {
        $message = "
        <div class='bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r shadow-sm flex items-center gap-3'>
            <div class='text-red-500'><i class='fas fa-exclamation-circle text-xl'></i></div>
            <div>
                <p class='text-sm text-red-700 font-bold'>Error</p>
                <p class='text-xs text-red-600'>End date cannot be earlier than start date.</p>
            </div>
        </div>";
        $activeTab = 'new'; // Stay on form
    } 
    // 2. Validation: Cannot apply for past dates (Optional, remove if not needed)
    elseif (strtotime($start) < strtotime(date('Y-m-d'))) {
        $message = "
        <div class='bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-6 rounded-r shadow-sm flex items-center gap-3'>
            <div class='text-yellow-500'><i class='fas fa-exclamation-triangle text-xl'></i></div>
            <div>
                <p class='text-sm text-yellow-700 font-bold'>Warning</p>
                <p class='text-xs text-yellow-600'>You are applying for a past date.</p>
            </div>
        </div>";
        // We still allow it, but warn. Or you can block it.
        $stmt = $pdo->prepare("INSERT INTO leave_requests (user_id, leave_type, start_date, end_date, reason, status, applied_on) VALUES (?, ?, ?, ?, ?, 'Pending', NOW())");
        if ($stmt->execute([$userId, $type, $start, $end, $reason])) {
            $message = "
            <div class='bg-emerald-50 border-l-4 border-emerald-500 p-4 mb-6 rounded-r shadow-sm flex items-center gap-3'>
                <div class='text-emerald-500'><i class='fas fa-check-circle text-xl'></i></div>
                <div>
                    <p class='text-sm text-emerald-700 font-bold'>Success</p>
                    <p class='text-xs text-emerald-600'>Your leave application has been submitted.</p>
                </div>
            </div>";
            $activeTab = 'requests'; // Switch to history tab to show the new entry
        }
    }
    // 3. Success Case
    else {
        $stmt = $pdo->prepare("INSERT INTO leave_requests (user_id, leave_type, start_date, end_date, reason, status, applied_on) VALUES (?, ?, ?, ?, ?, 'Pending', NOW())");
        if ($stmt->execute([$userId, $type, $start, $end, $reason])) {
            $message = "
            <div class='bg-emerald-50 border-l-4 border-emerald-500 p-4 mb-6 rounded-r shadow-sm flex items-center gap-3'>
                <div class='text-emerald-500'><i class='fas fa-check-circle text-xl'></i></div>
                <div>
                    <p class='text-sm text-emerald-700 font-bold'>Success</p>
                    <p class='text-xs text-emerald-600'>Your leave application has been submitted.</p>
                </div>
            </div>";
            $activeTab = 'requests'; // Switch to history tab
        } else {
            $message = "<div class='bg-red-100 text-red-700 p-3 rounded mb-4'>Database error.</div>";
        }
    }
}

// --- 2. FETCH DATA ---
$limits = ['Paid' => 15, 'Sick' => 10, 'Unpaid' => 5];

// Calculate Used Leaves (Only Approved ones count)
$sql = "SELECT leave_type, SUM(DATEDIFF(end_date, start_date) + 1) as used_days 
        FROM leave_requests 
        WHERE user_id = ? AND status = 'Approved' 
        GROUP BY leave_type";
$stmt = $pdo->prepare($sql);
$stmt->execute([$userId]);
$usedData = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Prepare Stats for Progress Bars
$leaveStats = [];
$totalRemaining = 0;
foreach ($limits as $type => $limit) {
    $used = $usedData[$type] ?? 0;
    $remaining = max(0, $limit - $used);
    $percentage = ($limit > 0) ? ($used / $limit) * 100 : 0;
    $totalRemaining += $remaining;
    
    $leaveStats[$type] = [
        'limit' => $limit, 'used' => $used, 'remaining' => $remaining, 'percent' => $percentage
    ];
}

// Fetch Counts
$pendingCount = $pdo->prepare("SELECT COUNT(*) FROM leave_requests WHERE user_id = ? AND status = 'Pending'");
$pendingCount->execute([$userId]);
$pendingCount = $pendingCount->fetchColumn();

$approvedCount = $pdo->prepare("SELECT COUNT(*) FROM leave_requests WHERE user_id = ? AND status = 'Approved' AND YEAR(start_date) = YEAR(CURRENT_DATE())");
$approvedCount->execute([$userId]);
$approvedCount = $approvedCount->fetchColumn();

// Fetch History
$historyStmt = $pdo->prepare("SELECT * FROM leave_requests WHERE user_id = ? ORDER BY applied_on DESC");
$historyStmt->execute([$userId]);
$history = $historyStmt->fetchAll();

// Helper for Profile Photo
function getProfileImage($name) {
    // Generates a nice avatar image based on the name
    return "https://ui-avatars.com/api/?name=" . urlencode($name) . "&background=0D9488&color=fff&size=128&bold=true";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #F8FAFC; }
        .tab-btn { transition: all 0.2s ease; }
        .tab-btn.active { background-color: #0F172A; color: white; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .progress-bar { transition: width 1s ease-in-out; }
        
        /* Smooth Fade In */
        .fade-in { animation: fadeIn 0.3s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="flex flex-col min-h-screen text-gray-800">

    <nav class="bg-slate-900 text-white shadow-lg sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-3">
                    <div class="bg-teal-500 w-8 h-8 rounded-lg flex items-center justify-center shadow-lg shadow-teal-500/30">
                        <i class="fas fa-layer-group text-white text-xs"></i>
                    </div>
                    <span class="font-bold text-lg tracking-wide">HRMS</span>
                </div>
                <div class="hidden md:flex space-x-8">
                    <a href="dashboard.php" class="text-slate-300 hover:text-white px-1 py-4 text-sm font-medium transition-colors">Dashboard</a>
                    <a href="attendance.php" class="text-slate-300 hover:text-white px-1 py-4 text-sm font-medium transition-colors">Attendance</a>
                    <a href="leave.php" class="text-white border-b-2 border-teal-500 px-1 py-4 text-sm font-medium">Leaves</a>
                    <a href="profile.php" class="text-slate-300 hover:text-white px-1 py-4 text-sm font-medium transition-colors">My Profile</a>
                </div>
                <div class="flex items-center gap-4">
                    <div class="flex flex-col text-right mr-1 hidden sm:block">
                         <span class="text-xs font-semibold text-slate-200"><?= htmlspecialchars($fullName) ?></span>
                         <span class="text-[10px] text-slate-400 uppercase tracking-wider">Employee</span>
                    </div>
                    <div class="h-10 w-10 rounded-full border-2 border-slate-700 shadow-md overflow-hidden bg-slate-800">
                        <img src="<?= getProfileImage($fullName) ?>" alt="Profile" class="h-full w-full object-cover">
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-6xl w-full mx-auto px-4 py-8 flex-1">
        
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-slate-900">Leave Management</h1>
                <p class="text-slate-500 mt-1">Track your balance and submit new requests.</p>
            </div>
            
            <div class="bg-white p-1.5 rounded-xl shadow-sm border border-gray-200 inline-flex">
                <button onclick="switchTab('balance')" id="tab-balance" class="tab-btn <?= $activeTab=='balance'?'active':'' ?> px-5 py-2 text-sm font-medium text-gray-600 rounded-lg">Overview</button>
                <button onclick="switchTab('requests')" id="tab-requests" class="tab-btn <?= $activeTab=='requests'?'active':'' ?> px-5 py-2 text-sm font-medium text-gray-600 rounded-lg">History</button>
                <button onclick="switchTab('new')" id="tab-new" class="tab-btn <?= $activeTab=='new'?'active':'' ?> px-5 py-2 text-sm font-medium text-gray-600 rounded-lg">Apply Leave</button>
            </div>
        </div>

        <?= $message ?>

        <div id="view-balance" class="fade-in grid grid-cols-1 lg:grid-cols-12 gap-6 items-start <?= $activeTab=='balance'?'':'hidden' ?>">
            
            <div class="lg:col-span-8 bg-white rounded-2xl shadow-sm border border-slate-100 p-8">
                <h3 class="text-lg font-bold text-slate-800 mb-8 flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-teal-50 flex items-center justify-center text-teal-600">
                        <i class="fas fa-chart-pie text-sm"></i>
                    </div>
                    My Leave Balance
                </h3>

                <div class="mb-10">
                    <div class="flex justify-between items-end mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-1 bg-teal-500 h-8 rounded-full"></div>
                            <div>
                                <span class="block font-bold text-slate-700">Paid Time Off</span>
                                <span class="text-xs text-slate-400">Standard annual leave</span>
                            </div>
                        </div>
                        <span class="text-sm font-medium text-slate-600 bg-slate-50 px-3 py-1 rounded-full border border-slate-100">
                            <?= $leaveStats['Paid']['used'] ?> / <?= $leaveStats['Paid']['limit'] ?> Used
                        </span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden">
                        <div class="progress-bar bg-teal-500 h-3 rounded-full shadow-[0_0_10px_rgba(20,184,166,0.3)]" style="width: <?= $leaveStats['Paid']['percent'] ?>%"></div>
                    </div>
                    <p class="text-right text-xs text-teal-600 font-semibold mt-2"><?= $leaveStats['Paid']['remaining'] ?> days remaining</p>
                </div>

                <div class="mb-10">
                    <div class="flex justify-between items-end mb-3">
                         <div class="flex items-center gap-3">
                            <div class="w-1 bg-orange-400 h-8 rounded-full"></div>
                            <div>
                                <span class="block font-bold text-slate-700">Sick Leave</span>
                                <span class="text-xs text-slate-400">For medical emergencies</span>
                            </div>
                        </div>
                        <span class="text-sm font-medium text-slate-600 bg-slate-50 px-3 py-1 rounded-full border border-slate-100">
                            <?= $leaveStats['Sick']['used'] ?> / <?= $leaveStats['Sick']['limit'] ?> Used
                        </span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden">
                        <div class="progress-bar bg-orange-400 h-3 rounded-full shadow-[0_0_10px_rgba(251,146,60,0.3)]" style="width: <?= $leaveStats['Sick']['percent'] ?>%"></div>
                    </div>
                    <p class="text-right text-xs text-orange-500 font-semibold mt-2"><?= $leaveStats['Sick']['remaining'] ?> days remaining</p>
                </div>

                <div>
                     <div class="flex justify-between items-end mb-3">
                         <div class="flex items-center gap-3">
                            <div class="w-1 bg-slate-400 h-8 rounded-full"></div>
                            <div>
                                <span class="block font-bold text-slate-700">Unpaid Leave</span>
                                <span class="text-xs text-slate-400">Loss of pay leave</span>
                            </div>
                        </div>
                        <span class="text-sm font-medium text-slate-600 bg-slate-50 px-3 py-1 rounded-full border border-slate-100">
                            <?= $leaveStats['Unpaid']['used'] ?> / <?= $leaveStats['Unpaid']['limit'] ?> Used
                        </span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden">
                        <div class="progress-bar bg-slate-400 h-3 rounded-full" style="width: <?= $leaveStats['Unpaid']['percent'] ?>%"></div>
                    </div>
                    <p class="text-right text-xs text-slate-500 font-semibold mt-2"><?= $leaveStats['Unpaid']['remaining'] ?> days remaining</p>
                </div>
            </div>

            <div class="lg:col-span-4 flex flex-col gap-6">
                <div class="bg-teal-600 text-white rounded-2xl p-8 shadow-xl shadow-teal-600/20 relative overflow-hidden min-h-[180px] flex flex-col justify-center">
                    <div class="relative z-10">
                        <span class="text-teal-100 font-medium text-sm uppercase tracking-wider">Balance</span>
                        <div class="flex items-baseline gap-2 mt-1">
                            <h2 class="text-6xl font-bold tracking-tight"><?= $totalRemaining ?></h2>
                            <span class="text-xl font-medium text-teal-100">Days</span>
                        </div>
                        <p class="text-teal-50 text-sm mt-2 opacity-90">Total remaining leaves available across all categories.</p>
                    </div>
                    <div class="absolute -right-6 -bottom-10 text-teal-500 opacity-40">
                        <i class="fas fa-leaf text-[150px]"></i>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100">
                        <div class="w-10 h-10 rounded-full bg-orange-50 flex items-center justify-center text-orange-500 mb-3">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-slate-800"><?= $pendingCount ?></h3>
                        <p class="text-xs text-slate-500 font-medium uppercase mt-1">Pending</p>
                    </div>
                    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100">
                        <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-500 mb-3">
                            <i class="fas fa-check-double"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-slate-800"><?= $approvedCount ?></h3>
                        <p class="text-xs text-slate-500 font-medium uppercase mt-1">Approved</p>
                    </div>
                </div>
            </div>
        </div>

        <div id="view-requests" class="fade-in bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden <?= $activeTab=='requests'?'':'hidden' ?>">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-slate-700">Request History</h3>
                <span class="text-xs text-slate-500 bg-white px-2 py-1 rounded border border-slate-200">Total: <?= count($history) ?></span>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-white text-slate-500 border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider">Leave Type</th>
                            <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider">Duration</th>
                            <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider">Reason</th>
                            <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider">Date Applied</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php if (count($history) > 0): ?>
                            <?php foreach ($history as $req): ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 rounded-full 
                                            <?= $req['leave_type'] == 'Paid' ? 'bg-teal-500' : 
                                               ($req['leave_type'] == 'Sick' ? 'bg-orange-500' : 'bg-slate-400') ?>">
                                        </div>
                                        <span class="font-semibold text-slate-700"><?= htmlspecialchars($req['leave_type']) ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-slate-800 font-medium">
                                        <?= date('M d', strtotime($req['start_date'])) ?> <span class="text-slate-400">to</span> <?= date('M d', strtotime($req['end_date'])) ?>
                                    </div>
                                    <div class="text-xs text-slate-400 mt-0.5">
                                        <?php echo (strtotime($req['end_date']) - strtotime($req['start_date'])) / (60 * 60 * 24) + 1; ?> Days
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-500 italic max-w-xs truncate" title="<?= htmlspecialchars($req['reason']) ?>">
                                    "<?= htmlspecialchars($req['reason']) ?>"
                                </td>
                                <td class="px-6 py-4">
                                    <?php 
                                        $statusClass = match($req['status']) {
                                            'Approved' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                            'Rejected' => 'bg-red-100 text-red-700 border-red-200',
                                            default => 'bg-amber-50 text-amber-600 border-amber-200'
                                        };
                                        $icon = match($req['status']) {
                                            'Approved' => 'fa-check',
                                            'Rejected' => 'fa-times',
                                            default => 'fa-clock'
                                        };
                                    ?>
                                    <span class="px-3 py-1 text-xs font-bold rounded-full border <?= $statusClass ?> flex items-center gap-1 w-fit">
                                        <i class="fas <?= $icon ?> text-[10px]"></i> <?= $req['status'] ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-400">
                                    <?= date('M d, Y', strtotime($req['applied_on'])) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="px-6 py-12 text-center text-slate-400 italic">No leave history found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="view-new" class="fade-in max-w-2xl mx-auto <?= $activeTab=='new'?'':'hidden' ?>">
            <div class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden">
                <div class="bg-slate-50 px-8 py-6 border-b border-slate-100">
                    <h3 class="text-xl font-bold text-slate-800">New Leave Request</h3>
                    <p class="text-slate-500 text-sm mt-1">Please fill in the details below.</p>
                </div>
                
                <form method="POST" class="p-8 space-y-6">
                    <input type="hidden" name="apply_leave" value="1">
                    
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Leave Category</label>
                        <div class="relative">
                            <select name="leave_type" class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none appearance-none transition">
                                <option value="Paid">Paid Time Off (PTO)</option>
                                <option value="Sick">Sick Leave</option>
                                <option value="Casual">Casual Leave</option>
                                <option value="Unpaid">Unpaid Leave</option>
                            </select>
                            <i class="fas fa-chevron-down absolute right-4 top-4 text-slate-400 pointer-events-none"></i>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Start Date</label>
                            <input type="date" name="start_date" required class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-teal-500 outline-none transition text-slate-600">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">End Date</label>
                            <input type="date" name="end_date" required class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-teal-500 outline-none transition text-slate-600">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Reason for Leave</label>
                        <textarea name="reason" rows="4" placeholder="Briefly describe why you are requesting leave..." required class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-teal-500 outline-none transition text-slate-600"></textarea>
                    </div>

                    <div class="pt-4 flex items-center justify-end gap-4">
                        <button type="button" onclick="switchTab('balance')" class="px-6 py-2.5 rounded-lg font-medium text-slate-500 hover:bg-slate-50 transition">Cancel</button>
                        <button type="submit" class="px-8 py-2.5 rounded-lg bg-teal-600 text-white font-bold hover:bg-teal-700 transition shadow-lg shadow-teal-500/30 transform hover:-translate-y-0.5">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>

    </main>

    <script>
        function switchTab(tabName) {
            // 1. Hide all views
            ['balance', 'requests', 'new'].forEach(id => {
                const el = document.getElementById('view-' + id);
                const btn = document.getElementById('tab-' + id);
                
                if(el) el.classList.add('hidden');
                if(btn) {
                    btn.classList.remove('active', 'bg-slate-900', 'text-white', 'shadow-md');
                    btn.classList.add('text-gray-600');
                }
            });

            // 2. Show selected view
            const selectedView = document.getElementById('view-' + tabName);
            if(selectedView) selectedView.classList.remove('hidden');
            
            // 3. Highlight button
            const activeBtn = document.getElementById('tab-' + tabName);
            if(activeBtn) {
                activeBtn.classList.add('active', 'bg-slate-900', 'text-white', 'shadow-md');
                activeBtn.classList.remove('text-gray-600');
            }
        }
    </script>
</body>
</html>