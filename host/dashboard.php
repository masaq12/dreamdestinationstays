<?php
require_once '../config/config.php';
require_once '../config/database.php';

if (!isHost()) {
    redirect(SITE_URL . '/login.php');
}

$pageTitle = 'Host Dashboard - Dream Destination Stays';

try {
    $pdo = getPDOConnection();
    
    // Get host stats
    $host_id = $_SESSION['user_id'];
    
    $stats = [
        'total_listings' => $pdo->prepare("SELECT COUNT(*) FROM listings WHERE host_id = ?"),
        'active_listings' => $pdo->prepare("SELECT COUNT(*) FROM listings WHERE host_id = ? AND status = 'active'"),
        'total_bookings' => $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE host_id = ?"),
        'upcoming_bookings' => $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE host_id = ? AND booking_status = 'confirmed' AND check_in >= CURDATE()"),
    ];
    
    foreach ($stats as $key => $stmt) {
        $stmt->execute([$host_id]);
        $stats[$key] = $stmt->fetchColumn();
    }
    
    // Get balance information
    $stmt = $pdo->prepare("SELECT * FROM host_balances WHERE user_id = ?");
    $stmt->execute([$host_id]);
    $balance = $stmt->fetch();
    
    // Get verification status
    $stmt = $pdo->prepare("SELECT verification_status FROM host_verification WHERE user_id = ?");
    $stmt->execute([$host_id]);
    $verification = $stmt->fetch();
    
    // Get recent bookings
    $stmt = $pdo->prepare("
        SELECT b.*, l.title as listing_title, g.full_name as guest_name
        FROM bookings b
        JOIN listings l ON b.listing_id = l.listing_id
        JOIN users g ON b.guest_id = g.user_id
        WHERE b.host_id = ?
        ORDER BY b.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$host_id]);
    $recent_bookings = $stmt->fetchAll();
    
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Error loading dashboard';
}

include '../includes/header.php';
?>

<div class="container">
    <h1 style="margin-bottom: 10px;">
        <i class="fas fa-tachometer-alt"></i> Host Dashboard
    </h1>
    
    <?php if ($verification && $verification['verification_status'] === 'pending'): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Verification Pending:</strong> Your host account is pending verification. 
            Some features may be limited until verification is complete.
        </div>
    <?php elseif ($verification && $verification['verification_status'] === 'verified'): ?>
        <div class="alert alert-success" style="margin-bottom: 20px;">
            <i class="fas fa-check-circle"></i>
            <strong>Verified Host:</strong> Your account is verified and active!
        </div>
    <?php endif; ?>
    
    <!-- Balance Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <i class="fas fa-wallet" style="font-size: 32px;"></i>
            <div class="stat-value"><?php echo formatCurrency($balance['available_balance']); ?></div>
            <div class="stat-label">Available Balance</div>
        </div>
        
        <div class="stat-card warning">
            <i class="fas fa-clock" style="font-size: 32px;"></i>
            <div class="stat-value"><?php echo formatCurrency($balance['pending_balance']); ?></div>
            <div class="stat-label">Pending Earnings</div>
        </div>
        
        <div class="stat-card secondary">
            <i class="fas fa-chart-line" style="font-size: 32px;"></i>
            <div class="stat-value"><?php echo formatCurrency($balance['total_earned']); ?></div>
            <div class="stat-label">Total Earned</div>
        </div>
        
        <div class="stat-card success">
            <i class="fas fa-hand-holding-usd" style="font-size: 32px;"></i>
            <div class="stat-value"><?php echo formatCurrency($balance['total_paid_out']); ?></div>
            <div class="stat-label">Total Paid Out</div>
        </div>
    </div>
    
    <!-- Quick Stats -->
    <div class="grid grid-4" style="margin-bottom: 30px;">
        <div class="card" style="text-align: center;">
            <i class="fas fa-home" style="font-size: 48px; color: var(--primary-color); margin-bottom: 15px;"></i>
            <h3 style="font-size: 32px; margin: 10px 0;"><?php echo $stats['total_listings']; ?></h3>
            <p style="color: #666;">Total Listings</p>
            <a href="listings.php" class="btn btn-outline" style="margin-top: 10px;">
                <i class="fas fa-eye"></i> View All
            </a>
        </div>
        
        <div class="card" style="text-align: center;">
            <i class="fas fa-check-circle" style="font-size: 48px; color: var(--success-color); margin-bottom: 15px;"></i>
            <h3 style="font-size: 32px; margin: 10px 0;"><?php echo $stats['active_listings']; ?></h3>
            <p style="color: #666;">Active Listings</p>
            <a href="add_listing.php" class="btn btn-outline" style="margin-top: 10px;">
                <i class="fas fa-plus"></i> Add New
            </a>
        </div>
        
        <div class="card" style="text-align: center;">
            <i class="fas fa-calendar-check" style="font-size: 48px; color: var(--secondary-color); margin-bottom: 15px;"></i>
            <h3 style="font-size: 32px; margin: 10px 0;"><?php echo $stats['total_bookings']; ?></h3>
            <p style="color: #666;">Total Bookings</p>
            <a href="bookings.php" class="btn btn-outline" style="margin-top: 10px;">
                <i class="fas fa-eye"></i> View All
            </a>
        </div>
        
        <div class="card" style="text-align: center;">
            <i class="fas fa-calendar-alt" style="font-size: 48px; color: var(--warning-color); margin-bottom: 15px;"></i>
            <h3 style="font-size: 32px; margin: 10px 0;"><?php echo $stats['upcoming_bookings']; ?></h3>
            <p style="color: #666;">Upcoming Bookings</p>
            <a href="bookings.php?filter=upcoming" class="btn btn-outline" style="margin-top: 10px;">
                <i class="fas fa-eye"></i> View
            </a>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="card" style="margin-bottom: 30px;">
        <h2 style="margin-bottom: 20px;"><i class="fas fa-bolt"></i> Quick Actions</h2>
        <div class="grid grid-4">
            <a href="add_listing.php" class="btn btn-primary" style="text-align: center; padding: 20px;">
                <i class="fas fa-plus" style="font-size: 24px; display: block; margin-bottom: 10px;"></i>
                Add New Listing
            </a>
            <a href="earnings.php" class="btn btn-secondary" style="text-align: center; padding: 20px;">
                <i class="fas fa-dollar-sign" style="font-size: 24px; display: block; margin-bottom: 10px;"></i>
                View Earnings
            </a>
            <a href="payouts.php" class="btn btn-success" style="text-align: center; padding: 20px;">
                <i class="fas fa-hand-holding-usd" style="font-size: 24px; display: block; margin-bottom: 10px;"></i>
                Request Payout
            </a>
            <a href="payout_settings.php" class="btn btn-outline" style="text-align: center; padding: 20px;">
                <i class="fas fa-cog" style="font-size: 24px; display: block; margin-bottom: 10px;"></i>
                Payout Settings
            </a>
        </div>
    </div>
    
    <!-- Recent Bookings -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-calendar-check"></i> Recent Bookings</h2>
        </div>
        
        <?php if (empty($recent_bookings)): ?>
            <p style="text-align: center; color: #666; padding: 40px;">
                No bookings yet. Make sure your listings are active and competitive!
            </p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Listing</th>
                            <th>Guest</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_bookings as $booking): ?>
                            <tr>
                                <td>#<?php echo $booking['booking_id']; ?></td>
                                <td><?php echo htmlspecialchars($booking['listing_title']); ?></td>
                                <td><?php echo htmlspecialchars($booking['guest_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($booking['check_in'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($booking['check_out'])); ?></td>
                                <td><?php echo formatCurrency($booking['total_amount']); ?></td>
                                <td>
                                    <?php
                                    $status_class = 'badge-info';
                                    if ($booking['booking_status'] === 'confirmed') $status_class = 'badge-success';
                                    if ($booking['booking_status'] === 'completed') $status_class = 'badge-success';
                                    if ($booking['booking_status'] === 'cancelled') $status_class = 'badge-danger';
                                    ?>
                                    <span class="badge <?php echo $status_class; ?>">
                                        <?php echo ucfirst($booking['booking_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="booking_details.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px;">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
