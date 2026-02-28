<?php
// ไฟล์: admin/reports.php
session_start();

// 1. ตรวจสอบสิทธิ์ (Security)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';

try {
    // 2. ดึงข้อมูลรายงานสรุป (ข้อ 3.1.9)
    
    // 2.1 สรุปจำนวนร้านอาหารแยกตามหมวดหมู่
    $stmt_cat_report = $conn->prepare("
        SELECT c.category_name, COUNT(r.restaurant_id) as total 
        FROM restaurant_categories c
        LEFT JOIN restaurants r ON c.category_id = r.category_id
        GROUP BY c.category_id
    ");
    $stmt_cat_report->execute();
    $cat_stats = $stmt_cat_report->fetchAll(PDO::FETCH_ASSOC);

    // 2.2 รายงานข้อมูลร้านอาหาร
    $stmt_rest_list = $conn->prepare("
        SELECT r.restaurant_name, u.first_name, u.last_name, u.status,
               (SELECT COUNT(*) FROM foods WHERE restaurant_id = r.restaurant_id) as menu_count
        FROM restaurants r
        JOIN users u ON r.user_id = u.user_id
        ORDER BY menu_count DESC
    ");
    $stmt_rest_list->execute();
    $rest_reports = $stmt_rest_list->fetchAll(PDO::FETCH_ASSOC);

    // 2.3 รายงานข้อมูลผู้ส่งอาหาร (Rider)
    $stmt_rider_report = $conn->prepare("
        SELECT u.first_name, u.last_name, u.phone, u.status,
               (SELECT COUNT(*) FROM orders WHERE rider_id = u.user_id AND status = 'completed') as jobs_done
        FROM users u
        WHERE u.role = 'rider'
        ORDER BY jobs_done DESC
    ");
    $stmt_rider_report->execute();
    // *** จุดที่แก้ไข: ตั้งชื่อให้ตรงกับด้านล่าง ***
    $rider_reports = $stmt_rider_report->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Reports | FoodDelivery Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['Prompt', 'sans-serif'] }, colors: { brand: { pink: '#f1416c' }, sidebar: '#ffffff', body: '#f4f6f9' } } } }
    </script>
    <style>
        .nav-link { display: flex; align-items: center; padding: 0.75rem 1.5rem; color: #64748b; font-size: 0.875rem; font-weight: 500; border-left: 3px solid transparent; transition: 0.2s; }
        .nav-link:hover, .nav-link.active { color: #f1416c; background-color: #fff1f2; border-left-color: #f1416c; }
        @media print { .no-print { display: none; } aside { display: none; } main { padding: 0; } .rounded-3xl { border-radius: 0; shadow: none; border: 1px solid #ddd; } }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 bg-slate-50">

    <aside class="flex-shrink-0 flex flex-col w-64 h-full bg-white shadow-xl no-print">
        <div class="flex items-center h-16 px-6 text-white bg-brand-pink"><i class="text-xl bi bi-shop me-2"></i><span class="text-lg font-bold">FoodAdmin.</span></div>
        <div class="flex-1 py-4 overflow-y-auto">
            <p class="px-6 mb-2 text-xs font-semibold tracking-wider text-slate-400 uppercase">Main Menu</p>
            <a href="index.php" class="nav-link"><i class="bi bi-grid-1x2-fill mr-3"></i> Dashboard</a>
            <a href="approve_users.php" class="nav-link"><i class="bi bi-person-check mr-3"></i> อนุมัติการใช้งาน</a>
            <p class="px-6 mt-6 mb-2 text-xs font-semibold tracking-wider text-slate-400 uppercase">Analytics</p>
            <a href="reports.php" class="nav-link active"><i class="bi bi-bar-chart-line mr-3"></i> รายงานภาพรวม</a>
            <p class="px-6 mt-6 mb-2 text-xs font-semibold tracking-wider text-slate-400 uppercase">Settings</p>
            <a href="profile.php" class="nav-link"><i class="bi bi-person-badge mr-3"></i> โปรไฟล์ส่วนตัว</a>
            <a href="../logout.php" class="mt-2 text-rose-500 nav-link hover:bg-rose-50"><i class="bi bi-box-arrow-right mr-3"></i> ออกจากระบบ</a>
        </div>
    </aside>

    <div class="flex flex-col flex-1 h-full overflow-hidden">
        <header class="flex items-center justify-between flex-shrink-0 h-16 px-8 bg-white shadow-sm no-print">
            <h1 class="text-lg font-bold text-slate-700">System Reports</h1>
            <div class="flex items-center gap-4">
                <button onclick="window.print()" class="text-slate-400 hover:text-brand-pink transition"><i class="bi bi-printer text-xl"></i></button>
                <div class="h-6 w-px bg-slate-200"></div>
                <div class="flex items-center gap-2">
                    <div class="flex items-center justify-center w-8 h-8 text-sm font-bold text-white rounded-full bg-brand-pink">AD</div>
                    <span class="text-sm font-medium text-slate-700"><?= htmlspecialchars($_SESSION['full_name']); ?></span>
                </div>
            </div>
        </header>

        <main class="flex-1 p-8 overflow-y-auto">
            <div class="max-w-6xl mx-auto">
                <div class="flex justify-between items-end mb-8">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-800">รายงานสรุปผลการดำเนินงาน</h2>
                        <p class="text-slate-500 text-sm">ข้อมูลสถิติล่าสุดของร้านอาหารและผู้ส่งอาหารในระบบ</p>
                    </div>
                    <button onclick="window.print()" class="no-print bg-slate-900 text-white px-5 py-2.5 rounded-xl text-sm font-bold flex items-center gap-2 hover:bg-slate-800 transition">
                        <i class="bi bi-file-earmark-pdf"></i> พิมพ์รายงาน
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-10">
                    <?php foreach($cat_stats as $stat): ?>
                        <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
                            <p class="text-xs font-bold text-slate-400 uppercase mb-1"><?= htmlspecialchars($stat['category_name']) ?></p>
                            <h3 class="text-2xl font-bold text-slate-800"><?= number_format($stat['total']) ?> <span class="text-sm font-normal text-slate-400">ร้าน</span></h3>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden mb-10">
                    <div class="p-6 border-b border-slate-50 flex justify-between items-center">
                        <h3 class="font-bold text-slate-800"><i class="bi bi-shop text-brand-pink mr-2"></i>รายงานข้อมูลร้านอาหาร</h3>
                    </div>
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 text-xs font-bold text-slate-400 uppercase">
                            <tr>
                                <th class="px-6 py-4">ชื่อร้านอาหาร</th>
                                <th class="px-6 py-4 text-center">จำนวนเมนู</th>
                                <th class="px-6 py-4">สถานะสิทธิ์</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-sm">
                            <?php foreach($rest_reports as $r): ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4 font-bold text-slate-700">
                                    <?= htmlspecialchars($r['restaurant_name']) ?> 
                                    <span class="block text-xs font-normal text-slate-400">เจ้าของ: <?= htmlspecialchars($r['first_name'].' '.$r['last_name']) ?></span>
                                </td>
                                <td class="px-6 py-4 text-center font-bold text-indigo-600"><?= number_format($r['menu_count']) ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase <?= $r['status'] == 'approved' ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600' ?>">
                                        <?= $r['status'] ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="p-6 border-b border-slate-50">
                        <h3 class="font-bold text-slate-800"><i class="bi bi-motorcycle text-brand-pink mr-2"></i>รายงานผลงานผู้ส่งอาหาร (Rider)</h3>
                    </div>
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 text-xs font-bold text-slate-400 uppercase">
                            <tr>
                                <th class="px-6 py-4">ชื่อ-นามสกุล</th>
                                <th class="px-6 py-4">เบอร์โทรศัพท์</th>
                                <th class="px-6 py-4 text-center">ส่งสำเร็จแล้ว</th>
                                <th class="px-6 py-4">สถานะ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-sm">
                            <?php if(!empty($rider_reports)): ?>
                                <?php foreach($rider_reports as $ri): ?>
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-6 py-4 font-bold text-slate-700"><?= htmlspecialchars($ri['first_name'].' '.$ri['last_name']) ?></td>
                                    <td class="px-6 py-4 text-slate-500"><?= htmlspecialchars($ri['phone']) ?></td>
                                    <td class="px-6 py-4 text-center font-bold text-brand-pink"><?= number_format($ri['jobs_done'] ?? 0) ?> งาน</td>
                                    <td class="px-6 py-4">
                                        <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase <?= $ri['status'] == 'approved' ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600' ?>">
                                            <?= $ri['status'] ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-10 text-center text-slate-400">ไม่พบข้อมูลผู้ส่งอาหารในระบบ</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>