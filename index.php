<?php
require_once 'config/config.php';
require_once 'config/database.php';

$pageTitle = 'Dream Destinations - Book unique stays worldwide';

// Check if already logged in and redirect appropriately
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect(SITE_URL . '/admin/dashboard.php');
    } elseif (isHost()) {
        redirect(SITE_URL . '/host/dashboard.php');
    } elseif (isGuest()) {
        redirect(SITE_URL . '/guest/browse.php');
    }
}

try {
    $pdo = getPDOConnection();
    
    // Get search parameters
    $location = $_GET['location'] ?? '';
    $check_in = $_GET['check_in'] ?? '';
    $check_out = $_GET['check_out'] ?? '';
    $guests = $_GET['guests'] ?? 1;
    
    // Get popular cities with listing counts
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
              (SELECT AVG(rating) FROM reviews r JOIN bookings b ON r.booking_id = b.booking_id 
               WHERE b.listing_id = l.listing_id AND r.review_type = 'guest_to_host') as avg_rating,
              (SELECT COUNT(*) FROM reviews r JOIN bookings b ON r.booking_id = b.booking_id 
               WHERE b.listing_id = l.listing_id AND r.review_type = 'guest_to_host') as review_count
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
    
    $query .= " ORDER BY l.created_at DESC LIMIT 12";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $listings = $stmt->fetchAll();
    
} catch (Exception $e) {
    $cities = [];
    $listings = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/airbnb-style.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/mobile-style.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Mobile Header -->
    <header class="mobile-header">
        <div class="mobile-header-content">
            <a href="<?php echo SITE_URL; ?>" class="mobile-logo">
                <i class="fas fa-home"></i>
                <div class="mobile-logo-text">
                    <span class="brand-line1">Dream Destination</span>
                    <span class="brand-line2">Stays</span>
                </div>
            </a>
            <div style="display: flex; gap: 8px;">
                <a href="<?php echo SITE_URL; ?>/register.php?type=host" style="display: none; color: var(--primary-color); text-decoration: none; font-size: 13px; padding: 8px 12px; font-weight: 600;">
                    Host
                </a>
                <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-primary" style="font-size: 13px; padding: 8px 16px;">
                    Sign In
                </a>
            </div>
        </div>
    </header>

    <!-- Desktop Navbar -->
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <a href="<?php echo SITE_URL; ?>">
                    <i class="fas fa-home"></i>
                    <?php echo SITE_NAME; ?>
                </a>
            </div>
            <div class="nav-menu">
                <a href="<?php echo SITE_URL; ?>/login.php">Login</a>
                <a href="<?php echo SITE_URL; ?>/register.php">Register</a>
            </div>
        </div>
    </nav>

    <?php if (empty($location) && empty($check_in) && empty($check_out)): ?>
    <!-- Hero Section -->
    <section class="hero-airbnb">
        <div class="hero-content">
            <h1>Not sure where to go? Perfect.</h1>
            <p>Explore unique stays around the world</p>
        </div>
    </section>
    <?php endif; ?>

    <!-- Search Section Below Header -->
    <div class="search-section">
        <div class="search-container">
            <form method="GET" action="" style="display: grid; grid-template-columns: 1fr; gap: 12px; background: white; padding: 16px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
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
            <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px;">
                <span style="font-weight: 600; color: var(--dark-color); font-size: 14px;">Active Filters:</span>
                
                <?php if (!empty($location)): ?>
                    <div class="filter-badge">
                        <span>Location: <?php echo htmlspecialchars($location); ?></span>
                        <button onclick="removeFilter('location')" type="button"><i class="fas fa-times"></i></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($check_in)): ?>
                    <div class="filter-badge">
                        <span>Check-in: <?php echo htmlspecialchars($check_in); ?></span>
                        <button onclick="removeFilter('check_in')" type="button"><i class="fas fa-times"></i></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($check_out)): ?>
                    <div class="filter-badge">
                        <span>Check-out: <?php echo htmlspecialchars($check_out); ?></span>
                        <button onclick="removeFilter('check_out')" type="button"><i class="fas fa-times"></i></button>
                    </div>
                <?php endif; ?>
                
                <a href="<?php echo SITE_URL; ?>" style="color: var(--primary-color); font-size: 13px; font-weight: 600; text-decoration: none; padding: 6px 12px;">
                    Clear all
                </a>
            </div>
        <?php endif; ?>
        
        <!-- Explore Nearby Section -->
        <?php if (!empty($cities) && empty($location) && empty($check_in)): ?>
        <div class="explore-nearby-section" style="margin-bottom: 32px;">
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
                <a href="<?php echo SITE_URL; ?>" class="btn btn-primary">Clear Filters</a>
            </div>
        <?php else: ?>
            <div class="cards-grid">
                <?php foreach ($listings as $listing): ?>
                    <div class="listing-card" onclick="location.href='listing_public.php?id=<?php echo $listing['listing_id']; ?>'">
                        <div class="listing-card-image">
                            <?php if ($listing['primary_photo']): ?>
                                <img src="<?php echo SITE_URL . '/' . htmlspecialchars($listing['primary_photo']); ?>" 
                                     alt="<?php echo htmlspecialchars($listing['title']); ?>">
                            <?php else: ?>
                                <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); display: flex; align-items: center; justify-content: center; color: #999;">
                                    <i class="fas fa-home" style="font-size: 48px;"></i>
                                </div>
                            <?php endif; ?>
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

   
    <!-- Footer -->
   <?php include 'includes/footer.php'; ?>

    <script>
        function removeFilter(filterName) {
            const url = new URL(window.location.href);
            url.searchParams.delete(filterName);
            window.location.href = url.toString();
        }

        // Responsive search form layout
        window.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.search-section form');
            function updateSearchLayout() {
                if (window.innerWidth >= 640) {
                    form.style.gridTemplateColumns = '2fr 1fr 1fr 1fr auto';
                    form.style.alignItems = 'end';
                } else {
                    form.style.gridTemplateColumns = '1fr';
                    form.style.alignItems = 'stretch';
                }
            }
            updateSearchLayout();
            window.addEventListener('resize', updateSearchLayout);
        });
    </script>
</body>
</html>
