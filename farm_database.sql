-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 04, 2025 at 04:22 AM
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
-- Database: `farm_database`
--

-- --------------------------------------------------------

--
-- Table structure for table `pest_alerts`
--

CREATE TABLE `pest_alerts` (
  `id` int(11) NOT NULL,
  `pest_type` varchar(100) NOT NULL,
  `location` varchar(100) NOT NULL,
  `severity` enum('low','medium','high','critical') NOT NULL,
  `status` enum('new','acknowledged','resolved') DEFAULT 'new',
  `description` text DEFAULT NULL,
  `suggested_actions` text DEFAULT NULL,
  `detected_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pest_alerts`
--

INSERT INTO `pest_alerts` (`id`, `pest_type`, `location`, `severity`, `status`, `description`, `suggested_actions`, `detected_at`, `updated_at`) VALUES
(1, 'Aphids', 'Greenhouse A - North', 'medium', 'new', 'Small colony of aphids detected on tomato plants. Population appears to be growing.', 'Apply insecticidal soap spray. Monitor daily for population changes. Consider introducing ladybugs as biological control.', '2025-10-03 01:56:38', '2025-10-03 03:56:38'),
(2, 'Caterpillars', 'Field B - Section 1', 'high', 'acknowledged', 'Multiple caterpillars found on corn stalks. Significant leaf damage observed.', 'Apply Bt (Bacillus thuringiensis) spray immediately. Inspect neighboring plants. Consider pheromone traps for monitoring.', '2025-10-02 21:56:38', '2025-10-03 03:56:38'),
(3, 'Fungal Infection', 'Greenhouse A - South', 'critical', 'new', 'Powdery mildew spreading rapidly on cucumber plants. High humidity conditions favor growth.', 'Reduce humidity levels immediately. Apply fungicide treatment. Remove affected leaves. Improve air circulation.', '2025-10-03 03:26:38', '2025-10-03 03:56:38'),
(4, 'Spider Mites', 'Field A - Section 2', 'low', 'resolved', 'Minor spider mite activity detected on bean plants. Population controlled.', 'Continue monitoring. Maintain adequate soil moisture. Previous neem oil treatment was effective.', '2025-10-02 03:56:38', '2025-10-03 03:56:38'),
(5, 'Beetles', 'Field A - Section 1', 'medium', 'acknowledged', 'Colorado potato beetles found on potato plants. Early instar larvae present.', 'Hand-pick adult beetles and egg masses. Apply spinosad if population increases. Monitor weekly.', '2025-10-02 23:56:38', '2025-10-03 03:56:38'),
(6, 'Whiteflies', 'Greenhouse A - Bed 1', 'high', 'new', 'Large whitefly population on pepper plants. Sticky honeydew present on leaves.', 'Install yellow sticky traps immediately. Apply horticultural oil spray. Consider beneficial insects like Encarsia formosa.', '2025-10-03 02:56:38', '2025-10-03 03:56:38'),
(7, 'Thrips', 'Field B - Center', 'low', 'acknowledged', 'Western flower thrips detected on lettuce. Minor leaf stippling observed.', 'Monitor population levels. Blue sticky traps recommended. Predatory mites may provide control.', '2025-10-02 19:56:38', '2025-10-03 03:56:38'),
(8, 'Root Rot', 'Greenhouse A - Bed 2', 'high', 'new', 'Pythium root rot suspected in hydroponic system. Plants showing wilting symptoms.', 'Check water pH and oxygen levels. Replace nutrient solution. Apply beneficial bacteria treatment. Improve drainage.', '2025-10-03 00:56:38', '2025-10-03 03:56:38');

-- --------------------------------------------------------

--
-- Table structure for table `sensors`
--

CREATE TABLE `sensors` (
  `id` int(11) NOT NULL,
  `sensor_name` varchar(100) NOT NULL,
  `sensor_type` enum('temperature','humidity','soil_moisture') NOT NULL,
  `location` varchar(100) NOT NULL,
  `status` enum('online','offline') DEFAULT 'online',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sensors`
--

INSERT INTO `sensors` (`id`, `sensor_name`, `sensor_type`, `location`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Greenhouse Temp Sensor 1', 'temperature', 'Greenhouse A - North', 'online', '2025-10-03 03:56:38', '2025-10-03 03:56:38'),
(2, 'Greenhouse Temp Sensor 2', 'temperature', 'Greenhouse A - South', 'online', '2025-10-03 03:56:38', '2025-10-03 03:56:38'),
(3, 'Field Temp Sensor 1', 'temperature', 'Field B - Center', 'offline', '2025-10-03 03:56:38', '2025-10-03 03:56:38'),
(4, 'Greenhouse Humidity 1', 'humidity', 'Greenhouse A - North', 'online', '2025-10-03 03:56:38', '2025-10-03 03:56:38'),
(5, 'Greenhouse Humidity 2', 'humidity', 'Greenhouse A - South', 'online', '2025-10-03 03:56:38', '2025-10-03 03:56:38'),
(6, 'Field Humidity 1', 'humidity', 'Field B - Center', 'online', '2025-10-03 03:56:38', '2025-10-03 03:56:38'),
(7, 'Soil Moisture 1', 'soil_moisture', 'Field A - Section 1', 'online', '2025-10-03 03:56:38', '2025-10-03 03:56:38'),
(8, 'Soil Moisture 2', 'soil_moisture', 'Field A - Section 2', 'online', '2025-10-03 03:56:38', '2025-10-03 03:56:38'),
(9, 'Soil Moisture 3', 'soil_moisture', 'Field B - Section 1', 'offline', '2025-10-03 03:56:38', '2025-10-03 03:56:38'),
(10, 'Greenhouse Soil 1', 'soil_moisture', 'Greenhouse A - Bed 1', 'online', '2025-10-03 03:56:38', '2025-10-03 03:56:38');

-- --------------------------------------------------------

--
-- Table structure for table `sensor_readings`
--

CREATE TABLE `sensor_readings` (
  `id` int(11) NOT NULL,
  `sensor_id` int(11) NOT NULL,
  `value` decimal(10,2) NOT NULL,
  `unit` varchar(20) NOT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sensor_readings`
--

INSERT INTO `sensor_readings` (`id`, `sensor_id`, `value`, `unit`, `recorded_at`) VALUES
(1, 1, 24.50, '°C', '2025-10-03 02:56:38'),
(2, 1, 25.20, '°C', '2025-10-03 01:56:38'),
(3, 1, 23.80, '°C', '2025-10-03 00:56:38'),
(4, 1, 22.10, '°C', '2025-10-02 21:56:38'),
(5, 1, 20.50, '°C', '2025-10-02 15:56:38'),
(6, 1, 26.80, '°C', '2025-10-02 03:56:38'),
(7, 1, 28.20, '°C', '2025-10-01 03:56:38'),
(8, 2, 26.10, '°C', '2025-10-03 02:56:38'),
(9, 2, 26.80, '°C', '2025-10-03 01:56:38'),
(10, 2, 25.40, '°C', '2025-10-03 00:56:38'),
(11, 2, 23.70, '°C', '2025-10-02 21:56:38'),
(12, 2, 21.90, '°C', '2025-10-02 15:56:38'),
(13, 2, 27.50, '°C', '2025-10-02 03:56:38'),
(14, 2, 29.10, '°C', '2025-10-01 03:56:38'),
(15, 4, 68.50, '%', '2025-10-03 02:56:38'),
(16, 4, 70.20, '%', '2025-10-03 01:56:38'),
(17, 4, 65.80, '%', '2025-10-03 00:56:38'),
(18, 4, 62.10, '%', '2025-10-02 21:56:38'),
(19, 4, 58.50, '%', '2025-10-02 15:56:38'),
(20, 4, 72.80, '%', '2025-10-02 03:56:38'),
(21, 4, 75.20, '%', '2025-10-01 03:56:38'),
(22, 5, 71.30, '%', '2025-10-03 02:56:38'),
(23, 5, 73.10, '%', '2025-10-03 01:56:38'),
(24, 5, 69.70, '%', '2025-10-03 00:56:38'),
(25, 5, 66.40, '%', '2025-10-02 21:56:38'),
(26, 5, 63.20, '%', '2025-10-02 15:56:38'),
(27, 5, 76.10, '%', '2025-10-02 03:56:38'),
(28, 5, 78.50, '%', '2025-10-01 03:56:38'),
(29, 6, 45.80, '%', '2025-10-03 02:56:38'),
(30, 6, 48.20, '%', '2025-10-03 01:56:38'),
(31, 6, 42.10, '%', '2025-10-03 00:56:38'),
(32, 6, 41.50, '%', '2025-10-02 21:56:38'),
(33, 6, 39.80, '%', '2025-10-02 15:56:38'),
(34, 6, 52.30, '%', '2025-10-02 03:56:38'),
(35, 6, 55.70, '%', '2025-10-01 03:56:38'),
(36, 7, 45.20, '%', '2025-10-03 02:56:38'),
(37, 7, 44.80, '%', '2025-10-03 01:56:38'),
(38, 7, 46.10, '%', '2025-10-03 00:56:38'),
(39, 7, 43.50, '%', '2025-10-02 21:56:38'),
(40, 7, 41.20, '%', '2025-10-02 15:56:38'),
(41, 7, 48.70, '%', '2025-10-02 03:56:38'),
(42, 7, 52.10, '%', '2025-10-01 03:56:38'),
(43, 8, 38.70, '%', '2025-10-03 02:56:38'),
(44, 8, 37.20, '%', '2025-10-03 01:56:38'),
(45, 8, 39.80, '%', '2025-10-03 00:56:38'),
(46, 8, 35.10, '%', '2025-10-02 21:56:38'),
(47, 8, 32.80, '%', '2025-10-02 15:56:38'),
(48, 8, 42.30, '%', '2025-10-02 03:56:38'),
(49, 8, 45.90, '%', '2025-10-01 03:56:38'),
(50, 10, 62.40, '%', '2025-10-03 02:56:38'),
(51, 10, 61.80, '%', '2025-10-03 01:56:38'),
(52, 10, 63.20, '%', '2025-10-03 00:56:38'),
(53, 10, 59.70, '%', '2025-10-02 21:56:38'),
(54, 10, 57.30, '%', '2025-10-02 15:56:38'),
(55, 10, 65.10, '%', '2025-10-02 03:56:38'),
(56, 10, 67.80, '%', '2025-10-01 03:56:38');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('admin','student','farmer') DEFAULT 'student',
  `status` enum('active','inactive') DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admins@farm.com', 'admin', 'active', '2025-10-03 15:58:05', '2025-10-03 03:56:38', '2025-10-03 15:58:05'),
(2, 'farmer1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'farmer1@farm.com', 'farmer', 'active', '2025-10-03 14:01:25', '2025-10-03 03:56:38', '2025-10-03 14:01:25'),
(3, 'student1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student1@university.edu', 'student', 'active', '2025-10-02 03:56:38', '2025-10-03 03:56:38', '2025-10-03 03:56:38'),
(4, 'farmer2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'farmer2@farm.com', 'farmer', 'active', '2025-10-03 00:56:38', '2025-10-03 03:56:38', '2025-10-03 03:56:38'),
(5, 'student2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student2@university.edu', 'student', 'inactive', NULL, '2025-10-03 03:56:38', '2025-10-03 03:56:38'),
(6, 'asdas', '$2y$10$mi6oBf2Szul3pyRpazC6ceiYvFteoV5vcEz7ylBsgMR1MuME2ADdO', 'asdasd@gmail.com', 'student', 'active', NULL, '2025-10-03 04:43:08', '2025-10-03 04:43:08');

-- --------------------------------------------------------

--
-- Table structure for table `user_settings`
--

CREATE TABLE `user_settings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_settings`
--

INSERT INTO `user_settings` (`id`, `user_id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 1, 'theme', 'dark', '2025-10-03 03:56:38', '2025-10-03 04:57:49'),
(2, 1, 'dashboard_layout', 'grid', '2025-10-03 03:56:38', '2025-10-03 04:57:49'),
(3, 1, 'notifications_email', 'true', '2025-10-03 03:56:38', '2025-10-03 03:56:38'),
(4, 1, 'notifications_pest_alerts', 'true', '2025-10-03 03:56:38', '2025-10-03 03:56:38'),
(5, 1, 'chart_refresh_interval', '300', '2025-10-03 03:56:38', '2025-10-03 03:56:38'),
(6, 2, 'theme', 'light', '2025-10-03 03:56:38', '2025-10-03 03:56:38'),
(7, 2, 'dashboard_layout', 'list', '2025-10-03 03:56:38', '2025-10-03 03:56:38'),
(8, 2, 'notifications_email', 'false', '2025-10-03 03:56:38', '2025-10-03 03:56:38'),
(9, 2, 'notifications_pest_alerts', 'true', '2025-10-03 03:56:38', '2025-10-03 03:56:38'),
(10, 2, 'chart_refresh_interval', '600', '2025-10-03 03:56:38', '2025-10-03 03:56:38'),
(11, 3, 'theme', 'light', '2025-10-03 03:56:38', '2025-10-03 03:56:38'),
(12, 3, 'dashboard_layout', 'grid', '2025-10-03 03:56:38', '2025-10-03 03:56:38'),
(13, 3, 'notifications_email', 'true', '2025-10-03 03:56:38', '2025-10-03 03:56:38'),
(14, 3, 'notifications_pest_alerts', 'false', '2025-10-03 03:56:38', '2025-10-03 03:56:38'),
(15, 3, 'chart_refresh_interval', '300', '2025-10-03 03:56:38', '2025-10-03 03:56:38'),
(18, 1, 'sidebar_collapsed', '0', '2025-10-03 04:57:49', '2025-10-03 04:57:49'),
(19, 1, 'chart_style', 'modern', '2025-10-03 04:57:49', '2025-10-03 04:57:49');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `pest_alerts`
--
ALTER TABLE `pest_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_severity_status` (`severity`,`status`),
  ADD KEY `idx_detected_at` (`detected_at`);

--
-- Indexes for table `sensors`
--
ALTER TABLE `sensors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sensor_readings`
--
ALTER TABLE `sensor_readings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sensor_recorded` (`sensor_id`,`recorded_at`),
  ADD KEY `idx_recorded_at` (`recorded_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_setting` (`user_id`,`setting_key`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `pest_alerts`
--
ALTER TABLE `pest_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `sensors`
--
ALTER TABLE `sensors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `sensor_readings`
--
ALTER TABLE `sensor_readings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `user_settings`
--
ALTER TABLE `user_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `sensor_readings`
--
ALTER TABLE `sensor_readings`
  ADD CONSTRAINT `sensor_readings_ibfk_1` FOREIGN KEY (`sensor_id`) REFERENCES `sensors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD CONSTRAINT `user_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
