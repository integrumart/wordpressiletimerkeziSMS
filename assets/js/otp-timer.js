/**
 * OTP Timer Script
 * 
 * Handles the countdown timer for OTP expiration on login form
 */
(function() {
    'use strict';
    
    // Initialize timer when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        var timer = document.querySelector('.imsms-otp-timer');
        if (!timer) {
            return;
        }
        
        var remaining = parseInt(timer.getAttribute('data-expiry'), 10);
        
        function formatTime(seconds) {
            var mins = Math.floor(seconds / 60);
            var secs = seconds % 60;
            return mins + ':' + (secs < 10 ? '0' : '') + secs;
        }
        
        function updateTimer() {
            remaining--;
            if (remaining <= 0) {
                timer.textContent = '0:00';
                timer.style.color = '#d63638';
                return;
            }
            timer.textContent = formatTime(remaining);
            setTimeout(updateTimer, 1000);
        }
        
        if (remaining > 0) {
            setTimeout(updateTimer, 1000);
        }
    });
})();
