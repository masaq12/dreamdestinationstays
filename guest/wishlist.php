<?php
require_once '../config/config.php';
require_once '../config/database.php';

if (!isGuest()) {
    redirect(SITE_URL . '/login.php');
}

$pageTitle = 'My Wishlist - Dream Destination Stays';

try {
    $pdo = getPDOConnection();
    
    // Get wishlist items
    $stmt = $pdo->prepare("
        SELECT w.*, l.*, u.full_name as host_name,
        (SELECT photo_url FROM listing_photos WHERE listing_id = l.listing_id AND is_primary = 1 LIMIT 1) as primary_photo,
        (SELECT AVG(rating) FROM reviews r JOIN bookings b ON r.booking_id = b.booking_id WHERE b.listing_id = l.listing_id AND r.review_type = 'guest_to_host') as avg_rating,
        (SELECT COUNT(*) FROM reviews r JOIN bookings b ON r.booking_id = b.booking_id WHERE b.listing_id = l.listing_id AND r.review_type = 'guest_to_host') as review_count
        FROM wishlists w
        JOIN listings l ON w.listing_id = l.listing_id
        JOIN users u ON l.host_id = u.user_id
        WHERE w.user_id = ?
        ORDER BY w.added_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $wishlist_items = $stmt->fetchAll();
    
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Error loading wishlist';
    $wishlist_items = [];
}

include '../includes/header.php';
?>

<div class="container">
    <h1 style="margin-bottom: 30px;">
        <i class="fas fa-heart"></i> My Wishlist
        <span style="font-size: 18px; font-weight: normal; color: #666;">(<?php echo count($wishlist_items); ?> saved)</span>
    </h1>
    
    <?php if (empty($wishlist_items)): ?>
        <div class="card" style="text-align: center; padding: 60px;">
            <i class="far fa-heart" style="font-size: 64px; color: #ccc; margin-bottom: 20px;"></i>
            <h3>Your wishlist is empty</h3>
            <p style="color: #666; margin-bottom: 20px;">Start saving your favorite listings</p>
            <a href="<?php echo SITE_URL; ?>/index.php" class="btn btn-primary">
                <i class="fas fa-search"></i> Browse Listings
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-3">
            <?php foreach ($wishlist_items as $item): ?>
                <div class="listing-card" style="position: relative;">
                    <button class="wishlist-btn" onclick="removeFromWishlist(<?php echo $item['listing_id']; ?>, this)" 
                            style="position: absolute; top: 10px; right: 10px; background: white; border: none; border-radius: 50%; width: 40px; height: 40px; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.15); z-index: 10;">
                        <i class="fas fa-heart" style="color: #ff385c; font-size: 18px;"></i>
                    </button>
                    
                    <a href="<?php echo SITE_URL; ?>/listing_public.php?id=<?php echo $item['listing_id']; ?>" style="text-decoration: none; color: inherit;">
                        <?php if ($item['primary_photo']): ?>
                            <img src="<?php echo SITE_URL . '/' . htmlspecialchars($item['primary_photo']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                 class="listing-image"
                                 onerror="this.src='<?php echo SITE_URL; ?>/assets/images/placeholder.jpg'">
                        <?php else: ?>
                            <div class="listing-image" style="background-color: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-home" style="font-size: 48px; color: #ccc;"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="listing-content">
                            <h3 class="listing-title"><?php echo htmlspecialchars($item['title']); ?></h3>
                            <p style="color: #666; margin: 5px 0;">
                                <i class="fas fa-map-marker-alt"></i> 
                                <?php echo htmlspecialchars($item['city'] . ', ' . $item['country']); ?>
                            </p>
                            
                            <div style="display: flex; gap: 15px; margin: 10px 0; color: #666; font-size: 14px;">
                                <span><i class="fas fa-users"></i> <?php echo $item['max_guests']; ?></span>
                                <span><i class="fas fa-bed"></i> <?php echo $item['bedrooms']; ?></span>
                                <span><i class="fas fa-bath"></i> <?php echo $item['bathrooms']; ?></span>
                            </div>
                            
                            <?php if ($item['avg_rating']): ?>
                                <p style="color: var(--warning-color); margin: 5px 0;">
                                    <i class="fas fa-star"></i> 
                                    <?php echo number_format($item['avg_rating'], 1); ?> 
                                    (<?php echo $item['review_count']; ?> reviews)
                                </p>
                            <?php endif; ?>
                            
                            <div style="border-top: 1px solid #eee; padding-top: 10px; margin-top: 10px;">
                                <div class="listing-price">
                                    <?php echo formatCurrency($item['price_per_night']); ?> <span style="font-size: 14px; font-weight: normal;">/ night</span>
                                </div>
                                <p style="color: #666; font-size: 12px; margin: 5px 0 0 0;">
                                    Saved on <?php echo date('M d, Y', strtotime($item['added_at'])); ?>
                                </p>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function removeFromWishlist(listingId, btn) {
    event.preventDefault();
    event.stopPropagation();
    
    if (!confirm('Remove this listing from your wishlist?')) {
        return;
    }
    
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
            // Remove the card from view
            btn.closest('.listing-card').remove();
            
            // Check if wishlist is now empty
            const grid = document.querySelector('.grid');
            if (grid && grid.children.length === 0) {
                location.reload();
            }
        } else {
            alert(data.message || 'Error removing from wishlist');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error removing from wishlist');
    });
}
</script>

<?php include '../includes/footer.php'; ?>
