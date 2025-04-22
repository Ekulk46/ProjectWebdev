<?php
// admin/generate_report.php
// ไฟล์สำหรับสร้างรายงานสรุปการจองห้องซ้อมดนตรี

// นำเข้าไฟล์ที่จำเป็น
require_once '../config/database.php';
require_once '../includes/functions.php';

// เริ่ม session
session_start();

// ตรวจสอบว่าล็อกอินและเป็น admin หรือ staff หรือไม่
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff')) {
    header('Location: ../auth/login.php');
    exit();
}

// รับค่าเดือนและปีจาก GET parameters
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// ตรวจสอบความถูกต้องของข้อมูล
if ($month < 1 || $month > 12 || $year < 2000 || $year > date('Y') + 1) {
    die("ข้อมูลไม่ถูกต้อง");
}

// สร้างวันที่เริ่มต้นและสิ้นสุดของเดือน
$start_date = sprintf('%04d-%02d-01', $year, $month);
$end_date = date('Y-m-t', strtotime($start_date));

// ชื่อเดือนภาษาไทย
$thai_months = [
    1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน', 
    5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม', 
    9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
];

// 1. สรุปรายการจองห้องซ้อม
$bookings_sql = "SELECT b.*, r.room_name, u.full_name, u.email
                FROM bookings b
                JOIN rooms r ON b.room_id = r.room_id
                JOIN users u ON b.user_id = u.user_id
                WHERE b.booking_date BETWEEN ? AND ?
                ORDER BY b.booking_date, b.start_time";

$stmt = mysqli_prepare($conn, $bookings_sql);
mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
mysqli_stmt_execute($stmt);
$bookings_result = mysqli_stmt_get_result($stmt);
$bookings = [];

while ($row = mysqli_fetch_assoc($bookings_result)) {
    $bookings[] = $row;
}

// 2. สรุปรายได้
$revenue_sql = "SELECT 
                    SUM(total_price) as total_revenue,
                    COUNT(*) as total_bookings,
                    SUM(CASE WHEN status = 'confirmed' OR status = 'completed' THEN total_price ELSE 0 END) as confirmed_revenue,
                    COUNT(CASE WHEN status = 'confirmed' OR status = 'completed' THEN 1 END) as confirmed_bookings,
                    SUM(CASE WHEN status = 'cancelled' THEN total_price ELSE 0 END) as cancelled_revenue,
                    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_bookings
                FROM bookings
                WHERE booking_date BETWEEN ? AND ?";

$stmt = mysqli_prepare($conn, $revenue_sql);
mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
mysqli_stmt_execute($stmt);
$revenue_result = mysqli_stmt_get_result($stmt);
$revenue_data = mysqli_fetch_assoc($revenue_result);

// 3. สรุปจำนวนผู้ใช้งาน
$users_sql = "SELECT COUNT(DISTINCT user_id) as total_users
             FROM bookings
             WHERE booking_date BETWEEN ? AND ?";

$stmt = mysqli_prepare($conn, $users_sql);
mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
mysqli_stmt_execute($stmt);
$users_result = mysqli_stmt_get_result($stmt);
$users_data = mysqli_fetch_assoc($users_result);

// 4. สรุปเวลาการใช้งานแต่ละห้อง (ชั่วโมง)
$room_usage_sql = "SELECT 
                    r.room_id,
                    r.room_name,
                    COUNT(*) as total_bookings,
                    SUM(TIME_TO_SEC(TIMEDIFF(end_time, start_time))/3600) as total_hours
                FROM bookings b
                JOIN rooms r ON b.room_id = r.room_id
                WHERE b.booking_date BETWEEN ? AND ?
                AND (b.status = 'confirmed' OR b.status = 'completed')
                GROUP BY r.room_id
                ORDER BY total_hours DESC";

$stmt = mysqli_prepare($conn, $room_usage_sql);
mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
mysqli_stmt_execute($stmt);
$room_usage_result = mysqli_stmt_get_result($stmt);
$room_usage = [];

while ($row = mysqli_fetch_assoc($room_usage_result)) {
    $room_usage[] = $row;
}

// สร้างชื่อไฟล์
$filename = "รายงานการจองห้องซ้อมดนตรี_{$thai_months[$month]}_{$year}.csv";

// ตั้งค่า header สำหรับการดาวน์โหลดไฟล์ CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// เปิด output stream
$output = fopen('php://output', 'w');

// เขียน BOM (Byte Order Mark) เพื่อให้ Excel อ่านภาษาไทยได้ถูกต้อง
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// ส่วนหัวของรายงาน
fputcsv($output, ["รายงานการจองห้องซ้อมดนตรีประจำเดือน {$thai_months[$month]} {$year}"]);
fputcsv($output, [""]);

// 1. สรุปรายได้
fputcsv($output, ["1. สรุปรายได้"]);
fputcsv($output, ["รายได้รวมทั้งหมด", number_format($revenue_data['total_revenue'], 2) . " บาท"]);
fputcsv($output, ["จำนวนการจองทั้งหมด", $revenue_data['total_bookings'] . " รายการ"]);
fputcsv($output, ["รายได้จากการจองที่ยืนยันแล้ว", number_format($revenue_data['confirmed_revenue'], 2) . " บาท"]);
fputcsv($output, ["จำนวนการจองที่ยืนยันแล้ว", $revenue_data['confirmed_bookings'] . " รายการ"]);
fputcsv($output, ["มูลค่าการจองที่ยกเลิก", number_format($revenue_data['cancelled_revenue'], 2) . " บาท"]);
fputcsv($output, ["จำนวนการจองที่ยกเลิก", $revenue_data['cancelled_bookings'] . " รายการ"]);
fputcsv($output, [""]);

// 2. สรุปจำนวนผู้ใช้งาน
fputcsv($output, ["2. สรุปจำนวนผู้ใช้งาน"]);
fputcsv($output, ["จำนวนผู้ใช้งานที่มีการจองทั้งหมด", $users_data['total_users'] . " คน"]);
fputcsv($output, [""]);

// 3. สรุปเวลาการใช้งานแต่ละห้อง
fputcsv($output, ["3. สรุปเวลาการใช้งานแต่ละห้อง"]);
fputcsv($output, ["ชื่อห้อง", "จำนวนการจอง", "เวลาใช้งานรวม (ชั่วโมง)"]);

foreach ($room_usage as $room) {
    fputcsv($output, [
        $room['room_name'],
        $room['total_bookings'],
        number_format($room['total_hours'], 2)
    ]);
}
fputcsv($output, [""]);

// 4. รายละเอียดการจองทั้งหมด
fputcsv($output, ["4. รายการจองทั้งหมด"]);
fputcsv($output, ["รหัสการจอง", "ชื่อผู้จอง", "อีเมล", "ห้อง", "วันที่จอง", "เวลาเริ่ม", "เวลาสิ้นสุด", "ราคารวม", "สถานะ"]);

foreach ($bookings as $booking) {
    // แปลสถานะเป็นภาษาไทย
    $status_thai = '';
    switch($booking['status']) {
        case 'pending': $status_thai = 'รอยืนยัน'; break;
        case 'confirmed': $status_thai = 'ยืนยันแล้ว'; break;
        case 'cancelled': $status_thai = 'ยกเลิกแล้ว'; break;
        case 'completed': $status_thai = 'เสร็จสิ้น'; break;
        default: $status_thai = $booking['status'];
    }
    
    fputcsv($output, [
        $booking['booking_id'],
        $booking['full_name'],
        $booking['email'],
        $booking['room_name'],
        date('d/m/Y', strtotime($booking['booking_date'])),
        date('H:i', strtotime($booking['start_time'])),
        date('H:i', strtotime($booking['end_time'])),
        number_format($booking['total_price'], 2) . " บาท",
        $status_thai
    ]);
}

// ปิด CSV file
fclose($output);
exit();
?>