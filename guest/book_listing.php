<?php
require_once '../config/config.php';
require_once '../config/database.php';

if (!isGuest() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/guest/browse.php');
}

try {
    $pdo = getPDOConnection();
    $pdo->beginTransaction();
    
    $guest_id = $_SESSION['user_id'];
    $listing_id = (int)$_POST['listing_id'];
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];
    $num_guests = (int)$_POST['num_guests'];
    $payment_credential_id = (int)$_POST['payment_credential_id'];
    $num_nights = (int)$_POST['num_nights'];
    $total_amount = (float)$_POST['total_amount'];
    
    // Get listing details
    $stmt = $pdo->prepare("SELECT * FROM listings WHERE listing_id = ? AND status = 'active'");
    $stmt->execute([$listing_id]);
    $listing = $stmt->fetch();
    
    if (!$listing) {
        throw new Exception('Listing not found or inactive');
    }
    
    // Verify payment credential belongs to user and is active
    $stmt = $pdo->prepare("SELECT * FROM payment_credentials WHERE credential_id = ? AND user_id = ? AND status = 'active'");
    $stmt->execute([$payment_credential_id, $guest_id]);
    $credential = $stmt->fetch();
    
    if (!$credential) {
        throw new Exception('Invalid or inactive payment credential');
    }
    
    // Check if dates are available
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM bookings 
        WHERE listing_id = ? 
        AND booking_status IN ('confirmed', 'checked_in')
        AND (
            (check_in <= ? AND check_out >= ?)
            OR (check_in <= ? AND check_out >= ?)
            OR (check_in >= ? AND check_out <= ?)
        )
    ");
    $stmt->execute([$listing_id, $check_in, $check_in, $check_out, $check_out, $check_in, $check_out]);
    $conflicting_bookings = $stmt->fetchColumn();
    
    if ($conflicting_bookings > 0) {
        throw new Exception('Selected dates are not available');
    }
    
    // Get guest balance
    $stmt = $pdo->prepare("SELECT current_balance FROM guest_balances WHERE user_id = ?");
    $stmt->execute([$guest_id]);
    $guest_balance = $stmt->fetchColumn();
    
    // Check if guest has sufficient balance
    if ($guest_balance < $total_amount) {
        throw new Exception('Insufficient balance. Please add funds to your account.');
    }
    
    // Calculate pricing
    $nightly_rate = $listing['price_per_night'];
    $cleaning_fee = $listing['cleaning_fee'];
    $nights_total = $nightly_rate * $num_nights;
    $service_fee = $nights_total * ($listing['service_fee_percent'] / 100);
    $subtotal = $nights_total + $cleaning_fee + $service_fee;
    $tax_amount = $subtotal * 0.10; // 10% tax
    $calculated_total = $subtotal + $tax_amount;
    
    // Verify total amount
    if (abs($calculated_total - $total_amount) > 0.01) {
        throw new Exception('Price mismatch. Please refresh and try again.');
    }
    
    // Create booking
    $stmt = $pdo->prepare("
        INSERT INTO bookings (
            listing_id, guest_id, host_id, check_in, check_out, num_guests, num_nights,
            nightly_rate, cleaning_fee, service_fee, tax_amount, total_amount,
            payment_credential_id, booking_status, payment_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending')
    ");
    $stmt->execute([
        $listing_id, $guest_id, $listing['host_id'], $check_in, $check_out, $num_guests, $num_nights,
        $nightly_rate, $cleaning_fee, $service_fee, $tax_amount, $total_amount,
        $payment_credential_id
    ]);
    $booking_id = $pdo->lastInsertId();
    
    // Deduct from guest balance
    $new_balance = $guest_balance - $total_amount;
    $stmt = $pdo->prepare("UPDATE guest_balances SET current_balance = ?, pending_holds = pending_holds + ?, total_spent = total_spent + ? WHERE user_id = ?");
    $stmt->execute([$new_balance, $total_amount, $total_amount, $guest_id]);
    
    // Record transaction
    $stmt = $pdo->prepare("
        INSERT INTO transactions (user_id, transaction_type, amount, balance_before, balance_after, reference_type, reference_id, description)
        VALUES (?, 'deduction', ?, ?, ?, 'booking', ?, ?)
    ");
    $stmt->execute([$guest_id, $total_amount, $guest_balance, $new_balance, $booking_id, 'Payment for booking #' . $booking_id]);
    
    // Place funds in escrow
    $stmt = $pdo->prepare("INSERT INTO escrow (booking_id, amount, status) VALUES (?, ?, 'held')");
    $stmt->execute([$booking_id, $total_amount]);
    
    // Update booking status
    $stmt = $pdo->prepare("UPDATE bookings SET booking_status = 'confirmed', payment_status = 'held' WHERE booking_id = ?");
    $stmt->execute([$booking_id]);
    
    // Update host pending balance
    $host_earnings = $total_amount - $service_fee;
    $stmt = $pdo->prepare("UPDATE host_balances SET pending_balance = pending_balance + ? WHERE user_id = ?");
    $stmt->execute([$host_earnings, $listing['host_id']]);
    
    $pdo->commit();
    
    $_SESSION['success_message'] = 'Booking confirmed! Your payment has been secured in escrow.';
    redirect(SITE_URL . '/guest/bookings.php');
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error_message'] = $e->getMessage();
    redirect(SITE_URL . '/guest/listing_details.php?id=' . ($listing_id ?? 0));
}
?>
