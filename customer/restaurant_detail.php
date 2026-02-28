<?php
// ไฟล์: customer/restaurant_detail.php
session_start();

// ตรวจสอบสิทธิ์
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';

// ตรวจสอบว่ามีการส่ง id ร้านอาหารมาหรือไม่
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$restaurant_id = $_GET['id'];

try {
    // 1. ดึงข้อมูลรายละเอียดของร้านอาหาร (รวมถึงรูปภาพร้าน)
    $stmt_rest = $conn->prepare("
        SELECT r.*, c.category_name 
        FROM restaurants r 
        LEFT JOIN restaurant_categories c ON r.category_id = c.category_id 
        WHERE r.restaurant_id = :id
    ");
    $stmt_rest->execute([':id' => $restaurant_id]);
    $restaurant = $stmt_rest->fetch(PDO::FETCH_ASSOC);

    if (!$restaurant) {
        die("<div style='text-align:center; margin-top:50px;'><h3>ไม่พบข้อมูลร้านอาหารนี้</h3><a href='index.php'>กลับหน้าแรก</a></div>");
    }

    // 2. ดึงข้อมูลหมวดหมู่อาหาร
    $stmt_cat = $conn->prepare("
        SELECT DISTINCT fc.food_cat_id, fc.name 
        FROM food_categories fc
        JOIN foods f ON fc.food_cat_id = f.food_cat_id
        WHERE fc.restaurant_id = :id
        ORDER BY fc.food_cat_id ASC
    ");
    $stmt_cat->execute([':id' => $restaurant_id]);
    $food_categories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

    // 3. ดึงรายการอาหาร
    $stmt_foods = $conn->prepare("
        SELECT * FROM foods 
        WHERE restaurant_id = :id 
        ORDER BY food_cat_id ASC, food_name ASC
    ");
    $stmt_foods->execute([':id' => $restaurant_id]);
    $foods = $stmt_foods->fetchAll(PDO::FETCH_ASSOC);

    $menu_by_category = [];
    foreach ($foods as $food) {
        $menu_by_category[$food['food_cat_id']][] = $food;
    }

} catch(PDOException $e) {
    die("เกิดข้อผิดพลาด: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($restaurant['restaurant_name']); ?> | เมนูอาหาร</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8f9fa; }
        .text-brand { color: #ff6b6b !important; }
        .bg-brand { background-color: #ff6b6b !important; }
        
        /* แก้ไข Banner ให้ดึงรูปจากฐานข้อมูล */
        .restaurant-banner {
            <?php 
                $res_img = !empty($restaurant['restaurant_img']) ? '../assets/uploads/restaurants/'.$restaurant['restaurant_img'] : 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=1200&q=80';
            ?>
            background: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.7)), url('<?= $res_img ?>') center center;
            background-size: cover;
            color: white;
            padding: 80px 0 40px 0;
            margin-bottom: 2rem;
            border-bottom-left-radius: 30px;
            border-bottom-right-radius: 30px;
        }

        .food-card { border: none; border-radius: 15px; overflow: hidden; transition: 0.3s; display: flex; flex-direction: row; background-color: #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.05); height: 100%; }
        .food-card:hover { transform: translateY(-3px); box-shadow: 0 8px 15px rgba(0,0,0,0.1); }
        .food-img { width: 120px; min-width: 120px; object-fit: cover; background-color: #eee; }
        .food-details { padding: 15px; flex-grow: 1; display: flex; flex-direction: column; }
        .food-price { font-size: 1.1rem; font-weight: bold; color: #ff6b6b; }
        .qty-input { width: 60px; text-align: center; border: 1px solid #ddd; border-radius: 8px;}
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark position-absolute w-100" style="z-index: 10; background: transparent;">
    <div class="container mt-2">
        <a href="index.php" class="btn btn-light rounded-circle shadow-sm" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
            <i class="bi bi-arrow-left text-dark fs-5"></i>
        </a>
        <div class="ms-auto">
            <a href="cart.php" class="btn btn-light position-relative rounded-pill px-3 shadow-sm">
                <i class="bi bi-cart3 fs-5 me-1 text-danger"></i> ตะกร้า
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light">
                    <?= isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0; ?>
                </span>
            </a>
        </div>
    </div>
</nav>

<div class="restaurant-banner shadow">
    <div class="container text-center text-md-start">
        <span class="badge bg-danger mb-2 px-3 py-2 rounded-pill fs-6"><i class="bi bi-tag-fill me-1"></i> <?= htmlspecialchars($restaurant['category_name'] ?? 'ร้านอาหาร'); ?></span>
        <h1 class="display-5 fw-bold mb-2 shadow-sm"><?= htmlspecialchars($restaurant['restaurant_name']); ?></h1>
        <p class="fs-5 opacity-75 mb-0"><i class="bi bi-geo-alt-fill text-danger me-1"></i> <?= htmlspecialchars($restaurant['address'] ?? 'ไม่ระบุที่อยู่'); ?></p>
    </div>
</div>

<div class="container mb-5 pb-5">
    
    <?php if(isset($_GET['add_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> เพิ่มสินค้าลงตะกร้าเรียบร้อยแล้ว!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if(empty($foods)): ?>
        <div class="text-center py-5">
            <i class="bi bi-journal-x display-1 text-muted opacity-25"></i>
            <h4 class="text-muted mt-3">ร้านนี้ยังไม่ได้เพิ่มเมนูอาหาร</h4>
        </div>
    <?php else: ?>
        <?php foreach($food_categories as $cat): ?>
            <?php if(isset($menu_by_category[$cat['food_cat_id']])): ?>
                <h4 class="fw-bold mt-5 mb-3 border-bottom pb-2 border-danger border-2 d-inline-block">
                    <?= htmlspecialchars($cat['name']); ?>
                </h4>
                <div class="row g-4 mb-4">
                    <?php foreach($menu_by_category[$cat['food_cat_id']] as $food): ?>
                        <div class="col-md-6 col-xl-4">
                            <div class="food-card">
                                <?php $img_src = !empty($food['food_img']) ? '../assets/uploads/foods/' . htmlspecialchars($food['food_img']) : 'https://via.placeholder.com/150?text=No+Image'; ?>
                                <img src="<?= $img_src ?>" class="food-img" alt="ภาพอาหาร" onerror="this.src='https://via.placeholder.com/150?text=No+Image';">
                                
                                <div class="food-details">
                                    <h6 class="fw-bold mb-1 text-truncate"><?= htmlspecialchars($food['food_name']); ?></h6>
                                    <div class="mt-auto pt-2 border-top d-flex justify-content-between align-items-center">
                                        <span class="food-price">฿<?= number_format($food['price'], 2); ?></span>
                                        
                                        <form action="cart.php" method="POST" class="d-flex align-items-center gap-2">
                                            <input type="hidden" name="action" value="add">
                                            <input type="hidden" name="food_id" value="<?= $food['food_id']; ?>">
                                            <input type="hidden" name="restaurant_id" value="<?= $restaurant_id; ?>">
                                            <input type="number" name="quantity" class="form-control form-control-sm qty-input" value="1" min="1" max="99">
                                            <button type="submit" class="btn btn-danger btn-sm rounded-circle shadow-sm" style="width: 32px; height: 32px;">
                                                <i class="bi bi-plus-lg"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="position-fixed bottom-0 start-0 w-100 bg-white p-3 shadow-lg border-top d-lg-none" style="z-index: 1030;">
    <a href="cart.php" class="btn btn-danger w-100 rounded-pill fw-bold fs-5 py-2">
        <i class="bi bi-cart-check me-2"></i> ดูตะกร้าสินค้า
    </a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>