<?php
require_once '../config/config.php';
require_once '../config/database.php';

if (!isAdmin()) {
    redirect(SITE_URL . '/login.php');
}

$pageTitle = 'All Bookings - Admin - Dream Destination Stays';

// Handle booking actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $pdo = getPDOConnection();
        $booking_id = $_POST['booking_id'] ?? 0;
        
        if ($_POST['action'] === 'cancel') {
            $stmt = $pdo->prepare("UPDATE bookings SET booking_status = 'cancelled' WHERE booking_id = ?");
            $stmt->execute([$booking_id]);
            $_SESSION['success_message'] = 'Booking cancelled successfully';
        } elseif ($_POST['action'] === 'refund') {
            // Process refund
            $pdo->beginTransaction();
            
            // Get booking details
            $stmt = $pdo->prepare("SELECT * FROM bookings WHERE booking_id = ?");
            $stmt->execute([$booking_id]);
            $booking = $stmt->fetch();
            
            if ($booking) {
                // Update booking status
                $stmt = $pdo->prepare("UPDATE bookings SET booking_status = 'refunded', payment_status = 'refunded' WHERE booking_id = ?");
                $stmt->execute([$booking_id]);
                
                // Refund to guest balance
                $stmt = $pdo->prepare("SELECT current_balance FROM guest_balances WHERE user_id = ?");
                $stmt->execute([$booking['guest_id']]);
                $guest_balance = $stmt->fetch();
                
                $old_balance = $guest_balance['current_balance'];
                $new_balance = $old_balance + $booking['total_amount'];
                
                $stmt = $pdo->prepare("UPDATE guest_balances SET current_balance = ? WHERE user_id = ?");
                $stmt->execute([$new_balance, $booking['guest_id']]);
                
                // Record transaction
                $stmt = $pdo->prepare("
                    INSERT INTO transactions (user_id, transaction_type, amount, balance_before, balance_after, reference_type, reference_id, description)
                    VALUES (?, 'refund', ?, ?, ?, 'booking', ?, ?)
                ");
                $stmt->execute([
                    $booking['guest_id'],
                    $booking['total_amount'],
                    $old_balance,
                    $new_balance,
                    $booking_id,
                    'Refund for booking #' . $booking_id
                ]);
                
                // Update escrow
                $stmt = $pdo->prepare("UPDATE escrow SET status = 'refunded' WHERE booking_id = ?");
                $stmt->execute([$booking_id]);
            }
            
            $pdo->commit();
            $_SESSION['success_message'] = 'Booking refunded successfully';
        }
        
        redirect($_SERVER['PHP_SELF']);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error_message'] = 'Error processing action: ' . $e->getMessage();
    }
}

try {
    $pdo = getPDOConnection();
    
    // Filter options
    $status_filter = $_GET['status'] ?? '';
    $search = $_GET['search'] ?? '';
    
    // Build query
    $query = "
        SELECT b.*, 
        l.title as listing_title,
        g.full_name as guest_name, g.email as guest_email,
        h.full_name as host_name, h.email as host_email
        FROM bookings b
        JOIN listings l ON b.listing_id = l.listing_id
        JOIN users g ON b.guest_id = g.user_id
        JOIN users h ON b.host_id = h.user_id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($status_filter) {
        $query .= " AND b.booking_status = ?";
        $params[] = $status_filter;
    }
    
    if ($search) {
        $query .= " AND (l.title LIKE ? OR g.full_name LIKE ? OR h.full_name LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    $query .= " ORDER BY b.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll();
    
    // Get stats
    $stats = [
        'total' => $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
        'confirmed' => $pdo->query("SELECT COUNT(*) FROM bookings WHERE booking_status = 'confirmed'")->fetchColumn(),
        'completed' => $pdo->query("SELECT COUNT(*) FROM bookings WHERE booking_status = 'completed'")->fetchColumn(),
        'cancelled' => $pdo->query("SELECT COUNT(*) FROM bookings WHERE booking_status = 'cancelled'")->fetchColumn(),
        'total_revenue' => $pdo->query("SELECT SUM(total_amount) FROM bookings WHERE booking_status IN ('confirmed', 'completed')")->fetchColumn() ?? 0,
    ];
    
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Error loading bookings';
    $bookings = [];
    $stats = ['total' => 0, 'confirmed' => 0, 'completed' => 0, 'cancelled' => 0, 'total_revenue' => 0];
}

include '../includes/header.php';
?>

<div class="container">
    <h1 style="margin-bottom: 30px;">
        <i class="fas fa-calendar-check"></i> All Bookings
    </h1>
    
    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <i class="fas fa-calendar"></i>
            <div class="stat-value"><?php echo number_format($stats['total']); ?></div>
            <div class="stat-label">Total Bookings</div>
        </div>
        <div class="stat-card success">
            <i class="fas fa-check-circle"></i>
            <div class="stat-value"><?php echo number_format($stats['confirmed']); ?></div>
            <div class="stat-label">Confirmed</div>
        </div>
        <div class="stat-card secondary">
            <i class="fas fa-flag-checkered"></i>
            <div class="stat-value"><?php echo number_format($stats['completed']); ?></div>
            <div class="stat-label">Completed</div>
        </div>
        <div class="stat-card warning">
            <i class="fas fa-dollar-sign"></i>
            <div class="stat-value"><?php echo formatCurrency($stats['total_revenue']); ?></div>
            <div class="stat-label">Total Revenue</div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card">
        <form method="GET" class="grid grid-3" style="gap: 15px;">
            <div class="form-group">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Listing, guest, or host..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="">All Statuses</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                    <option value="checked_in" <?php echo $status_filter === 'checked_in' ? 'selected' : ''; ?>>Checked In</option>
                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    <option value="refunded" <?php echo $status_filter === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                </select>
            </div>
            <div style="display: flex; align-items: flex-end; gap: 10px;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-outline">Clear</a>
            </div>
        </form>
    </div>
    
    <!-- Bookings Table -->
    <div class="card">
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Listing</th>
                        <th>Guest</th>
                        <th>Host</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Nights</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Booked</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bookings)): ?>
                        <tr>
                            <td colspan="12" style="text-align: center; color: #666; padding: 40px;">
                                No bookings found
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><strong>#<?php echo $booking['booking_id']; ?></strong></td>
                                <td>
                                    <?php echo htmlspecialchars($booking['listing_title']); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($booking['guest_name']); ?>
                                    <br>
                                    <small style="color: #666;"><?php echo htmlspecialchars($booking['guest_email']); ?></small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($booking['host_name']); ?>
                                    <br>
                                    <small style="color: #666;"><?php echo htmlspecialchars($booking['host_email']); ?></small>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($booking['check_in'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($booking['check_out'])); ?></td>
                                <td><?php echo $booking['num_nights']; ?></td>
                                <td><strong><?php echo formatCurrency($booking['total_amount']); ?></strong></td>
                                <td>
                                    <?php
                                    $badge_class = 'badge-info';
                                    if ($booking['booking_status'] === 'confirmed') $badge_class = 'badge-success';
                                    if ($booking['booking_status'] === 'completed') $badge_class = 'badge-success';
                                    if ($booking['booking_status'] === 'cancelled') $badge_class = 'badge-danger';
                                    if ($booking['booking_status'] === 'refunded') $badge_class = 'badge-warning';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo ucfirst($booking['booking_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $payment_badge = 'badge-info';
                                    if ($booking['payment_status'] === 'completed') $payment_badge = 'badge-success';
                                    if ($booking['payment_status'] === 'refunded') $payment_badge = 'badge-warning';
                                    ?>
                                    <span class="badge <?php echo $payment_badge; ?>">
                                        <?php echo ucfirst($booking['payment_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($booking['created_at'])); ?></td>
                                <td>
                                    <div style="display: flex; gap: 5px; flex-direction: column;">
                                        <?php if ($booking['booking_status'] === 'confirmed'): ?>
                                            <form method="POST">
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                                <input type="hidden" name="action" value="cancel">
                                                <button type="submit" class="btn btn-warning" style="padding: 5px 10px; font-size: 12px; width: 100%;"
                                                        onclick="return confirm('Cancel this booking?')">
                                                    <i class="fas fa-times"></i> Cancel
                                                </button>
                                            </form>
                                            <form method="POST">
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                                <input type="hidden" name="action" value="refund">
                                                <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 12px; width: 100%;"
                                                        onclick="return confirm('Refund this booking? Guest will receive full refund.')">
                                                    <i class="fas fa-undo"></i> Refund
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if (in_array($booking['booking_status'], ['confirmed', 'checked_in']) && $booking['payment_status'] === 'held'): ?>
                                            <form method="POST" action="release_payment.php">
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                                <button type="submit" class="btn btn-success" style="padding: 5px 10px; font-size: 12px; width: 100%;"
                                                        onclick="return confirm('Release payment to host? This action cannot be undone.')">
                                                    <i class="fas fa-unlock"></i> Release
                                                </button>
                                            </form>
                                        <?php endif; ?>
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
