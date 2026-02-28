<?php
// ไฟล์: restaurant/sales_report.php
session_start();

// 1. ตรวจสอบสิทธิ์ (Security)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'restaurant') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';
$user_id = $_SESSION['user_id'];

try {
    // 2. ดึงข้อมูลร้านค้า
    $stmt_res = $conn->prepare("SELECT restaurant_id, restaurant_name FROM restaurants WHERE user_id = :uid");
    $stmt_res->execute([':uid' => $user_id]);
    $restaurant = $stmt_res->fetch(PDO::FETCH_ASSOC);
    $res_id = $restaurant['restaurant_id'] ?? 0;

    // 3. ข้อมูลสรุปตัวเลข (Stat Cards) - ดึงจาก net_price ตามฐานข้อมูลใหม่
    // ยอดขายรวมทั้งหมด (เฉพาะออเดอร์ที่สำเร็จ)
    $stmt_total = $conn->prepare("SELECT SUM(net_price) FROM orders WHERE restaurant_id = :rid AND status = 'completed'");
    $stmt_total->execute([':rid' => $res_id]);
    $total_sales = $stmt_total->fetchColumn() ?? 0;

    // จำนวนออเดอร์ทั้งหมด
    $stmt_count = $conn->prepare("SELECT COUNT(*) FROM orders WHERE restaurant_id = :rid");
    $stmt_count->execute([':rid' => $res_id]);
    $total_orders = $stmt_count->fetchColumn() ?? 0;

    // 4. ข้อมูลสำหรับกราฟเส้น (ยอดขายรายวันในเดือนปัจจุบัน)
    $stmt_line = $conn->prepare("
        SELECT DATE(order_date) as date, SUM(net_price) as daily_total 
        FROM orders 
        WHERE restaurant_id = :rid AND status = 'completed' 
        AND MONTH(order_date) = MONTH(CURRENT_DATE())
        GROUP BY DATE(order_date)
        ORDER BY date ASC
    ");
    $stmt_line->execute([':rid' => $res_id]);
    $line_data = $stmt_line->fetchAll(PDO::FETCH_ASSOC);

    // 5. ข้อมูลสำหรับกราฟวงกลม (สัดส่วนสถานะออเดอร์)
    $stmt_pie = $conn->prepare("SELECT status, COUNT(*) as count FROM orders WHERE restaurant_id = :rid GROUP BY status");
    $stmt_pie->execute([':rid' => $res_id]);
    $pie_data = $stmt_pie->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) { die("Error: " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report | ShopManager</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['Prompt', 'sans-serif'] }, colors: { brand: { pink: '#f1416c', orange: '#ff5c00' } } } } }
    </script>
    <style>
        .nav-link { display: flex; align-items: center; padding: 0.85rem 1.5rem; color: #64748b; font-size: 0.9rem; font-weight: 500; border-left: 4px solid transparent; transition: 0.2s; }
        .nav-link:hover, .nav-link.active { color: #ff5c00; background-color: #fff7f3; border-left-color: #ff5c00; }
        @media print { .no-print { display: none !important; } aside { display: none; } main { padding: 0; } .card { border: 1px solid #eee; } }
    </style>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800">

    <aside class="w-64 bg-white shadow-2xl z-20 flex-shrink-0 flex flex-col no-print">
        <div class="h-16 flex items-center px-6 bg-brand-orange text-white">
            <i class="bi bi-graph-up-arrow text-xl me-2"></i>
            <span class="font-bold text-lg tracking-wide">Analytics</span>
        </div>
        <div class="flex-1 py-6 overflow-y-auto">
            <a href="index.php" class="nav-link"><i class="bi bi-speedometer2 mr-3"></i> แดชบอร์ด</a>
            <a href="orders.php" class="nav-link"><i class="bi bi-cart-check mr-3"></i> รายการสั่งอาหาร</a>
            <p class="px-6 text-xs font-bold text-slate-400 uppercase tracking-widest mb-4 mt-8">รายงาน</p>
            <a href="sales_report.php" class="nav-link active"><i class="bi bi-bar-chart-line mr-3"></i> สรุปยอดขาย [3.2.16]</a>
            <p class="px-6 text-xs font-bold text-slate-400 uppercase tracking-widest mb-4 mt-8">ตั้งค่า</p>
            <a href="profile.php" class="nav-link"><i class="bi bi-person-gear mr-3"></i> โปรไฟล์ร้าน</a>
            <a href="../logout.php" class="nav-link text-rose-500 mt-4"><i class="bi bi-box-arrow-right mr-3"></i> ออกจากระบบ</a>
        </div>
    </aside>

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-8 z-10 no-print">
            <h1 class="font-bold text-slate-700 uppercase text-sm tracking-widest">Sales Analysis</h1>
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-brand-pink text-white flex items-center justify-center font-bold text-xs shadow-sm">AD</div>
                    <span class="text-sm font-bold text-slate-700 hidden md:block"><?= htmlspecialchars($_SESSION['full_name']); ?></span>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-8">
            <div class="max-w-6xl mx-auto">
                
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-4">
                    <div>
                        <h2 class="text-3xl font-black text-slate-900">สรุปยอดขาย</h2>
                        <p class="text-slate-400 mt-1">รายงานความเคลื่อนไหวรายได้และออเดอร์ของร้านคุณ</p>
                    </div>
                    <button onclick="window.print()" class="no-print bg-brand-pink text-white px-6 py-3 rounded-2xl font-bold shadow-lg shadow-rose-100 hover:scale-105 transition-transform flex items-center gap-2">
                        <i class="bi bi-download"></i> ดาวน์โหลดรายงาน
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                    <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100 relative overflow-hidden card">
                        <p class="text-xs font-bold text-slate-400 uppercase mb-2">รายได้สุทธิทั้งหมด</p>
                        <h3 class="text-3xl font-black text-brand-orange">฿<?= number_format($total_sales, 2); ?></h3>
                        <div class="absolute -right-4 -bottom-4 opacity-5 text-8xl text-brand-orange rotate-12"><i class="bi bi-wallet2"></i></div>
                    </div>
                    <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100 card">
                        <p class="text-xs font-bold text-slate-400 uppercase mb-2">คำสั่งซื้อทั้งหมด</p>
                        <h3 class="text-3xl font-black text-slate-800"><?= number_format($total_orders); ?> <span class="text-sm font-normal">บิล</span></h3>
                    </div>
                    <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100 card">
                        <p class="text-xs font-bold text-slate-400 uppercase mb-2">เฉลี่ยต่อบิล</p>
                        <h3 class="text-3xl font-black text-indigo-600">฿<?= $total_orders > 0 ? number_format($total_sales / $total_orders, 2) : 0; ?></h3>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-10">
                    <div class="lg:col-span-2 bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100 card">
                        <div class="flex justify-between items-center mb-8">
                            <h4 class="font-bold text-slate-800 tracking-tight">กราฟยอดขายรายวัน (เดือนนี้)</h4>
                            <span class="text-[10px] bg-emerald-50 text-emerald-600 px-3 py-1 rounded-full font-bold uppercase tracking-widest">Live Data</span>
                        </div>
                        <canvas id="salesLineChart" height="250"></canvas>
                    </div>

                    <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100 card">
                        <h4 class="font-bold text-slate-800 mb-8 text-center tracking-tight">สัดส่วนสถานะออเดอร์</h4>
                        <div class="relative h-64">
                            <canvas id="statusPieChart"></canvas>
                        </div>
                        <div class="mt-8 space-y-3">
                            <?php foreach($pie_data as $p): ?>
                                <div class="flex justify-between text-xs font-bold">
                                    <span class="text-slate-400 uppercase tracking-widest"><?= $p['status']; ?></span>
                                    <span class="text-slate-700"><?= $p['count']; ?> รายการ</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script>
        // 1. Line Chart
        const lineCtx = document.getElementById('salesLineChart').getContext('2d');
        new Chart(lineCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($line_data, 'date')); ?>,
                datasets: [{
                    label: 'ยอดขาย (บาท)',
                    data: <?= json_encode(array_column($line_data, 'daily_total')); ?>,
                    borderColor: '#f1416c',
                    backgroundColor: 'rgba(241, 65, 108, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 4,
                    pointRadius: 4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#f1416c',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, grid: { display: false } }, x: { grid: { display: false } } }
            }
        });

        // 2. Pie Chart
        const pieCtx = document.getElementById('statusPieChart').getContext('2d');
        new Chart(pieCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($pie_data, 'status')); ?>,
                datasets: [{
                    data: <?= json_encode(array_column($pie_data, 'count')); ?>,
                    backgroundColor: ['#f1416c', '#ff5c00', '#6366f1', '#10b981', '#f59e0b'],
                    borderWidth: 0,
                    hoverOffset: 15
                }]
            },
            options: {
                cutout: '75%',
                maintainAspectRatio: false,
                plugins: { legend: { display: false } }
            }
        });
    </script>
</body>
</html>