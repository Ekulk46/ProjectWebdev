<?php
// ไฟล์ index.php
// หน้าหลักของเว็บไซต์
require_once __DIR__ . '/includes/header.php';

// ดึงข้อมูลห้องซ้อมทั้งหมดที่มีสถานะพร้อมใช้งาน
$sql = "SELECT * FROM rooms WHERE is_available = 1";
$result = mysqli_query($conn, $sql);

// ดึงข้อมูลการจองห้องซ้อมทั้งหมด
$bookings_sql = "SELECT b.booking_id, b.booking_date, b.start_time, b.end_time, b.status, 
                r.room_name, r.room_id, r.image_url
                FROM bookings b 
                JOIN rooms r ON b.room_id = r.room_id 
                ORDER BY b.booking_date DESC, b.start_time ASC";
$bookings_result = mysqli_query($conn, $bookings_sql);
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

<div class="container">
    <!-- แสดงรายการจองห้องซ้อม -->
    <div class="admin-section">
        <h2>ตารางการจองห้องซ้อม</h2>
        
        <?php if (mysqli_num_rows($bookings_result) > 0): ?>
            <div class="table-responsive">
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>ชื่อห้อง</th>
                            <th>วันที่</th>
                            <th>เวลาเริ่ม</th>
                            <th>เวลาสิ้นสุด</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($booking = mysqli_fetch_assoc($bookings_result)): 
                            // แปลงสถานะเป็นภาษาไทย
                            $status_thai = '';
                            $status_class = '';
                            switch ($booking['status']) {
                                case 'pending':
                                    $status_thai = 'รอการยืนยัน';
                                    $status_class = 'status-pending';
                                    break;
                                case 'confirmed':
                                    $status_thai = 'ยืนยันแล้ว';
                                    $status_class = 'status-active';
                                    break;
                                case 'cancelled':
                                    $status_thai = 'ยกเลิกแล้ว';
                                    $status_class = 'status-banned';
                                    break;
                                case 'completed':
                                    $status_thai = 'เสร็จสิ้น';
                                    $status_class = 'status-inactive';
                                    break;
                                default:
                                    $status_thai = $booking['status'];
                                    $status_class = '';
                            }
                            
                            // แปลงรูปแบบวันที่
                            $date = new DateTime($booking['booking_date']);
                            $thai_date = $date->format('d/m/Y');
                        ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center;">
                                    <?php if (!empty($booking['image_url'])): ?>
                                    <div style="width: 40px; height: 40px; margin-right: 10px; overflow: hidden; border-radius: 3px;">
                                        <img src="<?php echo htmlspecialchars($booking['image_url']); ?>" alt="<?php echo htmlspecialchars($booking['room_name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                    <?php endif; ?>
                                    <span><?php echo htmlspecialchars($booking['room_name']); ?></span>
                                </div>
                            </td>
                            <td><?php echo $thai_date; ?></td>
                            <td><?php echo date('H:i', strtotime($booking['start_time'])); ?> น.</td>
                            <td><?php echo date('H:i', strtotime($booking['end_time'])); ?> น.</td>
                            <td>
                                <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_thai; ?></span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fa fa-info-circle"></i> ยังไม่มีรายการจองห้องซ้อมในขณะนี้
            </div>
        <?php endif; ?>
    </div>

    <h2>ห้องซ้อมดนตรี</h2>
    <p>ยินดีต้อนรับสู่ระบบจองห้องซ้อมดนตรี! เลือกห้องซ้อมที่คุณต้องการใช้งานและจองเวลาได้ง่ายๆ</p>

    <?php if (!isLoggedIn()): ?>
        <div class="alert alert-info">
            กรุณา <a href="auth/login.php">เข้าสู่ระบบ</a> หรือ <a href="auth/register.php">สมัครสมาชิก</a> เพื่อจองห้องซ้อม
        </div>
    <?php endif; ?>
    
    <!-- แสดงรายการห้องซ้อมที่พร้อมใช้งาน -->
    <?php if (mysqli_num_rows($result) > 0): ?>
        <div class="room-list">
            <?php while ($room = mysqli_fetch_assoc($result)): ?>
                <div class="room-item">
                    <div class="room-image">
                        <?php if (!empty($room['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($room['image_url']); ?>" alt="<?php echo htmlspecialchars($room['room_name']); ?>">
                        <?php else: ?>
                            <img src="assets/images/default-room.jpg" alt="Default Room Image">
                        <?php endif; ?>
                    </div>
                    <div class="room-details">
                        <h3><?php echo htmlspecialchars($room['room_name']); ?></h3>
                        <div class="room-price"><?php echo number_format($room['hourly_rate']); ?> บาท/ชั่วโมง</div>
                        <?php if (!empty($room['capacity'])): ?>
                            <div class="room-capacity">รองรับ <?php echo $room['capacity']; ?> คน</div>
                        <?php endif; ?>
                        <p><?php echo htmlspecialchars($room['description']); ?></p>
                        <?php if (isLoggedIn()): ?>
                            <a href="booking/create.php?room_id=<?php echo $room['room_id']; ?>" class="btn">จองห้องนี้</a>
                        <?php else: ?>
                            <a href="auth/login.php" class="btn">เข้าสู่ระบบเพื่อจอง</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            ไม่พบข้อมูลห้องซ้อมที่พร้อมใช้งานในขณะนี้
        </div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ .'/includes/footer.php';
?>