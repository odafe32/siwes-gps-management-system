-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 07, 2026 at 11:49 PM
-- Server version: 8.4.3
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `siwes_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','supervisor','coordinator','admin') NOT NULL,
  `matric_number` varchar(50) DEFAULT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `institution` varchar(255) DEFAULT NULL,
  `siwes_start_date` date DEFAULT NULL,
  `siwes_end_date` date DEFAULT NULL,
  `workplace_name` varchar(255) DEFAULT NULL,
  `workplace_latitude` decimal(10,8) DEFAULT NULL,
  `workplace_longitude` decimal(11,8) DEFAULT NULL,
  `workplace_address` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `level` varchar(10) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `supervisor_id` int DEFAULT NULL,
  `reference_id` int DEFAULT NULL,
  `organization_id` int DEFAULT NULL,
  `supervisor_type` enum('industry','school') DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `full_name`, `email`, `password`, `role`, `matric_number`, `student_id`, `department`, `institution`, `siwes_start_date`, `siwes_end_date`, `workplace_name`, `workplace_latitude`, `workplace_longitude`, `workplace_address`, `phone`, `level`, `is_active`, `supervisor_id`, `reference_id`, `organization_id`, `supervisor_type`, `created_at`, `updated_at`) VALUES
(1, 'John Doe', 'johndoe', 'John Doe', 'student@test.com', '$2y$10$vBlv5LdXSNH8aNXFl6nZ3ONrSga./5Y74e4M.QWgo95thdHaa2Odi', 'student', '2021/123456', 'STU001', 'Computer Science', 'University of Nigeria', '2026-01-15', '2026-06-15', 'Tech Solutions Ltd', 6.52440000, 3.37920000, '123 Business District, Lagos, Nigeria', '08034567890', '400', 1, 2, NULL, 3, NULL, '2026-09-07 16:47:06', '2026-09-07 22:37:46'),
(2, 'Dr. Jane Smith', 'janesmith', 'Dr. Jane Smith', 'supervisor@test.com', '$2y$10$vBlv5LdXSNH8aNXFl6nZ3ONrSga./5Y74e4M.QWgo95thdHaa2Odi', 'supervisor', NULL, NULL, 'Computer Science', 'University of Nigeria', NULL, NULL, NULL, NULL, NULL, NULL, '08023456789', NULL, 1, NULL, NULL, 3, 'school', '2026-09-07 16:47:06', '2026-09-07 22:37:54'),
(3, 'Admin User', 'admin', 'Admin User', 'admin@test.com', '$2y$10$vBlv5LdXSNH8aNXFl6nZ3ONrSga./5Y74e4M.QWgo95thdHaa2Odi', 'admin', NULL, NULL, 'IT Department', 'University of Nigeria', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '2026-09-07 16:47:06', '2026-09-07 16:47:06');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
