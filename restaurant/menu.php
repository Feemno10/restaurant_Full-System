<?php
// ไฟล์: restaurant/menu.php
session_start();

// 1. ตรวจสอบสิทธิ์
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'restaurant') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';
$user_id = $_SESSION['user_id'];
$message = '';

try {
    // 2. ดึง restaurant_id
    $stmt_res = $conn->prepare("SELECT restaurant_id FROM restaurants WHERE user_id = :uid");
    $stmt_res->execute([':uid' => $user_id]);
    $restaurant = $stmt_res->fetch(PDO::FETCH_ASSOC);

    if (!$restaurant) {
        header("Location: profile.php?msg=setup_first");
        exit();
    }
    $restaurant_id = $restaurant['restaurant_id'];

    // 3. จัดการคำสั่ง CRUD (ข้อ 3.2.8)
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
        // --- เพิ่มเมนูอาหารใหม่ ---
        if (isset($_POST['add_food'])) {
            $name = trim($_POST['food_name']);
            $price = $_POST['price'];
            $cat_id = $_POST['food_cat_id'];
            $food_img = '';

            // จัดการอัปโหลดรูป
            if (isset($_FILES['food_img']) && $_FILES['food_img']['error'] == 0) {
                $upload_dir = '../assets/uploads/foods/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                
                $ext = strtolower(pathinfo($_FILES['food_img']['name'], PATHINFO_EXTENSION));
                $food_img = 'food_' . time() . '_' . rand(100,999) . '.' . $ext;
                move_uploaded_file($_FILES['food_img']['tmp_name'], $upload_dir . $food_img);
            }

            $stmt = $conn->prepare("INSERT INTO foods (restaurant_id, food_cat_id, food_name, price, food_img) VALUES (:rid, :cid, :n, :p, :img)");
            $stmt->execute([':rid'=>$restaurant_id, ':cid'=>$cat_id, ':n'=>$name, ':p'=>$price, ':img'=>$food_img]);
            $message = "<div class='bg-emerald-500 text-white p-3 rounded-2xl mb-4 shadow-md'>เพิ่มเมนู '$name' สำเร็จ!</div>";
        }

        // --- ลบเมนูอาหาร (รวมถึงลบไฟล์รูป) ---
        if (isset($_POST['delete_food'])) {
            $fid = $_POST['food_id'];
            // ดึงชื่อรูปมาลบทิ้งก่อน
            $stmt_img = $conn->prepare("SELECT food_img FROM foods WHERE food_id = :fid AND restaurant_id = :rid");
            $stmt_img->execute([':fid'=>$fid, ':rid'=>$restaurant_id]);
            $old_img = $stmt_img->fetchColumn();
            
            if ($old_img && file_exists('../assets/uploads/foods/' . $old_img)) {
                unlink('../assets/uploads/foods/' . $old_img);
            }

            $stmt = $conn->prepare("DELETE FROM foods WHERE food_id = :fid AND restaurant_id = :rid");
            $stmt->execute([':fid'=>$fid, ':rid'=>$restaurant_id]);
            $message = "<div class='bg-rose-500 text-white p-3 rounded-2xl mb-4 shadow-md'>ลบเมนูเรียบร้อยแล้ว</div>";
        }
    }

    // 4. ดึงข้อมูลประกอบหน้าเว็บ
    $categories = $conn->prepare("SELECT * FROM food_categories WHERE restaurant_id = :rid");
    $categories->execute([':rid' => $restaurant_id]);
    $cat_list = $categories->fetchAll(PDO::FETCH_ASSOC);

    $stmt_menu = $conn->prepare("
        SELECT f.*, fc.name as cat_name 
        FROM foods f 
        JOIN food_categories fc ON f.food_cat_id = fc.food_cat_id 
        WHERE f.restaurant_id = :rid 
        ORDER BY f.food_id DESC
    ");
    $stmt_menu->execute([':rid' => $restaurant_id]);
    $menus = $stmt_menu->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) { die("Error: " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Management | ShopManager</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['Prompt', 'sans-serif'] }, colors: { brand: { orange: '#ff5c00', light: '#fff7f3' } } } } }
    </script>
    <style>
        .nav-link { display: flex; align-items: center; padding: 0.85rem 1.5rem; color: #64748b; font-size: 0.9rem; font-weight: 500; border-left: 4px solid transparent; transition: 0.2s; }
        .nav-link:hover, .nav-link.active { color: #ff5c00; background-color: #fff7f3; border-left-color: #ff5c00; }
    </style>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800">

    <aside class="w-64 bg-white shadow-2xl z-20 flex-shrink-0 flex flex-col">
        <div class="h-16 flex items-center px-6 bg-brand-orange text-white"><i class="bi bi-shop-window text-xl me-2"></i><span class="font-bold text-lg tracking-wide">ShopManager</span></div>
        <div class="flex-1 py-6 overflow-y-auto">
            <a href="index.php" class="nav-link"><i class="bi bi-speedometer2 mr-3"></i> แดชบอร์ด</a>
            <a href="orders.php" class="nav-link"><i class="bi bi-cart-check mr-3"></i> รายการสั่งอาหาร</a>
            <p class="px-6 text-xs font-bold text-slate-400 uppercase tracking-widest mb-4 mt-8">จัดการร้าน</p>
            <a href="food_category.php" class="nav-link"><i class="bi bi-tags mr-3"></i> หมวดหมู่อาหาร</a>
            <a href="menu.php" class="nav-link active"><i class="bi bi-egg-fried mr-3"></i> จัดการรายการอาหาร</a>
            <p class="px-6 text-xs font-bold text-slate-400 uppercase tracking-widest mb-4 mt-8">อื่นๆ</p>
            <a href="profile.php" class="nav-link"><i class="bi bi-person-gear mr-3"></i> โปรไฟล์ร้าน</a>
            <a href="../logout.php" class="nav-link text-rose-500 mt-4"><i class="bi bi-box-arrow-right mr-3"></i> ออกจากระบบ</a>
        </div>
    </aside>

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-8 z-10 font-bold text-slate-700">
            <span>จัดการรายการอาหาร (Menu)</span>
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-brand-orange text-white flex items-center justify-center text-xs">RM</div>
                <span class="text-sm hidden md:block">Restaurant Manager</span>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-8">
            <div class="max-w-6xl mx-auto">
                
                <div class="flex justify-between items-end mb-8">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-800">รายการอาหารในร้าน</h2>
                        <p class="text-slate-500 text-sm">เพิ่มเมนูอร่อยของคุณ พร้อมรูปภาพและราคาที่ชัดเจน</p>
                    </div>
                    <button onclick="document.getElementById('addModal').classList.remove('hidden')" class="bg-brand-orange text-white px-6 py-3 rounded-2xl text-sm font-bold shadow-lg shadow-orange-100 hover:bg-orange-600 transition flex items-center gap-2">
                        <i class="bi bi-plus-circle-fill"></i> เพิ่มเมนูใหม่
                    </button>
                </div>

                <?= $message; ?>

                <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 border-b border-slate-100 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                            <tr>
                                <th class="px-8 py-4">รูปภาพ</th>
                                <th class="px-8 py-4">ชื่อเมนู</th>
                                <th class="px-8 py-4">หมวดหมู่</th>
                                <th class="px-8 py-4 text-center">ราคา</th>
                                <th class="px-8 py-4 text-right">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-sm">
                            <?php foreach ($menus as $m): ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-8 py-4">
                                    <img src="../assets/uploads/foods/<?= $m['food_img'] ?: 'default-food.png' ?>" class="w-14 h-14 rounded-2xl object-cover shadow-sm" onerror="this.src='https://via.placeholder.com/100?text=Food'">
                                </td>
                                <td class="px-8 py-4 font-bold text-slate-700"><?= htmlspecialchars($m['food_name']) ?></td>
                                <td class="px-8 py-4"><span class="bg-slate-100 text-slate-500 px-3 py-1 rounded-full text-[10px] font-bold"><?= htmlspecialchars($m['cat_name']) ?></span></td>
                                <td class="px-8 py-4 text-center font-bold text-brand-orange">฿<?= number_format($m['price'], 2) ?></td>
                                <td class="px-8 py-4 text-right">
                                    <form action="menu.php" method="POST" onsubmit="return confirm('ยืนยันการลบเมนูนี้?')">
                                        <input type="hidden" name="food_id" value="<?= $m['food_id'] ?>">
                                        <button type="submit" name="delete_food" class="p-2 text-rose-400 hover:bg-rose-50 rounded-xl transition"><i class="bi bi-trash3"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </main>
    </div>

    <div id="addModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg p-8">
            <div class="flex justify-between items-center mb-6"><h3 class="text-xl font-bold">เพิ่มเมนูอาหารใหม่</h3><button onclick="this.closest('#addModal').classList.add('hidden')" class="text-slate-400"><i class="bi bi-x-lg"></i></button></div>
            <form action="menu.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                <div><label class="text-xs font-bold text-slate-400 uppercase block mb-1">ชื่อเมนู</label><input type="text" name="food_name" required class="w-full bg-slate-50 border-none rounded-xl px-5 py-3 focus:ring-2 focus:ring-brand-orange outline-none"></div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="text-xs font-bold text-slate-400 uppercase block mb-1">ราคา (บาท)</label><input type="number" name="price" step="0.01" required class="w-full bg-slate-50 border-none rounded-xl px-5 py-3 focus:ring-2 focus:ring-brand-orange outline-none"></div>
                    <div><label class="text-xs font-bold text-slate-400 uppercase block mb-1">หมวดหมู่</label>
                        <select name="food_cat_id" required class="w-full bg-slate-50 border-none rounded-xl px-5 py-3 focus:ring-2 focus:ring-brand-orange outline-none">
                            <?php foreach($cat_list as $c): ?><option value="<?= $c['food_cat_id'] ?>"><?= $c['name'] ?></option><?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div><label class="text-xs font-bold text-slate-400 uppercase block mb-1">รูปภาพประกอบ</label><input type="file" name="food_img" accept="image/*" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-brand-orange file:text-white hover:file:bg-orange-600"></div>
                <button type="submit" name="add_food" class="w-full bg-slate-900 text-white font-bold py-4 rounded-2xl hover:bg-slate-800 transition shadow-lg mt-4">บันทึกรายการอาหาร</button>
            </form>
        </div>
    </div>

</body>
</html>