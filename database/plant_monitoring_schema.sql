-- Plant Monitoring System Schema
-- Add these tables to your existing farm_database

-- --------------------------------------------------------
-- Table structure for table `Plants`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `Plants` (
  `PlantID` INT AUTO_INCREMENT PRIMARY KEY,
  `PlantName` VARCHAR(100) NOT NULL,
  `LocalName` VARCHAR(100) NOT NULL COMMENT 'Filipino name',
  `MinSoilMoisture` INT NOT NULL COMMENT 'Minimum soil moisture percentage',
  `MaxSoilMoisture` INT NOT NULL COMMENT 'Maximum soil moisture percentage',
  `MinTemperature` FLOAT NOT NULL COMMENT 'Minimum temperature in Celsius',
  `MaxTemperature` FLOAT NOT NULL COMMENT 'Maximum temperature in Celsius',
  `MinHumidity` INT NOT NULL COMMENT 'Minimum humidity percentage',
  `MaxHumidity` INT NOT NULL COMMENT 'Maximum humidity percentage',
  `WarningTrigger` INT DEFAULT 5 COMMENT 'Number of violations before notification',
  `SuggestedAction` TEXT NOT NULL COMMENT 'Recommended action when thresholds violated',
  `CreatedAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_plant_name` (`PlantName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `ActivePlant`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `ActivePlant` (
  `ID` INT PRIMARY KEY AUTO_INCREMENT,
  `SelectedPlantID` INT NOT NULL,
  `UpdatedAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`SelectedPlantID`) REFERENCES `Plants`(`PlantID`) ON DELETE CASCADE,
  INDEX `idx_selected_plant` (`SelectedPlantID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `SensorReadings` (Plant-specific)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `SensorReadings` (
  `ReadingID` INT AUTO_INCREMENT PRIMARY KEY,
  `PlantID` INT NOT NULL,
  `SoilMoisture` INT NOT NULL COMMENT 'Soil moisture percentage',
  `Temperature` FLOAT NOT NULL COMMENT 'Temperature in Celsius',
  `Humidity` INT NOT NULL COMMENT 'Humidity percentage',
  `WarningLevel` INT DEFAULT 0 COMMENT 'Cumulative warning count',
  `ReadingTime` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`PlantID`) REFERENCES `Plants`(`PlantID`) ON DELETE CASCADE,
  INDEX `idx_plant_reading` (`PlantID`, `ReadingTime`),
  INDEX `idx_reading_time` (`ReadingTime`),
  INDEX `idx_warning_level` (`WarningLevel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `Notifications` (Plant-specific)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `Notifications` (
  `NotificationID` INT AUTO_INCREMENT PRIMARY KEY,
  `PlantID` INT NOT NULL,
  `Message` TEXT NOT NULL,
  `SensorType` VARCHAR(50) NOT NULL COMMENT 'soil_moisture, temperature, or humidity',
  `Level` INT NOT NULL COMMENT 'Warning level when notification was triggered',
  `SuggestedAction` TEXT NOT NULL,
  `CurrentValue` FLOAT NOT NULL COMMENT 'The sensor value that triggered notification',
  `RequiredRange` VARCHAR(50) NOT NULL COMMENT 'Expected range for the sensor',
  `Status` VARCHAR(20) DEFAULT 'Below Minimum' COMMENT 'Below Minimum or Above Maximum',
  `IsRead` TINYINT(1) DEFAULT 0,
  `ReadAt` TIMESTAMP NULL DEFAULT NULL,
  `CreatedAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`PlantID`) REFERENCES `Plants`(`PlantID`) ON DELETE CASCADE,
  INDEX `idx_plant_notification` (`PlantID`, `CreatedAt`),
  INDEX `idx_is_read` (`IsRead`),
  INDEX `idx_sensor_type` (`SensorType`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Insert 10 common Philippine plants with thresholds
-- --------------------------------------------------------

INSERT INTO `Plants` (`PlantName`, `LocalName`, `MinSoilMoisture`, `MaxSoilMoisture`, `MinTemperature`, `MaxTemperature`, `MinHumidity`, `MaxHumidity`, `WarningTrigger`, `SuggestedAction`) VALUES
('Tomato', 'Kamatis', 30, 60, 18, 35, 40, 70, 5, 'Water lightly and improve airflow. Ensure proper drainage to prevent root rot.'),
('Lettuce', 'Letsugas', 40, 70, 15, 25, 50, 80, 5, 'Add water immediately and keep in cooler place. Provide shade during hot hours.'),
('Chili Pepper', 'Sili', 25, 55, 20, 35, 40, 70, 5, 'Water moderately, avoid direct noon sunlight. Mulch to retain moisture.'),
('Eggplant', 'Talong', 40, 70, 18, 30, 50, 80, 5, 'Water soil consistently and ensure consistent sunlight. Check for pests regularly.'),
('Banana', 'Saging', 45, 75, 25, 34, 70, 90, 5, 'Add water immediately, ensure high humidity. Protect from strong winds.'),
('Pechay', 'Pechay', 35, 70, 16, 25, 50, 85, 5, 'Water soil regularly and keep partially shaded. Harvest before bolting.'),
('Calamansi', 'Kalamansi', 25, 55, 20, 32, 40, 70, 5, 'Water slightly and provide good airflow. Fertilize monthly during growing season.'),
('Okra', 'Okra', 30, 60, 20, 32, 40, 70, 5, 'Water soil deeply and ensure full sun. Harvest pods when young and tender.'),
('Malunggay', 'Moringa', 20, 50, 22, 35, 30, 60, 5, 'Minimal water needed, avoid overwatering. Very drought-tolerant once established.'),
('Cucumber', 'Pipino', 35, 75, 18, 32, 50, 80, 5, 'Water consistently, keep humid. Provide trellis support for better growth.');

-- --------------------------------------------------------
-- Set default active plant (Tomato)
-- --------------------------------------------------------

INSERT INTO `ActivePlant` (`SelectedPlantID`) 
SELECT `PlantID` FROM `Plants` WHERE `PlantName` = 'Tomato' LIMIT 1;

-- --------------------------------------------------------
-- Create view for easy access to active plant data
-- --------------------------------------------------------

CREATE OR REPLACE VIEW `ActivePlantView` AS
SELECT 
    p.*,
    ap.UpdatedAt as ActivatedAt
FROM `Plants` p
INNER JOIN `ActivePlant` ap ON p.PlantID = ap.SelectedPlantID
LIMIT 1;
