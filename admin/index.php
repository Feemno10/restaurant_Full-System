<?php
// ไฟล์: admin/index.php
session_start();

// 1. ตรวจสอบสิทธิ์ (Security) - ต้องล็อกอินและเป็น 'admin' เท่านั้น
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';

try {
    // 2. ดึงสถิติต่างๆ มาแสดงบน Dashboard
    
    // 2.1 นับจำนวนผู้ใช้ที่ "รอการอนุมัติ"
    $stmt_pending = $conn->prepare("SELECT COUNT(*) FROM users WHERE role IN ('restaurant', 'rider') AND status = 'pending'");
    $stmt_pending->execute();
    $pending_count = $stmt_pending->fetchColumn();

    // 2.2 นับจำนวนร้านอาหารที่อนุมัติแล้ว
    $stmt_rest = $conn->prepare("SELECT COUNT(*) FROM users WHERE role = 'restaurant' AND status = 'approved'");
    $stmt_rest->execute();
    $rest_count = $stmt_rest->fetchColumn();

    // 2.3 นับจำนวนผู้ส่งอาหาร (Rider) ที่อนุมัติแล้ว
    $stmt_rider = $conn->prepare("SELECT COUNT(*) FROM users WHERE role = 'rider' AND status = 'approved'");
    $stmt_rider->execute();
    $rider_count = $stmt_rider->fetchColumn();

    // 2.4 นับจำนวนออเดอร์ทั้งหมดในระบบ
    $stmt_orders = $conn->prepare("SELECT COUNT(*) FROM orders");
    $stmt_orders->execute();
    $order_count = $stmt_orders->fetchColumn();

} catch(PDOException $e) {
    die("เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | FoodDelivery</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Prompt', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            pink: '#f1416c', /* สีชมพูแดงแบบในรูป */
                        },
                        sidebar: '#ffffff',
                        body: '#f4f6f9'
                    }
                }
            }
        }
    </script>
    <style>
        body { background-color: theme('colors.body'); }
        /* สไตล์ Scrollbar สำหรับ Sidebar */
        .sidebar-scroll::-webkit-scrollbar { width: 4px; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background-color: #e2e8f0; border-radius: 10px; }
        
        /* สไตล์สำหรับลิงก์ใน Sidebar */
        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: #64748b;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s ease-in-out;
            border-left: 3px solid transparent;
        }
        .nav-link:hover {
            color: theme('colors.brand.pink');
            background-color: #f8fafc;
        }
        .nav-link.active {
            color: theme('colors.brand.pink');
            border-left-color: theme('colors.brand.pink');
            background-color: #fff1f2;
        }
        .nav-icon { margin-right: 0.75rem; font-size: 1.1rem; }
    </style>
</head>
<body class="text-slate-800 antialiased flex h-screen overflow-hidden">

    <aside class="w-64 bg-sidebar shadow-xl z-20 flex-shrink-0 flex flex-col transition-transform duration-300 ease-in-out transform -translate-x-full md:translate-x-0 absolute md:relative h-full" id="sidebar">
        <div class="h-16 flex items-center px-6 bg-brand-pink text-white">
            <i class="bi bi-shop text-xl me-2"></i>
            <span class="font-bold text-lg tracking-wide">FoodAdmin.</span>
        </div>
        
        <div class="flex-1 overflow-y-auto sidebar-scroll py-4">
            <p class="px-6 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Main Menu</p>
            
            <a href="index.php" class="nav-link active">
                <i class="bi bi-grid-1x2-fill nav-icon"></i> Dashboard
            </a>
            
            <a href="approve_users.php" class="nav-link">
                <i class="bi bi-person-check nav-icon"></i> อนุมัติการใช้งาน
                <?php if($pending_count > 0): ?>
                    <span class="ml-auto bg-rose-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full"><?= $pending_count; ?></span>
                <?php endif; ?>
            </a>
            
            <p class="px-6 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2 mt-6">Management</p>
            
            <a href="categories.php" class="nav-link">
                <i class="bi bi-tags nav-icon"></i> หมวดหมู่ร้านอาหาร
            </a>
            
            <a href="manage_users.php" class="nav-link">
                <i class="bi bi-people nav-icon"></i> จัดการผู้ใช้งาน
            </a>
            
            <p class="px-6 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2 mt-6">Analytics</p>
            
            <a href="reports.php" class="nav-link">
                <i class="bi bi-bar-chart-line nav-icon"></i> รายงานภาพรวม
            </a>
            
            <p class="px-6 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2 mt-6">Settings</p>
            
            <a href="profile.php" class="nav-link">
                <i class="bi bi-person-badge nav-icon"></i> โปรไฟล์ส่วนตัว
            </a>
            <a href="../logout.php" class="nav-link text-rose-500 hover:text-rose-600 hover:bg-rose-50 mt-2">
                <i class="bi bi-box-arrow-right nav-icon"></i> ออกจากระบบ
            </a>
        </div>
    </aside>
    <div class="flex-1 flex flex-col h-full overflow-hidden">
        
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-4 sm:px-6 lg:px-8 z-10 shrink-0 relative">
            
            <div class="flex items-center">
                <button id="sidebarToggle" class="md:hidden text-slate-500 hover:text-slate-700 focus:outline-none mr-4">
                    <i class="bi bi-list text-2xl"></i>
                </button>
                
                <div class="hidden md:flex items-center bg-slate-100 rounded-full px-4 py-1.5 w-64 border border-transparent focus-within:border-brand-pink focus-within:bg-white transition-colors">
                    <i class="bi bi-search text-slate-400 text-sm"></i>
                    <input type="text" placeholder="Search..." class="bg-transparent border-none focus:ring-0 text-sm ml-2 w-full text-slate-600 outline-none">
                </div>
            </div>

            <div class="flex items-center gap-2 sm:gap-4">
                
                <button class="relative text-slate-400 hover:text-brand-pink transition-colors px-2">
                    <i class="bi bi-bell text-xl"></i>
                    <?php if($pending_count > 0): ?>
                        <span class="absolute top-1 right-2 block h-2 w-2 rounded-full bg-rose-500 ring-2 ring-white"></span>
                    <?php endif; ?>
                </button>
                
                <div class="h-6 w-px bg-slate-200 mx-1"></div>

                <div class="relative">
                    <button id="profileBtn" class="flex items-center gap-2 cursor-pointer focus:outline-none hover:bg-slate-50 p-1 pr-2 rounded-full transition-colors">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['full_name']); ?>&background=f1416c&color=fff&rounded=true" 
                             alt="Profile" 
                             class="h-9 w-9 object-cover rounded-full shadow-sm">
                        
                        <span class="text-[15px] text-slate-700 hidden sm:block"><?= htmlspecialchars($_SESSION['full_name']); ?></span>
                        <i class="bi bi-chevron-down text-xs text-slate-400 hidden sm:block"></i>
                    </button>

                    <div id="profileDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg py-2 border border-slate-100 transform opacity-0 scale-95 transition-all duration-200 origin-top-right z-[100]">
                        <a href="profile.php" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 hover:text-brand-pink transition-colors">
                            <i class="bi bi-person-gear mr-2"></i> จัดการโปรไฟล์
                        </a>
                        <div class="border-t border-slate-100 my-1"></div>
                        <a href="../logout.php" class="block px-4 py-2 text-sm text-rose-600 hover:bg-rose-50 transition-colors">
                            <i class="bi bi-box-arrow-right mr-2"></i> ออกจากระบบ
                        </a>
                    </div>
                </div>

            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-body p-4 sm:p-6 lg:p-8 relative z-0">
            
            <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">Dashboard</h1>
                    <p class="text-sm text-slate-500">ภาพรวมระบบประจำเดือน <?= date('F Y'); ?></p>
                </div>
                <a href="reports.php" class="bg-brand-pink text-white px-5 py-2.5 rounded-xl text-sm font-medium hover:bg-rose-600 transition-colors shadow-sm flex items-center gap-2">
                    <i class="bi bi-download"></i> ดาวน์โหลดรายงาน
                </a>
            </div>

            <?php if($pending_count > 0): ?>
                <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl p-4 mb-6 flex items-start sm:items-center justify-between gap-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <i class="bi bi-exclamation-circle-fill text-amber-500 text-xl"></i>
                        <p class="text-sm font-medium">มีคำขอลงทะเบียนร้านอาหาร/คนขับ <strong><?= $pending_count; ?></strong> รายการ รอการอนุมัติจากคุณ</p>
                    </div>
                    <a href="approve_users.php" class="bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold px-3 py-1.5 rounded-lg shrink-0 transition-colors shadow-sm">
                        ตรวจสอบทันที
                    </a>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                
                <div class="bg-gradient-to-r from-rose-400 to-brand-pink rounded-xl p-6 text-white shadow-lg relative overflow-hidden">
                    <h3 class="text-3xl font-bold mb-1"><?= number_format($order_count); ?></h3>
                    <p class="text-rose-100 text-sm font-medium">ออเดอร์ทั้งหมด</p>
                    <i class="bi bi-bar-chart-fill absolute -bottom-4 -right-4 text-6xl text-rose-300 opacity-50 rotate-12"></i>
                </div>

                <div class="bg-gradient-to-r from-indigo-500 to-purple-500 rounded-xl p-6 text-white shadow-lg relative overflow-hidden">
                    <h3 class="text-3xl font-bold mb-1"><?= number_format($rest_count); ?></h3>
                    <p class="text-indigo-100 text-sm font-medium">ร้านอาหาร (อนุมัติ)</p>
                    <i class="bi bi-shop absolute -bottom-4 -right-2 text-6xl text-indigo-300 opacity-50"></i>
                </div>

                <div class="bg-gradient-to-r from-sky-400 to-blue-500 rounded-xl p-6 text-white shadow-lg relative overflow-hidden">
                    <h3 class="text-3xl font-bold mb-1"><?= number_format($rider_count); ?></h3>
                    <p class="text-sky-100 text-sm font-medium">ผู้ส่งอาหาร (Rider)</p>
                    <i class="bi bi-motorcycle absolute -bottom-2 -right-2 text-6xl text-sky-200 opacity-50"></i>
                </div>

                <div class="bg-gradient-to-r from-amber-400 to-orange-500 rounded-xl p-6 text-white shadow-lg relative overflow-hidden">
                    <h3 class="text-3xl font-bold mb-1"><?= number_format($pending_count); ?></h3>
                    <p class="text-amber-100 text-sm font-medium">รอการอนุมัติ</p>
                    <i class="bi bi-person-lines-fill absolute -bottom-4 -right-4 text-6xl text-amber-200 opacity-50"></i>
                </div>

            </div>

            <h2 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2 mt-10">
                <i class="bi bi-grid-1x2-fill text-brand-pink"></i> เครื่องมือจัดการระบบ
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 pb-10">
                
                <a href="approve_users.php" class="group bg-white rounded-3xl p-8 border border-slate-100 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 text-center relative overflow-hidden">
                    <div class="mx-auto w-20 h-20 bg-amber-50 text-amber-500 rounded-full flex items-center justify-center mb-5 group-hover:bg-amber-500 group-hover:text-white transition-colors duration-300">
                        <i class="bi bi-person-check-fill text-4xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 mb-2">จัดการการอนุมัติบัญชี</h3>
                    <p class="text-slate-500 text-sm">ตรวจสอบและเปิดใช้งานบัญชีร้านอาหาร หรือไรเดอร์ใหม่</p>
                </a>

                <a href="categories.php" class="group bg-white rounded-3xl p-8 border border-slate-100 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 text-center relative overflow-hidden">
                    <div class="mx-auto w-20 h-20 bg-emerald-50 text-emerald-500 rounded-full flex items-center justify-center mb-5 group-hover:bg-emerald-500 group-hover:text-white transition-colors duration-300">
                        <i class="bi bi-tags-fill text-4xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 mb-2">หมวดหมู่ร้านอาหาร</h3>
                    <p class="text-slate-500 text-sm">สร้าง หรือแก้ไขหมวดหมู่อาหาร (เช่น ชาบู, ตามสั่ง)</p>
                </a>

                <a href="manage_users.php" class="group bg-white rounded-3xl p-8 border border-slate-100 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 text-center relative overflow-hidden">
                    <div class="mx-auto w-20 h-20 bg-blue-50 text-blue-500 rounded-full flex items-center justify-center mb-5 group-hover:bg-blue-500 group-hover:text-white transition-colors duration-300">
                        <i class="bi bi-people-fill text-4xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 mb-2">ข้อมูลผู้ใช้งานทั้งหมด</h3>
                    <p class="text-slate-500 text-sm">ดูข้อมูล แก้ไข หรือระงับบัญชีผู้ใช้งานในระบบ</p>
                </a>

            </div>

        </main>
    </div>

    <div id="sidebarOverlay" class="fixed inset-0 bg-slate-900 bg-opacity-50 z-30 hidden md:hidden"></div>

    <script>
        // ----------------------------------------------------
        // 1. Script สำหรับ Dropdown โปรไฟล์
        // ----------------------------------------------------
        const profileBtn = document.getElementById('profileBtn');
        const profileDropdown = document.getElementById('profileDropdown');
        let isDropdownOpen = false;

        profileBtn.addEventListener('click', (e) => {
            e.stopPropagation(); // ป้องกันไม่ให้คลิกทะลุไป event อื่น
            isDropdownOpen = !isDropdownOpen;
            
            if(isDropdownOpen) {
                profileDropdown.classList.remove('hidden');
                // ใช้ setTimeout เล็กน้อยเพื่อให้ CSS Transition ทำงานทัน
                setTimeout(() => {
                    profileDropdown.classList.remove('opacity-0', 'scale-95');
                    profileDropdown.classList.add('opacity-100', 'scale-100');
                }, 10);
            } else {
                profileDropdown.classList.remove('opacity-100', 'scale-100');
                profileDropdown.classList.add('opacity-0', 'scale-95');
                setTimeout(() => {
                    profileDropdown.classList.add('hidden');
                }, 200);
            }
        });

        // ปิด Dropdown อัตโนมัติเมื่อคลิกพื้นที่อื่นบนหน้าจอ
        document.addEventListener('click', (e) => {
            if(isDropdownOpen && !profileBtn.contains(e.target) && !profileDropdown.contains(e.target)) {
                isDropdownOpen = false;
                profileDropdown.classList.remove('opacity-100', 'scale-100');
                profileDropdown.classList.add('opacity-0', 'scale-95');
                setTimeout(() => {
                    profileDropdown.classList.add('hidden');
                }, 200);
            }
        });

        // ----------------------------------------------------
        // 2. Script สำหรับเปิด/ปิด Sidebar บนมือถือ
        // ----------------------------------------------------
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        function toggleSidebar() {
            sidebar.classList.toggle('-translate-x-full');
            sidebarOverlay.classList.toggle('hidden');
        }

        sidebarToggle.addEventListener('click', toggleSidebar);
        sidebarOverlay.addEventListener('click', toggleSidebar);
    </script>
</body>
</html>