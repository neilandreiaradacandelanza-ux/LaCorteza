-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 19, 2026 at 09:08 PM
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
-- Database: `lacorteza`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `firstname` varchar(255) NOT NULL,
  `lastname` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `confirm_password` varchar(255) NOT NULL,
  `role` varchar(20) DEFAULT 'customer',
  `email_verified_at` datetime DEFAULT NULL,
  `verification_token` varchar(64) DEFAULT NULL,
  `verification_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`id`, `full_name`, `firstname`, `lastname`, `username`, `email`, `password`, `confirm_password`, `role`) VALUES
(3, NULL, 'pogi', 'pogi', 'pogi@gmail.con', 'pogi@gmail.con', '$2y$10$8Iv8/bsVMYX6yDTh8OMx/O9lcikCNUS2VG/jCbI30xLamUccrFHG2', '', 'customer'),
(4, NULL, 'peperd', 'echaves', 'peperd', 'peperd@gmail.com', '$2y$10$4qIyMrMF49m3aUdWW.JMBOb0vauWoa98WxfZ1TcFCcpR7W2sPhwTi', '', 'customer'),
(6, NULL, 'rktthrt', 'jweirjwe', 'apads', 'neiL@afdaskas.com', '$2y$10$EfTHbSe2zFIfPwT4tr6w5uGiFzxXi2..mBh9LLHbkZZxyhxTKMrQ.', '', 'customer'),
(8, NULL, 'Roel', 'Llanita', 'musashi', 'roeladolfo.llanita@my.smciligan.edu.ph', '$2y$10$4Och1xmF8NUDG4LAzrrUxevl23JXzxvNYV/YKtOxECoTSj7zLamSi', '', 'customer'),
(9, NULL, 'Neil Andrei', 'Candelanza', 'xinfuuu', 'neilandreiarada.candelanza@my.smciligan.edu.ph', '$2y$10$SKJOAHRhYWgFYED.F1OFy.oy6M0PHRcbaXghl9dqk2N27vZTppuO2', '', 'customer'),
(10, NULL, 'Jaja', 'Avocat', 'jaja', 'jaja@gmail.com', '$2y$10$0Pag0slwTXdMyUNY3FHKduiBu2XlQ.8riCvMoFrCV8BnD2bRZQsj2', '', 'customer'),
(11, 'System Administrator', 'System', 'Admin', 'admin123', 'admin@lacorteza.com', '$2y$10$qYOuceERSZkcSySm02KhXOOjrSC88Uokbx5XKNf.7o46aGEJk/IDy', '$2y$10$qYOuceERSZkcSySm02KhXOOjrSC88Uokbx5XKNf.7o46aGEJk/IDy', 'admin'),
(12, NULL, 'gwapo', 'neil', 'neil', 'neiL@gmail.com', '$2y$10$JYeXJUNGXMa9rg9GaUXrV.NS/Li0Bc3CpLvdgjIHr5bnhetFfitly', '', 'customer'),
(13, NULL, 'Jaira', 'Pang-an', 'jajaa', 'Jaira143@gmail.com', '$2y$10$S6qHBSL6.B5yoxInR28LaeSfGtMP27MMI5xvCKgufe4XW2BIYel/W', '', 'customer'),
(17, NULL, 'Edrick Miguel', 'Delacruz', 'migze', 'migzedelacruz@gmail.com', '$2y$10$OO9n5jPNywzP23QQRphw6OhprWx.06ZfcuxAhz0JwM4uDw0QdpFVK', '', 'customer'),
(18, NULL, 'Xinfuuu', 'Pogi', 'Xinfuuuuuu', 'xinfu@gmail.com', '$2y$10$k3I5X0WIUNHL6ZRDJos6j.5lvTM/iIMmZUF7dhjDDVHPqRrbbjI6K', '', 'customer');

-- Sample accounts count as already verified
UPDATE `accounts` SET `email_verified_at` = NOW();

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `account_id`, `full_name`, `firstname`, `lastname`, `username`, `email`, `password`, `created_at`) VALUES
(1, 11, 'System Administrator', 'System', 'Admin', 'admin', 'admin@lacorteza.com', '$2y$10$wN9a/C2MylV8uN1U0C4jye1AOnM6a0yR2bO4W0h.wZ5gBf5D1T2KG', '2026-10-15 17:04:17');

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `customer_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`customer_id`, `account_id`, `full_name`, `firstname`, `lastname`, `username`, `email`, `password`, `created_at`) VALUES
(1, 3, NULL, 'pogi', 'pogi', 'pogi@gmail.con', 'pogi@gmail.con', '$2y$10$8Iv8/bsVMYX6yDTh8OMx/O9lcikCNUS2VG/jCbI30xLamUccrFHG2', '2026-10-15 17:04:17'),
(2, 4, NULL, 'peperd', 'echaves', 'peperd', 'peperd@gmail.com', '$2y$10$4qIyMrMF49m3aUdWW.JMBOb0vauWoa98WxfZ1TcFCcpR7W2sPhwTi', '2026-10-15 17:04:17'),
(3, 6, NULL, 'rktthrt', 'jweirjwe', 'apads', 'neiL@afdaskas.com', '$2y$10$EfTHbSe2zFIfPwT4tr6w5uGiFzxXi2..mBh9LLHbkZZxyhxTKMrQ.', '2026-10-15 17:04:17'),
(4, 8, NULL, 'Roel', 'Llanita', 'musashi', 'roeladolfo.llanita@my.smciligan.edu.ph', '$2y$10$4Och1xmF8NUDG4LAzrrUxevl23JXzxvNYV/YKtOxECoTSj7zLamSi', '2026-10-15 17:04:17'),
(5, 9, NULL, 'Neil Andrei', 'Candelanza', 'xinfuuu', 'neilandreiarada.candelanza@my.smciligan.edu.ph', '$2y$10$SKJOAHRhYWgFYED.F1OFy.oy6M0PHRcbaXghl9dqk2N27vZTppuO2', '2026-10-15 17:04:17'),
(6, 10, NULL, 'Jaja', 'Avocat', 'jaja', 'jaja@gmail.com', '$2y$10$0Pag0slwTXdMyUNY3FHKduiBu2XlQ.8riCvMoFrCV8BnD2bRZQsj2', '2026-10-15 17:04:17'),
(7, 12, NULL, 'gwapo', 'neil', 'neil', 'neiL@gmail.com', '$2y$10$JYeXJUNGXMa9rg9GaUXrV.NS/Li0Bc3CpLvdgjIHr5bnhetFfitly', '2026-10-15 17:04:17');

-- --------------------------------------------------------

--
-- Table structure for table `packages`
--

CREATE TABLE `packages` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `badge` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `inclusions` text DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `packages`
--

INSERT INTO `packages` (`id`, `title`, `price`, `badge`, `description`, `inclusions`, `status`, `created_at`) VALUES
(1, 'Exclusive Resort Use', 25000.00, 'Most Popular', 'Complete private access for your large events and family gatherings.', 'Full access to Main Pool & Kiddie Pool|Exclusive use of Pavilion Hall|Barbecue & Grilling Area access|Free use of tables & chairs (up to 30 pax)|Free parking space inside venue', 'Active', '2026-10-15 14:28:36'),
(2, 'Pavilion Hall Only', 6000.00, '', 'Ideal for indoor receptions, meetings, and seminars without pool access.', 'Covered event space with stage|Tables & chairs set up for up to 50 guests|Basic sound system ready|X Shared Pool Acess', 'Active', '2026-10-15 14:28:36'),
(3, 'Poolside Gazebo / Cottage', 2500.00, '', 'Perfect for small group day trips and casual swim sessions.', 'Reserved shaded cottage space|Free use of 1 picnic table|Shared Pool Access', 'Active', '2026-10-15 14:28:36');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `expires_at`, `created_at`) VALUES
(4, 'neilandreiarada.candelanza@my.smciligan.edu.ph', '9c1f3f3c75f626fea4ebc152ea27a1864e18be82af70b42a461054e9f67aa61e', '2026-10-19 00:10:08', '2026-10-18 15:10:08'),
(5, 'neilandreiarada.candelanza@my.smciligan.edu.ph', 'd200c696d0d76263425e495bad4e0dd2ebdd416fb44160a6311b8d9c975fad96', '2026-10-19 00:14:33', '2026-10-18 15:14:33'),
(6, 'neilandreiarada.candelanza@my.smciligan.edu.ph', 'a34875b750be4cde433a0c62188ace76efd1fd92e78944e52672ec0b8a011310', '2026-10-19 00:15:51', '2026-10-18 15:15:51');

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `payment_id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `payment_type` varchar(50) DEFAULT 'PayPal',
  `payment_amount` decimal(10,2) NOT NULL,
  `paypal_order_id` varchar(100) DEFAULT NULL,
  `paypal_capture_id` varchar(100) DEFAULT NULL,
  `payment_status` varchar(50) DEFAULT 'Completed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`payment_id`, `reservation_id`, `payment_type`, `payment_amount`, `paypal_order_id`, `paypal_capture_id`, `payment_status`, `created_at`) VALUES
(2, 4, 'PayPal', 5000.00, '3UH05715VT332122D', '1RV83819JX842522P', 'Completed', '2026-10-17 18:36:12');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `reservation_date` date NOT NULL,
  `time_slot` varchar(100) NOT NULL,
  `package_type` varchar(100) NOT NULL,
  `guest_count` int(11) NOT NULL DEFAULT 1,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` varchar(50) DEFAULT 'Pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `user_id`, `phone_number`, `reservation_date`, `time_slot`, `package_type`, `guest_count`, `total_amount`, `status`, `created_at`, `updated_at`) VALUES
(3, 4, NULL, '2026-10-15', 'Day Shift (8:00 AM - 5:00 PM)', 'Exclusive Resort Use', 30, 10000.00, 'Confirmed', '2026-10-15 23:20:14', '2026-10-18 03:05:42'),
(4, 12, NULL, '2026-10-15', 'Night Shift (6:00 PM - 12:00 MN)', 'Exclusive Resort Use', 30, 10000.00, 'Confirmed', '2026-10-15 23:20:59', '2026-10-18 02:36:12');

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `id` int(11) NOT NULL,
  `blocked_date` date NOT NULL,
  `time_slot` varchar(100) NOT NULL DEFAULT 'All Shifts',
  `reason` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`id`, `blocked_date`, `time_slot`, `reason`, `created_at`) VALUES
(2, '2026-10-18', 'Night Shift (6:00 PM - 12:00 MN)', 'Pogi', '2026-10-17 19:08:22');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `account_id` (`account_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `account_id` (`account_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `packages`
--
ALTER TABLE `packages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `reservation_id` (`reservation_id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_user_id` (`user_id`),
  ADD KEY `idx_reservation_date` (`reservation_date`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_date_slot` (`blocked_date`,`time_slot`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `packages`
--
ALTER TABLE `packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `admin_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `customer`
--
ALTER TABLE `customer`
  ADD CONSTRAINT `customer_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `fk_reservations_accounts` FOREIGN KEY (`user_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
