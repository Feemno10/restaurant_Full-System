<?php
// ไฟล์: customer/review.php
session_start();

// 1. ตรวจสอบสิทธิ์ว่าล็อกอินหรือยัง และเป็น customer หรือไม่
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';

// ตรวจสอบว่ามีการส่ง order_id มาหรือไม่
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    header("Location: history.php");
    exit();
}

$order_id = $_GET['order_id'];
$user_id = $_SESSION['user_id'];
$message = '';

try {
    // 2. ตรวจสอบว่าออเดอร์นี้เป็นของลูกค้ารายนี้จริง และสถานะเป็น completed แล้วเท่านั้น
    $stmt_check = $conn->prepare("
        SELECT o.order_id, o.status, r.restaurant_name 
        FROM orders o
        JOIN restaurants r ON o.restaurant_id = r.restaurant_id
        WHERE o.order_id = :order_id AND o.customer_id = :user_id
    ");
    $stmt_check->execute([':order_id' => $order_id, ':user_id' => $user_id]);
    $order = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        die("<div class='container mt-5 text-center'><h4>ไม่พบข้อมูลออเดอร์นี้ หรือคุณไม่มีสิทธิ์เข้าถึง</h4><a href='history.php' class='btn btn-primary mt-3'>กลับหน้าประวัติ</a></div>");
    }

    if ($order['status'] !== 'completed') {
        die("<div class='container mt-5 text-center'><h4>ออเดอร์นี้ยังไม่เสร็จสิ้น ไม่สามารถรีวิวได้</h4><a href='history.php' class='btn btn-primary mt-3'>กลับหน้าประวัติ</a></div>");
    }

    // 3. ตรวจสอบว่าเคยรีวิวออเดอร์นี้ไปแล้วหรือยัง
    $stmt_review = $conn->prepare("SELECT * FROM reviews WHERE order_id = :order_id");
    $stmt_review->execute([':order_id' => $order_id]);
    $existing_review = $stmt_review->fetch(PDO::FETCH_ASSOC);

    // 4. บันทึกข้อมูลรีวิวลงฐานข้อมูล
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_review'])) {
        if (!$existing_review) {
            $rating = (int)$_POST['rating'];
            $comment = trim($_POST['comment']);

            // ตรวจสอบความถูกต้องของคะแนน
            if ($rating >= 1 && $rating <= 5) {
                $stmt_insert = $conn->prepare("
                    INSERT INTO reviews (order_id, customer_id, rating, comment) 
                    VALUES (:order_id, :customer_id, :rating, :comment)
                ");
                $stmt_insert->execute([
                    ':order_id' => $order_id,
                    ':customer_id' => $user_id,
                    ':rating' => $rating,
                    ':comment' => $comment
                ]);

                $message = "<div class='alert alert-success alert-dismissible fade show rounded-4 shadow-sm' role='alert'>
                                <i class='bi bi-check-circle-fill me-2 fs-5'></i> ขอบคุณสำหรับรีวิวครับ! ความคิดเห็นของคุณถูกส่งไปยังร้านค้าแล้ว
                                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                            </div>";
                
                // ดึงข้อมูลรีวิวมาแสดงผลทันทีหลังบันทึก
                $stmt_review->execute([':order_id' => $order_id]);
                $existing_review = $stmt_review->fetch(PDO::FETCH_ASSOC);
            } else {
                $message = "<div class='alert alert-danger'>กรุณาให้คะแนนดาว 1-5 ดาวครับ</div>";
            }
        }
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
    <title>รีวิวอาหาร | FoodDelivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8f9fa; }
        .text-brand { color: #ff6b6b !important; }
        .bg-brand { background-color: #ff6b6b !important; }
        .btn-brand { background-color: #ff6b6b; border-color: #ff6b6b; color: white; }
        .btn-brand:hover { background-color: #ff5252; color: white; }
        
        /* สไตล์สำหรับระบบดาว (ใช้เทคนิค CSS Flex Reverse คู่กับ Radio Buttons) */
        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: center;
            gap: 10px;
        }
        .star-rating input[type="radio"] { display: none; }
        .star-rating label {
            font-size: 3rem;
            color: #e4e5e9;
            cursor: pointer;
            transition: color 0.2s;
        }
        /* เมื่อนำเมาส์ไปชี้ดาว หรือกดเลือกดาว ให้เปลี่ยนเป็นสีเหลืองทอง */
        .star-rating input[type="radio"]:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: #ffc107;
        }

        .review-card { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top py-3 shadow-sm">
    <div class="container">
        <a class="navbar-brand text-brand fw-bold" href="index.php"><i class="bi bi-shop me-2"></i>FoodDelivery</a>
        <a href="history.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> กลับหน้าประวัติ
        </a>
    </div>
</nav>

<div class="container mt-5 mb-5">
    <?= $message; ?>

    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card review-card">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-2 text-center">
                    <h4 class="fw-bold"><i class="bi bi-star text-warning me-2"></i> รีวิวอาหารและบริการ</h4>
                    <p class="text-muted mb-0">ร้าน <span class="fw-bold text-brand"><?= htmlspecialchars($order['restaurant_name']); ?></span></p>
                    <p class="text-muted small">ออเดอร์ #<?= str_pad($order['order_id'], 5, '0', STR_PAD_LEFT); ?></p>
                </div>
                
                <div class="card-body p-4 p-md-5 pt-3">
                    
                    <?php if($existing_review): ?>
                        <div class="text-center py-4 bg-light rounded-4">
                            <h5 class="fw-bold mb-3 text-success"><i class="bi bi-check-circle-fill"></i> คุณได้รีวิวออเดอร์นี้แล้ว</h5>
                            <div class="mb-3">
                                <?php for($i=1; $i<=5; $i++): ?>
                                    <i class="bi bi-star-fill fs-3 <?= ($i <= $existing_review['rating']) ? 'text-warning' : 'text-secondary opacity-25' ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <?php if(!empty($existing_review['comment'])): ?>
                                <p class="fst-italic text-muted px-4">" <?= htmlspecialchars($existing_review['comment']); ?> "</p>
                            <?php endif; ?>
                            <a href="history.php" class="btn btn-brand rounded-pill mt-3 px-4">กลับหน้าประวัติ</a>
                        </div>
                    <?php else: ?>
                        <form action="review.php?order_id=<?= $order_id; ?>" method="POST">
                            
                            <div class="text-center mb-4">
                                <label class="form-label fw-bold d-block mb-2">ให้คะแนนความอร่อย</label>
                                <div class="star-rating">
                                    <input type="radio" id="star5" name="rating" value="5" required>
                                    <label for="star5" title="5 ดาว"><i class="bi bi-star-fill"></i></label>
                                    
                                    <input type="radio" id="star4" name="rating" value="4">
                                    <label for="star4" title="4 ดาว"><i class="bi bi-star-fill"></i></label>
                                    
                                    <input type="radio" id="star3" name="rating" value="3">
                                    <label for="star3" title="3 ดาว"><i class="bi bi-star-fill"></i></label>
                                    
                                    <input type="radio" id="star2" name="rating" value="2">
                                    <label for="star2" title="2 ดาว"><i class="bi bi-star-fill"></i></label>
                                    
                                    <input type="radio" id="star1" name="rating" value="1">
                                    <label for="star1" title="1 ดาว"><i class="bi bi-star-fill"></i></label>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="comment" class="form-label fw-bold">ข้อความรีวิว (ไม่บังคับ)</label>
                                <textarea class="form-control rounded-4 p-3 bg-light border-0" id="comment" name="comment" rows="4" placeholder="อาหารอร่อยไหม? บริการเป็นอย่างไรบ้าง? พิมพ์ข้อความที่นี่..."></textarea>
                            </div>

                            <div class="d-grid">
                                <button type="submit" name="submit_review" class="btn btn-brand btn-lg rounded-pill fw-bold shadow-sm">
                                    ส่งรีวิว <i class="bi bi-send-fill ms-1"></i>
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>