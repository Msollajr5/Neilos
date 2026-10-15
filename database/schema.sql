-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Aug 27, 2026 at 09:58 AM
-- Server version: 8.0.44
-- PHP Version: 8.4.24

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `neilosne_portal`
--

-- --------------------------------------------------------

--
-- Table structure for table `active_services`
--

CREATE TABLE `active_services` (
  `id` int UNSIGNED NOT NULL,
  `service_id` varchar(100) NOT NULL,
  `order_id` int UNSIGNED NOT NULL,
  `partner_id` int UNSIGNED NOT NULL,
  `customer_name` varchar(200) NOT NULL,
  `service_type` varchar(100) NOT NULL,
  `circuit_id` varchar(100) DEFAULT NULL,
  `bandwidth_capacity` varchar(100) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `building_name` varchar(200) DEFAULT NULL,
  `kam_id` int UNSIGNED DEFAULT NULL,
  `activation_date` date DEFAULT NULL,
  `billing_start_date` date DEFAULT NULL,
  `status` varchar(100) NOT NULL DEFAULT 'Active',
  `monitoring_status` varchar(100) DEFAULT 'Unknown',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `active_services`
--

INSERT INTO `active_services` (`id`, `service_id`, `order_id`, `partner_id`, `customer_name`, `service_type`, `circuit_id`, `bandwidth_capacity`, `location`, `building_name`, `kam_id`, `activation_date`, `billing_start_date`, `status`, `monitoring_status`, `created_at`, `updated_at`) VALUES
(1, 'SVC-260824-001', 1, 2, 'Magari Mtaaani', 'Layer 2 ( last mile)', 'CKT-FR-260824-001', '', 'Mwenge', 'Mwanga tower', 2, '2026-08-24', '2026-08-24', 'Active', 'Unknown', '2026-08-24 21:30:19', '2026-08-24 21:30:19');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `module` varchar(100) NOT NULL DEFAULT '',
  `record_id` int UNSIGNED DEFAULT NULL,
  `old_value` text,
  `new_value` text,
  `ip_address` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `module`, `record_id`, `old_value`, `new_value`, `ip_address`, `created_at`) VALUES
(1, 1, 'Login', 'users', 1, NULL, NULL, '102.205.249.34', '2026-08-24 11:26:10'),
(2, 1, 'Login', 'users', 1, NULL, NULL, '197.186.8.30', '2026-08-24 11:34:14'),
(3, 1, 'Login', 'users', 1, NULL, NULL, '102.205.249.34', '2026-08-24 12:43:58'),
(4, 1, 'Login', 'users', 1, NULL, NULL, '102.205.249.34', '2026-08-24 13:08:09'),
(5, 1, 'Login', 'users', 1, NULL, NULL, '102.205.249.34', '2026-08-24 14:48:55'),
(6, 1, 'Login', 'users', 1, NULL, NULL, '102.205.249.34', '2026-08-24 14:49:35'),
(7, 1, 'Deleted KYC Application #1 (Fiber Works Ltd - Contractor)', 'kyc', 1, NULL, NULL, '102.205.249.34', '2026-08-24 15:52:45'),
(8, 1, 'Login', 'users', 1, NULL, NULL, '197.186.8.30', '2026-08-24 20:26:45'),
(9, 1, 'Updated Neilos company settings', 'neilos_company_info', 1, NULL, NULL, '197.186.8.30', '2026-08-24 20:35:03'),
(10, 7, 'Login', 'users', 7, NULL, NULL, '37.203.35.7', '2026-08-24 20:36:40'),
(11, 3, 'Login', 'users', 3, NULL, NULL, '197.186.8.30', '2026-08-24 20:37:17'),
(12, 2, 'Login', 'users', 2, NULL, NULL, '197.186.8.30', '2026-08-24 20:37:35'),
(13, 11, 'Login', 'users', 11, NULL, NULL, '197.186.8.30', '2026-08-24 20:37:54'),
(14, 13, 'Login', 'users', 13, NULL, NULL, '197.186.8.30', '2026-08-24 20:38:10'),
(15, 1, 'Updated partner Afrilink Solutions Ltd', 'partners', 2, NULL, NULL, '197.186.8.30', '2026-08-24 20:39:49'),
(16, 7, 'KYC #3 Rejected. Reason: imekosewa', 'kyc', 3, NULL, NULL, '37.203.35.7', '2026-08-24 20:42:44'),
(17, 7, 'KYC #3 Approved', 'kyc', 3, NULL, NULL, '37.203.35.7', '2026-08-24 20:48:34'),
(18, 7, 'Created feasibility request FR-260824-001', 'orders', 1, NULL, NULL, '197.186.8.30', '2026-08-24 20:54:41'),
(19, 3, 'BSA marked technically feasible with NRC discount for order #1 — routed to Management', 'orders', 1, NULL, NULL, '197.186.8.30', '2026-08-24 20:58:27'),
(20, 1, 'User logged out', 'auth', 1, NULL, NULL, '197.186.8.30', '2026-08-24 21:02:31'),
(21, 10, 'Login', 'users', 10, NULL, NULL, '197.186.8.30', '2026-08-24 21:02:56'),
(22, 10, 'Management decision \'Approve with Revised Price\' for order #1', 'orders', 1, NULL, NULL, '197.186.8.30', '2026-08-24 21:05:42'),
(23, 7, 'SOF generated for order #1 (Partner: Afrilink Solutions Ltd, KYC Application ID: 3)', 'orders', 1, NULL, NULL, '197.186.8.30', '2026-08-24 21:07:02'),
(24, 7, 'Partner returned order #1 to KAM: sijapenda hela', 'orders', 1, NULL, NULL, '197.186.8.30', '2026-08-24 21:09:32'),
(25, 2, 'KAM approved standard commercial for order #1', 'orders', 1, NULL, NULL, '197.186.8.30', '2026-08-24 21:11:29'),
(26, 7, 'SOF generated for order #1 (Partner: Afrilink Solutions Ltd, KYC Application ID: 3)', 'orders', 1, NULL, NULL, '197.186.8.30', '2026-08-24 21:12:18'),
(27, 7, 'Signed SOF uploaded for order #1', 'orders', 1, NULL, NULL, '197.186.8.30', '2026-08-24 21:12:40'),
(28, 2, 'Countersigned SOF uploaded for order #1 by KAM', 'orders', 1, NULL, NULL, '197.186.8.30', '2026-08-24 21:14:57'),
(29, 2, 'Order #1 moved to Installation by KAM', 'orders', 1, NULL, NULL, '197.186.8.30', '2026-08-24 21:15:21'),
(30, 11, 'Contractor assigned to order #1', 'orders', 1, NULL, NULL, '197.186.8.30', '2026-08-24 21:16:22'),
(31, 13, 'Accepted job assignment #1 for order #1', 'contractor_assignments', 1, NULL, NULL, '197.186.8.30', '2026-08-24 21:17:40'),
(32, 13, 'Evidence uploaded (2 file(s)): Site Photo for assignment #1', 'contractor_evidence', NULL, NULL, NULL, '197.186.8.30', '2026-08-24 21:18:20'),
(33, 13, 'Evidence uploaded (1 file(s)): ONT/ONU Serial for assignment #1', 'contractor_evidence', NULL, NULL, NULL, '197.186.8.30', '2026-08-24 21:18:53'),
(34, 13, 'Evidence uploaded (1 file(s)): Signal Test for assignment #1', 'contractor_evidence', NULL, NULL, NULL, '197.186.8.30', '2026-08-24 21:19:18'),
(35, 13, 'Evidence uploaded (1 file(s)): Speed Test for assignment #1', 'contractor_evidence', NULL, NULL, NULL, '197.186.8.30', '2026-08-24 21:19:50'),
(36, 13, 'Evidence uploaded (1 file(s)): Ping Test for assignment #1', 'contractor_evidence', NULL, NULL, NULL, '197.186.8.30', '2026-08-24 21:20:20'),
(37, 13, 'Evidence uploaded (1 file(s)): Latency Test for assignment #1', 'contractor_evidence', NULL, NULL, NULL, '197.186.8.30', '2026-08-24 21:21:07'),
(38, 13, 'Evidence uploaded (1 file(s)): UAT Sign-off for assignment #1', 'contractor_evidence', NULL, NULL, NULL, '197.186.8.30', '2026-08-24 21:21:43'),
(39, 13, 'Evidence uploaded (1 file(s)): Installation Remarks for assignment #1', 'contractor_evidence', NULL, NULL, NULL, '197.186.8.30', '2026-08-24 21:22:06'),
(40, 13, 'SLA clock paused for order #1 due to blocker', 'orders', 1, NULL, NULL, '197.186.8.30', '2026-08-24 21:22:39'),
(41, 13, 'Progress update on assignment #1 [BLOCKER]', 'contractor_assignments', 1, NULL, NULL, '197.186.8.30', '2026-08-24 21:22:39'),
(42, 13, 'SLA clock resumed for order #1 (paused 0.02h, total paused: 0.02h)', 'orders', 1, NULL, NULL, '197.186.8.30', '2026-08-24 21:23:51'),
(43, 13, 'Progress update on assignment #1 [RESUMED]', 'contractor_assignments', 1, NULL, NULL, '197.186.8.30', '2026-08-24 21:23:51'),
(44, 11, 'Order #1 returned to contractor from Testing', 'orders', 1, NULL, NULL, '197.186.8.30', '2026-08-24 21:25:30'),
(45, 11, 'NOC IP configured flag set to 1 for order #1', 'orders', 1, NULL, NULL, '197.186.8.30', '2026-08-24 21:26:57'),
(46, 11, 'Testing approved for order #1, moved to UAT', 'orders', 1, NULL, NULL, '197.186.8.30', '2026-08-24 21:27:18'),
(47, 7, 'Partner returned order #1 from UAT to Installation', 'orders', 1, NULL, NULL, '197.186.8.30', '2026-08-24 21:28:13'),
(48, 11, 'Project Manager submitted job as complete for order #1, moved to Testing', 'orders', 1, NULL, NULL, '197.186.8.30', '2026-08-24 21:29:15'),
(49, 11, 'Testing approved for order #1, moved to UAT', 'orders', 1, NULL, NULL, '197.186.8.30', '2026-08-24 21:29:42'),
(50, 7, 'Partner accepted service — order #1 CLOSED. Billing start: 2026-08-24', 'orders', 1, NULL, 'closed_date=2026-08-24', '197.186.8.30', '2026-08-24 21:30:20'),
(51, 7, 'FTTH bulk upload: 1 created, 0 errors from ftth_bulk_upload_template (2).csv', 'ftth_bulk_uploads', 1, NULL, NULL, '197.186.8.30', '2026-08-24 22:08:49'),
(52, 10, 'Deleted partner Kilimanjaro Telecom Ltd (ID: 3)', 'partners', 3, NULL, NULL, '197.186.8.30', '2026-08-24 22:12:49'),
(53, 10, 'Deleted order #FR-260824-002 (ID: 2)', 'orders', 2, NULL, NULL, '197.186.8.30', '2026-08-24 22:13:24'),
(54, 3, 'Login', 'users', 3, NULL, NULL, '197.186.3.49', '2026-08-25 16:56:42'),
(55, 3, 'User logged out', 'auth', 3, NULL, NULL, '197.186.3.49', '2026-08-25 16:59:44'),
(56, 1, 'Login', 'users', 1, NULL, NULL, '197.186.3.49', '2026-08-25 17:25:33'),
(57, 1, 'Login', 'users', 1, NULL, NULL, '197.186.3.49', '2026-08-25 17:26:00'),
(58, 1, 'Login', 'users', 1, NULL, NULL, '197.186.75.148', '2026-08-25 22:08:42'),
(59, 1, 'Login', 'users', 1, NULL, NULL, '102.205.249.34', '2026-08-26 12:03:11');

-- --------------------------------------------------------

--
-- Table structure for table `contractor_assignments`
--

CREATE TABLE `contractor_assignments` (
  `id` int UNSIGNED NOT NULL,
  `order_id` int UNSIGNED NOT NULL,
  `contractor_partner_id` int UNSIGNED NOT NULL COMMENT 'partners.id where kyc_type=Contractor',
  `contractor_user_id` int UNSIGNED DEFAULT NULL COMMENT 'Specific contractor user assigned',
  `assigned_by` int UNSIGNED NOT NULL COMMENT 'Project Manager user id',
  `assigned_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `target_date` date DEFAULT NULL,
  `work_order_notes` text,
  `status` varchar(100) NOT NULL DEFAULT 'Assigned',
  `accepted_by` int UNSIGNED DEFAULT NULL,
  `accepted_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `completion_remarks` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `contractor_assignments`
--

INSERT INTO `contractor_assignments` (`id`, `order_id`, `contractor_partner_id`, `contractor_user_id`, `assigned_by`, `assigned_at`, `target_date`, `work_order_notes`, `status`, `accepted_by`, `accepted_at`, `completed_at`, `completion_remarks`) VALUES
(1, 1, 4, NULL, 11, '2026-08-24 21:16:22', '2026-08-25', 'fanya vzuri', 'Completed Submitted', 13, '2026-08-24 21:17:40', '2026-08-24 21:29:15', '');

-- --------------------------------------------------------

--
-- Table structure for table `contractor_evidence`
--

CREATE TABLE `contractor_evidence` (
  `id` int UNSIGNED NOT NULL,
  `assignment_id` int UNSIGNED NOT NULL,
  `order_id` int UNSIGNED NOT NULL,
  `evidence_type` varchar(100) NOT NULL,
  `serial_number` varchar(200) DEFAULT NULL COMMENT 'For ONT/ONU serial number',
  `notes` text,
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_size` int UNSIGNED DEFAULT NULL,
  `uploaded_by` int UNSIGNED NOT NULL,
  `uploaded_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `is_mandatory` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `contractor_evidence`
--

INSERT INTO `contractor_evidence` (`id`, `assignment_id`, `order_id`, `evidence_type`, `serial_number`, `notes`, `file_name`, `file_path`, `file_size`, `uploaded_by`, `uploaded_at`, `is_mandatory`) VALUES
(1, 1, 1, 'Site Photo', NULL, NULL, 'localhost_Neilos_public__page=order_detail&id=6 (1).png', 'uploads/orders/1/evidence/8b4646d0eda64723de57b5860b4e0ee3.png', 348487, 13, '2026-08-24 21:18:20', 0),
(2, 1, 1, 'Site Photo', NULL, NULL, 'localhost_Neilos_public__page=order_detail&id=6.png', 'uploads/orders/1/evidence/796670b6966f9f15f08672b7ad3b23bf.png', 359446, 13, '2026-08-24 21:18:20', 0),
(3, 1, 1, 'ONT/ONU Serial', 'HWTC12345678', NULL, 'localhost_Neilos_public__page=sla_tracking (1).png', 'uploads/orders/1/evidence/2315382db5fabfd25c346c4c7497f36a.png', 282051, 13, '2026-08-24 21:18:53', 0),
(4, 1, 1, 'Signal Test', NULL, NULL, 'localhost_Neilos_public__page=sla_tracking (1).png', 'uploads/orders/1/evidence/be640f7d1af399a93c80f14e37db5090.png', 282051, 13, '2026-08-24 21:19:18', 0),
(5, 1, 1, 'Speed Test', NULL, NULL, 'localhost_Neilos_public__page=order_detail&id=6.png', 'uploads/orders/1/evidence/1947c4a68c867b2911f57c6990dab427.png', 359446, 13, '2026-08-24 21:19:50', 0),
(6, 1, 1, 'Ping Test', NULL, NULL, 'localhost_Neilos_public__page=sla_tracking (1).png', 'uploads/orders/1/evidence/81dff43b272ce1972d7e4027fe99d30e.png', 282051, 13, '2026-08-24 21:20:20', 0),
(7, 1, 1, 'Latency Test', NULL, NULL, 'localhost_Neilos_public__page=sla_tracking (1).png', 'uploads/orders/1/evidence/be6e86eff7f4905d7284ec858e2db89b.png', 282051, 13, '2026-08-24 21:21:07', 0),
(8, 1, 1, 'UAT Sign-off', NULL, NULL, 'localhost_Neilos_public__page=order_detail&id=6.png', 'uploads/orders/1/evidence/858769b555cc3a196a009ab246249376.png', 359446, 13, '2026-08-24 21:21:43', 0),
(9, 1, 1, 'Installation Remarks', NULL, 'Good', NULL, NULL, NULL, 13, '2026-08-24 21:22:06', 0);

-- --------------------------------------------------------

--
-- Table structure for table `contractor_progress_updates`
--

CREATE TABLE `contractor_progress_updates` (
  `id` int UNSIGNED NOT NULL,
  `assignment_id` int UNSIGNED NOT NULL,
  `order_id` int UNSIGNED NOT NULL,
  `updated_by` int UNSIGNED NOT NULL,
  `progress_status` varchar(100) NOT NULL DEFAULT 'In Progress',
  `is_blocker` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'If 1, this update is a blocker — SLA pauses',
  `is_resumed` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'If 1, installation resumed — SLA resumes',
  `delay_reason` enum('Customer Unavailable','Access Denied','Weather','Missing Materials','Technical Issue','Other') DEFAULT NULL,
  `notes` text NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `contractor_progress_updates`
--

INSERT INTO `contractor_progress_updates` (`id`, `assignment_id`, `order_id`, `updated_by`, `progress_status`, `is_blocker`, `is_resumed`, `delay_reason`, `notes`, `created_at`) VALUES
(1, 1, 1, 13, 'In Progress', 0, 0, NULL, 'Job accepted and proceeding to site.', '2026-08-24 21:17:40'),
(2, 1, 1, 13, 'Delayed', 1, 0, 'Weather', 'Mvua', '2026-08-24 21:22:39'),
(3, 1, 1, 13, 'In Progress', 0, 1, NULL, 'good', '2026-08-24 21:23:51'),
(4, 1, 1, 13, 'Completed', 0, 0, NULL, 'Installation completed and submitted for review. ', '2026-08-24 21:24:19'),
(5, 1, 1, 13, 'Completed', 0, 0, NULL, 'Installation completed and submitted for review. ', '2026-08-24 21:26:12');

-- --------------------------------------------------------

--
-- Table structure for table `evidence_checklist_config`
--

CREATE TABLE `evidence_checklist_config` (
  `id` int UNSIGNED NOT NULL,
  `service_type` varchar(100) NOT NULL,
  `evidence_type` varchar(100) NOT NULL,
  `is_mandatory` tinyint(1) DEFAULT '1',
  `is_noc_conditional` tinyint(1) DEFAULT '0' COMMENT 'If 1, only required when noc_ip_configured=1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `evidence_checklist_config`
--

INSERT INTO `evidence_checklist_config` (`id`, `service_type`, `evidence_type`, `is_mandatory`, `is_noc_conditional`, `created_at`) VALUES
(1, 'FTTH', 'Site Photo', 1, 0, '2026-08-24 11:10:42'),
(2, 'FTTH', 'ONT/ONU Serial', 1, 0, '2026-08-24 11:10:42'),
(3, 'FTTH', 'Signal Test', 1, 0, '2026-08-24 11:10:42'),
(4, 'FTTH', 'Speed Test', 1, 1, '2026-08-24 11:10:42'),
(5, 'FTTH', 'Ping Test', 1, 1, '2026-08-24 11:10:42'),
(6, 'FTTH', 'Latency Test', 1, 0, '2026-08-24 11:10:42'),
(7, 'FTTH', 'UAT Sign-off', 1, 0, '2026-08-24 11:10:42'),
(8, 'FTTH', 'Installation Remarks', 1, 0, '2026-08-24 11:10:42'),
(9, 'FTTB', 'Site Photo', 1, 0, '2026-08-24 11:10:42'),
(10, 'FTTB', 'ONT/ONU Serial', 1, 0, '2026-08-24 11:10:42'),
(11, 'FTTB', 'Signal Test', 1, 0, '2026-08-24 11:10:42'),
(12, 'FTTB', 'Speed Test', 1, 1, '2026-08-24 11:10:42'),
(13, 'FTTB', 'Ping Test', 1, 1, '2026-08-24 11:10:42'),
(14, 'FTTB', 'Latency Test', 1, 0, '2026-08-24 11:10:42'),
(15, 'FTTB', 'UAT Sign-off', 1, 0, '2026-08-24 11:10:42'),
(16, 'FTTB', 'Installation Remarks', 1, 0, '2026-08-24 11:10:42'),
(17, 'DIA', 'Site Photo', 1, 0, '2026-08-24 11:10:42'),
(18, 'DIA', 'ONT/ONU Serial', 1, 0, '2026-08-24 11:10:42'),
(19, 'DIA', 'Signal Test', 1, 0, '2026-08-24 11:10:42'),
(20, 'DIA', 'Speed Test', 1, 1, '2026-08-24 11:10:42'),
(21, 'DIA', 'Ping Test', 1, 1, '2026-08-24 11:10:42'),
(22, 'DIA', 'Latency Test', 1, 0, '2026-08-24 11:10:42'),
(23, 'DIA', 'UAT Sign-off', 1, 0, '2026-08-24 11:10:42'),
(24, 'DIA', 'Installation Remarks', 1, 0, '2026-08-24 11:10:42'),
(25, 'Dedicated Layer 2', 'Site Photo', 1, 0, '2026-08-24 11:10:42'),
(26, 'Dedicated Layer 2', 'ONT/ONU Serial', 1, 0, '2026-08-24 11:10:42'),
(27, 'Dedicated Layer 2', 'Signal Test', 1, 0, '2026-08-24 11:10:42'),
(28, 'Dedicated Layer 2', 'Speed Test', 1, 1, '2026-08-24 11:10:42'),
(29, 'Dedicated Layer 2', 'Ping Test', 1, 1, '2026-08-24 11:10:42'),
(30, 'Dedicated Layer 2', 'Latency Test', 1, 0, '2026-08-24 11:10:42'),
(31, 'Dedicated Layer 2', 'UAT Sign-off', 1, 0, '2026-08-24 11:10:42'),
(32, 'Dedicated Layer 2', 'Installation Remarks', 1, 0, '2026-08-24 11:10:42'),
(33, 'Layer 2 ( last mile)', 'Site Photo', 1, 0, '2026-08-24 11:10:42'),
(34, 'Layer 2 ( last mile)', 'ONT/ONU Serial', 1, 0, '2026-08-24 11:10:42'),
(35, 'Layer 2 ( last mile)', 'Signal Test', 1, 0, '2026-08-24 11:10:42'),
(36, 'Layer 2 ( last mile)', 'Speed Test', 1, 1, '2026-08-24 11:10:42'),
(37, 'Layer 2 ( last mile)', 'Ping Test', 1, 1, '2026-08-24 11:10:42'),
(38, 'Layer 2 ( last mile)', 'Latency Test', 1, 0, '2026-08-24 11:10:42'),
(39, 'Layer 2 ( last mile)', 'UAT Sign-off', 1, 0, '2026-08-24 11:10:42'),
(40, 'Layer 2 ( last mile)', 'Installation Remarks', 1, 0, '2026-08-24 11:10:42'),
(41, 'BIA (Broadband Internet Access)', 'Site Photo', 1, 0, '2026-08-24 11:10:42'),
(42, 'BIA (Broadband Internet Access)', 'ONT/ONU Serial', 1, 0, '2026-08-24 11:10:42'),
(43, 'BIA (Broadband Internet Access)', 'Signal Test', 1, 0, '2026-08-24 11:10:42'),
(44, 'BIA (Broadband Internet Access)', 'Speed Test', 1, 1, '2026-08-24 11:10:42'),
(45, 'BIA (Broadband Internet Access)', 'Ping Test', 1, 1, '2026-08-24 11:10:42'),
(46, 'BIA (Broadband Internet Access)', 'Latency Test', 1, 0, '2026-08-24 11:10:42'),
(47, 'BIA (Broadband Internet Access)', 'UAT Sign-off', 1, 0, '2026-08-24 11:10:42'),
(48, 'BIA (Broadband Internet Access)', 'Installation Remarks', 1, 0, '2026-08-24 11:10:42'),
(49, 'Remote Hands Only', 'Site Photo', 1, 0, '2026-08-24 11:10:42'),
(50, 'Remote Hands Only', 'ONT/ONU Serial', 1, 0, '2026-08-24 11:10:42'),
(51, 'Remote Hands Only', 'Installation Remarks', 1, 0, '2026-08-24 11:10:42'),
(52, 'FTTE', 'Site Photo', 1, 0, '2026-08-24 11:26:09'),
(53, 'FTTE', 'ONT/ONU Serial', 1, 0, '2026-08-24 11:26:09'),
(54, 'FTTE', 'Signal Test', 1, 0, '2026-08-24 11:26:09'),
(55, 'FTTE', 'Speed Test', 1, 0, '2026-08-24 11:26:09'),
(56, 'FTTE', 'Latency Test', 1, 0, '2026-08-24 11:26:09'),
(57, 'FTTE', 'UAT Sign-off', 1, 0, '2026-08-24 11:26:09'),
(58, 'FTTE', 'Installation Remarks', 1, 0, '2026-08-24 11:26:09'),
(59, 'BIA', 'Site Photo', 1, 0, '2026-08-24 11:26:09'),
(60, 'BIA', 'ONT/ONU Serial', 1, 0, '2026-08-24 11:26:09'),
(61, 'BIA', 'Signal Test', 1, 0, '2026-08-24 11:26:09'),
(62, 'BIA', 'Speed Test', 1, 0, '2026-08-24 11:26:09'),
(63, 'BIA', 'Latency Test', 1, 0, '2026-08-24 11:26:09'),
(64, 'BIA', 'UAT Sign-off', 1, 0, '2026-08-24 11:26:09'),
(65, 'BIA', 'Installation Remarks', 1, 0, '2026-08-24 11:26:09');

-- --------------------------------------------------------

--
-- Table structure for table `ftth_bulk_uploads`
--

CREATE TABLE `ftth_bulk_uploads` (
  `id` int UNSIGNED NOT NULL,
  `uploaded_by` int UNSIGNED NOT NULL,
  `original_file` varchar(255) NOT NULL,
  `total_rows` int NOT NULL DEFAULT '0',
  `success_rows` int NOT NULL DEFAULT '0',
  `error_rows` int NOT NULL DEFAULT '0',
  `errors_json` longtext,
  `created_orders` longtext,
  `uploaded_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `ftth_bulk_uploads`
--

INSERT INTO `ftth_bulk_uploads` (`id`, `uploaded_by`, `original_file`, `total_rows`, `success_rows`, `error_rows`, `errors_json`, `created_orders`, `uploaded_at`) VALUES
(1, 7, 'ftth_bulk_upload_template (2).csv', 1, 1, 0, '[]', '2', '2026-08-24 22:08:49');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int UNSIGNED NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `order_id` int UNSIGNED NOT NULL,
  `partner_id` int UNSIGNED NOT NULL,
  `invoice_type` enum('NRC','MRC','NRC_MRC','Usage') NOT NULL DEFAULT 'NRC_MRC',
  `amount_usd` decimal(12,2) NOT NULL,
  `vat_usd` decimal(12,2) NOT NULL,
  `total_usd` decimal(12,2) NOT NULL,
  `amount_tzs` decimal(14,2) NOT NULL,
  `vat_tzs` decimal(14,2) NOT NULL,
  `total_tzs` decimal(14,2) NOT NULL,
  `exchange_rate` decimal(10,4) NOT NULL DEFAULT '2550.0000',
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `due_date` date NOT NULL,
  `status` enum('Draft','Issued','Paid','Partially Paid','Overdue','Cancelled') NOT NULL DEFAULT 'Draft',
  `pdf_path` varchar(500) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kyc_documents`
--

CREATE TABLE `kyc_documents` (
  `id` int UNSIGNED NOT NULL,
  `application_id` int UNSIGNED NOT NULL,
  `doc_type` enum('certificate_of_incorporation','tin_certificate','vrn_certificate','business_license','tax_clearance','bank_proof','id_copy','other') NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int UNSIGNED DEFAULT NULL,
  `uploaded_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kyc_history`
--

CREATE TABLE `kyc_history` (
  `id` int UNSIGNED NOT NULL,
  `kyc_id` int UNSIGNED NOT NULL,
  `action_by` int UNSIGNED DEFAULT NULL,
  `action_role` varchar(50) NOT NULL,
  `from_status` varchar(50) DEFAULT NULL,
  `to_status` varchar(50) NOT NULL,
  `action_title` varchar(200) NOT NULL,
  `details` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `kyc_history`
--

INSERT INTO `kyc_history` (`id`, `kyc_id`, `action_by`, `action_role`, `from_status`, `to_status`, `action_title`, `details`, `created_at`) VALUES
(2, 2, 1, 'System Admin', NULL, 'Draft', 'KYC Created by Admin/Management', NULL, '2026-08-24 15:56:41'),
(3, 3, 1, 'System Admin', NULL, 'Draft', 'KYC Created by Admin/Management', NULL, '2026-08-24 20:29:49'),
(4, 3, 1, 'System Admin', 'Draft', 'Submitted for Approval', 'KYC Submitted for Approval', NULL, '2026-08-24 20:29:49'),
(5, 2, 1, 'System Admin', 'Draft', 'Submitted for Approval', 'KYC Submitted for Approval', NULL, '2026-08-24 20:31:18'),
(6, 3, 7, 'Partner', 'Submitted for Approval', 'Rejected', 'KYC Rejected by Partner', 'Reason: imekosewa', '2026-08-24 20:42:44'),
(7, 3, 1, 'System Admin', 'Rejected', 'Submitted for Approval', 'KYC Resubmitted for Approval', NULL, '2026-08-24 20:44:08'),
(8, 3, 7, 'Partner', 'Submitted for Approval', 'Approved', 'KYC Approved by Partner', 'Approved by Partner Admin (Afrilink) (admin@afrilink.co.tz)', '2026-08-24 20:48:34');

-- --------------------------------------------------------

--
-- Table structure for table `neilos_company_info`
--

CREATE TABLE `neilos_company_info` (
  `id` int UNSIGNED NOT NULL,
  `company_name` varchar(200) NOT NULL DEFAULT 'Neilos Network Ltd',
  `trading_name` varchar(200) DEFAULT NULL,
  `registration_number` varchar(100) DEFAULT NULL,
  `tin` varchar(100) DEFAULT NULL,
  `vat_vrn` varchar(100) DEFAULT NULL,
  `address` text,
  `city_region` varchar(100) DEFAULT 'Dar es Salaam',
  `country` varchar(100) DEFAULT 'Tanzania',
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(200) DEFAULT NULL,
  `website` varchar(200) DEFAULT NULL,
  `authorized_signatory` varchar(200) DEFAULT NULL,
  `signatory_title` varchar(100) DEFAULT NULL,
  `finance_contact` varchar(200) DEFAULT NULL,
  `finance_email` varchar(200) DEFAULT NULL,
  `tech_contact` varchar(200) DEFAULT NULL,
  `tech_email` varchar(200) DEFAULT NULL,
  `logo_path` varchar(500) DEFAULT NULL,
  `updated_by` int UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `neilos_company_info`
--

INSERT INTO `neilos_company_info` (`id`, `company_name`, `trading_name`, `registration_number`, `tin`, `vat_vrn`, `address`, `city_region`, `country`, `phone`, `email`, `website`, `authorized_signatory`, `signatory_title`, `finance_contact`, `finance_email`, `tech_contact`, `tech_email`, `logo_path`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'Neilos Network Ltd', 'Neilos', 'REG-100890', 'TIN-001234567', '4551', 'ILALA', 'Dar es Salaam', 'Tanzania', '+255623700877', 'manager@neilosnetwork.co.tz', 'https://neilos.co.tz', 'Mr Moses Palela', 'Managing Director', 'Irene', 'irene@neilosnetwork.co.tz', 'Noc', 'noc@neilosnetwork.co.tz', NULL, 1, '2026-08-24 11:10:42', '2026-08-24 20:35:03');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(500) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_queue`
--

CREATE TABLE `notification_queue` (
  `id` int UNSIGNED NOT NULL,
  `channel` enum('Email','SMS','WhatsApp') NOT NULL DEFAULT 'Email',
  `recipient` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `context_type` varchar(50) DEFAULT NULL,
  `context_id` int UNSIGNED DEFAULT NULL,
  `status` enum('Queued','Sent','Failed') NOT NULL DEFAULT 'Queued',
  `attempts` int NOT NULL DEFAULT '0',
  `error_message` text,
  `sent_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `notification_queue`
--

INSERT INTO `notification_queue` (`id`, `channel`, `recipient`, `subject`, `message`, `context_type`, `context_id`, `status`, `attempts`, `error_message`, `sent_at`, `created_at`) VALUES
(1, 'Email', 'admin@afrilink.co.tz', 'KYC Application Submitted for Review — Afrilink Solutions Ltd', 'Your KYC application has been submitted for approval. Please log into the portal to review and approve/reject.', 'kyc', 3, 'Queued', 0, NULL, NULL, '2026-08-24 20:29:49'),
(2, 'Email', 'admin@neilos.co.tz', 'KYC Application Submitted — Afrilink Solutions Ltd', 'KYC Application #3 for Afrilink Solutions Ltd has been submitted for approval.', 'kyc', 3, 'Queued', 0, NULL, NULL, '2026-08-24 20:29:49'),
(3, 'Email', 'management@neilos.co.tz', 'KYC Application Submitted — Afrilink Solutions Ltd', 'KYC Application #3 for Afrilink Solutions Ltd has been submitted for approval.', 'kyc', 3, 'Queued', 0, NULL, NULL, '2026-08-24 20:29:49'),
(4, 'Email', 'admin@fiberworks.co.tz', 'KYC Application Submitted for Review — Fiber Works Ltd', 'Your KYC application has been submitted for approval. Please log into the portal to review and approve/reject.', 'kyc', 2, 'Queued', 0, NULL, NULL, '2026-08-24 20:31:18'),
(5, 'Email', 'admin@neilos.co.tz', 'KYC Application Submitted — Fiber Works Ltd', 'KYC Application #2 for Fiber Works Ltd has been submitted for approval.', 'kyc', 2, 'Queued', 0, NULL, NULL, '2026-08-24 20:31:18'),
(6, 'Email', 'management@neilos.co.tz', 'KYC Application Submitted — Fiber Works Ltd', 'KYC Application #2 for Fiber Works Ltd has been submitted for approval.', 'kyc', 2, 'Queued', 0, NULL, NULL, '2026-08-24 20:31:18'),
(7, 'Email', 'admin@neilos.co.tz', 'KYC Application Rejected — Afrilink Solutions Ltd', 'KYC Application #3 for Afrilink Solutions Ltd was REJECTED.\nRejection Reason: imekosewa', 'kyc', 3, 'Queued', 0, NULL, NULL, '2026-08-24 20:42:44'),
(8, 'Email', 'management@neilos.co.tz', 'KYC Application Rejected — Afrilink Solutions Ltd', 'KYC Application #3 for Afrilink Solutions Ltd was REJECTED.\nRejection Reason: imekosewa', 'kyc', 3, 'Queued', 0, NULL, NULL, '2026-08-24 20:42:44'),
(9, 'Email', 'admin@afrilink.co.tz', 'KYC Application Submitted for Review — Afrilink Solutions Ltd', 'Your KYC application has been submitted for approval. Please log into the portal to review and approve/reject.', 'kyc', 3, 'Queued', 0, NULL, NULL, '2026-08-24 20:44:08'),
(10, 'Email', 'admin@neilos.co.tz', 'KYC Application Submitted — Afrilink Solutions Ltd', 'KYC Application #3 for Afrilink Solutions Ltd has been submitted for approval.', 'kyc', 3, 'Queued', 0, NULL, NULL, '2026-08-24 20:44:08'),
(11, 'Email', 'management@neilos.co.tz', 'KYC Application Submitted — Afrilink Solutions Ltd', 'KYC Application #3 for Afrilink Solutions Ltd has been submitted for approval.', 'kyc', 3, 'Queued', 0, NULL, NULL, '2026-08-24 20:44:08'),
(12, 'Email', 'admin@afrilink.co.tz', 'KYC Application Approved — Afrilink Solutions Ltd', 'Your KYC application #3 has been successfully approved.', 'kyc', 3, 'Queued', 0, NULL, NULL, '2026-08-24 20:48:34'),
(13, 'Email', 'admin@neilos.co.tz', 'KYC Approved — Afrilink Solutions Ltd', 'KYC Application #3 for Afrilink Solutions Ltd was approved by the partner/contractor.', 'kyc', 3, 'Queued', 0, NULL, NULL, '2026-08-24 20:48:34'),
(14, 'Email', 'management@neilos.co.tz', 'KYC Approved — Afrilink Solutions Ltd', 'KYC Application #3 for Afrilink Solutions Ltd was approved by the partner/contractor.', 'kyc', 3, 'Queued', 0, NULL, NULL, '2026-08-24 20:48:34'),
(15, 'Email', 'comfortmnyinga@gmail.com', 'New Feasibility Request: FR-260824-001', 'Order FR-260824-001 — Magari Mtaaani (Layer 2 ( last mile)) submitted by partner. Please review technical feasibility.', 'order', 1, 'Queued', 0, NULL, NULL, '2026-08-24 20:54:41'),
(16, 'Email', 'manager@neilosnetwork.co.tz', 'Exception Approval Required: FR-260824-001', 'Order FR-260824-001 — Magari Mtaaani (Layer 2 ( last mile)) requires your exception approval for non-standard commercial terms. KAM: John KAM. Please log in to review and approve.', 'order', 1, 'Queued', 0, NULL, NULL, '2026-08-24 20:58:27'),
(17, 'Email', 'comfortmnyinga@gmail.com', 'Partner Returned: FR-260824-001', 'Order FR-260824-001 — Magari Mtaaani (Layer 2 ( last mile)) — Partner has returned the order from Pending SOF for review. Please check remarks and re-assess.', 'order', 1, 'Queued', 0, NULL, NULL, '2026-08-24 21:09:32'),
(18, 'Email', 'john.kam@neilosnetwork.co.tz', 'Partner Returned: FR-260824-001', 'Order FR-260824-001 — Magari Mtaaani (Layer 2 ( last mile)) — Partner has returned the order from Pending SOF. Please review.', 'order', 1, 'Queued', 0, NULL, NULL, '2026-08-24 21:09:32'),
(19, 'Email', 'admin@afrilink.co.tz', 'Your Service Request Has Been Approved: FR-260824-001', 'Great news! Order FR-260824-001 — Magari Mtaaani (Layer 2 ( last mile)) has been reviewed and approved. Please log in to the Partner Portal to generate and upload your signed Service Order Form (SOF) to proceed.', 'order', 1, 'Queued', 0, NULL, NULL, '2026-08-24 21:11:29'),
(20, 'Email', 'john.kam@neilosnetwork.co.tz', 'Signed SOF Received: FR-260824-001', 'Order FR-260824-001 — Magari Mtaaani (Layer 2 ( last mile)) — Partner has uploaded the signed SOF. Please review, countersign, and upload the Neilos copy to proceed to project.', 'order', 1, 'Queued', 0, NULL, NULL, '2026-08-24 21:12:40'),
(21, 'Email', 'admin@afrilink.co.tz', 'SOF Executed — Installation Will Begin: FR-260824-001', 'Order FR-260824-001 — Magari Mtaaani (Layer 2 ( last mile)) — The Service Order Form has been executed by both parties. Our installation team will contact you to schedule site access.', 'order', 1, 'Queued', 0, NULL, NULL, '2026-08-24 21:14:57'),
(22, 'Email', 'pm@neilosnetwork.co.tz', 'New Installation Order: FR-260824-001', 'Order FR-260824-001 — Magari Mtaaani (Layer 2 ( last mile)) is ready for project assignment. SOF is countersigned. Please assign a contractor and begin installation.', 'order', 1, 'Queued', 0, NULL, NULL, '2026-08-24 21:14:57'),
(23, 'Email', 'pm@neilosnetwork.co.tz', 'Order Released to Installation: FR-260824-001', 'Order FR-260824-001 — Magari Mtaaani (Layer 2 ( last mile)) has been released to installation. Please assign a contractor as soon as possible.', 'order', 1, 'Queued', 0, NULL, NULL, '2026-08-24 21:15:21'),
(24, 'Email', 'pm@neilosnetwork.co.tz', 'Installation Completed — Review Required: FR-260824-001', 'Order FR-260824-001 — Magari Mtaaani (Layer 2 ( last mile)) — Contractor has submitted the installation as complete. Please review evidence and approve for UAT.', 'order', 1, 'Queued', 0, NULL, NULL, '2026-08-24 21:24:19'),
(25, 'Email', 'comfortmnyinga@gmail.com', 'Testing Required: FR-260824-001', 'Order FR-260824-001 — Magari Mtaaani (Layer 2 ( last mile)) is ready for testing review. Contractor has submitted completion.', 'order', 1, 'Queued', 0, NULL, NULL, '2026-08-24 21:24:19'),
(26, 'Email', 'pm@neilosnetwork.co.tz', 'Installation Completed — Review Required: FR-260824-001', 'Order FR-260824-001 — Magari Mtaaani (Layer 2 ( last mile)) — Contractor has submitted the installation as complete. Please review evidence and approve for UAT.', 'order', 1, 'Queued', 0, NULL, NULL, '2026-08-24 21:26:12'),
(27, 'Email', 'comfortmnyinga@gmail.com', 'Testing Required: FR-260824-001', 'Order FR-260824-001 — Magari Mtaaani (Layer 2 ( last mile)) is ready for testing review. Contractor has submitted completion.', 'order', 1, 'Queued', 0, NULL, NULL, '2026-08-24 21:26:12'),
(28, 'Email', 'admin@afrilink.co.tz', 'Your Service Is Ready for Acceptance: FR-260824-001', 'Order FR-260824-001 — Magari Mtaaani (Layer 2 ( last mile)) — Installation is complete and has passed our internal testing. Please log in to the Partner Portal within 48 hours to accept the service or return it for correction. If no response is received within 48 hours, the service will be automatically accepted.', 'order', 1, 'Queued', 0, NULL, NULL, '2026-08-24 21:27:18'),
(29, 'Email', 'pm@neilosnetwork.co.tz', 'UAT Rejected — Returned to Installation: FR-260824-001', 'Order FR-260824-001 — Magari Mtaaani (Layer 2 ( last mile)) — Partner has returned the service from UAT to installation for corrections. Please review their remarks and reassign.', 'order', 1, 'Queued', 0, NULL, NULL, '2026-08-24 21:28:13'),
(30, 'Email', 'comfortmnyinga@gmail.com', 'UAT Returned: FR-260824-001', 'Order FR-260824-001 — Magari Mtaaani (Layer 2 ( last mile)) has been returned from UAT by the partner. Installation corrections required.', 'order', 1, 'Queued', 0, NULL, NULL, '2026-08-24 21:28:13'),
(31, 'Email', 'admin@afrilink.co.tz', 'Your Service Is Ready for Acceptance: FR-260824-001', 'Order FR-260824-001 — Magari Mtaaani (Layer 2 ( last mile)) — Installation is complete and has passed our internal testing. Please log in to the Partner Portal within 48 hours to accept the service or return it for correction. If no response is received within 48 hours, the service will be automatically accepted.', 'order', 1, 'Queued', 0, NULL, NULL, '2026-08-24 21:29:42'),
(32, 'Email', 'john.kam@neilosnetwork.co.tz', 'Order Closed — Service Accepted: FR-260824-001', 'Order FR-260824-001 — Magari Mtaaani (Layer 2 ( last mile)) has been accepted by the partner. Order is now CLOSED. Billing start date: 24 Aug 2026.', 'order', 1, 'Queued', 0, NULL, NULL, '2026-08-24 21:30:20');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int UNSIGNED NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `sof_number` varchar(50) DEFAULT NULL COMMENT 'SO-prefixed reference generated at SOF print',
  `partner_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED DEFAULT NULL,
  `service_id` varchar(100) DEFAULT NULL,
  `capacity` int UNSIGNED DEFAULT '0',
  `capacity_unit` varchar(20) DEFAULT 'Mbps',
  `pop_location` varchar(200) DEFAULT NULL,
  `customer_name` varchar(200) NOT NULL,
  `customer_contact_name` varchar(200) DEFAULT NULL,
  `customer_contact_phone` varchar(50) DEFAULT NULL,
  `customer_contact_email` varchar(200) DEFAULT NULL,
  `customer_location` varchar(255) DEFAULT NULL,
  `building_name` varchar(200) DEFAULT NULL,
  `floor_number` varchar(50) DEFAULT NULL,
  `apartment_number` varchar(50) DEFAULT NULL,
  `end_user_address` text,
  `city_region` varchar(100) DEFAULT NULL,
  `gps_coordinates` varchar(100) DEFAULT NULL,
  `site_category` varchar(100) DEFAULT NULL,
  `interface_type` varchar(50) DEFAULT 'SFP',
  `connector_type` varchar(50) DEFAULT 'LC',
  `media_type` varchar(50) DEFAULT 'Single-mode Fiber',
  `power_type` varchar(50) DEFAULT 'AC 220V',
  `rack_space` varchar(50) DEFAULT NULL,
  `nrc_usd` decimal(12,2) NOT NULL DEFAULT '0.00',
  `mrc_usd` decimal(12,2) NOT NULL DEFAULT '0.00',
  `vat_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_mrc_incl_vat` decimal(12,2) NOT NULL DEFAULT '0.00',
  `standard_mrc` decimal(12,2) DEFAULT NULL,
  `revised_mrc` decimal(12,2) DEFAULT NULL,
  `mrc_justification` text,
  `kam_approved_at` datetime DEFAULT NULL,
  `kam_approved_by` int UNSIGNED DEFAULT NULL,
  `kam_proposed_nrc` decimal(14,2) DEFAULT NULL COMMENT 'KAM exception NRC proposal',
  `kam_proposed_mrc` decimal(14,2) DEFAULT NULL COMMENT 'KAM exception MRC proposal',
  `kam_commercial_justification` text COMMENT 'Mandatory when KAM proposes exception',
  `kam_remarks` text COMMENT 'Optional remarks from KAM',
  `management_approved_at` datetime DEFAULT NULL,
  `management_approved_by` int UNSIGNED DEFAULT NULL,
  `management_approved_price` decimal(12,2) DEFAULT NULL,
  `management_final_nrc` decimal(14,2) DEFAULT NULL COMMENT 'Management final NRC when using Approve with Revised Price',
  `management_final_mrc` decimal(14,2) DEFAULT NULL COMMENT 'Management final MRC when using Approve with Revised Price',
  `management_decision` enum('Approve as Requested','Approve with Revised Price','Keep Standard Price','Return to KAM') DEFAULT NULL,
  `management_remarks` text,
  `management_return_remarks` text COMMENT 'Remarks when Management returns to KAM',
  `management_remarks_visible` tinyint(1) DEFAULT '0',
  `sof_generated_at` datetime DEFAULT NULL,
  `sof_uploaded_at` datetime DEFAULT NULL,
  `sof_signed_file` varchar(500) DEFAULT NULL,
  `sof_signed_filename` varchar(255) DEFAULT NULL,
  `countersigned_sof_at` datetime DEFAULT NULL,
  `countersigned_sof_file` varchar(500) DEFAULT NULL,
  `countersigned_sof_filename` varchar(255) DEFAULT NULL,
  `countersigned_sof_by` int UNSIGNED DEFAULT NULL,
  `sof_return_comments` text,
  `return_reason` varchar(100) DEFAULT NULL,
  `return_remarks` text,
  `return_route` enum('BSA','KAM') DEFAULT NULL,
  `returned_by` int UNSIGNED DEFAULT NULL,
  `returned_at` datetime DEFAULT NULL,
  `service_type` varchar(100) NOT NULL DEFAULT 'FTTH',
  `fttx_package` varchar(100) DEFAULT NULL,
  `bandwidth` varchar(100) DEFAULT NULL,
  `nni_location` varchar(200) DEFAULT NULL,
  `aggregate_capacity` varchar(100) DEFAULT NULL,
  `contract_term` varchar(50) DEFAULT NULL,
  `special_requirements` text,
  `usd_tzs_rate` decimal(12,4) DEFAULT '2600.0000',
  `base_nrc_usd` decimal(14,2) DEFAULT '0.00',
  `remote_hands_nrc_usd` decimal(14,2) DEFAULT '0.00',
  `nrc_subtotal_usd` decimal(14,2) DEFAULT '0.00',
  `vat_on_nrc` decimal(14,2) DEFAULT '0.00',
  `total_nrc_incl_vat` decimal(14,2) DEFAULT '0.00',
  `base_mrc` decimal(14,2) DEFAULT '0.00',
  `mrc_currency` varchar(10) DEFAULT 'TZS',
  `discount_pct` decimal(5,2) DEFAULT '0.00',
  `discount_amount` decimal(14,2) DEFAULT '0.00',
  `vat_on_mrc` decimal(14,2) DEFAULT '0.00',
  `created_by` int UNSIGNED DEFAULT NULL,
  `assigned_kam_name` varchar(100) DEFAULT NULL,
  `kam_id` int UNSIGNED DEFAULT NULL,
  `status` varchar(100) NOT NULL DEFAULT 'Feasibility Review',
  `bsa_reviewed_by` int UNSIGNED DEFAULT NULL,
  `bsa_reviewed_at` datetime DEFAULT NULL,
  `bsa_feasi_approved` tinyint(1) DEFAULT NULL,
  `bsa_remarks` text,
  `bsa_assigned_engineer` varchar(150) DEFAULT NULL,
  `bsa_target_completion_date` date DEFAULT NULL,
  `bsa_special_conditions` text,
  `bsa_technical_result` enum('Technically Feasible','Technically Not Feasible') DEFAULT NULL,
  `bsa_not_feasible_reason` text,
  `standard_nrc` decimal(12,2) DEFAULT '60.00',
  `revised_nrc` decimal(12,2) DEFAULT NULL,
  `nrc_justification` text,
  `bsa_approved_at` datetime DEFAULT NULL,
  `bsa_approved_by` int UNSIGNED DEFAULT NULL,
  `bsa_revision_note` text,
  `kam_reviewed_by` int UNSIGNED DEFAULT NULL,
  `kam_reviewed_at` datetime DEFAULT NULL,
  `commercial_discount_pct` decimal(5,2) DEFAULT '0.00',
  `closed_date` date DEFAULT NULL,
  `billing_start_date` date DEFAULT NULL,
  `billing_start_adjusted_by` int UNSIGNED DEFAULT NULL,
  `billing_start_adjusted_at` datetime DEFAULT NULL,
  `uat_submitted_at` datetime DEFAULT NULL,
  `uat_accepted_at` datetime DEFAULT NULL,
  `uat_accepted_by` int UNSIGNED DEFAULT NULL,
  `uat_rejected_at` datetime DEFAULT NULL,
  `uat_rejection_reason` text,
  `uat_return_reason` text,
  `uat_signoff_file` varchar(500) DEFAULT NULL,
  `billing_trigger_date` date DEFAULT NULL,
  `cancellation_reason` text,
  `sla_paused` tinyint(1) NOT NULL DEFAULT '0',
  `sla_paused_at` datetime DEFAULT NULL,
  `sla_paused_hours` decimal(8,2) NOT NULL DEFAULT '0.00' COMMENT 'Accumulated paused hours',
  `noc_ip_configured` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'PM flag: NOC has completed IP configuration',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `sla_pause_reason` text,
  `circuit_id` varchar(100) DEFAULT NULL,
  `uat_notified_at` datetime DEFAULT NULL,
  `uat_deadline` datetime DEFAULT NULL,
  `activation_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `sof_number`, `partner_id`, `user_id`, `service_id`, `capacity`, `capacity_unit`, `pop_location`, `customer_name`, `customer_contact_name`, `customer_contact_phone`, `customer_contact_email`, `customer_location`, `building_name`, `floor_number`, `apartment_number`, `end_user_address`, `city_region`, `gps_coordinates`, `site_category`, `interface_type`, `connector_type`, `media_type`, `power_type`, `rack_space`, `nrc_usd`, `mrc_usd`, `vat_amount`, `total_mrc_incl_vat`, `standard_mrc`, `revised_mrc`, `mrc_justification`, `kam_approved_at`, `kam_approved_by`, `kam_proposed_nrc`, `kam_proposed_mrc`, `kam_commercial_justification`, `kam_remarks`, `management_approved_at`, `management_approved_by`, `management_approved_price`, `management_final_nrc`, `management_final_mrc`, `management_decision`, `management_remarks`, `management_return_remarks`, `management_remarks_visible`, `sof_generated_at`, `sof_uploaded_at`, `sof_signed_file`, `sof_signed_filename`, `countersigned_sof_at`, `countersigned_sof_file`, `countersigned_sof_filename`, `countersigned_sof_by`, `sof_return_comments`, `return_reason`, `return_remarks`, `return_route`, `returned_by`, `returned_at`, `service_type`, `fttx_package`, `bandwidth`, `nni_location`, `aggregate_capacity`, `contract_term`, `special_requirements`, `usd_tzs_rate`, `base_nrc_usd`, `remote_hands_nrc_usd`, `nrc_subtotal_usd`, `vat_on_nrc`, `total_nrc_incl_vat`, `base_mrc`, `mrc_currency`, `discount_pct`, `discount_amount`, `vat_on_mrc`, `created_by`, `assigned_kam_name`, `kam_id`, `status`, `bsa_reviewed_by`, `bsa_reviewed_at`, `bsa_feasi_approved`, `bsa_remarks`, `bsa_assigned_engineer`, `bsa_target_completion_date`, `bsa_special_conditions`, `bsa_technical_result`, `bsa_not_feasible_reason`, `standard_nrc`, `revised_nrc`, `nrc_justification`, `bsa_approved_at`, `bsa_approved_by`, `bsa_revision_note`, `kam_reviewed_by`, `kam_reviewed_at`, `commercial_discount_pct`, `closed_date`, `billing_start_date`, `billing_start_adjusted_by`, `billing_start_adjusted_at`, `uat_submitted_at`, `uat_accepted_at`, `uat_accepted_by`, `uat_rejected_at`, `uat_rejection_reason`, `uat_return_reason`, `uat_signoff_file`, `billing_trigger_date`, `cancellation_reason`, `sla_paused`, `sla_paused_at`, `sla_paused_hours`, `noc_ip_configured`, `created_at`, `updated_at`, `sla_pause_reason`, `circuit_id`, `uat_notified_at`, `uat_deadline`, `activation_date`) VALUES
(1, 'FR-260824-001', 'SO-260824-001', 2, 7, 'SVC-260824-001', 0, 'Mbps', NULL, 'Magari Mtaaani', 'Hellen', '0623700877', 'erickmsolla0@gmail.com', 'Mwenge', 'Mwanga tower', '3rd floor', '15', NULL, NULL, '-6.7835, 39.2685', 'Commercial Building', 'SFP', 'LC', 'Single-mode Fiber', 'AC 220V', NULL, 0.00, 0.00, 0.00, 236000.00, 220000.00, NULL, NULL, '2026-08-24 21:11:29', 2, NULL, NULL, NULL, '', '2026-08-24 21:05:41', 10, 200000.00, 220000.00, 200000.00, 'Approve with Revised Price', '', NULL, 0, '2026-08-24 21:07:02', '2026-08-24 21:12:40', 'uploads/orders/1/sof/f886204c2b1bbad4214aed58ce8173cc.pdf', 'SAF.pdf', '2026-08-24 21:14:56', 'uploads/orders/1/sof/a0f3c1a515a36c7d37b4ae263e671f80.pdf', 'SAF.pdf', 2, 'sijapenda hela', NULL, NULL, 'KAM', 7, '2026-08-24 21:09:32', 'Layer 2 ( last mile)', '', '', 'Wingu', '150', '12 Months', '', 2585.0000, 250000.00, 80000.00, 300000.00, 54000.00, 354000.00, 220000.00, 'TZS', 0.00, 0.00, 36000.00, 7, 'John KAM', 2, 'Closed', 3, '2026-08-24 20:58:27', NULL, NULL, NULL, NULL, 'Distance', 'Technically Feasible', NULL, 250000.00, 220000.00, 'Fibre 300m', NULL, NULL, NULL, NULL, NULL, 0.00, '2026-08-24', '2026-08-24', NULL, NULL, '2026-08-24 21:29:42', '2026-08-24 21:30:19', 7, NULL, NULL, 'do it', NULL, NULL, NULL, 0, NULL, 0.02, 1, '2026-08-24 20:54:41', '2026-08-24 21:30:20', NULL, 'CKT-FR-260824-001', '2026-08-24 21:29:42', '2026-08-27 21:29:42', '2026-08-24');

-- --------------------------------------------------------

--
-- Table structure for table `order_documents`
--

CREATE TABLE `order_documents` (
  `id` int UNSIGNED NOT NULL,
  `order_id` int UNSIGNED NOT NULL,
  `doc_type` enum('sof','feasibility_report','diagram','uat_signoff','completion_certificate','invoice','evidence','countersigned_sof','other') NOT NULL DEFAULT 'other',
  `evidence_category` varchar(100) DEFAULT NULL COMMENT 'Links to evidence checklist type for multi-file support',
  `is_partner_visible` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'If 1, partner can see this doc',
  `document_type` varchar(100) DEFAULT NULL COMMENT 'Legacy friendly name',
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int UNSIGNED DEFAULT NULL,
  `uploaded_by` int UNSIGNED NOT NULL,
  `uploaded_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `order_documents`
--

INSERT INTO `order_documents` (`id`, `order_id`, `doc_type`, `evidence_category`, `is_partner_visible`, `document_type`, `file_name`, `file_path`, `file_size`, `uploaded_by`, `uploaded_at`) VALUES
(1, 1, 'other', NULL, 0, 'Supporting Document', 'SOD.pdf', 'uploads/orders/1/a5ca6ee192db8acd1432626624a72585.pdf', 284124, 7, '2026-08-24 20:54:41'),
(2, 1, 'sof', NULL, 0, 'Signed SOF', 'SAF.pdf', 'uploads/orders/1/sof/f886204c2b1bbad4214aed58ce8173cc.pdf', 286809, 7, '2026-08-24 21:12:40'),
(3, 1, 'countersigned_sof', NULL, 0, 'Countersigned SOF', 'SAF.pdf', 'uploads/orders/1/sof/a0f3c1a515a36c7d37b4ae263e671f80.pdf', 286809, 2, '2026-08-24 21:14:56');

-- --------------------------------------------------------

--
-- Table structure for table `order_returns`
--

CREATE TABLE `order_returns` (
  `id` int UNSIGNED NOT NULL,
  `order_id` int UNSIGNED NOT NULL,
  `returned_by` int UNSIGNED NOT NULL,
  `returned_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `from_status` varchar(100) NOT NULL,
  `to_status` varchar(100) NOT NULL,
  `return_reason` varchar(200) NOT NULL,
  `return_remarks` text,
  `routed_to` enum('BSA','KAM','Project Manager','Contractor','Partner') NOT NULL,
  `old_nrc` decimal(12,2) DEFAULT NULL,
  `new_nrc` decimal(12,2) DEFAULT NULL,
  `old_mrc` decimal(12,2) DEFAULT NULL,
  `new_mrc` decimal(12,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `order_returns`
--

INSERT INTO `order_returns` (`id`, `order_id`, `returned_by`, `returned_at`, `from_status`, `to_status`, `return_reason`, `return_remarks`, `routed_to`, `old_nrc`, `new_nrc`, `old_mrc`, `new_mrc`) VALUES
(1, 1, 7, '2026-08-24 21:09:32', 'Pending SOF', 'Await Commercial Approval', 'Partner not satisfied with pricing', 'sijapenda hela', 'KAM', 220000.00, NULL, 220000.00, NULL),
(2, 1, 7, '2026-08-24 21:28:13', 'UAT', 'Installation', 'Quality concern', 'do it', 'Project Manager', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_timeline`
--

CREATE TABLE `order_timeline` (
  `id` int UNSIGNED NOT NULL,
  `order_id` int UNSIGNED NOT NULL,
  `status` varchar(100) NOT NULL,
  `note` text,
  `changed_by` int UNSIGNED DEFAULT NULL,
  `changed_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `order_timeline`
--

INSERT INTO `order_timeline` (`id`, `order_id`, `status`, `note`, `changed_by`, `changed_at`) VALUES
(1, 1, 'Feasibility Review', 'Feasibility request submitted by partner.', 7, '2026-08-24 20:54:41'),
(2, 1, 'Management Approval', 'BSA provided NRC discount (220000 vs standard 250000) — routed to Management Approval.', 3, '2026-08-24 20:58:27'),
(3, 1, 'Pending SOF', 'Management approved with revised pricing: NRC TZS 220,000, MRC TZS 200,000.', 10, '2026-08-24 21:05:41'),
(4, 1, 'Await Commercial Approval', 'Partner not satisfied — returned to Account Manager: sijapenda hela', 7, '2026-08-24 21:09:32'),
(5, 1, 'Pending SOF', 'KAM: Commercial approved at standard pricing. Partner notified for SOF signature.', 2, '2026-08-24 21:11:29'),
(6, 1, 'SOF Review', 'Partner uploaded signed SOF. Awaiting Neilos countersignature.', 7, '2026-08-24 21:12:40'),
(7, 1, 'SOF Review', 'Countersigned SOF uploaded by KAM. Ready to proceed to project.', 2, '2026-08-24 21:14:56'),
(8, 1, 'Installation', 'Countersigned SOF verified — order released to Project Installation.', 2, '2026-08-24 21:15:21'),
(9, 1, 'Installation', 'Contractor assigned (partner #4).', 11, '2026-08-24 21:16:22'),
(10, 1, 'Installation', 'Job assignment accepted.', 13, '2026-08-24 21:17:40'),
(11, 1, 'Installation', 'SLA paused — installation blocker posted.', 13, '2026-08-24 21:22:39'),
(12, 1, 'Installation', 'Contractor blocker: Mvua [Reason: Weather]', 13, '2026-08-24 21:22:39'),
(13, 1, 'Installation', 'SLA resumed — paused for 0.02 hours. Total paused: 0.02 hrs.', 13, '2026-08-24 21:23:51'),
(14, 1, 'Testing', 'Job completion submitted. Awaiting PM/BSA testing review.', 13, '2026-08-24 21:24:19'),
(15, 1, 'Installation', 'Returned to contractor: redo', 11, '2026-08-24 21:25:30'),
(16, 1, 'Testing', 'Job completion submitted. Awaiting PM/BSA testing review.', 13, '2026-08-24 21:26:12'),
(17, 1, 'Testing', 'NOC IP configuration marked as COMPLETE — Speed Test and Ping Test evidence are now required.', 11, '2026-08-24 21:26:57'),
(18, 1, 'UAT', 'Testing approved (NOC IP configured). Partner notified for UAT acceptance (72hr window).', 11, '2026-08-24 21:27:17'),
(19, 1, 'Installation', 'Partner returned the service to Installation during the UAT acceptance window.', 7, '2026-08-24 21:28:13'),
(20, 1, 'Testing', 'Job completion submitted by Project Manager. Awaiting Testing — Internal Review.', 11, '2026-08-24 21:29:15'),
(21, 1, 'UAT', 'Testing approved (NOC IP configured). Partner notified for UAT acceptance (72hr window).', 11, '2026-08-24 21:29:42'),
(22, 1, 'Closed', 'Partner accepted service. Order closed. Closed Date: 2026-08-24. Billing Start Date: 2026-08-24.', 7, '2026-08-24 21:30:19');

-- --------------------------------------------------------

--
-- Table structure for table `partners`
--

CREATE TABLE `partners` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(200) NOT NULL,
  `trading_name` varchar(200) DEFAULT NULL,
  `partner_type` enum('ISP','Reseller','VAR','Enterprise','Government','Other') NOT NULL DEFAULT 'ISP',
  `kyc_type` enum('Partner','Contractor') NOT NULL DEFAULT 'Partner',
  `customer_category` varchar(100) DEFAULT NULL,
  `industry_sector` varchar(100) DEFAULT NULL,
  `nature_of_business` text,
  `registration_number` varchar(100) DEFAULT NULL,
  `tin` varchar(100) DEFAULT NULL,
  `vat_vrn` varchar(100) DEFAULT NULL,
  `address` text,
  `city_region` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'Tanzania',
  `kam_id` int UNSIGNED DEFAULT NULL,
  `assigned_kam_name` varchar(100) DEFAULT NULL,
  `status` enum('Active','Inactive','Suspended') NOT NULL DEFAULT 'Active',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `partners`
--

INSERT INTO `partners` (`id`, `name`, `trading_name`, `partner_type`, `kyc_type`, `customer_category`, `industry_sector`, `nature_of_business`, `registration_number`, `tin`, `vat_vrn`, `address`, `city_region`, `country`, `kam_id`, `assigned_kam_name`, `status`, `created_at`, `updated_at`) VALUES
(1, 'FastNet Communications Ltd', 'FastNet', 'ISP', 'Partner', NULL, NULL, NULL, 'REG-100234', '100-234-567', NULL, 'Plot 12, New Bagamoyo Rd, Kijitonyama', 'Dar es Salaam', 'Tanzania', NULL, NULL, 'Active', '2026-08-24 11:10:42', '2026-08-24 11:10:42'),
(2, 'Afrilink Solutions Ltd', 'Afrilink', 'Reseller', 'Partner', NULL, NULL, NULL, 'REG-100567', '100-567-890', NULL, 'Victoria House, 4th Floor, Victoria', 'Dar es Salaam', 'Tanzania', 2, 'John KAM', 'Active', '2026-08-24 11:10:42', '2026-08-24 20:39:49'),
(4, 'Fiber Works Ltd', 'FiberWorks', 'Other', 'Contractor', NULL, NULL, NULL, 'REG-CONT-001', 'CONT-998877', NULL, '45 Industrial Area, Dar es Salaam', 'Dar es Salaam', 'Tanzania', NULL, NULL, 'Active', '2026-08-24 11:10:42', '2026-08-24 11:10:42');

-- --------------------------------------------------------

--
-- Table structure for table `partner_kyc_applications`
--

CREATE TABLE `partner_kyc_applications` (
  `id` int UNSIGNED NOT NULL,
  `partner_id` int UNSIGNED NOT NULL,
  `kyc_type` enum('Partner','Contractor') NOT NULL DEFAULT 'Partner',
  `registered_name` varchar(200) NOT NULL,
  `trading_name` varchar(200) DEFAULT NULL,
  `registration_number` varchar(100) NOT NULL,
  `tin` varchar(100) NOT NULL,
  `vat_vrn` varchar(100) DEFAULT NULL,
  `customer_category` varchar(100) DEFAULT NULL,
  `industry_sector` varchar(100) DEFAULT NULL,
  `nature_of_business` text,
  `office_address` text,
  `city_region` varchar(100) NOT NULL,
  `country` varchar(100) DEFAULT 'Tanzania',
  `website` varchar(200) DEFAULT NULL,
  `main_contact_name` varchar(200) DEFAULT NULL,
  `main_contact_phone` varchar(50) DEFAULT NULL,
  `main_contact_email` varchar(200) DEFAULT NULL,
  `ops_contact_name` varchar(200) DEFAULT NULL,
  `ops_contact_phone` varchar(50) DEFAULT NULL,
  `ops_contact_email` varchar(200) DEFAULT NULL,
  `tech_supervisor_name` varchar(200) DEFAULT NULL,
  `tech_supervisor_phone` varchar(50) DEFAULT NULL,
  `tech_supervisor_email` varchar(200) DEFAULT NULL,
  `escalation_contact_name` varchar(200) DEFAULT NULL,
  `escalation_contact_phone` varchar(50) DEFAULT NULL,
  `escalation_contact_email` varchar(200) DEFAULT NULL,
  `bank_name` varchar(200) DEFAULT NULL,
  `bank_branch` varchar(200) DEFAULT NULL,
  `bank_account_name` varchar(200) DEFAULT NULL,
  `bank_account_number` varchar(100) DEFAULT NULL,
  `bank_payment_terms` varchar(200) DEFAULT NULL,
  `cap_ftth_install` tinyint(1) DEFAULT '0',
  `cap_sme_install` tinyint(1) DEFAULT '0',
  `cap_enterprise_install` tinyint(1) DEFAULT '0',
  `cap_site_survey` tinyint(1) DEFAULT '0',
  `cap_maintenance` tinyint(1) DEFAULT '0',
  `cap_remote_support` tinyint(1) DEFAULT '0',
  `service_regions` text,
  `business_license` varchar(500) DEFAULT NULL,
  `hse_certificate` varchar(500) DEFAULT NULL,
  `trca_certificate` varchar(500) DEFAULT NULL,
  `custom_fields` json DEFAULT NULL COMMENT 'IT-added custom fields',
  `status` varchar(50) NOT NULL DEFAULT 'Draft',
  `rejection_reason` text,
  `changes_requested_note` text,
  `submitted_at` datetime DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `reviewed_by` int UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `address` text,
  `signatory2_name` varchar(200) DEFAULT NULL,
  `signatory2_email` varchar(200) DEFAULT NULL,
  `signatory2_phone` varchar(50) DEFAULT NULL,
  `billing_contact_name` varchar(200) DEFAULT NULL,
  `billing_contact_email` varchar(200) DEFAULT NULL,
  `billing_contact_phone` varchar(50) DEFAULT NULL,
  `contractor_agreement` varchar(500) DEFAULT NULL,
  `signed_sla_file` varchar(500) DEFAULT NULL,
  `safety_esg_file` varchar(500) DEFAULT NULL,
  `confidentiality_nda_file` varchar(500) DEFAULT NULL,
  `service_regions_file` varchar(500) DEFAULT NULL,
  `sla_accepted` tinyint(1) DEFAULT '0',
  `sla_accepted_at` datetime DEFAULT NULL,
  `safety_esg_accepted` tinyint(1) DEFAULT '0',
  `safety_esg_accepted_at` datetime DEFAULT NULL,
  `confidentiality_accepted` tinyint(1) DEFAULT '0',
  `confidentiality_accepted_at` datetime DEFAULT NULL,
  `accepted_by` int UNSIGNED DEFAULT NULL,
  `auth_signatory_name` varchar(200) DEFAULT NULL,
  `auth_signatory_email` varchar(200) DEFAULT NULL,
  `auth_signatory_mobile` varchar(50) DEFAULT NULL,
  `review_notes` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `partner_kyc_applications`
--

INSERT INTO `partner_kyc_applications` (`id`, `partner_id`, `kyc_type`, `registered_name`, `trading_name`, `registration_number`, `tin`, `vat_vrn`, `customer_category`, `industry_sector`, `nature_of_business`, `office_address`, `city_region`, `country`, `website`, `main_contact_name`, `main_contact_phone`, `main_contact_email`, `ops_contact_name`, `ops_contact_phone`, `ops_contact_email`, `tech_supervisor_name`, `tech_supervisor_phone`, `tech_supervisor_email`, `escalation_contact_name`, `escalation_contact_phone`, `escalation_contact_email`, `bank_name`, `bank_branch`, `bank_account_name`, `bank_account_number`, `bank_payment_terms`, `cap_ftth_install`, `cap_sme_install`, `cap_enterprise_install`, `cap_site_survey`, `cap_maintenance`, `cap_remote_support`, `service_regions`, `business_license`, `hse_certificate`, `trca_certificate`, `custom_fields`, `status`, `rejection_reason`, `changes_requested_note`, `submitted_at`, `reviewed_at`, `reviewed_by`, `created_at`, `updated_at`, `address`, `signatory2_name`, `signatory2_email`, `signatory2_phone`, `billing_contact_name`, `billing_contact_email`, `billing_contact_phone`, `contractor_agreement`, `signed_sla_file`, `safety_esg_file`, `confidentiality_nda_file`, `service_regions_file`, `sla_accepted`, `sla_accepted_at`, `safety_esg_accepted`, `safety_esg_accepted_at`, `confidentiality_accepted`, `confidentiality_accepted_at`, `accepted_by`, `auth_signatory_name`, `auth_signatory_email`, `auth_signatory_mobile`, `review_notes`) VALUES
(2, 4, 'Contractor', 'Fiber Works Ltd', NULL, '5325', '668462854', '4551', NULL, NULL, NULL, 'Dae es salaam', 'Dar es Salaam', 'Tanzania', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Hamis Hamis', '0674928056', 'Kingistarr@gmail.com', NULL, NULL, NULL, 'ASDFGY', 'branch', 'LUCAS MAFIKIRI KANDONGA ', ' 0152599738300', '30 Days', 1, 0, 0, 0, 0, 0, 'DAR ES SALAAM', 'uploads/kyc/2/ff911f0e36b450254777449e71b286e2.docx', 'uploads/kyc/2/9897eb664f8b82897f89a8202b7d94a2.docx', 'uploads/kyc/2/63f7e9dfe0959ba293688382f5953e82.docx', '[]', 'Submitted for Approval', NULL, NULL, '2026-08-24 20:31:18', NULL, NULL, '2026-08-24 15:56:41', '2026-08-24 20:31:18', NULL, 'Hamis Hamis', 'Kingistarr@gmail.com', '0674928056', 'Hamis Hamis', 'Kingistarr@gmail.com', '0674928056', 'uploads/kyc/2/4884b22343d578535efd653292f121c5.docx', 'uploads/kyc/2/ac8f5a25a0750a55320f750579079b1e.docx', 'uploads/kyc/2/a45ca572389d6b0f31badc119fc2425b.docx', 'uploads/kyc/2/3b9125cb0d7a9d83c8e2294b9931e242.docx', 'uploads/kyc/2/7ff6becd88e3669b81a8611821b4890a.pdf', 1, '2026-08-24 20:31:18', 1, '2026-08-24 20:31:18', 1, '2026-08-24 20:31:18', NULL, 'Hamis Hamis', 'Kingistarr@gmail.com', '0674928056', NULL),
(3, 2, 'Partner', 'Afrilink Solutions Ltd', NULL, 'REG-100890', 'TIN-001234567', '1234567890', NULL, NULL, NULL, 'Dar es Salaam', 'Dar es Salaam', 'Tanzania', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'UPENDO JUMA MBATE', '0713887984', 'erickmsolla0@gmail.com', NULL, NULL, NULL, 'CRDB', 'Kinondoni', 'Afrilink', '213456876523456', '30 Days', 1, 1, 0, 1, 1, 0, 'DAR ES SALAAM, DODOMA', 'uploads/kyc/3/3e179b31fd4cc46b0c4be5a830b349d8.pdf', 'uploads/kyc/3/6d71cc7a62217c98b17ac03bd54c6810.pdf', 'uploads/kyc/3/20073b235ccdd3f5d6d436e386dfa07f.pdf', '[]', 'Approved', 'imekosewa', NULL, '2026-08-24 20:44:08', '2026-08-24 20:48:34', 7, '2026-08-24 20:29:49', '2026-08-24 20:48:34', NULL, 'SIKUDHANI ALLAN MBWAMBO', 'erickmsolla0@gmail.com', '0713887984', 'ESTER ABRAHAM MWANGWA', 'erickmsolla0@gmail.com', '0755489475', 'uploads/kyc/3/c9fcba95fed623cffde76dc4264b71b7.pdf', 'uploads/kyc/3/a55ef70cf6b11b784833ee721ff75dba.pdf', 'uploads/kyc/3/386666c4d22607f5f4d03579a64005ad.pdf', 'uploads/kyc/3/bbf66019ff5345b1f901cfbcb031d39c.pdf', 'uploads/kyc/3/924acb742f54598ae351ff5e4034ef19.pdf', 1, '2026-08-24 20:44:08', 1, '2026-08-24 20:44:08', 1, '2026-08-24 20:44:08', NULL, 'Partner Admin (Afrilink)', 'admin@afrilink.co.tz', '0712000007', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `price_change_audit`
--

CREATE TABLE `price_change_audit` (
  `id` int UNSIGNED NOT NULL,
  `order_id` int UNSIGNED NOT NULL,
  `field_name` varchar(100) NOT NULL,
  `old_value` decimal(14,2) DEFAULT NULL,
  `new_value` decimal(14,2) DEFAULT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'TZS',
  `changed_by` int UNSIGNED NOT NULL,
  `stage` varchar(100) NOT NULL,
  `justification` text,
  `changed_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `price_change_audit`
--

INSERT INTO `price_change_audit` (`id`, `order_id`, `field_name`, `old_value`, `new_value`, `currency`, `changed_by`, `stage`, `justification`, `changed_at`) VALUES
(1, 1, 'management_final_nrc', 220000.00, 220000.00, 'TZS', 10, 'Management Approval', '', '2026-08-24 21:05:41'),
(2, 1, 'management_final_mrc', 220000.00, 200000.00, 'TZS', 10, 'Management Approval', '', '2026-08-24 21:05:42');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `code` varchar(50) NOT NULL,
  `category` enum('Internet','Ethernet','IP Transit','Colocation','Cloud','Voice','Managed Services') NOT NULL,
  `description` text,
  `unit` varchar(50) DEFAULT 'Mbps',
  `base_nrc` decimal(12,2) NOT NULL DEFAULT '0.00',
  `base_mrc` decimal(12,2) NOT NULL DEFAULT '0.00',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `name`, `code`, `category`, `description`, `unit`, `base_nrc`, `base_mrc`, `is_active`, `created_at`) VALUES
(1, 'Dedicated Internet Access (DIA)', 'DIA-STD', 'Internet', 'High-speed, symmetrical, uncontended DIA with guaranteed SLA.', 'Mbps', 300.00, 15.00, 1, '2026-08-24 11:10:42'),
(2, 'Layer 2 Ethernet Point-to-Point', 'EPL-STD', 'Ethernet', 'Point-to-point Layer 2 Ethernet connection over optical fiber.', 'Mbps', 250.00, 12.00, 1, '2026-08-24 11:10:42'),
(3, 'Colocation Rack Space (Full Rack)', 'COLO-FULL', 'Colocation', 'Full 42U rack space with dual A+B power feeds and precision cooling.', 'Rack', 500.00, 800.00, 1, '2026-08-24 11:10:42'),
(4, 'IP Transit Service', 'IPT-STD', 'IP Transit', 'BGP IP Transit with multiple Tier-1 upstream paths.', 'Mbps', 200.00, 8.00, 1, '2026-08-24 11:10:42'),
(5, 'FTTH Wholesale Fiber Connection', 'FTTH-WHOLESALE', 'Internet', 'Wholesale last-mile FTTH connection for ISPs and Resellers.', 'Mbps', 60.00, 8.00, 1, '2026-08-24 11:10:42');

-- --------------------------------------------------------

--
-- Table structure for table `sla_config`
--

CREATE TABLE `sla_config` (
  `id` int UNSIGNED NOT NULL,
  `sla_name` varchar(200) NOT NULL,
  `sla_type` varchar(100) NOT NULL COMMENT 'stage, site_type, service_type',
  `target_hours` int NOT NULL COMMENT 'Working hours',
  `description` text,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `sla_config`
--

INSERT INTO `sla_config` (`id`, `sla_name`, `sla_type`, `target_hours`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Feasibility Acknowledgement', 'stage', 8, 'BSA must acknowledge within 1 working day', 1, '2026-08-24 11:10:42', '2026-08-24 11:10:42'),
(2, 'In Coverage Installation', 'site_type', 16, 'Standard serviceable location - 2 working days', 1, '2026-08-24 11:10:42', '2026-08-24 11:10:42'),
(3, 'Apartment without MDU', 'site_type', 24, 'MDU readiness required - 3 working days', 1, '2026-08-24 11:10:42', '2026-08-24 11:10:42'),
(4, 'Extension above 200m', 'site_type', 40, 'Extension work required - 5 working days', 1, '2026-08-24 11:10:42', '2026-08-24 11:10:42'),
(5, 'Pole + Extension', 'site_type', 56, 'Pole and extension required - 7 working days', 1, '2026-08-24 11:10:42', '2026-08-24 11:10:42'),
(6, 'Testing Review after Completion', 'stage', 8, 'PM/BSA reviews same day or next working day', 1, '2026-08-24 11:10:42', '2026-08-24 11:10:42'),
(7, 'Partner UAT Response', 'stage', 48, '48 hours for partner UAT acceptance', 1, '2026-08-24 11:10:42', '2026-08-24 11:10:42');

-- --------------------------------------------------------

--
-- Table structure for table `system_events`
--

CREATE TABLE `system_events` (
  `id` int UNSIGNED NOT NULL,
  `event_type` varchar(100) NOT NULL,
  `order_id` int UNSIGNED DEFAULT NULL,
  `assignment_id` int UNSIGNED DEFAULT NULL,
  `user_id` int UNSIGNED DEFAULT NULL,
  `payload` longtext,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `system_events`
--

INSERT INTO `system_events` (`id`, `event_type`, `order_id`, `assignment_id`, `user_id`, `payload`, `created_at`) VALUES
(1, 'kyc_status_updated', NULL, NULL, 1, '{\"event_type\":\"kyc_status_updated\",\"order_id\":null,\"timestamp\":\"2026-08-24 20:29:49\",\"data\":{\"kyc_id\":3,\"status\":\"Submitted for Approval\"}}', '2026-08-24 20:29:49'),
(2, 'kyc_status_updated', NULL, NULL, 1, '{\"event_type\":\"kyc_status_updated\",\"order_id\":null,\"timestamp\":\"2026-08-24 20:31:18\",\"data\":{\"kyc_id\":2,\"status\":\"Submitted for Approval\"}}', '2026-08-24 20:31:18'),
(3, 'kyc_status_updated', NULL, NULL, 7, '{\"event_type\":\"kyc_status_updated\",\"order_id\":null,\"timestamp\":\"2026-08-24 20:42:44\",\"data\":{\"kyc_id\":3,\"status\":\"Rejected\"}}', '2026-08-24 20:42:44'),
(4, 'kyc_status_updated', NULL, NULL, 1, '{\"event_type\":\"kyc_status_updated\",\"order_id\":null,\"timestamp\":\"2026-08-24 20:44:08\",\"data\":{\"kyc_id\":3,\"status\":\"Submitted for Approval\"}}', '2026-08-24 20:44:08'),
(5, 'kyc_status_updated', NULL, NULL, 7, '{\"event_type\":\"kyc_status_updated\",\"order_id\":null,\"timestamp\":\"2026-08-24 20:48:34\",\"data\":{\"kyc_id\":3,\"status\":\"Approved\"}}', '2026-08-24 20:48:34'),
(6, 'order_status_sync', 1, NULL, 3, '{\"event_type\":\"order_status_sync\",\"order_id\":1,\"timestamp\":\"2026-08-24 20:58:27\",\"data\":{\"trigger_action\":\"bsa_feasible\",\"old_status\":\"Management Approval\",\"new_status\":\"Management Approval\",\"status_changed\":false,\"total_items\":8,\"uploaded_items\":0,\"total_mandatory\":6,\"completed_mandatory\":0,\"all_mandatory_complete\":false},\"order_number\":\"FR-260824-001\",\"status\":\"Management Approval\",\"service_type\":\"Layer 2 ( last mile)\",\"customer_name\":\"Magari Mtaaani\",\"partner_name\":\"Afrilink Solutions Ltd\",\"total_nrc\":354000,\"total_mrc\":259600,\"total_revenue\":613600,\"billing_start_date\":null,\"is_billing_active\":false}', '2026-08-24 20:58:27'),
(7, 'order_status_sync', 1, NULL, 2, '{\"event_type\":\"order_status_sync\",\"order_id\":1,\"timestamp\":\"2026-08-24 21:15:21\",\"data\":{\"trigger_action\":\"proceed_to_project\",\"old_status\":\"Installation\",\"new_status\":\"Installation\",\"status_changed\":false,\"total_items\":8,\"uploaded_items\":0,\"total_mandatory\":6,\"completed_mandatory\":0,\"all_mandatory_complete\":false},\"order_number\":\"FR-260824-001\",\"status\":\"Installation\",\"service_type\":\"Layer 2 ( last mile)\",\"customer_name\":\"Magari Mtaaani\",\"partner_name\":\"Afrilink Solutions Ltd\",\"total_nrc\":354000,\"total_mrc\":236000,\"total_revenue\":590000,\"billing_start_date\":null,\"is_billing_active\":false}', '2026-08-24 21:15:21'),
(8, 'order_status_sync', 1, NULL, 13, '{\"event_type\":\"order_status_sync\",\"order_id\":1,\"timestamp\":\"2026-08-24 21:18:20\",\"data\":{\"trigger_action\":\"upload_evidence\",\"old_status\":\"Installation\",\"new_status\":\"Installation\",\"status_changed\":false,\"total_items\":8,\"uploaded_items\":1,\"total_mandatory\":6,\"completed_mandatory\":1,\"all_mandatory_complete\":false},\"order_number\":\"FR-260824-001\",\"status\":\"Installation\",\"service_type\":\"Layer 2 ( last mile)\",\"customer_name\":\"Magari Mtaaani\",\"partner_name\":\"Afrilink Solutions Ltd\",\"total_nrc\":354000,\"total_mrc\":236000,\"total_revenue\":590000,\"billing_start_date\":null,\"is_billing_active\":false}', '2026-08-24 21:18:20'),
(9, 'order_status_sync', 1, NULL, 13, '{\"event_type\":\"order_status_sync\",\"order_id\":1,\"timestamp\":\"2026-08-24 21:18:53\",\"data\":{\"trigger_action\":\"upload_evidence\",\"old_status\":\"Installation\",\"new_status\":\"Installation\",\"status_changed\":false,\"total_items\":8,\"uploaded_items\":2,\"total_mandatory\":6,\"completed_mandatory\":2,\"all_mandatory_complete\":false},\"order_number\":\"FR-260824-001\",\"status\":\"Installation\",\"service_type\":\"Layer 2 ( last mile)\",\"customer_name\":\"Magari Mtaaani\",\"partner_name\":\"Afrilink Solutions Ltd\",\"total_nrc\":354000,\"total_mrc\":236000,\"total_revenue\":590000,\"billing_start_date\":null,\"is_billing_active\":false}', '2026-08-24 21:18:53'),
(10, 'order_status_sync', 1, NULL, 13, '{\"event_type\":\"order_status_sync\",\"order_id\":1,\"timestamp\":\"2026-08-24 21:19:18\",\"data\":{\"trigger_action\":\"upload_evidence\",\"old_status\":\"Installation\",\"new_status\":\"Installation\",\"status_changed\":false,\"total_items\":8,\"uploaded_items\":3,\"total_mandatory\":6,\"completed_mandatory\":3,\"all_mandatory_complete\":false},\"order_number\":\"FR-260824-001\",\"status\":\"Installation\",\"service_type\":\"Layer 2 ( last mile)\",\"customer_name\":\"Magari Mtaaani\",\"partner_name\":\"Afrilink Solutions Ltd\",\"total_nrc\":354000,\"total_mrc\":236000,\"total_revenue\":590000,\"billing_start_date\":null,\"is_billing_active\":false}', '2026-08-24 21:19:18'),
(11, 'order_status_sync', 1, NULL, 13, '{\"event_type\":\"order_status_sync\",\"order_id\":1,\"timestamp\":\"2026-08-24 21:19:50\",\"data\":{\"trigger_action\":\"upload_evidence\",\"old_status\":\"Installation\",\"new_status\":\"Installation\",\"status_changed\":false,\"total_items\":8,\"uploaded_items\":4,\"total_mandatory\":6,\"completed_mandatory\":3,\"all_mandatory_complete\":false},\"order_number\":\"FR-260824-001\",\"status\":\"Installation\",\"service_type\":\"Layer 2 ( last mile)\",\"customer_name\":\"Magari Mtaaani\",\"partner_name\":\"Afrilink Solutions Ltd\",\"total_nrc\":354000,\"total_mrc\":236000,\"total_revenue\":590000,\"billing_start_date\":null,\"is_billing_active\":false}', '2026-08-24 21:19:50'),
(12, 'order_status_sync', 1, NULL, 13, '{\"event_type\":\"order_status_sync\",\"order_id\":1,\"timestamp\":\"2026-08-24 21:20:20\",\"data\":{\"trigger_action\":\"upload_evidence\",\"old_status\":\"Installation\",\"new_status\":\"Installation\",\"status_changed\":false,\"total_items\":8,\"uploaded_items\":5,\"total_mandatory\":6,\"completed_mandatory\":3,\"all_mandatory_complete\":false},\"order_number\":\"FR-260824-001\",\"status\":\"Installation\",\"service_type\":\"Layer 2 ( last mile)\",\"customer_name\":\"Magari Mtaaani\",\"partner_name\":\"Afrilink Solutions Ltd\",\"total_nrc\":354000,\"total_mrc\":236000,\"total_revenue\":590000,\"billing_start_date\":null,\"is_billing_active\":false}', '2026-08-24 21:20:20'),
(13, 'order_status_sync', 1, NULL, 13, '{\"event_type\":\"order_status_sync\",\"order_id\":1,\"timestamp\":\"2026-08-24 21:21:07\",\"data\":{\"trigger_action\":\"upload_evidence\",\"old_status\":\"Installation\",\"new_status\":\"Installation\",\"status_changed\":false,\"total_items\":8,\"uploaded_items\":6,\"total_mandatory\":6,\"completed_mandatory\":4,\"all_mandatory_complete\":false},\"order_number\":\"FR-260824-001\",\"status\":\"Installation\",\"service_type\":\"Layer 2 ( last mile)\",\"customer_name\":\"Magari Mtaaani\",\"partner_name\":\"Afrilink Solutions Ltd\",\"total_nrc\":354000,\"total_mrc\":236000,\"total_revenue\":590000,\"billing_start_date\":null,\"is_billing_active\":false}', '2026-08-24 21:21:07'),
(14, 'order_status_sync', 1, NULL, 13, '{\"event_type\":\"order_status_sync\",\"order_id\":1,\"timestamp\":\"2026-08-24 21:21:43\",\"data\":{\"trigger_action\":\"upload_evidence\",\"old_status\":\"Installation\",\"new_status\":\"Installation\",\"status_changed\":false,\"total_items\":8,\"uploaded_items\":7,\"total_mandatory\":6,\"completed_mandatory\":5,\"all_mandatory_complete\":false},\"order_number\":\"FR-260824-001\",\"status\":\"Installation\",\"service_type\":\"Layer 2 ( last mile)\",\"customer_name\":\"Magari Mtaaani\",\"partner_name\":\"Afrilink Solutions Ltd\",\"total_nrc\":354000,\"total_mrc\":236000,\"total_revenue\":590000,\"billing_start_date\":null,\"is_billing_active\":false}', '2026-08-24 21:21:43'),
(15, 'order_status_sync', 1, NULL, 13, '{\"event_type\":\"order_status_sync\",\"order_id\":1,\"timestamp\":\"2026-08-24 21:22:06\",\"data\":{\"trigger_action\":\"upload_evidence\",\"old_status\":\"Installation\",\"new_status\":\"Installation\",\"status_changed\":false,\"total_items\":8,\"uploaded_items\":8,\"total_mandatory\":6,\"completed_mandatory\":6,\"all_mandatory_complete\":true},\"order_number\":\"FR-260824-001\",\"status\":\"Installation\",\"service_type\":\"Layer 2 ( last mile)\",\"customer_name\":\"Magari Mtaaani\",\"partner_name\":\"Afrilink Solutions Ltd\",\"total_nrc\":354000,\"total_mrc\":236000,\"total_revenue\":590000,\"billing_start_date\":null,\"is_billing_active\":false}', '2026-08-24 21:22:06'),
(16, 'order_status_sync', 1, NULL, 13, '{\"event_type\":\"order_status_sync\",\"order_id\":1,\"timestamp\":\"2026-08-24 21:24:19\",\"data\":{\"trigger_action\":\"submit_completion\",\"old_status\":\"Testing\",\"new_status\":\"Testing\",\"status_changed\":false,\"total_items\":8,\"uploaded_items\":8,\"total_mandatory\":6,\"completed_mandatory\":6,\"all_mandatory_complete\":true},\"order_number\":\"FR-260824-001\",\"status\":\"Testing\",\"service_type\":\"Layer 2 ( last mile)\",\"customer_name\":\"Magari Mtaaani\",\"partner_name\":\"Afrilink Solutions Ltd\",\"total_nrc\":354000,\"total_mrc\":236000,\"total_revenue\":590000,\"billing_start_date\":null,\"is_billing_active\":false}', '2026-08-24 21:24:19'),
(17, 'order_status_sync', 1, NULL, 13, '{\"event_type\":\"order_status_sync\",\"order_id\":1,\"timestamp\":\"2026-08-24 21:26:12\",\"data\":{\"trigger_action\":\"submit_completion\",\"old_status\":\"Testing\",\"new_status\":\"Testing\",\"status_changed\":false,\"total_items\":8,\"uploaded_items\":8,\"total_mandatory\":6,\"completed_mandatory\":6,\"all_mandatory_complete\":true},\"order_number\":\"FR-260824-001\",\"status\":\"Testing\",\"service_type\":\"Layer 2 ( last mile)\",\"customer_name\":\"Magari Mtaaani\",\"partner_name\":\"Afrilink Solutions Ltd\",\"total_nrc\":354000,\"total_mrc\":236000,\"total_revenue\":590000,\"billing_start_date\":null,\"is_billing_active\":false}', '2026-08-24 21:26:12'),
(18, 'order_status_sync', 1, NULL, 11, '{\"event_type\":\"order_status_sync\",\"order_id\":1,\"timestamp\":\"2026-08-24 21:29:15\",\"data\":{\"trigger_action\":\"submit_completion\",\"old_status\":\"Testing\",\"new_status\":\"Testing\",\"status_changed\":false,\"total_items\":8,\"uploaded_items\":8,\"total_mandatory\":8,\"completed_mandatory\":8,\"all_mandatory_complete\":true},\"order_number\":\"FR-260824-001\",\"status\":\"Testing\",\"service_type\":\"Layer 2 ( last mile)\",\"customer_name\":\"Magari Mtaaani\",\"partner_name\":\"Afrilink Solutions Ltd\",\"total_nrc\":354000,\"total_mrc\":236000,\"total_revenue\":590000,\"billing_start_date\":null,\"is_billing_active\":false}', '2026-08-24 21:29:15');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int UNSIGNED NOT NULL,
  `partner_id` int UNSIGNED DEFAULT NULL COMMENT 'NULL = admin/internal user',
  `full_name` varchar(200) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(200) NOT NULL,
  `password` varchar(255) NOT NULL,
  `mobile` varchar(30) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'Partner',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `permissions` text,
  `created_by` int UNSIGNED DEFAULT NULL,
  `is_first_login` tinyint(1) NOT NULL DEFAULT '1',
  `last_login` datetime DEFAULT NULL,
  `otp_secret` varchar(32) DEFAULT NULL,
  `otp_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `otp_verified` tinyint(1) NOT NULL DEFAULT '0',
  `profile_picture` varchar(500) DEFAULT NULL,
  `status` enum('Active','Inactive','Suspended') NOT NULL DEFAULT 'Active',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `partner_id`, `full_name`, `username`, `email`, `password`, `mobile`, `role`, `is_active`, `permissions`, `created_by`, `is_first_login`, `last_login`, `otp_secret`, `otp_enabled`, `otp_verified`, `profile_picture`, `status`, `created_at`, `updated_at`) VALUES
(1, NULL, 'System Administrator', 'admin', 'admin@neilosnetwork.co.tz', '$2y$10$AFWPBl7pldA0p2fypm36LeD/9R0Cxdkz/G/v9jiLyw.bNKUiM/Z9u', '0712000001', 'System Admin', 1, NULL, NULL, 0, '2026-08-26 12:03:11', NULL, 0, 1, NULL, 'Active', '2026-08-24 11:10:42', '2026-08-26 12:03:11'),
(2, NULL, 'John KAM', 'kam_john', 'john.kam@neilosnetwork.co.tz', '$2y$10$AFWPBl7pldA0p2fypm36LeD/9R0Cxdkz/G/v9jiLyw.bNKUiM/Z9u', '0712000002', 'KAM', 1, NULL, NULL, 0, '2026-08-24 20:37:35', NULL, 0, 1, NULL, 'Active', '2026-08-24 11:10:42', '2026-08-24 20:37:35'),
(3, NULL, 'Sarah BSA', 'bsa_sarah', 'comfortmnyinga@gmail.com', '$2y$10$AFWPBl7pldA0p2fypm36LeD/9R0Cxdkz/G/v9jiLyw.bNKUiM/Z9u', '0712000003', 'BSA', 1, NULL, NULL, 0, '2026-08-25 16:56:42', NULL, 0, 1, NULL, 'Active', '2026-08-24 11:10:42', '2026-08-25 16:56:42'),
(4, NULL, 'NOC Support Lead', 'noc_support', 'noc.support@neilosnetwork.co.tz', '$2y$10$AFWPBl7pldA0p2fypm36LeD/9R0Cxdkz/G/v9jiLyw.bNKUiM/Z9u', '0712000004', 'NOC Support', 1, NULL, NULL, 0, NULL, NULL, 0, 1, NULL, 'Active', '2026-08-24 11:10:42', '2026-08-24 11:10:42'),
(5, NULL, 'Commercial Director', 'commercial_dir', 'commercial@neilosnetwork.co.tz', '$2y$10$AFWPBl7pldA0p2fypm36LeD/9R0Cxdkz/G/v9jiLyw.bNKUiM/Z9u', '0712000005', 'Commercial', 1, NULL, NULL, 0, NULL, NULL, 0, 1, NULL, 'Active', '2026-08-24 11:10:42', '2026-08-24 11:10:42'),
(6, 1, 'Partner Admin (FastNet)', 'partner_fastnet', 'admin@fastnet.co.tz', '$2y$10$AFWPBl7pldA0p2fypm36LeD/9R0Cxdkz/G/v9jiLyw.bNKUiM/Z9u', '0712000006', 'Partner', 1, NULL, NULL, 0, NULL, NULL, 0, 1, NULL, 'Active', '2026-08-24 11:10:42', '2026-08-24 11:26:09'),
(7, 2, 'Partner Admin (Afrilink)', 'partner_afrilink', 'admin@afrilink.co.tz', '$2y$10$AFWPBl7pldA0p2fypm36LeD/9R0Cxdkz/G/v9jiLyw.bNKUiM/Z9u', '0712000007', 'Partner', 1, NULL, NULL, 0, '2026-08-24 20:36:40', NULL, 0, 1, NULL, 'Active', '2026-08-24 11:10:42', '2026-08-24 20:36:40'),
(10, NULL, 'Operations Manager', 'manager', 'manager@neilosnetwork.co.tz', '$2y$10$AFWPBl7pldA0p2fypm36LeD/9R0Cxdkz/G/v9jiLyw.bNKUiM/Z9u', '0712000010', 'Management', 1, NULL, NULL, 0, '2026-08-24 21:02:56', NULL, 0, 1, NULL, 'Active', '2026-08-24 11:10:42', '2026-08-24 21:02:56'),
(11, NULL, 'Project Manager', 'pm', 'pm@neilosnetwork.co.tz', '$2y$10$AFWPBl7pldA0p2fypm36LeD/9R0Cxdkz/G/v9jiLyw.bNKUiM/Z9u', '0712000011', 'Project Manager', 1, NULL, NULL, 0, '2026-08-24 20:37:54', NULL, 0, 1, NULL, 'Active', '2026-08-24 11:10:42', '2026-08-24 20:37:54'),
(12, NULL, 'Finance / Billing', 'billing', 'billing@neilosnetwork.co.tz', '$2y$10$AFWPBl7pldA0p2fypm36LeD/9R0Cxdkz/G/v9jiLyw.bNKUiM/Z9u', '0712000012', 'Billing', 1, NULL, NULL, 0, NULL, NULL, 0, 1, NULL, 'Active', '2026-08-24 11:10:42', '2026-08-24 11:10:42'),
(13, 4, 'Contractor Admin', 'contractor1', 'admin@fiberworks.co.tz', '$2y$10$AFWPBl7pldA0p2fypm36LeD/9R0Cxdkz/G/v9jiLyw.bNKUiM/Z9u', '0712000013', 'Contractor', 1, NULL, NULL, 0, '2026-08-24 20:38:10', NULL, 0, 1, NULL, 'Active', '2026-08-24 11:10:42', '2026-08-24 20:38:10');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `active_services`
--
ALTER TABLE `active_services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `service_id` (`service_id`),
  ADD KEY `idx_order` (`order_id`),
  ADD KEY `idx_partner` (`partner_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_module` (`module`),
  ADD KEY `idx_record` (`record_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `contractor_assignments`
--
ALTER TABLE `contractor_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contractor_user_id` (`contractor_user_id`),
  ADD KEY `assigned_by` (`assigned_by`),
  ADD KEY `accepted_by` (`accepted_by`),
  ADD KEY `idx_order` (`order_id`),
  ADD KEY `idx_contractor` (`contractor_partner_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `contractor_evidence`
--
ALTER TABLE `contractor_evidence`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploaded_by` (`uploaded_by`),
  ADD KEY `idx_assignment` (`assignment_id`),
  ADD KEY `idx_order` (`order_id`);

--
-- Indexes for table `contractor_progress_updates`
--
ALTER TABLE `contractor_progress_updates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_assignment` (`assignment_id`),
  ADD KEY `idx_order` (`order_id`);

--
-- Indexes for table `evidence_checklist_config`
--
ALTER TABLE `evidence_checklist_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_service_evidence` (`service_type`,`evidence_type`);

--
-- Indexes for table `ftth_bulk_uploads`
--
ALTER TABLE `ftth_bulk_uploads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploaded_by` (`uploaded_by`),
  ADD KEY `idx_uploaded_at` (`uploaded_at`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `partner_id` (`partner_id`),
  ADD KEY `idx_invoice_number` (`invoice_number`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `kyc_documents`
--
ALTER TABLE `kyc_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_id`);

--
-- Indexes for table `kyc_history`
--
ALTER TABLE `kyc_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_kyc` (`kyc_id`);

--
-- Indexes for table `neilos_company_info`
--
ALTER TABLE `neilos_company_info`
  ADD PRIMARY KEY (`id`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_read` (`user_id`,`is_read`);

--
-- Indexes for table `notification_queue`
--
ALTER TABLE `notification_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `service_id` (`service_id`),
  ADD KEY `bsa_reviewed_by` (`bsa_reviewed_by`),
  ADD KEY `bsa_approved_by` (`bsa_approved_by`),
  ADD KEY `kam_reviewed_by` (`kam_reviewed_by`),
  ADD KEY `kam_approved_by` (`kam_approved_by`),
  ADD KEY `management_approved_by` (`management_approved_by`),
  ADD KEY `countersigned_sof_by` (`countersigned_sof_by`),
  ADD KEY `returned_by` (`returned_by`),
  ADD KEY `uat_accepted_by` (`uat_accepted_by`),
  ADD KEY `billing_start_adjusted_by` (`billing_start_adjusted_by`),
  ADD KEY `idx_order_number` (`order_number`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_partner` (`partner_id`);

--
-- Indexes for table `order_documents`
--
ALTER TABLE `order_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploaded_by` (`uploaded_by`),
  ADD KEY `idx_order_doc` (`order_id`,`doc_type`);

--
-- Indexes for table `order_returns`
--
ALTER TABLE `order_returns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `returned_by` (`returned_by`),
  ADD KEY `idx_order` (`order_id`);

--
-- Indexes for table `order_timeline`
--
ALTER TABLE `order_timeline`
  ADD PRIMARY KEY (`id`),
  ADD KEY `changed_by` (`changed_by`),
  ADD KEY `idx_order_status` (`order_id`,`status`),
  ADD KEY `idx_changed_at` (`changed_at`);

--
-- Indexes for table `partners`
--
ALTER TABLE `partners`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_kyc_type` (`kyc_type`),
  ADD KEY `idx_kam` (`kam_id`);

--
-- Indexes for table `partner_kyc_applications`
--
ALTER TABLE `partner_kyc_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `partner_id` (`partner_id`),
  ADD KEY `reviewed_by` (`reviewed_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_kyc_type` (`kyc_type`);

--
-- Indexes for table `price_change_audit`
--
ALTER TABLE `price_change_audit`
  ADD PRIMARY KEY (`id`),
  ADD KEY `changed_by` (`changed_by`),
  ADD KEY `idx_order` (`order_id`),
  ADD KEY `idx_changed_at` (`changed_at`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `sla_config`
--
ALTER TABLE `sla_config`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `system_events`
--
ALTER TABLE `system_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_order` (`order_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `partner_id` (`partner_id`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `active_services`
--
ALTER TABLE `active_services`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `contractor_assignments`
--
ALTER TABLE `contractor_assignments`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `contractor_evidence`
--
ALTER TABLE `contractor_evidence`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `contractor_progress_updates`
--
ALTER TABLE `contractor_progress_updates`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `evidence_checklist_config`
--
ALTER TABLE `evidence_checklist_config`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=827453;

--
-- AUTO_INCREMENT for table `ftth_bulk_uploads`
--
ALTER TABLE `ftth_bulk_uploads`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kyc_documents`
--
ALTER TABLE `kyc_documents`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kyc_history`
--
ALTER TABLE `kyc_history`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `neilos_company_info`
--
ALTER TABLE `neilos_company_info`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_queue`
--
ALTER TABLE `notification_queue`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `order_documents`
--
ALTER TABLE `order_documents`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `order_returns`
--
ALTER TABLE `order_returns`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `order_timeline`
--
ALTER TABLE `order_timeline`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `partners`
--
ALTER TABLE `partners`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `partner_kyc_applications`
--
ALTER TABLE `partner_kyc_applications`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `price_change_audit`
--
ALTER TABLE `price_change_audit`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `sla_config`
--
ALTER TABLE `sla_config`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `system_events`
--
ALTER TABLE `system_events`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `contractor_assignments`
--
ALTER TABLE `contractor_assignments`
  ADD CONSTRAINT `contractor_assignments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contractor_assignments_ibfk_2` FOREIGN KEY (`contractor_partner_id`) REFERENCES `partners` (`id`),
  ADD CONSTRAINT `contractor_assignments_ibfk_3` FOREIGN KEY (`contractor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `contractor_assignments_ibfk_4` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `contractor_assignments_ibfk_5` FOREIGN KEY (`accepted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `contractor_evidence`
--
ALTER TABLE `contractor_evidence`
  ADD CONSTRAINT `contractor_evidence_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `contractor_assignments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contractor_evidence_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contractor_evidence_ibfk_3` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `contractor_progress_updates`
--
ALTER TABLE `contractor_progress_updates`
  ADD CONSTRAINT `contractor_progress_updates_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `contractor_assignments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contractor_progress_updates_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contractor_progress_updates_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `ftth_bulk_uploads`
--
ALTER TABLE `ftth_bulk_uploads`
  ADD CONSTRAINT `ftth_bulk_uploads_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`);

--
-- Constraints for table `kyc_documents`
--
ALTER TABLE `kyc_documents`
  ADD CONSTRAINT `kyc_documents_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `partner_kyc_applications` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `neilos_company_info`
--
ALTER TABLE `neilos_company_info`
  ADD CONSTRAINT `neilos_company_info_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`),
  ADD CONSTRAINT `orders_ibfk_10` FOREIGN KEY (`returned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_11` FOREIGN KEY (`uat_accepted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_12` FOREIGN KEY (`billing_start_adjusted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `orders_ibfk_4` FOREIGN KEY (`bsa_reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_5` FOREIGN KEY (`bsa_approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_6` FOREIGN KEY (`kam_reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_7` FOREIGN KEY (`kam_approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_8` FOREIGN KEY (`management_approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_9` FOREIGN KEY (`countersigned_sof_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_documents`
--
ALTER TABLE `order_documents`
  ADD CONSTRAINT `order_documents_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_documents_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_returns`
--
ALTER TABLE `order_returns`
  ADD CONSTRAINT `order_returns_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_returns_ibfk_2` FOREIGN KEY (`returned_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_timeline`
--
ALTER TABLE `order_timeline`
  ADD CONSTRAINT `order_timeline_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_timeline_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `partner_kyc_applications`
--
ALTER TABLE `partner_kyc_applications`
  ADD CONSTRAINT `partner_kyc_applications_ibfk_1` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `partner_kyc_applications_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `price_change_audit`
--
ALTER TABLE `price_change_audit`
  ADD CONSTRAINT `price_change_audit_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `price_change_audit_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
