<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Dream Destination Stays - Marketplace'; ?></title>
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
            <button class="mobile-menu-toggle" onclick="toggleMobileSidebar()">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </header>

    <!-- Mobile Sidebar Overlay -->
    <div class="mobile-sidebar-overlay" id="mobileSidebarOverlay" onclick="closeMobileSidebar()"></div>

    <!-- Mobile Sidebar -->
    <div class="mobile-sidebar" id="mobileSidebar">
        <div class="mobile-sidebar-header">
            <button class="mobile-sidebar-close" onclick="closeMobileSidebar()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <?php if (isLoggedIn()): ?>
            <div class="mobile-user-info">
                <div class="mobile-user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="mobile-user-details">
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></div>
                    <div class="user-role">
                        <?php 
                        if (isAdmin()) echo 'Admin';
                        elseif (isHost()) echo 'Host';
                        else echo 'Guest';
                        ?>
                    </div>
                </div>
            </div>

            <nav class="mobile-nav-items">
                <?php if (isAdmin()): ?>
                    <a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="mobile-nav-item">
                        <i class="fas fa-chart-line"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="<?php echo SITE_URL; ?>/admin/users.php" class="mobile-nav-item">
                        <i class="fas fa-users"></i>
                        <span>Users</span>
                    </a>
                    <a href="<?php echo SITE_URL; ?>/admin/listings.php" class="mobile-nav-item">
                        <i class="fas fa-list"></i>
                        <span>Listings</span>
                    </a>
                    <a href="<?php echo SITE_URL; ?>/admin/bookings.php" class="mobile-nav-item">
                        <i class="fas fa-calendar-check"></i>
                        <span>Bookings</span>
                    </a>
                    <a href="<?php echo SITE_URL; ?>/admin/payouts.php" class="mobile-nav-item">
                        <i class="fas fa-money-check-alt"></i>
                        <span>Payouts</span>
                    </a>
                    <a href="<?php echo SITE_URL; ?>/admin/transactions.php" class="mobile-nav-item">
                        <i class="fas fa-exchange-alt"></i>
                        <span>Transactions</span>
                    </a>
                <?php elseif (isHost()): ?>
                    <a href="<?php echo SITE_URL; ?>/host/dashboard.php" class="mobile-nav-item">
                        <i class="fas fa-chart-line"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="<?php echo SITE_URL; ?>/host/listings.php" class="mobile-nav-item">
                        <i class="fas fa-home"></i>
                        <span>My Listings</span>
                    </a>
                    <a href="<?php echo SITE_URL; ?>/host/bookings.php" class="mobile-nav-item">
                        <i class="fas fa-calendar"></i>
                        <span>Bookings</span>
                    </a>
                    <a href="<?php echo SITE_URL; ?>/host/earnings.php" class="mobile-nav-item">
                        <i class="fas fa-dollar-sign"></i>
                        <span>Earnings</span>
                    </a>
                    <a href="<?php echo SITE_URL; ?>/host/payouts.php" class="mobile-nav-item">
                        <i class="fas fa-wallet"></i>
                        <span>Payouts</span>
                    </a>
                <?php elseif (isGuest()): ?>
                    <a href="<?php echo SITE_URL; ?>/guest/browse.php" class="mobile-nav-item">
                        <i class="fas fa-search"></i>
                        <span>Browse</span>
                    </a>
                    <a href="<?php echo SITE_URL; ?>/guest/wishlist.php" class="mobile-nav-item">
                        <i class="fas fa-heart"></i>
                        <span>Wishlist</span>
                    </a>
                    <a href="<?php echo SITE_URL; ?>/guest/bookings.php" class="mobile-nav-item">
                        <i class="fas fa-calendar"></i>
                        <span>My Bookings</span>
                    </a>
                    <a href="<?php echo SITE_URL; ?>/guest/checkout.php" class="mobile-nav-item">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Check Out</span>
                    </a>
                    <a href="<?php echo SITE_URL; ?>/guest/balance.php" class="mobile-nav-item">
                        <i class="fas fa-wallet"></i>
                        <span>Balance</span>
                    </a>
                <?php endif; ?>
            </nav>

            <div class="mobile-sidebar-footer">
                <a href="<?php echo SITE_URL; ?>/logout.php" class="mobile-logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        <?php else: ?>
            <nav class="mobile-nav-items">
                <a href="<?php echo SITE_URL; ?>/login.php" class="mobile-nav-item">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Login</span>
                </a>
                <a href="<?php echo SITE_URL; ?>/register.php" class="mobile-nav-item">
                    <i class="fas fa-user-plus"></i>
                    <span>Register</span>
                </a>
            </nav>
        <?php endif; ?>
    </div>

    <!-- Desktop Navbar (original) -->
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <a href="<?php echo SITE_URL; ?>">
                    <i class="fas fa-home"></i>
                    <?php echo SITE_NAME; ?>
                </a>
            </div>
            <div class="nav-menu">
                <?php if (isLoggedIn()): ?>
                    <?php if (isAdmin()): ?>
                        <a href="<?php echo SITE_URL; ?>/admin/dashboard.php">Dashboard</a>
                        <a href="<?php echo SITE_URL; ?>/admin/users.php">Users</a>
                        <a href="<?php echo SITE_URL; ?>/admin/listings.php">Listings</a>
                        <a href="<?php echo SITE_URL; ?>/admin/bookings.php">Bookings</a>
                        <a href="<?php echo SITE_URL; ?>/admin/payouts.php">Payouts</a>
                        <a href="<?php echo SITE_URL; ?>/admin/transactions.php">Transactions</a>
                    <?php elseif (isHost()): ?>
                        <a href="<?php echo SITE_URL; ?>/host/dashboard.php">Dashboard</a>
                        <a href="<?php echo SITE_URL; ?>/host/listings.php">My Listings</a>
                        <a href="<?php echo SITE_URL; ?>/host/bookings.php">Bookings</a>
                        <a href="<?php echo SITE_URL; ?>/host/earnings.php">Earnings</a>
                        <a href="<?php echo SITE_URL; ?>/host/payouts.php">Payouts</a>
                    <?php elseif (isGuest()): ?>
                        <a href="<?php echo SITE_URL; ?>/guest/browse.php">Browse</a>
                        <a href="<?php echo SITE_URL; ?>/guest/wishlist.php"><i class="fas fa-heart"></i> Wishlist</a>
                        <a href="<?php echo SITE_URL; ?>/guest/bookings.php">My Bookings</a>
                        <a href="<?php echo SITE_URL; ?>/guest/checkout.php"><i class="fas fa-sign-out-alt"></i> Check Out</a>
                        <a href="<?php echo SITE_URL; ?>/guest/balance.php">Balance</a>
                    <?php endif; ?>
                    <div class="user-menu">
                        <span class="user-name">
                            <i class="fas fa-user-circle"></i>
                            <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?>
                        </span>
                        <a href="<?php echo SITE_URL; ?>/logout.php" class="btn-logout">Logout</a>
                    </div>
                <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>/login.php">Login</a>
                    <a href="<?php echo SITE_URL; ?>/register.php">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <main class="main-content">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php 
                echo htmlspecialchars($_SESSION['success_message']); 
                unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <?php 
                echo htmlspecialchars($_SESSION['error_message']); 
                unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>

    <script>
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('mobileSidebar');
            const overlay = document.getElementById('mobileSidebarOverlay');
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        function closeMobileSidebar() {
            const sidebar = document.getElementById('mobileSidebar');
            const overlay = document.getElementById('mobileSidebarOverlay');
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        }
    </script>
