<?php
// ไฟล์: restaurant/orders.php
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
    // 2. ดึง restaurant_id ของเจ้าของร้านนี้
    $stmt_res = $conn->prepare("SELECT restaurant_id FROM restaurants WHERE user_id = :uid");
    $stmt_res->execute([':uid' => $user_id]);
    $restaurant = $stmt_res->fetch(PDO::FETCH_ASSOC);

    if (!$restaurant) {
        header("Location: profile.php?msg=setup_first");
        exit();
    }
    $restaurant_id = $restaurant['restaurant_id'];

    // 3. จัดการการอัปเดตสถานะออเดอร์ (Update Status - ข้อ 3.2.14)
    if (isset($_GET['action']) && isset($_GET['order_id'])) {
        $order_id = $_GET['order_id'];
        $action = $_GET['action'];
        $new_status = '';

        if ($action == 'accept') $new_status = 'preparing';
        elseif ($action == 'finish') $new_status = 'delivering'; // ส่งต่อให้ Rider หรือพร้อมส่ง
        elseif ($action == 'cancel') $new_status = 'cancelled';

        if (!empty($new_status)) {
            $stmt_up = $conn->prepare("UPDATE orders SET status = :s WHERE order_id = :oid AND restaurant_id = :rid");
            $stmt_up->execute([':s' => $new_status, ':oid' => $order_id, ':rid' => $restaurant_id]);
            $message = "<div class='bg-emerald-500 text-white p-4 rounded-2xl mb-6 shadow-lg'><i class='bi bi-check-circle-fill mr-2'></i>อัปเดตสถานะออเดอร์ #$order_id เรียบร้อยแล้ว</div>";
        }
    }

    // 4. ดึงข้อมูลออเดอร์ทั้งหมดของร้าน (เรียงจากใหม่ไปเก่า)
    $stmt_orders = $conn->prepare("
        SELECT o.*, u.first_name, u.last_name, u.phone 
        FROM orders o
        JOIN users u ON o.customer_id = u.user_id
        WHERE o.restaurant_id = :rid
        ORDER BY o.order_date DESC
    ");
    $stmt_orders->execute([':rid' => $restaurant_id]);
    $orders = $stmt_orders->fetchAll(PDO::FETCH_ASSOC);

    // 5. ดึงรายการอาหารย่อยของทุกลูกค้า (สำหรับแสดงในตาราง)
    $stmt_details = $conn->prepare("
        SELECT od.order_id, od.quantity, od.price, f.food_name 
        FROM order_details od
        JOIN foods f ON od.food_id = f.food_id
        JOIN orders o ON od.order_id = o.order_id
        WHERE o.restaurant_id = :rid
    ");
    $stmt_details->execute([':rid' => $restaurant_id]);
    $details_flat = $stmt_details->fetchAll(PDO::FETCH_ASSOC);

    $order_items = [];
    foreach ($details_flat as $d) {
        $order_items[$d['order_id']][] = $d;
    }

} catch(PDOException $e) { die("Error: " . $e->getMessage()); }

// ฟังก์ชันแสดงสี Badge ตามสถานะ
function getStatusStyle($status) {
    switch ($status) {
        case 'pending': return 'bg-amber-100 text-amber-600 border-amber-200';
        case 'preparing': return 'bg-blue-100 text-blue-600 border-blue-200';
        case 'delivering': return 'bg-purple-100 text-purple-600 border-purple-200';
        case 'completed': return 'bg-emerald-100 text-emerald-600 border-emerald-200';
        case 'cancelled': return 'bg-rose-100 text-rose-600 border-rose-200';
        default: return 'bg-slate-100 text-slate-600 border-slate-200';
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders | ShopManager</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['Prompt', 'sans-serif'] }, colors: { brand: { orange: '#ff5c00', light: '#fff7f3' } } } } }
    </script>
    <style>
        .nav-link { display: flex; align-items: center; padding: 0.85rem 1.5rem; color: #64748b; font-size: 0.9rem; font-weight: 500; border-left: 4px solid transparent; transition: 0.2s; }
        .nav-link:hover, .nav-link.active { color: #ff5c00; background-color: #fff7f3; border-left-color: #ff5c00; }
        .order-card { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .order-card:hover { transform: translateY(-4px); box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1); }
    </style>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800">

    <aside class="w-64 bg-white shadow-2xl z-20 flex-shrink-0 flex flex-col">
        <div class="h-16 flex items-center px-6 bg-brand-orange text-white"><i class="bi bi-shop-window text-xl me-2"></i><span class="font-bold text-lg tracking-wide">ShopManager</span></div>
        <div class="flex-1 py-6 overflow-y-auto">
            <a href="index.php" class="nav-link"><i class="bi bi-speedometer2 mr-3"></i> แดชบอร์ด</a>
            <a href="orders.php" class="nav-link active"><i class="bi bi-cart-check mr-3"></i> รายการสั่งอาหาร [3.2.14]</a>
            <p class="px-6 text-xs font-bold text-slate-400 uppercase tracking-widest mb-4 mt-8">จัดการร้าน</p>
            <a href="food_category.php" class="nav-link"><i class="bi bi-tags mr-3"></i> หมวดหมู่อาหาร</a>
            <a href="menu.php" class="nav-link"><i class="bi bi-egg-fried mr-3"></i> จัดการรายการอาหาร</a>
            <p class="px-6 text-xs font-bold text-slate-400 uppercase tracking-widest mb-4 mt-8">อื่นๆ</p>
            <a href="profile.php" class="nav-link"><i class="bi bi-person-gear mr-3"></i> โปรไฟล์ร้าน</a>
            <a href="../logout.php" class="nav-link text-rose-500 mt-4"><i class="bi bi-box-arrow-right mr-3"></i> ออกจากระบบ</a>
        </div>
    </aside>

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-8 z-10 font-bold text-slate-700">
            <div class="flex items-center gap-2"><i class="bi bi-receipt text-brand-orange"></i> <span>รายการสั่งซื้อจากลูกค้า</span></div>
            <div class="text-sm text-slate-400 hidden sm:block">จัดการคำสั่งซื้อและสถานะการจัดส่ง</div>
        </header>

        <main class="flex-1 overflow-y-auto p-8">
            <div class="max-w-6xl mx-auto">
                
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-slate-800 text-center sm:text-left">ออเดอร์ปัจจุบัน</h2>
                    <p class="text-slate-500 text-sm text-center sm:text-left">ตรวจสอบคำสั่งซื้อใหม่และเตรียมความอร่อยส่งถึงมือลูกค้า</p>
                </div>

                <?= $message; ?>

                <?php if (empty($orders)): ?>
                    <div class="bg-white rounded-3xl p-20 text-center border-2 border-dashed border-slate-200">
                        <i class="bi bi-inbox text-6xl text-slate-200"></i>
                        <p class="text-slate-400 mt-4 font-medium">ยังไม่มีรายการสั่งซื้อเข้ามาในขณะนี้</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 gap-6">
                        <?php foreach ($orders as $ord): ?>
                            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden order-card">
                                <div class="bg-slate-50/50 px-8 py-4 border-b border-slate-100 flex flex-wrap justify-between items-center gap-4">
                                    <div class="flex items-center gap-4">
                                        <div class="bg-white px-3 py-1 rounded-xl border border-slate-200 font-bold text-slate-700 text-sm">
                                            #<?= str_pad($ord['order_id'], 5, '0', STR_PAD_LEFT); ?>
                                        </div>
                                        <div class="text-xs text-slate-400 font-medium">
                                            <i class="bi bi-clock mr-1"></i> <?= date('d M Y, H:i', strtotime($ord['order_date'])); ?>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="px-4 py-1.5 rounded-full text-[10px] font-bold uppercase border <?= getStatusStyle($ord['status']); ?>">
                                            <?= $ord['status']; ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="p-8 grid grid-cols-1 lg:grid-cols-3 gap-8">
                                    <div class="lg:col-span-1 border-r border-slate-50 pr-4">
                                        <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">ลูกค้า</h4>
                                        <p class="font-bold text-slate-800 text-lg"><?= htmlspecialchars($ord['first_name'] . ' ' . $ord['last_name']); ?></p>
                                        <p class="text-brand-orange font-bold mt-1 flex items-center gap-2"><i class="bi bi-telephone-fill"></i> <?= htmlspecialchars($ord['phone']); ?></p>
                                    </div>

                                    <div class="lg:col-span-1">
                                        <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">รายการอาหาร</h4>
                                        <ul class="space-y-2">
                                            <?php 
                                                $items = $order_items[$ord['order_id']] ?? [];
                                                foreach ($items as $item):
                                            ?>
                                                <li class="text-sm text-slate-600 flex justify-between">
                                                    <span><strong class="text-slate-800"><?= $item['quantity']; ?>x</strong> <?= htmlspecialchars($item['food_name']); ?></span>
                                                    <span class="text-slate-400 font-medium">฿<?= number_format($item['price'] * $item['quantity'], 2); ?></span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                        <div class="mt-4 pt-4 border-t border-slate-50 flex justify-between items-center">
                                            <span class="text-xs font-bold text-slate-400">ราคาสุทธิ</span>
                                            <span class="text-xl font-bold text-brand-orange">฿<?= number_format($ord['net_price'], 2); ?></span>
                                        </div>
                                    </div>

                                    <div class="lg:col-span-1 flex flex-col justify-center gap-3">
                                        <?php if ($ord['status'] == 'pending'): ?>
                                            <a href="?action=accept&order_id=<?= $ord['order_id']; ?>" class="w-full bg-slate-900 text-white text-center py-3 rounded-2xl font-bold hover:bg-slate-800 transition shadow-lg shadow-slate-200">
                                                <i class="bi bi-check2-circle mr-2"></i> รับออเดอร์
                                            </a>
                                            <a href="?action=cancel&order_id=<?= $ord['order_id']; ?>" class="w-full text-rose-500 text-center py-2 text-sm font-bold hover:underline" onclick="return confirm('ยืนยันการปฏิเสธออเดอร์นี้?')">ปฏิเสธ</a>
                                        <?php elseif ($ord['status'] == 'preparing'): ?>
                                            <a href="?action=finish&order_id=<?= $ord['order_id']; ?>" class="w-full bg-emerald-500 text-white text-center py-3 rounded-2xl font-bold hover:bg-emerald-600 transition shadow-lg shadow-emerald-100">
                                                <i class="bi bi-fire mr-2"></i> ทำอาหารเสร็จแล้ว
                                            </a>
                                        <?php else: ?>
                                            <div class="text-center p-4 bg-slate-50 rounded-2xl border border-slate-100">
                                                <p class="text-xs font-bold text-slate-400 uppercase">ดำเนินการโดย</p>
                                                <p class="text-sm font-bold text-slate-600 mt-1 italic">Rider / Completed</p>
                                            </div>
                                        <?php endif; ?>
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