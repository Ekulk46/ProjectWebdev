<?php
// ไฟล์ index.php
// หน้าหลักของเว็บไซต์
require_once __DIR__ . '/includes/header.php';

// ดึงข้อมูลห้องซ้อมทั้งหมดที่มีสถานะพร้อมใช้งาน
$sql = "SELECT * FROM rooms WHERE is_available = 1";
$result = mysqli_query($conn, $sql)
?>

<div class="booking-banner">
    <h2>จองห้องซ้อมดนตรีออนไลน์</h2>
    <p>บริการห้องซ้อมคุณภาพ พร้อมอุปกรณ์ครบครัน จองง่าย สะดวก รวดเร็ว</p>
    <?php if (isLoggedIn()): ?>
        <a href="booking/create.php" class="btn">จองห้องซ้อมเลย</a>
    <?php else: ?>
        <a href="auth/login.php" class="btn">เข้าสู่ระบบเพื่อจอง</a>
    <?php endif; ?>
</div>


<h2>ห้องซ้อมดนตรี</h2>
<p>ยินดีต้อนรับสู่ระบบจองห้องซ้อมดนตรี! เลือกห้องซ้อมที่คุณต้องการใช้งานและจองเวลาได้ง่ายๆ</p>

<?php if (!isLoggedIn()): ?>
    <div class="alert alert-info">
        กรุณา <a href="auth/login.php">เข้าสู่ระบบ</a> หรือ <a href="auth/register.php">สมัครสมาชิก</a> เพื่อจองห้องซ้อม
    </div>
<?php endif; ?>













<?php
require_once __DIR__ .'/includes/footer.php';
?>