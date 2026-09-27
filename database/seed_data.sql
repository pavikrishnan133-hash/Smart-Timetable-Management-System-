-- ==============================================================================
-- SMART ACADEMIC TIMETABLE MANAGEMENT SYSTEM
-- Department: B.Voc SD & SA (Software Development & System Administration)
-- Rich Demo Seed Data
-- ==============================================================================

USE `timetable_management_db`;

-- Clear existing sample data safely
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `students`;
TRUNCATE TABLE `timetable_entries`;
TRUNCATE TABLE `timetables`;
TRUNCATE TABLE `conflict_logs`;
TRUNCATE TABLE `staff_availability`;
TRUNCATE TABLE `subjects`;
TRUNCATE TABLE `classrooms`;
TRUNCATE TABLE `staff`;
TRUNCATE TABLE `academic_years`;
TRUNCATE TABLE `periods`;
TRUNCATE TABLE `schedule_settings`;
TRUNCATE TABLE `admins`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Default Admin (Username: admin, Password: admin123)
INSERT INTO `admins` (`id`, `username`, `password_hash`, `full_name`, `email`, `role`, `avatar`) VALUES
(1, 'admin', '$2y$10$wT8K8U1fXb6V1yWz47gP3uF6fFf4dG5q1H9e7K8L2M3N4P5Q6R7S8', 'Dr. S. K. Subramanian', 'admin@bvoc-sdsa.edu', 'Head of Department / Admin', 'assets/images/admin_avatar.png');

-- 2. Academic Years (B.Voc SD & SA)
INSERT INTO `academic_years` (`id`, `year_name`, `code`, `semester`, `batch_strength`, `academic_session`, `is_active`) VALUES
(1, '1st Year B.Voc (SD & SA)', 'BVOC-SDSA-Y1', 'Semester 1', 30, '2026-2027', 1),
(2, '2nd Year B.Voc (SD & SA)', 'BVOC-SDSA-Y2', 'Semester 3', 28, '2026-2027', 1),
(3, '3rd Year B.Voc (SD & SA)', 'BVOC-SDSA-Y3', 'Semester 5', 25, '2026-2027', 1);

-- 3. Department Staff / Faculty
-- Roles: HOD (max 14 hours), Faculty (max 18 hours)
INSERT INTO `staff` (`id`, `staff_code`, `full_name`, `email`, `phone`, `designation`, `role`, `max_weekly_hours`, `color_code`, `is_active`) VALUES
(1, 'FAC001', 'Dr. Ramesh Kumar', 'ramesh.hod@bvoc-sdsa.edu', '+91 98401 23456', 'Professor & HOD', 'HOD', 14, '#3B82F6', 1),
(2, 'FAC002', 'Prof. Priya Sharma', 'priya.s@bvoc-sdsa.edu', '+91 98402 34567', 'Associate Professor', 'Faculty', 18, '#10B981', 1),
(3, 'FAC003', 'Prof. Anand Verma', 'anand.v@bvoc-sdsa.edu', '+91 98403 45678', 'Assistant Professor', 'Faculty', 18, '#8B5CF6', 1),
(4, 'FAC004', 'Prof. Deepa Nair', 'deepa.n@bvoc-sdsa.edu', '+91 98404 56789', 'Assistant Professor', 'Faculty', 18, '#F59E0B', 1),
(5, 'FAC005', 'Prof. Karthik Rajan', 'karthik.r@bvoc-sdsa.edu', '+91 98405 67890', 'Assistant Professor', 'Faculty', 18, '#EC4899', 1),
(6, 'FAC006', 'Prof. Sunita Menon', 'sunita.m@bvoc-sdsa.edu', '+91 98406 78901', 'Senior Lecturer', 'Faculty', 18, '#06B6D4', 1),
(7, 'FAC007', 'Prof. Vignesh Sundar', 'vignesh.s@bvoc-sdsa.edu', '+91 98407 89012', 'Assistant Professor', 'Faculty', 18, '#6366F1', 1);

-- 4. Classrooms and Specialized Labs
INSERT INTO `classrooms` (`id`, `room_number`, `room_name`, `room_type`, `capacity`, `block_floor`, `is_active`) VALUES
(1, 'LH-201', '1st Year Lecture Hall', 'Theory Classroom', 40, 'Academic Block - 2nd Floor', 1),
(2, 'LH-202', '2nd Year Lecture Hall', 'Theory Classroom', 40, 'Academic Block - 2nd Floor', 1),
(3, 'LH-203', '3rd Year Lecture Hall', 'Theory Classroom', 35, 'Academic Block - 2nd Floor', 1),
(4, 'LAB-SD01', 'Software Development Lab', 'Computer Lab', 35, 'IT Wing - 3rd Floor', 1),
(5, 'LAB-SA02', 'System Admin & Cloud Lab', 'Computer Lab', 35, 'IT Wing - 3rd Floor', 1),
(6, 'LAB-HW03', 'Hardware & IoT Lab', 'General Lab', 30, 'IT Wing - Ground Floor', 1);

-- 5. Subjects per Academic Year (5 Days * 5 Periods = 25 Hours per Year)
-- 1st Year Subjects (Total 25 Hours)
INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `academic_year_id`, `category`, `weekly_hours`, `assigned_faculty_id`, `preferred_room_type`, `is_consecutive_required`, `is_active`) VALUES
(1, 'SDSA101', 'Programming in C & Data Structures', 1, 'Major Theory', 4, 2, 'Theory Classroom', 0, 1),
(2, 'SDSA102', 'Fundamentals of Computer Systems', 1, 'Major Theory', 4, 3, 'Theory Classroom', 0, 1),
(3, 'SDSA103', 'C Programming & Linux Lab', 1, 'Major Lab', 4, 2, 'Computer Lab', 1, 1),
(4, 'SDSA104', 'Web Essentials & HTML/CSS', 1, 'Skill Theory', 4, 4, 'Theory Classroom', 0, 1),
(5, 'SDSA105', 'Web Design Practical Lab', 1, 'Skill Lab', 4, 4, 'Computer Lab', 1, 1),
(6, 'SDSA106', 'Professional English & Communication', 1, 'Common Theory', 3, 6, 'Theory Classroom', 0, 1),
(7, 'SDSA107', 'Digital Logic & Hardware Lab', 1, 'Skill Lab', 2, 7, 'General Lab', 1, 1);

-- 2nd Year Subjects (Total 25 Hours)
INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `academic_year_id`, `category`, `weekly_hours`, `assigned_faculty_id`, `preferred_room_type`, `is_consecutive_required`, `is_active`) VALUES
(8, 'SDSA301', 'Java Object Oriented Programming', 2, 'Major Theory', 4, 1, 'Theory Classroom', 0, 1),
(9, 'SDSA302', 'Database Management Systems (DBMS)', 2, 'Major Theory', 4, 5, 'Theory Classroom', 0, 1),
(10, 'SDSA303', 'Java & DBMS Application Lab', 2, 'Major Lab', 4, 7, 'Computer Lab', 1, 1),
(11, 'SDSA304', 'Linux Server Administration', 2, 'Skill Theory', 4, 3, 'Theory Classroom', 0, 1),
(12, 'SDSA305', 'Server Config & Network Lab', 2, 'Skill Lab', 4, 3, 'Computer Lab', 1, 1),
(13, 'SDSA306', 'Operating System Principles', 2, 'Major Theory', 3, 7, 'Theory Classroom', 0, 1),
(14, 'SDSA307', 'Mini Software Project', 2, 'Project', 2, 5, 'Computer Lab', 1, 1);

-- 3rd Year Subjects (Total 25 Hours)
INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `academic_year_id`, `category`, `weekly_hours`, `assigned_faculty_id`, `preferred_room_type`, `is_consecutive_required`, `is_active`) VALUES
(15, 'SDSA501', 'Cloud Computing & DevOps', 3, 'Major Theory', 4, 1, 'Theory Classroom', 0, 1),
(16, 'SDSA502', 'Full-Stack Web Development (PHP/React)', 3, 'Major Theory', 4, 4, 'Theory Classroom', 0, 1),
(17, 'SDSA503', 'DevOps & Cloud Orchestration Lab', 3, 'Major Lab', 4, 1, 'Computer Lab', 1, 1),
(18, 'SDSA504', 'Network Security & Ethical Hacking', 3, 'Skill Theory', 4, 5, 'Theory Classroom', 0, 1),
(19, 'SDSA505', 'Full-Stack Web & API Lab', 3, 'Skill Lab', 4, 4, 'Computer Lab', 1, 1),
(20, 'SDSA506', 'Cyber Security Testing Lab', 3, 'Skill Lab', 2, 5, 'Computer Lab', 1, 1),
(21, 'SDSA507', 'Capstone Project & Industrial Seminar', 3, 'Project', 3, 6, 'Theory Classroom', 0, 1);

-- 6. Schedule Settings
INSERT INTO `schedule_settings` (`setting_key`, `setting_value`) VALUES
('working_days', '["Monday","Tuesday","Wednesday","Thursday","Friday"]'),
('working_days_count', '5'),
('periods_per_day', '5'),
('default_academic_session', '2026-2027'),
('institution_name', 'B.Voc SD & SA Department'),
('college_name', 'School of Vocational Studies & Advanced Technology'),
('department_hod', 'Dr. Ramesh Kumar'),
('auto_avoid_conflicts', '1'),
('consecutive_lab_periods', '2');

-- 7. Period Definitions
INSERT INTO `periods` (`id`, `period_index`, `period_name`, `start_time`, `end_time`, `is_break_after`, `break_name`, `break_start_time`, `break_end_time`) VALUES
(1, 1, 'Period 1', '08:30:00', '09:25:00', 0, NULL, NULL, NULL),
(2, 2, 'Period 2', '09:25:00', '10:20:00', 0, NULL, NULL, NULL),
(3, 3, 'Period 3', '10:20:00', '11:15:00', 1, 'Tea & Refreshment Break', '11:15:00', '11:30:00'),
(4, 4, 'Period 4', '11:30:00', '12:25:00', 0, NULL, NULL, NULL),
(5, 5, 'Period 5', '12:25:00', '13:20:00', 0, NULL, NULL, NULL);

-- 8. Initialize Staff Availability Matrix (All Available By Default)
INSERT INTO `staff_availability` (`staff_id`, `day_of_week`, `period_index`, `is_available`, `preference`)
SELECT s.id, d.day_val, p.period_index, 1, 'neutral'
FROM `staff` s
CROSS JOIN (SELECT 1 AS day_val UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) d
CROSS JOIN (SELECT 1 AS period_index UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) p;

-- 9. Sample Students
INSERT INTO `students` (`id`, `register_number`, `full_name`, `email`, `phone`, `academic_year_id`, `section`, `is_active`) VALUES
(1, '2026BVOC001', 'Aarav Patel', 'aarav.patel@student.bvoc.edu', '+91 98111 22334', 1, 'Section A', 1),
(2, '2026BVOC002', 'Diya Natarajan', 'diya.n@student.bvoc.edu', '+91 98222 33445', 1, 'Section A', 1),
(3, '2025BVOC001', 'Rahul Krishnan', 'rahul.k@student.bvoc.edu', '+91 98333 44556', 2, 'Section A', 1),
(4, '2025BVOC002', 'Sneha Balaji', 'sneha.b@student.bvoc.edu', '+91 98444 55667', 2, 'Section A', 1),
(5, '2024BVOC001', 'Vijay Raghavan', 'vijay.r@student.bvoc.edu', '+91 98555 66778', 3, 'Section A', 1),
(6, '2024BVOC002', 'Meera Swaminathan', 'meera.s@student.bvoc.edu', '+91 98666 77889', 3, 'Section A', 1);

