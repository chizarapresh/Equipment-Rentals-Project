-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Apr 05, 2026 at 09:18 PM
-- Server version: 8.4.7
-- PHP Version: 8.5.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db2417081_equipmentdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `equipment`
--

CREATE TABLE `equipment` (
  `equipment_id` int NOT NULL,
  `categoryID` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `daily_rate` decimal(10,2) NOT NULL,
  `total_quantity` int NOT NULL,
  `available_quantity` int NOT NULL,
  `status` enum('available','rented','maintenance') NOT NULL DEFAULT 'available',
  `condition_status` enum('new','good','fair') NOT NULL DEFAULT 'good',
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `equipment`
--

INSERT INTO `equipment` (`equipment_id`, `categoryID`, `name`, `description`, `daily_rate`, `total_quantity`, `available_quantity`, `status`, `condition_status`, `image`) VALUES
(1, 102, 'Tent (2-Person)', 'Lightweight 2-person dome tent, waterproof and easy to pitch. Ideal for weekend camping trips.', 8.00, 10, 10, 'available', 'new', 'images/equipment/tent-2person.jpg'),
(2, 102, 'Tent (4-Person)', 'Spacious 4-person family tent with two rooms and a weather-resistant fly sheet.', 12.00, 10, 10, 'available', 'good', 'images/equipment/tent-4person.jpg'),
(3, 102, 'Sleeping Bag', 'Mummy-style sleeping bag rated to -5°C, packable and lightweight for backpacking.', 5.00, 10, 10, 'available', 'new', 'images/equipment/sleeping-bag.jpg'),
(4, 102, 'Sleeping Pad', 'Self-inflating foam sleeping pad providing insulation and comfort on uneven ground.', 3.00, 10, 10, 'available', 'good', 'images/equipment/sleeping-pad.png'),
(5, 102, 'Trekking Poles (Pair)', 'Adjustable aluminium trekking poles with cork grips and wrist straps. Collapsible for easy packing.', 4.00, 10, 10, 'available', 'good', 'images/equipment/trekking-poles.jpg'),
(6, 102, 'Headlamp', 'Rechargeable LED headlamp with 300 lumen output and red night-vision mode. Waterproof rated IPX4.', 2.00, 10, 10, 'available', 'new', 'images/equipment/headlamp.jpg'),
(7, 102, 'Topographic Map Set', 'Set of detailed OS topographic maps covering popular hiking regions in Scotland.', 1.50, 10, 10, 'available', 'good', 'images/equipment/topo-map.jpg'),
(8, 102, 'Compass', 'Silva baseplate compass with declination adjustment and magnifier. Essential for navigation.', 1.50, 10, 10, 'available', 'new', 'images/equipment/compass.jpg'),
(9, 102, 'Camp Chair', 'Foldable lightweight camp chair with cup holder and carry bag. Supports up to 120kg.', 3.00, 10, 10, 'available', 'good', 'images/equipment/camp-chair.jpg'),
(10, 102, 'Camp Table', 'Aluminium folding camp table, adjustable height, suitable for 4 people.', 4.00, 10, 10, 'available', 'good', 'images/equipment/camp-table.jpg'),
(11, 101, 'Kayak (Single)', 'Stable sit-on-top single kayak suitable for calm lochs and coastal waters. Paddle included.', 20.00, 10, 10, 'available', 'good', 'images/equipment/kayak-single.jpg'),
(12, 101, 'Kayak (Double)', 'Two-person touring kayak with storage hatches. Great for loch and sea exploration.', 30.00, 10, 10, 'available', 'good', 'images/equipment/kayak-double.jpg'),
(13, 101, 'Paddleboard (SUP)', 'Inflatable stand-up paddleboard with paddle, pump and carry bag. Suitable for all skill levels.', 18.00, 10, 10, 'available', 'new', 'images/equipment/paddleboard.jpg'),
(14, 101, 'Surfboard', 'Foam-top beginner surfboard, 8ft, with leash. Buoyant and durable for learning.', 15.00, 10, 10, 'available', 'good', 'images/equipment/surfboard.jpg'),
(15, 101, 'Life Jacket (Adult)', 'CE-approved buoyancy aid for adults (70–90kg). Comfortable fit for active water sports.', 4.00, 10, 10, 'available', 'new', 'images/equipment/lifejacket-adult.jpg'),
(16, 101, 'Life Jacket (Child)', 'CE-approved children\'s life jacket (15–40kg). Bright colour for high visibility on water.', 3.00, 10, 10, 'available', 'new', 'images/equipment/lifejacket-child.jpg'),
(17, 101, 'Jet Ski', 'High-performance 3-seater jet ski. Licence and safety briefing required before rental.', 150.00, 10, 10, 'available', 'good', 'images/equipment/jetski.jpg'),
(18, 101, 'Wetsuit (Adult)', '5mm full wetsuit for cold Scottish waters. Available in sizes S, M, L, XL.', 10.00, 10, 10, 'available', 'good', 'images/equipment/wetsuit.jpg'),
(19, 101, 'Snorkel Set', 'Mask, snorkel and fins set for exploring shallow coastal waters.', 5.00, 10, 10, 'available', 'new', 'images/equipment/snorkel-set.jpg'),
(20, 103, 'Campervan (2-Berth)', 'Compact 2-berth campervan with kitchenette, heating and bedding. Full licence required.', 120.00, 10, 10, 'available', 'good', 'images/equipment/campervan-2.jpg'),
(21, 103, 'Campervan (4-Berth)', 'Famil y 4-berth campervan with full kitchen, shower, and heating. Ideal for extended trips.', 180.00, 10, 10, 'available', 'good', 'images/equipment/campervan-4.jpg'),
(22, 103, 'Camping Trailer', 'Tow-behind camping trailer with pop-up tent section and storage. Requires tow bar fitting.', 55.00, 10, 10, 'available', 'fair', 'images/equipment/camping-trailer.jpg'),
(23, 103, 'Roof Rack (Universal)', 'Universal fit roof rack compatible with most car roof rails. Load-rated to 75kg.', 10.00, 10, 10, 'available', 'good', 'images/equipment/roof-rack.jpg'),
(24, 103, 'Watersports Carrier', 'Roof-mounted carrier for kayaks, paddleboards and surfboards. Foam padding to protect equipment.', 12.00, 10, 10, 'available', 'new', 'images/equipment/watersports-carrier.jpg'),
(25, 103, 'Bike Rack (4-Bike)', 'Towbar-mounted bike rack for up to 4 bikes. Quick-release and lockable.', 8.00, 10, 10, 'available', 'good', 'images/equipment/bike-rack.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `equipment_categories`
--

CREATE TABLE `equipment_categories` (
  `category_id` int NOT NULL,
  `category_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `equipment_categories`
--

INSERT INTO `equipment_categories` (`category_id`, `category_name`) VALUES
(102, 'Camping/Hiking'),
(103, 'Transportation'),
(101, 'Water Sports');

-- --------------------------------------------------------

--
-- Table structure for table `rentals`
--

CREATE TABLE `rentals` (
  `rental_id` int NOT NULL,
  `user_id` int NOT NULL,
  `equipment_id` int NOT NULL,
  `rental_date` date NOT NULL,
  `due_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `status` enum('rented','returned','overdue') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'rented'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `rentals`
--

INSERT INTO `rentals` (`rental_id`, `user_id`, `equipment_id`, `rental_date`, `due_date`, `return_date`, `status`) VALUES
(1, 1000, 25, '2026-04-04', '2026-04-11', '2026-04-05', 'returned'),
(2, 1000, 10, '2026-04-04', '2026-04-11', '2026-04-05', 'returned'),
(3, 1000, 9, '2026-04-05', '2026-04-12', '2026-04-05', 'returned');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int NOT NULL,
  `firstname` varchar(50) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `DOB` date DEFAULT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `account_status` enum('active','suspended','inactive') NOT NULL DEFAULT 'active',
  `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `firstname`, `lastname`, `email`, `password_hash`, `phone`, `DOB`, `role`, `account_status`, `date_created`) VALUES
(1000, 'Jane', 'Doe', 'janedoe@gmail.com', '$2y$12$CnqMaVIe.hNSqMeudXqIUunq4vNMHTdxt7Y6jndyAWikPcYG2DhiW', '+446000000000', '2008-08-01', 'user', 'active', '2026-04-01 22:47:44'),
(1001, 'John', 'Smith', 'johnsmith@gmail.com', '$2y$12$pPsFdU58mBLZGmAJWUu2e.2cn8TZmfhanCohSlEb3HW.fkAS1zIj.', '+441000000000', '2000-01-04', 'admin', 'active', '2026-04-01 22:54:59');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `equipment`
--
ALTER TABLE `equipment`
  ADD PRIMARY KEY (`equipment_id`),
  ADD KEY `categoryID` (`categoryID`);

--
-- Indexes for table `equipment_categories`
--
ALTER TABLE `equipment_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `rentals`
--
ALTER TABLE `rentals`
  ADD PRIMARY KEY (`rental_id`),
  ADD KEY `equipment_id` (`equipment_id`),
  ADD KEY `fk_users` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
  MODIFY `equipment_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `equipment_categories`
--
ALTER TABLE `equipment_categories`
  MODIFY `category_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT for table `rentals`
--
ALTER TABLE `rentals`
  MODIFY `rental_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1002;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `equipment`
--
ALTER TABLE `equipment`
  ADD CONSTRAINT `equipment_ibfk_1` FOREIGN KEY (`categoryID`) REFERENCES `equipment_categories` (`category_id`);

--
-- Constraints for table `rentals`
--
ALTER TABLE `rentals`
  ADD CONSTRAINT `fk_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `rentals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `rentals_ibfk_2` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`equipment_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
