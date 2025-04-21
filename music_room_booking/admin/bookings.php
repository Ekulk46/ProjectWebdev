<?php
// ไฟล์ admin/bookings.php
// หน้าจัดการการจองห้องซ้อมดนตรีสำหรับ Admin

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

// ตรวจสอบว่ามีการเรียก AJAX สำหรับดึงข้อมูลการจองหรือไม่
if (isset($_GET['action']) && $_GET['action'] === 'get_booking_details' && isset($_GET['booking_id'])) {
    $booking_id = intval($_GET['booking_id']);
    
    // ดึงข้อมูลการจองพร้อมข้อมูลผู้ใช้และห้อง
    $sql = "SELECT b.*, u.username, u.full_name, u.email, u.phone, r.room_name, r.hourly_rate
            FROM bookings b
            JOIN users u ON b.user_id = u.user_id
            JOIN rooms r ON b.room_id = r.room_id
            WHERE b.booking_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $booking_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 0) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Booking not found']);
        exit();
    }
    
    $booking = mysqli_fetch_assoc($result);
    
    // คำนวณจำนวนชั่วโมงที่จอง
    $start_time = new DateTime($booking['start_time']);
    $end_time = new DateTime($booking['end_time']);
    $interval = $start_time->diff($end_time);
    $hours = $interval->h + ($interval->i / 60);
    $booking['hours'] = $hours;
    
    // ดึงข้อมูลการชำระเงิน (ถ้ามี)
    $payment_sql = "SELECT * FROM payments WHERE booking_id = ?";
    $payment_stmt = mysqli_prepare($conn, $payment_sql);
    mysqli_stmt_bind_param($payment_stmt, "i", $booking_id);
    mysqli_stmt_execute($payment_stmt);
    $payment_result = mysqli_stmt_get_result($payment_stmt);
    
    if (mysqli_num_rows($payment_result) > 0) {
        $booking['payment'] = mysqli_fetch_assoc($payment_result);
    }
    
    // ส่งข้อมูลกลับในรูปแบบ JSON
    header('Content-Type: application/json');
    echo json_encode($booking);
    exit();
}

// รับ action จาก URL
$action = isset($_GET['action']) ? $_GET['action'] : '';
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
$message = '';
$error = '';

// การยืนยันการจอง
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_booking'])) {
    $confirm_booking_id = intval($_POST['booking_id']);
    
    // อัพเดทสถานะการจองเป็น confirmed
    $update_sql = "UPDATE bookings SET status = 'confirmed' WHERE booking_id = ?";
    $update_stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($update_stmt, "i", $confirm_booking_id);
    
    if (mysqli_stmt_execute($update_stmt)) {
        $message = "ยืนยันการจองเรียบร้อยแล้ว";
    } else {
        $error = "เกิดข้อผิดพลาดในการยืนยันการจอง: " . mysqli_error($conn);
    }
}

// การยกเลิกการจอง
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {
    $cancel_booking_id = intval($_POST['booking_id']);
    
    // อัพเดทสถานะการจองเป็น cancelled
    $update_sql = "UPDATE bookings SET status = 'cancelled' WHERE booking_id = ?";
    $update_stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($update_stmt, "i", $cancel_booking_id);
    
    if (mysqli_stmt_execute($update_stmt)) {
        $message = "ยกเลิกการจองเรียบร้อยแล้ว";
    } else {
        $error = "เกิดข้อผิดพลาดในการยกเลิกการจอง: " . mysqli_error($conn);
    }
}

// การทำเครื่องหมายว่าการจองเสร็จสิ้น
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_booking'])) {
    $complete_booking_id = intval($_POST['booking_id']);
    
    // อัพเดทสถานะการจองเป็น completed
    $update_sql = "UPDATE bookings SET status = 'completed' WHERE booking_id = ?";
    $update_stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($update_stmt, "i", $complete_booking_id);
    
    if (mysqli_stmt_execute($update_stmt)) {
        $message = "ทำเครื่องหมายการจองเป็นเสร็จสิ้นเรียบร้อยแล้ว";
    } else {
        $error = "เกิดข้อผิดพลาดในการอัพเดทสถานะการจอง: " . mysqli_error($conn);
    }
}

// การลบข้อมูลการจอง
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_booking'])) {
    $delete_booking_id = intval($_POST['booking_id']);
    
    // ลบข้อมูลการจอง
    $delete_sql = "DELETE FROM bookings WHERE booking_id = ?";
    $delete_stmt = mysqli_prepare($conn, $delete_sql);
    mysqli_stmt_bind_param($delete_stmt, "i", $delete_booking_id);
    
    if (mysqli_stmt_execute($delete_stmt)) {
        $message = "ลบข้อมูลการจองเรียบร้อยแล้ว";
    } else {
        $error = "เกิดข้อผิดพลาดในการลบข้อมูล: " . mysqli_error($conn);
    }
}

// ดึงข้อมูลการจองทั้งหมด พร้อมข้อมูลผู้ใช้และห้อง
$sql = "SELECT b.*, u.username, u.full_name, u.email, r.room_name, r.hourly_rate
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN rooms r ON b.room_id = r.room_id
        ORDER BY b.booking_date DESC, b.start_time DESC";
$result = mysqli_query($conn, $sql);
$bookings = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $bookings[] = $row;
    }
}

// สถิติการจองตามสถานะ
$booking_stats = [
    'pending' => 0,
    'confirmed' => 0,
    'cancelled' => 0,
    'completed' => 0,
    'total' => count($bookings),
    'today' => 0
];

$current_date = date('Y-m-d');
foreach ($bookings as $booking) {
    $booking_stats[$booking['status']]++;
    if ($booking['booking_date'] == $current_date) {
        $booking_stats['today']++;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการการจอง - ระบบจองห้องซ้อมดนตรี</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        /* เพิ่ม CSS เฉพาะสำหรับหน้านี้ */
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 15px;
            text-align: center;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card h3 {
            margin-bottom: 10px;
            color: #333;
        }
        
        .stat-card p {
            font-size: 1.5rem;
            font-weight: bold;
            color: #4CAF50;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .search-box {
            display: flex;
            gap: 10px;
        }
        
        .search-box input {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        
        .booking-filter {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 5px 10px;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
            border-radius: 3px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .filter-btn.active {
            background-color: #4CAF50;
            color: #fff;
            border-color: #4CAF50;
        }
        
        /* ปรับแต่งส่วนของตาราง */
        .booking-table {
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
            overflow-x: auto;
            font-size: 13px;
            border-collapse: collapse;
            width: 100%;
        }
        
        .booking-table th, .booking-table td {
            padding: 10px;
            line-height: 1.2;
            vertical-align: middle;
        }
        
        .booking-table tbody tr {
            border-bottom: 1px solid #f0f0f0;
        }
        
        .booking-table tbody tr:hover {
            background-color: #f9f9f9;
        }
        
        .booking-table thead {
            position: sticky;
            top: 0;
            background-color: #f2f2f2;
            z-index: 10;
            border-bottom: 2px solid #ddd;
        }
        
        .booking-table th {
            font-weight: 500;
            text-align: left;
        }
        
        /* ปรับแต่ง badge สำหรับสถานะการจอง */
        .status-badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
            text-align: center;
            min-width: 80px;
        }
        
        .status-pending {
            background-color: #ffa726;
            color: white;
        }
        
        .status-confirmed {
            background-color: #43a047;
            color: white;
        }
        
        .status-cancelled {
            background-color: #e53935;
            color: white;
        }
        
        .status-completed {
            background-color: #5c6bc0;
            color: white;
        }
        
        /* ปรับขนาดปุ่มในตารางให้เล็กลง */
        .booking-table .btn {
            padding: 4px 16px;
            font-size: 12px;
            margin-right: 3px;
        }
        
        .btn-confirm {
            background-color: #4CAF50;
            color: white;
        }
        
        .btn-cancel {
            background-color: #f44336;
            color: white;
        }
        
        .btn-complete {
            background-color: #2196F3;
            color: white;
        }
        
        .btn-delete {
            background-color: #ff5722;
            color: white;
        }
        
        /* ปุ่มกลุ่มในตาราง */
        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 5px;
        }
        
        .pagination a {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 3px;
            text-decoration: none;
            color: #333;
            transition: background-color 0.3s;
        }
        
        .pagination a.active {
            background-color: #4CAF50;
            color: #fff;
            border-color: #4CAF50;
        }
        
        .pagination a:hover:not(.active) {
            background-color: #f1f1f1;
        }
        
        /* ปรับแต่ง Modal */
        .modal-content {
            max-width: 500px;
            width: 90%;
            margin: 10% auto;
        }
        
        .date-filter {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            align-items: center;
        }
        
        .date-filter label {
            margin-right: 5px;
        }

        /* โครงสร้างพื้นฐานของ Modal */
        .modal {
            display: none; /* ซ่อนก่อน */
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto; /* scroll ถ้าเนื้อหาเกิน */
            background-color: rgba(0,0,0,0.5); /* พื้นหลังมืด */
        }

        /* กล่องเนื้อหา */
        .modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            animation: fadeIn 0.3s ease;
        }

        /* ปุ่มปิด */
        .close-modal {
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        /* fade in */
        @keyframes fadeIn {
            from {opacity: 0;}
            to {opacity: 1;}
        }

        /* ปรับขนาดตารางให้เหมาะสมกับหน้าจอมือถือ */
        @media (max-width: 768px) {
            .booking-table {
                display: block;
                overflow-x: auto;
            }
            
            .modal-content {
                width: 95%;
                margin: 5% auto;
            }
            
            .dashboard-stats {
                grid-template-columns: 1fr;
            }
            
            .date-filter {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <h1><a href="../index.php">ระบบจองห้องซ้อมดนตรี</a></h1>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php">จัดการผู้ใช้</a></li>
                    <li><a href="rooms.php">จัดการห้อง</a></li>
                    <li><a href="bookings.php" class="active">จัดการการจอง</a></li>
                    <li>
                        <a href="<?php echo getBaseUrl(); ?>/auth/logout.php" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการออกจากระบบ?')">ออกจากระบบ</a>
                    </li>
                </ul>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <main>
            <div class="admin-header">
                <h2>จัดการการจองห้องซ้อมดนตรี</h2>
                <div class="search-box">
                    <input type="text" id="searchBooking" placeholder="ค้นหาการจอง..." onkeyup="searchTable()">
                    <button class="btn" onclick="searchTable()">ค้นหา</button>
                </div>
            </div>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- สถิติการจอง -->
            <div class="dashboard-stats">
                <div class="stat-card">
                    <h3>การจองทั้งหมด</h3>
                    <p><?php echo $booking_stats['total']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>รอการยืนยัน</h3>
                    <p><?php echo $booking_stats['pending']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>ยืนยันแล้ว</h3>
                    <p><?php echo $booking_stats['confirmed']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>การจองวันนี้</h3>
                    <p><?php echo $booking_stats['today']; ?></p>
                </div>
            </div>
            
            <!-- ตัวกรองการจอง -->
            <div class="booking-filter">
                <button class="filter-btn active" onclick="filterBookings('all')">ทั้งหมด</button>
                <button class="filter-btn" onclick="filterBookings('pending')">รอการยืนยัน</button>
                <button class="filter-btn" onclick="filterBookings('confirmed')">ยืนยันแล้ว</button>
                <button class="filter-btn" onclick="filterBookings('cancelled')">ยกเลิกแล้ว</button>
                <button class="filter-btn" onclick="filterBookings('completed')">เสร็จสิ้นแล้ว</button>
            </div>
            
            <!-- ตัวกรองตามวันที่ -->
            <div class="date-filter">
                <div>
                    <label for="start_date">วันที่เริ่มต้น:</label>
                    <input type="date" id="start_date" onchange="filterByDate()">
                </div>
                <div>
                    <label for="end_date">วันที่สิ้นสุด:</label>
                    <input type="date" id="end_date" onchange="filterByDate()">
                </div>
                <button class="btn" onclick="resetDateFilter()">รีเซ็ตวันที่</button>
            </div>
            
            <div class="admin-section">
                <table class="table booking-table" id="bookingTable">
                    <thead>
                        <tr>
                            <th>รหัสการจอง</th>
                            <th>ชื่อผู้จอง</th>
                            <th>ชื่อห้อง</th>
                            <th>วันที่จอง</th>
                            <th>เวลา</th>
                            <th>ราคารวม</th>
                            <th>สถานะ</th>
                            <th>วันที่สร้างการจอง</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr class="booking-row" 
                                data-status="<?php echo $booking['status']; ?>"
                                data-date="<?php echo $booking['booking_date']; ?>">
                                <td><?php echo $booking['booking_id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($booking['full_name']); ?>
                                    <br><small><?php echo htmlspecialchars($booking['email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($booking['room_name']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($booking['booking_date'])); ?></td>
                                <td><?php echo date('H:i', strtotime($booking['start_time'])) . ' - ' . date('H:i', strtotime($booking['end_time'])); ?></td>
                                <td><?php echo number_format($booking['total_price'], 2); ?> บาท</td>
                                <td>
                                    <span class="status-badge status-<?php echo $booking['status']; ?>">
                                        <?php 
                                        switch($booking['status']) {
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
                                                echo $booking['status'];
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($booking['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($booking['status'] === 'pending'): ?>
                                            <button class="btn btn-confirm" onclick="openConfirmModal(<?php echo $booking['booking_id']; ?>, '<?php echo htmlspecialchars($booking['full_name']); ?>')">ยืนยัน</button>
                                            <button class="btn btn-cancel" onclick="openCancelModal(<?php echo $booking['booking_id']; ?>, '<?php echo htmlspecialchars($booking['full_name']); ?>')">ยกเลิก</button>
                                        <?php elseif ($booking['status'] === 'confirmed'): ?>
                                            <button class="btn btn-complete" onclick="openCompleteModal(<?php echo $booking['booking_id']; ?>, '<?php echo htmlspecialchars($booking['full_name']); ?>')">เสร็จสิ้น</button>
                                            <button class="btn btn-cancel" onclick="openCancelModal(<?php echo $booking['booking_id']; ?>, '<?php echo htmlspecialchars($booking['full_name']); ?>')">ยกเลิก</button>
                                        <?php endif; ?>
                                        <button class="btn btn-delete" onclick="openDeleteModal(<?php echo $booking['booking_id']; ?>, '<?php echo htmlspecialchars($booking['full_name']); ?>')">ลบ</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- การแบ่งหน้า -->
                <div class="pagination">
                    <a href="#" class="active">1</a>
                    <a href="#">2</a>
                    <a href="#">3</a>
                    <a href="#">&raquo;</a>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Modal สำหรับยืนยันการจอง -->
    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('confirmModal')">&times;</span>
            <h3>ยืนยันการจอง</h3>
            <p>คุณต้องการยืนยันการจองของ <strong id="confirm_name"></strong> หรือไม่?</p>
            
            <form method="POST" action="">
                <input type="hidden" id="confirm_booking_id" name="booking_id" value="">
                
                <div class="form-group" style="display: flex; justify-content: left; gap: 20px; margin-top: 20px;">
                    <button type="button" class="btn btn-cancel" onclick="closeModal('confirmModal')">ยกเลิก</button>
                    <button type="submit" name="confirm_booking" class="btn btn-confirm" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการยืนยัน?')">ยืนยันการจอง</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal สำหรับยกเลิกการจอง -->
    <div id="cancelModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('cancelModal')">&times;</span>
            <h3>ยกเลิกการจอง</h3>
            <p>คุณต้องการยกเลิกการจองของ <strong id="cancel_name"></strong> หรือไม่?</p>
            
            <form method="POST" action="">
                <input type="hidden" id="cancel_booking_id" name="booking_id" value="">
                
                <div class="form-group" style="display: flex; justify-content: left; gap: 20px; margin-top: 20px;">
                    <button type="button" class="btn btn-cancel" onclick="closeModal('cancelModal')">ปิด</button>
                    <button type="submit" name="cancel_booking" class="btn">ยกเลิกการจอง</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal สำหรับทำเครื่องหมายว่าการจองเสร็จสิ้น -->
    <div id="completeModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('completeModal')">&times;</span>
            <h3>ทำเครื่องหมายการจองเป็นเสร็จสิ้น</h3>
            <p>คุณต้องการทำเครื่องหมายว่าการจองของ <strong id="complete_name"></strong> เสร็จสิ้นแล้วหรือไม่?</p>
            
            <form method="POST" action="">
                <input type="hidden" id="complete_booking_id" name="booking_id" value="">
                
                <div class="form-group" style="display: flex; justify-content: center; gap: 150px; margin-top: 20px;">
                    <button type="button" class="btn" onclick="closeModal('completeModal')">ปิด</button>
                    <button type="submit" name="complete_booking" class="btn btn-complete">ยืนยันการเสร็จสิ้น</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal สำหรับลบการจอง -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('deleteModal')">&times;</span>
            <h3>ยืนยันการลบการจอง</h3>
            <p>คุณต้องการลบข้อมูลการจองของ <strong id="delete_name"></strong> หรือไม่?</p>
            <p>การดำเนินการนี้ไม่สามารถยกเลิกได้</p>
            
            <form method="POST" action="">
                <input type="hidden" id="delete_booking_id" name="booking_id" value="">
                
                <div class="form-group" style="display: flex; justify-content: left; gap: 20px; margin-top: 20px;">
                    <button type="button" class="btn" onclick="closeModal('deleteModal')">ยกเลิก</button>
                    <button type="submit" name="delete_booking" class="btn btn-delete">ยืนยันการลบ</button>
                </div>
            </form>
        </div>   
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> ระบบจองห้องซ้อมดนตรี</p>
        </div>
    </footer>
    
    <script>
        // ฟังก์ชันสำหรับเปิด Modal ยืนยันการจอง
        function openConfirmModal(bookingId, name) {
            document.getElementById('confirm_booking_id').value = bookingId;
            document.getElementById('confirm_name').textContent = name;
            document.getElementById('confirmModal').style.display = 'block';
        }
        
        // ฟังก์ชันสำหรับเปิด Modal ยกเลิกการจอง
        function openCancelModal(bookingId, name) {
            document.getElementById('cancel_booking_id').value = bookingId;
            document.getElementById('cancel_name').textContent = name;
            document.getElementById('cancelModal').style.display = 'block';
        }
        
        // ฟังก์ชันสำหรับเปิด Modal ทำเครื่องหมายว่าการจองเสร็จสิ้น
        function openCompleteModal(bookingId, name) {
            document.getElementById('complete_booking_id').value = bookingId;
            document.getElementById('complete_name').textContent = name;
            document.getElementById('completeModal').style.display = 'block';
        }
        
        // ฟังก์ชันสำหรับเปิด Modal ลบการจอง
        function openDeleteModal(bookingId, name) {
            document.getElementById('delete_booking_id').value = bookingId;
            document.getElementById('delete_name').textContent = name;
            document.getElementById('deleteModal').style.display = 'block';
        }
        
        // ฟังก์ชันสำหรับปิด Modal
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // ปิด Modal เมื่อคลิกนอกพื้นที่ Modal
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }
        
        // ฟังก์ชันสำหรับค้นหาในตาราง
        function searchTable() {
            const input = document.getElementById('searchBooking');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('bookingTable');
            const tr = table.getElementsByTagName('tr');
            
            for (let i = 0; i < tr.length; i++) {
                if (tr[i].getElementsByTagName('td').length > 0) {
                    let found = false;
                    const td = tr[i].getElementsByTagName('td');
                    
                    for (let j = 0; j < td.length; j++) {
                        if (td[j]) {
                            const txtValue = td[j].textContent || td[j].innerText;
                            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                                found = true;
                                break;
                            }
                        }
                    }
                    
                    if (found) {
                        tr[i].style.display = '';
                    } else {
                        tr[i].style.display = 'none';
                    }
                }
            }
        }
        
        // ฟังก์ชันสำหรับกรองการจองตามสถานะ
        function filterBookings(status) {
            const rows = document.querySelectorAll('.booking-row');
            const buttons = document.querySelectorAll('.filter-btn');
            
            // อัพเดทสถานะของปุ่มกรอง
            buttons.forEach(button => {
                button.classList.remove('active');
            });
            
            event.target.classList.add('active');
            
            // กรองตารางตามสถานะ
            rows.forEach(row => {
                if (status === 'all' || row.getAttribute('data-status') === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
        
        // ฟังก์ชันสำหรับกรองตามวันที่
        function filterByDate() {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            const rows = document.querySelectorAll('.booking-row');
            
            rows.forEach(row => {
                const bookingDate = row.getAttribute('data-date');
                
                if ((!startDate || bookingDate >= startDate) && (!endDate || bookingDate <= endDate)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
        
        // ฟังก์ชันสำหรับรีเซ็ตตัวกรองวันที่
        function resetDateFilter() {
            document.getElementById('start_date').value = '';
            document.getElementById('end_date').value = '';
            
            const rows = document.querySelectorAll('.booking-row');
            rows.forEach(row => {
                row.style.display = '';
            });
        }
        
        // ฟังก์ชันช่วยจัดรูปแบบวันที่และเวลา
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('th-TH', { day: '2-digit', month: '2-digit', year: 'numeric' });
        }
        
        function formatTime(timeString) {
            const time = new Date(`2000-01-01T${timeString}`);
            return time.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' });
        }
        
        function formatDateTime(dateTimeString) {
            const dateTime = new Date(dateTimeString);
            return dateTime.toLocaleString('th-TH', { 
                day: '2-digit', 
                month: '2-digit', 
                year: 'numeric',
                hour: '2-digit', 
                minute: '2-digit' 
            });
        }

        // ตั้งค่าเริ่มต้นสำหรับ pagination
        document.addEventListener('DOMContentLoaded', function() {
            // ในกรณีที่มีการกดที่ pagination links
            const paginationLinks = document.querySelectorAll('.pagination a');
            paginationLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    paginationLinks.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                    // ในกรณีนี้เรายังไม่ได้ทำการ implement pagination จริง
                    // จึงเป็นเพียงการเปลี่ยนสถานะปุ่มเท่านั้น
                });
            });
        });
    </script>
</body>
</html>