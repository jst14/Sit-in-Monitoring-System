-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 14, 2026 at 07:33 AM
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
-- Database: `sit_in_monitoring`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `body` text NOT NULL,
  `posted_by` varchar(80) DEFAULT 'Admin',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `body`, `posted_by`, `is_active`, `created_at`) VALUES
(1, 'Welcome to CCS Sit-in Monitoring System', 'Students are reminded to log in before using any laboratory. Each student is allotted 30 sit-in sessions per semester. Please observe proper lab etiquette at all times.', 'Admin', 1, '2026-03-18 21:18:39'),
(2, 'New Announcement', 'We are almost done, and trying to set this up for our soft launch...', 'Admin', 1, '2026-04-16 07:22:14'),
(3, 'New Announcement', 'Hello Students, Good day, we are almost done.', 'Admin', 1, '2026-04-22 15:14:31'),
(4, 'New Announcement', 'Good day Students, We are announcing that we will be not be able to allow you sitting-in this week.', 'Admin', 1, '2026-04-22 15:23:31');

-- --------------------------------------------------------

--
-- Table structure for table `labs`
--

CREATE TABLE `labs` (
  `id` int(10) UNSIGNED NOT NULL,
  `lab_name` varchar(50) NOT NULL COMMENT 'e.g. Lab 524, Lab 526',
  `capacity` tinyint(3) UNSIGNED NOT NULL DEFAULT 40,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `labs`
--

INSERT INTO `labs` (`id`, `lab_name`, `capacity`, `is_active`) VALUES
(3, 'Lab 524', 40, 1),
(4, 'Lab 526', 40, 1),
(5, 'Lab 528', 40, 1),
(6, 'Lab 530', 40, 1),
(7, 'Lab 542', 30, 1),
(8, 'MAC', 40, 1);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `type` enum('info','success','warning','danger') NOT NULL DEFAULT 'info',
  `message` varchar(255) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `message`, `is_read`, `created_at`) VALUES
(82, 3, 'info', 'Your reservation request has been submitted and is pending approval.', 0, '2026-05-14 02:24:28'),
(83, 3, 'success', 'Your reservation for Lab 526 on 2026-04-14 at 10:24 has been approved.', 0, '2026-05-14 02:24:49'),
(84, 6, 'info', 'Your reservation request has been submitted and is pending approval.', 0, '2026-05-14 02:33:58'),
(85, 6, 'success', 'Your reservation for Lab 524 on 2026-03-10 at 10:33 has been approved.', 0, '2026-05-14 02:34:17'),
(86, 3, 'success', 'Your feedback has been recorded and will be read by the admin.', 0, '2026-05-14 02:35:08'),
(87, 5, 'info', 'Your reservation request has been submitted and is pending approval.', 0, '2026-05-14 02:56:59'),
(88, 5, 'success', 'Your reservation for Lab 530 on 2026-05-02 at 13:30 has been approved.', 0, '2026-05-14 02:57:16'),
(89, 3, 'info', 'Your reservation request has been submitted and is pending approval.', 0, '2026-05-14 02:59:15'),
(90, 3, 'success', 'Your reservation for Lab 526 on 2026-05-14 at 10:59 has been approved.', 0, '2026-05-14 02:59:51'),
(91, 4, 'info', 'Your reservation request has been submitted and is pending approval.', 0, '2026-05-14 03:10:06'),
(92, 4, 'success', 'Your reservation for Lab 526 on 2026-05-14 at 11:10 has been approved.', 0, '2026-05-14 03:18:51'),
(93, 5, 'info', 'Your reservation request has been submitted and is pending approval.', 0, '2026-05-14 04:41:39'),
(94, 5, 'success', 'Your reservation for Lab 526 on 2026-05-14 at 12:41 has been approved.', 0, '2026-05-14 04:42:14'),
(95, 2, 'info', 'Your reservation request has been submitted and is pending approval.', 0, '2026-05-14 04:46:41'),
(96, 2, 'success', 'Your reservation for Lab 542 on 2026-05-14 at 12:46 has been approved.', 0, '2026-05-14 04:47:27'),
(97, 2, 'warning', 'Your sit-in session in Lab 542 ended due to logout on 2026-05-14 at 13:10.', 0, '2026-05-14 05:10:37'),
(98, 6, 'info', 'Your reservation request has been submitted and is pending approval.', 0, '2026-05-14 05:22:28'),
(99, 6, 'success', 'Your reservation for Lab 542 on 2026-05-14 at 13:22 has been approved.', 0, '2026-05-14 05:23:34');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `lab_id` int(10) UNSIGNED NOT NULL,
  `computer_number` int(11) DEFAULT NULL,
  `reserved_date` date NOT NULL,
  `time_start` time NOT NULL,
  `time_end` time NOT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `admin_remarks` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `user_id`, `lab_id`, `computer_number`, `reserved_date`, `time_start`, `time_end`, `purpose`, `status`, `admin_remarks`, `created_at`, `updated_at`) VALUES
(23, 3, 4, 14, '2026-04-14', '10:24:00', '11:24:00', 'PHP', 'approved', NULL, '2026-05-14 02:24:28', '2026-05-14 02:24:49'),
(24, 6, 3, 1, '2026-03-10', '10:33:00', '11:33:00', 'Thesis', 'approved', NULL, '2026-05-14 02:33:58', '2026-05-14 02:34:17'),
(25, 5, 6, 26, '2026-05-02', '13:30:00', '14:30:00', 'C# Programming', 'approved', NULL, '2026-05-14 02:56:59', '2026-05-14 02:57:16'),
(26, 3, 4, 14, '2026-05-14', '10:59:00', '11:59:00', 'PHP', 'approved', NULL, '2026-05-14 02:59:15', '2026-05-14 02:59:51'),
(27, 4, 4, 26, '2026-05-14', '11:10:00', '12:10:00', 'Python', 'approved', NULL, '2026-05-14 03:10:06', '2026-05-14 03:18:51'),
(28, 5, 4, 30, '2026-05-14', '12:41:00', '13:41:00', 'PHP', 'approved', NULL, '2026-05-14 04:41:39', '2026-05-14 04:42:14'),
(30, 6, 7, 16, '2026-05-14', '13:22:00', '14:22:00', 'JavaScript', 'approved', NULL, '2026-05-14 05:22:28', '2026-05-14 05:23:34');

-- --------------------------------------------------------

--
-- Table structure for table `reservation_disabled_dates`
--

CREATE TABLE `reservation_disabled_dates` (
  `id` int(11) NOT NULL,
  `lab_id` int(11) NOT NULL,
  `disabled_date` date NOT NULL,
  `reason` varchar(255) DEFAULT 'No classes',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sit_in_sessions`
--

CREATE TABLE `sit_in_sessions` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `lab_id` int(10) UNSIGNED NOT NULL,
  `computer_number` int(11) DEFAULT NULL COMMENT 'Computer number (1-40)',
  `purpose` varchar(255) DEFAULT NULL COMMENT 'e.g. C Programming, Web Dev',
  `time_in` datetime NOT NULL DEFAULT current_timestamp(),
  `time_out` datetime DEFAULT NULL COMMENT 'NULL = still sitting in',
  `status` enum('active','completed','cancelled') NOT NULL DEFAULT 'active',
  `satisfaction` enum('satisfied','unsatisfied') DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `feedback_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sit_in_sessions`
--

INSERT INTO `sit_in_sessions` (`id`, `user_id`, `lab_id`, `computer_number`, `purpose`, `time_in`, `time_out`, `status`, `satisfaction`, `feedback`, `feedback_at`, `created_at`) VALUES
(35, 3, 4, 14, 'PHP', '2026-04-14 10:24:00', '2026-05-14 10:26:03', 'completed', 'satisfied', 'Thank you so much!', '2026-05-14 10:35:08', '2026-05-14 02:24:49'),
(36, 6, 3, 1, 'Thesis', '2026-03-10 10:33:00', '2026-05-14 10:57:52', 'completed', NULL, NULL, NULL, '2026-05-14 02:34:17'),
(37, 5, 6, 26, 'C# Programming', '2026-05-02 13:30:00', '2026-05-14 11:07:59', 'completed', NULL, NULL, NULL, '2026-05-14 02:57:16'),
(38, 3, 4, 14, 'PHP', '2026-05-14 10:59:00', '2026-05-14 11:08:22', 'completed', NULL, NULL, NULL, '2026-05-14 02:59:51'),
(39, 4, 4, 26, 'Python', '2026-05-14 11:10:00', '2026-05-14 12:40:18', 'completed', NULL, NULL, NULL, '2026-05-14 03:18:51'),
(40, 5, 4, 30, 'PHP', '2026-05-14 12:42:14', NULL, 'active', NULL, NULL, NULL, '2026-05-14 04:42:14'),
(42, 6, 7, 16, 'JavaScript', '2026-05-14 13:23:34', '2026-05-14 13:25:45', 'completed', NULL, NULL, NULL, '2026-05-14 05:23:34');

-- --------------------------------------------------------

--
-- Table structure for table `software`
--

CREATE TABLE `software` (
  `id` int(10) UNSIGNED NOT NULL,
  `software_name` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `lab_id` int(10) UNSIGNED DEFAULT NULL,
  `uploaded_by` int(10) UNSIGNED DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `software`
--

INSERT INTO `software` (`id`, `software_name`, `category`, `file_path`, `lab_id`, `uploaded_by`, `uploaded_at`) VALUES
(1, 'MS Office 365', 'Word, Excel, PowerPoint', '/software/ms_office_365.exe', 3, NULL, '2026-05-13 18:58:24'),
(2, 'MS Office 365', 'Word, Excel, PowerPoint', '/software/ms_office_365.exe', 4, NULL, '2026-05-13 18:58:24'),
(3, 'MS Office 365', 'Word, Excel, PowerPoint', '/software/ms_office_365.exe', 5, NULL, '2026-05-13 18:58:24'),
(4, 'MS Office 365', 'Word, Excel, PowerPoint', '/software/ms_office_365.exe', 6, NULL, '2026-05-13 18:58:24'),
(5, 'MS Office 365', 'Word, Excel, PowerPoint', '/software/ms_office_365.exe', 7, NULL, '2026-05-13 18:58:24'),
(6, 'MS Office 365', 'Word, Excel, PowerPoint', '/software/ms_office_365.exe', 8, NULL, '2026-05-13 18:58:24'),
(7, 'Visual Studio Code', 'Code editor & debugger', '/software/visual_studio_code.exe', 3, NULL, '2026-05-13 18:58:24'),
(8, 'Visual Studio Code', 'Code editor & debugger', '/software/visual_studio_code.exe', 4, NULL, '2026-05-13 18:58:24'),
(9, 'Visual Studio Code', 'Code editor & debugger', '/software/visual_studio_code.exe', 5, NULL, '2026-05-13 18:58:24'),
(10, 'Visual Studio Code', 'Code editor & debugger', '/software/visual_studio_code.exe', 6, NULL, '2026-05-13 18:58:24'),
(11, 'Visual Studio Code', 'Code editor & debugger', '/software/visual_studio_code.exe', 7, NULL, '2026-05-13 18:58:24'),
(12, 'Visual Studio Code', 'Code editor & debugger', '/software/visual_studio_code.exe', 8, NULL, '2026-05-13 18:58:24'),
(13, 'XAMPP', 'Apache + MySQL + PHP', '/software/xampp.exe', 3, NULL, '2026-05-13 18:58:24'),
(14, 'XAMPP', 'Apache + MySQL + PHP', '/software/xampp.exe', 4, NULL, '2026-05-13 18:58:24'),
(15, 'XAMPP', 'Apache + MySQL + PHP', '/software/xampp.exe', 5, NULL, '2026-05-13 18:58:24'),
(16, 'XAMPP', 'Apache + MySQL + PHP', '/software/xampp.exe', 6, NULL, '2026-05-13 18:58:24'),
(17, 'XAMPP', 'Apache + MySQL + PHP', '/software/xampp.exe', 7, NULL, '2026-05-13 18:58:24'),
(18, 'XAMPP', 'Apache + MySQL + PHP', '/software/xampp.exe', 8, NULL, '2026-05-13 18:58:24'),
(19, 'MySQL Workbench', 'Database management tool', '/software/mysql_workbench.exe', 3, NULL, '2026-05-13 18:58:24'),
(20, 'MySQL Workbench', 'Database management tool', '/software/mysql_workbench.exe', 8, NULL, '2026-05-13 18:58:24'),
(21, 'NetBeans IDE', 'Java development', '/software/netbeans_ide.exe', 3, NULL, '2026-05-13 18:58:24'),
(22, 'NetBeans IDE', 'Java development', '/software/netbeans_ide.exe', 4, NULL, '2026-05-13 18:58:24'),
(23, 'IntelliJ IDEA', 'JetBrains Java IDE', '/software/intellij_idea.exe', 4, NULL, '2026-05-13 18:58:24'),
(24, 'IntelliJ IDEA', 'JetBrains Java IDE', '/software/intellij_idea.exe', 5, NULL, '2026-05-13 18:58:24'),
(25, 'Git', 'Version control', '/software/git.exe', 3, NULL, '2026-05-13 18:58:24'),
(26, 'Git', 'Version control', '/software/git.exe', 4, NULL, '2026-05-13 18:58:24'),
(27, 'Git', 'Version control', '/software/git.exe', 5, NULL, '2026-05-13 18:58:24'),
(28, 'Git', 'Version control', '/software/git.exe', 6, NULL, '2026-05-13 18:58:24'),
(29, 'Git', 'Version control', '/software/git.exe', 7, NULL, '2026-05-13 18:58:24'),
(30, 'Git', 'Version control', '/software/git.exe', 8, NULL, '2026-05-13 18:58:24'),
(31, 'Cisco Packet Tracer', 'Network simulation', '/software/cisco_packet_tracer.exe', 5, NULL, '2026-05-13 18:58:24'),
(32, 'Oracle Virtual Box', 'Simulator Virtualization Tool', '/software/oracle_virtual_box.exe', 7, NULL, '2026-05-13 18:58:24'),
(33, 'Oracle Virtual Box', 'Simulator Virtualization Tool', '/software/oracle_virtual_box.exe', 3, NULL, '2026-05-13 18:58:24'),
(34, 'VMware Workstation', 'Simulator Host Machine', '/software/vmware_workstation.exe', 3, NULL, '2026-05-13 18:58:24'),
(35, 'VMware Workstation', 'Simulator Host Machine', '/software/vmware_workstation.exe', 7, NULL, '2026-05-13 18:58:24'),
(36, 'Notepad++', 'Code editor & debugger', '/software/notepad++.exe', 3, NULL, '2026-05-13 18:58:24'),
(37, 'Notepad++', 'Code editor & debugger', '/software/notepad++.exe', 4, NULL, '2026-05-13 18:58:24'),
(38, 'Notepad++', 'Code editor & debugger', '/software/notepad++.exe', 5, NULL, '2026-05-13 18:58:24'),
(39, 'Notepad++', 'Code editor & debugger', '/software/notepad++.exe', 6, NULL, '2026-05-13 18:58:24'),
(40, 'Notepad++', 'Code editor & debugger', '/software/notepad++.exe', 7, NULL, '2026-05-13 18:58:24'),
(41, 'Notepad++', 'Code editor & debugger', '/software/notepad++.exe', 8, NULL, '2026-05-13 18:58:24'),
(42, 'Python 3.x', 'Python interpreter & pip', '/software/python_3.x.exe', 3, NULL, '2026-05-13 18:58:24'),
(43, 'Python 3.x', 'Python interpreter & pip', '/software/python_3.x.exe', 4, NULL, '2026-05-13 18:58:24'),
(44, 'Python 3.x', 'Python interpreter & pip', '/software/python_3.x.exe', 5, NULL, '2026-05-13 18:58:24'),
(45, 'Python 3.x', 'Python interpreter & pip', '/software/python_3.x.exe', 6, NULL, '2026-05-13 18:58:24'),
(46, 'Python 3.x', 'Python interpreter & pip', '/software/python_3.x.exe', 7, NULL, '2026-05-13 18:58:24'),
(47, 'Python 3.x', 'Python interpreter & pip', '/software/python_3.x.exe', 8, NULL, '2026-05-13 18:58:24');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_number` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `course` varchar(20) NOT NULL,
  `year_level` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `password` varchar(255) NOT NULL COMMENT 'bcrypt hash',
  `profile_pic` varchar(255) DEFAULT NULL COMMENT 'relative path to uploaded photo',
  `sessions_left` tinyint(3) UNSIGNED NOT NULL DEFAULT 30 COMMENT 'max sit-in sessions allowed',
  `points` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `role` varchar(20) NOT NULL DEFAULT 'student'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `id_number`, `email`, `first_name`, `last_name`, `middle_name`, `address`, `course`, `year_level`, `password`, `profile_pic`, `sessions_left`, `points`, `created_at`, `updated_at`, `role`) VALUES
(1, '123123', 'admin@ccs.edu.ph', 'CCS', 'Administrator', NULL, NULL, '', 1, '$2a$12$yHqMqHGZQIF9dSqWEAkXpe2IidWx0lwVwYQrpoSec1NXksT149PdK', NULL, 0, 0, '2026-03-26 04:49:44', '2026-05-14 02:18:21', 'admin'),
(2, 'admin', 'admin@example.com', 'Admin', 'User', '', 'Admin Address', 'Admin', 1, '$2a$12$yHqMqHGZQIF9dSqWEAkXpe2IidWx0lwVwYQrpoSec1NXksT149PdK', NULL, 29, 0, '2026-04-15 17:56:47', '2026-05-14 05:10:11', 'admin'),
(3, '20041404', 'jstl200404@email.com', 'John Simon', 'Limosnero', 'T.', 'Cebu City', 'BSIT', 3, '$2y$10$7UmUxdD/muOmoZZdGpn8J.WncXzwh/kbLYaVVt74sD8LGn2QvgIdi', 'uploads/profile_3.jpg', 28, 0, '2026-05-14 02:20:47', '2026-05-14 03:08:22', 'student'),
(4, '20200814', 'alfea@email.com', 'Alfea', 'Zulueta', '', 'Manila', 'BSCA', 1, '$2y$10$o3WZWeKvdFqFqiNZDSaA9OWsLZgacUQl9cilePGok1pBJjCrNURkW', NULL, 29, 0, '2026-05-14 02:28:04', '2026-05-14 04:40:18', 'student'),
(5, '20180302', 'pedrocalungsod@email.com', 'Pedro', 'Calungsod', '', 'Cebu City', 'BSCS', 4, '$2y$10$jSvTdypTkBaIfMVx51N88e.nHYJsGztmWfNUvAeGvA8QgSqYCRIa.', 'uploads/profile_5.jpg', 29, 0, '2026-05-14 02:29:41', '2026-05-14 03:07:59', 'student'),
(6, '20170606', 'lbj@email.com', 'Lebron', 'James', '', 'Akron', 'BSCRIM', 2, '$2y$10$F40DA2J04N/3d2K3nsi/PeEEUY8uOyoKdRC6DlYOO3IpvcVeg0oqm', 'uploads/profile_6.jpg', 28, 0, '2026-05-14 02:31:22', '2026-05-14 05:25:45', 'student');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `labs`
--
ALTER TABLE `labs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_lab_name` (`lab_name`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notif_user` (`user_id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_res_user` (`user_id`),
  ADD KEY `idx_res_lab` (`lab_id`),
  ADD KEY `idx_res_date` (`reserved_date`);

--
-- Indexes for table `reservation_disabled_dates`
--
ALTER TABLE `reservation_disabled_dates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_lab_date` (`lab_id`,`disabled_date`);

--
-- Indexes for table `sit_in_sessions`
--
ALTER TABLE `sit_in_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_lab_id` (`lab_id`),
  ADD KEY `idx_time_in` (`time_in`);

--
-- Indexes for table `software`
--
ALTER TABLE `software`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploaded_by` (`uploaded_by`),
  ADD KEY `idx_lab_id` (`lab_id`),
  ADD KEY `idx_category` (`category`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_id_number` (`id_number`),
  ADD UNIQUE KEY `uq_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `labs`
--
ALTER TABLE `labs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `reservation_disabled_dates`
--
ALTER TABLE `reservation_disabled_dates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sit_in_sessions`
--
ALTER TABLE `sit_in_sessions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `software`
--
ALTER TABLE `software`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `fk_res_lab` FOREIGN KEY (`lab_id`) REFERENCES `labs` (`id`),
  ADD CONSTRAINT `fk_res_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sit_in_sessions`
--
ALTER TABLE `sit_in_sessions`
  ADD CONSTRAINT `fk_sess_lab` FOREIGN KEY (`lab_id`) REFERENCES `labs` (`id`),
  ADD CONSTRAINT `fk_sess_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `software`
--
ALTER TABLE `software`
  ADD CONSTRAINT `software_ibfk_1` FOREIGN KEY (`lab_id`) REFERENCES `labs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `software_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
