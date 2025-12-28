<?php
require_once '../config/config.php';
require_once '../config/database.php';

if (!isHost()) {
    redirect(SITE_URL . '/login.php');
}

$pageTitle = 'Add New Listing - Dream Destination Stays';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getPDOConnection();
        
        // Sanitize inputs
        $title = sanitizeInput($_POST['title']);
        $description = sanitizeInput($_POST['description']);
        $property_type = sanitizeInput($_POST['property_type']);
        $address = sanitizeInput($_POST['address']);
        $city = sanitizeInput($_POST['city']);
        $state = sanitizeInput($_POST['state']);
        $country = sanitizeInput($_POST['country']);
        $zipcode = sanitizeInput($_POST['zipcode']);
        $price_per_night = (float)$_POST['price_per_night'];
        $cleaning_fee = (float)$_POST['cleaning_fee'];
        $max_guests = (int)$_POST['max_guests'];
        $bedrooms = (int)$_POST['bedrooms'];
        $beds = (int)$_POST['beds'];
        $bathrooms = (float)$_POST['bathrooms'];
        $house_rules = sanitizeInput($_POST['house_rules']);
        $amenities = sanitizeInput($_POST['amenities']);
        
        // Validate
        if (empty($title) || empty($city) || empty($country) || $price_per_night <= 0) {
            throw new Exception('Please fill in all required fields');
        }
        
        // Insert listing
        $stmt = $pdo->prepare("
            INSERT INTO listings (
                host_id, title, description, property_type, address, city, state, country, zipcode,
                price_per_night, cleaning_fee, max_guests, bedrooms, beds, bathrooms,
                house_rules, amenities, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
        ");
        
        $stmt->execute([
            $_SESSION['user_id'], $title, $description, $property_type, $address, $city, $state, $country, $zipcode,
            $price_per_night, $cleaning_fee, $max_guests, $bedrooms, $beds, $bathrooms,
            $house_rules, $amenities
        ]);
        
        $listing_id = $pdo->lastInsertId();
        
        $_SESSION['success_message'] = 'Listing created successfully! Now add some photos.';
        redirect(SITE_URL . '/host/manage_photos.php?id=' . $listing_id);
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
}

include '../includes/header.php';
?>

<div class="container">
    <h1 style="margin-bottom: 30px;">
        <i class="fas fa-plus"></i> Add New Listing
    </h1>
    
    <div class="card" style="max-width: 900px; margin: 0 auto;">
        <form method="POST" action="">
            
            <!-- Basic Information -->
            <h2 style="margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid var(--border-color);">
                <i class="fas fa-info-circle"></i> Basic Information
            </h2>
            
            <div class="form-group">
                <label class="form-label" for="title">Property Title *</label>
                <input type="text" id="title" name="title" class="form-control" required 
                       placeholder="Beautiful Apartment in Downtown">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="description">Description</label>
                <textarea id="description" name="description" class="form-control" rows="5" 
                          placeholder="Describe your property..."></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="property_type">Property Type</label>
                <select id="property_type" name="property_type" class="form-control">
                    <option value="Apartment">Apartment</option>
                    <option value="House">House</option>
                    <option value="Villa">Villa</option>
                    <option value="Condo">Condo</option>
                    <option value="Studio">Studio</option>
                    <option value="Loft">Loft</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            
            <!-- Location -->
            <h2 style="margin: 30px 0 20px; padding-bottom: 10px; border-bottom: 2px solid var(--border-color);">
                <i class="fas fa-map-marker-alt"></i> Location
            </h2>
            
            <div class="form-group">
                <label class="form-label" for="address">Street Address</label>
                <input type="text" id="address" name="address" class="form-control" 
                       placeholder="123 Main Street">
            </div>
            
            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label" for="city">City *</label>
                    <input type="text" id="city" name="city" class="form-control" required 
                           placeholder="New York">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="state">State / Province</label>
                    <input type="text" id="state" name="state" class="form-control" 
                           placeholder="NY">
                </div>
            </div>
            
            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label" for="country">Country *</label>
                    <input type="text" id="country" name="country" class="form-control" required 
                           placeholder="USA">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="zipcode">Zip / Postal Code</label>
                    <input type="text" id="zipcode" name="zipcode" class="form-control" 
                           placeholder="10001">
                </div>
            </div>
            
            <!-- Property Details -->
            <h2 style="margin: 30px 0 20px; padding-bottom: 10px; border-bottom: 2px solid var(--border-color);">
                <i class="fas fa-bed"></i> Property Details
            </h2>
            
            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label" for="max_guests">Maximum Guests *</label>
                    <input type="number" id="max_guests" name="max_guests" class="form-control" 
                           min="1" required value="2">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="bedrooms">Bedrooms *</label>
                    <input type="number" id="bedrooms" name="bedrooms" class="form-control" 
                           min="0" required value="1">
                </div>
            </div>
            
            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label" for="beds">Beds *</label>
                    <input type="number" id="beds" name="beds" class="form-control" 
                           min="1" required value="1">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="bathrooms">Bathrooms *</label>
                    <input type="number" id="bathrooms" name="bathrooms" class="form-control" 
                           min="0.5" step="0.5" required value="1">
                </div>
            </div>
            
            <!-- Pricing -->
            <h2 style="margin: 30px 0 20px; padding-bottom: 10px; border-bottom: 2px solid var(--border-color);">
                <i class="fas fa-dollar-sign"></i> Pricing
            </h2>
            
            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label" for="price_per_night">Price per Night * ($)</label>
                    <input type="number" id="price_per_night" name="price_per_night" class="form-control" 
                           min="1" step="0.01" required data-currency>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="cleaning_fee">Cleaning Fee ($)</label>
                    <input type="number" id="cleaning_fee" name="cleaning_fee" class="form-control" 
                           min="0" step="0.01" value="0" data-currency>
                </div>
            </div>
            
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Note:</strong> Platform service fee (15%) and taxes (10%) will be added automatically at checkout.
            </div>
            
            <!-- Additional Information -->
            <h2 style="margin: 30px 0 20px; padding-bottom: 10px; border-bottom: 2px solid var(--border-color);">
                <i class="fas fa-list"></i> Additional Information
            </h2>
            
            <div class="form-group">
                <label class="form-label" for="amenities">Amenities</label>
                <textarea id="amenities" name="amenities" class="form-control" rows="3" 
                          placeholder="WiFi, Kitchen, Parking, Pool, etc."></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="house_rules">House Rules</label>
                <textarea id="house_rules" name="house_rules" class="form-control" rows="4" 
                          placeholder="No smoking, No pets, Check-in after 3pm, etc."></textarea>
            </div>
            
            <!-- Submit -->
            <div style="margin-top: 30px; display: flex; gap: 15px; justify-content: flex-end;">
                <a href="listings.php" class="btn btn-outline">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Create Listing
                </button>
            </div>
            
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
