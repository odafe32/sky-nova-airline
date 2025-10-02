-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 15, 2025 at 08:06 AM
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
-- Database: `airlines`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin','admin') DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `email_notifications` tinyint(1) DEFAULT 1,
  `booking_alerts` tinyint(1) DEFAULT 1,
  `dark_mode` tinyint(1) DEFAULT 0,
  `phone` varchar(20) DEFAULT NULL,
  `timezone` varchar(50) DEFAULT 'Africa/Lagos'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `full_name`, `email`, `password`, `role`, `created_at`, `email_notifications`, `booking_alerts`, `dark_mode`, `phone`, `timezone`) VALUES
(1, 'Prof. Sidi', 'profsidi@gmail.com', '$2y$10$zD9Ww6TeD0VUHtg2b6SWu.pqAMr7cFpwjkQwZQA0MwGjr7RxPNq9i', 'admin', '2025-08-26 12:25:31', 1, 1, 0, NULL, 'Africa/Lagos'),
(2, 'System Administrator', 'admin@sola.com', '$2y$10$7rLSvRVyTQORapkDNc2UNeUEhvmSzwWF9cvneuqUlbSD7NfbLdG.u', 'super_admin', '2025-08-26 13:11:55', 1, 1, 0, NULL, 'Africa/Lagos');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` bigint(20) UNSIGNED NOT NULL,
  `booking_ref` varchar(32) NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `flight_id` bigint(20) UNSIGNED NOT NULL,
  `passengers` int(11) NOT NULL CHECK (`passengers` > 0),
  `seats` int(11) NOT NULL DEFAULT 1,
  `class` enum('Economy','Business','First') NOT NULL DEFAULT 'Economy',
  `trip_type` enum('oneway','round') DEFAULT 'oneway',
  `passenger_name` varchar(255) NOT NULL,
  `passenger_email` varchar(255) NOT NULL,
  `passenger_phone` varchar(20) NOT NULL,
  `return_flight_id` int(11) DEFAULT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `status` enum('Pending','Confirmed','Cancelled','Completed') DEFAULT 'Pending',
  `payment_status` enum('Pending','Paid','Failed','Refunded') DEFAULT 'Pending',
  `payment_reference` varchar(100) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `paid_amount` decimal(10,2) DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL,
  `booking_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `paystack_reference` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `booking_ref`, `user_id`, `flight_id`, `passengers`, `seats`, `class`, `trip_type`, `passenger_name`, `passenger_email`, `passenger_phone`, `return_flight_id`, `total_amount`, `status`, `payment_status`, `payment_reference`, `payment_method`, `paid_amount`, `payment_date`, `booking_date`, `created_at`, `paystack_reference`, `updated_at`) VALUES
(3, 'SOL68B1FEEB91300', 13, 5, 1, 1, 'Economy', 'round', 'Nkosi Sidi', 'nkosi@gmail.com', '09033162442', NULL, 20.00, 'Confirmed', 'Paid', 'SOL68B1FEEB91300', 'Paystack', 20.00, '2025-08-29 20:26:53', '2025-08-29 19:26:35', '2025-08-29 19:26:35', 'SOL68B1FEEB91300', '2025-08-29 19:26:53'),
(5, 'SOL68B2D69E9FD03', 13, 2, 1, 1, 'Economy', 'round', 'Nkosi Sidi', 'nkosi@gmail.com', '09033162442', NULL, 20.00, 'Cancelled', 'Paid', 'SOL68B2D69E9FD03', 'Paystack', 20.00, '2025-08-30 11:48:38', '2025-08-30 10:46:54', '2025-08-30 10:46:54', 'SOL68B2D69E9FD03', '2025-09-01 12:28:39');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `cart_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `flight_id` int(11) NOT NULL,
  `passengers` int(11) DEFAULT 1,
  `class` enum('Economy','Business','First') NOT NULL DEFAULT 'Economy',
  `trip_type` enum('oneway','round') DEFAULT 'oneway',
  `return_flight_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expires_at` timestamp NOT NULL DEFAULT (current_timestamp() + interval 30 minute),
  `booking_reference` varchar(20) DEFAULT NULL,
  `status` enum('active','expired','booked') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `flights`
--

CREATE TABLE `flights` (
  `flight_id` bigint(20) UNSIGNED NOT NULL,
  `airline` varchar(100) NOT NULL,
  `flight_no` varchar(50) NOT NULL,
  `origin` varchar(100) NOT NULL,
  `destination` varchar(100) NOT NULL,
  `flight_date` date NOT NULL,
  `departure_time` time NOT NULL,
  `arrival_time` time NOT NULL,
  `aircraft` varchar(100) NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `seats_available` int(11) NOT NULL,
  `status` enum('Scheduled','Delayed','Cancelled','Completed','Boarding') DEFAULT 'Scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status_id` tinyint(4) NOT NULL DEFAULT 1,
  `economy_price` decimal(10,2) DEFAULT 0.00,
  `business_price` decimal(10,2) DEFAULT 0.00,
  `first_class_price` decimal(10,2) DEFAULT 0.00,
  `economy_seats` int(11) DEFAULT 0,
  `business_seats` int(11) DEFAULT 0,
  `first_class_seats` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `flights`
--

INSERT INTO `flights` (`flight_id`, `airline`, `flight_no`, `origin`, `destination`, `flight_date`, `departure_time`, `arrival_time`, `aircraft`, `price`, `seats_available`, `status`, `created_at`, `updated_at`, `status_id`, `economy_price`, `business_price`, `first_class_price`, `economy_seats`, `business_seats`, `first_class_seats`) VALUES
(2, 'Air France', 'SOLA001', 'LOS', 'KAD', '2025-09-02', '15:02:00', '20:35:00', 'B77-20', 300.00, 150, 'Cancelled', '2025-08-26 19:34:03', '2025-09-08 13:36:30', 1, 20.00, 30.00, 50.00, 105, 37, 7),
(5, 'Air Pis', 'SOLA004', 'ABJ', 'KEF', '2025-08-29', '18:16:00', '18:18:00', 'B7720-1', 0.00, 6, 'Scheduled', '2025-08-28 17:13:02', '2025-08-29 19:26:53', 1, 20.00, 20.00, 20.00, 1, 2, 2),
(7, 'Samaila', 'SOLA006', 'SAM', 'SAM', '2025-08-28', '18:21:00', '18:22:00', 'B7720-1', 0.00, 5, 'Scheduled', '2025-08-28 17:17:24', '2025-08-28 18:20:40', 1, 30.00, 20.00, 20.00, 1, 2, 2),
(8, 'Air France', 'SOLA007', 'NIG', 'LON', '2025-09-03', '22:53:00', '22:53:00', 'B7720', 0.00, 10, 'Scheduled', '2025-09-01 21:49:14', '2025-09-01 21:49:14', 1, 430.00, 500.00, 700.00, 5, 3, 2),
(10, 'Min', 'SOLA008', 'SAM', 'SID', '2025-10-08', '21:12:00', '21:23:00', 'SAM001', 0.00, 10, 'Scheduled', '2025-09-08 14:33:08', '2025-09-08 14:33:08', 1, 450.00, 1200.00, 2500.00, 5, 3, 2);

-- --------------------------------------------------------

--
-- Stand-in structure for view `flight_availability`
-- (See below for the actual view)
--
CREATE TABLE `flight_availability` (
`flight_id` bigint(20) unsigned
,`airline` varchar(100)
,`flight_no` varchar(50)
,`origin` varchar(100)
,`destination` varchar(100)
,`flight_date` date
,`departure_time` time
,`arrival_time` time
,`economy_seats` int(11)
,`business_seats` int(11)
,`first_class_seats` int(11)
,`economy_price` decimal(10,2)
,`business_price` decimal(10,2)
,`first_class_price` decimal(10,2)
,`economy_booked` decimal(32,0)
,`business_booked` decimal(32,0)
,`first_booked` decimal(32,0)
,`economy_available` decimal(33,0)
,`business_available` decimal(33,0)
,`first_available` decimal(33,0)
,`flight_status` enum('Scheduled','Delayed','Cancelled','Completed','Boarding')
);

-- --------------------------------------------------------

--
-- Table structure for table `flight_status`
--

CREATE TABLE `flight_status` (
  `status_id` tinyint(4) NOT NULL,
  `status_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `flight_status`
--

INSERT INTO `flight_status` (`status_id`, `status_name`) VALUES
(3, 'Boarding'),
(5, 'Cancelled'),
(2, 'Delayed'),
(4, 'Landed'),
(1, 'On Time');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `booking_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_reference` varchar(100) NOT NULL,
  `payment_status` varchar(20) NOT NULL DEFAULT 'Pending',
  `transaction_id` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `booking_id`, `user_id`, `amount`, `payment_method`, `payment_reference`, `payment_status`, `transaction_id`, `created_at`, `updated_at`) VALUES
(1, 3, 13, 20.00, 'Paystack', 'SOL68B1FEEB91300', 'Completed', '5287313427', '2025-08-29 20:26:53', '2025-08-29 20:26:53'),
(2, 5, 13, 20.00, 'Paystack', 'SOL68B2D69E9FD03', 'Completed', '5289365036', '2025-08-30 11:48:38', '2025-08-30 11:48:38');

-- --------------------------------------------------------

--
-- Table structure for table `sub_admins`
--

CREATE TABLE `sub_admins` (
  `sub_admin_id` int(11) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_password_change` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sub_admins`
--

INSERT INTO `sub_admins` (`sub_admin_id`, `full_name`, `email`, `password`, `status`, `created_at`, `updated_at`, `last_password_change`) VALUES
(1, 'Sidi Codes', 'sidicodes@gmail.com', '$2y$10$sXFvwwJhKwlT2U//mYnxPekRlG2GmqYm0BrQH51/zBA/wGG5LUw3W', 'Active', '2025-08-27 13:19:00', '2025-09-09 12:33:14', '2025-09-04 21:35:56');

-- --------------------------------------------------------

--
-- Table structure for table `temp_users`
--

CREATE TABLE `temp_users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `verification_code` varchar(6) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `avatar` varchar(255) DEFAULT 'default.png',
  `membership` enum('Standard','Silver','Gold','Platinum') DEFAULT 'Standard',
  `phone` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `nationality` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `emergency_contact` varchar(20) DEFAULT NULL,
  `passport_number` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `password`, `status`, `avatar`, `membership`, `phone`, `date_of_birth`, `gender`, `nationality`, `address`, `emergency_contact`, `passport_number`, `created_at`, `updated_at`, `created_by`, `updated_by`) VALUES
(12, 'Sidi Samaila Agya', 'sidi@gmail.com', '$2y$10$Jg8CRT1hA6upRT0CpNcAXOddJZDZ.L6Pe.lDxgFdlqFvdZ6I6QOT2', 'active', NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-26 12:07:56', '2025-08-28 18:18:20', NULL, NULL),
(13, 'Nkosi Sidi Agya', 'nkosi@gmail.com', '$2y$10$GTAYbih4eQ0aGRZV245OFOaXjNPvSejduZsWPGu6gYJY0qXzjT5MW', 'active', '../uploads/avatars/avatar_13_1756762879.jpg', '', '+2349033162442', '2000-09-02', 'Male', 'Nigerian', 'kontagora', '07033162442', 'AF2012', '2025-08-27 13:20:04', '2025-09-09 12:02:57', NULL, 13),
(14, 'Rebecca Sidi', 'rebecca@gmail.com', '$2y$10$qIPFPutkn7tAH4qEC6blEOMF2QQ4/SnR5jgAXrMYxTcO5U2MzgBcG', 'active', 'default.png', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-04 18:48:04', '2025-09-04 18:48:04', NULL, NULL),
(17, 'Yambale Deborah', 'yambale1@gmail.com', '$2y$10$cNKzxFoA0N5pLOWuOitddOySge6c1p4n8AgIaxWgGh8pEaZacgyQ2', 'active', 'default.png', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-08 17:33:25', '2025-09-08 17:43:06', NULL, NULL);

-- --------------------------------------------------------

--
-- Structure for view `flight_availability`
--
DROP TABLE IF EXISTS `flight_availability`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `flight_availability`  AS SELECT `f`.`flight_id` AS `flight_id`, `f`.`airline` AS `airline`, `f`.`flight_no` AS `flight_no`, `f`.`origin` AS `origin`, `f`.`destination` AS `destination`, `f`.`flight_date` AS `flight_date`, `f`.`departure_time` AS `departure_time`, `f`.`arrival_time` AS `arrival_time`, `f`.`economy_seats` AS `economy_seats`, `f`.`business_seats` AS `business_seats`, `f`.`first_class_seats` AS `first_class_seats`, `f`.`economy_price` AS `economy_price`, `f`.`business_price` AS `business_price`, `f`.`first_class_price` AS `first_class_price`, coalesce(`booked`.`economy_booked`,0) AS `economy_booked`, coalesce(`booked`.`business_booked`,0) AS `business_booked`, coalesce(`booked`.`first_booked`,0) AS `first_booked`, `f`.`economy_seats`- coalesce(`booked`.`economy_booked`,0) AS `economy_available`, `f`.`business_seats`- coalesce(`booked`.`business_booked`,0) AS `business_available`, `f`.`first_class_seats`- coalesce(`booked`.`first_booked`,0) AS `first_available`, `f`.`status` AS `flight_status` FROM (`flights` `f` left join (select `bookings`.`flight_id` AS `flight_id`,sum(case when `bookings`.`class` = 'Economy' then `bookings`.`passengers` else 0 end) AS `economy_booked`,sum(case when `bookings`.`class` = 'Business' then `bookings`.`passengers` else 0 end) AS `business_booked`,sum(case when `bookings`.`class` = 'First' then `bookings`.`passengers` else 0 end) AS `first_booked` from `bookings` where `bookings`.`status` in ('Confirmed','Completed') group by `bookings`.`flight_id`) `booked` on(`f`.`flight_id` = `booked`.`flight_id`)) WHERE `f`.`status` = 'Scheduled' ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD UNIQUE KEY `booking_ref` (`booking_ref`),
  ADD KEY `flight_id` (`flight_id`),
  ADD KEY `fk_bookings_user` (`user_id`),
  ADD KEY `idx_seats` (`seats`),
  ADD KEY `idx_class_seats` (`class`,`seats`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD UNIQUE KEY `unique_cart_item` (`user_id`,`flight_id`,`class`);

--
-- Indexes for table `flights`
--
ALTER TABLE `flights`
  ADD PRIMARY KEY (`flight_id`),
  ADD KEY `status_id` (`status_id`);

--
-- Indexes for table `flight_status`
--
ALTER TABLE `flight_status`
  ADD PRIMARY KEY (`status_id`),
  ADD UNIQUE KEY `status_name` (`status_name`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `sub_admins`
--
ALTER TABLE `sub_admins`
  ADD PRIMARY KEY (`sub_admin_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `temp_users`
--
ALTER TABLE `temp_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `flights`
--
ALTER TABLE `flights`
  MODIFY `flight_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `flight_status`
--
ALTER TABLE `flight_status`
  MODIFY `status_id` tinyint(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sub_admins`
--
ALTER TABLE `sub_admins`
  MODIFY `sub_admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `temp_users`
--
ALTER TABLE `temp_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`flight_id`) REFERENCES `flights` (`flight_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bookings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `flights`
--
ALTER TABLE `flights`
  ADD CONSTRAINT `flights_ibfk_1` FOREIGN KEY (`status_id`) REFERENCES `flight_status` (`status_id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`),
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
