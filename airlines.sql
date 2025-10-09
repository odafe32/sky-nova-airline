-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 07, 2025 at 10:30 PM
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

-- Dumping data for table `admins`

INSERT INTO `admins` (`admin_id`, `full_name`, `email`, `password`, `role`, `created_at`, `email_notifications`, `booking_alerts`, `dark_mode`, `phone`, `timezone`) VALUES
(1, 'Ahmed Musa', 'ahmed.musa@skynova.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '2025-10-01 12:00:00', 1, 1, 0, '+2348034567890', 'Africa/Lagos'),
(2, 'Fatima Abubakar', 'fatima.abubakar@skynova.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', '2025-10-01 12:00:00', 1, 1, 0, '+2348034567891', 'Africa/Lagos'),
(3, 'Chukwuemeka Okoro', 'chukwuemeka.okoro@skynova.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '2025-10-01 12:00:00', 1, 1, 0, '+2348034567892', 'Africa/Lagos');

-- --------------------------------------------------------
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

-- Dumping data for table `users`

INSERT INTO `users` (`user_id`, `full_name`, `email`, `password`, `status`, `avatar`, `membership`, `phone`, `date_of_birth`, `gender`, `nationality`, `address`, `emergency_contact`, `passport_number`, `created_at`, `updated_at`, `created_by`, `updated_by`) VALUES
(1, 'Ibrahim Hassan', 'ibrahim.hassan@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'default.png', 'Silver', '+2348023456789', '1985-03-15', 'Male', 'Nigerian', 'Plot 123 Victoria Island, Lagos', '+2348034567890', 'NG123456789', '2025-10-01 10:00:00', '2025-10-01 10:00:00', NULL, NULL),
(2, 'Maryam Ahmed', 'maryam.ahmed@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'default.png', 'Gold', '+2348034567890', '1990-07-22', 'Female', 'Nigerian', 'House 45 Wuse II, Abuja', '+2348023456789', 'NG987654321', '2025-10-01 10:00:00', '2025-10-01 10:00:00', NULL, NULL),
(3, 'Chinedu Okwu', 'chinedu.okwu@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'default.png', 'Standard', '+2347012345678', '1988-11-08', 'Male', 'Nigerian', 'No 12 Awolowo Road, Ikoyi, Lagos', '+2348034567891', 'NG456789123', '2025-10-01 10:00:00', '2025-10-01 10:00:00', NULL, NULL),
(4, 'Aisha Bello', 'aisha.bello@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'default.png', 'Platinum', '+2348067890123', '1992-01-30', 'Female', 'Nigerian', 'Flat 8B Bourdillon Court, Ikoyi, Lagos', '+2347012345678', 'NG789123456', '2025-10-01 10:00:00', '2025-10-01 10:00:00', NULL, NULL),
(5, 'Emmanuel Nwosu', 'emmanuel.nwosu@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'default.png', 'Silver', '+2349076543210', '1987-05-18', 'Male', 'Nigerian', 'Suite 201 Eko Atlantic, Lagos', '+2348067890123', 'NG321654987', '2025-10-01 10:00:00', '2025-10-01 10:00:00', NULL, NULL),
(6, 'Blessing Ekpo', 'blessing.ekpo@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'default.png', 'Gold', '+2348087654321', '1991-09-12', 'Female', 'Nigerian', 'House 67 GRA, Port Harcourt', '+2349076543210', 'NG654987321', '2025-10-01 10:00:00', '2025-10-01 10:00:00', NULL, NULL),
(7, 'Adebayo Johnson', 'adebayo.johnson@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'default.png', 'Standard', '+2348098765432', '1989-12-05', 'Male', 'Nigerian', 'No 89 Allen Avenue, Ikeja, Lagos', '+2348087654321', 'NG987321654', '2025-10-01 10:00:00', '2025-10-01 10:00:00', NULL, NULL),
(8, 'Kemi Adeyemi', 'kemi.adeyemi@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'default.png', 'Silver', '+2348109876543', '1993-04-25', 'Female', 'Nigerian', 'Apartment 4C Banana Island, Lagos', '+2348098765432', 'NG147258369', '2025-10-01 10:00:00', '2025-10-01 10:00:00', NULL, NULL),
(9, 'Tunde Bakare', 'tunde.bakare@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'default.png', 'Gold', '+2348110987654', '1986-08-14', 'Male', 'Nigerian', 'Penthouse Ocean Parade, Victoria Island, Lagos', '+2348109876543', 'NG369258147', '2025-10-01 10:00:00', '2025-10-01 10:00:00', NULL, NULL),
(10, 'Ngozi Okonkwo', 'ngozi.okonkwo@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'default.png', 'Platinum', '+2348121098765', '1994-02-28', 'Female', 'Nigerian', 'Villa 23 Ikoyi Crescent, Lagos', '+2348110987654', 'NG258147369', '2025-10-01 10:00:00', '2025-10-01 10:00:00', NULL, NULL),
(11, 'Suleiman Abdullahi', 'suleiman.abdullahi@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'default.png', 'Standard', '+2348132109876', '1984-06-10', 'Male', 'Nigerian', 'Block 5 Dolphin Estate, Lagos', '+2348121098765', 'NG741963852', '2025-10-01 10:00:00', '2025-10-01 10:00:00', NULL, NULL),
(12, 'Hauwa Ibrahim', 'hauwa.ibrahim@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'default.png', 'Silver', '+2348143210987', '1995-10-20', 'Female', 'Nigerian', 'Apartment 12 Admiralty Way, Lekki, Lagos', '+2348132109876', 'NG852963741', '2025-10-01 10:00:00', '2025-10-01 10:00:00', NULL, NULL);

-- --------------------------------------------------------
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

-- Dumping data for table `flights`

INSERT INTO `flights` (`flight_id`, `airline`, `flight_no`, `origin`, `destination`, `flight_date`, `departure_time`, `arrival_time`, `aircraft`, `price`, `seats_available`, `status`, `created_at`, `updated_at`, `status_id`, `economy_price`, `business_price`, `first_class_price`, `economy_seats`, `business_seats`, `first_class_seats`) VALUES
-- Domestic flights within Nigeria
(1, 'SkyNova Airlines', 'SN001', 'Lagos', 'Abuja', '2025-10-08', '07:30:00', '08:45:00', 'Boeing 737-800', 85000.00, 150, 'Scheduled', '2025-10-01 12:00:00', '2025-10-01 12:00:00', 1, 85000.00, 145000.00, 285000.00, 120, 24, 6),
(2, 'SkyNova Airlines', 'SN002', 'Abuja', 'Lagos', '2025-10-08', '15:20:00', '16:35:00', 'Boeing 737-800', 85000.00, 150, 'Scheduled', '2025-10-01 12:00:00', '2025-10-01 12:00:00', 1, 85000.00, 145000.00, 285000.00, 120, 24, 6),
(3, 'SkyNova Airlines', 'SN003', 'Lagos', 'Port Harcourt', '2025-10-08', '09:15:00', '10:30:00', 'Boeing 737-700', 75000.00, 140, 'Scheduled', '2025-10-01 12:00:00', '2025-10-01 12:00:00', 1, 75000.00, 125000.00, 245000.00, 110, 24, 6),
(4, 'SkyNova Airlines', 'SN004', 'Port Harcourt', 'Lagos', '2025-10-08', '18:45:00', '20:00:00', 'Boeing 737-700', 75000.00, 140, 'Scheduled', '2025-10-01 12:00:00', '2025-10-01 12:00:00', 1, 75000.00, 125000.00, 245000.00, 110, 24, 6),
(5, 'SkyNova Airlines', 'SN005', 'Lagos', 'Kano', '2025-10-08', '11:00:00', '12:15:00', 'Boeing 737-800', 65000.00, 150, 'Scheduled', '2025-10-01 12:00:00', '2025-10-01 12:00:00', 1, 65000.00, 110000.00, 215000.00, 120, 24, 6),
(6, 'SkyNova Airlines', 'SN006', 'Kano', 'Lagos', '2025-10-08', '20:30:00', '21:45:00', 'Boeing 737-800', 65000.00, 150, 'Scheduled', '2025-10-01 12:00:00', '2025-10-01 12:00:00', 1, 65000.00, 110000.00, 215000.00, 120, 24, 6),
(7, 'SkyNova Airlines', 'SN007', 'Abuja', 'Kano', '2025-10-08', '13:45:00', '14:55:00', 'Boeing 737-700', 55000.00, 140, 'Scheduled', '2025-10-01 12:00:00', '2025-10-01 12:00:00', 1, 55000.00, 95000.00, 185000.00, 110, 24, 6),
(8, 'SkyNova Airlines', 'SN008', 'Kano', 'Abuja', '2025-10-08', '16:30:00', '17:40:00', 'Boeing 737-700', 55000.00, 140, 'Scheduled', '2025-10-01 12:00:00', '2025-10-01 12:00:00', 1, 55000.00, 95000.00, 185000.00, 110, 24, 6),

-- International flights
(9, 'SkyNova Airlines', 'SN009', 'Lagos', 'LHR', '2025-10-08', '23:30:00', '05:45:00', 'Boeing 787-9', 450000.00, 250, 'Scheduled', '2025-10-01 12:00:00', '2025-10-01 12:00:00', 1, 450000.00, 750000.00, 1500000.00, 180, 50, 20),
(10, 'SkyNova Airlines', 'SN010', 'LHR', 'Lagos', '2025-10-08', '14:15:00', '20:30:00', 'Boeing 787-9', 450000.00, 250, 'Scheduled', '2025-10-01 12:00:00', '2025-10-01 12:00:00', 1, 450000.00, 750000.00, 1500000.00, 180, 50, 20),
(11, 'SkyNova Airlines', 'SN011', 'Lagos', 'DXB', '2025-10-08', '14:20:00', '22:35:00', 'Boeing 777-300ER', 380000.00, 350, 'Scheduled', '2025-10-01 12:00:00', '2025-10-01 12:00:00', 1, 380000.00, 650000.00, 1250000.00, 250, 75, 25),
(12, 'SkyNova Airlines', 'SN012', 'DXB', 'Lagos', '2025-10-08', '02:45:00', '08:55:00', 'Boeing 777-300ER', 380000.00, 350, 'Scheduled', '2025-10-01 12:00:00', '2025-10-01 12:00:00', 1, 380000.00, 650000.00, 1250000.00, 250, 75, 25),
(13, 'SkyNova Airlines', 'SN013', 'Lagos', 'CAI', '2025-10-08', '22:15:00', '02:30:00', 'Boeing 737-800', 280000.00, 150, 'Scheduled', '2025-10-01 12:00:00', '2025-10-01 12:00:00', 1, 280000.00, 480000.00, 950000.00, 120, 24, 6),
(14, 'SkyNova Airlines', 'SN014', 'CAI', 'Lagos', '2025-10-08', '10:00:00', '16:15:00', 'Boeing 737-800', 280000.00, 150, 'Scheduled', '2025-10-01 12:00:00', '2025-10-01 12:00:00', 1, 280000.00, 480000.00, 950000.00, 120, 24, 6),
(15, 'SkyNova Airlines', 'SN015', 'Abuja', 'ACC', '2025-10-08', '12:30:00', '15:20:00', 'Boeing 737-700', 195000.00, 140, 'Scheduled', '2025-10-01 12:00:00', '2025-10-01 12:00:00', 1, 195000.00, 325000.00, 650000.00, 110, 24, 6),
(16, 'SkyNova Airlines', 'SN016', 'ACC', 'Abuja', '2025-10-08', '08:45:00', '11:35:00', 'Boeing 737-700', 195000.00, 140, 'Scheduled', '2025-10-01 12:00:00', '2025-10-01 12:00:00', 1, 195000.00, 325000.00, 650000.00, 110, 24, 6),

-- More domestic flights for the next few days
(17, 'SkyNova Airlines', 'SN017', 'Lagos', 'Enugu', '2025-10-09', '06:45:00', '07:35:00', 'Boeing 737-700', 45000.00, 140, 'Scheduled', '2025-10-01 12:00:00', '2025-10-01 12:00:00', 1, 45000.00, 75000.00, 150000.00, 110, 24, 6),
(18, 'SkyNova Airlines', 'SN018', 'Enugu', 'Lagos', '2025-10-09', '19:15:00', '20:05:00', 'Boeing 737-700', 45000.00, 140, 'Scheduled', '2025-10-01 12:00:00', '2025-10-01 12:00:00', 1, 45000.00, 75000.00, 150000.00, 110, 24, 6),
(19, 'SkyNova Airlines', 'SN019', 'Abuja', 'Benin City', '2025-10-09', '10:20:00', '11:10:00', 'Boeing 737-700', 35000.00, 140, 'Scheduled', '2025-10-01 12:00:00', '2025-10-01 12:00:00', 1, 35000.00, 60000.00, 120000.00, 110, 24, 6),
(20, 'SkyNova Airlines', 'SN020', 'Benin City', 'Abuja', '2025-10-09', '17:30:00', '18:20:00', 'Boeing 737-700', 35000.00, 140, 'Scheduled', '2025-10-01 12:00:00', '2025-10-01 12:00:00', 1, 35000.00, 60000.00, 120000.00, 110, 24, 6);

-- --------------------------------------------------------
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

-- Dumping data for table `bookings`

INSERT INTO `bookings` (`booking_id`, `booking_ref`, `user_id`, `flight_id`, `passengers`, `seats`, `class`, `trip_type`, `passenger_name`, `passenger_email`, `passenger_phone`, `return_flight_id`, `total_amount`, `status`, `payment_status`, `payment_reference`, `payment_method`, `paid_amount`, `payment_date`, `booking_date`, `created_at`, `paystack_reference`, `updated_at`) VALUES
-- Domestic bookings
(1, 'SN20251008001', 1, 1, 2, 2, 'Economy', 'round', 'Ibrahim Hassan', 'ibrahim.hassan@email.com', '+2348023456789', 2, 170000.00, 'Confirmed', 'Paid', 'TXN_20251008_001', 'Paystack', 170000.00, '2025-10-07 14:30:00', '2025-10-07 14:25:00', '2025-10-07 14:25:00', 'paystack_ref_001', '2025-10-07 14:30:00'),
(2, 'SN20251008002', 2, 3, 1, 1, 'Business', 'oneway', 'Maryam Ahmed', 'maryam.ahmed@email.com', '+2348034567890', NULL, 125000.00, 'Confirmed', 'Paid', 'TXN_20251008_002', 'Paystack', 125000.00, '2025-10-07 15:45:00', '2025-10-07 15:40:00', '2025-10-07 15:40:00', 'paystack_ref_002', '2025-10-07 15:45:00'),
(3, 'SN20251008003', 3, 5, 3, 3, 'Economy', 'round', 'Chinedu Okwu', 'chinedu.okwu@email.com', '+2347012345678', 6, 195000.00, 'Confirmed', 'Paid', 'TXN_20251008_003', 'Paystack', 195000.00, '2025-10-07 16:20:00', '2025-10-07 16:15:00', '2025-10-07 16:15:00', 'paystack_ref_003', '2025-10-07 16:20:00'),
(4, 'SN20251008004', 4, 7, 1, 1, 'First', 'oneway', 'Aisha Bello', 'aisha.bello@email.com', '+2348067890123', NULL, 185000.00, 'Confirmed', 'Paid', 'TXN_20251008_004', 'Paystack', 185000.00, '2025-10-07 17:10:00', '2025-10-07 17:05:00', '2025-10-07 17:05:00', 'paystack_ref_004', '2025-10-07 17:10:00'),
(5, 'SN20251008005', 5, 9, 2, 2, 'Business', 'oneway', 'Emmanuel Nwosu', 'emmanuel.nwosu@email.com', '+2349076543210', NULL, 1500000.00, 'Confirmed', 'Paid', 'TXN_20251008_005', 'Paystack', 1500000.00, '2025-10-07 18:30:00', '2025-10-07 18:25:00', '2025-10-07 18:25:00', 'paystack_ref_005', '2025-10-07 18:30:00'),
(6, 'SN20251008006', 6, 11, 1, 1, 'Economy', 'round', 'Blessing Ekpo', 'blessing.ekpo@email.com', '+2348087654321', 12, 760000.00, 'Confirmed', 'Paid', 'TXN_20251008_006', 'Paystack', 760000.00, '2025-10-07 19:15:00', '2025-10-07 19:10:00', '2025-10-07 19:10:00', 'paystack_ref_006', '2025-10-07 19:15:00'),
(7, 'SN20251008007', 7, 13, 2, 2, 'Business', 'oneway', 'Adebayo Johnson', 'adebayo.johnson@email.com', '+2348098765432', NULL, 960000.00, 'Confirmed', 'Paid', 'TXN_20251008_007', 'Paystack', 960000.00, '2025-10-07 20:45:00', '2025-10-07 20:40:00', '2025-10-07 20:40:00', 'paystack_ref_007', '2025-10-07 20:45:00'),
(8, 'SN20251008008', 8, 15, 1, 1, 'Economy', 'round', 'Kemi Adeyemi', 'kemi.adeyemi@email.com', '+2348109876543', 16, 390000.00, 'Confirmed', 'Paid', 'TXN_20251008_008', 'Paystack', 390000.00, '2025-10-07 21:20:00', '2025-10-07 21:15:00', '2025-10-07 21:15:00', 'paystack_ref_008', '2025-10-07 21:20:00'),
(9, 'SN20251008009', 9, 17, 3, 3, 'Economy', 'oneway', 'Tunde Bakare', 'tunde.bakare@email.com', '+2348110987654', NULL, 135000.00, 'Confirmed', 'Paid', 'TXN_20251008_009', 'Paystack', 135000.00, '2025-10-07 22:05:00', '2025-10-07 22:00:00', '2025-10-07 22:00:00', 'paystack_ref_009', '2025-10-07 22:05:00'),
(10, 'SN20251008010', 10, 19, 1, 1, 'Business', 'round', 'Ngozi Okonkwo', 'ngozi.okonkwo@email.com', '+2348121098765', 20, 70000.00, 'Confirmed', 'Paid', 'TXN_20251008_010', 'Paystack', 70000.00, '2025-10-07 22:30:00', '2025-10-07 22:25:00', '2025-10-07 22:25:00', 'paystack_ref_010', '2025-10-07 22:30:00'),

-- Additional bookings for variety
(11, 'SN20251008011', 11, 2, 1, 1, 'Economy', 'oneway', 'Suleiman Abdullahi', 'suleiman.abdullahi@email.com', '+2348132109876', NULL, 85000.00, 'Confirmed', 'Paid', 'TXN_20251008_011', 'Paystack', 85000.00, '2025-10-07 23:15:00', '2025-10-07 23:10:00', '2025-10-07 23:10:00', 'paystack_ref_011', '2025-10-07 23:15:00'),
(12, 'SN20251008012', 12, 4, 2, 2, 'First', 'oneway', 'Hauwa Ibrahim', 'hauwa.ibrahim@email.com', '+2348143210987', NULL, 490000.00, 'Confirmed', 'Paid', 'TXN_20251008_012', 'Paystack', 490000.00, '2025-10-07 23:45:00', '2025-10-07 23:40:00', '2025-10-07 23:40:00', 'paystack_ref_012', '2025-10-07 23:45:00'),
(13, 'SN20251008013', 1, 10, 1, 1, 'Economy', 'oneway', 'Ibrahim Hassan', 'ibrahim.hassan@email.com', '+2348023456789', NULL, 450000.00, 'Confirmed', 'Paid', 'TXN_20251008_013', 'Paystack', 450000.00, '2025-10-08 00:30:00', '2025-10-08 00:25:00', '2025-10-08 00:25:00', 'paystack_ref_013', '2025-10-08 00:30:00'),
(14, 'SN20251008014', 3, 8, 1, 1, 'Business', 'oneway', 'Chinedu Okwu', 'chinedu.okwu@email.com', '+2347012345678', NULL, 95000.00, 'Confirmed', 'Paid', 'TXN_20251008_014', 'Paystack', 95000.00, '2025-10-08 01:15:00', '2025-10-08 01:10:00', '2025-10-08 01:10:00', 'paystack_ref_014', '2025-10-08 01:15:00'),
(15, 'SN20251008015', 5, 12, 2, 2, 'Economy', 'oneway', 'Emmanuel Nwosu', 'emmanuel.nwosu@email.com', '+2349076543210', NULL, 760000.00, 'Confirmed', 'Paid', 'TXN_20251008_015', 'Paystack', 760000.00, '2025-10-08 02:00:00', '2025-10-08 01:55:00', '2025-10-08 01:55:00', 'paystack_ref_015', '2025-10-08 02:00:00');

-- --------------------------------------------------------
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

-- Dumping data for table `payments`

INSERT INTO `payments` (`payment_id`, `booking_id`, `user_id`, `amount`, `payment_method`, `payment_reference`, `payment_status`, `transaction_id`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 170000.00, 'Paystack', 'TXN_20251008_001', 'Completed', 'txn_001_20251008', '2025-10-07 14:30:00', '2025-10-07 14:30:00'),
(2, 2, 2, 125000.00, 'Paystack', 'TXN_20251008_002', 'Completed', 'txn_002_20251008', '2025-10-07 15:45:00', '2025-10-07 15:45:00'),
(3, 3, 3, 195000.00, 'Paystack', 'TXN_20251008_003', 'Completed', 'txn_003_20251008', '2025-10-07 16:20:00', '2025-10-07 16:20:00'),
(4, 4, 4, 185000.00, 'Paystack', 'TXN_20251008_004', 'Completed', 'txn_004_20251008', '2025-10-07 17:10:00', '2025-10-07 17:10:00'),
(5, 5, 5, 1500000.00, 'Paystack', 'TXN_20251008_005', 'Completed', 'txn_005_20251008', '2025-10-07 18:30:00', '2025-10-07 18:30:00'),
(6, 6, 6, 760000.00, 'Paystack', 'TXN_20251008_006', 'Completed', 'txn_006_20251008', '2025-10-07 19:15:00', '2025-10-07 19:15:00'),
(7, 7, 7, 960000.00, 'Paystack', 'TXN_20251008_007', 'Completed', 'txn_007_20251008', '2025-10-07 20:45:00', '2025-10-07 20:45:00'),
(8, 8, 8, 390000.00, 'Paystack', 'TXN_20251008_008', 'Completed', 'txn_008_20251008', '2025-10-07 21:20:00', '2025-10-07 21:20:00'),
(9, 9, 9, 135000.00, 'Paystack', 'TXN_20251008_009', 'Completed', 'txn_009_20251008', '2025-10-07 22:05:00', '2025-10-07 22:05:00'),
(10, 10, 10, 70000.00, 'Paystack', 'TXN_20251008_010', 'Completed', 'txn_010_20251008', '2025-10-07 22:30:00', '2025-10-07 22:30:00'),
(11, 11, 11, 85000.00, 'Paystack', 'TXN_20251008_011', 'Completed', 'txn_011_20251008', '2025-10-07 23:15:00', '2025-10-07 23:15:00'),
(12, 12, 12, 490000.00, 'Paystack', 'TXN_20251008_012', 'Completed', 'txn_012_20251008', '2025-10-07 23:45:00', '2025-10-07 23:45:00'),
(13, 13, 1, 450000.00, 'Paystack', 'TXN_20251008_013', 'Completed', 'txn_013_20251008', '2025-10-08 00:30:00', '2025-10-08 00:30:00'),
(14, 14, 3, 95000.00, 'Paystack', 'TXN_20251008_014', 'Completed', 'txn_014_20251008', '2025-10-08 01:15:00', '2025-10-08 01:15:00'),
(15, 15, 5, 760000.00, 'Paystack', 'TXN_20251008_015', 'Completed', 'txn_015_20251008', '2025-10-08 02:00:00', '2025-10-08 02:00:00');

-- --------------------------------------------------------
-- Table structure for table `flight_status`
--

CREATE TABLE `flight_status` (
  `status_id` tinyint(4) NOT NULL,
  `status_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `flight_status`

INSERT INTO `flight_status` (`status_id`, `status_name`) VALUES
(1, 'On Time'),
(2, 'Delayed'),
(3, 'Boarding'),
(4, 'Departed'),
(5, 'Cancelled');

-- --------------------------------------------------------
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

-- Dumping data for table `sub_admins`

INSERT INTO `sub_admins` (`sub_admin_id`, `full_name`, `email`, `password`, `status`, `created_at`, `updated_at`, `last_password_change`) VALUES
(1, 'Yusuf Mohammed', 'yusuf.mohammed@skynova.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Active', '2025-10-01 12:00:00', '2025-10-01 12:00:00', NULL),
(2, 'Grace Okafor', 'grace.okafor@skynova.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Active', '2025-10-01 12:00:00', '2025-10-01 12:00:00', NULL);

-- --------------------------------------------------------
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
-- Indexes and constraints
--

-- Indexes for table `admins`
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `email` (`email`);

-- Indexes for table `bookings`
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD UNIQUE KEY `booking_ref` (`booking_ref`),
  ADD KEY `flight_id` (`flight_id`),
  ADD KEY `fk_bookings_user` (`user_id`),
  ADD KEY `idx_seats` (`seats`),
  ADD KEY `idx_class_seats` (`class`,`seats`);

-- Indexes for table `cart`
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD UNIQUE KEY `unique_cart_item` (`user_id`,`flight_id`,`class`);

-- Indexes for table `flights`
ALTER TABLE `flights`
  ADD PRIMARY KEY (`flight_id`),
  ADD KEY `status_id` (`status_id`);

-- Indexes for table `flight_status`
ALTER TABLE `flight_status`
  ADD PRIMARY KEY (`status_id`),
  ADD UNIQUE KEY `status_name` (`status_name`);

-- Indexes for table `payments`
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `user_id` (`user_id`);

-- Indexes for table `sub_admins`
ALTER TABLE `sub_admins`
  ADD PRIMARY KEY (`sub_admin_id`),
  ADD UNIQUE KEY `email` (`email`);

-- Indexes for table `users`
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`);

-- AUTO_INCREMENT values
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `bookings`
  MODIFY `booking_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

ALTER TABLE `cart`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `flights`
  MODIFY `flight_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

ALTER TABLE `flight_status`
  MODIFY `status_id` tinyint(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

ALTER TABLE `sub_admins`
  MODIFY `sub_admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `users`
  MODIFY `user_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

-- Constraints
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`flight_id`) REFERENCES `flights` (`flight_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bookings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `flights`
  ADD CONSTRAINT `flights_ibfk_1` FOREIGN KEY (`status_id`) REFERENCES `flight_status` (`status_id`);

ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`),
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
