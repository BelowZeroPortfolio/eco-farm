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
