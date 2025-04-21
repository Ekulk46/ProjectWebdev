<?php
// ไฟล์ includes/header.php
// เริ่ม session และเรียกใช้ไฟล์ที่จำเป็น
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจองห้องซ้อมดนตรี</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;700&display=swap">
    <link rel="stylesheet" href="<?php echo getBaseUrl(); ?>/assets/css/style.css">
    <script src="<?php echo getBaseUrl(); ?>/assets/js/style.js" defer></script>
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <h1><a href="<?php echo getBaseUrl(); ?>/index.php">ระบบจองห้องซ้อมดนตรี</a></h1>
            </div>
            <nav>
                <ul>
                    <li><a href="<?php echo getBaseUrl(); ?>/index.php">หน้าหลัก</a></li>
                    <li><a href="<?php echo getBaseUrl(); ?>/booking/index.php">ห้องทั้งหมด</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li><a href="<?php echo getBaseUrl(); ?>/booking/create.php">จองห้องซ้อม</a></li>
                        <li><a href="<?php echo getBaseUrl(); ?>/booking/view.php">การจองของฉัน</a></li>
                        <li>
                            <a href="<?php echo getBaseUrl(); ?>/auth/logout.php" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการออกจากระบบ?')">ออกจากระบบ</a>
                        </li>

                    <?php else: ?>
                        <li><a href="<?php echo getBaseUrl(); ?>/auth/login.php">เข้าสู่ระบบ</a></li>
                        <li><a href="<?php echo getBaseUrl(); ?>/auth/register.php">สมัครสมาชิก</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <div class="container">
        <main>