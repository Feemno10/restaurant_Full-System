<?php
// ไฟล์: rider/complete_job.php
session_start();

// 1. ตรวจสอบสิทธิ์ (Security)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'rider') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';
$user_id = $_SESSION['user_id'];

// รับค่า Order ID
if (!isset($_GET['order_id'])) {
    header("Location: index.php");
    exit();
}
$order_id = $_GET['order_id'];

try {
    // 2. อัปเดตสถานะเป็นสำเร็จ (ข้อ 3.4.9)
    // ตรวจสอบว่าไรเดอร์คนนี้เป็นเจ้าของงานจริง เพื่อความปลอดภัย
    $stmt = $conn->prepare("UPDATE orders SET status = 'completed' WHERE order_id = :oid AND rider_id = :rid");
    $stmt->execute([':oid' => $order_id, ':rid' => $user_id]);

    // 3. ดึงยอดเงินสุทธิเพื่อแสดงสรุปให้ไรเดอร์ดู
    $stmt_info = $conn->prepare("SELECT net_price FROM orders WHERE order_id = :oid");
    $stmt_info->execute([':oid' => $order_id]);
    $order = $stmt_info->fetch(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("เกิดข้อผิดพลาด: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Success! | FoodDelivery</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Prompt', 'sans-serif'] },
                    colors: { brand: { green: '#10b981' } }
                }
            }
        }
    </script>
    <style>
        /* สไตล์พิเศษสำหรับหน้าจอสำเร็จ */
        .check-container { animation: scaleIn 0.5s cubic-bezier(0.16, 1, 0.3, 1); }
        @keyframes scaleIn { from { transform: scale(0.5); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    </style>
</head>
<body class="bg-slate-50 flex items-center justify-center min-h-screen p-4">

    <div class="max-w-md w-full bg-white rounded-[3rem] shadow-2xl shadow-emerald-100 p-8 text-center relative overflow-hidden">
        <div class="absolute -top-10 -right-10 w-40 h-40 bg-emerald-50 rounded-full"></div>
        <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-slate-50 rounded-full"></div>

        <div class="relative z-10">
            <div class="check-container w-24 h-24 bg-brand-green text-white rounded-full flex items-center justify-center mx-auto mb-8 shadow-xl shadow-emerald-200">
                <i class="bi bi-check-lg text-5xl"></i>
            </div>

            <h1 class="text-3xl font-black text-slate-800 mb-2">จัดส่งอาหารสำเร็จ!</h1>
            <p class="text-slate-400 font-medium mb-8">ขอบคุณที่คุณส่งมอบความอร่อยถึงมือลูกค้าครับ</p>

            <div class="bg-slate-50 rounded-[2rem] p-6 mb-10 border border-slate-100">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">ยอดเงินรวมที่คุณต้องเก็บ</p>
                <p class="text-4xl font-black text-brand-green tracking-tighter">฿<?= number_format($order['net_price'], 2); ?></p>
                <div class="mt-4 pt-4 border-t border-slate-200/50 flex justify-between text-xs font-bold">
                    <span class="text-slate-400 uppercase">Order ID</span>
                    <span class="text-slate-600">#<?= str_pad($order_id, 5, '0', STR_PAD_LEFT); ?></span>
                </div>
            </div>

            <div class="space-y-3">
                <a href="index.php" class="block w-full bg-slate-900 text-white py-4 rounded-2xl font-bold hover:bg-slate-800 transition shadow-lg shadow-slate-200">
                    <i class="bi bi-house-door-fill mr-2"></i> กลับหน้าหลักเพื่อรับงานใหม่
                </a>
                <a href="../export_receipt.php?order_id=<?= $order_id; ?>" class="block w-full text-slate-500 py-3 text-sm font-bold hover:underline">
                    <i class="bi bi-download mr-1"></i> ดูใบสรุปรายการส่งอาหาร
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <script>
        window.onload = function() {
            confetti({
                particleCount: 100,
                spread: 70,
                origin: { y: 0.6 },
                colors: ['#10b981', '#34d399', '#059669']
            });
        };
    </script>
</body>
</html>