<?php
// ไฟล์: customer/cart.php
session_start();

// 1. ตรวจสอบสิทธิ์ว่าล็อกอินหรือยัง
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';

// สร้างตัวแปร Session สำหรับตะกร้าถ้ายังไม่มี
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// ---------------------------------------------------------
// ส่วนที่ 1: จัดการคำสั่งต่างๆ (เพิ่ม / แก้ไขจำนวน / ลบ / ล้างตะกร้า)
// ---------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    $food_id = $_POST['food_id'] ?? 0;
    $restaurant_id = $_POST['restaurant_id'] ?? 0;
    
    // --- การเพิ่มสินค้าลงตะกร้า ---
    if ($action == 'add') {
        $qty = (int)($_POST['quantity'] ?? 1);
        
        // กฎ: สั่งได้ทีละร้าน ถ้าเปลี่ยนร้านให้ล้างตะกร้าเก่า
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
    
    // --- การอัปเดตจำนวนในหน้าตะกร้า ---
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

// --- ลบรายการเดียว ---
if (isset($_GET['action']) && $_GET['action'] == 'remove' && isset($_GET['id'])) {
    unset($_SESSION['cart'][$_GET['id']]);
    if (empty($_SESSION['cart'])) { unset($_SESSION['cart_restaurant_id']); }
    header("Location: cart.php");
    exit();
}

// --- ล้างทั้งตะกร้า ---
if (isset($_GET['action']) && $_GET['action'] == 'clear') {
    $_SESSION['cart'] = [];
    unset($_SESSION['cart_restaurant_id']);
    header("Location: cart.php");
    exit();
}

// ---------------------------------------------------------
// ส่วนที่ 2: ดึงข้อมูลและคำนวณราคาสรุป
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
            $discount_percent = $row['discount_percent'];
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
    <title>ตะกร้าสินค้า | FoodExpress</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Prompt', 'sans-serif'] },
                    colors: { brand: { primary: '#f1416c', secondary: '#ff5c00' } }
                }
            }
        }
    </script>
    <style>
        body { background-color: #f8fafc; font-family: 'Prompt', sans-serif; }
        .glass-card { background: rgba(255, 255, 255, 0.95); border: 1px solid rgba(255, 255, 255, 0.4); box-shadow: 0 15px 35px rgba(0,0,0,0.03); }
        .cart-img { width: 80px; height: 80px; object-fit: cover; border-radius: 1rem; }
        
        /* สไตล์ปุ่ม Pop Cafe (Pill shape) */
        .pop-btn {
            background-color: #ffffff;
            color: #f1416c;
            border: 2px solid #f1f5f9;
            box-shadow: 0 10px 25px rgba(241, 65, 108, 0.1);
        }
        .pop-btn:hover {
            border-color: #f1416c;
            background-color: #f1416c;
            color: #ffffff;
            box-shadow: 0 15px 35px rgba(241, 65, 108, 0.2);
        }
    </style>
</head>
<body class="pb-24">

<nav class="bg-white shadow-sm sticky top-0 z-30">
    <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="bg-brand-primary p-2 rounded-xl"><i class="bi bi-bag-heart-fill text-xl text-white"></i></div>
            <span class="text-xl font-black text-slate-800 uppercase italic">Food<span class="text-brand-primary">Express</span></span>
        </div>
        <a href="index.php" class="btn btn-outline-dark px-4 py-2 rounded-full border border-slate-200 text-sm font-bold text-slate-600 hover:bg-slate-50 transition">
            เลือกเมนูเพิ่ม
        </a>
    </div>
</nav>

<div class="max-w-6xl mx-auto px-4 mt-8 animate__animated animate__fadeInUp">
    <h2 class="text-3xl font-black text-slate-800 mb-6">ตะกร้าสินค้าของคุณ 🛒</h2>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <div class="lg:col-span-2">
            <div class="glass-card rounded-[2rem] p-6 md:p-8">
                <?php if(empty($cart_items)): ?>
                    <div class="text-center py-12">
                        <i class="bi bi-cart-x text-6xl text-slate-300 mb-4 block"></i>
                        <h4 class="text-xl font-bold text-slate-500 mb-4">ยังไม่มีรายการอาหารในตะกร้า</h4>
                        <a href="index.php" class="inline-block bg-slate-900 text-white px-8 py-3 rounded-full font-bold shadow-lg hover:bg-brand-primary transition">กลับไปเลือกร้านอาหาร</a>
                    </div>
                <?php else: ?>
                    <div class="flex justify-between items-center mb-6 pb-4 border-b border-slate-100">
                        <h5 class="font-bold text-lg text-slate-700 flex items-center gap-2">
                            <i class="bi bi-shop text-brand-primary"></i> <?= htmlspecialchars($restaurant_name) ?>
                        </h5>
                        <a href="cart.php?action=clear" class="text-rose-400 hover:text-rose-600 text-sm font-bold transition" onclick="return confirm('ล้างตะกร้าทั้งหมด?')">ล้างตะกร้า</a>
                    </div>

                    <div class="space-y-6">
                        <?php foreach($cart_items as $item): ?>
                        <div class="flex items-center gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-100">
                            <img src="../assets/uploads/foods/<?= $item['food_img'] ?>" class="cart-img shadow-sm" onerror="this.src='https://via.placeholder.com/80';">
                            
                            <div class="flex-1">
                                <h6 class="font-bold text-slate-800 text-lg mb-1"><?= htmlspecialchars($item['food_name']) ?></h6>
                                <span class="text-brand-primary font-bold">฿<?= number_format($item['price'], 2) ?></span>
                            </div>

                            <div class="flex flex-col items-end gap-3">
                                <form action="cart.php" method="POST" class="flex items-center bg-white rounded-lg border border-slate-200 overflow-hidden shadow-sm">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="food_id" value="<?= $item['food_id'] ?>">
                                    <input type="number" name="quantity" class="w-16 text-center py-2 font-bold focus:outline-none text-slate-700" 
                                           value="<?= $item['quantity'] ?>" min="1" max="99" onchange="this.form.submit()">
                                </form>
                                <span class="font-black text-slate-800">฿<?= number_format($item['subtotal'], 2) ?></span>
                            </div>

                            <a href="cart.php?action=remove&id=<?= $item['food_id'] ?>" class="w-10 h-10 flex items-center justify-center rounded-full bg-white text-rose-400 hover:bg-rose-50 hover:text-rose-600 transition shadow-sm border border-slate-100 ml-2">
                                <i class="bi bi-trash-fill"></i>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if(!empty($cart_items)): ?>
        <div class="lg:col-span-1">
            <div class="glass-card rounded-[2rem] p-6 md:p-8 sticky top-24">
                
                <h3 class="text-xl font-bold mb-6 text-slate-800">สรุปคำสั่งซื้อ</h3>
                
                <div class="space-y-4 text-slate-500 font-medium mb-6">
                    <div class="flex justify-between">
                        <span>ยอดรวมอาหาร</span>
                        <span>฿<?= number_format($total_price, 2) ?></span>
                    </div>
                    <?php if($discount_percent > 0): ?>
                    <div class="flex justify-between text-rose-500 font-bold bg-rose-50 p-2 rounded-lg">
                        <span>ส่วนลด (<?= $discount_percent ?>%)</span>
                        <span>- ฿<?= number_format($discount_amount, 2) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="flex justify-between text-emerald-500">
                        <span>ค่าจัดส่ง</span>
                        <span>ฟรี</span>
                    </div>
                </div>

                <div class="flex justify-between items-center mb-8 pt-6 border-t-2 border-dashed border-slate-200">
                    <span class="text-xl font-black text-slate-800">ราคาสุทธิ</span>
                    <span class="text-4xl font-black text-[#f1416c]">฿<?= number_format($net_price, 2) ?></span>
                </div>

                <form action="checkout.php" method="POST">
                    <input type="hidden" name="restaurant_id" value="<?= $_SESSION['cart_restaurant_id'] ?>">
                    <input type="hidden" name="total_price" value="<?= $total_price ?>">
                    <input type="hidden" name="discount_percent" value="<?= $discount_percent ?>">
                    <input type="hidden" name="net_price" value="<?= $net_price ?>">
                    
                    <button type="submit" class="pop-btn w-full py-4 text-lg font-black rounded-full flex items-center justify-center gap-2 transition-all transform hover:-translate-y-1">
                        ดำเนินการชำระเงิน <i class="bi bi-arrow-right-circle-fill text-2xl ml-1"></i>
                    </button>
                </form>
                
                <p class="text-center text-slate-400 font-medium text-sm mt-5">
                    สั่งจากร้าน "<?= htmlspecialchars($restaurant_name) ?>"
                </p>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

</body>
</html>