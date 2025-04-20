<?php
// ไฟล์ auth/logout.php
// สำหรับการออกจากระบบ

// เริ่ม session
session_start();

// ล้าง session ทั้งหมด
$_SESSION = array();
session_destroy();

// ไปยังหน้าล็อกอิน
header('Location: login.php');
exit();
?>