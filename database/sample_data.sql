-- Sample Data for IoT Farm Monitoring System
-- This file contains static sample data for demonstration purposes

-- Insert sample users with different roles
-- All users have password: "password"
INSERT INTO users (username, password, email, role, status, last_login) VALUES
('admin', '$2y$10$oiF/VWG/vKYk8g3yDNcNk.jRVf17DScq2mN/IVOpDAOm/q4ol8KGe', 'admin@farm.com', 'admin', 'active', NOW()),
('farmer1', '$2y$10$oiF/VWG/vKYk8g3yDNcNk.jRVf17DScq2mN/IVOpDAOm/q4ol8KGe', 'farmer1@farm.com', 'farmer', 'active', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
('student1', '$2y$10$oiF/VWG/vKYk8g3yDNcNk.jRVf17DScq2mN/IVOpDAOm/q4ol8KGe', 'student1@university.edu', 'student', 'active', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('farmer2', '$2y$10$oiF/VWG/vKYk8g3yDNcNk.jRVf17DScq2mN/IVOpDAOm/q4ol8KGe', 'farmer2@farm.com', 'farmer', 'active', DATE_SUB(NOW(), INTERVAL 3 HOUR)),
('student2', '$2y$10$oiF/VWG/vKYk8g3yDNcNk.jRVf17DScq2mN/IVOpDAOm/q4ol8KGe', 'student2@university.edu', 'student', 'inactive', NULL);

-- Insert sample sensors
INSERT INTO sensors (sensor_name, sensor_type, location, status) VALUES
('Greenhouse Temp Sensor 1', 'temperature', 'Greenhouse A - North', 'online'),
('Greenhouse Temp Sensor 2', 'temperature', 'Greenhouse A - South', 'online'),
('Field Temp Sensor 1', 'temperature', 'Field B - Center', 'offline'),
('Greenhouse Humidity 1', 'humidity', 'Greenhouse A - North', 'online'),
('Greenhouse Humidity 2', 'humidity', 'Greenhouse A - South', 'online'),
('Field Humidity 1', 'humidity', 'Field B - Center', 'online'),
('Soil Moisture 1', 'soil_moisture', 'Field A - Section 1', 'online'),
('Soil Moisture 2', 'soil_moisture', 'Field A - Section 2', 'online'),
('Soil Moisture 3', 'soil_moisture', 'Field B - Section 1', 'offline'),
('Greenhouse Soil 1', 'soil_moisture', 'Greenhouse A - Bed 1', 'online');

-- Insert sample sensor readings (last 7 days of data)
-- Temperature readings (18°C - 35°C range)
INSERT INTO sensor_readings (sensor_id, value, unit, recorded_at) VALUES
-- Greenhouse Temp Sensor 1 (ID: 1)
(1, 24.5, '°C', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(1, 25.2, '°C', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(1, 23.8, '°C', DATE_SUB(NOW(), INTERVAL 3 HOUR)),
(1, 22.1, '°C', DATE_SUB(NOW(), INTERVAL 6 HOUR)),
(1, 20.5, '°C', DATE_SUB(NOW(), INTERVAL 12 HOUR)),
(1, 26.8, '°C', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1, 28.2, '°C', DATE_SUB(NOW(), INTERVAL 2 DAY)),

-- Greenhouse Temp Sensor 2 (ID: 2)
(2, 26.1, '°C', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(2, 26.8, '°C', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(2, 25.4, '°C', DATE_SUB(NOW(), INTERVAL 3 HOUR)),
(2, 23.7, '°C', DATE_SUB(NOW(), INTERVAL 6 HOUR)),
(2, 21.9, '°C', DATE_SUB(NOW(), INTERVAL 12 HOUR)),
(2, 27.5, '°C', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, 29.1, '°C', DATE_SUB(NOW(), INTERVAL 2 DAY)),

-- Humidity readings (40% - 85% range)
-- Greenhouse Humidity 1 (ID: 4)
(4, 68.5, '%', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(4, 70.2, '%', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(4, 65.8, '%', DATE_SUB(NOW(), INTERVAL 3 HOUR)),
(4, 62.1, '%', DATE_SUB(NOW(), INTERVAL 6 HOUR)),
(4, 58.5, '%', DATE_SUB(NOW(), INTERVAL 12 HOUR)),
(4, 72.8, '%', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(4, 75.2, '%', DATE_SUB(NOW(), INTERVAL 2 DAY)),

-- Greenhouse Humidity 2 (ID: 5)
(5, 71.3, '%', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(5, 73.1, '%', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(5, 69.7, '%', DATE_SUB(NOW(), INTERVAL 3 HOUR)),
(5, 66.4, '%', DATE_SUB(NOW(), INTERVAL 6 HOUR)),
(5, 63.2, '%', DATE_SUB(NOW(), INTERVAL 12 HOUR)),
(5, 76.1, '%', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(5, 78.5, '%', DATE_SUB(NOW(), INTERVAL 2 DAY)),

-- Field Humidity 1 (ID: 6)
(6, 45.8, '%', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(6, 48.2, '%', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(6, 42.1, '%', DATE_SUB(NOW(), INTERVAL 3 HOUR)),
(6, 41.5, '%', DATE_SUB(NOW(), INTERVAL 6 HOUR)),
(6, 39.8, '%', DATE_SUB(NOW(), INTERVAL 12 HOUR)),
(6, 52.3, '%', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(6, 55.7, '%', DATE_SUB(NOW(), INTERVAL 2 DAY)),

-- Soil Moisture readings (20% - 80% range)
-- Soil Moisture 1 (ID: 7)
(7, 45.2, '%', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(7, 44.8, '%', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(7, 46.1, '%', DATE_SUB(NOW(), INTERVAL 3 HOUR)),
(7, 43.5, '%', DATE_SUB(NOW(), INTERVAL 6 HOUR)),
(7, 41.2, '%', DATE_SUB(NOW(), INTERVAL 12 HOUR)),
(7, 48.7, '%', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(7, 52.1, '%', DATE_SUB(NOW(), INTERVAL 2 DAY)),

-- Soil Moisture 2 (ID: 8)
(8, 38.7, '%', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(8, 37.2, '%', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(8, 39.8, '%', DATE_SUB(NOW(), INTERVAL 3 HOUR)),
(8, 35.1, '%', DATE_SUB(NOW(), INTERVAL 6 HOUR)),
(8, 32.8, '%', DATE_SUB(NOW(), INTERVAL 12 HOUR)),
(8, 42.3, '%', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(8, 45.9, '%', DATE_SUB(NOW(), INTERVAL 2 DAY)),

-- Greenhouse Soil 1 (ID: 10)
(10, 62.4, '%', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(10, 61.8, '%', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(10, 63.2, '%', DATE_SUB(NOW(), INTERVAL 3 HOUR)),
(10, 59.7, '%', DATE_SUB(NOW(), INTERVAL 6 HOUR)),
(10, 57.3, '%', DATE_SUB(NOW(), INTERVAL 12 HOUR)),
(10, 65.1, '%', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(10, 67.8, '%', DATE_SUB(NOW(), INTERVAL 2 DAY));

-- Insert sample cameras
INSERT INTO cameras (camera_name, location, ip_address, port, username, password, camera_type, resolution, fps, detection_enabled, detection_sensitivity, status, last_detection) VALUES
('Greenhouse A - North Camera', 'Greenhouse A - North', '192.168.1.101', 80, 'admin', '$2y$10$oiF/VWG/vKYk8g3yDNcNk.jRVf17DScq2mN/IVOpDAOm/q4ol8KGe', 'ip_camera', '1920x1080', 30, TRUE, 'high', 'online', DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
('Greenhouse A - South Camera', 'Greenhouse A - South', '192.168.1.102', 80, 'admin', '$2y$10$oiF/VWG/vKYk8g3yDNcNk.jRVf17DScq2mN/IVOpDAOm/q4ol8KGe', 'ip_camera', '1920x1080', 30, TRUE, 'medium', 'online', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
('Field B - Center Camera', 'Field B - Center', '192.168.1.103', 80, 'admin', '$2y$10$oiF/VWG/vKYk8g3yDNcNk.jRVf17DScq2mN/IVOpDAOm/q4ol8KGe', 'ip_camera', '1280x720', 25, TRUE, 'medium', 'offline', NULL),
('Field A - Section 1 Camera', 'Field A - Section 1', '192.168.1.104', 80, 'admin', '$2y$10$oiF/VWG/vKYk8g3yDNcNk.jRVf17DScq2mN/IVOpDAOm/q4ol8KGe', 'ip_camera', '1920x1080', 30, TRUE, 'high', 'online', DATE_SUB(NOW(), INTERVAL 4 HOUR)),
('Field A - Section 2 Camera', 'Field A - Section 2', '192.168.1.105', 80, 'admin', '$2y$10$oiF/VWG/vKYk8g3yDNcNk.jRVf17DScq2mN/IVOpDAOm/q4ol8KGe', 'ip_camera', '1920x1080', 30, FALSE, 'low', 'error', NULL),
('Greenhouse A - Bed 1 Camera', 'Greenhouse A - Bed 1', '192.168.1.106', 80, 'admin', '$2y$10$oiF/VWG/vKYk8g3yDNcNk.jRVf17DScq2mN/IVOpDAOm/q4ol8KGe', 'ip_camera', '1280x720', 25, TRUE, 'high', 'online', DATE_SUB(NOW(), INTERVAL 1 HOUR));

-- Insert sample pest alerts (updated with camera references)
INSERT INTO pest_alerts (camera_id, pest_type, location, severity, status, confidence_score, image_path, description, suggested_actions, detected_at) VALUES
(1, 'Aphids', 'Greenhouse A - North', 'medium', 'new', 87.5, '/uploads/detections/aphids_001.jpg', 'AI detected small colony of aphids on tomato plants. Population appears to be growing based on image analysis.', 'Apply insecticidal soap spray. Monitor daily for population changes. Consider introducing ladybugs as biological control.', DATE_SUB(NOW(), INTERVAL 2 HOUR)),

(4, 'Caterpillars', 'Field A - Section 1', 'high', 'acknowledged', 92.3, '/uploads/detections/caterpillars_001.jpg', 'Multiple caterpillars detected on corn stalks. AI analysis shows significant leaf damage patterns.', 'Apply Bt (Bacillus thuringiensis) spray immediately. Inspect neighboring plants. Consider pheromone traps for monitoring.', DATE_SUB(NOW(), INTERVAL 6 HOUR)),

(2, 'Fungal Infection', 'Greenhouse A - South', 'critical', 'new', 95.8, '/uploads/detections/fungal_001.jpg', 'Powdery mildew detected spreading rapidly on cucumber plants. High humidity conditions detected by camera sensors.', 'Reduce humidity levels immediately. Apply fungicide treatment. Remove affected leaves. Improve air circulation.', DATE_SUB(NOW(), INTERVAL 30 MINUTE)),

(5, 'Spider Mites', 'Field A - Section 2', 'low', 'resolved', 78.2, '/uploads/detections/mites_001.jpg', 'Minor spider mite activity detected on bean plants through AI image analysis. Population now controlled.', 'Continue monitoring. Maintain adequate soil moisture. Previous neem oil treatment was effective.', DATE_SUB(NOW(), INTERVAL 1 DAY)),

(4, 'Beetles', 'Field A - Section 1', 'medium', 'acknowledged', 89.1, '/uploads/detections/beetles_001.jpg', 'Colorado potato beetles detected on potato plants. AI identified early instar larvae present.', 'Hand-pick adult beetles and egg masses. Apply spinosad if population increases. Monitor weekly.', DATE_SUB(NOW(), INTERVAL 4 HOUR)),

(6, 'Whiteflies', 'Greenhouse A - Bed 1', 'high', 'new', 91.7, '/uploads/detections/whiteflies_001.jpg', 'Large whitefly population detected on pepper plants. Camera analysis shows sticky honeydew present on leaves.', 'Install yellow sticky traps immediately. Apply horticultural oil spray. Consider beneficial insects like Encarsia formosa.', DATE_SUB(NOW(), INTERVAL 1 HOUR)),

(3, 'Thrips', 'Field B - Center', 'low', 'acknowledged', 82.4, '/uploads/detections/thrips_001.jpg', 'Western flower thrips detected on lettuce through AI analysis. Minor leaf stippling patterns observed.', 'Monitor population levels. Blue sticky traps recommended. Predatory mites may provide control.', DATE_SUB(NOW(), INTERVAL 8 HOUR)),

(2, 'Root Rot', 'Greenhouse A - South', 'high', 'new', 88.9, '/uploads/detections/root_rot_001.jpg', 'Pythium root rot symptoms detected in hydroponic system. AI analysis shows plants with wilting symptoms.', 'Check water pH and oxygen levels. Replace nutrient solution. Apply beneficial bacteria treatment. Improve drainage.', DATE_SUB(NOW(), INTERVAL 3 HOUR));

-- Insert sample user settings
INSERT INTO user_settings (user_id, setting_key, setting_value) VALUES
(1, 'theme', 'dark'),
(1, 'dashboard_layout', 'grid'),
(1, 'notifications_email', 'true'),
(1, 'notifications_pest_alerts', 'true'),
(1, 'chart_refresh_interval', '300'),

(2, 'theme', 'light'),
(2, 'dashboard_layout', 'list'),
(2, 'notifications_email', 'false'),
(2, 'notifications_pest_alerts', 'true'),
(2, 'chart_refresh_interval', '600'),

(3, 'theme', 'light'),
(3, 'dashboard_layout', 'grid'),
(3, 'notifications_email', 'true'),
(3, 'notifications_pest_alerts', 'false'),
(3, 'chart_refresh_interval', '300');