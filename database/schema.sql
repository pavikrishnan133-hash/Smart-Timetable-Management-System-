-- ==============================================================================
-- SMART ACADEMIC TIMETABLE MANAGEMENT SYSTEM
-- Department: B.Voc SD & SA (Software Development & System Administration)
-- Relational MySQL Database Schema
-- ==============================================================================

CREATE DATABASE IF NOT EXISTS `timetable_management_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `timetable_management_db`;

-- 1. Admins Table
CREATE TABLE IF NOT EXISTS `admins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `role` VARCHAR(30) DEFAULT 'Admin',
    `avatar` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Academic Years Table (1st Year, 2nd Year, 3rd Year)
CREATE TABLE IF NOT EXISTS `academic_years` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `year_name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(30) NOT NULL UNIQUE,
    `semester` VARCHAR(50) NOT NULL,
    `batch_strength` INT DEFAULT 30,
    `academic_session` VARCHAR(30) DEFAULT '2026-2027',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Staff / Faculty Table
CREATE TABLE IF NOT EXISTS `staff` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `staff_code` VARCHAR(30) NOT NULL UNIQUE,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `designation` VARCHAR(100) NOT NULL DEFAULT 'Assistant Professor',
    `department` VARCHAR(100) DEFAULT 'B.Voc SD & SA',
    `role` ENUM('HOD', 'Faculty') NOT NULL DEFAULT 'Faculty',
    `max_weekly_hours` INT NOT NULL DEFAULT 18,
    `color_code` VARCHAR(20) DEFAULT '#3B82F6',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Staff Availability Matrix Table (Day & Period availability)
CREATE TABLE IF NOT EXISTS `staff_availability` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `staff_id` INT NOT NULL,
    `day_of_week` INT NOT NULL,
    `period_index` INT NOT NULL,
    `is_available` TINYINT(1) DEFAULT 1,
    `preference` ENUM('preferred', 'neutral', 'unavailable') DEFAULT 'neutral',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`staff_id`) REFERENCES `staff`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `staff_day_period_unique` (`staff_id`, `day_of_week`, `period_index`),
    INDEX `idx_staff_avail` (`staff_id`, `day_of_week`, `period_index`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Classrooms & Labs Table
CREATE TABLE IF NOT EXISTS `classrooms` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `room_number` VARCHAR(30) NOT NULL UNIQUE,
    `room_name` VARCHAR(100) NOT NULL,
    `room_type` ENUM('Theory Classroom', 'Computer Lab', 'General Lab') NOT NULL DEFAULT 'Theory Classroom',
    `capacity` INT NOT NULL DEFAULT 40,
    `block_floor` VARCHAR(50) DEFAULT 'Main Block - Floor 2',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Subjects Table
CREATE TABLE IF NOT EXISTS `subjects` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `subject_code` VARCHAR(30) NOT NULL,
    `subject_name` VARCHAR(150) NOT NULL,
    `academic_year_id` INT NOT NULL,
    `category` ENUM('Major Theory', 'Major Lab', 'Skill Theory', 'Skill Lab', 'Common Theory', 'Project') NOT NULL,
    `weekly_hours` INT NOT NULL DEFAULT 4,
    `assigned_faculty_id` INT NOT NULL,
    `secondary_faculty_id` INT DEFAULT NULL,
    `preferred_room_type` ENUM('Theory Classroom', 'Computer Lab', 'General Lab') NOT NULL DEFAULT 'Theory Classroom',
    `is_consecutive_required` TINYINT(1) DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`assigned_faculty_id`) REFERENCES `staff`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`secondary_faculty_id`) REFERENCES `staff`(`id`) ON DELETE SET NULL,
    UNIQUE KEY `year_subject_code_unique` (`academic_year_id`, `subject_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Schedule Settings
CREATE TABLE IF NOT EXISTS `schedule_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT NOT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Period Definitions
CREATE TABLE IF NOT EXISTS `periods` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `period_index` INT NOT NULL UNIQUE,
    `period_name` VARCHAR(50) NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `is_break_after` TINYINT(1) DEFAULT 0,
    `break_name` VARCHAR(50) DEFAULT NULL,
    `break_start_time` TIME DEFAULT NULL,
    `break_end_time` TIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Generated Timetables Master Record
CREATE TABLE IF NOT EXISTS `timetables` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(150) NOT NULL,
    `academic_year_id` INT NOT NULL,
    `academic_session` VARCHAR(50) DEFAULT '2026-2027',
    `version` INT DEFAULT 1,
    `quality_score` INT DEFAULT 0,
    `quality_details` JSON DEFAULT NULL,
    `total_conflicts` INT DEFAULT 0,
    `is_published` TINYINT(1) DEFAULT 1,
    `created_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Timetable Entries Table
CREATE TABLE IF NOT EXISTS `timetable_entries` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `timetable_id` INT NOT NULL,
    `academic_year_id` INT NOT NULL,
    `day_of_week` INT NOT NULL,
    `period_index` INT NOT NULL,
    `subject_id` INT NOT NULL,
    `faculty_id` INT NOT NULL,
    `classroom_id` INT NOT NULL,
    `is_locked` TINYINT(1) DEFAULT 0,
    `notes` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`timetable_id`) REFERENCES `timetables`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`faculty_id`) REFERENCES `staff`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`classroom_id`) REFERENCES `classrooms`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_class_day_period` (`academic_year_id`, `day_of_week`, `period_index`),
    INDEX `idx_tt_entries_lookup` (`day_of_week`, `period_index`, `faculty_id`, `classroom_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Timetable Conflict Logs Table
CREATE TABLE IF NOT EXISTS `conflict_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `timetable_id` INT DEFAULT NULL,
    `academic_year_id` INT DEFAULT NULL,
    `conflict_type` ENUM('faculty_clash', 'room_clash', 'workload_exceeded', 'unmet_hours', 'unavailable_slot', 'lab_split') NOT NULL,
    `severity` ENUM('high', 'medium', 'low') NOT NULL DEFAULT 'high',
    `day_of_week` INT DEFAULT NULL,
    `period_index` INT DEFAULT NULL,
    `faculty_id` INT DEFAULT NULL,
    `classroom_id` INT DEFAULT NULL,
    `subject_id` INT DEFAULT NULL,
    `message` TEXT NOT NULL,
    `details_json` JSON DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Students Table
CREATE TABLE IF NOT EXISTS `students` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `register_number` VARCHAR(50) NOT NULL UNIQUE,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `phone` VARCHAR(20) DEFAULT NULL,
    `academic_year_id` INT NOT NULL,
    `section` VARCHAR(20) DEFAULT 'A',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years`(`id`) ON DELETE CASCADE,
    INDEX `idx_students_year` (`academic_year_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

