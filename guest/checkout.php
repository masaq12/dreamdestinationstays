<?php
require_once '../config/config.php';
require_once '../config/database.php';

if (!isGuest()) {
    redirect(SITE_URL . '/login.php');
}

$pageTitle = 'Check Out - Dream Destination Stays';

try {
    $pdo = getPDOConnection();
    
    // Get all bookings eligible for checkout (checked_in and checkout date reached)
    $stmt = $pdo->prepare("
        SELECT b.*, l.title as listing_title, l.city, l.country,
        h.full_name as host_name,
        (SELECT photo_url FROM listing_photos WHERE listing_id = l.listing_id AND is_primary = 1 LIMIT 1) as primary_photo
        FROM bookings b
        JOIN listings l ON b.listing_id = l.listing_id
        JOIN users h ON b.host_id = h.user_id
        WHERE b.guest_id = ?
        AND b.booking_status = 'checked_in'
        AND b.check_out <= CURDATE()
        ORDER BY b.check_out ASC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $checkout_ready = $stmt->fetchAll();
    
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Error loading checkout information';
    $checkout_ready = [];
}

include '../includes/header.php';
?>

<div class="container">
    <h1 style="margin-bottom: 10px;">
        <i class="fas fa-sign-out-alt"></i> Check Out
    </h1>
    <p style="color: #666; margin-bottom: 30px;">Complete your stay and leave a review</p>
    
    <?php if (empty($checkout_ready)): ?>
        <div class="card" style="text-align: center; padding: 60px;">
            <i class="fas fa-check-circle" style="font-size: 64px; color: #28a745; margin-bottom: 20px;"></i>
            <h3>No Active Check-Outs</h3>
            <p style="color: #666;">You don't have any stays ready for checkout at the moment.</p>
            <a href="bookings.php" class="btn btn-primary" style="margin-top: 20px;">
                <i class="fas fa-calendar-check"></i> View My Bookings
            </a>
        </div>
    <?php else: ?>
        <div style="background-color: #fff3cd; padding: 15px; border-radius: 8px; margin-bottom: 30px;">
            <h4 style="margin: 0 0 10px 0; color: #856404;">
                <i class="fas fa-info-circle"></i> Ready to Check Out
            </h4>
            <p style="margin: 0; color: #856404; font-size: 14px;">
                You have <?php echo count($checkout_ready); ?> stay(s) ready for checkout. 
                When you check out, payment will be released to the host and you can leave a review.
            </p>
        </div>
        
        <?php foreach ($checkout_ready as $booking): ?>
            <div class="card" style="margin-bottom: 20px;">
                <div class="grid grid-2" style="gap: 30px;">
                    <div>
                        <?php if ($booking['primary_photo']): ?>
                            <img src="<?php echo SITE_URL . '/' . htmlspecialchars($booking['primary_photo']); ?>" 
                                 alt="<?php echo htmlspecialchars($booking['listing_title']); ?>" 
                                 style="width: 100%; height: 250px; object-fit: cover; border-radius: 8px;"
                                 onerror="this.src='<?php echo SITE_URL; ?>/assets/images/placeholder.jpg'">
                        <?php else: ?>
                            <div style="width: 100%; height: 250px; background-color: #f0f0f0; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-home" style="font-size: 64px; color: #ccc;"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                            <div>
                                <h3 style="margin-bottom: 5px;"><?php echo htmlspecialchars($booking['listing_title']); ?></h3>
                                <p style="color: #666; margin: 0;">
                                    <i class="fas fa-map-marker-alt"></i> 
                                    <?php echo htmlspecialchars($booking['city'] . ', ' . $booking['country']); ?>
                                </p>
                            </div>
                            <span class="badge badge-warning" style="font-size: 14px;">
                                <i class="fas fa-clock"></i> Ready to Check Out
                            </span>
                        </div>
                        
                        <div style="background-color: var(--light-color); padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                            <div class="grid grid-2" style="gap: 20px;">
                                <div>
                                    <p style="margin: 0; color: #666; font-size: 14px;">Check-in</p>
                                    <p style="margin: 5px 0; font-weight: bold;">
                                        <i class="fas fa-calendar"></i> 
                                        <?php echo date('M d, Y', strtotime($booking['check_in'])); ?>
                                    </p>
                                </div>
                                <div>
                                    <p style="margin: 0; color: #666; font-size: 14px;">Check-out</p>
                                    <p style="margin: 5px 0; font-weight: bold;">
                                        <i class="fas fa-calendar"></i> 
                                        <?php echo date('M d, Y', strtotime($booking['check_out'])); ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="grid grid-3" style="gap: 20px; margin-top: 10px; padding-top: 10px; border-top: 1px solid #ddd;">
                                <div>
                                    <p style="margin: 0; color: #666; font-size: 14px;">Nights</p>
                                    <p style="margin: 5px 0; font-weight: bold;">
                                        <i class="fas fa-moon"></i> <?php echo $booking['num_nights']; ?>
                                    </p>
                                </div>
                                <div>
                                    <p style="margin: 0; color: #666; font-size: 14px;">Guests</p>
                                    <p style="margin: 5px 0; font-weight: bold;">
                                        <i class="fas fa-users"></i> <?php echo $booking['num_guests']; ?>
                                    </p>
                                </div>
                                <div>
                                    <p style="margin: 0; color: #666; font-size: 14px;">Host</p>
                                    <p style="margin: 5px 0; font-weight: bold;">
                                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($booking['host_name']); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div style="background-color: #e3f2fd; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <p style="margin: 0; color: #666; font-size: 14px;">Total Amount</p>
                                    <p style="margin: 5px 0; font-size: 20px; font-weight: bold; color: var(--primary-color);">
                                        <?php echo formatCurrency($booking['total_amount']); ?>
                                    </p>
                                    <p style="margin: 5px 0; color: #666; font-size: 12px;">
                                        Booking #<?php echo $booking['booking_id']; ?>
                                    </p>
                                </div>
                                <div style="text-align: right;">
                                    <p style="margin: 0; color: #666; font-size: 14px;">Payment Status</p>
                                    <p style="margin: 5px 0; font-weight: bold; color: #ff9800;">
                                        <i class="fas fa-lock"></i> In Escrow
                                    </p>
                                    <p style="margin: 5px 0; color: #666; font-size: 12px;">
                                        Will be released to host
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <form method="GET" action="checkout_with_review.php" style="margin: 0;">
                            <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                            <button type="submit" class="btn btn-success" style="width: 100%; padding: 15px; font-size: 16px;">
                                <i class="fas fa-sign-out-alt"></i> Check Out & Leave Review
                            </button>
                        </form>
                        
                        <p style="margin: 10px 0 0 0; text-align: center; color: #666; font-size: 13px;">
                            <i class="fas fa-info-circle"></i> Checking out will release payment to the host
                        </p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
