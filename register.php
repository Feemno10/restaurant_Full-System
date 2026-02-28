<?php
// ไฟล์: register.php
session_start();
require_once 'config/database.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $plain_password = $_POST['password'];
    $role = $_POST['role'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $phone = trim($_POST['phone']);

    $hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);
    $status = ($role === 'customer') ? 'approved' : 'pending';

    try {
        $stmt = $conn->prepare("INSERT INTO users (username, password, role, first_name, last_name, phone, status) 
                                VALUES (:username, :password, :role, :first_name, :last_name, :phone, :status)");
        
        $stmt->execute([
            ':username' => $username,
            ':password' => $hashed_password,
            ':role' => $role,
            ':first_name' => $first_name,
            ':last_name' => $last_name,
            ':phone' => $phone,
            ':status' => $status
        ]);

        if ($role === 'customer') {
            $message = "<div class='p-4 mb-6 text-sm text-emerald-800 rounded-2xl bg-emerald-50/80 border border-emerald-100 animate__animated animate__fadeInDown backdrop-blur-md'>
                            <span class='font-bold'><i class='bi bi-check-circle-fill mr-2'></i>สำเร็จ!</span> บัญชีของคุณพร้อมใช้งานแล้วครับ
                        </div>";
        } else {
            $message = "<div class='p-4 mb-6 text-sm text-amber-800 rounded-2xl bg-amber-50/80 border border-amber-100 animate__animated animate__fadeInDown backdrop-blur-md'>
                            <span class='font-bold'><i class='bi bi-info-circle-fill mr-2'></i>ลงทะเบียนเรียบร้อย!</span> กรุณารอการอนุมัติจากแอดมินนะครับ
                        </div>";
        }
    } catch(PDOException $e) {
        if ($e->getCode() == 23000) {
            $message = "<div class='p-4 mb-6 text-sm text-rose-800 rounded-2xl bg-rose-50/80 border border-rose-100 animate__animated animate__shakeX backdrop-blur-md'>
                            <span class='font-bold'><i class='bi bi-exclamation-octagon-fill mr-2'></i>ชื่อซ้ำ!</span> ชื่อผู้ใช้งานนี้ถูกใช้ไปแล้วครับ
                        </div>";
        } else {
            $message = "<div class='p-4 bg-white/50 rounded-2xl'>Error: " . $e->getMessage() . "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก | FoodExpress Premium</title>
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
        /* ล็อคโครงสร้างให้อยู่กึ่งกลางเป๊ะ */
        .premium-layout {
            background-color: #f8fafc;
            position: relative;
            overflow-x: hidden;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        /* Blobs ตกแต่งพื้นหลัง */
        .blob {
            position: absolute;
            width: 800px;
            height: 800px;
            background: radial-gradient(circle, rgba(241, 65, 108, 0.1) 0%, rgba(241, 65, 108, 0) 70%);
            border-radius: 50%;
            filter: blur(80px);
            z-index: 0;
            animation: float 25s infinite alternate;
        }
        .blob-2 {
            background: radial-gradient(circle, rgba(255, 92, 0, 0.1) 0%, rgba(255, 92, 0, 0) 70%);
            width: 700px;
            height: 700px;
            right: -200px;
            bottom: -100px;
            animation-delay: -7s;
        }
        @keyframes float { 0% { transform: translate(0, 0) scale(1); } 100% { transform: translate(150px, 100px) scale(1.1); } }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 1100px; /* คุมความกว้างไม่ให้ยืดเกินไป */
            z-index: 10;
        }
    </style>
</head>
<body class="premium-layout">

    <div class="blob top-0 left-[-200px]"></div>
    <div class="blob blob-2"></div>

    <div class="grid grid-cols-1 lg:grid-cols-12 rounded-[3rem] overflow-hidden glass-card animate__animated animate__zoomIn">
        
        <div class="hidden lg:flex lg:col-span-5 flex-col justify-between p-12 bg-slate-900 relative overflow-hidden">
            <div class="absolute inset-0 z-0">
                <img src="https://images.unsplash.com/photo-1504674900247-0877df9cc836?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                     class="w-full h-full object-cover opacity-20 mix-blend-overlay">
            </div>
            
            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-10">
                    <div class="bg-gradient-to-br from-brand-primary to-brand-secondary p-2.5 rounded-2xl shadow-lg">
                        <i class="bi bi-bag-heart-fill text-2xl text-white"></i>
                    </div>
                    <span class="text-2xl font-black text-white tracking-tighter uppercase italic">Food<span class="text-brand-primary">Express</span></span>
                </div>
                
                <h1 class="text-4xl font-black text-white leading-tight mb-8">
                    ร่วมเดินทางสู่ <br> <span class="text-transparent bg-clip-text bg-gradient-to-r from-brand-primary to-brand-secondary underline decoration-white/10">ความอร่อยรูปแบบใหม่</span>
                </h1>
                
                <div class="space-y-4">
                    <div class="flex items-center gap-4 p-4 rounded-3xl bg-white/5 border border-white/10 backdrop-blur-sm">
                        <div class="w-10 h-10 rounded-2xl bg-brand-primary/20 flex items-center justify-center text-brand-primary">
                            <i class="bi bi-person-check-fill"></i>
                        </div>
                        <p class="text-white text-sm font-bold">คุณภาพระดับพรีเมียม</p>
                    </div>
                    <div class="flex items-center gap-4 p-4 rounded-3xl bg-white/5 border border-white/10 backdrop-blur-sm">
                        <div class="w-10 h-10 rounded-2xl bg-brand-secondary/20 flex items-center justify-center text-brand-secondary">
                            <i class="bi bi-shield-lock-fill"></i>
                        </div>
                        <p class="text-white text-sm font-bold">ปลอดภัย 100%</p>
                    </div>
                </div>
            </div>

            <div class="relative z-10 text-white/30 text-[10px] font-black uppercase tracking-[0.4em] flex items-center gap-2">
                <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                © 2026 IT Project • Phangnga Technical College
            </div>
        </div>

        <div class="lg:col-span-7 p-8 md:p-12 lg:p-16 flex flex-col justify-center bg-white/40">
            <div class="mb-10 text-center lg:text-left">
                <h2 class="text-4xl font-black text-slate-800 mb-2 tracking-tight">สร้างบัญชีใหม่ 👋</h2>
                <p class="text-slate-400 font-medium italic">สัมผัสประสบการณ์การสั่งอาหารที่เหนือระดับ</p>
            </div>

            <?= $message; ?>

            <form action="register.php" method="POST" class="space-y-5">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">ชื่อจริง</label>
                        <input type="text" name="first_name" required class="w-full px-5 py-4 bg-white border-2 border-slate-100 rounded-2xl focus:outline-none focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/5 transition-all text-slate-700 font-bold placeholder:font-normal" placeholder="สมชาย">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">นามสกุล</label>
                        <input type="text" name="last_name" required class="w-full px-5 py-4 bg-white border-2 border-slate-100 rounded-2xl focus:outline-none focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/5 transition-all text-slate-700 font-bold placeholder:font-normal" placeholder="รักเรียน">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Username</label>
                        <input type="text" name="username" required class="w-full px-5 py-4 bg-white border-2 border-slate-100 rounded-2xl focus:outline-none focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/5 transition-all text-slate-700 font-bold placeholder:font-normal" placeholder="ใช้ล็อกอิน">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">เบอร์โทรศัพท์</label>
                        <input type="tel" name="phone" required class="w-full px-5 py-4 bg-white border-2 border-slate-100 rounded-2xl focus:outline-none focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/5 transition-all text-slate-700 font-bold placeholder:font-normal" placeholder="08x-xxx-xxxx">
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">รหัสผ่าน</label>
                    <input type="password" name="password" required class="w-full px-5 py-4 bg-white border-2 border-slate-100 rounded-2xl focus:outline-none focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/5 transition-all text-slate-700 font-bold placeholder:font-normal" placeholder="••••••••">
                </div>

                <div class="space-y-1.5 relative">
                    <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">ประเภทสมาชิก</label>
                    <select name="role" required class="w-full px-5 py-4 bg-white border-2 border-slate-100 rounded-2xl focus:outline-none focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/5 transition-all text-slate-700 font-bold appearance-none cursor-pointer">
                        <option value="customer" selected>🙋‍♂️ ลูกค้าทั่วไป (เข้าใช้งานได้ทันที)</option>
                        <option value="restaurant">🏪 พาร์ทเนอร์ร้านอาหาร (รอตรวจสอบ)</option>
                        <option value="rider">🛵 พนักงานส่งอาหาร (รอตรวจสอบ)</option>
                    </select>
                    <div class="absolute right-5 bottom-4 pointer-events-none text-slate-400"><i class="bi bi-chevron-down"></i></div>
                </div>

                <div class="p-3 bg-brand-primary/5 rounded-2xl border border-brand-primary/10 flex items-center gap-3">
                    <i class="bi bi-info-circle-fill text-brand-primary"></i>
                    <p class="text-[10px] text-brand-primary font-bold uppercase tracking-wider leading-relaxed">
                        บัญชีร้านค้าและไรเดอร์จะเริ่มใช้งานได้หลังจากแอดมินอนุมัติแล้วเท่านั้น
                    </p>
                </div>

                <button type="submit" 
                    class="w-full py-6 bg-slate-900 text-white font-black text-lg rounded-[1.5rem] shadow-2xl hover:bg-brand-primary hover:-translate-y-1 active:scale-[0.97] transition-all transform flex items-center justify-center gap-3 group">
                    <span>ลงทะเบียนเข้าระบบ</span>
                    <i class="bi bi-arrow-right-short text-3xl group-hover:translate-x-1 transition-transform"></i>
                </button>

                <p class="text-center text-slate-500 font-medium pt-4">
                    มีบัญชีอยู่แล้วใช่ไหม? 
                    <a href="login.php" class="text-brand-primary font-black hover:underline underline-offset-8 decoration-2 italic ms-1">เข้าสู่ระบบที่นี่</a>
                </p>
            </form>
        </div>
    </div>

    <div class="absolute bottom-6 text-slate-400 text-[10px] font-black uppercase tracking-[0.5em] animate__animated animate__fadeInUp">
        © 2026 IT Project • Phangnga Technical College
    </div>

</body>
</html>