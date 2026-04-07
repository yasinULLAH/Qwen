<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <h4><i class="fas fa-utensils"></i> FeastFlow Pro</h4>
        <small>Restaurant Management System</small>
    </div>
    
    <ul class="sidebar-menu">
        <?php if (can_access_role(['Admin', 'Accountant', 'Counter', 'Server'])): ?>
        <li>
            <a href="<?php echo APP_URL; ?>?page=dashboard" class="<?php echo $page === 'dashboard' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>
        <?php endif; ?>
        
        <?php if (can_access_role(['Admin', 'Counter', 'Server'])): ?>
        <li class="sidebar-header">Sales & Orders</li>
        <li>
            <a href="<?php echo APP_URL; ?>?page=pos" class="<?php echo $page === 'pos' ? 'active' : ''; ?>">
                <i class="fas fa-cash-register"></i> Point of Sale
            </a>
        </li>
        <li>
            <a href="<?php echo APP_URL; ?>?page=orders" class="<?php echo $page === 'orders' ? 'active' : ''; ?>">
                <i class="fas fa-receipt"></i> All Orders
            </a>
        </li>
        <li>
            <a href="<?php echo APP_URL; ?>?page=seating" class="<?php echo $page === 'seating' ? 'active' : ''; ?>">
                <i class="fas fa-chair"></i> Table Management
            </a>
        </li>
        <?php endif; ?>
        
        <?php if (can_access_role(['Admin', 'Kitchen'])): ?>
        <li class="sidebar-header">Kitchen</li>
        <li>
            <a href="<?php echo APP_URL; ?>?page=kitchen" class="<?php echo $page === 'kitchen' ? 'active' : ''; ?>">
                <i class="fas fa-fire"></i> Kitchen Display
            </a>
        </li>
        <?php endif; ?>
        
        <?php if (can_access_role(['Admin', 'Accountant'])): ?>
        <li class="sidebar-header">Customers & Finance</li>
        <li>
            <a href="<?php echo APP_URL; ?>?page=khata" class="<?php echo $page === 'khata' ? 'active' : ''; ?>">
                <i class="fas fa-book"></i> Customer Khata
            </a>
        </li>
        <li>
            <a href="<?php echo APP_URL; ?>?page=customers" class="<?php echo $page === 'customers' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Customers
            </a>
        </li>
        <li>
            <a href="<?php echo APP_URL; ?>?page=finance" class="<?php echo $page === 'finance' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i> Finance & Expenses
            </a>
        </li>
        <?php endif; ?>
        
        <?php if (can_access_role(['Admin', 'Accountant'])): ?>
        <li class="sidebar-header">Inventory</li>
        <li>
            <a href="<?php echo APP_URL; ?>?page=inventory" class="<?php echo $page === 'inventory' ? 'active' : ''; ?>">
                <i class="fas fa-boxes"></i> Ingredients Stock
            </a>
        </li>
        <li>
            <a href="<?php echo APP_URL; ?>?page=products" class="<?php echo $page === 'products' ? 'active' : ''; ?>">
                <i class="fas fa-hamburger"></i> Products & Menu
            </a>
        </li>
        <li>
            <a href="<?php echo APP_URL; ?>?page=wastage" class="<?php echo $page === 'wastage' ? 'active' : ''; ?>">
                <i class="fas fa-trash"></i> Wastage Log
            </a>
        </li>
        <?php endif; ?>
        
        <?php if (can_access_role(['Admin'])): ?>
        <li class="sidebar-header">HR & Staff</li>
        <li>
            <a href="<?php echo APP_URL; ?>?page=employees" class="<?php echo $page === 'employees' ? 'active' : ''; ?>">
                <i class="fas fa-user-tie"></i> Employees
            </a>
        </li>
        <li>
            <a href="<?php echo APP_URL; ?>?page=payroll" class="<?php echo $page === 'payroll' ? 'active' : ''; ?>">
                <i class="fas fa-money-check-alt"></i> Payroll
            </a>
        </li>
        <li>
            <a href="<?php echo APP_URL; ?>?page=timeclock" class="<?php echo $page === 'timeclock' ? 'active' : ''; ?>">
                <i class="fas fa-clock"></i> Time Clock
            </a>
        </li>
        <?php endif; ?>
        
        <?php if (can_access_role(['Admin', 'Server'])): ?>
        <li class="sidebar-header">Reservations</li>
        <li>
            <a href="<?php echo APP_URL; ?>?page=reservations" class="<?php echo $page === 'reservations' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check"></i> Bookings
            </a>
        </li>
        <li>
            <a href="<?php echo APP_URL; ?>?page=waitlist" class="<?php echo $page === 'waitlist' ? 'active' : ''; ?>">
                <i class="fas fa-list"></i> Wait List
            </a>
        </li>
        <?php endif; ?>
        
        <?php if (can_access_role(['Admin'])): ?>
        <li class="sidebar-header">Reports & Analytics</li>
        <li>
            <a href="<?php echo APP_URL; ?>?page=reports" class="<?php echo $page === 'reports' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i> Sales Reports
            </a>
        </li>
        <li>
            <a href="<?php echo APP_URL; ?>?page=audit" class="<?php echo $page === 'audit' ? 'active' : ''; ?>">
                <i class="fas fa-shield-alt"></i> Audit Logs
            </a>
        </li>
        <?php endif; ?>
        
        <?php if (can_access_role(['Admin'])): ?>
        <li class="sidebar-header">Admin Tools</li>
        <li>
            <a href="<?php echo APP_URL; ?>?page=settings" class="<?php echo $page === 'settings' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i> Settings
            </a>
        </li>
        <li>
            <a href="<?php echo APP_URL; ?>?page=backup" class="<?php echo $page === 'backup' ? 'active' : ''; ?>">
                <i class="fas fa-database"></i> Backup & Restore
            </a>
        </li>
        <li>
            <a href="<?php echo APP_URL; ?>?page=users" class="<?php echo $page === 'users' ? 'active' : ''; ?>">
                <i class="fas fa-user-shield"></i> User Management
            </a>
        </li>
        <li>
            <a href="<?php echo APP_URL; ?>?page=kiosk_devices" class="<?php echo $page === 'kiosk_devices' ? 'active' : ''; ?>">
                <i class="fas fa-desktop"></i> Kiosk Devices
            </a>
        </li>
        <?php endif; ?>
        
        <li class="sidebar-header mt-3">Quick Access</li>
        <li>
            <a href="<?php echo APP_URL; ?>?page=kiosk_login" target="_blank">
                <i class="fas fa-mobile-alt"></i> Kiosk Mode
            </a>
        </li>
        <li>
            <a href="<?php echo APP_URL; ?>?page=qr_menu" target="_blank">
                <i class="fas fa-qrcode"></i> QR Menu
            </a>
        </li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content">
    <!-- Top Navbar -->
    <div class="top-navbar">
        <div>
            <button class="btn btn-light d-md-none" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <span class="ms-2 text-muted">
                <?php echo date('l, F j, Y'); ?>
            </span>
        </div>
        
        <div class="user-info">
            <!-- Notifications -->
            <div class="dropdown notification-badge">
                <a href="#" class="text-muted" data-bs-toggle="dropdown">
                    <i class="fas fa-bell fa-lg"></i>
                    <?php 
                    $unread_count = get_notification_count();
                    if ($unread_count > 0):
                    ?>
                    <span class="notification-count"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </a>
                <div class="dropdown-menu dropdown-menu-end" style="width: 300px;">
                    <div class="dropdown-header">Notifications</div>
                    <div id="notificationsList">
                        <?php
                        $notifications = get_unread_notifications(5);
                        if (empty($notifications)):
                        ?>
                        <div class="dropdown-item text-muted small">No new notifications</div>
                        <?php else: ?>
                        <?php foreach ($notifications as $notif): ?>
                        <a href="#" class="dropdown-item small" onclick="markNotificationRead(<?php echo $notif['id']; ?>)">
                            <i class="fas fa-info-circle text-primary"></i>
                            <?php echo htmlspecialchars($notif['message']); ?>
                            <br><small class="text-muted"><?php echo time_ago($notif['timestamp']); ?></small>
                        </a>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="<?php echo APP_URL; ?>?page=notifications" class="dropdown-item text-center small">View All</a>
                </div>
            </div>
            
            <!-- User Profile -->
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle text-dark" data-bs-toggle="dropdown">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                        <?php echo strtoupper(substr(get_current_user()['name'], 0, 1)); ?>
                    </div>
                    <span class="ms-2 fw-medium"><?php echo htmlspecialchars(get_current_user()['name']); ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="<?php echo APP_URL; ?>?page=profile"><i class="fas fa-user me-2"></i> Profile</a></li>
                    <li><a class="dropdown-item" href="<?php echo APP_URL; ?>?page=timeclock&action=clock" id="clockInOutBtn"><i class="fas fa-clock me-2"></i> Clock In/Out</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="<?php echo APP_URL; ?>?page=logout"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
