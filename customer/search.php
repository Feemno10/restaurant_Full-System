<?php
// ไฟล์: customer/search.php
session_start();

// ตรวจสอบสิทธิ์
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';

// รับค่าจาก URL
$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
$category_id = isset($_GET['cat']) ? $_GET['cat'] : '';

try {
    // 1. ดึงหมวดหมู่ทั้งหมดสำหรับทำปุ่ม Chips
    $stmt_cat = $conn->prepare("SELECT * FROM restaurant_categories");
    $stmt_cat->execute();
    $categories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

    // 2. ดึงข้อมูลร้านอาหารตามเงื่อนไขค้นหา
    $sql = "SELECT r.restaurant_id, r.restaurant_name, r.address, c.category_name 
            FROM restaurants r 
            LEFT JOIN restaurant_categories c ON r.category_id = c.category_id 
            JOIN users u ON r.user_id = u.user_id 
            WHERE u.status = 'approved'";
    
    $params = [];

    if (!empty($keyword)) {
        $sql .= " AND r.restaurant_name LIKE :keyword";
        $params[':keyword'] = "%$keyword%";
    }

    if (!empty($category_id)) {
        $sql .= " AND r.category_id = :category_id";
        $params[':category_id'] = $category_id;
    }

    $sql .= " ORDER BY r.restaurant_name ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $restaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $result_count = count($restaurants);

} catch(PDOException $e) {
    die("เกิดข้อผิดพลาด: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ค้นหาเมนูอร่อย | FoodDelivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8f9fa; }
        .text-brand { color: #ff6b6b !important; }
        .bg-brand { background-color: #ff6b6b !important; }
        
        /* แถบ Header สำหรับค้นหาโดยเฉพาะ */
        .search-header {
            background-color: #fff;
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 1020;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        
        .search-input-wrapper {
            position: relative;
            flex-grow: 1;
        }
        .search-input-wrapper .bi-search {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #ff6b6b;
            font-size: 1.2rem;
        }
        .search-input {
            width: 100%;
            padding: 12px 20px 12px 45px;
            border-radius: 50px;
            border: 1px solid #ffeaea;
            background-color: #fff5f5;
            font-size: 1rem;
            transition: all 0.3s;
        }
        .search-input:focus {
            outline: none;
            background-color: #fff;
            border-color: #ff6b6b;
            box-shadow: 0 0 0 4px rgba(255, 107, 107, 0.1);
        }
        
        /* ปุ่มหมวดหมู่ (Chips) แบบเลื่อนได้ */
        .chips-container {
            display: flex;
            overflow-x: auto;
            gap: 10px;
            padding: 15px 0;
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        .chips-container::-webkit-scrollbar { display: none; }
        
        .chip {
            white-space: nowrap;
            padding: 8px 20px;
            border-radius: 50px;
            background-color: #fff;
            color: #555;
            border: 1px solid #ddd;
            text-decoration: none;
            font-weight: 500;
            transition: 0.2s;
        }
        .chip:hover { background-color: #fff5f5; color: #ff6b6b; border-color: #ff6b6b; }
        .chip.active { background-color: #ff6b6b; color: #fff; border-color: #ff6b6b; }

        /* การ์ดร้านอาหาร */
        .restaurant-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            transition: 0.3s;
        }
        .restaurant-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.08); }
        .restaurant-img { height: 160px; object-fit: cover; }
    </style>
</head>
<body>

<div class="search-header">
    <div class="container d-flex align-items-center gap-3">
        <a href="index.php" class="text-dark fs-4"><i class="bi bi-arrow-left"></i></a>
        
        <form action="search.php" method="GET" class="search-input-wrapper m-0">
            <i class="bi bi-search"></i>
            <input type="text" name="q" class="search-input" placeholder="ค้นหาชาบู, สุกี้ หรือร้านที่คุณชอบ..." value="<?= htmlspecialchars($keyword); ?>" autofocus>
            <?php if(!empty($category_id)): ?>
                <input type="hidden" name="cat" value="<?= htmlspecialchars($category_id); ?>">
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="container mb-5">
    
    <div class="chips-container mt-2">
        <a href="search.php<?= !empty($keyword) ? '?q='.urlencode($keyword) : '' ?>" class="chip <?= empty($category_id) ? 'active' : '' ?>">🍽️ ทั้งหมด</a>
        <?php foreach($categories as $cat): ?>
            <a href="search.php?cat=<?= $cat['category_id'] ?><?= !empty($keyword) ? '&q='.urlencode($keyword) : '' ?>" 
               class="chip <?= ($category_id == $cat['category_id']) ? 'active' : '' ?>">
                <?= htmlspecialchars($cat['category_name']); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="mt-3 mb-4">
        <?php if(!empty($keyword)): ?>
            <h5 class="fw-bold mb-1">ผลการค้นหา: <span class="text-brand">"<?= htmlspecialchars($keyword); ?>"</span></h5>
        <?php else: ?>
            <h5 class="fw-bold mb-1">ร้านอาหารทั้งหมด</h5>
        <?php endif; ?>
        <p class="text-muted small">พบทั้งหมด <?= $result_count; ?> ร้าน</p>
    </div>

    <div class="row g-4">
        <?php if($result_count > 0): ?>
            <?php foreach($restaurants as $rest): ?>
                <div class="col-md-4 col-lg-3">
                    <a href="restaurant_detail.php?id=<?= $rest['restaurant_id'] ?>" class="text-decoration-none text-dark">
                        <div class="card restaurant-card shadow-sm h-100 bg-white">
                            <img src="https://images.unsplash.com/photo-1544025162-8311029c1356?w=500&q=80" class="card-img-top restaurant-img" alt="ภาพร้าน">
                            <div class="card-body p-3">
                                <h6 class="card-title fw-bold text-truncate mb-2"><?= htmlspecialchars($rest['restaurant_name']); ?></h6>
                                <span class="badge bg-light text-brand border mb-2"><i class="bi bi-tag-fill me-1"></i> <?= htmlspecialchars($rest['category_name'] ?? 'ไม่มีหมวดหมู่'); ?></span>
                                <p class="card-text text-muted small text-truncate mb-0">
                                    <i class="bi bi-geo-alt-fill text-danger"></i> <?= htmlspecialchars($rest['address'] ?? 'ไม่ระบุที่อยู่'); ?>
                                </p>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <div class="bg-white rounded-4 shadow-sm p-5 d-inline-block">
                    <i class="bi bi-search display-1 text-muted opacity-25 mb-3"></i>
                    <h5 class="fw-bold">ไม่พบร้านอาหาร</h5>
                    <p class="text-muted mb-0">ลองพิมพ์ชื่อร้านใหม่อีกครั้ง หรือเลือกดูจากหมวดหมู่ด้านบน</p>
                    <a href="search.php" class="btn btn-outline-danger rounded-pill mt-4 px-4">ดูร้านทั้งหมด</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>