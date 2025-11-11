-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 06, 2025 at 06:59 PM
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
-- Database: `tool_kart`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `cart_id` int(11) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `tool_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `rental_start_date` date NOT NULL,
  `rental_end_date` date NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`, `description`, `created_at`) VALUES
(1, 'Gardening', 'Tools for garden maintenance and landscaping', '2025-10-01 14:48:14'),
(2, 'Carpentry', 'Woodworking and construction tools', '2025-10-01 14:48:14'),
(3, 'Plumbing', 'Plumbing repair and installation tools', '2025-10-01 14:48:14'),
(4, 'Electrical', 'Electrical work and wiring tools', '2025-10-01 14:48:14'),
(5, 'Painting', 'Painting and decorating equipment', '2025-10-01 14:48:14'),
(6, 'Automotive', 'Vehicle maintenance and repair tools', '2025-10-01 14:48:14'),
(7, 'Power Tools', 'Electric and pneumatic power tools for construction and DIY projects', '2025-10-01 15:02:13'),
(8, 'Hand Tools', 'Traditional hand tools including hammers, screwdrivers, and wrenches', '2025-10-01 15:02:13'),
(9, 'Ladders & Scaffolding', 'Access equipment for working at heights', '2025-10-01 15:02:13'),
(10, 'Measuring & Layout', 'Tools for measurement, marking, and layout work', '2025-10-01 15:02:13'),
(11, 'Concrete & Masonry', 'Specialized tools for concrete and masonry work', '2025-10-01 15:02:13'),
(12, 'Flooring', 'Tools and equipment for floor installation and maintenance', '2025-10-01 15:02:13'),
(13, 'Roofing', 'Equipment and tools for roofing projects', '2025-10-01 15:02:13'),
(14, 'Landscaping', 'Tools for outdoor landscaping and hardscaping', '2025-10-01 15:02:13'),
(15, 'Woodworking', 'Specialized tools for woodworking and carpentry', '2025-10-01 15:02:13'),
(16, 'Metalworking', 'Tools for cutting, shaping, and working with metal', '2025-10-01 15:02:13'),
(17, 'Power Tools', 'Electric and pneumatic power tools for construction and DIY projects', '2025-10-01 15:21:02'),
(18, 'Hand Tools', 'Traditional hand tools including hammers, screwdrivers, and wrenches', '2025-10-01 15:21:02'),
(19, 'Ladders & Scaffolding', 'Access equipment for working at heights', '2025-10-01 15:21:02'),
(20, 'Measuring & Layout', 'Tools for measurement, marking, and layout work', '2025-10-01 15:21:02'),
(21, 'Concrete & Masonry', 'Specialized tools for concrete and masonry work', '2025-10-01 15:21:02'),
(22, 'Flooring', 'Tools and equipment for floor installation and maintenance', '2025-10-01 15:21:02'),
(23, 'Roofing', 'Equipment and tools for roofing projects', '2025-10-01 15:21:02'),
(24, 'Landscaping', 'Tools for outdoor landscaping and hardscaping', '2025-10-01 15:21:02'),
(25, 'Woodworking', 'Specialized tools for woodworking and carpentry', '2025-10-01 15:21:02'),
(26, 'Metalworking', 'Tools for cutting, shaping, and working with metal', '2025-10-01 15:21:02');

-- --------------------------------------------------------

--
-- Table structure for table `faq`
--

CREATE TABLE `faq` (
  `id` int(11) NOT NULL,
  `question` text NOT NULL,
  `answer` text NOT NULL,
  `category` varchar(100) DEFAULT 'General',
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faq`
--

INSERT INTO `faq` (`id`, `question`, `answer`, `category`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'How do I rent a tool?', 'To rent a tool, simply browse our catalog, select the tool you need, choose your rental dates, and proceed to checkout. You\'ll need to create an account or log in if you already have one.', 'Rentals', 1, 1, '2025-10-01 14:58:18', '2025-10-01 14:58:18'),
(2, 'What is the minimum rental period?', 'The minimum rental period is one day. You can rent tools for as little as 24 hours or as long as you need them.', 'Rentals', 2, 1, '2025-10-01 14:58:18', '2025-10-01 14:58:18'),
(11, 'Do you offer delivery services?', 'No,Currentle we don,t offer delivery services. You can pick-up you tool directly from our shop.', 'General', 11, 1, '2025-10-01 15:02:13', '2025-10-01 15:06:49'),
(13, 'What safety equipment should I use with power tools?', 'We strongly recommend using appropriate safety equipment including safety glasses, ear protection, and work gloves when using power tools. Some tools may require additional safety equipment as specified in their manuals.', 'Safety', 13, 1, '2025-10-01 15:02:13', '2025-10-01 15:02:13');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tool_id` int(11) NOT NULL,
  `notification_type` enum('availability','return_date','special_offer','damage_fine','late_fine','returned') DEFAULT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `tool_id`, `notification_type`, `message`, `is_read`, `created_at`) VALUES
(55, 7, 22, 'damage_fine', 'Damage fee of ₹10.00 has been applied to your rental.', 1, '2025-11-06 17:26:47'),
(56, 7, 22, '', NULL, 1, '2025-11-06 17:27:02'),
(57, 7, 54, 'damage_fine', 'Damage fee of ₹40.00 has been applied to your rental.', 1, '2025-11-06 17:32:23'),
(58, 7, 54, 'damage_fine', 'Damage fee of ₹40.00 has been applied to your rental.', 1, '2025-11-06 17:34:37'),
(59, 7, 54, 'returned', 'Your rental of \'Chalk Line\' has been marked as returned. Thank you for using our service!', 1, '2025-11-06 17:34:48'),
(60, 7, 54, 'returned', 'Your rental of \'Chalk Line\' has been marked as returned. Thank you for using our service!', 1, '2025-11-06 17:34:53'),
(61, 7, 54, 'returned', 'Your rental of \'Chalk Line\' has been marked as returned. Thank you for using our service!', 1, '2025-11-06 17:35:27'),
(62, 7, 22, 'returned', 'Your rental of \'Chainsaw\' has been marked as returned. Thank you for using our service!', 1, '2025-11-06 17:35:31'),
(63, 7, 36, 'damage_fine', 'Additional damage fee of ₹60.00 has been applied to your rental. Total damage fee: ₹60.00', 0, '2025-11-06 17:37:06'),
(64, 7, 29, 'damage_fine', 'Additional damage fee of ₹120.00 has been applied to your rental. Total damage fee: ₹120.00', 0, '2025-11-06 17:37:33'),
(65, 7, 29, 'damage_fine', 'Additional damage fee of ₹120.00 has been applied to your rental. Total damage fee: ₹240.00', 0, '2025-11-06 17:37:45'),
(66, 7, 29, 'damage_fine', 'Additional damage fee of ₹120.00 has been applied to your rental. Total damage fee: ₹360.00', 0, '2025-11-06 17:40:40'),
(67, 7, 36, 'returned', 'Your rental of \'Circuit Tester\' has been marked as returned. Thank you for using our service!', 0, '2025-11-06 17:41:09'),
(68, 7, 36, 'returned', 'Your rental of \'Circuit Tester\' has been marked as returned. Thank you for using our service!', 0, '2025-11-06 17:42:24'),
(69, 7, 29, 'returned', 'Your rental of \'Chisel Set\' has been marked as returned. Thank you for using our service!', 0, '2025-11-06 17:44:06'),
(70, 7, 43, 'damage_fine', 'Additional damage fee of ₹260.00 has been applied to your rental. Total damage fee: ₹260.00', 0, '2025-11-06 17:44:09'),
(71, 7, 43, 'damage_fine', 'Additional damage fee of ₹260.00 has been applied to your rental. Total damage fee: ₹520.00', 0, '2025-11-06 17:47:21'),
(72, 7, 43, 'returned', 'Your rental of \'Angle Grinder\' has been marked as returned. Thank you for using our service!', 0, '2025-11-06 17:47:24'),
(73, 7, 43, 'returned', 'Your rental of \'Angle Grinder\' has been marked as returned. Thank you for using our service!', 0, '2025-11-06 17:47:57'),
(74, 7, 43, 'returned', 'Your rental of \'Angle Grinder\' has been marked as returned. Thank you for using our service!', 0, '2025-11-06 17:50:30'),
(75, 7, 43, 'returned', 'Your rental of \'Angle Grinder\' has been marked as returned. Thank you for using our service!', 0, '2025-11-06 17:53:35');

-- --------------------------------------------------------

--
-- Table structure for table `rentals`
--

CREATE TABLE `rentals` (
  `rental_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tool_id` int(11) NOT NULL,
  `rental_date` date NOT NULL,
  `rental_time` time DEFAULT NULL,
  `return_date` date NOT NULL,
  `return_time` time DEFAULT NULL,
  `actual_return_date` date DEFAULT NULL,
  `actual_return_time` time DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `total_amount` decimal(10,2) NOT NULL,
  `late_fine` decimal(10,2) DEFAULT 0.00,
  `damage_fine` decimal(10,2) DEFAULT 0.00,
  `status` enum('active','returned','overdue','cancelled') DEFAULT 'active',
  `payment_method` enum('full','cod') DEFAULT 'full',
  `deposit_amount` decimal(10,2) DEFAULT 0.00,
  `address_updated` tinyint(1) DEFAULT 0,
  `full_payment_received` tinyint(1) DEFAULT 0,
  `id_proof_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rentals`
--

INSERT INTO `rentals` (`rental_id`, `user_id`, `tool_id`, `rental_date`, `rental_time`, `return_date`, `return_time`, `actual_return_date`, `actual_return_time`, `quantity`, `total_amount`, `late_fine`, `damage_fine`, `status`, `payment_method`, `deposit_amount`, `address_updated`, `full_payment_received`, `id_proof_image`, `created_at`, `updated_at`) VALUES
(30, 7, 22, '2025-11-07', '18:24:41', '2025-11-08', '18:00:00', '2025-11-06', '18:35:31', 1, 200.00, 0.00, 10.00, 'returned', 'full', 0.00, 1, 0, 'uploads/id_proofs/id_proof_690cd9d92b6a0_1762449881.png', '2025-11-06 17:24:41', '2025-11-06 17:35:31'),
(31, 7, 54, '2025-11-07', '18:27:30', '2025-11-10', '18:00:00', '2025-11-06', '18:35:27', 3, 180.00, 0.00, 40.00, 'returned', 'full', 0.00, 1, 0, 'uploads/id_proofs/id_proof_690cd9d92b6a0_1762449881.png', '2025-11-06 17:27:30', '2025-11-06 17:35:27'),
(32, 7, 29, '2025-11-08', '18:36:15', '2025-11-11', '18:00:00', '2025-11-06', '18:44:06', 3, 540.00, 0.00, 360.00, 'returned', 'full', 0.00, 1, 0, 'uploads/id_proofs/id_proof_690cd9d92b6a0_1762449881.png', '2025-11-06 17:36:15', '2025-11-06 17:44:06'),
(33, 7, 36, '2025-11-07', '18:36:37', '2025-11-11', '18:00:00', '2025-11-06', '18:42:24', 3, 360.00, 0.00, 60.00, 'returned', 'cod', 72.00, 1, 1, 'uploads/id_proofs/id_proof_690cd9d92b6a0_1762449881.png', '2025-11-06 17:36:37', '2025-11-06 17:42:24'),
(34, 7, 43, '2025-11-07', '18:42:08', '2025-11-10', '18:00:00', '2025-11-06', '18:53:35', 3, 1170.00, 0.00, 520.00, 'returned', 'full', 0.00, 1, 0, 'uploads/id_proofs/id_proof_690cd9d92b6a0_1762449881.png', '2025-11-06 17:42:08', '2025-11-06 17:53:35'),
(35, 7, 46, '2025-11-07', '18:47:52', '2025-11-11', '18:00:00', NULL, NULL, 4, 960.00, 0.00, 0.00, 'active', 'full', 0.00, 1, 0, 'uploads/id_proofs/id_proof_690cd9d92b6a0_1762449881.png', '2025-11-06 17:47:52', '2025-11-06 17:47:52');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tool_id` int(11) NOT NULL,
  `rental_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review_text` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_name` varchar(100) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_name`, `setting_value`, `description`, `created_at`, `updated_at`) VALUES
(1, 'late_fee_per_day', '50', 'Late return fee per day per tool (in INR)', '2025-10-01 14:48:14', '2025-10-01 14:48:14'),
(2, 'damage_fee_percentage', '10', 'Damage fee as percentage of tool actual price', '2025-10-01 14:48:14', '2025-11-06 17:08:07');

-- --------------------------------------------------------

--
-- Table structure for table `tools`
--

CREATE TABLE `tools` (
  `tool_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `daily_rate` decimal(10,2) NOT NULL,
  `actual_price` decimal(10,2) NOT NULL,
  `quantity_available` int(11) DEFAULT 1,
  `brand` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','maintenance') DEFAULT 'active',
  `is_featured` tinyint(1) DEFAULT 0,
  `is_common` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tools`
--

INSERT INTO `tools` (`tool_id`, `name`, `description`, `category_id`, `daily_rate`, `actual_price`, `quantity_available`, `brand`, `model`, `image_url`, `status`, `is_featured`, `is_common`, `created_at`, `updated_at`) VALUES
(1, 'Electric Drill', 'Professional grade electric drill with multiple bits', 2, 150.00, 5000.00, 1, 'Bosch', 'GSB 500', 'electric_drill.jpg', 'active', 1, 0, '2025-10-01 14:48:14', '2025-11-06 16:53:42'),
(2, 'Lawn Mower', 'Electric lawn mower for medium to large gardens', 1, 300.00, 15000.00, 2, 'Honda', 'HRX217', 'lawn_mower.jpg', 'active', 1, 0, '2025-10-01 14:48:14', '2025-10-01 15:02:13'),
(3, 'Pipe Wrench Set', 'Complete set of pipe wrenches for plumbing work', 3, 100.00, 2500.00, 5, 'Ridgid', 'PWS-300', 'pipe_wrench.jpg', 'active', 0, 1, '2025-10-01 14:48:14', '2025-10-01 15:02:13'),
(4, 'Paint Sprayer', 'Electric paint sprayer for interior and exterior use', 5, 250.00, 8000.00, 2, 'Wagner', 'PS-250', 'paint_sprayer.jpg', 'active', 0, 0, '2025-10-01 14:48:14', '2025-10-02 06:10:01'),
(5, 'Circular Saw', 'Heavy-duty circular saw for cutting wood and metal', 2, 200.00, 6500.00, 4, 'Makita', 'CS-200', 'circular_saw.jpg', 'active', 1, 0, '2025-10-01 14:48:14', '2025-10-10 09:43:32'),
(6, 'Garden Shears', 'Professional garden shears for pruning and trimming', 1, 75.00, 1500.00, 4, 'Fiskars', 'GS-100', 'garden_shears.jpg', 'active', 0, 1, '2025-10-01 14:48:14', '2025-10-02 06:09:54'),
(7, 'Cordless Drill Set', '18V cordless drill with two batteries and charger. Perfect for drilling and driving screws in wood, metal, and plastic.', 7, 180.00, 6500.00, 3, 'DeWalt', 'DCD771C2', 'cordless_drill.jpg', 'active', 1, 1, '2025-10-01 15:02:13', '2025-11-06 17:20:24'),
(8, 'Impact Driver', 'High-torque impact driver for heavy-duty fastening applications. Comes with two batteries and charger.', 7, 160.00, 5800.00, 3, 'Makita', 'XDT13Z', 'impact_driver.jpg', 'active', 1, 1, '2025-10-01 15:02:13', '2025-10-02 06:10:17'),
(9, 'Rotary Hammer', 'Heavy-duty rotary hammer for drilling into concrete and masonry. Perfect for construction work.', 7, 250.00, 9500.00, 2, 'Bosch', 'GBH2-28', 'rotary_hammer.jpg', 'active', 1, 0, '2025-10-01 15:02:13', '2025-10-01 15:02:13'),
(10, 'Socket Wrench Set', 'Complete socket wrench set with sizes ranging from 8mm to 22mm. Professional quality chrome vanadium steel.', 8, 80.00, 2500.00, 5, 'Stanley', 'STHT79625', 'socket_set.jpg', 'active', 0, 1, '2025-10-01 15:02:13', '2025-10-01 15:28:08'),
(11, 'Tool Box', 'Heavy-duty tool box with multiple compartments for organizing your hand tools. Lockable for security.', 8, 60.00, 1800.00, 6, 'Klein', '85022', 'tool_box.jpg', 'active', 0, 1, '2025-10-01 15:02:13', '2025-10-01 15:02:13'),
(12, 'Extension Ladder', 'Aluminum extension ladder with 250lb load capacity. Extends from 8ft to 16ft for versatile reach.', 9, 120.00, 4500.00, 3, 'Louisville', 'LE2528', 'extension_ladder.jpg', 'active', 1, 0, '2025-10-01 15:02:13', '2025-11-06 17:21:43'),
(13, 'Step Ladder', '6-foot step ladder with GRP construction for durability. Non-slip feet and platform for safety.', 9, 90.00, 3200.00, 3, 'Werner', '6016', 'step_ladder.jpg', 'active', 0, 1, '2025-10-01 15:02:13', '2025-10-01 15:02:13'),
(14, 'Laser Level', 'Self-leveling rotary laser level with 360-degree coverage. Includes receiver for outdoor use.', 10, 150.00, 5500.00, 3, 'Bosch', 'GCL2-15', 'laser_level.jpg', 'active', 1, 0, '2025-10-01 15:02:13', '2025-10-01 15:02:13'),
(15, 'Measuring Tape', '25-foot measuring tape with durable blade and ergonomic handle. Perfect for all measuring tasks.', 10, 25.00, 400.00, 10, 'Stanley', 'FatMax 25ft', 'measuring_tape.jpg', 'active', 0, 1, '2025-10-01 15:02:13', '2025-10-01 15:02:13'),
(16, 'Demolition Hammer', 'Electric demolition hammer for breaking concrete, tile, and masonry. Variable speed control.', 11, 220.00, 8500.00, 2, 'Makita', 'HM1307', 'demo_hammer.jpg', 'active', 1, 0, '2025-10-01 15:02:13', '2025-11-06 17:20:29'),
(17, 'Floor Sander', 'Drum floor sander for refinishing hardwood floors. Includes dust collection bag for clean operation.', 12, 300.00, 12000.00, 1, 'Clarke', '2200 Turbo', 'floor_sander.jpg', 'active', 1, 0, '2025-10-01 15:02:13', '2025-10-02 08:14:05'),
(18, 'Roofing Nailer', 'Coil roofing nailer for fast installation of shingles and roofing materials. Reduces installation time significantly.', 13, 180.00, 7000.00, 2, 'Hitachi', 'NV45AB', 'roofing_nailer.jpg', 'active', 1, 0, '2025-10-01 15:02:13', '2025-10-01 15:02:13'),
(19, 'Tiller', 'Rear-tine tiller for breaking up hard soil and preparing garden beds. 4-cycle engine for reliable performance.', 14, 280.00, 11000.00, 2, 'Toro', '51608', 'tiller.jpg', 'active', 1, 0, '2025-10-01 15:02:13', '2025-10-01 15:02:13'),
(20, 'Table Saw', '10-inch table saw with 15-amp motor for precise cuts in wood. Includes blade guard and splitter for safety.', 15, 260.00, 10000.00, 2, 'Skil', '779240-00', 'table_saw.jpg', 'active', 1, 0, '2025-10-01 15:02:13', '2025-10-01 15:02:13'),
(21, 'Metal Cutting Saw', 'Abrasive chop saw for cutting metal, aluminum, and other materials. 15-amp motor for fast cuts.', 16, 190.00, 7500.00, 2, 'Makita', 'LS1013', 'metal_saw.jpg', 'active', 1, 0, '2025-10-01 15:02:13', '2025-10-01 15:02:13'),
(22, 'Chainsaw', '16-inch gas-powered chainsaw for tree trimming and cutting firewood. Professional grade with tool-free tensioning.', 1, 200.00, 8000.00, 1, 'Husqvarna', '136', 'chainsaw.jpg', 'active', 1, 0, '2025-10-01 15:02:13', '2025-11-06 17:35:31'),
(23, 'Soldering Iron Kit', 'Complete soldering station with temperature control. Perfect for electronics repair and hobby projects.', 4, 70.00, 2200.00, 4, 'Weller', 'WLC100', 'soldering_kit.jpg', 'active', 0, 1, '2025-10-01 15:02:13', '2025-10-01 15:02:13'),
(24, 'TEST', 'sfdgfddf', 6, 1234.00, 12345.00, 4, 'TEST', 'TEST_model', 'uploads/tools/tool_1759391156_68de2db490ae2.jpg', 'active', 0, 0, '2025-10-02 07:45:56', '2025-11-06 17:19:15'),
(25, 'Hedge Trimmer', 'Electric hedge trimmer for precise trimming and shaping of hedges and shrubs', 1, 120.00, 2500.00, 5, 'Bosch', 'AQT 36-14 X', '', 'active', 0, 0, '2025-10-02 08:13:25', '2025-10-02 08:13:25'),
(26, 'Leaf Blower', 'Powerful leaf blower for clearing leaves and debris from yards and driveways', 1, 150.00, 3200.00, 3, 'Makita', 'UBT2000', '', 'active', 0, 0, '2025-10-02 08:13:25', '2025-10-02 08:13:25'),
(27, 'Pruning Shears', 'Professional pruning shears for precise cutting of branches and stems', 1, 40.00, 800.00, 10, 'Fiskars', 'PowerGear2', '', 'active', 0, 0, '2025-10-02 08:13:25', '2025-10-02 08:13:25'),
(28, 'Jigsaw', 'Variable speed jigsaw for cutting curves and intricate shapes in wood', 2, 140.00, 2800.00, 4, 'DeWalt', 'DCS331', '', 'active', 0, 0, '2025-10-02 08:13:25', '2025-10-02 08:13:25'),
(29, 'Chisel Set', 'Professional wood chisel set for carving and shaping wood', 2, 60.00, 1200.00, 6, 'Stanley', 'FatMax Chisels', '', 'active', 0, 0, '2025-10-02 08:13:25', '2025-11-06 17:44:06'),
(30, 'Wood Plane', 'Traditional hand plane for smoothing and shaping wood surfaces', 2, 50.00, 1000.00, 5, 'Veritas', 'Smoothing Plane', '', 'active', 0, 0, '2025-10-02 08:13:25', '2025-10-02 08:13:25'),
(31, 'Pipe Cutter', 'Professional pipe cutter for clean cuts in copper and plastic pipes', 3, 80.00, 1600.00, 5, 'RIDGID', '32470', '', 'active', 0, 0, '2025-10-02 08:13:25', '2025-10-02 08:13:25'),
(32, 'Plunger', 'Heavy-duty plunger for clearing clogged drains and toilets', 3, 15.00, 300.00, 15, 'Korky', '528MP', '', 'active', 0, 0, '2025-10-02 08:13:25', '2025-10-02 08:13:25'),
(33, 'Tubing Cutter', 'Precision tubing cutter for cutting soft copper and plastic tubing', 3, 60.00, 1200.00, 6, 'Southwire', '30250', '', 'active', 0, 0, '2025-10-02 08:13:25', '2025-10-02 08:13:25'),
(34, 'Multimeter', 'Digital multimeter for measuring voltage, current, and resistance', 4, 70.00, 1400.00, 6, 'Fluke', '101', '', 'active', 0, 0, '2025-10-02 08:13:25', '2025-10-02 08:13:25'),
(35, 'Wire Stripper', 'Precision wire stripper for safely removing insulation from electrical wires', 4, 25.00, 500.00, 12, 'Klein', '11057', '', 'active', 0, 0, '2025-10-02 08:13:25', '2025-10-02 08:13:25'),
(36, 'Circuit Tester', 'Non-contact voltage tester for safely detecting live electrical circuits', 4, 30.00, 600.00, 13, 'Klein', 'NCVT-1', '', 'active', 0, 0, '2025-10-02 08:13:25', '2025-11-06 17:42:24'),
(37, 'Paint Roller Set', 'Professional paint roller set with various nap lengths for different surfaces', 5, 35.00, 700.00, 10, 'Wooster', 'Pro Roller', '', 'active', 0, 0, '2025-10-02 08:13:25', '2025-10-02 08:13:25'),
(38, 'Paint Brush Set', 'Premium quality paint brushes for detailed work and smooth finishes', 5, 45.00, 900.00, 8, 'Purdy', 'XL Brush', '', 'active', 0, 0, '2025-10-02 08:13:25', '2025-10-02 08:13:25'),
(39, 'Drop Cloth', 'Heavy-duty drop cloth for protecting floors and furniture during painting', 5, 20.00, 400.00, 15, 'Canvas', 'Protective', '', 'active', 0, 0, '2025-10-02 08:13:25', '2025-10-02 08:13:25'),
(40, 'Socket Wrench Set', 'Complete socket wrench set with various sizes for automotive repairs', 6, 80.00, 1600.00, 5, 'TEKTON', '3/8\" Drive', '', 'active', 0, 0, '2025-10-02 08:13:25', '2025-10-02 08:13:25'),
(41, 'Torque Wrench', 'Precision torque wrench for accurate fastener tightening', 6, 100.00, 2000.00, 3, 'CDI', 'Torque Wrench', '', 'active', 0, 0, '2025-10-02 08:13:25', '2025-10-02 08:13:25'),
(42, 'Oil Filter Wrench', 'Specialized wrench for removing and installing oil filters', 6, 30.00, 600.00, 7, 'Lisle', 'Filter Wrench', '', 'active', 0, 0, '2025-10-02 08:13:25', '2025-10-02 08:13:25'),
(43, 'Angle Grinder', 'Powerful angle grinder for cutting, grinding, and polishing metal and stone', 7, 130.00, 2600.00, 13, 'Makita', 'GA7021', '', 'active', 0, 0, '2025-10-02 08:13:25', '2025-11-06 17:53:35'),
(44, 'Reciprocating Saw', 'Heavy-duty reciprocating saw for demolition and cutting applications', 7, 140.00, 2800.00, 3, 'Milwaukee', '2720-20', '', 'active', 0, 0, '2025-10-02 08:13:25', '2025-10-02 08:13:25'),
(45, 'Random Orbital Sander', 'Variable speed orbital sander for smooth sanding of wood and paint', 7, 90.00, 1800.00, 5, 'Bosch', 'ROS20VSC', '', 'active', 0, 0, '2025-10-02 08:13:25', '2025-10-02 08:13:25'),
(46, 'Combination Wrench Set', 'Complete combination wrench set with various sizes for general use', 8, 60.00, 1200.00, 2, 'Craftsman', 'CMHT86726', '', 'active', 0, 0, '2025-10-02 08:13:25', '2025-11-06 17:47:52'),
(47, 'Pliers Set', 'Professional pliers set including needle-nose, standard, and wire cutters', 8, 40.00, 800.00, 8, 'Channellock', 'Pliers Set', '', 'active', 0, 0, '2025-10-02 08:13:25', '2025-10-02 08:13:25'),
(48, 'Screwdriver Set', 'Precision screwdriver set with various tip types and sizes', 8, 35.00, 700.00, 10, 'Wiha', 'Slotted Set', '', 'active', 0, 0, '2025-10-02 08:13:25', '2025-10-02 08:13:25'),
(49, 'Telescoping Ladder', 'Multi-position telescoping ladder for versatile height adjustments', 9, 160.00, 3200.00, 3, 'Little Giant', 'SkyScraper', '', 'active', 0, 0, '2025-10-02 08:13:25', '2025-10-02 08:13:25'),
(50, 'Platform Ladder', 'Sturdy platform ladder with wide steps for safe elevated work', 9, 140.00, 2800.00, 4, 'Werner', 'MT-28', '', 'active', 0, 0, '2025-10-02 08:13:25', '2025-10-02 08:13:25'),
(51, 'Scaffolding Tower', 'Adjustable scaffolding tower for multi-level work access', 9, 200.00, 4000.00, 2, 'Kee Klamp', 'Tower', '', 'active', 0, 0, '2025-10-02 08:13:25', '2025-10-02 08:13:25'),
(52, 'Combination Square', 'Precision combination square for measuring angles and marking lines', 10, 40.00, 800.00, 6, 'Starrett', '151', '', 'active', 0, 0, '2025-10-02 08:13:25', '2025-10-02 08:13:25'),
(53, 'Speed Square', 'Versatile speed square for quick angle measurements and cuts', 10, 25.00, 500.00, 12, 'Swanson', 'Red HD', '', 'active', 0, 0, '2025-10-02 08:13:25', '2025-10-02 08:13:25'),
(54, 'Chalk Line', 'Professional chalk line for marking long straight lines', 10, 20.00, 400.00, 10, 'Stanley', '46-024', '', 'active', 0, 0, '2025-10-02 08:13:25', '2025-11-06 17:35:27'),
(57, 'TEST12', 'fgfgjfg', 6, 12.00, 12345.00, 1, 'TEST', 'TEST_model', 'uploads/tools/tool_1759395117_68de3d2d57479.png', 'active', 0, 0, '2025-10-02 08:51:57', '2025-10-02 09:40:13');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` enum('customer','admin') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password_hash`, `first_name`, `last_name`, `phone`, `address`, `role`, `created_at`, `updated_at`) VALUES
(4, 'admin', 'admin@toolkart.com', '$2y$10$f1hLFyreqPqSdRAZPE.lz.Z3bfJmnc7S1YJy1.FYJ/CMg6O4xDldC', 'Admin', 'User', NULL, NULL, 'admin', '2025-10-01 14:55:39', '2025-10-01 14:55:39'),
(7, 'adithyann', 'adhi81952@gmail.com', '$2y$10$WWl8D/YAxIuOsje/GrvsJ.rBSaWDy2PPVOLpNn6JkSfn09yHJDsGm', 'Adithyan', 'M', '1234567890', 'TEST address', 'customer', '2025-11-06 17:23:30', '2025-11-06 17:23:30');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `tool_id` (`tool_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `faq`
--
ALTER TABLE `faq`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `tool_id` (`tool_id`);

--
-- Indexes for table `rentals`
--
ALTER TABLE `rentals`
  ADD PRIMARY KEY (`rental_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `tool_id` (`tool_id`),
  ADD KEY `idx_id_proof_image` (`id_proof_image`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD UNIQUE KEY `unique_review` (`user_id`,`rental_id`),
  ADD KEY `tool_id` (`tool_id`),
  ADD KEY `rental_id` (`rental_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_name` (`setting_name`);

--
-- Indexes for table `tools`
--
ALTER TABLE `tools`
  ADD PRIMARY KEY (`tool_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `faq`
--
ALTER TABLE `faq`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `rentals`
--
ALTER TABLE `rentals`
  MODIFY `rental_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tools`
--
ALTER TABLE `tools`
  MODIFY `tool_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`tool_id`) REFERENCES `tools` (`tool_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`tool_id`) REFERENCES `tools` (`tool_id`) ON DELETE CASCADE;

--
-- Constraints for table `rentals`
--
ALTER TABLE `rentals`
  ADD CONSTRAINT `rentals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rentals_ibfk_2` FOREIGN KEY (`tool_id`) REFERENCES `tools` (`tool_id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`tool_id`) REFERENCES `tools` (`tool_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`rental_id`) REFERENCES `rentals` (`rental_id`) ON DELETE CASCADE;

--
-- Constraints for table `tools`
--
ALTER TABLE `tools`
  ADD CONSTRAINT `tools_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
