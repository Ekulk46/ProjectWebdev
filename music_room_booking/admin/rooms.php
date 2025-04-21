<?php
// ไฟล์ admin/rooms.php
// หน้าจัดการห้องซ้อมดนตรี สำหรับ Admin

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

// ตัวแปรสำหรับข้อความแจ้งเตือน
$message = '';
$error = '';

// จัดการการเพิ่มห้องใหม่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_room'])) {
    $room_name = sanitize($_POST['room_name']);
    $description = sanitize($_POST['description']);
    $hourly_rate = floatval($_POST['hourly_rate']);
    $capacity = intval($_POST['capacity']);
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    
    // ตรวจสอบการอัปโหลดรูปภาพ
    $image_url = '';
    if (isset($_FILES['room_image']) && $_FILES['room_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../assets/images/';
         
        // สร้างชื่อไฟล์ใหม่เพื่อป้องกันการซ้ำกัน
        $file_extension = pathinfo($_FILES['room_image']['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid('room_') . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;
        
        // ตรวจสอบว่าเป็นรูปภาพจริงหรือไม่
        $check = getimagesize($_FILES['room_image']['tmp_name']);
        if ($check !== false) {
            // อัปโหลดไฟล์
            if (move_uploaded_file($_FILES['room_image']['tmp_name'], $upload_path)) {
                $image_url = 'assets/images/' . $new_filename;
            } else {
                $error = "เกิดข้อผิดพลาดในการอัปโหลดไฟล์";
            }
        } else {
            $error = "ไฟล์ที่อัปโหลดไม่ใช่รูปภาพ";
        }
    }
    
    // ถ้าไม่มีข้อผิดพลาด ให้บันทึกข้อมูลลงฐานข้อมูล
    if (empty($error)) {
        $insert_sql = "INSERT INTO rooms (room_name, description, hourly_rate, capacity, image_url, is_available) 
                      VALUES (?, ?, ?, ?, ?, ?)";
        $insert_stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($insert_stmt, "ssdisd", $room_name, $description, $hourly_rate, $capacity, $image_url, $is_available);
        
        if (mysqli_stmt_execute($insert_stmt)) {
            $message = "เพิ่มห้องใหม่เรียบร้อยแล้ว";
        } else {
            $error = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . mysqli_error($conn);
        }
    }
}

// จัดการการอัปเดตข้อมูลห้อง
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_room'])) {
  $room_id = intval($_POST['room_id']);
  $room_name = sanitize($_POST['room_name']);
  $description = sanitize($_POST['description']);
  $hourly_rate = floatval($_POST['hourly_rate']);
  $capacity = intval($_POST['capacity']);
  $is_available = isset($_POST['is_available']) ? 1 : 0;
  
  // อัพเดทข้อมูลโดยไม่รวม image_url
  $update_sql = "UPDATE rooms SET room_name = ?, description = ?, hourly_rate = ?, 
                capacity = ?, is_available = ? WHERE room_id = ?";
  $update_stmt = mysqli_prepare($conn, $update_sql);
  mysqli_stmt_bind_param($update_stmt, "ssdiii", $room_name, $description, $hourly_rate, 
                        $capacity, $is_available, $room_id);
  
  if (mysqli_stmt_execute($update_stmt)) {
      $message = "อัปเดตข้อมูลห้องเรียบร้อยแล้ว";
  } else {
      $error = "เกิดข้อผิดพลาดในการอัปเดตข้อมูล: " . mysqli_error($conn);
  }
}

// จัดการการลบห้อง
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_room'])) {
    $room_id = intval($_POST['room_id']);
    
    // ตรวจสอบว่ามีการจองห้องนี้หรือไม่
    $check_bookings_sql = "SELECT COUNT(*) as count FROM bookings WHERE room_id = ?";
    $check_bookings_stmt = mysqli_prepare($conn, $check_bookings_sql);
    mysqli_stmt_bind_param($check_bookings_stmt, "i", $room_id);
    mysqli_stmt_execute($check_bookings_stmt);
    $booking_result = mysqli_stmt_get_result($check_bookings_stmt);
    $booking_data = mysqli_fetch_assoc($booking_result);
    
    if ($booking_data['count'] > 0) {
        $error = "ไม่สามารถลบห้องนี้ได้เนื่องจากมีการจองที่เกี่ยวข้อง";
    } else {
        // ดึงข้อมูลรูปภาพของห้อง
        $get_image_sql = "SELECT image_url FROM rooms WHERE room_id = ?";
        $get_image_stmt = mysqli_prepare($conn, $get_image_sql);
        mysqli_stmt_bind_param($get_image_stmt, "i", $room_id);
        mysqli_stmt_execute($get_image_stmt);
        $image_result = mysqli_stmt_get_result($get_image_stmt);
        $image_data = mysqli_fetch_assoc($image_result);
        
        // ลบข้อมูลห้องจากฐานข้อมูล
        $delete_sql = "DELETE FROM rooms WHERE room_id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_sql);
        mysqli_stmt_bind_param($delete_stmt, "i", $room_id);
        
        if (mysqli_stmt_execute($delete_stmt)) {
            // ลบไฟล์รูปภาพถ้ามี
            if (!empty($image_data['image_url']) && file_exists('../' . $image_data['image_url'])) {
                unlink('../' . $image_data['image_url']);
            }
            $message = "ลบห้องเรียบร้อยแล้ว";
        } else {
            $error = "เกิดข้อผิดพลาดในการลบข้อมูล: " . mysqli_error($conn);
        }
    }
}

// ดึงข้อมูลห้องทั้งหมด
$sql = "SELECT * FROM rooms ORDER BY room_id ASC";
$result = mysqli_query($conn, $sql);
$rooms = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $rooms[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการห้องซ้อมดนตรี - ระบบจองห้องซ้อมดนตรี</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        /* เพิ่ม CSS เฉพาะสำหรับหน้านี้ */
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .add-room-btn {
            display: inline-block;
            padding: 10px 15px;
            background-color: #4CAF50;
            color: #fff;
            text-decoration: none;
            border-radius: 3px;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-weight: 500;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .add-room-btn:hover {
        background-color: #45a049;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        transform: translateY(-2px);
        }
        
        .room-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .room-card {
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s;
        }
        
        .room-card:hover {
            transform: translateY(-5px);
        }
        
        .room-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-bottom: 1px solid #eee;
        }
        
        .room-details {
            padding: 15px;
        }
        
        .room-name {
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .room-description {
            color: #666;
            margin-bottom: 10px;
            max-height: 60px;
            overflow: hidden;
        }
        
        .room-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .room-price {
            color: #4CAF50;
            font-weight: 500;
        }
        
        .room-capacity {
            color: #666;
        }
        
        .room-status {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 10px;
            display: inline-block;
        }
        
        .status-available {
            background-color: #4CAF50;
            color: white;
        }
        
        .status-unavailable {
            background-color: #f44336;
            color: white;
        }
        
        .room-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .btn-edit, .btn-delete {
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
            flex: 1;
        }
        
        .btn-edit {
            background-color: #2196F3;
            color: white;
        }
        
        .btn-delete {
            background-color: #f44336;
            color: white;
        }

        .btn-edit:hover {
        background-color:rgb(69, 105, 160);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        transform: translateY(-2px);
        }

        .btn-delete:hover {
        background-color:rgb(160, 69, 69);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        transform: translateY(-2px);
        }
        
        .modal-content {
            max-width: 700px;
            width: 70%;
            margin: 5% auto;
            padding: 20px;
        }
        
        .form-preview-image {
            max-width: 200px;
            max-height: 200px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 5px;
        }
        
        .room-stats {
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
        
        .search-box {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .search-box input {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 3px;
            flex: 1;
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

        /* ปรับแต่งสำหรับหน้าจอมือถือ */
        @media (max-width: 768px) {
            .room-cards {
                grid-template-columns: 1fr;
            }
            
            .room-stats {
                grid-template-columns: 1fr;
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
                    <li><a href="rooms.php" class="active">จัดการห้อง</a></li>
                    <li><a href="bookings.php">จัดการการจอง</a></li>
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
                <h2>จัดการห้องซ้อมดนตรี</h2>
                <button class="add-room-btn" onclick="openAddModal()">เพิ่มห้องใหม่</button>
            </div>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- สถิติห้อง -->
            <div class="room-stats">
                <?php
                $total_rooms = count($rooms);
                $available_rooms = 0;
                $total_capacity = 0;
                
                foreach ($rooms as $room) {
                    if ($room['is_available'] == 1) $available_rooms++;
                    $total_capacity += $room['capacity'];
                }
                ?>
                <div class="stat-card">
                    <h3>ห้องทั้งหมด</h3>
                    <p><?php echo $total_rooms; ?></p>
                </div>
                <div class="stat-card">
                    <h3>ห้องที่เปิดให้บริการ</h3>
                    <p><?php echo $available_rooms; ?></p>
                </div>
            </div>
            
            <!-- ค้นหาห้อง -->
            <div class="search-box">
                <input type="text" id="searchRoom" placeholder="ค้นหาห้อง..." onkeyup="searchRooms()">
                <button class="btn" onclick="searchRooms()">ค้นหา</button>
            </div>
            
            <!-- แสดงห้องในรูปแบบการ์ด -->
            <div class="room-cards" id="roomContainer">
                <?php foreach ($rooms as $room): ?>
                    <div class="room-card" data-name="<?php echo strtolower($room['room_name']); ?>">
                        <img src="<?php echo !empty($room['image_url']) ? '../' . $room['image_url'] : '../assets/images/room-placeholder.jpg'; ?>" 
                             class="room-image" alt="<?php echo htmlspecialchars($room['room_name']); ?>">
                        <div class="room-details">
                            <h3 class="room-name"><?php echo htmlspecialchars($room['room_name']); ?></h3>
                            <div class="room-description"><?php echo htmlspecialchars($room['description']); ?></div>
                            <div class="room-meta">
                                <span class="room-price"><?php echo number_format($room['hourly_rate'], 2); ?> บาท/ชั่วโมง</span>
                                <span class="room-capacity">รองรับ <?php echo $room['capacity']; ?> คน</span>
                            </div>
                            <span class="room-status <?php echo $room['is_available'] ? 'status-available' : 'status-unavailable'; ?>">
                                <?php echo $room['is_available'] ? 'เปิดให้บริการ' : 'ปิดให้บริการ'; ?>
                            </span>
                            <div class="room-actions">
                                <button class="btn-edit" onclick="openEditModal(<?php echo $room['room_id']; ?>)">แก้ไข</button>
                                <button class="btn-delete" onclick="openDeleteModal(<?php echo $room['room_id']; ?>, '<?php echo htmlspecialchars($room['room_name']); ?>')">ลบ</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($rooms)): ?>
                    <div class="alert alert-info" style="grid-column: 1 / -1;">ยังไม่มีข้อมูลห้อง กรุณาเพิ่มห้องใหม่</div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <!-- Modal สำหรับเพิ่มห้องใหม่ -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('addModal')">&times;</span>
            <h3>เพิ่มห้องใหม่</h3>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="room_name">ชื่อห้อง:</label>
                    <input type="text" id="room_name" name="room_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="description">รายละเอียด:</label>
                    <textarea id="description" name="description" class="form-control" rows="4"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="hourly_rate">ราคาต่อชั่วโมง (บาท):</label>
                    <input type="number" id="hourly_rate" name="hourly_rate" class="form-control" min="0" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label for="capacity">จำนวนคนที่รองรับ:</label>
                    <input type="number" id="capacity" name="capacity" class="form-control" min="1" required>
                </div>
                
                <div class="form-group">
                    <label for="room_image">รูปภาพห้อง:</label>
                    <input type="file" id="room_image" name="room_image" class="form-control" accept="image/*" onchange="previewImage(this, 'image_preview')">
                    <img id="image_preview" class="form-preview-image" style="display: none;">
                </div>
                
                <div class="form-group">
                    <label>สถานะ:</label>
                    <div class="checkbox">
                        <input type="checkbox" id="is_available" name="is_available" checked>
                        <label for="is_available">เปิดให้บริการ</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" name="add_room" class="btn">เพิ่มห้อง</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal สำหรับแก้ไขข้อมูลห้อง -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('editModal')">&times;</span>
            <h3>แก้ไขข้อมูลห้อง</h3>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" id="edit_room_id" name="room_id" value="">
                
                <div class="form-group">
                    <label for="edit_room_name">ชื่อห้อง:</label>
                    <input type="text" id="edit_room_name" name="room_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_description">รายละเอียด:</label>
                    <textarea id="edit_description" name="description" class="form-control" rows="4"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="edit_hourly_rate">ราคาต่อชั่วโมง (บาท):</label>
                    <input type="number" id="edit_hourly_rate" name="hourly_rate" class="form-control" min="0" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_capacity">จำนวนคนที่รองรับ:</label>
                    <input type="number" id="edit_capacity" name="capacity" class="form-control" min="1" required>
                </div>
                
                <div class="form-group">
                    <label>สถานะ:</label>
                    <div class="checkbox">
                        <input type="checkbox" id="edit_is_available" name="is_available">
                        <label for="edit_is_available">เปิดให้บริการ</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" name="update_room" class="btn">บันทึกการเปลี่ยนแปลง</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal สำหรับยืนยันการลบห้อง -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('deleteModal')">&times;</span>
            <h3>ยืนยันการลบห้อง</h3>
            <p>คุณต้องการลบห้อง <strong id="delete_room_name"></strong> หรือไม่?</p>
            <p>การดำเนินการนี้ไม่สามารถยกเลิกได้</p>
            
            <form method="POST" action="">
                <input type="hidden" id="delete_room_id" name="room_id" value="">
                
                <div class="form-group" style="display: flex; justify-content: space-between; margin-top: 20px;">
                    <button type="button" class="btn" onclick="closeModal('deleteModal')">ยกเลิก</button>
                    <button type="submit" name="delete_room" class="btn btn-delete">ยืนยันการลบ</button>
                </div>
            </form>
        </div>
    </div>
    
    <footer>
        <div class="container">
        <p>&copy; <?php echo date('Y'); ?> ระบบจองห้องซ้อมดนตรี</p>
        </div>
    </footer>
    
    <script>
        // สคริปต์สำหรับการจัดการ Modal
        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }
        
        function openEditModal(roomId) {
            // ดึงข้อมูลห้องจาก JavaScript Object
            const rooms = <?php echo json_encode($rooms); ?>;
            const room = rooms.find(r => r.room_id == roomId);
            
            // กรอกข้อมูลลงใน form
            document.getElementById('edit_room_id').value = room.room_id;
            document.getElementById('edit_room_name').value = room.room_name;
            document.getElementById('edit_description').value = room.description;
            document.getElementById('edit_hourly_rate').value = room.hourly_rate;
            document.getElementById('edit_capacity').value = room.capacity;
            document.getElementById('edit_is_available').checked = room.is_available == 1;
            
            // แสดง Modal
            document.getElementById('editModal').style.display = 'block';
        }
        
        function openDeleteModal(roomId, roomName) {
            document.getElementById('delete_room_id').value = roomId;
            document.getElementById('delete_room_name').textContent = roomName;
            document.getElementById('deleteModal').style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // สคริปต์สำหรับการแสดงตัวอย่างรูปภาพ
        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.src = '';
                preview.style.display = 'none';
            }
        }
        
        // สคริปต์สำหรับการค้นหาห้อง
        function searchRooms() {
            const input = document.getElementById('searchRoom');
            const filter = input.value.toLowerCase();
            const roomContainer = document.getElementById('roomContainer');
            const rooms = roomContainer.getElementsByClassName('room-card');
            
            let noResults = true;
            
            for (let i = 0; i < rooms.length; i++) {
                const roomName = rooms[i].getAttribute('data-name').toLowerCase();
                
                if (roomName.includes(filter)) {
                    rooms[i].style.display = '';
                    noResults = false;
                } else {
                    rooms[i].style.display = 'none';
                }
            }
            
            // แสดงข้อความเมื่อไม่พบผลลัพธ์
            let noResultsElement = document.getElementById('noResults');
            
            if (noResults) {
                if (!noResultsElement) {
                    noResultsElement = document.createElement('div');
                    noResultsElement.id = 'noResults';
                    noResultsElement.className = 'alert alert-info';
                    noResultsElement.style.gridColumn = '1 / -1';
                    noResultsElement.textContent = 'ไม่พบห้องที่ค้นหา';
                    roomContainer.appendChild(noResultsElement);
                }
            } else {
                if (noResultsElement) {
                    noResultsElement.remove();
                }
            }
        }
        
        // ปิด Modal เมื่อคลิกนอกพื้นที่ Modal
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>