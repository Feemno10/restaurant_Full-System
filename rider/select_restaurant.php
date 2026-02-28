<?php
// ไฟล์: customer/select_restaurant.php
session_start();

// 1. ตรวจสอบสิทธิ์ (Security) - ต้องเป็น customer เท่านั้น
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';
$user_id = $_SESSION['user_id'];

// 2. รับค่าการกรองหมวดหมู่ (ถ้ามี)
$cat_id = isset($_GET['cat_id']) ? $_GET['cat_id'] : 'all';

try {
    // 3. ดึงข้อมูลหมวดหมู่ทั้งหมดสำหรับทำปุ่ม Filter (ข้อ 3.1.5)
    $stmt_cats = $conn->prepare("SELECT * FROM restaurant_categories");
    $stmt_cats->execute();
    $categories = $stmt_cats->fetchAll(PDO::FETCH_ASSOC);

    // 4. ดึงข้อมูลร้านอาหารที่ได้รับอนุมัติแล้วเท่านั้น (ข้อ 3.1.7)
    $sql_res = "SELECT r.*, rc.category_name, u.status 
                FROM restaurants r
                JOIN restaurant_categories rc ON r.category_id = rc.category_id
                JOIN users u ON r.user_id = u.user_id
                WHERE u.status = 'approved'";
    
    // ถ้ามีการเลือกหมวดหมู่ ให้เพิ่มเงื่อนไข WHERE
    if ($cat_id !== 'all') {
        $sql_res .= " AND r.category_id = :cid";
        $stmt_res = $conn->prepare($sql_res);
        $stmt_res->execute([':cid' => $cat_id]);
    } else {
        $stmt_res = $conn->prepare($sql_res);
        $stmt_res->execute();
    }
    $restaurants = $stmt_res->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) { die("Error: " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เลือกร้านอาหาร | FoodDelivery</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['Prompt', 'sans-serif'] }, colors: { brand: { pink: '#f1416c' } } } } }
    </script>
</head>
<body class="bg-slate-50 font-sans text-slate-800">

    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <a href="index.php" class="flex items-center gap-2">
                    <div class="bg-brand-pink p-1.5 rounded-lg shadow-lg shadow-rose-200 text-white">
                        <i class="bi bi-shop-window text-xl"></i>
                    </div>
                    <span class="font-black text-xl tracking-tighter text-slate-900 uppercase">Food<span class="text-brand-pink">Delivery</span></span>
                </a>
                
                <div class="flex items-center gap-6">
                    <a href="cart.php" class="relative text-slate-400 hover:text-brand-pink transition">
                        <i class="bi bi-cart3 text-2xl"></i>
                        <span class="absolute -top-1 -right-2 bg-brand-pink text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full ring-2 ring-white">0</span>
                    </a>
                    <div class="h-6 w-px bg-slate-200"></div>
                    <div class="flex items-center gap-3">
                        <div class="text-right hidden sm:block">
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest leading-none mb-1">Customer</p>
                            <p class="text-sm font-bold text-slate-700"><?= htmlspecialchars($_SESSION['full_name']); ?></p>
                        </div>
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['full_name']); ?>&background=f1416c&color=fff" class="w-10 h-10 rounded-2xl shadow-md border-2 border-white">
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        
        <div class="mb-12 text-center">
            <h1 class="text-4xl font-black text-slate-900 tracking-tight mb-4">หิวแล้วใช่ไหม? สั่งเลย! 🍔</h1>
            <p class="text-slate-400 font-medium">เลือกร้านอาหารที่ถูกใจและสัมผัสความอร่อยส่งตรงถึงหน้าบ้านคุณ</p>
        </div>

        <div class="flex flex-wrap justify-center gap-3 mb-12">
            <a href="?cat_id=all" class="px-6 py-2.5 rounded-full text-sm font-bold transition-all <?= $cat_id == 'all' ? 'bg-slate-900 text-white shadow-xl shadow-slate-200' : 'bg-white text-slate-500 hover:bg-slate-100' ?>">
                ทั้งหมด
            </a>
            <?php foreach ($categories as $cat): ?>
                <a href="?cat_id=<?= $cat['category_id']; ?>" class="px-6 py-2.5 rounded-full text-sm font-bold transition-all <?= $cat_id == $cat['category_id'] ? 'bg-brand-pink text-white shadow-xl shadow-rose-200' : 'bg-white text-slate-500 hover:bg-slate-100' ?>">
                    <?= htmlspecialchars($cat['category_name']); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($restaurants as $res): ?>
                <div class="bg-white rounded-[2.5rem] overflow-hidden border border-slate-100 shadow-sm hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 group">
                    <div class="relative h-48 bg-slate-100 overflow-hidden">
                        <img src="https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&q=80&w=800" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                        <div class="absolute bottom-4 left-6">
                            <span class="bg-brand-pink text-white text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-lg">
                                <?= htmlspecialchars($res['category_name']); ?>
                            </span>
                        </div>
                        <?php if ($res['discount_percent'] > 0): ?>
                            <div class="absolute top-4 right-6 bg-amber-400 text-slate-900 text-xs font-black px-4 py-1.5 rounded-full shadow-lg flex items-center gap-1">
                                <i class="bi bi-lightning-fill"></i> ลด <?= $res['discount_percent']; ?>%
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="p-8">
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-xl font-bold text-slate-900 group-hover:text-brand-pink transition-colors leading-tight">
                                <?= htmlspecialchars($res['restaurant_name']); ?>
                            </h3>
                        </div>
                        <p class="text-sm text-slate-400 font-medium mb-6 line-clamp-1"><i class="bi bi-geo-alt-fill text-slate-300"></i> <?= htmlspecialchars($res['address']); ?></p>
                        
                        <div class="flex items-center justify-between pt-6 border-t border-slate-50">
                            <div class="flex items-center gap-2">
                                <div class="flex text-amber-400 text-sm">
                                    <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-half"></i>
                                </div>
                                <span class="text-xs font-bold text-slate-400">(4.5)</span>
                            </div>
                            <a href="select_food.php?restaurant_id=<?= $res['restaurant_id']; ?>" class="bg-slate-900 text-white text-xs font-bold px-6 py-2.5 rounded-xl hover:bg-brand-pink transition shadow-lg shadow-slate-100">
                                ดูเมนูอาหาร
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if(empty($restaurants)): ?>
                <div class="col-span-full py-20 text-center bg-white rounded-[3rem] border-2 border-dashed border-slate-200">
                    <i class="bi bi-emoji-frown text-6xl text-slate-200"></i>
                    <p class="text-slate-400 mt-4 font-bold">ไม่พบร้านอาหารในหมวดหมู่นี้</p>
                    <a href="?cat_id=all" class="text-brand-pink text-sm font-bold hover:underline mt-2 inline-block">กลับไปดูทั้งหมด</a>
                </div>
            <?php endif; ?>
        </div>

    </main>

    <footer class="bg-white border-t border-slate-100 py-10 mt-20">
        <div class="text-center text-slate-400 text-xs font-bold uppercase tracking-[0.3em]">
            &copy; 2026 Phangnga Technical College Project
        </div>
    </footer>

</body>
</html>