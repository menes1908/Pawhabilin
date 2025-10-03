-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 03, 2025 at 11:56 AM
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

--
-- Dumping data for table `admin_activity_logs`
--

INSERT INTO `admin_activity_logs` (`admin_activity_logs_id`, `admin_id`, `admin_activity_logs_action`, `admin_activity_logs_details`, `admin_activity_logs_created_at`) VALUES
(1, 2, 'updates', '{\"target\":\"promotion\",\"target_id\":\"2\",\"details\":\"{\\\"message\\\":\\\"Added promotion\\\"}\",\"previous\":null,\"new\":{\"promo_id\":\"2\",\"promo_type\":\"product\",\"promo_code\":\"3WBWPBJA\",\"promo_name\":\"20% OFF ON ALL PRODUCTS\",\"promo_description\":\"efwsfwe\",\"promo_discount_type\":\"percent\",\"promo_discount_value\":\"20.00\",\"promo_points_cost\":\"100\",\"free_product_id\":null,\"promo_min_purchase_amount\":\"200.00\",\"promo_usage_limit\":null,\"promo_per_user_limit\":null,\"promo_require_active_subscription\":\"1\",\"promo_starts_at\":\"2025-10-01 00:00:00\",\"promo_ends_at\":\"2025-10-31 23:59:00\",\"promo_active\":\"1\",\"promo_created_at\":\"2025-10-01 22:41:58\",\"promo_updated_at\":null},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/promocontroller.php\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-01 22:41:58'),
(2, 2, 'updates', '{\"target\":\"product\",\"target_id\":\"11\",\"details\":\"{\\\"message\\\":\\\"Updated product\\\",\\\"fields_changed\\\":[\\\"name\\\"]}\",\"previous\":{\"products_name\":\"345r6\",\"products_pet_type\":\"Bird\",\"products_description\":\"6346\",\"products_category\":\"necessity\",\"products_price\":\"345.00\",\"products_stock\":\"234\",\"products_image_url\":null,\"products_active\":1},\"new\":{\"name\":\"6732\",\"pet_type\":\"Bird\",\"description\":\"6346\",\"category\":\"necessity\",\"price\":345,\"stock\":\"234\",\"active\":1,\"products_image_url\":null},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/productcontroller.php?action=update\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-01 22:42:38'),
(3, 2, 'updates', '{\"target\":\"product\",\"target_id\":\"10\",\"details\":\"{\\\"message\\\":\\\"Updated product\\\",\\\"fields_changed\\\":[\\\"stock\\\"]}\",\"previous\":{\"products_name\":\"t43wegdxz\",\"products_pet_type\":\"Bird\",\"products_description\":\"sdregsdfg\",\"products_category\":\"necessity\",\"products_price\":\"3425.00\",\"products_stock\":\"324\",\"products_image_url\":null,\"products_active\":1},\"new\":{\"name\":\"t43wegdxz\",\"pet_type\":\"Bird\",\"description\":\"sdregsdfg\",\"category\":\"necessity\",\"price\":3425,\"stock\":\"200\",\"active\":1,\"products_image_url\":null},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/productcontroller.php?action=update\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-01 22:43:41'),
(4, 2, 'stock_changes', '{\"target\":\"product\",\"target_id\":\"10\",\"details\":\"{\\\"message\\\":\\\"Stock changed\\\"}\",\"previous\":{\"stock\":\"324\"},\"new\":{\"stock\":\"200\"},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/productcontroller.php?action=update\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-01 22:43:41'),
(5, 2, 'updates', '{\"target\":\"promotion\",\"target_id\":\"1\",\"details\":\"{\\\"message\\\":\\\"Toggled promotion active status\\\"}\",\"previous\":null,\"new\":{\"promo_active\":0},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/promocontroller.php\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-01 22:57:23'),
(6, 2, 'updates', '{\"target\":\"promotion\",\"target_id\":\"1\",\"details\":\"{\\\"message\\\":\\\"Toggled promotion active status\\\"}\",\"previous\":null,\"new\":{\"promo_active\":1},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/promocontroller.php\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-01 22:57:25'),
(7, 2, 'updates', '{\"target\":\"promotion\",\"target_id\":\"1\",\"details\":\"{\\\"message\\\":\\\"Toggled promotion active status\\\"}\",\"previous\":null,\"new\":{\"promo_active\":0},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/promocontroller.php\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-01 22:57:26'),
(8, 2, 'updates', '{\"target\":\"promotion\",\"target_id\":\"1\",\"details\":\"{\\\"message\\\":\\\"Toggled promotion active status\\\"}\",\"previous\":null,\"new\":{\"promo_active\":1},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/promocontroller.php\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-01 22:57:26'),
(9, 2, 'updates', '{\"target\":\"promotion\",\"target_id\":\"2\",\"details\":\"{\\\"message\\\":\\\"Toggled promotion active status\\\"}\",\"previous\":null,\"new\":{\"promo_active\":0},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/promocontroller.php\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-01 22:57:27'),
(10, 2, 'updates', '{\"target\":\"promotion\",\"target_id\":\"2\",\"details\":\"{\\\"message\\\":\\\"Toggled promotion active status\\\"}\",\"previous\":null,\"new\":{\"promo_active\":1},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/promocontroller.php\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-01 22:57:28'),
(11, 2, 'updates', '{\"target\":\"promotion\",\"target_id\":\"2\",\"details\":\"{\\\"message\\\":\\\"Toggled promotion active status\\\"}\",\"previous\":null,\"new\":{\"promo_active\":0},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/promocontroller.php\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-01 22:57:30'),
(12, 2, 'updates', '{\"target\":\"promotion\",\"target_id\":\"2\",\"details\":\"{\\\"message\\\":\\\"Toggled promotion active status\\\"}\",\"previous\":null,\"new\":{\"promo_active\":1},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/promocontroller.php\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-01 22:57:30'),
(13, 2, 'updates', '{\"target\":\"product\",\"target_id\":\"11\",\"details\":\"{\\\"message\\\":\\\"Updated product\\\",\\\"fields_changed\\\":[\\\"category\\\"]}\",\"previous\":{\"products_name\":\"6732\",\"products_pet_type\":\"Bird\",\"products_description\":\"6346\",\"products_category\":\"necessity\",\"products_price\":\"345.00\",\"products_stock\":\"234\",\"products_image_url\":null,\"products_active\":1},\"new\":{\"name\":\"6732\",\"pet_type\":\"Bird\",\"description\":\"6346\",\"category\":\"food\",\"price\":345,\"stock\":\"234\",\"active\":1,\"products_image_url\":null},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/productcontroller.php?action=update\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-01 23:05:52'),
(14, 2, 'updates', '{\"target\":\"sitter\",\"target_id\":\"2\",\"details\":\"{\\\"message\\\":\\\"Updated sitter\\\",\\\"fields_changed\\\":[\\\"bio\\\"]}\",\"previous\":{\"sitters_name\":\"ampuiti amputi\",\"sitters_bio\":\"aaaa\",\"sitter_email\":\"amaputie@gmail.com\",\"sitters_contact\":\"0956159882\",\"sitter_specialty\":\"Dogs, Cats, Birds\",\"sitter_experience\":\"\",\"sitters_image_url\":\"pictures/sitters/amaputie-gmail-com-1759042950-8787.png\",\"sitters_active\":1,\"years_experience\":2,\"sitters_verified\":1},\"new\":{\"name\":\"ampuiti amputi\",\"bio\":\"gesdfgesf\",\"email\":\"amaputie@gmail.com\",\"phone\":\"0956159882\",\"experience\":\"\",\"years_experience\":2,\"specialties\":[\"Dogs\",\"Cats\",\"Birds\"],\"active\":1,\"verified\":1,\"sitters_image_url\":\"pictures/sitters/amaputie-gmail-com-1759042950-8787.png\"},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/sittercontroller.php\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-01 23:06:27'),
(15, 2, 'updates', '{\"target\":\"appointment\",\"target_id\":\"10\",\"details\":\"{\\\"message\\\":\\\"Updated appointment\\\",\\\"fields_changed\\\":[\\\"full_name\\\",\\\"email\\\",\\\"phone\\\",\\\"pet_name\\\",\\\"pet_type\\\",\\\"pet_breed\\\",\\\"pet_age\\\",\\\"type\\\",\\\"datetime\\\",\\\"status\\\",\\\"notes\\\"]}\",\"previous\":null,\"new\":{\"full_name\":\"Cris Carlo\",\"email\":\"cc@gmail.com\",\"phone\":\"09237846239\",\"pet_name\":\"Fred\",\"pet_type\":\"other\",\"pet_breed\":\"Shih Tzu\",\"pet_age\":\"2\",\"type\":\"vet\",\"datetime\":\"2025-10-02T09:00\",\"status\":\"confirmed\",\"notes\":\"\"},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/appointmentcontroller.php\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-02 00:48:18'),
(16, 2, 'updates', '{\"target\":\"appointment\",\"target_id\":\"10\",\"details\":\"{\\\"message\\\":\\\"Updated appointment\\\",\\\"fields_changed\\\":[\\\"full_name\\\",\\\"email\\\",\\\"phone\\\",\\\"pet_name\\\",\\\"pet_type\\\",\\\"pet_breed\\\",\\\"pet_age\\\",\\\"type\\\",\\\"datetime\\\",\\\"status\\\",\\\"notes\\\"]}\",\"previous\":null,\"new\":{\"full_name\":\"Cris Carlo\",\"email\":\"cc@gmail.com\",\"phone\":\"09237846239\",\"pet_name\":\"Fred\",\"pet_type\":\"other\",\"pet_breed\":\"Shih Tzu\",\"pet_age\":\"2\",\"type\":\"vet\",\"datetime\":\"2025-10-02T09:00\",\"status\":\"completed\",\"notes\":\"\"},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/appointmentcontroller.php\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-02 00:48:58'),
(17, 2, 'updates', '{\"target\":\"appointment\",\"target_id\":\"11\",\"details\":\"{\\\"message\\\":\\\"Updated appointment\\\",\\\"fields_changed\\\":[\\\"full_name\\\",\\\"email\\\",\\\"phone\\\",\\\"pet_name\\\",\\\"pet_type\\\",\\\"pet_breed\\\",\\\"pet_age\\\",\\\"type\\\",\\\"datetime\\\",\\\"status\\\",\\\"notes\\\"]}\",\"previous\":null,\"new\":{\"full_name\":\"Cris Carlo\",\"email\":\"cc@gmail.com\",\"phone\":\"09162342389\",\"pet_name\":\"Kitty\",\"pet_type\":\"other\",\"pet_breed\":\"Egyptian\",\"pet_age\":\"\",\"type\":\"grooming\",\"datetime\":\"2025-10-03T10:00\",\"status\":\"completed\",\"notes\":\"\"},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/appointmentcontroller.php\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-02 00:51:23'),
(18, 2, 'updates', '{\"target\":\"appointment\",\"target_id\":\"11\",\"details\":\"{\\\"message\\\":\\\"Updated appointment\\\",\\\"fields_changed\\\":[\\\"full_name\\\",\\\"email\\\",\\\"phone\\\",\\\"pet_name\\\",\\\"pet_type\\\",\\\"pet_breed\\\",\\\"pet_age\\\",\\\"type\\\",\\\"datetime\\\",\\\"status\\\",\\\"notes\\\"]}\",\"previous\":null,\"new\":{\"full_name\":\"Cris Carlo\",\"email\":\"cc@gmail.com\",\"phone\":\"09162342389\",\"pet_name\":\"Kitty\",\"pet_type\":\"other\",\"pet_breed\":\"Egyptian\",\"pet_age\":\"\",\"type\":\"grooming\",\"datetime\":\"2025-10-03T10:00\",\"status\":\"pending\",\"notes\":\"\"},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/appointmentcontroller.php\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-02 00:57:38'),
(19, 2, 'updates', '{\"target\":\"appointment\",\"target_id\":\"11\",\"details\":\"{\\\"message\\\":\\\"Updated appointment\\\",\\\"fields_changed\\\":[\\\"full_name\\\",\\\"email\\\",\\\"phone\\\",\\\"pet_name\\\",\\\"pet_type\\\",\\\"pet_breed\\\",\\\"pet_age\\\",\\\"type\\\",\\\"datetime\\\",\\\"status\\\",\\\"notes\\\"]}\",\"previous\":null,\"new\":{\"full_name\":\"Cris Carlo\",\"email\":\"cc@gmail.com\",\"phone\":\"09162342389\",\"pet_name\":\"Kitty\",\"pet_type\":\"other\",\"pet_breed\":\"Egyptian\",\"pet_age\":\"\",\"type\":\"grooming\",\"datetime\":\"2025-10-03T10:00\",\"status\":\"confirmed\",\"notes\":\"\"},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/appointmentcontroller.php\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-02 00:57:54'),
(20, 2, 'updates', '{\"target\":\"promotion\",\"target_id\":\"3\",\"details\":\"{\\\"message\\\":\\\"Added promotion\\\"}\",\"previous\":null,\"new\":{\"promo_id\":\"3\",\"promo_type\":\"appointment\",\"promo_code\":\"XYSD4N7P\",\"promo_name\":\"FREE GROOMING\",\"promo_description\":\"\",\"promo_discount_type\":\"free_item\",\"promo_discount_value\":null,\"promo_points_cost\":null,\"free_product_id\":null,\"promo_min_purchase_amount\":null,\"promo_usage_limit\":null,\"promo_per_user_limit\":null,\"promo_require_active_subscription\":\"1\",\"promo_starts_at\":\"2025-10-02 01:02:00\",\"promo_ends_at\":\"2025-10-31 23:59:00\",\"promo_active\":\"1\",\"promo_created_at\":\"2025-10-02 01:03:04\",\"promo_updated_at\":null},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/promocontroller.php\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-02 01:03:04'),
(21, 2, 'updates', '{\"target\":\"appointment\",\"target_id\":\"11\",\"details\":\"{\\\"message\\\":\\\"Updated appointment\\\",\\\"fields_changed\\\":[\\\"full_name\\\",\\\"email\\\",\\\"phone\\\",\\\"pet_name\\\",\\\"pet_type\\\",\\\"pet_breed\\\",\\\"pet_age\\\",\\\"type\\\",\\\"datetime\\\",\\\"status\\\",\\\"notes\\\"]}\",\"previous\":null,\"new\":{\"full_name\":\"Cris Carlo\",\"email\":\"cc@gmail.com\",\"phone\":\"09162342389\",\"pet_name\":\"Kitty\",\"pet_type\":\"other\",\"pet_breed\":\"Egyptian\",\"pet_age\":\"\",\"type\":\"grooming\",\"datetime\":\"2025-10-03T10:00\",\"status\":\"completed\",\"notes\":\"\"},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/appointmentcontroller.php\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-02 01:10:21'),
(22, 2, 'updates', '{\"target\":\"appointment\",\"target_id\":\"6\",\"details\":\"{\\\"message\\\":\\\"Updated appointment\\\",\\\"fields_changed\\\":[\\\"full_name\\\",\\\"email\\\",\\\"phone\\\",\\\"pet_name\\\",\\\"pet_type\\\",\\\"pet_breed\\\",\\\"pet_age\\\",\\\"type\\\",\\\"datetime\\\",\\\"status\\\",\\\"notes\\\"]}\",\"previous\":null,\"new\":{\"full_name\":\"Brian\",\"email\":\"b@gmail.com\",\"phone\":\"09236784923\",\"pet_name\":\"adwd\",\"pet_type\":\"dog\",\"pet_breed\":\"\",\"pet_age\":\"2\",\"type\":\"pet_sitting\",\"datetime\":\"2025-10-01T09:00\",\"status\":\"completed\",\"notes\":\"\"},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/appointmentcontroller.php\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-02 21:21:45'),
(23, 2, 'updates', '{\"target\":\"appointment\",\"target_id\":\"10\",\"details\":\"{\\\"message\\\":\\\"Updated appointment\\\",\\\"fields_changed\\\":[\\\"full_name\\\",\\\"email\\\",\\\"phone\\\",\\\"pet_name\\\",\\\"pet_type\\\",\\\"pet_breed\\\",\\\"pet_age\\\",\\\"type\\\",\\\"datetime\\\",\\\"status\\\",\\\"notes\\\"]}\",\"previous\":null,\"new\":{\"full_name\":\"Cris Carlo\",\"email\":\"cc@gmail.com\",\"phone\":\"09237846239\",\"pet_name\":\"Fred\",\"pet_type\":\"other\",\"pet_breed\":\"Shih Tzu\",\"pet_age\":\"2\",\"type\":\"vet\",\"datetime\":\"2025-10-02T09:00\",\"status\":\"pending\",\"notes\":\"\"},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/appointmentcontroller.php\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-02 21:24:43'),
(24, 2, 'updates', '{\"target\":\"appointment\",\"target_id\":\"10\",\"details\":\"{\\\"message\\\":\\\"Updated appointment\\\",\\\"fields_changed\\\":[\\\"full_name\\\",\\\"email\\\",\\\"phone\\\",\\\"pet_name\\\",\\\"pet_type\\\",\\\"pet_breed\\\",\\\"pet_age\\\",\\\"type\\\",\\\"datetime\\\",\\\"status\\\",\\\"notes\\\"]}\",\"previous\":null,\"new\":{\"full_name\":\"Cris Carlo\",\"email\":\"cc@gmail.com\",\"phone\":\"09237846239\",\"pet_name\":\"Fred\",\"pet_type\":\"other\",\"pet_breed\":\"Shih Tzu\",\"pet_age\":\"2\",\"type\":\"vet\",\"datetime\":\"2025-10-02T09:00\",\"status\":\"completed\",\"notes\":\"\"},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/appointmentcontroller.php\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-02 21:24:50'),
(25, 2, 'updates', '{\"target\":\"appointment\",\"target_id\":\"5\",\"details\":\"{\\\"message\\\":\\\"Updated appointment\\\",\\\"fields_changed\\\":[\\\"full_name\\\",\\\"email\\\",\\\"phone\\\",\\\"pet_name\\\",\\\"pet_type\\\",\\\"pet_breed\\\",\\\"pet_age\\\",\\\"type\\\",\\\"datetime\\\",\\\"status\\\",\\\"notes\\\"]}\",\"previous\":null,\"new\":{\"full_name\":\"Grd Mln\",\"email\":\"gm@gmail.com\",\"phone\":\"09834723942\",\"pet_name\":\"Jape\",\"pet_type\":\"dog\",\"pet_breed\":\"Golden Retriever\",\"pet_age\":\"6\",\"type\":\"pet_sitting\",\"datetime\":\"2025-10-03T09:00\",\"status\":\"confirmed\",\"notes\":\"papasok sa tulay\"},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/appointmentcontroller.php\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-02 21:25:00'),
(26, 2, 'updates', '{\"target\":\"appointment\",\"target_id\":\"5\",\"details\":\"{\\\"message\\\":\\\"Updated appointment\\\",\\\"fields_changed\\\":[\\\"full_name\\\",\\\"email\\\",\\\"phone\\\",\\\"pet_name\\\",\\\"pet_type\\\",\\\"pet_breed\\\",\\\"pet_age\\\",\\\"type\\\",\\\"datetime\\\",\\\"status\\\",\\\"notes\\\"]}\",\"previous\":null,\"new\":{\"full_name\":\"Grd Mln\",\"email\":\"gm@gmail.com\",\"phone\":\"09834723942\",\"pet_name\":\"Jape\",\"pet_type\":\"dog\",\"pet_breed\":\"Golden Retriever\",\"pet_age\":\"6\",\"type\":\"pet_sitting\",\"datetime\":\"2025-10-03T09:00\",\"status\":\"completed\",\"notes\":\"papasok sa tulay\"},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/appointmentcontroller.php\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-02 21:25:05'),
(27, 2, 'updates', '{\"target\":\"appointment\",\"target_id\":\"4\",\"details\":\"{\\\"message\\\":\\\"Updated appointment\\\",\\\"fields_changed\\\":[\\\"full_name\\\",\\\"email\\\",\\\"phone\\\",\\\"pet_name\\\",\\\"pet_type\\\",\\\"pet_breed\\\",\\\"pet_age\\\",\\\"type\\\",\\\"datetime\\\",\\\"status\\\",\\\"notes\\\"]}\",\"previous\":null,\"new\":{\"full_name\":\"Jastin Andal\",\"email\":\"ja@gmail.com\",\"phone\":\"09632478324\",\"pet_name\":\"Iris\",\"pet_type\":\"cat\",\"pet_breed\":\"Persian\",\"pet_age\":\"3\",\"type\":\"vet\",\"datetime\":\"2025-09-30T15:00\",\"status\":\"completed\",\"notes\":\"\"},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/appointmentcontroller.php\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-02 22:17:24'),
(28, 2, 'auth_login', '{\"target\":\"user\",\"target_id\":\"2\",\"details\":\"{\\\"message\\\":\\\"Login successful\\\",\\\"email\\\":\\\"ajo23@gmail.com\\\"}\",\"previous\":null,\"new\":null,\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/login\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-02 22:18:23'),
(29, 2, 'auth_login', '{\"target\":\"user\",\"target_id\":\"2\",\"details\":\"{\\\"message\\\":\\\"Login successful\\\",\\\"email\\\":\\\"ajo23@gmail.com\\\"}\",\"previous\":null,\"new\":null,\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/login\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-02 22:21:32'),
(30, 2, 'auth_login', '{\"target\":\"user\",\"target_id\":\"2\",\"details\":\"{\\\"message\\\":\\\"Login successful\\\",\\\"email\\\":\\\"ajo23@gmail.com\\\"}\",\"previous\":null,\"new\":null,\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/login\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-02 22:45:34'),
(31, 2, 'updates', '{\"target\":\"order\",\"target_id\":\"4\",\"details\":\"{\\\"message\\\":\\\"Updated delivery status\\\",\\\"fields_changed\\\":[\\\"deliveries_delivery_status\\\",\\\"deliveries_estimated_delivery_date\\\",\\\"deliveries_actual_delivery_date\\\",\\\"deliveries_recipient_signature\\\"]}\",\"previous\":{\"deliveries_actual_delivery_date\":null,\"deliveries_recipient_signature\":0},\"new\":{\"deliveries_delivery_status\":\"out_for_delivery\",\"deliveries_estimated_delivery_date\":\"2025-10-04\",\"deliveries_actual_delivery_date\":null,\"deliveries_recipient_signature\":0},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/ordercontroller.php\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-02 23:12:34'),
(32, 2, 'updates', '{\"target\":\"product\",\"target_id\":\"11\",\"details\":\"{\\\"message\\\":\\\"Updated product\\\",\\\"fields_changed\\\":[\\\"name\\\",\\\"pet_type\\\",\\\"category\\\",\\\"price\\\",\\\"stock\\\",\\\"image\\\"]}\",\"previous\":{\"products_name\":\"6732\",\"products_pet_type\":\"Bird\",\"products_description\":\"6346\",\"products_category\":\"food\",\"products_price\":\"345.00\",\"products_stock\":\"234\",\"products_image_url\":null,\"products_active\":1},\"new\":{\"name\":\"GTR\",\"pet_type\":\"Dog\",\"description\":\"6346\",\"category\":\"toy\",\"price\":400,\"stock\":\"30\",\"active\":1,\"products_image_url\":\"pictures/products/82398136164f03ae26e2886f86524a8e-1759420310-6187.jpg\"},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/productcontroller.php?action=update\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-02 23:51:50'),
(33, 2, 'price_changes', '{\"target\":\"product\",\"target_id\":\"11\",\"details\":\"{\\\"message\\\":\\\"Price changed\\\"}\",\"previous\":{\"price\":345},\"new\":{\"price\":400},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/productcontroller.php?action=update\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-02 23:51:50'),
(34, 2, 'stock_changes', '{\"target\":\"product\",\"target_id\":\"11\",\"details\":\"{\\\"message\\\":\\\"Stock changed\\\"}\",\"previous\":{\"stock\":\"234\"},\"new\":{\"stock\":\"30\"},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/productcontroller.php?action=update\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-02 23:51:50'),
(35, 2, 'updates', '{\"target\":\"sitter\",\"target_id\":\"1\",\"details\":\"{\\\"message\\\":\\\"Updated sitter\\\",\\\"fields_changed\\\":[\\\"years_experience\\\"]}\",\"previous\":{\"sitters_name\":\"John Ricardo\",\"sitters_bio\":\"qw3aed\",\"sitter_email\":\"jr@gmail.com\",\"sitters_contact\":\"0956 789 0999\",\"sitter_specialty\":\"Dog, Cat, Fish\",\"sitter_experience\":\"\",\"sitters_image_url\":\"pictures/sitters/images-1758347866-6335.jpg\",\"sitters_active\":1,\"years_experience\":3,\"sitters_verified\":1},\"new\":{\"name\":\"John Ricardo\",\"bio\":\"qw3aed\",\"email\":\"jr@gmail.com\",\"phone\":\"0956 789 0999\",\"experience\":\"\",\"years_experience\":4,\"specialties\":[\"Dog\",\"Cat\",\"Fish\"],\"active\":1,\"verified\":1,\"sitters_image_url\":\"pictures/sitters/images-1758347866-6335.jpg\"},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/sittercontroller.php\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-02 23:52:22'),
(36, 2, 'updates', '{\"target\":\"appointment\",\"target_id\":\"3\",\"details\":\"{\\\"message\\\":\\\"Updated appointment\\\",\\\"fields_changed\\\":[\\\"full_name\\\",\\\"email\\\",\\\"phone\\\",\\\"pet_name\\\",\\\"pet_type\\\",\\\"pet_breed\\\",\\\"pet_age\\\",\\\"type\\\",\\\"datetime\\\",\\\"status\\\",\\\"notes\\\"]}\",\"previous\":null,\"new\":{\"full_name\":\"Ace Jerbis\",\"email\":\"ajo23@gmail.com\",\"phone\":\"09562378940\",\"pet_name\":\"Peter\",\"pet_type\":\"bird\",\"pet_breed\":\"Parrot\",\"pet_age\":\"4\",\"type\":\"pet_sitting\",\"datetime\":\"2025-09-30T14:00\",\"status\":\"confirmed\",\"notes\":\"\"},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/appointmentcontroller.php\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-02 23:52:38'),
(37, 2, 'updates', '{\"target\":\"order\",\"target_id\":\"4\",\"details\":\"{\\\"message\\\":\\\"Updated delivery status\\\",\\\"fields_changed\\\":[\\\"deliveries_delivery_status\\\",\\\"deliveries_estimated_delivery_date\\\",\\\"deliveries_actual_delivery_date\\\",\\\"deliveries_recipient_signature\\\"]}\",\"previous\":{\"deliveries_actual_delivery_date\":\"2025-10-02\",\"deliveries_recipient_signature\":0},\"new\":{\"deliveries_delivery_status\":\"out_for_delivery\",\"deliveries_estimated_delivery_date\":\"2025-10-04\",\"deliveries_actual_delivery_date\":\"2025-10-02\",\"deliveries_recipient_signature\":0},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/ordercontroller.php\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-03 00:28:14'),
(38, 2, 'updates', '{\"target\":\"order\",\"target_id\":\"4\",\"details\":\"{\\\"message\\\":\\\"Updated delivery status\\\",\\\"fields_changed\\\":[\\\"deliveries_delivery_status\\\",\\\"deliveries_estimated_delivery_date\\\",\\\"deliveries_actual_delivery_date\\\",\\\"deliveries_recipient_signature\\\"]}\",\"previous\":{\"deliveries_actual_delivery_date\":\"2025-10-02\",\"deliveries_recipient_signature\":0},\"new\":{\"deliveries_delivery_status\":\"delivered\",\"deliveries_estimated_delivery_date\":\"2025-10-04\",\"deliveries_actual_delivery_date\":\"2025-10-02\",\"deliveries_recipient_signature\":0},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/ordercontroller.php\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-03 00:28:24'),
(39, 2, 'updates', '{\"target\":\"sitter\",\"target_id\":\"2\",\"details\":\"{\\\"message\\\":\\\"Updated sitter\\\",\\\"fields_changed\\\":[\\\"specialties\\\"]}\",\"previous\":{\"sitters_name\":\"ampuiti amputi\",\"sitters_bio\":\"gesdfgesf\",\"sitter_email\":\"amaputie@gmail.com\",\"sitters_contact\":\"0956159882\",\"sitter_specialty\":\"Dogs, Cats, Birds\",\"sitter_experience\":\"\",\"sitters_image_url\":\"pictures/sitters/amaputie-gmail-com-1759042950-8787.png\",\"sitters_active\":1,\"years_experience\":2,\"sitters_verified\":1},\"new\":{\"name\":\"ampuiti amputi\",\"bio\":\"gesdfgesf\",\"email\":\"amaputie@gmail.com\",\"phone\":\"0956159882\",\"experience\":\"\",\"years_experience\":2,\"specialties\":[\"Dog\",\"Cat\",\"Fish\"],\"active\":1,\"verified\":1,\"sitters_image_url\":\"pictures/sitters/amaputie-gmail-com-1759042950-8787.png\"},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/sittercontroller.php\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-03 00:28:56'),
(40, 2, 'updates', '{\"target\":\"order\",\"target_id\":\"4\",\"details\":\"{\\\"message\\\":\\\"Updated delivery status\\\",\\\"fields_changed\\\":[\\\"deliveries_delivery_status\\\",\\\"deliveries_estimated_delivery_date\\\",\\\"deliveries_actual_delivery_date\\\",\\\"deliveries_recipient_signature\\\"]}\",\"previous\":{\"deliveries_actual_delivery_date\":\"2025-10-02\",\"deliveries_recipient_signature\":0},\"new\":{\"deliveries_delivery_status\":\"delivered\",\"deliveries_estimated_delivery_date\":\"2025-10-04\",\"deliveries_actual_delivery_date\":\"2025-10-02\",\"deliveries_recipient_signature\":1},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/ordercontroller.php\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-03 00:33:23'),
(41, 2, 'updates', '{\"target\":\"order\",\"target_id\":\"3\",\"details\":\"{\\\"message\\\":\\\"Updated delivery status\\\",\\\"fields_changed\\\":[\\\"deliveries_delivery_status\\\",\\\"deliveries_estimated_delivery_date\\\",\\\"deliveries_actual_delivery_date\\\",\\\"deliveries_recipient_signature\\\"]}\",\"previous\":{\"deliveries_actual_delivery_date\":\"2025-09-29\",\"deliveries_recipient_signature\":0},\"new\":{\"deliveries_delivery_status\":\"delivered\",\"deliveries_estimated_delivery_date\":\"2025-09-30\",\"deliveries_actual_delivery_date\":\"2025-09-29\",\"deliveries_recipient_signature\":1},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/ordercontroller.php\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-03 00:33:27'),
(42, 2, 'updates', '{\"target\":\"product\",\"target_id\":\"11\",\"details\":\"{\\\"message\\\":\\\"Updated product\\\",\\\"fields_changed\\\":[\\\"price\\\"]}\",\"previous\":{\"products_name\":\"GTR\",\"products_pet_type\":\"Dog\",\"products_description\":\"6346\",\"products_category\":\"toy\",\"products_price\":\"400.00\",\"products_stock\":\"30\",\"products_image_url\":\"pictures/products/82398136164f03ae26e2886f86524a8e-1759420310-6187.jpg\",\"products_active\":1},\"new\":{\"name\":\"GTR\",\"pet_type\":\"Dog\",\"description\":\"6346\",\"category\":\"toy\",\"price\":500,\"stock\":\"30\",\"active\":1,\"products_image_url\":\"pictures/products/82398136164f03ae26e2886f86524a8e-1759420310-6187.jpg\"},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/productcontroller.php?action=update\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-03 00:52:37'),
(43, 2, 'price_changes', '{\"target\":\"product\",\"target_id\":\"11\",\"details\":\"{\\\"message\\\":\\\"Price changed\\\"}\",\"previous\":{\"price\":400},\"new\":{\"price\":500},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/productcontroller.php?action=update\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-03 00:52:37'),
(44, 2, 'updates', '{\"target\":\"order\",\"target_id\":\"4\",\"details\":\"{\\\"message\\\":\\\"Updated delivery status\\\",\\\"fields_changed\\\":[\\\"deliveries_delivery_status\\\",\\\"deliveries_estimated_delivery_date\\\",\\\"deliveries_actual_delivery_date\\\",\\\"deliveries_recipient_signature\\\"]}\",\"previous\":{\"deliveries_actual_delivery_date\":\"2025-10-02\",\"deliveries_recipient_signature\":1},\"new\":{\"deliveries_delivery_status\":\"out_for_delivery\",\"deliveries_estimated_delivery_date\":\"2025-10-04\",\"deliveries_actual_delivery_date\":\"2025-10-02\",\"deliveries_recipient_signature\":1},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/ordercontroller.php\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-03 00:55:43'),
(45, 2, 'updates', '{\"target\":\"order\",\"target_id\":\"4\",\"details\":\"{\\\"message\\\":\\\"Updated delivery status\\\",\\\"fields_changed\\\":[\\\"deliveries_delivery_status\\\",\\\"deliveries_estimated_delivery_date\\\",\\\"deliveries_actual_delivery_date\\\",\\\"deliveries_recipient_signature\\\"]}\",\"previous\":{\"deliveries_actual_delivery_date\":\"2025-10-02\",\"deliveries_recipient_signature\":1},\"new\":{\"deliveries_delivery_status\":\"delivered\",\"deliveries_estimated_delivery_date\":\"2025-10-04\",\"deliveries_actual_delivery_date\":\"2025-10-02\",\"deliveries_recipient_signature\":1},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/ordercontroller.php\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-03 00:55:51'),
(46, 3, 'auth_login', '{\"target\":\"user\",\"target_id\":\"3\",\"details\":\"{\\\"message\\\":\\\"Login successful\\\",\\\"email\\\":\\\"glat21@gmail.com\\\"}\",\"previous\":null,\"new\":null,\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/login\",\"user_email\":\"glat21@gmail.com\"}', '2025-10-03 01:12:21'),
(47, 3, 'updates', '{\"target\":\"appointment\",\"target_id\":\"14\",\"details\":\"{\\\"message\\\":\\\"Updated appointment\\\",\\\"fields_changed\\\":[\\\"full_name\\\",\\\"email\\\",\\\"phone\\\",\\\"pet_name\\\",\\\"pet_type\\\",\\\"pet_breed\\\",\\\"pet_age\\\",\\\"type\\\",\\\"datetime\\\",\\\"status\\\",\\\"notes\\\"]}\",\"previous\":null,\"new\":{\"full_name\":\"Thea\",\"email\":\"t@gmail.com\",\"phone\":\"09781263489\",\"pet_name\":\"Blackie\",\"pet_type\":\"bird\",\"pet_breed\":\"Parrot\",\"pet_age\":\"2\",\"type\":\"grooming\",\"datetime\":\"2025-10-04T11:00\",\"status\":\"completed\",\"notes\":\"\"},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/appointmentcontroller.php\",\"user_email\":\"glat21@gmail.com\"}', '2025-10-03 01:13:53'),
(48, 3, 'auth_login', '{\"target\":\"user\",\"target_id\":\"3\",\"details\":\"{\\\"message\\\":\\\"Login successful\\\",\\\"email\\\":\\\"glat21@gmail.com\\\"}\",\"previous\":null,\"new\":null,\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/login\",\"user_email\":\"glat21@gmail.com\"}', '2025-10-03 01:14:16'),
(49, 3, 'updates', '{\"target\":\"appointment\",\"target_id\":\"14\",\"details\":\"{\\\"message\\\":\\\"Updated appointment\\\",\\\"fields_changed\\\":[\\\"full_name\\\",\\\"email\\\",\\\"phone\\\",\\\"pet_name\\\",\\\"pet_type\\\",\\\"pet_breed\\\",\\\"pet_age\\\",\\\"type\\\",\\\"datetime\\\",\\\"status\\\",\\\"notes\\\"]}\",\"previous\":null,\"new\":{\"full_name\":\"Thea\",\"email\":\"t@gmail.com\",\"phone\":\"09781263489\",\"pet_name\":\"Blackie\",\"pet_type\":\"bird\",\"pet_breed\":\"Parrot\",\"pet_age\":\"2\",\"type\":\"grooming\",\"datetime\":\"2025-10-04T11:00\",\"status\":\"confirmed\",\"notes\":\"\"},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/appointmentcontroller.php\",\"user_email\":\"glat21@gmail.com\"}', '2025-10-03 01:15:04'),
(50, 2, 'auth_login', '{\"target\":\"user\",\"target_id\":\"2\",\"details\":\"{\\\"message\\\":\\\"Login successful\\\",\\\"email\\\":\\\"ajo23@gmail.com\\\"}\",\"previous\":null,\"new\":null,\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/login\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-03 01:53:16'),
(51, 2, 'auth_login', '{\"target\":\"user\",\"target_id\":\"2\",\"details\":\"{\\\"message\\\":\\\"Login successful\\\",\\\"email\\\":\\\"ajo23@gmail.com\\\"}\",\"previous\":null,\"new\":null,\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/login\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-03 02:10:35'),
(52, 2, 'auth_login', '{\"target\":\"user\",\"target_id\":\"2\",\"details\":\"{\\\"message\\\":\\\"Login successful\\\",\\\"email\\\":\\\"ajo23@gmail.com\\\"}\",\"previous\":null,\"new\":null,\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/login\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-03 02:12:00'),
(53, 2, 'auth_login', '{\"target\":\"user\",\"target_id\":\"2\",\"details\":\"{\\\"message\\\":\\\"Login successful\\\",\\\"email\\\":\\\"ajo23@gmail.com\\\"}\",\"previous\":null,\"new\":null,\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/login\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-03 02:18:52'),
(54, 2, 'auth_login', '{\"target\":\"user\",\"target_id\":\"2\",\"details\":\"{\\\"message\\\":\\\"Login successful\\\",\\\"email\\\":\\\"ajo23@gmail.com\\\"}\",\"previous\":null,\"new\":null,\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/login\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-03 02:19:51'),
(55, 2, 'auth_login', '{\"target\":\"user\",\"target_id\":\"2\",\"details\":\"{\\\"message\\\":\\\"Login successful\\\",\\\"email\\\":\\\"ajo23@gmail.com\\\"}\",\"previous\":null,\"new\":null,\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/login\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-03 02:32:36'),
(56, 1, 'auth_login', '{\"target\":\"user\",\"target_id\":\"1\",\"details\":\"{\\\"message\\\":\\\"Login successful\\\",\\\"email\\\":\\\"admin256@admin.com\\\"}\",\"previous\":null,\"new\":null,\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/login\",\"user_email\":\"admin256@admin.com\"}', '2025-10-03 02:50:06'),
(57, 1, 'auth_login', '{\"target\":\"user\",\"target_id\":\"1\",\"details\":\"{\\\"message\\\":\\\"Login successful\\\",\\\"email\\\":\\\"admin256@admin.com\\\"}\",\"previous\":null,\"new\":null,\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/login\",\"user_email\":\"admin256@admin.com\"}', '2025-10-03 02:53:29'),
(58, 2, 'auth_login', '{\"target\":\"user\",\"target_id\":\"2\",\"details\":\"{\\\"message\\\":\\\"Login successful\\\",\\\"email\\\":\\\"ajo23@gmail.com\\\"}\",\"previous\":null,\"new\":null,\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/login\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-03 02:53:59'),
(59, 1, 'auth_login', '{\"target\":\"user\",\"target_id\":\"1\",\"details\":\"{\\\"message\\\":\\\"Login successful\\\",\\\"email\\\":\\\"admin256@admin.com\\\"}\",\"previous\":null,\"new\":null,\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/login\",\"user_email\":\"admin256@admin.com\"}', '2025-10-03 03:01:51'),
(60, 2, 'auth_login_failed', '{\"target\":\"user\",\"target_id\":\"2\",\"details\":\"{\\\"message\\\":\\\"Login failed\\\",\\\"email\\\":\\\"ajo23@gmail.com\\\"}\",\"previous\":null,\"new\":null,\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/login\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-03 03:03:22'),
(61, 2, 'auth_login', '{\"target\":\"user\",\"target_id\":\"2\",\"details\":\"{\\\"message\\\":\\\"Login successful\\\",\\\"email\\\":\\\"ajo23@gmail.com\\\"}\",\"previous\":null,\"new\":null,\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/login\",\"user_email\":\"ajo23@gmail.com\"}', '2025-10-03 03:03:29'),
(62, 1, 'auth_login', '{\"target\":\"user\",\"target_id\":\"1\",\"details\":\"{\\\"message\\\":\\\"Login successful\\\",\\\"email\\\":\\\"admin256@admin.com\\\"}\",\"previous\":null,\"new\":null,\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/login\",\"user_email\":\"admin256@admin.com\"}', '2025-10-03 03:04:25'),
(63, 1, 'updates', '{\"target\":\"order\",\"target_id\":\"5\",\"details\":\"{\\\"message\\\":\\\"Updated delivery status\\\",\\\"fields_changed\\\":[\\\"deliveries_delivery_status\\\",\\\"deliveries_estimated_delivery_date\\\",\\\"deliveries_actual_delivery_date\\\",\\\"deliveries_recipient_signature\\\"]}\",\"previous\":{\"deliveries_actual_delivery_date\":null,\"deliveries_recipient_signature\":0},\"new\":{\"deliveries_delivery_status\":\"out_for_delivery\",\"deliveries_estimated_delivery_date\":\"2025-10-04\",\"deliveries_actual_delivery_date\":null,\"deliveries_recipient_signature\":0},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/ordercontroller.php\",\"user_email\":\"admin256@admin.com\"}', '2025-10-03 03:06:13'),
(64, 1, 'updates', '{\"target\":\"order\",\"target_id\":\"5\",\"details\":\"{\\\"message\\\":\\\"Updated delivery status\\\",\\\"fields_changed\\\":[\\\"deliveries_delivery_status\\\",\\\"deliveries_estimated_delivery_date\\\",\\\"deliveries_actual_delivery_date\\\",\\\"deliveries_recipient_signature\\\"]}\",\"previous\":{\"deliveries_actual_delivery_date\":null,\"deliveries_recipient_signature\":0},\"new\":{\"deliveries_delivery_status\":\"processing\",\"deliveries_estimated_delivery_date\":\"2025-10-04\",\"deliveries_actual_delivery_date\":null,\"deliveries_recipient_signature\":0},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/ordercontroller.php\",\"user_email\":\"admin256@admin.com\"}', '2025-10-03 03:06:41'),
(65, 1, 'updates', '{\"target\":\"order\",\"target_id\":\"6\",\"details\":\"{\\\"message\\\":\\\"Updated delivery status\\\",\\\"fields_changed\\\":[\\\"deliveries_delivery_status\\\",\\\"deliveries_estimated_delivery_date\\\",\\\"deliveries_actual_delivery_date\\\",\\\"deliveries_recipient_signature\\\"]}\",\"previous\":{\"deliveries_actual_delivery_date\":null,\"deliveries_recipient_signature\":0},\"new\":{\"deliveries_delivery_status\":\"out_for_delivery\",\"deliveries_estimated_delivery_date\":\"2025-10-04\",\"deliveries_actual_delivery_date\":null,\"deliveries_recipient_signature\":0},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/ordercontroller.php\",\"user_email\":\"admin256@admin.com\"}', '2025-10-03 03:06:59'),
(66, 1, 'auth_login', '{\"target\":\"user\",\"target_id\":\"1\",\"details\":\"{\\\"message\\\":\\\"Login successful\\\",\\\"email\\\":\\\"admin256@admin.com\\\"}\",\"previous\":null,\"new\":null,\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/login\",\"user_email\":\"admin256@admin.com\"}', '2025-10-03 03:14:23'),
(67, 1, 'updates', '{\"target\":\"order\",\"target_id\":\"6\",\"details\":\"{\\\"message\\\":\\\"Updated delivery status\\\",\\\"fields_changed\\\":[\\\"deliveries_delivery_status\\\",\\\"deliveries_estimated_delivery_date\\\",\\\"deliveries_actual_delivery_date\\\",\\\"deliveries_recipient_signature\\\"]}\",\"previous\":{\"deliveries_actual_delivery_date\":\"2025-10-03\",\"deliveries_recipient_signature\":0},\"new\":{\"deliveries_delivery_status\":\"out_for_delivery\",\"deliveries_estimated_delivery_date\":\"2025-10-04\",\"deliveries_actual_delivery_date\":\"2025-10-03\",\"deliveries_recipient_signature\":0},\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/controllers/admin/ordercontroller.php\",\"user_email\":\"admin256@admin.com\"}', '2025-10-03 03:14:58'),
(68, 1, 'auth_login', '{\"target\":\"user\",\"target_id\":\"1\",\"details\":\"{\\\"message\\\":\\\"Login successful\\\",\\\"email\\\":\\\"admin256@admin.com\\\"}\",\"previous\":null,\"new\":null,\"ip\":\"::1\",\"method\":\"POST\",\"path\":\"/Pawhabilin/login\",\"user_email\":\"admin256@admin.com\"}', '2025-10-03 17:08:35');

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
(3, 2, 'Ace Jerbis', 'ajo23@gmail.com', '09562378940', 'Peter', 'bird', 'Parrot', '4', 'pet_sitting', '2025-09-30 14:00:00', NULL, 2, 'confirmed', '2025-09-23 21:56:01'),
(4, 2, 'Jastin Andal', 'ja@gmail.com', '09632478324', 'Iris', 'cat', 'Persian', '3', 'vet', '2025-09-30 15:00:00', NULL, NULL, 'completed', '2025-09-23 22:30:00'),
(5, 2, 'Grd Mln', 'gm@gmail.com', '09834723942', 'Jape', 'dog', 'Golden Retriever', '6', 'pet_sitting', '2025-10-03 09:00:00', NULL, 3, 'completed', '2025-09-23 22:32:59'),
(6, 2, 'Brian', 'b@gmail.com', '09236784923', 'adwd', 'dog', '', '2', 'pet_sitting', '2025-10-01 09:00:00', NULL, 4, 'completed', '2025-09-23 23:14:09'),
(7, 2, 'Brian', 'thor@gmail.com', '09567234823', 'Kitty', 'cat', 'Egyptian', '', 'grooming', '2025-09-24 09:00:00', NULL, NULL, 'pending', '2025-09-29 02:28:08'),
(8, 2, 'Brian', 'thor@gmail.com', '09567234823', 'Fred', 'dog', 'Shih Tzu', '', 'grooming', '2025-09-29 09:00:00', NULL, NULL, 'pending', '2025-09-29 02:28:57'),
(9, 2, 'Brian', 'thor@gmail.com', '09023347823', 'Fred', 'dog', 'Shih Tzu', '', 'vet', '2025-09-30 09:00:00', NULL, NULL, 'pending', '2025-09-29 02:32:45'),
(10, 2, 'Cris Carlo', 'cc@gmail.com', '09237846239', 'Fred', 'other', 'Shih Tzu', '2', 'vet', '2025-10-02 09:00:00', NULL, NULL, 'completed', '2025-10-02 00:47:29'),
(11, 2, 'Cris Carlo', 'cc@gmail.com', '09162342389', 'Kitty', 'other', 'Egyptian', '', 'grooming', '2025-10-03 10:00:00', NULL, NULL, 'completed', '2025-10-02 00:50:54'),
(12, 2, 'Cris Carlo', 'thor@gmail.com', '09023347823', 'Fred', 'other', 'Shih Tzu', '2', 'grooming', '2025-10-11 10:00:00', NULL, NULL, 'pending', '2025-10-03 00:58:09'),
(13, 2, 'Ian Terennal', 'ajo23@gmail.com', '09423782233', 'Kitty', 'other', 'Egyptian', '', 'pet_sitting', '2025-10-10 09:00:00', NULL, 5, 'pending', '2025-10-03 01:10:36'),
(14, 3, 'Thea', 't@gmail.com', '09781263489', 'Blackie', 'bird', 'Parrot', '2', 'grooming', '2025-10-04 11:00:00', NULL, NULL, 'confirmed', '2025-10-03 01:13:25');

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
(4, 6, 'drop_off', '', '', '', '', ''),
(5, 13, 'drop_off', '', '', '', '', '');

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
(1, 3, 1, 'delivered', '2025-09-30', '2025-09-29', '1'),
(2, 4, 1, 'delivered', '2025-10-04', '2025-10-02', '1'),
(3, 5, 2, 'processing', '2025-10-04', NULL, '0'),
(4, 6, 1, 'delivered', '2025-10-04', '2025-10-03', 'Received 2025-10-03 03:15:12');

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

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`location_id`, `users_id`, `location_label`, `location_recipient_name`, `location_phone`, `location_address_line1`, `location_address_line2`, `location_barangay`, `location_city`, `location_province`, `location_is_default`, `location_active`, `location_created_at`, `location_updated_at`) VALUES
(1, 2, 'Home', 'Angel Curtis', '09127836123', '012, Residence', 'Highway lang tabi ng shell', 'hendo', 'lipa', 'batangas', 0, 1, '2025-09-29 01:32:45', '2025-09-29 01:32:45'),
(2, 3, 'Home', 'EJ Dimayuga', '09789231423', 'new things', 'tabi ng hintayan ng bus', 'purok 2', 'agoncillo', 'batangas', 0, 1, '2025-10-03 01:19:05', '2025-10-03 01:19:05');

--
-- Triggers `locations`
--
DELIMITER $$
CREATE TRIGGER `trg_locations_before_insert` BEFORE INSERT ON `locations` FOR EACH ROW BEGIN
  IF NEW.location_is_default = 1 THEN
    UPDATE locations SET location_is_default = 0 WHERE users_id = NEW.users_id AND location_is_default = 1;
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_locations_before_update` BEFORE UPDATE ON `locations` FOR EACH ROW BEGIN
  IF NEW.location_is_default = 1 AND (OLD.location_is_default <> NEW.location_is_default OR OLD.users_id <> NEW.users_id) THEN
    UPDATE locations SET location_is_default = 0 WHERE users_id = NEW.users_id AND location_id <> NEW.location_id AND location_is_default = 1;
  END IF;
END
$$
DELIMITER ;

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
(3, 'Kibbles n\' Bits', 'Dog', 'dry dog food known for its unique dual-textured food, which combines crunchy kibble with tender, meaty bits for a satisfying and flavorful meal', 'food', 300.00, NULL, '30', 'pictures/products/Kibblesn-Bits-Original-Beef-Chicken-Dry-Dog-Food-3-5LB-1024x1024-1758340461-5998.png', 1, '2025-09-20 11:54:21'),
(4, 'Kibbles n\' Bits MINI BITS', 'Dog', 'small, dry dog food kibbles designed for small breeds, though suitable for all sizes, with flavors like bacon & steak or beef & chicken, featuring crunchy and tender, meat-filled pieces easy to chew and digest', 'food', 250.00, NULL, '25', 'pictures/products/Kibblesn-Bits-Bacon-Steak-Flavor-Small-Breeds-Dry-Dog-Food-3-5LB-1758340519-5469.png', 0, '2025-09-20 11:55:19'),
(5, 'qwaed', 'Dog', 'aqwrfasdf', 'accessory', 23.00, NULL, '231', NULL, 1, '2025-09-20 12:04:58'),
(6, '23qrwa', 'Bird', 'adwasd', 'necessity', 333.00, NULL, '23', NULL, 0, '2025-09-20 12:05:12'),
(7, '23qrweqard', 'Small Pet', 'aes fdsfewas', 'necessity', 234.00, NULL, '1234', NULL, 0, '2025-09-20 12:05:26'),
(8, '23qwra', 'Cat', 'waqdc', 'necessity', 234.00, NULL, '234', NULL, 1, '2025-09-20 12:05:36'),
(9, 'q3awr3weqr', 'Cat', '23wetfesdf', 'necessity', 345.00, NULL, '234', NULL, 1, '2025-09-20 12:05:50'),
(10, 't43wegdxz', 'Bird', 'sdregsdfg', 'necessity', 3425.00, NULL, '200', NULL, 1, '2025-09-20 12:06:20'),
(11, 'GTR', 'Dog', '6346', 'toy', 500.00, NULL, '30', 'pictures/products/82398136164f03ae26e2886f86524a8e-1759420310-6187.jpg', 1, '2025-09-20 12:06:36');

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
(1, 'appointment', 'RDSGF45', 'Free appointment', 'awdawdaw', 'free_item', NULL, 500, NULL, NULL, NULL, NULL, 1, '2025-10-01 10:41:00', '2025-10-31 23:59:00', 1, '2025-10-01 10:42:32', '2025-10-01 22:57:26'),
(2, 'product', '3WBWPBJA', '20% OFF ON ALL PRODUCTS', 'efwsfwe', 'percent', 20.00, 100, NULL, 200.00, NULL, NULL, 1, '2025-10-01 00:00:00', '2025-10-31 23:59:00', 1, '2025-10-01 22:41:58', '2025-10-01 22:57:30'),
(3, 'appointment', 'XYSD4N7P', 'FREE GROOMING', '', 'free_item', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-10-02 01:02:00', '2025-10-31 23:59:00', 1, '2025-10-02 01:03:04', NULL);

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
  `sitters_created_at` datetime DEFAULT current_timestamp(),
  `sitters_verified` tinyint(1) NOT NULL DEFAULT 0,
  `years_experience` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sitters`
--

INSERT INTO `sitters` (`sitters_id`, `sitters_name`, `sitters_bio`, `sitter_email`, `sitters_contact`, `sitter_specialty`, `sitter_experience`, `sitters_image_url`, `sitters_active`, `sitters_created_at`, `sitters_verified`, `years_experience`) VALUES
(1, 'John Ricardo', 'qw3aed', 'jr@gmail.com', '0956 789 0999', 'Dog, Cat, Fish', '', 'pictures/sitters/images-1758347866-6335.jpg', 1, '2025-09-20 13:57:46', 1, 4),
(2, 'ampuiti amputi', 'gesdfgesf', 'amaputie@gmail.com', '0956159882', 'Dog, Cat, Fish', '', 'pictures/sitters/amaputie-gmail-com-1759042950-8787.png', 1, '2025-09-28 15:02:36', 1, 2);

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
(2, 2, 299.00, 'subscription', 'gcash', '2025-09-29 01:39:02'),
(3, 2, 320.00, 'product', 'gcash', '2025-09-29 01:39:38'),
(4, 2, 360.50, 'product', 'gcash', '2025-10-02 23:11:54'),
(5, 3, 550.00, 'product', 'cod', '2025-10-03 01:19:12'),
(6, 2, 500.00, 'product', 'cod', '2025-10-03 03:05:57');

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
(2, 3, 3, '1'),
(3, 4, 9, '1'),
(4, 5, 11, '1'),
(5, 6, 11, '1');

-- --------------------------------------------------------

--
-- Table structure for table `transaction_subscriptions`
--

CREATE TABLE `transaction_subscriptions` (
  `ts_id` int(11) NOT NULL,
  `transactions_id` int(11) NOT NULL,
  `us_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaction_subscriptions`
--

INSERT INTO `transaction_subscriptions` (`ts_id`, `transactions_id`, `us_id`) VALUES
(1, 2, 1);

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
(2, 240, 0, '2025-10-03 03:15:12');

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
(11, 2, 50, 'Order Received', '', 6, 0, 0, NULL, '2025-10-03 03:15:12');

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
  `up_qr_svg` mediumtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_promos`
--

INSERT INTO `user_promos` (`up_id`, `users_id`, `promo_id`, `up_code`, `up_claimed_at`, `up_redeemed_at`, `up_qr_svg`) VALUES
(1, 2, 3, 'XYSD4N7P-U2-528CD7', '2025-10-02 21:21:03', NULL, NULL),
(2, 2, 2, '3WBWPBJA-U2-F90E94', '2025-10-02 23:13:31', NULL, NULL);

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
(1, 2, 1, '2025-09-28 19:39:02', '2025-10-28 19:39:02', 'active');

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
-- Indexes for table `promotions`
--
ALTER TABLE `promotions`
  ADD PRIMARY KEY (`promo_id`),
  ADD UNIQUE KEY `uq_promotions_promo_code` (`promo_code`),
  ADD KEY `idx_promotions_active_dates` (`promo_active`,`promo_starts_at`,`promo_ends_at`),
  ADD KEY `idx_promotions_type` (`promo_type`),
  ADD KEY `promotions_free_product_fk` (`free_product_id`);

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
  MODIFY `admin_activity_logs_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointments_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `appointment_address`
--
ALTER TABLE `appointment_address`
  MODIFY `aa_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `deliveries`
--
ALTER TABLE `deliveries`
  MODIFY `deliveries_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `location_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `pets`
--
ALTER TABLE `pets`
  MODIFY `pets_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `products_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `promotions`
--
ALTER TABLE `promotions`
  MODIFY `promo_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `promotion_codes`
--
ALTER TABLE `promotion_codes`
  MODIFY `code_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `promotion_redemptions`
--
ALTER TABLE `promotion_redemptions`
  MODIFY `redemption_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sitters`
--
ALTER TABLE `sitters`
  MODIFY `sitters_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `subscriptions_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `transactions_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `transaction_products`
--
ALTER TABLE `transaction_products`
  MODIFY `tp_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `transaction_subscriptions`
--
ALTER TABLE `transaction_subscriptions`
  MODIFY `ts_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `users_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_points_ledger`
--
ALTER TABLE `user_points_ledger`
  MODIFY `upl_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `user_promos`
--
ALTER TABLE `user_promos`
  MODIFY `up_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user_subscriptions`
--
ALTER TABLE `user_subscriptions`
  MODIFY `us_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
-- Constraints for table `promotions`
--
ALTER TABLE `promotions`
  ADD CONSTRAINT `promotions_free_product_fk` FOREIGN KEY (`free_product_id`) REFERENCES `products` (`products_id`) ON DELETE SET NULL;

--
-- Constraints for table `promotion_codes`
--
ALTER TABLE `promotion_codes`
  ADD CONSTRAINT `promotion_codes_promo_fk` FOREIGN KEY (`promo_id`) REFERENCES `promotions` (`promo_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `promotion_codes_user_fk` FOREIGN KEY (`users_id`) REFERENCES `users` (`users_id`) ON DELETE SET NULL;

--
-- Constraints for table `promotion_redemptions`
--
ALTER TABLE `promotion_redemptions`
  ADD CONSTRAINT `pr_appt_fk` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointments_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pr_code_fk` FOREIGN KEY (`code_id`) REFERENCES `promotion_codes` (`code_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pr_promo_fk` FOREIGN KEY (`promo_id`) REFERENCES `promotions` (`promo_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pr_tx_fk` FOREIGN KEY (`transactions_id`) REFERENCES `transactions` (`transactions_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pr_user_fk` FOREIGN KEY (`users_id`) REFERENCES `users` (`users_id`) ON DELETE CASCADE;

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
-- Constraints for table `user_points_balance`
--
ALTER TABLE `user_points_balance`
  ADD CONSTRAINT `upb_user_fk` FOREIGN KEY (`users_id`) REFERENCES `users` (`users_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_points_ledger`
--
ALTER TABLE `user_points_ledger`
  ADD CONSTRAINT `upl_user_fk` FOREIGN KEY (`users_id`) REFERENCES `users` (`users_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_promos`
--
ALTER TABLE `user_promos`
  ADD CONSTRAINT `fk_up_promo` FOREIGN KEY (`promo_id`) REFERENCES `promotions` (`promo_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_up_user` FOREIGN KEY (`users_id`) REFERENCES `users` (`users_id`) ON DELETE CASCADE;

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
