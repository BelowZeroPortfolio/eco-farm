-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 29, 2025 at 03:31 PM
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
-- Table structure for table `activeplant`
--

CREATE TABLE `activeplant` (
  `ID` int(11) NOT NULL,
  `SelectedPlantID` int(11) NOT NULL,
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activeplant`
--

INSERT INTO `activeplant` (`ID`, `SelectedPlantID`, `UpdatedAt`) VALUES
(137, 5, '2025-11-29 14:27:42'),
(138, 7, '2025-11-29 14:27:42'),
(139, 3, '2025-11-29 14:27:42'),
(140, 2, '2025-11-29 14:27:42');

-- --------------------------------------------------------

--
-- Table structure for table `activeplants`
--

CREATE TABLE `activeplants` (
  `ActivePlantID` int(11) NOT NULL,
  `PlantID` int(11) NOT NULL,
  `ActivatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activeplants`
--

INSERT INTO `activeplants` (`ActivePlantID`, `PlantID`, `ActivatedAt`) VALUES
(71, 5, '2025-11-29 05:04:23'),
(72, 7, '2025-11-29 05:04:23'),
(73, 3, '2025-11-29 05:04:23'),
(74, 6, '2025-11-29 05:04:23');

-- --------------------------------------------------------

--
-- Stand-in structure for view `activeplantview`
-- (See below for the actual view)
--
CREATE TABLE `activeplantview` (
`PlantID` int(11)
,`PlantName` varchar(100)
,`LocalName` varchar(100)
,`MinSoilMoisture` int(11)
,`MaxSoilMoisture` int(11)
,`MinTemperature` float
,`MaxTemperature` float
,`MinHumidity` int(11)
,`MaxHumidity` int(11)
,`WarningTrigger` int(11)
,`SuggestedAction` text
,`CreatedAt` timestamp
,`UpdatedAt` timestamp
,`ActivatedAt` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `cameras`
--

CREATE TABLE `cameras` (
  `id` int(11) NOT NULL,
  `camera_name` varchar(100) NOT NULL,
  `location` varchar(100) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `port` int(11) DEFAULT 80,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `camera_type` enum('ip_camera','usb_camera','rtsp_stream') DEFAULT 'ip_camera',
  `resolution` varchar(20) DEFAULT '1920x1080',
  `fps` int(11) DEFAULT 30,
  `detection_enabled` tinyint(1) DEFAULT 1,
  `detection_sensitivity` enum('low','medium','high') DEFAULT 'medium',
  `detection_zones` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`detection_zones`)),
  `status` enum('online','offline','error') DEFAULT 'offline',
  `last_detection` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `NotificationID` int(11) NOT NULL,
  `PlantID` int(11) NOT NULL,
  `Message` text NOT NULL,
  `SensorType` varchar(50) NOT NULL COMMENT 'soil_moisture, temperature, or humidity',
  `Level` int(11) NOT NULL COMMENT 'Warning level when notification was triggered',
  `SuggestedAction` text NOT NULL,
  `CurrentValue` float NOT NULL COMMENT 'The sensor value that triggered notification',
  `RequiredRange` varchar(50) NOT NULL COMMENT 'Expected range for the sensor',
  `Status` varchar(20) DEFAULT 'Below Minimum' COMMENT 'Below Minimum or Above Maximum',
  `IsRead` tinyint(1) DEFAULT 0,
  `ReadAt` timestamp NULL DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`NotificationID`, `PlantID`, `Message`, `SensorType`, `Level`, `SuggestedAction`, `CurrentValue`, `RequiredRange`, `Status`, `IsRead`, `ReadAt`, `CreatedAt`) VALUES
(28, 10, 'Cucumber Soil Moisture is Below Minimum. Current value: 0, Required range: 35–75%', 'soil_moisture', 5, 'Water consistently, keep humid. Provide trellis support for better growth.', 0, '35–75%', 'Below Minimum', 0, NULL, '2025-11-28 06:09:38'),
(29, 10, 'Cucumber Temperature is Above Maximum. Current value: 33.2, Required range: 18–32°C', 'temperature', 5, 'Water consistently, keep humid. Provide trellis support for better growth.', 33.2, '18–32°C', 'Above Maximum', 0, NULL, '2025-11-28 06:09:38'),
(30, 10, 'Cucumber Humidity is Above Maximum. Current value: 85.8, Required range: 50–80%', 'humidity', 5, 'Water consistently, keep humid. Provide trellis support for better growth.', 85.8, '50–80%', 'Above Maximum', 0, NULL, '2025-11-28 06:09:38'),
(31, 10, 'Cucumber Soil Moisture is Below Minimum. Current value: 0, Required range: 35–75%', 'soil_moisture', 5, 'Water consistently, keep humid. Provide trellis support for better growth.', 0, '35–75%', 'Below Minimum', 0, NULL, '2025-11-28 07:38:19'),
(32, 10, 'Cucumber Temperature is Above Maximum. Current value: 33.2, Required range: 18–32°C', 'temperature', 5, 'Water consistently, keep humid. Provide trellis support for better growth.', 33.2, '18–32°C', 'Above Maximum', 0, NULL, '2025-11-28 07:38:19'),
(33, 10, 'Cucumber Humidity is Above Maximum. Current value: 85.8, Required range: 50–80%', 'humidity', 5, 'Water consistently, keep humid. Provide trellis support for better growth.', 85.8, '50–80%', 'Above Maximum', 0, NULL, '2025-11-28 07:38:19'),
(34, 3, 'Chili Pepper Soil Moisture is Below Minimum. Current value: 0, Required range: 25–55%', 'soil_moisture', 5, 'Water moderately, avoid direct noon sunlight. Mulch to retain moisture.', 0, '25–55%', 'Below Minimum', 0, NULL, '2025-11-28 07:47:11'),
(35, 3, 'Chili Pepper Humidity is Above Maximum. Current value: 85.8, Required range: 40–70%', 'humidity', 5, 'Water moderately, avoid direct noon sunlight. Mulch to retain moisture.', 85.8, '40–70%', 'Above Maximum', 0, NULL, '2025-11-28 07:47:11');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `token`, `expires_at`, `used`, `created_at`) VALUES
(11, 3, '2ce9f81e77c37d843e1c275baa75ad4d877b7fa5c0e69afec0b83fe58b5346db', '2025-10-21 12:44:34', 1, '2025-10-21 09:44:34'),
(12, 3, '9ce0f1071e0f87651ffef55027a5c5bfbf78a53b2f813a549a6fa7499b153e53', '2025-10-21 15:03:34', 1, '2025-10-21 12:03:34'),
(13, 3, '22a076f55618f6e9d7cc97df426fe6fceafc84404da75eb17a6137fea4ab37a8', '2025-10-21 15:03:45', 1, '2025-10-21 12:03:45'),
(14, 3, 'ce02b7912433e42a4ca2b0b6b07080fe788743dd2c41dd9a21e593ed90b2cf38', '2025-10-21 21:06:23', 1, '2025-10-21 12:06:23'),
(15, 3, '14eb94904bf66849d84234d38311bfc6210dfda4bfcf7b86fa5529bac550dd71', '2025-10-21 21:09:28', 1, '2025-10-21 12:09:28'),
(16, 3, '0ad3ece8fd1ef1ebc38196df3d7ce976bc152c853ab86894ad181dd177b98e5c', '2025-10-21 21:09:56', 1, '2025-10-21 12:09:56'),
(17, 3, '0a16dec7e65294216cce4f9aa2071923f9e859d7e02c4036f6948cd0a2c266d7', '2025-10-21 21:12:55', 1, '2025-10-21 12:12:55'),
(18, 3, '69150101ee7f868e281f52f02f1b74795f996603e1860b55ae4efe15fbc6f8fb', '2025-10-21 22:22:34', 0, '2025-10-21 13:22:34');

-- --------------------------------------------------------

--
-- Table structure for table `pest_alerts`
--

CREATE TABLE `pest_alerts` (
  `id` int(11) NOT NULL,
  `camera_id` int(11) DEFAULT NULL,
  `pest_type` varchar(100) NOT NULL,
  `common_name` varchar(200) DEFAULT NULL,
  `location` varchar(100) NOT NULL,
  `severity` enum('low','medium','high','critical') NOT NULL,
  `status` enum('new','acknowledged','resolved') DEFAULT 'new',
  `confidence_score` decimal(5,2) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `read_by` int(11) DEFAULT NULL,
  `notification_sent` tinyint(1) DEFAULT 0,
  `notification_sent_at` timestamp NULL DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `suggested_actions` text DEFAULT NULL,
  `detected_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pest_config`
--

CREATE TABLE `pest_config` (
  `id` int(11) NOT NULL,
  `pest_name` varchar(100) NOT NULL,
  `common_name` varchar(150) DEFAULT NULL,
  `pest_type` varchar(50) NOT NULL COMMENT 'Category: insect, mite, beetle, etc.',
  `description` text DEFAULT NULL,
  `severity` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `economic_threshold` varchar(200) DEFAULT NULL COMMENT 'When to take action (e.g., 10 per plant)',
  `suggested_actions` text NOT NULL COMMENT 'Treatment recommendations',
  `remarks` text DEFAULT NULL COMMENT 'Additional notes, lifecycle info, etc.',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pest_config`
--

INSERT INTO `pest_config` (`id`, `pest_name`, `common_name`, `pest_type`, `description`, `severity`, `economic_threshold`, `suggested_actions`, `remarks`, `is_active`, `created_at`, `updated_at`, `created_by`, `updated_by`) VALUES
(1, 'rice leaf roller', 'Tagapagkulong ng dahon ng palay', 'insect', 'Rolls leaves reducing photosynthesis. 10-30% yield loss.', 'high', '20% leaves show damage', 'Apply chlorantraniliprole 60ml/ha or flubendiamide 100ml/ha. Drain field 2 days before spraying. Preserve spiders and wasps.', 'Peak activity: vegetative to flowering stage', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(2, 'rice leaf caterpillar', 'Uod sa dahon ng palay', 'insect', 'Defoliates rice plants. 10-20% yield loss.', 'medium', '2-3 larvae per plant', 'Apply Bt 1kg/ha or spinosad 200ml/ha. Hand-pick larvae. Monitor at night.', 'Usually controlled by natural enemies', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(3, 'paddy stem maggot', 'Uod sa tangkay ng palay', 'insect', 'Causes dead hearts in young seedlings. 15-35% seedling mortality.', 'high', '5% dead hearts', 'Apply carbofuran 1kg/ha at transplanting. Use 15-20 day old seedlings. Drain field if severe.', 'Most damaging in young plants', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(4, 'asiatic rice borer', 'Uod tagabutas ng palay (Asyatik)', 'insect', 'Causes dead hearts and white heads. 30-80% yield loss.', 'critical', '5% dead hearts or white heads', 'Apply cartap hydrochloride 1kg/ha or chlorantraniliprole 60ml/ha. Cut and burn stubble. Use pheromone traps.', 'Treat at egg hatching stage', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(5, 'yellow rice borer', 'Dilaw na uod tagabutas ng palay', 'insect', 'Causes dead hearts and white heads. 25-60% yield loss.', 'critical', '5% white heads', 'Apply fipronil 100g/ha or flubendiamide 100ml/ha at maximum tillering. Maintain 5cm water depth for 3 days.', 'Synchronize planting dates to break cycle', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(6, 'rice gall midge', 'Gal-midge ng palay / Lamok-lamok ng palay', 'insect', 'Forms silver shoots, no grain production. 20-70% yield loss.', 'critical', '5% silver shoots', 'Apply carbofuran 1kg/ha or fipronil 100g/ha at tillering. Drain field for 3 days. Use resistant varieties.', 'Remove wild grasses around field', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(7, 'Rice Stemfly', 'Langaw sa tangkay ng palay', 'insect', 'Minor pest, causes small dead hearts. <5% yield loss.', 'low', '10% dead hearts', 'Monitor during seedling stage. Usually controlled by natural enemies. Treatment rarely needed.', 'Maintain field sanitation', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(8, 'brown plant hopper', 'Kayumangging sipsip-dahon ng palay', 'insect', 'Causes hopper burn, transmits viruses. 70-100% yield loss possible.', 'critical', '10 hoppers per plant', 'Apply imidacloprid 200g/ha or thiamethoxam 100g/ha. Drain field for 3-4 days. Scout every 2 days.', 'Transmits rice grassy stunt virus', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(9, 'white backed plant hopper', 'Puting likod na sipsip-dahon', 'insect', 'Transmits rice tungro virus. 15-40% yield loss.', 'high', '5-10 hoppers per plant', 'Apply buprofezin 500g/ha or pymetrozine 250g/ha. Remove weeds. Use virus-resistant varieties.', 'Scout weekly during vegetative stage', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(10, 'small brown plant hopper', 'Maliit na kayumangging sipsip-dahon', 'insect', 'Transmits rice ragged stunt virus. 10-30% yield loss.', 'high', '10-15 hoppers per plant', 'Apply thiamethoxam 100g/ha or dinotefuran 200g/ha. Avoid excessive nitrogen. Maintain balanced fertilization.', 'Scout at boot to heading stage', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(11, 'rice water weevil', 'Salagubang-tubig ng palay', 'insect', 'Larvae prune roots causing stunting. 10-25% yield loss.', 'high', '20% plants show feeding scars', 'Maintain 7-10cm water depth for 2 weeks after transplanting. Apply chlorpyrifos 500ml/ha if needed.', 'Delay permanent flood until established', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(12, 'rice leafhopper', 'Tagtalon-dahon ng palay', 'insect', 'Sucks sap, causes yellowing. 8-20% yield loss.', 'medium', '15 hoppers per plant', 'Apply buprofezin 500g/ha. Maintain field hygiene. Avoid early planting.', 'Natural enemies usually provide control', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(13, 'grain spreader thrips', 'Kulisap tagakalat ng butil', 'insect', 'Affects grain quality. 5-15% quality loss.', 'medium', 'High population at heading', 'Apply dimethoate 500ml/ha if severe. Harvest at proper maturity. Dry grain quickly.', 'Usually not economically damaging', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(14, 'rice shell pest', 'Balat-butil na pesteng palay', 'insect', 'Storage pest. 5-20% storage loss.', 'medium', 'Presence in stored grain', 'Dry grain to 12-14% moisture. Use hermetic storage. Apply diatomaceous earth.', 'Not a field pest - storage only', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(15, 'grub', 'Uod-lupa / Bulating salagubang', 'insect', 'White grubs sever roots. 10-25% plant loss.', 'medium', '2-4 per m²', 'Apply chlorpyrifos 2L/ha before planting. Practice crop rotation. Deep plowing exposes grubs.', 'Larvae of various beetles', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(16, 'mole cricket', 'Kamaro / Cricket sa lupa', 'insect', 'Tunnels uproot seedlings. 10-20% seedling loss.', 'medium', '2-4 per m²', 'Use poison baits in evening. Flood fields overnight. Apply fipronil 500ml/ha.', 'Plow in summer to destroy eggs', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(17, 'wireworm', 'Alupihan ng ugat', 'insect', 'Bores into seeds and roots. 10-20% stand loss.', 'medium', '1 per 10 plants', 'Apply phorate 10kg/ha at planting. Use seed treatment. Rotate with legumes.', 'Larvae of click beetles', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(18, 'white margined moth', 'Puting-margeng gamu-gamo', 'insect', 'Defoliates fruit trees. 20-40% defoliation.', 'high', '15% leaves damaged', 'Apply Bt 1kg/ha or chlorantraniliprole 60ml/ha. Hand-pick egg masses. Use light traps.', 'Larvae most active at night', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(19, 'black cutworm', 'Itim na uod-tagaputol', 'insect', 'Cuts seedlings at soil level. 10-30% plant loss.', 'medium', '3-5% plants cut', 'Apply chlorpyrifos 500ml/ha around plant base. Use cardboard collars. Scout at night.', 'Hide in soil during day', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(20, 'large cutworm', 'Malaking uod-tagaputol', 'insect', 'Feeds on young plants at night. 10-20% plant loss.', 'medium', '5% plants damaged', 'Hand-pick at night. Apply Bt 1kg/ha or spinosad 200ml/ha. Use bait traps.', 'Remove plant debris', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(21, 'yellow cutworm', 'Dilaw na uod-tagaputol', 'insect', 'Damages seedlings. 10-20% plant loss.', 'medium', '5% plants damaged', 'Apply carbaryl 1kg/ha around plant base. Remove debris. Cultivate before planting.', 'Use collars on transplants', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(22, 'red spider', 'Pulang gagamba / pulang hama', 'mite', 'Two-spotted spider mite causes bronzing. 10-25% yield loss.', 'medium', '5-10 mites per leaf', 'Apply abamectin 500ml/ha or spiromesifen 600ml/ha. Increase irrigation. Release predatory mites.', 'Avoid dusty conditions', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(23, 'corn borer', 'Uod-tagabutas ng mais', 'insect', 'Tunnels weaken stalks. 20-50% yield loss.', 'critical', '10% plants show damage', 'Apply Bt 1kg/ha or spinosad 200ml/ha. Apply granules in whorl. Remove infested plants.', 'Plant early to avoid peak', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(24, 'army worm', 'Uod-hukbo', 'insect', 'Migrates in groups, consumes plants. 80-100% defoliation.', 'critical', '2-3 larvae per plant', 'Apply chlorpyrifos 500ml/ha or emamectin benzoate 200g/ha at dusk. Scout at dawn. Treat borders first.', 'Can destroy entire fields', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(25, 'aphids', 'Kuto ng halaman', 'insect', 'Transmits viruses, causes stunting. 20-50% yield loss.', 'high', '50 aphids per plant', 'Apply imidacloprid 100ml/ha or acetamiprid 100g/ha. Release ladybugs. Use reflective mulch.', 'Avoid excessive nitrogen', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(26, 'Potosiabre vitarsis', 'Salagubang sa palay (Potosia type)', 'beetle', 'Flower beetle. <5% yield loss.', 'low', 'High population', 'Hand-pick beetles. Usually minor. Monitor during flowering.', 'Feeds on flowers and fruits', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(27, 'peach borer', 'Uod sa melokoton (peach borer)', 'insect', 'Bores into trunk. 10-20% tree mortality.', 'medium', 'Gum and frass present', 'Apply permethrin to trunk base. Remove gum. Paint trunk white. Use pheromone disruption.', 'April-August most active', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(28, 'english grain aphid', 'Kuto ng butil (Ingles na uri)', 'insect', 'Feeds on wheat heads. 5-15% yield loss.', 'medium', '15 aphids per head', 'Apply pirimicarb 250g/ha or thiamethoxam 100g/ha at milk stage. Preserve natural enemies.', 'Boot to dough stage critical', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(29, 'green bug', 'Berdeng kuto ng halaman', 'insect', 'Greenbug aphid injects toxin. 10-30% yield loss.', 'medium', '50 aphids per plant', 'Apply imidacloprid 100ml/ha. Use resistant varieties. Preserve parasitic wasps.', 'Scout twice weekly in spring', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(30, 'bird cherry-oataphid', 'Kuto ng trigo at oats', 'insect', 'Early season aphid. 5-15% yield loss.', 'medium', '10 aphids per tiller', 'Apply insecticidal soap if needed. Usually controlled by natural enemies.', 'Rarely requires treatment', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(31, 'wheat blossom midge', 'Lamok ng bulaklak ng trigo', 'insect', 'Destroys developing grain. 10-30% yield loss.', 'medium', 'Adults present at flowering', 'Apply lambda-cyhalothrin 250ml/ha at early flowering. Use resistant varieties. Plow stubble.', 'Scout at dusk for adults', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(32, 'penthaleus major', 'Pulang gagamba sa trigo', 'mite', 'Winter grain mite. <5% yield loss.', 'low', 'Silvering on leaves', 'Monitor only. Usually not damaging. Natural rainfall controls.', 'Appears as silvering', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(33, 'longlegged spider mite', 'Habang-paa na hama', 'mite', 'Beneficial predator of pest mites.', 'low', 'N/A', 'No action needed. Preserve as natural enemy.', 'Beneficial species', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(34, 'wheat phloeothrips', 'Kulisap sa tangkay ng trigo', 'insect', 'Minor pest of wheat heads. <3% yield loss.', 'low', 'High population', 'Monitor only. Rarely requires treatment.', 'Damage is cosmetic', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(35, 'wheat sawfly', 'Langaw na tagabutas ng trigo', 'insect', 'Causes lodging. 10-30% yield loss.', 'medium', 'Larvae present', 'Plant solid stem varieties. Harvest low. Rotate crops. Plow stubble immediately.', 'Larvae bore into stems', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(36, 'cerodonta denticornis', 'Langaw ng damo / rice fly', 'insect', 'Leaf miner. <5% yield loss.', 'low', 'White trails on leaves', 'Remove affected leaves. Usually minor. Natural parasitoids control.', 'Cosmetic damage', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(37, 'beet fly', 'Langaw ng beet', 'insect', 'Larvae mine leaves. 10-20% yield loss.', 'medium', '30% leaves mined', 'Remove affected leaves. Apply spinosad 200ml/ha. Use row covers.', 'One generation per season', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(38, 'flea beetle', 'Lukso-salagubang', 'beetle', 'Shot-hole damage. 20-40% yield loss in young plants.', 'high', '2-3 beetles per plant', 'Apply neem oil 3ml/L or pyrethrin 200ml/ha. Use floating row covers. Apply kaolin clay.', '10% leaf area threshold', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(39, 'cabbage army worm', 'Uod-hukbo ng repolyo', 'insect', 'Voracious feeder on crucifers. 30-60% defoliation.', 'high', '2 larvae per plant', 'Apply Bt 1kg/ha or emamectin benzoate 200g/ha in evening. Hand-pick egg masses.', 'Use pheromone traps', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(40, 'beet army worm', 'Uod-hukbo ng beet', 'insect', 'Attacks 300+ species. 25-50% yield loss.', 'high', '10% plants show damage', 'Apply spinosad 200ml/ha or indoxacarb 200ml/ha. Scout undersides for eggs.', 'Larvae hide in soil during day', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(41, 'Beet spot flies', 'Langaw na tagabuo ng batik sa beet', 'insect', 'Minor pest. <5% yield loss.', 'low', 'Presence of flies', 'Monitor only. Usually not significant.', 'Rarely requires treatment', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(42, 'meadow moth', 'Gamu-gamong parang', 'insect', 'Feeds on grass and crops. 10-20% defoliation.', 'medium', '5 larvae per m²', 'Apply Bt 1kg/ha or chlorantraniliprole 60ml/ha. Mow grass around fields.', 'Usually sporadic', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(43, 'beet weevil', 'Salagubang ng beet', 'beetle', 'Damages beets. 10-25% yield loss.', 'medium', 'Adults present', 'Apply thiamethoxam 100g/ha at seedling stage. Practice 3-year rotation. Remove debris.', 'Plow in fall', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(44, 'sericaorient alismots chulsky', 'Oriental na salagubang', 'beetle', 'Scarab beetle. <8% yield loss.', 'low', 'High population', 'Monitor and apply insecticide if needed. Usually minor.', 'Adults feed on leaves', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(45, 'alfalfa weevil', 'Salagubang ng alfalfa', 'beetle', 'Skeletonizes leaves. 15-30% yield loss.', 'medium', '30% tips show feeding', 'Apply chlorpyrifos 500ml/ha or malathion 1L/ha. Early harvest if severe.', 'Preserve natural enemies', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(46, 'flax budworm', 'Uod sa usbong ng flax', 'insect', 'Minor pest of flax. <5% yield loss.', 'low', '20% buds damaged', 'Monitor during bud stage. Usually sporadic.', 'Treat only if threshold met', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(47, 'alfalfa plant bug', 'Kulisap sa alfalfa', 'insect', 'Minor pest. <5% yield loss.', 'low', '2 bugs per sweep', 'Monitor during bud stage. Usually controlled by natural enemies.', 'Causes stippling', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(48, 'tarnished plant bug', 'Maduming kulisap ng halaman', 'insect', 'Causes fruit deformity. 10-25% yield loss.', 'medium', '1 bug per 6 plants', 'Apply acephate 500g/ha or bifenthrin 200ml/ha at bud stage. Remove weeds.', 'Use white sticky traps', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(49, 'Locustoidea', 'Tipaklong', 'insect', 'Swarms destroy entire fields. 100% crop loss possible.', 'critical', 'Swarm presence', 'Apply malathion 1000ml/ha or lambda-cyhalothrin 250ml/ha. Coordinate with neighbors. Report immediately.', 'IMMEDIATE ACTION REQUIRED', 1, '2025-10-22 23:18:36', '2025-10-23 00:01:05', NULL, NULL),
(50, 'lytta polita', 'Blister beetle (salagubang na nagdudulot ng paltos)', 'beetle', 'Blister beetle. <5% defoliation.', 'low', 'Presence of beetles', 'Hand-pick with gloves. Usually sporadic.', 'Avoid in hay - toxic', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(51, 'legume blister beetle', 'Paltos-salagubang sa munggo', 'beetle', 'Occasional pest. <5% defoliation.', 'low', 'Beetles present', 'Hand-pick if necessary. Usually sporadic.', 'Concern in hay production', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(52, 'blister beetle', 'Salagubang-paltos', 'beetle', 'Defoliates crops. 10-20% defoliation.', 'medium', 'High population', 'Hand-pick with gloves. Apply carbaryl 1kg/ha if severe.', 'Toxic to livestock', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(53, 'therioaphis maculata Buckton', 'Kuto ng alfalfa', 'insect', 'Spotted alfalfa aphid. <8% yield loss.', 'low', '40 aphids per stem', 'Monitor population. Usually controlled by predators.', 'Ladybugs provide control', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(54, 'odontothrips loti', 'Thrips sa munggo', 'insect', 'Clover thrips. <5% yield loss.', 'low', 'High population', 'Monitor only. Natural enemies control.', 'Usually minor', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(55, 'Thrips', 'Kulisap / Tripes', 'insect', 'Transmits viruses, causes scarring. 15-40% yield loss.', 'high', '5 thrips per flower', 'Apply spinosad 200ml/ha or abamectin 500ml/ha. Use blue sticky traps. Remove weeds.', '30 per plant threshold', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(56, 'alfalfa seed chalcid', 'Kulisap ng binhi ng alfalfa', 'insect', 'Affects seed production. <10% seed loss.', 'low', 'Seed fields only', 'Monitor seed fields. Use early-maturing varieties.', 'Not significant in forage', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(57, 'Pieris canidia', 'Puting paru-paro ng repolyo', 'insect', 'Cabbage butterfly. 15-30% yield loss.', 'medium', '0.3 larvae per plant', 'Hand-pick caterpillars and eggs. Apply Bt 1kg/ha or spinosad 200ml/ha. Use row covers.', 'Yellow eggs on undersides', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(58, 'Apolygus lucorum', 'Kulisap sa bulak / cotton bug', 'insect', 'Mirid bug causes fruit drop. 10-25% yield loss.', 'medium', '1 bug per 10 plants', 'Apply imidacloprid 200ml/ha at bud stage. Remove alternate hosts. Use pheromone traps.', 'Causes fruit deformity', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(59, 'Limacodidae', 'Uod-balat / slug caterpillar', 'insect', 'Slug caterpillars. <5% defoliation.', 'low', 'Caterpillars present', 'Hand-pick with gloves. Usually minor.', 'Stinging hairs - nuisance', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(60, 'Viteus vitifoliae', 'Kulisap ng ubas', 'insect', 'Grape phylloxera. <5% yield loss on resistant rootstocks.', 'low', 'Galls present', 'Use resistant rootstocks. Monitor for galls.', 'Not problematic on grafted vines', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(61, 'Colomerus vitis', 'Hama sa ubas', 'mite', 'Grape erineum mite. <5% yield loss.', 'low', 'Leaf galls present', 'Prune affected leaves. Rarely requires treatment.', 'Cosmetic damage', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(62, 'Brevipoalpus lewisi McGregor', 'Pulang mite ng prutas', 'mite', 'False spider mite. <5% yield loss.', 'low', 'Mites present', 'Monitor only. Natural predators control.', 'Usually minor', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(63, 'oides decempunctata', 'Salagubang ng dahon', 'beetle', 'Leaf beetle. <8% defoliation.', 'low', 'High population', 'Monitor and hand-pick if necessary.', 'Usually not significant', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(64, 'Polyphagotars onemus latus', 'Broad mite / Hama sa dahon', 'mite', 'Broad mite. <10% yield loss.', 'low', 'Leaf distortion', 'Apply abamectin only if severe.', 'Usually minor', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(65, 'Pseudococcus comstocki Kuwana', 'Mealybug / Malagkit na kuto', 'insect', 'Comstock mealybug. <8% yield loss.', 'low', 'Mealybugs present', 'Apply horticultural oil 20ml/L. Introduce Cryptolaemus.', 'Natural enemies help', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(66, 'parathrene regalis', 'Uod-tagabutas ng puno', 'insect', 'Clearwing moth borer. <5% yield loss.', 'low', 'Entry holes present', 'Prune affected branches. Usually sporadic.', 'Monitor for frass', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(67, 'Ampelophaga', 'Uod ng ubas (Ampelophaga)', 'insect', 'Hawk moth caterpillar. <5% defoliation.', 'low', 'Large caterpillars', 'Hand-pick caterpillars. Usually not severe.', 'Easy to spot', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(68, 'Lycorma delicatula', 'Spotted lanternfly / Lanternfly ng ubas', 'insect', 'Spotted lanternfly. <10% yield loss.', 'low', 'Egg masses or adults', 'Scrape egg masses. Apply contact insecticide. Remove tree of heaven.', 'Established areas only', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(69, 'Xylotrechus', 'Bukbok ng kahoy', 'beetle', 'Longhorn beetle. <5% tree mortality.', 'low', 'Infested wood', 'Remove and destroy infested wood. Maintain tree vigor.', 'Attacks weakened trees', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(70, 'Cicadella viridis', 'Berdeng leafhopper', 'insect', 'Green leafhopper. <5% yield loss.', 'low', 'High population', 'Monitor only. Natural enemies control.', 'Usually minor', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(71, 'Miridae', 'Kulisap ng halaman (Mirid bug)', 'insect', 'Plant bugs. <8% yield loss.', 'low', 'Damage present', 'Monitor for stippling. Usually minor.', 'Various species', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(72, 'Trialeurodes vaporariorum', 'Whitefly / Puting langaw', 'insect', 'Greenhouse whitefly. <10% yield loss.', 'low', '10 per leaf', 'Apply insecticidal soap 20ml/L. Use yellow sticky traps. Release Encarsia wasps.', 'Protected cultivation', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(73, 'Erythroneura apicalis', 'Red-tipped leafhopper', 'insect', 'Grape leafhopper. <5% yield loss.', 'low', '15 nymphs per leaf', 'Monitor population. Natural enemies control.', 'Causes stippling', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(74, 'Papilio xuthus', 'Paru-parong dilaw (Swallowtail)', 'insect', 'Swallowtail butterfly. <3% defoliation.', 'low', 'Caterpillars present', 'Hand-pick if needed. Usually aesthetic only.', 'Often beneficial for pollination', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(75, 'Panonchus citri McGregor', 'Red mite ng dalandan', 'mite', 'Citrus red mite. <8% yield loss.', 'low', '5-10 mites per leaf', 'Apply horticultural oil 20ml/L. Usually controlled by predatory mites.', 'Avoid broad-spectrum insecticides', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(76, 'Phyllocoptes oleiverus ashmead', 'Olive mite', 'mite', 'Citrus rust mite. <5% cosmetic damage.', 'low', 'Bronzing present', 'Apply sulfur 3g/L or oil if severe. Usually minor.', 'Some bronzing normal', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(77, 'Icerya purchasi Maskell', 'Cottony cushion scale / Kuto-buhok', 'insect', 'Cottony cushion scale. <5% yield loss.', 'low', 'Scales present', 'Introduce vedalia beetle. Apply oil only if severe.', 'Biocontrol usually sufficient', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(78, 'Unaspis yanonensis', 'Scale insect ng dalandan', 'insect', 'Arrowhead scale. <8% yield loss.', 'low', 'Scales present', 'Apply horticultural oil during dormant season. Prune infested branches.', 'Usually minor', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(79, 'Ceroplastes rubens', 'Pulang scale insect', 'insect', 'Red wax scale. <8% yield loss.', 'low', 'Scales present', 'Apply horticultural oil. Prune infested branches. Natural enemies help.', 'Parasitic wasps control', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(80, 'Chrysomphalus aonidum', 'Armored scale insect', 'insect', 'Florida red scale. <8% yield loss.', 'low', 'Scales present', 'Apply horticultural oil. Introduce Aphytis wasps.', 'Biocontrol available', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(81, 'Parlatoria zizyphus Lucus', 'Kuto ng jujube / zizyphus', 'insect', 'Black parlatoria scale. <5% yield loss.', 'low', 'Scales present', 'Apply horticultural oil. Maintain tree vigor.', 'Usually minor', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(82, 'Nipaecoccus vastalor', 'Nipa mealybug / Mealybug ng niyog', 'insect', 'Mealybug. <8% yield loss.', 'low', 'Mealybugs present', 'Apply insecticidal soap or neem oil. Introduce Cryptolaemus and Leptomastix.', 'Natural enemies available', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(83, 'Aleurocanthus spiniferus', 'Blackfly ng sitrus', 'insect', 'Orange spiny whitefly. <10% yield loss.', 'low', 'Whiteflies present', 'Apply horticultural oil. Use yellow sticky traps. Encarsia wasps control.', 'Natural enemies help', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(84, 'Tetradacus c Bactrocera minax', 'Prutas na langaw (Fruit fly)', 'insect', 'Chinese citrus fly. 40-90% fruit drop.', 'critical', 'Flies present', 'Install protein bait traps. Apply spinosad + protein bait weekly. Collect fallen fruit daily.', 'Bag fruits if high-value', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(85, 'Dacus dorsalis(Hendel)', 'Langaw ng prutas / Mango fruit fly', 'insect', 'Oriental fruit fly. 50-100% fruit damage.', 'critical', 'Flies present', 'Mass trapping with methyl eugenol. Apply spinosad bait spray. Remove all fallen fruit.', 'Attacks 150+ hosts', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(86, 'Bactrocera tsuneonis', 'Langaw ng prutas (Tsuneo type)', 'insect', 'Fruit fly. <10% fruit damage.', 'low', 'Flies present', 'Use protein bait traps. Practice sanitation.', 'Less damaging than major species', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(87, 'Prodenia litura', 'Uod-hukbo ng gulay', 'insect', 'Tobacco cutworm. 25-50% yield loss.', 'high', '2-3 larvae per plant', 'Apply Bt 1kg/ha or spinosad 200ml/ha in evening. Hand-pick larvae. Use pheromone traps.', 'Attacks 120+ crops', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(88, 'Adristyrannus', 'Uod ng kahoy / wood borer', 'insect', 'Minor pest. <5% yield loss.', 'low', 'High population', 'Monitor only. Usually not significant.', 'Natural control adequate', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(89, 'Phyllocnistis citrella Stainton', 'Leaf miner ng sitrus', 'insect', 'Citrus leafminer. <5% yield loss on mature trees.', 'low', 'Mines on young trees', 'Apply spinosad or abamectin on young trees only. Mature trees tolerate.', 'Prune affected shoots', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(90, 'Toxoptera citricidus', 'Itim na kuto ng sitrus', 'insect', 'Brown citrus aphid. 10-30% yield loss.', 'medium', '10% shoots infested', 'Apply imidacloprid or thiamethoxam. Preserve ladybugs. Prune infested shoots.', 'Transmits tristeza virus', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(91, 'Toxoptera aurantii', 'Kuto ng dahon ng dalandan', 'insect', 'Black citrus aphid. 5-15% yield loss.', 'medium', 'Aphids present', 'Apply insecticidal soap or neem oil. Introduce parasitic wasps. Prune water sprouts.', 'Less damaging than brown', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(92, 'Aphis citricola Vander Goot', 'Kuto ng prutas-sitrus', 'insect', 'Spiraea aphid. <5% yield loss.', 'low', 'Aphids present', 'Apply insecticidal soap. Usually minor.', 'Natural enemies control', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(93, 'Scirtothrips dorsalis Hood', 'Thrips ng sitrus', 'insect', 'Chilli thrips. <10% cosmetic damage.', 'low', 'Thrips present', 'Apply spinosad or abamectin. Remove weeds. Use blue sticky traps.', 'Causes fruit scarring', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(94, 'Dasineura sp', 'Gall midge / Lamok-lamok sa usbong', 'insect', 'Gall midge. <8% yield loss.', 'low', 'Galls present', 'Prune and destroy galls. Apply insecticide only if severe.', 'Usually minor', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(95, 'Lawana imitata Melichar', 'Puting planthopper ng mangga', 'insect', 'Planthopper. <5% yield loss.', 'low', 'High population', 'Monitor population. Natural enemies control.', 'Usually minor', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(96, 'Salurnis marginella Guerr', 'Kulisap ng mangga', 'insect', 'Minor pest. <5% yield loss.', 'low', 'Presence', 'Monitor only. Usually not significant.', 'Rarely requires action', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(97, 'Deporaus marginatus Pascoe', 'Weevil ng mangga', 'beetle', 'Weevil. <5% yield loss.', 'low', 'Weevils present', 'Monitor and hand-pick if present.', 'Minor occurrence', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(98, 'Chlumetia transversa', 'Uod ng mangga (mango leaf caterpillar)', 'insect', 'Minor pest. <5% yield loss.', 'low', 'Presence', 'Monitor population. Usually not significant.', 'Natural control adequate', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(99, 'Mango flat beak leafhopper', 'Patag-ilong na leafhopper ng mangga', 'insect', 'Causes hopper burn. 15-35% yield loss.', 'high', 'Hoppers present at flowering', 'Apply imidacloprid or thiamethoxam at panicle emergence. Prune affected branches.', 'Two applications 15 days apart', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(100, 'Rhytidodera bowrinii white', 'Bukbok ng mangga', 'beetle', 'Longhorn beetle. <5% tree damage.', 'low', 'Infested wood', 'Remove and destroy infested wood. Maintain tree vigor.', 'Attacks stressed trees', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(101, 'Sternochetus frigidus', 'Butas-butil ng mangga', 'beetle', 'Weevil. <8% yield loss.', 'low', 'High population', 'Monitor and apply insecticide if high.', 'Usually minor', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(102, 'Cicadellidae', 'Pamilyang leafhopper / Tagtalon-dahon', 'insect', 'Leafhoppers. <8% yield loss.', 'low', 'High population', 'Monitor population. Usually controlled by natural enemies.', 'Various species', 1, '2025-10-22 23:18:36', '2025-10-23 00:02:31', NULL, NULL),
(103, 'TESTING222', NULL, 'beetle', 'TESING22', 'medium', '12', 'TEST22', 'TEST22', 1, '2025-10-22 23:38:22', '2025-10-22 23:38:50', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `plants`
--

CREATE TABLE `plants` (
  `PlantID` int(11) NOT NULL,
  `PlantName` varchar(100) NOT NULL,
  `LocalName` varchar(100) NOT NULL COMMENT 'Filipino name',
  `MinSoilMoisture` int(11) NOT NULL COMMENT 'Minimum soil moisture percentage',
  `MaxSoilMoisture` int(11) NOT NULL COMMENT 'Maximum soil moisture percentage',
  `MinTemperature` float NOT NULL COMMENT 'Minimum temperature in Celsius',
  `MaxTemperature` float NOT NULL COMMENT 'Maximum temperature in Celsius',
  `MinHumidity` int(11) NOT NULL COMMENT 'Minimum humidity percentage',
  `MaxHumidity` int(11) NOT NULL COMMENT 'Maximum humidity percentage',
  `WarningTrigger` int(11) DEFAULT 5 COMMENT 'Number of violations before notification',
  `SuggestedAction` text NOT NULL COMMENT 'Recommended action when thresholds violated',
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `plants`
--

INSERT INTO `plants` (`PlantID`, `PlantName`, `LocalName`, `MinSoilMoisture`, `MaxSoilMoisture`, `MinTemperature`, `MaxTemperature`, `MinHumidity`, `MaxHumidity`, `WarningTrigger`, `SuggestedAction`, `CreatedAt`, `UpdatedAt`) VALUES
(1, 'Tomato', 'Kamatis', 30, 60, 18, 35, 40, 70, 5, 'Water lightly and improve airflow. Ensure proper drainage to prevent root rot.', '2025-11-27 12:06:11', '2025-11-27 12:06:11'),
(2, 'Lettuce', 'Letsugas', 40, 70, 15, 25, 50, 80, 5, 'Add water immediately and keep in cooler place. Provide shade during hot hours.', '2025-11-27 12:06:11', '2025-11-27 12:06:11'),
(3, 'Chili Pepper', 'Sili', 25, 55, 20, 35, 40, 70, 5, 'Water moderately, avoid direct noon sunlight. Mulch to retain moisture.', '2025-11-27 12:06:11', '2025-11-27 12:06:11'),
(4, 'Eggplant', 'Talong', 40, 70, 18, 30, 50, 80, 5, 'Water soil consistently and ensure consistent sunlight. Check for pests regularly.', '2025-11-27 12:06:11', '2025-11-27 12:06:11'),
(5, 'Banana', 'Saging', 45, 75, 25, 34, 70, 90, 5, 'Add water immediately, ensure high humidity. Protect from strong winds.', '2025-11-27 12:06:11', '2025-11-27 12:09:28'),
(6, 'Pechay', 'Pechay', 35, 70, 16, 25, 50, 85, 5, 'Water soil regularly and keep partially shaded. Harvest before bolting.', '2025-11-27 12:06:11', '2025-11-27 12:06:11'),
(7, 'Calamansi', 'Kalamansi', 25, 55, 20, 32, 40, 70, 5, 'Water slightly and provide good airflow. Fertilize monthly during growing season.', '2025-11-27 12:06:11', '2025-11-27 12:06:11'),
(8, 'Okra', 'Okra', 30, 60, 20, 32, 40, 70, 5, 'Water soil deeply and ensure full sun. Harvest pods when young and tender.', '2025-11-27 12:06:11', '2025-11-27 12:06:11'),
(9, 'Malunggay', 'Moringa', 20, 50, 22, 35, 30, 60, 5, 'Minimal water needed, avoid overwatering. Very drought-tolerant once established.', '2025-11-27 12:06:11', '2025-11-27 12:06:11'),
(10, 'Cucumber', 'Pipino', 35, 75, 18, 32, 50, 80, 5, 'Water consistently, keep humid. Provide trellis support for better growth.', '2025-11-27 12:06:11', '2025-11-27 12:06:11'),
(11, 'sample', 'sample', 11, 12, 23, 23, 22, 43, 1, 'sample', '2025-11-27 13:03:52', '2025-11-27 15:38:16');

-- --------------------------------------------------------

--
-- Table structure for table `sensorreadings`
--

CREATE TABLE `sensorreadings` (
  `ReadingID` int(11) NOT NULL,
  `PlantID` int(11) NOT NULL,
  `SoilMoisture` int(11) NOT NULL COMMENT 'Soil moisture percentage',
  `Temperature` float NOT NULL COMMENT 'Temperature in Celsius',
  `Humidity` int(11) NOT NULL COMMENT 'Humidity percentage',
  `WarningLevel` int(11) DEFAULT 0 COMMENT 'Cumulative warning count',
  `ReadingTime` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sensorreadings`
--

INSERT INTO `sensorreadings` (`ReadingID`, `PlantID`, `SoilMoisture`, `Temperature`, `Humidity`, `WarningLevel`, `ReadingTime`) VALUES
(1, 11, 0, 29.4, 100, 1, '2025-11-27 16:03:57'),
(2, 3, 0, 29.5, 100, 1, '2025-11-27 16:06:03'),
(3, 3, 0, 29.7, 100, 2, '2025-11-27 16:10:03'),
(4, 3, 0, 29.7, 100, 3, '2025-11-27 16:10:07'),
(5, 3, 0, 29.7, 100, 4, '2025-11-27 16:10:11'),
(6, 3, 0, 29.7, 100, 5, '2025-11-27 16:10:14'),
(7, 3, 0, 29.7, 100, 6, '2025-11-27 16:10:18'),
(8, 3, 0, 29.7, 100, 7, '2025-11-27 16:10:22'),
(9, 3, 40, 27.5, 55, 0, '2025-11-27 16:10:30'),
(10, 3, 0, 29.6, 100, 1, '2025-11-27 16:10:30'),
(11, 3, 0, 29.6, 100, 2, '2025-11-27 16:10:30'),
(12, 3, 0, 29.6, 100, 3, '2025-11-27 16:10:34'),
(13, 3, 0, 29.6, 100, 4, '2025-11-27 16:10:39'),
(14, 3, 0, 29.6, 100, 5, '2025-11-27 16:10:40'),
(15, 3, 0, 29.6, 100, 6, '2025-11-27 16:10:43'),
(16, 3, 0, 29.6, 100, 7, '2025-11-27 16:10:47'),
(17, 3, 0, 29.6, 100, 8, '2025-11-27 16:10:51'),
(18, 3, 0, 29.6, 100, 9, '2025-11-27 16:10:55'),
(19, 3, 0, 29.6, 100, 10, '2025-11-27 16:10:59'),
(20, 3, 0, 29.5, 100, 11, '2025-11-27 16:11:03'),
(21, 3, 0, 29.5, 100, 12, '2025-11-27 16:11:07'),
(22, 3, 0, 29.5, 100, 13, '2025-11-27 16:11:10'),
(23, 3, 0, 29.5, 100, 14, '2025-11-27 16:11:14'),
(24, 3, 0, 29.5, 100, 15, '2025-11-27 16:11:15'),
(25, 3, 0, 29.5, 100, 16, '2025-11-27 16:11:19'),
(26, 3, 0, 29.5, 100, 17, '2025-11-27 16:11:35'),
(27, 3, 0, 29.5, 100, 18, '2025-11-27 16:12:15'),
(28, 3, 0, 29.5, 100, 19, '2025-11-27 16:12:19'),
(29, 3, 0, 29.5, 100, 20, '2025-11-27 16:12:29'),
(30, 3, 0, 29.5, 100, 21, '2025-11-27 16:12:31'),
(31, 3, 40, 27.5, 55, 0, '2025-11-27 16:12:32'),
(32, 3, 0, 29.5, 100, 1, '2025-11-27 16:12:35'),
(33, 3, 0, 29.5, 100, 2, '2025-11-27 16:12:39'),
(34, 3, 0, 29.5, 100, 3, '2025-11-27 16:12:48'),
(35, 3, 0, 29.5, 100, 4, '2025-11-27 16:12:52'),
(36, 3, 0, 29.5, 100, 5, '2025-11-27 16:12:56'),
(37, 3, 0, 29.5, 100, 6, '2025-11-27 16:13:11'),
(38, 3, 0, 32.2, 89, 7, '2025-11-28 05:06:10'),
(39, 3, 40, 27.5, 55, 0, '2025-11-28 05:06:17'),
(40, 3, 0, 32.7, 89, 1, '2025-11-28 05:11:49'),
(41, 7, 0, 32.7, 89, 1, '2025-11-28 05:12:01'),
(42, 7, 0, 32.7, 89, 2, '2025-11-28 05:12:35'),
(43, 7, 0, 32.8, 90, 3, '2025-11-28 05:12:44'),
(44, 7, 0, 32.8, 90, 4, '2025-11-28 05:12:47'),
(45, 7, 0, 32.8, 90, 5, '2025-11-28 05:12:49'),
(46, 7, 0, 32.8, 90, 6, '2025-11-28 05:13:02'),
(47, 7, 0, 32.9, 89, 7, '2025-11-28 05:16:18'),
(48, 7, 40, 26, 55, 0, '2025-11-28 05:16:25'),
(49, 7, 0, 32.9, 89, 1, '2025-11-28 05:16:26'),
(50, 7, 0, 32.9, 89, 2, '2025-11-28 05:16:30'),
(51, 7, 40, 26, 55, 0, '2025-11-28 05:16:53'),
(52, 7, 0, 32.9, 89, 1, '2025-11-28 05:16:58'),
(53, 7, 0, 33.1, 89, 2, '2025-11-28 05:20:43'),
(54, 7, 0, 33.1, 89, 3, '2025-11-28 05:20:49'),
(55, 7, 0, 33.1, 89, 4, '2025-11-28 05:20:55'),
(56, 7, 0, 33.2, 89, 5, '2025-11-28 05:21:26'),
(57, 7, 0, 33.2, 88, 6, '2025-11-28 05:21:42'),
(58, 7, 0, 33.3, 87, 7, '2025-11-28 05:25:16'),
(59, 7, 0, 33.5, 86, 8, '2025-11-28 05:28:28'),
(60, 7, 0, 33.5, 86, 9, '2025-11-28 05:28:34'),
(61, 7, 0, 33.5, 86, 10, '2025-11-28 05:28:43'),
(62, 7, 40, 26, 55, 0, '2025-11-28 05:28:57'),
(63, 7, 0, 33.5, 86, 1, '2025-11-28 05:29:01'),
(64, 7, 0, 33.6, 85, 2, '2025-11-28 05:29:28'),
(65, 7, 0, 33.6, 86, 3, '2025-11-28 05:29:31'),
(66, 7, 0, 33.6, 86, 4, '2025-11-28 05:29:32'),
(67, 7, 0, 33.6, 86, 5, '2025-11-28 05:29:36'),
(68, 7, 0, 33.3, 86, 6, '2025-11-28 05:33:59'),
(69, 7, 40, 26, 55, 0, '2025-11-28 05:34:04'),
(70, 7, 0, 33.3, 86, 1, '2025-11-28 05:34:06'),
(71, 7, 0, 33.3, 86, 2, '2025-11-28 05:34:10'),
(72, 7, 0, 33.2, 86, 3, '2025-11-28 05:34:20'),
(73, 7, 0, 33.1, 86, 4, '2025-11-28 05:37:19'),
(74, 7, 0, 33.1, 86, 5, '2025-11-28 05:37:25'),
(75, 7, 40, 26, 55, 0, '2025-11-28 05:37:32'),
(76, 7, 0, 33.2, 86, 1, '2025-11-28 05:39:07'),
(77, 7, 0, 33.2, 86, 2, '2025-11-28 05:40:48'),
(78, 10, 0, 33.2, 86, 1, '2025-11-28 05:41:03'),
(79, 10, 0, 33.2, 86, 2, '2025-11-28 05:41:32'),
(80, 10, 0, 33.2, 86, 3, '2025-11-28 05:42:03'),
(81, 10, 0, 33.2, 86, 4, '2025-11-28 05:42:07'),
(82, 10, 0, 33.2, 86, 5, '2025-11-28 05:43:14'),
(83, 10, 0, 33.2, 86, 6, '2025-11-28 05:46:57'),
(84, 10, 0, 33.2, 86, 7, '2025-11-28 05:47:00'),
(85, 10, 55, 25, 65, 0, '2025-11-28 05:47:06'),
(86, 10, 0, 33.2, 86, 1, '2025-11-28 05:47:46'),
(87, 10, 0, 33.2, 86, 2, '2025-11-28 05:51:25'),
(88, 10, 0, 33.2, 86, 3, '2025-11-28 05:56:25'),
(89, 10, 0, 33.2, 86, 4, '2025-11-28 05:58:47'),
(90, 10, 0, 33.2, 86, 5, '2025-11-28 05:58:49'),
(91, 10, 0, 33.2, 86, 6, '2025-11-28 06:03:52'),
(92, 10, 55, 25, 65, 0, '2025-11-28 06:04:01'),
(93, 10, 0, 33.2, 86, 1, '2025-11-28 06:04:14'),
(94, 10, 0, 33.2, 86, 2, '2025-11-28 06:04:19'),
(95, 10, 0, 33.2, 86, 3, '2025-11-28 06:04:35'),
(96, 10, 0, 33.2, 86, 4, '2025-11-28 06:04:37'),
(97, 10, 0, 33.2, 86, 5, '2025-11-28 06:09:38'),
(98, 10, 55, 25, 65, 0, '2025-11-28 06:10:02'),
(99, 10, 0, 33.2, 86, 1, '2025-11-28 06:10:27'),
(100, 10, 0, 33.2, 86, 2, '2025-11-28 06:10:35'),
(101, 10, 0, 33.2, 86, 3, '2025-11-28 06:10:43'),
(102, 10, 0, 33.2, 86, 4, '2025-11-28 06:10:51'),
(103, 10, 0, 33.2, 86, 5, '2025-11-28 07:38:19'),
(104, 10, 0, 33.2, 86, 6, '2025-11-28 07:38:26'),
(105, 10, 0, 33.2, 86, 7, '2025-11-28 07:38:34'),
(106, 10, 0, 33.2, 86, 8, '2025-11-28 07:38:42'),
(107, 10, 0, 33.2, 86, 9, '2025-11-28 07:38:50'),
(108, 10, 0, 33.2, 86, 10, '2025-11-28 07:38:58'),
(109, 10, 0, 33.2, 86, 11, '2025-11-28 07:39:05'),
(110, 10, 0, 33.2, 86, 12, '2025-11-28 07:39:10'),
(111, 10, 0, 33.2, 86, 13, '2025-11-28 07:45:28'),
(112, 10, 0, 33.2, 86, 14, '2025-11-28 07:45:36'),
(113, 10, 0, 33.2, 86, 15, '2025-11-28 07:45:44'),
(114, 10, 0, 33.2, 86, 16, '2025-11-28 07:45:52'),
(115, 10, 0, 33.2, 86, 17, '2025-11-28 07:46:00'),
(116, 10, 0, 33.2, 86, 18, '2025-11-28 07:46:08'),
(117, 10, 0, 33.2, 86, 19, '2025-11-28 07:46:16'),
(118, 10, 0, 33.2, 86, 20, '2025-11-28 07:46:24'),
(119, 10, 0, 33.2, 86, 21, '2025-11-28 07:46:32'),
(120, 10, 0, 33.2, 86, 22, '2025-11-28 07:46:40'),
(121, 3, 0, 33.2, 86, 2, '2025-11-28 07:46:47'),
(122, 3, 0, 33.2, 86, 3, '2025-11-28 07:46:55'),
(123, 3, 0, 33.2, 86, 4, '2025-11-28 07:47:03'),
(124, 3, 0, 33.2, 86, 5, '2025-11-28 07:47:11'),
(125, 3, 0, 33.2, 86, 6, '2025-11-28 07:47:19'),
(126, 3, 0, 33.2, 86, 7, '2025-11-28 07:47:46'),
(127, 3, 0, 33.2, 86, 8, '2025-11-28 07:47:54'),
(128, 3, 0, 33.2, 86, 9, '2025-11-28 07:48:02'),
(129, 3, 0, 33.2, 86, 10, '2025-11-28 07:48:10'),
(130, 3, 0, 33.2, 86, 11, '2025-11-28 07:48:18'),
(131, 3, 40, 27.5, 55, 0, '2025-11-28 07:49:32'),
(132, 7, 40, 26, 55, 0, '2025-11-28 14:50:50'),
(133, 7, 40, 26, 55, 0, '2025-11-28 14:50:57'),
(134, 3, 40, 27.5, 55, 0, '2025-11-28 14:51:24'),
(135, 10, 55, 25, 65, 0, '2025-11-28 14:52:36'),
(136, 5, 60, 29.5, 80, 0, '2025-11-28 15:16:44'),
(137, 5, 60, 29.5, 80, 0, '2025-11-28 15:18:09');

-- --------------------------------------------------------

--
-- Table structure for table `sensors`
--

CREATE TABLE `sensors` (
  `id` int(11) NOT NULL,
  `sensor_name` varchar(100) NOT NULL,
  `sensor_type` enum('temperature','humidity','soil_moisture') NOT NULL,
  `location` varchar(100) NOT NULL,
  `arduino_pin` int(11) NOT NULL,
  `sensor_id` varchar(50) DEFAULT NULL,
  `calibration_offset` decimal(10,4) DEFAULT 0.0000,
  `alert_threshold_min` decimal(10,2) DEFAULT NULL,
  `alert_threshold_max` decimal(10,2) DEFAULT NULL,
  `status` enum('online','offline','error') DEFAULT 'offline',
  `last_reading_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sensors`
--

INSERT INTO `sensors` (`id`, `sensor_name`, `sensor_type`, `location`, `arduino_pin`, `sensor_id`, `calibration_offset`, `alert_threshold_min`, `alert_threshold_max`, `status`, `last_reading_at`, `created_at`, `updated_at`) VALUES
(30, 'Arduino Temperature Sensor', 'temperature', 'Farm Field', 2, NULL, 0.0000, 20.00, 28.00, 'online', '2025-11-29 14:31:09', '2025-10-23 07:49:27', '2025-11-29 14:31:09'),
(31, 'Arduino Humidity Sensor', 'humidity', 'Farm Field', 3, NULL, 0.0000, 60.00, 80.00, 'online', '2025-11-29 14:31:09', '2025-10-23 07:49:27', '2025-11-29 14:31:09'),
(32, 'Arduino Soil Moisture Sensor', 'soil_moisture', 'Farm Field', 10, NULL, 0.0000, 40.00, 60.00, 'online', '2025-11-29 14:31:09', '2025-10-23 07:49:27', '2025-11-29 14:31:09');

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
(61, 30, 24.80, '°C', '2025-10-23 03:49:27'),
(62, 30, 32.00, '°C', '2025-10-23 04:49:27'),
(63, 30, 18.00, '°C', '2025-10-23 05:49:27'),
(64, 30, 38.00, '°C', '2025-10-23 06:49:27'),
(65, 30, 25.50, '°C', '2025-10-23 09:33:44'),
(68, 31, 70.00, '%', '2025-10-23 03:49:27'),
(69, 31, 85.00, '%', '2025-10-23 04:49:27'),
(70, 31, 55.00, '%', '2025-10-23 05:49:27'),
(71, 31, 92.00, '%', '2025-10-23 06:49:27'),
(72, 31, 70.50, '%', '2025-10-23 09:33:44'),
(75, 32, 50.50, '%', '2025-10-23 03:49:27'),
(76, 32, 65.00, '%', '2025-10-23 04:49:27'),
(77, 32, 35.00, '%', '2025-10-23 05:49:27'),
(78, 32, 25.00, '%', '2025-10-23 06:49:27'),
(79, 32, 50.00, '%', '2025-10-23 09:33:44'),
(80, 31, 65.50, '%', '2025-10-23 09:33:54'),
(81, 32, 83.00, '%', '2025-10-23 09:33:54'),
(82, 30, 27.50, '°C', '2025-10-23 09:33:54'),
(83, 31, 65.50, '%', '2025-10-23 09:38:37'),
(84, 32, 79.00, '%', '2025-10-23 09:38:37'),
(85, 30, 27.50, '°C', '2025-10-23 09:38:37'),
(86, 31, 65.50, '%', '2025-10-23 09:38:43'),
(87, 32, 78.00, '%', '2025-10-23 09:38:43'),
(88, 30, 27.50, '°C', '2025-10-23 09:38:43'),
(89, 31, 65.50, '%', '2025-10-23 09:38:49'),
(90, 32, 79.00, '%', '2025-10-23 09:38:49'),
(91, 30, 27.50, '°C', '2025-10-23 09:38:49'),
(92, 31, 65.50, '%', '2025-10-23 09:38:55'),
(93, 32, 78.00, '%', '2025-10-23 09:38:55'),
(94, 30, 27.50, '°C', '2025-10-23 09:38:55'),
(95, 31, 65.50, '%', '2025-10-23 09:39:01'),
(96, 32, 78.00, '%', '2025-10-23 09:39:01'),
(97, 30, 27.50, '°C', '2025-10-23 09:39:01'),
(98, 31, 65.50, '%', '2025-10-23 09:39:07'),
(99, 32, 78.00, '%', '2025-10-23 09:39:08'),
(100, 30, 27.50, '°C', '2025-10-23 09:39:08'),
(101, 31, 65.50, '%', '2025-10-23 09:39:11'),
(102, 32, 78.00, '%', '2025-10-23 09:39:14'),
(103, 30, 27.50, '°C', '2025-10-23 09:39:14'),
(104, 31, 65.50, '%', '2025-10-23 09:39:17'),
(105, 32, 79.00, '%', '2025-10-23 09:39:20'),
(106, 30, 27.50, '°C', '2025-10-23 09:39:20'),
(107, 31, 65.50, '%', '2025-10-23 09:39:23'),
(108, 32, 78.00, '%', '2025-10-23 09:39:26'),
(109, 30, 27.50, '°C', '2025-10-23 09:39:26'),
(110, 31, 65.50, '%', '2025-10-23 09:39:29'),
(111, 32, 78.00, '%', '2025-10-23 09:39:32'),
(112, 30, 27.50, '°C', '2025-10-23 09:39:32'),
(113, 31, 65.50, '%', '2025-10-23 09:39:35'),
(114, 32, 78.00, '%', '2025-10-23 09:39:38'),
(115, 30, 27.50, '°C', '2025-10-23 09:39:38'),
(116, 31, 65.50, '%', '2025-10-23 09:39:41'),
(117, 32, 78.00, '%', '2025-10-23 09:39:44'),
(118, 30, 27.50, '°C', '2025-10-23 09:39:44'),
(119, 31, 65.50, '%', '2025-10-23 09:39:47'),
(120, 32, 78.00, '%', '2025-10-23 09:39:50'),
(121, 30, 27.50, '°C', '2025-10-23 09:39:50'),
(122, 31, 65.50, '%', '2025-10-23 09:39:54'),
(123, 32, 78.00, '%', '2025-10-23 09:39:54'),
(124, 30, 27.50, '°C', '2025-10-23 09:39:54'),
(125, 31, 65.50, '%', '2025-10-23 09:40:00'),
(126, 32, 79.00, '%', '2025-10-23 09:40:00'),
(127, 30, 27.50, '°C', '2025-10-23 09:40:00'),
(128, 31, 65.50, '%', '2025-10-23 09:40:06'),
(129, 32, 78.00, '%', '2025-10-23 09:40:06'),
(130, 30, 27.50, '°C', '2025-10-23 09:40:06'),
(131, 31, 65.50, '%', '2025-10-23 09:40:12'),
(132, 32, 79.00, '%', '2025-10-23 09:40:12'),
(133, 30, 27.50, '°C', '2025-10-23 09:40:12'),
(134, 31, 65.50, '%', '2025-10-23 09:40:18'),
(135, 32, 79.00, '%', '2025-10-23 09:40:18'),
(136, 30, 27.50, '°C', '2025-10-23 09:40:18'),
(137, 31, 65.50, '%', '2025-10-23 09:40:24'),
(138, 32, 79.00, '%', '2025-10-23 09:40:24'),
(139, 30, 27.50, '°C', '2025-10-23 09:40:24'),
(140, 31, 96.00, '%', '2025-10-23 13:06:38'),
(141, 32, 49.00, '%', '2025-10-23 13:06:38'),
(142, 30, 27.40, '°C', '2025-10-23 13:06:38'),
(143, 31, 97.50, '%', '2025-10-23 13:08:19'),
(144, 32, 72.00, '%', '2025-10-23 13:08:19'),
(145, 30, 27.40, '°C', '2025-10-23 13:08:19'),
(146, 31, 96.60, '%', '2025-10-23 13:08:45'),
(147, 32, 67.00, '%', '2025-10-23 13:08:45'),
(148, 30, 27.40, '°C', '2025-10-23 13:08:45'),
(149, 31, 96.30, '%', '2025-10-23 13:08:53'),
(150, 32, 66.00, '%', '2025-10-23 13:08:53'),
(151, 30, 27.40, '°C', '2025-10-23 13:08:53'),
(152, 31, 97.40, '%', '2025-10-23 13:09:28'),
(153, 32, 65.00, '%', '2025-10-23 13:09:28'),
(154, 30, 27.30, '°C', '2025-10-23 13:09:28'),
(155, 31, 97.10, '%', '2025-10-23 13:09:38'),
(156, 32, 64.00, '%', '2025-10-23 13:09:38'),
(157, 30, 27.30, '°C', '2025-10-23 13:09:38'),
(158, 31, 96.40, '%', '2025-10-23 13:10:53'),
(159, 32, 63.00, '%', '2025-10-23 13:10:53'),
(160, 30, 27.30, '°C', '2025-10-23 13:10:53'),
(161, 31, 96.40, '%', '2025-10-23 13:11:01'),
(162, 32, 63.00, '%', '2025-10-23 13:11:01'),
(163, 30, 27.30, '°C', '2025-10-23 13:11:01'),
(164, 31, 96.40, '%', '2025-10-23 13:36:14'),
(165, 32, 63.00, '%', '2025-10-23 13:36:14'),
(166, 30, 27.30, '°C', '2025-10-23 13:36:14'),
(167, 31, 96.40, '%', '2025-10-23 13:36:21'),
(168, 32, 63.00, '%', '2025-10-23 13:36:21'),
(169, 30, 27.30, '°C', '2025-10-23 13:36:21'),
(170, 31, 96.40, '%', '2025-10-23 13:36:27'),
(171, 32, 63.00, '%', '2025-10-23 13:36:27'),
(172, 30, 27.30, '°C', '2025-10-23 13:36:27'),
(173, 31, 96.40, '%', '2025-10-23 13:36:33'),
(174, 32, 63.00, '%', '2025-10-23 13:36:33'),
(175, 30, 27.30, '°C', '2025-10-23 13:36:33'),
(176, 31, 96.40, '%', '2025-10-23 13:36:39'),
(177, 32, 63.00, '%', '2025-10-23 13:36:39'),
(178, 30, 27.30, '°C', '2025-10-23 13:36:39'),
(179, 31, 96.40, '%', '2025-10-23 13:36:45'),
(180, 32, 63.00, '%', '2025-10-23 13:36:45'),
(181, 30, 27.30, '°C', '2025-10-23 13:36:45'),
(182, 31, 96.40, '%', '2025-10-23 13:36:51'),
(183, 32, 63.00, '%', '2025-10-23 13:36:52'),
(184, 30, 27.30, '°C', '2025-10-23 13:36:52'),
(185, 31, 96.40, '%', '2025-10-23 13:36:55'),
(186, 32, 63.00, '%', '2025-10-23 13:36:58'),
(187, 30, 27.30, '°C', '2025-10-23 13:36:58'),
(188, 31, 96.40, '%', '2025-10-23 13:37:01'),
(189, 32, 63.00, '%', '2025-10-23 13:37:04'),
(190, 30, 27.30, '°C', '2025-10-23 13:37:04'),
(191, 31, 96.40, '%', '2025-10-23 13:37:07'),
(192, 32, 63.00, '%', '2025-10-23 13:37:10'),
(193, 30, 27.30, '°C', '2025-10-23 13:37:10'),
(194, 31, 96.40, '%', '2025-10-23 13:37:13'),
(195, 32, 63.00, '%', '2025-10-23 13:37:16'),
(196, 30, 27.30, '°C', '2025-10-23 13:37:16'),
(197, 31, 96.40, '%', '2025-10-23 13:37:19'),
(198, 32, 63.00, '%', '2025-10-23 13:37:22'),
(199, 30, 27.30, '°C', '2025-10-23 13:37:22'),
(200, 31, 96.40, '%', '2025-10-23 13:37:25'),
(201, 30, 27.30, '°C', '2025-10-23 13:37:26'),
(202, 31, 96.40, '%', '2025-10-23 13:37:29'),
(203, 32, 63.00, '%', '2025-10-23 13:37:29'),
(204, 30, 27.30, '°C', '2025-10-23 13:37:32'),
(205, 31, 96.40, '%', '2025-10-23 13:37:35'),
(206, 32, 63.00, '%', '2025-10-23 13:37:35'),
(207, 30, 27.30, '°C', '2025-10-23 13:37:38'),
(208, 31, 96.40, '%', '2025-10-23 13:37:41'),
(209, 32, 63.00, '%', '2025-10-23 13:37:41'),
(210, 30, 27.30, '°C', '2025-10-23 13:37:44'),
(211, 31, 96.40, '%', '2025-10-23 13:37:47'),
(212, 32, 63.00, '%', '2025-10-23 13:37:47'),
(213, 30, 27.30, '°C', '2025-10-23 13:37:50'),
(214, 31, 96.40, '%', '2025-10-23 13:37:53'),
(215, 32, 63.00, '%', '2025-10-23 13:37:53'),
(216, 31, 96.40, '%', '2025-10-23 13:37:57'),
(217, 32, 63.00, '%', '2025-10-23 13:37:57'),
(218, 30, 27.30, '°C', '2025-10-23 13:37:57'),
(219, 31, 96.40, '%', '2025-10-23 13:38:03'),
(220, 32, 63.00, '%', '2025-10-23 13:38:03'),
(221, 30, 27.30, '°C', '2025-10-23 13:38:03'),
(222, 31, 96.40, '%', '2025-10-23 13:38:09'),
(223, 32, 63.00, '%', '2025-10-23 13:38:09'),
(224, 30, 27.30, '°C', '2025-10-23 13:38:09'),
(225, 31, 96.40, '%', '2025-10-23 13:38:15'),
(226, 32, 63.00, '%', '2025-10-23 13:38:15'),
(227, 30, 27.30, '°C', '2025-10-23 13:38:15'),
(228, 31, 96.40, '%', '2025-10-23 13:38:21'),
(229, 32, 63.00, '%', '2025-10-23 13:38:21'),
(230, 30, 27.30, '°C', '2025-10-23 13:38:21'),
(231, 31, 96.40, '%', '2025-10-23 13:38:28'),
(232, 32, 63.00, '%', '2025-10-23 13:38:28'),
(233, 30, 27.30, '°C', '2025-10-23 13:38:28'),
(234, 31, 96.40, '%', '2025-10-23 13:38:34'),
(235, 32, 63.00, '%', '2025-10-23 13:38:34'),
(236, 30, 27.30, '°C', '2025-10-23 13:38:34'),
(237, 31, 96.40, '%', '2025-10-23 13:38:40'),
(238, 32, 63.00, '%', '2025-10-23 13:38:40'),
(239, 30, 27.30, '°C', '2025-10-23 13:38:40'),
(240, 31, 96.40, '%', '2025-10-23 13:38:46'),
(241, 32, 63.00, '%', '2025-10-23 13:38:46'),
(242, 30, 27.30, '°C', '2025-10-23 13:38:46'),
(243, 31, 96.40, '%', '2025-10-23 13:38:52'),
(244, 32, 63.00, '%', '2025-10-23 13:38:52'),
(245, 30, 27.30, '°C', '2025-10-23 13:38:52'),
(246, 31, 96.40, '%', '2025-10-23 13:38:59'),
(247, 32, 63.00, '%', '2025-10-23 13:38:59'),
(248, 30, 27.30, '°C', '2025-10-23 13:38:59'),
(249, 31, 96.40, '%', '2025-10-23 13:39:05'),
(250, 32, 63.00, '%', '2025-10-23 13:39:05'),
(251, 30, 27.30, '°C', '2025-10-23 13:39:05'),
(252, 31, 96.40, '%', '2025-10-23 13:39:11'),
(253, 32, 63.00, '%', '2025-10-23 13:39:11'),
(254, 30, 27.30, '°C', '2025-10-23 13:39:11'),
(255, 31, 96.40, '%', '2025-10-23 13:39:17'),
(256, 32, 63.00, '%', '2025-10-23 13:39:17'),
(257, 30, 27.30, '°C', '2025-10-23 13:39:17'),
(258, 31, 96.40, '%', '2025-10-23 13:39:23'),
(259, 32, 63.00, '%', '2025-10-23 13:39:23'),
(260, 30, 27.30, '°C', '2025-10-23 13:39:23'),
(261, 31, 96.40, '%', '2025-10-23 13:39:27'),
(262, 32, 63.00, '%', '2025-10-23 13:39:27'),
(263, 30, 27.30, '°C', '2025-10-23 13:39:27'),
(264, 31, 96.40, '%', '2025-10-23 13:39:33'),
(265, 32, 63.00, '%', '2025-10-23 13:39:33'),
(266, 30, 27.30, '°C', '2025-10-23 13:39:33'),
(267, 31, 96.40, '%', '2025-10-23 13:39:39'),
(268, 32, 63.00, '%', '2025-10-23 13:39:39'),
(269, 30, 27.30, '°C', '2025-10-23 13:39:39'),
(270, 31, 96.40, '%', '2025-10-23 13:40:15'),
(271, 32, 63.00, '%', '2025-10-23 13:40:15'),
(272, 30, 27.30, '°C', '2025-10-23 13:40:15'),
(273, 31, 96.40, '%', '2025-10-23 13:40:42'),
(274, 32, 63.00, '%', '2025-10-23 13:40:42'),
(275, 30, 27.30, '°C', '2025-10-23 13:40:42'),
(276, 31, 96.40, '%', '2025-10-23 13:40:58'),
(277, 32, 63.00, '%', '2025-10-23 13:40:58'),
(278, 30, 27.30, '°C', '2025-10-23 13:40:58'),
(279, 31, 96.40, '%', '2025-10-23 13:41:18'),
(280, 32, 63.00, '%', '2025-10-23 13:41:18'),
(281, 30, 27.30, '°C', '2025-10-23 13:41:18'),
(282, 31, 96.40, '%', '2025-10-23 13:58:06'),
(283, 32, 63.00, '%', '2025-10-23 13:58:06'),
(284, 30, 27.30, '°C', '2025-10-23 13:58:06'),
(285, 31, 96.40, '%', '2025-10-23 13:59:20'),
(286, 32, 63.00, '%', '2025-10-23 13:59:20'),
(287, 30, 27.30, '°C', '2025-10-23 13:59:20'),
(288, 31, 96.40, '%', '2025-10-23 14:02:04'),
(289, 32, 63.00, '%', '2025-10-23 14:02:04'),
(290, 30, 27.30, '°C', '2025-10-23 14:02:04'),
(291, 31, 96.40, '%', '2025-10-23 14:10:38'),
(292, 32, 63.00, '%', '2025-10-23 14:10:38'),
(293, 30, 27.30, '°C', '2025-10-23 14:10:38'),
(294, 31, 96.40, '%', '2025-10-23 14:10:44'),
(295, 32, 63.00, '%', '2025-10-23 14:10:44'),
(296, 30, 27.30, '°C', '2025-10-23 14:10:44'),
(297, 31, 96.40, '%', '2025-10-23 14:10:50'),
(298, 32, 63.00, '%', '2025-10-23 14:10:50'),
(299, 30, 27.30, '°C', '2025-10-23 14:10:50'),
(300, 31, 96.40, '%', '2025-10-23 14:10:57'),
(301, 32, 63.00, '%', '2025-10-23 14:10:57'),
(302, 30, 27.30, '°C', '2025-10-23 14:10:57'),
(303, 31, 96.40, '%', '2025-10-23 14:11:03'),
(304, 32, 63.00, '%', '2025-10-23 14:11:03'),
(305, 30, 27.30, '°C', '2025-10-23 14:11:03'),
(306, 31, 96.40, '%', '2025-10-23 14:11:34'),
(307, 32, 63.00, '%', '2025-10-23 14:11:34'),
(308, 30, 27.30, '°C', '2025-10-23 14:11:35'),
(309, 31, 96.40, '%', '2025-10-23 14:13:04'),
(310, 32, 63.00, '%', '2025-10-23 14:13:04'),
(311, 30, 27.30, '°C', '2025-10-23 14:13:04'),
(312, 31, 96.40, '%', '2025-10-23 14:20:56'),
(313, 32, 63.00, '%', '2025-10-23 14:20:56'),
(314, 30, 27.30, '°C', '2025-10-23 14:20:56'),
(315, 31, 96.40, '%', '2025-10-23 14:21:13'),
(316, 32, 63.00, '%', '2025-10-23 14:21:13'),
(317, 30, 27.30, '°C', '2025-10-23 14:21:13'),
(318, 31, 96.40, '%', '2025-10-23 14:21:17'),
(319, 32, 63.00, '%', '2025-10-23 14:21:17'),
(320, 30, 27.30, '°C', '2025-10-23 14:21:17'),
(321, 31, 86.60, '%', '2025-10-24 00:41:20'),
(322, 32, 0.00, '%', '2025-10-24 00:41:20'),
(323, 30, 31.90, '°C', '2025-10-24 00:41:20'),
(324, 31, 86.80, '%', '2025-10-24 00:41:26'),
(325, 32, 0.00, '%', '2025-10-24 00:41:26'),
(326, 30, 31.90, '°C', '2025-10-24 00:41:26'),
(327, 31, 86.80, '%', '2025-10-24 00:41:32'),
(328, 32, 0.00, '%', '2025-10-24 00:41:32'),
(329, 30, 31.90, '°C', '2025-10-24 00:41:32'),
(330, 31, 86.80, '%', '2025-10-24 00:41:38'),
(331, 32, 0.00, '%', '2025-10-24 00:41:38'),
(332, 30, 31.90, '°C', '2025-10-24 00:41:38'),
(333, 31, 86.90, '%', '2025-10-24 00:41:44'),
(334, 32, 0.00, '%', '2025-10-24 00:41:44'),
(335, 30, 31.90, '°C', '2025-10-24 00:41:44'),
(336, 31, 87.70, '%', '2025-10-24 00:42:31'),
(337, 32, 0.00, '%', '2025-10-24 00:42:31'),
(338, 30, 31.80, '°C', '2025-10-24 00:42:31'),
(339, 31, 87.80, '%', '2025-10-24 00:42:35'),
(340, 32, 0.00, '%', '2025-10-24 00:42:35'),
(341, 30, 31.80, '°C', '2025-10-24 00:42:35'),
(342, 31, 87.80, '%', '2025-10-24 00:42:44'),
(343, 32, 100.00, '%', '2025-10-24 00:42:44'),
(344, 30, 31.80, '°C', '2025-10-24 00:42:44'),
(345, 31, 89.60, '%', '2025-10-24 08:37:00'),
(346, 32, 91.00, '%', '2025-10-24 08:37:00'),
(347, 30, 30.60, '°C', '2025-10-24 08:37:01'),
(348, 31, 90.10, '%', '2025-10-24 08:37:39'),
(349, 32, 97.00, '%', '2025-10-24 08:37:40'),
(350, 30, 30.40, '°C', '2025-10-24 08:37:40'),
(351, 31, 90.30, '%', '2025-10-24 08:37:50'),
(352, 32, 96.00, '%', '2025-10-24 08:37:50'),
(353, 30, 30.40, '°C', '2025-10-24 08:37:50'),
(354, 31, 100.00, '%', '2025-10-24 08:38:06'),
(355, 32, 0.00, '%', '2025-10-24 08:38:06'),
(356, 30, 150.00, '°C', '2025-10-24 08:38:06'),
(357, 31, 100.00, '%', '2025-10-24 08:38:16'),
(358, 32, 0.00, '%', '2025-10-24 08:38:16'),
(359, 30, 150.00, '°C', '2025-10-24 08:38:16'),
(360, 31, 62.90, '%', '2025-10-24 08:38:46'),
(361, 32, 20.00, '%', '2025-10-24 08:38:46'),
(362, 30, 24.90, '°C', '2025-10-24 08:38:46'),
(363, 31, 62.90, '%', '2025-10-24 08:39:57'),
(364, 32, 0.00, '%', '2025-10-24 08:39:57'),
(365, 30, 24.90, '°C', '2025-10-24 08:39:57'),
(366, 31, 62.90, '%', '2025-10-24 08:40:07'),
(367, 32, 0.00, '%', '2025-10-24 08:40:07'),
(368, 30, 24.90, '°C', '2025-10-24 08:40:07'),
(369, 31, 62.90, '%', '2025-10-24 08:41:18'),
(370, 32, 30.00, '%', '2025-10-24 08:41:18'),
(371, 30, 24.90, '°C', '2025-10-24 08:41:18'),
(372, 31, 62.90, '%', '2025-10-24 08:42:05'),
(373, 32, 100.00, '%', '2025-10-24 08:42:05'),
(374, 30, 24.90, '°C', '2025-10-24 08:42:05'),
(375, 31, 100.00, '%', '2025-11-27 13:15:53'),
(376, 32, 0.00, '%', '2025-11-27 13:15:53'),
(377, 30, 29.40, '°C', '2025-11-27 13:15:53'),
(378, 31, 100.00, '%', '2025-11-27 13:15:59'),
(379, 32, 0.00, '%', '2025-11-27 13:15:59'),
(380, 30, 29.40, '°C', '2025-11-27 13:15:59'),
(381, 31, 100.00, '%', '2025-11-27 15:05:04'),
(382, 32, 0.00, '%', '2025-11-27 15:05:04'),
(383, 30, 29.70, '°C', '2025-11-27 15:05:04'),
(384, 31, 100.00, '%', '2025-11-27 15:15:46'),
(385, 32, 0.00, '%', '2025-11-27 15:15:46'),
(386, 30, 29.80, '°C', '2025-11-27 15:15:46'),
(387, 31, 100.00, '%', '2025-11-27 15:16:02'),
(388, 32, 0.00, '%', '2025-11-27 15:16:02'),
(389, 30, 29.80, '°C', '2025-11-27 15:16:02'),
(390, 31, 100.00, '%', '2025-11-27 15:17:03'),
(391, 32, 0.00, '%', '2025-11-27 15:17:03'),
(392, 30, 29.80, '°C', '2025-11-27 15:17:03'),
(393, 31, 100.00, '%', '2025-11-27 15:17:34'),
(394, 32, 0.00, '%', '2025-11-27 15:17:34'),
(395, 30, 29.80, '°C', '2025-11-27 15:17:34'),
(396, 31, 100.00, '%', '2025-11-27 15:24:08'),
(397, 32, 0.00, '%', '2025-11-27 15:24:08'),
(398, 30, 29.80, '°C', '2025-11-27 15:24:08'),
(399, 31, 100.00, '%', '2025-11-27 15:24:28'),
(400, 32, 0.00, '%', '2025-11-27 15:24:28'),
(401, 30, 29.80, '°C', '2025-11-27 15:24:28'),
(402, 31, 100.00, '%', '2025-11-27 15:26:49'),
(403, 32, 0.00, '%', '2025-11-27 15:26:49'),
(404, 30, 29.80, '°C', '2025-11-27 15:26:49'),
(405, 31, 100.00, '%', '2025-11-27 15:36:28'),
(406, 32, 0.00, '%', '2025-11-27 15:36:28'),
(407, 30, 29.60, '°C', '2025-11-27 15:36:28'),
(408, 31, 100.00, '%', '2025-11-27 15:48:54'),
(409, 32, 0.00, '%', '2025-11-27 15:48:54'),
(410, 30, 29.50, '°C', '2025-11-27 15:48:54'),
(411, 31, 100.00, '%', '2025-11-27 15:49:20'),
(412, 32, 0.00, '%', '2025-11-27 15:49:21'),
(413, 30, 29.50, '°C', '2025-11-27 15:49:21'),
(414, 31, 100.00, '%', '2025-11-27 15:53:16'),
(415, 32, 0.00, '%', '2025-11-27 15:53:16'),
(416, 30, 29.50, '°C', '2025-11-27 15:53:16'),
(417, 31, 100.00, '%', '2025-11-27 16:00:21'),
(418, 32, 0.00, '%', '2025-11-27 16:00:21'),
(419, 30, 29.40, '°C', '2025-11-27 16:00:21'),
(420, 31, 100.00, '%', '2025-11-27 16:06:47'),
(421, 32, 0.00, '%', '2025-11-27 16:06:47'),
(422, 30, 29.50, '°C', '2025-11-27 16:06:47'),
(423, 31, 100.00, '%', '2025-11-27 16:07:35'),
(424, 32, 0.00, '%', '2025-11-27 16:07:35'),
(425, 30, 29.50, '°C', '2025-11-27 16:07:35'),
(426, 31, 100.00, '%', '2025-11-27 16:12:41'),
(427, 32, 0.00, '%', '2025-11-27 16:12:41'),
(428, 30, 29.50, '°C', '2025-11-27 16:12:41'),
(429, 31, 100.00, '%', '2025-11-27 16:12:59'),
(430, 32, 0.00, '%', '2025-11-27 16:12:59'),
(431, 30, 29.50, '°C', '2025-11-27 16:12:59'),
(432, 31, 88.90, '%', '2025-11-28 05:11:21'),
(433, 32, 0.00, '%', '2025-11-28 05:11:21'),
(434, 30, 32.60, '°C', '2025-11-28 05:11:21'),
(435, 31, 88.70, '%', '2025-11-28 05:17:01'),
(436, 32, 0.00, '%', '2025-11-28 05:17:01'),
(437, 30, 32.90, '°C', '2025-11-28 05:17:01'),
(438, 31, 86.20, '%', '2025-11-28 05:27:05'),
(439, 32, 0.00, '%', '2025-11-28 05:27:05'),
(440, 30, 33.40, '°C', '2025-11-28 05:27:05'),
(441, 31, 85.80, '%', '2025-11-28 05:38:56'),
(442, 32, 0.00, '%', '2025-11-28 05:38:56'),
(443, 30, 33.20, '°C', '2025-11-28 05:38:56'),
(444, 31, 85.80, '%', '2025-11-28 06:04:12'),
(445, 32, 0.00, '%', '2025-11-28 06:04:12'),
(446, 30, 33.20, '°C', '2025-11-28 06:04:12'),
(447, 31, 85.80, '%', '2025-11-28 06:10:10'),
(448, 32, 0.00, '%', '2025-11-28 06:10:10'),
(449, 30, 33.20, '°C', '2025-11-28 06:10:10'),
(450, 31, 85.80, '%', '2025-11-28 07:39:17'),
(451, 32, 0.00, '%', '2025-11-28 07:39:17'),
(452, 30, 33.20, '°C', '2025-11-28 07:39:17'),
(453, 31, 85.80, '%', '2025-11-28 07:47:42'),
(454, 32, 0.00, '%', '2025-11-28 07:47:42'),
(455, 30, 33.20, '°C', '2025-11-28 07:47:42'),
(456, 31, 85.80, '%', '2025-11-28 07:48:28'),
(457, 32, 0.00, '%', '2025-11-28 07:48:28'),
(458, 30, 33.20, '°C', '2025-11-28 07:48:28'),
(459, 31, 85.80, '%', '2025-11-28 07:49:04'),
(460, 32, 0.00, '%', '2025-11-28 07:49:04'),
(461, 30, 33.20, '°C', '2025-11-28 07:49:04'),
(462, 31, 85.80, '%', '2025-11-28 07:49:12'),
(463, 32, 0.00, '%', '2025-11-28 07:49:12'),
(464, 30, 33.20, '°C', '2025-11-28 07:49:12'),
(465, 31, 85.80, '%', '2025-11-28 07:54:40'),
(466, 32, 0.00, '%', '2025-11-28 07:54:40'),
(467, 30, 33.20, '°C', '2025-11-28 07:54:40'),
(468, 31, 85.80, '%', '2025-11-28 08:23:36'),
(469, 32, 0.00, '%', '2025-11-28 08:23:36'),
(470, 30, 33.20, '°C', '2025-11-28 08:23:36'),
(471, 31, 85.80, '%', '2025-11-28 08:28:38'),
(472, 32, 0.00, '%', '2025-11-28 08:28:38'),
(473, 30, 33.20, '°C', '2025-11-28 08:28:38'),
(474, 31, 99.90, '%', '2025-11-29 14:25:33'),
(475, 32, 0.00, '%', '2025-11-29 14:25:33'),
(476, 30, 28.50, '°C', '2025-11-29 14:25:33'),
(477, 31, 99.90, '%', '2025-11-29 14:25:37'),
(478, 32, 0.00, '%', '2025-11-29 14:25:37'),
(479, 30, 28.50, '°C', '2025-11-29 14:25:37'),
(480, 31, 99.90, '%', '2025-11-29 14:25:41'),
(481, 32, 0.00, '%', '2025-11-29 14:25:41'),
(482, 30, 28.50, '°C', '2025-11-29 14:25:41'),
(483, 31, 100.00, '%', '2025-11-29 14:25:45'),
(484, 32, 0.00, '%', '2025-11-29 14:25:45'),
(485, 30, 28.50, '°C', '2025-11-29 14:25:45'),
(486, 31, 100.00, '%', '2025-11-29 14:25:49'),
(487, 32, 0.00, '%', '2025-11-29 14:25:49'),
(488, 30, 28.50, '°C', '2025-11-29 14:25:49'),
(489, 31, 100.00, '%', '2025-11-29 14:25:53'),
(490, 32, 0.00, '%', '2025-11-29 14:25:53'),
(491, 30, 28.50, '°C', '2025-11-29 14:25:53'),
(492, 31, 100.00, '%', '2025-11-29 14:25:58'),
(493, 32, 0.00, '%', '2025-11-29 14:25:58'),
(494, 30, 28.50, '°C', '2025-11-29 14:25:58'),
(495, 31, 100.00, '%', '2025-11-29 14:26:02'),
(496, 32, 0.00, '%', '2025-11-29 14:26:02'),
(497, 30, 28.50, '°C', '2025-11-29 14:26:02'),
(498, 31, 100.00, '%', '2025-11-29 14:26:06'),
(499, 32, 0.00, '%', '2025-11-29 14:26:06'),
(500, 30, 28.50, '°C', '2025-11-29 14:26:06'),
(501, 31, 100.00, '%', '2025-11-29 14:26:10'),
(502, 32, 0.00, '%', '2025-11-29 14:26:10'),
(503, 30, 28.50, '°C', '2025-11-29 14:26:10'),
(504, 31, 100.00, '%', '2025-11-29 14:26:14'),
(505, 32, 0.00, '%', '2025-11-29 14:26:14'),
(506, 30, 28.50, '°C', '2025-11-29 14:26:14'),
(507, 31, 100.00, '%', '2025-11-29 14:26:18'),
(508, 32, 0.00, '%', '2025-11-29 14:26:18'),
(509, 30, 28.50, '°C', '2025-11-29 14:26:18'),
(510, 31, 100.00, '%', '2025-11-29 14:26:22'),
(511, 32, 0.00, '%', '2025-11-29 14:26:22'),
(512, 30, 28.50, '°C', '2025-11-29 14:26:22'),
(513, 31, 100.00, '%', '2025-11-29 14:26:26'),
(514, 32, 0.00, '%', '2025-11-29 14:26:26'),
(515, 30, 28.50, '°C', '2025-11-29 14:26:26'),
(516, 31, 100.00, '%', '2025-11-29 14:26:30'),
(517, 32, 0.00, '%', '2025-11-29 14:26:30'),
(518, 30, 28.50, '°C', '2025-11-29 14:26:30'),
(519, 31, 100.00, '%', '2025-11-29 14:26:34'),
(520, 32, 100.00, '%', '2025-11-29 14:26:34'),
(521, 30, 28.50, '°C', '2025-11-29 14:26:34'),
(522, 31, 100.00, '%', '2025-11-29 14:26:38'),
(523, 32, 100.00, '%', '2025-11-29 14:26:38'),
(524, 30, 28.60, '°C', '2025-11-29 14:26:38'),
(525, 31, 100.00, '%', '2025-11-29 14:26:42'),
(526, 32, 0.00, '%', '2025-11-29 14:26:42'),
(527, 30, 28.60, '°C', '2025-11-29 14:26:42'),
(528, 31, 100.00, '%', '2025-11-29 14:26:46'),
(529, 32, 0.00, '%', '2025-11-29 14:26:46'),
(530, 30, 28.60, '°C', '2025-11-29 14:26:46'),
(531, 31, 100.00, '%', '2025-11-29 14:26:50'),
(532, 32, 0.00, '%', '2025-11-29 14:26:50'),
(533, 30, 28.50, '°C', '2025-11-29 14:26:50'),
(534, 31, 100.00, '%', '2025-11-29 14:26:54'),
(535, 32, 0.00, '%', '2025-11-29 14:26:54'),
(536, 30, 28.60, '°C', '2025-11-29 14:26:54'),
(537, 31, 99.90, '%', '2025-11-29 14:26:58'),
(538, 32, 0.00, '%', '2025-11-29 14:26:58'),
(539, 30, 28.60, '°C', '2025-11-29 14:26:58'),
(540, 31, 99.90, '%', '2025-11-29 14:27:02'),
(541, 32, 0.00, '%', '2025-11-29 14:27:02'),
(542, 30, 28.60, '°C', '2025-11-29 14:27:02'),
(543, 31, 99.90, '%', '2025-11-29 14:27:06'),
(544, 32, 0.00, '%', '2025-11-29 14:27:06'),
(545, 30, 28.60, '°C', '2025-11-29 14:27:06'),
(546, 31, 99.90, '%', '2025-11-29 14:27:10'),
(547, 32, 0.00, '%', '2025-11-29 14:27:10'),
(548, 30, 28.60, '°C', '2025-11-29 14:27:10'),
(549, 31, 99.90, '%', '2025-11-29 14:27:14'),
(550, 32, 0.00, '%', '2025-11-29 14:27:14'),
(551, 30, 28.60, '°C', '2025-11-29 14:27:14'),
(552, 31, 100.00, '%', '2025-11-29 14:27:18'),
(553, 32, 0.00, '%', '2025-11-29 14:27:18'),
(554, 30, 28.50, '°C', '2025-11-29 14:27:18'),
(555, 31, 100.00, '%', '2025-11-29 14:27:22'),
(556, 32, 0.00, '%', '2025-11-29 14:27:22'),
(557, 30, 28.50, '°C', '2025-11-29 14:27:22'),
(558, 31, 100.00, '%', '2025-11-29 14:27:26'),
(559, 32, 0.00, '%', '2025-11-29 14:27:26'),
(560, 30, 28.50, '°C', '2025-11-29 14:27:26'),
(561, 31, 99.90, '%', '2025-11-29 14:27:31'),
(562, 32, 0.00, '%', '2025-11-29 14:27:31'),
(563, 30, 28.50, '°C', '2025-11-29 14:27:31'),
(564, 31, 99.90, '%', '2025-11-29 14:27:35'),
(565, 32, 0.00, '%', '2025-11-29 14:27:35'),
(566, 30, 28.50, '°C', '2025-11-29 14:27:35'),
(567, 31, 100.00, '%', '2025-11-29 14:27:39'),
(568, 32, 0.00, '%', '2025-11-29 14:27:39'),
(569, 30, 28.50, '°C', '2025-11-29 14:27:39'),
(570, 31, 100.00, '%', '2025-11-29 14:27:43'),
(571, 32, 0.00, '%', '2025-11-29 14:27:43'),
(572, 30, 28.50, '°C', '2025-11-29 14:27:43'),
(573, 31, 100.00, '%', '2025-11-29 14:27:47'),
(574, 32, 0.00, '%', '2025-11-29 14:27:47'),
(575, 30, 28.50, '°C', '2025-11-29 14:27:47'),
(576, 31, 100.00, '%', '2025-11-29 14:27:51'),
(577, 32, 0.00, '%', '2025-11-29 14:27:51'),
(578, 30, 28.50, '°C', '2025-11-29 14:27:51'),
(579, 31, 100.00, '%', '2025-11-29 14:27:55'),
(580, 32, 0.00, '%', '2025-11-29 14:27:55'),
(581, 30, 28.50, '°C', '2025-11-29 14:27:55'),
(582, 31, 100.00, '%', '2025-11-29 14:27:59'),
(583, 32, 0.00, '%', '2025-11-29 14:27:59'),
(584, 30, 28.50, '°C', '2025-11-29 14:27:59'),
(585, 31, 100.00, '%', '2025-11-29 14:28:03'),
(586, 32, 0.00, '%', '2025-11-29 14:28:03'),
(587, 30, 28.50, '°C', '2025-11-29 14:28:03'),
(588, 31, 100.00, '%', '2025-11-29 14:28:07'),
(589, 32, 0.00, '%', '2025-11-29 14:28:07'),
(590, 30, 28.50, '°C', '2025-11-29 14:28:07'),
(591, 31, 100.00, '%', '2025-11-29 14:28:11'),
(592, 32, 0.00, '%', '2025-11-29 14:28:11'),
(593, 30, 28.50, '°C', '2025-11-29 14:28:11'),
(594, 31, 100.00, '%', '2025-11-29 14:28:15'),
(595, 32, 0.00, '%', '2025-11-29 14:28:15'),
(596, 30, 28.50, '°C', '2025-11-29 14:28:15'),
(597, 31, 100.00, '%', '2025-11-29 14:28:19'),
(598, 32, 0.00, '%', '2025-11-29 14:28:19'),
(599, 30, 28.50, '°C', '2025-11-29 14:28:19'),
(600, 31, 100.00, '%', '2025-11-29 14:28:23'),
(601, 32, 0.00, '%', '2025-11-29 14:28:23'),
(602, 30, 28.50, '°C', '2025-11-29 14:28:23'),
(603, 31, 100.00, '%', '2025-11-29 14:28:27'),
(604, 32, 0.00, '%', '2025-11-29 14:28:27'),
(605, 30, 28.50, '°C', '2025-11-29 14:28:27'),
(606, 31, 100.00, '%', '2025-11-29 14:28:31'),
(607, 32, 0.00, '%', '2025-11-29 14:28:31'),
(608, 30, 28.50, '°C', '2025-11-29 14:28:31'),
(609, 31, 100.00, '%', '2025-11-29 14:28:35'),
(610, 32, 0.00, '%', '2025-11-29 14:28:35'),
(611, 30, 28.50, '°C', '2025-11-29 14:28:35'),
(612, 31, 99.60, '%', '2025-11-29 14:28:39'),
(613, 32, 0.00, '%', '2025-11-29 14:28:39'),
(614, 30, 28.50, '°C', '2025-11-29 14:28:39'),
(615, 31, 99.50, '%', '2025-11-29 14:28:43'),
(616, 32, 0.00, '%', '2025-11-29 14:28:43'),
(617, 30, 28.50, '°C', '2025-11-29 14:28:43'),
(618, 31, 100.00, '%', '2025-11-29 14:28:47'),
(619, 32, 0.00, '%', '2025-11-29 14:28:47'),
(620, 30, 28.50, '°C', '2025-11-29 14:28:47'),
(621, 31, 100.00, '%', '2025-11-29 14:28:51'),
(622, 32, 0.00, '%', '2025-11-29 14:28:51'),
(623, 30, 28.50, '°C', '2025-11-29 14:28:51'),
(624, 31, 100.00, '%', '2025-11-29 14:28:55'),
(625, 32, 0.00, '%', '2025-11-29 14:28:56'),
(626, 30, 28.50, '°C', '2025-11-29 14:28:56'),
(627, 31, 100.00, '%', '2025-11-29 14:29:00'),
(628, 32, 0.00, '%', '2025-11-29 14:29:00'),
(629, 30, 28.50, '°C', '2025-11-29 14:29:00'),
(630, 31, 100.00, '%', '2025-11-29 14:29:04'),
(631, 32, 0.00, '%', '2025-11-29 14:29:04'),
(632, 30, 28.50, '°C', '2025-11-29 14:29:04'),
(633, 31, 100.00, '%', '2025-11-29 14:29:08'),
(634, 32, 0.00, '%', '2025-11-29 14:29:08'),
(635, 30, 28.50, '°C', '2025-11-29 14:29:08'),
(636, 31, 100.00, '%', '2025-11-29 14:29:12'),
(637, 32, 0.00, '%', '2025-11-29 14:29:12'),
(638, 30, 28.50, '°C', '2025-11-29 14:29:12'),
(639, 31, 100.00, '%', '2025-11-29 14:29:16'),
(640, 32, 0.00, '%', '2025-11-29 14:29:16'),
(641, 30, 28.50, '°C', '2025-11-29 14:29:16'),
(642, 31, 100.00, '%', '2025-11-29 14:29:20'),
(643, 32, 0.00, '%', '2025-11-29 14:29:20'),
(644, 30, 28.60, '°C', '2025-11-29 14:29:20'),
(645, 31, 99.90, '%', '2025-11-29 14:29:24'),
(646, 32, 0.00, '%', '2025-11-29 14:29:24'),
(647, 30, 28.60, '°C', '2025-11-29 14:29:24'),
(648, 31, 99.90, '%', '2025-11-29 14:29:28'),
(649, 32, 0.00, '%', '2025-11-29 14:29:28'),
(650, 30, 28.60, '°C', '2025-11-29 14:29:28'),
(651, 31, 99.90, '%', '2025-11-29 14:29:32'),
(652, 32, 0.00, '%', '2025-11-29 14:29:32'),
(653, 30, 28.60, '°C', '2025-11-29 14:29:32'),
(654, 31, 99.90, '%', '2025-11-29 14:29:36'),
(655, 32, 0.00, '%', '2025-11-29 14:29:36'),
(656, 30, 28.60, '°C', '2025-11-29 14:29:36'),
(657, 31, 99.90, '%', '2025-11-29 14:29:40'),
(658, 32, 0.00, '%', '2025-11-29 14:29:40'),
(659, 30, 28.60, '°C', '2025-11-29 14:29:40'),
(660, 31, 100.00, '%', '2025-11-29 14:29:44'),
(661, 32, 0.00, '%', '2025-11-29 14:29:44'),
(662, 30, 28.60, '°C', '2025-11-29 14:29:44'),
(663, 31, 100.00, '%', '2025-11-29 14:29:48'),
(664, 32, 0.00, '%', '2025-11-29 14:29:48'),
(665, 30, 28.60, '°C', '2025-11-29 14:29:48'),
(666, 31, 100.00, '%', '2025-11-29 14:29:52'),
(667, 32, 0.00, '%', '2025-11-29 14:29:52'),
(668, 30, 28.50, '°C', '2025-11-29 14:29:52'),
(669, 31, 100.00, '%', '2025-11-29 14:29:56'),
(670, 32, 0.00, '%', '2025-11-29 14:29:56'),
(671, 30, 28.60, '°C', '2025-11-29 14:29:56'),
(672, 31, 100.00, '%', '2025-11-29 14:30:00'),
(673, 32, 0.00, '%', '2025-11-29 14:30:00'),
(674, 30, 28.60, '°C', '2025-11-29 14:30:00'),
(675, 31, 100.00, '%', '2025-11-29 14:30:04'),
(676, 32, 0.00, '%', '2025-11-29 14:30:04'),
(677, 30, 28.60, '°C', '2025-11-29 14:30:04'),
(678, 31, 100.00, '%', '2025-11-29 14:30:08'),
(679, 32, 0.00, '%', '2025-11-29 14:30:08'),
(680, 30, 28.60, '°C', '2025-11-29 14:30:08'),
(681, 31, 100.00, '%', '2025-11-29 14:30:12'),
(682, 32, 0.00, '%', '2025-11-29 14:30:12'),
(683, 30, 28.60, '°C', '2025-11-29 14:30:12'),
(684, 31, 100.00, '%', '2025-11-29 14:30:16'),
(685, 32, 0.00, '%', '2025-11-29 14:30:16'),
(686, 30, 28.60, '°C', '2025-11-29 14:30:16'),
(687, 31, 100.00, '%', '2025-11-29 14:30:20'),
(688, 32, 0.00, '%', '2025-11-29 14:30:20'),
(689, 30, 28.60, '°C', '2025-11-29 14:30:20'),
(690, 31, 99.90, '%', '2025-11-29 14:30:24'),
(691, 32, 0.00, '%', '2025-11-29 14:30:24'),
(692, 30, 28.60, '°C', '2025-11-29 14:30:24'),
(693, 31, 99.90, '%', '2025-11-29 14:30:29'),
(694, 32, 0.00, '%', '2025-11-29 14:30:29'),
(695, 30, 28.60, '°C', '2025-11-29 14:30:29'),
(696, 31, 99.90, '%', '2025-11-29 14:30:33'),
(697, 32, 0.00, '%', '2025-11-29 14:30:33'),
(698, 30, 28.60, '°C', '2025-11-29 14:30:33'),
(699, 31, 100.00, '%', '2025-11-29 14:30:37'),
(700, 32, 0.00, '%', '2025-11-29 14:30:37'),
(701, 30, 28.60, '°C', '2025-11-29 14:30:37'),
(702, 31, 100.00, '%', '2025-11-29 14:30:41'),
(703, 32, 0.00, '%', '2025-11-29 14:30:41'),
(704, 30, 28.60, '°C', '2025-11-29 14:30:41'),
(705, 31, 100.00, '%', '2025-11-29 14:30:45'),
(706, 32, 0.00, '%', '2025-11-29 14:30:45'),
(707, 30, 28.50, '°C', '2025-11-29 14:30:45'),
(708, 31, 100.00, '%', '2025-11-29 14:30:49'),
(709, 32, 0.00, '%', '2025-11-29 14:30:49'),
(710, 30, 28.60, '°C', '2025-11-29 14:30:49'),
(711, 31, 100.00, '%', '2025-11-29 14:30:53'),
(712, 32, 0.00, '%', '2025-11-29 14:30:53'),
(713, 30, 28.50, '°C', '2025-11-29 14:30:53'),
(714, 31, 100.00, '%', '2025-11-29 14:30:57'),
(715, 32, 0.00, '%', '2025-11-29 14:30:57'),
(716, 30, 28.60, '°C', '2025-11-29 14:30:57'),
(717, 31, 100.00, '%', '2025-11-29 14:31:01'),
(718, 32, 0.00, '%', '2025-11-29 14:31:01'),
(719, 30, 28.50, '°C', '2025-11-29 14:31:01'),
(720, 31, 100.00, '%', '2025-11-29 14:31:05'),
(721, 32, 0.00, '%', '2025-11-29 14:31:05'),
(722, 30, 28.50, '°C', '2025-11-29 14:31:05'),
(723, 31, 99.90, '%', '2025-11-29 14:31:09'),
(724, 32, 0.00, '%', '2025-11-29 14:31:09'),
(725, 30, 28.50, '°C', '2025-11-29 14:31:09');

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
(1, 'admin', '$2y$10$HXtzKW2gWBWgIcPgx5A56eDU1b95Bg6MpkjDis0Eo.tbUekvhFqNS', 'admin@farms.com', 'admin', 'active', '2025-11-29 14:27:03', '2025-10-20 14:59:13', '2025-11-29 14:27:03'),
(2, 'farmer1', '$2y$10$oiF/VWG/vKYk8g3yDNcNk.jRVf17DScq2mN/IVOpDAOm/q4ol8KGe', 'farmer1@farm.com', 'farmer', 'active', '2025-10-21 05:27:32', '2025-10-20 14:59:13', '2025-10-21 05:27:32'),
(3, 'student1', '$2y$10$E16YZTlMJYZ1etx1P64V8eXEpL7h1eq.tNzmBcV4oPkwMOo0fIZpO', 'kang2x2k17@gmail.com', 'student', 'active', '2025-10-21 13:36:33', '2025-10-20 14:59:13', '2025-10-21 13:55:26'),
(4, 'farmer2', '$2y$10$oiF/VWG/vKYk8g3yDNcNk.jRVf17DScq2mN/IVOpDAOm/q4ol8KGe', 'farmer2@farm.com', 'farmer', 'active', '2025-10-20 11:59:13', '2025-10-20 14:59:13', '2025-10-20 14:59:13'),
(5, 'student2', '$2y$10$oiF/VWG/vKYk8g3yDNcNk.jRVf17DScq2mN/IVOpDAOm/q4ol8KGe', 'student2@university.edu', 'student', 'inactive', NULL, '2025-10-20 14:59:13', '2025-10-20 14:59:13');

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
(1, 1, 'sensor_logging_interval', '0.0833', '2025-10-23 00:55:59', '2025-11-28 15:17:01');

-- --------------------------------------------------------

--
-- Structure for view `activeplantview`
--
DROP TABLE IF EXISTS `activeplantview`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `activeplantview`  AS SELECT `p`.`PlantID` AS `PlantID`, `p`.`PlantName` AS `PlantName`, `p`.`LocalName` AS `LocalName`, `p`.`MinSoilMoisture` AS `MinSoilMoisture`, `p`.`MaxSoilMoisture` AS `MaxSoilMoisture`, `p`.`MinTemperature` AS `MinTemperature`, `p`.`MaxTemperature` AS `MaxTemperature`, `p`.`MinHumidity` AS `MinHumidity`, `p`.`MaxHumidity` AS `MaxHumidity`, `p`.`WarningTrigger` AS `WarningTrigger`, `p`.`SuggestedAction` AS `SuggestedAction`, `p`.`CreatedAt` AS `CreatedAt`, `p`.`UpdatedAt` AS `UpdatedAt`, `ap`.`UpdatedAt` AS `ActivatedAt` FROM (`plants` `p` join `activeplant` `ap` on(`p`.`PlantID` = `ap`.`SelectedPlantID`)) LIMIT 0, 1 ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activeplant`
--
ALTER TABLE `activeplant`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `idx_selected_plant` (`SelectedPlantID`);

--
-- Indexes for table `activeplants`
--
ALTER TABLE `activeplants`
  ADD PRIMARY KEY (`ActivePlantID`),
  ADD UNIQUE KEY `unique_plant` (`PlantID`);

--
-- Indexes for table `cameras`
--
ALTER TABLE `cameras`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_location` (`location`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`NotificationID`),
  ADD KEY `idx_plant_notification` (`PlantID`,`CreatedAt`),
  ADD KEY `idx_is_read` (`IsRead`),
  ADD KEY `idx_sensor_type` (`SensorType`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `pest_alerts`
--
ALTER TABLE `pest_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `read_by` (`read_by`),
  ADD KEY `idx_camera_id` (`camera_id`),
  ADD KEY `idx_severity_status` (`severity`,`status`),
  ADD KEY `idx_detected_at` (`detected_at`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_read_at` (`read_at`),
  ADD KEY `idx_notification_sent` (`notification_sent`);

--
-- Indexes for table `pest_config`
--
ALTER TABLE `pest_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pest_name` (`pest_name`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_pest_name` (`pest_name`),
  ADD KEY `idx_severity` (`severity`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_common_name` (`common_name`);

--
-- Indexes for table `plants`
--
ALTER TABLE `plants`
  ADD PRIMARY KEY (`PlantID`),
  ADD KEY `idx_plant_name` (`PlantName`);

--
-- Indexes for table `sensorreadings`
--
ALTER TABLE `sensorreadings`
  ADD PRIMARY KEY (`ReadingID`),
  ADD KEY `idx_plant_reading` (`PlantID`,`ReadingTime`),
  ADD KEY `idx_reading_time` (`ReadingTime`),
  ADD KEY `idx_warning_level` (`WarningLevel`);

--
-- Indexes for table `sensors`
--
ALTER TABLE `sensors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_active_pin` (`arduino_pin`,`status`),
  ADD KEY `idx_arduino_pin` (`arduino_pin`),
  ADD KEY `idx_sensor_type` (`sensor_type`),
  ADD KEY `idx_status` (`status`);

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
-- AUTO_INCREMENT for table `activeplant`
--
ALTER TABLE `activeplant`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=141;

--
-- AUTO_INCREMENT for table `activeplants`
--
ALTER TABLE `activeplants`
  MODIFY `ActivePlantID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `cameras`
--
ALTER TABLE `cameras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `NotificationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `pest_alerts`
--
ALTER TABLE `pest_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `pest_config`
--
ALTER TABLE `pest_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT for table `plants`
--
ALTER TABLE `plants`
  MODIFY `PlantID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `sensorreadings`
--
ALTER TABLE `sensorreadings`
  MODIFY `ReadingID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=138;

--
-- AUTO_INCREMENT for table `sensors`
--
ALTER TABLE `sensors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `sensor_readings`
--
ALTER TABLE `sensor_readings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=726;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_settings`
--
ALTER TABLE `user_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activeplant`
--
ALTER TABLE `activeplant`
  ADD CONSTRAINT `activeplant_ibfk_1` FOREIGN KEY (`SelectedPlantID`) REFERENCES `plants` (`PlantID`) ON DELETE CASCADE;

--
-- Constraints for table `activeplants`
--
ALTER TABLE `activeplants`
  ADD CONSTRAINT `activeplants_ibfk_1` FOREIGN KEY (`PlantID`) REFERENCES `plants` (`PlantID`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`PlantID`) REFERENCES `plants` (`PlantID`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pest_alerts`
--
ALTER TABLE `pest_alerts`
  ADD CONSTRAINT `pest_alerts_ibfk_1` FOREIGN KEY (`camera_id`) REFERENCES `cameras` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pest_alerts_ibfk_2` FOREIGN KEY (`read_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `pest_config`
--
ALTER TABLE `pest_config`
  ADD CONSTRAINT `pest_config_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pest_config_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sensorreadings`
--
ALTER TABLE `sensorreadings`
  ADD CONSTRAINT `sensorreadings_ibfk_1` FOREIGN KEY (`PlantID`) REFERENCES `plants` (`PlantID`) ON DELETE CASCADE;

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
