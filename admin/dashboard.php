<?php
require_once '../config/config.php';
require_once '../config/database.php';

if (!isAdmin()) {
    redirect(SITE_URL . '/login.php');
}

$pageTitle = 'Admin Dashboard - Dream Destination Stays';

try {
    $pdo = getPDOConnection();
    
    // Get statistics
    $stats = [
        'total_users' => $pdo->query("SELECT COUNT(*) FROM users WHERE user_type != 'admin'")->fetchColumn(),
        'total_guests' => $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'guest'")->fetchColumn(),
        'total_hosts' => $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'host'")->fetchColumn(),
        'total_listings' => $pdo->query("SELECT COUNT(*) FROM listings")->fetchColumn(),
        'active_listings' => $pdo->query("SELECT COUNT(*) FROM listings WHERE status = 'active'")->fetchColumn(),
        'total_bookings' => $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
        'confirmed_bookings' => $pdo->query("SELECT COUNT(*) FROM bookings WHERE booking_status = 'confirmed'")->fetchColumn(),
        'total_guest_balance' => $pdo->query("SELECT SUM(current_balance) FROM guest_balances")->fetchColumn() ?? 0,
        'total_host_balance' => $pdo->query("SELECT SUM(available_balance) FROM host_balances")->fetchColumn() ?? 0,
        'total_platform_fees' => $pdo->query("SELECT SUM(platform_fees_paid) FROM host_balances")->fetchColumn() ?? 0,
        'today_bookings' => $pdo->query("SELECT COUNT(*) FROM bookings WHERE DATE(created_at) = CURDATE()")->fetchColumn(),
        'total_revenue' => $pdo->query("SELECT SUM(total_amount) FROM bookings WHERE booking_status IN ('confirmed', 'completed')")->fetchColumn() ?? 0,
    ];
    
    // Recent bookings
    $stmt = $pdo->query("
        SELECT b.*, l.title as listing_title, g.full_name as guest_name, h.full_name as host_name
        FROM bookings b
        JOIN listings l ON b.listing_id = l.listing_id
        JOIN users g ON b.guest_id = g.user_id
        JOIN users h ON b.host_id = h.user_id
        ORDER BY b.created_at DESC
        LIMIT 10
    ");
    $recent_bookings = $stmt->fetchAll();
    
    // Recent users
    $stmt = $pdo->query("
        SELECT * FROM users 
        WHERE user_type != 'admin'
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $recent_users = $stmt->fetchAll();
    
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Error loading dashboard data';
}

include '../includes/header.php';
?>

<div class="container">
    <h1 style="margin-bottom: 30px;">
        <i class="fas fa-tachometer-alt"></i> Admin Dashboard
    </h1>
    
    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <i class="fas fa-users" style="font-size: 24px;"></i>
            <div class="stat-value"><?php echo number_format($stats['total_users']); ?></div>
            <div class="stat-label">Total Users</div>
        </div>
        
        <div class="stat-card secondary">
            <i class="fas fa-home" style="font-size: 24px;"></i>
            <div class="stat-value"><?php echo number_format($stats['total_listings']); ?></div>
            <div class="stat-label">Total Listings</div>
        </div>
        
        <div class="stat-card success">
            <i class="fas fa-calendar-check" style="font-size: 24px;"></i>
            <div class="stat-value"><?php echo number_format($stats['total_bookings']); ?></div>
            <div class="stat-label">Total Bookings</div>
        </div>
        
        <div class="stat-card warning">
            <i class="fas fa-dollar-sign" style="font-size: 24px;"></i>
            <div class="stat-value"><?php echo formatCurrency($stats['total_platform_fees']); ?></div>
            <div class="stat-label">Platform Fees Earned</div>
        </div>
    </div>
    
    <!-- Additional Stats -->
    <div class="grid grid-3" style="margin-bottom: 30px;">
        <div class="card">
            <h3><i class="fas fa-user"></i> Guests</h3>
            <p style="font-size: 24px; font-weight: bold; color: var(--primary-color);">
                <?php echo number_format($stats['total_guests']); ?>
            </p>
            <p>Total Balance: <?php echo formatCurrency($stats['total_guest_balance']); ?></p>
        </div>
        
        <div class="card">
            <h3><i class="fas fa-building"></i> Hosts</h3>
            <p style="font-size: 24px; font-weight: bold; color: var(--secondary-color);">
                <?php echo number_format($stats['total_hosts']); ?>
            </p>
            <p>Total Balance: <?php echo formatCurrency($stats['total_host_balance']); ?></p>
        </div>
        
        <div class="card">
            <h3><i class="fas fa-chart-line"></i> Today's Activity</h3>
            <p style="font-size: 24px; font-weight: bold; color: var(--success-color);">
                <?php echo number_format($stats['today_bookings']); ?>
            </p>
            <p>New bookings today</p>
        </div>
    </div>
    
    <!-- Recent Bookings -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-calendar-check"></i> Recent Bookings</h2>
        </div>
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Listing</th>
                        <th>Guest</th>
                        <th>Host</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_bookings)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center;">No bookings yet</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_bookings as $booking): ?>
                            <tr>
                                <td>#<?php echo $booking['booking_id']; ?></td>
                                <td><?php echo htmlspecialchars($booking['listing_title']); ?></td>
                                <td><?php echo htmlspecialchars($booking['guest_name']); ?></td>
                                <td><?php echo htmlspecialchars($booking['host_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($booking['check_in'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($booking['check_out'])); ?></td>
                                <td><?php echo formatCurrency($booking['total_amount']); ?></td>
                                <td>
                                    <?php
                                    $status_class = 'badge-info';
                                    if ($booking['booking_status'] === 'confirmed') $status_class = 'badge-success';
                                    if ($booking['booking_status'] === 'cancelled') $status_class = 'badge-danger';
                                    if ($booking['booking_status'] === 'completed') $status_class = 'badge-success';
                                    ?>
                                    <span class="badge <?php echo $status_class; ?>">
                                        <?php echo ucfirst($booking['booking_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="bookings.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px;">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Recent Users -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-users"></i> Recent Users</h2>
        </div>
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_users)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">No users yet</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_users as $user): ?>
                            <tr>
                                <td><?php echo $user['user_id']; ?></td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo ucfirst($user['user_type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $status_class = $user['status'] === 'active' ? 'badge-success' : 'badge-danger';
                                    ?>
                                    <span class="badge <?php echo $status_class; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <a href="users.php?id=<?php echo $user['user_id']; ?>" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px;">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
