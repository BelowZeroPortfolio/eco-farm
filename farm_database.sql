-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 24, 2025 at 08:53 AM
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
(18, 3, '69150101ee7f868e281f52f02f1b74795f996603e1860b55ae4efe15fbc6f8fb', '2025-10-21 22:22:34', 1, '2025-10-21 13:22:34'),
(19, 3, '23b74cf8dabc73f3f01f5084550bb6ed1b83e54c88d375e449a49e219091e0fa', '2025-10-30 12:05:37', 1, '2025-10-30 03:05:37');

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

--
-- Dumping data for table `pest_alerts`
--

INSERT INTO `pest_alerts` (`id`, `camera_id`, `pest_type`, `common_name`, `location`, `severity`, `status`, `confidence_score`, `is_read`, `read_at`, `read_by`, `notification_sent`, `notification_sent_at`, `image_path`, `description`, `suggested_actions`, `detected_at`, `updated_at`) VALUES
(16, NULL, 'mole cricket', 'Kamaro / Cricket sa lupa', 'Webcam Detection', 'medium', 'new', 72.20, 0, NULL, NULL, 0, NULL, 'detections/detection_20251120_094232_cefcf3d2.jpg', 'Tunnels uproot seedlings. 10-20% seedling loss.', 'Use poison baits in evening. Flood fields overnight. Apply fipronil 500ml/ha.', '2025-11-20 01:42:32', '2025-11-20 01:42:32'),
(17, NULL, 'aphids', 'Kuto ng halaman', 'Webcam Detection', 'high', 'new', 72.00, 0, NULL, NULL, 0, NULL, 'detections/detection_20251120_094308_6b3cd6f3.jpg', 'Transmits viruses, causes stunting. 20-50% yield loss.', 'Apply imidacloprid 100ml/ha or acetamiprid 100g/ha. Release ladybugs. Use reflective mulch.', '2025-11-20 01:43:08', '2025-11-20 01:43:08'),
(18, NULL, 'aphids', 'Kuto ng halaman', 'Webcam Detection', 'high', 'new', 75.50, 0, NULL, NULL, 0, NULL, 'detections/detection_20251120_095034_3b625d22.jpg', 'Transmits viruses, causes stunting. 20-50% yield loss.', 'Apply imidacloprid 100ml/ha or acetamiprid 100g/ha. Release ladybugs. Use reflective mulch.', '2025-11-20 01:50:34', '2025-11-20 01:50:34'),
(19, NULL, 'rice leaf roller', 'Tagapagkulong ng dahon ng palay', 'Webcam Detection', 'high', 'new', 76.80, 0, NULL, NULL, 0, NULL, 'detections/detection_20251120_095356_53b7b5d2.jpg', 'Rolls leaves reducing photosynthesis. 10-30% yield loss.', 'Apply chlorantraniliprole 60ml/ha or flubendiamide 100ml/ha. Drain field 2 days before spraying. Preserve spiders and wasps.', '2025-11-20 01:53:56', '2025-11-20 01:53:56'),
(20, NULL, 'Limacodidae', 'Uod-balat / slug caterpillar', 'Webcam Detection', 'low', 'new', 74.10, 0, NULL, NULL, 0, NULL, 'detections/detection_20251120_095849_8fbc5964.jpg', 'Slug caterpillars. <5% defoliation.', 'Hand-pick with gloves. Usually minor.', '2025-11-20 01:58:49', '2025-11-20 01:58:49'),
(21, NULL, 'oides decempunctata', 'Salagubang ng dahon', 'Webcam Detection', 'low', 'new', 62.80, 0, NULL, NULL, 0, NULL, 'detections/detection_20251120_100041_821c649d.jpg', 'Leaf beetle. <8% defoliation.', 'Monitor and hand-pick if necessary.', '2025-11-20 02:00:41', '2025-11-20 02:00:41'),
(22, NULL, 'rice leaf roller', 'Tagapagkulong ng dahon ng palay', 'Webcam Detection', 'high', 'new', 76.80, 0, NULL, NULL, 0, NULL, 'detections/detection_20251120_100102_a810b419.jpg', 'Rolls leaves reducing photosynthesis. 10-30% yield loss.', 'Apply chlorantraniliprole 60ml/ha or flubendiamide 100ml/ha. Drain field 2 days before spraying. Preserve spiders and wasps.', '2025-11-20 02:01:02', '2025-11-20 02:01:02'),
(23, NULL, 'aphids', 'Kuto ng halaman', 'Webcam Detection', 'high', 'new', 68.40, 0, NULL, NULL, 0, NULL, 'detections/detection_20251120_100251_40f97a3e.jpg', 'Transmits viruses, causes stunting. 20-50% yield loss.', 'Apply imidacloprid 100ml/ha or acetamiprid 100g/ha. Release ladybugs. Use reflective mulch.', '2025-11-20 02:02:51', '2025-11-20 02:02:51');

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

-- --------------------------------------------------------

--
-- Table structure for table `sensor_alerts`
--

CREATE TABLE `sensor_alerts` (
  `id` int(11) NOT NULL,
  `sensor_id` int(11) NOT NULL,
  `sensor_type` varchar(50) NOT NULL,
  `sensor_value` decimal(10,2) NOT NULL,
  `threshold_min` decimal(10,2) DEFAULT NULL,
  `threshold_max` decimal(10,2) DEFAULT NULL,
  `severity` enum('low','medium','high','critical') NOT NULL,
  `status` enum('new','acknowledged','resolved') DEFAULT 'new',
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `read_by` int(11) DEFAULT NULL,
  `detected_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Sensor threshold alerts';

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
(1, 'admin', '$2y$10$HXtzKW2gWBWgIcPgx5A56eDU1b95Bg6MpkjDis0Eo.tbUekvhFqNS', 'admin@farms.com', 'admin', 'active', '2025-11-20 00:58:16', '2025-10-20 14:59:13', '2025-11-20 00:58:16'),
(2, 'farmer1', '$2y$10$oiF/VWG/vKYk8g3yDNcNk.jRVf17DScq2mN/IVOpDAOm/q4ol8KGe', 'farmer1@farm.com', 'farmer', 'active', '2025-10-21 05:27:32', '2025-10-20 14:59:13', '2025-10-21 05:27:32'),
(3, 'student1', '$2y$10$wocSMOs9S1i.C6R2d1tHyuzsTxGcMFbxjNfzhURbVtQy1cKhI4eIy', 'kang2x2k17@gmail.com', 'student', 'active', '2025-10-30 03:06:42', '2025-10-20 14:59:13', '2025-10-30 03:06:42'),
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
(1, 1, 'sensor_logging_interval', '30', '2025-10-23 00:55:59', '2025-10-25 14:05:57');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cameras`
--
ALTER TABLE `cameras`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_location` (`location`);

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
-- Indexes for table `sensors`
--
ALTER TABLE `sensors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_active_pin` (`arduino_pin`,`status`),
  ADD KEY `idx_arduino_pin` (`arduino_pin`),
  ADD KEY `idx_sensor_type` (`sensor_type`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `sensor_alerts`
--
ALTER TABLE `sensor_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `read_by` (`read_by`),
  ADD KEY `idx_sensor_id` (`sensor_id`),
  ADD KEY `idx_severity` (`severity`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_detected_at` (`detected_at`);

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
-- AUTO_INCREMENT for table `cameras`
--
ALTER TABLE `cameras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `pest_alerts`
--
ALTER TABLE `pest_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `pest_config`
--
ALTER TABLE `pest_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT for table `sensors`
--
ALTER TABLE `sensors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `sensor_alerts`
--
ALTER TABLE `sensor_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sensor_readings`
--
ALTER TABLE `sensor_readings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_settings`
--
ALTER TABLE `user_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

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
-- Constraints for table `sensor_alerts`
--
ALTER TABLE `sensor_alerts`
  ADD CONSTRAINT `sensor_alerts_ibfk_1` FOREIGN KEY (`sensor_id`) REFERENCES `sensors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sensor_alerts_ibfk_2` FOREIGN KEY (`read_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

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
