<?php
// ไฟล์: customer/index.php
session_start();

// 1. ตรวจสอบสิทธิ์ว่าล็อกอินหรือยัง และเป็น customer หรือไม่
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    // ถ้าไม่ใช่ลูกค้า ให้เด้งกลับไปหน้า login
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';

try {
    // 2. ดึงข้อมูลหมวดหมู่ร้านอาหารทั้งหมด
    $stmt_cat = $conn->prepare("SELECT * FROM restaurant_categories");
    $stmt_cat->execute();
    $categories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

    // 3. ดึงข้อมูลร้านอาหารล่าสุด 8 ร้าน (*** เพิ่ม r.restaurant_img ในคำสั่ง SELECT ***)
    $sql = "SELECT r.restaurant_id, r.restaurant_name, r.address, r.restaurant_img, c.category_name 
            FROM restaurants r 
            LEFT JOIN restaurant_categories c ON r.category_id = c.category_id 
            JOIN users u ON r.user_id = u.user_id 
            WHERE u.status = 'approved'
            ORDER BY r.restaurant_id DESC LIMIT 8";
    
    $stmt_rest = $conn->prepare($sql);
    $stmt_rest->execute();
    $restaurants = $stmt_rest->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $error_msg = "ไม่สามารถดึงข้อมูลได้: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าแรก | FoodDelivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8f9fa; }
        .text-brand { color: #ff6b6b !important; }
        .bg-brand { background-color: #ff6b6b !important; }
        .btn-brand { background-color: #ff6b6b; border-color: #ff6b6b; color: white; }
        .btn-brand:hover { background-color: #ff5252; color: white; }
        
        .navbar { box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        
        /* สไตล์หมวดหมู่หน้าแรก */
        .category-scroll {
            display: flex;
            overflow-x: auto;
            gap: 15px;
            padding-bottom: 10px;
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        .category-scroll::-webkit-scrollbar { display: none; }
        
        .cat-card {
            min-width: 100px;
            text-align: center;
            text-decoration: none;
            color: #333;
            transition: 0.3s;
        }
        .cat-icon-box {
            width: 70px;
            height: 70px;
            background-color: #fff;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px auto;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            font-size: 1.8rem;
            color: #ff6b6b;
            transition: 0.3s;
        }
        .cat-card:hover .cat-icon-box {
            background-color: #ff6b6b;
            color: #fff;
            transform: translateY(-5px);
        }

        /* การ์ดร้านอาหาร */
        .restaurant-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            transition: 0.3s;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .restaurant-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.08) !important;
        }
        .restaurant-img { height: 160px; object-fit: cover; background-color: #eee; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top py-3">
    <div class="container">
        <a class="navbar-brand text-brand fw-bold" href="index.php"><i class="bi bi-shop me-2"></i>FoodDelivery</a>
        
        <a href="cart.php" class="btn btn-light position-relative d-lg-none ms-auto me-2">
            <i class="bi bi-cart3 fs-5"></i>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">0</span>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <form class="d-flex mx-lg-auto my-2 my-lg-0 w-100" style="max-width: 400px;" action="search.php" method="GET">
                <div class="input-group">
                    <input type="text" class="form-control bg-light border-0 px-4 rounded-start-pill" name="q" placeholder="ค้นหาร้านอาหาร เมนูที่ใช่...">
                    <button class="btn btn-light bg-light border-0 text-danger rounded-end-pill pe-4" type="submit"><i class="bi bi-search"></i></button>
                </div>
            </form>

            <ul class="navbar-nav ms-auto align-items-center mt-3 mt-lg-0">
                <li class="nav-item d-none d-lg-block me-3">
                    <a href="cart.php" class="btn btn-light position-relative rounded-pill px-3">
                        <i class="bi bi-cart3 fs-5 me-1"></i> ตะกร้า
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light">0</span>
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle fw-bold" href="#" data-bs-toggle="dropdown">
                        <img src="../assets/uploads/profiles/<?= $_SESSION['profile_img'] ?? 'default.png' ?>" 
                             alt="Profile" class="rounded-circle me-1 border" width="32" height="32" style="object-fit: cover;" onerror="this.src='https://via.placeholder.com/32';">
                        คุณ <?= htmlspecialchars($_SESSION['full_name']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2">
                        <li><a class="dropdown-item py-2" href="profile.php"><i class="bi bi-person-gear me-2"></i>จัดการข้อมูลส่วนตัว</a></li>
                        <li><a class="dropdown-item py-2" href="history.php"><i class="bi bi-clock-history me-2"></i>ประวัติการสั่งอาหาร</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item py-2 text-danger" href="../logout.php"><i class="bi bi-box-arrow-right me-2"></i>ออกจากระบบ</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4 mb-5">
    
    <div class="bg-brand rounded-4 p-4 p-md-5 mb-4 text-white shadow-sm position-relative overflow-hidden">
        <div class="position-relative" style="z-index: 2;">
            <h2 class="fw-bold mb-2">หิวเมื่อไหร่ ก็สั่งเลย! 👋</h2>
            <p class="mb-4 fs-5 opacity-75">ส่งตรงความอร่อยถึงที่ รวดเร็ว ทันใจ</p>
            
            <form action="search.php" method="GET" class="bg-white p-2 rounded-pill shadow-sm d-flex" style="max-width: 500px;">
                <span class="d-flex align-items-center ps-3 text-muted"><i class="bi bi-geo-alt-fill text-danger"></i></span>
                <input type="text" name="q" class="form-control border-0 shadow-none bg-transparent" placeholder="คุณอยากทานอะไรวันนี้?">
                <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold">ค้นหา</button>
            </form>
        </div>
        <i class="bi bi-cup-hot position-absolute opacity-10" style="font-size: 15rem; right: -20px; top: -50px; transform: rotate(15deg);"></i>
    </div>

    <div class="mb-5">
        <div class="d-flex justify-content-between align-items-end mb-3">
            <h5 class="fw-bold mb-0">หมวดหมู่ยอดนิยม</h5>
            <a href="search.php" class="text-danger text-decoration-none small fw-bold">ดูทั้งหมด</a>
        </div>
        
        <div class="category-scroll pb-2">
            <?php foreach($categories as $cat): ?>
                <a href="search.php?cat=<?= $cat['category_id'] ?>" class="cat-card">
                    <div class="cat-icon-box">
                        <i class="bi bi-shop"></i>
                    </div>
                    <span class="small fw-bold"><?= htmlspecialchars($cat['category_name']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <h5 class="fw-bold mb-3">ร้านอาหารแนะนำสำหรับคุณ</h5>
    <div class="row g-4">
        <?php if(!empty($restaurants)): ?>
            <?php foreach($restaurants as $rest): ?>
                <div class="col-md-4 col-lg-3">
                    <a href="restaurant_detail.php?id=<?= $rest['restaurant_id'] ?>" class="card restaurant-card shadow-sm h-100 bg-white border-0">
                        
                        <?php 
                            $image_path = !empty($rest['restaurant_img']) 
                                          ? "../assets/uploads/restaurants/" . $rest['restaurant_img'] 
                                          : "https://via.placeholder.com/500x300?text=FoodDelivery";
                        ?>
                        <img src="<?= $image_path ?>" class="card-img-top restaurant-img" alt="ภาพร้าน" onerror="this.src='https://via.placeholder.com/500x300?text=FoodDelivery';">
                        
                        <div class="card-body p-3">
                            <h6 class="card-title fw-bold text-truncate mb-2"><?= htmlspecialchars($rest['restaurant_name']); ?></h6>
                            <span class="badge bg-light text-brand border mb-2"><i class="bi bi-tag-fill me-1"></i> <?= htmlspecialchars($rest['category_name'] ?? 'ไม่มีหมวดหมู่'); ?></span>
                            <p class="card-text text-muted small text-truncate mb-0">
                                <i class="bi bi-geo-alt-fill text-danger"></i> <?= htmlspecialchars($rest['address'] ?? 'ไม่ระบุที่อยู่'); ?>
                            </p>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5 mt-3 bg-white rounded-4 shadow-sm">
                <i class="bi bi-emoji-frown display-1 text-muted opacity-25 mb-3"></i>
                <h5 class="text-muted fw-bold">ยังไม่มีร้านอาหารในระบบ</h5>
                <p class="text-muted">กำลังรอให้ผู้ดูแลระบบอนุมัติร้านค้าใหม่ๆ เข้ามาครับ</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>