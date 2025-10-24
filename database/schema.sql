-- ============================================================================
-- IoT Farm Monitoring System - Complete Database Setup
-- ============================================================================
-- This file contains the complete database schema with all migrations merged
-- Run this file once to set up the entire database
-- ============================================================================

-- Create database
CREATE DATABASE IF NOT EXISTS farm_database;
USE farm_database;

-- ============================================================================
-- TABLES
-- ============================================================================

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    role ENUM('admin', 'student', 'farmer') DEFAULT 'student',
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Sensors table for IoT device information
CREATE TABLE IF NOT EXISTS sensors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sensor_name VARCHAR(100) NOT NULL,
    sensor_type ENUM('temperature', 'humidity', 'soil_moisture') NOT NULL,
    location VARCHAR(100) NOT NULL,
    arduino_pin INT NOT NULL,
    sensor_id VARCHAR(50),
    calibration_offset DECIMAL(10,4) DEFAULT 0.0000,
    alert_threshold_min DECIMAL(10,2) NULL,
    alert_threshold_max DECIMAL(10,2) NULL,
    status ENUM('online', 'offline', 'error') DEFAULT 'offline',
    last_reading_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_arduino_pin (arduino_pin),
    INDEX idx_sensor_type (sensor_type),
    INDEX idx_status (status),
    UNIQUE KEY unique_active_pin (arduino_pin, status)
);

-- Sensor readings table for historical data
CREATE TABLE IF NOT EXISTS sensor_readings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sensor_id INT NOT NULL,
    value DECIMAL(10,2) NOT NULL,
    unit VARCHAR(20) NOT NULL,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sensor_id) REFERENCES sensors(id) ON DELETE CASCADE,
    INDEX idx_sensor_recorded (sensor_id, recorded_at),
    INDEX idx_recorded_at (recorded_at)
);

-- Cameras table for pest detection cameras
CREATE TABLE IF NOT EXISTS cameras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    camera_name VARCHAR(100) NOT NULL,
    location VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45),
    port INT DEFAULT 80,
    username VARCHAR(50),
    password VARCHAR(255),
    camera_type ENUM('ip_camera', 'usb_camera', 'rtsp_stream') DEFAULT 'ip_camera',
    resolution VARCHAR(20) DEFAULT '1920x1080',
    fps INT DEFAULT 30,
    detection_enabled BOOLEAN DEFAULT TRUE,
    detection_sensitivity ENUM('low', 'medium', 'high') DEFAULT 'medium',
    detection_zones JSON,
    status ENUM('online', 'offline', 'error') DEFAULT 'offline',
    last_detection TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_location (location)
);

-- Pest alerts table with all fields (merged from migrations)
CREATE TABLE IF NOT EXISTS pest_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    camera_id INT,
    pest_type VARCHAR(100) NOT NULL,
    common_name VARCHAR(200) NULL,
    location VARCHAR(100) NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL,
    status ENUM('new', 'acknowledged', 'resolved') DEFAULT 'new',
    confidence_score DECIMAL(5,2),
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    read_by INT NULL,
    notification_sent BOOLEAN DEFAULT FALSE,
    notification_sent_at TIMESTAMP NULL,
    image_path VARCHAR(255),
    description TEXT,
    suggested_actions TEXT,
    detected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (camera_id) REFERENCES cameras(id) ON DELETE SET NULL,
    FOREIGN KEY (read_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_camera_id (camera_id),
    INDEX idx_severity_status (severity, status),
    INDEX idx_detected_at (detected_at),
    INDEX idx_is_read (is_read),
    INDEX idx_read_at (read_at),
    INDEX idx_notification_sent (notification_sent)
);

-- User settings table for personalization
CREATE TABLE IF NOT EXISTS user_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_setting (user_id, setting_key)
);

-- Password resets table for forgot password functionality
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expires (expires_at),
    INDEX idx_user_id (user_id)
);

-- Pest configuration table for dynamic pest management
CREATE TABLE IF NOT EXISTS pest_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pest_name VARCHAR(200) NOT NULL COMMENT 'Scientific name of the pest',
    common_name VARCHAR(200) NULL COMMENT 'Filipino/English common name for better comprehension',
    pest_type VARCHAR(100) NOT NULL UNIQUE COMMENT 'Unique identifier used by YOLO model',
    description TEXT NOT NULL COMMENT 'Detailed description of the pest',
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    economic_threshold VARCHAR(200) NULL COMMENT 'Population level requiring treatment',
    suggested_actions TEXT NOT NULL COMMENT 'Recommended control measures',
    remarks TEXT NULL COMMENT 'Additional notes and information',
    created_by INT NULL,
    updated_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_pest_type (pest_type),
    INDEX idx_severity (severity),
    INDEX idx_common_name (common_name)
) COMMENT='Dynamic pest configuration for database-driven pest management';

-- ============================================================================
-- PEST CONFIGURATION DATA
-- ============================================================================
-- Insert all 102 pest types from YOLO model with Filipino/English common names
-- These can be edited through the admin interface (pest_config.php)

INSERT INTO pest_config (pest_name, common_name, pest_type, severity, description, economic_threshold, suggested_actions, remarks) VALUES
-- Rice Pests (Critical)
('rice leaf roller', 'Tagapagkulong ng dahon ng palay', 'insect', 'high', 'Rolls leaves reducing photosynthesis. 10-30% yield loss.', '20% leaves show damage', 'Apply chlorantraniliprole 60ml/ha or flubendiamide 100ml/ha. Drain field 2 days before spraying. Preserve spiders and wasps.', 'Peak activity: vegetative to flowering stage'),
('rice leaf caterpillar', 'Uod sa dahon ng palay', 'insect', 'medium', 'Defoliates rice plants. 10-20% yield loss.', '2-3 larvae per plant', 'Apply Bt 1kg/ha or spinosad 200ml/ha. Hand-pick larvae. Monitor at night.', 'Usually controlled by natural enemies'),
('paddy stem maggot', 'Uod sa tangkay ng palay', 'insect', 'high', 'Causes dead hearts in young seedlings. 15-35% seedling mortality.', '5% dead hearts', 'Apply carbofuran 1kg/ha at transplanting. Use 15-20 day old seedlings. Drain field if severe.', 'Most damaging in young plants'),
('asiatic rice borer', 'Uod tagabutas ng palay (Asyatik)', 'insect', 'critical', 'Causes dead hearts and white heads. 30-80% yield loss.', '5% dead hearts or white heads', 'Apply cartap hydrochloride 1kg/ha or chlorantraniliprole 60ml/ha. Cut and burn stubble. Use pheromone traps.', 'Treat at egg hatching stage'),
('yellow rice borer', 'Dilaw na uod tagabutas ng palay', 'insect', 'critical', 'Causes dead hearts and white heads. 25-60% yield loss.', '5% white heads', 'Apply fipronil 100g/ha or flubendiamide 100ml/ha at maximum tillering. Maintain 5cm water depth for 3 days.', 'Synchronize planting dates to break cycle'),
('rice gall midge', 'Gal-midge ng palay / Lamok-lamok ng palay', 'insect', 'critical', 'Forms silver shoots, no grain production. 20-70% yield loss.', '5% silver shoots', 'Apply carbofuran 1kg/ha or fipronil 100g/ha at tillering. Drain field for 3 days. Use resistant varieties.', 'Remove wild grasses around field'),
('Rice Stemfly', 'Langaw sa tangkay ng palay', 'insect', 'low', 'Minor pest, causes small dead hearts. <5% yield loss.', '10% dead hearts', 'Monitor during seedling stage. Usually controlled by natural enemies. Treatment rarely needed.', 'Maintain field sanitation'),
('brown plant hopper', 'Kayumangging sipsip-dahon ng palay', 'insect', 'critical', 'Causes hopper burn, transmits viruses. 70-100% yield loss possible.', '10 hoppers per plant', 'Apply imidacloprid 200g/ha or thiamethoxam 100g/ha. Drain field for 3-4 days. Scout every 2 days.', 'Transmits rice grassy stunt virus'),
('white backed plant hopper', 'Puting likod na sipsip-dahon', 'insect', 'high', 'Transmits rice tungro virus. 15-40% yield loss.', '5-10 hoppers per plant', 'Apply buprofezin 500g/ha or pymetrozine 250g/ha. Remove weeds. Use virus-resistant varieties.', 'Scout weekly during vegetative stage'),
('small brown plant hopper', 'Maliit na kayumangging sipsip-dahon', 'insect', 'high', 'Transmits rice ragged stunt virus. 10-30% yield loss.', '10-15 hoppers per plant', 'Apply thiamethoxam 100g/ha or dinotefuran 200g/ha. Avoid excessive nitrogen. Maintain balanced fertilization.', 'Scout at boot to heading stage'),
('rice water weevil', 'Salagubang-tubig ng palay', 'insect', 'high', 'Larvae prune roots causing stunting. 10-25% yield loss.', '20% plants show feeding scars', 'Maintain 7-10cm water depth for 2 weeks after transplanting. Apply chlorpyrifos 500ml/ha if needed.', 'Delay permanent flood until established'),
('rice leafhopper', 'Tagtalon-dahon ng palay', 'insect', 'medium', 'Sucks sap, causes yellowing. 8-20% yield loss.', '15 hoppers per plant', 'Apply buprofezin 500g/ha. Maintain field hygiene. Avoid early planting.', 'Natural enemies usually provide control'),
('grain spreader thrips', 'Kulisap tagakalat ng butil', 'insect', 'medium', 'Affects grain quality. 5-15% quality loss.', 'High population at heading', 'Apply dimethoate 500ml/ha if severe. Harvest at proper maturity. Dry grain quickly.', 'Usually not economically damaging'),
('rice shell pest', 'Balat-butil na pesteng palay', 'insect', 'medium', 'Storage pest. 5-20% storage loss.', 'Presence in stored grain', 'Dry grain to 12-14% moisture. Use hermetic storage. Apply diatomaceous earth.', 'Not a field pest - storage only')

-- ============================================================================
-- VERIFICATION
-- ============================================================================

-- Show all tables
-- SHOW TABLES;

-- Show key table structures
-- DESCRIBE pest_alerts;
-- DESCRIBE pest_config;

-- ============================================================================
-- Setup complete!
-- ============================================================================
,
-- General Crop Pests
('grub', 'Uod-lupa / Bulating salagubang', 'insect', 'medium', 'White grubs sever roots. 10-25% plant loss.', '2-4 per m²', 'Apply chlorpyrifos 2L/ha before planting. Practice crop rotation. Deep plowing exposes grubs.', 'Larvae of various beetles'),
('mole cricket', 'Kamaro / Cricket sa lupa', 'insect', 'medium', 'Tunnels uproot seedlings. 10-20% seedling loss.', '2-4 per m²', 'Use poison baits in evening. Flood fields overnight. Apply fipronil 500ml/ha.', 'Plow in summer to destroy eggs'),
('wireworm', 'Alupihan ng ugat', 'insect', 'medium', 'Bores into seeds and roots. 10-20% stand loss.', '1 per 10 plants', 'Apply phorate 10kg/ha at planting. Use seed treatment. Rotate with legumes.', 'Larvae of click beetles'),
('white margined moth', 'Puting-margeng gamu-gamo', 'insect', 'high', 'Defoliates fruit trees. 20-40% defoliation.', '15% leaves damaged', 'Apply Bt 1kg/ha or chlorantraniliprole 60ml/ha. Hand-pick egg masses. Use light traps.', 'Larvae most active at night'),
('black cutworm', 'Itim na uod-tagaputol', 'insect', 'medium', 'Cuts seedlings at soil level. 10-30% plant loss.', '3-5% plants cut', 'Apply chlorpyrifos 500ml/ha around plant base. Use cardboard collars. Scout at night.', 'Hide in soil during day'),
('large cutworm', 'Malaking uod-tagaputol', 'insect', 'medium', 'Feeds on young plants at night. 10-20% plant loss.', '5% plants damaged', 'Hand-pick at night. Apply Bt 1kg/ha or spinosad 200ml/ha. Use bait traps.', 'Remove plant debris'),
('yellow cutworm', 'Dilaw na uod-tagaputol', 'insect', 'medium', 'Damages seedlings. 10-20% plant loss.', '5% plants damaged', 'Apply carbaryl 1kg/ha around plant base. Remove debris. Cultivate before planting.', 'Use collars on transplants'),
('red spider', 'Pulang gagamba / pulang hama', 'mite', 'medium', 'Two-spotted spider mite causes bronzing. 10-25% yield loss.', '5-10 mites per leaf', 'Apply abamectin 500ml/ha or spiromesifen 600ml/ha. Increase irrigation. Release predatory mites.', 'Avoid dusty conditions'),
('corn borer', 'Uod-tagabutas ng mais', 'insect', 'critical', 'Tunnels weaken stalks. 20-50% yield loss.', '10% plants show damage', 'Apply Bt 1kg/ha or spinosad 200ml/ha. Apply granules in whorl. Remove infested plants.', 'Plant early to avoid peak'),
('army worm', 'Uod-hukbo', 'insect', 'critical', 'Migrates in groups, consumes plants. 80-100% defoliation.', '2-3 larvae per plant', 'Apply chlorpyrifos 500ml/ha or emamectin benzoate 200g/ha at dusk. Scout at dawn. Treat borders first.', 'Can destroy entire fields'),
('aphids', 'Kuto ng halaman', 'insect', 'high', 'Small sap-sucking insects that weaken plants and transmit viruses', '50 aphids per plant', 'Apply imidacloprid 100ml/ha or acetamiprid 100g/ha. Release ladybugs. Use reflective mulch.', 'Avoid excessive nitrogen'),
('Potosiabre vitarsis', 'Salagubang sa palay (Potosia type)', 'beetle', 'low', 'Flower beetle. <5% yield loss.', 'High population', 'Hand-pick beetles. Usually minor. Monitor during flowering.', 'Feeds on flowers and fruits'),
('peach borer', 'Uod sa melokoton (peach borer)', 'insect', 'medium', 'Bores into trunk. 10-20% tree mortality.', 'Gum and frass present', 'Apply permethrin to trunk base. Remove gum. Paint trunk white. Use pheromone disruption.', 'April-August most active'),
-- Wheat/Grain Pests
('english grain aphid', 'Kuto ng butil (Ingles na uri)', 'insect', 'medium', 'Feeds on wheat heads. 5-15% yield loss.', '15 aphids per head', 'Apply pirimicarb 250g/ha or thiamethoxam 100g/ha at milk stage. Preserve natural enemies.', 'Boot to dough stage critical'),
('green bug', 'Berdeng kuto ng halaman', 'insect', 'medium', 'Greenbug aphid injects toxin. 10-30% yield loss.', '50 aphids per plant', 'Apply imidacloprid 100ml/ha. Use resistant varieties. Preserve parasitic wasps.', 'Scout twice weekly in spring'),
('bird cherry-oataphid', 'Kuto ng trigo at oats', 'insect', 'medium', 'Early season aphid. 5-15% yield loss.', '10 aphids per tiller', 'Apply insecticidal soap if needed. Usually controlled by natural enemies.', 'Rarely requires treatment'),
('wheat blossom midge', 'Lamok ng bulaklak ng trigo', 'insect', 'medium', 'Destroys developing grain. 10-30% yield loss.', 'Adults present at flowering', 'Apply lambda-cyhalothrin 250ml/ha at early flowering. Use resistant varieties. Plow stubble.', 'Scout at dusk for adults'),
('penthaleus major', 'Pulang gagamba sa trigo', 'mite', 'low', 'Winter grain mite. <5% yield loss.', 'Silvering on leaves', 'Monitor only. Usually not damaging. Natural rainfall controls.', 'Appears as silvering'),
('longlegged spider mite', 'Habang-paa na hama', 'mite', 'low', 'Beneficial predator of pest mites.', 'N/A', 'No action needed. Preserve as natural enemy.', 'Beneficial species'),
('wheat phloeothrips', 'Kulisap sa tangkay ng trigo', 'insect', 'low', 'Minor pest of wheat heads. <3% yield loss.', 'High population', 'Monitor only. Rarely requires treatment.', 'Damage is cosmetic'),
('wheat sawfly', 'Langaw na tagabutas ng trigo', 'insect', 'medium', 'Causes lodging. 10-30% yield loss.', 'Larvae present', 'Plant solid stem varieties. Harvest low. Rotate crops. Plow stubble immediately.', 'Larvae bore into stems'),
('cerodonta denticornis', 'Langaw ng damo / rice fly', 'insect', 'low', 'Leaf miner. <5% yield loss.', 'White trails on leaves', 'Remove affected leaves. Usually minor. Natural parasitoids control.', 'Cosmetic damage'),
-- Vegetable Pests
('beet fly', 'Langaw ng beet', 'insect', 'medium', 'Larvae mine leaves. 10-20% yield loss.', '30% leaves mined', 'Remove affected leaves. Apply spinosad 200ml/ha. Use row covers.', 'One generation per season'),
('flea beetle', 'Lukso-salagubang', 'beetle', 'high', 'Shot-hole damage. 20-40% yield loss in young plants.', '2-3 beetles per plant', 'Apply neem oil 3ml/L or pyrethrin 200ml/ha. Use floating row covers. Apply kaolin clay.', '10% leaf area threshold'),
('cabbage army worm', 'Uod-hukbo ng repolyo', 'insect', 'high', 'Voracious feeder on crucifers. 30-60% defoliation.', '2 larvae per plant', 'Apply Bt 1kg/ha or emamectin benzoate 200g/ha in evening. Hand-pick egg masses.', 'Use pheromone traps'),
('beet army worm', 'Uod-hukbo ng beet', 'insect', 'high', 'Attacks 300+ species. 25-50% yield loss.', '10% plants show damage', 'Apply spinosad 200ml/ha or indoxacarb 200ml/ha. Scout undersides for eggs.', 'Larvae hide in soil during day'),
('Beet spot flies', 'Langaw na tagabuo ng batik sa beet', 'insect', 'low', 'Minor pest. <5% yield loss.', 'Presence of flies', 'Monitor only. Usually not significant.', 'Rarely requires treatment'),
('meadow moth', 'Gamu-gamong parang', 'insect', 'medium', 'Feeds on grass and crops. 10-20% defoliation.', '5 larvae per m²', 'Apply Bt 1kg/ha or chlorantraniliprole 60ml/ha. Mow grass around fields.', 'Usually sporadic'),
('beet weevil', 'Salagubang ng beet', 'beetle', 'medium', 'Damages beets. 10-25% yield loss.', 'Adults present', 'Apply thiamethoxam 100g/ha at seedling stage. Practice 3-year rotation. Remove debris.', 'Plow in fall'),
('sericaorient alismots chulsky', 'Oriental na salagubang', 'beetle', 'low', 'Scarab beetle. <8% yield loss.', 'High population', 'Monitor and apply insecticide if needed. Usually minor.', 'Adults feed on leaves'),
-- Alfalfa/Legume Pests
('alfalfa weevil', 'Salagubang ng alfalfa', 'beetle', 'medium', 'Skeletonizes leaves. 15-30% yield loss.', '30% tips show feeding', 'Apply chlorpyrifos 500ml/ha or malathion 1L/ha. Early harvest if severe.', 'Preserve natural enemies'),
('flax budworm', 'Uod sa usbong ng flax', 'insect', 'low', 'Minor pest of flax. <5% yield loss.', '20% buds damaged', 'Monitor during bud stage. Usually sporadic.', 'Treat only if threshold met'),
('alfalfa plant bug', 'Kulisap sa alfalfa', 'insect', 'low', 'Minor pest. <5% yield loss.', '2 bugs per sweep', 'Monitor during bud stage. Usually controlled by natural enemies.', 'Causes stippling'),
('tarnished plant bug', 'Maduming kulisap ng halaman', 'insect', 'medium', 'Causes fruit deformity. 10-25% yield loss.', '1 bug per 6 plants', 'Apply acephate 500g/ha or bifenthrin 200ml/ha at bud stage. Remove weeds.', 'Use white sticky traps'),
('Locustoidea', 'Tipaklong', 'insect', 'critical', 'Swarms destroy entire fields. 100% crop loss possible.', 'Swarm presence', 'Apply malathion 1000ml/ha or lambda-cyhalothrin 250ml/ha. Coordinate with neighbors. Report immediately.', 'IMMEDIATE ACTION REQUIRED'),
('lytta polita', 'Blister beetle (salagubang na nagdudulot ng paltos)', 'beetle', 'low', 'Blister beetle. <5% defoliation.', 'Presence of beetles', 'Hand-pick with gloves. Usually sporadic.', 'Avoid in hay - toxic'),
('legume blister beetle', 'Paltos-salagubang sa munggo', 'beetle', 'low', 'Occasional pest. <5% defoliation.', 'Beetles present', 'Hand-pick if necessary. Usually sporadic.', 'Concern in hay production'),
('blister beetle', 'Salagubang-paltos', 'beetle', 'medium', 'Defoliates crops. 10-20% defoliation.', 'High population', 'Hand-pick with gloves. Apply carbaryl 1kg/ha if severe.', 'Toxic to livestock'),
('therioaphis maculata Buckton', 'Kuto ng alfalfa', 'insect', 'low', 'Spotted alfalfa aphid. <8% yield loss.', '40 aphids per stem', 'Monitor population. Usually controlled by predators.', 'Ladybugs provide control'),
('odontothrips loti', 'Thrips sa munggo', 'insect', 'low', 'Clover thrips. <5% yield loss.', 'High population', 'Monitor only. Natural enemies control.', 'Usually minor'),
('Thrips', 'Kulisap / Tripes', 'insect', 'high', 'Transmits viruses, causes scarring. 15-40% yield loss.', '5 thrips per flower', 'Apply spinosad 200ml/ha or abamectin 500ml/ha. Use blue sticky traps. Remove weeds.', '30 per plant threshold'),
('alfalfa seed chalcid', 'Kulisap ng binhi ng alfalfa', 'insect', 'low', 'Affects seed production. <10% seed loss.', 'Seed fields only', 'Monitor seed fields. Use early-maturing varieties.', 'Not significant in forage'),
('Pieris canidia', 'Puting paru-paro ng repolyo', 'insect', 'medium', 'Cabbage butterfly. 15-30% yield loss.', '0.3 larvae per plant', 'Hand-pick caterpillars and eggs. Apply Bt 1kg/ha or spinosad 200ml/ha. Use row covers.', 'Yellow eggs on undersides'),
('Apolygus lucorum', 'Kulisap sa bulak / cotton bug', 'insect', 'medium', 'Mirid bug causes fruit drop. 10-25% yield loss.', '1 bug per 10 plants', 'Apply imidacloprid 200ml/ha at bud stage. Remove alternate hosts. Use pheromone traps.', 'Causes fruit deformity'),
-- Fruit Tree Pests
('Limacodidae', 'Uod-balat / slug caterpillar', 'insect', 'low', 'Slug caterpillars. <5% defoliation.', 'Caterpillars present', 'Hand-pick with gloves. Usually minor.', 'Stinging hairs - nuisance'),
('Viteus vitifoliae', 'Kulisap ng ubas', 'insect', 'low', 'Grape phylloxera. <5% yield loss on resistant rootstocks.', 'Galls present', 'Use resistant rootstocks. Monitor for galls.', 'Not problematic on grafted vines'),
('Colomerus vitis', 'Hama sa ubas', 'mite', 'low', 'Grape erineum mite. <5% yield loss.', 'Leaf galls present', 'Prune affected leaves. Rarely requires treatment.', 'Cosmetic damage'),
('Brevipoalpus lewisi McGregor', 'Pulang mite ng prutas', 'mite', 'low', 'False spider mite. <5% yield loss.', 'Mites present', 'Monitor only. Natural predators control.', 'Usually minor'),
('oides decempunctata', 'Salagubang ng dahon', 'beetle', 'low', 'Leaf beetle. <8% defoliation.', 'High population', 'Monitor and hand-pick if necessary.', 'Usually not significant'),
('Polyphagotars onemus latus', 'Broad mite / Hama sa dahon', 'mite', 'low', 'Broad mite. <10% yield loss.', 'Leaf distortion', 'Apply abamectin only if severe.', 'Usually minor'),
('Pseudococcus comstocki Kuwana', 'Mealybug / Malagkit na kuto', 'insect', 'low', 'Comstock mealybug. <8% yield loss.', 'Mealybugs present', 'Apply horticultural oil 20ml/L. Introduce Cryptolaemus.', 'Natural enemies help'),
('parathrene regalis', 'Uod-tagabutas ng puno', 'insect', 'low', 'Clearwing moth borer. <5% yield loss.', 'Entry holes present', 'Prune affected branches. Usually sporadic.', 'Monitor for frass'),
('Ampelophaga', 'Uod ng ubas (Ampelophaga)', 'insect', 'low', 'Hawk moth caterpillar. <5% defoliation.', 'Large caterpillars', 'Hand-pick caterpillars. Usually not severe.', 'Easy to spot'),
('Lycorma delicatula', 'Spotted lanternfly / Lanternfly ng ubas', 'insect', 'low', 'Spotted lanternfly. <10% yield loss.', 'Egg masses or adults', 'Scrape egg masses. Apply contact insecticide. Remove tree of heaven.', 'Established areas only'),
('Xylotrechus', 'Bukbok ng kahoy', 'beetle', 'low', 'Longhorn beetle. <5% tree mortality.', 'Infested wood', 'Remove and destroy infested wood. Maintain tree vigor.', 'Attacks weakened trees'),
('Cicadella viridis', 'Berdeng leafhopper', 'insect', 'low', 'Green leafhopper. <5% yield loss.', 'High population', 'Monitor only. Natural enemies control.', 'Usually minor'),
('Miridae', 'Kulisap ng halaman (Mirid bug)', 'insect', 'low', 'Plant bugs. <8% yield loss.', 'Damage present', 'Monitor for stippling. Usually minor.', 'Various species'),
('Trialeurodes vaporariorum', 'Whitefly / Puting langaw', 'insect', 'low', 'Greenhouse whitefly. <10% yield loss.', '10 per leaf', 'Apply insecticidal soap 20ml/L. Use yellow sticky traps. Release Encarsia wasps.', 'Protected cultivation'),
('Erythroneura apicalis', 'Red-tipped leafhopper', 'insect', 'low', 'Grape leafhopper. <5% yield loss.', '15 nymphs per leaf', 'Monitor population. Natural enemies control.', 'Causes stippling'),
('Papilio xuthus', 'Paru-parong dilaw (Swallowtail)', 'insect', 'low', 'Swallowtail butterfly. <3% defoliation.', 'Caterpillars present', 'Hand-pick if needed. Usually aesthetic only.', 'Often beneficial for pollination'),
-- Citrus Pests
('Panonchus citri McGregor', 'Red mite ng dalandan', 'mite', 'low', 'Citrus red mite. <8% yield loss.', '5-10 mites per leaf', 'Apply horticultural oil 20ml/L. Usually controlled by predatory mites.', 'Avoid broad-spectrum insecticides'),
('Phyllocoptes oleiverus ashmead', 'Olive mite', 'mite', 'low', 'Citrus rust mite. <5% cosmetic damage.', 'Bronzing present', 'Apply sulfur 3g/L or oil if severe. Usually minor.', 'Some bronzing normal'),
('Icerya purchasi Maskell', 'Cottony cushion scale / Kuto-buhok', 'insect', 'low', 'Cottony cushion scale. <5% yield loss.', 'Scales present', 'Introduce vedalia beetle. Apply oil only if severe.', 'Biocontrol usually sufficient'),
('Unaspis yanonensis', 'Scale insect ng dalandan', 'insect', 'low', 'Arrowhead scale. <8% yield loss.', 'Scales present', 'Apply horticultural oil during dormant season. Prune infested branches.', 'Usually minor'),
('Ceroplastes rubens', 'Pulang scale insect', 'insect', 'low', 'Red wax scale. <8% yield loss.', 'Scales present', 'Apply horticultural oil. Prune infested branches. Natural enemies help.', 'Parasitic wasps control'),
('Chrysomphalus aonidum', 'Armored scale insect', 'insect', 'low', 'Florida red scale. <8% yield loss.', 'Scales present', 'Apply horticultural oil. Introduce Aphytis wasps.', 'Biocontrol available'),
('Parlatoria zizyphus Lucus', 'Kuto ng jujube / zizyphus', 'insect', 'low', 'Black parlatoria scale. <5% yield loss.', 'Scales present', 'Apply horticultural oil. Maintain tree vigor.', 'Usually minor'),
('Nipaecoccus vastalor', 'Nipa mealybug / Mealybug ng niyog', 'insect', 'low', 'Mealybug. <8% yield loss.', 'Mealybugs present', 'Apply insecticidal soap or neem oil. Introduce Cryptolaemus and Leptomastix.', 'Natural enemies available'),
('Aleurocanthus spiniferus', 'Blackfly ng sitrus', 'insect', 'low', 'Orange spiny whitefly. <10% yield loss.', 'Whiteflies present', 'Apply horticultural oil. Use yellow sticky traps. Encarsia wasps control.', 'Natural enemies help'),
('Tetradacus c Bactrocera minax', 'Prutas na langaw (Fruit fly)', 'insect', 'critical', 'Chinese citrus fly. 40-90% fruit drop.', 'Flies present', 'Install protein bait traps. Apply spinosad + protein bait weekly. Collect fallen fruit daily.', 'Bag fruits if high-value'),
('Dacus dorsalis(Hendel)', 'Langaw ng prutas / Mango fruit fly', 'insect', 'critical', 'Oriental fruit fly. 50-100% fruit damage.', 'Flies present', 'Mass trapping with methyl eugenol. Apply spinosad bait spray. Remove all fallen fruit.', 'Attacks 150+ hosts'),
('Bactrocera tsuneonis', 'Langaw ng prutas (Tsuneo type)', 'insect', 'low', 'Fruit fly. <10% fruit damage.', 'Flies present', 'Use protein bait traps. Practice sanitation.', 'Less damaging than major species'),
('Prodenia litura', 'Uod-hukbo ng gulay', 'insect', 'high', 'Tobacco cutworm. 25-50% yield loss.', '2-3 larvae per plant', 'Apply Bt 1kg/ha or spinosad 200ml/ha in evening. Hand-pick larvae. Use pheromone traps.', 'Attacks 120+ crops'),
('Adristyrannus', 'Uod ng kahoy / wood borer', 'insect', 'low', 'Minor pest. <5% yield loss.', 'High population', 'Monitor only. Usually not significant.', 'Natural control adequate'),
('Phyllocnistis citrella Stainton', 'Leaf miner ng sitrus', 'insect', 'low', 'Citrus leafminer. <5% yield loss on mature trees.', 'Mines on young trees', 'Apply spinosad or abamectin on young trees only. Mature trees tolerate.', 'Prune affected shoots'),
('Toxoptera citricidus', 'Itim na kuto ng sitrus', 'insect', 'medium', 'Brown citrus aphid. 10-30% yield loss.', '10% shoots infested', 'Apply imidacloprid or thiamethoxam. Preserve ladybugs. Prune infested shoots.', 'Transmits tristeza virus'),
('Toxoptera aurantii', 'Kuto ng dahon ng dalandan', 'insect', 'medium', 'Black citrus aphid. 5-15% yield loss.', 'Aphids present', 'Apply insecticidal soap or neem oil. Introduce parasitic wasps. Prune water sprouts.', 'Less damaging than brown'),
('Aphis citricola Vander Goot', 'Kuto ng prutas-sitrus', 'insect', 'low', 'Spiraea aphid. <5% yield loss.', 'Aphids present', 'Apply insecticidal soap. Usually minor.', 'Natural enemies control'),
('Scirtothrips dorsalis Hood', 'Thrips ng sitrus', 'insect', 'low', 'Chilli thrips. <10% cosmetic damage.', 'Thrips present', 'Apply spinosad or abamectin. Remove weeds. Use blue sticky traps.', 'Causes fruit scarring'),
('Dasineura sp', 'Gall midge / Lamok-lamok sa usbong', 'insect', 'low', 'Gall midge. <8% yield loss.', 'Galls present', 'Prune and destroy galls. Apply insecticide only if severe.', 'Usually minor'),
('Lawana imitata Melichar', 'Puting planthopper ng mangga', 'insect', 'low', 'Planthopper. <5% yield loss.', 'High population', 'Monitor population. Natural enemies control.', 'Usually minor'),
('Salurnis marginella Guerr', 'Kulisap ng mangga', 'insect', 'low', 'Minor pest. <5% yield loss.', 'Presence', 'Monitor only. Usually not significant.', 'Rarely requires action'),
('Deporaus marginatus Pascoe', 'Weevil ng mangga', 'beetle', 'low', 'Weevil. <5% yield loss.', 'Weevils present', 'Monitor and hand-pick if present.', 'Minor occurrence'),
('Chlumetia transversa', 'Uod ng mangga (mango leaf caterpillar)', 'insect', 'low', 'Minor pest. <5% yield loss.', 'Presence', 'Monitor population. Usually not significant.', 'Natural control adequate'),
('Mango flat beak leafhopper', 'Patag-ilong na leafhopper ng mangga', 'insect', 'high', 'Causes hopper burn. 15-35% yield loss.', 'Hoppers present at flowering', 'Apply imidacloprid or thiamethoxam at panicle emergence. Prune affected branches.', 'Two applications 15 days apart'),
('Rhytidodera bowrinii white', 'Bukbok ng mangga', 'beetle', 'low', 'Longhorn beetle. <5% tree damage.', 'Infested wood', 'Remove and destroy infested wood. Maintain tree vigor.', 'Attacks stressed trees'),
('Sternochetus frigidus', 'Butas-butil ng mangga', 'beetle', 'low', 'Weevil. <8% yield loss.', 'High population', 'Monitor and apply insecticide if high.', 'Usually minor'),
('Cicadellidae', 'Pamilyang leafhopper / Tagtalon-dahon', 'insect', 'low', 'Leafhoppers. <8% yield loss.', 'High population', 'Monitor population. Usually controlled by natural enemies.', 'Various species');
