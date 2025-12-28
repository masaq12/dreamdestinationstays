<?php
require_once '../config/config.php';
require_once '../config/database.php';

if (!isGuest() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/guest/bookings.php');
}

try {
    $pdo = getPDOConnection();
    $pdo->beginTransaction();
    
    $booking_id = (int)$_POST['booking_id'];
    $guest_id = $_SESSION['user_id'];
    
    // Verify booking belongs to guest and is eligible for check-in
    $stmt = $pdo->prepare("
        SELECT b.* 
        FROM bookings b
        WHERE b.booking_id = ? 
        AND b.guest_id = ? 
        AND b.booking_status = 'confirmed'
        AND b.check_in <= CURDATE()
    ");
    $stmt->execute([$booking_id, $guest_id]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        throw new Exception('Invalid booking or check-in not available yet');
    }
    
    // Update booking status to checked_in
    $stmt = $pdo->prepare("
        UPDATE bookings 
        SET booking_status = 'checked_in', 
            updated_at = NOW()
        WHERE booking_id = ?
    ");
    $stmt->execute([$booking_id]);
    
    $pdo->commit();
    
    $_SESSION['success_message'] = 'Check-in successful! Enjoy your stay.';
    redirect(SITE_URL . '/guest/bookings.php');
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error_message'] = $e->getMessage();
    redirect(SITE_URL . '/guest/bookings.php');
}
?>
