<?php
require_once '../config/config.php';
require_once '../config/database.php';

if (!isAdmin()) {
    redirect(SITE_URL . '/login.php');
}

$pageTitle = 'All Listings - Admin - Dream Destination Stays';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $pdo = getPDOConnection();
        $listing_id = $_POST['listing_id'] ?? 0;
        
        if ($_POST['action'] === 'suspend') {
            $stmt = $pdo->prepare("UPDATE listings SET status = 'suspended' WHERE listing_id = ?");
            $stmt->execute([$listing_id]);
            $_SESSION['success_message'] = 'Listing suspended successfully';
        } elseif ($_POST['action'] === 'activate') {
            $stmt = $pdo->prepare("UPDATE listings SET status = 'active' WHERE listing_id = ?");
            $stmt->execute([$listing_id]);
            $_SESSION['success_message'] = 'Listing activated successfully';
        } elseif ($_POST['action'] === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM listings WHERE listing_id = ?");
            $stmt->execute([$listing_id]);
            $_SESSION['success_message'] = 'Listing deleted successfully';
        }
        
        redirect($_SERVER['PHP_SELF']);
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Error updating listing: ' . $e->getMessage();
    }
}

try {
    $pdo = getPDOConnection();
    
    // Filter options
    $status_filter = $_GET['status'] ?? '';
    $search = $_GET['search'] ?? '';
    
    // Build query
    $query = "
        SELECT l.*, u.full_name as host_name, u.email as host_email,
        (SELECT COUNT(*) FROM bookings WHERE listing_id = l.listing_id) as total_bookings
        FROM listings l
        JOIN users u ON l.host_id = u.user_id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($status_filter) {
        $query .= " AND l.status = ?";
        $params[] = $status_filter;
    }
    
    if ($search) {
        $query .= " AND (l.title LIKE ? OR l.city LIKE ? OR u.full_name LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    $query .= " ORDER BY l.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $listings = $stmt->fetchAll();
    
    // Get stats
    $stats = [
        'total' => $pdo->query("SELECT COUNT(*) FROM listings")->fetchColumn(),
        'active' => $pdo->query("SELECT COUNT(*) FROM listings WHERE status = 'active'")->fetchColumn(),
        'inactive' => $pdo->query("SELECT COUNT(*) FROM listings WHERE status = 'inactive'")->fetchColumn(),
        'suspended' => $pdo->query("SELECT COUNT(*) FROM listings WHERE status = 'suspended'")->fetchColumn(),
    ];
    
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Error loading listings';
    $listings = [];
    $stats = ['total' => 0, 'active' => 0, 'inactive' => 0, 'suspended' => 0];
}

include '../includes/header.php';
?>

<div class="container">
    <h1 style="margin-bottom: 30px;">
        <i class="fas fa-home"></i> All Listings
    </h1>
    
    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <i class="fas fa-home"></i>
            <div class="stat-value"><?php echo number_format($stats['total']); ?></div>
            <div class="stat-label">Total Listings</div>
        </div>
        <div class="stat-card success">
            <i class="fas fa-check-circle"></i>
            <div class="stat-value"><?php echo number_format($stats['active']); ?></div>
            <div class="stat-label">Active</div>
        </div>
        <div class="stat-card warning">
            <i class="fas fa-pause-circle"></i>
            <div class="stat-value"><?php echo number_format($stats['inactive']); ?></div>
            <div class="stat-label">Inactive</div>
        </div>
        <div class="stat-card secondary">
            <i class="fas fa-ban"></i>
            <div class="stat-value"><?php echo number_format($stats['suspended']); ?></div>
            <div class="stat-label">Suspended</div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card">
        <form method="GET" class="grid grid-3" style="gap: 15px;">
            <div class="form-group">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Title, city, or host..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="">All Statuses</option>
                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="suspended" <?php echo $status_filter === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                </select>
            </div>
            <div style="display: flex; align-items: flex-end; gap: 10px;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-outline">Clear</a>
            </div>
        </form>
    </div>
    
    <!-- Listings Table -->
    <div class="card">
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Host</th>
                        <th>Location</th>
                        <th>Price/Night</th>
                        <th>Bookings</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($listings)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; color: #666; padding: 40px;">
                                No listings found
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($listings as $listing): ?>
                            <tr>
                                <td><?php echo $listing['listing_id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($listing['title']); ?></strong>
                                    <br>
                                    <small style="color: #666;"><?php echo htmlspecialchars($listing['property_type']); ?></small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($listing['host_name']); ?>
                                    <br>
                                    <small style="color: #666;"><?php echo htmlspecialchars($listing['host_email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($listing['city'] . ', ' . $listing['country']); ?></td>
                                <td><?php echo formatCurrency($listing['price_per_night']); ?></td>
                                <td><?php echo $listing['total_bookings']; ?></td>
                                <td>
                                    <?php
                                    $badge_class = 'badge-info';
                                    if ($listing['status'] === 'active') $badge_class = 'badge-success';
                                    if ($listing['status'] === 'suspended') $badge_class = 'badge-danger';
                                    if ($listing['status'] === 'inactive') $badge_class = 'badge-warning';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo ucfirst($listing['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($listing['created_at'])); ?></td>
                                <td>
                                    <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                        <a href="<?php echo SITE_URL; ?>/listing_public.php?id=<?php echo $listing['listing_id']; ?>" 
                                           class="btn btn-primary" style="padding: 5px 10px; font-size: 12px;" target="_blank">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <?php if ($listing['status'] === 'active'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="listing_id" value="<?php echo $listing['listing_id']; ?>">
                                                <input type="hidden" name="action" value="suspend">
                                                <button type="submit" class="btn btn-warning" style="padding: 5px 10px; font-size: 12px;"
                                                        onclick="return confirm('Suspend this listing?')">
                                                    <i class="fas fa-pause"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="listing_id" value="<?php echo $listing['listing_id']; ?>">
                                                <input type="hidden" name="action" value="activate">
                                                <button type="submit" class="btn btn-success" style="padding: 5px 10px; font-size: 12px;"
                                                        onclick="return confirm('Activate this listing?')">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="listing_id" value="<?php echo $listing['listing_id']; ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;"
                                                    onclick="return confirm('Delete this listing? This cannot be undone!')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
