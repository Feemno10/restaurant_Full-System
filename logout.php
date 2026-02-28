<?php
// ไฟล์: logout.php
session_start();

// 1. ล้างค่าตัวแปร Session ทั้งหมด
session_unset();

// 2. ทำลาย Session ทิ้งอย่างสมบูรณ์
session_destroy();

// 3. เด้งกลับไปที่หน้าแรก
header("Location: index.php");
exit();
?>