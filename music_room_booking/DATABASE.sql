-- สร้างฐานข้อมูล
CREATE DATABASE music_room_booking;
USE music_room_booking;

-- สร้างตาราง users
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- สร้างตาราง rooms
CREATE TABLE rooms (
    room_id INT AUTO_INCREMENT PRIMARY KEY,
    room_name VARCHAR(50) NOT NULL,
    description TEXT,
    hourly_rate DECIMAL(10, 2) NOT NULL,
    capacity INT,
    image_url VARCHAR(255),
    is_available BOOLEAN DEFAULT TRUE
);

-- สร้างตาราง bookings
CREATE TABLE bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    room_id INT NOT NULL,
    booking_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (room_id) REFERENCES rooms(room_id)
);

-- เพิ่มข้อมูลห้องตัวอย่าง
INSERT INTO rooms (room_name, description, hourly_rate, capacity, image_url) VALUES
('ห้องซ้อม A', 'ห้องซ้อมขนาดเล็ก พร้อมเครื่องเสียงและกลองชุด', 200.00, 4, 'images/room_a.jpg'),
('ห้องซ้อม B', 'ห้องซ้อมขนาดกลาง พร้อมเครื่องเสียงและแอมป์กีตาร์', 300.00, 6, 'images/room_b.jpg'),
('ห้องซ้อม C', 'ห้องซ้อมขนาดใหญ่ พร้อมอุปกรณ์ครบชุด', 500.00, 10, 'images/room_c.jpg');


ALTER TABLE users
ADD COLUMN status ENUM('active', 'inactive', 'banned', 'pending') DEFAULT 'active';


ALTER TABLE users
ADD COLUMN role ENUM('user', 'admin', 'staff') DEFAULT 'user';