<?php
// ไฟล์ booking/index.php
// หน้าแสดงห้องทั้งหมด
require_once __DIR__ . '/../includes/header.php';

// ดึงข้อมูลห้องซ้อมทั้งหมดที่มีสถานะพร้อมใช้งาน
$sql = "SELECT * FROM rooms WHERE is_available = 1";
$result = mysqli_query($conn, $sql);
?>

<!-- Banner สำหรับหน้ารายการห้อง -->
<div class="booking-banner">
    <h2>ห้องซ้อมดนตรีคุณภาพ</h2>
    <p>เรามีห้องซ้อมดนตรีให้บริการหลากหลายรูปแบบ พร้อมอุปกรณ์ครบครัน เสียงดี บรรยากาศดี ในราคาที่คุณจับต้องได้</p>
    <?php if (isLoggedIn()): ?>
        <a href="create.php" class="btn">จองเลย</a>
    <?php else: ?>
        <a href="../auth/login.php" class="btn">เข้าสู่ระบบเพื่อจอง</a>
    <?php endif; ?>
</div>

<h2>ห้องซ้อมทั้งหมด</h2>
<p>เลือกห้องซ้อมที่คุณต้องการใช้บริการและทำการจองได้เลย</p>

<?php if (!isLoggedIn()): ?>
    <div class="alert alert-info">
        กรุณา <a href="../auth/login.php">เข้าสู่ระบบ</a> หรือ <a href="../auth/register.php">สมัครสมาชิก</a> เพื่อจองห้องซ้อม
    </div>
<?php endif; ?>

<div class="room-list">
    <?php 
    $delay = 0;
    while ($room = mysqli_fetch_assoc($result)): 
        $delay += 0.1; // เพิ่มดีเลย์ 0.1 วินาทีสำหรับแต่ละห้อง
    ?>
        <div class="room-item" style="animation-delay: <?php echo $delay; ?>s">
            <div class="room-image">
                <?php if (isset($room['image_url']) && !empty($room['image_url'])): ?>
                    <img src="../<?php echo $room['image_url']; ?>" data-src="../<?php echo $room['image_url']; ?>" alt="<?php echo $room['room_name']; ?>">
                <?php else: ?>
                    <img src="../assets/images/default-room.jpg" alt="<?php echo $room['room_name']; ?>">
                <?php endif; ?>
            </div>
            <div class="room-details">
                <h3><?php echo $room['room_name']; ?></h3>
                <p class="room-price"><?php echo number_format($room['hourly_rate']); ?> บาท/ชั่วโมง</p>
                <p class="room-capacity">รองรับ <?php echo $room['capacity']; ?> คน</p>
                <p><?php echo $room['description']; ?></p>
                <?php if (isLoggedIn()): ?>
                    <a href="create.php?room_id=<?php echo $room['room_id']; ?>" class="btn">จองเลย</a>
                <?php else: ?>
                    <a href="../auth/login.php" class="btn">เข้าสู่ระบบเพื่อจอง</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endwhile; ?>
    
    <?php if (mysqli_num_rows($result) == 0): ?>
        <div class="alert alert-warning">ขณะนี้ยังไม่มีห้องซ้อมที่พร้อมให้บริการ กรุณาตรวจสอบอีกครั้งในภายหลัง</div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

<?php
echo '<pre>';
print_r($room['image_url']);
echo '</pre>';
?>