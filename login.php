<?php
// ไฟล์: login.php
session_start();
require_once 'config/database.php';

// ป้องกันการล็อกอินซ้ำ
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') header("Location: admin/index.php");
    elseif ($_SESSION['role'] == 'restaurant') header("Location: restaurant/index.php");
    elseif ($_SESSION['role'] == 'rider') header("Location: rider/index.php");
    else header("Location: customer/index.php");
    exit();
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (password_verify($password, $user['password'])) {
                if ($user['role'] !== 'admin' && $user['status'] === 'pending') {
                    $message = "<div class='p-4 mb-4 text-sm text-amber-800 rounded-2xl bg-amber-50/80 border border-amber-100 animate__animated animate__shakeX flex items-center gap-3 backdrop-blur-md'>
                                    <i class='bi bi-hourglass-split text-lg'></i>
                                    <div><span class='font-bold'>รอการอนุมัติ!</span> บัญชีอยู่ระหว่างการตรวจสอบครับ</div>
                                </div>";
                } elseif ($user['role'] !== 'admin' && $user['status'] === 'rejected') {
                    $message = "<div class='p-4 mb-4 text-sm text-rose-800 rounded-2xl bg-rose-50/80 border border-rose-100 animate__animated animate__shakeX flex items-center gap-3 backdrop-blur-md'>
                                    <i class='bi bi-x-octagon-fill text-lg'></i>
                                    <div><span class='font-bold'>ถูกระงับ!</span> โปรดติดต่อผู้ดูแลระบบครับ</div>
                                </div>";
                } else {
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
                    $_SESSION['profile_img'] = $user['profile_img'];

                    header("Location: " . ($user['role'] == 'admin' ? 'admin/index.php' : ($user['role'] == 'restaurant' ? 'restaurant/index.php' : ($user['role'] == 'rider' ? 'rider/index.php' : 'customer/index.php'))));
                    exit();
                }
            } else {
                $message = "<div class='p-4 mb-4 text-sm text-rose-800 rounded-2xl bg-rose-50/80 border border-rose-100 animate__animated animate__headShake flex items-center gap-3 backdrop-blur-md'>
                                <i class='bi bi-shield-lock-fill text-lg'></i>
                                <div><span class='font-bold'>รหัสผ่านผิด!</span> โปรดลองอีกครั้งครับ</div>
                            </div>";
            }
        } else {
            $message = "<div class='p-4 mb-4 text-sm text-rose-800 rounded-2xl bg-rose-50/80 border border-rose-100 animate__animated animate__headShake flex items-center gap-3 backdrop-blur-md'>
                            <i class='bi bi-person-x-fill text-lg'></i>
                            <div><span class='font-bold'>ไม่พบผู้ใช้!</span> ชื่อผู้ใช้งานนี้ยังไม่ได้ลงทะเบียน</div>
                        </div>";
        }
    } catch(PDOException $e) { $message = "<div class='p-4 bg-white/50 rounded-2xl'>Error: " . $e->getMessage() . "</div>"; }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Login | FoodExpress Delivery</title>
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
        /* พื้นหลังระดับ Premium E-commerce (Mesh Gradient Blobs) */
        .premium-bg {
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
        }
        .blob {
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(241, 65, 108, 0.15) 0%, rgba(241, 65, 108, 0) 70%);
            border-radius: 50%;
            filter: blur(80px);
            z-index: 0;
            animation: float 20s infinite alternate;
        }
        .blob-2 {
            background: radial-gradient(circle, rgba(255, 92, 0, 0.15) 0%, rgba(255, 92, 0, 0) 70%);
            width: 500px;
            height: 500px;
            right: -100px;
            top: -100px;
            animation-delay: -5s;
        }
        .blob-3 {
            background: radial-gradient(circle, rgba(99, 102, 241, 0.1) 0%, rgba(99, 102, 241, 0) 70%);
            bottom: -150px;
            left: 20%;
            animation-delay: -10s;
        }
        @keyframes float {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(100px, 50px) scale(1.1); }
        }
        .glass-card { 
            background: rgba(255, 255, 255, 0.85); 
            backdrop-filter: blur(25px); 
            border: 1px solid rgba(255, 255, 255, 0.4); 
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08);
        }
    </style>
</head>
<body class="premium-bg min-h-screen flex items-center justify-center p-4">

    <div class="blob top-0 left-0"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>

    <div class="max-w-5xl w-full grid grid-cols-1 lg:grid-cols-2 rounded-[3.5rem] overflow-hidden glass-card relative z-10 animate__animated animate__fadeIn">
        
        <div class="hidden lg:flex flex-col justify-between p-16 bg-slate-900 relative overflow-hidden">
            <div class="absolute inset-0 z-0">
                <img src="https://images.unsplash.com/photo-1555939594-58d7cb561ad1?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                     class="w-full h-full object-cover opacity-10 mix-blend-luminosity">
            </div>
            
            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-12">
                    <div class="bg-gradient-to-br from-brand-primary to-brand-secondary p-3 rounded-2xl shadow-lg">
                        <i class="bi bi-bag-heart-fill text-2xl text-white"></i>
                    </div>
                    <span class="text-2xl font-black text-white tracking-tighter uppercase italic">Food<span class="text-brand-primary">Express</span></span>
                </div>
                
                <h1 class="text-5xl font-black text-white leading-[1.2] mb-8">
                    Taste the <br> <span class="text-transparent bg-clip-text bg-gradient-to-r from-brand-primary to-brand-secondary">Premium Experience</span>
                </h1>
                
                <div class="space-y-6">
                    <div class="flex items-center gap-5 p-4 rounded-3xl bg-white/5 border border-white/10 backdrop-blur-sm animate__animated animate__fadeInLeft animate__delay-1s">
                        <div class="w-12 h-12 rounded-2xl bg-brand-primary/20 flex items-center justify-center text-brand-primary">
                            <i class="bi bi-star-fill text-xl"></i>
                        </div>
                        <div>
                            <p class="text-white font-bold">Best Quality</p>
                            <p class="text-slate-400 text-sm">คัดสรรร้านอาหารระดับ 5 ดาว</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-5 p-4 rounded-3xl bg-white/5 border border-white/10 backdrop-blur-sm animate__animated animate__fadeInLeft animate__delay-2s">
                        <div class="w-12 h-12 rounded-2xl bg-brand-secondary/20 flex items-center justify-center text-brand-secondary">
                            <i class="bi bi-truck text-xl"></i>
                        </div>
                        <div>
                            <p class="text-white font-bold">Priority Delivery</p>
                            <p class="text-slate-400 text-sm">จัดส่งด่วนพิเศษถึงหน้าบ้านคุณ</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="relative z-10 text-white/30 text-[10px] font-black uppercase tracking-[0.4em] flex items-center gap-2">
                <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                System Active • 2026 Phangnga Project
            </div>
        </div>

        <div class="p-10 md:p-20 flex flex-col justify-center bg-white/40">
            <div class="mb-12">
                <h2 class="text-4xl font-black text-slate-800 mb-2">เข้าสู่ระบบ 👋</h2>
                <p class="text-slate-400 font-medium">ยินดีต้อนรับกลับมาสู่ระบบพรีเมียมของเรา</p>
            </div>

            <?= $message; ?>

            <form action="login.php" method="POST" class="space-y-6">
                
                <div class="space-y-2">
                    <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">ชื่อผู้ใช้งาน</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none text-slate-400 group-focus-within:text-brand-primary transition-colors">
                            <i class="bi bi-person-circle text-lg"></i>
                        </div>
                        <input type="text" name="username" required
                            class="block w-full pl-14 pr-5 py-5 bg-white border-2 border-slate-100 rounded-[1.5rem] focus:outline-none focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/5 transition-all text-slate-700 font-bold placeholder:font-normal placeholder:text-slate-300"
                            placeholder="กรอกUsernameของคุณ">
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">รหัสผ่าน</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none text-slate-400 group-focus-within:text-brand-primary transition-colors">
                            <i class="bi bi-lock-fill text-lg"></i>
                        </div>
                        <input type="password" name="password" required
                            class="block w-full pl-14 pr-5 py-5 bg-white border-2 border-slate-100 rounded-[1.5rem] focus:outline-none focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/5 transition-all text-slate-700 font-bold placeholder:font-normal placeholder:text-slate-300"
                            placeholder="••••••••">
                    </div>
                </div>

                <div class="flex items-center justify-between px-2">
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox" class="w-5 h-5 rounded-lg border-2 border-slate-200 text-brand-primary focus:ring-brand-primary transition-all">
                        <span class="text-sm font-bold text-slate-500 group-hover:text-slate-800 transition-colors">จดจำฉันไว้</span>
                    </label>
                    <a href="#" class="text-sm font-black text-brand-primary hover:text-brand-secondary underline decoration-2 underline-offset-8">ลืมรหัสผ่าน?</a>
                </div>

                <button type="submit" 
                    class="w-full py-6 bg-slate-900 text-white font-black text-lg rounded-[1.5rem] shadow-2xl hover:bg-brand-primary hover:-translate-y-1 active:scale-[0.97] transition-all transform flex items-center justify-center gap-3 group">
                    <span>ดำเนินการเข้าสู่ระบบ</span>
                    <i class="bi bi-arrow-right-short text-3xl group-hover:translate-x-1 transition-transform"></i>
                </button>

                <div class="text-center pt-8 border-t border-slate-100/50">
                    <p class="text-slate-500 font-medium">
                        ยังไม่ได้เป็นสมาชิก? 
                        <a href="register.php" class="text-brand-primary font-black hover:underline underline-offset-8 decoration-2 italic ms-1">ลงทะเบียนฟรีที่นี่</a>
                    </p>
                </div>
            </form>
        </div>
    </div>

    <div class="absolute bottom-6 text-slate-400 text-[10px] font-black uppercase tracking-[0.5em] animate__animated animate__fadeInUp">
        Designed for Phangnga Technical College Project
    </div>

</body>
</html>