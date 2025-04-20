<?php
// ไฟล์ booking/create.php
// หน้าสำหรับสร้างการจองใหม่
require_once '../includes/header.php';

// ตรวจสอบว่าล็อกอินแล้วหรือไม่
if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit();
}

// รับค่า room_id จาก URL (ถ้ามี)
$selected_room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;

// ดึงข้อมูลห้องทั้งหมดที่มีสถานะพร้อมใช้งาน
$sql = "SELECT * FROM rooms WHERE is_available = 1";
$result = mysqli_query($conn, $sql);

// ประมวลผลฟอร์มการจอง
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // รับข้อมูลจากฟอร์ม
    $user_id = $_SESSION['user_id'];
    $room_id = intval($_POST['room_id']);
    $booking_date = sanitize($_POST['booking_date']);
    $start_time = sanitize($_POST['start_time']);
    $end_time = sanitize($_POST['end_time']);
    
    // ตรวจสอบข้อมูล
    if (empty($room_id) || empty($booking_date) || empty($start_time) || empty($end_time)) {
        $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } elseif ($start_time >= $end_time) {
        $error = 'เวลาเริ่มต้นต้องน้อยกว่าเวลาสิ้นสุด';
    } else {
        // ตรวจสอบว่าช่วงเวลาว่างหรือไม่
        if (isTimeSlotAvailable($room_id, $booking_date, $start_time, $end_time)) {
            // คำนวณราคาทั้งหมด
            $total_price = calculateTotalPrice($room_id, $start_time, $end_time);
            
            // บันทึกข้อมูลการจอง
            $sql = "INSERT INTO bookings (user_id, room_id, booking_date, start_time, end_time, total_price) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "iisssd", $user_id, $room_id, $booking_date, $start_time, $end_time, $total_price);
            
            if (mysqli_stmt_execute($stmt)) {
                $booking_id = mysqli_insert_id($conn);
                // ไปยังหน้ายืนยันการชำระเงิน
                header('Location: ../booking/view.php?booking_id=' . $booking_id);
                exit();
            } else {
                $error = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . mysqli_error($conn);
            }
        } else {
            $error = 'ช่วงเวลาที่คุณเลือกไม่ว่าง โปรดเลือกเวลาอื่น';
        }
    }
}
?>

<h2>จองห้องซ้อมดนตรี</h2>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<form method="POST" action="" id="booking-form">
    <div class="form-group">
        <label for="room_id">เลือกห้องซ้อม</label>
        <select id="room_id" name="room_id" class="form-control" required>
            <option value="">-- เลือกห้องซ้อม --</option>
            <?php mysqli_data_seek($result, 0); // รีเซ็ตตำแหน่งการอ่านข้อมูล ?>
            <?php while ($room = mysqli_fetch_assoc($result)): ?>
                <option value="<?php echo $room['room_id']; ?>" data-rate="<?php echo $room['hourly_rate']; ?>" <?php echo ($selected_room_id == $room['room_id']) ? 'selected' : ''; ?>>
                    <?php echo $room['room_name']; ?> - <?php echo number_format($room['hourly_rate']); ?> บาท/ชั่วโมง
                </option>
            <?php endwhile; ?>
        </select>
    </div>
    
    <div class="form-group">
        <label for="booking_date">วันที่ต้องการจอง</label>
        <input type="date" id="booking_date" name="booking_date" class="form-control" required>
    </div>
    
    <div class="form-group">
        <label for="start_time">เวลาเริ่มต้น</label>
        <input type="time" id="start_time" name="start_time" class="form-control" required>
    </div>
    
    <div class="form-group">
        <label for="end_time">เวลาสิ้นสุด</label>
        <input type="time" id="end_time" name="end_time" class="form-control" required>
    </div>
    
    <div class="form-group">
        <p>ราคารวม: <span id="price-display">0.00 บาท</span></p>
    </div>
    
    <div class="form-group">
        <button type="submit" class="btn">ยืนยันการจอง</button>
    </div>
</form>

<?php
require_once '../includes/footer.php';
?>