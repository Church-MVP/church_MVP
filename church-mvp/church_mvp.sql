-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 18, 2026 at 03:08 PM
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
-- Database: `church_mvp`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'viewer',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`, `email`, `full_name`, `role`, `is_active`, `last_login`, `created_by`, `created_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@church.com', 'System Administrator', 'super_admin', 1, '2026-01-18 13:44:02', NULL, '2026-01-11 06:20:14'),
(4, 'johndoe', '$2y$10$AvdCrV7lGFaYaOnEfbFbr.sFaCWEcD32V4Vh6MSbOjzqPEg/l8l3m', 'jdoe@example.com', 'John Doe', 'admin', 1, '2026-01-13 03:52:29', 1, '2026-01-13 03:51:04');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `announcement_date` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `content`, `announcement_date`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'New Service Time', 'Starting this Sunday, our second service will begin at 11:30am instead of 11:00am.', '2026-01-11', 0, '2026-01-11 06:20:14', '2026-01-15 16:46:00'),
(2, 'Volunteer Needed', 'We are looking for volunteers for our children\'s ministry. Please contact the office if interested.', '2026-01-08', 1, '2026-01-11 06:20:14', '2026-01-11 06:20:14'),
(3, 'Church Picnic', 'Save the date! Our annual church picnic will be on January 25th at Riverside Park.', '2026-01-05', 1, '2026-01-11 06:20:14', '2026-01-11 06:20:14');

-- --------------------------------------------------------

--
-- Table structure for table `donations`
--

CREATE TABLE `donations` (
  `id` int(11) NOT NULL,
  `campaign_id` int(11) DEFAULT NULL,
  `donor_name` varchar(100) NOT NULL,
  `donor_email` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `donation_type` varchar(50) DEFAULT 'General',
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `donation_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_anonymous` tinyint(1) DEFAULT 0,
  `is_recurring` tinyint(1) DEFAULT 0,
  `recurring_frequency` varchar(50) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'completed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `donations`
--

INSERT INTO `donations` (`id`, `campaign_id`, `donor_name`, `donor_email`, `amount`, `donation_type`, `payment_method`, `transaction_id`, `message`, `donation_date`, `is_anonymous`, `is_recurring`, `recurring_frequency`, `status`) VALUES
(1, NULL, 'John Doe', 'john.doe@email.com', 100.00, 'Tithe', 'Credit Card', NULL, NULL, '2026-01-11 06:20:14', 0, 0, NULL, 'completed'),
(2, NULL, 'Jane Smith', 'jane.smith@email.com', 50.00, 'Building Fund', 'PayPal', NULL, NULL, '2026-01-11 06:20:14', 0, 0, NULL, 'completed'),
(3, NULL, 'Anonymous', 'anonymous@church.com', 250.00, 'Missions', 'Cash', NULL, NULL, '2026-01-11 06:20:14', 0, 0, NULL, 'completed');

-- --------------------------------------------------------

--
-- Table structure for table `donation_campaigns`
--

CREATE TABLE `donation_campaigns` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `short_description` varchar(500) DEFAULT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `goal_amount` decimal(10,2) DEFAULT 0.00,
  `current_amount` decimal(10,2) DEFAULT 0.00,
  `donation_type` varchar(100) DEFAULT 'General',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `is_featured` tinyint(1) DEFAULT 0,
  `show_progress_bar` tinyint(1) DEFAULT 1,
  `show_donor_count` tinyint(1) DEFAULT 1,
  `donor_count` int(11) DEFAULT 0,
  `minimum_amount` decimal(10,2) DEFAULT 1.00,
  `suggested_amounts` varchar(255) DEFAULT '25,50,100,250,500',
  `payment_methods` varchar(255) DEFAULT 'pending',
  `allow_anonymous` tinyint(1) DEFAULT 1,
  `allow_recurring` tinyint(1) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `donation_campaigns`
--

INSERT INTO `donation_campaigns` (`id`, `title`, `slug`, `description`, `short_description`, `featured_image`, `goal_amount`, `current_amount`, `donation_type`, `start_date`, `end_date`, `is_active`, `is_featured`, `show_progress_bar`, `show_donor_count`, `donor_count`, `minimum_amount`, `suggested_amounts`, `payment_methods`, `allow_anonymous`, `allow_recurring`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Building Fund 2026', 'building-fund-2026', '<p>Help us expand our church facilities to accommodate our growing congregation. Your generous donation will go towards:</p><ul><li>New sanctuary expansion (500 additional seats)</li><li>Modern audio/visual equipment</li><li>Improved accessibility features</li><li>New children\'s ministry wing</li></ul><p>Together, we can create a welcoming space for all who seek to worship with us.</p>', 'Help us expand our church facilities to serve our growing community and create a welcoming space for worship.', NULL, 50000.00, 12500.00, 'Building Fund', NULL, NULL, 1, 1, 1, 1, 45, 1.00, '50,100,250,500,1000', 'pending', 1, 0, NULL, '2026-01-17 12:28:22', NULL),
(2, 'Youth Summer Camp 2026', 'youth-summer-camp-2026', '<p>Send our youth to an unforgettable summer camp experience! Your donation helps cover:</p><ul><li>Camp registration fees</li><li>Transportation costs</li><li>Meals and accommodations</li><li>Activity materials</li></ul><p>Many families cannot afford to send their children to camp. Your gift makes it possible for every child to experience God\'s love in nature.</p>', 'Help send our youth to summer camp for an unforgettable experience of faith, fun, and fellowship.', NULL, 8000.00, 3200.00, 'Youth Ministry', NULL, NULL, 1, 1, 1, 1, 28, 1.00, '25,50,100,200', 'pending', 1, 0, NULL, '2026-01-17 12:28:22', NULL),
(3, 'Community Food Bank', 'community-food-bank', '<p>Our food bank serves over 200 families each month. Your donation helps us:</p><ul><li>Purchase fresh produce and proteins</li><li>Stock essential pantry items</li><li>Provide holiday meal packages</li><li>Support emergency food assistance</li></ul><p>No one in our community should go hungry. Together, we can make a difference.</p>', 'Support our food bank that serves over 200 families monthly with essential groceries and fresh produce.', NULL, 15000.00, 9750.00, 'Community Outreach', NULL, NULL, 1, 0, 1, 1, 89, 1.00, '25,50,100,250', 'pending', 1, 0, NULL, '2026-01-17 12:28:22', NULL),
(4, 'Mission Trip - Guatemala', 'mission-trip-guatemala', '<p>Join us in supporting our mission team traveling to Guatemala this summer. Funds will be used for:</p><ul><li>Building homes for families in need</li><li>Medical supplies and clinics</li><li>School supplies for children</li><li>Team travel and accommodations</li></ul><p>Be part of bringing hope and God\'s love to communities in Guatemala.</p>', 'Support our mission team traveling to Guatemala to build homes and serve communities in need.', NULL, 25000.00, 18500.00, 'Missions', NULL, NULL, 1, 1, 1, 1, 62, 1.00, '50,100,250,500,1000', 'pending', 1, 0, NULL, '2026-01-17 12:28:22', NULL),
(5, 'General Tithes & Offerings', 'general-tithes-offerings', '<p>Your tithes and offerings support the daily operations and ministries of our church:</p><ul><li>Worship services and programs</li><li>Pastoral care and counseling</li><li>Facility maintenance</li><li>Staff support</li><li>Community events</li></ul><p>Thank you for your faithful giving that keeps our ministry running.</p>', 'Support our church\'s daily operations, worship services, and ongoing ministry programs.', NULL, 0.00, 0.00, 'General', NULL, NULL, 1, 0, 1, 1, 0, 1.00, '25,50,100,250,500', 'pending', 1, 0, NULL, '2026-01-17 12:28:22', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `event_date` date NOT NULL,
  `event_time` time NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `event_image` varchar(255) DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `registration_url` varchar(500) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `title`, `event_date`, `event_time`, `location`, `description`, `image_url`, `created_at`, `updated_at`, `event_image`, `end_time`, `registration_url`, `contact_email`, `contact_phone`) VALUES
(1, 'Youth Night', '2026-01-17', '18:00:00', 'Youth Hall', 'Join us for games, worship, and fellowship!', NULL, '2026-01-11 06:20:14', '2026-01-11 06:20:14', NULL, NULL, NULL, NULL, NULL),
(2, 'Bible Study', '2026-01-30', '19:00:00', 'Main Building - Room 3', 'Weekly Bible study on the Book of Romans', '', '2026-01-11 06:20:14', '2026-01-15 10:45:15', NULL, NULL, NULL, NULL, NULL),
(3, 'Community Outreach', '2026-01-18', '09:00:00', 'City Center', 'Serving our community with food and love', NULL, '2026-01-11 06:20:14', '2026-01-11 06:20:14', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `excerpt` varchar(500) DEFAULT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `author_id` int(11) DEFAULT NULL,
  `status` enum('draft','published') DEFAULT 'draft',
  `target_pages` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`target_pages`)),
  `show_on_homepage` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `published_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sermons`
--

CREATE TABLE `sermons` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `preacher` varchar(100) NOT NULL,
  `sermon_date` date NOT NULL,
  `description` text DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `audio_url` varchar(255) DEFAULT NULL,
  `scripture_reference` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `cover_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sermons`
--

INSERT INTO `sermons` (`id`, `title`, `preacher`, `sermon_date`, `description`, `video_url`, `audio_url`, `scripture_reference`, `created_at`, `updated_at`, `cover_image`) VALUES
(1, 'Walking in Faith', 'Pastor John Smith', '2026-01-05', 'A powerful message about trusting God in uncertain times.', 'https://www.youtube.com/watch?v=STY0SqRKR5c', '', 'Hebrews 11:1-6', '2026-01-11 06:20:14', '2026-01-15 02:56:50', ''),
(2, 'The Power of Prayer', 'Pastor Sarah Johnson', '2025-12-29', 'Understanding how prayer transforms our lives and circumstances.', NULL, NULL, 'Matthew 6:5-15', '2026-01-11 06:20:14', '2026-01-11 06:20:14', NULL),
(3, 'Love Your Neighbor', 'Pastor John Smith', '2025-12-22', 'Practical ways to show Christ\'s love to those around us.', NULL, NULL, 'Luke 10:25-37', '2026-01-11 06:20:14', '2026-01-11 06:20:14', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'site_title', 'Christ Mission Ministries Inc.', '2026-01-13 03:57:10'),
(2, 'hero_title', 'Helping You Grow Your Faith', '2026-01-11 06:20:14'),
(3, 'hero_subtitle', 'St. James Yard, Bend &amp; Stop Barnersville Estate Road, Barnersville Township Montserrado County, Liberia West Africa', '2026-01-13 03:57:10'),
(4, 'service_time_1', 'Sunday Morning: 9:00 AM', '2026-01-11 06:20:14'),
(5, 'service_time_2', 'Sunday Evening: 11:30 AM', '2026-01-11 06:20:14'),
(6, 'service_time_3', 'Wednesday Prayer: 7:00 PM', '2026-01-11 06:20:14'),
(7, 'live_stream_url', 'https://www.youtube.com/embed/VIDEO_ID', '2026-01-11 06:20:14'),
(8, 'church_address', 'St. James Yard, Bend &amp; Stop Barnersville Estate Road, Barnersville Township Montserrado County, Liberia West Africa', '2026-01-13 03:57:10'),
(9, 'church_phone', '(+231) 0771234567', '2026-01-13 03:57:10'),
(10, 'church_email', 'info@christmissionministry.com', '2026-01-13 03:57:10');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `donations`
--
ALTER TABLE `donations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_campaign_id` (`campaign_id`);

--
-- Indexes for table `donation_campaigns`
--
ALTER TABLE `donation_campaigns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_is_featured` (`is_featured`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `sermons`
--
ALTER TABLE `sermons`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `donations`
--
ALTER TABLE `donations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `donation_campaigns`
--
ALTER TABLE `donation_campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sermons`
--
ALTER TABLE `sermons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `site_settings`
--
ALTER TABLE `site_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admins`
--
ALTER TABLE `admins`
  ADD CONSTRAINT `admins_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `donation_campaigns`
--
ALTER TABLE `donation_campaigns`
  ADD CONSTRAINT `donation_campaigns_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
