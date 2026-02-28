<?php
// ไฟล์: customer/history.php
session_start();

// 1. ตรวจสอบสิทธิ์ว่าล็อกอินหรือยัง
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';
$user_id = $_SESSION['user_id'];

try {
    // 2. ดึงข้อมูลบิลหลัก (Orders) ของลูกค้ารายนี้ เรียงจากใหม่ไปเก่า
    $stmt_orders = $conn->prepare("
        SELECT o.order_id, o.total_price, o.net_price, o.status, o.order_date, r.restaurant_name 
        FROM orders o
        JOIN restaurants r ON o.restaurant_id = r.restaurant_id
        WHERE o.customer_id = :user_id
        ORDER BY o.order_date DESC
    ");
    $stmt_orders->execute([':user_id' => $user_id]);
    $orders = $stmt_orders->fetchAll(PDO::FETCH_ASSOC);

    // 3. ดึงรายละเอียดรายการอาหาร (Order Details) ของทุกออเดอร์ของลูกค้ารายนี้
    $stmt_details = $conn->prepare("
        SELECT od.order_id, od.quantity, od.price, f.food_name 
        FROM order_details od
        JOIN foods f ON od.food_id = f.food_id
        JOIN orders o ON od.order_id = o.order_id
        WHERE o.customer_id = :user_id
    ");
    $stmt_details->execute([':user_id' => $user_id]);
    $details_flat = $stmt_details->fetchAll(PDO::FETCH_ASSOC);

    // 4. จัดกลุ่มรายการอาหารให้เข้ากับ order_id เพื่อง่ายต่อการวนลูปแสดงผล
    $order_details = [];
    foreach ($details_flat as $detail) {
        $order_details[$detail['order_id']][] = $detail;
    }

} catch(PDOException $e) {
    die("เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage());
}

// ฟังก์ชันแปลงสถานะเป็นภาษาไทยและสีของ Badge
function getStatusBadge($status) {
    switch ($status) {
        case 'pending':
            return '<span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> รอยืนยันจากร้าน</span>';
        case 'preparing':
            return '<span class="badge bg-info text-dark"><i class="bi bi-fire"></i> กำลังเตรียมอาหาร</span>';
        case 'delivering':
            return '<span class="badge bg-primary"><i class="bi bi-motorcycle"></i> กำลังจัดส่ง</span>';
        case 'completed':
            return '<span class="badge bg-success"><i class="bi bi-check-circle-fill"></i> จัดส่งสำเร็จ</span>';
        case 'cancelled':
            return '<span class="badge bg-danger"><i class="bi bi-x-circle-fill"></i> ยกเลิกแล้ว</span>';
        default:
            return '<span class="badge bg-secondary">ไม่ทราบสถานะ</span>';
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติการสั่งอาหาร | FoodDelivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8f9fa; }
        .text-brand { color: #ff6b6b !important; }
        .bg-brand { background-color: #ff6b6b !important; }
        .btn-brand { background-color: #ff6b6b; border-color: #ff6b6b; color: white; }
        .btn-brand:hover { background-color: #ff5252; color: white; }
        
        .order-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            transition: 0.3s;
        }
        .order-card:hover { box-shadow: 0 8px 25px rgba(0,0,0,0.08); }
        .order-header {
            background-color: #fff;
            border-bottom: 2px dashed #eee;
            padding: 15px 20px;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
        }
        .order-body { padding: 20px; background-color: #fff; }
        .order-footer {
            background-color: #fafafa;
            padding: 15px 20px;
            border-bottom-left-radius: 15px;
            border-bottom-right-radius: 15px;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top py-3 shadow-sm">
    <div class="container">
        <a class="navbar-brand text-brand fw-bold" href="index.php"><i class="bi bi-shop me-2"></i>FoodDelivery</a>
        <a href="index.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> กลับหน้าแรก
        </a>
    </div>
</nav>

<div class="container mt-5 mb-5">
    
    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2 fs-5"></i> <strong>สั่งอาหารสำเร็จ!</strong> ออเดอร์ของคุณถูกส่งไปยังร้านอาหารแล้ว โปรดรอการยืนยันครับ
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex align-items-center mb-4">
        <h3 class="fw-bold mb-0"><i class="bi bi-clock-history text-brand me-2"></i> ประวัติการสั่งอาหาร</h3>
        <span class="badge bg-light text-dark border ms-3 fs-6"><?= count($orders); ?> รายการ</span>
    </div>

    <?php if(empty($orders)): ?>
        <div class="text-center py-5 bg-white rounded-4 shadow-sm">
            <i class="bi bi-receipt display-1 text-muted opacity-25"></i>
            <h4 class="mt-3 text-muted fw-bold">ยังไม่มีประวัติการสั่งอาหาร</h4>
            <p class="text-muted">เมื่อคุณสั่งอาหาร รายการทั้งหมดจะแสดงที่นี่ครับ</p>
            <a href="index.php" class="btn btn-brand rounded-pill px-4 mt-2">สั่งอาหารเลย</a>
        </div>
    <?php else: ?>
        
        <div class="row">
            <?php foreach($orders as $order): ?>
                <div class="col-lg-6">
                    <div class="card order-card">
                        
                        <div class="order-header d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="fw-bold mb-1"><i class="bi bi-shop text-brand me-1"></i> <?= htmlspecialchars($order['restaurant_name']); ?></h6>
                                <small class="text-muted">
                                    <i class="bi bi-calendar2-date"></i> <?= date('d/m/Y H:i', strtotime($order['order_date'])); ?> 
                                    | ออเดอร์ #<?= str_pad($order['order_id'], 5, '0', STR_PAD_LEFT); ?>
                                </small>
                            </div>
                            <div>
                                <?= getStatusBadge($order['status']); ?>
                            </div>
                        </div>
                        
                        <div class="order-body">
                            <ul class="list-unstyled mb-0">
                                <?php 
                                    $items = $order_details[$order['order_id']] ?? []; 
                                    foreach($items as $item):
                                ?>
                                    <li class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom border-light">
                                        <div class="text-truncate me-3">
                                            <span class="fw-bold text-dark me-2"><?= $item['quantity']; ?>x</span> 
                                            <?= htmlspecialchars($item['food_name']); ?>
                                        </div>
                                        <span class="text-muted small">฿<?= number_format($item['price'] * $item['quantity'], 2); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        
                        <div class="order-footer d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small d-block">ยอดสุทธิ</span>
                                <span class="fw-bold fs-5 text-brand">฿<?= number_format($order['net_price'], 2); ?></span>
                            </div>
                            
                            <?php if($order['status'] == 'completed'): ?>
                                <a href="review.php?order_id=<?= $order['order_id']; ?>" class="btn btn-outline-warning rounded-pill btn-sm fw-bold">
                                    <i class="bi bi-star-fill"></i> รีวิวอาหาร
                                </a>
                            <?php elseif($order['status'] == 'pending'): ?>
                                <button class="btn btn-light rounded-pill btn-sm text-muted" disabled>
                                    <i class="bi bi-arrow-clockwise"></i> รอร้านรับออเดอร์
                                </button>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>