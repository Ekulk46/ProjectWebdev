<?php
// ไฟล์ admin/index.php
// หน้าจัดการผู้ใช้และกำหนด Role สำหรับ Admin

// นำเข้าไฟล์ที่จำเป็น
require_once '../config/database.php';
require_once '../includes/functions.php';

// เริ่ม session
session_start();

// ตรวจสอบว่าล็อกอินและเป็น admin หรือไม่
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

// รับ action จาก URL
$action = isset($_GET['action']) ? $_GET['action'] : '';
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$message = '';
$error = '';

// เพิ่มส่วนการประมวลผลสำหรับการลบผู้ใช้
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $delete_user_id = intval($_POST['user_id']);
    
    // ตรวจสอบว่าไม่ได้พยายามลบตัวเอง
    if ($delete_user_id == $_SESSION['user_id']) {
        $error = "ไม่สามารถลบบัญชีของตัวเองได้";
    } else {
        // ลบข้อมูลผู้ใช้
        $delete_sql = "DELETE FROM users WHERE user_id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_sql);
        mysqli_stmt_bind_param($delete_stmt, "i", $delete_user_id);
        
        if (mysqli_stmt_execute($delete_stmt)) {
            $message = "ลบผู้ใช้เรียบร้อยแล้ว";
        } else {
            $error = "เกิดข้อผิดพลาดในการลบข้อมูล: " . mysqli_error($conn);
        }
    }
}

// จัดการการอัปเดต Role และ Status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $update_user_id = intval($_POST['user_id']);
    $new_role = sanitize($_POST['role']);
    $new_status = sanitize($_POST['status']);
    
    // ตรวจสอบว่าไม่ได้พยายามเปลี่ยนแปลง admin คนอื่น (ถ้าต้องการป้องกัน)
    $check_sql = "SELECT role FROM users WHERE user_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "i", $update_user_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    $user_data = mysqli_fetch_assoc($check_result);
    
    // อัปเดตข้อมูลผู้ใช้
    $update_sql = "UPDATE users SET role = ?, status = ? WHERE user_id = ?";
    $update_stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($update_stmt, "ssi", $new_role, $new_status, $update_user_id);
    
    if (mysqli_stmt_execute($update_stmt)) {
        $message = "อัปเดตข้อมูลผู้ใช้เรียบร้อยแล้ว";
    } else {
        $error = "เกิดข้อผิดพลาดในการอัปเดตข้อมูล: " . mysqli_error($conn);
    }
}

// ดึงข้อมูลผู้ใช้ทั้งหมด
$sql = "SELECT user_id, username, email, full_name, role, status, created_at FROM users ORDER BY user_id ASC";
$result = mysqli_query($conn, $sql);
$users = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้ - ระบบจองห้องซ้อมดนตรี</title>
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
        
        .user-filter {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
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
        .user-table {
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
            height: 200px; /* คงความสูงของตาราง */
            overflow-y: auto; /* คง scroll แนวตั้ง */
            font-size: 13px; /* ลดขนาดฟอนต์ลงอีกเล็กน้อย */
            border-collapse: collapse; /* ให้ขอบตารางชิดกัน */
            width: 100%;
        }
        
        .user-table th, .user-table td {
            padding: 4px 10px; /* ลด padding ลง */
            line-height: 1.1; /* ลดระยะห่างระหว่างบรรทัด */
            vertical-align: middle; /* จัดให้ข้อความอยู่ตรงกลางในแนวตั้ง */
        }
        
        .user-table tbody tr {
            border-bottom: 1px solid #f0f0f0; /* เพิ่มเส้นคั่นระหว่างแถวบางๆ */
        }
        
        .user-table tbody tr:hover {
            background-color: #f9f9f9; /* สีพื้นหลังเมื่อ hover */
        }
        
        .user-table thead {
            position: sticky;
            top: 0;
            background-color: #f2f2f2;
            z-index: 10;
            border-bottom: 2px solid #ddd; /* เพิ่มเส้นใต้ส่วนหัวตาราง */
        }
        
        .user-table th {
            font-weight: 500;
            text-align: left;
        }
        
        /* ปรับแต่ง badge ให้เล็กลง */
        .role-badge, .status-badge {
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 16px;
            font-weight: 500;
            display: inline-block;
        }
        
        /* เพิ่มสีสำหรับ badge */
        .role-admin {
            background-color: #ff7043;
            color: white;
        }
        
        .role-staff {
            background-color: #5c6bc0;
            color: white;
        }
        
        .role-user {
            background-color: #66bb6a;
            color: white;
        }
        
        .status-active {
            background-color: #43a047;
            color: white;
        }
        
        .status-inactive {
            background-color: #757575;
            color: white;
        }
        
        .status-banned {
            background-color: #e53935;
            color: white;
        }
        
        .status-pending {
            background-color: #ffa726;
            color: white;
        }
        
        /* ปรับขนาดปุ่มในตารางให้เล็กลง */
        .user-table .btn {
            padding: 2px 8px;
            font-size: 16px;
            margin-right: 3px;
        }
        
        .btn-edit {
            background-color: #2196F3;
            color: white;
        }
        
        .btn-delete {
            background-color: #f44336;
            color: white;
        }
        
        /* ปุ่มกลุ่มในตาราง */
        .action-buttons {
            display: flex;
            gap: 5px;
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
        
        /* ปรับขนาดตารางให้เหมาะสมกับหน้าจอมือถือ */
        @media (max-width: 768px) {
            .user-table {
                display: block;
                overflow-x: auto;
                height: 500px; /* ปรับความสูงเมื่ออยู่บนมือถือ */
            }
            
            .modal-content {
                width: 95%;
                margin: 5% auto;
            }
            
            .dashboard-stats {
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
                    <li><a href="index.php" class="active">จัดการผู้ใช้</a></li>
                    <li><a href="rooms.php">จัดการห้อง</a></li>
                    <li><a href="bookings.php">จัดการการจอง</a></li>
                    <li><a href="../auth/logout.php">ออกจากระบบ</a></li>
                </ul>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <main>
            <div class="admin-header">
                <h2>จัดการผู้ใช้งานระบบ</h2>
                <div class="search-box">
                    <input type="text" id="searchUser" placeholder="ค้นหาผู้ใช้..." onkeyup="searchTable()">
                    <button class="btn" onclick="searchTable()">ค้นหา</button>
                </div>
            </div>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- สถิติผู้ใช้งาน -->
            <div class="dashboard-stats">
                <div class="stat-card">
                    <h3>ผู้ใช้ทั้งหมด</h3>
                    <p><?php echo count($users); ?></p>
                </div>
                <?php
                $active_users = 0;
                $admin_users = 0;
                $staff_users = 0;
                
                foreach ($users as $user) {
                    if ($user['status'] == 'active') $active_users++;
                    if ($user['role'] == 'admin') $admin_users++;
                    if ($user['role'] == 'staff') $staff_users++;
                }
                ?>
                <div class="stat-card">
                    <h3>ผู้ใช้ที่เปิดใช้งาน</h3>
                    <p><?php echo $active_users; ?></p>
                </div>
                <div class="stat-card">
                    <h3>ผู้ดูแลระบบ</h3>
                    <p><?php echo $admin_users; ?></p>
                </div>
                <div class="stat-card">
                    <h3>เจ้าหน้าที่</h3>
                    <p><?php echo $staff_users; ?></p>
                </div>
            </div>
            
            <!-- ตัวกรองผู้ใช้ -->
            <div class="user-filter">
                <button class="filter-btn active" onclick="filterUsers('all')">ทั้งหมด</button>
                <button class="filter-btn" onclick="filterUsers('admin')">ผู้ดูแลระบบ</button>
                <button class="filter-btn" onclick="filterUsers('staff')">เจ้าหน้าที่</button>
                <button class="filter-btn" onclick="filterUsers('user')">ผู้ใช้ทั่วไป</button>
                <button class="filter-btn" onclick="filterUsers('active')">เปิดใช้งาน</button>
                <button class="filter-btn" onclick="filterUsers('inactive')">ปิดใช้งาน</button>
            </div>
            
            <div class="admin-section">
                <table class="table user-table" id="userTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>ชื่อผู้ใช้</th>
                            <th>อีเมล</th>
                            <th>ชื่อ-นามสกุล</th>
                            <th>บทบาท</th>
                            <th>สถานะ</th>
                            <th>วันที่สร้าง</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr class="user-row" 
                                data-role="<?php echo $user['role']; ?>" 
                                data-status="<?php echo $user['status']; ?>">
                                <td><?php echo $user['user_id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td>
                                    <span class="role-badge role-<?php echo $user['role']; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $user['status']; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-edit" onclick="openEditModal(<?php echo $user['user_id']; ?>, '<?php echo $user['role']; ?>', '<?php echo $user['status']; ?>')">แก้ไข</button>
                                        <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                            <button class="btn btn-delete" onclick="openDeleteModal(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">ลบ</button>
                                        <?php endif; ?>
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
    
    <!-- Modal สำหรับแก้ไขข้อมูลผู้ใช้ -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('editModal')">&times;</span>
            <h3>แก้ไขข้อมูลผู้ใช้</h3>
            <form method="POST" action="">
                <input type="hidden" id="edit_user_id" name="user_id" value="">
                
                <div class="form-group">
                    <label for="role">บทบาท:</label>
                    <select id="role" name="role" class="form-control">
                        <option value="user">User</option>
                        <option value="staff">Staff</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="status">สถานะ:</label>
                    <select id="status" name="status" class="form-control">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="banned">Banned</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <button type="submit" name="update_user" class="btn">บันทึกการเปลี่ยนแปลง</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal สำหรับยืนยันการลบผู้ใช้ -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('deleteModal')">&times;</span>
            <h3>ยืนยันการลบผู้ใช้</h3>
            <p>คุณต้องการลบผู้ใช้ <strong id="delete_username"></strong> หรือไม่?</p>
            <p>การดำเนินการนี้ไม่สามารถยกเลิกได้</p>
            
            <form method="POST" action="">
                <input type="hidden" id="delete_user_id" name="user_id" value="">
                
                <div class="form-group" style="display: flex; justify-content: space-between; margin-top: 20px;">
                    <button type="button" class="btn" onclick="closeModal('deleteModal')">ยกเลิก</button>
                    <button type="submit" name="delete_user" class="btn btn-delete">ยืนยันการลบ</button>
                </div>
            </form>
        </div>
    </div>
    
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> ระบบจองห้องซ้อมดนตรี. สงวนลิขสิทธิ์.</p>
        </div>
    </footer>
    
    <script>
        // JavaScript สำหรับ Modal
        var editModal = document.getElementById("editModal");
        var deleteModal = document.getElementById("deleteModal");
        
        function openEditModal(userId, role, status) {
            document.getElementById("edit_user_id").value = userId;
            document.getElementById("role").value = role;
            document.getElementById("status").value = status;
            editModal.style.display = "block";
        }
        
        function openDeleteModal(userId, username) {
            document.getElementById("delete_user_id").value = userId;
            document.getElementById("delete_username").textContent = username;
            deleteModal.style.display = "block";
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = "none";
        }
        
        // ปิด Modal เมื่อคลิกนอกกรอบ
        window.onclick = function(event) {
            if (event.target == editModal) {
                closeModal('editModal');
            }
            if (event.target == deleteModal) {
                closeModal('deleteModal');
            }
        }
        
        // ฟังก์ชันสำหรับกรองข้อมูลผู้ใช้
        function filterUsers(filter) {
            const rows = document.querySelectorAll('.user-row');
            const filterButtons = document.querySelectorAll('.filter-btn');
            
            // ลบคลาส active จากปุ่มทั้งหมด
            filterButtons.forEach(btn => {
                btn.classList.remove('active');
            });
            
            // เพิ่มคลาส active ให้กับปุ่มที่ถูกกด
            event.target.classList.add('active');
            
            rows.forEach(row => {
                if (filter === 'all') {
                    row.style.display = '';
                } else if (filter === 'active' || filter === 'inactive') {
                    row.style.display = row.getAttribute('data-status') === filter ? '' : 'none';
                } else {
                    row.style.display = row.getAttribute('data-role') === filter ? '' : 'none';
                }
            });
        }
        
        // ฟังก์ชันสำหรับค้นหาข้อมูลในตาราง
        function searchTable() {
            const input = document.getElementById('searchUser');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('userTable');
            const rows = table.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                let found = false;
                const cells = rows[i].getElementsByTagName('td');
                
                for (let j = 0; j < cells.length; j++) {
                    const cell = cells[j];
                    if (cell) {
                        const textValue = cell.textContent || cell.innerText;
                        if (textValue.toUpperCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                }
                
                rows[i].style.display = found ? '' : 'none';
            }
        }
    </script>
</body>
</html>