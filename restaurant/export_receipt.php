<?php
// ไฟล์: export_receipt.php
session_start();

// 1. ตรวจสอบการล็อกอิน (เข้าถึงได้ทั้ง Customer, Restaurant, Admin, Rider)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';

// ตรวจสอบ Order ID
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    die("ไม่พบรหัสคำสั่งซื้อ");
}

$order_id = $_GET['order_id'];

try {
    // 2. ดึงข้อมูลออเดอร์ ข้อมูลลูกค้า และข้อมูลร้านอาหาร
    $stmt_order = $conn->prepare("
        SELECT o.*, 
               u.first_name as cus_f, u.last_name as cus_l, u.phone as cus_phone,
               r.restaurant_name, r.address as res_addr, r.discount_percent as res_discount
        FROM orders o
        JOIN users u ON o.customer_id = u.user_id
        JOIN restaurants r ON o.restaurant_id = r.restaurant_id
        WHERE o.order_id = :oid
    ");
    $stmt_order->execute([':oid' => $order_id]);
    $order = $stmt_order->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        die("ไม่พบข้อมูลออเดอร์นี้ในระบบ");
    }

    // 3. ดึงรายการอาหารในออเดอร์
    $stmt_items = $conn->prepare("
        SELECT od.*, f.food_name 
        FROM order_details od
        JOIN foods f ON od.food_id = f.food_id
        WHERE od.order_id = :oid
    ");
    $stmt_items->execute([':oid' => $order_id]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt_#<?= str_pad($order['order_id'], 5, '0', STR_PAD_LEFT); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['Prompt', 'sans-serif'] } } } }
    </script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background-color: white !important; }
            .receipt-container { box-shadow: none !important; border: none !important; width: 100% !important; max-width: 100% !important; padding: 0 !important; }
        }
    </style>
</head>
<body class="bg-slate-100 py-10 font-sans">

    <div class="max-w-2xl mx-auto no-print mb-6 flex justify-between items-center px-4">
        <a href="javascript:history.back()" class="text-slate-500 hover:text-slate-800 font-bold transition flex items-center gap-2">
            <i class="bi bi-arrow-left"></i> กลับ
        </a>
        <button onclick="window.print()" class="bg-slate-900 text-white px-6 py-2.5 rounded-xl font-bold shadow-lg hover:bg-slate-800 transition flex items-center gap-2">
            <i class="bi bi-printer"></i> พิมพ์ใบเสร็จ
        </button>
    </div>

    <div class="max-w-2xl mx-auto bg-white p-8 sm:p-12 shadow-2xl rounded-none sm:rounded-[2.5rem] receipt-container border border-slate-100">
        
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-6 border-b-2 border-dashed border-slate-100 pb-8 mb-8">
            <div>
                <h1 class="text-3xl font-black text-slate-900 mb-2 tracking-tight">INVOICE</h1>
                <p class="text-slate-400 font-bold text-xs uppercase tracking-widest">Order ID: <span class="text-slate-800">#<?= str_pad($order['order_id'], 5, '0', STR_PAD_LEFT); ?></span></p>
                <p class="text-slate-400 font-bold text-xs uppercase tracking-widest mt-1">Date: <span class="text-slate-800"><?= date('d/m/Y H:i', strtotime($order['order_date'])); ?></span></p>
            </div>
            <div class="text-right">
                <h2 class="text-xl font-bold text-rose-500"><?= htmlspecialchars($order['restaurant_name']); ?></h2>
                <p class="text-xs text-slate-500 max-w-[200px] mt-1"><?= htmlspecialchars($order['res_addr']); ?></p>
            </div>
        </div>

        <div class="mb-10">
            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3">Bill To:</h3>
            <p class="font-bold text-slate-800 text-lg"><?= htmlspecialchars($order['cus_f'] . ' ' . $order['cus_l']); ?></p>
            <p class="text-sm text-slate-500 font-medium">Phone: <?= htmlspecialchars($order['cus_phone']); ?></p>
        </div>

        <div class="mb-10">
            <table class="w-full">
                <thead>
                    <tr class="text-left border-b border-slate-100 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                        <th class="pb-3">รายการอาหาร</th>
                        <th class="pb-3 text-center">จำนวน</th>
                        <th class="pb-3 text-right">ราคาต่อหน่วย</th>
                        <th class="pb-3 text-right">รวม</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 text-sm font-medium">
                    <?php 
                    $subtotal = 0;
                    foreach ($items as $item): 
                        $item_total = $item['quantity'] * $item['price'];
                        $subtotal += $item_total;
                    ?>
                    <tr>
                        <td class="py-4 text-slate-700"><?= htmlspecialchars($item['food_name']); ?></td>
                        <td class="py-4 text-center text-slate-500"><?= $item['quantity']; ?></td>
                        <td class="py-4 text-right text-slate-500">฿<?= number_format($item['price'], 2); ?></td>
                        <td class="py-4 text-right text-slate-800 font-bold">฿<?= number_format($item_total, 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="border-t-2 border-slate-50 pt-6 flex flex-col items-end gap-2">
            <div class="flex justify-between w-full sm:w-64 text-sm">
                <span class="text-slate-400 font-bold uppercase tracking-wider">Subtotal</span>
                <span class="text-slate-800 font-bold">฿<?= number_format($subtotal, 2); ?></span>
            </div>
            <?php if ($order['discount_percent'] > 0): ?>
                <div class="flex justify-between w-full sm:w-64 text-sm text-rose-500">
                    <span class="font-bold uppercase tracking-wider">Discount (<?= $order['discount_percent']; ?>%)</span>
                    <span class="font-bold">- ฿<?= number_format(($subtotal * $order['discount_percent'] / 100), 2); ?></span>
                </div>
            <?php endif; ?>
            <div class="flex justify-between w-full sm:w-64 mt-4 bg-slate-900 text-white p-4 rounded-2xl shadow-xl shadow-slate-200">
                <span class="font-black uppercase tracking-widest text-xs">Total Amount</span>
                <span class="font-black text-xl">฿<?= number_format($order['net_price'], 2); ?></span>
            </div>
        </div>

        <div class="mt-16 text-center border-t border-slate-50 pt-8">
            <p class="text-sm font-bold text-slate-800">ขอบคุณที่ใช้บริการ "<?= htmlspecialchars($order['restaurant_name']); ?>"</p>
            <p class="text-[10px] text-slate-400 uppercase tracking-widest mt-1 italic">Please keep this receipt for your records.</p>
            <div class="mt-6 flex justify-center gap-4 text-slate-200">
                <i class="bi bi-star-fill"></i>
                <i class="bi bi-star-fill"></i>
                <i class="bi bi-star-fill"></i>
            </div>
        </div>

    </div>

</body>
</html>