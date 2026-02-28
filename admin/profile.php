<?php
// ไฟล์: admin/profile.php
session_start();

// 1. ตรวจสอบสิทธิ์ (Security)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';
$user_id = $_SESSION['user_id'];
$message = '';

// 2. จัดการเมื่อมีการกดปุ่ม Submit (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // --- ส่วนที่ 1: แก้ไขข้อมูลส่วนตัว (ข้อ 3.1.3) ---
    if (isset($_POST['update_profile'])) {
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $phone = trim($_POST['phone']);
        $profile_img = $_SESSION['profile_img'];

        // จัดการอัปโหลดรูปภาพ
        if (isset($_FILES['profile_img']) && $_FILES['profile_img']['error'] == 0) {
            $upload_dir = '../assets/uploads/profiles/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            $ext = strtolower(pathinfo($_FILES['profile_img']['name'], PATHINFO_EXTENSION));
            $new_file_name = 'admin_' . $user_id . '_' . time() . '.' . $ext;
            
            if (move_uploaded_file($_FILES['profile_img']['tmp_name'], $upload_dir . $new_file_name)) {
                $profile_img = $new_file_name;
            }
        }

        try {
            $stmt = $conn->prepare("UPDATE users SET first_name = :f, last_name = :l, phone = :p, profile_img = :img WHERE user_id = :id");
            $stmt->execute([':f' => $first_name, ':l' => $last_name, ':p' => $phone, ':img' => $profile_img, ':id' => $user_id]);
            
            $_SESSION['full_name'] = $first_name . ' ' . $last_name;
            $_SESSION['profile_img'] = $profile_img;
            $message = "<div class='bg-emerald-500 text-white p-4 rounded-xl mb-6 shadow-lg'><i class='bi bi-check-circle-fill mr-2'></i>อัปเดตข้อมูลสำเร็จ!</div>";
        } catch(PDOException $e) { $message = "<div class='bg-rose-500 text-white p-4 rounded-xl mb-6 shadow-lg'>Error: " . $e->getMessage() . "</div>"; }
    }

    // --- ส่วนที่ 2: เปลี่ยนรหัสผ่าน (ข้อ 3.1.4) ---
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
                    $message = "<div class='bg-emerald-500 text-white p-4 rounded-xl mb-6 shadow-lg'><i class='bi bi-shield-check mr-2'></i>เปลี่ยนรหัสผ่านเรียบร้อย!</div>";
                } else { $message = "<div class='bg-rose-500 text-white p-4 rounded-xl mb-6 shadow-lg'>รหัสผ่านใหม่ไม่ตรงกัน</div>"; }
            } else { $message = "<div class='bg-rose-500 text-white p-4 rounded-xl mb-6 shadow-lg'>รหัสผ่านปัจจุบันไม่ถูกต้อง</div>"; }
        } catch(PDOException $e) { $message = "<div class='bg-rose-500 text-white p-4 rounded-xl mb-6 shadow-lg'>Error: " . $e->getMessage() . "</div>"; }
    }
}

// 3. ดึงข้อมูลล่าสุดมาแสดง
$stmt_user = $conn->prepare("SELECT * FROM users WHERE user_id = :id");
$stmt_user->execute([':id' => $user_id]);
$admin = $stmt_user->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings | FoodDelivery Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['Prompt', 'sans-serif'] }, colors: { brand: { pink: '#f1416c' }, sidebar: '#ffffff', body: '#f4f6f9' } } } }
    </script>
    <style>
        .nav-link { display: flex; align-items: center; padding: 0.75rem 1.5rem; color: #64748b; font-size: 0.875rem; font-weight: 500; border-left: 3px solid transparent; transition: 0.2s; }
        .nav-link:hover, .nav-link.active { color: #f1416c; background-color: #fff1f2; border-left-color: #f1416c; }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 bg-slate-50">

    <aside class="flex-shrink-0 flex flex-col w-64 h-full transition-transform duration-300 transform bg-white shadow-xl">
        <div class="flex items-center h-16 px-6 text-white bg-brand-pink"><i class="text-xl bi bi-shop me-2"></i><span class="text-lg font-bold">FoodAdmin.</span></div>
        <div class="flex-1 py-4 overflow-y-auto">
            <p class="px-6 mb-2 text-xs font-semibold tracking-wider text-slate-400 uppercase">Main Menu</p>
            <a href="index.php" class="nav-link"><i class="bi bi-grid-1x2-fill mr-3"></i> Dashboard</a>
            <a href="approve_users.php" class="nav-link"><i class="bi bi-person-check mr-3"></i> อนุมัติการใช้งาน</a>
            <p class="px-6 mt-6 mb-2 text-xs font-semibold tracking-wider text-slate-400 uppercase">Settings</p>
            <a href="profile.php" class="nav-link active"><i class="bi bi-person-badge mr-3"></i> โปรไฟล์ส่วนตัว</a>
            <a href="../logout.php" class="mt-2 text-rose-500 nav-link hover:bg-rose-50"><i class="bi bi-box-arrow-right mr-3"></i> ออกจากระบบ</a>
        </div>
    </aside>

    <div class="flex flex-col flex-1 h-full overflow-hidden">
        <header class="flex items-center justify-between flex-shrink-0 h-16 px-8 bg-white shadow-sm">
            <h1 class="text-lg font-bold text-slate-700">Settings</h1>
            <div class="flex items-center gap-4">
                <i class="text-xl bi bi-bell text-slate-400"></i>
                <div class="h-6 w-px bg-slate-200"></div>
                <div class="flex items-center gap-2" id="profileBtn">
                    <div class="flex items-center justify-center w-8 h-8 text-sm font-bold text-white rounded-full bg-brand-pink">AD</div>
                    <span class="text-sm font-medium text-slate-700"><?= htmlspecialchars($_SESSION['full_name']); ?></span>
                    <i class="text-xs bi bi-chevron-down text-slate-400"></i>
                </div>
            </div>
        </header>

        <main class="flex-1 p-8 overflow-y-auto">
            <div class="max-w-4xl mx-auto">
                <div class="mb-8 flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-800">จัดการข้อมูลส่วนตัว</h2>
                        <p class="text-slate-500 text-sm">แก้ไขข้อมูลและรหัสผ่านเพื่อความปลอดภัยของระบบ</p>
                    </div>
                    <button class="bg-brand-pink text-white px-5 py-2.5 rounded-xl text-sm font-medium shadow-lg shadow-rose-200 flex items-center gap-2">
                        <i class="bi bi-download"></i> ดาวน์โหลดสำเนาโปรไฟล์
                    </button>
                </div>

                <?= $message; ?>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 text-center">
                        <form action="profile.php" method="POST" enctype="multipart/form-data" id="imgForm">
                            <div class="relative inline-block mb-4 group">
                                <img src="../assets/uploads/profiles/<?= $admin['profile_img'] ?>" class="w-32 h-32 rounded-3xl object-cover border-4 border-slate-50 shadow-md group-hover:opacity-75 transition" onerror="this.src='https://ui-avatars.com/api/?name=Admin&background=f1416c&color=fff'">
                                <label class="absolute inset-0 flex items-center justify-center cursor-pointer opacity-0 group-hover:opacity-100 transition">
                                    <span class="bg-white/90 p-2 rounded-full shadow-lg text-brand-pink"><i class="bi bi-camera-fill fs-5"></i></span>
                                    <input type="file" name="profile_img" class="hidden" onchange="document.getElementById('imgForm').submit()">
                                </label>
                            </div>
                            <h3 class="font-bold text-slate-800"><?= htmlspecialchars($admin['first_name']); ?> (Admin)</h3>
                            <p class="text-slate-400 text-xs mt-1">@<?= htmlspecialchars($admin['username']); ?></p>
                            <input type="hidden" name="update_profile" value="1">
                            <input type="hidden" name="first_name" value="<?= $admin['first_name'] ?>">
                            <input type="hidden" name="last_name" value="<?= $admin['last_name'] ?>">
                            <input type="hidden" name="phone" value="<?= $admin['phone'] ?>">
                        </form>
                    </div>

                    <div class="md:col-span-2 space-y-6">
                        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-8">
                            <h3 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-2"><i class="bi bi-person-lines-fill text-brand-pink"></i> ข้อมูลทั่วไป</h3>
                            <form action="profile.php" method="POST">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                                    <div><label class="text-xs font-bold text-slate-400 uppercase mb-1 block">ชื่อจริง</label><input type="text" name="first_name" value="<?= htmlspecialchars($admin['first_name']); ?>" class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 focus:ring-2 focus:ring-brand-pink outline-none"></div>
                                    <div><label class="text-xs font-bold text-slate-400 uppercase mb-1 block">นามสกุล</label><input type="text" name="last_name" value="<?= htmlspecialchars($admin['last_name']); ?>" class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 focus:ring-2 focus:ring-brand-pink outline-none"></div>
                                </div>
                                <div class="mb-6"><label class="text-xs font-bold text-slate-400 uppercase mb-1 block">เบอร์โทรศัพท์</label><input type="text" name="phone" value="<?= htmlspecialchars($admin['phone']); ?>" class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 focus:ring-2 focus:ring-brand-pink outline-none"></div>
                                <button type="submit" name="update_profile" class="w-full bg-slate-900 text-white font-bold py-3 rounded-xl hover:bg-slate-800 transition">บันทึกการเปลี่ยนแปลง</button>
                            </form>
                        </div>

                        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-8">
                            <h3 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-2"><i class="bi bi-key-fill text-brand-pink"></i> ความปลอดภัย</h3>
                            <form action="profile.php" method="POST">
                                <div class="mb-4"><label class="text-xs font-bold text-slate-400 uppercase mb-1 block">รหัสผ่านปัจจุบัน</label><input type="password" name="current_password" required class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 focus:ring-2 focus:ring-brand-pink outline-none"></div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                                    <div><label class="text-xs font-bold text-slate-400 uppercase mb-1 block">รหัสผ่านใหม่</label><input type="password" name="new_password" required class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 focus:ring-2 focus:ring-brand-pink outline-none"></div>
                                    <div><label class="text-xs font-bold text-slate-400 uppercase mb-1 block">ยืนยันรหัสผ่านใหม่</label><input type="password" name="confirm_password" required class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 focus:ring-2 focus:ring-brand-pink outline-none"></div>
                                </div>
                                <button type="submit" name="change_password" class="w-full border-2 border-slate-100 text-slate-700 font-bold py-3 rounded-xl hover:bg-slate-50 transition">เปลี่ยนรหัสผ่านใหม่</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>