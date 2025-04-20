<?php
// includes/footer.php
$current_year = date('Y');
?>

</main>
    </div>
    <footer>
        <div class="container">
            <p >&copy; <?php echo date('Y'); ?> ระบบจองห้องซ้อมดนตรี | พัฒนาโดย <a href="#">ทีมงาน PHP</a></p>
        </div>
    </footer>
    <!-- เรียกใช้ JavaScript -->
    <script src="<?php echo getBaseUrl(); ?>/assets/js/style.js"></script>
</body>
</html>

<footer class="site-footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-logo">
                <h2>ห้องซ้อมดนตรี</h2>
                <p  style="text-align: left;">บริการจองห้องซ้อมดนตรีออนไลน์</p>
            </div>
            
            <div class="footer-links">
                <h3  style="text-align: left;">ลิงก์</h3>
            </div>
            
            <div class="footer-contact">
                <h3  style="text-align: left;">ติดต่อเรา</h3>
                <p  style="text-align: left;">เบอร์โทร: 02-123-4567</p>
                <p  style="text-align: left;">อีเมล: contact@musicroom.com</p>
                <p  style="text-align: left;">ที่อยู่: 123 ถนนดนตรี แขวงเสียงเพราะ เขตจตุจักร กรุงเทพฯ 10900</p>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p style="text-align: left;">&copy; <?php echo $current_year; ?> ระบบจองห้องซ้อมดนตรี - ออกแบบและพัฒนาโดย [ชื่อผู้พัฒนา]</p>
        </div>
    </div>
</footer>