<?php
// ไฟล์: rider/job_detail.php
session_start();

// 1. ตรวจสอบสิทธิ์ (Security)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'rider') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';
$user_id = $_SESSION['user_id'];

// ตรวจสอบ Order ID
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    header("Location: index.php");
    exit();
}
$order_id = $_GET['order_id'];

try {
    // 2. ดึงข้อมูลออเดอร์ ข้อมูลร้าน และข้อมูลลูกค้า
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

    // 3. ดึงรายการอาหารในออเดอร์นี้
    $stmt_items = $conn->prepare("
        SELECT od.*, f.food_name 
        FROM order_details od
        JOIN foods f ON od.food_id = f.food_id
        WHERE od.order_id = :oid
    ");
    $stmt_items->execute([':oid' => $order_id]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Detail #<?= $order_id ?> | RiderExpress</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['Prompt', 'sans-serif'] } } } }
    </script>
</head>
<body class="bg-slate-50 font-sans text-slate-800">

    <header class="bg-white shadow-sm sticky top-0 z-30">
        <div class="max-w-md mx-auto px-4 py-4 flex items-center justify-between">
            <a href="index.php" class="text-slate-400 hover:text-emerald-500 transition"><i class="bi bi-arrow-left fs-4"></i></a>
            <h1 class="font-bold text-slate-700">รายละเอียดงานส่งอาหาร</h1>
            <div class="w-8"></div>
        </div>
    </header>

    <main class="max-w-md mx-auto p-4 space-y-6 pb-24">
        
        <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 flex justify-between items-center">
            <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">รหัสออเดอร์</p>
                <h2 class="text-xl font-black text-slate-800">#<?= str_pad($order_id, 5, '0', STR_PAD_LEFT); ?></h2>
            </div>
            <div class="text-right">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">สถานะปัจจุบัน</p>
                <span class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-600 text-[10px] font-bold uppercase border border-emerald-200">
                    <?= $job['status']; ?>
                </span>
            </div>
        </div>

        <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 relative overflow-hidden">
            <div class="absolute -top-4 -right-4 opacity-5 text-6xl text-emerald-500"><i class="bi bi-shop"></i></div>
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                <i class="bi bi-geo-alt-fill text-emerald-500"></i> จุดรับอาหาร (ร้านค้า)
            </h3>
            <p class="text-lg font-bold text-slate-800"><?= htmlspecialchars($job['restaurant_name']); ?></p>
            <p class="text-sm text-slate-500 mt-1"><?= htmlspecialchars($job['res_addr']); ?></p>
        </div>

        <div class="bg-slate-900 p-8 rounded-[2rem] shadow-xl text-white relative overflow-hidden">
            <div class="absolute -top-4 -right-4 opacity-10 text-6xl"><i class="bi bi-house-door-fill"></i></div>
            <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                <i class="bi bi-geo-fill text-emerald-400"></i> จุดส่งอาหาร (ลูกค้า)
            </h3>
            <p class="text-xl font-black mb-1"><?= htmlspecialchars($job['first_name'].' '.$job['last_name']); ?></p>
            <p class="text-emerald-400 font-bold mb-4"><i class="bi bi-telephone"></i> <?= htmlspecialchars($job['cus_phone']); ?></p>
            <div class="bg-white/10 p-4 rounded-2xl flex items-center gap-3">
                <i class="bi bi-cash-coin text-2xl text-emerald-400"></i>
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">ยอดเงินที่ต้องเก็บสุทธิ</p>
                    <p class="text-xl font-black">฿<?= number_format($job['net_price'], 2); ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden">
            <div class="p-4 bg-slate-50 border-b border-slate-100">
                <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">รายการอาหารที่สั่ง</h4>
            </div>
            <div class="p-6">
                <ul class="space-y-4">
                    <?php foreach ($items as $item): ?>
                    <li class="flex justify-between items-center text-sm">
                        <span class="text-slate-600 font-medium">
                            <span class="font-bold text-emerald-500 mr-2"><?= $item['quantity']; ?>x</span> 
                            <?= htmlspecialchars($item['food_name']); ?>
                        </span>
                        <span class="font-bold text-slate-400">฿<?= number_format($item['price'] * $item['quantity'], 2); ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <div class="mt-6 pt-6 border-t border-dashed border-slate-100 flex justify-between items-center">
                    <span class="text-xs font-bold text-slate-400 uppercase">ยอดรวมทั้งหมด</span>
                    <span class="text-2xl font-black text-emerald-600">฿<?= number_format($job['net_price'], 2); ?></span>
                </div>
            </div>
        </div>

    </main>

    <div class="fixed bottom-0 left-0 w-full bg-white/80 backdrop-blur-lg border-t border-slate-100 p-4 z-40">
        <div class="max-w-md mx-auto">
            <?php if ($job['status'] == 'delivering'): ?>
                <form action="complete_job.php" method="GET" onsubmit="return confirm('ยืนยันว่าจัดส่งถึงมือลูกค้าเรียบร้อยแล้ว?')">
                    <input type="hidden" name="order_id" value="<?= $order_id ?>">
                    <button type="submit" class="w-full bg-emerald-500 text-white py-4 rounded-2xl font-black text-lg shadow-xl shadow-emerald-100 hover:bg-emerald-600 transition flex items-center justify-center gap-3 active:scale-95">
                        <i class="bi bi-check2-all text-2xl"></i> ส่งอาหารสำเร็จแล้ว [3.4.9]
                    </button>
                </form>
            <?php else: ?>
                <div class="w-full bg-slate-100 text-slate-400 py-4 rounded-2xl text-center font-bold">
                    งานนี้เสร็จสิ้นแล้ว
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>