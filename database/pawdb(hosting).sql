-- phpMyAdmin SQL Dump (updated for Option C: default_location_id on users)
-- Compatible with MariaDB 10.4.x / MySQL 5.7+
-- Notes:
-- - No triggers are created (hosting often blocks TRIGGER privilege).
-- - Adds users.default_location_id referencing locations(location_id) ON DELETE SET NULL.
-- - Does not include CREATE DATABASE or USE statements; select your DB first in phpMyAdmin.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
 /*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
 /*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
 /*!40101 SET NAMES utf8mb4 */;

--
-- Database: (selected in phpMyAdmin)
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_activity_logs`
--

CREATE TABLE `admin_activity_logs` (
  `admin_activity_logs_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `admin_activity_logs_action` varchar(255) NOT NULL,
  `admin_activity_logs_details` text DEFAULT NULL,
  `admin_activity_logs_created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `appointments_id` int(11) NOT NULL,
  `users_id` int(11) NOT NULL,
  `appointments_full_name` varchar(255) DEFAULT NULL,
  `appointments_email` varchar(255) DEFAULT NULL COMMENT 'Client email',
  `appointments_phone` varchar(25) DEFAULT NULL COMMENT 'Client phone number',
  `appointments_pet_name` varchar(100) DEFAULT NULL COMMENT 'Pet name',
  `appointments_pet_type` enum('dog','cat','bird','fish','other') DEFAULT NULL COMMENT 'Pet type',
  `appointments_pet_breed` varchar(100) DEFAULT NULL COMMENT 'Pet breed',
  `appointments_pet_age_years` varchar(5) DEFAULT NULL,
  `appointments_type` enum('grooming','vet','pet_sitting') NOT NULL,
  `appointments_date` datetime NOT NULL,
  `sitters_id` int(11) DEFAULT NULL,
  `aa_id` int(11) DEFAULT NULL,
  `appointments_status` enum('pending','confirmed','completed','cancelled') DEFAULT NULL,
  `appointments_created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointments_id`, `users_id`, `appointments_full_name`, `appointments_email`, `appointments_phone`, `appointments_pet_name`, `appointments_pet_type`, `appointments_pet_breed`, `appointments_pet_age_years`, `appointments_type`, `appointments_date`, `sitters_id`, `aa_id`, `appointments_status`, `appointments_created_at`) VALUES
(1, 2, 'Pietro Escano', 'pe@gmail.com', '09738983249', 'Laurence', 'dog', 'German Sheperd', '2', 'grooming', '2025-09-25 09:00:00', NULL, NULL, 'pending', '2025-09-23 21:50:39'),
(2, 2, 'Ace Jerbis', 'ajo23@gmail.com', '09562378940', 'Peter', 'bird', 'Parrot', '4', 'pet_sitting', '2025-09-30 14:00:00', NULL, 1, 'cancelled', '2025-09-23 21:55:12'),
(3, 2, 'Ace Jerbis', 'ajo23@gmail.com', '09562378940', 'Peter', 'bird', 'Parrot', '4', 'pet_sitting', '2025-09-30 14:00:00', NULL, 2, 'pending', '2025-09-23 21:56:01'),
(4, 2, 'Jastin Andal', 'ja@gmail.com', '09632478324', 'Iris', 'cat', 'Persian', '3', 'vet', '2025-09-30 15:00:00', NULL, NULL, 'confirmed', '2025-09-23 22:30:00'),
(5, 2, 'Grd Mln', 'gm@gmail.com', '09834723942', 'Jape', 'dog', 'Golden Retriever', '6', 'pet_sitting', '2025-10-03 09:00:00', NULL, 3, 'pending', '2025-09-23 22:32:59'),
(6, 2, 'Brian', 'b@gmail.com', '09236784923', 'adwd', 'dog', '', '2', 'pet_sitting', '2025-10-01 09:00:00', NULL, 4, 'pending', '2025-09-23 23:14:09');

-- --------------------------------------------------------

--
-- Table structure for table `appointment_address`
--

CREATE TABLE `appointment_address` (
  `aa_id` int(11) NOT NULL,
  `appointments_id` int(11) NOT NULL,
  `aa_type` enum('home-sitting','drop_off') NOT NULL,
  `aa_address` varchar(255) DEFAULT NULL,
  `aa_city` varchar(100) DEFAULT NULL,
  `aa_province` varchar(255) NOT NULL,
  `aa_postal_code` varchar(20) DEFAULT NULL,
  `aa_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointment_address`
--

INSERT INTO `appointment_address` (`aa_id`, `appointments_id`, `aa_type`, `aa_address`, `aa_city`, `aa_province`, `aa_postal_code`, `aa_notes`) VALUES
(1, 2, 'home-sitting', '23, Poblacion', 'Padre Garcia', 'Batangas', '4224', ''),
(2, 3, 'home-sitting', '23, Poblacion', 'Padre Garcia', 'Batangas', '4224', ''),
(3, 5, 'home-sitting', '12, Pigain', 'San Jose', 'Batangas', '4227', 'papasok sa tulay'),
(4, 6, 'drop_off', '', '', '', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `deliveries`
--

CREATE TABLE `deliveries` (
  `deliveries_id` int(11) NOT NULL,
  `transactions_id` int(11) NOT NULL,
  `location_id` int(11) DEFAULT NULL,
  `deliveries_delivery_status` enum('processing','out_for_delivery','delivered','cancelled') DEFAULT 'processing',
  `deliveries_estimated_delivery_date` date DEFAULT NULL,
  `deliveries_actual_delivery_date` date DEFAULT NULL,
  `deliveries_recipient_signature` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deliveries`
--

INSERT INTO `deliveries` (`deliveries_id`, `transactions_id`, `location_id`, `deliveries_delivery_status`, `deliveries_estimated_delivery_date`, `deliveries_actual_delivery_date`, `deliveries_recipient_signature`) VALUES
(1, 3, 2, 'cancelled', '2025-09-28', NULL, NULL),
(2, 4, 1, 'delivered', '2025-09-29', '2025-09-29', '1'),
(3, 5, 1, 'delivered', '2025-09-30', '2025-09-28', 'Received 2025-09-28 14:05:05'),
(4, 6, 2, 'delivered', '2025-09-30', NULL, 'Received 2025-09-28 14:09:58'),
(5, 7, 2, 'delivered', '2025-09-30', '2025-09-28', 'Received 2025-09-28 14:21:28'),
(6, 8, 2, 'cancelled', '2025-09-30', NULL, 'cancelled'),
(7, 9, 2, 'cancelled', '2025-09-30', NULL, 'cancelled'),
(8, 10, 2, 'cancelled', '2025-09-30', NULL, NULL),
(9, 11, 2, 'delivered', '2025-09-30', '2025-09-28', 'Received 2025-09-28 15:55:50');

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `location_id` int(11) NOT NULL,
  `users_id` int(11) NOT NULL,
  `location_label` varchar(40) DEFAULT NULL,
  `location_recipient_name` varchar(120) NOT NULL,
  `location_phone` varchar(32) DEFAULT NULL,
  `location_address_line1` varchar(160) NOT NULL,
  `location_address_line2` varchar(160) DEFAULT NULL,
  `location_barangay` varchar(120) DEFAULT NULL,
  `location_city` varchar(120) NOT NULL,
  `location_province` varchar(120) NOT NULL,
  `location_is_default` tinyint(1) NOT NULL DEFAULT 0,
  `location_active` tinyint(1) NOT NULL DEFAULT 1,
  `location_created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `location_updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- (No triggers created; enforcement handled via users.default_location_id)

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`location_id`, `users_id`, `location_label`, `location_recipient_name`, `location_phone`, `location_address_line1`, `location_address_line2`, `location_barangay`, `location_city`, `location_province`, `location_is_default`, `location_active`, `location_created_at`, `location_updated_at`) VALUES
(1, 2, 'Home', 'Accel John', '09023347823', '012, Residence', 'beside alfamart', 'poblacion', 'lipa', 'batangas', 0, 1, '2025-09-26 21:12:41', '2025-09-26 21:12:41'),
(2, 2, 'Office', 'Angel Curtis', '09328942394', '56', 'Highway lang tabi ng shell', 'purok 2', 'lian', 'batangas', 0, 1, '2025-09-26 21:13:53', '2025-09-26 21:13:53');

-- --------------------------------------------------------

--
-- Table structure for table `pets`
--

CREATE TABLE `pets` (
  `pets_id` int(11) NOT NULL,
  `users_id` int(11) NOT NULL,
  `pets_name` varchar(100) NOT NULL,
  `pets_species` varchar(50) DEFAULT NULL,
  `pets_breed` varchar(100) DEFAULT NULL,
  `pets_gender` enum('male','female','unknown') DEFAULT NULL,
  `pets_image_url` varchar(255) DEFAULT NULL,
  `pets_created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pets`
--

INSERT INTO `pets` (`pets_id`, `users_id`, `pets_name`, `pets_species`, `pets_breed`, `pets_gender`, `pets_image_url`, `pets_created_at`) VALUES
(1, 2, 'Fred', 'Dog', 'Shih Tzu', 'male', NULL, '2025-09-23 17:49:35'),
(2, 2, 'Kitty', 'Cat', 'Egyptian', 'female', NULL, '2025-09-23 17:50:19'),
(10, 2, 'Jen', 'Horse', '', 'male', NULL, '2025-09-26 20:13:48'),
(13, 2, 'Uno', 'Bird', 'Vulture', 'male', NULL, '2025-09-26 21:23:43');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `products_id` int(11) NOT NULL,
  `products_name` varchar(100) NOT NULL,
  `products_pet_type` varchar(50) DEFAULT NULL,
  `products_description` text DEFAULT NULL,
  `products_category` enum('food','accessory','necessity','toy') DEFAULT NULL,
  `products_price` decimal(10,2) NOT NULL,
  `products_original_price` decimal(10,2) DEFAULT NULL,
  `products_stock` varchar(255) DEFAULT NULL,
  `products_image_url` varchar(255) DEFAULT NULL,
  `products_active` tinyint(1) DEFAULT 1,
  `products_created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`products_id`, `products_name`, `products_pet_type`, `products_description`, `products_category`, `products_price`, `products_original_price`, `products_stock`, `products_image_url`, `products_active`, `products_created_at`) VALUES
(1, 'Goodest Tuna', 'Cat', 'Offer complete and balanced nutrition made with real tuna, natural ingredients, and added vitamins and minerals to support overall health', 'food', 70.00, NULL, '50', 'pictures/products/Tender-Tuna-1-1758305460-1056.png', 1, '2025-09-20 02:11:00'),
(2, 'Pedigree ADULT Food', 'Dog', 'a budget-friendly line of complete and balanced dry and wet food designed to meet the needs of adult dogs, focusing on four key areas: healthy skin and coat (from omega-6 fatty acids and zinc), a strong immune system (via vitamins and minerals), good digestion (with dietary fiber), and dental care (through its kibble texture)', 'food', 250.00, NULL, '15', 'pictures/products/jsdoqberm33yvec6iqx6-1758306068-3183.png', 0, '2025-09-20 02:21:08'),
(3, 'Kibbles n'' Bits', 'Dog', 'dry dog food known for its unique dual-textured food, which combines crunchy kibble with tender, meaty bits for a satisfying and flavorful meal', 'food', 300.00, NULL, '30', 'pictures/products/Kibblesn-Bits-Original-Beef-Chicken-Dry-Dog-Food-3-5LB-1024x1024-1758340461-5998.png', 1, '2025-09-20 11:54:21'),
(4, 'Kibbles n'' Bits MINI BITS', 'Dog', 'small, dry dog food kibbles designed for small breeds, though suitable for all sizes, with flavors like bacon & steak or beef & chicken, featuring crunchy and tender, meat-filled pieces easy to chew and digest', 'food', 250.00, NULL, '25', 'pictures/products/Kibblesn-Bits-Bacon-Steak-Flavor-Small-Breeds-Dry-Dog-Food-3-5LB-1758340519-5469.png', 0, '2025-09-20 11:55:19'),
(5, 'qwaed', 'Dog', 'aqwrfasdf', 'accessory', 23.00, NULL, '231', NULL, 1, '2025-09-20 12:04:58'),
(6, '23qrwa', 'Bird', 'adwasd', 'necessity', 333.00, NULL, '23', NULL, 0, '2025-09-20 12:05:12'),
(7, '23qrweqard', 'Small Pet', 'aes fdsfewas', 'necessity', 234.00, NULL, '1234', NULL, 0, '2025-09-20 12:05:26'),
(8, '23qwra', 'Cat', 'waqdc', 'necessity', 234.00, NULL, '234', NULL, 1, '2025-09-20 12:05:36'),
(9, 'q3awr3weqr', 'Cat', '23wetfesdf', 'necessity', 345.00, NULL, '234', NULL, 1, '2025-09-20 12:05:50'),
(10, 't43wegdxz', 'Bird', 'sdregsdfg', 'necessity', 3425.00, NULL, '324', NULL, 1, '2025-09-20 12:06:20'),
(11, '345rt', 'Bird', '6346', 'necessity', 345.00, NULL, '234', NULL, 1, '2025-09-20 12:06:36'),
(12, 'feswd', 'Dog', 'rewdfeasdf', 'accessory', 23.00, NULL, '33', NULL, 0, '2025-09-27 21:44:22');

-- --------------------------------------------------------

--
-- Table structure for table `promotions`
--

CREATE TABLE `promotions` (
  `promo_id` int(11) NOT NULL,
  `promo_type` enum('product','appointment') NOT NULL,
  `promo_code` varchar(40) DEFAULT NULL,
  `promo_name` varchar(150) NOT NULL,
  `promo_description` text DEFAULT NULL,
  `promo_discount_type` enum('percent','fixed','points_bonus','free_item','none') NOT NULL DEFAULT 'none',
  `promo_discount_value` decimal(10,2) DEFAULT NULL,
  `promo_points_cost` int(11) DEFAULT NULL,
  `free_product_id` int(11) DEFAULT NULL,
  `promo_min_purchase_amount` decimal(10,2) DEFAULT NULL,
  `promo_usage_limit` int(11) DEFAULT NULL,
  `promo_per_user_limit` int(11) DEFAULT NULL,
  `promo_require_active_subscription` tinyint(1) NOT NULL DEFAULT 0,
  `promo_starts_at` datetime NOT NULL,
  `promo_ends_at` datetime NOT NULL,
  `promo_active` tinyint(1) NOT NULL DEFAULT 1,
  `promo_created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `promo_updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `promotions`
--

INSERT INTO `promotions` (`promo_id`, `promo_type`, `promo_code`, `promo_name`, `promo_description`, `promo_discount_type`, `promo_discount_value`, `promo_points_cost`, `free_product_id`, `promo_min_purchase_amount`, `promo_usage_limit`, `promo_per_user_limit`, `promo_require_active_subscription`, `promo_starts_at`, `promo_ends_at`, `promo_active`, `promo_created_at`, `promo_updated_at`) VALUES
(1, 'appointment', 'RDSGF45', 'Free appointment', 'awdawdaw', 'free_item', NULL, 400, NULL, NULL, NULL, NULL, 1, '2025-10-01 10:41:00', '2025-10-31 23:59:00', 1, '2025-10-01 10:42:32', '2025-10-03 19:55:49'),
(2, 'product', '3WBWPBJA', '20% OFF ON ALL PRODUCTS', 'efwsfwe', 'percent', 20.00, 100, NULL, 200.00, NULL, NULL, 1, '2025-10-01 00:00:00', '2025-10-31 23:59:00', 1, '2025-10-01 22:41:58', '2025-10-01 22:57:30'),
(3, 'appointment', 'XYSD4N7P', 'FREE GROOMING', '', 'free_item', NULL, NULL, NULL, NULL, NULL, 1, 1, '2025-10-02 01:02:00', '2025-10-31 23:59:00', 1, '2025-10-02 01:03:04', '2025-10-03 18:12:29'),
(4, 'product', 'RWUBC42F', 'AUTUMN SALE 10% ALL PRODUCTS', 'FEGWSRFE', 'percent', 10.00, 10, NULL, NULL, NULL, 1, 1, '2025-10-01 18:27:00', '2025-10-10 18:27:00', 1, '2025-10-03 18:27:49', '2025-10-03 18:35:01'),
(5, 'appointment', 'SQAW2K97', '20% FOR PET SITTING', 'AWDAWSD', 'percent', 20.00, 10, NULL, NULL, NULL, 1, 1, '2025-10-05 03:00:00', '2025-10-13 08:00:00', 1, '2025-10-05 03:50:53', '2025-10-05 03:52:23'),
(6, 'appointment', '2BULMU7J', '10% for grooming', 'wadsdwa', 'percent', 10.00, 10, NULL, NULL, NULL, 1, 1, '2025-10-05 04:07:00', '2025-10-13 04:07:00', 1, '2025-10-05 04:07:56', NULL),
(7, 'product', 'EBR7B3EN', 'FREE PET SITTING', 'AWSDWAS', 'free_item', NULL, 10, NULL, NULL, NULL, 1, 1, '2025-10-05 13:24:00', '2025-10-13 13:24:00', 1, '2025-10-05 13:24:17', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `promotion_codes`
--

CREATE TABLE `promotion_codes` (
  `code_id` bigint(20) NOT NULL,
  `promo_id` int(11) NOT NULL,
  `users_id` int(11) DEFAULT NULL,
  `pc_code` varchar(32) NOT NULL,
  `pc_code_format` enum('text','qr') NOT NULL,
  `pc_qr_image_path` varchar(255) DEFAULT NULL,
  `pc_assigned_at` datetime DEFAULT NULL,
  `pc_expires_at` datetime DEFAULT NULL,
  `pc_used` tinyint(1) NOT NULL DEFAULT 0,
  `pc_created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promotion_redemptions`
--

CREATE TABLE `promotion_redemptions` (
  `redemption_id` bigint(20) NOT NULL,
  `promo_id` int(11) NOT NULL,
  `code_id` bigint(20) DEFAULT NULL,
  `users_id` int(11) NOT NULL,
  `transactions_id` int(11) DEFAULT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `pr_status` enum('reserved','applied','cancelled') NOT NULL DEFAULT 'reserved',
  `pr_discount_amount` decimal(10,2) DEFAULT NULL,
  `pr_points_spent` int(11) DEFAULT NULL,
  `pr_created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `pr_applied_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `promotion_redemptions`
--

INSERT INTO `promotion_redemptions` (`redemption_id`, `promo_id`, `code_id`, `users_id`, `transactions_id`, `appointment_id`, `pr_status`, `pr_discount_amount`, `pr_points_spent`, `pr_created_at`, `pr_applied_at`) VALUES
(1, 4, NULL, 2, 10, NULL, 'applied', 30.00, NULL, '2025-10-03 18:57:52', '2025-10-03 18:57:52'),
(2, 2, NULL, 2, 11, NULL, 'applied', 69.00, NULL, '2025-10-03 18:58:27', '2025-10-03 18:58:27'),
(3, 2, NULL, 2, 12, NULL, 'applied', 60.00, NULL, '2025-10-03 18:59:15', '2025-10-03 18:59:15'),
(4, 2, NULL, 2, 13, NULL, 'applied', 69.00, NULL, '2025-10-03 19:07:16', '2025-10-03 19:07:16');
-- --------------------------------------------------------

--
-- Table structure for table `sitters`
--

CREATE TABLE `sitters` (
  `sitters_id` int(11) NOT NULL,
  `sitters_name` varchar(100) NOT NULL,
  `sitters_bio` text DEFAULT NULL,
  `sitter_email` varchar(255) NOT NULL,
  `sitters_contact` varchar(255) DEFAULT NULL,
  `sitter_specialty` varchar(255) NOT NULL,
  `sitters_image_url` varchar(255) DEFAULT NULL,
  `sitters_active` tinyint(1) DEFAULT 1,
  `sitters_created_at` datetime DEFAULT current_timestamp(),
  `sitters_verified` tinyint(1) NOT NULL DEFAULT 0,
  `years_experience` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sitters`
--

INSERT INTO `sitters` (`sitters_id`, `sitters_name`, `sitters_bio`, `sitter_email`, `sitters_contact`, `sitter_specialty`, `sitters_image_url`, `sitters_active`, `sitters_created_at`, `sitters_verified`, `years_experience`) VALUES
(1, 'John Ricardo', 'qw3aed', 'jr@gmail.com', '0956 789 0999', '', 'pictures/sitters/images-1758347866-6335.jpg', 1, '2025-09-20 13:57:46', 1, NULL),
(2, 'Jastin', 'asewdawd', 'ja@gmail.com', '09283946727', 'Dog, Cat, Fish', 'pictures/sitters/luffy-1758964723-6685.png', 1, '2025-09-27 17:18:43', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

CREATE TABLE `subscriptions` (
  `subscriptions_id` int(11) NOT NULL,
  `subscriptions_name` varchar(100) NOT NULL,
  `subscriptions_description` text DEFAULT NULL,
  `subscriptions_price` decimal(10,2) NOT NULL,
  `subscriptions_duration_days` int(11) NOT NULL,
  `subscriptions_active` tinyint(1) DEFAULT 1,
  `subscriptions_created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subscriptions`
--

INSERT INTO `subscriptions` (`subscriptions_id`, `subscriptions_name`, `subscriptions_description`, `subscriptions_price`, `subscriptions_duration_days`, `subscriptions_active`, `subscriptions_created_at`) VALUES
(1, 'Premium', 'Premium Plan: Priority booking, premium sitters, support, and discounts', 299.00, 30, 1, '2025-09-29 01:38:18');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `transactions_id` int(11) NOT NULL,
  `users_id` int(11) NOT NULL,
  `transactions_amount` decimal(10,2) NOT NULL,
  `transactions_type` enum('product','subscription') NOT NULL,
  `transactions_payment_method` enum('cod','gcash','maya') DEFAULT NULL,
  `transactions_created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`transactions_id`, `users_id`, `transactions_amount`, `transactions_type`, `transactions_payment_method`, `transactions_created_at`) VALUES
(3, 2, 4165.00, 'product', 'gcash', '2025-09-26 21:26:52'),
(4, 2, 740.00, 'product', 'cod', '2025-09-27 17:34:43'),
(5, 2, 120.00, 'product', 'gcash', '2025-09-28 13:51:08'),
(6, 2, 350.00, 'product', 'cod', '2025-09-28 14:08:41'),
(7, 2, 120.00, 'product', 'cod', '2025-09-28 14:20:43'),
(8, 2, 350.00, 'product', 'cod', '2025-09-28 14:25:37'),
(9, 2, 395.00, 'product', 'cod', '2025-09-28 14:27:33'),
(10, 2, 395.00, 'product', 'cod', '2025-09-28 14:34:17'),
(11, 2, 3545.00, 'product', 'gcash', '2025-09-28 15:55:01');

-- --------------------------------------------------------

--
-- Table structure for table `transaction_products`
--

CREATE TABLE `transaction_products` (
  `tp_id` int(11) NOT NULL,
  `transactions_id` int(11) NOT NULL,
  `products_id` int(11) NOT NULL,
  `tp_quantity` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaction_products`
--

INSERT INTO `transaction_products` (`tp_id`, `transactions_id`, `products_id`, `tp_quantity`) VALUES
(3, 3, 9, '1'),
(4, 3, 10, '1'),
(5, 3, 11, '1'),
(6, 4, 9, '2'),
(7, 5, 1, '1'),
(8, 6, 3, '1'),
(9, 7, 1, '1'),
(10, 8, 3, '1'),
(11, 9, 9, '1'),
(12, 10, 9, '1'),
(13, 11, 10, '1'),
(14, 11, 1, '1');

-- --------------------------------------------------------

--
-- Table structure for table `transaction_subscriptions`
--

CREATE TABLE `transaction_subscriptions` (
  `ts_id` int(11) NOT NULL,
  `transactions_id` int(11) NOT NULL,
  `us_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `users_id` int(11) NOT NULL,
  `users_firstname` varchar(255) NOT NULL,
  `users_lastname` varchar(255) NOT NULL,
  `users_username` varchar(50) NOT NULL,
  `users_email` varchar(255) NOT NULL,
  `users_password_hash` varchar(255) NOT NULL,
  `users_role` varchar(1) NOT NULL,
  `users_image_url` varchar(255) DEFAULT NULL,
  `default_location_id` int(11) DEFAULT NULL,
  `users_created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`users_id`, `users_firstname`, `users_lastname`, `users_username`, `users_email`, `users_password_hash`, `users_role`, `users_image_url`, `default_location_id`, `users_created_at`) VALUES
(1, 'Admin', '', 'ADMIN', 'admin256@admin.com', 'Abcd@1234', '1', NULL, NULL, '2025-09-18 21:52:11'),
(2, 'Accel', 'John', 'ajo23', 'ajo23@gmail.com', 'Acejohn123@', '0', 'pictures/users/u2_1758863948_5683265be4.png', NULL, '2025-09-20 16:12:45'),
(3, 'Grade', 'Lat', 'glat', 'glat21@gmail.com', 'Glat1234!', '0', NULL, NULL, '2025-09-23 13:23:55');

-- --------------------------------------------------------

--
-- Table structure for table `user_points_balance`
--

CREATE TABLE `user_points_balance` (
  `users_id` int(11) NOT NULL,
  `upb_points` int(11) NOT NULL DEFAULT 0,
  `upb_points_balance` int(11) NOT NULL DEFAULT 0,
  `upb_updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_points_balance`
--

INSERT INTO `user_points_balance` (`users_id`, `upb_points`, `upb_points_balance`, `upb_updated_at`) VALUES
(2, 360, 0, '2025-10-05 13:43:47'),
(3, 30, 0, '2025-10-03 19:39:30');

-- --------------------------------------------------------

--
-- Table structure for table `user_points_ledger`
--

CREATE TABLE `user_points_ledger` (
  `upl_id` bigint(20) NOT NULL,
  `users_id` int(11) NOT NULL,
  `upl_points` int(11) NOT NULL DEFAULT 0,
  `upl_reason` varchar(100) DEFAULT NULL,
  `upl_source_type` enum('purchase','promo','manual','reversal') NOT NULL,
  `upl_source_id` bigint(20) DEFAULT NULL,
  `upl_points_change` int(11) NOT NULL,
  `upl_balance_after` int(11) NOT NULL,
  `upl_note` varchar(200) DEFAULT NULL,
  `upl_created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_points_ledger`
--

INSERT INTO `user_points_ledger` (`upl_id`, `users_id`, `upl_points`, `upl_reason`, `upl_source_type`, `upl_source_id`, `upl_points_change`, `upl_balance_after`, `upl_note`, `upl_created_at`) VALUES
(1, 2, 30, 'Appointment Completed', '', 6, 0, 0, NULL, '2025-10-02 21:21:45'),
(2, 2, 30, 'Appointment Completed', '', 10, 0, 0, NULL, '2025-10-02 21:24:50'),
(3, 2, 30, 'Appointment Completed', '', 5, 0, 0, NULL, '2025-10-02 21:25:05'),
(4, 2, 30, 'Appointment Completed', '', 4, 0, 0, NULL, '2025-10-02 22:17:24'),
(5, 2, 30, 'Order Received', '', 4, 0, 0, NULL, '2025-10-02 23:12:48'),
(6, 2, -100, 'Promo Claim', 'promo', 2, 0, 0, NULL, '2025-10-02 23:13:31'),
(7, 2, 30, 'Order Received', '', 4, 0, 0, NULL, '2025-10-03 00:33:23'),
(8, 2, 30, 'Order Received', '', 3, 0, 0, NULL, '2025-10-03 00:33:27'),
(9, 2, 30, 'Order Received', '', 4, 0, 0, NULL, '2025-10-03 00:55:51'),
(10, 2, 50, 'Order Received', '', 6, 0, 0, NULL, '2025-10-03 03:07:12'),
(11, 2, 50, 'Order Received', '', 6, 0, 0, NULL, '2025-10-03 03:15:12'),
(12, 2, -10, 'Promo Claim', 'promo', 4, 0, 0, NULL, '2025-10-03 18:35:13'),
(13, 3, 30, 'Order Received', '', 15, 0, 0, NULL, '2025-10-03 19:39:30'),
(14, 2, 20, 'Order Received', '', 13, 0, 0, NULL, '2025-10-05 01:33:45'),
(15, 2, -10, 'Promo Claim', 'promo', 5, 0, 0, NULL, '2025-10-05 04:12:32'),
(16, 2, 30, 'Appointment Completed', '', 16, 0, 0, NULL, '2025-10-05 12:27:53'),
(17, 2, 20, 'Order Received', '', 12, 0, 0, NULL, '2025-10-05 12:54:17'),
(18, 2, 30, 'Appointment Completed', '', 12, 0, 0, NULL, '2025-10-05 12:54:47'),
(19, 2, 20, 'Order Received', '', 11, 0, 0, NULL, '2025-10-05 13:40:44'),
(20, 2, 20, 'Order Received', '', 10, 0, 0, NULL, '2025-10-05 13:43:47');

-- --------------------------------------------------------

--
-- Table structure for table `user_promos`
--

CREATE TABLE `user_promos` (
  `up_id` int(11) NOT NULL,
  `users_id` int(11) NOT NULL,
  `promo_id` int(11) NOT NULL,
  `up_code` varchar(64) NOT NULL,
  `up_claimed_at` datetime NOT NULL DEFAULT current_timestamp(),
  `up_redeemed_at` datetime DEFAULT NULL,
  `up_qr_svg` mediumtext DEFAULT NULL,
  `up_qr_token` varchar(128) DEFAULT NULL,
  `up_qr_token_created_at` datetime DEFAULT NULL,
  `up_qr_token_redeemed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_promos`
--

INSERT INTO `user_promos` (`up_id`, `users_id`, `promo_id`, `up_code`, `up_claimed_at`, `up_redeemed_at`, `up_qr_svg`, `up_qr_token`, `up_qr_token_created_at`, `up_qr_token_redeemed_at`) VALUES
(1, 2, 3, 'XYSD4N7P-U2-528CD7', '2025-10-02 21:21:03', '2025-10-05 03:42:47', NULL, 'O3sitbmYigDpx_zzEEB-Yqe0T2jHUSRCKkq3ni0Yp98', '2025-10-05 03:42:06', '2025-10-05 03:42:47'),
(2, 2, 2, '3WBWPBJA-U2-F90E94', '2025-10-02 23:13:31', NULL, NULL, NULL, NULL, NULL),
(3, 2, 4, 'RWUBC42F-U2-9DEA3F', '2025-10-03 18:35:13', NULL, NULL, NULL, NULL, NULL),
(4, 3, 3, 'XYSD4N7P-U3-AE7050', '2025-10-03 21:48:00', NULL, NULL, NULL, NULL, NULL),
(5, 2, 5, 'SQAW2K97-U2-A4CDBC', '2025-10-05 04:12:32', '2025-10-05 04:13:01', NULL, 'ODq_qIbSSCR7bTWMSyXkjFk0uLQ_GlVUr6LzuOfGJt4', '2025-10-05 04:12:38', '2025-10-05 04:13:01');

-- --------------------------------------------------------

--
-- Table structure for table `user_subscriptions`
--

CREATE TABLE `user_subscriptions` (
  `us_id` int(11) NOT NULL,
  `users_id` int(11) NOT NULL,
  `subscriptions_id` int(11) NOT NULL,
  `us_start_date` datetime NOT NULL,
  `us_end_date` datetime DEFAULT NULL,
  `us_status` enum('active','expired','cancelled') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_subscriptions`
--

INSERT INTO `user_subscriptions` (`us_id`, `users_id`, `subscriptions_id`, `us_start_date`, `us_end_date`, `us_status`) VALUES
(1, 2, 1, '2025-09-28 19:39:02', '2025-10-28 19:39:02', 'active'),
(2, 3, 1, '2025-10-03 13:37:57', '2025-10-03 19:39:42', 'cancelled');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_activity_logs`
--
ALTER TABLE `admin_activity_logs`
  ADD PRIMARY KEY (`admin_activity_logs_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointments_id`),
  ADD KEY `users_id` (`users_id`),
  ADD KEY `sitters_id` (`sitters_id`),
  ADD KEY `appointments_ibfk_4` (`aa_id`);

--
-- Indexes for table `appointment_address`
--
ALTER TABLE `appointment_address`
  ADD PRIMARY KEY (`aa_id`),
  ADD UNIQUE KEY `appointments_id` (`appointments_id`);

--
-- Indexes for table `deliveries`
--
ALTER TABLE `deliveries`
  ADD PRIMARY KEY (`deliveries_id`),
  ADD UNIQUE KEY `transactions_id` (`transactions_id`),
  ADD KEY `location_id` (`location_id`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`location_id`),
  ADD KEY `users_id` (`users_id`),
  ADD KEY `users_default` (`users_id`,`location_is_default`);

--
-- Indexes for table `pets`
--
ALTER TABLE `pets`
  ADD PRIMARY KEY (`pets_id`),
  ADD KEY `users_id` (`users_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`products_id`);

--
-- Indexes for table `sitters`
--
ALTER TABLE `sitters`
  ADD PRIMARY KEY (`sitters_id`);

--
-- Indexes for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`subscriptions_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`transactions_id`),
  ADD KEY `users_id` (`users_id`);

--
-- Indexes for table `transaction_products`
--
ALTER TABLE `transaction_products`
  ADD PRIMARY KEY (`tp_id`),
  ADD KEY `transactions_id` (`transactions_id`),
  ADD KEY `products_id` (`products_id`);

--
-- Indexes for table `transaction_subscriptions`
--
ALTER TABLE `transaction_subscriptions`
  ADD PRIMARY KEY (`ts_id`),
  ADD UNIQUE KEY `transactions_id` (`transactions_id`),
  ADD UNIQUE KEY `us_id` (`us_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`users_id`),
  ADD UNIQUE KEY `users_username` (`users_username`),
  ADD UNIQUE KEY `users_email` (`users_email`),
  ADD KEY `default_location_id` (`default_location_id`);

--
-- Indexes for table `user_subscriptions`
--
ALTER TABLE `user_subscriptions`
  ADD PRIMARY KEY (`us_id`),
  ADD KEY `users_id` (`users_id`),
  ADD KEY `subscriptions_id` (`subscriptions_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_activity_logs`
--
ALTER TABLE `admin_activity_logs`
  MODIFY `admin_activity_logs_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointments_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `appointment_address`
--
ALTER TABLE `appointment_address`
  MODIFY `aa_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `deliveries`
--
ALTER TABLE `deliveries`
  MODIFY `deliveries_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `location_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `pets`
--
ALTER TABLE `pets`
  MODIFY `pets_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `products_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Indexes for table `promotions`
--
ALTER TABLE `promotions`
  ADD PRIMARY KEY (`promo_id`),
  ADD UNIQUE KEY `uq_promotions_promo_code` (`promo_code`),
  ADD KEY `idx_promotions_active_dates` (`promo_active`,`promo_starts_at`,`promo_ends_at`),
  ADD KEY `idx_promotions_type` (`promo_type`),
  ADD KEY `promotions_free_product_fk` (`free_product_id`);

--
-- AUTO_INCREMENT for table `sitters`
--
ALTER TABLE `sitters`
  MODIFY `sitters_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `subscriptions_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `transactions_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `transaction_products`
--
ALTER TABLE `transaction_products`
  MODIFY `tp_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `transaction_subscriptions`
--
ALTER TABLE `transaction_subscriptions`
  MODIFY `ts_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `users_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_subscriptions`
--
ALTER TABLE `user_subscriptions`
  MODIFY `us_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Indexes for table `promotion_codes`
--
ALTER TABLE `promotion_codes`
  ADD PRIMARY KEY (`code_id`),
  ADD UNIQUE KEY `uq_promotion_codes_code` (`pc_code`),
  ADD KEY `idx_promotion_codes_promo` (`promo_id`),
  ADD KEY `idx_promotion_codes_user` (`users_id`),
  ADD KEY `idx_promotion_codes_format` (`pc_code_format`);

--
-- Indexes for table `promotion_redemptions`
--
ALTER TABLE `promotion_redemptions`
  ADD PRIMARY KEY (`redemption_id`),
  ADD KEY `idx_promo_user` (`promo_id`,`users_id`),
  ADD KEY `idx_code_id` (`code_id`),
  ADD KEY `idx_pr_status` (`pr_status`),
  ADD KEY `idx_transactions` (`transactions_id`),
  ADD KEY `idx_appointment` (`appointment_id`),
  ADD KEY `pr_user_fk` (`users_id`),
  ADD KEY `idx_redemptions_promo_status` (`promo_id`,`pr_status`);

--
-- Indexes for table `user_points_balance`
--
ALTER TABLE `user_points_balance`
  ADD PRIMARY KEY (`users_id`);

--
-- Indexes for table `user_points_ledger`
--
ALTER TABLE `user_points_ledger`
  ADD PRIMARY KEY (`upl_id`),
  ADD KEY `idx_upl_user` (`users_id`),
  ADD KEY `idx_upl_source` (`upl_source_type`,`upl_source_id`);

--
-- Indexes for table `user_promos`
--
ALTER TABLE `user_promos`
  ADD PRIMARY KEY (`up_id`),
  ADD UNIQUE KEY `uniq_user_promo` (`users_id`,`promo_id`),
  ADD KEY `idx_user` (`up_id`,`users_id`),
  ADD KEY `fk_up_promo` (`promo_id`);

--
-- AUTO_INCREMENT for new tables
--
ALTER TABLE `promotions`
  MODIFY `promo_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
ALTER TABLE `promotion_codes`
  MODIFY `code_id` bigint(20) NOT NULL AUTO_INCREMENT;
ALTER TABLE `promotion_redemptions`
  MODIFY `redemption_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
ALTER TABLE `user_points_ledger`
  MODIFY `upl_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;
ALTER TABLE `user_promos`
  MODIFY `up_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_activity_logs`
--
ALTER TABLE `admin_activity_logs`
  ADD CONSTRAINT `admin_activity_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`users_id`) ON DELETE CASCADE;

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`users_id`) REFERENCES `users` (`users_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`sitters_id`) REFERENCES `sitters` (`sitters_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `appointments_ibfk_4` FOREIGN KEY (`aa_id`) REFERENCES `appointment_address` (`aa_id`) ON DELETE SET NULL;

--
-- Constraints for table `appointment_address`
--
ALTER TABLE `appointment_address`
  ADD CONSTRAINT `appointment_address_ibfk_1` FOREIGN KEY (`appointments_id`) REFERENCES `appointments` (`appointments_id`) ON DELETE CASCADE;

--
-- Constraints for table `deliveries`
--
ALTER TABLE `deliveries`
  ADD CONSTRAINT `deliveries_ibfk_1` FOREIGN KEY (`transactions_id`) REFERENCES `transactions` (`transactions_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `deliveries_ibfk_2` FOREIGN KEY (`location_id`) REFERENCES `locations` (`location_id`) ON DELETE SET NULL;

--
-- Constraints for table `locations`
--
ALTER TABLE `locations`
  ADD CONSTRAINT `locations_ibfk_1` FOREIGN KEY (`users_id`) REFERENCES `users` (`users_id`) ON DELETE CASCADE;

--
-- Constraints for table `pets`
--
ALTER TABLE `pets`
  ADD CONSTRAINT `pets_ibfk_1` FOREIGN KEY (`users_id`) REFERENCES `users` (`users_id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`users_id`) REFERENCES `users` (`users_id`) ON DELETE CASCADE;

--
-- Constraints for table `transaction_products`
--
ALTER TABLE `transaction_products`
  ADD CONSTRAINT `transaction_products_ibfk_1` FOREIGN KEY (`transactions_id`) REFERENCES `transactions` (`transactions_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transaction_products_ibfk_2` FOREIGN KEY (`products_id`) REFERENCES `products` (`products_id`) ON DELETE CASCADE;

--
-- Constraints for table `transaction_subscriptions`
--
ALTER TABLE `transaction_subscriptions`
  ADD CONSTRAINT `transaction_subscriptions_ibfk_1` FOREIGN KEY (`transactions_id`) REFERENCES `transactions` (`transactions_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transaction_subscriptions_ibfk_2` FOREIGN KEY (`us_id`) REFERENCES `user_subscriptions` (`us_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_subscriptions`
--
ALTER TABLE `user_subscriptions`
  ADD CONSTRAINT `user_subscriptions_ibfk_1` FOREIGN KEY (`users_id`) REFERENCES `users` (`users_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_subscriptions_ibfk_2` FOREIGN KEY (`subscriptions_id`) REFERENCES `subscriptions` (`subscriptions_id`) ON DELETE CASCADE;

--
-- New constraint for Option C: users.default_location_id -> locations.location_id
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_fk_default_location`
  FOREIGN KEY (`default_location_id`) REFERENCES `locations` (`location_id`) ON DELETE SET NULL;

--
-- Constraints for new promo tables
--
ALTER TABLE `promotions`
  ADD CONSTRAINT `promotions_free_product_fk` FOREIGN KEY (`free_product_id`) REFERENCES `products` (`products_id`) ON DELETE SET NULL;

ALTER TABLE `promotion_codes`
  ADD CONSTRAINT `promotion_codes_promo_fk` FOREIGN KEY (`promo_id`) REFERENCES `promotions` (`promo_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `promotion_codes_user_fk` FOREIGN KEY (`users_id`) REFERENCES `users` (`users_id`) ON DELETE SET NULL;

ALTER TABLE `promotion_redemptions`
  ADD CONSTRAINT `pr_appt_fk` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointments_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pr_code_fk` FOREIGN KEY (`code_id`) REFERENCES `promotion_codes` (`code_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pr_promo_fk` FOREIGN KEY (`promo_id`) REFERENCES `promotions` (`promo_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pr_tx_fk` FOREIGN KEY (`transactions_id`) REFERENCES `transactions` (`transactions_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pr_user_fk` FOREIGN KEY (`users_id`) REFERENCES `users` (`users_id`) ON DELETE CASCADE;

--
-- Constraints for user points and promos tables
--
ALTER TABLE `user_points_balance`
  ADD CONSTRAINT `upb_user_fk` FOREIGN KEY (`users_id`) REFERENCES `users` (`users_id`) ON DELETE CASCADE;

ALTER TABLE `user_points_ledger`
  ADD CONSTRAINT `upl_user_fk` FOREIGN KEY (`users_id`) REFERENCES `users` (`users_id`) ON DELETE CASCADE;

ALTER TABLE `user_promos`
  ADD CONSTRAINT `fk_up_promo` FOREIGN KEY (`promo_id`) REFERENCES `promotions` (`promo_id`) ON DELETE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
 /*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
 /*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;