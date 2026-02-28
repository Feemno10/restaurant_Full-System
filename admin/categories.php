<?php
// ไฟล์: admin/categories.php
session_start();

// 1. ตรวจสอบสิทธิ์ (Security)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';
$message = '';

// 2. จัดการคำสั่งเพิ่ม/แก้ไข/ลบ (CRUD Logic - ข้อ 3.1.5)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // --- เพิ่มหมวดหมู่ใหม่ ---
    if (isset($_POST['add_category'])) {
        $name = trim($_POST['category_name']);
        if (!empty($name)) {
            $stmt = $conn->prepare("INSERT INTO restaurant_categories (category_name) VALUES (:name)");
            $stmt->execute([':name' => $name]);
            $message = "<div class='bg-emerald-500 text-white p-3 rounded-xl mb-4 shadow-md'>เพิ่มหมวดหมู่ '$name' สำเร็จ!</div>";
        }
    }

    // --- แก้ไขหมวดหมู่ ---
    if (isset($_POST['edit_category'])) {
        $id = $_POST['category_id'];
        $name = trim($_POST['category_name']);
        $stmt = $conn->prepare("UPDATE restaurant_categories SET category_name = :name WHERE category_id = :id");
        $stmt->execute([':name' => $name, ':id' => $id]);
        $message = "<div class='bg-blue-500 text-white p-3 rounded-xl mb-4 shadow-md'>แก้ไขข้อมูลเรียบร้อยแล้ว</div>";
    }

    // --- ลบหมวดหมู่ ---
    if (isset($_POST['delete_category'])) {
        $id = $_POST['category_id'];
        try {
            $stmt = $conn->prepare("DELETE FROM restaurant_categories WHERE category_id = :id");
            $stmt->execute([':id' => $id]);
            $message = "<div class='bg-rose-500 text-white p-3 rounded-xl mb-4 shadow-md'>ลบหมวดหมู่เรียบร้อยแล้ว</div>";
        } catch(PDOException $e) {
            $message = "<div class='bg-rose-500 text-white p-3 rounded-xl mb-4 shadow-md'>ไม่สามารถลบได้ เนื่องจากมีร้านอาหารใช้งานหมวดหมู่นี้อยู่</div>";
        }
    }
}

// 3. ดึงข้อมูลหมวดหมู่ทั้งหมดมาแสดงผล
$stmt = $conn->prepare("SELECT * FROM restaurant_categories ORDER BY category_id DESC");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories | FoodDelivery Admin</title>
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
            <a href="categories.php" class="nav-link active"><i class="bi bi-tags mr-3"></i> หมวดหมู่ร้านอาหาร</a>
            <a href="manage_users.php" class="nav-link"><i class="bi bi-people mr-3"></i> จัดการผู้ใช้งาน</a>
            <p class="px-6 mt-6 mb-2 text-xs font-semibold tracking-wider text-slate-400 uppercase">Settings</p>
            <a href="profile.php" class="nav-link"><i class="bi bi-person-badge mr-3"></i> โปรไฟล์ส่วนตัว</a>
            <a href="../logout.php" class="mt-2 text-rose-500 nav-link hover:bg-rose-50"><i class="bi bi-box-arrow-right mr-3"></i> ออกจากระบบ</a>
        </div>
    </aside>

    <div class="flex flex-col flex-1 h-full overflow-hidden">
        <header class="flex items-center justify-between flex-shrink-0 h-16 px-8 bg-white shadow-sm">
            <h1 class="text-lg font-bold text-slate-700">Categories Management</h1>
            <div class="flex items-center gap-4">
                <i class="text-xl bi bi-bell text-slate-400"></i>
                <div class="h-6 w-px bg-slate-200"></div>
                <div class="flex items-center gap-2">
                    <div class="flex items-center justify-center w-8 h-8 text-sm font-bold text-white rounded-full bg-brand-pink">AD</div>
                    <span class="text-sm font-medium text-slate-700 hidden sm:block"><?= htmlspecialchars($_SESSION['full_name']); ?></span>
                </div>
            </div>
        </header>

        <main class="flex-1 p-8 overflow-y-auto">
            <div class="max-w-5xl mx-auto">
                <div class="flex justify-between items-end mb-8">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-800">หมวดหมู่ร้านอาหาร</h2>
                        <p class="text-slate-500 text-sm">สร้างและจัดการประเภทร้านอาหารเพื่อช่วยให้ลูกค้าค้นหาได้ง่ายขึ้น</p>
                    </div>
                    <button onclick="toggleModal('addModal')" class="bg-brand-pink text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-lg shadow-rose-200 hover:bg-rose-600 transition flex items-center gap-2">
                        <i class="bi bi-plus-lg"></i> เพิ่มหมวดหมู่ใหม่
                    </button>
                </div>

                <?= $message; ?>

                <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 border-b border-slate-100">
                            <tr>
                                <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase">ID</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase">ชื่อหมวดหมู่</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase text-right">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php foreach ($categories as $cat): ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4 text-sm text-slate-500">#<?= $cat['category_id'] ?></td>
                                <td class="px-6 py-4 font-bold text-slate-700"><?= htmlspecialchars($cat['category_name']) ?></td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button onclick="openEditModal(<?= $cat['category_id'] ?>, '<?= htmlspecialchars($cat['category_name']) ?>')" class="p-2 text-blue-500 hover:bg-blue-50 rounded-lg transition"><i class="bi bi-pencil-square"></i></button>
                                        <form action="categories.php" method="POST" onsubmit="return confirm('ยืนยันการลบหมวดหมู่นี้?')">
                                            <input type="hidden" name="category_id" value="<?= $cat['category_id'] ?>">
                                            <button type="submit" name="delete_category" class="p-2 text-rose-500 hover:bg-rose-50 rounded-lg transition"><i class="bi bi-trash3"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($categories)): ?>
                                <tr><td colspan="3" class="px-6 py-10 text-center text-slate-400">ยังไม่มีข้อมูลหมวดหมู่ในระบบ</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <div id="addModal" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-8 transform transition-all scale-95 opacity-0 duration-300" id="addContent">
            <h3 class="text-xl font-bold mb-6 text-slate-800">เพิ่มหมวดหมู่ใหม่</h3>
            <form action="categories.php" method="POST">
                <label class="block text-xs font-bold text-slate-400 uppercase mb-2">ชื่อหมวดหมู่</label>
                <input type="text" name="category_name" required placeholder="เช่น ชาบู / คาเฟ่" class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 mb-6 focus:ring-2 focus:ring-brand-pink outline-none">
                <div class="flex gap-3">
                    <button type="button" onclick="toggleModal('addModal')" class="flex-1 py-3 font-bold text-slate-400 hover:bg-slate-50 rounded-xl transition">ยกเลิก</button>
                    <button type="submit" name="add_category" class="flex-1 py-3 font-bold bg-slate-900 text-white rounded-xl hover:bg-slate-800 transition shadow-lg shadow-slate-200">บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editModal" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-8 transform transition-all scale-95 opacity-0 duration-300" id="editContent">
            <h3 class="text-xl font-bold mb-6 text-slate-800">แก้ไขชื่อหมวดหมู่</h3>
            <form action="categories.php" method="POST">
                <input type="hidden" name="category_id" id="edit_id">
                <label class="block text-xs font-bold text-slate-400 uppercase mb-2">ชื่อหมวดหมู่ใหม่</label>
                <input type="text" name="category_name" id="edit_name" required class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 mb-6 focus:ring-2 focus:ring-brand-pink outline-none">
                <div class="flex gap-3">
                    <button type="button" onclick="toggleModal('editModal')" class="flex-1 py-3 font-bold text-slate-400 hover:bg-slate-50 rounded-xl transition">ยกเลิก</button>
                    <button type="submit" name="edit_category" class="flex-1 py-3 font-bold bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition">อัปเดตข้อมูล</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleModal(modalId) {
            const modal = document.getElementById(modalId);
            const content = modal.querySelector('div');
            if (modal.classList.contains('hidden')) {
                modal.classList.remove('hidden');
                setTimeout(() => { content.classList.remove('scale-95', 'opacity-0'); }, 10);
            } else {
                content.classList.add('scale-95', 'opacity-0');
                setTimeout(() => { modal.classList.add('hidden'); }, 300);
            }
        }

        function openEditModal(id, name) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            toggleModal('editModal');
        }
    </script>
</body>
</html>