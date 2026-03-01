<?php
// ไฟล์: restaurant/export_receipt.php
session_start();
require_once '../config/database.php';

// 1. ตรวจสอบสิทธิ์ว่าล็อกอินเป็น "ร้านอาหาร" หรือไม่
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'restaurant') {
    header("Location: ../login.php");
    exit();
}

// 2. ตรวจสอบว่ามีการส่ง order_id มาหรือไม่
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    die("ไม่พบรหัสคำสั่งซื้อ");
}

$order_id = $_GET['order_id'];
$user_id = $_SESSION['user_id']; // ไอดีของเจ้าของร้าน

try {
    // 3. หา restaurant_id ของเจ้าของร้านที่ล็อกอินอยู่
    $stmt_rest = $conn->prepare("SELECT restaurant_id, restaurant_name FROM restaurants WHERE user_id = :uid");
    $stmt_rest->execute([':uid' => $user_id]);
    $rest = $stmt_rest->fetch(PDO::FETCH_ASSOC);

    if (!$rest) {
        die("ไม่พบข้อมูลร้านค้าของคุณในระบบ");
    }
    $restaurant_id = $rest['restaurant_id'];
    $restaurant_name = $rest['restaurant_name'];

    // 4. ดึงข้อมูลออเดอร์ ข้อมูลลูกค้า 
    // *** Security Check: ต้องระบุ o.restaurant_id = :rid ด้วยเพื่อป้องกันร้านอื่นมาดู ***
    $sql_order = "SELECT o.*, 
                         u.first_name, u.last_name
                  FROM orders o
                  JOIN users u ON o.customer_id = u.user_id
                  WHERE o.order_id = :oid AND o.restaurant_id = :rid";
                  
    $stmt_order = $conn->prepare($sql_order);
    $stmt_order->execute([':oid' => $order_id, ':rid' => $restaurant_id]);
    $order = $stmt_order->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        die("ไม่พบข้อมูลคำสั่งซื้อ หรือออเดอร์นี้ไม่ใช่ของร้านคุณ");
    }

    // 5. ดึงรายการอาหารในออเดอร์นี้
    $sql_items = "SELECT od.*, f.food_name 
                  FROM order_details od
                  JOIN foods f ON od.food_id = f.food_id
                  WHERE od.order_id = :oid";
    $stmt_items = $conn->prepare($sql_items);
    $stmt_items->execute([':oid' => $order_id]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}

// การจัดการข้อความแสดงสถานะการชำระเงิน
$payment_txt = "เงินสดปลายทาง (Cash)";
$payment_status_color = "bg-emerald-100 text-emerald-700 border-emerald-200"; // สีเขียวสำหรับเก็บเงินสด
$payment_alert = "🔴 ไรเดอร์ต้องเก็บเงินลูกค้า";

if ($order['payment_method'] == 'promptpay' || $order['payment_method'] == 'credit_card') {
    $payment_txt = ($order['payment_method'] == 'promptpay') ? "โอนเงิน (PromptPay)" : "บัตรเครดิต/เดบิต";
    $payment_status_color = "bg-blue-100 text-blue-700 border-blue-200"; // สีฟ้าสำหรับจ่ายแล้ว
    $payment_alert = "🟢 ชำระเงินเรียบร้อยแล้ว";
}

$order_date = date('d/m/Y H:i', strtotime($order['order_date']));
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบออเดอร์ #<?= str_pad($order_id, 5, '0', STR_PAD_LEFT) ?> | <?= htmlspecialchars($restaurant_name) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: { extend: { fontFamily: { sans: ['Prompt', 'sans-serif'] }, colors: { brand: { primary: '#f1416c' } } } }
        }
    </script>
    <style>
        body { background-color: #f8fafc; }
        
        /* ตั้งค่ากระดาษสำหรับเครื่องปริ้นสลิป (Thermal Printer) หรือปริ้น A4 ปกติ */
        @media print {
            body { background-color: #ffffff; margin: 0; padding: 0; }
            .no-print { display: none !important; }
            .print-container { box-shadow: none !important; border: none !important; width: 100% !important; max-width: 100% !important; padding: 10px !important; margin: 0 !important; }
            .dashed-line { border-bottom: 2px dashed #000; margin: 10px 0; }
            /* ปรับสีให้เป็นขาวดำสำหรับการปริ้นท์ */
            * { color: #000 !important; }
            .bg-slate-50 { background-color: transparent !important; border: 1px solid #000 !important; }
        }

        .receipt-card { background: white; box-shadow: 0 20px 40px -15px rgba(0,0,0,0.05); }
        .dashed-line { border-bottom: 2px dashed #e2e8f0; margin: 1.5rem 0; }
    </style>
</head>
<body class="font-sans text-slate-800 p-4 md:p-8 flex justify-center items-start min-h-screen">

    <div class="w-full max-w-lg receipt-card p-6 md:p-10 print-container border border-slate-200 rounded-2xl relative">
        
        <div class="text-center mb-6">
            <h1 class="text-2xl font-black text-slate-800 mb-1"><?= htmlspecialchars($restaurant_name) ?></h1>
            <p class="text-slate-500 font-bold tracking-widest text-xs uppercase border-b-2 border-slate-800 pb-4 inline-block px-4">Order Slip / ใบเตรียมอาหาร</p>
        </div>

        <div class="flex justify-between items-end mb-4">
            <div>
                <p class="text-slate-500 font-bold uppercase text-[10px] tracking-wider mb-1">Order No.</p>
                <p class="font-black text-slate-800 text-2xl">#<?= str_pad($order_id, 5, '0', STR_PAD_LEFT) ?></p>
            </div>
            <div class="text-right">
                <p class="text-slate-500 font-bold uppercase text-[10px] tracking-wider mb-1">เวลาสั่งซื้อ</p>
                <p class="font-bold text-slate-700 text-sm"><?= $order_date ?></p>
            </div>
        </div>

        <div class="dashed-line"></div>

        <div class="bg-slate-50 p-4 rounded-xl mb-6 border border-slate-100">
            <h3 class="font-black text-sm text-slate-800 mb-2"><i class="bi bi-person-lines-fill"></i> ข้อมูลจัดส่ง (Customer)</h3>
            <p class="font-bold text-slate-800 mb-1"><?= htmlspecialchars($order['first_name'].' '.$order['last_name']) ?></p>
            <p class="font-bold text-lg text-brand-primary mb-2"><i class="bi bi-telephone-fill text-sm"></i> <?= htmlspecialchars($order['delivery_phone']) ?></p>
            <p class="text-sm text-slate-600 leading-relaxed bg-white p-2 rounded-lg border border-slate-200">
                <?= !empty($order['delivery_address']) ? nl2br(htmlspecialchars($order['delivery_address'])) : 'ไม่ได้ระบุที่อยู่'; ?>
            </p>
        </div>

        <div class="mb-6">
            <h3 class="font-black text-sm text-slate-800 mb-3">รายการอาหาร (Items)</h3>
            <table class="w-full text-sm">
                <tbody class="text-slate-800">
                    <?php foreach($items as $item): ?>
                    <tr class="border-b border-slate-100">
                        <td class="py-3 pr-2 w-10 font-black text-lg text-brand-primary align-top"><?= $item['quantity'] ?>x</td>
                        <td class="py-3 font-bold text-base leading-tight"><?= htmlspecialchars($item['food_name']) ?></td>
                        <td class="py-3 text-right font-bold align-top">฿<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="dashed-line"></div>

        <div class="space-y-2 text-sm mb-4">
            <div class="flex justify-between items-center">
                <span class="text-slate-500 font-bold">รวมค่าอาหาร</span>
                <span class="font-bold">฿<?= number_format($order['total_price'], 2) ?></span>
            </div>
            <?php if($order['discount_percent'] > 0): ?>
            <div class="flex justify-between items-center text-brand-primary">
                <span class="font-bold">ส่วนลด (<?= $order['discount_percent'] ?>%)</span>
                <span class="font-bold">- ฿<?= number_format(($order['total_price'] * $order['discount_percent']) / 100, 2) ?></span>
            </div>
            <?php endif; ?>
        </div>

        <div class="flex justify-between items-center mb-4 bg-slate-100 p-3 rounded-lg">
            <span class="text-lg font-black text-slate-800 uppercase">ยอดสุทธิ</span>
            <span class="text-2xl font-black text-slate-900">฿<?= number_format($order['net_price'], 2) ?></span>
        </div>
        
        <div class="border-2 rounded-xl p-3 text-center <?= $payment_status_color ?>">
            <p class="font-black text-sm mb-1"><?= $payment_alert ?></p>
            <p class="text-xs font-bold uppercase tracking-widest opacity-80">(วิธีจ่าย: <?= $payment_txt ?>)</p>
        </div>

        <div class="mt-8 space-y-3 no-print">
            <button onclick="window.print()" class="w-full py-4 bg-slate-900 text-white font-bold rounded-xl hover:bg-slate-800 transition-colors flex justify-center items-center gap-2 shadow-lg">
                <i class="bi bi-printer text-xl"></i> พิมพ์ใบออเดอร์
            </button>
            <button onclick="window.close()" class="w-full py-4 bg-white border-2 border-slate-200 text-slate-600 font-bold rounded-xl hover:bg-slate-50 transition-colors flex justify-center items-center gap-2">
                <i class="bi bi-x-lg"></i> ปิดหน้าต่าง
            </button>
        </div>

    </div>
</body>
</html>