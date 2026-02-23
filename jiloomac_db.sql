-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 25, 2026 at 11:46 PM
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
-- Database: `jiloomac_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `carousel_slides`
--

CREATE TABLE `carousel_slides` (
  `id` int(11) NOT NULL,
  `image` varchar(255) NOT NULL,
  `title` varchar(100) NOT NULL,
  `topic` varchar(100) NOT NULL,
  `short_desc` text NOT NULL,
  `product_id` int(11) NOT NULL,
  `long_desc` text NOT NULL,
  `spec_time` varchar(50) DEFAULT '6 hours',
  `spec_port` varchar(50) DEFAULT 'Type-C',
  `spec_os` varchar(50) DEFAULT 'Android/iOS',
  `spec_bt` varchar(50) DEFAULT '5.3',
  `spec_control` varchar(50) DEFAULT 'Touch'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `carousel_slides`
--

INSERT INTO `carousel_slides` (`id`, `image`, `title`, `topic`, `short_desc`, `product_id`, `long_desc`, `spec_time`, `spec_port`, `spec_os`, `spec_bt`, `spec_control`) VALUES
(1, 'slide_1769269966.png', 'PERSONALIZED ANC', 'ORAIMO SPACEBUDS', 'Silence the world with 50dB Hybrid Noise Cancellation. Featuring industry-first Customized Voice Prompts and 40 hours of playback for the ultimate personalized audio experience.', 44, 'Step into the future of personal audio. The SpaceBuds feature a revolutionary Customized Voice Prompt system—record your own voice or download exclusive tones via the Oraimo Sound App for alerts like \"Connected\" or \"Low Battery.\" Combined with Smart Chat Mode and Wide-Area Tap controls, these earbuds are tailored exclusively to you.', '40 Hours', 'Type-C', 'Android / iOS', '5.4', 'Wide-Area Tap'),
(2, 'slide_1769270084.png', 'MEGA CAPACITY', 'ORAIMO POWERBOX', 'Unleash limitless energy. Boasting a gigantic 40,000mAh capacity with 22.5W AniFast™ Super Charging, the PowerBox can keep your devices alive for up to two weeks on a single charge.', 45, 'Built for road trips, power outages, and heavy users. The PowerBox features 4 high-speed output ports so you and your friends can charge simultaneously. It comes equipped with a smart digital LED display to track your juice, a built-in SOS flashlight for emergencies, and Oraimo\'s advanced Multi-Protection safety system.', '40,000 mAh', 'Type-C & TripleUSB', 'Universal', 'N/A (Fast Charge)', 'LED & SOS Button'),
(4, 'slide_1769279955.png', 'THE FUTURE ON YOUR WRIST', 'ORAIMO WATCH ', 'Stay connected without your phone. The Oraimo Watch Nova features a brilliant 1.69\" HD display, wireless Bluetooth calling, and over 120+ sport modes to track your active lifestyle.', 46, 'Your ultimate health and productivity assistant. The Watch Nova offers 24/7 heart rate monitoring, blood oxygen tracking, and sleep analysis directly from your wrist. With a premium metallic finish, IP68 water resistance, and a 7-day battery life, it is designed to look stunning in the boardroom and perform flawlessly at the gym.', '7 Days', 'Magnetic Charge', 'Android / iOS', '5.1', 'Full Touch + Crown');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `customer_name` varchar(255) DEFAULT NULL,
  `customer_phone` varchar(50) DEFAULT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `customer_address` text DEFAULT NULL,
  `payment_ref` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total_amount`, `status`, `created_at`, `customer_name`, `customer_phone`, `customer_email`, `customer_address`, `payment_ref`) VALUES
(9, NULL, 3700.00, 'completed', '2026-01-21 18:03:07', 'Emmanuel Ani', '09024156052', 'anikolise@gmail.com', 'Block 140, flat 3 mile two estate, Lagos state. Nigeria', '472291076'),
(10, NULL, 6500.00, 'completed', '2026-01-22 12:16:58', 'Emmanuel Ani', '09024156052', 'anikolise@gmail.com', 'Block 140, flat 3 mile two estate, Lagos state. Nigeria', '584629085'),
(11, NULL, 3700.00, 'completed', '2026-01-22 22:21:43', 'Emmanuel Ani', '09024156052', 'anikolise@gmail.com', 'Block 140, flat 3 mile two estate, Lagos state. Nigeria', '798824841'),
(12, NULL, 3700.00, 'completed', '2026-01-22 22:22:53', 'Emmanuel Ani', '09024156052', 'anikolise@gmail.com', 'Block 140, flat 3 mile two estate, Lagos state. Nigeria', '553680839'),
(13, NULL, 5100.00, 'completed', '2026-01-22 22:25:49', 'Emmanuel Ani', '09024156052', 'anikolise@gmail.com', 'Block 140, flat 3 mile two estate, Lagos state. Nigeria', '314009167'),
(14, 2, 5100.00, 'completed', '2026-01-22 22:51:21', 'Emmanuel Ani', '09024156052', 'anikolise@gmail.com', 'Block 140, flat 3 mile two estate, Lagos state. Nigeria', '98822637'),
(15, 2, 4100.00, 'completed', '2026-01-22 22:52:43', 'Emmanuel Ani', '09024156052', 'anikolise@gmail.com', 'Block 140, flat 3 mile two estate, Lagos state. Nigeria', '24893047'),
(16, 2, 4100.00, 'completed', '2026-01-22 22:53:54', 'Emmanuel Ani', '09024156052', 'anikolise@gmail.com', 'Block 140, flat 3 mile two estate, Lagos state. Nigeria', '372836975'),
(17, 2, 3700.00, 'completed', '2026-01-22 22:54:24', 'Emmanuel Ani', '09024156052', 'anikolise@gmail.com', 'Block 140, flat 3 mile two estate, Lagos state. Nigeria', '852577582'),
(18, 2, 493500.00, '', '2026-01-23 18:34:37', 'Emmanuel Ani', '09024156052', 'anikolise@gmail.com', 'Block 140, flat 3 mile two estate, Lagos state. Nigeria', '888567777'),
(19, 2, 493500.00, 'completed', '2026-01-23 18:40:21', 'Emmanuel Ani', '09024156052', 'anikolise@gmail.com', 'Block 140, flat 3 mile two estate, Lagos state. Nigeria', '272843372'),
(20, NULL, 493500.00, 'completed', '2026-01-24 12:47:55', 'Emmanuel Ani', '09024156052', 'anikolise@gmail.com', 'Block 140, flat 3 mile two estate, Lagos state. Nigeria', '923761662'),
(21, 2, 21200.00, 'completed', '2026-01-24 14:57:16', 'Emmanuel Ani', '09024156052', 'anikolise@gmail.com', 'Block 140, flat 3 mile two estate, Lagos state. Nigeria', '313074558'),
(22, 3, 53500.00, 'completed', '2026-01-25 19:31:49', 'Emmanuel Ani', '09024156052', 'anikolise@gmail.com', 'Block 140, flat 3 mile two estate, Lagos state. Nigeria', '738237373'),
(23, 3, 53500.00, 'completed', '2026-01-25 22:16:46', 'Emmanuel Ani', '09024156052', 'anikolise@gmail.com', 'Block 140, flat 3 mile two estate, Lagos state. Nigeria', '375298586'),
(24, 3, 43100.00, 'completed', '2026-01-25 22:33:00', 'Emmanuel Ani', '09024156052', 'anikolise@gmail.com', 'Block 140, flat 3 mile two estate, Lagos state. Nigeria', '566682060');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price_at_purchase` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `variant_id`, `quantity`, `price_at_purchase`) VALUES
(11, 24, 45, NULL, 22, 1, 39600.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(12,2) NOT NULL,
  `cost_price` decimal(12,2) DEFAULT NULL,
  `stock_quantity` int(11) DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `category` varchar(100) DEFAULT 'General',
  `status` enum('active','draft') DEFAULT 'active',
  `sku` varchar(50) DEFAULT NULL,
  `colors` varchar(255) DEFAULT NULL,
  `storage` varchar(255) DEFAULT NULL,
  `ram` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `brand`, `description`, `price`, `cost_price`, `stock_quantity`, `image`, `created_at`, `category`, `status`, `sku`, `colors`, `storage`, `ram`) VALUES
(40, 'Infinix note50  pro', '', 'Experience the Speed of the Future.\r\n\r\nThe Infinix Note 50 Pro redefines power and elegance. Engineered for gamers, creators, and professionals, it features a stunning 6.7-inch AMOLED display with a 120Hz refresh rate for buttery-smooth visuals. Under the hood, it\'s powered by the high-performance MediaTek Dimensity series chipset, ensuring zero lag even during heavy multitasking.', 850000.00, 800000.00, 30, '1769192579_6973bc83cf92c.webp', '2026-01-23 18:22:59', 'Phones', 'active', 'JIL-PH-7646', '', '', ''),
(41, 'Tecno camon 40 pro', 'Tecno', 'Portrait Master. Beyond the Lens.\"\r\n\r\nElevate your mobile photography with the all-new Tecno Camon 40 Pro. Designed for those who live life through a lens, this device combines a breathtaking aesthetic with professional-grade imaging technology. Featuring a vibrant 6.78-inch Curved AMOLED Display with a 144Hz refresh rate, every swipe and scroll feels incredibly fluid and immersive.', 490000.00, 450000.00, 27, '1769192956_6973bdfcd05f8.png', '2026-01-23 18:29:16', 'Phones', 'active', 'JIL-PH-4779', '', '', ''),
(43, ' itel Star 200 Powerbank 20000mAh Fast Charging Power Bank ', 'Itel', 'The itel Star 200 Powerbank features dual USB output ports that automatically recognize connected devices and deliver up to 5V 2.1A for efficient charging. Its USB-C and Micro USB inputs recharge the power bank fully within 10 hours at 5V 2.0A. Equipped with a safe and lightweight Li-polymer battery, it’s ideal for travel, including airplane use.', 17700.00, 15000.00, 5, '1769266526_6974dd5e8cae7.webp', '2026-01-24 14:55:26', 'Accessories', 'active', 'JIL-AC-6068', '', '', ''),
(44, 'Oraimo SpaceBuds Hybrid ANC (OTW-630)', 'Oraimo', 'Silence the world with 50dB Hybrid Noise Cancellation. Featuring industry-first Customized Voice Prompts and 40 hours of playback for the ultimate personalized audio experience.', 55900.00, 50500.00, 7, '1769269576_6974e94866aea.webp', '2026-01-24 15:46:16', 'Accessories', 'active', 'JIL-AC-6618', '', '', ''),
(45, 'Oraimo PowerBox 400 (40,000mAh)', 'Oraimo', 'Unleash limitless energy. Boasting a gigantic 40,000mAh capacity with 22.5W AniFast™ Super Charging, the PowerBox can keep your devices alive for up to two weeks on a single charge.', 39600.00, 30000.00, 29, '1769269681_6974e9b1cfb9d.webp', '2026-01-24 15:48:01', 'Accessories', 'active', 'JIL-PH-7187', '', '', ''),
(46, 'Oraimo Watch Nova (OSW-30)', 'Oraimo', 'Stay connected without your phone. The Oraimo Watch Nova features a brilliant 1.69\" HD display, wireless Bluetooth calling, and over 120+ sport modes to track your active lifestyle.', 50000.00, 45000.00, 4, '1769269775_6974ea0fcc37c.webp', '2026-01-24 15:49:35', 'Accessories', 'active', 'JIL-AC-3650', '', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `product_variants`
--

CREATE TABLE `product_variants` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `color` varchar(50) NOT NULL,
  `storage` varchar(50) NOT NULL,
  `ram` varchar(50) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_variants`
--

INSERT INTO `product_variants` (`id`, `product_id`, `color`, `storage`, `ram`, `price`, `stock`) VALUES
(1, 31, 'Black', '256GB', '6GB', 2000.00, 1),
(2, 32, 'Black', '256GB', '6GB', 3000000.00, 20),
(3, 32, 'red', '256GB', '6GB', 3000000.00, 10),
(4, 32, 'white', '256GB', '6GB', 3000000.00, 10),
(5, 33, 'Black', '256GB', '6GB', 20000.00, 26),
(6, 33, 'red', '256GB', '6GB', 20000.00, 4),
(7, 34, 'Black', '256GB', '6GB', 20000.00, 21),
(8, 35, 'Black', '256GB', '6GB', 200.00, 0),
(9, 36, 'white', '256GB', '4GB', 100.00, 22),
(10, 36, 'Black', '256GB', '6GB', 100.00, 3),
(13, 38, 'Standard', '-', '-', 200.00, 0),
(14, 41, 'blue', '512GB', '8GB', 490000.00, 14),
(15, 41, 'green', '256GB', '6GB', 490000.00, 13),
(16, 40, 'Chrome balck', '512GB', '8GB', 850000.00, 14),
(17, 40, 'silver white', '512GB', '6GB', 850000.00, 16),
(18, 42, 'white', '400GB', '6GB', 2000000.00, 1),
(19, 42, 'Red', '256GB', '6GB', 2000000.00, 2),
(20, 43, 'Standard', '-', '-', 17700.00, 5),
(21, 46, 'Standard', '-', '-', 50000.00, 4),
(22, 45, 'Standard', '-', '-', 39600.00, 29),
(23, 44, 'Standard', '-', '-', 55900.00, 7);

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `product_id`, `user_id`, `rating`, `comment`, `created_at`) VALUES
(3, 41, 2, 4, 'perfect', '2026-01-23 18:31:53'),
(4, 40, 2, 3, 'i loved it', '2026-01-23 18:39:39'),
(5, 41, 3, 2, 'it was okay', '2026-01-23 18:41:47'),
(6, 43, 2, 3, 'chill', '2026-01-24 14:57:29');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `announcement_text` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `announcement_text`, `is_active`, `updated_at`) VALUES
(1, 'heloooooo', 1, '2026-01-23 18:43:29');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','customer') DEFAULT 'customer',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `google_id` varchar(255) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `picture` varchar(255) DEFAULT NULL,
  `remember_token` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`, `google_id`, `profile_pic`, `picture`, `remember_token`) VALUES
(2, 'Super Admin', 'admin@jiloomac.com', '$2y$10$Dt8tjao16EY4Npq/wZX.7uLByR2g64YHHFA4u08dPBAgbqAPxA3dG', 'admin', '2026-01-17 18:34:38', NULL, NULL, NULL, 'cf8105d4e49bc4709bbcf37f494e24881f0d7cf5c8a1197b3ebd380c6f5fc7b7'),
(3, 'Kolise', 'anikolise@gmail.com', '$2y$10$Glsos/1JLPOge3envjNufu4Ofu2kyV5I4RgEr5EP1uvKVEmk4NqDe', 'customer', '2026-01-20 19:57:39', '100295384902223407049', 'https://lh3.googleusercontent.com/a/ACg8ocLYhIoVIq394QNl9cZ5EqN1PIH0FXpLHMX8hyJ09N-OKMfN1KA=s96-c', NULL, '7f3649529e71f7d2d8b6828e440229985853d1c6dc003db6ffae2b07404399ea'),
(4, 'Kolise', 'kolise2004@gmail.com', '', 'customer', '2026-01-20 22:15:03', '105686704449219308645', 'https://lh3.googleusercontent.com/a/ACg8ocJftXBBOMP8aA8XDTfLA52ZPxpGUx-WDJz607oRnAqqxM2vAg=s96-c', NULL, NULL),
(5, 'Chiamaka', 'kolise2005@gmail.com', '$2y$10$frw9LyBndr1.vrBKsU4pTeWRBJOVLwH/wiKbIuVHbVsFpYdJ8prom', 'customer', '2026-01-25 19:13:53', NULL, NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `carousel_slides`
--
ALTER TABLE `carousel_slides`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_order_items_orders` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`);

--
-- Indexes for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `carousel_slides`
--
ALTER TABLE `carousel_slides`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_orders` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
