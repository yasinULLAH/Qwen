</div> <!-- End Main Content -->

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<!-- Custom JS -->
<script src="<?php echo APP_URL; ?>assets/js/main.js"></script>

<script>
// Global app configuration
const AppConfig = {
    appUrl: '<?php echo APP_URL; ?>',
    currentPage: '<?php echo $page; ?>',
    csrfToken: '<?php echo generate_csrf_token(); ?>',
    userId: <?php echo get_current_user()['id']; ?>,
    userRole: '<?php echo get_current_user()['role']; ?>'
};

// Sidebar toggle for mobile
$(document).ready(function() {
    $('#sidebarToggle').on('click', function() {
        $('#sidebar').toggleClass('active');
    });
    
    // Close sidebar when clicking outside on mobile
    $(document).on('click', function(e) {
        if ($(window).width() < 768) {
            if (!$(e.target).closest('#sidebar, #sidebarToggle').length) {
                $('#sidebar').removeClass('active');
            }
        }
    });
    
    // Auto-hide alerts after 5 seconds
    $('.alert-dismissible').fadeIn().delay(5000).fadeOut('slow', function() {
        $(this).alert('close');
    });
    
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
});

// Notification functions
function markNotificationRead(notificationId) {
    $.ajax({
        url: AppConfig.appUrl + '?page=notifications',
        type: 'POST',
        data: {
            action: 'mark_read',
            notification_id: notificationId,
            csrf_token: AppConfig.csrfToken
        },
        success: function(response) {
            location.reload();
        }
    });
}

function refreshNotifications() {
    $.ajax({
        url: AppConfig.appUrl + '?page=notifications',
        type: 'POST',
        data: {
            action: 'get_unread',
            csrf_token: AppConfig.csrfToken
        },
        success: function(response) {
            try {
                const data = JSON.parse(response);
                if (data.success) {
                    updateNotificationBadge(data.count);
                    updateNotificationList(data.notifications);
                }
            } catch(e) {
                console.error('Error parsing notifications:', e);
            }
        }
    });
}

function updateNotificationBadge(count) {
    let badge = $('.notification-count');
    if (count > 0) {
        if (badge.length === 0) {
            $('.notification-badge').append('<span class="notification-count">' + count + '</span>');
        } else {
            badge.text(count);
        }
    } else {
        badge.remove();
    }
}

function updateNotificationList(notifications) {
    let html = '';
    if (notifications.length === 0) {
        html = '<div class="dropdown-item text-muted small">No new notifications</div>';
    } else {
        notifications.forEach(function(notif) {
            html += '<a href="#" class="dropdown-item small" onclick="markNotificationRead(' + notif.id + ')">';
            html += '<i class="fas fa-info-circle text-primary"></i> ' + notif.message;
            html += '<br><small class="text-muted">' + timeAgo(notif.timestamp) + '</small></a>';
        });
    }
    $('#notificationsList').html(html);
}

function timeAgo(timestamp) {
    const now = Math.floor(Date.now() / 1000);
    const diff = now - timestamp;
    
    if (diff < 60) return 'Just now';
    if (diff < 3600) return Math.floor(diff / 60) + ' minutes ago';
    if (diff < 86400) return Math.floor(diff / 3600) + ' hours ago';
    return Math.floor(diff / 86400) + ' days ago';
}

// Refresh notifications every 30 seconds
setInterval(refreshNotifications, 30000);

// Format currency helper
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: 'PKR'
    }).format(amount);
}

// Show success message
function showSuccess(message) {
    Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: message,
        timer: 2000,
        showConfirmButton: false
    });
}

// Show error message
function showError(message) {
    Swal.fire({
        icon: 'error',
        title: 'Error!',
        text: message
    });
}

// Show confirmation dialog
function showConfirm(message, callback) {
    Swal.fire({
        title: 'Are you sure?',
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, proceed!'
    }).then((result) => {
        if (result.isConfirmed && callback) {
            callback();
        }
    });
}

// Print receipt helper
function printReceipt(orderId, type = 'thermal') {
    window.open(AppConfig.appUrl + '?page=print_receipt&order_id=' + orderId + '&type=' + type, '_blank');
}

</script>

</body>
</html>
