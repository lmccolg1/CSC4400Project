-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 26, 2026 at 10:51 PM
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

CREATE TABLE IF NOT EXISTS `account` (
  `account_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `utype` varchar(20) DEFAULT NULL,
  `isbot` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `standing` varchar(20) DEFAULT NULL,
  `security_question` varchar(255) DEFAULT NULL,
  `security_answer` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`account_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `account`
--

INSERT INTO `account` (`account_id`, `username`, `password`, `utype`, `isbot`, `created_at`, `standing`, `security_question`, `security_answer`) VALUES
(1, 'flynn_mctaggart', 'ripntear', 'user', 0, '2026-03-17 14:11:50', NULL, 'What was the name of your first pet?', 'Daisy'),
(2, 'spartan117', 'pillarofautism', 'user', 0, '2026-03-29 13:07:00', NULL, 'What was the name of your first pet?', 'Cortana'),
(3, 'GOD', 'ashbogoba12', 'admin', 0, '2026-03-29 13:39:03', NULL, 'What was the make of your first car?', 'Toyota'),
(4, 'Jehova', '$2y$10$QTWu4yjb7608nT58WGgu3.lguyI/wvdQxKrFgf9vKvYKMQH/DELKG', 'admin', 0, '2026-04-12 23:09:56', NULL, 'What city were you born in?', 'Jerusalem'),
(5, 'Yahweh', '$2y$10$AePrS/CBSepu9PfPRNozCeR2.NU/tlPX8otUwR3pEs7LcpLWCiNU.', 'user', 0, '2026-04-12 23:10:59', NULL, 'What city were you born in?', 'Edom');

-- --------------------------------------------------------

--
-- Table structure for table `admin_requests`
--

CREATE TABLE IF NOT EXISTS `admin_requests` (
  `request_id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` int(11) NOT NULL,
  `status` enum('pending','approved','denied') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`request_id`),
  KEY `account_id` (`account_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

CREATE TABLE IF NOT EXISTS `bot` (
  `bot_id` int(11) NOT NULL AUTO_INCREMENT,
  `acc_id` int(11) DEFAULT NULL,
  `persona` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`bot_id`),
  KEY `acc_id` (`acc_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `matches`
--

CREATE TABLE IF NOT EXISTS `matches` (
  `match_id` int(11) NOT NULL AUTO_INCREMENT,
  `user1_id` int(11) NOT NULL,
  `user2_id` int(11) NOT NULL,
  `status` enum('liked','passed','matched') NOT NULL DEFAULT 'liked',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`match_id`),
  UNIQUE KEY `unique_pair` (`user1_id`,`user2_id`),
  KEY `user2_id` (`user2_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `message`
--

CREATE TABLE IF NOT EXISTS `message` (
  `message_id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) DEFAULT NULL,
  `receiver_id` int(11) DEFAULT NULL,
  `text` text DEFAULT NULL,
  `sent_at` datetime DEFAULT current_timestamp(),
  `read_at` datetime DEFAULT NULL,
  PRIMARY KEY (`message_id`),
  KEY `sender_id` (`sender_id`),
  KEY `receiver_id` (`receiver_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `profile`
--

CREATE TABLE IF NOT EXISTS `profile` (
  `profile_id` int(11) NOT NULL AUTO_INCREMENT,
  `acc_id` int(11) DEFAULT NULL,
  `screenname` varchar(50) DEFAULT NULL,
  `summary` text DEFAULT NULL,
  `likes` text DEFAULT NULL,
  `dislikes` text DEFAULT NULL,
  `isprivate` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`profile_id`),
  KEY `acc_id` (`acc_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `profile`
--

INSERT INTO `profile` (`profile_id`, `acc_id`, `screenname`, `summary`, `likes`, `dislikes`, `isprivate`) VALUES
(1, 2, 'master_chief', 'hasn\'t been in a good game for almost 20 years', 'green', 'purple', 0),
(2, 3, 'GOD', 'GOD', 'GOD', 'anchovies', 0),
(3, 4, 'Iehova', 'Jehova starts with an I', 'I', 'J', 0),
(4, 5, 'YHWH', 'oy', 'nothing', 'most things', 0),
(5, 1, 'doomguy', 'has never been in a bad game', 'rabbits', 'satan', 0);

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
-- Constraints for table `matches`
--
ALTER TABLE `matches`
  ADD CONSTRAINT `matches_ibfk_1` FOREIGN KEY (`user1_id`) REFERENCES `account` (`account_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `matches_ibfk_2` FOREIGN KEY (`user2_id`) REFERENCES `account` (`account_id`) ON DELETE CASCADE;

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
