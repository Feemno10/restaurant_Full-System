<?php
// ไฟล์: restaurant/index.php
session_start();

// 1. ตรวจสอบสิทธิ์ (Security) - ต้องล็อกอินและเป็น 'restaurant' เท่านั้น
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'restaurant') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';
$user_id = $_SESSION['user_id'];

try {
    // 2. ดึงข้อมูลร้านอาหารของผู้ใช้นี้
    $stmt_res = $conn->prepare("SELECT * FROM restaurants WHERE user_id = :uid");
    $stmt_res->execute([':uid' => $user_id]);
    $my_restaurant = $stmt_res->fetch(PDO::FETCH_ASSOC);

    // หากยังไม่มีข้อมูลร้านอาหาร (กรณีแอดมินเพิ่งสร้าง User ให้แต่ยังไม่ได้ลงทะเบียนร้าน)
    if (!$my_restaurant) {
        $res_name = "ยังไม่ได้ระบุชื่อร้าน";
        $res_id = 0;
    } else {
        $res_name = $my_restaurant['restaurant_name'];
        $res_id = $my_restaurant['restaurant_id'];
    }

    // 3. ดึงสถิติสำหรับ Dashboard (ข้อ 3.2.16)
    
    // 3.1 ออเดอร์ที่รอการยืนยัน/กำลังทำ (Pending/Preparing)
    $stmt_new_orders = $conn->prepare("SELECT COUNT(*) FROM orders WHERE restaurant_id = :rid AND status IN ('pending', 'preparing')");
    $stmt_new_orders->execute([':rid' => $res_id]);
    $new_orders_count = $stmt_new_orders->fetchColumn();

    // 3.2 จำนวนเมนูอาหารทั้งหมดที่มี (ข้อ 3.2.8)
    $stmt_foods = $conn->prepare("SELECT COUNT(*) FROM foods WHERE restaurant_id = :rid");
    $stmt_foods->execute([':rid' => $res_id]);
    $foods_count = $stmt_foods->fetchColumn();

    // 3.3 ยอดขายรวม (Completed)
    $stmt_sales = $conn->prepare("SELECT SUM(net_price) FROM orders WHERE restaurant_id = :rid AND status = 'completed'");
    $stmt_sales->execute([':rid' => $res_id]);
    $total_sales = $stmt_sales->fetchColumn() ?? 0;

    // 4. ดึงออเดอร์ล่าสุด 5 รายการ (ข้อ 3.2.14)
    $stmt_recent = $conn->prepare("
        SELECT o.*, u.first_name, u.last_name 
        FROM orders o 
        JOIN users u ON o.customer_id = u.user_id 
        WHERE o.restaurant_id = :rid 
        ORDER BY o.order_date DESC LIMIT 5
    ");
    $stmt_recent->execute([':rid' => $res_id]);
    $recent_orders = $stmt_recent->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Dashboard | FoodDelivery</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Prompt', 'sans-serif'] },
                    colors: { brand: { orange: '#ff5c00', light: '#fff7f3' } }
                }
            }
        }
    </script>
    <style>
        .nav-link { display: flex; align-items: center; padding: 0.85rem 1.5rem; color: #64748b; font-size: 0.9rem; font-weight: 500; border-left: 4px solid transparent; transition: 0.2s; }
        .nav-link:hover, .nav-link.active { color: #ff5c00; background-color: #fff7f3; border-left-color: #ff5c00; }
        .stat-card { transition: transform 0.3s; }
        .stat-card:hover { transform: translateY(-5px); }
    </style>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800">

    <aside class="w-64 bg-white shadow-2xl z-20 flex-shrink-0 flex flex-col">
        <div class="h-16 flex items-center px-6 bg-brand-orange text-white">
            <i class="bi bi-shop-window text-xl me-2"></i>
            <span class="font-bold text-lg tracking-wide">ShopManager</span>
        </div>
        <div class="flex-1 py-6 overflow-y-auto">
            <p class="px-6 text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">เมนูหลัก</p>
            <a href="index.php" class="nav-link active"><i class="bi bi-speedometer2 mr-3"></i> แดชบอร์ด</a>
            <a href="orders.php" class="nav-link"><i class="bi bi-cart-check mr-3"></i> รายการสั่งอาหาร [3.2.14]</a>
            
            <p class="px-6 text-xs font-bold text-slate-400 uppercase tracking-widest mb-4 mt-8">จัดการร้าน</p>
            <a href="food_category.php" class="nav-link"><i class="bi bi-tags mr-3"></i> หมวดหมู่อาหาร [3.2.7]</a>
            <a href="menu.php" class="nav-link"><i class="bi bi-egg-fried mr-3"></i> จัดการรายการอาหาร [3.2.8]</a>
            <a href="discount.php" class="nav-link"><i class="bi bi-percent mr-3"></i> ตั้งค่าส่วนลด [3.2.12]</a>
            
            <p class="px-6 text-xs font-bold text-slate-400 uppercase tracking-widest mb-4 mt-8">อื่นๆ</p>
            <a href="sales_report.php" class="nav-link"><i class="bi bi-graph-up-arrow mr-3"></i> สรุปยอดขาย [3.2.16]</a>
            <a href="profile.php" class="nav-link"><i class="bi bi-person-gear mr-3"></i> โปรไฟล์ร้าน [3.2.4]</a>
            <a href="../logout.php" class="nav-link text-rose-500 mt-4"><i class="bi bi-box-arrow-right mr-3"></i> ออกจากระบบ</a>
        </div>
    </aside>

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-8 z-10">
            <div class="flex items-center gap-4">
                <span class="text-slate-400"><i class="bi bi-house-door"></i></span>
                <i class="bi bi-chevron-right text-[10px] text-slate-300"></i>
                <span class="text-sm font-bold text-slate-700">หน้าหลัก</span>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-right">
                    <p class="text-sm font-bold text-slate-700 leading-none"><?= htmlspecialchars($res_name); ?></p>
                    <p class="text-[11px] text-brand-orange font-bold uppercase mt-1">เจ้าของร้านอาหาร</p>
                </div>
                <img src="../assets/uploads/profiles/<?= $_SESSION['profile_img'] ?? 'default.png' ?>" class="w-10 h-10 rounded-xl object-cover border-2 border-brand-light shadow-sm" onerror="this.src='https://ui-avatars.com/api/?name=Shop&background=ff5c00&color=fff'">
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-8">
            <div class="mb-8 flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">ยินดีต้อนรับกลับมา! 👋</h1>
                    <p class="text-slate-500 text-sm">นี่คือสรุปความเคลื่อนไหวของร้านคุณในวันนี้</p>
                </div>
                <div class="flex gap-2">
                    <a href="menu.php" class="bg-white text-slate-700 px-4 py-2 rounded-xl text-sm font-bold shadow-sm border border-slate-200 hover:bg-slate-50 transition">จัดการเมนู</a>
                    <a href="orders.php" class="bg-brand-orange text-white px-4 py-2 rounded-xl text-sm font-bold shadow-lg shadow-orange-100 hover:bg-orange-600 transition">ดูคำสั่งซื้อใหม่</a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 flex items-center gap-5 stat-card">
                    <div class="w-14 h-14 bg-orange-100 text-brand-orange rounded-2xl flex items-center justify-center text-2xl shadow-inner"><i class="bi bi-receipt"></i></div>
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase">ออเดอร์ใหม่วันนี้</p>
                        <h3 class="text-2xl font-bold text-slate-800"><?= number_format($new_orders_count); ?> <span class="text-sm font-normal text-slate-400">รายการ</span></h3>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 flex items-center gap-5 stat-card">
                    <div class="w-14 h-14 bg-indigo-100 text-indigo-600 rounded-2xl flex items-center justify-center text-2xl shadow-inner"><i class="bi bi-egg-fried"></i></div>
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase">เมนูอาหารทั้งหมด</p>
                        <h3 class="text-2xl font-bold text-slate-800"><?= number_format($foods_count); ?> <span class="text-sm font-normal text-slate-400">เมนู</span></h3>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 flex items-center gap-5 stat-card">
                    <div class="w-14 h-14 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center text-2xl shadow-inner"><i class="bi bi-wallet2"></i></div>
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase">รายได้รวมที่สำเร็จ</p>
                        <h3 class="text-2xl font-bold text-emerald-600">฿<?= number_format($total_sales, 2); ?></h3>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-6 border-b border-slate-50 flex justify-between items-center">
                    <h3 class="font-bold text-slate-800 text-lg"><i class="bi bi-clock-history text-brand-orange mr-2"></i>ออเดอร์ล่าสุด</h3>
                    <a href="orders.php" class="text-brand-orange text-xs font-bold hover:underline">ดูทั้งหมด</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                            <tr>
                                <th class="px-8 py-4">รหัสออเดอร์</th>
                                <th class="px-8 py-4">ลูกค้า</th>
                                <th class="px-8 py-4 text-center">ยอดสุทธิ</th>
                                <th class="px-8 py-4">สถานะ</th>
                                <th class="px-8 py-4 text-right">ดำเนินการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-sm">
                            <?php foreach($recent_orders as $ord): ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-8 py-4 font-bold text-slate-700">#<?= str_pad($ord['order_id'], 5, '0', STR_PAD_LEFT); ?></td>
                                <td class="px-8 py-4"><?= htmlspecialchars($ord['first_name'] . ' ' . $ord['last_name']); ?></td>
                                <td class="px-8 py-4 text-center font-bold">฿<?= number_format($ord['net_price'], 2); ?></td>
                                <td class="px-8 py-4">
                                    <?php
                                        $s = $ord['status'];
                                        $color = "bg-slate-100 text-slate-600";
                                        if($s == 'pending') $color = "bg-amber-100 text-amber-600";
                                        elseif($s == 'preparing') $color = "bg-blue-100 text-blue-600";
                                        elseif($s == 'completed') $color = "bg-emerald-100 text-emerald-600";
                                    ?>
                                    <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase <?= $color; ?>"><?= $s; ?></span>
                                </td>
                                <td class="px-8 py-4 text-right">
                                    <a href="orders.php?id=<?= $ord['order_id']; ?>" class="text-brand-orange hover:text-orange-700 transition"><i class="bi bi-eye-fill"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($recent_orders)): ?>
                                <tr><td colspan="5" class="px-8 py-10 text-center text-slate-400 italic">ยังไม่มีรายการสั่งซื้อเข้ามาในขณะนี้</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>