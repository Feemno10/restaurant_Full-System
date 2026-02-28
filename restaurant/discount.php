<?php
// ไฟล์: restaurant/discount.php
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
    // 2. ดึงข้อมูลร้านอาหารของผู้ใช้รายนี้
    $stmt_res = $conn->prepare("SELECT restaurant_id, restaurant_name, discount_percent FROM restaurants WHERE user_id = :uid");
    $stmt_res->execute([':uid' => $user_id]);
    $restaurant = $stmt_res->fetch(PDO::FETCH_ASSOC);

    if (!$restaurant) {
        header("Location: profile.php?msg=setup_first");
        exit();
    }
    $restaurant_id = $restaurant['restaurant_id'];

    // 3. จัดการการบันทึกส่วนลด (Update Logic - ข้อ 3.2.12)
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_discount'])) {
        $discount = (int)$_POST['discount_percent'];

        // ตรวจสอบความถูกต้อง (ต้องอยู่ระหว่าง 0-100)
        if ($discount >= 0 && $discount <= 100) {
            $stmt_up = $conn->prepare("UPDATE restaurants SET discount_percent = :d WHERE restaurant_id = :rid");
            $stmt_up->execute([':d' => $discount, ':rid' => $restaurant_id]);
            
            $message = "<div class='bg-emerald-500 text-white p-4 rounded-2xl mb-6 shadow-lg shadow-emerald-100 flex items-center gap-3 animate-bounce-short'>
                            <i class='bi bi-check-circle-fill fs-4'></i>
                            <div>
                                <p class='font-bold leading-tight'>ตั้งค่าส่วนลดสำเร็จ!</p>
                                <p class='text-xs opacity-90'>ระบบจะเริ่มใช้ส่วนลด $discount% กับออเดอร์ใหม่ทันที</p>
                            </div>
                        </div>";
            
            // รีเฟรชข้อมูลในตัวแปร
            $restaurant['discount_percent'] = $discount;
        } else {
            $message = "<div class='bg-rose-500 text-white p-4 rounded-2xl mb-6'>กรุณาระบุตัวเลขระหว่าง 0 ถึง 100 ครับ</div>";
        }
    }

} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Discount | ShopManager</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['Prompt', 'sans-serif'] }, colors: { brand: { orange: '#ff5c00', light: '#fff7f3' } } } } }
    </script>
    <style>
        .nav-link { display: flex; align-items: center; padding: 0.85rem 1.5rem; color: #64748b; font-size: 0.9rem; font-weight: 500; border-left: 4px solid transparent; transition: 0.2s; }
        .nav-link:hover, .nav-link.active { color: #ff5c00; background-color: #fff7f3; border-left-color: #ff5c00; }
        @keyframes bounce-short { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-4px); } }
        .animate-bounce-short { animation: bounce-short 0.5s ease-in-out 1; }
    </style>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800">

    <aside class="w-64 bg-white shadow-2xl z-20 flex-shrink-0 flex flex-col">
        <div class="h-16 flex items-center px-6 bg-brand-orange text-white">
            <i class="bi bi-shop-window text-xl me-2"></i>
            <span class="font-bold text-lg tracking-wide">ShopManager</span>
        </div>
        <div class="flex-1 py-6 overflow-y-auto">
            <a href="index.php" class="nav-link"><i class="bi bi-speedometer2 mr-3"></i> แดชบอร์ด</a>
            <a href="orders.php" class="nav-link"><i class="bi bi-cart-check mr-3"></i> รายการสั่งอาหาร</a>
            <p class="px-6 text-xs font-bold text-slate-400 uppercase tracking-widest mb-4 mt-8">จัดการร้าน</p>
            <a href="food_category.php" class="nav-link"><i class="bi bi-tags mr-3"></i> หมวดหมู่อาหาร</a>
            <a href="menu.php" class="nav-link"><i class="bi bi-egg-fried mr-3"></i> จัดการรายการอาหาร</a>
            <a href="discount.php" class="nav-link active"><i class="bi bi-percent mr-3"></i> ตั้งค่าส่วนลด [3.2.12]</a>
            <p class="px-6 text-xs font-bold text-slate-400 uppercase tracking-widest mb-4 mt-8">อื่นๆ</p>
            <a href="profile.php" class="nav-link"><i class="bi bi-person-gear mr-3"></i> โปรไฟล์ร้าน</a>
            <a href="../logout.php" class="nav-link text-rose-500 mt-4"><i class="bi bi-box-arrow-right mr-3"></i> ออกจากระบบ</a>
        </div>
    </aside>

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-8 z-10">
            <h1 class="font-bold text-slate-700 uppercase text-sm tracking-widest"><i class="bi bi-megaphone text-brand-orange mr-2"></i>Promotion Management</h1>
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-brand-orange text-white flex items-center justify-center font-bold text-xs">RM</div>
                <span class="text-sm font-bold text-slate-700 hidden md:block"><?= htmlspecialchars($restaurant['restaurant_name']); ?></span>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-8">
            <div class="max-w-2xl mx-auto">
                
                <div class="mb-10 text-center sm:text-left">
                    <h2 class="text-3xl font-bold text-slate-900 tracking-tight">ตั้งค่าส่วนลดค่าอาหาร</h2>
                    <p class="text-slate-500 mt-2">ดึงดูดลูกค้าด้วยโปรโมชั่นส่วนลดพิเศษสำหรับร้านของคุณ</p>
                </div>

                <?= $message; ?>

                <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 p-8 md:p-12 relative overflow-hidden">
                    <div class="absolute -top-10 -right-10 w-40 h-40 bg-orange-50 rounded-full opacity-50"></div>
                    <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-indigo-50 rounded-full opacity-50"></div>

                    <form action="discount.php" method="POST" class="relative z-10">
                        <div class="text-center mb-10">
                            <div class="inline-flex items-center justify-center w-20 h-20 bg-brand-light text-brand-orange rounded-3xl mb-6 shadow-inner">
                                <i class="bi bi-gift-fill text-4xl"></i>
                            </div>
                            <h3 class="text-xl font-bold text-slate-800">แคมเปญส่วนลดปัจจุบัน</h3>
                            <p class="text-slate-400 text-sm">ระบุเปอร์เซ็นต์ส่วนลดที่คุณต้องการมอบให้ลูกค้า</p>
                        </div>

                        <div class="bg-slate-50 p-6 rounded-3xl mb-8 border border-slate-100">
                            <label class="block text-center text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">เปอร์เซ็นต์ส่วนลดที่กำหนด</label>
                            <div class="flex items-center justify-center gap-4">
                                <input type="number" 
                                       name="discount_percent" 
                                       value="<?= htmlspecialchars($restaurant['discount_percent'] ?? 0); ?>" 
                                       min="0" max="100" 
                                       class="w-32 text-center text-5xl font-black text-brand-orange bg-transparent border-b-4 border-brand-orange focus:outline-none py-2"
                                       required>
                                <span class="text-4xl font-bold text-slate-300">%</span>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <button type="submit" name="save_discount" class="w-full bg-slate-900 text-white font-bold py-5 rounded-2xl hover:bg-slate-800 transition shadow-xl shadow-slate-200">
                                <i class="bi bi-save2 mr-2"></i> บันทึกและเริ่มใช้โปรโมชั่น
                            </button>
                            
                            <div class="p-4 bg-orange-50 rounded-2xl border border-orange-100 flex gap-3">
                                <i class="bi bi-info-circle-fill text-brand-orange"></i>
                                <p class="text-[11px] text-orange-800 font-medium leading-relaxed">
                                    <strong>หมายเหตุ:</strong> ส่วนลดนี้จะถูกนำไปหักลบจากราคารวมของอาหารทั้งหมดในตะกร้าของลูกค้าก่อนรวมยอดสุทธิ
                                </p>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="mt-8 bg-gradient-to-br from-brand-orange to-rose-500 rounded-[2rem] p-8 text-white shadow-xl shadow-orange-100 flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-widest opacity-80">ตัวอย่างโปรโมชั่นหน้าแอป</p>
                        <h4 class="text-2xl font-bold mt-1">ลดทันทีทั้งร้าน <?= htmlspecialchars($restaurant['discount_percent'] ?? 0); ?>%</h4>
                        <p class="text-xs opacity-80 mt-1">ฉลองเปิดร้านใหม่ สั่งเลยอร่อยคุ้ม!</p>
                    </div>
                    <div class="bg-white/20 p-4 rounded-2xl backdrop-blur-md border border-white/30">
                        <i class="bi bi-stars text-3xl"></i>
                    </div>
                </div>

            </div>
        </main>
    </div>

</body>
</html>