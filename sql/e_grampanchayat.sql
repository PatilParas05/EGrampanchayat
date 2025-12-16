-- e-Grampanchayat Database Schema (CORRECTED VERSION)
-- Drop and recreate database
DROP DATABASE IF EXISTS e_grampanchayat;
CREATE DATABASE e_grampanchayat;
USE e_grampanchayat;

-- Table structure for `users`
CREATE TABLE `users` (
  `user_id` INT(11) NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(255) NOT NULL,
  `aadhaar_number` VARCHAR(12) NOT NULL UNIQUE,
  `phone_number` VARCHAR(15) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('citizen', 'staff', 'admin') NOT NULL DEFAULT 'citizen',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for `applications`
CREATE TABLE `applications` (
  `application_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `certificate_type` VARCHAR(100) NOT NULL,
  `application_details` TEXT NOT NULL,
  `document_path` VARCHAR(255) NOT NULL,
  `status` ENUM('Pending', 'In Review', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
  `remark` TEXT,
  `certificate_download_path` VARCHAR(255) DEFAULT NULL,
  `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`application_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for `complaints` (UPDATED - Added admin_response column)
CREATE TABLE `complaints` (
  `complaint_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `status` ENUM('Pending', 'Submitted', 'Under Investigation', 'In Progress', 'Resolved', 'Closed') NOT NULL DEFAULT 'Submitted',
  `assigned_to` VARCHAR(255) DEFAULT NULL,
  `admin_response` TEXT DEFAULT NULL,
  `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`complaint_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for `notifications`
CREATE TABLE `notifications` (
  `notification_id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `type` ENUM('News', 'Notice', 'Meeting') NOT NULL DEFAULT 'Notice',
  `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
  `published_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`notification_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for `schemes`
CREATE TABLE `schemes` (
  `scheme_id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `eligibility` TEXT NOT NULL,
  `how_to_apply` TEXT NOT NULL,
  `published_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`scheme_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for `assets`
CREATE TABLE `assets` (
  `asset_id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `type` VARCHAR(100) NOT NULL,
  `location` VARCHAR(255) NOT NULL,
  `coordinates` VARCHAR(100) DEFAULT NULL,
  `details` TEXT,
  PRIMARY KEY (`asset_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- INSERT DEFAULT DATA
-- =====================================================

-- Insert Admin User
-- Email: admin@gp.com
-- Password: password
INSERT INTO `users` (`full_name`, `aadhaar_number`, `phone_number`, `email`, `password_hash`, `role`) VALUES
('Gram Panchayat Admin', '123456789012', '9999999999', 'admin@gp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert Staff User
-- Email: staff@gp.com
-- Password: password
INSERT INTO `users` (`full_name`, `aadhaar_number`, `phone_number`, `email`, `password_hash`, `role`) VALUES
('Gram Sevak Staff', '123456789013', '9999999998', 'staff@gp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff');

-- Insert Sample Citizen
-- Email: citizen@example.com
-- Password: citizen123
INSERT INTO `users` (`full_name`, `aadhaar_number`, `phone_number`, `email`, `password_hash`, `role`) VALUES
('Ram Kumar', '987654321098', '9876543210', 'citizen@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'citizen');

-- Insert Sample Notifications
INSERT INTO `notifications` (`title`, `content`, `type`, `is_active`) VALUES
('Welcome to e-Grampanchayat', 'Digital services are now available for all citizens. Apply for certificates online!', 'News', TRUE),
('Gram Sabha Meeting', 'Next Gram Sabha meeting scheduled on 15th December 2024 at 10:00 AM at Community Hall', 'Meeting', TRUE),
('Water Supply Schedule', 'Water supply will be available from 6 AM to 9 AM daily starting next week', 'Notice', TRUE),
('New Road Construction', 'Construction of new village road from Main Square to School will begin from next month', 'News', TRUE),
('Vaccination Drive', 'Free vaccination camp for children will be held on 20th December at Primary Health Center', 'Notice', TRUE);

-- Insert Sample Government Schemes
INSERT INTO `schemes` (`name`, `description`, `eligibility`, `how_to_apply`) VALUES
('Pradhan Mantri Awas Yojana', 
 'Housing scheme for economically weaker sections to provide financial assistance for house construction', 
 'Annual income below Rs. 3 lakh\nNo pucca house owned by family\nBelonging to economically weaker section', 
 'Visit Gram Panchayat office with income certificate, Aadhaar card, and land documents. Fill the application form and submit required documents.'),

('Mahatma Gandhi NREGA', 
 'Employment guarantee scheme providing 100 days of wage employment to rural households', 
 'Adult members of rural households\nWilling to do unskilled manual work\nResident of the village', 
 'Register at Gram Panchayat office with job card application. Submit Aadhaar card and address proof. Job card will be issued within 15 days.'),

('PM-KISAN Samman Nidhi', 
 'Direct income support of Rs. 6000 per year to small and marginal farmers', 
 'Farmers with cultivable land\nLand holding up to 2 hectares\nValid land records', 
 'Apply online at pmkisan.gov.in or visit Gram Panchayat office with land documents and Aadhaar card.'),

('Ayushman Bharat Yojana', 
 'Health insurance scheme providing coverage of Rs. 5 lakh per family per year', 
 'Families belonging to BPL category\nSocio-economic caste census listed families', 
 'Check eligibility online or at Panchayat office. Get Ayushman card made by submitting Aadhaar and family details.'),

('Chief Minister Solar Pump Scheme', 
 'State government subsidy for installing solar water pumps for agricultural purposes', 
 'Farmers with agricultural land\nElectricity connection available\nFunctioning bore well or water source', 
 'Apply through agriculture department or visit Panchayat office. Submit land documents, electricity bill, and bank details. 90% subsidy will be provided.');

-- Insert Sample Village Assets
INSERT INTO `assets` (`name`, `type`, `location`, `details`) VALUES
('Government Primary School', 'School', 'Main Road, Ward 1', '5 classrooms, 150 students capacity, computer lab, library'),
('Primary Health Center', 'Health Center', 'Near Bus Stand, Ward 2', 'Basic medical facilities, 1 doctor, 2 nurses, 24/7 emergency'),
('Community Water Tank', 'Water Tank', 'Central Square, Ward 3', '50,000 liters capacity, supplies water to 200+ families'),
('Village Community Hall', 'Community Hall', 'Panchayat Complex', 'Capacity: 200 people, AC facility, used for meetings and events'),
('Public Library', 'Library', 'Near School, Ward 1', '5000+ books, reading room, free membership for all villagers'),
('Sports Ground', 'Sports Facility', 'Behind School, Ward 1', 'Cricket pitch, football ground, running track');

-- Insert Sample Applications (for testing)
INSERT INTO `applications` (`user_id`, `certificate_type`, `application_details`, `document_path`, `status`) VALUES
(3, 'Income Certificate', 'I am Ram Kumar, requesting income certificate for bank loan purpose. My annual family income is Rs. 2,50,000 from agriculture.', 'uploads/doc_sample_1.pdf', 'Pending'),
(3, 'Residence Certificate', 'I am a permanent resident of this village for the last 15 years. Requesting residence certificate for government job application.', 'uploads/doc_sample_2.pdf', 'In Review');

-- Insert Sample Complaints (for testing)
INSERT INTO `complaints` (`user_id`, `subject`, `description`, `status`, `assigned_to`, `admin_response`) VALUES
(3, 'Street Light Not Working', 'Street light near my house (House No. 45, Ward 1) has not been working for the last 2 weeks. Requesting immediate repair.', 'Submitted', NULL, NULL),
(3, 'Water Supply Issue', 'Water supply is irregular in our area. We are not getting water for the past 3 days. Please look into this matter urgently.', 'Under Investigation', 'Water Department', 'Team has been dispatched to check the pipeline. Will be resolved within 24 hours.');

-- =====================================================
-- VERIFICATION QUERY
-- =====================================================
-- Run this to verify admin user was created:
-- SELECT user_id, full_name, email, role FROM users WHERE role = 'admin';

-- Default Login Credentials:
-- Admin: admin@gp.com / password
-- Staff: staff@gp.com / password  
-- Citizen: citizen@example.com / password