<?php
// ไฟล์: index.php
session_start();
require_once 'config/database.php';

// เช็คสถานะว่า Login หรือยัง (คืนค่าเป็น true หรือ false)
$is_logged_in = isset($_SESSION['user_id']);

try {
    $stmt_cat = $conn->prepare("SELECT * FROM restaurant_categories LIMIT 6");
    $stmt_cat->execute();
    $categories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

    $stmt_rest = $conn->prepare("
        SELECT r.restaurant_id, r.restaurant_name, c.category_name 
        FROM restaurants r 
        LEFT JOIN restaurant_categories c ON r.category_id = c.category_id 
        ORDER BY r.restaurant_id DESC LIMIT 8
    ");
    $stmt_rest->execute();
    $restaurants = $stmt_rest->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_msg = "ไม่สามารถเชื่อมต่อฐานข้อมูลได้: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าแรก | ระบบสั่งจองอาหารออนไลน์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8f9fa; }
        .text-brand { color: #ff6b6b !important; }
        .bg-brand { background-color: #ff6b6b !important; }
        .btn-brand { background-color: #ff6b6b; border-color: #ff6b6b; color: white; }
        .btn-brand:hover { background-color: #ff5252; border-color: #ff5252; color: white; }
        
        .navbar { box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .navbar-brand { font-weight: bold; font-size: 1.5rem; }
        
        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('https://images.unsplash.com/photo-1555396273-367ea4eb4db5?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80') center center;
            background-size: cover;
            color: white;
            padding: 100px 0;
            text-align: center;
        }
        .search-box {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 10px;
            border-radius: 50px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .search-box input { border: none; box-shadow: none; border-radius: 50px; padding-left: 20px;}
        .search-box input:focus { outline: none; box-shadow: none; }
        .search-box .btn { border-radius: 50px; padding: 10px 30px; }
        
        .restaurant-card { transition: transform 0.3s, box-shadow 0.3s; border: none; border-radius: 15px; overflow: hidden; }
        .restaurant-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .restaurant-img { height: 200px; object-fit: cover; background-color: #eee; }
        
        .cat-badge { background-color: #ffeaea; color: #ff6b6b; padding: 10px 20px; border-radius: 30px; font-weight: 600; text-decoration: none; display: inline-block; transition: 0.3s;}
        .cat-badge:hover { background-color: #ff6b6b; color: white; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top py-3">
    <div class="container">
        <a class="navbar-brand text-brand" href="index.php"><i class="bi bi-shop me-2"></i>FoodDelivery</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item"><a class="nav-link active" href="index.php">หน้าแรก</a></li>
                <li class="nav-item"><a class="nav-link" href="#restaurants">ร้านอาหาร</a></li>
                
                <?php if($is_logged_in): ?>
                    <li class="nav-item dropdown ms-3">
                        <a class="nav-link dropdown-toggle btn btn-light rounded-pill px-4" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle text-brand me-1"></i> <?= htmlspecialchars($_SESSION['full_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                            <?php if($_SESSION['role'] == 'admin'): ?>
                                <li><a class="dropdown-item" href="admin/index.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                            <?php elseif($_SESSION['role'] == 'restaurant'): ?>
                                <li><a class="dropdown-item" href="restaurant/index.php"><i class="bi bi-shop me-2"></i>จัดการร้านอาหาร</a></li>
                            <?php elseif($_SESSION['role'] == 'rider'): ?>
                                <li><a class="dropdown-item" href="rider/index.php"><i class="bi bi-motorcycle me-2"></i>รับงาน</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item" href="customer/profile.php"><i class="bi bi-person me-2"></i>โปรไฟล์</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>ออกจากระบบ</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item ms-lg-3"><a class="nav-link fw-bold" href="login.php">เข้าสู่ระบบ</a></li>
                    <li class="nav-item ms-2"><a class="btn btn-brand rounded-pill px-4" href="register.php">สมัครสมาชิก</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<section class="hero-section">
    <div class="container">
        <h1 class="display-4 fw-bold mb-4 shadow-sm">หิวเมื่อไหร่ ก็สั่งเลย!</h1>
        <p class="lead mb-5 shadow-sm">รวมร้านอาหารอร่อย ส่งตรงถึงที่ รวดเร็ว ทันใจ</p>
        
        <div class="search-box d-flex">
            <input type="text" class="form-control form-control-lg auth-required" placeholder="ค้นหาร้านอาหาร หรือเมนูชาบูที่คุณชอบ...">
            <button class="btn btn-brand btn-lg auth-required" type="button"><i class="bi bi-search"></i> ค้นหา</button>
        </div>
    </div>
</section>

<div class="container mt-5 pt-3">
    <div class="mb-4"><h3 class="fw-bold"><i class="bi bi-grid-fill text-brand me-2"></i>หมวดหมู่ยอดนิยม</h3></div>
    <div class="d-flex flex-wrap gap-3 mb-5">
        <?php if(!empty($categories)): ?>
            <?php foreach($categories as $cat): ?>
                <a href="customer/search.php?category=<?= $cat['category_id'] ?>" class="cat-badge auth-required"><?= htmlspecialchars($cat['category_name']); ?></a>
            <?php endforeach; ?>
        <?php else: ?>
            <a href="#" class="cat-badge auth-required">ชาบู / สุกี้</a>
            <a href="#" class="cat-badge auth-required">อาหารตามสั่ง</a>
            <a href="#" class="cat-badge auth-required">เครื่องดื่ม / คาเฟ่</a>
            <a href="#" class="cat-badge auth-required">ของหวาน</a>
        <?php endif; ?>
    </div>

    <div class="mb-4" id="restaurants"><h3 class="fw-bold"><i class="bi bi-star-fill text-brand me-2"></i>ร้านอาหารแนะนำ</h3></div>
    <div class="row g-4 mb-5">
        <?php if(!empty($restaurants)): ?>
            <?php foreach($restaurants as $rest): ?>
                <div class="col-md-4 col-lg-3">
                    <div class="card restaurant-card shadow-sm h-100">
                        <img src="https://images.unsplash.com/photo-1544025162-8311029c1356?w=500&q=80" class="card-img-top restaurant-img" alt="Restaurant Image">
                        <div class="card-body">
                            <h5 class="card-title fw-bold text-truncate"><?= htmlspecialchars($rest['restaurant_name']); ?></h5>
                            <p class="card-text text-muted small"><i class="bi bi-tag-fill me-1"></i> <?= htmlspecialchars($rest['category_name'] ?? 'ไม่มีหมวดหมู่'); ?></p>
                        </div>
                        <div class="card-footer bg-white border-0 pb-3">
                            <a href="customer/restaurant_detail.php?id=<?= $rest['restaurant_id'] ?>" class="btn btn-outline-danger w-100 rounded-pill auth-required">ดูเมนูอาหาร</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-md-4 col-lg-3">
                <div class="card restaurant-card shadow-sm h-100">
                    <img src="https://images.unsplash.com/photo-1544025162-8311029c1356?w=500&q=80" class="card-img-top restaurant-img" alt="Shabu">
                    <div class="card-body">
                        <h5 class="card-title fw-bold text-truncate">ชาบูกะทะร้อน พังงา</h5>
                        <p class="card-text text-muted small"><i class="bi bi-tag-fill me-1"></i> ชาบู / สุกี้</p>
                    </div>
                    <div class="card-footer bg-white border-0 pb-3">
                        <a href="#" class="btn btn-outline-danger w-100 rounded-pill auth-required">ดูเมนูอาหาร</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<footer class="bg-dark text-white py-5 mt-5">
    <div class="container text-center">
        <h4 class="text-brand fw-bold mb-3"><i class="bi bi-shop me-2"></i>FoodDelivery</h4>
        <p class="mb-0 text-muted small">&copy; <?= date('Y'); ?> FoodDelivery Project.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // ดึงสถานะ Login จาก PHP มาเก็บใน JavaScript
        const isLoggedIn = <?= $is_logged_in ? 'true' : 'false' ?>;
        
        // หาปุ่มหรือลิงก์ทั้งหมดที่มีคลาส auth-required
        const authElements = document.querySelectorAll('.auth-required');
        
        authElements.forEach(function(el) {
            el.addEventListener('click', function(e) {
                if (!isLoggedIn) {
                    e.preventDefault(); // ยกเลิกการไปหน้าอื่น
                    alert('กรุณาเข้าสู่ระบบก่อนทำการเลือกดูเมนูหรือสั่งอาหารครับ');
                    window.location.href = 'login.php'; // เด้งไปหน้า Login ทันที
                }
            });
        });
    });
</script>

</body>
</html>