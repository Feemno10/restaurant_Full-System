<?php
// ไฟล์: customer/checkout.php
session_start();
require_once '../config/database.php';

// 1. ตรวจสอบสิทธิ์ว่าล็อกอินหรือยัง และมีของในตะกร้าไหม
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}
if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}

// 2. รับค่าที่ส่งมาจากหน้า cart.php
$restaurant_id = $_POST['restaurant_id'] ?? $_SESSION['cart_restaurant_id'];
$total_price = $_POST['total_price'] ?? 0;
$discount_percent = $_POST['discount_percent'] ?? 0;
$net_price = $_POST['net_price'] ?? 0;

// 3. ดึงข้อมูลลูกค้าเพื่อมาแสดงเป็นค่าเริ่มต้นในช่องเบอร์โทรศัพท์
try {
    $stmt_user = $conn->prepare("SELECT first_name, last_name, phone FROM users WHERE user_id = :uid");
    $stmt_user->execute([':uid' => $_SESSION['user_id']]);
    $user = $stmt_user->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ชำระเงิน | FoodExpress</title>
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
        
        // ฟังก์ชันสลับการแสดงผล QR Code / ฟอร์มบัตรเครดิต
        function togglePaymentView(method) {
            document.getElementById('qr_code_section').classList.add('hidden');
            document.getElementById('credit_card_section').classList.add('hidden');
            
            if (method === 'promptpay') {
                document.getElementById('qr_code_section').classList.remove('hidden');
            } else if (method === 'credit_card') {
                document.getElementById('credit_card_section').classList.remove('hidden');
            }
        }
    </script>
    <style>
        body { background-color: #f8fafc; }
        .glass-card { background: rgba(255, 255, 255, 0.95); border: 1px solid rgba(255, 255, 255, 0.2); }
        /* สไตล์ปุ่มแบบ Pop Cafe (รูปทรงแคปซูลยา) */
        .btn-pill {
            background-color: white;
            color: #333;
            border-radius: 50px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            border: 1px solid #f1f5f9;
        }
        .btn-pill:hover {
            box-shadow: 0 15px 35px rgba(241, 65, 108, 0.15);
            border-color: #f1416c;
            color: #f1416c;
        }
    </style>
</head>
<body class="font-sans text-slate-800 pb-24">

    <nav class="bg-white shadow-sm sticky top-0 z-30">
        <div class="max-w-5xl mx-auto px-4 py-4 flex items-center gap-4">
            <a href="cart.php" class="text-slate-400 hover:text-brand-primary transition text-2xl"><i class="bi bi-arrow-left-circle-fill"></i></a>
            <h1 class="font-bold text-xl text-slate-700">ยืนยันการสั่งซื้อ</h1>
        </div>
    </nav>

    <div class="max-w-5xl mx-auto px-4 mt-8 animate__animated animate__fadeInUp">
        <form action="confirm_order.php" method="POST">
            
            <input type="hidden" name="restaurant_id" value="<?= $restaurant_id ?>">
            <input type="hidden" name="total_price" value="<?= $total_price ?>">
            <input type="hidden" name="discount_percent" value="<?= $discount_percent ?>">
            <input type="hidden" name="net_price" value="<?= $net_price ?>">

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <div class="lg:col-span-2 space-y-8">
                    
                    <div class="glass-card p-6 md:p-8 rounded-[2rem] shadow-sm">
                        <h2 class="text-xl font-bold mb-6 flex items-center gap-3">
                            <div class="bg-brand-primary/10 p-2 rounded-xl text-brand-primary"><i class="bi bi-geo-alt-fill"></i></div>
                            รายละเอียดการจัดส่ง
                        </h2>
                        
                        <div class="space-y-5">
                            <div>
                                <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">เบอร์ติดต่อผู้รับ</label>
                                <input type="text" name="delivery_phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" 
                                       class="w-full px-5 py-4 bg-slate-50 border-2 border-transparent rounded-2xl focus:outline-none focus:bg-white focus:border-brand-primary transition-all font-bold text-slate-700" required>
                            </div>
                            <div>
                                <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">ที่อยู่จัดส่ง (ระบุให้ชัดเจน)</label>
                                <textarea name="delivery_address" rows="3" 
                                          class="w-full px-5 py-4 bg-slate-50 border-2 border-transparent rounded-2xl focus:outline-none focus:bg-white focus:border-brand-primary transition-all font-bold text-slate-700" 
                                          placeholder="เช่น บ้านเลขที่ 123 หมู่ 4 ซอย... จุดสังเกต..." required></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="glass-card p-6 md:p-8 rounded-[2rem] shadow-sm">
                        <h2 class="text-xl font-bold mb-6 flex items-center gap-3">
                            <div class="bg-emerald-500/10 p-2 rounded-xl text-emerald-500"><i class="bi bi-wallet2"></i></div>
                            เลือกวิธีชำระเงิน
                        </h2>
                        
                        <div class="space-y-4">
                            <label class="relative block cursor-pointer group">
                                <input type="radio" name="payment_method" value="cash" class="peer sr-only" onchange="togglePaymentView('cash')" checked>
                                <div class="p-5 rounded-2xl border-2 border-slate-100 peer-checked:border-brand-primary peer-checked:bg-brand-primary/5 transition-all flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl"><i class="bi bi-cash-stack"></i></div>
                                    <div class="flex-1">
                                        <h3 class="font-bold text-slate-800 text-lg">เงินสดปลายทาง (Cash)</h3>
                                        <p class="text-sm text-slate-500">ชำระเงินกับไรเดอร์เมื่อได้รับอาหาร</p>
                                    </div>
                                    <i class="bi bi-check-circle-fill text-2xl text-brand-primary opacity-0 peer-checked:opacity-100 transition-opacity"></i>
                                </div>
                            </label>

                            <label class="relative block cursor-pointer group">
                                <input type="radio" name="payment_method" value="promptpay" class="peer sr-only" onchange="togglePaymentView('promptpay')">
                                <div class="p-5 rounded-2xl border-2 border-slate-100 peer-checked:border-brand-primary peer-checked:bg-brand-primary/5 transition-all flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xl"><i class="bi bi-qr-code-scan"></i></div>
                                    <div class="flex-1">
                                        <h3 class="font-bold text-slate-800 text-lg">สแกนจ่าย (PromptPay)</h3>
                                        <p class="text-sm text-slate-500">รองรับแอปธนาคารทุกแอป</p>
                                    </div>
                                    <i class="bi bi-check-circle-fill text-2xl text-brand-primary opacity-0 peer-checked:opacity-100 transition-opacity"></i>
                                </div>
                            </label>
                            
                            <div id="qr_code_section" class="hidden mt-4 p-6 bg-slate-50 rounded-[1.5rem] border border-slate-200 text-center animate__animated animate__fadeIn">
                                <p class="font-bold text-slate-700 mb-2">สแกนเพื่อชำระเงิน</p>
                                <p class="text-brand-primary font-black text-2xl mb-4">฿<?= number_format($net_price, 2) ?></p>
                                <img src="https://upload.wikimedia.org/wikipedia/commons/d/d0/QR_code_for_mobile_English_Wikipedia.svg" alt="QR" class="w-48 h-48 mx-auto rounded-xl shadow-md bg-white p-2 mb-4">
                                <p class="text-xs text-slate-400"><i class="bi bi-info-circle"></i> ระบบจำลองการชำระเงิน สำหรับโปรเจกต์เท่านั้น</p>
                            </div>

                            <label class="relative block cursor-pointer group">
                                <input type="radio" name="payment_method" value="credit_card" class="peer sr-only" onchange="togglePaymentView('credit_card')">
                                <div class="p-5 rounded-2xl border-2 border-slate-100 peer-checked:border-brand-primary peer-checked:bg-brand-primary/5 transition-all flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center text-xl"><i class="bi bi-credit-card-2-front"></i></div>
                                    <div class="flex-1">
                                        <h3 class="font-bold text-slate-800 text-lg">บัตรเครดิต / เดบิต</h3>
                                        <p class="text-sm text-slate-500">Visa, Mastercard, JCB</p>
                                    </div>
                                    <i class="bi bi-check-circle-fill text-2xl text-brand-primary opacity-0 peer-checked:opacity-100 transition-opacity"></i>
                                </div>
                            </label>

                            <div id="credit_card_section" class="hidden mt-4 p-6 bg-slate-50 rounded-[1.5rem] border border-slate-200 space-y-4 animate__animated animate__fadeIn">
                                <div>
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">หมายเลขบัตร</label>
                                    <input type="text" placeholder="0000 0000 0000 0000" class="w-full px-4 py-3 bg-white rounded-xl border border-slate-200 focus:outline-none focus:border-brand-primary font-mono text-slate-700">
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">วันหมดอายุ</label>
                                        <input type="text" placeholder="MM/YY" class="w-full px-4 py-3 bg-white rounded-xl border border-slate-200 focus:outline-none focus:border-brand-primary font-mono text-slate-700">
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">CVV</label>
                                        <input type="text" placeholder="123" class="w-full px-4 py-3 bg-white rounded-xl border border-slate-200 focus:outline-none focus:border-brand-primary font-mono text-slate-700">
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="lg:col-span-1">
                    <div class="glass-card p-6 md:p-8 rounded-[2rem] shadow-xl sticky top-24">
                        <h2 class="text-xl font-bold mb-6 text-slate-800">ยอดชำระเงิน</h2>
                        
                        <div class="space-y-3 text-slate-500 mb-6 font-medium">
                            <div class="flex justify-between">
                                <span>ยอดรวมอาหาร</span>
                                <span>฿<?= number_format($total_price, 2) ?></span>
                            </div>
                            <?php if($discount_percent > 0): ?>
                            <div class="flex justify-between text-brand-primary">
                                <span>ส่วนลด (<?= $discount_percent ?>%)</span>
                                <span>- ฿<?= number_format(($total_price * $discount_percent) / 100, 2) ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="flex justify-between text-emerald-500">
                                <span>ค่าจัดส่ง</span>
                                <span>ฟรี</span>
                            </div>
                        </div>

                        <div class="flex justify-between items-center mb-8 pt-6 border-t-2 border-dashed border-slate-200">
                            <span class="text-lg font-black text-slate-800">ราคาสุทธิ</span>
                            <span class="text-4xl font-black text-brand-primary tracking-tighter">฿<?= number_format($net_price, 2) ?></span>
                        </div>

                        <button type="submit" name="confirm_payment" class="btn-pill w-full py-5 text-lg font-black flex items-center justify-center gap-2 transition-all">
                            สั่งซื้อเลย <i class="bi bi-arrow-right-circle-fill text-2xl"></i>
                        </button>
                        
                        <div class="mt-6 flex items-center justify-center gap-2 text-xs text-slate-400 font-bold uppercase tracking-widest">
                            <i class="bi bi-shield-lock-fill text-brand-primary"></i> ปลอดภัย 100%
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

</body>
</html>