-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 13, 2025 at 10:57 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `fruit_quiz_game`
--

-- --------------------------------------------------------

--
-- Table structure for table `fruits`
--

CREATE TABLE `fruits` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `image_path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fruits`
--

INSERT INTO `fruits` (`id`, `name`, `image_path`) VALUES
(1, 'Apple', 'images/apple.jpg'),
(2, 'Banana', 'images/banana.jpg'),
(3, 'Orange', 'images/orange.jpg'),
(4, 'Mango', 'images/mango.jpg'),
(5, 'Strawberry', 'images/strawberry.jpg'),
(6, 'Grape', 'images/grape.jpg'),
(7, 'Pineapple', 'images/pineapple.jpg'),
(8, 'Watermelon', 'images/watermelon.jpg'),
(9, 'Kiwi', 'images/kiwi.jpg'),
(10, 'Peach', 'images/peach.jpg'),
(11, 'Pomelo', 'images/pomelo.jpg'),
(12, 'Papaya', 'images/papaya.jpg'),
(13, 'Dragon Fruit', 'images/dragon fruit.jpg'),
(14, 'Pear', 'images/pear.jpg'),
(15, 'Avocado', 'images/avocado.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `players`
--

CREATE TABLE `players` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `score` int(11) NOT NULL,
  `time_started` datetime NOT NULL,
  `time_ended` datetime NOT NULL,
  `duration_seconds` int(11) NOT NULL,
  `date_played` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `players`
--

INSERT INTO `players` (`id`, `username`, `score`, `time_started`, `time_ended`, `duration_seconds`, `date_played`) VALUES
(2, 'adsad', 9, '2025-03-13 22:41:28', '2025-03-13 22:42:02', 34, '2025-03-13'),
(3, 'dasdasd', 10, '2025-03-13 22:42:21', '2025-03-13 22:43:12', 51, '2025-03-13');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `fruits`
--
ALTER TABLE `fruits`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `players`
--
ALTER TABLE `players`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `fruits`
--
ALTER TABLE `fruits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `players`
--
ALTER TABLE `players`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
