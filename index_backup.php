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
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/airbnb-style.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/mobile-style.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Additional styles for the new design */
        .public-header {
            background-color: #ffffff;
            border-bottom: 1px solid var(--gray-200);
            padding: 12px 16px;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .public-header-content {
            max-width: 1760px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .public-logo {
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            cursor: pointer;
        }

        .public-logo i {
            font-size: 28px;
            color: var(--primary-color);
        }

        .public-logo-text {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }

        .public-logo-text .brand-line1,
        .public-logo-text .brand-line2 {
            font-size: 16px;
            font-weight: 700;
            color: var(--primary-color);
        }

        .public-header-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .public-header-actions .btn {
            padding: 8px 16px;
            font-size: 13px;
            white-space: nowrap;
        }

        .btn-host {
            background-color: transparent;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }

        .btn-host:hover {
            background-color: var(--primary-color);
            color: white;
        }

        /* Hero Section - Enhanced */
        .hero-section {
            position: relative;
            height: 60vh;
            min-height: 400px;
            background: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.5)), 
                        url('https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1920') center/cover;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-bottom: 0;
        }

        .hero-content {
            text-align: center;
            max-width: 700px;
            padding: 0 20px;
        }

        .hero-content h1 {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 12px;
            line-height: 1.2;
        }

        .hero-content p {
            font-size: 18px;
            margin-bottom: 0;
            opacity: 0.95;
        }

        /* Search Section - Matches guest/browse.php */
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

        /* Active Filters */
        .active-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 16px;
            padding: 0 16px;
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

        /* Section Styling */
        .explore-nearby-section,
        .listings-section {
            padding: 32px 16px;
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

        /* Cities Grid */
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

        /* Listings Grid */
        .listings-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
        }

        .listing-card {
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: box-shadow 0.2s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .listing-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .listing-card-image {
            position: relative;
            height: 192px;
            overflow: hidden;
        }

        .listing-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .listing-card-content {
            padding: 12px;
        }

        .listing-card-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .listing-card-location {
            font-size: 12px;
            color: var(--gray-500);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .listing-card-location i {
            font-size: 12px;
        }

        .listing-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .listing-card-rating {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
        }

        .listing-card-rating i {
            color: #FBBF24;
            font-size: 12px;
        }

        .listing-card-rating .rating-value {
            font-weight: 600;
        }

        .listing-card-rating .review-count {
            color: var(--gray-500);
        }

        .listing-card-price {
            text-align: right;
        }

        .listing-card-price .price-amount {
            font-weight: 700;
            color: var(--dark-color);
            font-size: 14px;
        }

        .listing-card-price .price-unit {
            font-size: 12px;
            color: var(--gray-500);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            margin: 20px 0;
        }

        .empty-state i {
            font-size: 48px;
            color: var(--gray-400);
            margin-bottom: 16px;
        }

        .empty-state h3 {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--dark-color);
        }

        .empty-state p {
            color: var(--gray-500);
            margin-bottom: 20px;
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 48px 16px;
            text-align: center;
            margin-top: 32px;
        }

        .cta-card h2 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .cta-card p {
            font-size: 16px;
            margin-bottom: 24px;
            opacity: 0.95;
        }

        .cta-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-cta-primary,
        .btn-cta-secondary {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s;
            display: inline-block;
        }

        .btn-cta-primary {
            background: white;
            color: #667eea;
        }

        .btn-cta-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .btn-cta-secondary {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid white;
        }

        .btn-cta-secondary:hover {
            background: rgba(255,255,255,0.3);
        }

        /* Footer */
        .footer-public {
            background: var(--gray-100);
            border-top: 1px solid var(--gray-200);
            padding: 32px 16px 16px;
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }

        .footer-section h4 {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 12px;
            color: var(--dark-color);
        }

        .footer-links {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .footer-links a {
            font-size: 13px;
            color: var(--gray-600);
            text-decoration: none;
            transition: color 0.2s;
        }

        .footer-links a:hover {
            color: var(--primary-color);
        }

        .footer-bottom {
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding-top: 16px;
            border-top: 1px solid var(--gray-200);
            font-size: 12px;
            color: var(--gray-500);
            text-align: center;
        }

        .footer-social {
            display: flex;
            gap: 16px;
            justify-content: center;
        }

        .footer-social a {
            color: var(--gray-600);
            font-size: 18px;
            transition: color 0.2s;
        }

        .footer-social a:hover {
            color: var(--primary-color);
        }

        /* Responsive Design */
        @media (min-width: 640px) {
            .search-form-grid {
                grid-template-columns: 2fr 1fr 1fr 1fr auto;
                align-items: end;
            }
            
            .search-btn {
                width: auto;
                padding: 12px 24px;
            }
            
            .cities-grid,
            .listings-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .hero-content h1 {
                font-size: 48px;
            }

            .hero-content p {
                font-size: 20px;
            }

            .footer-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .footer-bottom {
                flex-direction: row;
                justify-content: space-between;
            }
        }

        @media (min-width: 1024px) {
            .public-header-content {
                padding: 0 40px;
            }

            .hero-section {
                height: 70vh;
                min-height: 600px;
            }

            .hero-content h1 {
                font-size: 56px;
            }

            .hero-content p {
                font-size: 24px;
            }

            .explore-nearby-section,
            .listings-section {
                padding: 48px 40px;
            }

            .section-header h2 {
                font-size: 32px;
            }

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

            .listings-grid {
                grid-template-columns: repeat(3, 1fr);
            }

            .footer-grid {
                grid-template-columns: repeat(3, 1fr);
            }

            .cta-section {
                padding: 64px 40px;
            }

            .cta-card h2 {
                font-size: 40px;
            }

            .cta-card p {
                font-size: 20px;
            }
        }

        @media (min-width: 1280px) {
            .listings-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }
    </style>
</head>
<body>
    <!-- Public Header -->
    <header class="public-header">
        <div class="public-header-content">
            <a href="<?php echo SITE_URL; ?>" class="public-logo">
                <i class="fas fa-home"></i>
                <div class="public-logo-text">
                    <span class="brand-line1">Dream Destination</span>
                    <span class="brand-line2">Stays</span>
                </div>
            </a>
            
            <div class="public-header-actions">
                <a href="<?php echo SITE_URL; ?>/register.php?type=host" class="btn btn-host">
                    Become a Host
                </a>
                <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-primary">
                    Sign In
                </a>
            </div>
        </div>
    </header>

    <?php if (empty($location) && empty($check_in) && empty($check_out)): ?>
    <!-- Hero Section - Only show when no search -->
    <section class="hero-section">
        <div class="hero-content">
            <h1>Not sure where to go? Perfect.</h1>
            <p>Explore unique stays around the world</p>
        </div>
    </section>
    <?php endif; ?>

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
    <?php if (!empty($location) || !empty($check_in) || !empty($check_out)): ?>
        <div class="active-filters">
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

    <!-- Explore Nearby Cities - Show only when no active search -->
    <?php if (!empty($cities) && empty($location) && empty($check_in)): ?>
    <section class="explore-nearby-section">
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
    </section>
    <?php endif; ?>

    <!-- Featured Listings -->
    <section class="listings-section">
        <div class="section-header">
            <h2>
                <?php 
                if (!empty($location)) {
                    echo "Stays in " . htmlspecialchars($location);
                } else {
                    echo "Explore places to stay";
                }
                ?>
            </h2>
            <?php if (empty($location) && empty($check_in) && empty($check_out)): ?>
                <p><?php echo count($listings); ?> featured stays</p>
            <?php endif; ?>
        </div>
        
        <?php if (empty($listings)): ?>
            <div class="empty-state">
                <i class="fas fa-search"></i>
                <h3>No stays found</h3>
                <p>Try adjusting your search criteria or explore other destinations</p>
                <a href="<?php echo SITE_URL; ?>" class="btn btn-primary">
                    Clear Filters
                </a>
            </div>
        <?php else: ?>
            <div class="listings-grid">
                <?php foreach ($listings as $listing): ?>
                    <a href="<?php echo SITE_URL; ?>/listing_public.php?id=<?php echo $listing['listing_id']; ?>" 
                       class="listing-card">
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
                            <div class="listing-car