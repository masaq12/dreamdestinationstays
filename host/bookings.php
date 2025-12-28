<?php
require_once '../config/config.php';
require_once '../config/database.php';

if (!isHost()) {
    redirect(SITE_URL . '/login.php');
}

$pageTitle = 'My Bookings - Host - Dream Destination Stays';

try {
    $pdo = getPDOConnection();
    $host_id = $_SESSION['user_id'];
    
    // Filter
    $filter = $_GET['filter'] ?? 'all';
    
    // Build query based on filter
    $query = "
        SELECT b.*, 
        l.title as listing_title, l.price_per_night,
        g.full_name as guest_name, g.email as guest_email, g.phone as guest_phone
        FROM bookings b
        JOIN listings l ON b.listing_id = l.listing_id
        JOIN users g ON b.guest_id = g.user_id
        WHERE b.host_id = ?
    ";
    
    $params = [$host_id];
    
    switch($filter) {
        case 'upcoming':
            $query .= " AND b.booking_status = 'confirmed' AND b.check_in >= CURDATE()";
            break;
        case 'current':
            $query .= " AND b.booking_status IN ('confirmed', 'checked_in') AND b.check_in <= CURDATE() AND b.check_out >= CURDATE()";
            break;
        case 'past':
            $query .= " AND b.booking_status = 'completed'";
            break;
        case 'cancelled':
            $query .= " AND b.booking_status IN ('cancelled', 'refunded')";
            break;
    }
    
    $query .= " ORDER BY b.check_in DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll();
    
    // Get stats
    $stats = [
        'total' => $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE host_id = ?"),
        'upcoming' => $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE host_id = ? AND booking_status = 'confirmed' AND check_in >= CURDATE()"),
        'current' => $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE host_id = ? AND booking_status IN ('confirmed', 'checked_in') AND check_in <= CURDATE() AND check_out >= CURDATE()"),
        'completed' => $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE host_id = ? AND booking_status = 'completed'"),
    ];
    
    foreach ($stats as $key => $stmt) {
        $stmt->execute([$host_id]);
        $stats[$key] = $stmt->fetchColumn();
    }
    
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Error loading bookings';
    $bookings = [];
    $stats = ['total' => 0, 'upcoming' => 0, 'current' => 0, 'completed' => 0];
}

include '../includes/header.php';
?>

<div class="container">
    <h1 style="margin-bottom: 30px;">
        <i class="fas fa-calendar-check"></i> My Bookings
    </h1>
    
    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <i class="fas fa-calendar"></i>
            <div class="stat-value"><?php echo number_format($stats['total']); ?></div>
            <div class="stat-label">Total Bookings</div>
        </div>
        <div class="stat-card warning">
            <i class="fas fa-calendar-plus"></i>
            <div class="stat-value"><?php echo number_format($stats['upcoming']); ?></div>
            <div class="stat-label">Upcoming</div>
        </div>
        <div class="stat-card secondary">
            <i class="fas fa-home"></i>
            <div class="stat-value"><?php echo number_format($stats['current']); ?></div>
            <div class="stat-label">Current Stays</div>
        </div>
        <div class="stat-card success">
            <i class="fas fa-check-circle"></i>
            <div class="stat-value"><?php echo number_format($stats['completed']); ?></div>
            <div class="stat-label">Completed</div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card">
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="?filter=all" class="btn <?php echo $filter === 'all' ? 'btn-primary' : 'btn-outline'; ?>">
                <i class="fas fa-list"></i> All
            </a>
            <a href="?filter=upcoming" class="btn <?php echo $filter === 'upcoming' ? 'btn-primary' : 'btn-outline'; ?>">
                <i class="fas fa-calendar-plus"></i> Upcoming
            </a>
            <a href="?filter=current" class="btn <?php echo $filter === 'current' ? 'btn-primary' : 'btn-outline'; ?>">
                <i class="fas fa-home"></i> Current Stays
            </a>
            <a href="?filter=past" class="btn <?php echo $filter === 'past' ? 'btn-primary' : 'btn-outline'; ?>">
                <i class="fas fa-history"></i> Past
            </a>
            <a href="?filter=cancelled" class="btn <?php echo $filter === 'cancelled' ? 'btn-primary' : 'btn-outline'; ?>">
                <i class="fas fa-times-circle"></i> Cancelled
            </a>
        </div>
    </div>
    
    <!-- Bookings List -->
    <?php if (empty($bookings)): ?>
        <div class="card" style="text-align: center; padding: 60px 20px;">
            <i class="fas fa-calendar-times" style="font-size: 64px; color: #ddd; margin-bottom: 20px;"></i>
            <h2>No bookings found</h2>
            <p style="color: #666; margin-bottom: 20px;">
                <?php if ($filter === 'all'): ?>
                    You don't have any bookings yet. Make sure your listings are active!
                <?php else: ?>
                    No <?php echo $filter; ?> bookings at the moment.
                <?php endif; ?>
            </p>
            <a href="<?php echo SITE_URL; ?>/host/listings.php" class="btn btn-primary">
                <i class="fas fa-home"></i> View My Listings
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-1" style="gap: 20px;">
            <?php foreach ($bookings as $booking): ?>
                <div class="card">
                    <div class="grid grid-2" style="gap: 20px;">
                        <!-- Booking Info -->
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                                <div>
                                    <h3 style="margin: 0 0 5px 0;">
                                        <?php echo htmlspecialchars($booking['listing_title']); ?>
                                    </h3>
                                    <p style="color: #666; margin: 0;">
                                        Booking #<?php echo $booking['booking_id']; ?>
                                    </p>
                                </div>
                                <div>
                                    <?php
                                    $badge_class = 'badge-info';
                                    if ($booking['booking_status'] === 'confirmed') $badge_class = 'badge-success';
                                    if ($booking['booking_status'] === 'completed') $badge_class = 'badge-success';
                                    if ($booking['booking_status'] === 'cancelled') $badge_class = 'badge-danger';
                                    if ($booking['booking_status'] === 'checked_in') $badge_class = 'badge-warning';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $booking['booking_status'])); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="grid grid-2" style="gap: 20px; margin-bottom: 15px;">
                                <div>
                                    <p style="margin: 0; font-size: 12px; color: #666;">CHECK-IN</p>
                                    <p style="margin: 5px 0; font-weight: bold;">
                                        <i class="fas fa-calendar-check"></i>
                                        <?php echo date('M d, Y', strtotime($booking['check_in'])); ?>
                                    </p>
                                </div>
                                <div>
                                    <p style="margin: 0; font-size: 12px; color: #666;">CHECK-OUT</p>
                                    <p style="margin: 5px 0; font-weight: bold;">
                                        <i class="fas fa-calendar-times"></i>
                                        <?php echo date('M d, Y', strtotime($booking['check_out'])); ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div style="background-color: var(--light-color); padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                    <span><?php echo $booking['num_nights']; ?> nights × <?php echo formatCurrency($booking['nightly_rate']); ?></span>
                                    <strong><?php echo formatCurrency($booking['num_nights'] * $booking['nightly_rate']); ?></strong>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                    <span>Cleaning Fee</span>
                                    <span><?php echo formatCurrency($booking['cleaning_fee']); ?></span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                    <span>Service Fee</span>
                                    <span><?php echo formatCurrency($booking['service_fee']); ?></span>
                                </div>
                                <div style="border-top: 2px solid #ddd; padding-top: 10px; margin-top: 10px; display: flex; justify-content: space-between; font-size: 18px;">
                                    <strong>Total</strong>
                                    <strong><?php echo formatCurrency($booking['total_amount']); ?></strong>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Guest Info -->
                        <div>
                            <h4><i class="fas fa-user"></i> Guest Information</h4>
                            <div style="background-color: var(--light-color); padding: 15px; border-radius: 8px;">
                                <p style="margin: 0 0 10px 0;">
                                    <strong><?php echo htmlspecialchars($booking['guest_name']); ?></strong>
                                </p>
                                <p style="margin: 0 0 10px 0; color: #666;">
                                    <i class="fas fa-envelope"></i>
                                    <?php echo htmlspecialchars($booking['guest_email']); ?>
                                </p>
                                <?php if ($booking['guest_phone']): ?>
                                    <p style="margin: 0 0 10px 0; color: #666;">
                                        <i class="fas fa-phone"></i>
                                        <?php echo htmlspecialchars($booking['guest_phone']); ?>
                                    </p>
                                <?php endif; ?>
                                <p style="margin: 10px 0 0 0; color: #666;">
                                    <i class="fas fa-users"></i>
                                    <?php echo $booking['num_guests']; ?> guest<?php echo $booking['num_guests'] > 1 ? 's' : ''; ?>
                                </p>
                            </div>
                            
                            <div style="margin-top: 15px;">
                                <a href="booking_details.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-primary" style="width: 100%;">
                                    <i class="fas fa-eye"></i> View Full Details
                                </a>
                            </div>
                            
                            <?php if ($booking['booking_status'] === 'completed'): ?>
                                <div style="margin-top: 10px;">
                                    <button class="btn btn-secondary" style="width: 100%;">
                                        <i class="fas fa-star"></i> Leave Review
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
