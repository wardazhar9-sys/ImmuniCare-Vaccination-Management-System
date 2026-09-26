-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 21, 2026 at 03:59 PM
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
-- Database: `vaccination_management_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `child_id` int(11) NOT NULL,
  `hospital_id` int(11) NOT NULL,
  `vaccine_id` int(11) NOT NULL,
  `booking_date` date NOT NULL,
  `booking_time` time NOT NULL,
  `status` enum('Pending','Approved','Rejected','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `parent_id`, `child_id`, `hospital_id`, `vaccine_id`, `booking_date`, `booking_time`, `status`, `created_at`) VALUES
(1, 1, 1, 1, 1, '2026-09-30', '10:00:00', 'Pending', '2026-09-13 09:03:48'),
(2, 1, 1, 1, 7, '2026-09-02', '11:31:00', 'Pending', '2026-09-13 18:31:27'),
(3, 1, 1, 1, 1, '2026-11-20', '10:20:00', 'Pending', '2026-09-15 12:22:39'),
(4, 1, 5, 2, 3, '2026-12-25', '11:00:00', 'Approved', '2026-09-16 10:43:20'),
(5, 6, 7, 1, 2, '2026-12-31', '11:00:00', 'Completed', '2026-09-17 10:07:42'),
(6, 6, 8, 2, 17, '2026-10-30', '09:15:00', 'Completed', '2026-09-17 10:15:47'),
(7, 6, 7, 2, 1, '2026-10-30', '09:15:00', 'Approved', '2026-09-17 10:53:45'),
(8, 6, 7, 2, 7, '2027-01-01', '22:00:00', 'Rejected', '2026-09-17 12:55:00');

-- --------------------------------------------------------

--
-- Table structure for table `children`
--

CREATE TABLE `children` (
  `id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `child_name` varchar(100) NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` enum('Male','Female') NOT NULL,
  `blood_group` varchar(10) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `children`
--

INSERT INTO `children` (`id`, `parent_id`, `child_name`, `date_of_birth`, `gender`, `blood_group`, `address`, `created_at`) VALUES
(1, 1, 'Maryam', '2023-03-16', 'Female', 'O+', 'Naya Nazimabad, Karachi', '2026-09-12 20:32:08'),
(3, 1, 'Ismail', '2024-11-08', 'Male', 'B-', 'North Karachi', '2026-09-15 21:29:23'),
(5, 1, 'Sara', '2026-09-02', 'Female', 'O+', 'North Karachi, Pakistan', '2026-09-16 06:11:23'),
(7, 6, 'Musfirah', '2024-11-08', 'Female', 'O+', 'Naya Nazimabad, Karachi', '2026-09-17 10:06:48'),
(8, 6, 'Muhammad Umar', '2023-03-16', 'Male', 'B+', 'Naya Nazimabad, Karachi', '2026-09-17 10:15:14');

-- --------------------------------------------------------

--
-- Table structure for table `hospitals`
--

CREATE TABLE `hospitals` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `hospital_name` varchar(150) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` text NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hospitals`
--

INSERT INTO `hospitals` (`id`, `user_id`, `hospital_name`, `phone`, `address`, `city`, `location`, `status`, `created_at`) VALUES
(1, 2, 'Aga Khan University Hospital', '021-111-911-911', 'Stadium Road', 'Karachi', 'Karachi, Sindh', 'Active', '2026-09-13 08:49:19'),
(2, 5, 'Liaquat National Hospital', '', '', 'Karachi', 'Karachi, Sindh', 'Active', '2026-09-16 09:17:03'),
(3, 11, 'Indus Hospital', '021-111-111-111', 'Korangi Crossing, Karachi', 'Karachi', 'Karachi, Sindh', 'Active', '2026-09-18 22:21:48'),
(4, 12, 'South City Hospital', '021-111-724-000', 'Shahrah-e-Firdousi, Clifton, Karachi', 'Karachi', 'Karachi, Sindh', 'Active', '2026-09-18 22:21:48'),
(5, 13, 'Patel Hospital', '021-111-174-174', 'ST-18, Block 4, Gulshan-e-Iqbal, Karachi', 'Karachi', 'Karachi, Sindh', 'Active', '2026-09-18 22:21:48'),
(6, 14, 'Ziauddin Hospital', '021-111-942-942', 'Block 4, Clifton, Karachi', 'Karachi', 'Karachi, Sindh', 'Active', '2026-09-18 22:21:48');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) DEFAULT 'general',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `created_at`) VALUES
(1, 5, 'New Appointment', 'A new vaccination appointment has been booked for Musfirah.', 'appointment', 1, '2026-09-17 10:53:45'),
(2, 6, 'Appointment Approved', 'Your vaccination appointment for Musfirah (BCG - Dose 1) has been approved.', 'appointment', 1, '2026-09-17 11:17:57'),
(3, 5, 'New Appointment', 'A new vaccination appointment has been booked for Musfirah.', 'appointment', 0, '2026-09-17 12:55:00'),
(4, 6, 'Appointment Approved', 'Your vaccination appointment for Musfirah (Polio - Dose 1) has been approved.', 'appointment', 1, '2026-09-17 14:42:23');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','parent','hospital') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Ayesha Khan', 'ayesha123@gmail.com', '$2y$10$/R3GCSHYe/X/DJNrOxJmreY2zXoRz2QPfFbiU9fvhektwbSNWUH0S', 'parent', '2026-09-11 21:22:08'),
(2, 'Aga Khan Hospital', 'agh@gmail.com', '$2y$10$9NUfR/QykYiU6ZLQJ6Vh5Oj1oaUgr59UWagdbdLj/LuGN2YHDynEu', 'hospital', '2026-09-11 21:41:56'),
(3, 'Muhammad Ali', 'ali@gmail.com', '$2y$10$FvflE9J.8hNttvk4kRw/.u7aXvjGnliCAsFqevQp.WPeVEjkqzfRm', 'parent', '2026-09-11 22:06:17'),
(4, 'Ahmed Yousuf', 'ahmed123@gmail.com', '$2y$10$.8Y.jkDw/wOKxezq10OnJeXlsu4vn/s5iuRjR.FRopfUsb5UAmZzW', 'parent', '2026-09-12 08:21:17'),
(5, 'Liaquat National Hospital', 'liaquat@gmail.com', '$2y$10$xX2ei9PdREW7CKFNmBWm9uR5zn.j5T2tVHrJMcsA/hb2QApomDVB2', 'hospital', '2026-09-16 07:17:45'),
(6, 'Wardah Azhar', 'wardazhar9@gmail.com', '$2y$10$RtPP/uN2MCEJLd6HEVUF.e8DeeAr0oo7HxNJYLOHphzzJ446D6bEW', 'parent', '2026-09-17 10:04:11'),
(11, 'Indus Hospital', 'indus@immunicare.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'hospital', '2026-09-18 22:20:18'),
(12, 'South City Hospital', 'southcity@immunicare.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'hospital', '2026-09-18 22:20:18'),
(13, 'Patel Hospital', 'patel@immunicare.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'hospital', '2026-09-18 22:20:18'),
(14, 'Ziauddin Hospital', 'ziauddin@immunicare.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'hospital', '2026-09-18 22:20:18'),
(15, 'Shoaib Hassan', 'shoaib@gmail.com', '$2y$10$LfzYd4c2UUv8mvpLoTN24u2T93f9p1sSoWHtQ8UVyUa0BboQr1ghS', 'parent', '2026-09-20 11:21:45'),
(16, 'ImmuniCare Admin', 'admin@immunicare.com', '$2y$10$Rd/TqL2RkGOC4gpLCX2eruYyBcTGJHUUfSLF7cSu5/FIK1mZpQeN2', 'admin', '2026-09-21 12:09:24');

-- --------------------------------------------------------

--
-- Table structure for table `vaccination_records`
--

CREATE TABLE `vaccination_records` (
  `id` int(11) NOT NULL,
  `child_id` int(11) NOT NULL,
  `vaccine_id` int(11) NOT NULL,
  `hospital_id` int(11) NOT NULL,
  `vaccination_date` date NOT NULL,
  `status` enum('Vaccinated','Not Vaccinated') NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vaccination_records`
--

INSERT INTO `vaccination_records` (`id`, `child_id`, `vaccine_id`, `hospital_id`, `vaccination_date`, `status`, `remarks`, `created_at`) VALUES
(3, 8, 17, 2, '2026-09-17', 'Vaccinated', '', '2026-09-17 13:12:48'),
(4, 7, 2, 1, '2026-09-17', 'Vaccinated', '', '2026-09-17 14:46:53');

-- --------------------------------------------------------

--
-- Table structure for table `vaccination_schedules`
--

CREATE TABLE `vaccination_schedules` (
  `id` int(11) NOT NULL,
  `child_id` int(11) NOT NULL,
  `vaccine_id` int(11) NOT NULL,
  `scheduled_date` date NOT NULL,
  `scheduled_time` time NOT NULL,
  `status` enum('Scheduled','Completed','Missed','Cancelled') NOT NULL DEFAULT 'Scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vaccination_schedules`
--

INSERT INTO `vaccination_schedules` (`id`, `child_id`, `vaccine_id`, `scheduled_date`, `scheduled_time`, `status`, `created_at`) VALUES
(1, 5, 3, '2026-12-25', '11:00:00', 'Scheduled', '2026-09-16 10:55:37'),
(2, 8, 17, '2026-10-30', '09:15:00', 'Completed', '2026-09-17 10:41:35'),
(3, 7, 2, '2026-12-31', '11:00:00', 'Completed', '2026-09-17 14:44:43');

-- --------------------------------------------------------

--
-- Table structure for table `vaccines`
--

CREATE TABLE `vaccines` (
  `id` int(11) NOT NULL,
  `vaccine_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `age_group` varchar(50) DEFAULT NULL,
  `dose_number` int(11) NOT NULL,
  `availability` enum('Available','Unavailable') NOT NULL DEFAULT 'Available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vaccines`
--

INSERT INTO `vaccines` (`id`, `vaccine_name`, `description`, `age_group`, `dose_number`, `availability`, `created_at`) VALUES
(1, 'BCG', 'Protects against tuberculosis.', 'At Birth', 1, 'Available', '2026-09-13 08:41:51'),
(2, 'Polio', 'Protects children against poliovirus.', 'At Birth - 5 Years', 1, 'Available', '2026-09-13 08:41:51'),
(3, 'Hepatitis B', 'Protects against hepatitis B infection.', 'At Birth', 1, 'Available', '2026-09-13 08:41:51'),
(4, 'Pentavalent', 'Protects against diphtheria, tetanus, pertussis, hepatitis B and Hib.', '6 Weeks - 14 Weeks', 1, 'Available', '2026-09-13 08:41:51'),
(5, 'Pneumococcal', 'Protects against pneumococcal infections.', '6 Weeks - 15 Months', 1, 'Available', '2026-09-13 08:41:51'),
(6, 'Rotavirus', 'Protects against rotavirus infection and severe diarrhea.', '6 Weeks - 24 Weeks', 1, 'Available', '2026-09-13 08:41:51'),
(7, 'Measles', 'Protects against measles infection.', '9 Months - 15 Months', 1, 'Available', '2026-09-13 08:41:51'),
(8, 'Diphtheria', 'Protects against diphtheria, a serious bacterial infection.', 'Childhood', 1, 'Available', '2026-09-15 11:40:12'),
(9, 'Tetanus', 'Protects against tetanus, a serious bacterial infection that can enter through wounds.', 'Childhood and Adults', 1, 'Available', '2026-09-15 11:40:12'),
(10, 'Pertussis', 'Protects against pertussis, also known as whooping cough.', 'Childhood', 1, 'Available', '2026-09-15 11:40:12'),
(11, 'Hib', 'Protects against Haemophilus influenzae type b infections, including meningitis and pneumonia.', 'Infants and Young Children', 1, 'Available', '2026-09-15 11:40:12'),
(12, 'HPV', 'Protects against infections caused by human papillomavirus and related cancers.', 'Adolescents', 1, 'Available', '2026-09-15 11:40:12'),
(13, 'MMR', 'Protects against measles, mumps and rubella.', 'Children', 1, 'Available', '2026-09-15 11:40:12'),
(14, 'Rubella', 'Protects against rubella infection and helps prevent congenital rubella syndrome.', 'Children and Adolescents', 1, 'Available', '2026-09-15 11:40:12'),
(15, 'Mumps', 'Protects against mumps, a contagious viral infection.', 'Children and Adolescents', 1, 'Available', '2026-09-15 11:40:12'),
(16, 'Varicella', 'Protects against chickenpox caused by the varicella-zoster virus.', 'Children', 1, 'Available', '2026-09-15 11:40:12'),
(17, 'Hepatitis A', 'Protects against hepatitis A, a viral infection affecting the liver.', 'Children and At-Risk Groups', 1, 'Available', '2026-09-15 11:40:12'),
(18, 'Influenza', 'Protects against seasonal influenza and helps reduce complications from flu.', 'Children and Adults', 1, 'Available', '2026-09-15 11:40:12'),
(19, 'Meningococcal', 'Protects against meningococcal disease, which can cause meningitis and bloodstream infections.', 'Children and Adolescents', 1, 'Available', '2026-09-15 11:40:12'),
(20, 'Typhoid', 'Protects against typhoid fever caused by Salmonella Typhi.', 'Children and At-Risk Groups', 1, 'Available', '2026-09-15 11:40:12'),
(21, 'Rabies', 'Protects against rabies, a serious viral disease transmitted mainly through infected animal bites.', 'At-Risk Groups and Travelers', 1, 'Available', '2026-09-15 11:40:12'),
(22, 'Japanese Encephalitis', 'Protects against Japanese encephalitis, a mosquito-borne viral infection.', 'Children and Travelers', 1, 'Available', '2026-09-15 11:40:12'),
(23, 'Yellow Fever', 'Protects against yellow fever, a mosquito-borne viral disease found in certain regions.', 'Travelers and At-Risk Populations', 1, 'Available', '2026-09-15 11:40:12'),
(24, 'Cholera', 'Protects against cholera, a bacterial infection that can cause severe diarrhea and dehydration.', 'At-Risk Groups and Travelers', 1, 'Available', '2026-09-15 11:40:12'),
(25, 'COVID-19', 'Protects against COVID-19 and helps reduce the risk of severe disease.', 'Eligible Age and Risk Groups', 1, 'Available', '2026-09-15 11:40:12'),
(26, 'RSV', 'Protects against respiratory syncytial virus, which can cause serious respiratory illness.', 'Infants and At-Risk Groups', 1, 'Available', '2026-09-15 11:40:12'),
(27, 'Dengue', 'Helps protect against dengue in populations for whom vaccination is recommended.', 'Eligible Populations', 1, 'Available', '2026-09-15 11:40:12');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `child_id` (`child_id`),
  ADD KEY `hospital_id` (`hospital_id`),
  ADD KEY `vaccine_id` (`vaccine_id`);

--
-- Indexes for table `children`
--
ALTER TABLE `children`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `hospitals`
--
ALTER TABLE `hospitals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `vaccination_records`
--
ALTER TABLE `vaccination_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `child_id` (`child_id`),
  ADD KEY `vaccine_id` (`vaccine_id`),
  ADD KEY `hospital_id` (`hospital_id`);

--
-- Indexes for table `vaccination_schedules`
--
ALTER TABLE `vaccination_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `child_id` (`child_id`),
  ADD KEY `vaccine_id` (`vaccine_id`);

--
-- Indexes for table `vaccines`
--
ALTER TABLE `vaccines`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `children`
--
ALTER TABLE `children`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `hospitals`
--
ALTER TABLE `hospitals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `vaccination_records`
--
ALTER TABLE `vaccination_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `vaccination_schedules`
--
ALTER TABLE `vaccination_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `vaccines`
--
ALTER TABLE `vaccines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`child_id`) REFERENCES `children` (`id`),
  ADD CONSTRAINT `bookings_ibfk_3` FOREIGN KEY (`hospital_id`) REFERENCES `hospitals` (`id`),
  ADD CONSTRAINT `bookings_ibfk_4` FOREIGN KEY (`vaccine_id`) REFERENCES `vaccines` (`id`);

--
-- Constraints for table `children`
--
ALTER TABLE `children`
  ADD CONSTRAINT `children_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `hospitals`
--
ALTER TABLE `hospitals`
  ADD CONSTRAINT `hospitals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `vaccination_records`
--
ALTER TABLE `vaccination_records`
  ADD CONSTRAINT `vaccination_records_ibfk_1` FOREIGN KEY (`child_id`) REFERENCES `children` (`id`),
  ADD CONSTRAINT `vaccination_records_ibfk_2` FOREIGN KEY (`vaccine_id`) REFERENCES `vaccines` (`id`),
  ADD CONSTRAINT `vaccination_records_ibfk_3` FOREIGN KEY (`hospital_id`) REFERENCES `hospitals` (`id`);

--
-- Constraints for table `vaccination_schedules`
--
ALTER TABLE `vaccination_schedules`
  ADD CONSTRAINT `vaccination_schedules_ibfk_1` FOREIGN KEY (`child_id`) REFERENCES `children` (`id`),
  ADD CONSTRAINT `vaccination_schedules_ibfk_2` FOREIGN KEY (`vaccine_id`) REFERENCES `vaccines` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- Run after vaccination_management_system(1).sql.
-- MariaDB/MySQLi only.

CREATE TABLE IF NOT EXISTS schema_migrations (
    version VARCHAR(40) NOT NULL,
    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active' AFTER role;

ALTER TABLE hospitals
    MODIFY status ENUM('Pending', 'Active', 'Inactive') NOT NULL DEFAULT 'Pending',
    ADD COLUMN IF NOT EXISTS verified_at DATETIME NULL AFTER status;

ALTER TABLE children
    ADD COLUMN IF NOT EXISTS archived_at DATETIME NULL AFTER created_at;

ALTER TABLE bookings
    ADD COLUMN IF NOT EXISTS notes VARCHAR(500) NULL AFTER booking_time,
    ADD COLUMN IF NOT EXISTS cancelled_at DATETIME NULL AFTER status,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

ALTER TABLE vaccination_schedules
    ADD COLUMN IF NOT EXISTS booking_id INT NULL AFTER id,
    ADD COLUMN IF NOT EXISTS hospital_id INT NULL AFTER vaccine_id,
    ADD COLUMN IF NOT EXISTS dose_number INT NOT NULL DEFAULT 1 AFTER vaccine_id,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

ALTER TABLE vaccination_records
    ADD COLUMN IF NOT EXISTS booking_id INT NULL AFTER id,
    ADD COLUMN IF NOT EXISTS schedule_id INT NULL AFTER booking_id,
    ADD COLUMN IF NOT EXISTS dose_number INT NOT NULL DEFAULT 1 AFTER vaccine_id,
    ADD COLUMN IF NOT EXISTS recorded_by INT NULL AFTER hospital_id,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

ALTER TABLE notifications
    ADD COLUMN IF NOT EXISTS link_url VARCHAR(255) NULL AFTER type,
    MODIFY is_read TINYINT(1) NOT NULL DEFAULT 0;

UPDATE hospitals
SET status = 'Active'
WHERE status IS NULL OR status = '';

UPDATE vaccination_schedules vs
JOIN (
    SELECT child_id, vaccine_id, MIN(id) AS booking_id
    FROM bookings
    GROUP BY child_id, vaccine_id
) b ON b.child_id = vs.child_id AND b.vaccine_id = vs.vaccine_id
JOIN bookings source_booking ON source_booking.id = b.booking_id
JOIN vaccines v ON v.id = vs.vaccine_id
SET
    vs.booking_id = b.booking_id,
    vs.hospital_id = source_booking.hospital_id,
    vs.dose_number = v.dose_number
WHERE vs.booking_id IS NULL;

UPDATE vaccination_records vr
JOIN (
    SELECT child_id, vaccine_id, hospital_id, MIN(id) AS booking_id
    FROM bookings
    GROUP BY child_id, vaccine_id, hospital_id
) b ON b.child_id = vr.child_id
    AND b.vaccine_id = vr.vaccine_id
    AND b.hospital_id = vr.hospital_id
JOIN vaccines v ON v.id = vr.vaccine_id
SET
    vr.booking_id = b.booking_id,
    vr.dose_number = v.dose_number
WHERE vr.booking_id IS NULL;

UPDATE vaccination_records vr
JOIN vaccination_schedules vs
    ON vs.child_id = vr.child_id
    AND vs.vaccine_id = vr.vaccine_id
    AND vs.hospital_id = vr.hospital_id
SET vr.schedule_id = vs.id
WHERE vr.schedule_id IS NULL;

ALTER TABLE hospitals
    ADD UNIQUE KEY uq_hospitals_user_id (user_id),
    ADD KEY idx_hospitals_status_city (status, city);

ALTER TABLE bookings
    ADD KEY idx_bookings_parent_status (parent_id, status),
    ADD KEY idx_bookings_hospital_date (hospital_id, booking_date, booking_time),
    ADD KEY idx_bookings_child_status (child_id, status);

ALTER TABLE children
    ADD KEY idx_children_parent_archived (parent_id, archived_at);

ALTER TABLE vaccination_schedules
    ADD UNIQUE KEY uq_schedule_booking (booking_id),
    ADD KEY idx_schedule_hospital_date (hospital_id, scheduled_date, scheduled_time),
    ADD KEY idx_schedule_child_status (child_id, status);

ALTER TABLE vaccination_records
    ADD UNIQUE KEY uq_record_booking (booking_id),
    ADD KEY idx_records_child_date (child_id, vaccination_date),
    ADD KEY idx_records_hospital_date (hospital_id, vaccination_date);

ALTER TABLE notifications
    ADD KEY idx_notifications_user_read (user_id, is_read, created_at);

ALTER TABLE vaccination_schedules
    ADD CONSTRAINT fk_schedule_booking
        FOREIGN KEY (booking_id) REFERENCES bookings(id),
    ADD CONSTRAINT fk_schedule_hospital
        FOREIGN KEY (hospital_id) REFERENCES hospitals(id);

ALTER TABLE vaccination_records
    ADD CONSTRAINT fk_record_booking
        FOREIGN KEY (booking_id) REFERENCES bookings(id),
    ADD CONSTRAINT fk_record_schedule
        FOREIGN KEY (schedule_id) REFERENCES vaccination_schedules(id),
    ADD CONSTRAINT fk_record_user
        FOREIGN KEY (recorded_by) REFERENCES users(id);

ALTER TABLE notifications
    ADD CONSTRAINT fk_notifications_user
        FOREIGN KEY (user_id) REFERENCES users(id);

CREATE TABLE IF NOT EXISTS contact_messages (
    id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('New', 'In Progress', 'Resolved', 'Spam') NOT NULL DEFAULT 'New',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_contact_status_created (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT NOT NULL AUTO_INCREMENT,
    actor_user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(60) NOT NULL,
    entity_id INT NULL,
    details JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_entity (entity_type, entity_id, created_at),
    KEY idx_audit_actor (actor_user_id, created_at),
    CONSTRAINT fk_audit_actor
        FOREIGN KEY (actor_user_id) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO schema_migrations (version)
VALUES ('001_harden_schema');

-- Domain expansion migration. Apply once after 001_harden_schema.sql.

CREATE TABLE IF NOT EXISTS schema_migrations (
    version VARCHAR(40) NOT NULL,
    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS vaccine_doses (
    id INT NOT NULL AUTO_INCREMENT,
    vaccine_id INT NOT NULL,
    dose_number INT NOT NULL,
    recommended_age_days INT NULL,
    minimum_interval_days INT NULL,
    catch_up_rule VARCHAR(500) NULL,
    clinical_source VARCHAR(255) NULL,
    source_version VARCHAR(50) NULL,
    status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_vaccine_dose (vaccine_id, dose_number),
    KEY idx_dose_vaccine_status (vaccine_id, status),
    CONSTRAINT fk_dose_vaccine
        FOREIGN KEY (vaccine_id) REFERENCES vaccines(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO vaccine_doses
    (vaccine_id, dose_number, status)
SELECT id, dose_number, 'Active'
FROM vaccines;

ALTER TABLE hospitals
    ADD COLUMN IF NOT EXISTS verification_status
        ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending' AFTER status;

UPDATE hospitals
SET verification_status = 'Approved'
WHERE status = 'Active' AND verification_status = 'Pending';

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS email_verified_at DATETIME NULL AFTER status;

ALTER TABLE bookings
    ADD COLUMN IF NOT EXISTS vaccine_dose_id INT NULL AFTER vaccine_id,
    ADD COLUMN IF NOT EXISTS rejection_reason VARCHAR(500) NULL AFTER cancelled_at,
    ADD COLUMN IF NOT EXISTS rescheduled_at DATETIME NULL AFTER rejection_reason;

ALTER TABLE vaccination_schedules
    ADD COLUMN IF NOT EXISTS vaccine_dose_id INT NULL AFTER vaccine_id,
    ADD COLUMN IF NOT EXISTS scheduled_at_utc DATETIME NULL AFTER scheduled_time;

ALTER TABLE vaccination_records
    ADD COLUMN IF NOT EXISTS vaccine_dose_id INT NULL AFTER vaccine_id,
    ADD COLUMN IF NOT EXISTS lot_number VARCHAR(100) NULL AFTER remarks,
    ADD COLUMN IF NOT EXISTS manufacturer VARCHAR(150) NULL AFTER lot_number,
    ADD COLUMN IF NOT EXISTS expiry_date DATE NULL AFTER manufacturer,
    ADD COLUMN IF NOT EXISTS administration_site VARCHAR(100) NULL AFTER expiry_date,
    ADD COLUMN IF NOT EXISTS recorded_at DATETIME NULL AFTER vaccination_date;

UPDATE bookings b
JOIN vaccine_doses d
    ON d.vaccine_id = b.vaccine_id
    AND d.dose_number = (SELECT dose_number FROM vaccines WHERE id = b.vaccine_id)
SET b.vaccine_dose_id = d.id
WHERE b.vaccine_dose_id IS NULL;

UPDATE vaccination_schedules s
JOIN vaccine_doses d
    ON d.vaccine_id = s.vaccine_id
    AND d.dose_number = s.dose_number
SET s.vaccine_dose_id = d.id
WHERE s.vaccine_dose_id IS NULL;

UPDATE vaccination_records r
JOIN vaccine_doses d
    ON d.vaccine_id = r.vaccine_id
    AND d.dose_number = r.dose_number
SET r.vaccine_dose_id = d.id,
    r.recorded_at = COALESCE(r.recorded_at, r.created_at)
WHERE r.vaccine_dose_id IS NULL;

ALTER TABLE notifications
    ADD COLUMN IF NOT EXISTS booking_id INT NULL AFTER user_id,
    ADD COLUMN IF NOT EXISTS schedule_id INT NULL AFTER booking_id,
    ADD COLUMN IF NOT EXISTS record_id INT NULL AFTER schedule_id,
    ADD COLUMN IF NOT EXISTS read_at DATETIME NULL AFTER is_read;

CREATE TABLE IF NOT EXISTS hospital_hours (
    id INT NOT NULL AUTO_INCREMENT,
    hospital_id INT NOT NULL,
    weekday TINYINT NOT NULL,
    opens_at TIME NOT NULL,
    closes_at TIME NOT NULL,
    is_closed TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_hospital_weekday (hospital_id, weekday),
    CONSTRAINT fk_hours_hospital
        FOREIGN KEY (hospital_id) REFERENCES hospitals(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS hospital_holidays (
    id INT NOT NULL AUTO_INCREMENT,
    hospital_id INT NOT NULL,
    holiday_date DATE NOT NULL,
    reason VARCHAR(255) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_hospital_holiday (hospital_id, holiday_date),
    CONSTRAINT fk_holiday_hospital
        FOREIGN KEY (hospital_id) REFERENCES hospitals(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS hospital_slots (
    id INT NOT NULL AUTO_INCREMENT,
    hospital_id INT NOT NULL,
    slot_date DATE NOT NULL,
    slot_time TIME NOT NULL,
    capacity INT NOT NULL DEFAULT 1,
    booked_count INT NOT NULL DEFAULT 0,
    status ENUM('Open', 'Closed') NOT NULL DEFAULT 'Open',
    PRIMARY KEY (id),
    UNIQUE KEY uq_hospital_slot (hospital_id, slot_date, slot_time),
    KEY idx_slot_availability (hospital_id, slot_date, status),
    CONSTRAINT fk_slot_hospital
        FOREIGN KEY (hospital_id) REFERENCES hospitals(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS hospital_inventory (
    id INT NOT NULL AUTO_INCREMENT,
    hospital_id INT NOT NULL,
    vaccine_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    reorder_level INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_hospital_inventory (hospital_id, vaccine_id),
    CONSTRAINT fk_inventory_hospital
        FOREIGN KEY (hospital_id) REFERENCES hospitals(id),
    CONSTRAINT fk_inventory_vaccine
        FOREIGN KEY (vaccine_id) REFERENCES vaccines(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS notification_outbox (
    id BIGINT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    channel ENUM('in_app', 'email', 'sms', 'whatsapp') NOT NULL DEFAULT 'in_app',
    event_type VARCHAR(100) NOT NULL,
    payload JSON NOT NULL,
    status ENUM('Pending', 'Sent', 'Failed') NOT NULL DEFAULT 'Pending',
    attempts INT NOT NULL DEFAULT 0,
    last_error VARCHAR(500) NULL,
    available_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sent_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_outbox_status_available (status, available_at),
    CONSTRAINT fk_outbox_user
        FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS email_verification_tokens (
    id BIGINT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_email_token (token_hash),
    KEY idx_email_token_user (user_id, expires_at),
    CONSTRAINT fk_email_token_user
        FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id BIGINT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_reset_token (token_hash),
    KEY idx_reset_token_user (user_id, expires_at),
    CONSTRAINT fk_reset_token_user
        FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS auth_events (
    id BIGINT NOT NULL AUTO_INCREMENT,
    user_id INT NULL,
    event_type VARCHAR(80) NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_auth_event_user (user_id, created_at),
    CONSTRAINT fk_auth_event_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS api_tokens (
    id BIGINT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL,
    name VARCHAR(100) NOT NULL,
    expires_at DATETIME NULL,
    revoked_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_api_token_hash (token_hash),
    KEY idx_api_token_user (user_id, revoked_at),
    CONSTRAINT fk_api_token_user
        FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO schema_migrations (version)
VALUES ('002_domain_expansion');

-- Reports, preferences, and migration bookkeeping.

CREATE TABLE IF NOT EXISTS user_preferences (
    user_id INT NOT NULL,
    email_notifications TINYINT(1) NOT NULL DEFAULT 1,
    reminder_notifications TINYINT(1) NOT NULL DEFAULT 1,
    locale VARCHAR(10) NOT NULL DEFAULT 'en',
    timezone VARCHAR(64) NOT NULL DEFAULT 'UTC',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id),
    CONSTRAINT fk_preferences_user
        FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS report_exports (
    id BIGINT NOT NULL AUTO_INCREMENT,
    requested_by INT NOT NULL,
    report_type VARCHAR(80) NOT NULL,
    format ENUM('CSV', 'HTML') NOT NULL DEFAULT 'CSV',
    filters JSON NULL,
    status ENUM('Pending', 'Ready', 'Failed') NOT NULL DEFAULT 'Ready',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_report_user_created (requested_by, created_at),
    CONSTRAINT fk_report_user
        FOREIGN KEY (requested_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO schema_migrations (version)
VALUES ('003_reporting_preferences');

-- Bind every new booking to the hospital slot it reserves.

ALTER TABLE bookings
    ADD COLUMN IF NOT EXISTS slot_id INT NULL AFTER hospital_id;

UPDATE bookings b
JOIN hospital_slots s
    ON s.hospital_id = b.hospital_id
    AND s.slot_date = b.booking_date
    AND s.slot_time = b.booking_time
SET b.slot_id = s.id
WHERE b.slot_id IS NULL;

ALTER TABLE bookings
    ADD KEY idx_bookings_slot (slot_id),
    ADD CONSTRAINT fk_bookings_slot
        FOREIGN KEY (slot_id) REFERENCES hospital_slots(id);

INSERT IGNORE INTO schema_migrations (version)
VALUES ('004_booking_slots');
