<?php
// ไฟล์ booking/view.php
// หน้าแสดงรายการจองของผู้ใช้
require_once '../includes/header.php';

// ตรวจสอบว่าล็อกอินแล้วหรือไม่
if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit();
}

// ดึงข้อมูลการจองของผู้ใช้ทั้งหมด
$user_id = $_SESSION['user_id'];
$sql = "SELECT b.*, r.room_name, r.hourly_rate, r.image_url
        FROM bookings b 
        INNER JOIN rooms r ON b.room_id = r.room_id 
        WHERE b.user_id = ? 
        ORDER BY b.booking_date DESC, b.start_time DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<div class="booking-header">
    <h2>การจองของฉัน</h2>
    <a href="create.php" class="btn btn-primary">จองห้องใหม่</a>
</div>

<?php if (mysqli_num_rows($result) > 0): ?>
    <div class="booking-card-container">
    <?php while ($booking = mysqli_fetch_assoc($result)): ?>
        <div class="booking-card">
            <div class="booking-card-header <?php echo $booking['status']; ?>">
                <h3><?php echo $booking['room_name']; ?></h3>
                <span class="booking-status">
                    <?php 
                    switch ($booking['status']) {
                        case 'pending':
                            echo 'รอยืนยัน';
                            break;
                        case 'confirmed':
                            echo 'ยืนยันแล้ว';
                            break;
                        case 'cancelled':
                            echo 'ยกเลิกแล้ว';
                            break;
                        case 'completed':
                            echo 'เสร็จสิ้น';
                            break;
                        default:
                            echo 'ไม่ทราบสถานะ';
                    }
                    ?>
                </span>
            </div>
            
            <div class="booking-card-body">
                <div class="booking-image">
                    <?php if (isset($booking['image_url']) && !empty($booking['image_url'])): ?>
                        <img src="../<?php echo $booking['image_url']; ?>" alt="<?php echo $booking['room_name']; ?>">
                    <?php else: ?>
                        <img src="../assets/images/default-room.jpg" alt="<?php echo $booking['room_name']; ?>">
                    <?php endif; ?>
                </div>
                
                <div class="booking-details">
                    <div class="booking-info">
                        <div class="info-item">
                            <span class="info-label">วันที่:</span>
                            <span class="info-value"><?php echo date('d/m/Y', strtotime($booking['booking_date'])); ?></span>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">เวลา:</span>
                            <span class="info-value"><?php echo date('H:i', strtotime($booking['start_time'])) . ' - ' . date('H:i', strtotime($booking['end_time'])); ?></span>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">ราคา:</span>
                            <span class="info-value"><?php echo number_format($booking['total_price'], 2); ?> บาท</span>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">วันที่จอง:</span>
                            <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($booking['created_at'])); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="booking-card-footer">
                <?php if ($booking['status'] == 'pending' || $booking['status'] == 'confirmed'): ?>
                    <?php if (strtotime($booking['booking_date'] . ' ' . $booking['start_time']) > time()): ?>
                        <a href="cancel.php?booking_id=<?php echo $booking['booking_id']; ?>" class="btn btn-danger" onclick="return confirm('คุณแน่ใจหรือไม่ที่จะยกเลิกการจองนี้?');">ยกเลิกการจอง</a>
                    <?php else: ?>
                        <span class="notice">ไม่สามารถยกเลิกได้ (เลยเวลาแล้ว)</span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endwhile; ?>
    </div>
<?php else: ?>
    <div class="no-bookings">
        <div class="alert alert-info">คุณยังไม่มีรายการจอง</div>
        <a href="index.php" class="btn btn-primary">ดูห้องทั้งหมด</a>
    </div>
<?php endif; ?>

<style>
.booking-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.booking-card-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.booking-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
}

.booking-card:hover {
    transform: translateY(-5px);
}

.booking-card-header {
    padding: 12px 15px;
    background-color: #f8f9fa;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #ddd;
}

.booking-card-header h3 {
    margin: 0;
    font-size: 18px;
    color: #333;
}

.booking-card-header.confirmed {
    background-color: #d4edda;
}

.booking-card-header.pending {
    background-color: #fff3cd;
}

.booking-card-header.cancelled {
    background-color: #f8d7da;
}

.booking-card-header.completed {
    background-color: #cce5ff;
}

.booking-status {
    font-size: 14px;
    font-weight: bold;
    padding: 4px 8px;
    border-radius: 4px;
    background-color: #fff;
}

.booking-card-body {
    padding: 15px;
    display: flex;
    flex-direction: column;
}

.booking-image {
    margin-bottom: 10px;
    height: 150px;
    overflow: hidden;
    border-radius: 4px;
}

.booking-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.booking-details {
    flex: 1;
}

.booking-info {
    display: grid;
    grid-template-columns: 1fr;
    gap: 8px;
}

.info-item {
    display: flex;
    justify-content: space-between;
    padding: 5px 0;
    border-bottom: 1px dashed #eee;
}

.info-label {
    font-weight: bold;
    color: #555;
}

.info-value {
    text-align: right;
}

.booking-card-footer {
    padding: 12px 15px;
    background-color: #f8f9fa;
    border-top: 1px solid #ddd;
    text-align: center;
}

.notice {
    color: #6c757d;
    font-style: italic;
}

.no-bookings {
    text-align: center;
    padding: 30px;
}

.btn-danger {
    background-color: #dc3545;
    color: white;
}

.btn-primary {
    background-color: #007bff;
    color: white;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
}

@media (max-width: 768px) {
    .booking-card-container {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
require_once '../includes/footer.php';
?>