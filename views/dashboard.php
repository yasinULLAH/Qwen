<?php
/**
 * Dashboard View
 * Main dashboard with statistics and quick actions
 */

// Check permission
check_permission(['Admin', 'Accountant', 'Counter', 'Server']);

// Get dashboard statistics
$today = date('Y-m-d');
$first_day_of_month = date('Y-m-01');

// Today's sales
$stmt = dbQuery("SELECT COUNT(*) as count, COALESCE(SUM(netamount), 0) as total FROM orders WHERE DATE(created_at) = ? AND status NOT IN ('Cancelled')", [$today]);
$today_stats = dbFetchOne($stmt);

// Month's sales
$stmt = dbQuery("SELECT COUNT(*) as count, COALESCE(SUM(netamount), 0) as total FROM orders WHERE DATE(created_at) >= ? AND status NOT IN ('Cancelled')", [$first_day_of_month]);
$month_stats = dbFetchOne($stmt);

// Pending orders (for kitchen)
$stmt = dbQuery("SELECT COUNT(*) as count FROM orders WHERE status IN ('Pending', 'Cooking')");
$pending_orders = dbFetchOne($stmt);

// Low stock items
$stmt = dbQuery("SELECT COUNT(*) as count FROM ingredients WHERE stocklevel <= 10");
$low_stock = dbFetchOne($stmt);

// Customer khata (total outstanding)
$stmt = dbQuery("SELECT COALESCE(SUM(balance), 0) as total FROM customers WHERE balance > 0");
$total_khata = dbFetchOne($stmt);

// Reservations today
$stmt = dbQuery("SELECT COUNT(*) as count FROM reservations WHERE DATE(reservationtime) = ? AND status = 'Confirmed'", [$today]);
$reservations_today = dbFetchOne($stmt);

// Recent orders
$stmt = dbQuery("
    SELECT o.*, u.name as user_name, c.name as customer_name 
    FROM orders o 
    LEFT JOIN users u ON o.userid = u.id 
    LEFT JOIN customers c ON o.customerid = c.id 
    ORDER BY o.created_at DESC 
    LIMIT 10
");
$recent_orders = dbFetchAll($stmt);

// Top selling products today
$stmt = dbQuery("
    SELECT p.name, SUM(oi.qty) as total_qty 
    FROM orderitems oi 
    JOIN products p ON oi.productid = p.id 
    JOIN orders o ON oi.orderid = o.id 
    WHERE DATE(o.created_at) = ? 
    GROUP BY p.id, p.name 
    ORDER BY total_qty DESC 
    LIMIT 5
", [$today]);
$top_products = dbFetchAll($stmt);

// Notifications for dashboard
$notifications = get_unread_notifications(5);
?>

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h2><i class="fas fa-tachometer-alt"></i> Dashboard</h2>
            <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars(get_current_user()['name']); ?>!</p>
        </div>
        <div class="col-auto">
            <button class="btn btn-primary" onclick="location.reload()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row">
    <div class="col-md-3">
        <div class="card border-start border-4 border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Today's Sales</h6>
                        <h3 class="mb-0"><?php echo format_currency($today_stats['total']); ?></h3>
                        <small class="text-muted"><?php echo $today_stats['count']; ?> orders</small>
                    </div>
                    <div class="text-primary">
                        <i class="fas fa-chart-line fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-start border-4 border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Monthly Sales</h6>
                        <h3 class="mb-0"><?php echo format_currency($month_stats['total']); ?></h3>
                        <small class="text-muted"><?php echo $month_stats['count']; ?> orders</small>
                    </div>
                    <div class="text-success">
                        <i class="fas fa-calendar-check fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-start border-4 border-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Pending Orders</h6>
                        <h3 class="mb-0"><?php echo $pending_orders['count']; ?></h3>
                        <small class="text-muted">In kitchen</small>
                    </div>
                    <div class="text-warning">
                        <i class="fas fa-clock fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-start border-4 border-danger">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Customer Khata</h6>
                        <h3 class="mb-0"><?php echo format_currency($total_khata['total']); ?></h3>
                        <small class="text-muted">Outstanding</small>
                    </div>
                    <div class="text-danger">
                        <i class="fas fa-book fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions & Alerts -->
<div class="row mt-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-receipt"></i> Recent Orders</span>
                <a href="?page=orders" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_orders)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No recent orders</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($recent_orders as $order): ?>
                            <tr>
                                <td><strong>#<?php echo $order['id']; ?></strong></td>
                                <td><?php echo htmlspecialchars($order['customer_name'] ?? 'Walk-in'); ?></td>
                                <td><?php echo format_currency($order['netamount']); ?></td>
                                <td>
                                    <?php
                                    $status_class = match($order['status']) {
                                        'Pending' => 'warning',
                                        'Cooking' => 'info',
                                        'Served' => 'success',
                                        'Paid' => 'success',
                                        'Credit' => 'danger',
                                        'Cancelled' => 'secondary',
                                        default => 'secondary'
                                    };
                                    ?>
                                    <span class="badge bg-<?php echo $status_class; ?>"><?php echo $order['status']; ?></span>
                                </td>
                                <td><?php echo date('h:i A', strtotime($order['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- Quick Actions -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-bolt"></i> Quick Actions
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <?php if (can_access_role(['Admin', 'Counter', 'Server'])): ?>
                    <a href="?page=pos" class="btn btn-primary">
                        <i class="fas fa-cash-register"></i> New POS Order
                    </a>
                    <?php endif; ?>
                    
                    <?php if (can_access_role(['Admin', 'Kitchen'])): ?>
                    <a href="?page=kitchen" class="btn btn-warning">
                        <i class="fas fa-fire"></i> Kitchen Display
                    </a>
                    <?php endif; ?>
                    
                    <?php if (can_access_role(['Admin', 'Accountant'])): ?>
                    <a href="?page=khata" class="btn btn-danger">
                        <i class="fas fa-book"></i> Customer Khata
                    </a>
                    <?php endif; ?>
                    
                    <?php if (can_access_role(['Admin', 'Accountant'])): ?>
                    <a href="?page=inventory" class="btn btn-info text-white">
                        <i class="fas fa-boxes"></i> Check Inventory
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Alerts -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-exclamation-triangle"></i> Alerts
            </div>
            <div class="card-body">
                <?php if ($low_stock['count'] > 0): ?>
                <div class="alert alert-warning mb-2">
                    <i class="fas fa-exclamation-circle"></i>
                    <strong><?php echo $low_stock['count']; ?></strong> ingredients are low in stock!
                    <a href="?page=inventory" class="alert-link">View Inventory</a>
                </div>
                <?php endif; ?>
                
                <?php if ($reservations_today['count'] > 0): ?>
                <div class="alert alert-info mb-2">
                    <i class="fas fa-calendar-check"></i>
                    <strong><?php echo $reservations_today['count']; ?></strong> reservations today!
                    <a href="?page=reservations" class="alert-link">View Bookings</a>
                </div>
                <?php endif; ?>
                
                <?php if ($pending_orders['count'] > 0): ?>
                <div class="alert alert-warning mb-2">
                    <i class="fas fa-clock"></i>
                    <strong><?php echo $pending_orders['count']; ?></strong> orders pending in kitchen!
                    <a href="?page=kitchen" class="alert-link">View Kitchen</a>
                </div>
                <?php endif; ?>
                
                <?php if ($low_stock['count'] == 0 && $reservations_today['count'] == 0 && $pending_orders['count'] == 0): ?>
                <div class="alert alert-success mb-0">
                    <i class="fas fa-check-circle"></i>
                    Everything is running smoothly! No alerts at the moment.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Top Products -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-star"></i> Top Selling Products Today
            </div>
            <div class="card-body">
                <?php if (empty($top_products)): ?>
                <p class="text-muted text-center mb-0">No sales data available for today yet.</p>
                <?php else: ?>
                <div class="row">
                    <?php foreach ($top_products as $index => $product): ?>
                    <div class="col-md-2 col-6 mb-3">
                        <div class="text-center p-3 border rounded">
                            <div class="display-4 text-primary mb-2">
                                <?php echo ['#1 🥇', '#2 🥈', '#3 🥉', '#4', '#5'][$index]; ?>
                            </div>
                            <h6 class="mb-1"><?php echo htmlspecialchars($product['name']); ?></h6>
                            <small class="text-muted"><?php echo $product['total_qty']; ?> sold</small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-refresh dashboard every 60 seconds
setTimeout(function() {
    location.reload();
}, 60000);
</script>
