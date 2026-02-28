<?php
// ไฟล์: customer/profile.php
session_start();

// 1. ตรวจสอบสิทธิ์ว่าล็อกอินหรือยัง และเป็น customer หรือไม่
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';
$user_id = $_SESSION['user_id'];
$message = '';

// 2. จัดการเมื่อมีการกดปุ่ม Submit (POST Request)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // ---------------------------------------------------------
    // ฟังก์ชัน: 3.3.4 & 3.3.5 อัปเดตข้อมูลส่วนตัวและรูปภาพ
    // ---------------------------------------------------------
    if (isset($_POST['update_profile'])) {
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $phone = trim($_POST['phone']);
        
        // จัดการอัปโหลดรูปภาพ (ถ้ามีการเลือกไฟล์ใหม่)
        $profile_img = $_SESSION['profile_img']; // ใช้รูปเดิมเป็นค่าเริ่มต้น
        
        if (isset($_FILES['profile_img']) && $_FILES['profile_img']['error'] == 0) {
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
            $file_name = $_FILES['profile_img']['name'];
            $file_tmp = $_FILES['profile_img']['tmp_name'];
            
            // หา Extension ของไฟล์
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed_ext)) {
                // ตั้งชื่อไฟล์ใหม่ป้องกันชื่อซ้ำ
                $new_file_name = 'user_' . $user_id . '_' . time() . '.' . $ext;
                
                // กำหนดโฟลเดอร์เป้าหมาย
                $upload_dir = '../assets/uploads/profiles/';
                $upload_path = $upload_dir . $new_file_name;
                
                // --- ส่วนที่เพิ่มเข้ามา: ตรวจสอบและสร้างโฟลเดอร์อัตโนมัติ ---
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true); 
                }
                
                if (move_uploaded_file($file_tmp, $upload_path)) {
                    $profile_img = $new_file_name;
                } else {
                    $message = "<div class='alert alert-danger'>เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ (ไม่สามารถบันทึกไฟล์ได้)</div>";
                }
            } else {
                $message = "<div class='alert alert-warning'>รองรับเฉพาะไฟล์รูปภาพ .jpg, .jpeg, .png, .gif เท่านั้น</div>";
            }
        }
        
        // อัปเดตข้อมูลลงฐานข้อมูล
        if (empty($message) || strpos($message, 'alert-success') !== false || strpos($message, 'alert-warning') === false && strpos($message, 'alert-danger') === false) {
            try {
                $stmt = $conn->prepare("UPDATE users SET first_name = :fname, last_name = :lname, phone = :phone, profile_img = :img WHERE user_id = :id");
                $stmt->execute([
                    ':fname' => $first_name,
                    ':lname' => $last_name,
                    ':phone' => $phone,
                    ':img' => $profile_img,
                    ':id' => $user_id
                ]);
                
                // อัปเดต Session ให้แสดงข้อมูลใหม่ทันที
                $_SESSION['full_name'] = $first_name . ' ' . $last_name;
                $_SESSION['profile_img'] = $profile_img;
                
                $message = "<div class='alert alert-success alert-dismissible fade show'><i class='bi bi-check-circle-fill me-2'></i> อัปเดตข้อมูลส่วนตัวเรียบร้อยแล้ว <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            } catch(PDOException $e) {
                $message = "<div class='alert alert-danger'>ข้อผิดพลาด: " . $e->getMessage() . "</div>";
            }
        }
    }
    
    // ---------------------------------------------------------
    // ฟังก์ชัน: 3.3.6 เปลี่ยนรหัสผ่าน
    // ---------------------------------------------------------
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        try {
            // ดึงรหัสผ่านเดิมจากฐานข้อมูลมาตรวจสอบ
            $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = :id");
            $stmt->execute([':id' => $user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($current_password, $user['password'])) {
                if ($new_password === $confirm_password) {
                    // เข้ารหัสผ่านใหม่
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_stmt = $conn->prepare("UPDATE users SET password = :pass WHERE user_id = :id");
                    $update_stmt->execute([':pass' => $hashed_password, ':id' => $user_id]);
                    
                    $message = "<div class='alert alert-success alert-dismissible fade show'><i class='bi bi-shield-check me-2'></i> เปลี่ยนรหัสผ่านเรียบร้อยแล้ว <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
                } else {
                    $message = "<div class='alert alert-danger alert-dismissible fade show'><i class='bi bi-exclamation-triangle-fill me-2'></i> รหัสผ่านใหม่และการยืนยันรหัสผ่านไม่ตรงกัน <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
                }
            } else {
                $message = "<div class='alert alert-danger alert-dismissible fade show'><i class='bi bi-x-circle-fill me-2'></i> รหัสผ่านปัจจุบันไม่ถูกต้อง <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            }
        } catch(PDOException $e) {
            $message = "<div class='alert alert-danger'>ข้อผิดพลาด: " . $e->getMessage() . "</div>";
        }
    }
}

// 3. ดึงข้อมูลล่าสุดของ User มาแสดงในฟอร์ม
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = :id");
    $stmt->execute([':id' => $user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error fetching user data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โปรไฟล์ส่วนตัว | ระบบสั่งอาหาร</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8f9fa; }
        .text-brand { color: #ff6b6b !important; }
        .bg-brand { background-color: #ff6b6b !important; }
        .btn-brand { background-color: #ff6b6b; border-color: #ff6b6b; color: white; }
        .btn-brand:hover { background-color: #ff5252; color: white; }
        .profile-img-preview {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border: 4px solid #fff;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top py-3 shadow-sm">
    <div class="container">
        <a class="navbar-brand text-brand fw-bold" href="index.php"><i class="bi bi-shop me-2"></i>FoodDelivery</a>
        <div class="d-flex align-items-center">
            <a href="index.php" class="btn btn-outline-secondary btn-sm me-2"><i class="bi bi-arrow-left"></i> กลับไปเลือกร้าน</a>
        </div>
    </div>
</nav>

<div class="container mt-5 mb-5">
    <?= $message; ?>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm text-center pt-4 pb-3 rounded-4">
                <div class="card-body">
                    <?php 
                        // ตรวจสอบว่ามีรูปภาพหรือไม่ ถ้าไม่มีให้ใช้รูป default 
                        $img_src = !empty($user_data['profile_img']) ? '../assets/uploads/profiles/' . htmlspecialchars($user_data['profile_img']) : '../assets/uploads/profiles/default.png';
                    ?>
                    <img src="<?= $img_src ?>" alt="Profile" class="rounded-circle profile-img-preview mb-3" onerror="this.src='https://via.placeholder.com/150';">
                    
                    <h4 class="fw-bold"><?= htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?></h4>
                    <p class="text-muted mb-1"><i class="bi bi-person-badge"></i> @<?= htmlspecialchars($user_data['username']); ?></p>
                    <span class="badge bg-success rounded-pill px-3 py-2 mt-2"><i class="bi bi-check-circle"></i> สมาชิก (Customer)</span>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                    <h5 class="fw-bold"><i class="bi bi-pencil-square text-brand me-2"></i> แก้ไขข้อมูลส่วนตัว</h5>
                </div>
                <div class="card-body p-4">
                    <form action="profile.php" method="POST" enctype="multipart/form-data">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">ชื่อจริง</label>
                                <input type="text" class="form-control" name="first_name" value="<?= htmlspecialchars($user_data['first_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">นามสกุล</label>
                                <input type="text" class="form-control" name="last_name" value="<?= htmlspecialchars($user_data['last_name']); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">เบอร์โทรศัพท์</label>
                            <input type="text" class="form-control" name="phone" value="<?= htmlspecialchars($user_data['phone']); ?>" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">เปลี่ยนรูปประจำตัว <span class="text-muted" style="font-size:0.8rem;">(อัปโหลดเฉพาะเมื่อต้องการเปลี่ยน)</span></label>
                            <input type="file" class="form-control" name="profile_img" accept="image/*">
                        </div>

                        <button type="submit" name="update_profile" class="btn btn-brand px-4 rounded-pill">บันทึกข้อมูลส่วนตัว</button>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                    <h5 class="fw-bold"><i class="bi bi-key text-brand me-2"></i> เปลี่ยนรหัสผ่าน</h5>
                </div>
                <div class="card-body p-4">
                    <form action="profile.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label">รหัสผ่านปัจจุบัน</label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">รหัสผ่านใหม่</label>
                                <input type="password" class="form-control" name="new_password" required minlength="6">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">ยืนยันรหัสผ่านใหม่</label>
                                <input type="password" class="form-control" name="confirm_password" required minlength="6">
                            </div>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-outline-danger px-4 rounded-pill">เปลี่ยนรหัสผ่าน</button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>