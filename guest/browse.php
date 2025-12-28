<?php
require_once '../config/config.php';
require_once '../config/database.php';

if (!isGuest()) {
    redirect(SITE_URL . '/login.php');
}

$pageTitle = 'Browse Stays - Dream Destinations';
include '../includes/header.php';

try {
    $pdo = getPDOConnection();
    
    // Get search parameters
    $location = $_GET['location'] ?? '';
    $check_in = $_GET['check_in'] ?? '';
    $check_out = $_GET['check_out'] ?? '';
    $guests = $_GET['guests'] ?? 1;
    
    // Get popular cities with listing counts (for explore nearby)
    $stmt = $pdo->query("
        SELECT 
            city, 
            country,
            COUNT(*) as listing_count,
            (SELECT photo_url FROM listing_photos lp 
             JOIN listings l2 ON lp.listing_id = l2.listing_id 
             WHERE l2.city = l.city AND l2.status = 'active' AND lp.is_primary = 1 
             LIMIT 1) as city_image
        FROM listings l
        WHERE status = 'active'
        GROUP BY city, country
        ORDER BY listing_count DESC
        LIMIT 8
    ");
    $cities = $stmt->fetchAll();
    
    // Build query for listings
    $query = "SELECT l.*, u.full_name as host_name,
              (SELECT photo_url FROM listing_photos WHERE listing_id = l.listing_id AND is_primary = 1 LIMIT 1) as primary_photo,
              (SELECT AVG(rating) FROM reviews r JOIN bookings b ON r.booking_id = b.booking_id WHERE b.listing_id = l.listing_id AND r.review_type = 'guest_to_host') as avg_rating,
              (SELECT COUNT(*) FROM reviews r JOIN bookings b ON r.booking_id = b.booking_id WHERE b.listing_id = l.listing_id AND r.review_type = 'guest_to_host') as review_count
              FROM listings l
              JOIN users u ON l.host_id = u.user_id
              WHERE l.status = 'active'";
    
    $params = [];
    
    if (!empty($location)) {
        $query .= " AND (l.city LIKE ? OR l.state LIKE ? OR l.country LIKE ?)";
        $params[] = "%$location%";
        $params[] = "%$location%";
        $params[] = "%$location%";
    }
    
    if (!empty($guests)) {
        $query .= " AND l.max_guests >= ?";
        $params[] = $guests;
    }
    
    // Check availability if dates provided
    if (!empty($check_in) && !empty($check_out)) {
        $query .= " AND l.listing_id NOT IN (
            SELECT DISTINCT listing_id FROM bookings 
            WHERE booking_status IN ('confirmed', 'checked_in')
            AND (
                (check_in <= ? AND check_out >= ?)
                OR (check_in <= ? AND check_out >= ?)
                OR (check_in >= ? AND check_out <= ?)
            )
        )";
        $params[] = $check_in;
        $params[] = $check_in;
        $params[] = $check_out;
        $params[] = $check_out;
        $params[] = $check_in;
        $params[] = $check_out;
    }
    
    $query .= " ORDER BY l.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $listings = $stmt->fetchAll();
    
    // Get wishlist items
    $stmt = $pdo->prepare("SELECT listing_id FROM wishlists WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $wishlist_items = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Error loading listings';
    $listings = [];
    $cities = [];
}
?>

<style>
/* Enhanced styles for browse page */
.search-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 24px 16px;
    margin-bottom: 24px;
}

.search-container {
    max-width: 1200px;
    margin: 0 auto;
}

.search-form-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 12px;
    background: white;
    padding: 16px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.search-input-group {
    position: relative;
}

.search-input-group i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--gray-400);
    font-size: 16px;
}

.search-input {
    width: 100%;
    padding: 12px 12px 12px 40px;
    border: 1px solid var(--gray-300);
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.2s;
}

.search-input:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(236, 72, 153, 0.1);
}

.search-btn {
    width: 100%;
    background-color: var(--primary-color);
    color: white;
    border: none;
    padding: 12px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 14px;
}

.search-btn:hover {
    background-color: var(--primary-hover);
}

/* Explore Nearby Section */
.explore-nearby-section {
    margin-bottom: 32px;
}

.section-header {
    margin-bottom: 20px;
}

.section-header h2 {
    font-size: 24px;
    font-weight: 700;
    color: var(--dark-color);
    margin-bottom: 4px;
}

.section-header p {
    font-size: 14px;
    color: var(--gray-500);
}

.cities-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
}

.city-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: all 0.2s;
    cursor: pointer;
    text-decoration: none;
    color: inherit;
}

.city-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transform: translateY(-2px);
}

.city-image {
    width: 64px;
    height: 64px;
    border-radius: 8px;
    object-fit: cover;
    flex-shrink: 0;
}

.city-info {
    flex: 1;
}

.city-name {
    font-size: 16px;
    font-weight: 600;
    color: var(--dark-color);
    margin-bottom: 2px;
}

.city-distance {
    font-size: 13px;
    color: var(--gray-500);
}

/* Active filter badge */
.active-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 16px;
}

.filter-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--primary-color);
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 13px;
}

.filter-badge button {
    background: rgba(255,255,255,0.3);
    border: none;
    color: white;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
}

@media (min-width: 640px) {
    .search-form-grid {
        grid-template-columns: 2fr 1fr 1fr 1fr auto;
        align-items: end;
    }
    
    .search-btn {
        width: auto;
        padding: 12px 24px;
    }
    
    .cities-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 1024px) {
    .cities-grid {
        grid-template-columns: repeat(4, 1fr);
    }
    
    .city-card {
        flex-direction: column;
        text-align: center;
    }
    
    .city-image {
        width: 100%;
        height: 120px;
        border-radius: 8px 8px 0 0;
        margin: -12px -12px 12px -12px;
    }
}
</style>

<!-- Search Section Below Header -->
<div class="search-section">
    <div class="search-container">
        <form method="GET" action="" class="search-form-grid">
            <div class="search-input-group">
                <i class="fas fa-map-marker-alt"></i>
                <input type="text" name="location" placeholder="Where to?" value="<?php echo htmlspecialchars($location); ?>" class="search-input">
            </div>
            
            <div class="search-input-group">
                <i class="fas fa-calendar"></i>
                <input type="date" name="check_in" value="<?php echo htmlspecialchars($check_in); ?>" min="<?php echo date('Y-m-d'); ?>" class="search-input" placeholder="Check in">
            </div>
            
            <div class="search-input-group">
                <i class="fas fa-calendar"></i>
                <input type="date" name="check_out" value="<?php echo htmlspecialchars($check_out); ?>" min="<?php echo date('Y-m-d'); ?>" class="search-input" placeholder="Check out">
            </div>
            
            <div class="search-input-group">
                <i class="fas fa-users"></i>
                <input type="number" name="guests" value="<?php echo htmlspecialchars($guests); ?>" min="1" class="search-input" placeholder="Guests">
            </div>
            
            <button type="submit" class="search-btn">
                <i class="fas fa-search"></i>
                <span>Search</span>
            </button>
        </form>
    </div>
</div>

<!-- Main Content -->
<div class="container">
    <?php if (!empty($location) || !empty($check_in) || !empty($check_out)): ?>
        <div class="active-filters">
            <span style="font-weight: 600; color: var(--dark-color); font-size: 14px;">Active Filters:</span>
            
            <?php if (!empty($location)): ?>
                <div class="filter-badge">
                    <span>Location: <?php echo htmlspecialchars($location); ?></span>
                    <button onclick="removeFilter('location')"><i class="fas fa-times"></i></button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($check_in)): ?>
                <div class="filter-badge">
                    <span>Check-in: <?php echo htmlspecialchars($check_in); ?></span>
                    <button onclick="removeFilter('check_in')"><i class="fas fa-times"></i></button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($check_out)): ?>
                <div class="filter-badge">
                    <span>Check-out: <?php echo htmlspecialchars($check_out); ?></span>
                    <button onclick="removeFilter('check_out')"><i class="fas fa-times"></i></button>
                </div>
            <?php endif; ?>
            
            <a href="<?php echo SITE_URL; ?>/guest/browse.php" style="color: var(--primary-color); font-size: 13px; font-weight: 600; text-decoration: none; padding: 6px 12px;">
                Clear all
            </a>
        </div>
    <?php endif; ?>
    
    <!-- Explore Nearby Section - Show only when no active search -->
    <?php if (!empty($cities) && empty($location) && empty($check_in)): ?>
    <div class="explore-nearby-section">
        <div class="section-header">
            <h2>Explore nearby</h2>
            <p>Popular destinations close to you</p>
        </div>
        
        <div class="cities-grid">
            <?php foreach ($cities as $city): ?>
                <a href="?location=<?php echo urlencode($city['city']); ?>" class="city-card">
                    <?php if ($city['city_image']): ?>
                        <img src="<?php echo SITE_URL . '/' . htmlspecialchars($city['city_image']); ?>" 
                             alt="<?php echo htmlspecialchars($city['city']); ?>" 
                             class="city-image">
                    <?php else: ?>
                        <div class="city-image" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 32px;">
                            <i class="fas fa-city"></i>
                        </div>
                    <?php endif; ?>
                    <div class="city-info">
                        <div class="city-name"><?php echo htmlspecialchars($city['city']); ?>, <?php echo htmlspecialchars($city['country']); ?></div>
                        <div class="city-distance"><?php echo $city['listing_count']; ?> stays available</div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Listings Section -->
    <h1 class="page-title">
        <?php 
        if (!empty($location)) {
            echo "Stays in " . htmlspecialchars($location);
        } else {
            echo "Explore places to stay";
        }
        ?>
    </h1>
    
    <?php if (empty($listings)): ?>
        <div style="text-align: center; padding: 60px 20px; background: white; border-radius: 12px; margin-top: 20px;">
            <i class="fas fa-search" style="font-size: 48px; color: var(--gray-400); margin-bottom: 16px;"></i>
            <h3 style="font-size: 20px; font-weight: 600; margin-bottom: 8px; color: var(--dark-color);">No listings found</h3>
            <p style="color: var(--gray-500); margin-bottom: 20px;">Try adjusting your search criteria</p>
            <a href="<?php echo SITE_URL; ?>/guest/browse.php" class="btn btn-primary">Clear Filters</a>
        </div>
    <?php else: ?>
        <div class="cards-grid">
            <?php foreach ($listings as $listing): ?>
                <div class="listing-card" onclick="location.href='listing_details.php?id=<?php echo $listing['listing_id']; ?>'">
                    <div class="listing-card-image">
                        <?php if ($listing['primary_photo']): ?>
                            <img src="<?php echo SITE_URL . '/' . htmlspecialchars($listing['primary_photo']); ?>" 
                                 alt="<?php echo htmlspecialchars($listing['title']); ?>">
                        <?php else: ?>
                            <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); display: flex; align-items: center; justify-content: center; color: #999;">
                                <i class="fas fa-home" style="font-size: 48px;"></i>
                            </div>
                        <?php endif; ?>
                        
                        <?php $in_wishlist = in_array($listing['listing_id'], $wishlist_items); ?>
                        <button class="listing-card-wishlist" onclick="toggleWishlist(<?php echo $listing['listing_id']; ?>, this, event)">
                            <i class="<?php echo $in_wishlist ? 'fas' : 'far'; ?> fa-heart" style="color: <?php echo $in_wishlist ? 'var(--primary-color)' : 'var(--gray-600)'; ?>"></i>
                        </button>
                    </div>
                    
                    <div class="listing-card-content">
                        <h3 class="listing-card-title"><?php echo htmlspecialchars($listing['title']); ?></h3>
                        <div class="listing-card-location">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo htmlspecialchars($listing['city'] . ', ' . $listing['country']); ?></span>
                        </div>
                        
                        <div class="listing-card-footer">
                            <div class="listing-card-rating">
                                <?php if ($listing['avg_rating']): ?>
                                    <i class="fas fa-star"></i>
                                    <span class="rating-value"><?php echo number_format($listing['avg_rating'], 1); ?></span>
                                    <span class="review-count">(<?php echo $listing['review_count']; ?>)</span>
                                <?php else: ?>
                                    <span style="color: var(--gray-500); font-size: 11px;">New</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="listing-card-price">
                                <span class="price-amount"><?php echo formatCurrency($listing['price_per_night']); ?></span>
                                <span class="price-unit"> /night</span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function toggleWishlist(listingId, btn, event) {
    event.preventDefault();
    event.stopPropagation();
    
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
            const icon = btn.querySelector('i');
            if (data.action === 'added') {
                icon.classList.remove('far');
                icon.classList.add('fas');
                icon.style.color = 'var(--primary-color)';
            } else {
                icon.classList.remove('fas');
                icon.classList.add('far');
                icon.style.color = 'var(--gray-600)';
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function removeFilter(filterName) {
    const url = new URL(window.location.href);
    url.searchParams.delete(filterName);
    window.location.href = url.toString();
}
</script>

<?php include '../includes/footer.php'; ?>
