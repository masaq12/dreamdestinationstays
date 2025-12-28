<?php
require_once '../config/config.php';
require_once '../config/database.php';

if (!isAdmin() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/admin/dashboard.php');
}

try {
    $pdo = getPDOConnection();
    $pdo->beginTransaction();
    
    $booking_id = (int)$_POST['booking_id'];
    
    // Get booking details
    $stmt = $pdo->prepare("
        SELECT b.*, l.host_id, e.status as escrow_status
        FROM bookings b
        JOIN listings l ON b.listing_id = l.listing_id
        LEFT JOIN escrow e ON b.booking_id = e.booking_id
        WHERE b.booking_id = ?
    ");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        throw new Exception('Booking not found');
    }
    
    if ($booking['payment_status'] === 'completed') {
        throw new Exception('Payment already released');
    }
    
    if ($booking['escrow_status'] !== 'held') {
        throw new Exception('No funds in escrow for this booking');
    }
    
    // Update booking status
    $stmt = $pdo->prepare("
        UPDATE bookings 
        SET booking_status = 'completed',
            payment_status = 'completed',
            updated_at = NOW()
        WHERE booking_id = ?
    ");
    $stmt->execute([$booking_id]);
    
    // Release escrow
    $stmt = $pdo->prepare("
        UPDATE escrow 
        SET status = 'released',
            released_at = NOW(),
            release_reason = 'Manual admin release',
            released_by = ?
        WHERE booking_id = ? AND status = 'held'
    ");
    $stmt->execute([$_SESSION['user_id'], $booking_id]);
    
    // Calculate host earnings
    $platform_fee = $booking['service_fee'];
    $host_earnings = $booking['total_amount'] - $platform_fee;
    
    // Update guest balance - remove pending hold
    $stmt = $pdo->prepare("
        UPDATE guest_balances 
        SET pending_holds = pending_holds - ? 
        WHERE user_id = ?
    ");
    $stmt->execute([$booking['total_amount'], $booking['guest_id']]);
    
    // Update host balance - move from pending to available
    $stmt = $pdo->prepare("
        UPDATE host_balances 
        SET available_balance = available_balance + ?,
            pending_balance = pending_balance - ?,
            total_earned = total_earned + ?,
            platform_fees_paid = platform_fees_paid + ?
        WHERE user_id = ?
    ");
    $stmt->execute([
        $host_earnings,
        $host_earnings,
        $host_earnings,
        $platform_fee,
        $booking['host_id']
    ]);
    
    // Get host balance for transaction
    $stmt = $pdo->prepare("SELECT available_balance FROM host_balances WHERE user_id = ?");
    $stmt->execute([$booking['host_id']]);
    $host_new_balance = $stmt->fetchColumn();
    
    // Record transaction for host
    $stmt = $pdo->prepare("
        INSERT INTO transactions (
            user_id, transaction_type, amount, balance_before, balance_after,
            reference_type, reference_id, description
        ) VALUES (?, 'earning', ?, ?, ?, 'booking', ?, ?)
    ");
    $stmt->execute([
        $booking['host_id'],
        $host_earnings,
        $host_new_balance - $host_earnings,
        $host_new_balance,
        $booking_id,
        'Earning from booking #' . $booking_id . ' (admin release)'
    ]);
    
    // Log admin action
    $stmt = $pdo->prepare("
        INSERT INTO admin_actions (admin_id, action_type, target_type, target_id, description)
        VALUES (?, 'release_payment', 'booking', ?, ?)
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $booking_id,
        'Manually released payment for booking #' . $booking_id
    ]);
    
    $pdo->commit();
    
    $_SESSION['success_message'] = 'Payment released successfully. Host balance has been updated.';
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error_message'] = $e->getMessage();
}

redirect($_SERVER['HTTP_REFERER'] ?? SITE_URL . '/admin/bookings.php');
?>
