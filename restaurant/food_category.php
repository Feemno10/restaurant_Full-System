<?php
// ไฟล์: restaurant/food_category.php
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
    // 2. ดึง restaurant_id ของเจ้าของร้านนี้ก่อน
    $stmt_res = $conn->prepare("SELECT restaurant_id FROM restaurants WHERE user_id = :uid");
    $stmt_res->execute([':uid' => $user_id]);
    $restaurant = $stmt_res->fetch(PDO::FETCH_ASSOC);

    if (!$restaurant) {
        // ถ้ายังไม่ได้ตั้งชื่อร้านในหน้า profile ให้เด้งกลับไปก่อน
        header("Location: profile.php?msg=please_setup_restaurant");
        exit();
    }
    $restaurant_id = $restaurant['restaurant_id'];

    // 3. จัดการคำสั่งเพิ่ม/แก้ไข/ลบ (CRUD Logic - ข้อ 3.2.7)
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
        // --- เพิ่มหมวดหมู่อาหารใหม่ ---
        if (isset($_POST['add_category'])) {
            $name = trim($_POST['cat_name']);
            if (!empty($name)) {
                $stmt = $conn->prepare("INSERT INTO food_categories (restaurant_id, name) VALUES (:rid, :name)");
                $stmt->execute([':rid' => $restaurant_id, ':name' => $name]);
                $message = "<div class='bg-emerald-500 text-white p-3 rounded-2xl mb-4 shadow-md'><i class='bi bi-check-circle mr-2'></i>เพิ่มหมวดหมู่เรียบร้อย</div>";
            }
        }

        // --- แก้ไขหมวดหมู่ ---
        if (isset($_POST['edit_category'])) {
            $cat_id = $_POST['cat_id'];
            $name = trim($_POST['cat_name']);
            $stmt = $conn->prepare("UPDATE food_categories SET name = :name WHERE food_cat_id = :cid AND restaurant_id = :rid");
            $stmt->execute([':name' => $name, ':cid' => $cat_id, ':rid' => $restaurant_id]);
            $message = "<div class='bg-blue-500 text-white p-3 rounded-2xl mb-4 shadow-md'><i class='bi bi-info-circle mr-2'></i>อัปเดตข้อมูลสำเร็จ</div>";
        }

        // --- ลบหมวดหมู่ ---
        if (isset($_POST['delete_category'])) {
            $cat_id = $_POST['cat_id'];
            try {
                $stmt = $conn->prepare("DELETE FROM food_categories WHERE food_cat_id = :cid AND restaurant_id = :rid");
                $stmt->execute([':cid' => $cat_id, ':rid' => $restaurant_id]);
                $message = "<div class='bg-rose-500 text-white p-3 rounded-2xl mb-4 shadow-md'><i class='bi bi-trash mr-2'></i>ลบหมวดหมู่เรียบร้อย</div>";
            } catch(PDOException $e) {
                $message = "<div class='bg-rose-500 text-white p-3 rounded-2xl mb-4 shadow-md'>ไม่สามารถลบได้ เนื่องจากมีรายการอาหารอยู่ในหมวดหมู่นี้</div>";
            }
        }
    }

    // 4. ดึงหมวดหมู่ทั้งหมดของร้านนี้มาแสดง
    $stmt_cats = $conn->prepare("SELECT * FROM food_categories WHERE restaurant_id = :rid ORDER BY food_cat_id DESC");
    $stmt_cats->execute([':rid' => $restaurant_id]);
    $food_categories = $stmt_cats->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Categories | ShopManager</title>
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
        <div class="h-16 flex items-center px-6 bg-brand-orange text-white">
            <i class="bi bi-shop-window text-xl me-2"></i>
            <span class="font-bold text-lg tracking-wide">ShopManager</span>
        </div>
        <div class="flex-1 py-6 overflow-y-auto">
            <a href="index.php" class="nav-link"><i class="bi bi-speedometer2 mr-3"></i> แดชบอร์ด</a>
            <a href="orders.php" class="nav-link"><i class="bi bi-cart-check mr-3"></i> รายการสั่งอาหาร</a>
            <p class="px-6 text-xs font-bold text-slate-400 uppercase tracking-widest mb-4 mt-8">จัดการร้าน</p>
            <a href="food_category.php" class="nav-link active"><i class="bi bi-tags mr-3"></i> หมวดหมู่อาหาร</a>
            <a href="menu.php" class="nav-link"><i class="bi bi-egg-fried mr-3"></i> จัดการรายการอาหาร</a>
            <p class="px-6 text-xs font-bold text-slate-400 uppercase tracking-widest mb-4 mt-8">อื่นๆ</p>
            <a href="profile.php" class="nav-link"><i class="bi bi-person-gear mr-3"></i> โปรไฟล์ร้าน</a>
            <a href="../logout.php" class="nav-link text-rose-500 mt-4"><i class="bi bi-box-arrow-right mr-3"></i> ออกจากระบบ</a>
        </div>
    </aside>

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-8 z-10">
            <div class="flex items-center gap-2 text-sm font-bold text-slate-400">
                <i class="bi bi-tags"></i> <span>Menu Management</span>
                <i class="bi bi-chevron-right text-[10px] mx-1"></i>
                <span class="text-slate-700">Food Categories</span>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-brand-orange text-white flex items-center justify-center font-bold text-xs">RM</div>
                <span class="text-sm font-bold text-slate-700 hidden md:block">Restaurant Manager</span>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-8">
            <div class="max-w-5xl mx-auto">
                
                <div class="flex justify-between items-end mb-8">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-800">หมวดหมู่รายการอาหาร</h2>
                        <p class="text-slate-500 text-sm">สร้างหมวดหมู่เพื่อจัดกลุ่มเมนูอาหารในร้านของคุณให้เป็นระเบียบ</p>
                    </div>
                    <button onclick="toggleModal('addModal')" class="bg-brand-orange text-white px-6 py-3 rounded-2xl text-sm font-bold shadow-lg shadow-orange-100 hover:bg-orange-600 transition flex items-center gap-2">
                        <i class="bi bi-plus-circle-fill"></i> เพิ่มหมวดหมู่ใหม่
                    </button>
                </div>

                <?= $message; ?>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($food_categories as $cat): ?>
                        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 flex justify-between items-center group hover:border-brand-orange transition-colors">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-orange-50 text-brand-orange rounded-2xl flex items-center justify-center text-xl group-hover:bg-brand-orange group-hover:text-white transition-colors">
                                    <i class="bi bi-bookmark-star"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-800"><?= htmlspecialchars($cat['name']); ?></h4>
                                    <p class="text-[10px] text-slate-400 uppercase font-bold tracking-widest">ID: #<?= $cat['food_cat_id']; ?></p>
                                </div>
                            </div>
                            <div class="flex gap-1">
                                <button onclick="openEditModal(<?= $cat['food_cat_id']; ?>, '<?= htmlspecialchars($cat['name']); ?>')" class="p-2 text-blue-400 hover:bg-blue-50 rounded-xl transition"><i class="bi bi-pencil-square"></i></button>
                                <form action="food_category.php" method="POST" onsubmit="return confirm('ยืนยันการลบหมวดหมู่นี้?')">
                                    <input type="hidden" name="cat_id" value="<?= $cat['food_cat_id']; ?>">
                                    <button type="submit" name="delete_category" class="p-2 text-rose-400 hover:bg-rose-50 rounded-xl transition"><i class="bi bi-trash3"></i></button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if(empty($food_categories)): ?>
                        <div class="col-span-full py-20 text-center bg-white rounded-3xl border-2 border-dashed border-slate-200">
                            <i class="bi bi-tags text-5xl text-slate-200"></i>
                            <p class="text-slate-400 mt-4 font-medium">ยังไม่มีข้อมูลหมวดหมู่ กรุณาเพิ่มหมวดหมู่แรกของคุณ</p>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </main>
    </div>

    <div id="addModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-8 transform transition-all">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold">เพิ่มหมวดหมู่ใหม่</h3>
                <button onclick="toggleModal('addModal')" class="text-slate-400 hover:text-slate-600"><i class="bi bi-x-lg"></i></button>
            </div>
            <form action="food_category.php" method="POST">
                <label class="block text-xs font-bold text-slate-400 uppercase mb-2">ชื่อหมวดหมู่ (เช่น ของทานเล่น / เนื้อวัว / ผัก)</label>
                <input type="text" name="cat_name" required placeholder="ระบุชื่อกลุ่มอาหาร..." class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 mb-6 focus:ring-2 focus:ring-brand-orange outline-none">
                <div class="flex gap-3">
                    <button type="button" onclick="toggleModal('addModal')" class="flex-1 py-4 font-bold text-slate-400 hover:bg-slate-50 rounded-2xl transition">ยกเลิก</button>
                    <button type="submit" name="add_category" class="flex-1 py-4 font-bold bg-slate-900 text-white rounded-2xl hover:bg-slate-800 transition shadow-lg">ยืนยันการเพิ่ม</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-8 transform transition-all">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold">แก้ไขหมวดหมู่</h3>
                <button onclick="toggleModal('editModal')" class="text-slate-400 hover:text-slate-600"><i class="bi bi-x-lg"></i></button>
            </div>
            <form action="food_category.php" method="POST">
                <input type="hidden" name="cat_id" id="edit_cat_id">
                <label class="block text-xs font-bold text-slate-400 uppercase mb-2">ชื่อหมวดหมู่ใหม่</label>
                <input type="text" name="cat_name" id="edit_cat_name" required class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 mb-6 focus:ring-2 focus:ring-brand-orange outline-none">
                <div class="flex gap-3">
                    <button type="button" onclick="toggleModal('editModal')" class="flex-1 py-4 font-bold text-slate-400 rounded-2xl">ยกเลิก</button>
                    <button type="submit" name="edit_category" class="flex-1 py-4 font-bold bg-blue-600 text-white rounded-2xl shadow-lg">บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleModal(id) {
            document.getElementById(id).classList.toggle('hidden');
        }
        function openEditModal(id, name) {
            document.getElementById('edit_cat_id').value = id;
            document.getElementById('edit_cat_name').value = name;
            toggleModal('editModal');
        }
    </script>
</body>
</html>