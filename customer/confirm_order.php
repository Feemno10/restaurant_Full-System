<?php
// ไฟล์: customer/confirm_order.php
session_start();
require_once '../config/database.php';

// 1. ตรวจสอบสิทธิ์และรับค่าจากฟอร์ม Checkout
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer' || !isset($_POST['confirm_payment'])) {
    header("Location: index.php");
    exit();
}
if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}

$customer_id = $_SESSION['user_id'];
$restaurant_id = $_POST['restaurant_id'];
$total_price = $_POST['total_price'];
$discount_percent = $_POST['discount_percent'];
$net_price = $_POST['net_price'];

// ค่าใหม่ที่รับจากหน้า checkout.php
$payment_method = $_POST['payment_method'] ?? 'cash';
$delivery_phone = trim($_POST['delivery_phone']);
$delivery_address = trim($_POST['delivery_address']);
$cart = $_SESSION['cart'];

try {
    // เริ่ม Transaction เพื่อความปลอดภัยของข้อมูล
    $conn->beginTransaction();

    // 2. บันทึกคำสั่งซื้อหลักลงตาราง orders
    // หมายเหตุ: ตรวจสอบให้แน่ใจว่าตาราง orders มีคอลัมน์ payment_method, delivery_address, delivery_phone
    $sql_order = "INSERT INTO orders (customer_id, restaurant_id, total_price, discount_percent, net_price, status, payment_method, delivery_address, delivery_phone, order_date) 
                  VALUES (:cus, :res, :total, :disc, :net, 'pending', :pay, :addr, :phone, NOW())";
    
    $stmt_order = $conn->prepare($sql_order);
    $stmt_order->execute([
        ':cus' => $customer_id,
        ':res' => $restaurant_id,
        ':total' => $total_price,
        ':disc' => $discount_percent,
        ':net' => $net_price,
        ':pay' => $payment_method,      // บันทึกช่องทางชำระเงิน
        ':addr' => $delivery_address,   // บันทึกที่อยู่จัดส่ง
        ':phone' => $delivery_phone     // บันทึกเบอร์โทร
    ]);

    // ดึงรหัสออเดอร์ล่าสุด
    $order_id = $conn->lastInsertId();

    // 3. ดึงราคาอาหารปัจจุบันเพื่อบันทึกลงตาราง order_details
    $placeholders = implode(',', array_fill(0, count($cart), '?'));
    $stmt_food_price = $conn->prepare("SELECT food_id, price FROM foods WHERE food_id IN ($placeholders)");
    $stmt_food_price->execute(array_keys($cart));
    $food_info = $stmt_food_price->fetchAll(PDO::FETCH_KEY_PAIR);

    $sql_detail = "INSERT INTO order_details (order_id, food_id, quantity, price) VALUES (:oid, :fid, :qty, :price)";
    $stmt_detail = $conn->prepare($sql_detail);

    // ลูปบันทึกรายการอาหารทีละเมนู
    foreach ($cart as $food_id => $quantity) {
        $stmt_detail->execute([
            ':oid' => $order_id,
            ':fid' => $food_id,
            ':qty' => $quantity,
            ':price' => $food_info[$food_id]
        ]);
    }

    // 4. บันทึกสำเร็จ ยืนยันข้อมูลทั้งหมด
    $conn->commit();

    // ล้างตะกร้าสินค้า
    unset($_SESSION['cart']);
    unset($_SESSION['cart_restaurant_id']);

    // ส่งลูกค้าไปยังหน้าประวัติการสั่งซื้อ
    header("Location: history.php?status=order_success&order_id=" . $order_id);
    exit();

} catch (Exception $e) {
    // ถ้ายกเลิก ให้ออเดอร์ไม่ถูกสร้าง
    $conn->rollBack();
    die("เกิดข้อผิดพลาดในการสั่งซื้อ: " . $e->getMessage());
}
?>