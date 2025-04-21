<?php
// ไฟล์ auth/login.php
// หน้าสำหรับล็อกอินเข้าสู่ระบบ

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

// ประมวลผลฟอร์มล็อกอิน
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // รับข้อมูลจากฟอร์ม
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    // ตรวจสอบว่ามีการกรอกข้อมูลครบถ้วน
    if (empty($username) || empty($password)) {
        $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
    } else {
        // ค้นหาผู้ใช้ในฐานข้อมูล
        $sql = "SELECT * FROM users WHERE username = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            
            // ตรวจสอบว่าบัญชีผู้ใช้ถูกเปิดใช้งานหรือไม่
            if ($user['status'] !== 'active') {
                $error = 'บัญชีผู้ใช้นี้ถูกระงับหรือยังไม่ได้เปิดใช้งาน กรุณาติดต่อผู้ดูแลระบบ';
            } 
            // ตรวจสอบรหัสผ่าน
            elseif (password_verify($password, $user['password'])) {
                // สร้าง session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role']; // เก็บค่า role ไว้ใน session
                
                // เก็บข้อมูลอื่นๆ ที่อาจจำเป็นต้องใช้
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                
                // ตรวจสอบว่าเป็น admin หรือไม่
                if ($user['role'] == 'admin') {
                    // ถ้าเป็น admin ให้ redirect ไปยังหน้า admin
                    header('Location: ../admin/index.php');
                } else {
                    // ถ้าเป็น user ธรรมดา ให้ redirect ไปยังหน้าหลัก
                    header('Location: ../index.php');
                }
                exit();
            } else {
                $error = 'รหัสผ่านไม่ถูกต้อง';
            }
        } else {
            $error = 'ไม่พบชื่อผู้ใช้นี้ในระบบ';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - ระบบจองห้องซ้อมดนตรี</title>
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
            <h2>เข้าสู่ระบบ</h2>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">ชื่อผู้ใช้</label>
                    <input type="text" id="username" name="username" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="password">รหัสผ่าน</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn">เข้าสู่ระบบ</button>
                </div>
                
                <p>ยังไม่มีบัญชี? <a href="register.php">สมัครสมาชิก</a></p>
            </form>
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