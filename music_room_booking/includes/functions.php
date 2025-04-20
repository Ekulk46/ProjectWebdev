<?php
// ไฟล์ includes/functions.php
// ฟังก์ชันที่ใช้งานบ่อยในระบบ

// ฟังก์ชันสำหรับตรวจสอบการล็อกอิน
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

// ฟังก์ชันสำหรับกำหนด base URL ของเว็บไซต์
// ฟังก์ชันสำหรับกำหนด base URL ของเว็บไซต์
function getBaseUrl() {
    // กำหนดให้รู้จัก protocol และ host
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    
    // ระบุชื่อโฟลเดอร์โปรเจคให้ชัดเจน
    $baseDir = '/music_room_booking';
    
    // สำหรับกรณีที่มีการติดตั้งใน subdirectory ที่ต่างกัน
    // $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    // $baseDir = rtrim(str_replace('\\', '/', $scriptDir), '/');
    
    return "$protocol://$host$baseDir";
}

// ฟังก์ชันสำหรับดึงข้อมูลผู้ใช้ปัจจุบัน
function getCurrentUser() {
    global $conn;
    if (isLoggedIn()) {
        $user_id = $_SESSION['user_id'];
        $sql = "SELECT * FROM users WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }
    return null;
}

// ฟังก์ชันสำหรับตรวจสอบว่าเวลาจองว่างหรือไม่
function isTimeSlotAvailable($room_id, $booking_date, $start_time, $end_time) {
    global $conn;
    
    $sql = "SELECT COUNT(*) as count FROM bookings 
            WHERE room_id = ? AND booking_date = ? AND status != 'cancelled'
            AND ((start_time <= ? AND end_time > ?) OR 
                (start_time < ? AND end_time >= ?) OR
                (start_time >= ? AND end_time <= ?))";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "isssssss", $room_id, $booking_date, $end_time, $start_time, $end_time, $start_time, $start_time, $end_time);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    return $row['count'] == 0;
}

// ฟังก์ชันคำนวณราคาทั้งหมดจากการจอง
function calculateTotalPrice($room_id, $start_time, $end_time) {
    global $conn;
    
    // ดึงอัตราค่าห้องต่อชั่วโมง
    $sql = "SELECT hourly_rate FROM rooms WHERE room_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $room_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $room = mysqli_fetch_assoc($result);
    
    // คำนวณจำนวนชั่วโมง
    $start = new DateTime($start_time);
    $end = new DateTime($end_time);
    $diff = $start->diff($end);
    $hours = $diff->h + ($diff->i / 60);
    
    // คำนวณราคารวม
    return $room['hourly_rate'] * $hours;
}

// ฟังก์ชันทำความสะอาดข้อมูลที่รับเข้ามา
function sanitize($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = mysqli_real_escape_string($conn, $data);
    return $data;
}

// ฟังก์ชันสร้าง QR code สำหรับการชำระเงิน
function generatePaymentQRCode($booking_id, $amount) {
    // สมมติว่ามีการสร้าง QR code สำหรับการชำระเงิน
    $qr_code_path = "assets/images/qr_codes/payment_" . $booking_id . ".png";
    
    // เพิ่มโค้ดสำหรับสร้าง QR code ที่นี่ (ในที่นี้เป็นแค่ stub)
    
    return $qr_code_path;
}

// ฟังก์ชันแสดงข้อความแจ้งเตือน
function showAlert($message, $type = 'info') {
    return '<div class="alert alert-' . $type . '">' . $message . '</div>';
}

// ฟังก์ชันเชื่อมต่อฐานข้อมูล
function getConnection() {
    // ข้อมูลสำหรับการเชื่อมต่อฐานข้อมูล
    $host = 'localhost'; // หรือ IP ของ host ที่ใช้
    $username = 'root';  // username สำหรับเข้าถึงฐานข้อมูล
    $password = '';      // password สำหรับเข้าถึงฐานข้อมูล
    $database = 'music_room_booking'; // ชื่อฐานข้อมูลที่จะใช้งาน
    
    // สร้างการเชื่อมต่อ
    $conn = new mysqli($host, $username, $password, $database);
    
    // ตรวจสอบการเชื่อมต่อ
    if ($conn->connect_error) {
        die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
    }
    
    // ตั้งค่า character set เป็น utf8 สำหรับรองรับภาษาไทย
    $conn->set_charset("utf8");
    
    return $conn;
}

?>

