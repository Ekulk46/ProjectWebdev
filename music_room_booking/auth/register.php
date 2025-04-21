<?php
// ไฟล์ auth/register.php
// หน้าสำหรับสมัครสมาชิกใหม่

// นำเข้าไฟล์ที่จำเป็น
require_once '../config/database.php';
require_once '../includes/functions.php';

// เริ่ม session
session_start();

// ตรวจสอบว่าล็อกอินแล้วหรือไม่
if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

// ประมวลผลฟอร์มสมัครสมาชิก
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // รับข้อมูลจากฟอร์ม
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $email = sanitize($_POST['email']);
    $full_name = sanitize($_POST['full_name']);
    $phone = sanitize($_POST['phone']);
    
    // ตรวจสอบว่ามีการกรอกข้อมูลครบถ้วน
    if (empty($username) || empty($password) || empty($confirm_password) || empty($email) || empty($full_name)) {
        $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } elseif ($password != $confirm_password) {
        $error = 'รหัสผ่านไม่ตรงกัน';
    } elseif (strlen($password) < 6) {
        $error = 'รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร';
    } else {
        // ตรวจสอบว่าชื่อผู้ใช้หรืออีเมลซ้ำหรือไม่
        $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $username, $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $error = 'ชื่อผู้ใช้หรืออีเมลนี้มีอยู่ในระบบแล้ว';
        } else {
            // เข้ารหัสรหัสผ่าน
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // บันทึกข้อมูลลงในฐานข้อมูล
            $sql = "INSERT INTO users (username, password, email, full_name, phone) VALUES (?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sssss", $username, $hashed_password, $email, $full_name, $phone);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = 'สมัครสมาชิกสำเร็จ! คุณสามารถเข้าสู่ระบบได้ทันที';
            } else {
                $error = 'เกิดข้อผิดพลาดในการสมัครสมาชิก: ' . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก - ระบบจองห้องซ้อมดนตรี</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <h1><a href="../index.php">ระบบจองห้องซ้อมดนตรี</a></h1>
            </div>
            <nav>
                <ul>
                    <li><a href="../index.php">หน้าหลัก</a></li>
                    <li><a href="../booking/index.php">ห้องทั้งหมด</a></li>
                    <li><a href="login.php">เข้าสู่ระบบ</a></li>
                    <li><a href="register.php">สมัครสมาชิก</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <div class="container">
        <main>
            <h2>สมัครสมาชิก</h2>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                    <p><a href="login.php">คลิกที่นี่เพื่อเข้าสู่ระบบ</a></p>
                </div>
            <?php else: ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="username">ชื่อผู้ใช้</label>
                        <input type="text" id="username" name="username" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">อีเมล</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="full_name">ชื่อ-นามสกุล</label>
                        <input type="text" id="full_name" name="full_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">เบอร์โทรศัพท์</label>
                        <input type="tel" id="phone" name="phone" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">รหัสผ่าน (อย่างน้อย 6 ตัวอักษร)</label>
                        <input type="password" id="password" name="password" class="form-control" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">ยืนยันรหัสผ่าน</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn">สมัครสมาชิก</button>
                    </div>
                    
                    <p>มีบัญชีอยู่แล้ว? <a href="login.php">เข้าสู่ระบบ</a></p>
                </form>
            <?php endif; ?>
        </main>
    </div>
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> ระบบจองห้องซ้อมดนตรี</p>
        </div>
    </footer>
    <script src="../assets/js/style.js"></script>
</body>
</html>