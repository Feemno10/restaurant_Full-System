<?php
// ไฟล์: rider/index.php
session_start();

// 1. ตรวจสอบสิทธิ์ (Security) - ต้องเป็น rider เท่านั้น
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'rider') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';
$user_id = $_SESSION['user_id'];
$message = '';

try {
    // 2. จัดการการเลือกรับงาน (ข้อ 3.4.8)
    if (isset($_GET['action']) && $_GET['action'] == 'take' && isset($_GET['order_id'])) {
        $oid = $_GET['order_id'];
        
        // ตรวจสอบก่อนว่าไรเดอร์คนนี้มีงานที่ยังส่งไม่เสร็จค้างอยู่หรือไม่ (รับได้ทีละงาน)
        $stmt_check = $conn->prepare("SELECT COUNT(*) FROM orders WHERE rider_id = :rid AND status = 'delivering'");
        $stmt_check->execute([':rid' => $user_id]);
        
        if ($stmt_check->fetchColumn() > 0) {
            $message = "<div class='bg-amber-500 text-white p-4 rounded-2xl mb-6 shadow-lg'>คุณมีงานที่กำลังส่งค้างอยู่ กรุณาส่งงานเดิมให้เสร็จก่อนรับงานใหม่ครับ</div>";
        } else {
            // อัปเดตระบุตัวผู้ส่งในออเดอร์
            $stmt_take = $conn->prepare("UPDATE orders SET rider_id = :rid WHERE order_id = :oid AND rider_id IS NULL");
            $stmt_take->execute([':rid' => $user_id, ':oid' => $oid]);
            $message = "<div class='bg-emerald-500 text-white p-4 rounded-2xl mb-6 shadow-lg'><i class='bi bi-check-circle-fill mr-2'></i>รับงานสำเร็จ! เริ่มการจัดส่งได้เลย</div>";
        }
    }

    // 3. ดึงสถิติงานที่ส่งสำเร็จแล้ว (ข้อ 3.4.9)
    $stmt_done = $conn->prepare("SELECT COUNT(*) FROM orders WHERE rider_id = :rid AND status = 'completed'");
    $stmt_done->execute([':rid' => $user_id]);
    $jobs_done = $stmt_done->fetchColumn();

    // 4. ดึงรายการงานใหม่ที่ "รอไรเดอร์มารับ" (ข้อ 3.4.7)
    // เงื่อนไข: สถานะคือ 'delivering' (ร้านทำเสร็จแล้ว) และยังไม่มี rider_id
    $stmt_available = $conn->prepare("
        SELECT o.*, r.restaurant_name, r.address as res_addr, u.first_name, u.last_name 
        FROM orders o
        JOIN restaurants r ON o.restaurant_id = r.restaurant_id
        JOIN users u ON o.customer_id = u.user_id
        WHERE o.status = 'delivering' AND o.rider_id IS NULL
        ORDER BY o.order_date ASC
    ");
    $stmt_available->execute();
    $available_jobs = $stmt_available->fetchAll(PDO::FETCH_ASSOC);

    // 5. ดึงงานที่กำลังส่งอยู่ในขณะนี้
    $stmt_active = $conn->prepare("
        SELECT o.*, r.restaurant_name, r.address as res_addr, u.first_name, u.last_name, u.phone as cus_phone
        FROM orders o
        JOIN restaurants r ON o.restaurant_id = r.restaurant_id
        JOIN users u ON o.customer_id = u.user_id
        WHERE o.rider_id = :rid AND o.status = 'delivering'
        LIMIT 1
    ");
    $stmt_active->execute([':rid' => $user_id]);
    $active_job = $stmt_active->fetch(PDO::FETCH_ASSOC);

} catch(PDOException $e) { die("Error: " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rider Dashboard | FoodDelivery</title>
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
<body class="bg-[#f8fafc] flex h-screen overflow-hidden text-slate-800">

    <aside class="w-64 bg-white shadow-2xl z-20 flex-shrink-0 flex flex-col">
        <div class="h-16 flex items-center px-6 bg-brand-green text-white">
            <i class="bi bi-motorcycle text-2xl me-2"></i>
            <span class="font-bold text-lg tracking-wide">RiderExpress</span>
        </div>
        <div class="flex-1 py-6 overflow-y-auto">
            <a href="index.php" class="nav-link active"><i class="bi bi-speedometer2 mr-3"></i> แดชบอร์ด</a>
            <a href="jobs.php" class="nav-link"><i class="bi bi-box-seam mr-3"></i> ประวัติงานส่ง</a>
            <p class="px-6 text-xs font-bold text-slate-400 uppercase tracking-widest mb-4 mt-8">การตั้งค่า</p>
            <a href="profile.php" class="nav-link"><i class="bi bi-person-gear mr-3"></i> โปรไฟล์ [3.4.3]</a>
            <a href="../logout.php" class="nav-link text-rose-500 mt-4"><i class="bi bi-box-arrow-right mr-3"></i> ออกจากระบบ</a>
        </div>
    </aside>

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-8 z-10">
            <h1 class="font-bold text-slate-700 uppercase text-xs tracking-[0.2em]">Rider Control Panel</h1>
            <div class="flex items-center gap-4">
                <i class="bi bi-bell text-slate-400 text-xl"></i>
                <div class="h-6 w-px bg-slate-200"></div>
                <div class="flex items-center gap-2">
                    <div class="w-9 h-9 rounded-full bg-brand-green text-white flex items-center justify-center font-bold text-sm shadow-sm">RD</div>
                    <span class="text-sm font-bold text-slate-700 hidden md:block"><?= htmlspecialchars($_SESSION['full_name']); ?></span>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-8">
            <div class="max-w-5xl mx-auto">
                
                <?= $message; ?>

                <div class="mb-10 flex flex-col md:flex-row justify-between items-center bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100 gap-6">
                    <div>
                        <h2 class="text-2xl font-black text-slate-900">สวัสดีครับคุณไรเดอร์! 👋</h2>
                        <p class="text-slate-400 mt-1">วันนี้คุณพร้อมส่งความอร่อยแล้วหรือยัง? ตรวจสอบงานใหม่ด้านล่างครับ</p>
                    </div>
                    <div class="flex gap-4">
                        <div class="text-center bg-emerald-50 px-6 py-3 rounded-2xl border border-emerald-100">
                            <p class="text-[10px] font-bold text-emerald-600 uppercase tracking-widest">ส่งสำเร็จแล้ว</p>
                            <h3 class="text-2xl font-black text-emerald-700"><?= number_format($jobs_done); ?> <span class="text-xs font-normal">งาน</span></h3>
                        </div>
                    </div>
                </div>

                <?php if ($active_job): ?>
                    <h3 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-2"><i class="bi bi-geo-alt-fill text-brand-green"></i> งานที่กำลังดำเนินการอยู่</h3>
                    <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-[2.5rem] p-8 text-white shadow-xl mb-12 relative overflow-hidden">
                        <div class="relative z-10 grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <span class="bg-brand-green text-white px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest">In Progress</span>
                                <h4 class="text-3xl font-black mt-4 italic">#<?= str_pad($active_job['order_id'], 5, '0', STR_PAD_LEFT); ?></h4>
                                <div class="mt-6 space-y-4">
                                    <div class="flex items-start gap-3">
                                        <i class="bi bi-shop text-brand-green text-xl"></i>
                                        <div>
                                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">รับจากร้าน</p>
                                            <p class="font-bold"><?= htmlspecialchars($active_job['restaurant_name']); ?></p>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <i class="bi bi-person-circle text-brand-green text-xl"></i>
                                        <div>
                                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">ส่งให้คุณ</p>
                                            <p class="font-bold"><?= htmlspecialchars($active_job['first_name'].' '.$active_job['last_name']); ?></p>
                                            <p class="text-xs text-slate-400">เบอร์โทร: <?= htmlspecialchars($active_job['cus_phone']); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col justify-end items-end">
                                <p class="text-3xl font-black text-brand-green mb-6">฿<?= number_format($active_job['net_price'], 2); ?></p>
                                <a href="job_detail.php?order_id=<?= $active_job['order_id']; ?>" class="w-full md:w-auto bg-brand-green text-white px-10 py-4 rounded-2xl font-bold hover:bg-emerald-400 transition shadow-lg text-center">จัดการการจัดส่ง</a>
                            </div>
                        </div>
                        <i class="bi bi-truck absolute -bottom-10 -right-10 text-[12rem] opacity-5 -rotate-12"></i>
                    </div>
                <?php endif; ?>

                <h3 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-2"><i class="bi bi-lightning-charge-fill text-amber-500"></i> รายการงานใหม่ที่รอรับ</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php foreach ($available_jobs as $job): ?>
                        <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 hover:shadow-md transition group">
                            <div class="flex justify-between items-start mb-6">
                                <div class="bg-slate-50 px-3 py-1 rounded-xl font-bold text-[10px] text-slate-400 border border-slate-100">#<?= str_pad($job['order_id'], 5, '0', STR_PAD_LEFT); ?></div>
                                <p class="text-xl font-black text-brand-green">฿<?= number_format($job['net_price'], 2); ?></p>
                            </div>
                            <div class="space-y-4 mb-8">
                                <div class="flex gap-3">
                                    <div class="w-1.5 h-1.5 rounded-full bg-brand-green mt-1.5 shadow-[0_0_8px_#10b981]"></div>
                                    <div>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest leading-none">ร้านอาหาร</p>
                                        <p class="text-sm font-bold text-slate-700"><?= htmlspecialchars($job['restaurant_name']); ?></p>
                                    </div>
                                </div>
                                <div class="flex gap-3">
                                    <div class="w-1.5 h-1.5 rounded-full bg-slate-300 mt-1.5"></div>
                                    <div>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest leading-none">ที่อยู่ร้าน</p>
                                        <p class="text-xs font-medium text-slate-500 line-clamp-1"><?= htmlspecialchars($job['res_addr']); ?></p>
                                    </div>
                                </div>
                            </div>
                            <a href="?action=take&order_id=<?= $job['order_id']; ?>" class="block w-full bg-slate-900 text-white text-center py-4 rounded-2xl font-bold hover:bg-brand-green transition shadow-lg" onclick="return confirm('ต้องการรับงานนี้ใช่หรือไม่?')">
                                <i class="bi bi-check2-all mr-2"></i> รับงานส่งอาหาร [3.4.8]
                            </a>
                        </div>
                    <?php endforeach; ?>

                    <?php if(empty($available_jobs)): ?>
                        <div class="col-span-full py-20 text-center bg-white rounded-[2rem] border-2 border-dashed border-slate-100">
                            <i class="bi bi-bicycle text-6xl text-slate-100"></i>
                            <p class="text-slate-400 mt-4 font-medium italic">ยังไม่มีงานใหม่ในขณะนี้ ลองรีเฟรชหน้าจอดูนะครับ</p>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </main>
    </div>

</body>
</html>