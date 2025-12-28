<?php
require_once 'config/config.php';
require_once 'config/database.php';

$pageTitle = 'Register - Dream Destination Stays';

if (isLoggedIn()) {
    redirect(SITE_URL . '/index.php');
}

$error = '';
$success = '';
$user_type = $_GET['type'] ?? 'guest';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitizeInput($_POST['full_name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $user_type = $_POST['user_type'] ?? 'guest';
    
    if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all required fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        try {
            $pdo = getPDOConnection();
            
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email already registered';
            } else {
                // Insert user
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, full_name, phone, user_type) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$email, $password_hash, $full_name, $phone, $user_type]);
                $user_id = $pdo->lastInsertId();
                
                // Create balance record based on user type
                if ($user_type === 'guest') {
                    $stmt = $pdo->prepare("INSERT INTO guest_balances (user_id, current_balance) VALUES (?, 0.00)");
                    $stmt->execute([$user_id]);
                    
                    // Create a default payment credential
                    $credential_number = 'BC-' . strtoupper(generateRandomString(12));
                    $stmt = $pdo->prepare("INSERT INTO payment_credentials (user_id, credential_type, credential_number, credential_name, status, expiry_date) VALUES (?, 'business_card', ?, ?, 'active', DATE_ADD(CURDATE(), INTERVAL 3 YEAR))");
                    $stmt->execute([$user_id, $credential_number, $full_name]);
                    
                    $_SESSION['success_message'] = 'Registration successful! You have been credited with $1000.00 and a payment credential has been issued.';
                } elseif ($user_type === 'host') {
                    $stmt = $pdo->prepare("INSERT INTO host_balances (user_id) VALUES (?)");
                    $stmt->execute([$user_id]);
                    
                    $stmt = $pdo->prepare("INSERT INTO host_verification (user_id, verification_status) VALUES (?, 'pending')");
                    $stmt->execute([$user_id]);
                    
                    $_SESSION['success_message'] = 'Registration successful! Your host account is pending verification.';
                }
                
                redirect(SITE_URL . '/login.php');
            }
        } catch (Exception $e) {
            $error = 'An error occurred. Please try again.';
        }
    }
}

include 'includes/header.php';
?>

<div class="auth-container" style="max-width: 550px;">
    <div class="auth-card">
        <div class="auth-header">
            <h2><i class="fas fa-user-plus"></i> Create Your Account</h2>
            <p>Join Dream Destination Stays and start your journey</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">Account Type</label>
                <div style="display: flex; gap: 15px;">
                    <label style="flex: 1; cursor: pointer;">
                        <input type="radio" name="user_type" value="guest" <?php echo $user_type === 'guest' ? 'checked' : ''; ?> required>
                        <span style="margin-left: 5px;">Guest (Book stays)</span>
                    </label>
                    <label style="flex: 1; cursor: pointer;">
                        <input type="radio" name="user_type" value="host" <?php echo $user_type === 'host' ? 'checked' : ''; ?> required>
                        <span style="margin-left: 5px;">Host (List property)</span>
                    </label>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="full_name">Full Name *</label>
                <input type="text" id="full_name" name="full_name" class="form-control" required 
                       value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="email">Email Address *</label>
                <input type="email" id="email" name="email" class="form-control" required 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" class="form-control" 
                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Password * (min 6 characters)</label>
                <input type="password" id="password" name="password" class="form-control" required minlength="6">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="confirm_password">Confirm Password *</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="6">
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">
                <i class="fas fa-user-plus"></i> Create Account
            </button>
        </form>
        
        <div class="auth-link">
            <p>Already have an account? <a href="<?php echo SITE_URL; ?>/login.php">Login here</a></p>
        </div>
        
        <<!-- div style="margin-top: 20px; padding: 15px; background-color: #e8f5e9; border-radius: 8px;">
            <p style="margin: 0; font-size: 14px;"><strong>Guest Benefits:</strong></p>
            <p style="margin: 5px 0; font-size: 13px;">✓ $1000 starting balance</p>
            <p style="margin: 5px 0; font-size: 13px;">✓ Payment credentials issued automatically</p>
            <p style="margin: 5px 0; font-size: 13px;">✓ Instant booking capability</p>
        </div> -->
    </div>
</div>

<?php include 'includes/footer.php'; ?>
