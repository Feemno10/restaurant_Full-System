<?php
// ไฟล์: admin/approve_users.php
session_start();

// 1. ตรวจสอบสิทธิ์ (Security)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';
$message = '';

// 2. จัดการคำสั่ง อนุมัติ/ยกเลิก (Action Logic - ข้อ 3.1.7 & 3.1.8)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $action = $_GET['action'];
    $new_status = '';

    if ($action == 'approve') $new_status = 'approved';
    elseif ($action == 'reject') $new_status = 'rejected';
    elseif ($action == 'pending') $new_status = 'pending';

    if (!empty($new_status)) {
        try {
            $stmt = $conn->prepare("UPDATE users SET status = :s WHERE user_id = :id AND role IN ('restaurant', 'rider')");
            $stmt->execute([':s' => $new_status, ':id' => $id]);
            $message = "<div class='bg-emerald-500 text-white p-4 rounded-xl mb-6 shadow-lg'><i class='bi bi-check-circle-fill mr-2'></i>ดำเนินการเปลี่ยนสถานะเรียบร้อยแล้ว</div>";
        } catch(PDOException $e) {
            $message = "<div class='bg-rose-500 text-white p-4 rounded-xl mb-6 shadow-lg'>Error: " . $e->getMessage() . "</div>";
        }
    }
}

// 3. ดึงข้อมูลผู้ใช้ที่รอการอนุมัติ (Pending)
$stmt_pending = $conn->prepare("SELECT * FROM users WHERE role IN ('restaurant', 'rider') AND status = 'pending' ORDER BY created_at DESC");
$stmt_pending->execute();
$pending_users = $stmt_pending->fetchAll(PDO::FETCH_ASSOC);

// 4. ดึงข้อมูลผู้ใช้ทั้งหมด (ยกเว้นแอดมินและลูกค้า) เพื่อใช้ในการยกเลิกสิทธิ์
$stmt_all = $conn->prepare("SELECT * FROM users WHERE role IN ('restaurant', 'rider') AND status != 'pending' ORDER BY status ASC");
$stmt_all->execute();
$active_users = $stmt_all->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Users | FoodDelivery Admin</title>
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
            <a href="approve_users.php" class="nav-link active"><i class="bi bi-person-check mr-3"></i> อนุมัติการใช้งาน</a>
            <p class="px-6 mt-6 mb-2 text-xs font-semibold tracking-wider text-slate-400 uppercase">Management</p>
            <a href="categories.php" class="nav-link"><i class="bi bi-tags mr-3"></i> หมวดหมู่ร้านอาหาร</a>
            <a href="manage_users.php" class="nav-link"><i class="bi bi-people mr-3"></i> จัดการผู้ใช้งาน</a>
            <p class="px-6 mt-6 mb-2 text-xs font-semibold tracking-wider text-slate-400 uppercase">Settings</p>
            <a href="profile.php" class="nav-link"><i class="bi bi-person-badge mr-3"></i> โปรไฟล์ส่วนตัว</a>
            <a href="../logout.php" class="mt-2 text-rose-500 nav-link hover:bg-rose-50"><i class="bi bi-box-arrow-right mr-3"></i> ออกจากระบบ</a>
        </div>
    </aside>

    <div class="flex flex-col flex-1 h-full overflow-hidden">
        <header class="flex items-center justify-between flex-shrink-0 h-16 px-8 bg-white shadow-sm">
            <h1 class="text-lg font-bold text-slate-700">User Approvals</h1>
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <div class="flex items-center justify-center w-8 h-8 text-sm font-bold text-white rounded-full bg-brand-pink">AD</div>
                    <span class="text-sm font-medium text-slate-700 hidden sm:block"><?= htmlspecialchars($_SESSION['full_name']); ?></span>
                </div>
            </div>
        </header>

        <main class="flex-1 p-8 overflow-y-auto">
            <div class="max-w-6xl mx-auto">
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-slate-800">อนุมัติและจัดการสิทธิ์ผู้ใช้งาน</h2>
                    <p class="text-slate-500 text-sm">ตรวจสอบรายชื่อร้านอาหารและผู้ส่งอาหารที่ขอเข้าใช้งานระบบ</p>
                </div>

                <?= $message; ?>

                <div class="mb-10">
                    <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                        <i class="bi bi-hourglass-split text-amber-500"></i> รายการที่รอการตรวจสอบ
                        <span class="bg-amber-100 text-amber-600 text-xs px-2 py-1 rounded-full"><?= count($pending_users) ?> รายการ</span>
                    </h3>
                    
                    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                        <table class="w-full text-left">
                            <thead class="bg-slate-50 border-b border-slate-100">
                                <tr>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase">ชื่อ-นามสกุล</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase">ประเภท</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase">วันที่สมัคร</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase text-right">ดำเนินการ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <?php foreach ($pending_users as $u): ?>
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-6 py-4 font-bold text-slate-700">
                                        <?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?>
                                        <div class="text-xs font-normal text-slate-400">Tel: <?= htmlspecialchars($u['phone']) ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase <?= $u['role'] == 'restaurant' ? 'bg-indigo-100 text-indigo-600' : 'bg-emerald-100 text-emerald-600' ?>">
                                            <?= $u['role'] == 'restaurant' ? 'ร้านอาหาร' : 'Rider' ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-500"><?= date('d/m/Y H:i', strtotime($u['created_at'])) ?></td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            <a href="?action=approve&id=<?= $u['user_id'] ?>" class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-1.5 rounded-lg text-xs font-bold transition shadow-sm" onclick="return confirm('ยืนยันการอนุมัติ?')">อนุมัติ</a>
                                            <a href="?action=reject&id=<?= $u['user_id'] ?>" class="bg-rose-500 hover:bg-rose-600 text-white px-4 py-1.5 rounded-lg text-xs font-bold transition shadow-sm" onclick="return confirm('ยืนยันการปฏิเสธ?')">ปฏิเสธ</a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($pending_users)): ?>
                                    <tr><td colspan="4" class="px-6 py-10 text-center text-slate-400 italic">ไม่มีรายการรออนุมัติในขณะนี้</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div>
                    <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                        <i class="bi bi-shield-check text-brand-pink"></i> บัญชีที่ผ่านการตรวจสอบแล้ว
                    </h3>
                    
                    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden opacity-90">
                        <table class="w-full text-left">
                            <thead class="bg-slate-50 border-b border-slate-100">
                                <tr>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase">ชื่อ-นามสกุล</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase">สถานะปัจจุบัน</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase text-right">จัดการสิทธิ์</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <?php foreach ($active_users as $u): ?>
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-6 py-4 text-sm font-medium text-slate-700">
                                        <?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?>
                                        <span class="ml-2 text-[10px] text-slate-400 uppercase"><?= $u['role'] ?></span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if($u['status'] == 'approved'): ?>
                                            <span class="flex items-center gap-1.5 text-emerald-500 font-bold text-xs"><i class="bi bi-check-circle"></i> ใช้งานได้</span>
                                        <?php else: ?>
                                            <span class="flex items-center gap-1.5 text-rose-500 font-bold text-xs"><i class="bi bi-x-circle"></i> ถูกระงับสิทธิ์</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <?php if($u['status'] == 'approved'): ?>
                                            <a href="?action=reject&id=<?= $u['user_id'] ?>" class="text-rose-500 hover:underline text-xs font-bold" onclick="return confirm('ต้องการระงับการใช้งานร้าน/ไรเดอร์นี้หรือไม่?')">ยกเลิกการใช้งาน</a>
                                        <?php else: ?>
                                            <a href="?action=approve&id=<?= $u['user_id'] ?>" class="text-emerald-500 hover:underline text-xs font-bold">คืนสิทธิ์การใช้งาน</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>