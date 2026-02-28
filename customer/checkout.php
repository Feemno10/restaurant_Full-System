<?php
// ไฟล์: customer/checkout.php
session_start();

// 1. ตรวจสอบสิทธิ์ว่าล็อกอินหรือยัง
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';

// 2. ตรวจสอบว่ามีตะกร้าสินค้าหรือไม่ ถ้าไม่มีให้กลับไปหน้าแรก
if (empty($_SESSION['cart']) || !isset($_SESSION['cart_restaurant_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$restaurant_id = $_SESSION['cart_restaurant_id'];
$message = '';

// 3. ดึงข้อมูลสินค้าในตะกร้าเพื่อคำนวณยอดรวมเตรียมแสดงผล/บันทึก
$cart_items = [];
$total_price = 0;
$restaurant_name = '';

$food_ids = implode(',', array_map('intval', array_keys($_SESSION['cart'])));

try {
    // ดึงข้อมูลผู้ใช้งาน (สำหรับโชว์เบอร์โทรติดต่อ)
    $stmt_user = $conn->prepare("SELECT first_name, last_name, phone FROM users WHERE user_id = :id");
    $stmt_user->execute([':id' => $user_id]);
    $user_info = $stmt_user->fetch(PDO::FETCH_ASSOC);

    // ดึงข้อมูลอาหารจากตะกร้า
    $sql = "SELECT f.*, r.restaurant_name 
            FROM foods f 
            JOIN restaurants r ON f.restaurant_id = r.restaurant_id 
            WHERE f.food_id IN ($food_ids)";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $foods = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($foods as $row) {
        $row['quantity'] = $_SESSION['cart'][$row['food_id']];
        $row['subtotal'] = $row['price'] * $row['quantity'];
        $total_price += $row['subtotal'];
        $cart_items[] = $row;
        $restaurant_name = $row['restaurant_name'];
    }
    
    // จำลองส่วนลดเป็น 0 (สามารถต่อยอดดึงส่วนลดจากร้านค้าได้ในอนาคต ข้อ 3.2.12)
    $discount_percent = 0; 
    $net_price = $total_price; 

} catch(PDOException $e) {
    die("เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage());
}

// ---------------------------------------------------------
// 4. บันทึกข้อมูลลงฐานข้อมูลเมื่อกด "ยืนยันการสั่งซื้อ"
// ---------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_order'])) {
    try {
        // เริ่มต้น Transaction (เพื่อความชัวร์ว่าข้อมูลต้องลงครบทุกตาราง)
        $conn->beginTransaction();

        // 4.1 บันทึกข้อมูลลงตาราง orders (บิลหลัก)
        $stmt_order = $conn->prepare("
            INSERT INTO orders (customer_id, restaurant_id, total_price, discount_percent, net_price, status) 
            VALUES (:customer_id, :restaurant_id, :total_price, :discount, :net_price, 'pending')
        ");
        $stmt_order->execute([
            ':customer_id' => $user_id,
            ':restaurant_id' => $restaurant_id,
            ':total_price' => $total_price,
            ':discount' => $discount_percent,
            ':net_price' => $net_price
        ]);

        // ดึง order_id ที่เพิ่งบันทึกไปเมื่อกี้
        $last_order_id = $conn->lastInsertId();

        // 4.2 บันทึกข้อมูลรายการอาหารลงตาราง order_details (บิลย่อย)
        $stmt_detail = $conn->prepare("
            INSERT INTO order_details (order_id, food_id, quantity, price) 
            VALUES (:order_id, :food_id, :quantity, :price)
        ");

        foreach ($cart_items as $item) {
            $stmt_detail->execute([
                ':order_id' => $last_order_id,
                ':food_id' => $item['food_id'],
                ':quantity' => $item['quantity'],
                ':price' => $item['price']
            ]);
        }

        // ยืนยันการบันทึกข้อมูล (Commit)
        $conn->commit();

        // 4.3 ล้างตะกร้าสินค้า
        $_SESSION['cart'] = [];
        unset($_SESSION['cart_restaurant_id']);

        // เด้งไปหน้าประวัติการสั่งซื้อ พร้อมโชว์แจ้งเตือนสำเร็จ
        header("Location: history.php?success=1");
        exit();

    } catch(PDOException $e) {
        // ถ้าระหว่างบันทึกมี Error ให้ยกเลิกการบันทึกทั้งหมด (Rollback)
        $conn->rollBack();
        $message = "<div class='alert alert-danger'><i class='bi bi-x-circle-fill me-2'></i> เกิดข้อผิดพลาดในการสั่งซื้อ: " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ยืนยันการสั่งซื้อ | FoodDelivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8f9fa; }
        .text-brand { color: #ff6b6b !important; }
        .bg-brand { background-color: #ff6b6b !important; }
        .btn-brand { background-color: #ff6b6b; border-color: #ff6b6b; color: white; }
        .btn-brand:hover { background-color: #ff5252; color: white; }
        
        .checkout-card { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .order-item-img { width: 60px; height: 60px; object-fit: cover; border-radius: 10px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top py-3 shadow-sm">
    <div class="container">
        <a class="navbar-brand text-brand fw-bold" href="index.php"><i class="bi bi-shop me-2"></i>FoodDelivery</a>
        <a href="cart.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> กลับไปแก้ไขตะกร้า
        </a>
    </div>
</nav>

<div class="container mt-5 mb-5">
    
    <?= $message; ?>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card checkout-card">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-2 text-center">
                    <h4 class="fw-bold"><i class="bi bi-check2-circle text-success me-2"></i> ยืนยันการสั่งซื้อ</h4>
                    <p class="text-muted mb-0">โปรดตรวจสอบรายการอาหารและข้อมูลติดต่อของคุณให้ถูกต้อง</p>
                </div>
                
                <div class="card-body p-4 p-md-5 pt-3">
                    
                    <div class="bg-light p-4 rounded-4 mb-4 border border-light">
                        <h6 class="fw-bold mb-3"><i class="bi bi-person-lines-fill text-brand me-2"></i> ข้อมูลผู้ติดต่อ (สำหรับ Rider)</h6>
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <p class="mb-1 text-muted small">ชื่อ-นามสกุล</p>
                                <p class="fw-bold mb-0"><?= htmlspecialchars($user_info['first_name'] . ' ' . $user_info['last_name']); ?></p>
                            </div>
                            <div class="col-sm-6">
                                <p class="mb-1 text-muted small">เบอร์โทรศัพท์ (จำเป็น)</p>
                                <p class="fw-bold mb-0 text-danger fs-5"><?= htmlspecialchars($user_info['phone']); ?></p>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold mb-3"><i class="bi bi-bag-check text-brand me-2"></i> สรุปรายการอาหารร้าน <span class="text-brand"><?= htmlspecialchars($restaurant_name); ?></span></h6>
                    
                    <ul class="list-group list-group-flush mb-4 border-top border-bottom">
                        <?php foreach($cart_items as $item): ?>
                            <li class="list-group-item py-3 px-0 d-flex justify-content-between align-items-center bg-transparent border-light">
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-secondary rounded-pill me-3"><?= $item['quantity']; ?>x</span>
                                    <div>
                                        <h6 class="mb-0 fw-bold"><?= htmlspecialchars($item['food_name']); ?></h6>
                                        <small class="text-muted">@ ฿<?= number_format($item['price'], 2); ?></small>
                                    </div>
                                </div>
                                <span class="fw-bold text-dark">฿<?= number_format($item['subtotal'], 2); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <div class="px-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">ยอดรวมค่าอาหาร</span>
                            <span>฿<?= number_format($total_price, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">ค่าจัดส่ง</span>
                            <span class="text-success">ฟรี (โปรโมชั่น)</span>
                        </div>
                        <div class="d-flex justify-content-between mt-3 pt-3 border-top">
                            <span class="fw-bold fs-5">ยอดที่ต้องชำระ (เงินสด)</span>
                            <span class="fw-bold fs-4 text-brand">฿<?= number_format($net_price, 2); ?></span>
                        </div>
                    </div>

                    <form action="checkout.php" method="POST" class="mt-5">
                        <div class="d-grid gap-2">
                            <button type="submit" name="confirm_order" class="btn btn-brand btn-lg rounded-pill fw-bold shadow-sm">
                                ยืนยันการสั่งซื้อและรอรับอาหาร
                            </button>
                            <a href="cart.php" class="btn btn-light rounded-pill text-muted mt-2">ขอยกเลิกและแก้ไขตะกร้า</a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>