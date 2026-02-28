<?php
// ไฟล์: admin/manage_users.php
session_start();

// 1. ตรวจสอบสิทธิ์ (Security)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';
$message = '';

// 2. จัดการคำสั่ง CRUD (Create, Update, Delete - ข้อ 3.1.6)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // --- เพิ่มผู้ใช้งานใหม่ ---
    if (isset($_POST['add_user'])) {
        $username = trim($_POST['username']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'];
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $phone = trim($_POST['phone']);
        $status = 'approved'; // แอดมินสร้างเองให้ผ่านเลย

        try {
            $stmt = $conn->prepare("INSERT INTO users (username, password, role, first_name, last_name, phone, status) 
                                    VALUES (:u, :p, :r, :f, :l, :ph, :s)");
            $stmt->execute([':u'=>$username, ':p'=>$password, ':r'=>$role, ':f'=>$first_name, ':l'=>$last_name, ':ph'=>$phone, ':s'=>$status]);
            $message = "<div class='bg-emerald-500 text-white p-3 rounded-xl mb-4 shadow-md'>สร้างผู้ใช้งานใหม่สำเร็จ!</div>";
        } catch(PDOException $e) {
            $message = "<div class='bg-rose-500 text-white p-3 rounded-xl mb-4 shadow-md'>ข้อผิดพลาด: Username นี้อาจมีผู้ใช้แล้ว</div>";
        }
    }

    // --- แก้ไขข้อมูลผู้ใช้งาน ---
    if (isset($_POST['edit_user'])) {
        $id = $_POST['user_id'];
        $f_name = trim($_POST['first_name']);
        $l_name = trim($_POST['last_name']);
        $phone = trim($_POST['phone']);
        $role = $_POST['role'];

        $stmt = $conn->prepare("UPDATE users SET first_name = :f, last_name = :l, phone = :ph, role = :r WHERE user_id = :id");
        $stmt->execute([':f'=>$f_name, ':l'=>$l_name, ':ph'=>$phone, ':r'=>$role, ':id'=>$id]);
        $message = "<div class='bg-blue-500 text-white p-3 rounded-xl mb-4 shadow-md'>อัปเดตข้อมูลสำเร็จ</div>";
    }

    // --- ลบผู้ใช้งาน ---
    if (isset($_POST['delete_user'])) {
        $id = $_POST['user_id'];
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = :id AND role != 'admin'");
        $stmt->execute([':id' => $id]);
        $message = "<div class='bg-rose-500 text-white p-3 rounded-xl mb-4 shadow-md'>ลบผู้ใช้งานเรียบร้อยแล้ว</div>";
    }
}

// 3. ดึงข้อมูลผู้ใช้งาน (เฉพาะ ร้านอาหาร และ คนขับ ตามข้อ 3.1.6)
$stmt = $conn->prepare("SELECT * FROM users WHERE role IN ('restaurant', 'rider') ORDER BY user_id DESC");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users | FoodDelivery Admin</title>
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

    <aside class="flex-shrink-0 flex flex-col w-64 h-full bg-white shadow-xl">
        <div class="flex items-center h-16 px-6 text-white bg-brand-pink"><i class="text-xl bi bi-shop me-2"></i><span class="text-lg font-bold">FoodAdmin.</span></div>
        <div class="flex-1 py-4 overflow-y-auto">
            <p class="px-6 mb-2 text-xs font-semibold tracking-wider text-slate-400 uppercase">Main Menu</p>
            <a href="index.php" class="nav-link"><i class="bi bi-grid-1x2-fill mr-3"></i> Dashboard</a>
            <a href="approve_users.php" class="nav-link"><i class="bi bi-person-check mr-3"></i> อนุมัติการใช้งาน</a>
            <p class="px-6 mt-6 mb-2 text-xs font-semibold tracking-wider text-slate-400 uppercase">Management</p>
            <a href="categories.php" class="nav-link"><i class="bi bi-tags mr-3"></i> หมวดหมู่ร้านอาหาร</a>
            <a href="manage_users.php" class="nav-link active"><i class="bi bi-people mr-3"></i> จัดการผู้ใช้งาน</a>
            <p class="px-6 mt-6 mb-2 text-xs font-semibold tracking-wider text-slate-400 uppercase">Settings</p>
            <a href="profile.php" class="nav-link"><i class="bi bi-person-badge mr-3"></i> โปรไฟล์ส่วนตัว</a>
            <a href="../logout.php" class="mt-2 text-rose-500 nav-link hover:bg-rose-50"><i class="bi bi-box-arrow-right mr-3"></i> ออกจากระบบ</a>
        </div>
    </aside>

    <div class="flex flex-col flex-1 h-full overflow-hidden">
        <header class="flex items-center justify-between flex-shrink-0 h-16 px-8 bg-white shadow-sm">
            <h1 class="text-lg font-bold text-slate-700">User Management</h1>
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <div class="flex items-center justify-center w-8 h-8 text-sm font-bold text-white rounded-full bg-brand-pink">AD</div>
                    <span class="text-sm font-medium text-slate-700 hidden sm:block"><?= htmlspecialchars($_SESSION['full_name']); ?></span>
                </div>
            </div>
        </header>

        <main class="flex-1 p-8 overflow-y-auto">
            <div class="max-w-6xl mx-auto">
                <div class="flex justify-between items-end mb-8">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-800">จัดการผู้ใช้งานร้านอาหารและคนขับ</h2>
                        <p class="text-slate-500 text-sm">สร้างและแก้ไขข้อมูลผู้ดูแลร้านและผู้ส่งอาหารในระบบ</p>
                    </div>
                    <button onclick="toggleModal('addModal')" class="bg-brand-pink text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-lg shadow-rose-200 hover:bg-rose-600 transition flex items-center gap-2">
                        <i class="bi bi-person-plus-fill"></i> เพิ่มผู้ใช้ใหม่
                    </button>
                </div>

                <?= $message; ?>

                <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 border-b border-slate-100">
                            <tr>
                                <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase">ชื่อ-นามสกุล</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase">ประเภท (Role)</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase">เบอร์โทรศัพท์</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase">สถานะ</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase text-right">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php foreach ($users as $u): ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4 font-bold text-slate-700">
                                    <?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?>
                                    <div class="text-xs font-normal text-slate-400">@<?= htmlspecialchars($u['username']) ?></div>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold <?= $u['role'] == 'restaurant' ? 'bg-indigo-100 text-indigo-600' : 'bg-emerald-100 text-emerald-600' ?>">
                                        <?= $u['role'] == 'restaurant' ? 'ร้านอาหาร' : 'ผู้ส่งอาหาร' ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-500"><?= htmlspecialchars($u['phone']) ?></td>
                                <td class="px-6 py-4">
                                    <span class="text-xs font-bold <?= $u['status'] == 'approved' ? 'text-emerald-500' : 'text-amber-500' ?>">
                                        ● <?= $u['status'] == 'approved' ? 'ใช้งานได้' : 'รออนุมัติ' ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button onclick="openEditModal(<?= htmlspecialchars(json_encode($u)) ?>)" class="p-2 text-blue-500 hover:bg-blue-50 rounded-lg transition"><i class="bi bi-pencil-square"></i></button>
                                        <form action="manage_users.php" method="POST" onsubmit="return confirm('ยืนยันการลบผู้ใช้นี้?')">
                                            <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                            <button type="submit" name="delete_user" class="p-2 text-rose-500 hover:bg-rose-50 rounded-lg transition"><i class="bi bi-trash3"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <div id="addModal" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg p-8 transform transition-all" id="addContent">
            <h3 class="text-xl font-bold mb-6">เพิ่มผู้ใช้งานใหม่ (Restaurant/Rider)</h3>
            <form action="manage_users.php" method="POST" class="grid grid-cols-2 gap-4">
                <div class="col-span-2"><label class="text-xs font-bold text-slate-400 uppercase">Username</label><input type="text" name="username" required class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 focus:ring-2 focus:ring-brand-pink outline-none"></div>
                <div class="col-span-2"><label class="text-xs font-bold text-slate-400 uppercase">Password</label><input type="password" name="password" required class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 focus:ring-2 focus:ring-brand-pink outline-none"></div>
                <div><label class="text-xs font-bold text-slate-400 uppercase">ชื่อจริง</label><input type="text" name="first_name" required class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 focus:ring-2 focus:ring-brand-pink outline-none"></div>
                <div><label class="text-xs font-bold text-slate-400 uppercase">นามสกุล</label><input type="text" name="last_name" required class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 focus:ring-2 focus:ring-brand-pink outline-none"></div>
                <div><label class="text-xs font-bold text-slate-400 uppercase">เบอร์โทร</label><input type="text" name="phone" required class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 focus:ring-2 focus:ring-brand-pink outline-none"></div>
                <div><label class="text-xs font-bold text-slate-400 uppercase">ประเภท</label>
                    <select name="role" class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 focus:ring-2 focus:ring-brand-pink outline-none">
                        <option value="restaurant">ร้านอาหาร</option>
                        <option value="rider">ผู้ส่งอาหาร</option>
                    </select>
                </div>
                <div class="col-span-2 flex gap-3 mt-4">
                    <button type="button" onclick="toggleModal('addModal')" class="flex-1 py-3 font-bold text-slate-400 hover:bg-slate-50 rounded-xl transition">ยกเลิก</button>
                    <button type="submit" name="add_user" class="flex-1 py-3 font-bold bg-slate-900 text-white rounded-xl hover:bg-slate-800 transition">บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editModal" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg p-8" id="editContent">
            <h3 class="text-xl font-bold mb-6">แก้ไขข้อมูลผู้ใช้งาน</h3>
            <form action="manage_users.php" method="POST" class="grid grid-cols-2 gap-4">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div><label class="text-xs font-bold text-slate-400 uppercase">ชื่อจริง</label><input type="text" name="first_name" id="edit_f" required class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-brand-pink"></div>
                <div><label class="text-xs font-bold text-slate-400 uppercase">นามสกุล</label><input type="text" name="last_name" id="edit_l" required class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-brand-pink"></div>
                <div class="col-span-2"><label class="text-xs font-bold text-slate-400 uppercase">เบอร์โทร</label><input type="text" name="phone" id="edit_p" required class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-brand-pink"></div>
                <div class="col-span-2"><label class="text-xs font-bold text-slate-400 uppercase">ประเภท</label>
                    <select name="role" id="edit_r" class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-brand-pink">
                        <option value="restaurant">ร้านอาหาร</option>
                        <option value="rider">ผู้ส่งอาหาร</option>
                    </select>
                </div>
                <div class="col-span-2 flex gap-3 mt-4">
                    <button type="button" onclick="toggleModal('editModal')" class="flex-1 py-3 font-bold text-slate-400 rounded-xl">ยกเลิก</button>
                    <button type="submit" name="edit_user" class="flex-1 py-3 font-bold bg-blue-600 text-white rounded-xl">บันทึกการแก้ไข</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleModal(id) {
            const m = document.getElementById(id);
            m.classList.toggle('hidden');
        }
        function openEditModal(user) {
            document.getElementById('edit_user_id').value = user.user_id;
            document.getElementById('edit_f').value = user.first_name;
            document.getElementById('edit_l').value = user.last_name;
            document.getElementById('edit_p').value = user.phone;
            document.getElementById('edit_r').value = user.role;
            toggleModal('editModal');
        }
    </script>
</body>
</html>