-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 20, 2025 at 12:32 PM
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
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `product` varchar(255) NOT NULL,
  `price` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `session_id`, `product`, `price`, `quantity`, `added_at`) VALUES
(1, '766l60phlds02891breno9oq20', '12kg', 2300, 1, '2025-03-17 08:52:12'),
(2, '766l60phlds02891breno9oq20', '12kg', 2300, 1, '2025-03-17 08:52:45'),
(3, '766l60phlds02891breno9oq20', '12kg', 2300, 1, '2025-03-17 08:52:48'),
(4, 'ocfhfeo4jat5hs6stl095l57a4', '12kg', 2300, 1, '2025-03-17 11:34:46'),
(5, 'ocfhfeo4jat5hs6stl095l57a4', '12kg', 2300, 1, '2025-03-17 11:34:50'),
(6, 'ocfhfeo4jat5hs6stl095l57a4', '6kg', 1200, 1, '2025-03-17 11:34:55'),
(7, 'ocfhfeo4jat5hs6stl095l57a4', '12kg', 2300, 1, '2025-03-17 11:41:50'),
(8, 'ocfhfeo4jat5hs6stl095l57a4', '6kg', 1200, 1, '2025-03-18 09:25:59'),
(9, 'ocfhfeo4jat5hs6stl095l57a4', '6kg', 1200, 1, '2025-03-18 09:26:02'),
(10, 'ocfhfeo4jat5hs6stl095l57a4', '12kg', 2300, 1, '2025-03-18 09:26:20'),
(11, 'ocfhfeo4jat5hs6stl095l57a4', '12kg', 2300, 1, '2025-03-20 07:20:12');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`cust_id`, `F_name`, `L_name`, `Email`, `Phone_number`, `pass`, `created_at`) VALUES
(1, 'bla', 'bla', 'bla@gmail.com', '024802840', '$2y$10$5KrAyDX7HZznGphQF4D4Je6luFWINZtRp61y64ONIdvIc3yeXC7lu', '2025-03-20 09:53:00');

-- --------------------------------------------------------

--
-- Table structure for table `funds_deductions`
--

CREATE TABLE `funds_deductions` (
  `id` int(11) NOT NULL,
  `purchase_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `deduction_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `note` varchar(255) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `funds_deductions`
--

INSERT INTO `funds_deductions` (`id`, `purchase_id`, `amount`, `deduction_date`, `note`) VALUES
(1, 4, 0.00, '2025-03-14 05:31:41', 'Deduction for purchasing 1 units of 6kg at KES 0.00 each.'),
(2, 5, 1800.00, '2025-03-14 05:39:17', 'Deduction for purchasing 2 units of 6kg at KES 900.00 each.'),
(3, 6, 18000.00, '2025-03-14 05:59:04', 'Deduction for purchasing 20 units of 6kg at KES 900.00 each.'),
(4, 7, 1800.00, '2025-03-14 06:24:49', 'Deduction for purchasing 3 units of 6kg at KES 600.00 each.'),
(5, 8, 1400.00, '2025-03-14 06:25:33', 'Deduction for purchasing 2 units of 6kg at KES 700.00 each.'),
(6, 9, 12000.00, '2025-03-14 08:18:34', 'Deduction for purchasing 20 units of 6kg at KES 600.00 each.'),
(7, 10, 10000.00, '2025-03-18 09:16:14', 'Deduction for purchasing 10 units of 12kg at KES 1,000.00 each.');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `phone_number` varchar(50) NOT NULL,
  `delivery_location` varchar(255) NOT NULL,
  `apartment_number` varchar(100) DEFAULT NULL,
  `cart_summary` text DEFAULT NULL,
  `total_amount` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `order_no` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `session_id`, `first_name`, `last_name`, `phone_number`, `delivery_location`, `apartment_number`, `cart_summary`, `total_amount`, `created_at`, `order_no`) VALUES
(1, '766l60phlds02891breno9oq20', 'Sameer', 'Munir', '0727590770', 'Nairobi', 'Pangani Palace House Number 1', '{\"items\":[{\"product\":\"12kg\",\"price\":2300},{\"product\":\"12kg\",\"price\":2300},{\"product\":\"12kg\",\"price\":2300}],\"total\":6900}', 6900, '2025-03-17 09:53:18', 90044374),
(2, 'ocfhfeo4jat5hs6stl095l57a4', 'Omar', 'Munir', '0727590770', 'Mombasa', 'Pangani Palace House Number 1', '{\"items\":[{\"product\":\"12kg\",\"price\":2300},{\"product\":\"12kg\",\"price\":2300},{\"product\":\"6kg\",\"price\":1200}],\"total\":5800}', 5800, '2025-03-17 11:35:25', 55259111),
(3, 'ocfhfeo4jat5hs6stl095l57a4', 'Sameer', 'Munir', '0727590770', 'Mombasa', 'home', '{\"items\":[{\"product\":\"12kg\",\"price\":2300}],\"total\":2300}', 2300, '2025-03-17 11:42:07', 91619391),
(4, 'ocfhfeo4jat5hs6stl095l57a4', 'Leon', 'W', '07123456789', 'Mombasa', '1', '{\"items\":[{\"product\":\"6kg\",\"price\":1200},{\"product\":\"6kg\",\"price\":1200},{\"product\":\"12kg\",\"price\":2300}],\"total\":4700}', 4700, '2025-03-18 09:29:31', 63008498),
(5, 'ocfhfeo4jat5hs6stl095l57a4', 'Leon', 'W', '07123456789', 'Mombasa', '1', '{\"items\":[{\"product\":\"6kg\",\"price\":1200},{\"product\":\"6kg\",\"price\":1200},{\"product\":\"12kg\",\"price\":2300}],\"total\":4700}', 4700, '2025-03-18 09:29:59', 77396638);

-- --------------------------------------------------------

--
-- Table structure for table `order_details`
--

CREATE TABLE `order_details` (
  `detail_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product` varchar(50) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `total` decimal(10,2) GENERATED ALWAYS AS (`quantity` * `price`) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `procurement_funds`
--

CREATE TABLE `procurement_funds` (
  `id` int(11) NOT NULL,
  `allocated_amount` decimal(10,2) NOT NULL,
  `used_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `allocated_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `note` varchar(255) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `procurement_funds`
--

INSERT INTO `procurement_funds` (`id`, `allocated_amount`, `used_amount`, `allocated_date`, `note`) VALUES
(1, 1000000.00, 0.00, '2025-03-14 05:15:16', 'Tight budget'),
(2, 1000.00, 0.00, '2025-03-14 05:43:46', 'Tight budget'),
(3, 200.00, 0.00, '2025-03-14 08:16:24', ''),
(4, 200000.00, 0.00, '2025-03-18 09:09:48', 'today');

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
  `status` varchar(50) DEFAULT 'completed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_history`
--

INSERT INTO `purchase_history` (`id`, `supplier_id`, `product`, `quantity`, `purchased_by`, `purchase_date`, `status`) VALUES
(1, 2, '6kg', 10, 22, '2025-03-14 04:42:24', 'completed'),
(2, 3, '12kg', 12, 24, '2025-03-14 04:52:05', 'completed'),
(3, 2, '6kg', 23, 9, '2025-03-14 05:15:46', 'completed'),
(4, 1, '6kg', 1, 22, '2025-03-14 05:31:41', 'completed'),
(5, 2, '6kg', 2, 22, '2025-03-14 05:39:16', 'completed'),
(6, 2, '6kg', 20, 9, '2025-03-14 05:59:03', 'completed'),
(7, 3, '6kg', 3, 24, '2025-03-14 06:24:49', 'completed'),
(8, 1, '6kg', 2, 24, '2025-03-14 06:25:32', 'completed'),
(9, 3, '6kg', 20, 24, '2025-03-14 08:18:34', 'completed'),
(10, 1, '12kg', 10, 24, '2025-03-18 09:16:14', 'completed');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `contact` varchar(100) DEFAULT NULL,
  `product` varchar(50) NOT NULL,
  `quantity` int(11) DEFAULT NULL,
  `status` varchar(50) NOT NULL,
  `assigned_to` varchar(255) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sale_date` datetime DEFAULT current_timestamp(),
  `order_no` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `customer_name`, `contact`, `product`, `quantity`, `status`, `assigned_to`, `total`, `sale_date`, `order_no`) VALUES
(1, 'Samantha', '+255-1234', '6kg', 10, 'Pending', NULL, 0.00, '2025-03-14 11:09:56', 0),
(2, 'Willy Smith', '+254-5678', '12kg', 35, 'Delivered', NULL, 0.00, '2025-03-14 11:09:56', 0),
(3, 'JEFF', '5566755', '12kg', 3, 'Pending', 'liz', 0.00, '2025-03-14 11:09:56', 0),
(4, 'hesbon', '+255-873773', '12kg', 10, 'Pending', 'liz', 0.00, '2025-03-14 11:09:56', 0),
(5, 'hesbon', '+255-873773', '6kg', 4, 'Pending', 'liz', 0.00, '2025-03-14 11:09:56', 0),
(6, 'sameer', '+254 95757575', '6kg', 20, 'Pending', 'Messi', 0.00, '2025-03-14 11:09:56', 0),
(7, 'john', '0723451234', '6kg', 20, 'Pending', 'Mikaeel', 0.00, '2025-03-17 12:07:47', 0),
(8, 'omar munir', '0727590770', '6kg', 1, 'Pending', 'Mikaeel', 0.00, '2025-03-17 14:40:22', 0),
(9, 'Leon', '07123456789', '6', 2, '0', '0', 200.00, '2025-03-18 12:36:22', 22914718),
(10, 'Leon', '07123456789', '12', 1, '0', '0', 150.00, '2025-03-18 12:36:22', 22914718);

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock`
--

CREATE TABLE `stock` (
  `id` int(11) NOT NULL,
  `product` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock`
--

INSERT INTO `stock` (`id`, `product`, `quantity`) VALUES
(1, '6kg', 181),
(2, '12kg', 104);

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
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `cost_6kg` decimal(55,2) NOT NULL DEFAULT 0.00,
  `cost_12kg` decimal(65,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `address`, `phone`, `email`, `details`, `created_at`, `cost_6kg`, `cost_12kg`) VALUES
(1, 'GasPro Solutions', 'Industrial -area, Isiolo, DRC', '123-456-7890', 'info@gaspro.com', 'Reliable supplier of high-quality gas cylinders.', '2025-03-14 04:32:23', 700.00, 1000.00),
(2, 'BlueFlame Distributors', '456 Wambugu Rd, Parklands, Kenya', '234-567-8901', 'contact@blueflame.com', 'Leading distributor with competitive pricing.', '2025-03-14 04:32:23', 900.00, 1100.00),
(3, 'EcoFuel Suppliers', '789 Moi-Avenue Ave, Kilifi, Kenya', '345-678-9012', 'sales@ecofuel.com', 'Eco-friendly and sustainable gas solutions.', '2025-03-14 04:32:23', 600.00, 1900.00);

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
  `role` enum('user','admin','procurement','sales','inventory') NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `created_at`, `reset_token`, `reset_expires`, `role`) VALUES
(3, 'Athman Ali', 'ngugijohnnieperminus@gmail.com', '$2y$10$Ly3kWj1N0TcjigJfN83kK..qERfKbvI4A6wDUlKblsQm9oiMlld1m', '2025-02-25 08:20:53', NULL, NULL, 'sales'),
(5, 'Mikaeel', 'kj@gmail.com', '$2y$10$L9KFtnRX6bsu3BSnH7l/k.j0fuR1o65l.CuCO3NMwj8dk57/LY.e2', '2025-02-25 08:41:28', NULL, NULL, 'sales'),
(6, 'Jane Doe', 'janedoe@gmail.com', '$2y$10$ZqleJxa9DvIFF8ovRabSYemjs64wjub04tGvLsgjUnC6hE4OdIzyy', '2025-02-25 11:22:37', NULL, NULL, 'user'),
(7, 'rihana', 'rihana@gmail.com', '$2y$10$6g923VNJ.6bMCBCUCMJUo.cGjLbLbz/q70OlLjoaq/OuqKZ25mi1S', '2025-02-25 12:01:23', '3f93e997dfea16ef47d93521f78e89c6', '2025-03-03 13:27:26', 'sales'),
(9, 'johnnie ngugi', 'johnwanjagi18@gmail.com', '$2y$10$jeEDtCWusDKWJcD7/QPPa.JW1IOLCXHAPZ/v.gmmEaYY8O9Jd17fS', '2025-02-26 05:06:47', '4db937889f0b2aa3d8f7ef0f045bb7d6', '2025-02-28 11:19:19', 'admin'),
(10, 'saeed Bama', 'saeed@gmail.com', '$2y$10$Ey5J71ffxb7SRp2baQQvwu3h9wa08c.utXogqmAItSqyQFcar0obe', '2025-02-26 09:14:13', NULL, NULL, 'procurement'),
(11, 'Boniface Kaniu', 'bonifacekaniu@gmail.com', '$2y$10$Fh04J3vBNR7hcNkB1XaBcup4cUhjb706I0ulz4IpFnmHZLe1Lpmze', '2025-02-26 20:09:27', NULL, NULL, 'user'),
(12, 'james', 'james@gmail.com', '$2y$10$uuIdMA9QHDXHfBcCfyBZZ.sG/haKDNexa1XSh3ECKPnnSABVemM36', '2025-02-28 09:22:01', NULL, NULL, 'user'),
(13, 'Mary', 'mary@gmail.com', '$2y$10$Xx6Guwlh1oGu/YhQwmAR.uDe7VoGz.gE1G2xixde9GlCXWRzHP2HG', '2025-02-28 10:14:50', 'fdd8c9668cc5d48652a727b0d36a86fa', '2025-02-28 12:18:01', 'user'),
(14, 'asman', 'asman@gmail.com', '$2y$10$cHdMbLGDK/TYMegFjlKMTukuxmRbcSovRv9D8j612SysoIKka0XC.', '2025-02-28 10:18:39', NULL, NULL, 'user'),
(15, 'maria', 'maria@gmail.com', '$2y$10$OX3Srxv2jpJPdsr5QIKM2e7cN5YC0Je3b4UkTJqDbMwrElwThitWe', '2025-02-28 11:14:27', NULL, NULL, 'admin'),
(16, 'moha', 'moha@gmail.com', '$2y$10$b9rgfF7kviwUeMmC8VvLeO3E.6ZSnEssf2sjydyb.Hxl/wEXEHFuC', '2025-02-28 12:09:41', NULL, NULL, 'sales'),
(17, 'Leon', 'leon@gmail.com', '$2y$10$f84YxOqCT8vmVgCyhYrA4e6cYpiMvPyWyjTk.1YYRnCPy2e1O/m.a', '2025-02-28 13:07:46', NULL, NULL, 'user'),
(19, 'Fatuma', 'fatuma@gmail.com', '$2y$10$gX8MpaJKzJgVnwj4EKrXzutFqN0AiyMZzswMbWMmFO3mJaFMc2Ycq', '2025-03-04 06:11:46', 'b895d0a6cfe658871814fbf9abea8733', '2025-03-04 08:12:13', 'user'),
(20, 'Messi', 'messi@gmail.com', '$2y$10$Tp/Ok1l7ZLvUEw3seVciW.MSJjLpH9reQi172ULaPr2jGunFwdtAW', '2025-03-04 09:07:11', NULL, NULL, 'sales'),
(21, 'samuel', 'samuel@gmail.com', '$2y$10$ODv66Atf1ejAZ585.gRRA.DL5RFDr7xU./8lJiT0RbrW2geLPDmzW', '2025-03-04 09:46:23', NULL, NULL, 'user'),
(22, 'Abdi', 'abdi@gmail.com', '$2y$10$x2TxYzCb3BZDTpQwMc0qdeeniSeXoKOp.ySI96bhai/nnfGtRRsju', '2025-03-04 11:22:09', NULL, NULL, 'procurement'),
(23, 'john', 'john@gmail.com', '$2y$10$INNpgpauBmPrBjImP9eHZulPUrUR0bDK6V5Ty1CcBHIxL5pFdQC1K', '2025-03-05 06:27:02', NULL, NULL, 'sales'),
(24, 'samantha', 'samantha@gmail.com', '$2y$10$N3HAWt.QSplzhQjaB7QQzeh07Bo.ZtZeGKf613n99lcsE9DntMeiS', '2025-03-07 07:28:44', NULL, NULL, 'procurement'),
(25, 'celest', 'celest@gmail.com', '$2y$10$o/MZ3DDKueLy41FCEAdmh.WnqezmzruNQMeLpN2ltxf.m9TEkfupi', '2025-03-10 07:44:36', NULL, NULL, 'user'),
(27, 'liz', 'liz@gmail.com', '$2y$10$ekW5ZduXtocZSsGwfZj6IOlGCoiHn21EPWxnAoSvNvuu1l42/9Tvi', '2025-03-13 07:41:17', NULL, NULL, 'sales'),
(28, 'Jane Fraser', 'janefrazer@oceangas.info.com', '$2y$10$33BcLIKhGPr28AomMAazUufPnFP5UChq8Zo89vPteBNVOOqyAr.Xy', '2025-03-14 12:37:15', NULL, NULL, 'admin'),
(29, 'Sameer Munir', 'sameermunid606@gmail.com', '$2y$10$aDenVqYF8C3nbTDaW/OuZewnjEKK9b8jnqs8Usq7mo6EDoX7PMl0K', '2025-03-17 06:52:42', NULL, NULL, 'admin'),
(30, 'omar', 'omar@gmail.com', '$2y$10$1kMShzbH.MlULp7ODko30.3a0W.yU2biDTUR66xxbBcchRVbfu6r2', '2025-03-17 11:34:10', NULL, NULL, 'user');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`cust_id`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- Indexes for table `funds_deductions`
--
ALTER TABLE `funds_deductions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_details`
--
ALTER TABLE `order_details`
  ADD PRIMARY KEY (`detail_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `procurement_funds`
--
ALTER TABLE `procurement_funds`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `purchase_history`
--
ALTER TABLE `purchase_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `purchased_by` (`purchased_by`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `stock`
--
ALTER TABLE `stock`
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `cust_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `funds_deductions`
--
ALTER TABLE `funds_deductions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `order_details`
--
ALTER TABLE `order_details`
  MODIFY `detail_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `procurement_funds`
--
ALTER TABLE `procurement_funds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `purchase_history`
--
ALTER TABLE `purchase_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock`
--
ALTER TABLE `stock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `order_details`
--
ALTER TABLE `order_details`
  ADD CONSTRAINT `order_details_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);

--
-- Constraints for table `purchase_history`
--
ALTER TABLE `purchase_history`
  ADD CONSTRAINT `purchase_history_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  ADD CONSTRAINT `purchase_history_ibfk_2` FOREIGN KEY (`purchased_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
