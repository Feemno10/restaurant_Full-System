<?php
// ไฟล์: rider/delivery_detail.php
session_start();

// 1. ตรวจสอบสิทธิ์ (Security)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'rider') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';
$user_id = $_SESSION['user_id'];
$message = '';

// ตรวจสอบ Order ID
if (!isset($_GET['order_id'])) {
    header("Location: index.php");
    exit();
}
$order_id = $_GET['order_id'];

try {
    // 2. จัดการเมื่อกดปุ่ม "ส่งอาหารสำเร็จ" (ข้อ 3.4.9)
    if (isset($_POST['complete_delivery'])) {
        $stmt_done = $conn->prepare("UPDATE orders SET status = 'completed' WHERE order_id = :oid AND rider_id = :rid");
        $stmt_done->execute([':oid' => $order_id, ':rid' => $user_id]);
        header("Location: index.php?msg=delivery_success");
        exit();
    }

    // 3. ดึงข้อมูลรายละเอียดงาน
    $stmt = $conn->prepare("
        SELECT o.*, 
               r.restaurant_name, r.address as res_addr,
               u.first_name, u.last_name, u.phone as cus_phone
        FROM orders o
        JOIN restaurants r ON o.restaurant_id = r.restaurant_id
        JOIN users u ON o.customer_id = u.user_id
        WHERE o.order_id = :oid AND o.rider_id = :rid
    ");
    $stmt->execute([':oid' => $order_id, ':rid' => $user_id]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$job) {
        die("ไม่พบข้อมูลงานนี้ หรือคุณไม่มีสิทธิ์เข้าถึงครับ");
    }

    // 4. ดึงรายการอาหาร
    $stmt_items = $conn->prepare("
        SELECT od.*, f.food_name 
        FROM order_details od
        JOIN foods f ON od.food_id = f.food_id
        WHERE od.order_id = :oid
    ");
    $stmt_items->execute([':oid' => $order_id]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) { die("Error: " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Task Details | FoodDelivery</title>
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
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800">

    <aside class="w-64 bg-white shadow-2xl z-20 flex-shrink-0 flex flex-col no-print">
        <div class="h-16 flex items-center px-6 bg-brand-green text-white">
            <i class="bi bi-bicycle text-2xl me-2"></i>
            <span class="font-bold text-lg">RiderTask</span>
        </div>
        <div class="flex-1 py-6 overflow-y-auto">
            <a href="index.php" class="nav-link"><i class="bi bi-speedometer2 mr-3"></i> แดชบอร์ด</a>
            <a href="index.php" class="nav-link active"><i class="bi bi-geo-alt mr-3"></i> รายละเอียดการส่ง</a>
            <div class="mt-8 px-6 text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">อื่น ๆ</div>
            <a href="profile.php" class="nav-link"><i class="bi bi-person-gear mr-3"></i> โปรไฟล์ส่วนตัว</a>
            <a href="../logout.php" class="nav-link text-rose-500 mt-4"><i class="bi bi-box-arrow-right mr-3"></i> ออกจากระบบ</a>
        </div>
    </aside>

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-8 z-10">
            <div class="flex items-center gap-2">
                <a href="index.php" class="text-slate-400 hover:text-brand-green transition"><i class="bi bi-arrow-left text-xl"></i></a>
                <h1 class="font-bold text-slate-700 ml-2 uppercase text-xs tracking-widest">Job Details / #<?= str_pad($order_id, 5, '0', STR_PAD_LEFT); ?></h1>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-brand-green text-white flex items-center justify-center font-bold text-xs">RD</div>
                <span class="text-sm font-bold text-slate-700 hidden md:block"><?= htmlspecialchars($_SESSION['full_name']); ?></span>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-4 sm:p-8">
            <div class="max-w-4xl mx-auto">
                
                <div class="mb-8 flex justify-between items-center bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100">
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">สถานะงาน</p>
                        <span class="px-4 py-1 rounded-full bg-blue-100 text-blue-600 text-xs font-bold uppercase border border-blue-200">
                            <?= $job['status']; ?>
                        </span>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">ยอดเงินที่ต้องเก็บ</p>
                        <p class="text-2xl font-black text-brand-green">฿<?= number_format($job['net_price'], 2); ?></p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10">
                    <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100 relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-6 opacity-5 text-7xl text-brand-green"><i class="bi bi-shop"></i></div>
                        <h3 class="text-sm font-bold text-slate-400 uppercase tracking-widest mb-6"><i class="bi bi-geo-fill text-brand-green mr-1"></i> จุดรับอาหาร</h3>
                        <p class="text-xl font-black text-slate-800 mb-2"><?= htmlspecialchars($job['restaurant_name']); ?></p>
                        <p class="text-sm text-slate-500 leading-relaxed italic">"<?= htmlspecialchars($job['res_addr']); ?>"</p>
                    </div>

                    <div class="bg-slate-900 p-8 rounded-[2.5rem] shadow-xl text-white relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-6 opacity-10 text-7xl"><i class="bi bi-person-circle"></i></div>
                        <h3 class="text-sm font-bold text-slate-400 uppercase tracking-widest mb-6"><i class="bi bi-house-door-fill text-brand-green mr-1"></i> จุดส่งอาหาร</h3>
                        <p class="text-xl font-black mb-2"><?= htmlspecialchars($job['first_name'] . ' ' . $job['last_name']); ?></p>
                        <p class="text-brand-green font-bold mb-4 flex items-center gap-2"><i class="bi bi-telephone-outbound-fill"></i> <?= htmlspecialchars($job['phone'] ?? $job['cus_phone']); ?></p>
                        <p class="text-xs text-slate-400 font-medium">กรุณาโทรหาลูกค้าเมื่อถึงที่หมาย</p>
                    </div>
                </div>

                <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden mb-12">
                    <div class="p-6 border-b border-slate-50 bg-slate-50/50">
                        <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest">รายการอาหารที่ต้องรับ</h4>
                    </div>
                    <div class="p-8">
                        <ul class="space-y-4">
                            <?php foreach ($items as $item): ?>
                                <li class="flex justify-between items-center">
                                    <div class="flex items-center gap-4">
                                        <span class="w-8 h-8 rounded-xl bg-emerald-50 text-brand-green flex items-center justify-center font-bold text-sm"><?= $item['quantity']; ?>x</span>
                                        <span class="font-bold text-slate-700"><?= htmlspecialchars($item['food_name']); ?></span>
                                    </div>
                                    <span class="text-sm font-medium text-slate-400">฿<?= number_format($item['price'] * $item['quantity'], 2); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="mt-8 pt-6 border-t border-dashed border-slate-200 flex justify-between items-center">
                            <span class="font-bold text-slate-400 italic">รวมราคาสุทธิ (หักส่วนลดแล้ว)</span>
                            <span class="text-3xl font-black text-brand-green">฿<?= number_format($job['net_price'], 2); ?></span>
                        </div>
                    </div>
                </div>

                <?php if ($job['status'] == 'delivering'): ?>
                    <form action="" method="POST" onsubmit="return confirm('ยืนยันว่าคุณส่งอาหารสำเร็จแล้ว?')">
                        <button type="submit" name="complete_delivery" class="w-full bg-brand-green text-white py-6 rounded-[2rem] font-black text-xl shadow-2xl shadow-emerald-200 hover:bg-emerald-400 transition-all flex items-center justify-center gap-3 active:scale-95">
                            <i class="bi bi-check-all text-3xl"></i> ส่งอาหารสำเร็จแล้ว [3.4.9]
                        </button>
                    </form>
                <?php else: ?>
                    <div class="bg-slate-100 text-slate-400 py-6 rounded-[2rem] text-center font-bold italic">
                        งานนี้ดำเนินการเสร็จสิ้นแล้ว
                    </div>
                <?php endif; ?>

            </div>
        </main>
    </div>

</body>
</html>