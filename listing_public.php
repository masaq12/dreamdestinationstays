<?php
require_once 'config/config.php';
require_once 'config/database.php';

$listing_id = $_GET['id'] ?? 0;
$pageTitle = 'Listing Details - Dream Destination Stays';

try {
    $pdo = getPDOConnection();
    
    // Get listing details
    $stmt = $pdo->prepare("
        SELECT l.*, u.full_name as host_name, u.email as host_email, u.user_id as host_user_id,
        (SELECT AVG(rating) FROM reviews r JOIN bookings b ON r.booking_id = b.booking_id WHERE b.listing_id = l.listing_id AND r.review_type = 'guest_to_host') as avg_rating,
        (SELECT COUNT(*) FROM reviews r JOIN bookings b ON r.booking_id = b.booking_id WHERE b.listing_id = l.listing_id AND r.review_type = 'guest_to_host') as review_count
        FROM listings l
        JOIN users u ON l.host_id = u.user_id
        WHERE l.listing_id = ? AND l.status = 'active'
    ");
    $stmt->execute([$listing_id]);
    $listing = $stmt->fetch();
    
    if (!$listing) {
        $_SESSION['error_message'] = 'Listing not found';
        redirect(SITE_URL . '/index.php');
    }
    
    // Get listing photos
    $stmt = $pdo->prepare("SELECT * FROM listing_photos WHERE listing_id = ? ORDER BY is_primary DESC, display_order ASC");
    $stmt->execute([$listing_id]);
    $photos = $stmt->fetchAll();
    
    // Get guest payment credentials and balance if logged in as guest
    $balance_info = null;
    $payment_credentials = [];
    $is_in_wishlist = false;
    
    if (isGuest()) {
        $stmt = $pdo->prepare("SELECT current_balance, pending_holds FROM guest_balances WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $balance_info = $stmt->fetch();
        
        $stmt = $pdo->prepare("SELECT * FROM payment_credentials WHERE user_id = ? AND status = 'active' ORDER BY created_at DESC");
        $stmt->execute([$_SESSION['user_id']]);
        $payment_credentials = $stmt->fetchAll();
        
        // Check if in wishlist
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlists WHERE user_id = ? AND listing_id = ?");
        $stmt->execute([$_SESSION['user_id'], $listing_id]);
        $is_in_wishlist = $stmt->fetchColumn() > 0;
    }
    
    // Get reviews
    $stmt = $pdo->prepare("
        SELECT r.*, u.full_name as reviewer_name, b.check_in, b.check_out
        FROM reviews r
        JOIN bookings b ON r.booking_id = b.booking_id
        JOIN users u ON r.reviewer_id = u.user_id
        WHERE b.listing_id = ? AND r.review_type = 'guest_to_host'
        ORDER BY r.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$listing_id]);
    $reviews = $stmt->fetchAll();
    
    $pageTitle = htmlspecialchars($listing['title']) . ' - Dream Destination Stays';
    
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Error loading listing details';
    redirect(SITE_URL . '/index.php');
}

include 'includes/header.php';
?>

<div class="container">
    <!-- Listing Title -->
    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px;">
        <div>
            <h1 style="margin-bottom: 10px;"><?php echo htmlspecialchars($listing['title']); ?></h1>
            <p style="color: #666; margin-bottom: 10px;">
                <i class="fas fa-map-marker-alt"></i> 
                <?php echo htmlspecialchars($listing['city'] . ', ' . $listing['state'] . ', ' . $listing['country']); ?>
                <?php if ($listing['avg_rating']): ?>
                    <span style="margin-left: 20px; color: var(--warning-color);">
                        <i class="fas fa-star"></i> 
                        <?php echo number_format($listing['avg_rating'], 1); ?> 
                        (<?php echo $listing['review_count']; ?> reviews)
                    </span>
                <?php endif; ?>
            </p>
        </div>
        <?php if (isGuest()): ?>
            <button class="btn btn-outline" onclick="toggleWishlist(<?php echo $listing_id; ?>)" id="wishlistBtn">
                <i class="<?php echo $is_in_wishlist ? 'fas' : 'far'; ?> fa-heart"></i>
                <?php echo $is_in_wishlist ? 'Saved' : 'Save'; ?>
            </button>
        <?php endif; ?>
    </div>
    
    <div class="grid grid-2" style="gap: 30px;">
        <!-- Left Column - Photos and Details -->
        <div>
            <!-- Photo Gallery -->
            <div style="margin-bottom: 30px;">
                <?php if (!empty($photos)): ?>
                    <div style="border-radius: 12px; overflow: hidden;">
                        <img src="<?php echo SITE_URL . '/' . htmlspecialchars($photos[0]['photo_url']); ?>" 
                             alt="Listing photo" 
                             id="mainPhoto"
                             style="width: 100%; height: 400px; object-fit: cover; cursor: pointer;"
                             onerror="this.src='<?php echo SITE_URL; ?>/assets/images/placeholder.jpg'">
                    </div>
                    
                    <?php if (count($photos) > 1): ?>
                        <div class="grid grid-4" style="gap: 10px; margin-top: 10px;">
                            <?php foreach ($photos as $index => $photo): ?>
                                <div style="border-radius: 8px; overflow: hidden; border: 2px solid transparent;" class="photo-thumb" data-index="<?php echo $index; ?>">
                                    <img src="<?php echo SITE_URL . '/' . htmlspecialchars($photo['photo_url']); ?>" 
                                         alt="Listing photo" 
                                         style="width: 100%; height: 100px; object-fit: cover; cursor: pointer;"
                                         onclick="changeMainPhoto('<?php echo SITE_URL . '/' . htmlspecialchars($photo['photo_url']); ?>', <?php echo $index; ?>)">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div style="background-color: #f0f0f0; height: 400px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-home" style="font-size: 64px; color: #ccc;"></i>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Property Details -->
            <div class="card">
                <h2><i class="fas fa-info-circle"></i> Property Details</h2>
                <div class="grid grid-4" style="gap: 15px; margin-top: 20px;">
                    <div style="text-align: center; padding: 15px; background-color: var(--light-color); border-radius: 8px;">
                        <i class="fas fa-users" style="font-size: 32px; color: var(--primary-color);"></i>
                        <p style="margin: 10px 0 0 0; font-weight: bold;"><?php echo $listing['max_guests']; ?> Guests</p>
                    </div>
                    <div style="text-align: center; padding: 15px; background-color: var(--light-color); border-radius: 8px;">
                        <i class="fas fa-bed" style="font-size: 32px; color: var(--secondary-color);"></i>
                        <p style="margin: 10px 0 0 0; font-weight: bold;"><?php echo $listing['bedrooms']; ?> Bedrooms</p>
                    </div>
                    <div style="text-align: center; padding: 15px; background-color: var(--light-color); border-radius: 8px;">
                        <i class="fas fa-door-open" style="font-size: 32px; color: var(--success-color);"></i>
                        <p style="margin: 10px 0 0 0; font-weight: bold;"><?php echo $listing['beds']; ?> Beds</p>
                    </div>
                    <div style="text-align: center; padding: 15px; background-color: var(--light-color); border-radius: 8px;">
                        <i class="fas fa-bath" style="font-size: 32px; color: var(--warning-color);"></i>
                        <p style="margin: 10px 0 0 0; font-weight: bold;"><?php echo $listing['bathrooms']; ?> Baths</p>
                    </div>
                </div>
            </div>
            
            <!-- Description -->
            <div class="card">
                <h2><i class="fas fa-align-left"></i> Description</h2>
                <p style="line-height: 1.8; white-space: pre-wrap;"><?php echo htmlspecialchars($listing['description']); ?></p>
            </div>
            
            <!-- Amenities -->
            <?php if ($listing['amenities']): ?>
                <div class="card">
                    <h2><i class="fas fa-check-circle"></i> Amenities</h2>
                    <div class="grid grid-2" style="gap: 10px;">
                        <?php
                        $amenities = explode(',', $listing['amenities']);
                        foreach ($amenities as $amenity):
                            $amenity = trim($amenity);
                            if ($amenity):
                        ?>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-check" style="color: var(--success-color);"></i>
                                <span><?php echo htmlspecialchars($amenity); ?></span>
                            </div>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- House Rules -->
            <?php if ($listing['house_rules']): ?>
                <div class="card">
                    <h2><i class="fas fa-list-check"></i> House Rules</h2>
                    <p style="line-height: 1.8; white-space: pre-wrap;"><?php echo htmlspecialchars($listing['house_rules']); ?></p>
                </div>
            <?php endif; ?>
            
            <!-- Host Info -->
            <div class="card">
                <h2><i class="fas fa-user"></i> Hosted by <?php echo htmlspecialchars($listing['host_name']); ?></h2>
                <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($listing['host_email']); ?></p>
            </div>
            
            <!-- Reviews -->
            <?php if (!empty($reviews)): ?>
                <div class="card">
                    <h2><i class="fas fa-star"></i> Guest Reviews (<?php echo count($reviews); ?>)</h2>
                    <?php foreach ($reviews as $review): ?>
                        <div style="border-bottom: 1px solid #eee; padding: 15px 0;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                <strong><?php echo htmlspecialchars($review['reviewer_name']); ?></strong>
                                <div style="color: var(--warning-color);">
                                    <?php for ($i = 0; $i < $review['rating']; $i++): ?>
                                        <i class="fas fa-star"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <p><?php echo htmlspecialchars($review['comment']); ?></p>
                            <p style="color: #666; font-size: 12px; margin-top: 5px;">
                                <?php echo date('F Y', strtotime($review['created_at'])); ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Right Column - Booking Card -->
        <div>
            <div class="card" style="position: sticky; top: 80px;">
                <div style="border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 20px;">
                    <div class="listing-price" style="font-size: 32px;">
                        <?php echo formatCurrency($listing['price_per_night']); ?>
                        <span style="font-size: 16px; font-weight: normal;"> / night</span>
                    </div>
                    <p style="color: #666; margin: 5px 0;">+ <?php echo formatCurrency($listing['cleaning_fee']); ?> cleaning fee</p>
                </div>
                
                <?php if (!isLoggedIn()): ?>
                    <!-- Non-logged in users -->
                    <div style="background-color: #f0f0f0; padding: 20px; border-radius: 8px; text-align: center; margin-bottom: 20px;">
                        <p style="margin-bottom: 15px; font-weight: bold;">Sign in to book this property</p>
                        <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-primary" style="width: 100%; margin-bottom: 10px;">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                        <a href="<?php echo SITE_URL; ?>/register.php?type=guest" class="btn btn-secondary" style="width: 100%;">
                            <i class="fas fa-user-plus"></i> Create Account
                        </a>
                    </div>
                <?php elseif (isGuest()): ?>
                    <!-- Balance Display -->
                    <div style="background-color: #e8f5e9; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <p style="margin: 0; font-size: 14px;"><strong>Your Balance:</strong></p>
                        <p style="margin: 5px 0 0 0; font-size: 24px; font-weight: bold; color: var(--success-color);">
                            <?php echo formatCurrency($balance_info['current_balance']); ?>
                        </p>
                        <?php if ($balance_info['pending_holds'] > 0): ?>
                            <p style="margin: 5px 0 0 0; font-size: 12px; color: #666;">
                                Pending: <?php echo formatCurrency($balance_info['pending_holds']); ?>
                            </p>
                        <?php endif; ?>
                        <a href="<?php echo SITE_URL; ?>/guest/balance.php" style="font-size: 12px; color: var(--primary-color);">Manage Balance</a>
                    </div>
                    
                    <!-- Booking Form -->
                    <form method="POST" action="<?php echo SITE_URL; ?>/guest/book_listing.php" id="bookingForm">
                        <input type="hidden" name="listing_id" value="<?php echo $listing_id; ?>">
                        <input type="hidden" name="price_per_night" id="price_per_night" value="<?php echo $listing['price_per_night']; ?>">
                        <input type="hidden" name="cleaning_fee" id="cleaning_fee" value="<?php echo $listing['cleaning_fee']; ?>">
                        <input type="hidden" name="service_fee_percent" id="service_fee_percent" value="<?php echo $listing['service_fee_percent']; ?>">
                        <input type="hidden" name="num_nights" id="num_nights" value="1">
                        <input type="hidden" name="total_amount" id="total_amount" value="0">
                        <input type="hidden" id="guest_balance" value="<?php echo $balance_info['current_balance']; ?>">
                        
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-calendar-check"></i> Check-in</label>
                            <input type="date" name="check_in" id="check_in" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-calendar-check"></i> Check-out</label>
                            <input type="date" name="check_out" id="check_out" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-users"></i> Guests</label>
                            <input type="number" name="num_guests" class="form-control" required min="1" max="<?php echo $listing['max_guests']; ?>" value="1">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-credit-card"></i> Payment Method</label>
                            <select name="payment_credential_id" class="form-control" required>
                                <option value="">Select payment method</option>
                                <?php foreach ($payment_credentials as $cred): ?>
                                    <option value="<?php echo $cred['credential_id']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $cred['credential_type'])); ?> - 
                                        <?php echo htmlspecialchars($cred['credential_number']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Price Breakdown -->
                        <div style="background-color: var(--light-color); padding: 15px; border-radius: 8px; margin: 20px 0;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                <span><span id="nights_display">1</span> night(s) × <?php echo formatCurrency($listing['price_per_night']); ?></span>
                                <span id="nights_total"><?php echo formatCurrency($listing['price_per_night']); ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                <span>Cleaning fee</span>
                                <span><?php echo formatCurrency($listing['cleaning_fee']); ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                <span>Service fee (<?php echo $listing['service_fee_percent']; ?>%)</span>
                                <span id="service_fee_display">$0.00</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                <span>Tax (10%)</span>
                                <span id="tax_display">$0.00</span>
                            </div>
                            <div style="border-top: 2px solid #ddd; padding-top: 10px; margin-top: 10px; display: flex; justify-content: space-between; font-size: 18px; font-weight: bold;">
                                <span>Total</span>
                                <span id="total_display"><?php echo formatCurrency($listing['price_per_night']); ?></span>
                            </div>
                        </div>
                        
                        <div id="balance_warning" style="display: none; background-color: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                            <i class="fas fa-exclamation-triangle" style="color: #856404;"></i>
                            <strong style="color: #856404;">Insufficient Balance</strong>
                            <p style="margin: 5px 0 0 0; color: #856404; font-size: 14px;">
                                You need more balance. Please add funds to your account.
                            </p>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 18px;">
                            <i class="fas fa-calendar-check"></i> Book Now
                        </button>
                    </form>
                <?php else: ?>
                    <!-- Logged in as host or admin -->
                    <div style="background-color: #f0f0f0; padding: 20px; border-radius: 8px; text-align: center;">
                        <p>To book this property, please login as a guest.</p>
                    </div>
                <?php endif; ?>
                
                <p style="text-align: center; color: #666; font-size: 12px; margin-top: 15px;">
                    <i class="fas fa-shield-alt"></i> Secure payment • Instant confirmation
                </p>
            </div>
        </div>
    </div>
</div>

<script>
function changeMainPhoto(photoUrl, index) {
    document.getElementById('mainPhoto').src = photoUrl;
    
    // Update active thumbnail
    document.querySelectorAll('.photo-thumb').forEach((thumb, i) => {
        if (i === index) {
            thumb.style.borderColor = 'var(--primary-color)';
        } else {
            thumb.style.borderColor = 'transparent';
        }
    });
}

<?php if (isGuest()): ?>
// Calculate booking total
function calculateTotal() {
    const checkIn = document.getElementById('check_in').value;
    const checkOut = document.getElementById('check_out').value;
    
    if (!checkIn || !checkOut) return;
    
    const date1 = new Date(checkIn);
    const date2 = new Date(checkOut);
    const nights = Math.ceil((date2 - date1) / (1000 * 60 * 60 * 24));
    
    if (nights < 1) {
        document.getElementById('balance_warning').style.display = 'block';
        document.getElementById('balance_warning').querySelector('p').textContent = 'Check-out must be after check-in';
        return;
    }
    
    const pricePerNight = parseFloat(document.getElementById('price_per_night').value);
    const cleaningFee = parseFloat(document.getElementById('cleaning_fee').value);
    const serviceFeePercent = parseFloat(document.getElementById('service_fee_percent').value);
    
    const nightsTotal = pricePerNight * nights;
    const serviceFee = (nightsTotal + cleaningFee) * (serviceFeePercent / 100);
    const tax = (nightsTotal + cleaningFee + serviceFee) * 0.10;
    const total = nightsTotal + cleaningFee + serviceFee + tax;
    
    document.getElementById('num_nights').value = nights;
    document.getElementById('nights_display').textContent = nights;
    document.getElementById('nights_total').textContent = '$' + nightsTotal.toFixed(2);
    document.getElementById('service_fee_display').textContent = '$' + serviceFee.toFixed(2);
    document.getElementById('tax_display').textContent = '$' + tax.toFixed(2);
    document.getElementById('total_display').textContent = '$' + total.toFixed(2);
    document.getElementById('total_amount').value = total.toFixed(2);
    
    // Check balance
    const guestBalance = parseFloat(document.getElementById('guest_balance').value);
    if (total > guestBalance) {
        document.getElementById('balance_warning').style.display = 'block';
    } else {
        document.getElementById('balance_warning').style.display = 'none';
    }
}

document.getElementById('check_in').addEventListener('change', calculateTotal);
document.getElementById('check_out').addEventListener('change', calculateTotal);

function toggleWishlist(listingId) {
    fetch('<?php echo SITE_URL; ?>/guest/wishlist_toggle.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'listing_id=' + listingId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const btn = document.getElementById('wishlistBtn');
            const icon = btn.querySelector('i');
            if (data.action === 'added') {
                icon.classList.remove('far');
                icon.classList.add('fas');
                btn.innerHTML = '<i class="fas fa-heart"></i> Saved';
            } else {
                icon.classList.remove('fas');
                icon.classList.add('far');
                btn.innerHTML = '<i class="far fa-heart"></i> Save';
            }
        } else {
            alert(data.message || 'Error updating wishlist');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating wishlist');
    });
}
<?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>
