<?php
require 'db.php';
require 'auth.php';
requireLogin();

// NOTE: Ensure this matches how you save ID in auth.php
$userId = $_SESSION['user_id']; 
$fullName = $_SESSION['full_name'] ?? 'User';
$today = date('Y-m-d');
$message = '';

// --- 1. HANDLE CLOCK IN / CLOCK OUT ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $currentTime = date('H:i:s');

    if ($action == 'clock_in') {
        // Check if already clocked in today
        $stmt = $pdo->prepare("SELECT id FROM attendance WHERE employee_id = ? AND date = ?");
        $stmt->execute([$userId, $today]);
        
        if ($stmt->rowCount() == 0) {
            $status = ($currentTime > '09:30:00') ? 'Late' : 'Present';
            
            $stmt = $pdo->prepare("INSERT INTO attendance (employee_id, date, clock_in, status) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$userId, $today, $currentTime, $status])) {
                $message = "<div class='bg-green-100 text-green-700 p-3 rounded mb-4'>Successfully Clocked In! Timer Started.</div>";
            }
        }
    } elseif ($action == 'clock_out') {
        $stmt = $pdo->prepare("UPDATE attendance SET clock_out = ? WHERE employee_id = ? AND date = ?");
        if ($stmt->execute([$currentTime, $userId, $today])) {
            $message = "<div class='bg-blue-100 text-blue-700 p-3 rounded mb-4'>Successfully Clocked Out.</div>";
        }
    }
}

// --- 2. GET TODAY'S STATUS ---
$stmt = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = ? AND date = ?");
$stmt->execute([$userId, $today]);
$todayRecord = $stmt->fetch();

$isClockedIn = $todayRecord && $todayRecord['clock_in'] && !$todayRecord['clock_out'];
$isCompleted = $todayRecord && $todayRecord['clock_in'] && $todayRecord['clock_out'];

// Prepare Clock In Time for JS
$clockInTimeJS = $isClockedIn ? $todayRecord['clock_in'] : '';

// --- 3. GET HISTORY ---
$stmt = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = ? ORDER BY date DESC LIMIT 10");
$stmt->execute([$userId]);
$history = $stmt->fetchAll();

function getProfileImage($name) {
    return "https://ui-avatars.com/api/?name=" . urlencode($name) . "&background=0D9488&color=fff&size=128&bold=true";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #F8FAFC; }
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
                    <a href="attendance.php" class="text-white border-b-2 border-teal-500 px-1 py-4 text-sm font-medium">Attendance</a>
                    <a href="leave.php" class="text-slate-300 hover:text-white px-1 py-4 text-sm font-medium transition-colors">Leaves</a>
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
        
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-slate-900">Attendance</h1>
            <p class="text-slate-500 mt-1">View your attendance records and clock in/out</p>
        </div>

        <?= $message ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="lg:col-span-1 h-fit">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="bg-teal-700 px-6 py-4 flex items-center gap-3">
                        <i class="far fa-clock text-teal-100 text-xl"></i>
                        <h2 class="text-white font-bold text-lg">
                            <?= $isClockedIn ? 'Work Timer' : 'Attendance Clock' ?>
                        </h2>
                    </div>

                    <div class="p-8 flex flex-col items-center justify-center text-center">
                        
                        <div class="font-mono text-5xl font-bold text-slate-800 tracking-tight mb-2" id="digital-clock">
                            --:--:--
                        </div>
                        <p class="text-slate-500 text-sm font-medium mb-6" id="clock-label">
                            <?= $isClockedIn ? 'Duration Worked' : date('l, F j, Y') ?>
                        </p>

                        <div class="mb-8">
                            <?php if ($isClockedIn): ?>
                                <span class="bg-green-100 text-green-700 px-4 py-1.5 rounded-full text-sm font-bold flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span> Timer Running
                                </span>
                            <?php elseif ($isCompleted): ?>
                                <span class="bg-blue-100 text-blue-700 px-4 py-1.5 rounded-full text-sm font-bold">
                                    <i class="fas fa-check-circle"></i> Shift Completed
                                </span>
                            <?php else: ?>
                                <span class="bg-gray-100 text-gray-500 px-4 py-1.5 rounded-full text-sm font-bold">
                                    <span class="w-2 h-2 rounded-full bg-gray-400"></span> Not Clocked In
                                </span>
                            <?php endif; ?>
                        </div>

                        <form method="POST" class="w-full">
                            <?php if ($isClockedIn): ?>
                                <input type="hidden" name="action" value="clock_out">
                                <button type="submit" class="w-full bg-red-500 hover:bg-red-600 text-white font-bold py-4 rounded-xl shadow-lg shadow-red-500/30 transition transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                                    <i class="fas fa-sign-out-alt"></i> Clock Out
                                </button>
                                <p class="text-xs text-slate-400 mt-3">Started at: <?= date('h:i A', strtotime($todayRecord['clock_in'])) ?></p>

                            <?php elseif ($isCompleted): ?>
                                <button type="button" disabled class="w-full bg-slate-100 text-slate-400 font-bold py-4 rounded-xl cursor-not-allowed border border-slate-200">
                                    Done for Today
                                </button>
                                <p class="text-xs text-slate-400 mt-3">
                                    <?= date('h:i A', strtotime($todayRecord['clock_in'])) ?> - <?= date('h:i A', strtotime($todayRecord['clock_out'])) ?>
                                </p>

                            <?php else: ?>
                                <input type="hidden" name="action" value="clock_in">
                                <button type="submit" class="w-full bg-teal-600 hover:bg-teal-700 text-white font-bold py-4 rounded-xl shadow-lg shadow-teal-500/30 transition transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                                    <i class="fas fa-sign-in-alt"></i> Clock In
                                </button>
                                <p class="text-xs text-slate-400 mt-3">Office starts at 09:30 AM</p>
                            <?php endif; ?>
                        </form>

                    </div>
                </div>
            </div>

            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 min-h-[400px]">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                            <i class="far fa-list-alt text-teal-600"></i> Attendance Records
                        </h3>
                        <div class="flex bg-slate-100 p-1 rounded-lg">
                            <button class="px-3 py-1 bg-white text-xs font-bold text-slate-700 rounded shadow-sm">List View</button>
                            <button class="px-3 py-1 text-xs font-medium text-slate-500 hover:text-slate-700">Calendar</button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-100 text-xs text-slate-500 uppercase">
                                    <th class="px-4 py-3 font-semibold">Date</th>
                                    <th class="px-4 py-3 font-semibold">Clock In</th>
                                    <th class="px-4 py-3 font-semibold">Clock Out</th>
                                    <th class="px-4 py-3 font-semibold">Hours</th>
                                    <th class="px-4 py-3 font-semibold text-right">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <?php if(count($history) > 0): ?>
                                    <?php foreach($history as $row): ?>
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="px-4 py-4 text-sm font-medium text-slate-700">
                                            <?= date('M d, Y', strtotime($row['date'])) ?>
                                        </td>
                                        <td class="px-4 py-4 text-sm text-slate-600">
                                            <?= date('h:i A', strtotime($row['clock_in'])) ?>
                                        </td>
                                        <td class="px-4 py-4 text-sm text-slate-600">
                                            <?= $row['clock_out'] ? date('h:i A', strtotime($row['clock_out'])) : '<span class="text-slate-400">-</span>' ?>
                                        </td>
                                        <td class="px-4 py-4 text-sm font-mono text-slate-600">
                                            <?php 
                                            if ($row['clock_out']) {
                                                $start = strtotime($row['clock_in']);
                                                $end = strtotime($row['clock_out']);
                                                $hours = round(($end - $start) / 3600, 1);
                                                echo $hours . "h";
                                            } else {
                                                echo "-";
                                            }
                                            ?>
                                        </td>
                                        <td class="px-4 py-4 text-right">
                                            <?php 
                                                $badgeColor = match($row['status']) {
                                                    'Present' => 'bg-green-100 text-green-700',
                                                    'Late' => 'bg-orange-100 text-orange-700',
                                                    'Absent' => 'bg-red-100 text-red-700',
                                                    default => 'bg-gray-100 text-gray-700'
                                                };
                                            ?>
                                            <span class="inline-block px-2 py-1 text-xs font-bold rounded <?= $badgeColor ?>">
                                                <?= $row['status'] ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-8 text-slate-400 text-sm">No attendance records found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

    </main>

    <script>
        // Pass PHP variables to JS
        const isClockedIn = <?= $isClockedIn ? 'true' : 'false' ?>;
        const clockInTimeStr = "<?= $clockInTimeJS ?>"; // Format HH:MM:SS

        function updateDisplay() {
            const display = document.getElementById('digital-clock');
            const now = new Date();
            
            if (isClockedIn && clockInTimeStr) {
                // --- TIMER MODE (Count Up) ---
                
                // Parse the clock-in time
                // We create a Date object for TODAY with the clock-in time
                const [h, m, s] = clockInTimeStr.split(':');
                const startTime = new Date();
                startTime.setHours(h, m, s, 0);

                // Calculate difference in milliseconds
                let diff = now - startTime;
                
                // If diff is negative (e.g. timezone skew), just show 0
                if (diff < 0) diff = 0;

                // Convert to HH:MM:SS
                const hours = Math.floor(diff / 3600000);
                const minutes = Math.floor((diff % 3600000) / 60000);
                const seconds = Math.floor((diff % 60000) / 1000);

                const hh = String(hours).padStart(2, '0');
                const mm = String(minutes).padStart(2, '0');
                const ss = String(seconds).padStart(2, '0');

                display.textContent = `${hh}:${mm}:${ss}`;
                display.classList.add('text-teal-700'); // Add green tint for timer

            } else {
                // --- CLOCK MODE (Real Time) ---
                let hours = now.getHours();
                const minutes = String(now.getMinutes()).padStart(2, '0');
                const seconds = String(now.getSeconds()).padStart(2, '0');
                const ampm = hours >= 12 ? 'PM' : 'AM';
                
                hours = hours % 12;
                hours = hours ? hours : 12; 
                hours = String(hours).padStart(2, '0');

                display.textContent = `${hours}:${minutes}:${seconds} ${ampm}`;
                display.classList.remove('text-teal-700');
            }
        }

        setInterval(updateDisplay, 1000);
        updateDisplay(); // Run immediately
    </script>
</body>
</html>