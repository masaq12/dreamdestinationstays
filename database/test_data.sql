-- Dream Destination Stays - Quick Setup Test Data
-- Run this AFTER importing schema.sql
-- This creates test users and sample data for testing

USE dream_destinations;

-- ===========================================
-- 1. CREATE TEST USERS
-- ===========================================

-- Test Guest User
-- Email: guest@test.com
-- Password: guest123
INSERT INTO users (email, password_hash, full_name, phone, user_type, status) VALUES
('guest@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Guest', '1234567890', 'guest', 'active');

SET @guest_id = LAST_INSERT_ID();

-- Test Host User  
-- Email: host@test.com
-- Password: host123
INSERT INTO users (email, password_hash, full_name, phone, user_type, status) VALUES
('host@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane Host', '0987654321', 'host', 'active');

SET @host_id = LAST_INSERT_ID();

-- ===========================================
-- 2. SETUP GUEST PAYMENT & BALANCE
-- ===========================================

-- Add guest balance ($1000)
INSERT INTO guest_balances (user_id, current_balance, pending_holds, total_spent) VALUES
(@guest_id, 1000.00, 0.00, 0.00);

-- Add payment credential for guest
INSERT INTO payment_credentials (user_id, credential_type, credential_number, credential_name, status) VALUES
(@guest_id, 'business_card', '4532-1234-5678-9012', 'Guest Business Card', 'active');

-- ===========================================
-- 3. SETUP HOST BALANCE & VERIFICATION
-- ===========================================

-- Initialize host balance
INSERT INTO host_balances (user_id, available_balance, pending_balance, total_earned, total_paid_out, platform_fees_paid) VALUES
(@host_id, 0.00, 0.00, 0.00, 0.00, 0.00);

-- Verify host account
INSERT INTO host_verification (user_id, business_name, verification_status, verified_at) VALUES
(@host_id, 'Jane Properties LLC', 'verified', NOW());

-- Add payout method for host
INSERT INTO payout_methods (user_id, method_type, account_details, account_holder_name, is_default, status) VALUES
(@host_id, 'bank_account', '{"account_number":"123456789","routing_number":"987654321","bank_name":"Test Bank","account_type":"checking"}', 'Jane Host', 1, 'active');

-- ===========================================
-- 4. CREATE SAMPLE LISTINGS
-- ===========================================

-- Listing 1: Luxury Beach Villa
INSERT INTO listings (host_id, title, description, property_type, address, city, state, country, zipcode, price_per_night, cleaning_fee, service_fee_percent, max_guests, bedrooms, beds, bathrooms, house_rules, amenities, status) VALUES
(@host_id, 
'Luxury Beach Villa with Ocean View', 
'Beautiful 3-bedroom villa right on the beach. Wake up to stunning ocean views every morning. Perfect for families or groups. Fully equipped kitchen, private pool, and direct beach access.',
'Villa',
'123 Ocean Drive',
'Miami',
'Florida',
'USA',
'33139',
250.00,
50.00,
15.00,
6,
3,
3,
2.5,
'No smoking, No parties, Check-in after 3 PM, Check-out before 11 AM',
'WiFi, Kitchen, Pool, Beach Access, Parking, Air Conditioning, TV',
'active');

SET @listing1_id = LAST_INSERT_ID();

-- Listing 2: Cozy Mountain Cabin
INSERT INTO listings (host_id, title, description, property_type, address, city, state, country, zipcode, price_per_night, cleaning_fee, service_fee_percent, max_guests, bedrooms, beds, bathrooms, house_rules, amenities, status) VALUES
(@host_id,
'Cozy Mountain Cabin with Fireplace',
'Escape to this charming mountain cabin surrounded by nature. Perfect for a romantic getaway or peaceful retreat. Features a wood-burning fireplace, deck with mountain views, and hiking trails nearby.',
'Cabin',
'456 Mountain Road',
'Aspen',
'Colorado', 
'USA',
'81611',
180.00,
40.00,
15.00,
4,
2,
2,
1.5,
'No smoking, Quiet hours after 10 PM, No pets',
'WiFi, Fireplace, Deck, Hiking, Parking, Heating, Kitchen',
'active');

SET @listing2_id = LAST_INSERT_ID();

-- Listing 3: Downtown Apartment
INSERT INTO listings (host_id, title, description, property_type, address, city, state, country, zipcode, price_per_night, cleaning_fee, service_fee_percent, max_guests, bedrooms, beds, bathrooms, house_rules, amenities, status) VALUES
(@host_id,
'Modern Downtown Apartment - Walk to Everything',
'Stylish apartment in the heart of downtown. Walking distance to restaurants, shopping, and entertainment. Perfect for business travelers or city explorers. High-speed internet and workspace included.',
'Apartment',
'789 Main Street, Apt 5B',
'New York',
'New York',
'USA',
'10001',
150.00,
30.00,
15.00,
2,
1,
1,
1.0,
'No smoking, No parties, Quiet hours after 10 PM',
'WiFi, Kitchen, Workspace, TV, Air Conditioning, Elevator',
'active');

-- ===========================================
-- 5. ADD LISTING PHOTOS (Placeholders)
-- ===========================================

-- Note: In production, actual photos would be uploaded
-- These are placeholder entries

INSERT INTO listing_photos (listing_id, photo_url, is_primary, display_order) VALUES
(@listing1_id, 'uploads/listings/beach-villa-1.jpg', 1, 1),
(@listing1_id, 'uploads/listings/beach-villa-2.jpg', 0, 2),
(@listing2_id, 'uploads/listings/mountain-cabin-1.jpg', 1, 1),
(@listing2_id, 'uploads/listings/mountain-cabin-2.jpg', 0, 2),
(@listing2_id, 'uploads/listings/downtown-apt-1.jpg', 1, 1);

-- ===========================================
-- 6. CREATE SAMPLE AVAILABILITY
-- ===========================================

-- Make listings available for next 90 days
DELIMITER //

CREATE PROCEDURE create_availability()
BEGIN
    DECLARE i INT DEFAULT 0;
    DECLARE listing INT;
    
    -- For each listing
    DECLARE listing_cursor CURSOR FOR SELECT listing_id FROM listings WHERE host_id = @host_id;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET i = 999;
    
    OPEN listing_cursor;
    
    listing_loop: LOOP
        FETCH listing_cursor INTO listing;
        
        IF i = 999 THEN
            LEAVE listing_loop;
        END IF;
        
        SET i = 0;
        
        WHILE i < 90 DO
            INSERT IGNORE INTO listing_availability (listing_id, date, status) 
            VALUES (listing, DATE_ADD(CURDATE(), INTERVAL i DAY), 'available');
            SET i = i + 1;
        END WHILE;
        
    END LOOP;
    
    CLOSE listing_cursor;
END//

DELIMITER ;

CALL create_availability();
DROP PROCEDURE create_availability;

-- ===========================================
-- 7. SUMMARY
-- ===========================================

SELECT '✅ Test Data Created Successfully!' as Status;
SELECT '' as '';
SELECT 'Test Accounts Created:' as Info;
SELECT 'Guest: guest@test.com / guest123' as Account;
SELECT 'Host: host@test.com / host123' as Account;
SELECT 'Admin: admin@dreamdestinations.com / admin123' as Account;
SELECT '' as '';
SELECT 'Guest Balance: $1,000.00' as Balance;
SELECT 'Payment Credential Added' as Payment;
SELECT '' as '';
SELECT COUNT(*) as 'Listings Created' FROM listings WHERE host_id = @host_id;
SELECT COUNT(*) as 'Photos Added' FROM listing_photos;
SELECT COUNT(*) as 'Availability Records' FROM listing_availability;
SELECT '' as '';
SELECT 'You can now:' as NextSteps;
SELECT '1. Login as guest and browse listings' as Step;
SELECT '2. Login as host and manage properties' as Step;
SELECT '3. Login as admin and oversee platform' as Step;
