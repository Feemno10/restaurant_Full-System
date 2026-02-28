<?php
// ไฟล์: restaurant/incoming_orders.php
session_start();

// 1. ตรวจสอบสิทธิ์ (Security)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'restaurant') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';
$user_id = $_SESSION['user_id'];
$message = '';

try {
    // 2. ดึงข้อมูลร้านอาหาร
    $stmt_res = $conn->prepare("SELECT restaurant_id, restaurant_name FROM restaurants WHERE user_id = :uid");
    $stmt_res->execute([':uid' => $user_id]);
    $restaurant = $stmt_res->fetch(PDO::FETCH_ASSOC);
    $res_id = $restaurant['restaurant_id'] ?? 0;

    // 3. ดึงออเดอร์ใหม่ที่สถานะเป็น 'pending' เท่านั้น (ข้อ 3.2.14)
    $stmt_incoming = $conn->prepare("
        SELECT o.*, u.first_name, u.last_name, u.phone 
        FROM orders o
        JOIN users u ON o.customer_id = u.user_id
        WHERE o.restaurant_id = :rid AND o.status = 'pending'
        ORDER BY o.order_date ASC
    ");
    $stmt_incoming->execute([':rid' => $res_id]);
    $incoming_orders = $stmt_incoming->fetchAll(PDO::FETCH_ASSOC);

    // 4. ดึงรายการอาหารของออเดอร์ที่ค้างอยู่
    $stmt_items = $conn->prepare("
        SELECT od.*, f.food_name 
        FROM order_details od
        JOIN foods f ON od.food_id = f.food_id
        JOIN orders o ON od.order_id = o.order_id
        WHERE o.restaurant_id = :rid AND o.status = 'pending'
    ");
    $stmt_items->execute([':rid' => $res_id]);
    $details = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
    
    $order_details = [];
    foreach ($details as $d) {
        $order_details[$d['order_id']][] = $d;
    }

} catch(PDOException $e) { die("Error: " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incoming Orders | ShopManager</title>
    <meta http-equiv="refresh" content="30">
    
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['Prompt', 'sans-serif'] }, colors: { brand: { orange: '#ff5c00' } } } } }
    </script>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 font-sans">

    <aside class="w-64 bg-white shadow-2xl z-20 flex-shrink-0 flex flex-col">
        <div class="h-16 flex items-center px-6 bg-brand-orange text-white">
            <i class="bi bi-bell-fill text-xl me-2 animate-pulse"></i>
            <span class="font-bold text-lg">Incoming</span>
        </div>
        <div class="flex-1 py-6 overflow-y-auto">
            <a href="index.php" class="flex items-center px-6 py-3 text-slate-500 hover:text-brand-orange transition-colors"><i class="bi bi-speedometer2 mr-3"></i> Dashboard</a>
            <a href="incoming_orders.php" class="flex items-center px-6 py-3 text-brand-orange bg-orange-50 border-l-4 border-brand-orange font-bold"><i class="bi bi-lightning-charge mr-3"></i> ออเดอร์เข้าใหม่</a>
            <a href="orders.php" class="flex items-center px-6 py-3 text-slate-500 hover:text-brand-orange transition-colors"><i class="bi bi-cart-check mr-3"></i> ประวัติการสั่งซื้อ</a>
            <div class="mt-8 px-6 text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Management</div>
            <a href="menu.php" class="flex items-center px-6 py-3 text-slate-500 hover:text-brand-orange transition-colors"><i class="bi bi-egg-fried mr-3"></i> จัดการรายการอาหาร</a>
            <a href="../logout.php" class="flex items-center px-6 py-3 text-rose-500 mt-4"><i class="bi bi-box-arrow-right mr-3"></i> ออกจากระบบ</a>
        </div>
    </aside>

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-8 z-10">
            <h1 class="font-bold text-slate-700">ออเดอร์ใหม่ที่รอยืนยัน</h1>
            <div class="flex items-center gap-3">
                <span class="text-xs font-bold text-slate-400">อัปเดตล่าสุด: <?= date('H:i:s'); ?></span>
                <div class="h-6 w-px bg-slate-200"></div>
                <span class="text-sm font-bold text-brand-orange"><?= htmlspecialchars($restaurant['restaurant_name']); ?></span>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-8">
            <div class="max-w-5xl mx-auto">
                
                <div class="mb-8 flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div>
                        <h2 class="text-3xl font-black text-slate-900">Incoming Feed</h2>
                        <p class="text-slate-400 mt-1">ออเดอร์ที่ลูกค้าเพิ่งสั่งเข้ามา รอยืนยันเพื่อเริ่มทำอาหารครับ</p>
                    </div>
                    <div class="bg-brand-orange text-white px-6 py-3 rounded-2xl shadow-lg shadow-orange-100 flex items-center gap-3">
                        <i class="bi bi-inboxes-fill text-2xl"></i>
                        <div>
                            <p class="text-[10px] font-bold uppercase opacity-80">รอยืนยัน</p>
                            <p class="text-xl font-black"><?= count($incoming_orders); ?> รายการ</p>
                        </div>
                    </div>
                </div>

                <?php if (empty($incoming_orders)): ?>
                    <div class="bg-white rounded-[2.5rem] p-20 text-center border-2 border-dashed border-slate-200">
                        <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6 text-slate-200">
                            <i class="bi bi-cup-hot text-4xl"></i>
                        </div>
                        <p class="text-slate-400 font-bold text-lg">ตอนนี้ยังไม่มีออเดอร์ใหม่เข้ามาครับ</p>
                        <p class="text-slate-300 text-sm mt-1 font-medium">หน้านี้จะรีเฟรชตัวเองโดยอัตโนมัติเมื่อมีคำสั่งซื้อใหม่</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 gap-6">
                        <?php foreach ($incoming_orders as $ord): ?>
                            <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden hover:shadow-xl transition-all duration-300">
                                <div class="p-8 flex flex-col lg:flex-row gap-8">
                                    
                                    <div class="w-full lg:w-1/4 border-r border-slate-50 pr-4">
                                        <div class="bg-slate-900 text-white inline-block px-4 py-1 rounded-xl text-xs font-black mb-4">#<?= str_pad($ord['order_id'], 5, '0', STR_PAD_LEFT); ?></div>
                                        <h4 class="text-[10px] font-black text-slate-300 uppercase tracking-widest mb-1">สั่งซื้อเมื่อ</h4>
                                        <p class="text-sm font-bold text-slate-600 mb-4"><?= date('H:i', strtotime($ord['order_date'])); ?> น.</p>
                                        
                                        <h4 class="text-[10px] font-black text-slate-300 uppercase tracking-widest mb-1">ลูกค้า</h4>
                                        <p class="font-bold text-slate-800"><?= htmlspecialchars($ord['first_name'].' '.$ord['last_name']); ?></p>
                                        <p class="text-sm text-brand-orange font-bold"><i class="bi bi-telephone"></i> <?= htmlspecialchars($ord['phone']); ?></p>
                                    </div>

                                    <div class="w-full lg:w-2/4">
                                        <h4 class="text-[10px] font-black text-slate-300 uppercase tracking-widest mb-4">รายการอาหารที่สั่ง</h4>
                                        <div class="space-y-3">
                                            <?php foreach ($order_details[$ord['order_id']] as $item): ?>
                                                <div class="flex justify-between items-center bg-slate-50 p-3 rounded-2xl">
                                                    <span class="text-sm font-bold text-slate-700">
                                                        <span class="text-brand-orange mr-2"><?= $item['quantity']; ?>x</span> 
                                                        <?= htmlspecialchars($item['food_name']); ?>
                                                    </span>
                                                    <span class="text-xs font-bold text-slate-400">฿<?= number_format($item['price'] * $item['quantity'], 2); ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="mt-4 flex justify-between items-center px-2">
                                            <span class="text-xs font-bold text-slate-400 italic">รวมหลังหักส่วนลด <?= $ord['discount_percent']; ?>%</span>
                                            <span class="text-2xl font-black text-brand-orange">฿<?= number_format($ord['net_price'], 2); ?></span>
                                        </div>
                                    </div>

                                    <div class="w-full lg:w-1/4 flex flex-col justify-center gap-3">
                                        <a href="orders.php?action=accept&order_id=<?= $ord['order_id']; ?>" 
                                           class="bg-brand-orange text-white py-4 rounded-2xl font-black shadow-lg shadow-orange-100 hover:bg-orange-600 transition text-center flex items-center justify-center gap-2">
                                            <i class="bi bi-check-circle-fill text-xl"></i> รับออเดอร์
                                        </a>
                                        <a href="orders.php?action=cancel&order_id=<?= $ord['order_id']; ?>" 
                                           class="text-slate-400 font-bold text-xs hover:text-rose-500 transition text-center" 
                                           onclick="return confirm('ยืนยันการปฏิเสธออเดอร์นี้?')">ปฏิเสธรายการนี้</a>
                                    </div>

                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            </div>
        </main>
    </div>

</body>
</html>