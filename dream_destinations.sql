-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 24, 2025 at 09:13 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dream_destinations`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_actions`
--

CREATE TABLE `admin_actions` (
  `action_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action_type` varchar(100) NOT NULL,
  `target_type` varchar(50) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_actions`
--

INSERT INTO `admin_actions` (`action_id`, `admin_id`, `action_type`, `target_type`, `target_id`, `description`, `created_at`) VALUES
(1, 1, 'release_payment', 'booking', 3, 'Manually released payment for booking #3', '2025-12-24 07:35:50'),
(2, 1, 'release_payment', 'booking', 4, 'Manually released payment for booking #4', '2025-12-24 07:36:04');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL,
  `listing_id` int(11) NOT NULL,
  `guest_id` int(11) NOT NULL,
  `host_id` int(11) NOT NULL,
  `check_in` date NOT NULL,
  `check_out` date NOT NULL,
  `num_guests` int(11) NOT NULL,
  `num_nights` int(11) NOT NULL,
  `nightly_rate` decimal(10,2) NOT NULL,
  `cleaning_fee` decimal(10,2) DEFAULT 0.00,
  `service_fee` decimal(10,2) DEFAULT 0.00,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_credential_id` int(11) NOT NULL,
  `booking_status` enum('pending','confirmed','checked_in','completed','cancelled','refunded') DEFAULT 'pending',
  `payment_status` enum('pending','held','completed','refunded') DEFAULT 'pending',
  `cancellation_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `checked_out_at` timestamp NULL DEFAULT NULL,
  `auto_released` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `listing_id`, `guest_id`, `host_id`, `check_in`, `check_out`, `num_guests`, `num_nights`, `nightly_rate`, `cleaning_fee`, `service_fee`, `tax_amount`, `total_amount`, `payment_credential_id`, `booking_status`, `payment_status`, `cancellation_reason`, `created_at`, `updated_at`, `checked_out_at`, `auto_released`) VALUES
(1, 1, 3, 5, '2025-12-23', '2025-12-25', 3, 2, 250.00, 50.00, 75.00, 62.50, 687.50, 1, 'refunded', 'refunded', NULL, '2025-12-23 15:52:29', '2025-12-23 16:16:25', NULL, 0),
(2, 3, 3, 5, '2025-12-21', '2025-12-22', 1, 1, 150.00, 30.00, 22.50, 20.25, 222.75, 1, 'cancelled', 'held', NULL, '2025-12-23 15:56:19', '2025-12-23 16:16:31', NULL, 0),
(3, 3, 4, 5, '2025-12-22', '2025-12-23', 1, 1, 150.00, 30.00, 22.50, 20.25, 222.75, 2, 'completed', 'completed', NULL, '2025-12-23 16:12:06', '2025-12-24 07:35:49', NULL, 0),
(4, 5, 4, 5, '2025-12-24', '2025-12-25', 1, 1, 150.00, 50.00, 22.50, 22.25, 244.75, 2, 'completed', 'completed', NULL, '2025-12-24 07:25:46', '2025-12-24 07:36:04', NULL, 0),
(5, 3, 4, 5, '2025-12-24', '2025-12-25', 1, 1, 150.00, 30.00, 22.50, 20.25, 222.75, 2, 'checked_in', 'held', NULL, '2025-12-24 07:53:07', '2025-12-24 07:53:58', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `disputes`
--

CREATE TABLE `disputes` (
  `dispute_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `raised_by` int(11) NOT NULL,
  `dispute_type` enum('payment','property_condition','cancellation','other') NOT NULL,
  `description` text NOT NULL,
  `status` enum('open','investigating','resolved','closed') DEFAULT 'open',
  `resolution` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `escrow`
--

CREATE TABLE `escrow` (
  `escrow_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('held','released','refunded') DEFAULT 'held',
  `held_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `released_at` timestamp NULL DEFAULT NULL,
  `release_reason` varchar(100) DEFAULT NULL,
  `released_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `escrow`
--

INSERT INTO `escrow` (`escrow_id`, `booking_id`, `amount`, `status`, `held_at`, `released_at`, `release_reason`, `released_by`) VALUES
(1, 1, 687.50, 'refunded', '2025-12-23 15:52:29', NULL, NULL, NULL),
(2, 2, 222.75, 'held', '2025-12-23 15:56:19', NULL, NULL, NULL),
(3, 3, 222.75, 'released', '2025-12-23 16:12:06', '2025-12-24 07:35:50', 'Manual admin release', 1),
(4, 4, 244.75, 'released', '2025-12-24 07:25:46', '2025-12-24 07:36:04', 'Manual admin release', 1),
(5, 5, 222.75, 'held', '2025-12-24 07:53:07', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `guest_balances`
--

CREATE TABLE `guest_balances` (
  `balance_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `current_balance` decimal(10,2) DEFAULT 0.00,
  `pending_holds` decimal(10,2) DEFAULT 0.00,
  `total_spent` decimal(10,2) DEFAULT 0.00,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `guest_balances`
--

INSERT INTO `guest_balances` (`balance_id`, `user_id`, `current_balance`, `pending_holds`, `total_spent`, `updated_at`) VALUES
(1, 3, 778.25, 910.25, 910.25, '2025-12-23 16:17:10'),
(2, 4, 309.75, 222.75, 690.25, '2025-12-24 07:53:07');

-- --------------------------------------------------------

--
-- Table structure for table `host_balances`
--

CREATE TABLE `host_balances` (
  `balance_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `available_balance` decimal(10,2) DEFAULT 0.00,
  `pending_balance` decimal(10,2) DEFAULT 0.00,
  `total_earned` decimal(10,2) DEFAULT 0.00,
  `total_paid_out` decimal(10,2) DEFAULT 0.00,
  `platform_fees_paid` decimal(10,2) DEFAULT 0.00,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `host_balances`
--

INSERT INTO `host_balances` (`balance_id`, `user_id`, `available_balance`, `pending_balance`, `total_earned`, `total_paid_out`, `platform_fees_paid`, `updated_at`) VALUES
(1, 2, 0.00, 0.00, 0.00, 0.00, 0.00, '2025-12-23 09:38:49'),
(2, 5, 322.50, 1013.00, 422.50, 100.00, 45.00, '2025-12-24 07:53:07');

-- --------------------------------------------------------

--
-- Table structure for table `host_verification`
--

CREATE TABLE `host_verification` (
  `verification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `business_name` varchar(255) DEFAULT NULL,
  `verification_status` enum('pending','verified','rejected') DEFAULT 'pending',
  `verification_documents` text DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `host_verification`
--

INSERT INTO `host_verification` (`verification_id`, `user_id`, `business_name`, `verification_status`, `verification_documents`, `verified_at`, `created_at`) VALUES
(1, 2, NULL, 'pending', NULL, NULL, '2025-12-23 09:38:49'),
(2, 5, 'Jane Properties LLC', 'verified', NULL, '2025-12-23 11:14:46', '2025-12-23 11:14:46');

-- --------------------------------------------------------

--
-- Table structure for table `listings`
--

CREATE TABLE `listings` (
  `listing_id` int(11) NOT NULL,
  `host_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `property_type` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `zipcode` varchar(20) DEFAULT NULL,
  `price_per_night` decimal(10,2) NOT NULL,
  `cleaning_fee` decimal(10,2) DEFAULT 0.00,
  `service_fee_percent` decimal(5,2) DEFAULT 15.00,
  `max_guests` int(11) DEFAULT 1,
  `bedrooms` int(11) DEFAULT 1,
  `beds` int(11) DEFAULT 1,
  `bathrooms` decimal(3,1) DEFAULT 1.0,
  `house_rules` text DEFAULT NULL,
  `amenities` text DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `listings`
--

INSERT INTO `listings` (`listing_id`, `host_id`, `title`, `description`, `property_type`, `address`, `city`, `state`, `country`, `zipcode`, `price_per_night`, `cleaning_fee`, `service_fee_percent`, `max_guests`, `bedrooms`, `beds`, `bathrooms`, `house_rules`, `amenities`, `status`, `created_at`, `updated_at`) VALUES
(1, 5, 'Luxury Beach Villa with Ocean View', 'Beautiful 3-bedroom villa right on the beach. Wake up to stunning ocean views every morning. Perfect for families or groups. Fully equipped kitchen, private pool, and direct beach access.', 'Villa', '123 Ocean Drive', 'Miami', 'Florida', 'USA', '33139', 250.00, 50.00, 15.00, 6, 3, 3, 2.5, 'No smoking, No parties, Check-in after 3 PM, Check-out before 11 AM', 'WiFi, Kitchen, Pool, Beach Access, Parking, Air Conditioning, TV', 'active', '2025-12-23 11:14:46', '2025-12-23 11:14:46'),
(3, 5, 'Modern Downtown Apartment - Walk to Everything', 'Stylish apartment in the heart of downtown. Walking distance to restaurants, shopping, and entertainment. Perfect for business travelers or city explorers. High-speed internet and workspace included.', 'Apartment', '789 Main Street, Apt 5B', 'New York', 'New York', 'USA', '10001', 150.00, 30.00, 15.00, 2, 1, 1, 1.0, 'No smoking, No parties, Quiet hours after 10 PM', 'WiFi, Kitchen, Workspace, TV, Air Conditioning, Elevator', 'active', '2025-12-23 11:14:47', '2025-12-23 11:14:47'),
(4, 5, 'Urban abode - Cozy 1BHK with Pool &amp; Play Area', '(Please note: The apartment is on the first floor and has no elevator. There are mobile network issues)\r\n\r\nBeautiful fully furnished 1 BHK in Orlim on the first floor with a lovely view of green fields. Enjoy a peaceful stay with a pool, kids’ play area, and Wi-Fi. Perfect for couples or families looking for a cozy, comfortable getaway close to South Goa’s beaches and cafes.\r\nThe space\r\n🌿 Peaceful Location:\r\nLocated in Orlim, this cozy and beautifully designed 1 BHK is surrounded by lush green fields — perfect for a calm Goan getaway.\r\n\r\n🏠 Open Floor Plan:\r\nA bright, airy layout connects the living room, dining area and kitchen seamlessly.\r\n\r\n🛋️ Living Room:\r\n• Sofa bed\r\n• Smart TV\r\n• Air conditioning\r\n• Dining space\r\n\r\n🍳 Kitchen:\r\n• Fully equipped with fridge, stove, toaster, kettle, utensils &amp; cutlery (No Mixer but can be rented)\r\n• Ideal for home-cooked meals or quick bites\r\n\r\n🛏️ Bedroom:\r\n• Queen-sized bed\r\n• Air conditioning\r\n• Private balcony overlooking fields — perfect for morning coffee\r\n\r\n💦 Amenities:\r\n• Swimming pool &amp; kids’ play area\r\n• High-speed Wi-Fi\r\n• Private Parking\r\n• 24/7 security &amp; CCTV\r\n\r\n🌴 Ideal For:\r\nCouples, families, or remote workers looking for a comfortable, peaceful stay close to South Goa’s beaches and cafés.\r\nGuest access\r\nYou get a spacious 1 BHK. You have access to the pool. There is parking available. The area is very safe with zero crimes being reported.\r\nOther things to note\r\n🔒 Check-In &amp; Identification\r\n• ✅ All guests must share valid government-issued ID copies virtually before check-in.\r\n• ⛔ Check-in will not be permitted without this.\r\n\r\n🚷 Visitors &amp; Guest Policy\r\n• 🚫 Strictly no visitors allowed at any time. Only registered guests are allowed inside the apartment and premises.\r\n• 🚫 Parties, events, or loud gatherings are not permitted.\r\n\r\n🕰️ Check-In\r\n• ⏰ Early check-in before 11:00 AM will attract half-day charges, and before 7:00 AM will attract full-day charges, subject to availability.\r\n\r\n🧹 Housekeeping &amp; Cleaning\r\n• 🧼 Cleaning is provided every alternate day (Sundays excluded)\r\n• 🛏️ Linen is changed every 3 days.\r\n• 📅 Cleaning must be requested at least one day in advance.\r\n\r\n🔇 Noise &amp; Quiet Hours\r\n• 🚭 Smoking is only allowed in the balconies – not inside the apartment\r\n\r\n💼 Responsibility &amp; Damages\r\n• 💥 Any damages or breakages must be reported immediately and will be chargeable.\r\n• 🔌 Please switch off all lights, fans, ACs, and appliances when stepping out.\r\n\r\n🚗 Parking &amp; Access\r\n• 🅿️ One parking spot is available.', 'Apartment', '690, Sri Kumaran Avenue, Ponnampalayam, Erode, Tamilnadu - 638459', 'Erode', 'Tamil Nadu', 'India', '638459', 3600.00, 500.00, 15.00, 2, 1, 1, 1.0, 'No smoking', 'Wifi, Kitchen, Parking', 'active', '2025-12-23 13:54:53', '2025-12-23 14:02:12'),
(5, 5, 'Apartment in Village', 'Peaceful Room near to Care hospital and star hospital in Banjarahills. property is located behind the city center mall, opposite to Taj krishna hotel lane, Banjarahills, Hyderabad. Good ventilation, attached Large bathroom and dedicated fridge. This is in ground floor of the villa.\r\nThe space\r\nThis is just one bedroom in a villa which is in ground floor.\r\nGuest access\r\nguest can have access to ground floor bedroom.\r\nDuring your stay\r\nI will be available on call.\r\nOther things to note\r\nIn the event of power disruption, the House is equipped with 2000va Inverter but this powers only some fans and lights but not heavy power consumption items like Air Conditioners or Fridge.', 'Apartment', '690, Sri Kumaran Avenue, Ponnampalayam, Erode, Tamilnadu - 638459', 'Erode', 'New York', 'USA', '638459', 150.00, 50.00, 15.00, 2, 1, 1, 1.0, 'No smoking', 'Kitchen', 'active', '2025-12-23 16:14:20', '2025-12-23 16:14:54');

-- --------------------------------------------------------

--
-- Table structure for table `listing_availability`
--

CREATE TABLE `listing_availability` (
  `availability_id` int(11) NOT NULL,
  `listing_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('available','booked','blocked') DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `listing_availability`
--

INSERT INTO `listing_availability` (`availability_id`, `listing_id`, `date`, `status`) VALUES
(1, 1, '2025-12-23', 'available'),
(2, 1, '2025-12-24', 'available'),
(3, 1, '2025-12-25', 'available'),
(4, 1, '2025-12-26', 'available'),
(5, 1, '2025-12-27', 'available'),
(6, 1, '2025-12-28', 'available'),
(7, 1, '2025-12-29', 'available'),
(8, 1, '2025-12-30', 'available'),
(9, 1, '2025-12-31', 'available'),
(10, 1, '2026-01-01', 'available'),
(11, 1, '2026-01-02', 'available'),
(12, 1, '2026-01-03', 'available'),
(13, 1, '2026-01-04', 'available'),
(14, 1, '2026-01-05', 'available'),
(15, 1, '2026-01-06', 'available'),
(16, 1, '2026-01-07', 'available'),
(17, 1, '2026-01-08', 'available'),
(18, 1, '2026-01-09', 'available'),
(19, 1, '2026-01-10', 'available'),
(20, 1, '2026-01-11', 'available'),
(21, 1, '2026-01-12', 'available'),
(22, 1, '2026-01-13', 'available'),
(23, 1, '2026-01-14', 'available'),
(24, 1, '2026-01-15', 'available'),
(25, 1, '2026-01-16', 'available'),
(26, 1, '2026-01-17', 'available'),
(27, 1, '2026-01-18', 'available'),
(28, 1, '2026-01-19', 'available'),
(29, 1, '2026-01-20', 'available'),
(30, 1, '2026-01-21', 'available'),
(31, 1, '2026-01-22', 'available'),
(32, 1, '2026-01-23', 'available'),
(33, 1, '2026-01-24', 'available'),
(34, 1, '2026-01-25', 'available'),
(35, 1, '2026-01-26', 'available'),
(36, 1, '2026-01-27', 'available'),
(37, 1, '2026-01-28', 'available'),
(38, 1, '2026-01-29', 'available'),
(39, 1, '2026-01-30', 'available'),
(40, 1, '2026-01-31', 'available'),
(41, 1, '2026-02-01', 'available'),
(42, 1, '2026-02-02', 'available'),
(43, 1, '2026-02-03', 'available'),
(44, 1, '2026-02-04', 'available'),
(45, 1, '2026-02-05', 'available'),
(46, 1, '2026-02-06', 'available'),
(47, 1, '2026-02-07', 'available'),
(48, 1, '2026-02-08', 'available'),
(49, 1, '2026-02-09', 'available'),
(50, 1, '2026-02-10', 'available'),
(51, 1, '2026-02-11', 'available'),
(52, 1, '2026-02-12', 'available'),
(53, 1, '2026-02-13', 'available'),
(54, 1, '2026-02-14', 'available'),
(55, 1, '2026-02-15', 'available'),
(56, 1, '2026-02-16', 'available'),
(57, 1, '2026-02-17', 'available'),
(58, 1, '2026-02-18', 'available'),
(59, 1, '2026-02-19', 'available'),
(60, 1, '2026-02-20', 'available'),
(61, 1, '2026-02-21', 'available'),
(62, 1, '2026-02-22', 'available'),
(63, 1, '2026-02-23', 'available'),
(64, 1, '2026-02-24', 'available'),
(65, 1, '2026-02-25', 'available'),
(66, 1, '2026-02-26', 'available'),
(67, 1, '2026-02-27', 'available'),
(68, 1, '2026-02-28', 'available'),
(69, 1, '2026-03-01', 'available'),
(70, 1, '2026-03-02', 'available'),
(71, 1, '2026-03-03', 'available'),
(72, 1, '2026-03-04', 'available'),
(73, 1, '2026-03-05', 'available'),
(74, 1, '2026-03-06', 'available'),
(75, 1, '2026-03-07', 'available'),
(76, 1, '2026-03-08', 'available'),
(77, 1, '2026-03-09', 'available'),
(78, 1, '2026-03-10', 'available'),
(79, 1, '2026-03-11', 'available'),
(80, 1, '2026-03-12', 'available'),
(81, 1, '2026-03-13', 'available'),
(82, 1, '2026-03-14', 'available'),
(83, 1, '2026-03-15', 'available'),
(84, 1, '2026-03-16', 'available'),
(85, 1, '2026-03-17', 'available'),
(86, 1, '2026-03-18', 'available'),
(87, 1, '2026-03-19', 'available'),
(88, 1, '2026-03-20', 'available'),
(89, 1, '2026-03-21', 'available'),
(90, 1, '2026-03-22', 'available'),
(181, 3, '2025-12-23', 'available'),
(182, 3, '2025-12-24', 'available'),
(183, 3, '2025-12-25', 'available'),
(184, 3, '2025-12-26', 'available'),
(185, 3, '2025-12-27', 'available'),
(186, 3, '2025-12-28', 'blocked'),
(187, 3, '2025-12-29', 'available'),
(188, 3, '2025-12-30', 'available'),
(189, 3, '2025-12-31', 'available'),
(190, 3, '2026-01-01', 'available'),
(191, 3, '2026-01-02', 'available'),
(192, 3, '2026-01-03', 'available'),
(193, 3, '2026-01-04', 'available'),
(194, 3, '2026-01-05', 'available'),
(195, 3, '2026-01-06', 'available'),
(196, 3, '2026-01-07', 'available'),
(197, 3, '2026-01-08', 'available'),
(198, 3, '2026-01-09', 'available'),
(199, 3, '2026-01-10', 'available'),
(200, 3, '2026-01-11', 'available'),
(201, 3, '2026-01-12', 'available'),
(202, 3, '2026-01-13', 'available'),
(203, 3, '2026-01-14', 'available'),
(204, 3, '2026-01-15', 'available'),
(205, 3, '2026-01-16', 'available'),
(206, 3, '2026-01-17', 'available'),
(207, 3, '2026-01-18', 'available'),
(208, 3, '2026-01-19', 'available'),
(209, 3, '2026-01-20', 'available'),
(210, 3, '2026-01-21', 'available'),
(211, 3, '2026-01-22', 'available'),
(212, 3, '2026-01-23', 'available'),
(213, 3, '2026-01-24', 'available'),
(214, 3, '2026-01-25', 'available'),
(215, 3, '2026-01-26', 'available'),
(216, 3, '2026-01-27', 'available'),
(217, 3, '2026-01-28', 'available'),
(218, 3, '2026-01-29', 'available'),
(219, 3, '2026-01-30', 'available'),
(220, 3, '2026-01-31', 'available'),
(221, 3, '2026-02-01', 'available'),
(222, 3, '2026-02-02', 'available'),
(223, 3, '2026-02-03', 'available'),
(224, 3, '2026-02-04', 'available'),
(225, 3, '2026-02-05', 'available'),
(226, 3, '2026-02-06', 'available'),
(227, 3, '2026-02-07', 'available'),
(228, 3, '2026-02-08', 'available'),
(229, 3, '2026-02-09', 'available'),
(230, 3, '2026-02-10', 'available'),
(231, 3, '2026-02-11', 'available'),
(232, 3, '2026-02-12', 'available'),
(233, 3, '2026-02-13', 'available'),
(234, 3, '2026-02-14', 'available'),
(235, 3, '2026-02-15', 'available'),
(236, 3, '2026-02-16', 'available'),
(237, 3, '2026-02-17', 'available'),
(238, 3, '2026-02-18', 'available'),
(239, 3, '2026-02-19', 'available'),
(240, 3, '2026-02-20', 'available'),
(241, 3, '2026-02-21', 'available'),
(242, 3, '2026-02-22', 'available'),
(243, 3, '2026-02-23', 'available'),
(244, 3, '2026-02-24', 'available'),
(245, 3, '2026-02-25', 'available'),
(246, 3, '2026-02-26', 'available'),
(247, 3, '2026-02-27', 'available'),
(248, 3, '2026-02-28', 'available'),
(249, 3, '2026-03-01', 'available'),
(250, 3, '2026-03-02', 'available'),
(251, 3, '2026-03-03', 'available'),
(252, 3, '2026-03-04', 'available'),
(253, 3, '2026-03-05', 'available'),
(254, 3, '2026-03-06', 'available'),
(255, 3, '2026-03-07', 'available'),
(256, 3, '2026-03-08', 'available'),
(257, 3, '2026-03-09', 'available'),
(258, 3, '2026-03-10', 'available'),
(259, 3, '2026-03-11', 'available'),
(260, 3, '2026-03-12', 'available'),
(261, 3, '2026-03-13', 'available'),
(262, 3, '2026-03-14', 'available'),
(263, 3, '2026-03-15', 'available'),
(264, 3, '2026-03-16', 'available'),
(265, 3, '2026-03-17', 'available'),
(266, 3, '2026-03-18', 'available'),
(267, 3, '2026-03-19', 'available'),
(268, 3, '2026-03-20', 'available'),
(269, 3, '2026-03-21', 'available'),
(270, 3, '2026-03-22', 'available');

-- --------------------------------------------------------

--
-- Table structure for table `listing_photos`
--

CREATE TABLE `listing_photos` (
  `photo_id` int(11) NOT NULL,
  `listing_id` int(11) NOT NULL,
  `photo_url` varchar(500) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `display_order` int(11) DEFAULT 0,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `listing_photos`
--

INSERT INTO `listing_photos` (`photo_id`, `listing_id`, `photo_url`, `is_primary`, `display_order`, `uploaded_at`) VALUES
(6, 1, 'uploads/listings/listing_1_1766503332_0.jpg', 1, 1, '2025-12-23 15:22:12'),
(7, 1, 'uploads/listings/listing_1_1766503343_0.png', 0, 2, '2025-12-23 15:22:23'),
(8, 4, 'uploads/listings/listing_4_1766503391_0.jpg', 1, 1, '2025-12-23 15:23:11'),
(9, 4, 'uploads/listings/listing_4_1766503391_1.jpg', 0, 2, '2025-12-23 15:23:11'),
(10, 4, 'uploads/listings/listing_4_1766503391_2.png', 0, 3, '2025-12-23 15:23:11'),
(11, 4, 'uploads/listings/listing_4_1766503391_3.jfif', 0, 4, '2025-12-23 15:23:11'),
(12, 4, 'uploads/listings/listing_4_1766503391_4.jpg', 0, 5, '2025-12-23 15:23:11'),
(13, 4, 'uploads/listings/listing_4_1766503391_5.jpg', 0, 6, '2025-12-23 15:23:11'),
(14, 3, 'uploads/listings/listing_3_1766503423_0.jfif', 1, 1, '2025-12-23 15:23:43'),
(15, 5, 'uploads/listings/listing_5_1766506472_0.png', 1, 1, '2025-12-23 16:14:32');

-- --------------------------------------------------------

--
-- Table structure for table `payment_credentials`
--

CREATE TABLE `payment_credentials` (
  `credential_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `credential_type` enum('business_card','business_number') NOT NULL,
  `credential_number` varchar(50) NOT NULL,
  `credential_name` varchar(255) DEFAULT NULL,
  `status` enum('active','suspended','expired') DEFAULT 'active',
  `issued_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `expiry_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_credentials`
--

INSERT INTO `payment_credentials` (`credential_id`, `user_id`, `credential_type`, `credential_number`, `credential_name`, `status`, `issued_date`, `expiry_date`, `created_at`) VALUES
(1, 3, 'business_card', 'BC-B0B5B615243F', 'guest', 'active', '2025-12-23 09:41:02', '2028-12-23', '2025-12-23 09:41:02'),
(2, 4, 'business_card', '4532-1234-5678-9012', 'Guest Business Card', 'active', '2025-12-23 11:14:45', NULL, '2025-12-23 11:14:45');

-- --------------------------------------------------------

--
-- Table structure for table `payouts`
--

CREATE TABLE `payouts` (
  `payout_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `payout_method_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','completed','failed','cancelled') DEFAULT 'pending',
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payouts`
--

INSERT INTO `payouts` (`payout_id`, `user_id`, `payout_method_id`, `amount`, `status`, `requested_at`, `processed_at`, `notes`) VALUES
(1, 5, 1, 50.00, 'completed', '2025-12-24 07:38:46', '2025-12-24 07:39:37', '\nAdmin approved: ');

-- --------------------------------------------------------

--
-- Table structure for table `payout_methods`
--

CREATE TABLE `payout_methods` (
  `payout_method_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `method_type` enum('bank_account','crypto_wallet','business_account') NOT NULL,
  `account_details` text NOT NULL,
  `account_holder_name` varchar(255) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `status` enum('active','suspended') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payout_methods`
--

INSERT INTO `payout_methods` (`payout_method_id`, `user_id`, `method_type`, `account_details`, `account_holder_name`, `is_default`, `status`, `created_at`, `updated_at`) VALUES
(1, 5, 'bank_account', '{\"account_number\":\"123456789\",\"routing_number\":\"987654321\",\"bank_name\":\"Test Bank\",\"account_type\":\"checking\"}', 'Jane Host', 1, 'active', '2025-12-23 11:14:46', '2025-12-23 11:14:46');

-- --------------------------------------------------------

--
-- Table structure for table `platform_settings`
--

CREATE TABLE `platform_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `platform_settings`
--

INSERT INTO `platform_settings` (`setting_id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'platform_fee_percent', '15.00', '2025-12-23 09:21:53'),
(2, 'tax_rate', '10.00', '2025-12-23 09:21:53'),
(3, 'min_payout_amount', '50.00', '2025-12-23 09:21:53'),
(4, 'currency', 'USD', '2025-12-23 09:21:53'),
(5, 'platform_name', 'Dream Destinations', '2025-12-23 09:21:53');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `reviewer_id` int(11) NOT NULL,
  `reviewee_id` int(11) NOT NULL,
  `review_type` enum('guest_to_host','host_to_guest') NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`review_id`, `booking_id`, `reviewer_id`, `reviewee_id`, `review_type`, `rating`, `comment`, `created_at`) VALUES
(1, 4, 4, 5, 'guest_to_host', 1, 'Good Amenities and support', '2025-12-24 07:37:33');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `transaction_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `transaction_type` enum('deposit','deduction','earning','payout','refund','fee','hold','release') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `balance_before` decimal(10,2) NOT NULL,
  `balance_after` decimal(10,2) NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`transaction_id`, `user_id`, `transaction_type`, `amount`, `balance_before`, `balance_after`, `reference_type`, `reference_id`, `description`, `created_at`) VALUES
(1, 3, 'deduction', 687.50, 1000.00, 312.50, 'booking', 1, 'Payment for booking #1', '2025-12-23 15:52:29'),
(2, 3, 'deduction', 222.75, 312.50, 89.75, 'booking', 2, 'Payment for booking #2', '2025-12-23 15:56:19'),
(3, 4, 'deduction', 222.75, 1000.00, 777.25, 'booking', 3, 'Payment for booking #3', '2025-12-23 16:12:06'),
(4, 3, 'refund', 687.50, 89.75, 777.25, 'booking', 1, 'Refund for booking #1', '2025-12-23 16:16:25'),
(5, 3, 'deposit', 1.00, 777.25, 778.25, NULL, NULL, 'Admin Adjustment: Test', '2025-12-23 16:17:10'),
(6, 4, 'deduction', 244.75, 777.25, 532.50, 'booking', 4, 'Payment for booking #4', '2025-12-24 07:25:46'),
(7, 5, 'earning', 200.25, 0.00, 200.25, 'booking', 3, 'Earning from booking #3 (admin release)', '2025-12-24 07:35:50'),
(8, 5, 'earning', 222.25, 200.25, 422.50, 'booking', 4, 'Earning from booking #4 (admin release)', '2025-12-24 07:36:04'),
(9, 5, 'payout', 50.00, 422.50, 372.50, 'payout', 1, 'Payout request #1', '2025-12-24 07:38:46'),
(10, 5, 'payout', 50.00, 372.50, 322.50, 'payout', 1, 'Payout to bank_account', '2025-12-24 07:39:37'),
(11, 4, 'deduction', 222.75, 532.50, 309.75, 'booking', 5, 'Payment for booking #5', '2025-12-24 07:53:07');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `user_type` enum('guest','host','admin') NOT NULL,
  `status` enum('active','suspended','frozen') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `email`, `password_hash`, `full_name`, `phone`, `user_type`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin@dreamdestinations.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', '1234567890', 'admin', 'active', '2025-12-23 09:21:53', '2025-12-23 09:21:53'),
(2, 'host@gmail.com', '$2y$10$7YHZEYPxDNROWKRhy7aiWem4ySdigPorgKGH3lrOvJt0tfa40CZye', 'host', '6656565', 'host', 'active', '2025-12-23 09:38:49', '2025-12-23 09:38:49'),
(3, 'guest@gmail.com', '$2y$10$8HnwikSspxBGZdKgzBJq3.nEv9em6LKpuCbmByKt83qK320h/hxGW', 'guest', '454545454', 'guest', 'active', '2025-12-23 09:41:01', '2025-12-23 09:41:01'),
(4, 'guest@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Guest', '1234567890', 'guest', 'active', '2025-12-23 11:14:45', '2025-12-23 14:01:05'),
(5, 'host@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane Host', '0987654321', 'host', 'active', '2025-12-23 11:14:45', '2025-12-23 11:14:45');

-- --------------------------------------------------------

--
-- Table structure for table `wishlists`
--

CREATE TABLE `wishlists` (
  `wishlist_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `listing_id` int(11) NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlists`
--

INSERT INTO `wishlists` (`wishlist_id`, `user_id`, `listing_id`, `added_at`, `notes`) VALUES
(1, 4, 3, '2025-12-24 07:00:29', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_actions`
--
ALTER TABLE `admin_actions`
  ADD PRIMARY KEY (`action_id`),
  ADD KEY `idx_admin_id` (`admin_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `listing_id` (`listing_id`),
  ADD KEY `payment_credential_id` (`payment_credential_id`),
  ADD KEY `idx_guest_id` (`guest_id`),
  ADD KEY `idx_host_id` (`host_id`),
  ADD KEY `idx_booking_status` (`booking_status`),
  ADD KEY `idx_dates` (`check_in`,`check_out`),
  ADD KEY `idx_checkout_date` (`check_out`,`booking_status`);

--
-- Indexes for table `disputes`
--
ALTER TABLE `disputes`
  ADD PRIMARY KEY (`dispute_id`),
  ADD KEY `raised_by` (`raised_by`),
  ADD KEY `idx_booking_id` (`booking_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `escrow`
--
ALTER TABLE `escrow`
  ADD PRIMARY KEY (`escrow_id`),
  ADD UNIQUE KEY `booking_id` (`booking_id`),
  ADD KEY `idx_booking_id` (`booking_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_escrow_status` (`status`,`booking_id`);

--
-- Indexes for table `guest_balances`
--
ALTER TABLE `guest_balances`
  ADD PRIMARY KEY (`balance_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `host_balances`
--
ALTER TABLE `host_balances`
  ADD PRIMARY KEY (`balance_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `host_verification`
--
ALTER TABLE `host_verification`
  ADD PRIMARY KEY (`verification_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `listings`
--
ALTER TABLE `listings`
  ADD PRIMARY KEY (`listing_id`),
  ADD KEY `idx_host_id` (`host_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_city` (`city`);

--
-- Indexes for table `listing_availability`
--
ALTER TABLE `listing_availability`
  ADD PRIMARY KEY (`availability_id`),
  ADD UNIQUE KEY `unique_listing_date` (`listing_id`,`date`),
  ADD KEY `idx_listing_date` (`listing_id`,`date`);

--
-- Indexes for table `listing_photos`
--
ALTER TABLE `listing_photos`
  ADD PRIMARY KEY (`photo_id`),
  ADD KEY `idx_listing_id` (`listing_id`);

--
-- Indexes for table `payment_credentials`
--
ALTER TABLE `payment_credentials`
  ADD PRIMARY KEY (`credential_id`),
  ADD UNIQUE KEY `credential_number` (`credential_number`),
  ADD KEY `idx_credential_number` (`credential_number`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `payouts`
--
ALTER TABLE `payouts`
  ADD PRIMARY KEY (`payout_id`),
  ADD KEY `payout_method_id` (`payout_method_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `payout_methods`
--
ALTER TABLE `payout_methods`
  ADD PRIMARY KEY (`payout_method_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `platform_settings`
--
ALTER TABLE `platform_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `reviewer_id` (`reviewer_id`),
  ADD KEY `idx_booking_id` (`booking_id`),
  ADD KEY `idx_reviewee_id` (`reviewee_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_transaction_type` (`transaction_type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_user_type` (`user_type`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD PRIMARY KEY (`wishlist_id`),
  ADD UNIQUE KEY `unique_user_listing` (`user_id`,`listing_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_listing_id` (`listing_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_actions`
--
ALTER TABLE `admin_actions`
  MODIFY `action_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `disputes`
--
ALTER TABLE `disputes`
  MODIFY `dispute_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `escrow`
--
ALTER TABLE `escrow`
  MODIFY `escrow_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `guest_balances`
--
ALTER TABLE `guest_balances`
  MODIFY `balance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `host_balances`
--
ALTER TABLE `host_balances`
  MODIFY `balance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `host_verification`
--
ALTER TABLE `host_verification`
  MODIFY `verification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `listings`
--
ALTER TABLE `listings`
  MODIFY `listing_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `listing_availability`
--
ALTER TABLE `listing_availability`
  MODIFY `availability_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=272;

--
-- AUTO_INCREMENT for table `listing_photos`
--
ALTER TABLE `listing_photos`
  MODIFY `photo_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `payment_credentials`
--
ALTER TABLE `payment_credentials`
  MODIFY `credential_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payouts`
--
ALTER TABLE `payouts`
  MODIFY `payout_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payout_methods`
--
ALTER TABLE `payout_methods`
  MODIFY `payout_method_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `platform_settings`
--
ALTER TABLE `platform_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `wishlists`
--
ALTER TABLE `wishlists`
  MODIFY `wishlist_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_actions`
--
ALTER TABLE `admin_actions`
  ADD CONSTRAINT `admin_actions_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`listing_id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`guest_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `bookings_ibfk_3` FOREIGN KEY (`host_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `bookings_ibfk_4` FOREIGN KEY (`payment_credential_id`) REFERENCES `payment_credentials` (`credential_id`);

--
-- Constraints for table `disputes`
--
ALTER TABLE `disputes`
  ADD CONSTRAINT `disputes_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`),
  ADD CONSTRAINT `disputes_ibfk_2` FOREIGN KEY (`raised_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `escrow`
--
ALTER TABLE `escrow`
  ADD CONSTRAINT `escrow_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE;

--
-- Constraints for table `guest_balances`
--
ALTER TABLE `guest_balances`
  ADD CONSTRAINT `guest_balances_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `host_balances`
--
ALTER TABLE `host_balances`
  ADD CONSTRAINT `host_balances_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `host_verification`
--
ALTER TABLE `host_verification`
  ADD CONSTRAINT `host_verification_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `listings`
--
ALTER TABLE `listings`
  ADD CONSTRAINT `listings_ibfk_1` FOREIGN KEY (`host_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `listing_availability`
--
ALTER TABLE `listing_availability`
  ADD CONSTRAINT `listing_availability_ibfk_1` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`listing_id`) ON DELETE CASCADE;

--
-- Constraints for table `listing_photos`
--
ALTER TABLE `listing_photos`
  ADD CONSTRAINT `listing_photos_ibfk_1` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`listing_id`) ON DELETE CASCADE;

--
-- Constraints for table `payment_credentials`
--
ALTER TABLE `payment_credentials`
  ADD CONSTRAINT `payment_credentials_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `payouts`
--
ALTER TABLE `payouts`
  ADD CONSTRAINT `payouts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `payouts_ibfk_2` FOREIGN KEY (`payout_method_id`) REFERENCES `payout_methods` (`payout_method_id`);

--
-- Constraints for table `payout_methods`
--
ALTER TABLE `payout_methods`
  ADD CONSTRAINT `payout_methods_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`reviewee_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD CONSTRAINT `wishlists_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlists_ibfk_2` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`listing_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
