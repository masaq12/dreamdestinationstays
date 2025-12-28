<?php
require_once '../config/config.php';
require_once '../config/database.php';

if (!isHost()) {
    redirect(SITE_URL . '/login.php');
}

$pageTitle = 'My Listings - Dream Destination Stays';

try {
    $pdo = getPDOConnection();
    
    // Get all listings for this host
    $stmt = $pdo->prepare("
        SELECT l.*,
        (SELECT photo_url FROM listing_photos WHERE listing_id = l.listing_id AND is_primary = 1 LIMIT 1) as primary_photo,
        (SELECT COUNT(*) FROM bookings WHERE listing_id = l.listing_id) as booking_count
        FROM listings l
        WHERE l.host_id = ?
        ORDER BY l.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $listings = $stmt->fetchAll();
    
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Error loading listings';
    $listings = [];
}

include '../includes/header.php';
?>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h1><i class="fas fa-home"></i> My Listings</h1>
        <a href="add_listing.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Listing
        </a>
    </div>
    
    <?php if (empty($listings)): ?>
        <div class="card" style="text-align: center; padding: 60px;">
            <i class="fas fa-home" style="font-size: 64px; color: #ccc; margin-bottom: 20px;"></i>
            <h3>No listings yet</h3>
            <p>Create your first listing to start earning!</p>
            <a href="add_listing.php" class="btn btn-primary" style="margin-top: 20px;">
                <i class="fas fa-plus"></i> Create First Listing
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-2">
            <?php foreach ($listings as $listing): ?>
                <div class="card">
                    <div class="grid grid-2" style="gap: 20px;">
                        <div>
                            <?php if ($listing['primary_photo']): ?>
                                <img src="<?php echo SITE_URL . '/' . htmlspecialchars($listing['primary_photo']); ?>" 
                                     alt="<?php echo htmlspecialchars($listing['title']); ?>" 
                                     style="width: 100%; height: 200px; object-fit: cover; border-radius: 8px;"
                                     onerror="this.src='<?php echo SITE_URL; ?>/assets/images/placeholder.jpg'">
                            <?php else: ?>
                                <div style="width: 100%; height: 200px; background-color: #f0f0f0; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-home" style="font-size: 48px; color: #ccc;"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                                <h3 style="margin: 0;"><?php echo htmlspecialchars($listing['title']); ?></h3>
                                <?php
                                $status_class = 'badge-success';
                                if ($listing['status'] === 'inactive') $status_class = 'badge-warning';
                                if ($listing['status'] === 'suspended') $status_class = 'badge-danger';
                                ?>
                                <span class="badge <?php echo $status_class; ?>">
                                    <?php echo ucfirst($listing['status']); ?>
                                </span>
                            </div>
                            
                            <p style="color: #666; margin: 5px 0;">
                                <i class="fas fa-map-marker-alt"></i> 
                                <?php echo htmlspecialchars($listing['city'] . ', ' . $listing['country']); ?>
                            </p>
                            
                            <div style="margin: 15px 0;">
                                <div style="display: flex; gap: 15px; color: #666; font-size: 14px;">
                                    <span><i class="fas fa-users"></i> <?php echo $listing['max_guests']; ?></span>
                                    <span><i class="fas fa-bed"></i> <?php echo $listing['bedrooms']; ?></span>
                                    <span><i class="fas fa-bath"></i> <?php echo $listing['bathrooms']; ?></span>
                                </div>
                            </div>
                            
                            <div style="background-color: var(--light-color); padding: 10px; border-radius: 8px; margin: 15px 0;">
                                <p style="margin: 0; font-size: 24px; font-weight: bold; color: var(--primary-color);">
                                    <?php echo formatCurrency($listing['price_per_night']); ?> / night
                                </p>
                                <p style="margin: 5px 0 0 0; font-size: 12px; color: #666;">
                                    <?php echo $listing['booking_count']; ?> total bookings
                                </p>
                            </div>
                            
                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                <a href="edit_listing.php?id=<?php echo $listing['listing_id']; ?>" class="btn btn-primary" style="padding: 8px 15px; font-size: 14px;">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="manage_photos.php?id=<?php echo $listing['listing_id']; ?>" class="btn btn-secondary" style="padding: 8px 15px; font-size: 14px;">
                                    <i class="fas fa-images"></i> Photos
                                </a>
                                <a href="listing_details.php?id=<?php echo $listing['listing_id']; ?>" class="btn btn-outline" style="padding: 8px 15px; font-size: 14px;" target="_blank">
                                    <i class="fas fa-eye"></i> Preview
                                </a>
                                <a href="availability.php?id=<?php echo $listing['listing_id']; ?>" class="btn btn-secondary" style="padding: 8px 15px; font-size: 14px;">
                                    <i class="fas fa-calendar-alt"></i> Calendar
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
