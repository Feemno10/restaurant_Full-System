<?php
// ไฟล์: customer/export_receipt.php
session_start();
require_once '../config/database.php';

// 1. ตรวจสอบสิทธิ์และการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// 2. ตรวจสอบว่ามีการส่ง order_id มาหรือไม่
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    die("ไม่พบรหัสคำสั่งซื้อ");
}

$order_id = $_GET['order_id'];
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

try {
    // 3. ดึงข้อมูลออเดอร์ ข้อมูลลูกค้า และข้อมูลร้านอาหาร
    $sql_order = "SELECT o.*, 
                         r.restaurant_name, r.phone as res_phone, r.address as res_addr,
                         u.first_name, u.last_name 
                  FROM orders o
                  JOIN restaurants r ON o.restaurant_id = r.restaurant_id
                  JOIN users u ON o.customer_id = u.user_id
                  WHERE o.order_id = :oid";
                  
    $stmt_order = $conn->prepare($sql_order);
    $stmt_order->execute([':oid' => $order_id]);
    $order = $stmt_order->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        die("ไม่พบข้อมูลใบเสร็จนี้");
    }

    // Security: ตรวจสอบว่าลูกค้าคนนี้เป็นเจ้าของออเดอร์จริง (แอดมินหรือร้านดูได้)
    if ($role === 'customer' && $order['customer_id'] != $user_id) {
        die("คุณไม่มีสิทธิ์เข้าถึงใบเสร็จนี้");
    }

    // 4. ดึงรายการอาหารในออเดอร์นี้
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

// แปลงรูปแบบวิธีชำระเงินให้แสดงผลสวยงาม
$payment_txt = "เงินสด (Cash)";
if ($order['payment_method'] == 'promptpay') $payment_txt = "โอนเงิน (PromptPay)";
if ($order['payment_method'] == 'credit_card') $payment_txt = "บัตรเครดิต/เดบิต";

// ฟอร์แมตวันที่
$order_date = date('d/m/Y H:i', strtotime($order['order_date']));
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบเสร็จรับเงิน #<?= str_pad($order_id, 5, '0', STR_PAD_LEFT) ?> | FoodExpress</title>
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
        
        /* สไตล์พิเศษสำหรับการพิมพ์ (Print) */
        @media print {
            body { background-color: #ffffff; }
            .no-print { display: none !important; }
            .print-container { box-shadow: none !important; border: none !important; max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
            .print-break { page-break-inside: avoid; }
        }

        .receipt-card { background: white; border-radius: 1rem; box-shadow: 0 20px 40px -15px rgba(0,0,0,0.05); }
        .dashed-line { border-bottom: 2px dashed #e2e8f0; margin: 1.5rem 0; }
    </style>
</head>
<body class="font-sans text-slate-800 p-4 md:p-8 flex justify-center items-start min-h-screen">

    <div class="w-full max-w-xl receipt-card p-8 md:p-12 print-container relative overflow-hidden">
        
        <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-[#f1416c] to-[#ff5c00] no-print"></div>

        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center gap-2 mb-2">
                <i class="bi bi-bag-heart-fill text-3xl text-brand-primary"></i>
                <h1 class="text-3xl font-black uppercase italic tracking-tighter text-slate-800">Food<span class="text-brand-primary">Express</span></h1>
            </div>
            <p class="text-slate-500 font-bold tracking-widest text-sm uppercase">Receipt / Tax Invoice</p>
        </div>

        <div class="grid grid-cols-2 gap-4 text-sm mb-6">
            <div>
                <p class="text-slate-400 font-bold uppercase text-[10px] tracking-wider mb-1">รหัสคำสั่งซื้อ (Order No.)</p>
                <p class="font-bold text-slate-800 text-lg">#<?= str_pad($order_id, 5, '0', STR_PAD_LEFT) ?></p>
            </div>
            <div class="text-right">
                <p class="text-slate-400 font-bold uppercase text-[10px] tracking-wider mb-1">วันที่สั่งซื้อ (Date/Time)</p>
                <p class="font-bold text-slate-700"><?= $order_date ?></p>
            </div>
        </div>

        <div class="bg-slate-50 p-4 rounded-xl mb-6 text-sm grid grid-cols-1 sm:grid-cols-2 gap-4 print-break">
            <div>
                <p class="text-slate-400 font-bold uppercase text-[10px] tracking-wider mb-1"><i class="bi bi-shop"></i> ร้านอาหาร (Restaurant)</p>
                <p class="font-bold text-slate-800"><?= htmlspecialchars($order['restaurant_name']) ?></p>
            </div>
            <div>
                <p class="text-slate-400 font-bold uppercase text-[10px] tracking-wider mb-1"><i class="bi bi-person-circle"></i> ลูกค้า (Customer)</p>
                <p class="font-bold text-slate-800"><?= htmlspecialchars($order['first_name'].' '.$order['last_name']) ?></p>
                <p class="text-slate-500 mt-1"><?= htmlspecialchars($order['delivery_phone']) ?></p>
            </div>
        </div>

        <div class="dashed-line"></div>

        <div class="mb-6 print-break">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-slate-400 border-b border-slate-200 uppercase text-[10px] tracking-wider">
                        <th class="text-left pb-3 font-bold">รายการ (Item)</th>
                        <th class="text-center pb-3 font-bold">จำนวน (Qty)</th>
                        <th class="text-right pb-3 font-bold">ราคา (Price)</th>
                        <th class="text-right pb-3 font-bold">รวม (Total)</th>
                    </tr>
                </thead>
                <tbody class="text-slate-700 font-medium">
                    <?php foreach($items as $item): ?>
                    <tr>
                        <td class="py-4 border-b border-slate-100 pr-2"><?= htmlspecialchars($item['food_name']) ?></td>
                        <td class="py-4 border-b border-slate-100 text-center">x<?= $item['quantity'] ?></td>
                        <td class="py-4 border-b border-slate-100 text-right">฿<?= number_format($item['price'], 2) ?></td>
                        <td class="py-4 border-b border-slate-100 text-right font-bold">฿<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="space-y-3 text-sm print-break">
            <div class="flex justify-between text-slate-500">
                <span>ยอดรวมค่าอาหาร (Subtotal)</span>
                <span class="font-bold">฿<?= number_format($order['total_price'], 2) ?></span>
            </div>
            
            <?php if($order['discount_percent'] > 0): ?>
            <div class="flex justify-between text-brand-primary">
                <span>ส่วนลด (Discount <?= $order['discount_percent'] ?>%)</span>
                <span class="font-bold">- ฿<?= number_format(($order['total_price'] * $order['discount_percent']) / 100, 2) ?></span>
            </div>
            <?php endif; ?>
            
            <div class="flex justify-between text-slate-500">
                <span>ค่าจัดส่ง (Delivery Fee)</span>
                <span class="font-bold text-emerald-500">ฟรี (Free)</span>
            </div>
        </div>

        <div class="dashed-line"></div>

        <div class="flex justify-between items-center mb-2 print-break">
            <span class="text-lg font-black text-slate-800 uppercase">ยอดสุทธิ (Net Total)</span>
            <span class="text-3xl font-black text-brand-primary">฿<?= number_format($order['net_price'], 2) ?></span>
        </div>
        
        <div class="flex justify-between items-center text-sm mt-4">
            <span class="text-slate-400 font-bold uppercase text-[10px] tracking-wider">วิธีชำระเงิน (Payment)</span>
            <span class="font-bold bg-slate-100 px-3 py-1 rounded-md text-slate-600"><?= $payment_txt ?></span>
        </div>

        <div class="dashed-line"></div>

        <div class="text-center mt-8 print-break">
            <p class="font-bold text-slate-800 text-lg mb-1">ขอบคุณที่ใช้บริการ 🙏</p>
            <p class="text-slate-400 text-xs mb-6">Thank you for ordering with FoodExpress.</p>
            
            <div class="font-[barcode] text-4xl text-slate-300 tracking-[0.2em] mb-2 opacity-50">||| ||||||| ||| ||||</div>
            <p class="text-[10px] text-slate-400 tracking-widest uppercase">Powered by IT Phang-Nga</p>
        </div>

        <div class="mt-10 space-y-3 no-print">
            <button onclick="window.print()" class="w-full py-4 bg-slate-900 text-white font-bold rounded-2xl hover:bg-brand-primary transition-colors flex justify-center items-center gap-2 shadow-lg">
                <i class="bi bi-printer text-xl"></i> พิมพ์ / บันทึกเป็น PDF
            </button>
            
            <?php if($role == 'customer'): ?>
                <a href="history.php" class="w-full py-4 bg-slate-100 text-slate-600 font-bold rounded-2xl hover:bg-slate-200 transition-colors flex justify-center items-center gap-2">
                    <i class="bi bi-clock-history"></i> กลับไปหน้าประวัติ
                </a>
            <?php else: ?>
                <button onclick="window.close()" class="w-full py-4 bg-slate-100 text-slate-600 font-bold rounded-2xl hover:bg-slate-200 transition-colors flex justify-center items-center gap-2">
                    <i class="bi bi-x-circle"></i> ปิดหน้าต่างนี้
                </button>
            <?php endif; ?>
        </div>

    </div>
</body>
</html>