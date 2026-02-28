<?php
// ไฟล์: rider/profile.php
session_start();

// 1. ตรวจสอบสิทธิ์ (Security)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'rider') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';
$user_id = $_SESSION['user_id'];
$message = '';

// 2. จัดการเมื่อมีการกดปุ่ม Submit (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // --- ส่วนที่ 1: แก้ไขข้อมูลส่วนตัว (ข้อ 3.4.3) ---
    if (isset($_POST['update_profile'])) {
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $phone = trim($_POST['phone']);
        $profile_img = $_SESSION['profile_img'] ?? 'default.png';

        // จัดการอัปโหลดรูปภาพ
        if (isset($_FILES['profile_img']) && $_FILES['profile_img']['error'] == 0) {
            $upload_dir = '../assets/uploads/profiles/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            $ext = strtolower(pathinfo($_FILES['profile_img']['name'], PATHINFO_EXTENSION));
            $new_file_name = 'rider_' . $user_id . '_' . time() . '.' . $ext;
            
            if (move_uploaded_file($_FILES['profile_img']['tmp_name'], $upload_dir . $new_file_name)) {
                $profile_img = $new_file_name;
            }
        }

        try {
            $stmt = $conn->prepare("UPDATE users SET first_name = :f, last_name = :l, phone = :p, profile_img = :img WHERE user_id = :id");
            $stmt->execute([':f' => $first_name, ':l' => $last_name, ':p' => $phone, ':img' => $profile_img, ':id' => $user_id]);
            
            $_SESSION['full_name'] = $first_name . ' ' . $last_name;
            $_SESSION['profile_img'] = $profile_img;
            $message = "<div class='bg-emerald-500 text-white p-4 rounded-2xl mb-6 shadow-lg shadow-emerald-100 flex items-center gap-3'>
                            <i class='bi bi-check-circle-fill fs-5'></i>
                            <p class='font-bold'>อัปเดตข้อมูลส่วนตัวสำเร็จแล้วครับ!</p>
                        </div>";
        } catch(PDOException $e) { 
            $message = "<div class='bg-rose-500 text-white p-4 rounded-2xl mb-6 shadow-lg'>Error: " . $e->getMessage() . "</div>"; 
        }
    }

    // --- ส่วนที่ 2: เปลี่ยนรหัสผ่าน (ข้อ 3.4.4) ---
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        try {
            $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = :id");
            $stmt->execute([':id' => $user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (password_verify($current_password, $user['password'])) {
                if ($new_password === $confirm_password) {
                    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt_up = $conn->prepare("UPDATE users SET password = :p WHERE user_id = :id");
                    $stmt_up->execute([':p' => $hashed, ':id' => $user_id]);
                    $message = "<div class='bg-emerald-600 text-white p-4 rounded-2xl mb-6 shadow-lg'><i class='bi bi-shield-check mr-2'></i>เปลี่ยนรหัสผ่านใหม่เรียบร้อยแล้ว</div>";
                } else { $message = "<div class='bg-rose-500 text-white p-4 rounded-2xl mb-6'>รหัสผ่านใหม่ไม่ตรงกัน</div>"; }
            } else { $message = "<div class='bg-rose-500 text-white p-4 rounded-2xl mb-6'>รหัสผ่านปัจจุบันไม่ถูกต้อง</div>"; }
        } catch(PDOException $e) { $message = "<div class='bg-rose-500 text-white p-4 rounded-2xl mb-6 shadow-lg'>Error: " . $e->getMessage() . "</div>"; }
    }
}

// 3. ดึงข้อมูลล่าสุดมาแสดง
$stmt_user = $conn->prepare("SELECT * FROM users WHERE user_id = :id");
$stmt_user->execute([':id' => $user_id]);
$rider = $stmt_user->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rider Profile | FoodDelivery</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['Prompt', 'sans-serif'] }, colors: { brand: { green: '#10b981', light: '#ecfdf5' } } } } }
    </script>
    <style>
        .nav-link { display: flex; align-items: center; padding: 0.85rem 1.5rem; color: #64748b; font-size: 0.9rem; font-weight: 500; border-left: 4px solid transparent; transition: 0.2s; }
        .nav-link:hover, .nav-link.active { color: #10b981; background-color: #f0fdf4; border-left-color: #10b981; }
    </style>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800">

    <aside class="w-64 bg-white shadow-2xl z-20 flex-shrink-0 flex flex-col">
        <div class="h-16 flex items-center px-6 bg-brand-green text-white">
            <i class="bi bi-motorcycle text-2xl me-2"></i>
            <span class="font-bold text-lg tracking-wide">RiderExpress</span>
        </div>
        <div class="flex-1 py-6 overflow-y-auto">
            <a href="index.php" class="nav-link"><i class="bi bi-speedometer2 mr-3"></i> แดชบอร์ด</a>
            <a href="jobs.php" class="nav-link"><i class="bi bi-box-seam mr-3"></i> ประวัติงานส่ง</a>
            <p class="px-6 text-xs font-bold text-slate-400 uppercase tracking-widest mb-4 mt-8">บัญชีของฉัน</p>
            <a href="profile.php" class="nav-link active"><i class="bi bi-person-gear mr-3"></i> โปรไฟล์ส่วนตัว [3.4.3]</a>
            <a href="../logout.php" class="nav-link text-rose-500 mt-4"><i class="bi bi-box-arrow-right mr-3"></i> ออกจากระบบ</a>
        </div>
    </aside>

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-8 z-10">
            <h1 class="font-bold text-slate-700">ตั้งค่าโปรไฟล์ผู้ส่งอาหาร</h1>
            <div class="flex items-center gap-4">
                <i class="bi bi-bell text-slate-400"></i>
                <div class="h-6 w-px bg-slate-200"></div>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-brand-green text-white flex items-center justify-center font-bold text-xs uppercase shadow-sm">
                        <?= mb_substr($rider['first_name'], 0, 1); ?>
                    </div>
                    <span class="text-sm font-bold text-slate-700 hidden md:block"><?= htmlspecialchars($_SESSION['full_name']); ?></span>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-8">
            <div class="max-w-4xl mx-auto">
                
                <div class="mb-8">
                    <h2 class="text-2xl font-black text-slate-900">จัดการข้อมูลของคุณ</h2>
                    <p class="text-slate-400 text-sm mt-1">แก้ไขข้อมูลส่วนตัวและรหัสผ่านเพื่อความปลอดภัย</p>
                </div>

                <?= $message; ?>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-1">
                        <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100 text-center relative overflow-hidden">
                            <form action="profile.php" method="POST" enctype="multipart/form-data" id="imgForm">
                                <div class="relative inline-block group">
                                    <img src="../assets/uploads/profiles/<?= $rider['profile_img'] ?>" 
                                         class="w-32 h-32 rounded-3xl object-cover border-4 border-slate-50 shadow-md group-hover:opacity-80 transition" 
                                         onerror="this.src='https://ui-avatars.com/api/?name=Rider&background=10b981&color=fff'">
                                    <label class="absolute inset-0 flex items-center justify-center cursor-pointer opacity-0 group-hover:opacity-100 transition">
                                        <span class="bg-white/90 p-2 rounded-full shadow-lg text-brand-green"><i class="bi bi-camera-fill fs-5"></i></span>
                                        <input type="file" name="profile_img" class="hidden" onchange="document.getElementById('imgForm').submit()">
                                    </label>
                                </div>
                                <h3 class="font-bold text-slate-800 mt-4 text-lg"><?= htmlspecialchars($rider['first_name']); ?> (Rider)</h3>
                                <p class="text-slate-400 text-xs mt-1">@<?= htmlspecialchars($rider['username']); ?></p>
                                <input type="hidden" name="update_profile" value="1">
                                <input type="hidden" name="first_name" value="<?= $rider['first_name'] ?>">
                                <input type="hidden" name="last_name" value="<?= $rider['last_name'] ?>">
                                <input type="hidden" name="phone" value="<?= $rider['phone'] ?>">
                            </form>
                            <div class="mt-8 pt-8 border-t border-slate-50">
                                <div class="bg-emerald-50 p-4 rounded-2xl flex items-center gap-3">
                                    <i class="bi bi-patch-check-fill text-brand-green text-xl"></i>
                                    <div class="text-left">
                                        <p class="text-[10px] font-bold text-emerald-600 uppercase tracking-widest leading-none">สถานะบัญชี</p>
                                        <p class="text-sm font-bold text-emerald-700">Verified Rider</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 p-8 md:p-10">
                            <h3 class="text-lg font-bold text-slate-800 mb-8 flex items-center gap-3"><i class="bi bi-person-lines-fill text-brand-green"></i> ข้อมูลพื้นฐาน</h3>
                            <form action="profile.php" method="POST">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
                                    <div>
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">ชื่อจริง</label>
                                        <input type="text" name="first_name" value="<?= htmlspecialchars($rider['first_name']); ?>" required class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 focus:ring-2 focus:ring-brand-green outline-none font-medium">
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">นามสกุล</label>
                                        <input type="text" name="last_name" value="<?= htmlspecialchars($rider['last_name']); ?>" required class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 focus:ring-2 focus:ring-brand-green outline-none font-medium">
                                    </div>
                                </div>
                                <div class="mb-8">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">เบอร์โทรศัพท์สำหรับติดต่อลูกค้า</label>
                                    <input type="text" name="phone" value="<?= htmlspecialchars($rider['phone']); ?>" required class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 focus:ring-2 focus:ring-brand-green outline-none font-medium">
                                </div>
                                <button type="submit" name="update_profile" class="w-full bg-slate-900 text-white font-bold py-5 rounded-2xl hover:bg-slate-800 transition shadow-xl shadow-slate-100">
                                    บันทึกการเปลี่ยนแปลง
                                </button>
                            </form>
                        </div>

                        <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 p-8 md:p-10">
                            <h3 class="text-lg font-bold text-slate-800 mb-8 flex items-center gap-3"><i class="bi bi-shield-lock text-brand-green"></i> ความปลอดภัยและรหัสผ่าน</h3>
                            <form action="profile.php" method="POST">
                                <div class="mb-6">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">รหัสผ่านปัจจุบัน</label>
                                    <input type="password" name="current_password" required placeholder="••••••••" class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 focus:ring-2 focus:ring-brand-green outline-none">
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-8">
                                    <div>
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">รหัสผ่านใหม่</label>
                                        <input type="password" name="new_password" required placeholder="••••••••" class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 focus:ring-2 focus:ring-brand-green outline-none">
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">ยืนยันรหัสผ่านใหม่</label>
                                        <input type="password" name="confirm_password" required placeholder="••••••••" class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 focus:ring-2 focus:ring-brand-green outline-none">
                                    </div>
                                </div>
                                <button type="submit" name="change_password" class="w-full border-2 border-slate-100 text-slate-600 font-bold py-5 rounded-2xl hover:bg-slate-50 transition">
                                    เปลี่ยนรหัสผ่านใหม่
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

</body>
</html>