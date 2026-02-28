<?php
// ไฟล์: customer/cart.php
session_start();

// 1. ตรวจสอบสิทธิ์
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';

// สร้างตะกร้าใน Session ถ้ายังไม่มี
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// ---------------------------------------------------------
// ส่วนที่ 1: จัดการคำสั่งเพิ่ม/ลด/ลบ
// ---------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    $food_id = $_POST['food_id'] ?? 0;
    $restaurant_id = $_POST['restaurant_id'] ?? 0;
    
    if ($action == 'add') {
        $qty = (int)($_POST['quantity'] ?? 1);
        if (isset($_SESSION['cart_restaurant_id']) && $_SESSION['cart_restaurant_id'] != $restaurant_id && !empty($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        $_SESSION['cart_restaurant_id'] = $restaurant_id;
        if (isset($_SESSION['cart'][$food_id])) {
            $_SESSION['cart'][$food_id] += $qty;
        } else {
            $_SESSION['cart'][$food_id] = $qty;
        }
        header("Location: restaurant_detail.php?id=$restaurant_id&add_success=1");
        exit();
    }
    
    if ($action == 'update') {
        $qty = (int)($_POST['quantity'] ?? 1);
        if ($qty > 0) {
            $_SESSION['cart'][$food_id] = $qty;
        } else {
            unset($_SESSION['cart'][$food_id]);
        }
        header("Location: cart.php");
        exit();
    }
}

if (isset($_GET['action'])) {
    if ($_GET['action'] == 'remove' && isset($_GET['id'])) {
        unset($_SESSION['cart'][$_GET['id']]);
    } elseif ($_GET['action'] == 'clear') {
        $_SESSION['cart'] = [];
        unset($_SESSION['cart_restaurant_id']);
    }
    if (empty($_SESSION['cart'])) unset($_SESSION['cart_restaurant_id']);
    header("Location: cart.php");
    exit();
}

// ---------------------------------------------------------
// ส่วนที่ 2: ดึงข้อมูลและคำนวณราคา (ข้อ 3.3.11)
// ---------------------------------------------------------
$cart_items = [];
$total_price = 0;
$discount_percent = 0;
$restaurant_name = '';

if (!empty($_SESSION['cart'])) {
    $food_ids = implode(',', array_map('intval', array_keys($_SESSION['cart'])));
    try {
        $sql = "SELECT f.*, r.restaurant_name, r.discount_percent 
                FROM foods f 
                JOIN restaurants r ON f.restaurant_id = r.restaurant_id 
                WHERE f.food_id IN ($food_ids)";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $foods = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($foods as $row) {
            $qty = $_SESSION['cart'][$row['food_id']];
            $row['quantity'] = $qty;
            $row['subtotal'] = $row['price'] * $qty;
            $total_price += $row['subtotal'];
            $cart_items[] = $row;
            $restaurant_name = $row['restaurant_name'];
            $discount_percent = $row['discount_percent']; // ดึงส่วนลด (ข้อ 3.2.12)
        }
    } catch(PDOException $e) { $error = $e->getMessage(); }
}

$discount_amount = ($total_price * $discount_percent) / 100;
$net_price = $total_price - $discount_amount;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตะกร้าสินค้า | FoodDelivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Prompt', sans-serif; background-color: #f8f9fa; }
        .text-brand { color: #ff6b6b !important; }
        .bg-brand { background-color: #ff6b6b !important; }
        .card-custom { border: none; border-radius: 25px; box-shadow: 0 10px 40px rgba(0,0,0,0.04); }
        .cart-img { width: 75px; height: 75px; object-fit: cover; border-radius: 15px; }
        /* สไตล์ปุ่มตามรูป image_58aad7.png */
        .btn-checkout { 
            background: white; 
            color: #333; 
            border-radius: 50px; 
            padding: 18px; 
            font-weight: 700; 
            border: 1px solid #eee;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: 0.3s;
            width: 100%;
        }
        .btn-checkout:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); background: #fff; }
    </style>
</head>
<body>

<nav class="navbar navbar-light bg-white sticky-top py-3 shadow-sm">
    <div class="container">
        <a class="navbar-brand text-brand fw-bold" href="index.php"><i class="bi bi-shop me-2"></i>FoodDelivery</a>
        <a href="index.php" class="btn btn-outline-dark btn-sm rounded-pill px-4">เลือกอาหารเพิ่ม</a>
    </div>
</nav>

<div class="container mt-5 mb-5">
    <h3 class="fw-bold mb-5 text-center text-md-start">ตะกร้าสินค้าของคุณ</h3>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card card-custom p-4 p-md-5">
                <?php if(empty($cart_items)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-bag-heart display-1 text-muted opacity-25"></i>
                        <h4 class="mt-4 text-muted">ยังไม่มีรายการในตะกร้า</h4>
                        <a href="index.php" class="btn btn-brand rounded-pill mt-3 px-5 py-2 text-white fw-bold shadow-sm">ไปดูร้านอร่อยกันเลย</a>
                    </div>
                <?php else: ?>
                    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                        <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-shop me-2 text-brand"></i><?= htmlspecialchars($restaurant_name) ?></h5>
                        <a href="cart.php?action=clear" class="text-muted text-decoration-none small hover-danger"><i class="bi bi-trash3 me-1"></i>ล้างตะกร้า</a>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle table-borderless">
                            <tbody>
                                <?php foreach($cart_items as $item): ?>
                                <tr>
                                    <td style="width: 90px;">
                                        <img src="../assets/uploads/foods/<?= $item['food_img'] ?>" class="cart-img" onerror="this.src='https://via.placeholder.com/75';">
                                    </td>
                                    <td>
                                        <h6 class="fw-bold mb-0"><?= htmlspecialchars($item['food_name']) ?></h6>
                                        <span class="text-muted small">฿<?= number_format($item['price'], 2) ?></span>
                                    </td>
                                    <td style="width: 130px;">
                                        <form action="cart.php" method="POST">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="food_id" value="<?= $item['food_id'] ?>">
                                            <div class="input-group input-group-sm">
                                                <input type="number" name="quantity" class="form-control text-center fw-bold rounded-3 bg-light border-0" 
                                                       value="<?= $item['quantity'] ?>" min="1" max="99" onchange="this.form.submit()">
                                            </div>
                                        </form>
                                    </td>
                                    <td class="text-end fw-bold text-dark" style="width: 120px;">
                                        ฿<?= number_format($item['subtotal'], 2) ?>
                                    </td>
                                    <td class="text-end" style="width: 40px;">
                                        <a href="cart.php?action=remove&id=<?= $item['food_id'] ?>" class="text-danger opacity-50"><i class="bi bi-x-circle-fill"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-custom p-4 p-md-5 bg-white">
                <h5 class="fw-bold mb-4">สรุปราคาสุทธิ</h5>
                
                <div class="d-flex justify-content-between mb-3">
                    <span class="text-muted">ราคารวม</span>
                    <span class="fw-bold">฿<?= number_format($total_price, 2) ?></span>
                </div>

                <?php if($discount_percent > 0): ?>
                <div class="d-flex justify-content-between mb-3 text-danger font-bold">
                    <span>ส่วนลดร้านค้า (<?= $discount_percent ?>%)</span>
                    <span>- ฿<?= number_format($discount_amount, 2) ?></span>
                </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between align-items-center mb-5 mt-4 pt-3 border-top">
                    <span class="fw-bold fs-5">ราคาสุทธิ</span>
                    <span class="fw-black fs-2 text-brand">฿<?= number_format($net_price, 2) ?></span>
                </div>

                <?php if(!empty($cart_items)): ?>
                    <form action="confirm_order.php" method="POST">
                        <input type="hidden" name="restaurant_id" value="<?= $_SESSION['cart_restaurant_id']; ?>">
                        <input type="hidden" name="total_price" value="<?= $total_price ?>">
                        <input type="hidden" name="discount_percent" value="<?= $discount_percent ?>">
                        <input type="hidden" name="net_price" value="<?= $net_price ?>">
                        
                        <button type="submit" name="submit_order" class="btn-checkout mb-3">
                            สั่งซื้อเลย <i class="bi bi-cart-check ms-2"></i>
                        </button>
                    </form>
                    <p class="text-center text-muted small mb-0">สั่งจากร้าน "<?= htmlspecialchars($restaurant_name) ?>"</p>
                <?php else: ?>
                    <button class="btn btn-secondary w-100 rounded-pill py-3 fw-bold disabled" disabled>
                        ไม่มีสินค้าในตะกร้า
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>