<?php
// ไฟล์ booking/cancel.php
// หน้าสำหรับยกเลิกการจอง
require_once '../includes/header.php';

// ตรวจสอบว่าล็อกอินแล้วหรือไม่
if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit();
}

// ตรวจสอบว่ามีพารามิเตอร์ booking_id หรือไม่
if (!isset($_GET['booking_id']) || empty($_GET['booking_id'])) {
    header('Location: view.php');
    exit();
}

$booking_id = intval($_GET['booking_id']);
$user_id = $_SESSION['user_id'];

// ตรวจสอบว่าการจองนี้เป็นของผู้ใช้นี้หรือไม่
$sql = "SELECT * FROM bookings WHERE booking_id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $booking_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) != 1) {
    // ไม่พบการจองหรือการจองไม่ใช่ของผู้ใช้นี้
    header('Location: view.php');
    exit();
}

$booking = mysqli_fetch_assoc($result);

// ตรวจสอบว่าการจองนี้สามารถยกเลิกได้หรือไม่
if ($booking['status'] != 'pending' && $booking['status'] != 'confirmed') {
    // การจองไม่อยู่ในสถานะที่สามารถยกเลิกได้
    header('Location: view.php');
    exit();
}

// ตรวจสอบว่าเวลาการจองยังไม่มาถึง
if (strtotime($booking['booking_date'] . ' ' . $booking['start_time']) <= time()) {
    // เวลาการจองมาถึงแล้ว ไม่สามารถยกเลิกได้
    header('Location: view.php');
    exit();
}

// ประมวลผลการยกเลิก
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // อัพเดทสถานะการจองเป็นยกเลิก
    $sql = "UPDATE bookings SET status = 'cancelled' WHERE booking_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $booking_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $success = 'ยกเลิกการจองเรียบร้อยแล้ว';
    } else {
        $error = 'เกิดข้อผิดพลาดในการยกเลิกการจอง: ' . mysqli_error($conn);
    }
}

// ดึงข้อมูลห้อง
$sql = "SELECT * FROM rooms WHERE room_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $booking['room_id']);
mysqli_stmt_execute($stmt);
$room_result = mysqli_stmt_get_result($stmt);
$room = mysqli_fetch_assoc($room_result);
?>

<h2>ยกเลิกการจอง</h2>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
    <p><a href="view.php" class="btn">กลับไปยังรายการจอง</a></p>
<?php else: ?>
    <div class="booking-details">
        <h3>รายละเอียดการจอง</h3>
        <p><strong>ห้อง:</strong> <?php echo $room['room_name']; ?></p>
        <p><strong>วันที่:</strong> <?php echo date('d/m/Y', strtotime($booking['booking_date'])); ?></p>
        <p><strong>เวลา:</strong> <?php echo date('H:i', strtotime($booking['start_time'])) . ' - ' . date('H:i', strtotime($booking['end_time'])); ?></p>
        <p><strong>ราคา:</strong> <?php echo number_format($booking['total_price'], 2); ?> บาท</p>
        <p><strong>สถานะ:</strong> 
            <?php 
            switch ($booking['status']) {
                case 'pending':
                    echo 'รอยืนยัน';
                    break;
                case 'confirmed':
                    echo 'ยืนยันแล้ว';
                    break;
                default:
                    echo 'ไม่ทราบสถานะ';
            }
            ?>
        </p>
        
        <div class="alert alert-warning">
            <p>คุณกำลังจะยกเลิกการจองนี้ คุณแน่ใจหรือไม่?</p>
            <p>หมายเหตุ: หากคุณได้ชำระเงินแล้ว กรุณาติดต่อเจ้าหน้าที่เพื่อขอรับเงินคืน</p>
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <button type="submit" class="btn btn-danger">ยืนยันการยกเลิก</button>
                <a href="view.php" class="btn">ยกเลิกและกลับไปยังรายการจอง</a>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php
require_once '../includes/footer.php';
?>