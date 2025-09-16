-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 15, 2025 at 05:30 PM
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
  `pets_id` int(11) NOT NULL,
  `appointments_type` enum('grooming','vaccination','pet_sitting','other') NOT NULL,
  `appointments_date` datetime NOT NULL,
  `sitters_id` int(11) DEFAULT NULL,
  `aa_id` int(11) DEFAULT NULL,
  `appointments_status` enum('pending','confirmed','completed','cancelled') DEFAULT NULL,
  `appointments_notes` text DEFAULT NULL,
  `appointments_created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `appointment_address`
--

CREATE TABLE `appointment_address` (
  `aa_id` int(11) NOT NULL,
  `appointments_id` int(11) NOT NULL,
  `aa_type` enum('client_home','sitter_facility') NOT NULL,
  `aa_address` varchar(255) DEFAULT NULL,
  `aa_city` varchar(100) DEFAULT NULL,
  `aa_postal_code` varchar(20) DEFAULT NULL,
  `aa_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Table structure for table `sitters`
--

CREATE TABLE `sitters` (
  `sitters_id` int(11) NOT NULL,
  `sitters_name` varchar(100) NOT NULL,
  `sitters_bio` text DEFAULT NULL,
  `sitters_contact` varchar(255) DEFAULT NULL,
  `sitters_image_url` varchar(255) DEFAULT NULL,
  `sitters_active` tinyint(1) DEFAULT 1,
  `sitters_created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `users_username` varchar(50) NOT NULL,
  `users_email` varchar(255) NOT NULL,
  `users_password_hash` varchar(255) NOT NULL,
  `users_role` varchar(1) NOT NULL,
  `users_image_url` varchar(255) DEFAULT NULL,
  `users_created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  ADD KEY `pets_id` (`pets_id`),
  ADD KEY `sitters_id` (`sitters_id`);

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
  MODIFY `appointments_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `appointment_address`
--
ALTER TABLE `appointment_address`
  MODIFY `aa_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `deliveries`
--
ALTER TABLE `deliveries`
  MODIFY `deliveries_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pets`
--
ALTER TABLE `pets`
  MODIFY `pets_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pickups`
--
ALTER TABLE `pickups`
  MODIFY `pickups_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `products_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sitters`
--
ALTER TABLE `sitters`
  MODIFY `sitters_id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `users_id` int(11) NOT NULL AUTO_INCREMENT;

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
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`pets_id`) REFERENCES `pets` (`pets_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`sitters_id`) REFERENCES `sitters` (`sitters_id`) ON DELETE SET NULL;

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
