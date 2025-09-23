-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 23, 2025 at 06:47 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pawdb`
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
  `deliveries_address` varchar(255) NOT NULL,
  `deliveries_city` varchar(100) DEFAULT NULL,
  `deliveries_postal_code` varchar(20) DEFAULT NULL,
  `deliveries_delivery_status` enum('processing','out_for_delivery','delivered','cancelled') DEFAULT 'processing',
  `deliveries_estimated_delivery_date` date DEFAULT NULL,
  `deliveries_actual_delivery_date` date DEFAULT NULL,
  `deliveries_recipient_signature` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(2, 2, 'Kitty', 'Cat', 'Egyptian', 'female', NULL, '2025-09-23 17:50:19');

-- --------------------------------------------------------

--
-- Table structure for table `pickups`
--

CREATE TABLE `pickups` (
  `pickups_id` int(11) NOT NULL,
  `transactions_id` int(11) NOT NULL,
  `pickups_pickup_date` date NOT NULL,
  `pickups_pickup_time` time NOT NULL CHECK (`pickups_pickup_time` between '08:00:00' and '17:00:00'),
  `pickups_pickup_status` enum('scheduled','picked_up','cancelled') DEFAULT 'scheduled'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `products_stock` varchar(255) DEFAULT NULL,
  `products_image_url` varchar(255) DEFAULT NULL,
  `products_active` tinyint(1) DEFAULT 1,
  `products_created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`products_id`, `products_name`, `products_pet_type`, `products_description`, `products_category`, `products_price`, `products_stock`, `products_image_url`, `products_active`, `products_created_at`) VALUES
(1, 'Goodest Tuna', 'Cat', 'Offer complete and balanced nutrition made with real tuna, natural ingredients, and added vitamins and minerals to support overall health', 'food', 70.00, '50', 'pictures/products/Tender-Tuna-1-1758305460-1056.png', 1, '2025-09-20 02:11:00'),
(2, 'Pedigree ADULT Food', 'Dog', 'a budget-friendly line of complete and balanced dry and wet food designed to meet the needs of adult dogs, focusing on four key areas: healthy skin and coat (from omega-6 fatty acids and zinc), a strong immune system (via vitamins and minerals), good digestion (with dietary fiber), and dental care (through its kibble texture)', 'food', 250.00, '15', 'pictures/products/jsdoqberm33yvec6iqx6-1758306068-3183.png', 0, '2025-09-20 02:21:08'),
(3, 'Kibbles n\' Bits', 'Dog', 'dry dog food known for its unique dual-textured food, which combines crunchy kibble with tender, meaty bits for a satisfying and flavorful meal', 'food', 300.00, '30', 'pictures/products/Kibblesn-Bits-Original-Beef-Chicken-Dry-Dog-Food-3-5LB-1024x1024-1758340461-5998.png', 1, '2025-09-20 11:54:21'),
(4, 'Kibbles n\' Bits MINI BITS', 'Dog', 'small, dry dog food kibbles designed for small breeds, though suitable for all sizes, with flavors like bacon & steak or beef & chicken, featuring crunchy and tender, meat-filled pieces easy to chew and digest', 'food', 250.00, '25', 'pictures/products/Kibblesn-Bits-Bacon-Steak-Flavor-Small-Breeds-Dry-Dog-Food-3-5LB-1758340519-5469.png', 0, '2025-09-20 11:55:19'),
(5, 'qwaed', 'Dog', 'aqwrfasdf', 'accessory', 23.00, '231', NULL, 1, '2025-09-20 12:04:58'),
(6, '23qrwa', 'Bird', 'adwasd', 'necessity', 333.00, '23', NULL, 0, '2025-09-20 12:05:12'),
(7, '23qrweqard', 'Small Pet', 'aes fdsfewas', 'necessity', 234.00, '1234', NULL, 0, '2025-09-20 12:05:26'),
(8, '23qwra', 'Cat', 'waqdc', 'necessity', 234.00, '234', NULL, 1, '2025-09-20 12:05:36'),
(9, 'q3awr3weqr', 'Cat', '23wetfesdf', 'necessity', 345.00, '234', NULL, 1, '2025-09-20 12:05:50'),
(10, 't43wegdxz', 'Bird', 'sdregsdfg', 'necessity', 3425.00, '324', NULL, 1, '2025-09-20 12:06:20'),
(11, '345rt', 'Bird', '6346', 'necessity', 345.00, '234', NULL, 1, '2025-09-20 12:06:36');

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
  `sitter_experience` varchar(255) NOT NULL,
  `sitters_image_url` varchar(255) DEFAULT NULL,
  `sitters_active` tinyint(1) DEFAULT 1,
  `sitters_created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sitters`
--

INSERT INTO `sitters` (`sitters_id`, `sitters_name`, `sitters_bio`, `sitter_email`, `sitters_contact`, `sitter_specialty`, `sitter_experience`, `sitters_image_url`, `sitters_active`, `sitters_created_at`) VALUES
(1, 'John Ricardo', 'qw3aed', 'jr@gmail.com', '0956 789 0999', 'Dog, Cat, Fish', '4 years', 'pictures/sitters/images-1758347866-6335.jpg', 1, '2025-09-20 13:57:46');

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

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `transactions_id` int(11) NOT NULL,
  `users_id` int(11) NOT NULL,
  `transactions_amount` decimal(10,2) NOT NULL,
  `transactions_type` enum('product','appointment','subscription') NOT NULL,
  `transactions_fulfillment_type` enum('delivery','pickup') DEFAULT NULL,
  `transactions_payment_method` enum('cod','online') DEFAULT NULL,
  `transactions_created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `users_created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`users_id`, `users_firstname`, `users_lastname`, `users_username`, `users_email`, `users_password_hash`, `users_role`, `users_image_url`, `users_created_at`) VALUES
(1, 'Admin', '', 'ADMIN', 'admin256@admin.com', 'Abcd@1234', '1', NULL, '2025-09-18 21:52:11'),
(2, 'Accel', 'John', 'ajo23', 'ajo23@gmail.com', 'Acejohn123@', '0', NULL, '2025-09-20 16:12:45'),
(3, 'Grade', 'Lat', 'glat', 'glat21@gmail.com', 'Glat1234!', '0', NULL, '2025-09-23 13:23:55');

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
  ADD UNIQUE KEY `transactions_id` (`transactions_id`);

--
-- Indexes for table `pets`
--
ALTER TABLE `pets`
  ADD PRIMARY KEY (`pets_id`),
  ADD KEY `users_id` (`users_id`);

--
-- Indexes for table `pickups`
--
ALTER TABLE `pickups`
  ADD PRIMARY KEY (`pickups_id`),
  ADD UNIQUE KEY `transactions_id` (`transactions_id`);

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
  ADD UNIQUE KEY `users_email` (`users_email`);

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
  MODIFY `deliveries_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pets`
--
ALTER TABLE `pets`
  MODIFY `pets_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `pickups`
--
ALTER TABLE `pickups`
  MODIFY `pickups_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `products_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `sitters`
--
ALTER TABLE `sitters`
  MODIFY `sitters_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `subscriptions_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `transactions_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transaction_products`
--
ALTER TABLE `transaction_products`
  MODIFY `tp_id` int(11) NOT NULL AUTO_INCREMENT;

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
  ADD CONSTRAINT `deliveries_ibfk_1` FOREIGN KEY (`transactions_id`) REFERENCES `transactions` (`transactions_id`) ON DELETE CASCADE;

--
-- Constraints for table `pets`
--
ALTER TABLE `pets`
  ADD CONSTRAINT `pets_ibfk_1` FOREIGN KEY (`users_id`) REFERENCES `users` (`users_id`) ON DELETE CASCADE;

--
-- Constraints for table `pickups`
--
ALTER TABLE `pickups`
  ADD CONSTRAINT `pickups_ibfk_1` FOREIGN KEY (`transactions_id`) REFERENCES `transactions` (`transactions_id`) ON DELETE CASCADE;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
