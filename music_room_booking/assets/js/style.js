// ไฟล์ assets/js/style.js
// JavaScript สำหรับเว็บไซต์

document.addEventListener('DOMContentLoaded', function() {
    // เอาไว้จัดการกับวันที่ในฟอร์ม
    const dateInputs = document.querySelectorAll('input[type="date"]');
    if (dateInputs) {
        const today = new Date().toISOString().split('T')[0];
        dateInputs.forEach(input => {
            input.setAttribute('min', today);
        });
    }
  
    // ตรวจสอบฟอร์มก่อนส่ง
    const bookingForm = document.getElementById('booking-form');
    if (bookingForm) {
        bookingForm.addEventListener('submit', function(e) {
            const startTime = document.getElementById('start_time').value;
            const endTime = document.getElementById('end_time').value;
            
            if (startTime >= endTime) {
                e.preventDefault();
                alert('เวลาเริ่มต้นควรน้อยกว่าเวลาสิ้นสุด');
            }
        });
    }
  
    // คำนวณราคาทันทีเมื่อเปลี่ยนเวลา
    const startTimeInput = document.getElementById('start_time');
    const endTimeInput = document.getElementById('end_time');
    const roomSelect = document.getElementById('room_id');
    const priceDisplay = document.getElementById('price-display');
    
    function updatePrice() {
        if (startTimeInput && endTimeInput && roomSelect && priceDisplay) {
            const startTime = startTimeInput.value;
            const endTime = endTimeInput.value;
            const roomId = roomSelect.value;
            
            if (startTime && endTime && roomId && startTime < endTime) {
                const hourlyRate = roomSelect.options[roomSelect.selectedIndex].getAttribute('data-rate');
                const start = new Date(`2000-01-01T${startTime}`);
                const end = new Date(`2000-01-01T${endTime}`);
                const diffHours = (end - start) / (1000 * 60 * 60);
                const total = hourlyRate * diffHours;
                
                priceDisplay.textContent = total.toFixed(2) + ' บาท';
                
                // เพิ่มคลาสเอฟเฟกต์การอัพเดทราคา
                priceDisplay.classList.add('price-updated');
                setTimeout(() => {
                  priceDisplay.classList.remove('price-updated');
                }, 500);
            }
        }
    }
    
    if (startTimeInput && endTimeInput && roomSelect) {
        startTimeInput.addEventListener('change', updatePrice);
        endTimeInput.addEventListener('change', updatePrice);
        roomSelect.addEventListener('change', updatePrice);
    }
  
    // เอฟเฟกต์สำหรับแถบเมนู
    const navLinks = document.querySelectorAll('nav a');
    navLinks.forEach(link => {
        if (link.href === window.location.href) {
            link.classList.add('active');
        }
    });
    
    // เอฟเฟกต์สำหรับรูปภาพ lazy loading
    const roomImages = document.querySelectorAll('.room-image img');
    if ('IntersectionObserver' in window) {
      const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const img = entry.target;
            const src = img.getAttribute('data-src');
            if (src) {
              img.src = src;
              img.removeAttribute('data-src');
            }
            imageObserver.unobserve(img);
          }
        });
      });
  
      roomImages.forEach(img => {
        // ถ้ามี data-src ให้ใช้ lazy loading
        if (img.getAttribute('data-src')) {
          imageObserver.observe(img);
        }
      });
    } else {
      // Fallback สำหรับเบราว์เซอร์เก่าที่ไม่รองรับ IntersectionObserver
      roomImages.forEach(img => {
        const src = img.getAttribute('data-src');
        if (src) {
          img.src = src;
        }
      });
    }
    
    // Animation เมื่อ scroll ถึงองค์ประกอบ
    const animateElements = document.querySelectorAll('.room-item');
    
    if ('IntersectionObserver' in window) {
      const animationObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('animated');
          }
        });
      }, { threshold: 0.1 });
      
      animateElements.forEach(el => {
        animationObserver.observe(el);
      });
    } else {
      // Fallback สำหรับเบราว์เซอร์เก่า
      animateElements.forEach(el => {
        el.classList.add('animated');
      });
    }
    
    // เพิ่ม animation สำหรับห้องซ้อม
    document.querySelectorAll('.room-item').forEach(room => {
      room.classList.add('fade-in');
    });
  });
  
  // เพิ่ม CSS animation
  const style = document.createElement('style');
  style.textContent = `
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .fade-in {
      animation: fadeIn 0.5s ease-out forwards;
    }
    
    .room-item {
      opacity: 0;
    }
    
    .room-item.animated {
      opacity: 1;
    }
    
    @keyframes priceUpdate {
      0% { color: #e91e63; transform: scale(1); }
      50% { color: #4CAF50; transform: scale(1.1); }
      100% { color: #e91e63; transform: scale(1); }
    }
    
    .price-updated {
      animation: priceUpdate 0.5s ease-out;
    }
  `;
  document.head.appendChild(style);