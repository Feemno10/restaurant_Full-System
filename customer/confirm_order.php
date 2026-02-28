<?php
// ไฟล์: customer/confirm_order.php
session_start();
require_once '../config/database.php';

// 1. ตรวจสอบสิทธิ์ (Security)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}

// ตรวจสอบว่ามีข้อมูลในตะกร้าหรือไม่
if (empty($_SESSION['cart']) || !isset($_POST['submit_order'])) {
    header("Location: cart.php");
    exit();
}

// 2. รับค่าที่ส่งมาจากหน้าตะกร้า
$customer_id = $_SESSION['user_id'];
$restaurant_id = $_SESSION['cart_restaurant_id'];
$total_price = $_POST['total_price'];
$discount_percent = $_POST['discount_percent'];
$net_price = $_POST['net_price'];
$cart = $_SESSION['cart'];

try {
    // เริ่มต้น Transaction
    $conn->beginTransaction();

    // 3. บันทึกข้อมูลลงตาราง orders (Master)
    // สถานะเริ่มต้นเป็น 'pending' เพื่อรอร้านค้ากดยืนยัน
    $sql_order = "INSERT INTO orders (customer_id, restaurant_id, total_price, discount_percent, net_price, status, order_date) 
                  VALUES (:cus, :res, :total, :disc, :net, 'pending', NOW())";
    
    $stmt_order = $conn->prepare($sql_order);
    $stmt_order->execute([
        ':cus' => $customer_id,
        ':res' => $restaurant_id,
        ':total' => $total_price,
        ':disc' => $discount_percent,
        ':net' => $net_price
    ]);

    // รับ ID ของออเดอร์ที่เพิ่งถูกสร้าง
    $order_id = $conn->lastInsertId();

    // 4. บันทึกข้อมูลรายการอาหารลงตาราง order_details (Detail)
    // ดึงราคาปัจจุบันของอาหารแต่ละรายการเพื่อป้องกันปัญหาหากร้านเปลี่ยนราคาทีหลัง
    $placeholders = implode(',', array_fill(0, count($cart), '?'));
    $stmt_food_price = $conn->prepare("SELECT food_id, price FROM foods WHERE food_id IN ($placeholders)");
    $stmt_food_price->execute(array_keys($cart));
    $food_info = $stmt_food_price->fetchAll(PDO::FETCH_KEY_PAIR);

    $sql_detail = "INSERT INTO order_details (order_id, food_id, quantity, price) 
                   VALUES (:oid, :fid, :qty, :price)";
    $stmt_detail = $conn->prepare($sql_detail);

    foreach ($cart as $food_id => $quantity) {
        $stmt_detail->execute([
            ':oid' => $order_id,
            ':fid' => $food_id,
            ':qty' => $quantity,
            ':price' => $food_info[$food_id]
        ]);
    }

    // 5. บันทึกข้อมูลทั้งหมดสำเร็จ ยืนยัน Transaction
    $conn->commit();

    // ล้างข้อมูลตะกร้าใน Session หลังสั่งซื้อสำเร็จ
    unset($_SESSION['cart']);
    unset($_SESSION['cart_restaurant_id']);

    // 6. ส่งไปยังหน้าประวัติการสั่งซื้อเพื่อให้ลูกค้าติดตามสถานะ
    header("Location: history.php?status=success&order_id=" . $order_id);
    exit();

} catch (Exception $e) {
    // หากมีอะไรผิดพลาด ให้ยกเลิกการเขียนข้อมูลทั้งหมดที่ผ่านมา
    $conn->rollBack();
    die("เกิดข้อผิดพลาดในการสั่งซื้อ: " . $e->getMessage());
}
?>