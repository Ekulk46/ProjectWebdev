<?php
// ไฟล์ config/database.php
// ตั้งค่าการเชื่อมต่อฐานข้อมูล

define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'music_room_booking');
// เชื่อมต่อกับฐานข้อมูล MySQL
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
// ตรวจสอบการเชื่อมต่อ
if (!$conn) {
    die("การเชื่อมต่อล้มเหลว: " . mysqli_connect_error());
}

// ตั้งค่า charset เป็น utf8 เพื่อรองรับภาษาไทย
mysqli_set_charset($conn, "utf8");
?>