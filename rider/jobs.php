<?php
// ไฟล์: rider/jobs.php
session_start();

// 1. ตรวจสอบสิทธิ์ (Security)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'rider') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';
$user_id = $_SESSION['user_id'];

try {
    // 2. ดึงข้อมูลงานทั้งหมดที่ไรเดอร์คนนี้รับผิดชอบ (เรียงจากใหม่ไปเก่า)
    $stmt = $conn->prepare("
        SELECT o.*, r.restaurant_name, u.first_name, u.last_name 
        FROM orders o
        JOIN restaurants r ON o.restaurant_id = r.restaurant_id
        JOIN users u ON o.customer_id = u.user_id
        WHERE o.rider_id = :rid
        ORDER BY o.order_date DESC
    ");
    $stmt->execute([':rid' => $user_id]);
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) { die("Error: " . $e->getMessage()); }

// ฟังก์ชันกำหนดสี Badge ตามสถานะ (เลียนแบบสีในรูป image_63a684.jpg)
function getStatusBadge($status) {
    switch ($status) {
        case 'delivering': return 'bg-blue-100 text-blue-600 border-blue-200'; // กำลังส่ง
        case 'completed': return 'bg-emerald-100 text-emerald-600 border-emerald-200'; // สำเร็จ
        case 'cancelled': return 'bg-rose-100 text-rose-600 border-rose-200'; // ยกเลิก
        default: return 'bg-slate-100 text-slate-500 border-slate-200';
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Jobs | RiderPanel</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['Prompt', 'sans-serif'] }, colors: { brand: { green: '#10b981' } } } } }
    </script>
    <style>
        .nav-link { display: flex; align-items: center; padding: 0.85rem 1.5rem; color: #64748b; font-size: 0.9rem; font-weight: 500; border-left: 4px solid transparent; transition: 0.2s; }
        .nav-link:hover, .nav-link.active { color: #10b981; background-color: #f0fdf4; border-left-color: #10b981; }
    </style>
</head>
<body class="bg-[#f8fafc] flex h-screen overflow-hidden text-slate-800">

    <aside class="w-64 bg-white shadow-2xl z-20 flex-shrink-0 flex flex-col">
        <div class="h-16 flex items-center px-6 bg-brand-green text-white">
            <i class="bi bi-bicycle text-2xl me-2"></i>
            <span class="font-bold text-lg tracking-wide">RiderExpress</span>
        </div>
        <div class="flex-1 py-6 overflow-y-auto">
            <a href="index.php" class="nav-link"><i class="bi bi-speedometer2 mr-3"></i> แดชบอร์ด</a>
            <a href="jobs.php" class="nav-link active"><i class="bi bi-box-seam mr-3"></i> ประวัติงานส่ง</a>
            <p class="px-6 text-xs font-bold text-slate-400 uppercase tracking-widest mb-4 mt-8">บัญชีของฉัน</p>
            <a href="profile.php" class="nav-link"><i class="bi bi-person-gear mr-3"></i> โปรไฟล์ส่วนตัว</a>
            <a href="../logout.php" class="nav-link text-rose-500 mt-4"><i class="bi bi-box-arrow-right mr-3"></i> ออกจากระบบ</a>
        </div>
    </aside>

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-8 z-10">
            <h1 class="font-bold text-slate-700">รายการงานทั้งหมดของคุณ</h1>
            <div class="flex items-center gap-4">
                <div class="w-9 h-9 rounded-full bg-brand-green text-white flex items-center justify-center font-bold text-sm shadow-sm">RD</div>
                <span class="text-sm font-bold text-slate-700 hidden md:block"><?= htmlspecialchars($_SESSION['full_name']); ?></span>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-8">
            <div class="max-w-6xl mx-auto">
                
                <div class="mb-10 flex justify-between items-end">
                    <div>
                        <h2 class="text-3xl font-black text-slate-900 tracking-tight">Job History</h2>
                        <p class="text-slate-400 mt-1">รายการส่งอาหารที่คุณเคยดำเนินการทั้งหมด</p>
                    </div>
                    <button onclick="window.print()" class="bg-white text-slate-600 px-5 py-2.5 rounded-xl text-sm font-bold shadow-sm border border-slate-200 hover:bg-slate-50 transition flex items-center gap-2">
                        <i class="bi bi-printer"></i> พิมพ์รายงาน
                    </button>
                </div>

                <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50/50 border-b border-slate-100">
                                    <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Order ID</th>
                                    <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">ร้านอาหาร</th>
                                    <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">ชื่อลูกค้า</th>
                                    <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-center">ยอดเงิน</th>
                                    <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-center">สถานะ</th>
                                    <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-right">ดำเนินการ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <?php foreach ($jobs as $job): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors group">
                                    <td class="px-8 py-5">
                                        <span class="font-black text-slate-400 group-hover:text-brand-green transition-colors">#<?= str_pad($job['order_id'], 5, '0', STR_PAD_LEFT); ?></span>
                                    </td>
                                    <td class="px-8 py-5">
                                        <p class="font-bold text-slate-700"><?= htmlspecialchars($job['restaurant_name']); ?></p>
                                        <p class="text-[10px] text-slate-400 font-medium"><?= date('d M Y, H:i', strtotime($job['order_date'])); ?></p>
                                    </td>
                                    <td class="px-8 py-5">
                                        <p class="font-medium text-slate-600"><?= htmlspecialchars($job['first_name'] . ' ' . $job['last_name']); ?></p>
                                    </td>
                                    <td class="px-8 py-5 text-center">
                                        <p class="font-black text-slate-800">฿<?= number_format($job['net_price'], 2); ?></p>
                                    </td>
                                    <td class="px-8 py-5 text-center">
                                        <span class="px-4 py-1 rounded-full text-[10px] font-bold uppercase border <?= getStatusBadge($job['status']); ?>">
                                            <?= $job['status']; ?>
                                        </span>
                                    </td>
                                    <td class="px-8 py-5 text-right">
                                        <?php if($job['status'] == 'delivering'): ?>
                                            <a href="delivery_detail.php?order_id=<?= $job['order_id']; ?>" class="bg-brand-green text-white px-4 py-1.5 rounded-lg text-[10px] font-bold shadow-lg shadow-emerald-100 hover:scale-105 transition-transform inline-block">จัดการส่ง</a>
                                        <?php else: ?>
                                            <a href="../export_receipt.php?order_id=<?= $job['order_id']; ?>" class="text-slate-300 hover:text-brand-green transition-colors"><i class="bi bi-file-earmark-text text-lg"></i></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>

                                <?php if(empty($jobs)): ?>
                                    <tr>
                                        <td colspan="6" class="px-8 py-20 text-center text-slate-400 italic font-medium">
                                            คุณยังไม่มีประวัติการส่งอาหารในระบบ
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>

</body>
</html>