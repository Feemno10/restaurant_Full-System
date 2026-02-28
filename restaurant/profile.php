<?php
// ไฟล์: restaurant/profile.php
session_start();

// 1. ตรวจสอบสิทธิ์ (Security)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'restaurant') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';
$user_id = $_SESSION['user_id'];
$message = '';

// 2. จัดการเมื่อมีการกดปุ่ม Submit (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // --- ส่วนที่ 1: แก้ไขข้อมูลส่วนตัวและร้านอาหาร (รวมถึงอัปโหลดภาพ) ---
    if (isset($_POST['update_profile'])) {
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $phone = trim($_POST['phone']);
        $res_name = trim($_POST['restaurant_name']);
        $address = trim($_POST['address']);
        $category_id = $_POST['category_id'];
        
        // ดึงข้อมูลเดิมเพื่อตรวจสอบรูปภาพเก่า
        $stmt_old = $conn->prepare("SELECT restaurant_img FROM restaurants WHERE user_id = :uid");
        $stmt_old->execute([':uid' => $user_id]);
        $old_res_data = $stmt_old->fetch(PDO::FETCH_ASSOC);
        $res_img = $old_res_data['restaurant_img'] ?? null;

        // จัดการอัปโหลดรูปภาพร้าน
        if (isset($_FILES['restaurant_img']) && $_FILES['restaurant_img']['error'] == 0) {
            $upload_dir = '../assets/uploads/restaurants/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            $ext = strtolower(pathinfo($_FILES['restaurant_img']['name'], PATHINFO_EXTENSION));
            $new_file_name = 'res_' . $user_id . '_' . time() . '.' . $ext;
            
            if (move_uploaded_file($_FILES['restaurant_img']['tmp_name'], $upload_dir . $new_file_name)) {
                // ลบรูปเก่าถ้ามี (ยกเว้นรูป default)
                if ($res_img && file_exists($upload_dir . $res_img)) {
                    unlink($upload_dir . $res_img);
                }
                $res_img = $new_file_name;
            }
        }

        try {
            $conn->beginTransaction();

            // 1. อัปเดตตาราง users
            $stmt_u = $conn->prepare("UPDATE users SET first_name = :f, last_name = :l, phone = :p WHERE user_id = :id");
            $stmt_u->execute([':f' => $first_name, ':l' => $last_name, ':p' => $phone, ':id' => $user_id]);

            // 2. อัปเดตหรือเพิ่มข้อมูลในตาราง restaurants
            $check_res = $conn->prepare("SELECT restaurant_id FROM restaurants WHERE user_id = :uid");
            $check_res->execute([':uid' => $user_id]);
            
            if ($check_res->rowCount() > 0) {
                $stmt_r = $conn->prepare("UPDATE restaurants SET restaurant_name = :rn, restaurant_img = :img, address = :addr, category_id = :cid WHERE user_id = :uid");
                $stmt_r->execute([':rn' => $res_name, ':img' => $res_img, ':addr' => $address, ':cid' => $category_id, ':uid' => $user_id]);
            } else {
                $stmt_r = $conn->prepare("INSERT INTO restaurants (user_id, restaurant_name, restaurant_img, address, category_id) VALUES (:uid, :rn, :img, :addr, :cid)");
                $stmt_r->execute([':uid' => $user_id, ':rn' => $res_name, ':img' => $res_img, ':addr' => $address, ':cid' => $category_id]);
            }

            $conn->commit();
            $_SESSION['full_name'] = $first_name . ' ' . $last_name;
            $message = "<div class='bg-emerald-500 text-white p-4 rounded-2xl mb-6 shadow-lg shadow-emerald-100 flex items-center gap-3 animate-pulse'>
                            <i class='bi bi-check-circle-fill fs-4'></i>
                            <p class='font-bold'>บันทึกข้อมูลร้านและรูปภาพสำเร็จแล้ว!</p>
                        </div>";
        } catch(PDOException $e) {
            $conn->rollBack();
            $message = "<div class='bg-rose-500 text-white p-4 rounded-2xl mb-6 shadow-lg'>Error: " . $e->getMessage() . "</div>";
        }
    }

    // --- ส่วนที่ 2: เปลี่ยนรหัสผ่าน ---
    if (isset($_POST['change_password'])) {
        $current_pass = $_POST['current_password'];
        $new_pass = $_POST['new_password'];
        $confirm_pass = $_POST['confirm_password'];

        $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = :id");
        $stmt->execute([':id' => $user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (password_verify($current_pass, $user['password'])) {
            if ($new_pass === $confirm_pass) {
                $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                $stmt_up = $conn->prepare("UPDATE users SET password = :p WHERE user_id = :id");
                $stmt_up->execute([':p' => $hashed, ':id' => $user_id]);
                $message = "<div class='bg-emerald-500 text-white p-4 rounded-2xl mb-6 shadow-lg'><i class='bi bi-shield-check mr-2'></i>เปลี่ยนรหัสผ่านสำเร็จ</div>";
            } else { $message = "<div class='bg-rose-500 text-white p-4 rounded-2xl mb-6'>รหัสผ่านใหม่ไม่ตรงกัน</div>"; }
        } else { $message = "<div class='bg-rose-500 text-white p-4 rounded-2xl mb-6'>รหัสผ่านปัจจุบันไม่ถูกต้อง</div>"; }
    }
}

// 3. ดึงข้อมูลมาแสดงผล
$stmt_data = $conn->prepare("
    SELECT u.*, r.restaurant_name, r.restaurant_img, r.address, r.category_id 
    FROM users u 
    LEFT JOIN restaurants r ON u.user_id = r.user_id 
    WHERE u.user_id = :id
");
$stmt_data->execute([':id' => $user_id]);
$data = $stmt_data->fetch(PDO::FETCH_ASSOC);

$categories = $conn->query("SELECT * FROM restaurant_categories")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Profile | ShopManager</title>
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
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 font-sans">

    <aside class="w-64 bg-white shadow-2xl z-20 flex-shrink-0 flex flex-col">
        <div class="h-16 flex items-center px-6 bg-brand-orange text-white">
            <i class="bi bi-shop-window text-xl me-2"></i>
            <span class="font-bold text-lg tracking-wide">ShopManager</span>
        </div>
        <div class="flex-1 py-6 overflow-y-auto text-sm">
            <a href="index.php" class="nav-link"><i class="bi bi-speedometer2 mr-3"></i> แดชบอร์ด</a>
            <a href="orders.php" class="nav-link"><i class="bi bi-cart-check mr-3"></i> รายการสั่งอาหาร</a>
            <p class="px-6 text-xs font-bold text-slate-400 uppercase tracking-widest mb-4 mt-8">จัดการร้าน</p>
            <a href="food_category.php" class="nav-link"><i class="bi bi-tags mr-3"></i> หมวดหมู่อาหาร</a>
            <a href="menu.php" class="nav-link"><i class="bi bi-egg-fried mr-3"></i> จัดการรายการอาหาร</a>
            <a href="discount.php" class="nav-link"><i class="bi bi-percent mr-3"></i> ตั้งค่าส่วนลด</a>
            <p class="px-6 text-xs font-bold text-slate-400 uppercase tracking-widest mb-4 mt-8">อื่นๆ</p>
            <a href="profile.php" class="nav-link active"><i class="bi bi-person-gear mr-3"></i> โปรไฟล์ร้าน [3.2.4]</a>
            <a href="../logout.php" class="nav-link text-rose-500 mt-4"><i class="bi bi-box-arrow-right mr-3"></i> ออกจากระบบ</a>
        </div>
    </aside>

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-8 z-10">
            <h1 class="font-bold text-slate-700">ตั้งค่าข้อมูลส่วนตัวและร้านอาหาร</h1>
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-brand-orange text-white flex items-center justify-center font-bold text-xs">
                        <?= mb_substr($data['restaurant_name'] ?? 'S', 0, 1); ?>
                    </div>
                    <span class="text-sm font-bold text-slate-700 hidden md:block"><?= htmlspecialchars($data['restaurant_name'] ?? 'Shop'); ?></span>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-8">
            <div class="max-w-4xl mx-auto">
                
                <div class="mb-8 flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-800 tracking-tight">จัดการโปรไฟล์ร้านอาหาร</h2>
                        <p class="text-slate-500 text-sm">อัปเดตข้อมูลและภาพถ่ายหน้าร้านของคุณให้ดูน่าดึงดูด</p>
                    </div>
                </div>

                <?= $message; ?>

                <form action="profile.php" method="POST" enctype="multipart/form-data">
                    <div class="grid grid-cols-1 gap-8">
                        
                        <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100">
                            <h3 class="text-lg font-bold text-slate-800 mb-8 flex items-center gap-2"><i class="bi bi-shop text-brand-orange"></i> ข้อมูลและภาพร้าน</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-start">
                                <div class="col-span-1 text-center">
                                    <label class="text-[10px] font-bold text-slate-400 uppercase mb-3 block tracking-widest text-left">ภาพหน้าร้าน (แนะนำ 4:3)</label>
                                    <div class="relative group">
                                        <img id="preview" src="../assets/uploads/restaurants/<?= $data['restaurant_img'] ?? 'default_res.jpg' ?>" 
                                             class="w-full aspect-[4/3] rounded-3xl object-cover shadow-lg border-4 border-slate-50 bg-slate-100"
                                             onerror="this.src='https://via.placeholder.com/400x300?text=No+Image'">
                                        <label class="absolute inset-0 flex items-center justify-center cursor-pointer opacity-0 group-hover:opacity-100 transition-all duration-300 rounded-3xl bg-black/40 text-white font-bold">
                                            <i class="bi bi-camera-fill mr-2"></i> เปลี่ยนภาพ
                                            <input type="file" name="restaurant_img" class="hidden" accept="image/*" onchange="previewImage(this)">
                                        </label>
                                    </div>
                                    <p class="text-[10px] text-slate-400 mt-2 italic">คลิกที่รูปเพื่อเลือกไฟล์ใหม่</p>
                                </div>

                                <div class="col-span-1 md:col-span-2 space-y-6">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="text-[10px] font-bold text-slate-400 uppercase mb-2 block tracking-widest">ชื่อร้านอาหาร</label>
                                            <input type="text" name="restaurant_name" value="<?= htmlspecialchars($data['restaurant_name'] ?? ''); ?>" placeholder="เช่น ชาบูชิลล์ พังงา" class="w-full bg-slate-50 border-none rounded-2xl px-5 py-3 focus:ring-2 focus:ring-brand-orange outline-none font-medium" required>
                                        </div>
                                        <div>
                                            <label class="text-[10px] font-bold text-slate-400 uppercase mb-2 block tracking-widest">หมวดหมู่ร้าน</label>
                                            <select name="category_id" class="w-full bg-slate-50 border-none rounded-2xl px-5 py-3 focus:ring-2 focus:ring-brand-orange outline-none font-medium" required>
                                                <?php foreach($categories as $cat): ?>
                                                    <option value="<?= $cat['category_id'] ?>" <?= ($data['category_id'] ?? '') == $cat['category_id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($cat['category_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-bold text-slate-400 uppercase mb-2 block tracking-widest">ที่อยู่ร้านอาหาร</label>
                                        <textarea name="address" rows="3" class="w-full bg-slate-50 border-none rounded-2xl px-5 py-3 focus:ring-2 focus:ring-brand-orange outline-none font-medium" required><?= htmlspecialchars($data['address'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100">
                                <h3 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-2"><i class="bi bi-person-vcard text-brand-orange"></i> ข้อมูลส่วนตัว</h3>
                                <div class="space-y-4">
                                    <div class="flex gap-2">
                                        <div class="w-1/2">
                                            <label class="text-[10px] font-bold text-slate-400 uppercase mb-2 block">ชื่อจริง</label>
                                            <input type="text" name="first_name" value="<?= htmlspecialchars($data['first_name']); ?>" class="w-full bg-slate-50 border-none rounded-2xl px-4 py-3 focus:ring-2 focus:ring-brand-orange outline-none" required>
                                        </div>
                                        <div class="w-1/2">
                                            <label class="text-[10px] font-bold text-slate-400 uppercase mb-2 block">นามสกุล</label>
                                            <input type="text" name="last_name" value="<?= htmlspecialchars($data['last_name']); ?>" class="w-full bg-slate-50 border-none rounded-2xl px-4 py-3 focus:ring-2 focus:ring-brand-orange outline-none" required>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-bold text-slate-400 uppercase mb-2 block">เบอร์โทรศัพท์ติดต่อ</label>
                                        <input type="text" name="phone" value="<?= htmlspecialchars($data['phone']); ?>" class="w-full bg-slate-50 border-none rounded-2xl px-4 py-3 focus:ring-2 focus:ring-brand-orange outline-none" required>
                                    </div>
                                    <button type="submit" name="update_profile" class="w-full bg-slate-900 text-white font-bold py-4 rounded-2xl hover:bg-slate-800 shadow-lg shadow-slate-100 transition mt-4">บันทึกการเปลี่ยนแปลงทั้งหมด</button>
                                </div>
                            </div>

                            <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100">
                                <h3 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-2"><i class="bi bi-shield-lock text-brand-orange"></i> เปลี่ยนรหัสผ่าน [3.2.5]</h3>
                                <div class="space-y-4 text-sm">
                                    <input type="password" name="current_password" placeholder="รหัสผ่านปัจจุบัน" class="w-full bg-slate-50 border-none rounded-2xl px-5 py-3 focus:ring-2 focus:ring-brand-orange outline-none">
                                    <input type="password" name="new_password" placeholder="รหัสผ่านใหม่" class="w-full bg-slate-50 border-none rounded-2xl px-5 py-3 focus:ring-2 focus:ring-brand-orange outline-none">
                                    <input type="password" name="confirm_password" placeholder="ยืนยันรหัสผ่านใหม่" class="w-full bg-slate-50 border-none rounded-2xl px-5 py-3 focus:ring-2 focus:ring-brand-orange outline-none">
                                    <button type="submit" name="change_password" class="w-full border-2 border-slate-100 text-slate-700 font-bold py-4 rounded-2xl hover:bg-slate-50 transition mt-4">อัปเดตรหัสผ่านใหม่</button>
                                </div>
                            </div>
                        </div>

                    </div>
                </form>

            </div>
        </main>
    </div>

    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>