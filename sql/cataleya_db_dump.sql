CREATE DATABASE IF NOT EXISTS `cataleya_db`;
USE `cataleya_db`;

-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Aug 05, 2026 at 04:41 PM
-- Server version: 8.4.3
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cataleya_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `service_id` int NOT NULL,
  `staff_id` int DEFAULT NULL,
  `booking_date` varchar(50) NOT NULL,
  `booking_time` time NOT NULL,
  `deadline` datetime DEFAULT NULL,
  `status` enum('pending','confirmed','rescheduled','completed','cancelled') DEFAULT 'confirmed',
  `auto_cancelled` tinyint(1) DEFAULT '0',
  `total_amount` decimal(10,2) NOT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active_user_slot_key` varchar(128) GENERATED ALWAYS AS (case when (`status` in (_utf8mb4'confirmed',_utf8mb4'rescheduled')) then concat(`user_id`,_utf8mb4'|',`booking_date`,_utf8mb4'|',`booking_time`) else NULL end) STORED,
  `active_slot_key` varchar(128) GENERATED ALWAYS AS (case when (`status` in (_utf8mb4'confirmed',_utf8mb4'rescheduled')) then concat(`booking_date`,_utf8mb4'|',`booking_time`) else NULL end) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `service_id`, `staff_id`, `booking_date`, `booking_time`, `deadline`, `status`, `auto_cancelled`, `total_amount`, `notes`, `created_at`, `updated_at`) VALUES
(10, 3, 3, 4, '2026-07-28', '10:00:00', NULL, 'cancelled', 0, 350.00, '', '2026-07-27 05:31:44', '2026-08-01 19:04:07'),
(11, 4, 55, 8, '2026-07-29', '14:00:00', NULL, 'cancelled', 0, 500.00, '', '2026-07-27 06:28:22', '2026-08-01 19:04:07'),
(12, 3, 6, 2, '2026-08-02', '10:00:00', '2026-08-02 20:12:06', 'completed', 0, 249.00, '', '2026-08-01 18:12:06', '2026-08-02 10:20:23'),
(13, 4, 57, 17, '2026-08-06', '13:00:00', '2026-08-04 09:32:22', 'completed', 0, 759.05, '', '2026-08-03 07:32:22', '2026-08-03 07:33:07'),
(14, 3, 1, 3, '2026-08-11', '13:00:00', '2026-08-06 14:19:20', 'confirmed', 0, 569.05, '', '2026-08-05 12:19:20', '2026-08-05 12:21:08'),
(15, 13, 19, 5, '2026-08-13', '10:00:00', '2026-08-06 15:05:21', 'confirmed', 0, 760.00, '', '2026-08-05 13:05:21', '2026-08-05 13:25:19');

-- --------------------------------------------------------

--
-- Table structure for table `daily_slot_availability`
--

CREATE TABLE `daily_slot_availability` (
  `id` int NOT NULL,
  `slot_date` date NOT NULL,
  `slot_id` int NOT NULL,
  `status` enum('available','filling','booked','unavailable') DEFAULT 'available',
  `max_bookings` int DEFAULT '1',
  `current_bookings` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('pending','reviewed','resolved') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `description` text,
  `quantity` int NOT NULL DEFAULT '0',
  `unit_price` decimal(10,2) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `low_stock_threshold` int DEFAULT '10',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_transactions`
--

CREATE TABLE `inventory_transactions` (
  `id` int NOT NULL,
  `inventory_id` int NOT NULL,
  `transaction_type` enum('in','out') NOT NULL,
  `quantity` int NOT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `otp_codes`
--

CREATE TABLE `otp_codes` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `otp_code` varchar(6) NOT NULL,
  `purpose` enum('signup','reset') NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int NOT NULL,
  `booking_id` int NOT NULL,
  `user_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','gcash','card','bank_transfer') NOT NULL,
  `status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `transaction_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `booking_id`, `user_id`, `amount`, `payment_method`, `status`, `transaction_id`, `created_at`, `updated_at`) VALUES
(1, 13, 4, 379.53, 'gcash', 'completed', NULL, '2026-08-03 07:32:22', '2026-08-03 07:32:22'),
(2, 14, 3, 284.53, 'gcash', 'completed', NULL, '2026-08-05 12:19:21', '2026-08-05 12:19:21'),
(3, 15, 13, 380.00, 'gcash', 'completed', NULL, '2026-08-05 13:05:21', '2026-08-05 13:05:21');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `booking_id` int NOT NULL,
  `staff_id` int DEFAULT NULL,
  `rating` int NOT NULL,
  `comment` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ;

-- --------------------------------------------------------

--
-- Table structure for table `rewards`
--

CREATE TABLE `rewards` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `points` int DEFAULT '0',
  `visits` int DEFAULT '0',
  `tier` enum('bronze','silver','gold','platinum') DEFAULT 'bronze',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `rewards`
--

INSERT INTO `rewards` (`id`, `user_id`, `points`, `visits`, `tier`, `created_at`, `updated_at`) VALUES
(1, 3, 35, 3, 'bronze', '2026-07-27 05:31:44', '2026-08-05 12:19:21'),
(2, 4, 35, 2, 'bronze', '2026-07-27 06:28:22', '2026-08-03 07:32:22'),
(3, 13, 20, 1, 'bronze', '2026-08-05 13:04:09', '2026-08-05 13:05:21');

-- --------------------------------------------------------

--
-- Table structure for table `reward_transactions`
--

CREATE TABLE `reward_transactions` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `points_earned` int DEFAULT '0',
  `points_redeemed` int DEFAULT '0',
  `description` varchar(255) DEFAULT NULL,
  `booking_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `main_category` enum('Beauty Services','Spa Massage') NOT NULL,
  `sub_category` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `duration_minutes` int DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `name`, `description`, `main_category`, `sub_category`, `price`, `duration_minutes`, `image_url`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Signature Facial', NULL, 'Beauty Services', 'Facial Services', 599.00, 60, '../img/Signature facial.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:32'),
(2, 'Acne Clear Facial', NULL, 'Beauty Services', 'Facial Services', 599.00, 60, '../img/acne clear facial.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:32'),
(3, 'Classic Facial', NULL, 'Beauty Services', 'Facial Services', 350.00, 45, '../img/classic facial.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:32'),
(4, 'Hydra Facial', NULL, 'Beauty Services', 'Facial Services', 799.00, 75, '../img/hydra facial.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:32'),
(5, 'Diamond Peel', NULL, 'Beauty Services', 'Facial Services', 249.00, 30, '../img/diamond feal.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:32'),
(6, 'PDT Light Therapy', NULL, 'Beauty Services', 'Facial Services', 249.00, 30, '../img/pdt light.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:32'),
(7, 'Pimple Injection', NULL, 'Beauty Services', 'Facial Services', 199.00, 15, '../img/pimple injection.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:32'),
(8, 'BB Glow', NULL, 'Beauty Services', 'Facial Services', 799.00, 60, '../img/bb glow.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:32'),
(9, 'Warts Removal (Per Area)', NULL, 'Beauty Services', 'Facial Services', 1499.00, 30, '../img/warts removal.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:32'),
(10, 'Vajacial', NULL, 'Beauty Services', 'Facial Services', 999.00, 45, '../img/vajacial.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:32'),
(11, 'RF Face', NULL, 'Beauty Services', 'RF + Lipo Cav', 299.00, 30, '../img/rf face (1).png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:32'),
(12, 'RF Arms', NULL, 'Beauty Services', 'RF + Lipo Cav', 499.00, 45, '../img/rf arms.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:32'),
(13, 'RF Belly', NULL, 'Beauty Services', 'RF + Lipo Cav', 599.00, 45, '../img/rf belly.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:32'),
(14, 'RF Legs', NULL, 'Beauty Services', 'RF + Lipo Cav', 699.00, 60, '../img/rf legs.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:32'),
(15, 'RF Whole Body', NULL, 'Beauty Services', 'RF + Lipo Cav', 1999.00, 90, '../img/rf whole.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:32'),
(16, 'Natural Look', NULL, 'Beauty Services', 'EyeLash Enhancement', 350.00, 60, '../img/natural look.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:33'),
(17, 'Volume Look', NULL, 'Beauty Services', 'EyeLash Enhancement', 450.00, 75, '../img/volume look.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:33'),
(18, 'Cat Eye Look', NULL, 'Beauty Services', 'EyeLash Enhancement', 550.00, 90, '../img/cat eye look.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:33'),
(19, 'Wispy Look', NULL, 'Beauty Services', 'EyeLash Enhancement', 800.00, 120, '../img/wispy look.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:33'),
(20, 'Keratin lash Lift', NULL, 'Beauty Services', 'EyeLash Enhancement', 800.00, 60, '../img/keratin lash.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:33'),
(21, 'Last Lift/ Mascara', NULL, 'Beauty Services', 'EyeLash Enhancement', 350.00, 45, '../img/last lift.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:33'),
(22, 'Upper up', NULL, 'Beauty Services', 'Hair Laser Removal', 199.00, 15, '../img/removal upper up.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:33'),
(23, 'Face', NULL, 'Beauty Services', 'Hair Laser Removal', 299.00, 30, '../img/removal face.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:33'),
(24, 'Underarm', NULL, 'Beauty Services', 'Hair Laser Removal', 499.00, 30, '../img/removal arms.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:33'),
(25, 'Arms', NULL, 'Beauty Services', 'Hair Laser Removal', 799.00, 45, '../img/arms gluta.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:33'),
(26, 'Legs', NULL, 'Beauty Services', 'Hair Laser Removal', 899.00, 60, '../img/removal legs.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:33'),
(27, 'Chest', NULL, 'Beauty Services', 'Hair Laser Removal', 499.00, 30, '../img/removal chest.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:33'),
(28, 'Brazilian', NULL, 'Beauty Services', 'Hair Laser Removal', 799.00, 45, '../img/removal brazilian.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:33'),
(29, 'Whole Body', NULL, 'Beauty Services', 'Hair Laser Removal', 2500.00, 120, '../img/removal whole body.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:33'),
(30, 'Black Doll Carbon', NULL, 'Beauty Services', 'Laser Whitening', 799.00, 45, '../img/back doll.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:33'),
(31, 'Underarm Laser', NULL, 'Beauty Services', 'Laser Whitening', 499.00, 30, '../img/underarm laser.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:33'),
(32, 'Elbow Whitening', NULL, 'Beauty Services', 'Laser Whitening', 499.00, 30, '../img/elbow whitenming.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:33'),
(33, 'Knee Whitening', NULL, 'Beauty Services', 'Laser Whitening', 599.00, 30, '../img/knee whitening.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:33'),
(34, 'Melasma', NULL, 'Beauty Services', 'Laser Whitening', 799.00, 60, '../img/melasma.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:33'),
(35, 'Tattoo Removal', NULL, 'Beauty Services', 'Laser Whitening', 2500.00, 60, '../img/tattoo removal.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:33'),
(36, 'Double Chin', NULL, 'Beauty Services', 'Mesolipo', 999.00, 30, '../img/double chin.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:33'),
(37, 'Arms', NULL, 'Beauty Services', 'Mesolipo', 2000.00, 45, '../img/arms gluta.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:33'),
(38, 'Tummy', NULL, 'Beauty Services', 'Mesolipo', 1499.00, 45, '../img/tummy.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:33'),
(39, 'Thigh', NULL, 'Beauty Services', 'Mesolipo', 2000.00, 60, '../img/thigh.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:33'),
(40, 'Ultra Whitening Drip / Session', NULL, 'Beauty Services', 'Gluta Whitening', 1799.00, 60, '../img/ultra whitening.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:33'),
(41, 'Microblading', NULL, 'Beauty Services', 'Semi-Permanent Make Up', 2999.00, 120, '../img/microblading.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:33'),
(42, 'Lip Blush', NULL, 'Beauty Services', 'Semi-Permanent Make Up', 2499.00, 90, '../img/lip blush.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:33'),
(43, 'Eyeliner Tattoo', NULL, 'Beauty Services', 'Semi-Permanent Make Up', 1999.00, 60, '../img/eyeliner tattoo.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:33'),
(44, 'Underarm Waxing', NULL, 'Beauty Services', 'Waxing', 199.00, 15, '../img/waxing.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:33'),
(45, 'Full Leg Waxing', NULL, 'Beauty Services', 'Waxing', 599.00, 45, '../img/waxing.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:33'),
(46, 'Brazilian Waxing', NULL, 'Beauty Services', 'Waxing', 799.00, 45, '../img/waxing.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:33'),
(47, 'HIFU Face', NULL, 'Beauty Services', 'HIFU Ultheraphy', 2999.00, 90, '../img/hifu face.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:33'),
(48, 'HIFU Body', NULL, 'Beauty Services', 'HIFU Ultheraphy', 4999.00, 120, '../img/hifu body.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:33'),
(49, 'Manicure', NULL, 'Beauty Services', 'Nail Services', 199.00, 30, '../img/manicure.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:33'),
(50, 'Pedicure', NULL, 'Beauty Services', 'Nail Services', 249.00, 45, '../img/pedicure.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:33'),
(51, 'Gel Manicure', NULL, 'Beauty Services', 'Nail Services', 399.00, 45, '../img/gel manicure.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:33'),
(52, 'Gel Pedicure', NULL, 'Beauty Services', 'Nail Services', 499.00, 60, '../img/gel pedicure.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:33'),
(53, 'Swedish Massage', NULL, 'Spa Massage', 'Body Care', 999.00, 60, '../img/swedish massage.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:33'),
(54, 'Deep Tissue Massage', NULL, 'Spa Massage', 'Body Care', 1299.00, 90, '../img/deep tissue massage.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:33'),
(55, 'Aromatherapy Massage', NULL, 'Spa Massage', 'Body Care', 1199.00, 60, '../img/aromatherapy massage.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:33'),
(56, 'Hilot', NULL, 'Spa Massage', 'Traditional Body Care', 899.00, 60, '../img/hilot.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:33'),
(57, 'Ventosa', NULL, 'Spa Massage', 'Traditional Body Care', 799.00, 45, '../img/ventosa.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:33'),
(58, 'Body Scrub', NULL, 'Spa Massage', 'Body Skin Treatment', 699.00, 45, '../img/body scrub.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:33'),
(59, 'Body Wrap', NULL, 'Spa Massage', 'Body Skin Treatment', 999.00, 60, '../img/body wrap.png', 1, '2026-07-27 05:31:18', '2026-07-30 00:40:33');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `specialization` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT '1',
  `rating` decimal(3,2) DEFAULT '0.00',
  `total_reviews` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `category` enum('Beauty Services','Spa Massage') NOT NULL DEFAULT 'Beauty Services',
  `title` varchar(100) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `experience_years` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id`, `full_name`, `email`, `phone`, `specialization`, `is_available`, `rating`, `total_reviews`, `created_at`, `category`, `title`, `image_url`, `experience_years`) VALUES
(1, 'Jhacel', 'jhacel@cataleya.com', '09171234567', NULL, 1, 4.90, 45, '2026-07-22 13:14:42', 'Beauty Services', 'Aesthetician', '../img/Jhacel (1).png', 8),
(2, 'Cyrel', 'cyrel@cataleya.com', '09181234567', NULL, 1, 4.90, 38, '2026-07-22 13:14:42', 'Beauty Services', 'Aesthetician', '../img/curel.png', 8),
(3, 'Joy', 'joy@cataleya.com', '09191234567', NULL, 1, 4.90, 42, '2026-07-22 13:14:42', 'Beauty Services', 'Aesthetician', '../img/joy.png', 8),
(4, 'Coleen', 'coleen@cataleya.com', '09201234567', NULL, 1, 4.90, 35, '2026-07-22 13:14:42', 'Beauty Services', 'Aesthetician', '../img/coleen.png', 8),
(5, 'Jade', 'jade@cataleya.com', '09211234567', NULL, 1, 4.90, 40, '2026-07-22 13:14:42', 'Beauty Services', 'Aesthetician', '../img/jade.png', 8),
(6, 'Aira', 'aira@cataleya.com', '09221234567', NULL, 1, 4.90, 33, '2026-07-22 13:14:42', 'Beauty Services', 'Aesthetician', '../img/aira.png', 8),
(7, 'Melvin', 'melvin@cataleya.com', '09231234567', NULL, 1, 4.80, 28, '2026-07-22 13:14:42', 'Spa Massage', 'Massage Therapist', '../img/Melvin.png', 8),
(8, 'Megan', 'megan@cataleya.com', '09241234567', NULL, 1, 4.90, 31, '2026-07-22 13:14:42', 'Spa Massage', 'Massage Therapist', '../img/megan.png', 8),
(9, 'Dimple', 'dimple@cataleya.com', '09251234567', NULL, 1, 4.70, 25, '2026-07-22 13:14:42', 'Spa Massage', 'Massage Therapist', '../img/Dimple.png', 8),
(10, 'Roxanne', 'roxanne@cataleya.com', '09261234567', NULL, 1, 4.90, 29, '2026-07-22 13:14:42', 'Spa Massage', 'Massage Therapist', '../img/Roxanne.png', 8),
(11, 'Hajie', 'hajie@cataleya.com', '09271234567', NULL, 1, 4.80, 22, '2026-07-22 13:14:42', 'Spa Massage', 'Massage Therapist', '../img/Hajie.png', 8),
(12, 'Meah', 'meah@cataleya.com', '09281234567', NULL, 1, 4.90, 27, '2026-07-22 13:14:42', 'Spa Massage', 'Massage Therapist', '../img/Rectangle 252.png', 8),
(13, 'Yurie', 'yurie@cataleya.com', '09291234567', NULL, 1, 4.70, 24, '2026-07-22 13:14:42', 'Spa Massage', 'Massage Therapist', '../img/Yurie.png', 8),
(14, 'Trixie', 'trixie@cataleya.com', '09301234567', NULL, 1, 4.90, 30, '2026-07-22 13:14:42', 'Spa Massage', 'Massage Therapist', '../img/trixie.png', 8),
(15, 'Joy', 'joy.spa@cataleya.com', '09311234567', NULL, 1, 4.80, 26, '2026-07-22 13:14:42', 'Spa Massage', 'Massage Therapist', '../img/Joy...png', 8),
(16, 'Jade', 'jade.spa@cataleya.com', '09321234567', NULL, 1, 4.90, 32, '2026-07-22 13:14:42', 'Spa Massage', 'Massage Therapist', '../img/Jade...png', 8),
(17, 'Coleen', 'coleen.spa@cataleya.com', '09331234567', NULL, 1, 4.70, 21, '2026-07-22 13:14:42', 'Spa Massage', 'Massage Therapist', '../img/Coleen...png', 8);

-- --------------------------------------------------------

--
-- Table structure for table `staff_availability`
--

CREATE TABLE `staff_availability` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `day_of_week` enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_available` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `time_slots`
--

CREATE TABLE `time_slots` (
  `id` int NOT NULL,
  `slot_time` time NOT NULL,
  `display_time` varchar(20) NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `sort_order` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `time_slots`
--

INSERT INTO `time_slots` (`id`, `slot_time`, `display_time`, `is_active`, `sort_order`) VALUES
(1, '09:00:00', '9:00 AM', 1, 1),
(2, '10:00:00', '10:00 AM', 1, 2),
(3, '11:00:00', '11:00 AM', 1, 3),
(4, '12:00:00', '12:00 PM', 1, 4),
(5, '13:00:00', '1:00 PM', 1, 5),
(6, '14:00:00', '2:00 PM', 1, 6),
(7, '15:00:00', '3:00 PM', 1, 7),
(8, '16:00:00', '4:00 PM', 1, 8),
(9, '17:00:00', '5:00 PM', 1, 9),
(10, '18:00:00', '6:00 PM', 1, 10);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address_line1` varchar(255) DEFAULT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_admin` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `address_line1`, `address_line2`, `city`, `province`, `postal_code`, `password_hash`, `profile_photo`, `is_verified`, `created_at`, `is_admin`) VALUES
(3, 'Takt Hoshino', 'jdelamerced933@gmail.com', '09927884070', 'San Jose', '', 'San Luis', 'Pampanga', '2014', '$2y$10$ViUbSwS.xLMLqjp/al0yFuVq8lKuDIhlIxcqqBin8A86v9gNFhK1q', '../uploads/profile_photos/profile_3_1785462553.jpg', 1, '2026-07-22 09:12:03', 0),
(4, 'Shiro Hoshino', 'jdelamerced52@gmail.com', '09927884070', 'Piel', '224', 'Baliuag', 'Bulacan', '3006', '$2y$10$bMspPLz/9XiF0BQTSADZO.mOsRYPTdYq8kCpCPxCmfE.ob3rDPVc2', '../uploads/profile_photos/profile_4_1785135277.jpg', 1, '2026-07-27 06:26:06', 0),
(5, 'Administrator', 'cataleyaessence23@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, '$2y$10$1WJMJGLmvl4W7n06VmEUW.Y677A8qEcmdbGXi3qDC9J/ideKt1/7i', NULL, 1, '2026-08-03 03:54:44', 1),
(12, 'neil ivan', 'neilivanstamaria121@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, '$2y$10$NgCgZC9e1QYGqm5JHEhQHuWi9T2me/V30ZF6JyZCqGHBrv6ecmDmO', NULL, 1, '2026-08-05 12:27:19', 0),
(13, 'Trisha Ann', 'trishaannjoy@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, '$2y$10$3FP4GiZJAXzMpZCRrZPOBuFB1vuT2F85iM8iO4wbcR9EGWAUL0AAm', NULL, 1, '2026-08-05 13:03:09', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `service_id` (`service_id`),
  ADD KEY `staff_id` (`staff_id`),
  ADD UNIQUE KEY `uq_bookings_active_user_slot` (`active_user_slot_key`),
  ADD UNIQUE KEY `uq_bookings_active_slot` (`active_slot_key`);

--
-- Indexes for table `daily_slot_availability`
--
ALTER TABLE `daily_slot_availability`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slot_date` (`slot_date`,`slot_id`),
  ADD KEY `slot_id` (`slot_id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inventory_id` (`inventory_id`);

--
-- Indexes for table `otp_codes`
--
ALTER TABLE `otp_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `rewards`
--
ALTER TABLE `rewards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `reward_transactions`
--
ALTER TABLE `reward_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `staff_availability`
--
ALTER TABLE `staff_availability`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `staff_id` (`staff_id`,`day_of_week`);

--
-- Indexes for table `time_slots`
--
ALTER TABLE `time_slots`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slot_time` (`slot_time`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `daily_slot_availability`
--
ALTER TABLE `daily_slot_availability`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `otp_codes`
--
ALTER TABLE `otp_codes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rewards`
--
ALTER TABLE `rewards`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `reward_transactions`
--
ALTER TABLE `reward_transactions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `staff_availability`
--
ALTER TABLE `staff_availability`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `time_slots`
--
ALTER TABLE `time_slots`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_3` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `daily_slot_availability`
--
ALTER TABLE `daily_slot_availability`
  ADD CONSTRAINT `daily_slot_availability_ibfk_1` FOREIGN KEY (`slot_id`) REFERENCES `time_slots` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD CONSTRAINT `inventory_transactions_ibfk_1` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `otp_codes`
--
ALTER TABLE `otp_codes`
  ADD CONSTRAINT `otp_codes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `rewards`
--
ALTER TABLE `rewards`
  ADD CONSTRAINT `rewards_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reward_transactions`
--
ALTER TABLE `reward_transactions`
  ADD CONSTRAINT `reward_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reward_transactions_ibfk_2` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `staff_availability`
--
ALTER TABLE `staff_availability`
  ADD CONSTRAINT `staff_availability_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
