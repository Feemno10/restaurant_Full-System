<?php
// ไฟล์: config/database.php
$host = "localhost";
$dbname = "restaurant"; // ชื่อฐานข้อมูลของคุณ
$username = "root";           // username ฐานข้อมูล (XAMPP ปกติคือ root)
$password = "";               // password ฐานข้อมูล (XAMPP ปกติคือ ค่าว่าง)

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // ตั้งค่าให้ PDO แจ้งเตือนเมื่อมี Error
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit;
}
?>