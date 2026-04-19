-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 18, 2026 at 01:12 PM
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
-- Database: `tooltrace`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `id` int(10) UNSIGNED NOT NULL,
  `account_id` varchar(32) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `account_role` enum('admin','staff','organization') NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`id`, `account_id`, `username`, `email`, `account_role`, `password_hash`, `created_at`) VALUES
(1, '23e08ba230dea4f9', 'Anne Patrice Arbolente', 'annepatricearbolente@gmail.com', 'staff', '$2y$10$gNj2VrylhqVUMSks1na6vOCNVYwb4CPNZqc8JklctTiWDkJTQe8qG', '2026-04-16 10:59:54'),
(2, '51a12728305e5094', 'Aquino', 'mjraquino2@tip.edu.ph', 'admin', '$2y$10$FHb2j2e5WmwejOJf8hvD1OyEhiMpbLVeIWPB8AIs1VARo0oAHXgfK', '2026-04-14 22:40:45'),
(3, '6d3417a9ae168b85', 'RC Espino', 'mrcespino@tip.edu.ph', 'staff', '$2y$10$OXujFObWvFw.fq/uVdmrd.xyhNurgXE9f42KOHROL.XBrGGZeGSay', '2026-04-11 16:59:51'),
(4, '7438d3450bca178e', 'dzches', 'chesskaeunice@Gmail.com', 'staff', '$2y$10$tkQBjYgwwVWqWORfiHoomurbUcwBUJ7MEHomfL4FucYI0YQOsoJWW', '2026-04-11 04:32:13'),
(5, '79dc5b974d689b24', 'JPEG', 'maparbolente@tip.edu.ph', 'organization', '$2y$10$5mcLtSoO3gpGsyMTAetTo.N.Wz3rauRSPQtCljQiiIyiwraKUmjaq', '2026-04-12 20:05:17'),
(6, '84bf2d52bf19d2b9', 'Arbolente', 'yugotrenth@gmail.com', 'staff', '$2y$10$XSkLj9y07eJ7Au7XmtIYNujE1Drn5RCr3cnb0Ytn1TTh4bz4TVl6W', '2026-04-14 22:34:11'),
(7, 'a1b2c3d4e5f60001', 'Junior Marketing Association', 'jma.school@edu.ph', 'organization', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uSc/i.Er2', '2026-04-12 20:49:50'),
(8, 'a1b2c3d4e5f60002', 'Photography Club', 'photoclub.school@edu.ph', 'organization', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uSc/i.Er2', '2026-04-12 20:49:50'),
(9, 'a1b2c3d4e5f60003', 'Electronics and Robotics Society', 'ers.school@edu.ph', 'organization', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uSc/i.Er2', '2026-04-12 20:49:50'),
(10, 'a1b2c3d4e5f60004', 'Red Cross Youth Chapter', 'rcy.school@edu.ph', 'organization', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uSc/i.Er2', '2026-04-12 20:49:50'),
(11, 'a1b2c3d4e5f60005', 'Mathematics Society', 'mathsoc.school@edu.ph', 'organization', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uSc/i.Er2', '2026-04-12 20:49:50'),
(12, 'a1b2c3d4e5f60006', 'Architecture Students Association', 'asa.school@edu.ph', 'organization', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uSc/i.Er2', '2026-04-12 20:49:50'),
(13, 'a1b2c3d4e5f60007', 'Chemistry Enthusiasts Club', 'cec.school@edu.ph', 'organization', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uSc/i.Er2', '2026-04-12 20:49:50'),
(14, 'a1b2c3d4e5f60008', 'Sports Development Council', 'sdc.school@edu.ph', 'organization', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uSc/i.Er2', '2026-04-12 20:49:50'),
(15, 'a1b2c3d4e5f60009', 'Future Educators League', 'fel.school@edu.ph', 'organization', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uSc/i.Er2', '2026-04-12 20:49:50'),
(16, 'a1b2c3d4e5f60010', 'Physics Society', 'physics.school@edu.ph', 'organization', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uSc/i.Er2', '2026-04-12 20:49:50'),
(17, 'a1b2c3d4e5f60011', 'Information Technology Society', 'its.school@edu.ph', 'organization', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uSc/i.Er2', '2026-04-12 20:49:50'),
(18, 'a1b2c3d4e5f60012', 'Literary and Debate Society', 'lds.school@edu.ph', 'organization', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uSc/i.Er2', '2026-04-12 20:49:50'),
(19, 'a1b2c3d4e5f60013', 'Biology and Environment Club', 'bec.school@edu.ph', 'organization', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uSc/i.Er2', '2026-04-12 20:49:50'),
(20, 'a1b2c3d4e5f60014', 'CADSS', 'cadss.school@edu.ph', 'organization', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uSc/i.Er2', '2026-04-12 20:49:50'),
(21, 'a1b2c3d4e5f60015', 'AWS', 'aws.school@edu.ph', 'organization', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uSc/i.Er2', '2026-04-12 20:49:50'),
(22, 'a1b2c3d4e5f60016', 'ICONS', 'icons.school@edu.ph', 'organization', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uSc/i.Er2', '2026-04-12 20:49:50'),
(23, 'b2c82d9d5dea40fc', 'JPEG', 'eizantagaki@gmail.com', 'organization', '$2y$10$9lm02GGjhwc/pAXPDID3SurracyCE757V2M8mKPZf85/cjpFJ6xea', '2026-04-16 11:42:16'),
(24, 'dd6f969e01c330e3', 'Shofia Loise Magpayo', 'mslmagpayo@tip.edu.ph', 'organization', '$2y$10$6iYk.Y7nVUBH8TP/Vjak9OcKHyQPPb0T.socW4Mdo3Dlb41WCrwV.', '2026-04-14 22:50:36'),
(25, 'f6d74b96506d700e', 'admin', 'mfediaz@tip.edu.ph', 'admin', '$2y$10$iSuy3gTxoTmA8/d1ZdkBhuB7X.QuDRw9N52dg8a1.oJGRbww3RSXG', '2026-04-11 04:34:30');

-- --------------------------------------------------------

--
-- Table structure for table `borrow_transactions`
--

CREATE TABLE `borrow_transactions` (
  `transaction_id` varchar(20) NOT NULL,
  `request_group_id` varchar(20) DEFAULT NULL,
  `org_id` varchar(10) NOT NULL,
  `equipment_id` varchar(20) NOT NULL,
  `unit_id` int(11) DEFAULT NULL,
  `staff_id` int(11) NOT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `officer_in_charge` varchar(120) DEFAULT NULL,
  `date_borrowed` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `date_returned` date DEFAULT NULL,
  `status` enum('Pending','Borrowed','Returned','Overdue','Lost') NOT NULL DEFAULT 'Pending',
  `approval_status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `approved_by_staff_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `oic_id_path` varchar(255) DEFAULT NULL,
  `oic_id_mime` varchar(100) DEFAULT NULL,
  `oic_id_original_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `borrow_transactions`
--

INSERT INTO `borrow_transactions` (`transaction_id`, `request_group_id`, `org_id`, `equipment_id`, `unit_id`, `staff_id`, `purpose`, `location`, `officer_in_charge`, `date_borrowed`, `due_date`, `date_returned`, `status`, `approval_status`, `approved_by_staff_id`, `created_at`, `updated_at`, `oic_id_path`, `oic_id_mime`, `oic_id_original_name`) VALUES
('REQ-2026-0001', 'REQ-2026-0001', 'ORG-001', 'EQ-PJ-001', 1, 1, 'Web dev workshop', 'ICT Laboratory', 'Anne Patrice Arbolente', '2024-06-03', '2024-06-05', '2024-06-05', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0002', 'REQ-2026-0002', 'ORG-002', 'EQ-CAM-001', 6, 1, 'Business pitch demo', 'Business Dept Lounge', 'Marcus Rivera', '2024-06-04', '2024-06-04', '2024-06-04', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0003', 'REQ-2026-0003', 'ORG-003', 'EQ-HDMI-001', 8, 1, 'Event documentation', 'Photo Studio A', 'Leonor Go', '2024-06-05', '2024-06-10', NULL, 'Borrowed', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0003-01', 'REQ-2026-0003', 'ORG-001', 'EQ-PJ-001', 4, 1, 'Seminar', 'ICT Laboratory', 'Anne Patrice Arbolente', '2026-04-13', '2026-04-14', NULL, 'Pending', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0003-02', 'REQ-2026-0003', 'ORG-001', 'EQ-FT-001', 38, 1, 'Seminar', 'ICT Laboratory', 'Anne Patrice Arbolente', '2026-04-13', '2026-04-14', NULL, 'Pending', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0004', 'REQ-2026-0004', 'ORG-004', 'EQ-PWR-001', 9, 1, 'Financial computation', 'Robotics Lab', 'Sarah Connor', '2024-06-01', '2024-06-03', NULL, 'Overdue', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0005', 'REQ-2026-0005', 'ORG-005', 'EQ-PRC-001', 15, 1, 'Circuit testing', 'Red Cross Station', 'Clara Barton', '2024-06-06', '2024-06-08', '2024-06-08', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0006', 'REQ-2026-0006', 'ORG-006', 'EQ-SCC-001', 21, 1, 'First aid training', 'Math Hall', 'Isaac Newton', '2024-06-07', '2024-06-09', NULL, 'Borrowed', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0007', 'REQ-2026-0007', 'ORG-007', 'EQ-MIC-001', 25, 1, 'Quiz bowl prep', 'Architecture Studio', 'Arthur Vandelay', '2024-06-02', '2024-06-09', '2024-06-09', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0008', 'REQ-2026-0008', 'ORG-008', 'EQ-VGA-001', 28, 1, 'Thesis exhibit prep', 'Chemistry Lab', 'Marie Curie', '2024-05-30', '2024-06-02', NULL, 'Overdue', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0009', 'REQ-2026-0009', 'ORG-009', 'EQ-FT-001', 33, 1, 'Hackathon practice', 'Gymnasium', 'Michael Jordan', '2024-06-10', '2024-06-12', '2024-06-12', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0010', 'REQ-2026-0010', 'ORG-010', 'EQ-MC-001', 43, 1, 'Product demo setup', 'Education Bldg', 'Maria Montessori', '2024-06-11', '2024-06-11', '2024-06-11', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0011', 'REQ-2026-0011', 'ORG-011', 'EQ-PJ-001', 2, 1, 'Safety orientation', 'Physics Lab', 'Albert Einstein', '2024-06-12', '2024-06-12', '2024-06-12', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0012', 'REQ-2026-0012', 'ORG-012', 'EQ-CAM-001', 7, 1, 'Tryouts timing', 'IT Server Room', 'Alan Turing', '2024-06-05', '2024-06-20', NULL, 'Borrowed', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0013', 'REQ-2026-0013', 'ORG-013', 'EQ-HDMI-001', 8, 1, 'Voltage testing', 'Debate Hall', 'Cicero Brown', '2024-06-03', '2024-06-05', '2024-06-05', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0014', 'REQ-2026-0014', 'ORG-014', 'EQ-PWR-001', 10, 1, 'Teaching demo', 'Biology Garden', 'Charles Darwin', '2024-06-08', '2024-06-08', NULL, 'Overdue', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:55', NULL, NULL, NULL),
('REQ-2026-0015', 'REQ-2026-0015', 'ORG-015', 'EQ-PRC-001', 16, 1, 'Project file backup', 'Design Studio', 'Frank Wright', '2024-06-13', '2024-06-15', '2024-06-15', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:55', NULL, NULL, NULL),
('REQ-2026-0016', 'REQ-2026-0016', 'ORG-016', 'EQ-SCC-001', 22, 1, 'Health outreach', 'Cloud Lab', 'Jeff Bezos', '2024-06-14', '2024-06-16', NULL, 'Borrowed', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:55', NULL, NULL, NULL),
('REQ-2026-0017', 'REQ-2026-0017', 'ORG-017', 'EQ-MIC-001', 26, 1, 'Optics demonstration', 'Main Plaza', 'Steve Jobs', '2024-06-01', '2024-06-14', '2024-06-14', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:55', NULL, NULL, NULL),
('REQ-2026-0018', 'REQ-2026-0018', 'ORG-001', 'EQ-VGA-001', 29, 1, 'Symposium presentation', 'ICT Laboratory', 'Anne Patrice Arbolente', '2024-06-06', '2024-06-06', '2024-06-06', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0019', 'REQ-2026-0019', 'ORG-002', 'EQ-FT-001', 34, 1, 'Scale model drafting', 'Business Dept Lounge', 'Marcus Rivera', '2024-06-09', '2024-06-11', NULL, 'Overdue', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0020', 'REQ-2026-0020', 'ORG-003', 'EQ-MC-001', 44, 1, 'System demo', 'Photo Studio A', 'Leonor Go', '2024-06-15', '2024-06-17', '2024-06-17', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0021', 'REQ-2026-0021', 'ORG-004', 'EQ-PJ-001', 3, 1, 'Soldering workshop', 'Robotics Lab', 'Sarah Connor', '2024-06-16', '2024-06-18', NULL, 'Borrowed', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0022', 'REQ-2026-0022', 'ORG-005', 'EQ-CAM-001', 6, 1, 'Debate streaming', 'Red Cross Station', 'Clara Barton', '2024-06-10', '2024-06-20', NULL, 'Borrowed', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0023', 'REQ-2026-0023', 'ORG-006', 'EQ-HDMI-001', 8, 1, 'Flame test demo', 'Math Hall', 'Isaac Newton', '2024-06-17', '2024-06-17', '2024-06-17', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0024', 'REQ-2026-0024', 'ORG-007', 'EQ-PWR-001', 9, 1, 'Basketball tournament', 'Architecture Studio', 'Arthur Vandelay', '2024-06-04', '2024-06-04', '2024-06-04', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0025', 'REQ-2026-0025', 'ORG-008', 'EQ-PRC-001', 17, 1, 'Coding bootcamp', 'Chemistry Lab', 'Marie Curie', '2024-06-18', '2024-06-20', '2024-06-20', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0026', 'REQ-2026-0026', 'ORG-009', 'EQ-SCC-001', 23, 1, 'Blood donation drive', 'Gymnasium', 'Michael Jordan', '2024-06-19', '2024-06-19', '2024-06-19', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0027', 'REQ-2026-0027', 'ORG-010', 'EQ-MIC-001', 27, 1, 'Math tutoring demo', 'Education Bldg', 'Maria Montessori', '2024-06-11', '2024-06-25', NULL, 'Borrowed', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0028', 'REQ-2026-0028', 'ORG-011', 'EQ-VGA-001', 30, 1, 'Night drafting session', 'Physics Lab', 'Albert Einstein', '2024-06-05', '2024-06-07', '2024-06-07', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0029', 'REQ-2026-0029', 'ORG-012', 'EQ-FT-001', 35, 1, 'Circuit board assembly', 'IT Server Room', 'Alan Turing', '2024-06-20', '2024-06-22', NULL, 'Borrowed', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0030', 'REQ-2026-0030', 'ORG-013', 'EQ-MC-001', 45, 1, 'Budget computation', 'Debate Hall', 'Cicero Brown', '2024-05-28', '2024-05-30', NULL, 'Overdue', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0031', 'REQ-2026-0031', 'ORG-014', 'EQ-PJ-001', 4, 1, 'Web dev workshop', 'Biology Garden', 'Charles Darwin', '2024-06-21', '2024-06-23', '2024-06-23', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:55', NULL, NULL, NULL),
('REQ-2026-0032', 'REQ-2026-0032', 'ORG-015', 'EQ-CAM-001', 6, 1, 'Business pitch demo', 'Design Studio', 'Frank Wright', '2024-06-13', '2024-06-13', '2024-06-13', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:55', NULL, NULL, NULL),
('REQ-2026-0033', 'REQ-2026-0033', 'ORG-016', 'EQ-HDMI-001', 8, 1, 'Event documentation', 'Cloud Lab', 'Jeff Bezos', '2024-06-15', '2024-06-29', NULL, 'Borrowed', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:55', NULL, NULL, NULL),
('REQ-2026-0034', 'REQ-2026-0034', 'ORG-017', 'EQ-PWR-001', 10, 1, 'Financial computation', 'Main Plaza', 'Steve Jobs', '2024-06-22', '2024-06-22', '2024-06-22', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:55', NULL, NULL, NULL),
('REQ-2026-0035', 'REQ-2026-0035', 'ORG-001', 'EQ-PRC-001', 18, 1, 'Circuit testing', 'ICT Laboratory', 'Anne Patrice Arbolente', '2024-06-16', '2024-06-18', NULL, 'Borrowed', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0036', 'REQ-2026-0036', 'ORG-002', 'EQ-SCC-001', 24, 1, 'First aid training', 'Business Dept Lounge', 'Marcus Rivera', '2024-06-06', '2024-06-06', NULL, 'Overdue', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0037', 'REQ-2026-0037', 'ORG-003', 'EQ-MIC-001', 27, 1, 'Quiz bowl prep', 'Photo Studio A', 'Leonor Go', '2024-06-23', '2024-06-23', '2024-06-23', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0038', 'REQ-2026-0038', 'ORG-004', 'EQ-VGA-001', 31, 1, 'Thesis exhibit prep', 'Robotics Lab', 'Sarah Connor', '2024-06-08', '2024-06-22', NULL, 'Borrowed', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0039', 'REQ-2026-0039', 'ORG-005', 'EQ-FT-001', 36, 1, 'Hackathon practice', 'Red Cross Station', 'Clara Barton', '2024-06-17', '2024-06-19', '2024-06-19', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0040', 'REQ-2026-0040', 'ORG-006', 'EQ-MC-001', 46, 1, 'Product demo setup', 'Math Hall', 'Isaac Newton', '2024-06-24', '2024-06-28', NULL, 'Borrowed', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0041', 'REQ-2026-0041', 'ORG-007', 'EQ-PJ-001', 5, 1, 'Safety orientation', 'Architecture Studio', 'Arthur Vandelay', '2024-06-10', '2024-06-12', '2024-06-12', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0042', 'REQ-2026-0042', 'ORG-008', 'EQ-CAM-001', 7, 1, 'Tryouts timing', 'Chemistry Lab', 'Marie Curie', '2024-06-18', '2024-06-18', NULL, 'Overdue', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0043', 'REQ-2026-0043', 'ORG-009', 'EQ-HDMI-001', 8, 1, 'Voltage testing', 'Gymnasium', 'Michael Jordan', '2024-06-20', '2024-07-04', NULL, 'Borrowed', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0044', 'REQ-2026-0044', 'ORG-010', 'EQ-PWR-001', 9, 1, 'Teaching demo', 'Education Bldg', 'Maria Montessori', '2024-06-25', '2024-06-25', '2024-06-25', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0045', 'REQ-2026-0045', 'ORG-011', 'EQ-PRC-001', 15, 1, 'Project file backup', 'Physics Lab', 'Albert Einstein', '2024-06-11', '2024-06-13', NULL, 'Overdue', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0046', 'REQ-2026-0046', 'ORG-012', 'EQ-SCC-001', 21, 1, 'Health outreach', 'IT Server Room', 'Alan Turing', '2024-06-26', '2024-06-30', NULL, 'Borrowed', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0047', 'REQ-2026-0047', 'ORG-013', 'EQ-MIC-001', 25, 1, 'Optics demonstration', 'Debate Hall', 'Cicero Brown', '2024-06-04', '2024-06-04', '2024-06-04', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0048', 'REQ-2026-0048', 'ORG-014', 'EQ-VGA-001', 28, 1, 'Symposium presentation', 'Biology Garden', 'Charles Darwin', '2024-06-14', '2024-06-28', NULL, 'Borrowed', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:55', NULL, NULL, NULL),
('REQ-2026-0049', 'REQ-2026-0049', 'ORG-015', 'EQ-FT-001', 33, 1, 'Scale model drafting', 'Design Studio', 'Frank Wright', '2024-06-27', '2024-06-29', '2024-06-29', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:55', NULL, NULL, NULL),
('REQ-2026-0050', 'REQ-2026-0050', 'ORG-016', 'EQ-MC-001', 43, 1, 'System demo', 'Cloud Lab', 'Jeff Bezos', '2024-06-21', '2024-06-23', '2024-06-23', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:55', NULL, NULL, NULL),
('REQ-2026-0051', 'REQ-2026-0051', 'ORG-017', 'EQ-PJ-001', 1, 1, 'Soldering workshop', 'Main Plaza', 'Steve Jobs', '2024-06-28', '2024-07-02', NULL, 'Borrowed', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:55', NULL, NULL, NULL),
('REQ-2026-0052', 'REQ-2026-0052', 'ORG-001', 'EQ-CAM-001', 6, 1, 'Debate streaming', 'ICT Laboratory', 'Anne Patrice Arbolente', '2024-06-19', '2024-06-21', '2024-06-21', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0053', 'REQ-2026-0053', 'ORG-002', 'EQ-HDMI-001', 8, 1, 'Flame test demo', 'Business Dept Lounge', 'Marcus Rivera', '2024-06-22', '2024-07-06', NULL, 'Borrowed', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0054', 'REQ-2026-0054', 'ORG-003', 'EQ-PWR-001', 10, 1, 'Basketball tournament', 'Photo Studio A', 'Leonor Go', '2024-06-10', '2024-06-10', NULL, 'Overdue', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0055', 'REQ-2026-0055', 'ORG-004', 'EQ-PRC-001', 15, 1, 'Coding bootcamp', 'Robotics Lab', 'Sarah Connor', '2024-06-29', '2024-07-03', NULL, 'Borrowed', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0056', 'REQ-2026-0056', 'ORG-005', 'EQ-SCC-001', 22, 1, 'Blood donation drive', 'Red Cross Station', 'Clara Barton', '2024-06-07', '2024-06-07', '2024-06-07', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0057', 'REQ-2026-0057', 'ORG-006', 'EQ-MIC-001', 26, 1, 'Math tutoring demo', 'Math Hall', 'Isaac Newton', '2024-06-12', '2024-06-14', '2024-06-14', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0058', 'REQ-2026-0058', 'ORG-007', 'EQ-VGA-001', 28, 1, 'Night drafting session', 'Architecture Studio', 'Arthur Vandelay', '2024-06-24', '2024-07-08', NULL, 'Borrowed', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0059', 'REQ-2026-0059', 'ORG-008', 'EQ-FT-001', 34, 1, 'Circuit board assembly', 'Chemistry Lab', 'Marie Curie', '2024-06-30', '2024-07-02', NULL, 'Borrowed', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0060', 'REQ-2026-0060', 'ORG-009', 'EQ-MC-001', 44, 1, 'Budget computation', 'Gymnasium', 'Michael Jordan', '2024-06-23', '2024-06-23', '2024-06-23', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0061', 'REQ-2026-0061', 'ORG-010', 'EQ-PJ-001', 2, 1, 'Web dev workshop', 'Education Bldg', 'Maria Montessori', '2024-07-01', '2024-07-03', NULL, 'Borrowed', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0062', 'REQ-2026-0062', 'ORG-011', 'EQ-CAM-001', 7, 1, 'Business pitch demo', 'Physics Lab', 'Albert Einstein', '2024-06-25', '2024-06-25', '2024-06-25', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0063', 'REQ-2026-0063', 'ORG-012', 'EQ-HDMI-001', 8, 1, 'Event documentation', 'IT Server Room', 'Alan Turing', '2024-06-28', '2024-07-12', NULL, 'Borrowed', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0064', 'REQ-2026-0064', 'ORG-013', 'EQ-PWR-001', 9, 1, 'Financial computation', 'Debate Hall', 'Cicero Brown', '2024-07-02', '2024-07-02', '2024-07-02', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0065', 'REQ-2026-0065', 'ORG-014', 'EQ-PRC-001', 16, 1, 'Circuit testing', 'Biology Garden', 'Charles Darwin', '2024-06-08', '2024-06-08', NULL, 'Overdue', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:55', NULL, NULL, NULL),
('REQ-2026-0066', 'REQ-2026-0066', 'ORG-015', 'EQ-SCC-001', 23, 1, 'First aid training', 'Design Studio', 'Frank Wright', '2024-07-03', '2024-07-07', NULL, 'Borrowed', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:55', NULL, NULL, NULL),
('REQ-2026-0067', 'REQ-2026-0067', 'ORG-016', 'EQ-MIC-001', 27, 1, 'Quiz bowl prep', 'Cloud Lab', 'Jeff Bezos', '2024-06-26', '2024-06-28', '2024-06-28', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:55', NULL, NULL, NULL),
('REQ-2026-0068', 'REQ-2026-0068', 'ORG-017', 'EQ-VGA-001', 29, 1, 'Thesis exhibit prep', 'Main Plaza', 'Steve Jobs', '2024-07-01', '2024-07-15', NULL, 'Borrowed', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:55', NULL, NULL, NULL),
('REQ-2026-0069', 'REQ-2026-0069', 'ORG-001', 'EQ-FT-001', 35, 1, 'Hackathon practice', 'ICT Laboratory', 'Anne Patrice Arbolente', '2024-07-04', '2024-07-06', NULL, 'Borrowed', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0070', 'REQ-2026-0070', 'ORG-002', 'EQ-MC-001', 45, 1, 'Product demo setup', 'Business Dept Lounge', 'Marcus Rivera', '2024-07-05', '2024-07-09', NULL, 'Borrowed', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0071', 'REQ-2026-0071', 'ORG-003', 'EQ-PJ-001', 3, 1, 'Safety orientation', 'Photo Studio A', 'Leonor Go', '2024-06-28', '2024-06-28', '2024-06-28', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0072', 'REQ-2026-0072', 'ORG-004', 'EQ-CAM-001', 6, 1, 'Tryouts timing', 'Robotics Lab', 'Sarah Connor', '2024-07-06', '2024-07-10', NULL, 'Borrowed', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0073', 'REQ-2026-0073', 'ORG-005', 'EQ-HDMI-001', 8, 1, 'Voltage testing', 'Red Cross Station', 'Clara Barton', '2024-06-30', '2024-07-14', NULL, 'Borrowed', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0074', 'REQ-2026-0074', 'ORG-006', 'EQ-PWR-001', 10, 1, 'Teaching demo', 'Math Hall', 'Isaac Newton', '2024-06-15', '2024-06-15', NULL, 'Overdue', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0075', 'REQ-2026-0075', 'ORG-007', 'EQ-PRC-001', 17, 1, 'Project file backup', 'Architecture Studio', 'Arthur Vandelay', '2024-07-07', '2024-07-11', NULL, 'Borrowed', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0076', 'REQ-2026-0076', 'ORG-008', 'EQ-SCC-001', 24, 1, 'Health outreach', 'Chemistry Lab', 'Marie Curie', '2024-07-03', '2024-07-05', '2024-07-05', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0077', 'REQ-2026-0077', 'ORG-009', 'EQ-MIC-001', 25, 1, 'Optics demonstration', 'Gymnasium', 'Michael Jordan', '2024-07-08', '2024-07-08', NULL, 'Borrowed', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0078', 'REQ-2026-0078', 'ORG-010', 'EQ-VGA-001', 30, 1, 'Symposium presentation', 'Education Bldg', 'Maria Montessori', '2024-07-01', '2024-07-15', NULL, 'Borrowed', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0079', 'REQ-2026-0079', 'ORG-011', 'EQ-FT-001', 36, 1, 'Scale model drafting', 'Physics Lab', 'Albert Einstein', '2024-07-09', '2024-07-13', NULL, 'Borrowed', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0080', 'REQ-2026-0080', 'ORG-012', 'EQ-MC-001', 46, 1, 'System demo', 'IT Server Room', 'Alan Turing', '2024-07-04', '2024-07-04', '2024-07-04', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0081', 'REQ-2026-0081', 'ORG-013', 'EQ-PJ-001', 4, 1, 'Soldering workshop', 'Debate Hall', 'Cicero Brown', '2024-07-10', '2024-07-10', '2024-07-10', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0082', 'REQ-2026-0082', 'ORG-014', 'EQ-CAM-001', 7, 1, 'Debate streaming', 'Biology Garden', 'Charles Darwin', '2024-07-05', '2024-07-19', NULL, 'Borrowed', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:55', NULL, NULL, NULL),
('REQ-2026-0083', 'REQ-2026-0083', 'ORG-015', 'EQ-HDMI-001', 8, 1, 'Flame test demo', 'Design Studio', 'Frank Wright', '2024-07-11', '2024-07-13', NULL, 'Borrowed', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:55', NULL, NULL, NULL),
('REQ-2026-0084', 'REQ-2026-0084', 'ORG-016', 'EQ-PWR-001', 9, 1, 'Basketball tournament', 'Cloud Lab', 'Jeff Bezos', '2024-06-20', '2024-06-22', NULL, 'Overdue', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:55', NULL, NULL, NULL),
('REQ-2026-0085', 'REQ-2026-0085', 'ORG-017', 'EQ-PRC-001', 18, 1, 'Coding bootcamp', 'Main Plaza', 'Steve Jobs', '2024-07-06', '2024-07-08', '2024-07-08', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:55', NULL, NULL, NULL),
('REQ-2026-0086', 'REQ-2026-0086', 'ORG-001', 'EQ-SCC-001', 21, 1, 'Blood donation drive', 'ICT Laboratory', 'Anne Patrice Arbolente', '2024-07-12', '2024-07-16', NULL, 'Borrowed', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0087', 'REQ-2026-0087', 'ORG-002', 'EQ-MIC-001', 26, 1, 'Math tutoring demo', 'Business Dept Lounge', 'Marcus Rivera', '2024-07-08', '2024-07-22', NULL, 'Borrowed', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0088', 'REQ-2026-0088', 'ORG-003', 'EQ-VGA-001', 31, 1, 'Night drafting session', 'Photo Studio A', 'Leonor Go', '2024-07-07', '2024-07-07', '2024-07-07', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0089', 'REQ-2026-0089', 'ORG-004', 'EQ-FT-001', 37, 1, 'Circuit board assembly', 'Robotics Lab', 'Sarah Connor', '2024-07-13', '2024-07-15', NULL, 'Borrowed', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0090', 'REQ-2026-0090', 'ORG-005', 'EQ-MC-001', 47, 1, 'Budget computation', 'Red Cross Station', 'Clara Barton', '2024-07-09', '2024-07-11', '2024-07-11', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0091', 'REQ-2026-0091', 'ORG-006', 'EQ-PJ-001', 5, 1, 'Web dev workshop', 'Math Hall', 'Isaac Newton', '2024-07-14', '2024-07-16', NULL, 'Borrowed', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0092', 'REQ-2026-0092', 'ORG-007', 'EQ-CAM-001', 6, 1, 'Business pitch demo', 'Architecture Studio', 'Arthur Vandelay', '2024-07-10', '2024-07-10', '2024-07-10', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0093', 'REQ-2026-0093', 'ORG-008', 'EQ-HDMI-001', 8, 1, 'Event documentation', 'Chemistry Lab', 'Marie Curie', '2024-07-12', '2024-07-26', NULL, 'Borrowed', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0094', 'REQ-2026-0094', 'ORG-009', 'EQ-PWR-001', 10, 1, 'Financial computation', 'Gymnasium', 'Michael Jordan', '2024-07-15', '2024-07-19', NULL, 'Borrowed', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0095', 'REQ-2026-0095', 'ORG-010', 'EQ-PRC-001', 15, 1, 'Circuit testing', 'Education Bldg', 'Maria Montessori', '2024-07-11', '2024-07-13', '2024-07-13', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0096', 'REQ-2026-0096', 'ORG-011', 'EQ-SCC-001', 22, 1, 'First aid training', 'Physics Lab', 'Albert Einstein', '2024-07-16', '2024-07-20', NULL, 'Borrowed', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0097', 'REQ-2026-0097', 'ORG-012', 'EQ-MIC-001', 27, 1, 'Quiz bowl prep', 'IT Server Room', 'Alan Turing', '2024-07-14', '2024-07-28', NULL, 'Borrowed', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0098', 'REQ-2026-0098', 'ORG-013', 'EQ-VGA-001', 28, 1, 'Thesis exhibit prep', 'Debate Hall', 'Cicero Brown', '2024-07-17', '2024-07-17', '2024-07-17', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0099', 'REQ-2026-0099', 'ORG-014', 'EQ-FT-001', 38, 1, 'Hackathon practice', 'Biology Garden', 'Charles Darwin', '2024-07-13', '2024-07-15', NULL, 'Overdue', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:55', NULL, NULL, NULL),
('REQ-2026-0100', 'REQ-2026-0100', 'ORG-015', 'EQ-MC-001', 48, 1, 'Product demo setup', 'Design Studio', 'Frank Wright', '2024-07-18', '2024-07-18', '2024-07-18', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:55', NULL, NULL, NULL),
('REQ-2026-0101', 'REQ-2026-0101', 'ORG-001', 'EQ-MC-001', 43, 1, 'Seminar', 'ICT Laboratory', 'Anne Patrice Arbolente', '2026-04-13', '2026-04-14', NULL, 'Overdue', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0102', 'REQ-2026-0102', 'ORG-001', 'EQ-FT-001', 33, 1, 'Seminar', 'ICT Laboratory', 'Anne Patrice Arbolente', '2026-04-13', '2026-04-14', NULL, 'Overdue', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0103', 'REQ-2026-0103', 'ORG-001', 'EQ-MC-001', 44, 1, 'Seminar', 'ICT Laboratory', 'Anne Patrice Arbolente', '2026-04-13', '2026-04-14', NULL, 'Overdue', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0104', 'REQ-2026-0104', 'ORG-001', 'EQ-HDMI-001', 8, 1, 'Seminar', 'ICT Laboratory', 'Anne Patrice Arbolente', '2026-04-13', '2026-04-14', NULL, 'Pending', 'Pending', NULL, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0105-105', 'REQ-2026-0105', 'ORG-001', 'EQ-PJ-001', 4, 1, 'Seminar', 'ICT Laboratory', 'Anne Patrice Arbolente', '2026-04-13', '2026-04-14', '2026-04-13', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0105-106', 'REQ-2026-0105', 'ORG-001', 'EQ-CAM-001', 6, 1, 'Seminar', 'ICT Laboratory', 'Anne Patrice Arbolente', '2026-04-13', '2026-04-14', '2026-04-13', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0107-01', 'REQ-2026-0107', 'ORG-001', 'EQ-MC-001', 44, 1, 'Seminar', 'ICT Laboratory', 'Anne Patrice Arbolente', '2026-04-13', '2026-04-14', '2026-04-13', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0107-02', 'REQ-2026-0107', 'ORG-001', 'EQ-FT-001', 38, 1, 'Seminar', 'ICT Laboratory', 'Anne Patrice Arbolente', '2026-04-13', '2026-04-14', '2026-04-13', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0108-01', 'REQ-2026-0108', 'ORG-001', 'EQ-MC-001', 44, 1, 'Seminar', 'ICT Laboratory', 'Anne Patrice Arbolente', '2026-04-13', '2026-04-14', NULL, 'Overdue', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0108-02', 'REQ-2026-0108', 'ORG-001', 'EQ-FT-001', 38, 1, 'Seminar', 'ICT Laboratory', 'Anne Patrice Arbolente', '2026-04-13', '2026-04-14', NULL, 'Overdue', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0109-01', 'REQ-2026-0109', 'ORG-001', 'EQ-PJ-001', 4, 1, 'Semnar', 'ICT Laboratory', 'Anne Patrice Arbolente', '2026-04-13', '2026-04-14', '2026-04-13', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0109-02', 'REQ-2026-0109', 'ORG-001', 'EQ-FT-001', 39, 1, 'Semnar', 'ICT Laboratory', 'Anne Patrice Arbolente', '2026-04-13', '2026-04-14', '2026-04-13', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0109-03', 'REQ-2026-0109', 'ORG-001', 'EQ-MC-001', 46, 1, 'Semnar', 'ICT Laboratory', 'Anne Patrice Arbolente', '2026-04-13', '2026-04-14', '2026-04-13', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0110-01', 'REQ-2026-0110', 'ORG-001', 'EQ-MC-001', 46, 1, 'Seminar', 'ICT Laboratory', 'Anne Patrice Arbolente', '2026-04-14', '2026-04-15', '2026-04-14', 'Returned', 'Approved', 1, '2026-04-15 15:19:09', '2026-04-15 22:32:54', NULL, NULL, NULL),
('REQ-2026-0111-01', 'REQ-2026-0111', 'ORG-001', 'EQ-MC-001', 43, 1, 'Seminar', 'PE Annex 2', 'Raven Catindig', '2026-04-16', '2026-04-17', NULL, 'Overdue', 'Approved', 1, '2026-04-16 11:03:07', '2026-04-18 13:51:30', NULL, NULL, NULL),
('REQ-2026-0112-01', 'REQ-2026-0112', 'ORG-15BD2A', 'EQ-PJ-001', 4, 1, 'Seminar', 'PE Annex 2', 'Raven Catindig', '2026-04-16', '2026-04-17', '2026-04-16', 'Returned', 'Approved', 1, '2026-04-16 11:44:39', '2026-04-16 11:46:25', NULL, NULL, NULL),
('REQ-2026-0113-01', 'REQ-2026-0113', 'ORG-001', 'EQ-PWR-001', 9, 1, 'Seminar', 'PE Annex 2', 'Raven Catindig', '2026-04-16', '2026-04-17', NULL, 'Overdue', 'Approved', 1, '2026-04-16 11:58:51', '2026-04-18 13:51:30', NULL, NULL, NULL),
('REQ-2026-0114-01', 'REQ-2026-0114', 'ORG-001', 'EQ-PJ-001', 4, 1, 'Seminar', 'PE Annex 2', 'Raven Catindig', '2026-04-16', '2026-04-17', NULL, 'Pending', 'Pending', NULL, '2026-04-16 12:01:28', '2026-04-16 12:01:28', NULL, NULL, NULL),
('REQ-2026-0114-02', 'REQ-2026-0114', 'ORG-001', 'EQ-FT-001', 33, 1, 'Seminar', 'PE Annex 2', 'Raven Catindig', '2026-04-16', '2026-04-17', NULL, 'Pending', 'Pending', NULL, '2026-04-16 12:01:28', '2026-04-16 12:01:28', NULL, NULL, NULL),
('REQ-2026-0115-01', 'REQ-2026-0115', 'ORG-001', 'EQ-FT-001', 33, 1, 'Seminar', 'PE Annex 2', 'Raven Catindig', '2026-04-16', '2026-04-17', NULL, 'Borrowed', 'Pending', NULL, '2026-04-16 12:02:08', '2026-04-17 01:23:05', NULL, NULL, NULL),
('REQ-2026-0116-01', 'REQ-2026-0116', 'ORG-001', 'EQ-MC-001', 49, 1, 'Seminar', 'Annex 2', 'Anne Arbolente', '2026-04-17', '2026-04-18', NULL, 'Pending', 'Pending', NULL, '2026-04-17 01:48:56', '2026-04-17 01:48:56', 'data/uploads/oic_ids/50edb4c261b04b67ebc70af684e24a37.jpg', 'image/jpeg', 'Projectorscreendemo.jpg'),
('REQ-2026-0117-01', 'REQ-2026-0117', 'ORG-001', 'EQ-MC-001', 50, 1, 'Seminar', 'Bahay ni Cheska', 'ANNE PATRICE ARBOLENTE', '2026-04-18', '2026-04-19', '2026-04-18', 'Returned', 'Approved', 1, '2026-04-18 15:05:18', '2026-04-18 15:20:42', 'data/uploads/oic_ids/1a09f55a9003173019e3ee8e22ce809c.jpg', 'image/jpeg', '53f5cf1d-32be-4fda-8ee0-5d0feac3252a.jpg'),
('REQ-2026-0117-02', 'REQ-2026-0117', 'ORG-001', 'EQ-MC-001', 51, 1, 'Seminar', 'Bahay ni Cheska', 'ANNE PATRICE ARBOLENTE', '2026-04-18', '2026-04-19', '2026-04-18', 'Returned', 'Approved', 1, '2026-04-18 15:05:18', '2026-04-18 15:20:42', 'data/uploads/oic_ids/1a09f55a9003173019e3ee8e22ce809c.jpg', 'image/jpeg', '53f5cf1d-32be-4fda-8ee0-5d0feac3252a.jpg'),
('REQ-2026-0117-03', 'REQ-2026-0117', 'ORG-001', 'EQ-MC-001', 56, 1, 'Seminar', 'Bahay ni Cheska', 'ANNE PATRICE ARBOLENTE', '2026-04-18', '2026-04-19', '2026-04-18', 'Returned', 'Approved', 1, '2026-04-18 15:05:18', '2026-04-18 15:20:42', 'data/uploads/oic_ids/1a09f55a9003173019e3ee8e22ce809c.jpg', 'image/jpeg', '53f5cf1d-32be-4fda-8ee0-5d0feac3252a.jpg'),
('REQ-2026-0117-04', 'REQ-2026-0117', 'ORG-001', 'EQ-MC-001', 75, 1, 'Seminar', 'Bahay ni Cheska', 'ANNE PATRICE ARBOLENTE', '2026-04-18', '2026-04-19', '2026-04-18', 'Returned', 'Approved', 1, '2026-04-18 15:05:18', '2026-04-18 15:20:42', 'data/uploads/oic_ids/1a09f55a9003173019e3ee8e22ce809c.jpg', 'image/jpeg', '53f5cf1d-32be-4fda-8ee0-5d0feac3252a.jpg'),
('REQ-2026-0117-05', 'REQ-2026-0117', 'ORG-001', 'EQ-MC-001', 76, 1, 'Seminar', 'Bahay ni Cheska', 'ANNE PATRICE ARBOLENTE', '2026-04-18', '2026-04-19', '2026-04-18', 'Returned', 'Approved', 1, '2026-04-18 15:05:18', '2026-04-18 15:20:42', 'data/uploads/oic_ids/1a09f55a9003173019e3ee8e22ce809c.jpg', 'image/jpeg', '53f5cf1d-32be-4fda-8ee0-5d0feac3252a.jpg'),
('REQ-2026-0117-06', 'REQ-2026-0117', 'ORG-001', 'EQ-MC-001', 87, 1, 'Seminar', 'Bahay ni Cheska', 'ANNE PATRICE ARBOLENTE', '2026-04-18', '2026-04-19', '2026-04-18', 'Returned', 'Approved', 1, '2026-04-18 15:05:18', '2026-04-18 15:20:42', 'data/uploads/oic_ids/1a09f55a9003173019e3ee8e22ce809c.jpg', 'image/jpeg', '53f5cf1d-32be-4fda-8ee0-5d0feac3252a.jpg'),
('REQ-2026-0117-07', 'REQ-2026-0117', 'ORG-001', 'EQ-MC-001', 69, 1, 'Seminar', 'Bahay ni Cheska', 'ANNE PATRICE ARBOLENTE', '2026-04-18', '2026-04-19', '2026-04-18', 'Returned', 'Approved', 1, '2026-04-18 15:05:18', '2026-04-18 15:20:42', 'data/uploads/oic_ids/1a09f55a9003173019e3ee8e22ce809c.jpg', 'image/jpeg', '53f5cf1d-32be-4fda-8ee0-5d0feac3252a.jpg'),
('REQ-2026-0117-08', 'REQ-2026-0117', 'ORG-001', 'EQ-MC-001', 67, 1, 'Seminar', 'Bahay ni Cheska', 'ANNE PATRICE ARBOLENTE', '2026-04-18', '2026-04-19', '2026-04-18', 'Returned', 'Approved', 1, '2026-04-18 15:05:18', '2026-04-18 15:20:42', 'data/uploads/oic_ids/1a09f55a9003173019e3ee8e22ce809c.jpg', 'image/jpeg', '53f5cf1d-32be-4fda-8ee0-5d0feac3252a.jpg'),
('REQ-2026-0117-09', 'REQ-2026-0117', 'ORG-001', 'EQ-MC-001', 62, 1, 'Seminar', 'Bahay ni Cheska', 'ANNE PATRICE ARBOLENTE', '2026-04-18', '2026-04-19', '2026-04-18', 'Returned', 'Approved', 1, '2026-04-18 15:05:18', '2026-04-18 15:20:42', 'data/uploads/oic_ids/1a09f55a9003173019e3ee8e22ce809c.jpg', 'image/jpeg', '53f5cf1d-32be-4fda-8ee0-5d0feac3252a.jpg'),
('REQ-2026-0117-10', 'REQ-2026-0117', 'ORG-001', 'EQ-MC-001', 58, 1, 'Seminar', 'Bahay ni Cheska', 'ANNE PATRICE ARBOLENTE', '2026-04-18', '2026-04-19', '2026-04-18', 'Returned', 'Approved', 1, '2026-04-18 15:05:18', '2026-04-18 15:20:42', 'data/uploads/oic_ids/1a09f55a9003173019e3ee8e22ce809c.jpg', 'image/jpeg', '53f5cf1d-32be-4fda-8ee0-5d0feac3252a.jpg'),
('REQ-2026-0118-01', 'REQ-2026-0118', 'ORG-001', 'EQ-MC-001', 47, 1, 'Seminar', 'Bahay ni Cheska', 'ANNE PATRICE ARBOLENTE', '2026-04-18', '2026-04-19', NULL, 'Borrowed', 'Approved', 1, '2026-04-18 15:22:43', '2026-04-18 15:24:40', 'data/uploads/oic_ids/19c17134c88340db9b05b15c83be76d5.jpg', 'image/jpeg', '53f5cf1d-32be-4fda-8ee0-5d0feac3252a.jpg'),
('REQ-2026-0119-01', 'REQ-2026-0119', 'ORG-001', 'EQ-MC-001', 48, 1, 'Seminar', 'Bahay ni Cheska', 'ANNE PATRICE ARBOLENTE', '2026-04-18', '2026-04-19', '2026-04-18', 'Returned', 'Approved', 1, '2026-04-18 15:57:22', '2026-04-18 15:59:50', 'data/uploads/oic_ids/28b8ee4cba34f2383b09d7f1b1c60da5.png', 'image/png', 'Arbolente-Magpayo - Rizal in Action.png');

--
-- Triggers `borrow_transactions`
--
DELIMITER $$
CREATE TRIGGER `check_unit_equipment_match` BEFORE INSERT ON `borrow_transactions` FOR EACH ROW BEGIN
  DECLARE v_equip_id varchar(20);
  SELECT equipment_id INTO v_equip_id FROM equipment_units WHERE unit_id = NEW.unit_id;
  IF v_equip_id != NEW.equipment_id THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'unit_id does not belong to the given equipment_id';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `equipment`
--

CREATE TABLE `equipment` (
  `equipment_id` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `brand` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(50) NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `serial_no` varchar(50) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `equipment`
--

INSERT INTO `equipment` (`equipment_id`, `name`, `brand`, `description`, `category`, `quantity`, `serial_no`, `location`, `image`, `created_at`, `updated_at`) VALUES
('EQ-003', 'Projector Screen', 'Generic', 'Screen for Projector', 'Equipment', 20, NULL, NULL, 'assets/images/uploads/EQ-003_20260416_054932_18023b68.jpg', '2026-04-16 11:49:33', '2026-04-18 14:57:32'),
('EQ-CAM-001', 'Canon EOS R5 Camera', 'Canon', 'For capturing', 'Equipment', 2, NULL, NULL, 'assets/images/canoncam.jpg', '2026-04-15 15:19:09', '2026-04-16 02:00:05'),
('EQ-CAM-002', 'Nikon Z5 II Mirrorless Camera', 'Nikon', 'For capturing', 'Equipment', 0, NULL, NULL, 'assets/images/uploads/EQ-004_20260415_085353_917c0fdf.webp', '2026-04-15 15:19:09', '2026-04-15 15:19:41'),
('EQ-FT-001', 'Foldable Table', 'Generic', 'Portable folding table.', 'Furniture', 10, 'SN-FT-001', 'Storage Room', 'assets/images/foldabletable.jpg', '2026-04-15 15:19:09', '2026-04-16 02:00:05'),
('EQ-HDMI-001', 'HDMI Cable (1.8m)', 'Generic', 'High-speed 4K support.', 'Accessories', 1, 'SN-HDMI-001', 'ICT Office', 'assets/images/hdmicable.jpg', '2026-04-15 15:19:09', '2026-04-16 02:00:05'),
('EQ-MC-001', 'Monoblock Chair', 'Generic', 'Plastic monoblock chair.', 'Furniture', 50, 'SN-MC-001', 'Storage Room', 'assets/images/monoblock.jpeg', '2026-04-15 15:19:09', '2026-04-16 02:00:05'),
('EQ-MIC-001', 'Sony Wireless Mic', 'Sony', 'UHF Wireless Lavalier system.', 'Audio', 3, 'SN-MIC-001', 'AVR Room', 'assets/images/sonymic.jpg', '2026-04-15 15:19:09', '2026-04-16 02:00:05'),
('EQ-PJ-001', 'Epson EB-2250U Projector', 'Epson', '5,000 Lumens, WUXGA Resolution.', 'Visual', 5, 'SN-PJ-001', 'AVR Room', 'assets/images/epsonprojector.jpg', '2026-04-15 15:19:09', '2026-04-16 02:00:05'),
('EQ-PRC-001', 'Projector Remote Control', 'Epson', 'Compatible with Epson EB series.', 'Accessories', 6, 'SN-PRC-001', 'AVR Room', 'assets/images/projectorremote.jpg', '2026-04-15 15:19:09', '2026-04-16 02:00:05'),
('EQ-PWR-001', 'Power Cable (3m)', 'Generic', 'Standard 3-pin power connector.', 'Accessories', 6, 'SN-PWR-001', 'ICT Office', 'assets/images/powercable.jpg', '2026-04-15 15:19:09', '2026-04-16 02:00:05'),
('EQ-SCC-001', 'Soft Carrying Case', 'Generic', 'Padded case for projectors.', 'Storage', 4, 'SN-SCC-001', 'Equipment Room', 'assets/images/projectorcase.jpg', '2026-04-15 15:19:09', '2026-04-16 02:00:05'),
('EQ-VGA-001', 'VGA to HDMI Adapter', 'Generic', 'Active signal conversion.', 'Accessories', 5, 'SN-VGA-001', 'ICT Office', 'assets/images/vgatohdmi.jpg', '2026-04-15 15:19:09', '2026-04-16 02:00:05');

-- --------------------------------------------------------

--
-- Table structure for table `equipment_keywords`
--

CREATE TABLE `equipment_keywords` (
  `keyword_id` int(11) NOT NULL,
  `equipment_id` varchar(20) NOT NULL,
  `keyword` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `equipment_keywords`
--

INSERT INTO `equipment_keywords` (`keyword_id`, `equipment_id`, `keyword`) VALUES
(31, 'EQ-PWR-001', 'power'),
(32, 'EQ-PWR-001', 'cable'),
(33, 'EQ-PWR-001', 'cord'),
(34, 'EQ-PWR-001', 'plug'),
(35, 'EQ-PWR-001', 'electricity'),
(36, 'EQ-PWR-001', 'ac'),
(37, 'EQ-PWR-001', 'adapter'),
(38, 'EQ-PWR-001', 'charger'),
(39, 'EQ-PWR-001', 'extension'),
(40, 'EQ-PWR-001', 'electrical'),
(41, 'EQ-PRC-001', 'remote'),
(42, 'EQ-PRC-001', 'control'),
(43, 'EQ-PRC-001', 'projector'),
(44, 'EQ-PRC-001', 'epson'),
(45, 'EQ-PRC-001', 'wireless'),
(46, 'EQ-PRC-001', 'infrared'),
(47, 'EQ-PRC-001', 'pointer'),
(48, 'EQ-PRC-001', 'laser'),
(49, 'EQ-PRC-001', 'handheld'),
(50, 'EQ-PRC-001', 'ir'),
(51, 'EQ-SCC-001', 'case'),
(52, 'EQ-SCC-001', 'carrying'),
(53, 'EQ-SCC-001', 'storage'),
(54, 'EQ-SCC-001', 'bag'),
(55, 'EQ-SCC-001', 'padded'),
(56, 'EQ-SCC-001', 'protective'),
(57, 'EQ-SCC-001', 'container'),
(58, 'EQ-SCC-001', 'pouch'),
(59, 'EQ-SCC-001', 'cover'),
(60, 'EQ-SCC-001', 'box'),
(81, 'EQ-FT-001', 'table'),
(82, 'EQ-FT-001', 'foldable'),
(83, 'EQ-FT-001', 'folding'),
(84, 'EQ-FT-001', 'furniture'),
(85, 'EQ-FT-001', 'portable'),
(86, 'EQ-MC-001', 'chair'),
(87, 'EQ-MC-001', 'monoblock'),
(88, 'EQ-MC-001', 'furniture'),
(89, 'EQ-MC-001', 'plastic'),
(90, 'EQ-MC-001', 'seat'),
(91, 'EQ-PJ-001', 'projector'),
(92, 'EQ-PJ-001', 'epson'),
(93, 'EQ-PJ-001', 'visual'),
(94, 'EQ-PJ-001', 'presentation'),
(95, 'EQ-PJ-001', 'lcd'),
(96, 'EQ-PJ-001', 'display'),
(97, 'EQ-PJ-001', 'screen'),
(98, 'EQ-PJ-001', 'lamp'),
(99, 'EQ-PJ-001', 'beamer'),
(100, 'EQ-PJ-001', 'bulb'),
(131, 'EQ-HDMI-001', 'hdmi'),
(132, 'EQ-HDMI-001', 'cable'),
(133, 'EQ-HDMI-001', 'connector'),
(134, 'EQ-HDMI-001', 'video'),
(135, 'EQ-HDMI-001', '4k'),
(136, 'EQ-HDMI-001', 'wire'),
(137, 'EQ-HDMI-001', 'plug'),
(138, 'EQ-HDMI-001', 'audio'),
(139, 'EQ-HDMI-001', 'digital'),
(140, 'EQ-HDMI-001', 'transmission'),
(151, 'EQ-MIC-001', 'microphone'),
(152, 'EQ-MIC-001', 'mic'),
(153, 'EQ-MIC-001', 'wireless'),
(154, 'EQ-MIC-001', 'sony'),
(155, 'EQ-MIC-001', 'audio'),
(156, 'EQ-MIC-001', 'uhf'),
(157, 'EQ-MIC-001', 'lavalier'),
(158, 'EQ-MIC-001', 'sound'),
(159, 'EQ-MIC-001', 'speech'),
(160, 'EQ-MIC-001', 'recording'),
(171, 'EQ-VGA-001', 'vga'),
(172, 'EQ-VGA-001', 'adapter'),
(173, 'EQ-VGA-001', 'hdmi'),
(174, 'EQ-VGA-001', 'converter'),
(175, 'EQ-VGA-001', 'analog'),
(176, 'EQ-VGA-001', 'digital'),
(177, 'EQ-VGA-001', 'video'),
(178, 'EQ-VGA-001', 'display'),
(179, 'EQ-VGA-001', 'output'),
(180, 'EQ-VGA-001', 'port'),
(181, 'EQ-CAM-002', 'nikon'),
(182, 'EQ-CAM-002', 'camera'),
(183, 'EQ-CAM-002', 'mirrorless'),
(184, 'EQ-CAM-002', 'z5'),
(185, 'EQ-CAM-002', 'photo'),
(186, 'EQ-CAM-002', 'photography'),
(187, 'EQ-CAM-002', 'video'),
(188, 'EQ-CAM-002', 'capture'),
(189, 'EQ-CAM-002', 'dslr');

-- --------------------------------------------------------

--
-- Table structure for table `equipment_units`
--

CREATE TABLE `equipment_units` (
  `unit_id` int(11) NOT NULL,
  `equipment_id` varchar(20) NOT NULL,
  `unit_number` int(11) NOT NULL,
  `condition_tag` enum('NEW','EXCELLENT','GOOD','FAIR','POOR') DEFAULT 'GOOD',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `equipment_units`
--

INSERT INTO `equipment_units` (`unit_id`, `equipment_id`, `unit_number`, `condition_tag`, `updated_at`) VALUES
(1, 'EQ-PJ-001', 1, 'GOOD', '2026-04-15 15:19:09'),
(2, 'EQ-PJ-001', 2, 'EXCELLENT', '2026-04-15 15:19:09'),
(3, 'EQ-PJ-001', 3, 'GOOD', '2026-04-15 15:19:09'),
(4, 'EQ-PJ-001', 4, 'EXCELLENT', '2026-04-15 15:19:09'),
(5, 'EQ-PJ-001', 5, 'EXCELLENT', '2026-04-15 15:19:09'),
(6, 'EQ-CAM-001', 1, 'NEW', '2026-04-15 15:19:09'),
(7, 'EQ-CAM-001', 2, 'GOOD', '2026-04-15 15:19:09'),
(8, 'EQ-HDMI-001', 1, 'GOOD', '2026-04-15 15:19:09'),
(9, 'EQ-PWR-001', 1, 'GOOD', '2026-04-15 15:19:09'),
(10, 'EQ-PWR-001', 2, 'GOOD', '2026-04-15 15:19:09'),
(11, 'EQ-PWR-001', 3, 'GOOD', '2026-04-15 15:19:09'),
(12, 'EQ-PWR-001', 4, 'GOOD', '2026-04-15 15:19:09'),
(13, 'EQ-PWR-001', 5, 'GOOD', '2026-04-15 15:19:09'),
(14, 'EQ-PWR-001', 6, 'GOOD', '2026-04-15 15:19:09'),
(15, 'EQ-PRC-001', 1, 'GOOD', '2026-04-15 15:19:09'),
(16, 'EQ-PRC-001', 2, 'GOOD', '2026-04-15 15:19:09'),
(17, 'EQ-PRC-001', 3, 'GOOD', '2026-04-15 15:19:09'),
(18, 'EQ-PRC-001', 4, 'GOOD', '2026-04-15 15:19:09'),
(19, 'EQ-PRC-001', 5, 'GOOD', '2026-04-15 15:19:09'),
(20, 'EQ-PRC-001', 6, 'GOOD', '2026-04-15 15:19:09'),
(21, 'EQ-SCC-001', 1, 'GOOD', '2026-04-15 15:19:09'),
(22, 'EQ-SCC-001', 2, 'GOOD', '2026-04-15 15:19:09'),
(23, 'EQ-SCC-001', 3, 'GOOD', '2026-04-15 15:19:09'),
(24, 'EQ-SCC-001', 4, 'GOOD', '2026-04-15 15:19:09'),
(25, 'EQ-MIC-001', 1, 'GOOD', '2026-04-15 15:19:09'),
(26, 'EQ-MIC-001', 2, 'GOOD', '2026-04-15 15:19:09'),
(27, 'EQ-MIC-001', 3, 'GOOD', '2026-04-15 15:19:09'),
(28, 'EQ-VGA-001', 1, 'GOOD', '2026-04-15 15:19:09'),
(29, 'EQ-VGA-001', 2, 'GOOD', '2026-04-15 15:19:09'),
(30, 'EQ-VGA-001', 3, 'GOOD', '2026-04-15 15:19:09'),
(31, 'EQ-VGA-001', 4, 'GOOD', '2026-04-15 15:19:09'),
(32, 'EQ-VGA-001', 5, 'GOOD', '2026-04-15 15:19:09'),
(33, 'EQ-FT-001', 1, 'GOOD', '2026-04-15 15:19:09'),
(34, 'EQ-FT-001', 2, 'GOOD', '2026-04-15 15:19:09'),
(35, 'EQ-FT-001', 3, 'GOOD', '2026-04-15 15:19:09'),
(36, 'EQ-FT-001', 4, 'GOOD', '2026-04-15 15:19:09'),
(37, 'EQ-FT-001', 5, 'GOOD', '2026-04-15 15:19:09'),
(38, 'EQ-FT-001', 6, 'GOOD', '2026-04-15 15:19:09'),
(39, 'EQ-FT-001', 7, 'GOOD', '2026-04-15 15:19:09'),
(40, 'EQ-FT-001', 8, 'GOOD', '2026-04-15 15:19:09'),
(41, 'EQ-FT-001', 9, 'GOOD', '2026-04-15 15:19:09'),
(42, 'EQ-FT-001', 10, 'GOOD', '2026-04-15 15:19:09'),
(43, 'EQ-MC-001', 1, 'GOOD', '2026-04-15 15:19:09'),
(44, 'EQ-MC-001', 2, 'GOOD', '2026-04-15 15:19:09'),
(45, 'EQ-MC-001', 3, 'GOOD', '2026-04-15 15:19:09'),
(46, 'EQ-MC-001', 4, 'GOOD', '2026-04-15 15:19:09'),
(47, 'EQ-MC-001', 5, 'GOOD', '2026-04-15 15:19:09'),
(48, 'EQ-MC-001', 6, 'GOOD', '2026-04-15 15:19:09'),
(49, 'EQ-MC-001', 7, 'GOOD', '2026-04-15 15:19:09'),
(50, 'EQ-MC-001', 8, 'GOOD', '2026-04-15 15:19:09'),
(51, 'EQ-MC-001', 9, 'GOOD', '2026-04-15 15:19:09'),
(52, 'EQ-MC-001', 10, 'GOOD', '2026-04-15 15:19:09'),
(53, 'EQ-MC-001', 11, 'GOOD', '2026-04-15 15:19:09'),
(54, 'EQ-MC-001', 12, 'GOOD', '2026-04-15 15:19:09'),
(55, 'EQ-MC-001', 13, 'GOOD', '2026-04-15 15:19:09'),
(56, 'EQ-MC-001', 14, 'GOOD', '2026-04-15 15:19:09'),
(57, 'EQ-MC-001', 15, 'GOOD', '2026-04-15 15:19:09'),
(58, 'EQ-MC-001', 16, 'GOOD', '2026-04-15 15:19:09'),
(59, 'EQ-MC-001', 17, 'GOOD', '2026-04-15 15:19:09'),
(60, 'EQ-MC-001', 18, 'GOOD', '2026-04-15 15:19:09'),
(61, 'EQ-MC-001', 19, 'GOOD', '2026-04-15 15:19:09'),
(62, 'EQ-MC-001', 20, 'GOOD', '2026-04-15 15:19:09'),
(63, 'EQ-MC-001', 21, 'GOOD', '2026-04-15 15:19:09'),
(64, 'EQ-MC-001', 22, 'GOOD', '2026-04-15 15:19:09'),
(65, 'EQ-MC-001', 23, 'GOOD', '2026-04-15 15:19:09'),
(66, 'EQ-MC-001', 24, 'GOOD', '2026-04-15 15:19:09'),
(67, 'EQ-MC-001', 25, 'GOOD', '2026-04-15 15:19:09'),
(68, 'EQ-MC-001', 26, 'GOOD', '2026-04-15 15:19:09'),
(69, 'EQ-MC-001', 27, 'GOOD', '2026-04-15 15:19:09'),
(70, 'EQ-MC-001', 28, 'GOOD', '2026-04-15 15:19:09'),
(71, 'EQ-MC-001', 29, 'GOOD', '2026-04-15 15:19:09'),
(72, 'EQ-MC-001', 30, 'GOOD', '2026-04-15 15:19:09'),
(73, 'EQ-MC-001', 31, 'GOOD', '2026-04-15 15:19:09'),
(74, 'EQ-MC-001', 32, 'GOOD', '2026-04-15 15:19:09'),
(75, 'EQ-MC-001', 33, 'GOOD', '2026-04-15 15:19:09'),
(76, 'EQ-MC-001', 34, 'GOOD', '2026-04-15 15:19:09'),
(77, 'EQ-MC-001', 35, 'GOOD', '2026-04-15 15:19:09'),
(78, 'EQ-MC-001', 36, 'GOOD', '2026-04-15 15:19:09'),
(79, 'EQ-MC-001', 37, 'GOOD', '2026-04-15 15:19:09'),
(80, 'EQ-MC-001', 38, 'GOOD', '2026-04-15 15:19:09'),
(81, 'EQ-MC-001', 39, 'GOOD', '2026-04-15 15:19:09'),
(82, 'EQ-MC-001', 40, 'GOOD', '2026-04-15 15:19:09'),
(83, 'EQ-MC-001', 41, 'GOOD', '2026-04-15 15:19:09'),
(84, 'EQ-MC-001', 42, 'GOOD', '2026-04-15 15:19:09'),
(85, 'EQ-MC-001', 43, 'GOOD', '2026-04-15 15:19:09'),
(86, 'EQ-MC-001', 44, 'GOOD', '2026-04-15 15:19:09'),
(87, 'EQ-MC-001', 45, 'GOOD', '2026-04-15 15:19:09'),
(88, 'EQ-MC-001', 46, 'GOOD', '2026-04-15 15:19:09'),
(89, 'EQ-MC-001', 47, 'GOOD', '2026-04-15 15:19:09'),
(90, 'EQ-MC-001', 48, 'GOOD', '2026-04-15 15:19:09'),
(91, 'EQ-MC-001', 49, 'GOOD', '2026-04-15 15:19:09'),
(92, 'EQ-MC-001', 50, 'GOOD', '2026-04-15 15:19:09'),
(113, 'EQ-003', 1, 'GOOD', '2026-04-18 14:57:32'),
(114, 'EQ-003', 2, 'GOOD', '2026-04-18 14:57:32'),
(115, 'EQ-003', 3, 'GOOD', '2026-04-18 14:57:32'),
(116, 'EQ-003', 4, 'GOOD', '2026-04-18 14:57:32'),
(117, 'EQ-003', 5, 'GOOD', '2026-04-18 14:57:32'),
(118, 'EQ-003', 6, 'GOOD', '2026-04-18 14:57:32'),
(119, 'EQ-003', 7, 'GOOD', '2026-04-18 14:57:32'),
(120, 'EQ-003', 8, 'GOOD', '2026-04-18 14:57:32'),
(121, 'EQ-003', 9, 'GOOD', '2026-04-18 14:57:32'),
(122, 'EQ-003', 10, 'GOOD', '2026-04-18 14:57:32'),
(123, 'EQ-003', 11, 'GOOD', '2026-04-18 14:57:32'),
(124, 'EQ-003', 12, 'GOOD', '2026-04-18 14:57:32'),
(125, 'EQ-003', 13, 'GOOD', '2026-04-18 14:57:32'),
(126, 'EQ-003', 14, 'GOOD', '2026-04-18 14:57:32'),
(127, 'EQ-003', 15, 'GOOD', '2026-04-18 14:57:32'),
(128, 'EQ-003', 16, 'GOOD', '2026-04-18 14:57:32'),
(129, 'EQ-003', 17, 'GOOD', '2026-04-18 14:57:32'),
(130, 'EQ-003', 18, 'GOOD', '2026-04-18 14:57:32'),
(131, 'EQ-003', 19, 'GOOD', '2026-04-18 14:57:32'),
(132, 'EQ-003', 20, 'GOOD', '2026-04-18 14:57:32');

--
-- Triggers `equipment_units`
--
DELIMITER $$
CREATE TRIGGER `trg_equipment_units_delete` AFTER DELETE ON `equipment_units` FOR EACH ROW BEGIN
    UPDATE equipment
    SET quantity = quantity - 1
    WHERE equipment_id = OLD.equipment_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_equipment_units_insert` AFTER INSERT ON `equipment_units` FOR EACH ROW BEGIN
    UPDATE equipment
    SET quantity = quantity + 1
    WHERE equipment_id = NEW.equipment_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_equipment_units_update` AFTER UPDATE ON `equipment_units` FOR EACH ROW BEGIN
    IF OLD.equipment_id <> NEW.equipment_id THEN
        UPDATE equipment
        SET quantity = quantity - 1
        WHERE equipment_id = OLD.equipment_id;

        UPDATE equipment
        SET quantity = quantity + 1
        WHERE equipment_id = NEW.equipment_id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `organizations`
--

CREATE TABLE `organizations` (
  `org_id` varchar(10) NOT NULL,
  `org_name` varchar(100) NOT NULL,
  `org_email` varchar(100) NOT NULL,
  `members` int(11) DEFAULT 0,
  `status` varchar(20) DEFAULT 'Active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `recognized_org_id` int(11) DEFAULT NULL,
  `account_id` varchar(32) DEFAULT NULL,
  `account_num_id` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `organizations`
--

INSERT INTO `organizations` (`org_id`, `org_name`, `org_email`, `members`, `status`, `created_at`, `updated_at`, `recognized_org_id`, `account_id`, `account_num_id`) VALUES
('ORG-001', 'Joint Proficient Editors and Game Developers', 'maparbolente@tip.edu.ph', 2, 'Active', '2026-04-15 15:19:09', '2026-04-18 18:17:07', 1, '79dc5b974d689b24', 5),
('ORG-002', 'Junior Marketing Association', 'jma.school@edu.ph', 0, 'Active', '2026-04-15 15:19:09', '2026-04-18 18:17:07', 2, 'a1b2c3d4e5f60001', 7),
('ORG-003', 'Photography Club', 'photoclub.school@edu.ph', 0, 'Active', '2026-04-15 15:19:09', '2026-04-18 18:17:07', 3, 'a1b2c3d4e5f60002', 8),
('ORG-004', 'Electronics and Robotics Society', 'ers.school@edu.ph', 0, 'Active', '2026-04-15 15:19:09', '2026-04-18 18:17:07', 4, 'a1b2c3d4e5f60003', 9),
('ORG-005', 'Red Cross Youth Chapter', 'rcy.school@edu.ph', 0, 'Active', '2026-04-15 15:19:09', '2026-04-18 18:17:07', 5, 'a1b2c3d4e5f60004', 10),
('ORG-006', 'Mathematics Society', 'mathsoc.school@edu.ph', 0, 'Active', '2026-04-15 15:19:09', '2026-04-18 18:17:07', 6, 'a1b2c3d4e5f60005', 11),
('ORG-007', 'Architecture Students Association', 'asa.school@edu.ph', 12, 'Inactive', '2026-04-15 15:19:09', '2026-04-18 18:17:07', 7, 'a1b2c3d4e5f60006', 12),
('ORG-008', 'Chemistry Enthusiasts Club', 'cec.school@edu.ph', 0, 'Active', '2026-04-15 15:19:09', '2026-04-18 18:17:07', 8, 'a1b2c3d4e5f60007', 13),
('ORG-009', 'Sports Development Council', 'sdc.school@edu.ph', 0, 'Active', '2026-04-15 15:19:09', '2026-04-18 18:17:07', 9, 'a1b2c3d4e5f60008', 14),
('ORG-010', 'Future Educators League', 'fel.school@edu.ph', 0, 'Active', '2026-04-15 15:19:09', '2026-04-18 18:17:07', 10, 'a1b2c3d4e5f60009', 15),
('ORG-011', 'Physics Society', 'physics.school@edu.ph', 0, 'Active', '2026-04-15 15:19:09', '2026-04-18 18:17:07', 11, 'a1b2c3d4e5f60010', 16),
('ORG-012', 'Information Technology Society', 'its.school@edu.ph', 0, 'Active', '2026-04-15 15:19:09', '2026-04-18 18:17:07', 12, 'a1b2c3d4e5f60011', 17),
('ORG-013', 'Literary and Debate Society', 'lds.school@edu.ph', 0, 'Active', '2026-04-15 15:19:09', '2026-04-18 18:17:07', 13, 'a1b2c3d4e5f60012', 18),
('ORG-014', 'Biology and Environment Club', 'bec.school@edu.ph', 0, 'Active', '2026-04-15 15:19:09', '2026-04-18 18:17:07', 14, 'a1b2c3d4e5f60013', 19),
('ORG-015', 'CADSS', 'cadss.school@edu.ph', 0, 'Active', '2026-04-15 15:19:09', '2026-04-18 18:17:07', 15, 'a1b2c3d4e5f60014', 20),
('ORG-016', 'AWS', 'aws.school@edu.ph', 0, 'Active', '2026-04-15 15:19:09', '2026-04-18 18:17:07', 16, 'a1b2c3d4e5f60015', 21),
('ORG-017', 'ICONS', 'icons.school@edu.ph', 0, 'Active', '2026-04-15 15:19:09', '2026-04-18 18:17:07', 17, 'a1b2c3d4e5f60016', 22),
('ORG-15BD2A', 'JPEG', 'eizantagaki@gmail.com', 0, 'Active', '2026-04-16 11:42:16', '2026-04-18 18:17:07', 18, NULL, NULL),
('ORG-524AF8', 'Shofia Loise Magpayo', 'mslmagpayo@tip.edu.ph', 0, 'Active', '2026-04-15 15:19:09', '2026-04-18 18:17:07', 19, 'dd6f969e01c330e3', 24),
('ORG-6ABDDA', 'JPChe', 'cheesythess@gmail.com', 0, 'Active', '2026-04-18 15:37:37', '2026-04-18 18:17:07', 34, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `recognized_organizations`
--

CREATE TABLE `recognized_organizations` (
  `recognized_org_id` int(11) NOT NULL,
  `org_name` varchar(255) NOT NULL,
  `acronym` varchar(50) DEFAULT NULL,
  `org_email` varchar(100) DEFAULT NULL,
  `org_type` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `recognized_organizations`
--

INSERT INTO `recognized_organizations` (`recognized_org_id`, `org_name`, `acronym`, `org_email`, `org_type`) VALUES
(1, 'Joint Proficient Editors and Game Developers', NULL, 'maparbolente@tip.edu.ph', NULL),
(2, 'Junior Marketing Association', NULL, 'jma.school@edu.ph', NULL),
(3, 'Photography Club', NULL, 'photoclub.school@edu.ph', NULL),
(4, 'Electronics and Robotics Society', NULL, 'ers.school@edu.ph', NULL),
(5, 'Red Cross Youth Chapter', NULL, 'rcy.school@edu.ph', NULL),
(6, 'Mathematics Society', NULL, 'mathsoc.school@edu.ph', NULL),
(7, 'Architecture Students Association', NULL, 'asa.school@edu.ph', NULL),
(8, 'Chemistry Enthusiasts Club', NULL, 'cec.school@edu.ph', NULL),
(9, 'Sports Development Council', NULL, 'sdc.school@edu.ph', NULL),
(10, 'Future Educators League', NULL, 'fel.school@edu.ph', NULL),
(11, 'Physics Society', NULL, 'physics.school@edu.ph', NULL),
(12, 'Information Technology Society', NULL, 'its.school@edu.ph', NULL),
(13, 'Literary and Debate Society', NULL, 'lds.school@edu.ph', NULL),
(14, 'Biology and Environment Club', NULL, 'bec.school@edu.ph', NULL),
(15, 'CADSS', NULL, 'cadss.school@edu.ph', NULL),
(16, 'AWS', NULL, 'aws.school@edu.ph', NULL),
(17, 'ICONS', NULL, 'icons.school@edu.ph', NULL),
(18, 'JPEG', NULL, 'eizantagaki@gmail.com', NULL),
(19, 'Shofia Loise Magpayo', NULL, 'mslmagpayo@tip.edu.ph', NULL),
(33, 'Microsoft Student Community', 'MSC', NULL, NULL),
(34, 'JPChe', 'JPChe', 'cheesythess@gmail.com', NULL),
(35, 'CCS', 'CCS', 'princeeleazarflexio@Gmail.com', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `registration_requests`
--

CREATE TABLE `registration_requests` (
  `request_id` varchar(32) NOT NULL,
  `org_name` varchar(100) NOT NULL,
  `org_email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `account_role` enum('admin','staff','organization') NOT NULL DEFAULT 'organization',
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `requested_at` datetime DEFAULT current_timestamp(),
  `account_id` varchar(32) DEFAULT NULL,
  `account_num_id` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `registration_requests`
--

INSERT INTO `registration_requests` (`request_id`, `org_name`, `org_email`, `password_hash`, `account_role`, `status`, `requested_at`, `account_id`, `account_num_id`) VALUES
('06ad5ccf847a68f2', 'ICONS', 'meablola@tip.edu.ph', '$2y$10$lO/Gd9UG8io.VYB5zmYDhuMMbRoozQ.QQRsSjzaf/vPQC0L/k.Jp6', 'organization', 'pending', '2026-04-11 04:41:53', NULL, NULL),
('2586038fcaf7530f', 'Anne Patrice Arbolente', 'annepatricearbolente@gmail.com', '$2y$10$gNj2VrylhqVUMSks1na6vOCNVYwb4CPNZqc8JklctTiWDkJTQe8qG', 'staff', 'approved', '2026-04-16 10:59:11', NULL, NULL),
('336d46054ec3dfba', 'JPChe', 'cheesythess@gmail.com', '$2y$10$clCRksJfJBFiUik6Nmjpi.3mR72vBz8aF1Qh7GWgLwRovx6ag50Ca', 'organization', 'approved', '2026-04-18 15:37:10', NULL, NULL),
('4111afb43f00dc1b', 'JPEG', 'maparbolente@tip.edu.ph', '$2y$10$5mcLtSoO3gpGsyMTAetTo.N.Wz3rauRSPQtCljQiiIyiwraKUmjaq', 'organization', 'approved', '2026-04-12 20:04:43', '79dc5b974d689b24', 5),
('8f87515fbdc0bcad', 'Aquino', 'mjraquino2@tip.edu.ph', '$2y$10$FHb2j2e5WmwejOJf8hvD1OyEhiMpbLVeIWPB8AIs1VARo0oAHXgfK', 'admin', 'approved', '2026-04-14 20:44:04', '51a12728305e5094', 2),
('abf9a778341b6206', 'Shofia Loise Magpayo', 'mslmagpayo@tip.edu.ph', '$2y$10$6iYk.Y7nVUBH8TP/Vjak9OcKHyQPPb0T.socW4Mdo3Dlb41WCrwV.', 'organization', 'approved', '2026-04-14 20:40:17', 'dd6f969e01c330e3', 24),
('b25006ae429c5934', 'JPEG', 'eizantagaki@gmail.com', '$2y$10$9lm02GGjhwc/pAXPDID3SurracyCE757V2M8mKPZf85/cjpFJ6xea', 'organization', 'approved', '2026-04-16 11:37:31', NULL, NULL),
('c90f59bfa14cfb84', 'ROSE CARMEN ESPINO', 'rosesarewhat13@gmail.com', '$2y$10$/uREXmM.ppSyfS95oB8n9uk3npCdWQ7MqXCrFe3xBZqQz6Y4K19hC', 'organization', 'pending', '2026-04-13 00:33:06', NULL, NULL),
('e9105eb1206e2662', 'Arbolente', 'yugotrenth@gmail.com', '$2y$10$XSkLj9y07eJ7Au7XmtIYNujE1Drn5RCr3cnb0Ytn1TTh4bz4TVl6W', 'staff', 'approved', '2026-04-14 22:00:04', '84bf2d52bf19d2b9', 6),
('ed99087070f697e4', 'Microsoft Student Community', 'mayamiya627@gmail.com', '$2y$10$Ly8kKhuLQ/yYkC.ALOb16Op0p4SwSoQP2GWqlVsTgrxdTfZAAV5AG', 'organization', 'pending', '2026-04-17 01:50:59', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staff_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `position` varchar(50) DEFAULT 'Staff',
  `status` enum('Active','Restricted') NOT NULL DEFAULT 'Active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `account_id` varchar(32) DEFAULT NULL,
  `account_num_id` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`staff_id`, `name`, `email`, `position`, `status`, `created_at`, `updated_at`, `account_id`, `account_num_id`) VALUES
(1, 'Cheska Diaz', 'chesskaeunice@gmail.com', 'Staff', 'Active', '2026-04-15 15:19:09', '2026-04-18 15:47:34', '7438d3450bca178e', 4),
(2, 'Raven Catindig', 'mrjcatindig@tip.edu.ph', 'Staff', 'Active', '2026-04-15 15:19:09', '2026-04-15 21:51:41', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`account_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `uq_accounts_id` (`id`);

--
-- Indexes for table `borrow_transactions`
--
ALTER TABLE `borrow_transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `org_id` (`org_id`),
  ADD KEY `equipment_id` (`equipment_id`),
  ADD KEY `unit_id` (`unit_id`),
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `borrow_transactions_ibfk_approved_by` (`approved_by_staff_id`),
  ADD KEY `idx_borrow_transactions_request_group_id` (`request_group_id`),
  ADD KEY `idx_bt_request_group_id` (`request_group_id`),
  ADD KEY `idx_bt_unit_id` (`unit_id`),
  ADD KEY `idx_bt_equipment_id` (`equipment_id`),
  ADD KEY `idx_bt_status_approval` (`status`,`approval_status`);

--
-- Indexes for table `equipment`
--
ALTER TABLE `equipment`
  ADD PRIMARY KEY (`equipment_id`);

--
-- Indexes for table `equipment_keywords`
--
ALTER TABLE `equipment_keywords`
  ADD PRIMARY KEY (`keyword_id`),
  ADD KEY `equipment_id` (`equipment_id`);

--
-- Indexes for table `equipment_units`
--
ALTER TABLE `equipment_units`
  ADD PRIMARY KEY (`unit_id`),
  ADD UNIQUE KEY `uq_equipment_unit_number` (`equipment_id`,`unit_number`),
  ADD KEY `equipment_id` (`equipment_id`);

--
-- Indexes for table `organizations`
--
ALTER TABLE `organizations`
  ADD PRIMARY KEY (`org_id`),
  ADD UNIQUE KEY `uq_organizations_org_email` (`org_email`),
  ADD UNIQUE KEY `uq_organizations_org_name` (`org_name`),
  ADD KEY `fk_org_account` (`account_id`),
  ADD KEY `idx_organizations_recognized_org_id` (`recognized_org_id`),
  ADD KEY `idx_org_account_num_id` (`account_num_id`);

--
-- Indexes for table `recognized_organizations`
--
ALTER TABLE `recognized_organizations`
  ADD PRIMARY KEY (`recognized_org_id`),
  ADD UNIQUE KEY `uq_recognized_org_name` (`org_name`),
  ADD KEY `idx_recognized_org_acronym` (`acronym`);

--
-- Indexes for table `registration_requests`
--
ALTER TABLE `registration_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `fk_reg_account` (`account_id`),
  ADD KEY `idx_reg_account_num_id` (`account_num_id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staff_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_staff_account` (`account_id`),
  ADD KEY `idx_staff_account_num_id` (`account_num_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `equipment_keywords`
--
ALTER TABLE `equipment_keywords`
  MODIFY `keyword_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=190;

--
-- AUTO_INCREMENT for table `equipment_units`
--
ALTER TABLE `equipment_units`
  MODIFY `unit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=133;

--
-- AUTO_INCREMENT for table `recognized_organizations`
--
ALTER TABLE `recognized_organizations`
  MODIFY `recognized_org_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `borrow_transactions`
--
ALTER TABLE `borrow_transactions`
  ADD CONSTRAINT `borrow_transactions_ibfk_1` FOREIGN KEY (`org_id`) REFERENCES `organizations` (`org_id`),
  ADD CONSTRAINT `borrow_transactions_ibfk_2` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`equipment_id`),
  ADD CONSTRAINT `borrow_transactions_ibfk_3` FOREIGN KEY (`unit_id`) REFERENCES `equipment_units` (`unit_id`),
  ADD CONSTRAINT `borrow_transactions_ibfk_approved_by` FOREIGN KEY (`approved_by_staff_id`) REFERENCES `staff` (`staff_id`);

--
-- Constraints for table `equipment_keywords`
--
ALTER TABLE `equipment_keywords`
  ADD CONSTRAINT `equipment_keywords_ibfk_1` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`equipment_id`) ON DELETE CASCADE;

--
-- Constraints for table `equipment_units`
--
ALTER TABLE `equipment_units`
  ADD CONSTRAINT `equipment_units_ibfk_1` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`equipment_id`) ON DELETE CASCADE;

--
-- Constraints for table `organizations`
--
ALTER TABLE `organizations`
  ADD CONSTRAINT `fk_org_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_org_account_num` FOREIGN KEY (`account_num_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_organizations_recognized_org` FOREIGN KEY (`recognized_org_id`) REFERENCES `recognized_organizations` (`recognized_org_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `registration_requests`
--
ALTER TABLE `registration_requests`
  ADD CONSTRAINT `fk_reg_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_reg_account_num` FOREIGN KEY (`account_num_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `fk_staff_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_staff_account_num` FOREIGN KEY (`account_num_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
