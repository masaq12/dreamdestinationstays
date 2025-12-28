<?php
require_once '../config/config.php';
require_once '../config/database.php';

if (!isHost()) {
    redirect(SITE_URL . '/login.php');
}

$listing_id = $_GET['id'] ?? 0;
$pageTitle = 'Edit Listing - Host Dashboard';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getPDOConnection();
        
        // Verify ownership
        $stmt = $pdo->prepare("SELECT listing_id FROM listings WHERE listing_id = ? AND host_id = ?");
        $stmt->execute([$listing_id, $_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            throw new Exception('Unauthorized');
        }
        
        // Update listing
        $stmt = $pdo->prepare("
            UPDATE listings SET
                title = ?,
                description = ?,
                property_type = ?,
                address = ?,
                city = ?,
                state = ?,
                country = ?,
                zipcode = ?,
                price_per_night = ?,
                cleaning_fee = ?,
                max_guests = ?,
                bedrooms = ?,
                beds = ?,
                bathrooms = ?,
                house_rules = ?,
                amenities = ?,
                status = ?
            WHERE listing_id = ?
        ");
        
        $stmt->execute([
            sanitizeInput($_POST['title']),
            sanitizeInput($_POST['description']),
            sanitizeInput($_POST['property_type']),
            sanitizeInput($_POST['address']),
            sanitizeInput($_POST['city']),
            sanitizeInput($_POST['state']),
            sanitizeInput($_POST['country']),
            sanitizeInput($_POST['zipcode']),
            floatval($_POST['price_per_night']),
            floatval($_POST['cleaning_fee']),
            intval($_POST['max_guests']),
            intval($_POST['bedrooms']),
            intval($_POST['beds']),
            floatval($_POST['bathrooms']),
            sanitizeInput($_POST['house_rules']),
            sanitizeInput($_POST['amenities']),
            sanitizeInput($_POST['status']),
            $listing_id
        ]);
        
        $_SESSION['success_message'] = 'Listing updated successfully!';
        redirect(SITE_URL . '/host/listings.php');
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Error updating listing: ' . $e->getMessage();
    }
}

try {
    $pdo = getPDOConnection();
    
    // Get listing details
    $stmt = $pdo->prepare("SELECT * FROM listings WHERE listing_id = ? AND host_id = ?");
    $stmt->execute([$listing_id, $_SESSION['user_id']]);
    $listing = $stmt->fetch();
    
    if (!$listing) {
        $_SESSION['error_message'] = 'Listing not found';
        redirect(SITE_URL . '/host/listings.php');
    }
    
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Error loading listing';
    redirect(SITE_URL . '/host/listings.php');
}

include '../includes/header.php';
?>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h1><i class="fas fa-edit"></i> Edit Listing</h1>
        <div style="display: flex; gap: 10px;">
            <a href="preview_listing.php?id=<?php echo $listing_id; ?>" class="btn btn-outline" target="_blank">
                <i class="fas fa-eye"></i> Preview
            </a>
            <a href="manage_photos.php?id=<?php echo $listing_id; ?>" class="btn btn-secondary">
                <i class="fas fa-images"></i> Manage Photos
            </a>
            <a href="listings.php" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>
    
    <form method="POST" class="card">
        <h2 style="margin-bottom: 20px;">Listing Information</h2>
        
        <!-- Basic Info -->
        <div class="form-group">
            <label class="form-label">Title *</label>
            <input type="text" name="title" class="form-control" required value="<?php echo htmlspecialchars($listing['title']); ?>">
        </div>
        
        <div class="form-group">
            <label class="form-label">Description *</label>
            <textarea name="description" class="form-control" rows="6" required><?php echo htmlspecialchars($listing['description']); ?></textarea>
        </div>
        
        <div class="grid grid-2">
            <div class="form-group">
                <label class="form-label">Property Type *</label>
                <select name="property_type" class="form-control" required>
                    <option value="">Select type</option>
                    <option value="Apartment" <?php echo $listing['property_type'] === 'Apartment' ? 'selected' : ''; ?>>Apartment</option>
                    <option value="House" <?php echo $listing['property_type'] === 'House' ? 'selected' : ''; ?>>House</option>
                    <option value="Villa" <?php echo $listing['property_type'] === 'Villa' ? 'selected' : ''; ?>>Villa</option>
                    <option value="Condo" <?php echo $listing['property_type'] === 'Condo' ? 'selected' : ''; ?>>Condo</option>
                    <option value="Cabin" <?php echo $listing['property_type'] === 'Cabin' ? 'selected' : ''; ?>>Cabin</option>
                    <option value="Cottage" <?php echo $listing['property_type'] === 'Cottage' ? 'selected' : ''; ?>>Cottage</option>
                    <option value="Loft" <?php echo $listing['property_type'] === 'Loft' ? 'selected' : ''; ?>>Loft</option>
                    <option value="Other" <?php echo $listing['property_type'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Status *</label>
                <select name="status" class="form-control" required>
                    <option value="active" <?php echo $listing['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $listing['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
        </div>
        
        <!-- Location -->
        <h3 style="margin-top: 30px; margin-bottom: 20px;">Location</h3>
        
        <div class="form-group">
            <label class="form-label">Address *</label>
            <input type="text" name="address" class="form-control" required value="<?php echo htmlspecialchars($listing['address']); ?>">
        </div>
        
        <div class="grid grid-2">
            <div class="form-group">
                <label class="form-label">City *</label>
                <input type="text" name="city" class="form-control" required value="<?php echo htmlspecialchars($listing['city']); ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">State/Province *</label>
                <input type="text" name="state" class="form-control" required value="<?php echo htmlspecialchars($listing['state']); ?>">
            </div>
        </div>
        
        <div class="grid grid-2">
            <div class="form-group">
                <label class="form-label">Country *</label>
                <input type="text" name="country" class="form-control" required value="<?php echo htmlspecialchars($listing['country']); ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">Zipcode</label>
                <input type="text" name="zipcode" class="form-control" value="<?php echo htmlspecialchars($listing['zipcode'] ?? ''); ?>">
            </div>
        </div>
        
        <!-- Pricing -->
        <h3 style="margin-top: 30px; margin-bottom: 20px;">Pricing</h3>
        
        <div class="grid grid-2">
            <div class="form-group">
                <label class="form-label">Price Per Night ($) *</label>
                <input type="number" name="price_per_night" class="form-control" step="0.01" min="0" required value="<?php echo $listing['price_per_night']; ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">Cleaning Fee ($)</label>
                <input type="number" name="cleaning_fee" class="form-control" step="0.01" min="0" value="<?php echo $listing['cleaning_fee']; ?>">
            </div>
        </div>
        
        <!-- Property Details -->
        <h3 style="margin-top: 30px; margin-bottom: 20px;">Property Details</h3>
        
        <div class="grid grid-4">
            <div class="form-group">
                <label class="form-label">Max Guests *</label>
                <input type="number" name="max_guests" class="form-control" min="1" required value="<?php echo $listing['max_guests']; ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">Bedrooms *</label>
                <input type="number" name="bedrooms" class="form-control" min="0" required value="<?php echo $listing['bedrooms']; ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">Beds *</label>
                <input type="number" name="beds" class="form-control" min="0" required value="<?php echo $listing['beds']; ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">Bathrooms *</label>
                <input type="number" name="bathrooms" class="form-control" step="0.5" min="0" required value="<?php echo $listing['bathrooms']; ?>">
            </div>
        </div>
        
        <!-- Amenities -->
        <div class="form-group">
            <label class="form-label">Amenities (comma-separated)</label>
            <textarea name="amenities" class="form-control" rows="3" placeholder="WiFi, Kitchen, Parking, Pool, Air Conditioning, Heating, Washer, Dryer, TV, etc."><?php echo htmlspecialchars($listing['amenities'] ?? ''); ?></textarea>
            <small class="form-text">Separate each amenity with a comma</small>
        </div>
        
        <!-- House Rules -->
        <div class="form-group">
            <label class="form-label">House Rules</label>
            <textarea name="house_rules" class="form-control" rows="4" placeholder="No smoking, No pets, Check-in after 3 PM, etc."><?php echo htmlspecialchars($listing['house_rules'] ?? ''); ?></textarea>
        </div>
        
        <div style="display: flex; gap: 10px; margin-top: 30px;">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Changes
            </button>
            <a href="listings.php" class="btn btn-outline">
                Cancel
            </a>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
