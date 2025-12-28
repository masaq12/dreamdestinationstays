<?php
require_once '../config/config.php';
require_once '../config/database.php';

if (!isGuest()) {
    redirect(SITE_URL . '/login.php');
}

$pageTitle = 'My Bookings - Dream Destination Stays';

try {
    $pdo = getPDOConnection();
    
    // Get all bookings for this guest
    $stmt = $pdo->prepare("
        SELECT b.*, l.title as listing_title, l.city, l.country,
        h.full_name as host_name,
        (SELECT photo_url FROM listing_photos WHERE listing_id = l.listing_id AND is_primary = 1 LIMIT 1) as primary_photo
        FROM bookings b
        JOIN listings l ON b.listing_id = l.listing_id
        JOIN users h ON b.host_id = h.user_id
        WHERE b.guest_id = ?
        ORDER BY b.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $bookings = $stmt->fetchAll();
    
    // Count bookings ready for checkout
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM bookings 
        WHERE guest_id = ? 
        AND booking_status = 'checked_in' 
        AND check_out <= CURDATE()
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $checkout_count = $stmt->fetchColumn();
    
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Error loading bookings';
    $bookings = [];
}

include '../includes/header.php';
?>

<div class="container">
    <h1 style="margin-bottom: 30px;">
        <i class="fas fa-calendar-check"></i> My Bookings
    </h1>
    
    <?php if ($checkout_count > 0): ?>
        <div style="background-color: #fff3cd; border-left: 4px solid #ff9800; padding: 15px 20px; border-radius: 8px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h4 style="margin: 0 0 5px 0; color: #856404;">
                    <i class="fas fa-exclamation-triangle"></i> You have <?php echo $checkout_count; ?> stay(s) ready for checkout!
                </h4>
                <p style="margin: 0; color: #856404; font-size: 14px;">
                    Complete your checkout to release payment to the host and share your experience.
                </p>
            </div>
            <a href="checkout.php" class="btn" style="background-color: #ff9800; color: white; border: none; white-space: nowrap;">
                <i class="fas fa-sign-out-alt"></i> Go to Checkout
            </a>
        </div>
    <?php endif; ?>
    
    <?php if (empty($bookings)): ?>
        <div class="card" style="text-align: center; padding: 60px;">
            <i class="fas fa-calendar-times" style="font-size: 64px; color: #ccc; margin-bottom: 20px;"></i>
            <h3>No bookings yet</h3>
            <p>Start exploring amazing destinations!</p>
            <a href="browse.php" class="btn btn-primary" style="margin-top: 20px;">
                <i class="fas fa-search"></i> Browse Listings
            </a>
        </div>
    <?php else: ?>
        <?php foreach ($bookings as $booking): ?>
            <div class="card" style="margin-bottom: 20px;">
                <div class="grid grid-2" style="gap: 30px;">
                    <div>
                        <?php if ($booking['primary_photo']): ?>
                            <img src="<?php echo SITE_URL . '/' . htmlspecialchars($booking['primary_photo']); ?>" 
                                 alt="<?php echo htmlspecialchars($booking['listing_title']); ?>" 
                                 style="width: 100%; height: 200px; object-fit: cover; border-radius: 8px;"
                                 onerror="this.src='<?php echo SITE_URL; ?>/assets/images/placeholder.jpg'">
                        <?php else: ?>
                            <div style="width: 100%; height: 200px; background-color: #f0f0f0; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-home" style="font-size: 48px; color: #ccc;"></i>
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
                            <div>
                                <?php
                                $status_class = 'badge-info';
                                $status_text = ucfirst($booking['booking_status']);
                                if ($booking['booking_status'] === 'confirmed') $status_class = 'badge-success';
                                if ($booking['booking_status'] === 'completed') $status_class = 'badge-success';
                                if ($booking['booking_status'] === 'cancelled') $status_class = 'badge-danger';
                                if ($booking['booking_status'] === 'refunded') $status_class = 'badge-warning';
                                
                                // Check if ready for check-in
                                $ready_checkin = ($booking['booking_status'] === 'confirmed' && $booking['check_in'] <= date('Y-m-d'));
                                if ($ready_checkin) {
                                    $status_class = 'badge-info';
                                    $status_text = 'Ready to Check In';
                                }
                                
                                // Check if ready for checkout
                                $ready_checkout = ($booking['booking_status'] === 'checked_in' && $booking['check_out'] <= date('Y-m-d'));
                                if ($ready_checkout) {
                                    $status_class = 'badge-warning';
                                    $status_text = 'Ready to Check Out';
                                }
                                ?>
                                <span class="badge <?php echo $status_class; ?>" style="font-size: 14px;">
                                    <?php if ($ready_checkin || $ready_checkout): ?><i class="fas fa-exclamation-circle"></i> <?php endif; ?>
                                    <?php echo $status_text; ?>
                                </span>
                            </div>
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
                            
                            <div class="grid grid-2" style="gap: 20px; margin-top: 10px; padding-top: 10px; border-top: 1px solid #ddd;">
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
                            </div>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <p style="margin: 0; color: #666; font-size: 14px;">Total Amount</p>
                                <p style="margin: 5px 0; font-size: 24px; font-weight: bold; color: var(--primary-color);">
                                    <?php echo formatCurrency($booking['total_amount']); ?>
                                </p>
                                <p style="margin: 5px 0; color: #666; font-size: 12px;">
                                    Booking #<?php echo $booking['booking_id']; ?> • 
                                    <?php echo date('M d, Y', strtotime($booking['created_at'])); ?>
                                </p>
                            </div>
                            
                            <div style="display: flex; flex-direction: column; gap: 10px;">
                                <a href="listing_details.php?id=<?php echo $booking['listing_id']; ?>" class="btn btn-outline">
                                    <i class="fas fa-eye"></i> View Listing
                                </a>
                                
                                <?php 
                                // Check if guest can check-in (confirmed and check-in date arrived)
                                $can_checkin = ($booking['booking_status'] === 'confirmed' && $booking['check_in'] <= date('Y-m-d'));
                                
                                // Check if guest can checkout (on or after checkout date and status is checked_in)
                                $can_checkout = ($booking['booking_status'] === 'checked_in' && $booking['check_out'] <= date('Y-m-d'));
                                
                                // Check if guest can review (booking completed and no review yet)
                                $can_review = false;
                                if ($booking['booking_status'] === 'completed') {
                                    $check_review = $pdo->prepare("
                                        SELECT review_id FROM reviews 
                                        WHERE booking_id = ? AND reviewer_id = ? AND review_type = 'guest_to_host'
                                    ");
                                    $check_review->execute([$booking['booking_id'], $_SESSION['user_id']]);
                                    $can_review = !$check_review->fetch();
                                }
                                ?>
                                
                                <?php if ($can_checkin): ?>
                                    <form method="POST" action="checkin_booking.php" style="margin: 0;" onsubmit="return confirm('Confirm check-in for this booking?');">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                                            <i class="fas fa-sign-in-alt"></i> Check In
                                        </button>
                                    </form>
                                    <p style="margin: 5px 0; text-align: center; color: #666; font-size: 12px;">
                                        <i class="fas fa-info-circle"></i> Check in to your reservation
                                    </p>
                                <?php endif; ?>
                                
                                <?php if ($can_checkout): ?>
                                    <a href="checkout_with_review.php?booking_id=<?php echo $booking['booking_id']; ?>" class="btn btn-success" style="width: 100%;">
                                        <i class="fas fa-sign-out-alt"></i> Check Out & Review
                                    </a>
                                    <p style="margin: 5px 0; text-align: center; color: #666; font-size: 12px;">
                                        <i class="fas fa-exclamation-circle"></i> Payment will be released to host
                                    </p>
                                <?php endif; ?>
                                
                                <?php if ($can_review): ?>
                                    <a href="review.php?booking_id=<?php echo $booking['booking_id']; ?>" class="btn btn-secondary" style="width: 100%;">
                                        <i class="fas fa-star"></i> Write Review
                                    </a>
                                <?php elseif ($booking['booking_status'] === 'completed'): ?>
                                    <button class="btn btn-outline" style="width: 100%; cursor: default;" disabled>
                                        <i class="fas fa-check"></i> Review Submitted
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
