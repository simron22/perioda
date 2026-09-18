-- =====================================================
-- PERIODA - Period Tracker & Women's Health Management System
-- Database Schema
-- Import this file through phpMyAdmin (XAMPP)
-- =====================================================

CREATE DATABASE IF NOT EXISTS perioda CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE perioda;

-- -----------------------------------------------------
-- Table: users
-- Stores both normal users and the admin account
-- -----------------------------------------------------
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(15) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    status ENUM('active', 'disabled') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Table: period_records
-- Each row = one recorded menstrual period for a user
-- -----------------------------------------------------
CREATE TABLE period_records (
    period_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    estimated_ovulation_date DATE NULL,
    flow ENUM('light', 'medium', 'heavy') DEFAULT 'medium',
    notes VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Table: symptoms
-- -----------------------------------------------------
CREATE TABLE symptoms (
    symptom_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    period_id INT NULL,
    symptom VARCHAR(50) NOT NULL,
    symptom_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (period_id) REFERENCES period_records(period_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Table: moods
-- -----------------------------------------------------
CREATE TABLE moods (
    mood_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    period_id INT NULL,
    mood VARCHAR(30) NOT NULL,
    mood_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (period_id) REFERENCES period_records(period_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Table: notes
-- -----------------------------------------------------
CREATE TABLE notes (
    note_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    period_id INT NULL,
    note_text VARCHAR(1000) NOT NULL,
    note_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (period_id) REFERENCES period_records(period_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Table: health_tips
-- -----------------------------------------------------
CREATE TABLE health_tips (
    tip_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    category ENUM('Menstrual Hygiene','Nutrition','Exercise','Rest and Sleep','Period Comfort','General Wellness') NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Default admin account
-- Email: admin@kathford.edu.np | Password: Admin@123
-- (hash generated with PHP password_hash, bcrypt)
-- -----------------------------------------------------
INSERT INTO users (full_name, email, phone, password, role, status) VALUES
('System Admin', 'admin@kathford.edu.np', '9812345678', '$2y$10$NKFQd8bXhU2.lG4jOqXk3ekGVV13sZ0T0YMzjQMX5UhvAOtnXbyI6', 'admin', 'active');

-- -----------------------------------------------------
-- Sample health tips
-- -----------------------------------------------------
INSERT INTO health_tips (title, category, content) VALUES
('Change Products Regularly', 'Menstrual Hygiene', 'Change your pad, tampon, or menstrual cup regularly (every 4-6 hours) to maintain proper hygiene and reduce the risk of infection.'),
('Keep Reusable Products Clean', 'Menstrual Hygiene', 'If you use reusable menstrual products such as cloth pads or a menstrual cup, wash and sterilize them properly between uses.'),
('Eat a Balanced Diet', 'Nutrition', 'Maintain a balanced diet rich in iron, calcium, and vitamins during your period to help replenish nutrients lost during menstruation.'),
('Stay Hydrated', 'Nutrition', 'Drinking enough water supports general well-being and may help you feel more comfortable during your period.'),
('Light Exercise Helps', 'Exercise', 'Light exercise such as walking or stretching may help some people manage period discomfort and improve mood.'),
('Get Adequate Sleep', 'Rest and Sleep', 'Maintain adequate sleep and give your body sufficient rest, especially during your period when energy levels may be lower.'),
('Use a Warm Compress', 'Period Comfort', 'Applying a warm compress or heating pad to your lower abdomen may help ease menstrual cramps.'),
('Track Unusual Symptoms', 'General Wellness', 'Pay attention to unusual or persistent symptoms. If you experience persistent or unusually severe symptoms, consider consulting a qualified healthcare professional.');
