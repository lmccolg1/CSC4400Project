-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 13, 2026 at 05:14 AM
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
-- Database: `dating_app`
--

-- --------------------------------------------------------

--
-- Table structure for table `account`
--

CREATE TABLE `account` (
  `account_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `utype` varchar(20) DEFAULT NULL,
  `isbot` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `standing` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `account`
--

INSERT INTO `account` (`account_id`, `username`, `password`, `utype`, `isbot`, `created_at`, `standing`) VALUES
(1, 'flynn_mctaggart', '$2y$10$JotPLyuir4knYjJ4b3APHOj9/mhZyMdDTB.tZ.cwSdj2ZNP85wSh2', 'user', 0, '2026-03-17 14:11:50', NULL),
(2, 'spartan117', '$2y$10$iLi/dvMQbGq4Zg7uHfZH0.fa/knhjuHmErNPy5ZZ8EX8J4ziKGlRi', 'user', 0, '2026-03-29 13:07:00', NULL),
(3, 'GOD', '$2y$10$7XYm5THrwhs9NVDcAZCcYObEox4sQYyxQrbugYnC7ivV1yIvOC.EO', 'admin', 0, '2026-03-29 13:39:03', NULL),
(4, 'Jehova', '$2y$10$QTWu4yjb7608nT58WGgu3.lguyI/wvdQxKrFgf9vKvYKMQH/DELKG', 'admin', 0, '2026-04-12 23:09:56', NULL),
(5, 'Yahweh', '$2y$10$AePrS/CBSepu9PfPRNozCeR2.NU/tlPX8otUwR3pEs7LcpLWCiNU.', 'user', 0, '2026-04-12 23:10:59', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `admin_requests`
--

CREATE TABLE `admin_requests` (
  `request_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `status` enum('pending','approved','denied') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_requests`
--

INSERT INTO `admin_requests` (`request_id`, `account_id`, `status`, `created_at`) VALUES
(1, 4, 'approved', '2026-04-13 03:09:56'),
(2, 5, 'denied', '2026-04-13 03:10:59');

-- --------------------------------------------------------

--
-- Table structure for table `bot`
--

CREATE TABLE `bot` (
  `bot_id` int(11) NOT NULL,
  `acc_id` int(11) DEFAULT NULL,
  `persona` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `message`
--

CREATE TABLE `message` (
  `message_id` int(11) NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `receiver_id` int(11) DEFAULT NULL,
  `text` text DEFAULT NULL,
  `sent_at` datetime DEFAULT current_timestamp(),
  `read_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `profile`
--

CREATE TABLE `profile` (
  `profile_id` int(11) NOT NULL,
  `acc_id` int(11) DEFAULT NULL,
  `screenname` varchar(50) DEFAULT NULL,
  `summary` text DEFAULT NULL,
  `likes` text DEFAULT NULL,
  `dislikes` text DEFAULT NULL,
  `isprivate` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `profile`
--

INSERT INTO `profile` (`profile_id`, `acc_id`, `screenname`, `summary`, `likes`, `dislikes`, `isprivate`) VALUES
(1, 2, 'master_chief', 'hasn\'t been in a good game for almost 20 years', 'green', 'purple', 0),
(2, 3, 'GOD', 'GOD', 'GOD', 'anchovies', 0),
(3, 4, 'Iehova', 'Jehova starts with an I', 'I', 'J', 0),
(4, 5, 'YHWH', 'oy', 'nothing', 'most things', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `account`
--
ALTER TABLE `account`
  ADD PRIMARY KEY (`account_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `admin_requests`
--
ALTER TABLE `admin_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `account_id` (`account_id`);

--
-- Indexes for table `bot`
--
ALTER TABLE `bot`
  ADD PRIMARY KEY (`bot_id`),
  ADD KEY `acc_id` (`acc_id`);

--
-- Indexes for table `message`
--
ALTER TABLE `message`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `profile`
--
ALTER TABLE `profile`
  ADD PRIMARY KEY (`profile_id`),
  ADD KEY `acc_id` (`acc_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `account`
--
ALTER TABLE `account`
  MODIFY `account_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `admin_requests`
--
ALTER TABLE `admin_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `bot`
--
ALTER TABLE `bot`
  MODIFY `bot_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `message`
--
ALTER TABLE `message`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `profile`
--
ALTER TABLE `profile`
  MODIFY `profile_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_requests`
--
ALTER TABLE `admin_requests`
  ADD CONSTRAINT `admin_requests_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `account` (`account_id`);

--
-- Constraints for table `bot`
--
ALTER TABLE `bot`
  ADD CONSTRAINT `bot_ibfk_1` FOREIGN KEY (`acc_id`) REFERENCES `account` (`account_id`) ON DELETE CASCADE;

--
-- Constraints for table `message`
--
ALTER TABLE `message`
  ADD CONSTRAINT `message_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `account` (`account_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `message_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `account` (`account_id`) ON DELETE CASCADE;

--
-- Constraints for table `profile`
--
ALTER TABLE `profile`
  ADD CONSTRAINT `profile_ibfk_1` FOREIGN KEY (`acc_id`) REFERENCES `account` (`account_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
