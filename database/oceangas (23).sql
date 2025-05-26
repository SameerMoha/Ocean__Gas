-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 23, 2025 at 04:35 PM
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
-- Database: `oceangas`
--

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `cust_id` int(11) NOT NULL,
  `F_name` varchar(50) NOT NULL,
  `L_name` varchar(50) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Phone_number` varchar(20) NOT NULL,
  `pass` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `delivery_location` varchar(250) DEFAULT NULL,
  `apartment_number` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`cust_id`, `F_name`, `L_name`, `Email`, `Phone_number`, `pass`, `created_at`, `delivery_location`, `apartment_number`) VALUES
(1, 'bla', 'bla', 'bla@gmail.com', '024802840', '$2y$10$5KrAyDX7HZznGphQF4D4Je6luFWINZtRp61y64ONIdvIc3yeXC7lu', '2025-03-20 09:53:00', 'Nairobi', '99'),
(2, 'james', 'jones', 'james@gmail.com', '0727590770', '$2y$10$5xLSB2yk0j0v2xWbYFOFtO6Y6SG4iMgnlpw40HwjeIyv/HWqwXGua', '2025-04-09 07:52:26', NULL, NULL),
(3, 'ATHMAN', 'ALI', 'athman@gmail.com', '0727590770', '$2y$10$pkCn3hV1WjkPaOkHorrpve0W.r5ViEfh71GHXcOWk3tflM/ROZBxy', '2025-04-23 13:48:45', NULL, NULL),
(5, 'AMARA', 'JONES', 'amara@gmail.com', '0727590770', '$2y$10$SbiSWdNcu/8Z0ul/SAGzu.yXFhefHKQxNIi5SY3twBSIrCaQ8aH8K', '2025-05-05 07:13:52', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `deliveries`
--

CREATE TABLE `deliveries` (
  `delivery_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `delivery_status` varchar(50) DEFAULT 'Pending',
  `delivery_date` date DEFAULT NULL,
  `assigned_to` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deliveries`
--

INSERT INTO `deliveries` (`delivery_id`, `order_id`, `delivery_status`, `delivery_date`, `assigned_to`, `notes`, `created_at`) VALUES
(1, 1, 'Pending', '2025-05-14', 'Kevin', 'Rough terrain and alot of rain \r\n', '2025-05-14 08:34:46'),
(3, 2, 'Pending', '2025-05-14', 'Joe', 'extremely hot weather ', '2025-05-14 11:59:55'),
(4, 2, 'Pending', '2025-05-14', 'Joe', 'extremely hot weather ', '2025-05-14 12:01:01'),
(5, 3, 'Pending', '2025-05-14', 'Sarah', 'rough road \r\n', '2025-05-14 12:06:34'),
(6, 3, 'Pending', '2025-05-14', 'Sarah', 'rough road \r\n', '2025-05-14 12:06:43'),
(7, 9, 'Pending', '2025-05-15', 'John', 'blah\r\n', '2025-05-14 12:07:52'),
(9, 10, 'Pending', '2025-05-16', 'Yusra', 'leave it at the door it is safe \r\n', '2025-05-14 12:28:49'),
(10, 12, 'Delivered', '2025-05-16', 'Sarah', 'Don\'t be late ', '2025-05-14 15:48:40'),
(11, 11, 'Pending', '2025-05-24', 'Sarah', 'n', '2025-05-23 07:37:33'),
(12, 13, 'Pending', '2025-05-24', 'Kevin', '', '2025-05-23 08:07:09'),
(13, 18, 'Pending', '2025-05-29', 'Yusra', 'Leave at door', '2025-05-23 08:19:46'),
(14, 85, 'Cancelled', '2025-05-31', 'Sarah', '', '2025-05-23 12:56:14');

-- --------------------------------------------------------

--
-- Table structure for table `funds`
--

CREATE TABLE `funds` (
  `id` int(11) NOT NULL,
  `source_type` enum('allocation','deduction') NOT NULL,
  `funds_in` decimal(10,2) NOT NULL DEFAULT 0.00,
  `funds_out` decimal(10,2) NOT NULL DEFAULT 0.00,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `purchased_by` varchar(100) DEFAULT NULL,
  `note` varchar(255) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `funds`
--

INSERT INTO `funds` (`id`, `source_type`, `funds_in`, `funds_out`, `transaction_date`, `purchased_by`, `note`) VALUES
(1, 'allocation', 1000000.00, 0.00, '2025-03-14 05:15:16', NULL, 'Tight budget'),
(2, 'allocation', 1000.00, 0.00, '2025-03-14 05:43:46', NULL, 'Tight budget'),
(3, 'allocation', 200.00, 0.00, '2025-03-14 08:16:24', NULL, ''),
(4, 'allocation', 200000.00, 0.00, '2025-03-18 09:09:48', NULL, 'today'),
(5, 'allocation', 5000.00, 0.00, '2025-04-01 16:32:51', NULL, '1st'),
(6, 'allocation', 200.00, 0.00, '2025-04-08 07:17:56', NULL, ''),
(7, 'allocation', 50.00, 0.00, '2025-04-08 07:21:24', NULL, ''),
(8, 'allocation', 50.00, 0.00, '2025-04-08 08:43:25', NULL, ''),
(9, 'allocation', 1.00, 0.00, '2025-04-16 13:00:09', NULL, ''),
(10, 'allocation', 1.00, 0.00, '2025-04-16 13:09:54', NULL, ''),
(16, 'deduction', 0.00, 0.00, '2025-03-14 05:31:41', '4', 'Deduction for purchasing 1 units of 6kg at KES 0.00 each.'),
(17, 'deduction', 0.00, 1800.00, '2025-03-14 05:39:17', '5', 'Deduction for purchasing 2 units of 6kg at KES 900.00 each.'),
(18, 'deduction', 0.00, 18000.00, '2025-03-14 05:59:04', '6', 'Deduction for purchasing 20 units of 6kg at KES 900.00 each.'),
(19, 'deduction', 0.00, 1800.00, '2025-03-14 06:24:49', '7', 'Deduction for purchasing 3 units of 6kg at KES 600.00 each.'),
(20, 'deduction', 0.00, 1400.00, '2025-03-14 06:25:33', '8', 'Deduction for purchasing 2 units of 6kg at KES 700.00 each.'),
(21, 'deduction', 0.00, 12000.00, '2025-03-14 08:18:34', '9', 'Deduction for purchasing 20 units of 6kg at KES 600.00 each.'),
(22, 'deduction', 0.00, 10000.00, '2025-03-18 09:16:14', '10', 'Deduction for purchasing 10 units of 12kg at KES 1,000.00 each.'),
(23, 'deduction', 0.00, 14000.00, '2025-03-25 11:04:47', '11', 'Deduction for purchasing 20 units of 6kg at KES 700.00 each.'),
(24, 'deduction', 0.00, 1200.00, '2025-04-03 12:39:35', '12', 'Deduction for purchasing 2 units of 6kg at KES 600.00 each.'),
(25, 'deduction', 0.00, 600.00, '2025-04-03 13:30:36', '13', 'Deduction for purchasing 1 units of Total Gas 6kg at KES 600.00 each.'),
(26, 'deduction', 0.00, 13200.00, '2025-04-03 13:31:35', '14', 'Deduction for purchasing 11 units of Shell Afrigas 12kg at KES 1,200.00 each.'),
(27, 'deduction', 0.00, 36000.00, '2025-04-07 10:57:15', '15', 'Deduction for purchasing 60 units of Total Gas 6kg at KES 600.00 each.'),
(28, 'deduction', 0.00, 750.00, '2025-04-07 11:09:34', '16', 'Deduction for purchasing 1 units of K-gas 6kg at KES 750.00 each.'),
(29, 'deduction', 0.00, 600.00, '2025-04-07 11:35:58', '17', 'Deduction for purchasing 1 units of Total Gas 6kg at KES 600.00 each.'),
(30, 'deduction', 0.00, 0.00, '2025-04-08 08:35:09', '45', 'Purchase record for 1 units of Total Gas 6kg'),
(31, 'deduction', 0.00, 600.00, '2025-04-08 08:39:46', '47', 'Purchase record for 1 units of Total Gas 6kg'),
(32, 'deduction', 0.00, 600.00, '2025-04-08 08:40:55', '48', 'Purchase record for 1 units of Total Gas 6kg'),
(33, 'deduction', 0.00, 600.00, '2025-04-08 08:41:47', '49', 'Purchase record for 1 units of Total Gas 6kg'),
(34, 'deduction', 0.00, 600.00, '2025-04-08 08:44:49', '50', 'Purchase record for 1 units of Total Gas 6kg'),
(35, 'deduction', 0.00, 600.00, '2025-04-08 08:45:23', '51', 'Purchase record for 1 units of Total Gas 6kg'),
(36, 'deduction', 0.00, 399000.00, '2025-04-15 12:38:14', '52', 'Purchase record for 21 units of Total Gas 12kg'),
(37, 'deduction', 0.00, 1000.00, '2025-04-16 06:13:27', '53', 'Purchase record for 1 units of K-gas 12kg'),
(38, 'deduction', 0.00, 3750.00, '2025-04-16 06:14:01', '54', 'Purchase record for 5 units of K-gas 6kg'),
(39, 'deduction', 0.00, 5000.00, '2025-04-16 06:14:27', '55', 'Purchase record for 5 units of K-gas 12kg'),
(40, 'deduction', 0.00, 1000.00, '2025-04-16 13:03:12', '56', 'Purchase record for 1 units of K-gas 12kg'),
(41, 'deduction', 0.00, 1000.00, '2025-04-16 13:04:04', '57', 'Purchase record for 1 units of K-gas 12kg'),
(42, 'deduction', 0.00, 1000.00, '2025-04-16 13:20:20', '58', 'Purchase record for 1 units of K-gas 12kg'),
(43, 'deduction', 0.00, 10000.00, '2025-04-17 12:40:42', '59', 'Purchase record for 10 units of K-gas 12kg'),
(44, 'deduction', 0.00, 1200.00, '2025-04-17 12:43:54', '60', 'Purchase record for 1 units of Shell Afrigas 12kg'),
(45, 'deduction', 0.00, 1500.00, '2025-04-17 15:52:13', '61', 'Purchase record for 1 units of Total Gas 12kg'),
(46, 'deduction', 0.00, 1000.00, '2025-04-22 17:27:07', '62', 'Purchase record for 1 units of ProGas 6kg'),
(47, 'deduction', 0.00, 600.00, '2025-04-23 12:06:07', '63', 'Purchase record for 1 units of Total Gas 6kg'),
(48, 'deduction', 0.00, 20000.00, '2025-04-23 13:43:54', '64', 'Purchase record for 10 units of ProGas 12kg'),
(49, 'deduction', 0.00, 2000.00, '2025-04-30 18:11:17', '65', 'Purchase record for 1 units of ProGas 12kg'),
(50, 'deduction', 0.00, 1200.00, '2025-05-08 08:57:54', '66', 'Purchase record for 1 units of Shell Afrigas 12kg'),
(51, 'deduction', 0.00, 100000.00, '2025-05-08 08:59:35', '67', 'Purchase record for 100 units of K-gas 12kg'),
(52, 'deduction', 0.00, 15000.00, '2025-05-09 07:06:20', '68', 'Purchase record for 10 units of Total Gas 12kg'),
(53, 'deduction', 0.00, 750.00, '2025-05-09 07:15:33', '69', 'Purchase record for 1 units of K-gas 6kg'),
(54, 'deduction', 0.00, 2000.00, '2025-05-09 07:15:50', '70', 'Purchase record for 1 units of ProGas 12kg'),
(55, 'deduction', 0.00, 1000.00, '2025-05-14 07:17:08', '71', 'Purchase of 1×Luqman Gas 6kg'),
(56, 'deduction', 0.00, 20000.00, '2025-05-14 07:18:18', '72', 'Purchase of 10×Luqman Gas 12kg'),
(57, 'deduction', 0.00, 1000.00, '2025-05-14 07:57:46', '73', 'Purchase of 1×K-Gas 12kg'),
(58, 'deduction', 0.00, 1000.00, '2025-05-14 07:58:16', '74', 'Purchase of 1×K-Gas 12kg'),
(79, 'deduction', 0.00, 1000.00, '2025-05-19 08:28:32', '24', 'Purchase of 1×K-Gas 12kg'),
(80, 'deduction', 0.00, 23000.00, '2025-05-19 08:43:21', '24', 'Purchase of 10×Hashi Gas 12kg'),
(81, 'deduction', 0.00, 900.00, '2025-05-19 08:46:43', '24', 'Purchase of 1×Hashi Gas 6kg'),
(82, 'allocation', 1000.00, 0.00, '2025-05-19 09:11:39', NULL, ''),
(83, 'allocation', 1000.00, 0.00, '2025-05-20 06:57:58', NULL, ''),
(84, 'deduction', 0.00, 900.00, '2025-05-20 07:12:39', '24', 'Purchase of 1×Hashi Gas 6kg'),
(85, 'deduction', 0.00, 750.00, '2025-05-20 07:23:46', '24', 'Purchase of 1×K-Gas 6kg'),
(86, 'deduction', 0.00, 10000.00, '2025-05-23 13:33:02', '24', 'Purchase of 10×K-Gas 12kg');

-- --------------------------------------------------------

--
-- Table structure for table `inquiries_and_reviews`
--

CREATE TABLE `inquiries_and_reviews` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `company` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT current_timestamp(),
  `cust_id` int(11) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'open',
  `rating` int(11) DEFAULT NULL,
  `review_comment` text DEFAULT NULL,
  `review_date` datetime DEFAULT NULL,
  `product` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inquiries_and_reviews`
--

INSERT INTO `inquiries_and_reviews` (`id`, `name`, `email`, `location`, `company`, `message`, `submitted_at`, `cust_id`, `status`, `rating`, `review_comment`, `review_date`, `product`) VALUES
(7, NULL, NULL, NULL, NULL, 'djdjdjjdjdjdj', '2025-04-27 20:45:26', 1, 'open', NULL, NULL, NULL, NULL),
(10, NULL, NULL, NULL, NULL, 'ok', '2025-04-28 06:34:30', 1, 'open', NULL, NULL, NULL, NULL),
(11, NULL, NULL, NULL, NULL, 'bruh', '2025-04-28 06:47:26', 1, 'open', NULL, NULL, NULL, NULL),
(13, NULL, NULL, NULL, NULL, 'no\r\n', '2025-04-28 07:15:50', 1, 'open', NULL, NULL, NULL, NULL),
(17, NULL, NULL, NULL, NULL, 'hi', '2025-05-05 11:00:48', 1, 'open', NULL, NULL, NULL, NULL),
(18, 'heee', 'iiiiiiiiiiiiiiiiii@y.com', 'ihihi', 'ihi', 'l;ih', '2025-05-05 12:06:37', NULL, 'open', NULL, NULL, NULL, NULL),
(19, NULL, NULL, NULL, NULL, 'nnnnnnnnnnnnnnnnnnnnnnnnnnnn', '2025-05-06 08:51:48', 1, 'open', NULL, NULL, NULL, NULL),
(20, 'bla bla', 'bla@gmail.com', NULL, NULL, NULL, '2025-05-06 11:08:19', 1, 'open', 5, 'j', '2025-05-06 14:08:19', 'Total Gas 12kg'),
(21, 'bla bla', 'bla@gmail.com', NULL, NULL, NULL, '2025-05-06 11:27:38', 1, 'open', 4, 's', '2025-05-06 14:27:38', 'Shell Afrigas 12kg'),
(22, 'bla bla', 'bla@gmail.com', NULL, NULL, NULL, '2025-05-06 12:08:38', 1, 'open', 5, 'w', '2025-05-06 15:08:38', 'Shell Afrigas 12kg'),
(23, 'bla bla', 'bla@gmail.com', NULL, NULL, NULL, '2025-05-06 12:12:14', 1, 'open', 5, 'w', '2025-05-06 15:12:14', 'Shell Afrigas 12kg');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `invoice_summary` text DEFAULT NULL,
  `total_amount` int(11) DEFAULT NULL,
  `order_date` varchar(50) DEFAULT NULL,
  `order_number` varchar(50) DEFAULT NULL,
  `order_status` varchar(20) DEFAULT 'new',
  `cust_id` int(11) DEFAULT NULL,
  `billing_info` text DEFAULT NULL,
  `delivery_info` text DEFAULT NULL,
  `is_new` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `invoice_summary`, `total_amount`, `order_date`, `order_number`, `order_status`, `cust_id`, `billing_info`, `delivery_info`, `is_new`) VALUES
(1, '1 X K-Gas 12kg', 2300, '2025-04-09 10:24:32', '60897402', 'confirmed', NULL, '{\"name\":\"james jones\",\"email\":\"james@gmail.com\",\"phone\":\"0727590770\"}', '{\"address\":\"Mombasa\",\"apartment\":\"\"}', 0),
(2, '3 X K-Gas 12kg, 4 X K-Gas 6kg', 11700, '2025-04-09 10:31:49', '40507001', 'confirmed', NULL, '{\"name\":\"james jones\",\"email\":\"james@gmail.com\",\"phone\":\"0727590770\"}', '{\"address\":\"Kisumu\",\"apartment\":\"Pangani Palace House Number 1\"}', 0),
(3, '1 X K-Gas 12kg, 1 X Total Gas 12kg', 4600, '2025-04-09 14:40:30', '83269103', 'confirmed', NULL, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Nairobi\",\"apartment\":\"8\"}', 0),
(4, NULL, NULL, '2025-04-10 14:49:52', 'ORD1744285792', 'pending', 0, '{\"name\":\"Clark Kent\"}', '{}', 0),
(5, NULL, NULL, '2025-04-10 14:51:52', 'ORD1744285912', 'pending', 0, '{\"name\":\"Homelander\"}', '{}', 0),
(6, NULL, NULL, '2025-04-10 14:53:54', 'ORD1744286034', 'pending', 0, '{\"name\":\"Underwood\"}', '{}', 0),
(7, NULL, NULL, '2025-04-10 15:01:22', 'ORD1744286482', 'pending', 0, '{\"name\":\"Raymond Reddington\"}', '{}', 0),
(8, NULL, NULL, '2025-04-10 15:13:56', 'ORD1744287236', 'pending', 0, '{\"name\":\"Raymond Reddington\"}', '{}', 0),
(9, '1 X Total Gas 12kg', 2300, '2025-04-10 14:33:48', '14716723', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Thika\",\"apartment\":\"\"}', 0),
(10, '1 X K-Gas 12kg', 2300, '2025-04-10 14:45:47', '63140180', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Machakos\",\"apartment\":\"1\"}', 0),
(11, '1 X Total Gas 12kg', 2300, '2025-04-10 14:49:13', '13098930', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Machakos\",\"apartment\":\"\"}', 0),
(12, '1 X Shell Afrigas 12kg', 2300, '2025-04-11 11:08:01', '18581575', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Mombasa\",\"apartment\":\"\"}', 0),
(13, '1 X Shell Afrigas 12kg', 2300, '2025-04-11 11:18:02', '25486073', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Naivasha\",\"apartment\":\"\"}', 0),
(14, '1 X Shell Afrigas 12kg', 2300, '2025-04-11 11:20:01', '42310900', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Nakuru\",\"apartment\":\"\"}', 0),
(15, '1 X K-Gas 12kg', 2300, '2025-04-14 08:32:58', '66696981', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Naivasha\",\"apartment\":\"\"}', 0),
(16, NULL, NULL, '2025-04-14 10:43:37', 'ORD1744616617', 'pending', 0, '{\"name\":\"Clark Kent\"}', '{}', 0),
(17, NULL, NULL, '2025-04-14 11:57:23', 'ORD1744621043', 'pending', 0, '{\"name\":\"bro\"}', '{}', 0),
(18, '1 X Total Gas 12kg', 2300, '2025-04-14 14:05:10', '90373177', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Machakos\",\"apartment\":\"\"}', 0),
(19, '3 X K-Gas 12kg', 6900, '2025-04-14 19:08:59', '12357900', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Nairobi\",\"apartment\":\"Pangani Palace House Number 1\"}', 0),
(20, '6 X Total Gas 12kg', 13800, '2025-04-14 19:31:55', '83830725', 'confirmed', 1, '{\"name\":\"GGGGGGGGGGGGGGGGGGGGGGGGGG\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Nairobi\",\"apartment\":\"\"}', 0),
(21, '4 X Total Gas 12kg, 1 X K-Gas 12kg', 11500, '2025-04-14 23:01:26', '15077845', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Limuru\",\"apartment\":\"\"}', 0),
(22, '2 X Shell Afrigas 12kg, 2 X K-Gas 12kg', 9200, '2025-04-15 08:18:49', '58850391', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Mombasa\",\"apartment\":\" n\"}', 0),
(23, '1 X Shell Afrigas 12kg', 2300, '2025-04-15 08:22:17', '36718046', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Thika\",\"apartment\":\"y\"}', 0),
(24, '2 X Shell Afrigas 12kg', 4600, '2025-04-15 08:22:59', '61356645', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Nairobi\",\"apartment\":\"n\"}', 0),
(25, '1 X Shell Afrigas 12kg, 1 X K-Gas 12kg', 4600, '2025-04-15 08:27:01', '66561437', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Kisumu\",\"apartment\":\"\"}', 0),
(26, '1 X Shell Afrigas 12kg, 1 X K-Gas 12kg', 4600, '2025-04-15 08:28:00', '64894482', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Mombasa\",\"apartment\":\"\"}', 0),
(27, '20 X Total Gas 6kg, 1 X Shell Afrigas 12kg', 26300, '2025-04-15 08:39:07', '84226078', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Mombasa\",\"apartment\":\"\"}', 0),
(28, '5 X Shell Afrigas 12kg, 3 X K-Gas 6kg', 15100, '2025-04-15 08:51:46', '77935329', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Mombasa\",\"apartment\":\"\"}', 0),
(29, '1 X K-Gas 12kg, 1 X Shell Afrigas 12kg', 4600, '2025-04-15 11:09:51', '98572104', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Mombasa\",\"apartment\":\"n\"}', 0),
(30, '1 X Shell Afrigas 12kg, 1 X K-Gas 12kg', 4600, '2025-04-15 12:58:48', '55241848', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Thika\",\"apartment\":\"\"}', 0),
(31, '1 X Shell Afrigas 12kg, 1 X K-Gas 12kg', 4600, '2025-04-15 13:32:24', '48384221', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Nairobi\",\"apartment\":\"\"}', 0),
(32, '1 X Shell Afrigas 12kg, 1 X K-Gas 12kg', 0, '2025-04-15 14:14:09', '11708544', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Nairobi\",\"apartment\":\"\"}', 0),
(33, '1 X Shell Afrigas 12kg, 1 X K-Gas 12kg', 0, '2025-04-15 14:20:50', '32296198', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Machakos\",\"apartment\":\"\"}', 0),
(34, '1 X Total Gas 12kg, 1 X K-Gas 12kg', 4600, '2025-04-15 14:26:03', '10696296', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Eldoret\",\"apartment\":\"\"}', 0),
(35, '2 X Shell Afrigas 12kg, 1 X K-Gas 12kg', 6900, '2025-04-15 14:29:49', '36160333', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Machakos\",\"apartment\":\"\"}', 0),
(36, '14 X Total Gas 12kg', 32200, '2025-04-15 14:35:05', '69472949', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Nairobi\",\"apartment\":\"\"}', 0),
(37, '6 X Shell Afrigas 12kg', 13800, '2025-04-15 22:13:52', '62097430', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Nairobi\",\"apartment\":\"\"}', 0),
(38, '14 X K-Gas 12kg', 32200, '2025-04-16 08:08:03', '54876515', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Nairobi\",\"apartment\":\"\"}', 0),
(39, '3 X K-Gas 12kg', 6900, '2025-04-16 08:15:24', '61501528', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Nairobi\",\"apartment\":\"\"}', 0),
(40, '5 X K-Gas 12kg', 11500, '2025-04-16 08:19:13', '44179602', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Mombasa\",\"apartment\":\"\"}', 0),
(41, '1 X Shell Afrigas 12kg', 2000, '2025-04-22 14:30:32', '65981575', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Nyeri\",\"apartment\":\"\"}', 0),
(42, '1 X Total Gas 6kg', 1200, '2025-04-23 10:48:28', '62135045', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Machakos\",\"apartment\":\"\"}', 0),
(43, '1 X K-Gas 6kg', 1200, '2025-04-23 10:58:24', '90738423', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Nakuru\",\"apartment\":\"\"}', 0),
(44, '2 X Total Gas 6kg, 2 X Total Gas 12kg', 7000, '2025-04-23 10:59:44', '96717496', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Eldoret\",\"apartment\":\"\"}', 0),
(45, '10 X ProGas 12kg, 3 X Total Gas 12kg', 29900, '2025-04-23 15:50:01', '97901347', 'confirmed', 3, '{\"name\":\"ATHMAN ALI\",\"email\":\"athman@gmail.com\",\"phone\":\"0727590770\"}', '{\"address\":\"Kisumu\",\"apartment\":\"Pangani Palace House Number 1\"}', 0),
(46, '2 X Total Gas 6kg', 2400, '2025-04-24 13:46:26', '90204765', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Eldoret\",\"apartment\":\"Pangani Palace House Number 1\"}', 0),
(47, '1 X Total Gas 6kg', 1200, '2025-04-26 11:32:17', '81221218', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Nairobi\",\"apartment\":\"3\"}', 0),
(48, '1 X Total Gas 12kg', 2300, '2025-04-26 11:34:02', '55801468', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Mombasa\",\"apartment\":\"1\"}', 0),
(49, '2 X K-Gas 12kg', 4600, '2025-04-26 12:37:00', '44944248', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Kisumu\",\"apartment\":\"nnn\"}', 0),
(50, '1 X K-Gas 12kg', 2300, '2025-04-26 12:39:51', '92409410', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Kisumu\",\"apartment\":\"\"}', 0),
(51, '1 X Total Gas 6kg', 1200, '2025-04-26 12:46:09', '12747742', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Machakos\",\"apartment\":\"e\"}', 0),
(52, '1 X Total Gas 6kg', 1200, '2025-04-26 13:09:50', '64806703', 'confirmed', 2, '{\"name\":\"james jones\",\"email\":\"james@gmail.com\",\"phone\":\"0727590770\"}', '{\"address\":\"Thika\",\"apartment\":\"n\"}', 0),
(53, '1 X Total Gas 12kg', 2300, '2025-04-30 15:43:43', '98709021', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Limuru\",\"apartment\":\"weeh\"}', 0),
(54, '1 X Total Gas 6kg', 1200, '2025-04-30 16:01:13', '65889939', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Machakos\",\"apartment\":\"m\"}', 0),
(55, '1 X ProGas 12kg', 2300, '2025-04-30 21:12:22', '29498566', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Nairobi\",\"apartment\":\"3\"}', 0),
(56, '1 X Total Gas 6kg', 1200, '2025-04-30 22:04:30', '74048493', 'pending', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Nairobi\",\"apartment\":\"home\"}', 0),
(57, '1 X Total Gas 6kg', 1200, '2025-04-30 22:11:39', '26923022', 'pending', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Kisumu\",\"apartment\":\"2\"}', 0),
(58, '1 X Total Gas 6kg', 1200, '2025-05-02 09:19:54', '11766190', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Limuru\",\"apartment\":\"Pangani Palace House Number 1\"}', 0),
(59, '1 X Total Gas 12kg', 2300, '2025-05-02 09:22:22', '79853403', 'pending', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Nakuru\",\"apartment\":\"\"}', 0),
(60, '1 X K-Gas 12kg', 2300, '2025-05-02 09:25:42', '17512090', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Kisumu\",\"apartment\":\"\"}', 0),
(61, '1 X Total Gas 6kg', 1200, '2025-05-02 09:30:11', '53473636', 'pending', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Nakuru\",\"apartment\":\"\"}', 0),
(62, '1 X Total Gas 6kg, 1 X Total Gas 12kg', 3500, '2025-05-02 09:34:28', '76594366', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Thika\",\"apartment\":\"\"}', 0),
(63, '1 X Total Gas 6kg', 1200, '2025-05-02 10:06:15', '90186266', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Mombasa\",\"apartment\":\"\"}', 0),
(64, '1 X K-Gas 12kg', 2300, '2025-05-02 10:14:25', '63294029', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Limuru\",\"apartment\":\"\"}', 0),
(65, '1 X Total Gas 6kg', 1200, '2025-05-02 10:22:14', '46840903', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Nyeri\",\"apartment\":\"\"}', 0),
(66, '1 X Total Gas 6kg, 1 X Total Gas 12kg', 3500, '2025-05-02 10:51:28', '23060669', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Thika\",\"apartment\":\"\"}', 0),
(67, '1 X K-Gas 12kg', 2300, '2025-05-02 11:47:49', '56842099', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Nairobi\",\"apartment\":\"\"}', 0),
(68, '1 X Shell Afrigas 6kg', 0, '2025-05-02 12:00:17', '10276126', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Eldoret\",\"apartment\":\"s\"}', 0),
(69, '1 X Shell Afrigas 6kg', 1200, '2025-05-02 12:03:57', '63972859', 'pending', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Eldoret\",\"apartment\":\"s\"}', 0),
(70, '1 X Total Gas 6kg', 1200, '2025-05-02 12:04:17', '18628604', 'pending', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Machakos\",\"apartment\":\"1\"}', 0),
(71, '1 X Total Gas 6kg, 1 X Total Gas 12kg', 3500, '2025-05-02 15:35:43', '23083976', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Nakuru\",\"apartment\":\"\"}', 0),
(72, '1 X K-Gas 12kg', 2300, '2025-05-02 15:48:50', '17916396', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Limuru\",\"apartment\":\"\"}', 0),
(73, '1 X Total Gas 6kg', 1200, '2025-05-05 10:11:22', '71091118', 'confirmed', 3, '{\"name\":\"ATHMAN ALI\",\"email\":\"athman@gmail.com\",\"phone\":\"0727590770\"}', '{\"address\":\"Naivasha\",\"apartment\":\"\"}', 0),
(74, '1 X Total Gas 6kg', 1200, '2025-05-05 12:04:05', '44785370', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Nairobi\",\"apartment\":\"\"}', 0),
(75, '1 X Shell Afrigas 12kg', 2300, '2025-05-08 10:42:52', '34676000', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Nakuru\",\"apartment\":\"\"}', 0),
(76, '1 X Shell Afrigas 6kg', 1200, '2025-05-08 10:51:38', '52797129', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Kisumu\",\"apartment\":\"m\"}', 0),
(77, '1 X K-Gas 6kg', 1200, '2025-05-08 11:08:14', '98200691', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Mombasa\",\"apartment\":\"\"}', 0),
(78, '1 X Shell Afrigas 6kg', 1200, '2025-05-08 11:10:38', '65539459', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Limuru\",\"apartment\":\"\"}', 0),
(79, '1 X K-Gas 6kg', 1200, '2025-05-08 11:11:33', '52974686', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Thika\",\"apartment\":\"\"}', 0),
(80, '1 X K-Gas 6kg, 1 X Total Gas 6kg, 1 X Shell Afrigas 6kg', 3600, '2025-05-08 11:13:48', '35778733', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Nakuru\",\"apartment\":\"\"}', 0),
(81, '5 X K-Gas 12kg', 11500, '2025-05-08 12:02:00', '21793643', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Machakos\",\"apartment\":\"\"}', 0),
(82, '1 X ProGas 12kg', 2300, '2025-05-13 10:08:52', '49300023', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Mombasa\",\"apartment\":\"\"}', 0),
(83, '1 X Luqman Gas 6kg', 1200, '2025-05-14 15:43:32', '21154009', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Nairobi\",\"apartment\":\"\"}', 0),
(84, '1 X K-Gas 12kg', 2300, '2025-05-15 11:09:29', '71212829', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Mombasa\",\"apartment\":\"3\"}', 0),
(85, '1 X Total Gas 12kg', 2400, '2025-05-19 14:05:33', '52142541', 'confirmed', 1, '{\"name\":\"bla bla\",\"email\":\"bla@gmail.com\",\"phone\":\"024802840\"}', '{\"address\":\"Limuru\",\"apartment\":\"\"}', 0);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED DEFAULT NULL,
  `product_id` int(10) UNSIGNED DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `product_id`, `quantity`, `unit_price`, `product_name`) VALUES
(1, 3, NULL, 1, 1200.00, '6kg Gas Cylinder'),
(2, 4, NULL, 1, 2300.00, '12kg Gas Cylinder'),
(3, 5, NULL, 1, 2300.00, '12kg Gas Cylinder'),
(4, 8, NULL, 1, 1200.00, '6kg Gas Cylinder'),
(5, 11, NULL, 1, 1200.00, '6kg Gas Cylinder'),
(6, 13, NULL, 1, 2300.00, '12kg Gas Cylinder'),
(7, 15, NULL, 1, 2300.00, '12kg Gas Cylinder'),
(8, 15, NULL, 1, 1200.00, '6kg Gas Cylinder'),
(9, 15, NULL, 1, 1200.00, '6kg Gas Cylinder'),
(10, 16, NULL, 1, 1200.00, '6kg Gas Cylinder'),
(11, 16, NULL, 1, 1200.00, '6kg Gas Cylinder'),
(12, 16, NULL, 1, 2300.00, '12kg Gas Cylinder'),
(13, 17, NULL, 1, 2300.00, '12kg Gas Cylinder'),
(14, 17, NULL, 1, 2300.00, '12kg Gas Cylinder'),
(15, 17, NULL, 1, 2300.00, '12kg Gas Cylinder'),
(16, 17, NULL, 1, 1200.00, '6kg Gas Cylinder'),
(17, 18, NULL, 1, 2300.00, '12kg Gas Cylinder'),
(18, 19, NULL, 1, 2300.00, '12kg Gas Cylinder'),
(19, 19, NULL, 1, 1200.00, '6kg Gas Cylinder'),
(20, 19, NULL, 1, 1200.00, '6kg Gas Cylinder'),
(21, 19, NULL, 1, 1200.00, '6kg Gas Cylinder'),
(22, 19, NULL, 1, 2300.00, '12kg Gas Cylinder'),
(23, 20, NULL, 1, 1200.00, '6kg Gas Cylinder'),
(24, 21, NULL, 1, 2300.00, '12kg Gas Cylinder'),
(25, 22, NULL, 1, 1200.00, '6kg Gas Cylinder'),
(26, 23, NULL, 1, 1200.00, '6kg Gas Cylinder'),
(27, 24, NULL, 1, 1200.00, '6kg Gas Cylinder'),
(28, 25, NULL, 1, 2300.00, '12kg Gas Cylinder'),
(29, 27, NULL, 1, 2300.00, '12kg Gas Cylinder'),
(30, 28, NULL, 1, 2300.00, '12kg Gas Cylinder'),
(31, 30, NULL, 1, 2300.00, '12kg Gas Cylinder'),
(32, 31, NULL, 1, 2300.00, '12kg Gas Cylinder'),
(33, 32, NULL, 1, 2300.00, '12kg Gas Cylinder'),
(34, 33, NULL, 1, 2300.00, '12kg Gas Cylinder'),
(35, 34, NULL, 1, 2300.00, '12kg Gas Cylinder'),
(36, 35, NULL, 1, 2300.00, '12kg Gas Cylinder'),
(37, 36, NULL, 1, 2300.00, '12kg Gas Cylinder'),
(38, 37, NULL, 1, 1200.00, '6kg Gas Cylinder'),
(39, 37, NULL, 1, 1200.00, '6kg Gas Cylinder'),
(40, 38, NULL, 1, 2300.00, '12kg Gas Cylinder'),
(41, 38, NULL, 1, 2300.00, '12kg Gas Cylinder'),
(42, 38, NULL, 1, 2300.00, '12kg Gas Cylinder'),
(43, 38, NULL, 1, 2300.00, '12kg Gas Cylinder'),
(44, 39, NULL, 1, 2300.00, '12kg Gas Cylinder'),
(45, 40, NULL, 1, 2300.00, '12kg Gas Cylinder'),
(46, 40, NULL, 1, 1200.00, '6kg Gas Cylinder'),
(47, 44, NULL, 1, 1200.00, '6kg Gas Cylinder'),
(48, 1, NULL, 1, 2300.00, 'K-Gas 12kg'),
(49, 2, NULL, 1, 2300.00, 'K-Gas 12kg'),
(50, 2, NULL, 1, 2300.00, 'K-Gas 12kg'),
(51, 2, NULL, 1, 2300.00, 'K-Gas 12kg'),
(52, 2, NULL, 1, 1200.00, 'K-Gas 6kg'),
(53, 2, NULL, 1, 1200.00, 'K-Gas 6kg'),
(54, 2, NULL, 1, 1200.00, 'K-Gas 6kg'),
(55, 2, NULL, 1, 1200.00, 'K-Gas 6kg'),
(56, 3, NULL, 1, 2300.00, 'K-Gas 12kg'),
(57, 3, NULL, 1, 2300.00, 'Total Gas 12kg'),
(58, 9, NULL, 1, 2300.00, 'Total Gas 12kg'),
(59, 10, NULL, 1, 2300.00, 'K-Gas 12kg'),
(60, 11, NULL, 1, 2300.00, 'Total Gas 12kg'),
(61, 12, NULL, 1, 2300.00, 'Shell Afrigas 12kg'),
(62, 13, NULL, 1, 2300.00, 'Shell Afrigas 12kg'),
(63, 14, NULL, 1, 2300.00, 'Shell Afrigas 12kg'),
(64, 15, NULL, 1, 2300.00, 'K-Gas 12kg'),
(65, 18, NULL, 1, 2300.00, 'Total Gas 12kg'),
(66, 19, NULL, 1, 2300.00, 'K-Gas 12kg'),
(67, 19, NULL, 1, 2300.00, 'K-Gas 12kg'),
(68, 19, NULL, 1, 2300.00, 'K-Gas 12kg'),
(69, 20, NULL, 1, 2300.00, 'Total Gas 12kg'),
(70, 20, NULL, 1, 2300.00, 'Total Gas 12kg'),
(71, 20, NULL, 1, 2300.00, 'Total Gas 12kg'),
(72, 20, NULL, 1, 2300.00, 'Total Gas 12kg'),
(73, 20, NULL, 1, 2300.00, 'Total Gas 12kg'),
(74, 20, NULL, 1, 2300.00, 'Total Gas 12kg'),
(75, 21, NULL, 1, 2300.00, 'Total Gas 12kg'),
(76, 21, NULL, 1, 2300.00, 'Total Gas 12kg'),
(77, 21, NULL, 1, 2300.00, 'Total Gas 12kg'),
(78, 21, NULL, 1, 2300.00, 'Total Gas 12kg'),
(79, 21, NULL, 1, 2300.00, 'K-Gas 12kg'),
(80, 22, NULL, 1, 2300.00, 'Shell Afrigas 12kg'),
(81, 22, NULL, 1, 2300.00, 'Shell Afrigas 12kg'),
(82, 22, NULL, 1, 2300.00, 'K-Gas 12kg'),
(83, 22, NULL, 1, 2300.00, 'K-Gas 12kg'),
(84, 23, NULL, 1, 2300.00, 'Shell Afrigas 12kg'),
(85, 24, NULL, 1, 2300.00, 'Shell Afrigas 12kg'),
(86, 24, NULL, 1, 2300.00, 'Shell Afrigas 12kg'),
(87, 25, NULL, 1, 2300.00, 'Shell Afrigas 12kg'),
(88, 25, NULL, 1, 2300.00, 'K-Gas 12kg'),
(89, 26, NULL, 1, 2300.00, 'Shell Afrigas 12kg'),
(90, 26, NULL, 1, 2300.00, 'K-Gas 12kg'),
(91, 27, NULL, 1, 1200.00, 'Total Gas 6kg'),
(92, 27, NULL, 1, 1200.00, 'Total Gas 6kg'),
(93, 27, NULL, 1, 1200.00, 'Total Gas 6kg'),
(94, 27, NULL, 1, 1200.00, 'Total Gas 6kg'),
(95, 27, NULL, 1, 1200.00, 'Total Gas 6kg'),
(96, 27, NULL, 1, 1200.00, 'Total Gas 6kg'),
(97, 27, NULL, 1, 1200.00, 'Total Gas 6kg'),
(98, 27, NULL, 1, 1200.00, 'Total Gas 6kg'),
(99, 27, NULL, 1, 1200.00, 'Total Gas 6kg'),
(100, 27, NULL, 1, 1200.00, 'Total Gas 6kg'),
(101, 27, NULL, 1, 1200.00, 'Total Gas 6kg'),
(102, 27, NULL, 1, 1200.00, 'Total Gas 6kg'),
(103, 27, NULL, 1, 1200.00, 'Total Gas 6kg'),
(104, 27, NULL, 1, 1200.00, 'Total Gas 6kg'),
(105, 27, NULL, 1, 1200.00, 'Total Gas 6kg'),
(106, 27, NULL, 1, 1200.00, 'Total Gas 6kg'),
(107, 27, NULL, 1, 1200.00, 'Total Gas 6kg'),
(108, 27, NULL, 1, 1200.00, 'Total Gas 6kg'),
(109, 27, NULL, 1, 1200.00, 'Total Gas 6kg'),
(110, 27, NULL, 1, 1200.00, 'Total Gas 6kg'),
(111, 27, NULL, 1, 2300.00, 'Shell Afrigas 12kg'),
(112, 28, NULL, 1, 2300.00, 'Shell Afrigas 12kg'),
(113, 28, NULL, 1, 2300.00, 'Shell Afrigas 12kg'),
(114, 28, NULL, 1, 2300.00, 'Shell Afrigas 12kg'),
(115, 28, NULL, 1, 2300.00, 'Shell Afrigas 12kg'),
(116, 28, NULL, 1, 2300.00, 'Shell Afrigas 12kg'),
(117, 28, NULL, 1, 1200.00, 'K-Gas 6kg'),
(118, 28, NULL, 1, 1200.00, 'K-Gas 6kg'),
(119, 28, NULL, 1, 1200.00, 'K-Gas 6kg'),
(120, 29, NULL, 1, 2300.00, 'K-Gas 12kg'),
(121, 29, NULL, 1, 2300.00, 'Shell Afrigas 12kg'),
(122, 30, NULL, 1, 2300.00, 'Shell Afrigas 12kg'),
(123, 30, NULL, 1, 2300.00, 'K-Gas 12kg'),
(124, 31, NULL, 1, 2300.00, 'Shell Afrigas 12kg'),
(125, 31, NULL, 1, 2300.00, 'K-Gas 12kg'),
(126, 32, NULL, 1, 2300.00, 'Shell Afrigas 12kg'),
(127, 32, NULL, 1, 2300.00, 'K-Gas 12kg'),
(128, 33, NULL, 1, 2300.00, 'Shell Afrigas 12kg'),
(129, 33, NULL, 1, 2300.00, 'K-Gas 12kg'),
(130, 34, NULL, 1, 2300.00, 'Total Gas 12kg'),
(131, 34, NULL, 1, 2300.00, 'K-Gas 12kg'),
(132, 35, NULL, 1, 2300.00, 'Shell Afrigas 12kg'),
(133, 35, NULL, 1, 2300.00, 'K-Gas 12kg'),
(134, 35, NULL, 1, 2300.00, 'Shell Afrigas 12kg'),
(135, 36, NULL, 1, 2300.00, 'Total Gas 12kg'),
(136, 36, NULL, 1, 2300.00, 'Total Gas 12kg'),
(137, 36, NULL, 1, 2300.00, 'Total Gas 12kg'),
(138, 36, NULL, 1, 2300.00, 'Total Gas 12kg'),
(139, 36, NULL, 1, 2300.00, 'Total Gas 12kg'),
(140, 36, NULL, 1, 2300.00, 'Total Gas 12kg'),
(141, 36, NULL, 1, 2300.00, 'Total Gas 12kg'),
(142, 36, NULL, 1, 2300.00, 'Total Gas 12kg'),
(143, 36, NULL, 1, 2300.00, 'Total Gas 12kg'),
(144, 36, NULL, 1, 2300.00, 'Total Gas 12kg'),
(145, 36, NULL, 1, 2300.00, 'Total Gas 12kg'),
(146, 36, NULL, 1, 2300.00, 'Total Gas 12kg'),
(147, 36, NULL, 1, 2300.00, 'Total Gas 12kg'),
(148, 36, NULL, 1, 2300.00, 'Total Gas 12kg'),
(149, 37, NULL, 1, 2300.00, 'Shell Afrigas 12kg'),
(150, 37, NULL, 1, 2300.00, 'Shell Afrigas 12kg'),
(151, 37, NULL, 1, 2300.00, 'Shell Afrigas 12kg'),
(152, 37, NULL, 1, 2300.00, 'Shell Afrigas 12kg'),
(153, 37, NULL, 1, 2300.00, 'Shell Afrigas 12kg'),
(154, 37, NULL, 1, 2300.00, 'Shell Afrigas 12kg'),
(155, 38, NULL, 1, 2300.00, 'K-Gas 12kg'),
(156, 38, NULL, 1, 2300.00, 'K-Gas 12kg'),
(157, 38, NULL, 1, 2300.00, 'K-Gas 12kg'),
(158, 38, NULL, 1, 2300.00, 'K-Gas 12kg'),
(159, 38, NULL, 1, 2300.00, 'K-Gas 12kg'),
(160, 38, NULL, 1, 2300.00, 'K-Gas 12kg'),
(161, 38, NULL, 1, 2300.00, 'K-Gas 12kg'),
(162, 38, NULL, 1, 2300.00, 'K-Gas 12kg'),
(163, 38, NULL, 1, 2300.00, 'K-Gas 12kg'),
(164, 38, NULL, 1, 2300.00, 'K-Gas 12kg'),
(165, 38, NULL, 1, 2300.00, 'K-Gas 12kg'),
(166, 38, NULL, 1, 2300.00, 'K-Gas 12kg'),
(167, 38, NULL, 1, 2300.00, 'K-Gas 12kg'),
(168, 38, NULL, 1, 2300.00, 'K-Gas 12kg'),
(169, 39, NULL, 1, 2300.00, 'K-Gas 12kg'),
(170, 39, NULL, 1, 2300.00, 'K-Gas 12kg'),
(171, 39, NULL, 1, 2300.00, 'K-Gas 12kg'),
(172, 40, NULL, 1, 2300.00, 'K-Gas 12kg'),
(173, 40, NULL, 1, 2300.00, 'K-Gas 12kg'),
(174, 40, NULL, 1, 2300.00, 'K-Gas 12kg'),
(175, 40, NULL, 1, 2300.00, 'K-Gas 12kg'),
(176, 40, NULL, 1, 2300.00, 'K-Gas 12kg'),
(177, 41, NULL, 1, 2000.00, 'Shell Afrigas 12kg'),
(178, 42, NULL, 1, 1200.00, 'Total Gas 6kg'),
(179, 43, NULL, 1, 1200.00, 'K-Gas 6kg'),
(180, 44, NULL, 1, 1200.00, 'Total Gas 6kg'),
(181, 44, NULL, 1, 1200.00, 'Total Gas 6kg'),
(182, 44, NULL, 1, 2300.00, 'Total Gas 12kg'),
(183, 44, NULL, 1, 2300.00, 'Total Gas 12kg'),
(184, 45, NULL, 1, 2300.00, 'ProGas 12kg'),
(185, 45, NULL, 1, 2300.00, 'ProGas 12kg'),
(186, 45, NULL, 1, 2300.00, 'ProGas 12kg'),
(187, 45, NULL, 1, 2300.00, 'ProGas 12kg'),
(188, 45, NULL, 1, 2300.00, 'ProGas 12kg'),
(189, 45, NULL, 1, 2300.00, 'ProGas 12kg'),
(190, 45, NULL, 1, 2300.00, 'ProGas 12kg'),
(191, 45, NULL, 1, 2300.00, 'ProGas 12kg'),
(192, 45, NULL, 1, 2300.00, 'ProGas 12kg'),
(193, 45, NULL, 1, 2300.00, 'ProGas 12kg'),
(194, 45, NULL, 1, 2300.00, 'Total Gas 12kg'),
(195, 45, NULL, 1, 2300.00, 'Total Gas 12kg'),
(196, 45, NULL, 1, 2300.00, 'Total Gas 12kg'),
(197, 46, NULL, 1, 1200.00, 'Total Gas 6kg'),
(198, 46, NULL, 1, 1200.00, 'Total Gas 6kg'),
(199, 47, NULL, 1, 1200.00, 'Total Gas 6kg'),
(200, 48, NULL, 1, 2300.00, 'Total Gas 12kg'),
(201, 49, NULL, 1, 2300.00, 'K-Gas 12kg'),
(202, 49, NULL, 1, 2300.00, 'K-Gas 12kg'),
(203, 50, NULL, 1, 2300.00, 'K-Gas 12kg'),
(204, 51, NULL, 1, 1200.00, 'Total Gas 6kg'),
(205, 52, NULL, 1, 1200.00, 'Total Gas 6kg'),
(206, 53, NULL, 1, 2300.00, 'Total Gas 12kg'),
(207, 54, NULL, 1, 1200.00, 'Total Gas 6kg'),
(208, 55, NULL, 1, 2300.00, 'ProGas 12kg'),
(209, 56, NULL, 1, 1200.00, 'Total Gas 6kg'),
(210, 57, NULL, 1, 1200.00, 'Total Gas 6kg'),
(211, 58, 6, 1, 1200.00, 'Total Gas 6kg'),
(212, 59, 3, 1, 2300.00, 'Total Gas 12kg'),
(213, 60, 2, 1, 2300.00, 'K-Gas 12kg'),
(214, 61, 6, 1, 1200.00, 'Total Gas 6kg'),
(215, 62, 6, 1, 1200.00, 'Total Gas 6kg'),
(216, 62, 3, 1, 2300.00, 'Total Gas 12kg'),
(217, 63, 6, 1, 1200.00, 'Total Gas 6kg'),
(218, 64, 2, 1, 2300.00, 'K-Gas 12kg'),
(219, 65, 6, 1, 1200.00, 'Total Gas 6kg'),
(220, 66, 6, 1, 1200.00, 'Total Gas 6kg'),
(221, 66, 3, 1, 2300.00, 'Total Gas 12kg'),
(222, 67, 2, 1, 2300.00, 'K-Gas 12kg'),
(223, 69, 4, 1, 1200.00, 'Shell Afrigas 6kg'),
(224, 70, 6, 1, 1200.00, 'Total Gas 6kg'),
(225, 71, 6, 1, 1200.00, 'Total Gas 6kg'),
(226, 71, 3, 1, 2300.00, 'Total Gas 12kg'),
(227, 72, 2, 1, 2300.00, 'K-Gas 12kg'),
(228, 73, 6, 1, 1200.00, 'Total Gas 6kg'),
(229, 74, 6, 1, 1200.00, 'Total Gas 6kg'),
(230, 75, 1, 1, 2300.00, 'Shell Afrigas 12kg'),
(231, 76, 4, 1, 1200.00, 'Shell Afrigas 6kg'),
(232, 77, 5, 1, 1200.00, 'K-Gas 6kg'),
(233, 78, 4, 1, 1200.00, 'Shell Afrigas 6kg'),
(234, 79, 5, 1, 1200.00, 'K-Gas 6kg'),
(235, 80, 5, 1, 1200.00, 'K-Gas 6kg'),
(236, 80, 6, 1, 1200.00, 'Total Gas 6kg'),
(237, 80, 4, 1, 1200.00, 'Shell Afrigas 6kg'),
(238, 81, 2, 1, 2300.00, 'K-Gas 12kg'),
(239, 81, 2, 1, 2300.00, 'K-Gas 12kg'),
(240, 81, 2, 1, 2300.00, 'K-Gas 12kg'),
(241, 81, 2, 1, 2300.00, 'K-Gas 12kg'),
(242, 81, 2, 1, 2300.00, 'K-Gas 12kg'),
(243, 82, 12, 1, 2300.00, 'ProGas 12kg'),
(244, 83, 20, 1, 1200.00, 'Luqman Gas 6kg'),
(245, 84, 2, 1, 2300.00, 'K-Gas 12kg'),
(246, 85, 3, 1, 2400.00, 'Total Gas 12kg');

-- --------------------------------------------------------

--
-- Table structure for table `price`
--

CREATE TABLE `price` (
  `price_id` int(11) NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `selling_price` decimal(10,2) NOT NULL,
  `buying_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `price`
--

INSERT INTO `price` (`price_id`, `product_id`, `supplier_id`, `selling_price`, `buying_price`) VALUES
(1, 1, 2, 2300.00, 1900.00),
(2, 2, 1, 2300.00, 1000.00),
(3, 3, 3, 2400.00, 1500.00),
(4, 4, 2, 1200.00, 1000.00),
(5, 5, 1, 1100.00, 750.00),
(6, 6, 3, 1200.00, 900.00),
(7, 11, 10, 1200.00, 1000.00),
(8, 12, 10, 2300.00, 2000.00),
(9, 20, 16, 1200.00, 1000.00),
(10, 21, 16, 2300.00, 2000.00),
(11, 13, 11, 1200.00, 900.00),
(13, 14, 11, 2300.00, 2300.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(10) UNSIGNED NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `image_path` varchar(255) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `product_name`, `description`, `created_at`, `image_path`, `quantity`) VALUES
(1, 'Shell Afrigas 12kg', 'High-quality 12kg gas cylinder for household use', '2025-04-02 13:52:16', 'assets/images/Shell Afrigas 12kg.jpg', 0),
(2, 'K-Gas 12kg', 'High-quality 12kg gas cylinder for household use', '2025-04-02 13:52:16', 'assets/images/K-Gas 12kg.jpg', 97),
(3, 'Total Gas 12kg', 'High-quality 12kg gas cylinder for household use', '2025-04-02 13:52:16', 'assets/images/Total Gas 12kg.jpg', 9),
(4, 'Shell Afrigas 6kg', 'High-quality 6kg gas cylinder for household use', '2025-04-02 13:52:16', 'assets/images/Shell Afrigas 6kg.jpg', 85),
(5, 'K-Gas 6kg', 'High-quality 6kg gas cylinder for household use', '2025-04-02 13:52:16', 'assets/images/K-Gas 6kg.jpg', 44),
(6, 'Total Gas 6kg', 'High-quality 6kg gas cylinder for household use', '2025-04-02 13:52:16', 'assets/images/Total Gas 6kg.jpg', 64),
(11, 'ProGas 6kg', '6kg cylinder supplied by ProGas', '2025-04-23 04:49:41', 'assets/images/ProGas 6kg.jpg', 0),
(12, 'ProGas 12kg', '12kg cylinder supplied by ProGas', '2025-04-23 04:49:41', 'assets/images/ProGas 12kg.jpg', 0),
(13, 'Hashi Gas 6kg', '6kg cylinder supplied by Hashi Gas', '2025-05-13 16:40:51', NULL, 4),
(14, 'Hashi Gas 12kg', '12kg cylinder supplied by Hashi Gas', '2025-05-13 16:40:51', NULL, 10),
(20, 'Luqman Gas 6kg', '6kg cylinder supplied by Luqman Gas', '2025-05-13 17:04:13', NULL, 0),
(21, 'Luqman Gas 12kg', '12kg cylinder supplied by Luqman Gas', '2025-05-13 17:04:13', NULL, 10);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_history`
--

CREATE TABLE `purchase_history` (
  `id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `product` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL,
  `purchased_by` int(11) NOT NULL,
  `purchase_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT 'completed',
  `total` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_history`
--

INSERT INTO `purchase_history` (`id`, `supplier_id`, `product`, `quantity`, `purchased_by`, `purchase_date`, `status`, `total`) VALUES
(1, 2, '6kg', 10, 22, '2025-03-14 04:42:24', 'completed', NULL),
(2, 3, '12kg', 12, 24, '2025-03-14 04:52:05', 'completed', NULL),
(3, 2, '6kg', 23, 9, '2025-03-14 05:15:46', 'completed', NULL),
(4, 1, '6kg', 1, 22, '2025-03-14 05:31:41', 'completed', NULL),
(5, 2, '6kg', 2, 22, '2025-03-14 05:39:16', 'completed', NULL),
(6, 2, '6kg', 20, 9, '2025-03-14 05:59:03', 'completed', NULL),
(7, 3, '6kg', 3, 24, '2025-03-14 06:24:49', 'completed', NULL),
(8, 1, '6kg', 2, 24, '2025-03-14 06:25:32', 'completed', NULL),
(9, 3, '6kg', 20, 24, '2025-03-14 08:18:34', 'completed', NULL),
(10, 1, '12kg', 10, 24, '2025-03-18 09:16:14', 'completed', NULL),
(11, 1, '6kg', 20, 24, '2025-03-25 11:04:47', 'completed', NULL),
(12, 3, '6kg', 2, 24, '2025-04-03 12:39:35', 'completed', NULL),
(13, 3, 'Total Gas 6kg', 1, 24, '2025-04-03 13:30:36', 'completed', NULL),
(14, 2, 'Shell Afrigas 12kg', 11, 24, '2025-04-03 13:31:35', 'completed', NULL),
(15, 3, 'Total Gas 6kg', 60, 24, '2025-04-07 10:57:15', 'completed', NULL),
(16, 1, 'K-gas 6kg', 1, 24, '2025-04-07 11:09:34', 'completed', NULL),
(17, 3, 'Total Gas 6kg', 1, 24, '2025-04-07 11:35:58', 'completed', NULL),
(26, 3, 'Total Gas 6kg', 1, 24, '2025-04-08 06:51:35', 'completed', NULL),
(27, 3, 'Total Gas 6kg', 1, 24, '2025-04-08 06:52:07', 'completed', NULL),
(28, 3, 'Total Gas 12kg', 1, 24, '2025-04-08 07:00:14', 'completed', NULL),
(29, 2, 'Shell Afrigas 12kg', 1, 24, '2025-04-08 07:00:51', 'completed', NULL),
(30, 3, 'Total Gas 6kg', 1, 29, '2025-04-08 07:19:05', 'completed', NULL),
(31, 2, 'Shell Afrigas 12kg', 1, 24, '2025-04-08 07:46:17', 'completed', NULL),
(32, 2, 'Shell Afrigas 12kg', 1, 24, '2025-04-08 07:48:10', 'completed', NULL),
(33, 2, 'Shell Afrigas 12kg', 1, 24, '2025-04-08 07:54:07', 'completed', NULL),
(34, 2, 'Shell Afrigas 6kg', 1, 24, '2025-04-08 07:54:14', 'completed', NULL),
(35, 2, 'Shell Afrigas 6kg', 1, 24, '2025-04-08 07:55:27', 'completed', NULL),
(36, 2, 'Shell Afrigas 6kg', 1, 24, '2025-04-08 07:55:45', 'completed', NULL),
(37, 2, 'Shell Afrigas 6kg', 1, 24, '2025-04-08 08:02:02', 'completed', 900.00),
(38, 2, 'Shell Afrigas 6kg', 1, 24, '2025-04-08 08:05:45', 'completed', 900.00),
(39, 2, 'Shell Afrigas 6kg', 1, 24, '2025-04-08 08:06:40', 'completed', 900.00),
(40, 2, 'Shell Afrigas 6kg', 1, 24, '2025-04-08 08:08:24', 'completed', 900.00),
(41, 2, 'Shell Afrigas 6kg', 1, 24, '2025-04-08 08:08:33', 'completed', 900.00),
(42, 2, 'Shell Afrigas 6kg', 1, 24, '2025-04-08 08:08:55', 'completed', 900.00),
(43, 3, 'Total Gas 12kg', 1, 24, '2025-04-08 08:09:24', 'completed', 1500.00),
(44, 3, 'Total Gas 6kg', 1, 24, '2025-04-08 08:30:19', 'completed', 600.00),
(45, 3, 'Total Gas 6kg', 1, 24, '2025-04-08 08:35:09', 'completed', 600.00),
(46, 3, 'Total Gas 12kg', 1, 24, '2025-04-08 08:37:07', 'completed', 1500.00),
(47, 3, 'Total Gas 6kg', 1, 24, '2025-04-08 08:39:46', 'completed', 600.00),
(48, 3, 'Total Gas 6kg', 1, 24, '2025-04-08 08:40:55', 'completed', 600.00),
(49, 3, 'Total Gas 6kg', 1, 24, '2025-04-08 08:41:47', 'completed', 600.00),
(50, 3, 'Total Gas 6kg', 1, 24, '2025-04-08 08:44:49', 'completed', 600.00),
(51, 3, 'Total Gas 6kg', 1, 24, '2025-04-08 08:45:23', 'completed', 600.00),
(52, 3, 'Total Gas 12kg', 21, 24, '2025-04-15 12:38:14', 'completed', 31500.00),
(53, 1, 'K-gas 12kg', 1, 24, '2025-04-16 06:13:27', 'completed', 1000.00),
(54, 1, 'K-gas 6kg', 5, 24, '2025-04-16 06:14:01', 'completed', 3750.00),
(55, 1, 'K-gas 12kg', 5, 24, '2025-04-16 06:14:27', 'completed', 5000.00),
(56, 1, 'K-gas 12kg', 1, 24, '2025-04-16 13:03:12', 'completed', 1000.00),
(57, 1, 'K-gas 12kg', 1, 24, '2025-04-16 13:04:04', 'completed', 1000.00),
(58, 1, 'K-gas 12kg', 1, 24, '2025-04-16 13:20:20', 'completed', 1000.00),
(59, 1, 'K-gas 12kg', 10, 24, '2025-04-17 12:40:42', 'completed', 10000.00),
(60, 2, 'Shell Afrigas 12kg', 1, 24, '2025-04-17 12:43:54', 'completed', 2400.00),
(61, 3, 'Total Gas 12kg', 1, 24, '2025-04-17 15:52:13', 'completed', 1500.00),
(63, 3, 'Total Gas 6kg', 1, 24, '2025-04-23 12:06:07', 'completed', 600.00),
(64, 10, 'ProGas 12kg', 10, 24, '2025-04-23 13:43:54', 'completed', 20000.00),
(65, 10, 'ProGas 12kg', 1, 24, '2025-04-30 18:11:17', 'completed', 2000.00),
(66, 2, 'Shell Afrigas 12kg', 1, 24, '2025-05-08 08:57:54', 'completed', 1200.00),
(67, 1, 'K-gas 12kg', 100, 24, '2025-05-08 08:59:35', 'completed', 100000.00),
(68, 3, 'Total Gas 12kg', 10, 24, '2025-05-09 07:06:20', 'completed', 15000.00),
(69, 1, 'K-gas 6kg', 1, 24, '2025-05-09 07:15:33', 'completed', 750.00),
(70, 10, 'ProGas 12kg', 1, 24, '2025-05-09 07:15:50', 'completed', 2000.00),
(71, 16, 'Luqman Gas 6kg', 1, 24, '2025-05-14 07:17:08', 'completed', 1000.00),
(72, 16, 'Luqman Gas 12kg', 10, 24, '2025-05-14 07:18:18', 'completed', 20000.00),
(73, 1, 'K-Gas 12kg', 1, 24, '2025-05-14 07:57:46', 'completed', 1000.00),
(74, 1, 'K-Gas 12kg', 1, 24, '2025-05-14 07:58:16', 'completed', 1000.00),
(75, 1, 'K-Gas 12kg', 1, 24, '2025-05-19 08:28:32', 'completed', 1000.00),
(76, 11, 'Hashi Gas 12kg', 10, 24, '2025-05-19 08:43:21', 'completed', 23000.00),
(77, 11, 'Hashi Gas 6kg', 1, 24, '2025-05-19 08:46:43', 'completed', 900.00),
(78, 11, 'Hashi Gas 6kg', 1, 24, '2025-05-20 07:07:31', 'completed', 900.00),
(79, 11, 'Hashi Gas 6kg', 1, 24, '2025-05-20 07:08:05', 'completed', 900.00),
(80, 11, 'Hashi Gas 6kg', 1, 24, '2025-05-20 07:10:46', 'completed', 900.00),
(81, 11, 'Hashi Gas 6kg', 1, 24, '2025-05-20 07:12:39', 'completed', 900.00),
(82, 1, 'K-Gas 6kg', 1, 24, '2025-05-20 07:23:46', 'completed', 750.00),
(83, 1, 'K-Gas 12kg', 10, 24, '2025-05-23 13:33:02', 'completed', 10000.00);

-- --------------------------------------------------------

--
-- Table structure for table `sales_record`
--

CREATE TABLE `sales_record` (
  `sale_id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `sale_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_method` varchar(50) NOT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `order_id` varchar(255) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `line_total` decimal(10,2) DEFAULT NULL,
  `cust_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales_record`
--

INSERT INTO `sales_record` (`sale_id`, `order_number`, `customer_name`, `quantity`, `sale_date`, `payment_method`, `product_name`, `order_id`, `total_amount`, `product_id`, `line_total`, `cust_id`) VALUES
(12, 'ORD1744630851', 'john', 1, '2025-04-14 11:40:51', 'M-PESA', 'Total Gas 6kg', NULL, NULL, 6, NULL, NULL),
(13, 'ORD1744631616', 'n', 1, '2025-04-14 11:53:36', 'M-PESA', 'Total Gas 12kg', NULL, NULL, 3, NULL, NULL),
(14, 'ORD1744631744', 'n', 1, '2025-04-14 11:55:44', 'M-PESA', 'Total Gas 12kg', NULL, NULL, 3, NULL, NULL),
(19, '42310900', ' ', 1, '2025-04-11 08:20:01', 'MPESA', 'Shell Afrigas 12kg', '14', 2300.00, 1, NULL, NULL),
(20, '63140180', ' ', 1, '2025-04-10 11:45:47', 'MPESA', 'K-Gas 12kg', '10', 2300.00, 2, NULL, NULL),
(21, '12357900', ' ', 3, '2025-04-14 16:08:59', 'MPESA', 'K-Gas 12kg', '19', 6900.00, 2, NULL, NULL),
(22, 'ORD1744651502', 'ga', 1, '2025-04-14 17:25:02', 'M-PESA', 'Shell Afrigas 12kg', NULL, NULL, 1, NULL, NULL),
(23, '83830725', 'GGGGGGGGGGGGGGGGGGGGGGGGGG', 6, '2025-04-14 16:31:55', 'MPESA', 'Total Gas 12kg', '20', 13800.00, 3, NULL, NULL),
(24, 'ORD1744651983', 'n', 1, '2025-04-14 17:33:03', 'M-PESA', 'K-Gas 12kg', NULL, NULL, 2, NULL, NULL),
(25, 'ORD1744652678', 'hehe', 20, '2025-04-14 17:44:38', 'M-PESA', 'Shell Afrigas 12kg', NULL, NULL, 1, NULL, NULL),
(28, 'ORD1744662599', 'd', 30, '2025-04-14 20:29:59', 'M-PESA', 'Shell Afrigas 6kg', NULL, NULL, 4, NULL, NULL),
(29, 'ORD1744663143', 'celest', 1, '2025-04-14 20:39:03', 'M-PESA', 'K-Gas 12kg', NULL, 2300.00, 2, NULL, NULL),
(30, 'ORD1744663567', 'Monkey D Luffy', 1, '2025-04-14 20:46:07', 'M-PESA', 'Shell Afrigas 6kg', NULL, 1200.00, 4, NULL, NULL),
(31, 'ORD1744663630', '1', 1, '2025-04-14 20:47:10', 'M-PESA', 'Shell Afrigas 12kg', NULL, NULL, 1, NULL, NULL),
(32, 'ORD1744663696', 'why', 1, '2025-04-14 20:48:16', 'M-PESA', 'K-Gas 12kg', NULL, NULL, 2, NULL, NULL),
(33, 'ORD1744663802', 'w', 1, '2025-04-14 20:50:02', 'M-PESA', 'Shell Afrigas 12kg', NULL, NULL, 1, NULL, NULL),
(34, 'ORD1744663882', 'k', 1, '2025-04-14 20:51:22', 'M-PESA', 'Shell Afrigas 12kg', NULL, NULL, 1, NULL, NULL),
(35, 'ORD1744663934', 'wwww', 11, '2025-04-14 20:52:14', 'M-PESA', 'Shell Afrigas 12kg', NULL, NULL, 1, NULL, NULL),
(36, 'ORD1744664227', 'v', 1, '2025-04-14 20:57:07', 'M-PESA', 'Total Gas 12kg', NULL, NULL, 3, NULL, NULL),
(37, 'ORD1744664359', 'Peter Parker', 1, '2025-04-14 20:59:19', 'M-PESA', 'K-Gas 12kg', NULL, 2300.00, 2, NULL, NULL),
(41, '36718046', 'bla bla', 1, '2025-04-15 05:22:17', 'MPESA', 'Shell Afrigas 12kg', '23', 2300.00, 1, NULL, 1),
(42, '61356645', 'bla bla', 2, '2025-04-15 05:22:59', 'MPESA', 'Shell Afrigas 12kg', '24', 4600.00, 1, NULL, 1),
(44, '64894482', 'bla bla', 1, '2025-04-15 05:28:00', 'MPESA', 'Shell Afrigas 12kg', '26', 4600.00, 1, NULL, 1),
(45, '64894482', 'bla bla', 1, '2025-04-15 05:28:00', 'MPESA', 'K-Gas 12kg', '26', 4600.00, 2, NULL, 1),
(46, '84226078', 'bla bla', 20, '2025-04-15 05:39:07', 'MPESA', 'Total Gas 6kg', '27', 26300.00, 6, NULL, 1),
(47, '84226078', 'bla bla', 1, '2025-04-15 05:39:07', 'MPESA', 'Shell Afrigas 12kg', '27', 26300.00, 1, NULL, 1),
(48, 'ORD1744699484', 'booo', 1, '2025-04-15 06:44:44', 'M-PESA', 'Shell Afrigas 12kg', NULL, 3500.00, 1, NULL, NULL),
(49, 'ORD1744699484', 'booo', 1, '2025-04-15 06:44:44', 'M-PESA', 'Shell Afrigas 6kg', NULL, 3500.00, 4, NULL, NULL),
(50, 'ORD1744699608', 'Cilian Murphy', 2, '2025-04-15 06:46:48', 'M-PESA', 'Shell Afrigas 12kg', NULL, 7000.00, 1, NULL, NULL),
(51, 'ORD1744699608', 'Cilian Murphy', 2, '2025-04-15 06:46:48', 'M-PESA', 'Total Gas 6kg', NULL, 7000.00, 6, NULL, NULL),
(52, '77935329', 'bla bla', 5, '2025-04-15 05:51:46', 'MPESA', 'Shell Afrigas 12kg', '28', 15100.00, 1, NULL, 1),
(53, '77935329', 'bla bla', 3, '2025-04-15 05:51:46', 'MPESA', 'K-Gas 6kg', '28', 15100.00, 5, NULL, 1),
(54, 'ORD1744700037', 'celest', 7, '2025-04-15 06:53:57', 'CASH', 'K-Gas 12kg', NULL, 25700.00, 2, NULL, NULL),
(55, 'ORD1744700037', 'celest', 8, '2025-04-15 06:53:57', 'CASH', 'Shell Afrigas 6kg', NULL, 25700.00, 4, NULL, NULL),
(56, '98572104', 'bla bla', 1, '2025-04-15 08:09:51', 'MPESA', 'K-Gas 12kg', '29', 4600.00, 2, NULL, 1),
(57, '98572104', 'bla bla', 1, '2025-04-15 08:09:51', 'MPESA', 'Shell Afrigas 12kg', '29', 4600.00, 1, NULL, 1),
(58, '55241848', 'bla bla', 1, '2025-04-15 09:58:48', 'MPESA', 'Shell Afrigas 12kg', '30', 4600.00, 1, NULL, 1),
(59, '55241848', 'bla bla', 1, '2025-04-15 09:58:48', 'MPESA', 'K-Gas 12kg', '30', 4600.00, 2, NULL, 1),
(60, '48384221', 'bla bla', 1, '2025-04-15 10:32:24', 'MPESA', 'Shell Afrigas 12kg', '31', 4600.00, 1, NULL, 1),
(61, '48384221', 'bla bla', 1, '2025-04-15 10:32:24', 'MPESA', 'K-Gas 12kg', '31', 4600.00, 2, NULL, 1),
(62, '11708544', 'bla bla', 1, '2025-04-15 11:14:09', 'MPESA', 'Shell Afrigas 12kg', '32', NULL, 1, 0.00, 1),
(63, '11708544', 'bla bla', 1, '2025-04-15 11:14:09', 'MPESA', 'K-Gas 12kg', '32', NULL, 2, 0.00, 1),
(66, '10696296', 'bla bla', 1, '2025-04-15 11:26:03', 'MPESA', 'Total Gas 12kg', '34', 2300.00, 3, NULL, 1),
(67, '10696296', 'bla bla', 1, '2025-04-15 11:26:03', 'MPESA', 'K-Gas 12kg', '34', 2300.00, 2, NULL, 1),
(68, '36160333', 'bla bla', 2, '2025-04-15 11:29:49', 'MPESA', 'Shell Afrigas 12kg', '35', 4600.00, 1, NULL, 1),
(69, '36160333', 'bla bla', 1, '2025-04-15 11:29:49', 'MPESA', 'K-Gas 12kg', '35', 2300.00, 2, NULL, 1),
(70, '69472949', 'bla bla', 14, '2025-04-15 11:35:05', 'MPESA', 'Total Gas 12kg', '36', 32200.00, 3, NULL, 1),
(71, 'ORD1744721531', 'ggh', 1, '2025-04-15 12:52:11', 'M-PESA', 'Shell Afrigas 12kg', NULL, 2300.00, 1, NULL, NULL),
(72, 'ORD1744721582', 'oooooooo', 1, '2025-04-15 12:53:02', 'M-PESA', 'Shell Afrigas 12kg', NULL, 3500.00, 1, NULL, NULL),
(73, 'ORD1744721582', 'oooooooo', 1, '2025-04-15 12:53:02', 'M-PESA', 'Total Gas 6kg', NULL, 3500.00, 6, NULL, NULL),
(74, 'ORD1744723548', 'ddds', 1, '2025-04-15 13:25:48', 'M-PESA', 'K-Gas 6kg', NULL, 1200.00, 5, NULL, NULL),
(75, 'ORD1744723548', 'ddds', 2, '2025-04-15 13:25:48', 'M-PESA', 'Shell Afrigas 12kg', NULL, 4600.00, 1, NULL, NULL),
(76, 'ORD1744745781', 'n', 1, '2025-04-15 19:36:21', 'M-PESA', 'K-Gas 6kg', NULL, 1200.00, 5, NULL, NULL),
(77, '62097430', 'bla bla', 6, '2025-04-15 19:14:05', 'MPESA', 'Shell Afrigas 12kg', '37', 13800.00, 1, NULL, 1),
(78, '54876515', 'bla bla', 14, '2025-04-16 05:08:34', 'MPESA', 'K-Gas 12kg', '38', 32200.00, 2, NULL, 1),
(79, '66696981', 'bla bla', 1, '2025-04-16 05:09:06', 'MPESA', 'K-Gas 12kg', '15', 2300.00, 2, NULL, 1),
(80, '61501528', 'bla bla', 3, '2025-04-16 05:20:28', 'MPESA', 'K-Gas 12kg', '39', 6900.00, 2, NULL, 1),
(81, 'ORD1744892075', 'Matt Murdock', 2, '2025-04-17 12:14:35', 'M-PESA', 'Total Gas 6kg', NULL, 2400.00, 6, NULL, NULL),
(82, 'ORD1744892363', 'Kingpin', 1, '2025-04-17 12:19:23', 'M-PESA', 'Total Gas 12kg', NULL, 2300.00, 3, NULL, NULL),
(83, 'ORD1744892363', 'Kingpin', 1, '2025-04-17 12:19:24', 'M-PESA', 'Shell Afrigas 6kg', NULL, 1200.00, 4, NULL, NULL),
(84, '65981575', 'bla bla', 1, '2025-04-22 11:31:04', 'MPESA', 'Shell Afrigas 12kg', '41', 2000.00, 1, NULL, 1),
(85, '44179602', 'bla bla', 5, '2025-04-23 07:25:31', 'MPESA', 'K-Gas 12kg', '40', 11500.00, 2, NULL, 1),
(86, '62135045', 'bla bla', 1, '2025-04-23 07:48:48', 'MPESA', 'Total Gas 6kg', '42', 1200.00, 6, NULL, 1),
(87, '90738423', 'bla bla', 1, '2025-04-23 07:58:52', 'MPESA', 'K-Gas 6kg', '43', 1200.00, 5, NULL, 1),
(88, '96717496', 'bla bla', 2, '2025-04-23 08:00:08', 'MPESA', 'Total Gas 6kg', '44', 2400.00, 6, NULL, 1),
(89, '96717496', 'bla bla', 2, '2025-04-23 08:00:08', 'MPESA', 'Total Gas 12kg', '44', 4600.00, 3, NULL, 1),
(90, 'ORD1745408882', 'celest', 2, '2025-04-23 11:48:02', 'M-PESA', 'K-Gas 6kg', NULL, 2400.00, 5, NULL, NULL),
(91, '97901347', 'ATHMAN ALI', 10, '2025-04-23 13:40:59', 'MPESA', 'ProGas 12kg', '45', 23000.00, 11, NULL, NULL),
(92, '97901347', 'ATHMAN ALI', 3, '2025-04-23 13:40:59', 'MPESA', 'Total Gas 12kg', '45', 6900.00, 3, NULL, NULL),
(93, '64806703', 'james jones', 1, '2025-04-26 09:10:36', 'MPESA', 'Total Gas 6kg', '52', 1200.00, 6, NULL, 2),
(94, '92409410', 'bla bla', 1, '2025-04-27 10:55:15', 'MPESA', 'K-Gas 12kg', '50', 2300.00, 2, NULL, 1),
(95, 'ORD1745995491', 'a', 1, '2025-04-30 06:44:51', 'M-PESA', 'K-Gas 12kg', NULL, 2300.00, 2, NULL, NULL),
(96, '44944248', 'bla bla', 2, '2025-04-30 10:05:03', 'MPESA', 'K-Gas 12kg', '49', 4600.00, 2, NULL, 1),
(97, '12747742', 'bla bla', 1, '2025-04-30 10:08:55', 'MPESA', 'Total Gas 6kg', '51', 1200.00, 6, NULL, 1),
(98, '55801468', 'bla bla', 1, '2025-04-30 10:17:28', 'MPESA', 'Total Gas 12kg', '48', 2300.00, 3, NULL, 1),
(99, '81221218', 'bla bla', 1, '2025-04-30 10:19:07', 'MPESA', 'Total Gas 6kg', '47', 1200.00, 6, NULL, 1),
(100, 'ORD1746013350', 'Clark Kent', 10, '2025-04-30 11:42:30', 'CASH', 'Total Gas 12kg', NULL, 23000.00, 3, NULL, NULL),
(101, '90204765', 'bla bla', 2, '2025-04-30 10:47:18', 'MPESA', 'Total Gas 6kg', '46', 2400.00, 6, NULL, 1),
(102, '98709021', 'bla bla', 1, '2025-04-30 12:46:44', 'MPESA', 'Total Gas 12kg', '53', 2300.00, 3, NULL, 1),
(103, '65889939', 'bla bla', 1, '2025-04-30 13:02:37', 'MPESA', 'Total Gas 6kg', '54', 1200.00, 6, NULL, 1),
(104, '29498566', 'bla bla', 1, '2025-04-30 18:13:09', 'MPESA', 'ProGas 12kg', '55', 2300.00, 11, NULL, 1),
(105, '11766190', 'bla bla', 1, '2025-05-02 06:22:42', 'MPESA', 'Total Gas 6kg', '58', 1200.00, 6, NULL, 1),
(106, '76594366', 'bla bla', 1, '2025-05-02 06:34:45', 'MPESA', 'Total Gas 6kg', '62', 1200.00, 6, NULL, 1),
(107, '76594366', 'bla bla', 1, '2025-05-02 06:34:45', 'MPESA', 'Total Gas 12kg', '62', 2300.00, 3, NULL, 1),
(108, '90186266', 'bla bla', 1, '2025-05-02 07:20:57', 'MPESA', 'Total Gas 6kg', '63', 1200.00, 6, NULL, 1),
(109, '46840903', 'bla bla', 1, '2025-05-02 07:22:31', 'MPESA', 'Total Gas 6kg', '65', 1200.00, 6, NULL, 1),
(110, '23060669', 'bla bla', 1, '2025-05-02 07:51:48', 'MPESA', 'Total Gas 6kg', '66', 1200.00, 6, NULL, 1),
(111, '23060669', 'bla bla', 1, '2025-05-02 07:51:48', 'MPESA', 'Total Gas 12kg', '66', 2300.00, 3, NULL, 1),
(112, 'ORD1746173294', 'jjjjjjjjjjjjjjjjjjj', 2, '2025-05-02 08:08:14', 'M-PESA', 'K-Gas 12kg', NULL, 4600.00, 2, NULL, NULL),
(113, 'ORD1746173450', 'Walter White', 1, '2025-05-02 08:10:50', 'M-PESA', 'K-Gas 12kg', NULL, 2300.00, 2, NULL, NULL),
(114, 'ORD1746173450', 'Walter White', 1, '2025-05-02 08:10:50', 'M-PESA', 'Shell Afrigas 6kg', NULL, 1200.00, 4, NULL, NULL),
(115, 'ORD1746174025', 'Jacksepticeye', 1, '2025-05-02 08:20:25', 'M-PESA', 'K-Gas 6kg', NULL, 1200.00, 5, NULL, NULL),
(116, 'ORD1746174025', 'Jacksepticeye', 1, '2025-05-02 08:20:25', 'M-PESA', 'Total Gas 6kg', NULL, 1200.00, 6, NULL, NULL),
(117, 'ORD1746175625', 'Grinch', 1, '2025-05-02 08:47:05', 'M-PESA', 'Total Gas 6kg', NULL, 1200.00, 6, NULL, NULL),
(118, '56842099', 'bla bla', 1, '2025-05-02 08:48:12', 'MPESA', 'K-Gas 12kg', '67', 2300.00, 2, NULL, 1),
(119, '23083976', 'bla bla', 1, '2025-05-02 12:36:28', 'MPESA', 'Total Gas 6kg', '71', 1200.00, 6, NULL, 1),
(120, '23083976', 'bla bla', 1, '2025-05-02 12:36:28', 'MPESA', 'Total Gas 12kg', '71', 2300.00, 3, NULL, 1),
(121, '17916396', 'bla bla', 1, '2025-05-02 12:49:45', 'MPESA', 'K-Gas 12kg', '72', 2300.00, 2, NULL, 1),
(122, '71091118', 'ATHMAN ALI', 1, '2025-05-05 07:11:36', 'MPESA', 'Total Gas 6kg', '73', 1200.00, 6, NULL, 3),
(123, '17512090', 'bla bla', 1, '2025-05-05 08:17:31', 'MPESA', 'K-Gas 12kg', '60', 2300.00, 2, NULL, 1),
(124, '44785370', 'bla bla', 1, '2025-05-05 09:05:15', 'MPESA', 'Total Gas 6kg', '74', 1200.00, 6, NULL, 1),
(125, '34676000', 'bla bla', 1, '2025-05-08 07:44:22', 'MPESA', 'Shell Afrigas 12kg', '75', 2300.00, 1, NULL, 1),
(126, '34676000', 'bla bla', 1, '2025-05-08 07:45:44', 'MPESA', 'Shell Afrigas 12kg', '75', 2300.00, 1, NULL, 1),
(127, '34676000', 'bla bla', 1, '2025-05-08 07:46:03', 'MPESA', 'Shell Afrigas 12kg', '75', 2300.00, 1, NULL, 1),
(128, '52797129', 'bla bla', 1, '2025-05-08 08:09:00', 'MPESA', 'Shell Afrigas 6kg', '76', 1200.00, 4, NULL, 1),
(129, '98200691', 'bla bla', 1, '2025-05-08 08:09:05', 'MPESA', 'K-Gas 6kg', '77', 1200.00, 5, NULL, 1),
(130, '65539459', 'bla bla', 1, '2025-05-08 08:10:54', 'MPESA', 'Shell Afrigas 6kg', '78', 1200.00, 4, NULL, 1),
(131, '52974686', 'bla bla', 1, '2025-05-08 08:12:56', 'MPESA', 'K-Gas 6kg', '79', 1200.00, 5, NULL, 1),
(132, '35778733', 'bla bla', 1, '2025-05-08 08:14:18', 'MPESA', 'K-Gas 6kg', '80', 1200.00, 5, NULL, 1),
(133, '35778733', 'bla bla', 1, '2025-05-08 08:14:18', 'MPESA', 'Total Gas 6kg', '80', 1200.00, 6, NULL, 1),
(134, '35778733', 'bla bla', 1, '2025-05-08 08:14:18', 'MPESA', 'Shell Afrigas 6kg', '80', 1200.00, 4, NULL, 1),
(135, 'ORD1746693302', 'celest', 1, '2025-05-08 08:35:02', 'M-PESA', 'Total Gas 6kg', NULL, 1200.00, 6, NULL, NULL),
(136, 'ORD1746693302', 'celest', 1, '2025-05-08 08:35:02', 'M-PESA', 'K-Gas 6kg', NULL, 1200.00, 5, NULL, NULL),
(137, '21793643', 'bla bla', 1, '2025-05-08 09:02:34', 'MPESA', 'K-Gas 12kg', '81', 2300.00, 2, NULL, 1),
(138, '21793643', 'bla bla', 1, '2025-05-08 09:02:34', 'MPESA', 'K-Gas 12kg', '81', 2300.00, 2, NULL, 1),
(139, '21793643', 'bla bla', 1, '2025-05-08 09:02:34', 'MPESA', 'K-Gas 12kg', '81', 2300.00, 2, NULL, 1),
(140, '21793643', 'bla bla', 1, '2025-05-08 09:02:34', 'MPESA', 'K-Gas 12kg', '81', 2300.00, 2, NULL, 1),
(141, '21793643', 'bla bla', 1, '2025-05-08 09:02:34', 'MPESA', 'K-Gas 12kg', '81', 2300.00, 2, NULL, 1),
(142, 'ORD1746695006', 'Clark Kent', 10, '2025-05-08 09:03:26', 'M-PESA', 'K-Gas 12kg', NULL, 23000.00, 2, NULL, NULL),
(143, '21154009', 'bla bla', 1, '2025-05-14 12:44:01', 'MPESA', 'Luqman Gas 6kg', '83', 1200.00, 20, NULL, 1),
(144, '71212829', 'bla bla', 1, '2025-05-15 08:09:57', 'MPESA', 'K-Gas 12kg', '84', 2300.00, 2, NULL, 1),
(145, 'ORD1747903805', 'Clark Kent', 1, '2025-05-22 08:50:05', 'M-PESA', 'Shell Afrigas 12kg', NULL, 2300.00, 1, 2300.00, NULL),
(146, 'ORD1747903864', 'Tanjiro Kamado', 1, '2025-05-22 08:51:04', 'M-PESA', 'K-Gas 6kg', NULL, 1100.00, 5, 1100.00, NULL),
(147, 'ORD1747903864', 'Tanjiro Kamado', 1, '2025-05-22 08:51:04', 'M-PESA', 'Hashi Gas 6kg', NULL, 1200.00, 13, 1200.00, NULL),
(148, '49300023', 'bla bla', 1, '2025-05-23 12:27:12', 'MPESA', 'ProGas 12kg', '82', 2300.00, 12, NULL, 1),
(149, '52142541', 'bla bla', 1, '2025-05-23 12:27:46', 'MPESA', 'Total Gas 12kg', '85', 2400.00, 3, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `address` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `details` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `address`, `phone`, `email`, `created_at`, `details`) VALUES
(1, 'K-gas', 'Industrial -area, Isiolo, DRC', '123-456-7890', 'info@gaspro.com', '2025-03-14 04:32:23', 'Reliable supplier of high-quality gas cylinders.'),
(2, 'Shell Afrigas', '456 Wambugu Rd, Parklands, Kenya', '234-567-8901', 'contact@blueflame.com', '2025-03-14 04:32:23', 'Leading distributor with competitive pricing.'),
(3, 'Total Gas', '789 Moi-Avenue Ave, Kilifi, Kenya', '345-678-9012', 'sales@ecofuel.com', '2025-03-14 04:32:23', 'Eco-friendly and sustainable gas solutions.'),
(10, 'ProGas', 'somewhere', '07333333333', 'progas@gmail.com', '2025-04-23 04:49:41', 'Epic Gas'),
(11, 'Hashi Gas', 'Krypton', '070707070707', 'hashigas@info.com', '2025-05-13 16:40:51', 'Epic '),
(16, 'Luqman Gas', 'Gotham', '07111111111', 'luqmangas@sales.com', '2025-05-13 17:04:13', 'Amazing');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `role` enum('support','admin','procurement','sales') NOT NULL DEFAULT 'support',
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `created_at`, `reset_token`, `reset_expires`, `role`, `is_active`) VALUES
(3, 'Athman Ali', 'ngugijohnnieperminus@gmail.com', '$2y$10$Ly3kWj1N0TcjigJfN83kK..qERfKbvI4A6wDUlKblsQm9oiMlld1m', '2025-02-25 08:20:53', NULL, NULL, 'sales', 1),
(5, 'Mikaeel', 'kj@gmail.com', '$2y$10$L9KFtnRX6bsu3BSnH7l/k.j0fuR1o65l.CuCO3NMwj8dk57/LY.e2', '2025-02-25 08:41:28', NULL, NULL, 'sales', 1),
(6, 'Jane Doe', 'janedoe@gmail.com', '$2y$10$ZqleJxa9DvIFF8ovRabSYemjs64wjub04tGvLsgjUnC6hE4OdIzyy', '2025-02-25 11:22:37', NULL, NULL, 'support', 1),
(7, 'rihana', 'rihana@gmail.com', '$2y$10$6g923VNJ.6bMCBCUCMJUo.cGjLbLbz/q70OlLjoaq/OuqKZ25mi1S', '2025-02-25 12:01:23', '3f93e997dfea16ef47d93521f78e89c6', '2025-03-03 13:27:26', 'sales', 1),
(9, 'johnnie ngugi', 'johnwanjagi18@gmail.com', '$2y$10$jeEDtCWusDKWJcD7/QPPa.JW1IOLCXHAPZ/v.gmmEaYY8O9Jd17fS', '2025-02-26 05:06:47', '4db937889f0b2aa3d8f7ef0f045bb7d6', '2025-02-28 11:19:19', 'admin', 1),
(10, 'saeed Bama', 'saeed@gmail.com', '$2y$10$Ey5J71ffxb7SRp2baQQvwu3h9wa08c.utXogqmAItSqyQFcar0obe', '2025-02-26 09:14:13', NULL, NULL, 'procurement', 1),
(11, 'Boniface Kaniu', 'bonifacekaniu@gmail.com', '$2y$10$Fh04J3vBNR7hcNkB1XaBcup4cUhjb706I0ulz4IpFnmHZLe1Lpmze', '2025-02-26 20:09:27', NULL, NULL, '', 1),
(12, 'james', 'james@gmail.com', '$2y$10$uuIdMA9QHDXHfBcCfyBZZ.sG/haKDNexa1XSh3ECKPnnSABVemM36', '2025-02-28 09:22:01', NULL, NULL, '', 1),
(13, 'Mary', 'mary@gmail.com', '$2y$10$Xx6Guwlh1oGu/YhQwmAR.uDe7VoGz.gE1G2xixde9GlCXWRzHP2HG', '2025-02-28 10:14:50', 'fdd8c9668cc5d48652a727b0d36a86fa', '2025-02-28 12:18:01', '', 1),
(14, 'asman', 'asman@gmail.com', '$2y$10$cHdMbLGDK/TYMegFjlKMTukuxmRbcSovRv9D8j612SysoIKka0XC.', '2025-02-28 10:18:39', NULL, NULL, '', 1),
(15, 'maria', 'maria@gmail.com', '$2y$10$OX3Srxv2jpJPdsr5QIKM2e7cN5YC0Je3b4UkTJqDbMwrElwThitWe', '2025-02-28 11:14:27', NULL, NULL, 'admin', 1),
(16, 'moha', 'moha@gmail.com', '$2y$10$b9rgfF7kviwUeMmC8VvLeO3E.6ZSnEssf2sjydyb.Hxl/wEXEHFuC', '2025-02-28 12:09:41', NULL, NULL, 'sales', 1),
(17, 'Leon', 'leon@gmail.com', '$2y$10$f84YxOqCT8vmVgCyhYrA4e6cYpiMvPyWyjTk.1YYRnCPy2e1O/m.a', '2025-02-28 13:07:46', NULL, NULL, 'support', 1),
(19, 'Fatuma', 'fatuma@gmail.com', '$2y$10$gX8MpaJKzJgVnwj4EKrXzutFqN0AiyMZzswMbWMmFO3mJaFMc2Ycq', '2025-03-04 06:11:46', 'b895d0a6cfe658871814fbf9abea8733', '2025-03-04 08:12:13', '', 1),
(20, 'Messi', 'messi@gmail.com', '$2y$10$Tp/Ok1l7ZLvUEw3seVciW.MSJjLpH9reQi172ULaPr2jGunFwdtAW', '2025-03-04 09:07:11', NULL, NULL, 'sales', 1),
(21, 'samuel', 'samuel@gmail.com', '$2y$10$ODv66Atf1ejAZ585.gRRA.DL5RFDr7xU./8lJiT0RbrW2geLPDmzW', '2025-03-04 09:46:23', NULL, NULL, '', 1),
(22, 'Abdi', 'abdi@gmail.com', '$2y$10$x2TxYzCb3BZDTpQwMc0qdeeniSeXoKOp.ySI96bhai/nnfGtRRsju', '2025-03-04 11:22:09', NULL, NULL, 'procurement', 1),
(23, 'john', 'john@gmail.com', '$2y$10$INNpgpauBmPrBjImP9eHZulPUrUR0bDK6V5Ty1CcBHIxL5pFdQC1K', '2025-03-05 06:27:02', NULL, NULL, 'sales', 1),
(24, 'samantha', 'samantha@gmail.com', '$2y$10$Il8Tyh9flqgyNOvHPxoKD.TFC/m3fhK9csUhuj1TPXMtFn/TYzSyi', '2025-03-07 07:28:44', NULL, NULL, 'procurement', 1),
(25, 'celest', 'celest@gmail.com', '$2y$10$o/MZ3DDKueLy41FCEAdmh.WnqezmzruNQMeLpN2ltxf.m9TEkfupi', '2025-03-10 07:44:36', NULL, NULL, '', 1),
(27, 'liz', 'liz@gmail.com', '$2y$10$ekW5ZduXtocZSsGwfZj6IOlGCoiHn21EPWxnAoSvNvuu1l42/9Tvi', '2025-03-13 07:41:17', NULL, NULL, 'sales', 1),
(28, 'Jane Fraser', 'janefrazer@oceangas.info.com', '$2y$10$33BcLIKhGPr28AomMAazUufPnFP5UChq8Zo89vPteBNVOOqyAr.Xy', '2025-03-14 12:37:15', NULL, NULL, 'admin', 1),
(29, 'Sameer Munir', 'sameermunid606@gmail.com', '$2y$10$aDenVqYF8C3nbTDaW/OuZewnjEKK9b8jnqs8Usq7mo6EDoX7PMl0K', '2025-03-17 06:52:42', NULL, NULL, 'admin', 1),
(30, 'omar', 'omar@gmail.com', '$2y$10$1kMShzbH.MlULp7ODko30.3a0W.yU2biDTUR66xxbBcchRVbfu6r2', '2025-03-17 11:34:10', NULL, NULL, '', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`cust_id`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- Indexes for table `deliveries`
--
ALTER TABLE `deliveries`
  ADD PRIMARY KEY (`delivery_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `funds`
--
ALTER TABLE `funds`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inquiries_and_reviews`
--
ALTER TABLE `inquiries_and_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cust_id` (`cust_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `price`
--
ALTER TABLE `price`
  ADD PRIMARY KEY (`price_id`),
  ADD KEY `fk_product` (`product_id`),
  ADD KEY `fk_suppliers` (`supplier_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `purchase_history`
--
ALTER TABLE `purchase_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `purchased_by` (`purchased_by`);

--
-- Indexes for table `sales_record`
--
ALTER TABLE `sales_record`
  ADD PRIMARY KEY (`sale_id`),
  ADD KEY `cust_id` (`cust_id`),
  ADD KEY `fk_salesrecord_product` (`product_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `cust_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `deliveries`
--
ALTER TABLE `deliveries`
  MODIFY `delivery_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `funds`
--
ALTER TABLE `funds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT for table `inquiries_and_reviews`
--
ALTER TABLE `inquiries_and_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=247;

--
-- AUTO_INCREMENT for table `price`
--
ALTER TABLE `price`
  MODIFY `price_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `purchase_history`
--
ALTER TABLE `purchase_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `sales_record`
--
ALTER TABLE `sales_record`
  MODIFY `sale_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=150;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `inquiries_and_reviews`
--
ALTER TABLE `inquiries_and_reviews`
  ADD CONSTRAINT `inquiries_and_reviews_ibfk_1` FOREIGN KEY (`cust_id`) REFERENCES `customers` (`cust_id`);

--
-- Constraints for table `price`
--
ALTER TABLE `price`
  ADD CONSTRAINT `fk_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  ADD CONSTRAINT `fk_suppliers` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`);

--
-- Constraints for table `purchase_history`
--
ALTER TABLE `purchase_history`
  ADD CONSTRAINT `purchase_history_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  ADD CONSTRAINT `purchase_history_ibfk_2` FOREIGN KEY (`purchased_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `sales_record`
--
ALTER TABLE `sales_record`
  ADD CONSTRAINT `fk_salesrecord_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `sales_record_ibfk_1` FOREIGN KEY (`cust_id`) REFERENCES `customers` (`cust_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
