-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 27, 2024 at 05:13 AM
-- Server version: 8.0.30
-- PHP Version: 8.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `viserbus`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `username` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `name`, `email`, `username`, `email_verified_at`, `image`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Super Admins', 'admin@site.com', 'admin', NULL, NULL, '$2y$12$vc.c.pNxefhOjFzLFNMEW.16i/h1vQCigtZeTLDY12QlIlS0KTWbm', NULL, NULL, '2024-04-21 04:46:46');

-- --------------------------------------------------------

--
-- Table structure for table `admin_notifications`
--

CREATE TABLE `admin_notifications` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL DEFAULT '0',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `click_url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_password_resets`
--

CREATE TABLE `admin_password_resets` (
  `id` bigint UNSIGNED NOT NULL,
  `email` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assigned_vehicles`
--

CREATE TABLE `assigned_vehicles` (
  `id` bigint UNSIGNED NOT NULL,
  `trip_id` int UNSIGNED NOT NULL DEFAULT '0',
  `vehicle_id` int UNSIGNED NOT NULL DEFAULT '0',
  `start_from` time DEFAULT NULL,
  `end_at` time DEFAULT NULL,
  `status` tinyint NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `booked_tickets`
--

CREATE TABLE `booked_tickets` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL DEFAULT '0',
  `gender` int UNSIGNED NOT NULL DEFAULT '0',
  `trip_id` bigint UNSIGNED NOT NULL DEFAULT '0',
  `source_destination` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pickup_point` int UNSIGNED NOT NULL DEFAULT '0',
  `dropping_point` int UNSIGNED NOT NULL DEFAULT '0',
  `seats` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ticket_count` int UNSIGNED NOT NULL DEFAULT '0',
  `unit_price` decimal(28,8) NOT NULL DEFAULT '0.00000000',
  `sub_total` decimal(28,8) NOT NULL DEFAULT '0.00000000',
  `date_of_journey` date DEFAULT NULL,
  `pnr_number` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `counters`
--

CREATE TABLE `counters` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deposits`
--

CREATE TABLE `deposits` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL DEFAULT '0',
  `booked_ticket_id` int UNSIGNED NOT NULL DEFAULT '0',
  `method_code` int UNSIGNED NOT NULL DEFAULT '0',
  `amount` decimal(28,8) NOT NULL DEFAULT '0.00000000',
  `method_currency` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `charge` decimal(28,8) NOT NULL DEFAULT '0.00000000',
  `rate` decimal(28,8) NOT NULL DEFAULT '0.00000000',
  `final_amount` decimal(28,8) NOT NULL DEFAULT '0.00000000',
  `detail` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `btc_amount` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `btc_wallet` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `trx` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_try` int NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1=>success, 2=>pending, 3=>cancel',
  `from_api` tinyint(1) NOT NULL DEFAULT '0',
  `admin_feedback` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `success_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `failed_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_cron` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `device_tokens`
--

CREATE TABLE `device_tokens` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL DEFAULT '0',
  `is_app` tinyint(1) NOT NULL DEFAULT '0',
  `token` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `extensions`
--

CREATE TABLE `extensions` (
  `id` bigint UNSIGNED NOT NULL,
  `act` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `script` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `shortcode` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'object',
  `support` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'help section',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=>enable, 2=>disable',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `extensions`
--

INSERT INTO `extensions` (`id`, `act`, `name`, `description`, `image`, `script`, `shortcode`, `support`, `status`, `created_at`, `updated_at`) VALUES
(1, 'tawk-chat', 'Tawk.to', 'Key location is shown bellow', 'tawky_big.png', '<script>\r\n                        var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();\r\n                        (function(){\r\n                        var s1=document.createElement(\"script\"),s0=document.getElementsByTagName(\"script\")[0];\r\n                        s1.async=true;\r\n                        s1.src=\"https://embed.tawk.to/{{app_key}}\";\r\n                        s1.charset=\"UTF-8\";\r\n                        s1.setAttribute(\"crossorigin\",\"*\");\r\n                        s0.parentNode.insertBefore(s1,s0);\r\n                        })();\r\n                    </script>', '{\"app_key\":{\"title\":\"App Key\",\"value\":\"------\"}}', 'twak.png', 0, '2019-10-18 11:16:05', '2024-05-16 06:23:02'),
(2, 'google-recaptcha2', 'Google Recaptcha 2', 'Key location is shown bellow', 'recaptcha3.png', '\n<script src=\"https://www.google.com/recaptcha/api.js\"></script>\n<div class=\"g-recaptcha\" data-sitekey=\"{{site_key}}\" data-callback=\"verifyCaptcha\"></div>\n<div id=\"g-recaptcha-error\"></div>', '{\"site_key\":{\"title\":\"Site Key\",\"value\":\"6LdPC88fAAAAADQlUf_DV6Hrvgm-pZuLJFSLDOWV\"},\"secret_key\":{\"title\":\"Secret Key\",\"value\":\"6LdPC88fAAAAAG5SVaRYDnV2NpCrptLg2XLYKRKB\"}}', 'recaptcha.png', 0, '2019-10-18 11:16:05', '2024-07-06 00:01:09'),
(3, 'custom-captcha', 'Custom Captcha', 'Just put any random string', 'customcaptcha.png', NULL, '{\"random_key\":{\"title\":\"Random String\",\"value\":\"SecureString\"}}', 'na', 0, '2019-10-18 11:16:05', '2024-07-06 00:01:13'),
(4, 'google-analytics', 'Google Analytics', 'Key location is shown bellow', 'google_analytics.png', '<script async src=\"https://www.googletagmanager.com/gtag/js?id={{measurement_id}}\"></script>\n                <script>\n                  window.dataLayer = window.dataLayer || [];\n                  function gtag(){dataLayer.push(arguments);}\n                  gtag(\"js\", new Date());\n                \n                  gtag(\"config\", \"{{measurement_id}}\");\n                </script>', '{\"measurement_id\":{\"title\":\"Measurement ID\",\"value\":\"------\"}}', 'ganalytics.png', 0, NULL, '2021-05-03 22:19:12'),
(5, 'fb-comment', 'Facebook Comment ', 'Key location is shown bellow', 'Facebook.png', '<div id=\"fb-root\"></div><script async defer crossorigin=\"anonymous\" src=\"https://connect.facebook.net/en_GB/sdk.js#xfbml=1&version=v4.0&appId={{app_key}}&autoLogAppEvents=1\"></script>', '{\"app_key\":{\"title\":\"App Key\",\"value\":\"----\"}}', 'fb_com.png', 0, NULL, '2022-03-21 17:18:36');

-- --------------------------------------------------------

--
-- Table structure for table `fleet_types`
--

CREATE TABLE `fleet_types` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `seat_layout` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deck` int UNSIGNED NOT NULL DEFAULT '0',
  `deck_seats` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `facilities` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `has_ac` int UNSIGNED NOT NULL DEFAULT '0',
  `status` tinyint NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `forms`
--

CREATE TABLE `forms` (
  `id` bigint UNSIGNED NOT NULL,
  `act` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `form_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `frontends`
--

CREATE TABLE `frontends` (
  `id` bigint UNSIGNED NOT NULL,
  `data_keys` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `seo_content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `tempname` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `frontends`
--

INSERT INTO `frontends` (`id`, `data_keys`, `data_values`, `seo_content`, `tempname`, `slug`, `created_at`, `updated_at`) VALUES
(1, 'seo.data', '{\"seo_image\":\"1\",\"keywords\":[\"ViserBus\",\"bus booking system\",\"bus booking php script\",\"single vendor bus booking system\"],\"description\":\"Neque porro quisquam est qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit\",\"social_title\":\"ViserBus - Bus Ticket Booking System\",\"social_description\":\"Neque porro quisquam est qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit ff\",\"image\":\"66a3478bbd09b1721976715.png\"}', NULL, NULL, '', '2020-07-04 23:42:52', '2024-07-26 06:51:55'),
(24, 'about.content', '{\"has_image\":\"1\",\"heading\":\"Know Few Words About Autobus\",\"short_description\":\"<div class=\\\"section-header\\\" style=\\\"margin-bottom:20px;color:rgb(119,119,119);font-family:Lato, sans-serif;\\\"><p style=\\\"margin-right:0px;margin-left:0px;padding:0px;font-size:18px;color:rgb(66,66,72);\\\">Lorem Lorem ipsum dolor, sit amet consectetur adipisicing elit. Nulla sit reprehenderit non voluptas quam quod facilis, doloribus impedit magni. Numquam ipsum placeat ullam alias temporibus non quas aperiam odio pariatur.<\\/p><\\/div><p style=\\\"margin-right:0px;margin-left:0px;padding:0px;color:rgb(119,119,119);font-family:Lato, sans-serif;font-size:16px;\\\">Lorem ipsum dolor sit amet consectetur, adipisicing elit. Eos eveniet inventore blanditiis maxime doloremque minima. Quisquam, ex! Architecto laudantium culpa cupiditate hic facere est magni, possimus repudiandae, rerum eius omnis.lore Lorem ipsum dolor sit amet consectetur adipisicing elit. Doloremque excepturi sed possimus recusandae temporibus tempore, aspernatur, autem sequi natus iste fugit. Eaque vero temporibus illum quis beatae quam officia ad.ri sed possimus recusandae temporibus tempore, aspernatur, autem sequi natus iste fugit. Eaque vero temporibus sed possimus recusandae temporibus tempore, aspernatur, autem sequi natus iste fugit. Eaque vero temporibus illum quis beatae quam officia ad.<\\/p>\",\"title\":\"About Us\",\"description\":\"<div class=\\\"item\\\" style=\\\"margin-bottom:30px;color:rgb(119,119,119);font-family:Lato, sans-serif;\\\"><p style=\\\"margin-right:0px;margin-bottom:10px;margin-left:0px;padding:0px;\\\">Lorem ipsum dolor sit amet consectetur adipisicing elit. Facilis vel temporibus voluptatum quidem, blanditiis libero assumenda beatae ducimus placeat odio aperiam tenetur animi, reiciendis reprehenderit expedita nostrum a eum. Quod. Lorem ipsum dolor sit amet consectetur adipisicing elit. Voluptatum ipsum necessitatibus eum non quisquam! Quo esse est minima vero dolores eveniet voluptatibus nam. Veniam ad quae illum tenetur voluptates veritatis?<\\/p><p style=\\\"margin-right:0px;margin-bottom:10px;margin-left:0px;padding:0px;\\\">Lorem ipsum dolor sit amet consectetur adipisicing elit. Facilis vel temporibus voluptatum quidem, blanditiis libero assumenda beatae ducimus placeat odio aperiam tenetur animi, reiciendis reprehenderit expedita nostrum a eum. Quod. Lorem ipsum dolor sit amet consectetur adipisicing elit. Voluptatum ipsum necessitatibus eum non quisquam! Quo esse est minima vero dolores eveniet voluptatibus nam. Veniam ad quae illum tenetur voluptates veritatis?<\\/p><\\/div><div class=\\\"item\\\" style=\\\"margin-bottom:0px;color:rgb(119,119,119);font-family:Lato, sans-serif;\\\"><h4 class=\\\"title\\\" style=\\\"margin-bottom:10px;font-weight:600;line-height:1.2;font-size:24px;color:rgb(66,66,72);font-family:Georama, sans-serif;\\\">Why Make Bus Reservations With AutoBus<\\/h4><p style=\\\"margin-right:0px;margin-bottom:10px;margin-left:0px;padding:0px;\\\">Lorem ipsum dolor sit amet consectetur adipisicing elit. Facilis vel temporibus voluptatum quidem, blanditiis libero assumenda beatae ducimus placeat odio aperiam tenetur animi, reiciendis reprehenderit expedita nostrum a eum. Quod. Lorem ipsum dolor sit amet consectetur adipisicing elit. Voluptatum ipsum necessitatibus eum non quisquam! Quo esse est minima vero dolores eveniet voluptatibus nam. Veniam ad quae illum tenetur voluptates veritatis?<\\/p><ul class=\\\"info\\\" style=\\\"margin-top:15px;margin-bottom:-7px;margin-left:15px;\\\"><li style=\\\"list-style:disc;padding:3px 0px;\\\">Free Cancellation<\\/li><li style=\\\"list-style:disc;padding:3px 0px;\\\">Instant Refunds<\\/li><li style=\\\"list-style:disc;padding:3px 0px;\\\">Easy & Quick Bus Booking<\\/li><li style=\\\"list-style:disc;padding:3px 0px;\\\">Exciting Cashback & Bus Offers<\\/li><li style=\\\"list-style:disc;padding:3px 0px;\\\">Best Price Assured<\\/li><li style=\\\"list-style:disc;padding:3px 0px;\\\">24\\/7 Customer Assistance<\\/li><\\/ul><\\/div><h2 class=\\\"title\\\" style=\\\"margin-top:-10px;margin-bottom:15px;font-weight:600;line-height:1.2;font-size:36px;color:rgb(66,66,72);font-family:Georama, sans-serif;\\\"><\\/h2>\",\"image\":\"668687dc5c7ad1720092636.jpg\"}', NULL, 'basic', '', '2020-10-28 00:51:20', '2024-07-04 05:30:36'),
(31, 'social_icon.element', '{\"title\":\"Facebook\",\"social_icon\":\"<i class=\\\"las la-expand\\\"><\\/i>\",\"url\":\"https:\\/\\/www.google.com\\/\"}', NULL, 'basic', NULL, '2020-11-12 04:07:30', '2021-05-12 05:56:59'),
(42, 'policy_pages.element', '{\"title\":\"Privacy Policy\",\"details\":\"<h4>Introduction<\\/h4><h4 class=\\\"title\\\" style=\\\"margin-bottom:15px;font-weight:600;line-height:1.2;font-size:24px;color:rgb(66,66,72);font-family:Georama, sans-serif;\\\"><\\/h4><p style=\\\"font-family:Poppins, sans-serif;\\\">This Privacy Policy describes how we collects, uses, and discloses information, including personal information, in connection with your use of our website.<\\/p><br style=\\\"color:rgb(33,37,41);font-family:Poppins, sans-serif;font-size:16px;font-weight:400;\\\" \\/><h4>Information We Collect<\\/h4><h4 class=\\\"title\\\" style=\\\"margin-bottom:15px;font-weight:600;line-height:1.2;font-size:24px;color:rgb(66,66,72);font-family:Georama, sans-serif;\\\"><\\/h4><p style=\\\"font-family:Poppins, sans-serif;\\\">We collect two main types of information on the Website:<\\/p><ul style=\\\"color:rgb(33,37,41);font-family:Poppins, sans-serif;font-size:16px;font-weight:400;\\\"><li><p><span style=\\\"font-weight:bolder;\\\">Personal Information:\\u00a0<\\/span>This includes data that can identify you as an individual, such as your name, email address, phone number, or mailing address. We only collect this information when you voluntarily provide it to us, like signing up for a newsletter, contacting us through a form, or making a purchase.<\\/p><\\/li><li><p><span style=\\\"font-weight:bolder;\\\">Non-Personal Information:\\u00a0<\\/span>This data cannot be used to identify you directly. It includes details like your browser type, device type, operating system, IP address, browsing activity, and usage statistics. We collect this information automatically through cookies and other tracking technologies.<\\/p><\\/li><\\/ul><br style=\\\"color:rgb(33,37,41);font-family:Poppins, sans-serif;font-size:16px;font-weight:400;\\\" \\/><h4>How We Use Information<\\/h4><h4 class=\\\"title\\\" style=\\\"margin-bottom:15px;font-weight:600;line-height:1.2;font-size:24px;color:rgb(66,66,72);font-family:Georama, sans-serif;\\\"><\\/h4><p style=\\\"font-family:Poppins, sans-serif;\\\">The information we collect allows us to:<\\/p><ul style=\\\"color:rgb(33,37,41);font-family:Poppins, sans-serif;font-size:16px;font-weight:400;\\\"><li>Operate and maintain the Website effectively.<\\/li><li>Send you newsletters or marketing communications, but only with your consent.<\\/li><li>Respond to your inquiries and fulfill your requests.<\\/li><li>Improve the Website and your user experience.<\\/li><li>Personalize your experience on the Website based on your browsing habits.<\\/li><li>Analyze how the Website is used to improve our services.<\\/li><li>Comply with legal and regulatory requirements.<\\/li><\\/ul><br style=\\\"color:rgb(33,37,41);font-family:Poppins, sans-serif;font-size:16px;font-weight:400;\\\" \\/><h4>Sharing of Information<\\/h4><h4 class=\\\"title\\\" style=\\\"margin-bottom:15px;font-weight:600;line-height:1.2;font-size:24px;color:rgb(66,66,72);font-family:Georama, sans-serif;\\\"><\\/h4><p style=\\\"font-family:Poppins, sans-serif;\\\">We may share your information with trusted third-party service providers who assist us in operating the Website and delivering our services. These providers are obligated by contract to keep your information confidential and use it only for the specific purposes we disclose it for.<\\/p><p style=\\\"font-family:Poppins, sans-serif;\\\">We will never share your personal information with any third parties for marketing purposes without your explicit consent.<\\/p><br style=\\\"color:rgb(33,37,41);font-family:Poppins, sans-serif;font-size:16px;font-weight:400;\\\" \\/><h4>Data Retention<\\/h4><h4 class=\\\"title\\\" style=\\\"margin-bottom:15px;font-weight:600;line-height:1.2;font-size:24px;color:rgb(66,66,72);font-family:Georama, sans-serif;\\\"><\\/h4><p style=\\\"font-family:Poppins, sans-serif;\\\">We retain your personal information only for as long as necessary to fulfill the purposes it was collected for. We may retain it for longer periods only if required or permitted by law.<\\/p><br style=\\\"color:rgb(33,37,41);font-family:Poppins, sans-serif;font-size:16px;font-weight:400;\\\" \\/><h4>Security Measures<\\/h4><h4 class=\\\"title\\\" style=\\\"margin-bottom:15px;font-weight:600;line-height:1.2;font-size:24px;color:rgb(66,66,72);font-family:Georama, sans-serif;\\\"><\\/h4><p style=\\\"font-family:Poppins, sans-serif;\\\">We take reasonable precautions to protect your information from unauthorized access, disclosure, alteration, or destruction. However, complete security cannot be guaranteed for any website or internet transmission.<\\/p><br style=\\\"color:rgb(33,37,41);font-family:Poppins, sans-serif;font-size:16px;font-weight:400;\\\" \\/><h4>Changes to this Privacy Policy<\\/h4><h4 class=\\\"title\\\" style=\\\"margin-bottom:15px;font-weight:600;line-height:1.2;font-size:24px;color:rgb(66,66,72);font-family:Georama, sans-serif;\\\"><\\/h4><p style=\\\"font-family:Poppins, sans-serif;\\\">We may update this Privacy Policy periodically. We will notify you of any changes by posting the revised policy on the Website. We recommend reviewing this policy regularly to stay informed of any updates.<\\/p><p style=\\\"font-family:Poppins, sans-serif;\\\"><span style=\\\"font-weight:bolder;\\\">Remember:<\\/span>\\u00a0This is a sample policy and may need adjustments to comply with specific laws and reflect your website\'s unique data practices. Consider consulting with a legal professional to ensure your policy is fully compliant.<\\/p>\"}', NULL, 'basic', 'privacy-policy', '2021-06-09 08:50:42', '2024-07-26 06:54:45'),
(43, 'policy_pages.element', '{\"title\":\"Terms of Service\",\"details\":\"<h4>Introduction<\\/h4><h4><\\/h4><h4><\\/h4><p>This Privacy Policy describes how we collects, uses, and discloses information, including personal information, in connection with your use of our website.<\\/p><div style=\\\"color:rgb(33,37,41);font-size:16px;\\\"><br \\/><\\/div><h4>Information We Collect<\\/h4><h4><\\/h4><p>We collect two main types of information on the Website:<\\/p><ul style=\\\"color:rgb(33,37,41);font-size:16px;\\\"><li><p><span style=\\\"font-weight:bolder;\\\">Personal Information:\\u00a0<\\/span>This includes data that can identify you as an individual, such as your name, email address, phone number, or mailing address. We only collect this information when you voluntarily provide it to us, like signing up for a newsletter, contacting us through a form, or making a purchase.<\\/p><\\/li><li><p><span style=\\\"font-weight:bolder;\\\">Non-Personal Information:\\u00a0<\\/span>This data cannot be used to identify you directly. It includes details like your browser type, device type, operating system, IP address, browsing activity, and usage statistics. We collect this information automatically through cookies and other tracking technologies.<\\/p><\\/li><\\/ul><br style=\\\"color:rgb(33,37,41);font-size:16px;\\\" \\/><h4>How We Use Information<\\/h4><h4><\\/h4><p>The information we collect allows us to:<\\/p><ul style=\\\"color:rgb(33,37,41);font-size:16px;\\\"><li>Operate and maintain the Website effectively.<\\/li><li>Send you newsletters or marketing communications, but only with your consent.<\\/li><li>Respond to your inquiries and fulfill your requests.<\\/li><li>Improve the Website and your user experience.<\\/li><li>Personalize your experience on the Website based on your browsing habits.<\\/li><li>Analyze how the Website is used to improve our services.<\\/li><li>Comply with legal and regulatory requirements.<\\/li><\\/ul><br style=\\\"color:rgb(33,37,41);font-size:16px;\\\" \\/><h4>Sharing of Information<\\/h4><h4><\\/h4><p>We may share your information with trusted third-party service providers who assist us in operating the Website and delivering our services. These providers are obligated by contract to keep your information confidential and use it only for the specific purposes we disclose it for.<\\/p><p>We will never share your personal information with any third parties for marketing purposes without your explicit consent.<\\/p><br style=\\\"color:rgb(33,37,41);font-size:16px;\\\" \\/><h4>Data Retention<\\/h4><h4><\\/h4><p>We retain your personal information only for as long as necessary to fulfill the purposes it was collected for. We may retain it for longer periods only if required or permitted by law.<\\/p><br style=\\\"color:rgb(33,37,41);font-size:16px;\\\" \\/><h4>Security Measures<\\/h4><h4><\\/h4><p>We take reasonable precautions to protect your information from unauthorized access, disclosure, alteration, or destruction. However, complete security cannot be guaranteed for any website or internet transmission.<\\/p><br style=\\\"color:rgb(33,37,41);font-size:16px;\\\" \\/><h4>Changes to this Privacy Policy<\\/h4><h4><\\/h4><p>We may update this Privacy Policy periodically. We will notify you of any changes by posting the revised policy on the Website. We recommend reviewing this policy regularly to stay informed of any updates.<\\/p><p><span style=\\\"font-weight:bolder;\\\">Remember:<\\/span>\\u00a0This is a sample policy and may need adjustments to comply with specific laws and reflect your website\'s unique data practices. Consider consulting with a legal professional to ensure your policy is fully compliant.<\\/p>\"}', NULL, 'basic', 'terms-of-service', '2021-06-09 08:51:18', '2024-07-26 06:55:01'),
(44, 'maintenance.data', '{\"description\":\"<div class=\\\"mb-5\\\" style=\\\"font-family: Nunito, sans-serif; margin-bottom: 3rem !important;\\\"><h3 class=\\\"mb-3\\\" style=\\\"text-align: center; font-weight: 600; line-height: 1.3; font-size: 24px; font-family: Exo, sans-serif;\\\"><font color=\\\"#ff0000\\\">THE SITE IS UNDER MAINTENANCE<\\/font><\\/h3><p class=\\\"font-18\\\" style=\\\"color: rgb(111, 111, 111); text-align: center; margin-right: 0px; margin-left: 0px; font-size: 18px !important;\\\">We\'re just tuning up a few things.We apologize for the inconvenience but Front is currently undergoing planned maintenance. Thanks for your patience.<\\/p><\\/div>\",\"image\":\"6603c203472ad1711522307.png\"}', NULL, NULL, NULL, '2020-07-04 23:42:52', '2024-03-27 06:51:47'),
(64, 'banner.content', '{\"heading\":\"Get Your Ticket Online, Easy and Safely\",\"link_title\":\"Get ticket now\",\"link\":\"tickets\",\"has_image\":\"1\",\"background_image\":\"668688791bb031720092793.png\",\"animation_image\":\"66868879268741720092793.png\"}', NULL, 'basic', '', '2024-05-01 00:06:45', '2024-07-04 05:33:13'),
(66, 'register_disable.content', '{\"has_image\":\"1\",\"heading\":\"Registration Currently Disabled\",\"subheading\":\"Page you are looking for doesn\'t exit or an other error occurred or temporarily unavailable.\",\"button_name\":\"Go to Home\",\"button_url\":\"#\",\"image\":\"663a0f20ecd0b1715080992.png\"}', NULL, 'basic', '', '2024-05-07 05:23:12', '2024-05-07 05:28:09'),
(67, 'amenities.content', '{\"heading\":\"Our Amenities\",\"sub_heading\":\"Have a look at our popular reason. why you should choose you bus.Just choose a Bus and get a ticket for your great journey!\"}', NULL, 'basic', '', '2024-07-02 06:49:15', '2024-07-02 06:49:15'),
(68, 'amenities.element', '{\"title\":\"Wifi\",\"icon\":\"<i class=\\\"fas fa-wifi\\\"><\\/i>\"}', NULL, 'basic', '', '2024-07-02 06:49:35', '2024-07-02 06:49:35'),
(69, 'amenities.element', '{\"title\":\"Pillow\",\"icon\":\"<i class=\\\"las la-bed\\\"><\\/i>\"}', NULL, 'basic', '', '2024-07-02 06:49:51', '2024-07-02 06:49:51'),
(70, 'amenities.element', '{\"title\":\"Water Bottle\",\"icon\":\"<i class=\\\"las la-prescription-bottle\\\"><\\/i>\"}', NULL, 'basic', '', '2024-07-02 06:50:07', '2024-07-02 06:50:07'),
(71, 'amenities.element', '{\"title\":\"Soft Drinks\",\"icon\":\"<i class=\\\"fas fa-wine-glass-alt\\\"><\\/i>\"}', NULL, 'basic', '', '2024-07-02 06:50:23', '2024-07-02 06:50:23'),
(72, 'blog.content', '{\"heading\":\"Recent Blog Post\",\"subheading\":\"Have a look at our popular reason. why you should choose you bus. Just choose a Bus and get a ticket for your great journey. !\"}', NULL, 'basic', '', '2024-07-04 05:37:14', '2024-07-04 05:37:14'),
(79, 'breadcrumb.content', '{\"has_image\":\"1\",\"background_image\":\"66868cfc051ae1720093948.jpg\"}', NULL, 'basic', '', '2024-07-04 05:52:28', '2024-07-04 05:52:28'),
(80, 'contact.content', '{\"title\":\"Let\'s get in touch\",\"short_details\":\"We are open for any suggestion or just to have a chat\",\"address\":\"Bengla Road Suite Dhaka 1209\",\"contact_number\":\"+44 45678908\",\"email\":\"example@gmail.com\",\"latitude\":\"40.201023\",\"longitude\":\"-77.200272\",\"form_title\":\"Have any Questions?\"}', NULL, 'basic', '', '2024-07-04 05:53:48', '2024-07-04 05:53:48'),
(81, 'auth.content', '{\"has_image\":\"1\",\"signup_heading\":\"Totam nisi omnis aut\",\"signin_heading\":\"Non distinctio Null\",\"user_data_heading\":\"Vel perspiciatis im\",\"verify_email_heading\":\"tytopicuca@mailinator.com\",\"verify_phone_heading\":\"+1 (668) 313-7841\",\"reset_password_heading\":\"Sed aute nulla simil\",\"background_image\":\"66869002329471720094722.jpg\"}', NULL, 'basic', '', '2024-07-04 06:05:22', '2024-07-16 07:54:28'),
(82, 'faq.element', '{\"question\":\"Can we choose corporation buses from anywhere?\",\"answer\":\"Orangu ipsum dolor sit amet consectetur adipisicing elit. Sed minima porro aliquid eius laudantium ad quod a corrupti impedit, reiciendis iusto ut aperiam tenetur fuga repellendus tempore necessitatibus omnis libero?\"}', NULL, 'basic', '', '2024-07-04 06:05:57', '2024-07-04 06:05:57'),
(83, 'faq.element', '{\"question\":\"Can we choose corporation buses from anywhere?\",\"answer\":\"Orangu ipsum dolor sit amet consectetur adipisicing elit. Sed minima porro aliquid eius laudantium ad quod a corrupti impedit, reiciendis iusto ut aperiam tenetur fuga repellendus tempore necessitatibus omnis libero?\"}', NULL, 'basic', '', '2024-07-04 06:06:08', '2024-07-04 06:06:08'),
(84, 'faq.element', '{\"question\":\"Can we choose corporation buses from anywhere?\",\"answer\":\"Orangu ipsum dolor sit amet consectetur adipisicing elit. Sed minima porro aliquid eius laudantium ad quod a corrupti impedit, reiciendis iusto ut aperiam tenetur fuga repellendus tempore necessitatibus omnis libero?\"}', NULL, 'basic', '', '2024-07-04 06:06:20', '2024-07-04 06:06:20'),
(85, 'faq.element', '{\"question\":\"Why do we use it?\",\"answer\":\"It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution of letters, as opposed to using \'Content here, content here\', making it look like readable English. Many desktop publishing packages and web page editors now use Lorem Ipsum as their default model text, and a search for \'lorem ipsum\' will uncover many web sites still in their infancy. Various versions have evolved over the years, sometimes by accident, sometimes on purpose (injected humour and the like).\"}', NULL, 'basic', '', '2024-07-04 06:06:45', '2024-07-04 06:06:45'),
(86, 'faq.element', '{\"question\":\"What is Lorem Ipsum?\",\"answer\":\"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.\"}', NULL, 'basic', '', '2024-07-04 06:07:02', '2024-07-04 06:07:02'),
(87, 'footer.content', '{\"short_description\":\"Delectus culpa laboriosam debitis saepe. Commodi earum minus ut obcaecati veniam deserunt est!\"}', NULL, 'basic', '', '2024-07-04 06:07:39', '2024-07-04 06:07:39'),
(88, 'how_it_works.content', '{\"heading\":\"Get Your Tickets With Just 3 Steps\",\"sub_heading\":\"Have a look at our popular reason. why you should choose you bus. Just a Bus and get a ticket for your great journey. !\"}', NULL, 'basic', '', '2024-07-04 06:08:01', '2024-07-04 06:08:01'),
(89, 'how_it_works.element', '{\"heading\":\"Search Your Bus\",\"sub_heading\":\"Choose your origin, destination,Just choose a Bus journey dates and search for buses\",\"icon\":\"<i class=\\\"las la-search\\\"><\\/i>\"}', NULL, 'basic', '', '2024-07-04 06:08:24', '2024-07-04 06:08:24'),
(90, 'how_it_works.element', '{\"heading\":\"Choose The Ticket\",\"sub_heading\":\"Choose your origin, destination,Just a Bus for your great journey dates and search for buses\",\"icon\":\"<i class=\\\"las la-ticket-alt\\\"><\\/i>\"}', NULL, 'basic', '', '2024-07-04 06:08:41', '2024-07-04 06:08:41'),
(91, 'how_it_works.element', '{\"heading\":\"Pay Bill\",\"sub_heading\":\"Choose your origin, destination,choose a Bus for your great journey dates and search for buses\",\"icon\":\"<i class=\\\"las la-money-bill-wave-alt\\\"><\\/i>\"}', NULL, 'basic', '', '2024-07-04 06:08:58', '2024-07-04 06:08:58'),
(92, 'social_links.element', '{\"name\":\"Facebook\",\"icon\":\"<i class=\\\"lab la-facebook-f\\\"><\\/i>\",\"url\":\"https:\\/\\/www.facebook.com\\/\"}', NULL, 'basic', '', '2024-07-04 06:09:46', '2024-07-04 06:09:46'),
(93, 'social_links.element', '{\"name\":\"Twitter\",\"icon\":\"<i class=\\\"lab la-twitter\\\"><\\/i>\",\"url\":\"https:\\/\\/twitter.com\\/?lang=en\"}', NULL, 'basic', '', '2024-07-04 06:10:07', '2024-07-04 06:10:07'),
(94, 'social_links.element', '{\"name\":\"Vimeo\",\"icon\":\"<i class=\\\"lab la-vimeo\\\"><\\/i>\",\"url\":\"https:\\/\\/vimeo.com\\/log_in\"}', NULL, 'basic', '', '2024-07-04 06:11:02', '2024-07-04 06:11:02'),
(95, 'social_links.element', '{\"name\":\"Instagram\",\"icon\":\"<i class=\\\"lab la-instagram\\\"><\\/i>\",\"url\":\"https:\\/\\/www.instagram.com\\/?hl=en\"}', NULL, 'basic', '', '2024-07-04 06:11:20', '2024-07-04 06:11:20'),
(96, 'testimonials.content', '{\"heading\":\"Our Testimonials\",\"sub_heading\":\"Have a look at our popular reason. why you should choose you bus. Just choose a Bus and get a ticket for your great journey!\"}', NULL, 'basic', '', '2024-07-04 06:11:47', '2024-07-04 06:11:47'),
(97, 'testimonials.element', '{\"has_image\":\"1\",\"person\":\"Sarmin Sultana\",\"description\":\"Lorem ipsum dolor sit amet consectetur adipisicing elit. Ullam iusto mollitia facere accusantium deleniti odit eius, amet doloribus fugit delectus doloremque! In, corrupti? Est, autem suscipit voluptatem rerum deserunt laudantium.\",\"image\":\"6686923c85ee71720095292.jpg\"}', NULL, 'basic', '', '2024-07-04 06:14:52', '2024-07-04 06:14:52'),
(98, 'testimonials.element', '{\"has_image\":\"1\",\"person\":\"Parvin Akter\",\"description\":\"Lorem ipsum dolor sit amet consectetur adipisicing elit. Ullam iusto mollitia facere accusantium deleniti odit eius, amet doloribus fugit delectus doloremque! In, corrupti? Est, autem suscipit voluptatem rerum deserunt laudantium.\",\"image\":\"66869267d57711720095335.jpg\"}', NULL, 'basic', '', '2024-07-04 06:15:35', '2024-07-04 06:15:35'),
(99, 'cookie.data', '{\"short_desc\":\"We may use cookies or any other tracking technologies when you visit our website, including any other media form, mobile website, or mobile application related or connected to help customize the Site and improve your experience.\",\"description\":\"<h4>Cookie Policy<\\/h4>\\r\\n\\r\\n<p>This Cookie Policy explains how to use cookies and similar technologies to recognize you when you visit our website. It explains what these technologies are and why we use them, as well as your rights to control our use of them.<\\/p>\\r\\n<br>\\r\\n<h4>What are cookies?<\\/h4>\\r\\n\\r\\n<p>Cookies are small pieces of data stored on your computer or mobile device when you visit a website. Cookies are widely used by website owners to make their websites work, or to work more efficiently, as well as to provide reporting information.<\\/p>\\r\\n<br>\\r\\n<h4>Why do we use cookies?<\\/h4>\\r\\n\\r\\n<p>We use cookies for several reasons. Some cookies are required for technical reasons for our Website to operate, and we refer to these as \\\"essential\\\" or \\\"strictly necessary\\\" cookies. Other cookies enable us to track and target the interests of our users to enhance the experience on our Website. Third parties serve cookies through our Website for advertising, analytics, and other purposes.<\\/p>\\r\\n<br>\\r\\n<h4>What types of cookies do we use?<\\/h4>\\r\\n\\r\\n<div>\\r\\n    <ul style=\\\"list-style: unset;\\\">\\r\\n        <li>\\r\\n            <strong>Essential Website Cookies:<\\/strong> \\r\\n            These cookies are strictly necessary to provide you with services available through our Website and to use some of its features.\\r\\n        <\\/li>\\r\\n        <li>\\r\\n            <strong>Analytics and Performance Cookies:<\\/strong> \\r\\n            These cookies allow us to count visits and traffic sources to measure and improve our Website\'s performance.\\r\\n        <\\/li>\\r\\n        <li>\\r\\n            <strong>Advertising Cookies:<\\/strong> \\r\\n            These cookies make advertising messages more relevant to you and your interests. They perform functions like preventing the same ad from continuously reappearing, ensuring that ads are properly displayed, and in some cases selecting advertisements that are based on your interests.\\r\\n        <\\/li>\\r\\n    <\\/ul>\\r\\n<\\/div>\\r\\n<br>\\r\\n<h4>Data Collected by Cookies<\\/h4>\\r\\n<p>Cookies may collect various types of data, including but not limited to:<\\/p>\\r\\n<ul style=\\\"list-style: unset;\\\">\\r\\n    <li>IP addresses<\\/li>\\r\\n    <li>Browser and device information<\\/li>\\r\\n    <li>Referring website addresses<\\/li>\\r\\n    <li>Pages visited on our website<\\/li>\\r\\n    <li>Interactions with our website, such as clicks and mouse movements<\\/li>\\r\\n    <li>Time spent on our website<\\/li>\\r\\n<\\/ul>\\r\\n<br>\\r\\n<h4>How We Use Collected Data<\\/h4>\\r\\n\\r\\n<p>We may use data collected by cookies for the following purposes:<\\/p>\\r\\n<ul style=\\\"list-style: unset;\\\">\\r\\n    <li>To personalize your experience on our website<\\/li>\\r\\n    <li>To improve our website\'s functionality and performance<\\/li>\\r\\n    <li>To analyze trends and gather demographic information about our user base<\\/li>\\r\\n    <li>To deliver targeted advertising based on your interests<\\/li>\\r\\n    <li>To prevent fraudulent activity and enhance website security<\\/li>\\r\\n<\\/ul>\\r\\n<br>\\r\\n<h4>Third-party cookies<\\/h4>\\r\\n\\r\\n<p>In addition to our cookies, we may also use various third-party cookies to report usage statistics of our Website, deliver advertisements on and through our Website, and so on.<\\/p>\\r\\n<br>\\r\\n<h4>How can we control cookies?<\\/h4>\\r\\n\\r\\n<p>You have the right to decide whether to accept or reject cookies. You can exercise your cookie preferences by clicking on the \\\"Cookie Settings\\\" link in the footer of our website. You can also set or amend your web browser controls to accept or refuse cookies. If you choose to reject cookies, you may still use our Website though your access to some functionality and areas of our Website may be restricted.<\\/p>\\r\\n<br>\\r\\n<h4>Changes to our Cookie Policy<\\/h4>\\r\\n\\r\\n<p>We may update our Cookie Policy from time to time. We will notify you of any changes by posting the new Cookie Policy on this page.<\\/p>\",\"status\":1}', NULL, NULL, NULL, NULL, NULL),
(100, 'faq.content', '{\"heading\":\"Frequently Asked Questions\",\"sub_heading\":\"Nobis minus earum perferendis nemo cupiditate optio, rem neque incidunt quia laborum ut praesentium corporis quam exercitationem, atque illo aut excepturi cum.\"}', NULL, 'basic', '', '2024-07-04 06:50:15', '2024-07-04 06:50:15'),
(101, 'sign_in.content', '{\"has_image\":\"1\",\"heading\":\"Welcome to Bus Booking\",\"sub_heading\":\"Sign In your Account\",\"background_image\":\"6686a2e20d8421720099554.jpg\"}', NULL, 'basic', '', '2024-07-04 07:25:54', '2024-07-04 07:25:54'),
(102, 'sign_up.content', '{\"has_image\":\"1\",\"heading\":\"Welcome to Bus Booking\",\"sub_heading\":\"Sign Up your Account\",\"background_image\":\"6686a396adb0a1720099734.jpg\"}', NULL, 'basic', '', '2024-07-04 07:28:54', '2024-07-04 07:28:55'),
(104, 'blog.element', '{\"has_image\":[\"1\"],\"title\":\"What to do if your bus get demaged?\",\"description\":\"<h4 class=\\\"title\\\" style=\\\"margin-bottom:25px;font-weight:600;line-height:1.2;font-size:24px;color:rgb(66,66,72);font-family:Georama, sans-serif;\\\"><span style=\\\"color:rgb(119,119,119);font-family:Lato, sans-serif;font-size:16px;font-weight:400;\\\">In most legal cases there are one or more accusers and one or more defendants. A legal case is typically based on either civil or criminal law. In most legal cases there are one or mor<\\/span><br \\/><\\/h4><p class=\\\"blog-details-pera\\\" style=\\\"margin-right:0px;margin-bottom:20px;margin-left:0px;padding:0px;color:rgb(119,119,119);font-family:Lato, sans-serif;font-size:16px;\\\">A consectetur adipisicing elit. Debitis quidem, architecto nulla tempore modi, aliquam sunt corporis beatae ipsam quia sed quae odit adipisci tempora repellendus explicabo voluptate labore minus?<\\/p><p class=\\\"blog-details-pera\\\" style=\\\"margin-right:0px;margin-bottom:20px;margin-left:0px;padding:0px;color:rgb(119,119,119);font-family:Lato, sans-serif;font-size:16px;\\\">A consectetur adipisicing elit. Debitis quidem, architecto nulla tempore modi, aliquam sunt corporis beatae ipsam quia sed quae odit adipisci tempora repellendus explicabo voluptate labore minus?<\\/p><p class=\\\"blog-details-pera\\\" style=\\\"margin-right:0px;margin-bottom:20px;margin-left:0px;padding:0px;color:rgb(119,119,119);font-family:Lato, sans-serif;font-size:16px;\\\">A consectetur adipisicing elit. Lorem ipsum dolor sit amet consectetur adipisicing elit. Error ipsa incidunt dolores est doloremque quae numquam consequuntur, rerum, earum ipsam ad aperiam, pariatur soluta accusantium nesciunt aliquid voluptatem temporibus magnam.lorem Lorem ipsum dolor sit amet consectetur adipisicing elit. Debitis magnam, provident atque et est perferendis eum rem voluptas reprehenderit, sed dolor eaque itaque dicta nam fugit. Molestiae alias consequatur nostrum? Debitis quidem, architecto nulla tempore modi, aliquam sunt corporis beatae ipsam quia sed quae odit adipisci tempora repellendus explicabo voluptate labore minus?<\\/p><ul class=\\\"info\\\" style=\\\"margin-top:-7px;margin-bottom:-7px;color:rgb(119,119,119);font-family:Lato, sans-serif;\\\"><li style=\\\"list-style:none;padding:7px 0px 7px 20px;\\\">Some people do not understand why you should have to<\\/li><li style=\\\"list-style:none;padding:7px 0px 7px 20px;\\\">tempora repellendus explicabo voluptate labore minus?<\\/li><li style=\\\"list-style:none;padding:7px 0px 7px 20px;\\\">A consectetur adipisicing elit. Debitis quidem,<\\/li><\\/ul>\",\"image\":\"66a339ea956751721973226.png\"}', NULL, 'basic', 'what-to-do-if-your-bus-get-demaged', '2024-07-26 05:43:24', '2024-07-26 05:53:46'),
(105, 'blog.element', '{\"has_image\":[\"1\"],\"title\":\"A consectetur adipisicing elit.\",\"description\":\"<h4 class=\\\"title\\\" style=\\\"margin-bottom:25px;font-weight:600;line-height:1.2;font-size:24px;color:rgb(66,66,72);font-family:Georama, sans-serif;\\\"><span style=\\\"color:rgb(119,119,119);font-family:Lato, sans-serif;font-size:16px;font-weight:400;\\\">In most legal cases there are one or more accusers and one or more defendants. A legal case is typically based on either civil or criminal law. In most legal cases there are one or mor<\\/span><br \\/><\\/h4><p class=\\\"blog-details-pera\\\" style=\\\"margin-right:0px;margin-bottom:20px;margin-left:0px;color:rgb(119,119,119);font-size:16px;padding:0px;font-family:Lato, sans-serif;\\\">A consectetur adipisicing elit. Debitis quidem, architecto nulla tempore modi, aliquam sunt corporis beatae ipsam quia sed quae odit adipisci tempora repellendus explicabo voluptate labore minus?<\\/p><p class=\\\"blog-details-pera\\\" style=\\\"margin-right:0px;margin-bottom:20px;margin-left:0px;color:rgb(119,119,119);font-size:16px;padding:0px;font-family:Lato, sans-serif;\\\">A consectetur adipisicing elit. Debitis quidem, architecto nulla tempore modi, aliquam sunt corporis beatae ipsam quia sed quae odit adipisci tempora repellendus explicabo voluptate labore minus?<\\/p><p class=\\\"blog-details-pera\\\" style=\\\"margin-right:0px;margin-bottom:20px;margin-left:0px;color:rgb(119,119,119);font-size:16px;padding:0px;font-family:Lato, sans-serif;\\\">A consectetur adipisicing elit. Lorem ipsum dolor sit amet consectetur adipisicing elit. Error ipsa incidunt dolores est doloremque quae numquam consequuntur, rerum, earum ipsam ad aperiam, pariatur soluta accusantium nesciunt aliquid voluptatem temporibus magnam.lorem Lorem ipsum dolor sit amet consectetur adipisicing elit. Debitis magnam, provident atque et est perferendis eum rem voluptas reprehenderit, sed dolor eaque itaque dicta nam fugit. Molestiae alias consequatur nostrum? Debitis quidem, architecto nulla tempore modi, aliquam sunt corporis beatae ipsam quia sed quae odit adipisci tempora repellendus explicabo voluptate labore minus?<\\/p><ul class=\\\"info\\\" style=\\\"margin-top:-7px;margin-bottom:-7px;color:rgb(119,119,119);font-family:Lato, sans-serif;\\\"><li style=\\\"list-style:none;padding:7px 0px 7px 20px;\\\">Some people do not understand why you should have to<\\/li><li style=\\\"list-style:none;padding:7px 0px 7px 20px;\\\">tempora repellendus explicabo voluptate labore minus?<\\/li><li style=\\\"list-style:none;padding:7px 0px 7px 20px;\\\">A consectetur adipisicing elit. Debitis quidem,<\\/li><\\/ul>\",\"image\":\"66a339e0c074b1721973216.png\"}', NULL, 'basic', 'a-consectetur-adipisicing-elit', '2024-07-26 05:46:41', '2024-07-26 05:53:37'),
(106, 'blog.element', '{\"has_image\":[\"1\"],\"title\":\"A wonderful bus journy\",\"description\":\"<p class=\\\"blog-details-pera\\\" style=\\\"margin-right:0px;margin-bottom:20px;margin-left:0px;color:rgb(119,119,119);font-size:16px;padding:0px;font-family:Lato, sans-serif;\\\">In most legal cases there are one or more accusers and one or more defendants. A legal case is typically based on either civil or criminal law. In most legal cases there are one or mor<\\/p><p class=\\\"blog-details-pera\\\" style=\\\"margin-right:0px;margin-bottom:20px;margin-left:0px;color:rgb(119,119,119);font-size:16px;padding:0px;font-family:Lato, sans-serif;\\\">A consectetur adipisicing elit. Debitis quidem, architecto nulla tempore modi, aliquam sunt corporis beatae ipsam quia sed quae odit adipisci tempora repellendus explicabo voluptate labore minus?<\\/p><p class=\\\"blog-details-pera\\\" style=\\\"margin-right:0px;margin-bottom:20px;margin-left:0px;color:rgb(119,119,119);font-size:16px;padding:0px;font-family:Lato, sans-serif;\\\">A consectetur adipisicing elit. Debitis quidem, architecto nulla tempore modi, aliquam sunt corporis beatae ipsam quia sed quae odit adipisci tempora repellendus explicabo voluptate labore minus?<\\/p><p class=\\\"blog-details-pera\\\" style=\\\"margin-right:0px;margin-bottom:20px;margin-left:0px;color:rgb(119,119,119);font-size:16px;padding:0px;font-family:Lato, sans-serif;\\\">A consectetur adipisicing elit. Lorem ipsum dolor sit amet consectetur adipisicing elit. Error ipsa incidunt dolores est doloremque quae numquam consequuntur, rerum, earum ipsam ad aperiam, pariatur soluta accusantium nesciunt aliquid voluptatem temporibus magnam.lorem Lorem ipsum dolor sit amet consectetur adipisicing elit. Debitis magnam, provident atque et est perferendis eum rem voluptas reprehenderit, sed dolor eaque itaque dicta nam fugit. Molestiae alias consequatur nostrum? Debitis quidem, architecto nulla tempore modi, aliquam sunt corporis beatae ipsam quia sed quae odit adipisci tempora repellendus explicabo voluptate labore minus?<\\/p><ul class=\\\"info\\\" style=\\\"margin-top:-7px;margin-bottom:-7px;color:rgb(119,119,119);font-family:Lato, sans-serif;\\\"><li style=\\\"list-style:none;padding:7px 0px 7px 20px;\\\">Some people do not understand why you should have to<\\/li><li style=\\\"list-style:none;padding:7px 0px 7px 20px;\\\">tempora repellendus explicabo voluptate labore minus?<\\/li><li style=\\\"list-style:none;padding:7px 0px 7px 20px;\\\">A consectetur adipisicing elit. Debitis quidem,<\\/li><\\/ul><div class=\\\"quote-wrapper\\\" style=\\\"margin-top:20px;margin-bottom:20px;padding:25px 20px;background:rgba(27,39,61,0.03);color:rgb(119,119,119);font-family:Lato, sans-serif;\\\"><p style=\\\"margin-right:0px;margin-left:0px;padding:0px 0px 0px 10px;border-left:2px solid rgb(14,158,77);\\\"><span class=\\\"las la-quote-left\\\" style=\\\"color:rgb(14,158,77);font-size:46px;\\\"><\\/span>\\u00a0Lorem ipsum dolor sit amet consectetur adipisicing elit. Iusto veritatis quos aspernatur facere officiis. Odit, maiores voluptatum alias eaque exercitationem perspiciatis beatae soluta explicabo! Doloribus a saepe molestiae, minima unde consectetur ipsum non possimus quo corrupti id illum earum architecto veniam? Magni labore nesciunt<\\/p><\\/div>\",\"image\":\"66a339d6367861721973206.png\"}', NULL, 'basic', 'a-wonderful-bus-journy', '2024-07-26 05:47:48', '2024-07-26 05:53:26'),
(107, 'blog.element', '{\"has_image\":[\"1\"],\"title\":\"Why buy tickets from us?\",\"description\":\"<h2 style=\\\"margin-bottom:10px;line-height:24px;font-size:24px;color:rgb(0,0,0);padding:0px;font-family:DauphinPlain;\\\"><span style=\\\"color:rgb(0,0,0);font-family:\'Open Sans\', Arial, sans-serif;font-size:14px;text-align:justify;\\\">Contrary to popular belief, Lorem Ipsum is not simply random text. It has roots in a piece of classical Latin literature from 45 BC, making it over 2000 years old. Richard McClintock, a Latin professor at Hampden-Sydney College in Virginia, looked up one of the more obscure Latin words, consectetur, from a Lorem Ipsum passage, and going through the cites of the word in classical literature, discovered the undoubtable source. Lorem Ipsum comes from sections 1.10.32 and 1.10.33 of \\\"de Finibus Bonorum et Malorum\\\" (The Extremes of Good and Evil) by Cicero, written in 45 BC. This book is a treatise on the theory of ethics, very popular during the Renaissance. The first line of Lorem Ipsum, \\\"Lorem ipsum dolor sit amet..\\\", comes from a line in section 1.10.32.<\\/span><br \\/><\\/h2><p style=\\\"margin-right:0px;margin-bottom:15px;margin-left:0px;color:rgb(0,0,0);font-size:14px;padding:0px;text-align:justify;font-family:\'Open Sans\', Arial, sans-serif;\\\">The standard chunk of Lorem Ipsum used since the 1500s is reproduced below for those interested. Sections 1.10.32 and 1.10.33 from \\\"de Finibus Bonorum et Malorum\\\" by Cicero are also reproduced in their exact original form, accompanied by English versions from the 1914 translation by H. Rackham.<\\/p>\",\"image\":\"66a339cd0f53e1721973197.png\"}', NULL, 'basic', 'why-buy-tickets-from-us', '2024-07-26 05:48:18', '2024-07-26 05:53:17'),
(108, 'blog.element', '{\"has_image\":[\"1\"],\"title\":\"Where can I get some?\",\"description\":\"<span style=\\\"color:rgb(0,0,0);font-family:\'Open Sans\', Arial, sans-serif;font-size:14px;text-align:justify;\\\">There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour, or randomised words which don\'t look even slightly believable. If you are going to use a passage of Lorem Ipsum, you need to be sure there isn\'t anything embarrassing hidden in the middle of text. All the Lorem Ipsum generators on the Internet tend to repeat predefined chunks as necessary, making this the first true generator on the Internet. It uses a dictionary of over 200 Latin words, combined with a handful of model sentence structures, to generate Lorem Ipsum which looks reasonable. The generated Lorem Ipsum is therefore always free from repetition, injected humour, or non-characteristic words etc.<\\/span><br \\/>\",\"image\":\"66a339c07d8231721973184.png\"}', NULL, 'basic', 'where-can-i-get-some', '2024-07-26 05:48:55', '2024-07-26 05:53:04'),
(109, 'blog.element', '{\"has_image\":[\"1\"],\"title\":\"Why do we use it?\",\"description\":\"<p style=\\\"margin-right:0px;margin-bottom:15px;margin-left:0px;color:rgb(0,0,0);font-size:14px;padding:0px;text-align:justify;font-family:\'Open Sans\', Arial, sans-serif;\\\">Contrary to popular belief, Lorem Ipsum is not simply random text. It has roots in a piece of classical Latin literature from 45 BC, making it over 2000 years old. Richard McClintock, a Latin professor at Hampden-Sydney College in Virginia, looked up one of the more obscure Latin words, consectetur, from a Lorem Ipsum passage, and going through the cites of the word in classical literature, discovered the undoubtable source. Lorem Ipsum comes from sections 1.10.32 and 1.10.33 of \\\"de Finibus Bonorum et Malorum\\\" (The Extremes of Good and Evil) by Cicero, written in 45 BC. This book is a treatise on the theory of ethics, very popular during the Renaissance. The first line of Lorem Ipsum, \\\"Lorem ipsum dolor sit amet..\\\", comes from a line in section 1.10.32.<\\/p><p style=\\\"margin-right:0px;margin-bottom:15px;margin-left:0px;color:rgb(0,0,0);font-size:14px;padding:0px;text-align:justify;font-family:\'Open Sans\', Arial, sans-serif;\\\">The standard chunk of Lorem Ipsum used since the 1500s is reproduced below for those interested. Sections 1.10.32 and 1.10.33 from \\\"de Finibus Bonorum et Malorum\\\" by Cicero are also reproduced in their exact original form, accompanied by English versions from the 1914 translation by H. Rackham.<\\/p>\",\"image\":\"66a339b749cad1721973175.png\"}', NULL, 'basic', 'why-do-we-use-it', '2024-07-26 05:49:38', '2024-07-26 05:52:55'),
(110, 'blog.element', '{\"has_image\":[\"1\"],\"title\":\"The standard Lorem Ipsum passage, used since the 1500s\",\"description\":\"<h3 style=\\\"margin-top:15px;margin-bottom:15px;font-weight:700;font-size:14px;color:rgb(0,0,0);padding:0px;font-family:\'Open Sans\', Arial, sans-serif;\\\"><span style=\\\"color:rgb(0,0,0);font-weight:400;text-align:justify;\\\">\\\"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\\\"<\\/span><br \\/><\\/h3><h3 style=\\\"margin-top:15px;margin-bottom:15px;font-weight:700;font-size:14px;color:rgb(0,0,0);padding:0px;font-family:\'Open Sans\', Arial, sans-serif;\\\">Section 1.10.32 of \\\"de Finibus Bonorum et Malorum\\\", written by Cicero in 45 BC<\\/h3><p style=\\\"margin-right:0px;margin-bottom:15px;margin-left:0px;color:rgb(0,0,0);font-size:14px;padding:0px;text-align:justify;font-family:\'Open Sans\', Arial, sans-serif;\\\">\\\"Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem. Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur? Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur?\\\"<\\/p>\",\"image\":\"66a3399ae9b3b1721973146.png\"}', NULL, 'basic', 'the-standard-lorem-ipsum-passage-used-since-the-1500s', '2024-07-26 05:50:42', '2024-07-26 05:52:27'),
(111, 'blog.element', '{\"has_image\":[\"1\"],\"title\":\"Lorem Ipsum is simply dummy\",\"description\":\"<span style=\\\"font-weight:bolder;margin-top:0px;margin-right:0px;margin-left:0px;padding:0px;color:rgb(0,0,0);font-family:\'Open Sans\', Arial, sans-serif;font-size:14px;text-align:justify;\\\">Lorem Ipsum<\\/span><span style=\\\"color:rgb(0,0,0);font-family:\'Open Sans\', Arial, sans-serif;font-size:14px;text-align:justify;\\\">\\u00a0is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.<\\/span><br \\/>\",\"image\":\"66a339934b50c1721973139.png\"}', NULL, 'basic', 'lorem-ipsum-is-simply-dummy', '2024-07-26 05:51:18', '2024-07-26 05:52:19'),
(112, 'policy_pages.element', '{\"title\":\"Ticket Policies\",\"details\":\"<p style=\\\"margin-right:0px;margin-bottom:15px;margin-left:0px;color:rgb(0,0,0);font-size:14px;padding:0px;text-align:justify;font-family:\'Open Sans\', Arial, sans-serif;\\\">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Duis sem eros, luctus at sem id, commodo maximus mi. In finibus ac mi vitae gravida. Cras hendrerit sit amet quam vel vehicula. Sed id malesuada ante. Etiam vel diam ligula. Nam malesuada nisi sit amet tempor ultricies. Pellentesque felis sapien, fermentum nec tortor nec, consectetur scelerisque risus. Maecenas et mauris odio. Etiam finibus ex vel laoreet semper. Phasellus eget mauris a elit lacinia condimentum. Sed vitae consectetur nibh.<\\/p><p style=\\\"margin-right:0px;margin-bottom:15px;margin-left:0px;color:rgb(0,0,0);font-size:14px;padding:0px;text-align:justify;font-family:\'Open Sans\', Arial, sans-serif;\\\">Donec id dui in nibh pellentesque congue. Cras sit amet dictum nisi, a cursus dui. Pellentesque ac hendrerit lacus. Integer faucibus velit et mauris porta, sit amet mollis turpis maximus. Mauris eu semper augue. Curabitur bibendum tellus nec pellentesque elementum. In imperdiet efficitur volutpat. Sed lobortis ultrices eros. Cras efficitur purus vel velit molestie, a blandit metus porta. Fusce nec diam lectus. In iaculis ante consequat dolor vehicula facilisis. Suspendisse gravida turpis vitae risus dignissim, id finibus lacus maximus. Aliquam urna orci, fermentum ac placerat quis, elementum ut dolor.<\\/p>\"}', NULL, 'basic', 'ticket-policies', '2024-07-26 06:55:15', '2024-07-26 06:55:15');
INSERT INTO `frontends` (`id`, `data_keys`, `data_values`, `seo_content`, `tempname`, `slug`, `created_at`, `updated_at`) VALUES
(113, 'policy_pages.element', '{\"title\":\"Refund Policy\",\"details\":\"<h4 class=\\\"title\\\" style=\\\"margin-bottom:15px;font-weight:600;line-height:1.2;font-size:24px;color:rgb(66,66,72);font-family:Georama, sans-serif;\\\">Overview<\\/h4><h4 class=\\\"title\\\" style=\\\"margin-bottom:15px;font-weight:600;line-height:1.2;font-size:24px;color:rgb(66,66,72);font-family:Georama, sans-serif;\\\"><\\/h4><div class=\\\"content-item\\\" style=\\\"margin-bottom:40px;color:rgb(119,119,119);font-family:Lato, sans-serif;\\\"><p style=\\\"margin-right:0px;margin-left:0px;padding:0px;\\\">Lorem ipsum dolor sit, amet consectetur adipisicing elit. Assumenda officia vel omnis, odit quidem, expedita nam deserunt molestiae accusamus voluptas aut. Sapiente voluptatem nulla unde quia harum illum ipsum dolore! Lorem ipsum dolor sit amet consectetur adipisicing elit. In dolorem illum molestiae corrupti, maxime sint velit quibusdam officiis ipsam a minima quos voluptates possimus eaque, vitae, veniam consequuntur! Dolorem, architecto.<\\/p><\\/div><h4 class=\\\"title\\\" style=\\\"margin-bottom:15px;font-weight:600;line-height:1.2;font-size:24px;color:rgb(66,66,72);font-family:Georama, sans-serif;\\\">Data Collection & Use<\\/h4><h4 class=\\\"title\\\" style=\\\"margin-bottom:15px;font-weight:600;line-height:1.2;font-size:24px;color:rgb(66,66,72);font-family:Georama, sans-serif;\\\"><\\/h4><div class=\\\"content-item\\\" style=\\\"margin-bottom:40px;color:rgb(119,119,119);font-family:Lato, sans-serif;\\\"><p style=\\\"margin-right:0px;margin-left:0px;padding:0px;\\\">Lorem ipsum dolor sit, amet consectetur adipisicing elit. Assumenda officia vel omnis, odit quidem, expedita nam deserunt molestiae accusamus voluptas aut. Sapiente voluptatem nulla unde quia harum illum ipsum dolore! Lorem ipsum dolor sit amet consectetur adipisicing elit. In dolorem illum molestiae corrupti, maxime sint velit quibusdam officiis ipsam a minima quos voluptates possimus eaque, vitae, veniam consequuntur! Dolorem, architecto.<\\/p><ul class=\\\"info-list\\\" style=\\\"margin-top:15px;margin-bottom:-7px;\\\"><li style=\\\"list-style:none;padding:7px 0px 7px 25px;\\\">There are many variations of passages of Lorem available, but the majority<\\/li><li style=\\\"list-style:none;padding:7px 0px 7px 25px;\\\">Finibus onorum et alorum\\\" by icero classical lite rature, discovered<\\/li><li style=\\\"list-style:none;padding:7px 0px 7px 25px;\\\">There are many variations of passages of Lorem available, but the majority<\\/li><li style=\\\"list-style:none;padding:7px 0px 7px 25px;\\\">There are many variations of passages of Lorem available, but the majority<\\/li><\\/ul><\\/div><h4 class=\\\"title\\\" style=\\\"margin-bottom:15px;font-weight:600;line-height:1.2;font-size:24px;color:rgb(66,66,72);font-family:Georama, sans-serif;\\\">Cookies Data<\\/h4><h4 class=\\\"title\\\" style=\\\"margin-bottom:15px;font-weight:600;line-height:1.2;font-size:24px;color:rgb(66,66,72);font-family:Georama, sans-serif;\\\"><\\/h4><div class=\\\"content-item\\\" style=\\\"margin-bottom:40px;color:rgb(119,119,119);font-family:Lato, sans-serif;\\\"><p style=\\\"margin-right:0px;margin-left:0px;padding:0px;\\\">Lorem ipsum dolor sit, amet consectetur adipisicing elit. Assumenda officia vel omnis, odit quidem, expedita nam deserunt molestiae accusamus voluptas aut. Sapiente voluptatem nulla unde quia harum illum ipsum dolore! Lorem ipsum dolor sit amet consectetur adipisicing elit. In dolorem illum molestiae corrupti, maxime sint velit quibusdam officiis ipsam a minima quos voluptates possimus eaque, vitae, veniam consequuntur! Dolorem, architecto.<\\/p><\\/div><h4 class=\\\"title\\\" style=\\\"margin-bottom:15px;font-weight:600;line-height:1.2;font-size:24px;color:rgb(66,66,72);font-family:Georama, sans-serif;\\\">Data Collection & Use<\\/h4><h4 class=\\\"title\\\" style=\\\"margin-bottom:15px;font-weight:600;line-height:1.2;font-size:24px;color:rgb(66,66,72);font-family:Georama, sans-serif;\\\"><\\/h4><div class=\\\"content-item\\\" style=\\\"margin-bottom:40px;color:rgb(119,119,119);font-family:Lato, sans-serif;\\\"><p style=\\\"margin-right:0px;margin-left:0px;padding:0px;\\\">Lorem ipsum dolor sit, amet consectetur adipisicing elit. Assumenda officia vel omnis, odit quidem, expedita nam deserunt molestiae accusamus voluptas aut. Sapiente voluptatem nulla unde quia harum illum ipsum dolore! Lorem ipsum dolor sit amet consectetur adipisicing elit. In dolorem illum molestiae corrupti, maxime sint velit quibusdam officiis ipsam a minima quos voluptates possimus eaque, vitae, veniam consequuntur! Dolorem, architecto.<\\/p><\\/div><h4 class=\\\"title\\\" style=\\\"margin-bottom:15px;font-weight:600;line-height:1.2;font-size:24px;color:rgb(66,66,72);font-family:Georama, sans-serif;\\\">Data Cookies<\\/h4><h4 class=\\\"title\\\" style=\\\"margin-bottom:15px;font-weight:600;line-height:1.2;font-size:24px;color:rgb(66,66,72);font-family:Georama, sans-serif;\\\"><\\/h4><div class=\\\"content-item\\\" style=\\\"margin-bottom:40px;color:rgb(119,119,119);font-family:Lato, sans-serif;\\\"><p style=\\\"margin-right:0px;margin-left:0px;padding:0px;\\\">deserunt molestiae accusamus voluptas aut. Sapiente voluptatem nulla unde quia harum illum ipsum dolore! Lorem ipsum dolor sit amet consectetur adipisicing elit. In dolorem illum molestiae corrupti, maxime sint velit quibusdam officiis ipsam a minima quos voluptates possimus eaque, vitae, veniam consequuntur! Dolorem, architecto. Lorem ipsum dolor sit, amet consectetur adipisicing elit. Assumenda officia vel omnis, odit quidem, expedita nam deserunt molestiae accusamus voluptas aut. Sapiente voluptatem nulla unde quia harum illum ipsum dolore! Lorem ipsum dolor sit amet consectetur adipisicing elit. In dolorem illum molestiae corrupti, maxime sint velit quibusdam officiis ipsam a minima quos voluptates possimus eaque, vitae, veniam consequuntur! Dolorem, architecto.<\\/p><\\/div><h4 class=\\\"title\\\" style=\\\"margin-bottom:15px;font-weight:600;line-height:1.2;font-size:24px;color:rgb(66,66,72);font-family:Georama, sans-serif;\\\">Data Storage<\\/h4><h4 class=\\\"title\\\" style=\\\"margin-bottom:15px;font-weight:600;line-height:1.2;font-size:24px;color:rgb(66,66,72);font-family:Georama, sans-serif;\\\"><\\/h4><div class=\\\"content-item\\\" style=\\\"margin-bottom:40px;color:rgb(119,119,119);font-family:Lato, sans-serif;\\\"><p style=\\\"margin-right:0px;margin-left:0px;padding:0px;\\\">Pconsectetur adipisicing elit. Assumenda officia vel omnis, odit quidem, expedita nam deserunt molestiae accusamus voluptas aut. Sapiente voluptatem nulla unde quia harum illum ipsum dolore! Lorem ipsum dolor sit amet consectetur adipisicing elit. In dolorem illum molestiae corrupti, maxime sint velit quibusdam officiis ipsam a minima quos voluptates possimus eaque, vitae, veniam consequuntur! Dolorem, architecto.<\\/p><\\/div>\"}', NULL, 'basic', 'refund-policy', '2024-07-26 06:55:28', '2024-07-26 06:55:38');

-- --------------------------------------------------------

--
-- Table structure for table `gateways`
--

CREATE TABLE `gateways` (
  `id` bigint UNSIGNED NOT NULL,
  `form_id` int UNSIGNED NOT NULL DEFAULT '0',
  `code` int DEFAULT NULL,
  `name` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alias` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'NULL',
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=>enable, 2=>disable',
  `gateway_parameters` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `supported_currencies` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `crypto` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0: fiat currency, 1: crypto currency',
  `extra` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `gateways`
--

INSERT INTO `gateways` (`id`, `form_id`, `code`, `name`, `alias`, `image`, `status`, `gateway_parameters`, `supported_currencies`, `crypto`, `extra`, `description`, `created_at`, `updated_at`) VALUES
(1, 0, 101, 'Paypal', 'Paypal', '663a38d7b455d1715091671.png', 1, '{\"paypal_email\":{\"title\":\"PayPal Email\",\"global\":true,\"value\":\"sb-owud61543012@business.example.com\"}}', '{\"AUD\":\"AUD\",\"BRL\":\"BRL\",\"CAD\":\"CAD\",\"CZK\":\"CZK\",\"DKK\":\"DKK\",\"EUR\":\"EUR\",\"HKD\":\"HKD\",\"HUF\":\"HUF\",\"INR\":\"INR\",\"ILS\":\"ILS\",\"JPY\":\"JPY\",\"MYR\":\"MYR\",\"MXN\":\"MXN\",\"TWD\":\"TWD\",\"NZD\":\"NZD\",\"NOK\":\"NOK\",\"PHP\":\"PHP\",\"PLN\":\"PLN\",\"GBP\":\"GBP\",\"RUB\":\"RUB\",\"SGD\":\"SGD\",\"SEK\":\"SEK\",\"CHF\":\"CHF\",\"THB\":\"THB\",\"USD\":\"$\"}', 0, NULL, NULL, '2019-09-14 13:14:22', '2024-05-07 08:21:11'),
(2, 0, 102, 'Perfect Money', 'PerfectMoney', '663a3920e30a31715091744.png', 1, '{\"passphrase\":{\"title\":\"ALTERNATE PASSPHRASE\",\"global\":true,\"value\":\"hR26aw02Q1eEeUPSIfuwNypXX\"},\"wallet_id\":{\"title\":\"PM Wallet\",\"global\":false,\"value\":\"\"}}', '{\"USD\":\"$\",\"EUR\":\"\\u20ac\"}', 0, NULL, NULL, '2019-09-14 13:14:22', '2024-05-07 08:22:24'),
(3, 0, 103, 'Stripe Hosted', 'Stripe', '663a39861cb9d1715091846.png', 1, '{\"secret_key\":{\"title\":\"Secret Key\",\"global\":true,\"value\":\"sk_test_51I6GGiCGv1sRiQlEi5v1or9eR0HVbuzdMd2rW4n3DxC8UKfz66R4X6n4yYkzvI2LeAIuRU9H99ZpY7XCNFC9xMs500vBjZGkKG\"},\"publishable_key\":{\"title\":\"PUBLISHABLE KEY\",\"global\":true,\"value\":\"pk_test_51I6GGiCGv1sRiQlEOisPKrjBqQqqcFsw8mXNaZ2H2baN6R01NulFS7dKFji1NRRxuchoUTEDdB7ujKcyKYSVc0z500eth7otOM\"}}', '{\"USD\":\"USD\",\"AUD\":\"AUD\",\"BRL\":\"BRL\",\"CAD\":\"CAD\",\"CHF\":\"CHF\",\"DKK\":\"DKK\",\"EUR\":\"EUR\",\"GBP\":\"GBP\",\"HKD\":\"HKD\",\"INR\":\"INR\",\"JPY\":\"JPY\",\"MXN\":\"MXN\",\"MYR\":\"MYR\",\"NOK\":\"NOK\",\"NZD\":\"NZD\",\"PLN\":\"PLN\",\"SEK\":\"SEK\",\"SGD\":\"SGD\"}', 0, NULL, NULL, '2019-09-14 13:14:22', '2024-05-07 08:24:06'),
(4, 0, 104, 'Skrill', 'Skrill', '663a39494c4a91715091785.png', 1, '{\"pay_to_email\":{\"title\":\"Skrill Email\",\"global\":true,\"value\":\"merchant@skrill.com\"},\"secret_key\":{\"title\":\"Secret Key\",\"global\":true,\"value\":\"---\"}}', '{\"AED\":\"AED\",\"AUD\":\"AUD\",\"BGN\":\"BGN\",\"BHD\":\"BHD\",\"CAD\":\"CAD\",\"CHF\":\"CHF\",\"CZK\":\"CZK\",\"DKK\":\"DKK\",\"EUR\":\"EUR\",\"GBP\":\"GBP\",\"HKD\":\"HKD\",\"HRK\":\"HRK\",\"HUF\":\"HUF\",\"ILS\":\"ILS\",\"INR\":\"INR\",\"ISK\":\"ISK\",\"JOD\":\"JOD\",\"JPY\":\"JPY\",\"KRW\":\"KRW\",\"KWD\":\"KWD\",\"MAD\":\"MAD\",\"MYR\":\"MYR\",\"NOK\":\"NOK\",\"NZD\":\"NZD\",\"OMR\":\"OMR\",\"PLN\":\"PLN\",\"QAR\":\"QAR\",\"RON\":\"RON\",\"RSD\":\"RSD\",\"SAR\":\"SAR\",\"SEK\":\"SEK\",\"SGD\":\"SGD\",\"THB\":\"THB\",\"TND\":\"TND\",\"TRY\":\"TRY\",\"TWD\":\"TWD\",\"USD\":\"USD\",\"ZAR\":\"ZAR\",\"COP\":\"COP\"}', 0, NULL, NULL, '2019-09-14 13:14:22', '2024-05-07 08:23:05'),
(5, 0, 105, 'PayTM', 'Paytm', '663a390f601191715091727.png', 1, '{\"MID\":{\"title\":\"Merchant ID\",\"global\":true,\"value\":\"DIY12386817555501617\"},\"merchant_key\":{\"title\":\"Merchant Key\",\"global\":true,\"value\":\"bKMfNxPPf_QdZppa\"},\"WEBSITE\":{\"title\":\"Paytm Website\",\"global\":true,\"value\":\"DIYtestingweb\"},\"INDUSTRY_TYPE_ID\":{\"title\":\"Industry Type\",\"global\":true,\"value\":\"Retail\"},\"CHANNEL_ID\":{\"title\":\"CHANNEL ID\",\"global\":true,\"value\":\"WEB\"},\"transaction_url\":{\"title\":\"Transaction URL\",\"global\":true,\"value\":\"https:\\/\\/pguat.paytm.com\\/oltp-web\\/processTransaction\"},\"transaction_status_url\":{\"title\":\"Transaction STATUS URL\",\"global\":true,\"value\":\"https:\\/\\/pguat.paytm.com\\/paytmchecksum\\/paytmCallback.jsp\"}}', '{\"AUD\":\"AUD\",\"ARS\":\"ARS\",\"BDT\":\"BDT\",\"BRL\":\"BRL\",\"BGN\":\"BGN\",\"CAD\":\"CAD\",\"CLP\":\"CLP\",\"CNY\":\"CNY\",\"COP\":\"COP\",\"HRK\":\"HRK\",\"CZK\":\"CZK\",\"DKK\":\"DKK\",\"EGP\":\"EGP\",\"EUR\":\"EUR\",\"GEL\":\"GEL\",\"GHS\":\"GHS\",\"HKD\":\"HKD\",\"HUF\":\"HUF\",\"INR\":\"INR\",\"IDR\":\"IDR\",\"ILS\":\"ILS\",\"JPY\":\"JPY\",\"KES\":\"KES\",\"MYR\":\"MYR\",\"MXN\":\"MXN\",\"MAD\":\"MAD\",\"NPR\":\"NPR\",\"NZD\":\"NZD\",\"NGN\":\"NGN\",\"NOK\":\"NOK\",\"PKR\":\"PKR\",\"PEN\":\"PEN\",\"PHP\":\"PHP\",\"PLN\":\"PLN\",\"RON\":\"RON\",\"RUB\":\"RUB\",\"SGD\":\"SGD\",\"ZAR\":\"ZAR\",\"KRW\":\"KRW\",\"LKR\":\"LKR\",\"SEK\":\"SEK\",\"CHF\":\"CHF\",\"THB\":\"THB\",\"TRY\":\"TRY\",\"UGX\":\"UGX\",\"UAH\":\"UAH\",\"AED\":\"AED\",\"GBP\":\"GBP\",\"USD\":\"USD\",\"VND\":\"VND\",\"XOF\":\"XOF\"}', 0, NULL, NULL, '2019-09-14 13:14:22', '2024-05-07 08:22:07'),
(6, 0, 106, 'Payeer', 'Payeer', '663a38c9e2e931715091657.png', 1, '{\"merchant_id\":{\"title\":\"Merchant ID\",\"global\":true,\"value\":\"866989763\"},\"secret_key\":{\"title\":\"Secret key\",\"global\":true,\"value\":\"7575\"}}', '{\"USD\":\"USD\",\"EUR\":\"EUR\",\"RUB\":\"RUB\"}', 0, '{\"status\":{\"title\": \"Status URL\",\"value\":\"ipn.Payeer\"}}', NULL, '2019-09-14 13:14:22', '2024-05-07 08:20:57'),
(7, 0, 107, 'PayStack', 'Paystack', '663a38fc814e91715091708.png', 1, '{\"public_key\":{\"title\":\"Public key\",\"global\":true,\"value\":\"pk_test_cd330608eb47970889bca397ced55c1dd5ad3783\"},\"secret_key\":{\"title\":\"Secret key\",\"global\":true,\"value\":\"sk_test_8a0b1f199362d7acc9c390bff72c4e81f74e2ac3\"}}', '{\"USD\":\"USD\",\"NGN\":\"NGN\"}', 0, '{\"callback\":{\"title\": \"Callback URL\",\"value\":\"ipn.Paystack\"},\"webhook\":{\"title\": \"Webhook URL\",\"value\":\"ipn.Paystack\"}}\r\n', NULL, '2019-09-14 13:14:22', '2024-05-07 08:21:48'),
(9, 0, 109, 'Flutterwave', 'Flutterwave', '663a36c2c34d61715091138.png', 1, '{\"public_key\":{\"title\":\"Public Key\",\"global\":true,\"value\":\"----------------\"},\"secret_key\":{\"title\":\"Secret Key\",\"global\":true,\"value\":\"-----------------------\"},\"encryption_key\":{\"title\":\"Encryption Key\",\"global\":true,\"value\":\"------------------\"}}', '{\"BIF\":\"BIF\",\"CAD\":\"CAD\",\"CDF\":\"CDF\",\"CVE\":\"CVE\",\"EUR\":\"EUR\",\"GBP\":\"GBP\",\"GHS\":\"GHS\",\"GMD\":\"GMD\",\"GNF\":\"GNF\",\"KES\":\"KES\",\"LRD\":\"LRD\",\"MWK\":\"MWK\",\"MZN\":\"MZN\",\"NGN\":\"NGN\",\"RWF\":\"RWF\",\"SLL\":\"SLL\",\"STD\":\"STD\",\"TZS\":\"TZS\",\"UGX\":\"UGX\",\"USD\":\"USD\",\"XAF\":\"XAF\",\"XOF\":\"XOF\",\"ZMK\":\"ZMK\",\"ZMW\":\"ZMW\",\"ZWD\":\"ZWD\"}', 0, NULL, NULL, '2019-09-14 13:14:22', '2024-05-07 08:12:18'),
(10, 0, 110, 'RazorPay', 'Razorpay', '663a393a527831715091770.png', 1, '{\"key_id\":{\"title\":\"Key Id\",\"global\":true,\"value\":\"rzp_test_kiOtejPbRZU90E\"},\"key_secret\":{\"title\":\"Key Secret \",\"global\":true,\"value\":\"osRDebzEqbsE1kbyQJ4y0re7\"}}', '{\"INR\":\"INR\"}', 0, NULL, NULL, '2019-09-14 13:14:22', '2024-05-07 08:22:50'),
(11, 0, 111, 'Stripe Storefront', 'StripeJs', '663a3995417171715091861.png', 1, '{\"secret_key\":{\"title\":\"Secret Key\",\"global\":true,\"value\":\"sk_test_51I6GGiCGv1sRiQlEi5v1or9eR0HVbuzdMd2rW4n3DxC8UKfz66R4X6n4yYkzvI2LeAIuRU9H99ZpY7XCNFC9xMs500vBjZGkKG\"},\"publishable_key\":{\"title\":\"PUBLISHABLE KEY\",\"global\":true,\"value\":\"pk_test_51I6GGiCGv1sRiQlEOisPKrjBqQqqcFsw8mXNaZ2H2baN6R01NulFS7dKFji1NRRxuchoUTEDdB7ujKcyKYSVc0z500eth7otOM\"}}', '{\"USD\":\"USD\",\"AUD\":\"AUD\",\"BRL\":\"BRL\",\"CAD\":\"CAD\",\"CHF\":\"CHF\",\"DKK\":\"DKK\",\"EUR\":\"EUR\",\"GBP\":\"GBP\",\"HKD\":\"HKD\",\"INR\":\"INR\",\"JPY\":\"JPY\",\"MXN\":\"MXN\",\"MYR\":\"MYR\",\"NOK\":\"NOK\",\"NZD\":\"NZD\",\"PLN\":\"PLN\",\"SEK\":\"SEK\",\"SGD\":\"SGD\"}', 0, NULL, NULL, '2019-09-14 13:14:22', '2024-05-07 08:24:21'),
(12, 0, 112, 'Instamojo', 'Instamojo', '663a384d54a111715091533.png', 1, '{\"api_key\":{\"title\":\"API KEY\",\"global\":true,\"value\":\"test_2241633c3bc44a3de84a3b33969\"},\"auth_token\":{\"title\":\"Auth Token\",\"global\":true,\"value\":\"test_279f083f7bebefd35217feef22d\"},\"salt\":{\"title\":\"Salt\",\"global\":true,\"value\":\"19d38908eeff4f58b2ddda2c6d86ca25\"}}', '{\"INR\":\"INR\"}', 0, NULL, NULL, '2019-09-14 13:14:22', '2024-05-07 08:18:53'),
(13, 0, 501, 'Blockchain', 'Blockchain', '663a35efd0c311715090927.png', 1, '{\"api_key\":{\"title\":\"API Key\",\"global\":true,\"value\":\"55529946-05ca-48ff-8710-f279d86b1cc5\"},\"xpub_code\":{\"title\":\"XPUB CODE\",\"global\":true,\"value\":\"xpub6CKQ3xxWyBoFAF83izZCSFUorptEU9AF8TezhtWeMU5oefjX3sFSBw62Lr9iHXPkXmDQJJiHZeTRtD9Vzt8grAYRhvbz4nEvBu3QKELVzFK\"}}', '{\"BTC\":\"BTC\"}', 1, NULL, NULL, '2019-09-14 13:14:22', '2024-05-07 08:08:47'),
(15, 0, 503, 'CoinPayments', 'Coinpayments', '663a36a8d8e1d1715091112.png', 1, '{\"public_key\":{\"title\":\"Public Key\",\"global\":true,\"value\":\"---------------------\"},\"private_key\":{\"title\":\"Private Key\",\"global\":true,\"value\":\"---------------------\"},\"merchant_id\":{\"title\":\"Merchant ID\",\"global\":true,\"value\":\"---------------------\"}}', '{\"BTC\":\"Bitcoin\",\"BTC.LN\":\"Bitcoin (Lightning Network)\",\"LTC\":\"Litecoin\",\"CPS\":\"CPS Coin\",\"VLX\":\"Velas\",\"APL\":\"Apollo\",\"AYA\":\"Aryacoin\",\"BAD\":\"Badcoin\",\"BCD\":\"Bitcoin Diamond\",\"BCH\":\"Bitcoin Cash\",\"BCN\":\"Bytecoin\",\"BEAM\":\"BEAM\",\"BITB\":\"Bean Cash\",\"BLK\":\"BlackCoin\",\"BSV\":\"Bitcoin SV\",\"BTAD\":\"Bitcoin Adult\",\"BTG\":\"Bitcoin Gold\",\"BTT\":\"BitTorrent\",\"CLOAK\":\"CloakCoin\",\"CLUB\":\"ClubCoin\",\"CRW\":\"Crown\",\"CRYP\":\"CrypticCoin\",\"CRYT\":\"CryTrExCoin\",\"CURE\":\"CureCoin\",\"DASH\":\"DASH\",\"DCR\":\"Decred\",\"DEV\":\"DeviantCoin\",\"DGB\":\"DigiByte\",\"DOGE\":\"Dogecoin\",\"EBST\":\"eBoost\",\"EOS\":\"EOS\",\"ETC\":\"Ether Classic\",\"ETH\":\"Ethereum\",\"ETN\":\"Electroneum\",\"EUNO\":\"EUNO\",\"EXP\":\"EXP\",\"Expanse\":\"Expanse\",\"FLASH\":\"FLASH\",\"GAME\":\"GameCredits\",\"GLC\":\"Goldcoin\",\"GRS\":\"Groestlcoin\",\"KMD\":\"Komodo\",\"LOKI\":\"LOKI\",\"LSK\":\"LSK\",\"MAID\":\"MaidSafeCoin\",\"MUE\":\"MonetaryUnit\",\"NAV\":\"NAV Coin\",\"NEO\":\"NEO\",\"NMC\":\"Namecoin\",\"NVST\":\"NVO Token\",\"NXT\":\"NXT\",\"OMNI\":\"OMNI\",\"PINK\":\"PinkCoin\",\"PIVX\":\"PIVX\",\"POT\":\"PotCoin\",\"PPC\":\"Peercoin\",\"PROC\":\"ProCurrency\",\"PURA\":\"PURA\",\"QTUM\":\"QTUM\",\"RES\":\"Resistance\",\"RVN\":\"Ravencoin\",\"RVR\":\"RevolutionVR\",\"SBD\":\"Steem Dollars\",\"SMART\":\"SmartCash\",\"SOXAX\":\"SOXAX\",\"STEEM\":\"STEEM\",\"STRAT\":\"STRAT\",\"SYS\":\"Syscoin\",\"TPAY\":\"TokenPay\",\"TRIGGERS\":\"Triggers\",\"TRX\":\" TRON\",\"UBQ\":\"Ubiq\",\"UNIT\":\"UniversalCurrency\",\"USDT\":\"Tether USD (Omni Layer)\",\"USDT.BEP20\":\"Tether USD (BSC Chain)\",\"USDT.ERC20\":\"Tether USD (ERC20)\",\"USDT.TRC20\":\"Tether USD (Tron/TRC20)\",\"VTC\":\"Vertcoin\",\"WAVES\":\"Waves\",\"XCP\":\"Counterparty\",\"XEM\":\"NEM\",\"XMR\":\"Monero\",\"XSN\":\"Stakenet\",\"XSR\":\"SucreCoin\",\"XVG\":\"VERGE\",\"XZC\":\"ZCoin\",\"ZEC\":\"ZCash\",\"ZEN\":\"Horizen\"}', 1, NULL, NULL, '2019-09-14 13:14:22', '2024-05-07 08:11:52'),
(16, 0, 504, 'CoinPayments Fiat', 'CoinpaymentsFiat', '663a36b7b841a1715091127.png', 1, '{\"merchant_id\":{\"title\":\"Merchant ID\",\"global\":true,\"value\":\"6515561\"}}', '{\"USD\":\"USD\",\"AUD\":\"AUD\",\"BRL\":\"BRL\",\"CAD\":\"CAD\",\"CHF\":\"CHF\",\"CLP\":\"CLP\",\"CNY\":\"CNY\",\"DKK\":\"DKK\",\"EUR\":\"EUR\",\"GBP\":\"GBP\",\"HKD\":\"HKD\",\"INR\":\"INR\",\"ISK\":\"ISK\",\"JPY\":\"JPY\",\"KRW\":\"KRW\",\"NZD\":\"NZD\",\"PLN\":\"PLN\",\"RUB\":\"RUB\",\"SEK\":\"SEK\",\"SGD\":\"SGD\",\"THB\":\"THB\",\"TWD\":\"TWD\"}', 0, NULL, NULL, '2019-09-14 13:14:22', '2024-05-07 08:12:07'),
(17, 0, 505, 'Coingate', 'Coingate', '663a368e753381715091086.png', 1, '{\"api_key\":{\"title\":\"API Key\",\"global\":true,\"value\":\"6354mwVCEw5kHzRJ6thbGo-N\"}}', '{\"USD\":\"USD\",\"EUR\":\"EUR\"}', 0, NULL, NULL, '2019-09-14 13:14:22', '2024-05-07 08:11:26'),
(18, 0, 506, 'Coinbase Commerce', 'CoinbaseCommerce', '663a367e46ae51715091070.png', 1, '{\"api_key\":{\"title\":\"API Key\",\"global\":true,\"value\":\"c47cd7df-d8e8-424b-a20a\"},\"secret\":{\"title\":\"Webhook Shared Secret\",\"global\":true,\"value\":\"55871878-2c32-4f64-ab66\"}}', '{\"USD\":\"USD\",\"EUR\":\"EUR\",\"JPY\":\"JPY\",\"GBP\":\"GBP\",\"AUD\":\"AUD\",\"CAD\":\"CAD\",\"CHF\":\"CHF\",\"CNY\":\"CNY\",\"SEK\":\"SEK\",\"NZD\":\"NZD\",\"MXN\":\"MXN\",\"SGD\":\"SGD\",\"HKD\":\"HKD\",\"NOK\":\"NOK\",\"KRW\":\"KRW\",\"TRY\":\"TRY\",\"RUB\":\"RUB\",\"INR\":\"INR\",\"BRL\":\"BRL\",\"ZAR\":\"ZAR\",\"AED\":\"AED\",\"AFN\":\"AFN\",\"ALL\":\"ALL\",\"AMD\":\"AMD\",\"ANG\":\"ANG\",\"AOA\":\"AOA\",\"ARS\":\"ARS\",\"AWG\":\"AWG\",\"AZN\":\"AZN\",\"BAM\":\"BAM\",\"BBD\":\"BBD\",\"BDT\":\"BDT\",\"BGN\":\"BGN\",\"BHD\":\"BHD\",\"BIF\":\"BIF\",\"BMD\":\"BMD\",\"BND\":\"BND\",\"BOB\":\"BOB\",\"BSD\":\"BSD\",\"BTN\":\"BTN\",\"BWP\":\"BWP\",\"BYN\":\"BYN\",\"BZD\":\"BZD\",\"CDF\":\"CDF\",\"CLF\":\"CLF\",\"CLP\":\"CLP\",\"COP\":\"COP\",\"CRC\":\"CRC\",\"CUC\":\"CUC\",\"CUP\":\"CUP\",\"CVE\":\"CVE\",\"CZK\":\"CZK\",\"DJF\":\"DJF\",\"DKK\":\"DKK\",\"DOP\":\"DOP\",\"DZD\":\"DZD\",\"EGP\":\"EGP\",\"ERN\":\"ERN\",\"ETB\":\"ETB\",\"FJD\":\"FJD\",\"FKP\":\"FKP\",\"GEL\":\"GEL\",\"GGP\":\"GGP\",\"GHS\":\"GHS\",\"GIP\":\"GIP\",\"GMD\":\"GMD\",\"GNF\":\"GNF\",\"GTQ\":\"GTQ\",\"GYD\":\"GYD\",\"HNL\":\"HNL\",\"HRK\":\"HRK\",\"HTG\":\"HTG\",\"HUF\":\"HUF\",\"IDR\":\"IDR\",\"ILS\":\"ILS\",\"IMP\":\"IMP\",\"IQD\":\"IQD\",\"IRR\":\"IRR\",\"ISK\":\"ISK\",\"JEP\":\"JEP\",\"JMD\":\"JMD\",\"JOD\":\"JOD\",\"KES\":\"KES\",\"KGS\":\"KGS\",\"KHR\":\"KHR\",\"KMF\":\"KMF\",\"KPW\":\"KPW\",\"KWD\":\"KWD\",\"KYD\":\"KYD\",\"KZT\":\"KZT\",\"LAK\":\"LAK\",\"LBP\":\"LBP\",\"LKR\":\"LKR\",\"LRD\":\"LRD\",\"LSL\":\"LSL\",\"LYD\":\"LYD\",\"MAD\":\"MAD\",\"MDL\":\"MDL\",\"MGA\":\"MGA\",\"MKD\":\"MKD\",\"MMK\":\"MMK\",\"MNT\":\"MNT\",\"MOP\":\"MOP\",\"MRO\":\"MRO\",\"MUR\":\"MUR\",\"MVR\":\"MVR\",\"MWK\":\"MWK\",\"MYR\":\"MYR\",\"MZN\":\"MZN\",\"NAD\":\"NAD\",\"NGN\":\"NGN\",\"NIO\":\"NIO\",\"NPR\":\"NPR\",\"OMR\":\"OMR\",\"PAB\":\"PAB\",\"PEN\":\"PEN\",\"PGK\":\"PGK\",\"PHP\":\"PHP\",\"PKR\":\"PKR\",\"PLN\":\"PLN\",\"PYG\":\"PYG\",\"QAR\":\"QAR\",\"RON\":\"RON\",\"RSD\":\"RSD\",\"RWF\":\"RWF\",\"SAR\":\"SAR\",\"SBD\":\"SBD\",\"SCR\":\"SCR\",\"SDG\":\"SDG\",\"SHP\":\"SHP\",\"SLL\":\"SLL\",\"SOS\":\"SOS\",\"SRD\":\"SRD\",\"SSP\":\"SSP\",\"STD\":\"STD\",\"SVC\":\"SVC\",\"SYP\":\"SYP\",\"SZL\":\"SZL\",\"THB\":\"THB\",\"TJS\":\"TJS\",\"TMT\":\"TMT\",\"TND\":\"TND\",\"TOP\":\"TOP\",\"TTD\":\"TTD\",\"TWD\":\"TWD\",\"TZS\":\"TZS\",\"UAH\":\"UAH\",\"UGX\":\"UGX\",\"UYU\":\"UYU\",\"UZS\":\"UZS\",\"VEF\":\"VEF\",\"VND\":\"VND\",\"VUV\":\"VUV\",\"WST\":\"WST\",\"XAF\":\"XAF\",\"XAG\":\"XAG\",\"XAU\":\"XAU\",\"XCD\":\"XCD\",\"XDR\":\"XDR\",\"XOF\":\"XOF\",\"XPD\":\"XPD\",\"XPF\":\"XPF\",\"XPT\":\"XPT\",\"YER\":\"YER\",\"ZMW\":\"ZMW\",\"ZWL\":\"ZWL\"}\r\n\r\n', 0, '{\"endpoint\":{\"title\": \"Webhook Endpoint\",\"value\":\"ipn.CoinbaseCommerce\"}}', NULL, '2019-09-14 13:14:22', '2024-05-07 08:11:10'),
(24, 0, 113, 'Paypal Express', 'PaypalSdk', '663a38ed101a61715091693.png', 1, '{\"clientId\":{\"title\":\"Paypal Client ID\",\"global\":true,\"value\":\"Ae0-tixtSV7DvLwIh3Bmu7JvHrjh5EfGdXr_cEklKAVjjezRZ747BxKILiBdzlKKyp-W8W_T7CKH1Ken\"},\"clientSecret\":{\"title\":\"Client Secret\",\"global\":true,\"value\":\"EOhbvHZgFNO21soQJT1L9Q00M3rK6PIEsdiTgXRBt2gtGtxwRer5JvKnVUGNU5oE63fFnjnYY7hq3HBA\"}}', '{\"AUD\":\"AUD\",\"BRL\":\"BRL\",\"CAD\":\"CAD\",\"CZK\":\"CZK\",\"DKK\":\"DKK\",\"EUR\":\"EUR\",\"HKD\":\"HKD\",\"HUF\":\"HUF\",\"INR\":\"INR\",\"ILS\":\"ILS\",\"JPY\":\"JPY\",\"MYR\":\"MYR\",\"MXN\":\"MXN\",\"TWD\":\"TWD\",\"NZD\":\"NZD\",\"NOK\":\"NOK\",\"PHP\":\"PHP\",\"PLN\":\"PLN\",\"GBP\":\"GBP\",\"RUB\":\"RUB\",\"SGD\":\"SGD\",\"SEK\":\"SEK\",\"CHF\":\"CHF\",\"THB\":\"THB\",\"USD\":\"$\"}', 0, NULL, NULL, '2019-09-14 13:14:22', '2024-05-07 08:21:33'),
(25, 0, 114, 'Stripe Checkout', 'StripeV3', '663a39afb519f1715091887.png', 1, '{\"secret_key\":{\"title\":\"Secret Key\",\"global\":true,\"value\":\"sk_test_51I6GGiCGv1sRiQlEi5v1or9eR0HVbuzdMd2rW4n3DxC8UKfz66R4X6n4yYkzvI2LeAIuRU9H99ZpY7XCNFC9xMs500vBjZGkKG\"},\"publishable_key\":{\"title\":\"PUBLISHABLE KEY\",\"global\":true,\"value\":\"pk_test_51I6GGiCGv1sRiQlEOisPKrjBqQqqcFsw8mXNaZ2H2baN6R01NulFS7dKFji1NRRxuchoUTEDdB7ujKcyKYSVc0z500eth7otOM\"},\"end_point\":{\"title\":\"End Point Secret\",\"global\":true,\"value\":\"whsec_lUmit1gtxwKTveLnSe88xCSDdnPOt8g5\"}}', '{\"USD\":\"USD\",\"AUD\":\"AUD\",\"BRL\":\"BRL\",\"CAD\":\"CAD\",\"CHF\":\"CHF\",\"DKK\":\"DKK\",\"EUR\":\"EUR\",\"GBP\":\"GBP\",\"HKD\":\"HKD\",\"INR\":\"INR\",\"JPY\":\"JPY\",\"MXN\":\"MXN\",\"MYR\":\"MYR\",\"NOK\":\"NOK\",\"NZD\":\"NZD\",\"PLN\":\"PLN\",\"SEK\":\"SEK\",\"SGD\":\"SGD\"}', 0, '{\"webhook\":{\"title\": \"Webhook Endpoint\",\"value\":\"ipn.StripeV3\"}}', NULL, '2019-09-14 13:14:22', '2024-05-07 08:24:47'),
(27, 0, 115, 'Mollie', 'Mollie', '663a387ec69371715091582.png', 1, '{\"mollie_email\":{\"title\":\"Mollie Email \",\"global\":true,\"value\":\"vi@gmail.com\"},\"api_key\":{\"title\":\"API KEY\",\"global\":true,\"value\":\"test_cucfwKTWfft9s337qsVfn5CC4vNkrn\"}}', '{\"AED\":\"AED\",\"AUD\":\"AUD\",\"BGN\":\"BGN\",\"BRL\":\"BRL\",\"CAD\":\"CAD\",\"CHF\":\"CHF\",\"CZK\":\"CZK\",\"DKK\":\"DKK\",\"EUR\":\"EUR\",\"GBP\":\"GBP\",\"HKD\":\"HKD\",\"HRK\":\"HRK\",\"HUF\":\"HUF\",\"ILS\":\"ILS\",\"ISK\":\"ISK\",\"JPY\":\"JPY\",\"MXN\":\"MXN\",\"MYR\":\"MYR\",\"NOK\":\"NOK\",\"NZD\":\"NZD\",\"PHP\":\"PHP\",\"PLN\":\"PLN\",\"RON\":\"RON\",\"RUB\":\"RUB\",\"SEK\":\"SEK\",\"SGD\":\"SGD\",\"THB\":\"THB\",\"TWD\":\"TWD\",\"USD\":\"USD\",\"ZAR\":\"ZAR\"}', 0, NULL, NULL, '2019-09-14 13:14:22', '2024-05-07 08:19:42'),
(30, 0, 116, 'Cashmaal', 'Cashmaal', '663a361b16bd11715090971.png', 1, '{\"web_id\":{\"title\":\"Web Id\",\"global\":true,\"value\":\"3748\"},\"ipn_key\":{\"title\":\"IPN Key\",\"global\":true,\"value\":\"546254628759524554647987\"}}', '{\"PKR\":\"PKR\",\"USD\":\"USD\"}', 0, '{\"webhook\":{\"title\": \"IPN URL\",\"value\":\"ipn.Cashmaal\"}}', NULL, NULL, '2024-05-07 08:09:31'),
(36, 0, 119, 'Mercado Pago', 'MercadoPago', '663a386c714a91715091564.png', 1, '{\"access_token\":{\"title\":\"Access Token\",\"global\":true,\"value\":\"APP_USR-7924565816849832-082312-21941521997fab717db925cf1ea2c190-1071840315\"}}', '{\"USD\":\"USD\",\"CAD\":\"CAD\",\"CHF\":\"CHF\",\"DKK\":\"DKK\",\"EUR\":\"EUR\",\"GBP\":\"GBP\",\"NOK\":\"NOK\",\"PLN\":\"PLN\",\"SEK\":\"SEK\",\"AUD\":\"AUD\",\"NZD\":\"NZD\"}', 0, NULL, NULL, NULL, '2024-05-07 08:19:24'),
(37, 0, 120, 'Authorize.net', 'Authorize', '663a35b9ca5991715090873.png', 1, '{\"login_id\":{\"title\":\"Login ID\",\"global\":true,\"value\":\"59e4P9DBcZv\"},\"transaction_key\":{\"title\":\"Transaction Key\",\"global\":true,\"value\":\"47x47TJyLw2E7DbR\"}}', '{\"USD\":\"USD\",\"CAD\":\"CAD\",\"CHF\":\"CHF\",\"DKK\":\"DKK\",\"EUR\":\"EUR\",\"GBP\":\"GBP\",\"NOK\":\"NOK\",\"PLN\":\"PLN\",\"SEK\":\"SEK\",\"AUD\":\"AUD\",\"NZD\":\"NZD\"}', 0, NULL, NULL, NULL, '2024-05-07 08:07:53'),
(46, 0, 121, 'NMI', 'NMI', '663a3897754cf1715091607.png', 1, '{\"api_key\":{\"title\":\"API Key\",\"global\":true,\"value\":\"2F822Rw39fx762MaV7Yy86jXGTC7sCDy\"}}', '{\"AED\":\"AED\",\"ARS\":\"ARS\",\"AUD\":\"AUD\",\"BOB\":\"BOB\",\"BRL\":\"BRL\",\"CAD\":\"CAD\",\"CHF\":\"CHF\",\"CLP\":\"CLP\",\"CNY\":\"CNY\",\"COP\":\"COP\",\"DKK\":\"DKK\",\"EUR\":\"EUR\",\"GBP\":\"GBP\",\"HKD\":\"HKD\",\"IDR\":\"IDR\",\"ILS\":\"ILS\",\"INR\":\"INR\",\"JPY\":\"JPY\",\"KRW\":\"KRW\",\"MXN\":\"MXN\",\"MYR\":\"MYR\",\"NOK\":\"NOK\",\"NZD\":\"NZD\",\"PEN\":\"PEN\",\"PHP\":\"PHP\",\"PLN\":\"PLN\",\"PYG\":\"PYG\",\"RUB\":\"RUB\",\"SEC\":\"SEC\",\"SGD\":\"SGD\",\"THB\":\"THB\",\"TRY\":\"TRY\",\"TWD\":\"TWD\",\"USD\":\"USD\",\"ZAR\":\"ZAR\"}', 0, NULL, NULL, NULL, '2024-05-07 08:20:07'),
(50, 0, 507, 'BTCPay', 'BTCPay', '663a35cd25a8d1715090893.png', 1, '{\"store_id\":{\"title\":\"Store Id\",\"global\":true,\"value\":\"HsqFVTXSeUFJu7caoYZc3CTnP8g5LErVdHhEXPVTheHf\"},\"api_key\":{\"title\":\"Api Key\",\"global\":true,\"value\":\"4436bd706f99efae69305e7c4eff4780de1335ce\"},\"server_name\":{\"title\":\"Server Name\",\"global\":true,\"value\":\"https:\\/\\/testnet.demo.btcpayserver.org\"},\"secret_code\":{\"title\":\"Secret Code\",\"global\":true,\"value\":\"SUCdqPn9CDkY7RmJHfpQVHP2Lf2\"}}', '{\"BTC\":\"Bitcoin\",\"LTC\":\"Litecoin\"}', 1, '{\"webhook\":{\"title\": \"IPN URL\",\"value\":\"ipn.BTCPay\"}}', NULL, NULL, '2024-05-07 08:08:13'),
(51, 0, 508, 'Now payments hosted', 'NowPaymentsHosted', '663a38b8d57a81715091640.png', 1, '{\"api_key\":{\"title\":\"API Key\",\"global\":true,\"value\":\"--------\"},\"secret_key\":{\"title\":\"Secret Key\",\"global\":true,\"value\":\"------------\"}}', '{\"BTG\":\"BTG\",\"ETH\":\"ETH\",\"XMR\":\"XMR\",\"ZEC\":\"ZEC\",\"XVG\":\"XVG\",\"ADA\":\"ADA\",\"LTC\":\"LTC\",\"BCH\":\"BCH\",\"QTUM\":\"QTUM\",\"DASH\":\"DASH\",\"XLM\":\"XLM\",\"XRP\":\"XRP\",\"XEM\":\"XEM\",\"DGB\":\"DGB\",\"LSK\":\"LSK\",\"DOGE\":\"DOGE\",\"TRX\":\"TRX\",\"KMD\":\"KMD\",\"REP\":\"REP\",\"BAT\":\"BAT\",\"ARK\":\"ARK\",\"WAVES\":\"WAVES\",\"BNB\":\"BNB\",\"XZC\":\"XZC\",\"NANO\":\"NANO\",\"TUSD\":\"TUSD\",\"VET\":\"VET\",\"ZEN\":\"ZEN\",\"GRS\":\"GRS\",\"FUN\":\"FUN\",\"NEO\":\"NEO\",\"GAS\":\"GAS\",\"PAX\":\"PAX\",\"USDC\":\"USDC\",\"ONT\":\"ONT\",\"XTZ\":\"XTZ\",\"LINK\":\"LINK\",\"RVN\":\"RVN\",\"BNBMAINNET\":\"BNBMAINNET\",\"ZIL\":\"ZIL\",\"BCD\":\"BCD\",\"USDT\":\"USDT\",\"USDTERC20\":\"USDTERC20\",\"CRO\":\"CRO\",\"DAI\":\"DAI\",\"HT\":\"HT\",\"WABI\":\"WABI\",\"BUSD\":\"BUSD\",\"ALGO\":\"ALGO\",\"USDTTRC20\":\"USDTTRC20\",\"GT\":\"GT\",\"STPT\":\"STPT\",\"AVA\":\"AVA\",\"SXP\":\"SXP\",\"UNI\":\"UNI\",\"OKB\":\"OKB\",\"BTC\":\"BTC\"}', 1, '', NULL, NULL, '2024-05-07 08:20:40'),
(52, 0, 509, 'Now payments checkout', 'NowPaymentsCheckout', '663a38a59d2541715091621.png', 1, '{\"api_key\":{\"title\":\"API Key\",\"global\":true,\"value\":\"---------------\"},\"secret_key\":{\"title\":\"Secret Key\",\"global\":true,\"value\":\"-----------\"}}', '{\"USD\":\"USD\",\"EUR\":\"EUR\"}', 1, '', NULL, NULL, '2024-05-07 08:20:21'),
(53, 0, 122, '2Checkout', 'TwoCheckout', '663a39b8e64b91715091896.png', 1, '{\"merchant_code\":{\"title\":\"Merchant Code\",\"global\":true,\"value\":\"253248016872\"},\"secret_key\":{\"title\":\"Secret Key\",\"global\":true,\"value\":\"eQM)ID@&vG84u!O*g[p+\"}}', '{\"AFN\": \"AFN\",\"ALL\": \"ALL\",\"DZD\": \"DZD\",\"ARS\": \"ARS\",\"AUD\": \"AUD\",\"AZN\": \"AZN\",\"BSD\": \"BSD\",\"BDT\": \"BDT\",\"BBD\": \"BBD\",\"BZD\": \"BZD\",\"BMD\": \"BMD\",\"BOB\": \"BOB\",\"BWP\": \"BWP\",\"BRL\": \"BRL\",\"GBP\": \"GBP\",\"BND\": \"BND\",\"BGN\": \"BGN\",\"CAD\": \"CAD\",\"CLP\": \"CLP\",\"CNY\": \"CNY\",\"COP\": \"COP\",\"CRC\": \"CRC\",\"HRK\": \"HRK\",\"CZK\": \"CZK\",\"DKK\": \"DKK\",\"DOP\": \"DOP\",\"XCD\": \"XCD\",\"EGP\": \"EGP\",\"EUR\": \"EUR\",\"FJD\": \"FJD\",\"GTQ\": \"GTQ\",\"HKD\": \"HKD\",\"HNL\": \"HNL\",\"HUF\": \"HUF\",\"INR\": \"INR\",\"IDR\": \"IDR\",\"ILS\": \"ILS\",\"JMD\": \"JMD\",\"JPY\": \"JPY\",\"KZT\": \"KZT\",\"KES\": \"KES\",\"LAK\": \"LAK\",\"MMK\": \"MMK\",\"LBP\": \"LBP\",\"LRD\": \"LRD\",\"MOP\": \"MOP\",\"MYR\": \"MYR\",\"MVR\": \"MVR\",\"MRO\": \"MRO\",\"MUR\": \"MUR\",\"MXN\": \"MXN\",\"MAD\": \"MAD\",\"NPR\": \"NPR\",\"TWD\": \"TWD\",\"NZD\": \"NZD\",\"NIO\": \"NIO\",\"NOK\": \"NOK\",\"PKR\": \"PKR\",\"PGK\": \"PGK\",\"PEN\": \"PEN\",\"PHP\": \"PHP\",\"PLN\": \"PLN\",\"QAR\": \"QAR\",\"RON\": \"RON\",\"RUB\": \"RUB\",\"WST\": \"WST\",\"SAR\": \"SAR\",\"SCR\": \"SCR\",\"SGD\": \"SGD\",\"SBD\": \"SBD\",\"ZAR\": \"ZAR\",\"KRW\": \"KRW\",\"LKR\": \"LKR\",\"SEK\": \"SEK\",\"CHF\": \"CHF\",\"SYP\": \"SYP\",\"THB\": \"THB\",\"TOP\": \"TOP\",\"TTD\": \"TTD\",\"TRY\": \"TRY\",\"UAH\": \"UAH\",\"AED\": \"AED\",\"USD\": \"USD\",\"VUV\": \"VUV\",\"VND\": \"VND\",\"XOF\": \"XOF\",\"YER\": \"YER\"}', 0, '{\"approved_url\":{\"title\": \"Approved URL\",\"value\":\"ipn.TwoCheckout\"}}', NULL, NULL, '2024-05-07 08:24:56'),
(54, 0, 123, 'Checkout', 'Checkout', '663a3628733351715090984.png', 1, '{\"secret_key\":{\"title\":\"Secret Key\",\"global\":true,\"value\":\"------\"},\"public_key\":{\"title\":\"PUBLIC KEY\",\"global\":true,\"value\":\"------\"},\"processing_channel_id\":{\"title\":\"PROCESSING CHANNEL\",\"global\":true,\"value\":\"------\"}}', '{\"USD\":\"USD\",\"EUR\":\"EUR\",\"GBP\":\"GBP\",\"HKD\":\"HKD\",\"AUD\":\"AUD\",\"CAN\":\"CAN\",\"CHF\":\"CHF\",\"SGD\":\"SGD\",\"JPY\":\"JPY\",\"NZD\":\"NZD\"}', 0, NULL, NULL, NULL, '2024-05-07 08:09:44'),
(56, 0, 510, 'Binance', 'Binance', '663a35db4fd621715090907.png', 1, '{\"api_key\":{\"title\":\"API Key\",\"global\":true,\"value\":\"tsu3tjiq0oqfbtmlbevoeraxhfbp3brejnm9txhjxcp4to29ujvakvfl1ibsn3ja\"},\"secret_key\":{\"title\":\"Secret Key\",\"global\":true,\"value\":\"jzngq4t04ltw8d4iqpi7admfl8tvnpehxnmi34id1zvfaenbwwvsvw7llw3zdko8\"},\"merchant_id\":{\"title\":\"Merchant ID\",\"global\":true,\"value\":\"231129033\"}}', '{\"BTC\":\"Bitcoin\",\"USD\":\"USD\",\"BNB\":\"BNB\"}', 1, '{\"cron\":{\"title\": \"Cron Job URL\",\"value\":\"ipn.Binance\"}}', NULL, NULL, '2024-05-07 08:08:27'),
(57, 0, 124, 'SslCommerz', 'SslCommerz', '663a397a70c571715091834.png', 1, '{\"store_id\":{\"title\":\"Store ID\",\"global\":true,\"value\":\"---------\"},\"store_password\":{\"title\":\"Store Password\",\"global\":true,\"value\":\"----------\"}}', '{\"BDT\":\"BDT\",\"USD\":\"USD\",\"EUR\":\"EUR\",\"SGD\":\"SGD\",\"INR\":\"INR\",\"MYR\":\"MYR\"}', 0, NULL, NULL, NULL, '2024-05-07 08:23:54'),
(58, 0, 125, 'Aamarpay', 'Aamarpay', '663a34d5d1dfc1715090645.png', 1, '{\"store_id\":{\"title\":\"Store ID\",\"global\":true,\"value\":\"---------\"},\"signature_key\":{\"title\":\"Signature Key\",\"global\":true,\"value\":\"----------\"}}', '{\"BDT\":\"BDT\"}', 0, NULL, NULL, NULL, '2024-05-07 08:04:05');

-- --------------------------------------------------------

--
-- Table structure for table `gateway_currencies`
--

CREATE TABLE `gateway_currencies` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `symbol` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `method_code` int DEFAULT NULL,
  `gateway_alias` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `min_amount` decimal(28,8) NOT NULL DEFAULT '0.00000000',
  `max_amount` decimal(28,8) NOT NULL DEFAULT '0.00000000',
  `percent_charge` decimal(5,2) NOT NULL DEFAULT '0.00',
  `fixed_charge` decimal(28,8) NOT NULL DEFAULT '0.00000000',
  `rate` decimal(28,8) NOT NULL DEFAULT '0.00000000',
  `gateway_parameter` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `general_settings`
--

CREATE TABLE `general_settings` (
  `id` bigint UNSIGNED NOT NULL,
  `site_name` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cur_text` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'currency text',
  `cur_sym` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'currency symbol',
  `email_from` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_from_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_template` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `sms_template` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sms_from` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `push_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `push_template` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `base_color` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mail_config` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'email configuration',
  `sms_config` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `firebase_config` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `global_shortcodes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `ev` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'email verification, 0 - dont check, 1 - check',
  `en` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'email notification, 0 - dont send, 1 - send',
  `sv` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'mobile verication, 0 - dont check, 1 - check',
  `sn` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'sms notification, 0 - dont send, 1 - send',
  `pn` tinyint(1) NOT NULL DEFAULT '1',
  `force_ssl` tinyint(1) NOT NULL DEFAULT '0',
  `maintenance_mode` tinyint(1) NOT NULL DEFAULT '0',
  `secure_password` tinyint(1) NOT NULL DEFAULT '0',
  `agree` tinyint(1) NOT NULL DEFAULT '0',
  `multi_language` tinyint(1) NOT NULL DEFAULT '1',
  `registration` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0: Off	, 1: On',
  `active_template` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `socialite_credentials` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `last_cron` datetime DEFAULT NULL,
  `available_version` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `system_customized` tinyint(1) NOT NULL DEFAULT '0',
  `paginate_number` int NOT NULL DEFAULT '0',
  `currency_format` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1=>Both\r\n2=>Text Only\r\n3=>Symbol Only',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `general_settings`
--

INSERT INTO `general_settings` (`id`, `site_name`, `cur_text`, `cur_sym`, `email_from`, `email_from_name`, `email_template`, `sms_template`, `sms_from`, `push_title`, `push_template`, `base_color`, `mail_config`, `sms_config`, `firebase_config`, `global_shortcodes`, `ev`, `en`, `sv`, `sn`, `pn`, `force_ssl`, `maintenance_mode`, `secure_password`, `agree`, `multi_language`, `registration`, `active_template`, `socialite_credentials`, `last_cron`, `available_version`, `system_customized`, `paginate_number`, `currency_format`, `created_at`, `updated_at`) VALUES
(1, 'Viserbus', 'USD', '$', 'info@viserlab.com', '{{site_name}}', '<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">\r\n  <!--[if !mso]><!-->\r\n  <meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">\r\n  <!--<![endif]-->\r\n  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n  <title></title>\r\n  <style type=\"text/css\">\r\n.ReadMsgBody { width: 100%; background-color: #ffffff; }\r\n.ExternalClass { width: 100%; background-color: #ffffff; }\r\n.ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div { line-height: 100%; }\r\nhtml { width: 100%; }\r\nbody { -webkit-text-size-adjust: none; -ms-text-size-adjust: none; margin: 0; padding: 0; }\r\ntable { border-spacing: 0; table-layout: fixed; margin: 0 auto;border-collapse: collapse; }\r\ntable table table { table-layout: auto; }\r\n.yshortcuts a { border-bottom: none !important; }\r\nimg:hover { opacity: 0.9 !important; }\r\na { color: #0087ff; text-decoration: none; }\r\n.textbutton a { font-family: \'open sans\', arial, sans-serif !important;}\r\n.btn-link a { color:#FFFFFF !important;}\r\n\r\n@media only screen and (max-width: 480px) {\r\nbody { width: auto !important; }\r\n*[class=\"table-inner\"] { width: 90% !important; text-align: center !important; }\r\n*[class=\"table-full\"] { width: 100% !important; text-align: center !important; }\r\n/* image */\r\nimg[class=\"img1\"] { width: 100% !important; height: auto !important; }\r\n}\r\n</style>\r\n\r\n\r\n\r\n  <table bgcolor=\"#414a51\" width=\"100%\" border=\"0\" align=\"center\" cellpadding=\"0\" cellspacing=\"0\">\r\n    <tbody><tr>\r\n      <td height=\"50\"></td>\r\n    </tr>\r\n    <tr>\r\n      <td align=\"center\" style=\"text-align:center;vertical-align:top;font-size:0;\">\r\n        <table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\r\n          <tbody><tr>\r\n            <td align=\"center\" width=\"600\">\r\n              <!--header-->\r\n              <table class=\"table-inner\" width=\"95%\" border=\"0\" align=\"center\" cellpadding=\"0\" cellspacing=\"0\">\r\n                <tbody><tr>\r\n                  <td bgcolor=\"#0087ff\" style=\"border-top-left-radius:6px; border-top-right-radius:6px;text-align:center;vertical-align:top;font-size:0;\" align=\"center\">\r\n                    <table width=\"90%\" border=\"0\" align=\"center\" cellpadding=\"0\" cellspacing=\"0\">\r\n                      <tbody><tr>\r\n                        <td height=\"20\"></td>\r\n                      </tr>\r\n                      <tr>\r\n                        <td align=\"center\" style=\"font-family: \'Open sans\', Arial, sans-serif; color:#FFFFFF; font-size:16px; font-weight: bold;\">This is a System Generated Email</td>\r\n                      </tr>\r\n                      <tr>\r\n                        <td height=\"20\"></td>\r\n                      </tr>\r\n                    </tbody></table>\r\n                  </td>\r\n                </tr>\r\n              </tbody></table>\r\n              <!--end header-->\r\n              <table class=\"table-inner\" width=\"95%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\r\n                <tbody><tr>\r\n                  <td bgcolor=\"#FFFFFF\" align=\"center\" style=\"text-align:center;vertical-align:top;font-size:0;\">\r\n                    <table align=\"center\" width=\"90%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\r\n                      <tbody><tr>\r\n                        <td height=\"35\"></td>\r\n                      </tr>\r\n                      <!--logo-->\r\n                      <tr>\r\n                        <td align=\"center\" style=\"vertical-align:top;font-size:0;\">\r\n                          <a href=\"#\">\r\n                            <img style=\"display:block; line-height:0px; font-size:0px; border:0px;\" src=\"https://i.ibb.co/rw2fTRM/logo-dark.png\" width=\"220\" alt=\"img\">\r\n                          </a>\r\n                        </td>\r\n                      </tr>\r\n                      <!--end logo-->\r\n                      <tr>\r\n                        <td height=\"40\"></td>\r\n                      </tr>\r\n                      <!--headline-->\r\n                      <tr>\r\n                        <td align=\"center\" style=\"font-family: \'Open Sans\', Arial, sans-serif; font-size: 22px;color:#414a51;font-weight: bold;\">Hello {{fullname}} ({{username}})</td>\r\n                      </tr>\r\n                      <!--end headline-->\r\n                      <tr>\r\n                        <td align=\"center\" style=\"text-align:center;vertical-align:top;font-size:0;\">\r\n                          <table width=\"40\" border=\"0\" align=\"center\" cellpadding=\"0\" cellspacing=\"0\">\r\n                            <tbody><tr>\r\n                              <td height=\"20\" style=\" border-bottom:3px solid #0087ff;\"></td>\r\n                            </tr>\r\n                          </tbody></table>\r\n                        </td>\r\n                      </tr>\r\n                      <tr>\r\n                        <td height=\"20\"></td>\r\n                      </tr>\r\n                      <!--content-->\r\n                      <tr>\r\n                        <td align=\"left\" style=\"font-family: \'Open sans\', Arial, sans-serif; color:#7f8c8d; font-size:16px; line-height: 28px;\">{{message}}</td>\r\n                      </tr>\r\n                      <!--end content-->\r\n                      <tr>\r\n                        <td height=\"40\"></td>\r\n                      </tr>\r\n              \r\n                    </tbody></table>\r\n                  </td>\r\n                </tr>\r\n                <tr>\r\n                  <td height=\"45\" align=\"center\" bgcolor=\"#f4f4f4\" style=\"border-bottom-left-radius:6px;border-bottom-right-radius:6px;\">\r\n                    <table align=\"center\" width=\"90%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\r\n                      <tbody><tr>\r\n                        <td height=\"10\"></td>\r\n                      </tr>\r\n                      <!--preference-->\r\n                      <tr>\r\n                        <td class=\"preference-link\" align=\"center\" style=\"font-family: \'Open sans\', Arial, sans-serif; color:#95a5a6; font-size:14px;\">\r\n                          © 2024 <a href=\"#\">{{site_name}}</a>&nbsp;. All Rights Reserved. \r\n                        </td>\r\n                      </tr>\r\n                      <!--end preference-->\r\n                      <tr>\r\n                        <td height=\"10\"></td>\r\n                      </tr>\r\n                    </tbody></table>\r\n                  </td>\r\n                </tr>\r\n              </tbody></table>\r\n            </td>\r\n          </tr>\r\n        </tbody></table>\r\n      </td>\r\n    </tr>\r\n    <tr>\r\n      <td height=\"60\"></td>\r\n    </tr>\r\n  </tbody></table>', 'hi {{fullname}} ({{username}}), {{message}}', '{{site_name}}', '{{site_name}}', 'hi {{fullname}} ({{username}}), {{message}}', '0E9E4D', '{\"name\":\"php\"}', '{\"name\":\"clickatell\",\"clickatell\":{\"api_key\":\"----------------\"},\"infobip\":{\"username\":\"------------8888888\",\"password\":\"-----------------\"},\"message_bird\":{\"api_key\":\"-------------------\"},\"nexmo\":{\"api_key\":\"----------------------\",\"api_secret\":\"----------------------\"},\"sms_broadcast\":{\"username\":\"----------------------\",\"password\":\"-----------------------------\"},\"twilio\":{\"account_sid\":\"-----------------------\",\"auth_token\":\"---------------------------\",\"from\":\"----------------------\"},\"text_magic\":{\"username\":\"-----------------------\",\"apiv2_key\":\"-------------------------------\"},\"custom\":{\"method\":\"get\",\"url\":\"https:\\/\\/hostname.com\\/demo-api-v1\",\"headers\":{\"name\":[\"api_key\"],\"value\":[\"test_api 555\"]},\"body\":{\"name\":[\"from_number\"],\"value\":[\"5657545757\"]}}}', '{\"apiKey\":\"AIzaSyCb6zm7_8kdStXjZMgLZpwjGDuTUg0e_qM\",\"authDomain\":\"flutter-prime-df1c5.firebaseapp.com\",\"projectId\":\"flutter-prime-df1c5\",\"storageBucket\":\"flutter-prime-df1c5.appspot.com\",\"messagingSenderId\":\"274514992002\",\"appId\":\"1:274514992002:web:4d77660766f4797500cd9b\",\"measurementId\":\"G-KFPM07RXRC\",\"serverKey\":\"AAAA14oqxFc:APA91bE9uJdrjU_FX3gg_EtCfApRqoNojV71m6J-9yCQC7GoL2pBFcN9pdJjLLQxEAUcNxxatfWKLcnl5qCuLsmpPdr_3QRtH9XzfIu1MrLUJU3dHkBc4CGIkYMM9EWgXCNFjudhhQmH\"}', '{\n    \"site_name\":\"Name of your site\",\n    \"site_currency\":\"Currency of your site\",\n    \"currency_symbol\":\"Symbol of currency\"\n}', 1, 1, 0, 0, 1, 0, 0, 0, 1, 1, 1, 'basic', '{\"google\":{\"client_id\":\"------------\",\"client_secret\":\"-------------\",\"status\":1},\"facebook\":{\"client_id\":\"------\",\"client_secret\":\"------\",\"status\":1},\"linkedin\":{\"client_id\":\"-----\",\"client_secret\":\"-----\",\"status\":1}}', '2024-05-05 13:20:49', '0', 0, 20, 1, NULL, '2024-07-26 06:37:10');

-- --------------------------------------------------------

--
-- Table structure for table `languages`
--

CREATE TABLE `languages` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0: not default language, 1: default language',
  `image` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `languages`
--

INSERT INTO `languages` (`id`, `name`, `code`, `is_default`, `image`, `created_at`, `updated_at`) VALUES
(1, 'English', 'en', 1, '660b94fa876ac1712035066.png', '2020-07-06 03:47:55', '2024-04-01 23:17:46');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` bigint UNSIGNED NOT NULL,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2019_08_19_000000_create_failed_jobs_table', 1),
(4, '2020_06_14_061757_create_support_tickets_table', 3),
(5, '2020_06_14_061837_create_support_messages_table', 3),
(6, '2020_06_14_061904_create_support_attachments_table', 3),
(7, '2020_06_14_062359_create_admins_table', 3),
(8, '2020_06_14_064604_create_transactions_table', 4),
(9, '2020_06_14_065247_create_general_settings_table', 5),
(12, '2014_10_12_100000_create_password_resets_table', 6),
(13, '2020_06_14_060541_create_user_logins_table', 6),
(14, '2020_06_14_071708_create_admin_password_resets_table', 7),
(15, '2020_09_14_053026_create_countries_table', 8),
(16, '2021_03_15_084721_create_admin_notifications_table', 9),
(17, '2016_06_01_000001_create_oauth_auth_codes_table', 10),
(18, '2016_06_01_000002_create_oauth_access_tokens_table', 10),
(19, '2016_06_01_000003_create_oauth_refresh_tokens_table', 10),
(20, '2016_06_01_000004_create_oauth_clients_table', 10),
(21, '2016_06_01_000005_create_oauth_personal_access_clients_table', 10),
(22, '2021_05_08_103925_create_sms_gateways_table', 11),
(23, '2019_12_14_000001_create_personal_access_tokens_table', 12),
(24, '2021_05_23_111859_create_email_logs_table', 13),
(25, '2022_02_26_061836_create_forms_table', 14),
(26, '2023_06_15_144908_create_update_logs_table', 15);

-- --------------------------------------------------------

--
-- Table structure for table `notification_logs`
--

CREATE TABLE `notification_logs` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL DEFAULT '0',
  `sender` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sent_from` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sent_to` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `notification_type` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_read` tinyint NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_templates`
--

CREATE TABLE `notification_templates` (
  `id` bigint UNSIGNED NOT NULL,
  `act` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `push_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_body` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `sms_body` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `push_body` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `shortcodes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `email_status` tinyint(1) NOT NULL DEFAULT '1',
  `email_sent_from_name` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_sent_from_address` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sms_status` tinyint(1) NOT NULL DEFAULT '1',
  `sms_sent_from` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `push_status` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notification_templates`
--

INSERT INTO `notification_templates` (`id`, `act`, `name`, `subject`, `push_title`, `email_body`, `sms_body`, `push_body`, `shortcodes`, `email_status`, `email_sent_from_name`, `email_sent_from_address`, `sms_status`, `sms_sent_from`, `push_status`, `created_at`, `updated_at`) VALUES
(3, 'PAYMENT_COMPLETE', 'Payment - Automated - Successful', 'Payment Completed Successfully', '{{site_name}} - Payment successful', '<div>Your payment of <b>{{amount}}</b><font color=\"#212529\"><b>{{site_currency}}</b></font>&nbsp;is via&nbsp; <b>{{method_name}} </b>has been completed Successfully.<b><br></b></div><div><b><br></b></div><div><b>Details of your Payment :<br></b></div><div><br></div><div>Amount : {{amount}} {{site_currency}}</div><div>Charge: <font color=\"#000000\">{{charge}}&nbsp;</font><span style=\"background-color: var(--bs-card-bg); text-align: var(--bs-body-text-align);\"><font color=\"#000000\">{{site_currency}}</font></span></div><div><br></div><div>Conversion Rate : 1 {{site_currency}} = {{rate}} {{method_currency}}</div><div>Payable : {{method_amount}} {{method_currency}} <br></div><div>Paid via :&nbsp; {{method_name}}</div><div><br></div><div>Transaction Number : {{trx}}</div><div><br></div><div><b>Booking Information :<br></b></div><div><br></div><div>Date of Journey : {{journey_date}}</div><div>Seats: {{seats}}</div><div>Total Seats : {{total_seats}}</div><div>Starting Point : {{source}}</div><div>Destination : {{destination}}</div>', '{{amount}} {{site_currency}} Payment successful by {{gateway_name}} .\r\nJourney Date: {{journey_date}} , Seats: {{seats}}, Starting point: {{source}}, Dropping point: {{destination}}', 'Payment Completed Successfully', '{\r\n    \"trx\": \"Transaction Number\",\r\n    \"amount\": \"Request Amount By user\",\r\n    \"charge\": \"Gateway Charge\",\r\n    \"method_name\": \"Payment Method Name\",\r\n    \"method_currency\": \"Payment Method Currency\",\r\n    \"method_amount\": \"Payment Method Amount After Conversion\",\r\n    \"journey_date\": \"journey date\",\r\n    \"seats\": \"Seat Number\",\r\n    \"total_seats\": \"Total Seats\",\r\n    \"source\": \"Starting point\",\r\n    \"destination\": \"Destination point\"\r\n}', 1, '{{site_name}} Billing', NULL, 0, NULL, 1, '2021-11-03 12:00:00', '2024-07-17 13:17:59'),
(4, 'PAYMENT_APPROVE', 'Payment - Manual - Approved', 'Payment Request Approved', '{{site_name}} - Payment Request Approved', '<div>Your payment request of <b>{{amount}} {{site_currency}}</b> is via&nbsp; <b>{{method_name}} </b>is Approved .<b><br></b>\r\n</div>\r\n<div><b><br></b></div>\r\n<div><b>Details of your payment :<br></b></div>\r\n<div><br></div>\r\n<div>Amount : {{amount}} {{site_currency}}</div>\r\n<div>Charge: <font color=\"#FF0000\">{{charge}} {{site_currency}}</font>\r\n</div>\r\n<div><br></div>\r\n<div>Conversion Rate : 1 {{site_currency}} = {{rate}} {{method_currency}}</div>\r\n<div>Payable : {{method_amount}} {{method_currency}} <br></div>\r\n<div>Paid via :&nbsp; {{method_name}}</div>\r\n<div><br></div>\r\n<div>Transaction Number : {{trx}}</div>\r\n<div><br></div>\r\n<div><b>Booking Information :<br></b></div>\r\n<div><br></div>\r\n<div>Date of Journey : {{journey_date}}</div>\r\n<div>Seats: {{seats}}</div>\r\n<div>Total Seats : {{total_seats}}</div>\r\n<div>Starting Point : {{source}}</div>\r\n<div>Destination : {{destination}}</div>', 'Admin Approve Your {{amount}} {{gateway_currency}} payment request by {{gateway_name}} transaction : {{transaction}}.\r\nJourney Date: {{journey_date}} , Seats: {{seats}}, Starting point: {{source}}, Dropping point: {{destination}}', 'Payment of {{amount}} {{site_currency}} via {{method_name}} has been approved.', '{\r\n    \"trx\": \"Transaction Number\",\r\n    \"amount\": \"Request Amount By user\",\r\n    \"charge\": \"Gateway Charge\",\r\n    \"rate\": \"Conversion Rate\",\r\n    \"method_name\": \"Deposit Method Name\",\r\n    \"method_currency\": \"Deposit Method Currency\",\r\n    \"method_amount\": \"Deposit Method Amount After Conversion\",\r\n    \"journey_date\": \"journey date\",\r\n    \"seats\": \"Seat Number\",\r\n    \"total_seats\": \"Total Seats\",\r\n    \"source\": \"Starting point\",\r\n    \"destination\": \"Destination point\"\r\n}', 1, '{{site_name}} Billing', NULL, 0, NULL, 0, '2021-11-03 12:00:00', '2024-07-17 13:22:08'),
(5, 'PAYMENT_REJECT', 'Payment - Manual - Rejected', 'Payment Request Rejected', '{{site_name}} - Payment Request Rejected', '<div>Your payment request of <b>{{amount}} {{site_currency}}</b> is via&nbsp; <b>{{method_name}} </b>is Approved .<b><br></b>\r\n</div>\r\n<div><b><br></b></div>\r\n<div><b>Details of your payment :<br></b></div>\r\n<div><br></div>\r\n<div>Amount : {{amount}} {{site_currency}}</div>\r\n<div>Charge: <font color=\"#FF0000\">{{charge}} {{site_currency}}</font>\r\n</div>\r\n<div><br></div>\r\n<div>Conversion Rate : 1 {{site_currency}} = {{rate}} {{method_currency}}</div>\r\n<div>Payable : {{method_amount}} {{method_currency}} <br></div>\r\n<div>Paid via :&nbsp; {{method_name}}</div>\r\n<div><br></div>\r\n<div>Transaction Number : {{trx}}</div>\r\n<div><br></div>\r\n<div><b>Booking Information :<br></b></div>\r\n<div><br></div>\r\n<div>Date of Journey : {{journey_date}}</div>\r\n<div>Seats: {{seats}}</div>\r\n<div>Total Seats : {{total_seats}}</div>\r\n<div>Starting Point : {{source}}</div>\r\n<div>Destination : {{destination}}</div>', 'Admin Approve Your {{amount}} {{gateway_currency}} payment request by {{gateway_name}} transaction : {{transaction}}.\r\nJourney Date: {{journey_date}} , Seats: {{seats}}, Starting point: {{source}}, Dropping point: {{destination}}', 'Your deposit request of {{amount}} {{site_currency}} via {{method_name}} has been rejected.', '{\r\n    \"trx\": \"Transaction Number\",\r\n    \"amount\": \"Request Amount By user\",\r\n    \"charge\": \"Gateway Charge\",\r\n    \"rate\": \"Conversion Rate\",\r\n    \"method_name\": \"Payment Method Name\",\r\n    \"method_currency\": \"Payment Method Currency\",\r\n    \"method_amount\": \"Payment Method Amount After Conversion\",\r\n    \"journey_date\": \"journey date\",\r\n    \"seats\": \"Seat Number\",\r\n    \"total_seats\": \"Total Seats\",\r\n    \"source\": \"Starting point\",\r\n    \"destination\": \"Destination point\"\r\n}', 1, '{{site_name}} Billing', NULL, 0, NULL, 0, '2021-11-03 12:00:00', '2024-07-17 13:20:56'),
(6, 'PAYMENT_REQUEST', 'Payment- Manual - Requested', 'Payment Request Submitted Successfully', NULL, '<div>Your payment request of <b>{{amount}} {{site_currency}}</b> is via&nbsp; <b>{{method_name}} </b>submitted\r\n    successfully<b> .<br></b></div>\r\n<div><b><br></b></div>\r\n<div><b>Details of your payment :<br></b></div>\r\n<div><br></div>\r\n<div>Amount : {{amount}} {{site_currency}}</div>\r\n<div>Charge: <font color=\"#FF0000\">{{charge}} {{site_currency}}</font>\r\n</div>\r\n<div><br></div>\r\n<div>Conversion Rate : 1 {{site_currency}} = {{rate}} {{method_currency}}</div>\r\n<div>Payable : {{method_amount}} {{method_currency}} <br></div>\r\n<div>Pay via :&nbsp; {{method_name}}</div>\r\n<div><br></div>\r\n<div>Transaction Number : {{trx}}</div>\r\n<div><br></div>\r\n<div><b>Pending Booking Information :<br></b></div>\r\n<div><br></div>\r\n<div>Date of Journey : {{journey_date}}</div>\r\n<div>Seats: {{seats}}</div>\r\n<div>Total Seats : {{total_seats}}</div>\r\n<div>Starting Point : {{source}}</div>\r\n<div>Destination : {{destination}}</div>', '{{amount}} Payment requested by {{method}}. Charge: {{charge}} . Trx: {{trx}} .\r\nJourney Date: {{journey_date}} , Seats: {{seats}}, Starting point: {{source}}, Dropping point: {{destination}}', 'Your payment request of {{amount}} {{site_currency}} via {{method_name}} submitted successfully.', '{\r\n    \"trx\": \"Transaction Number\",\r\n    \"amount\": \"Request Amount By user\",\r\n    \"charge\": \"Gateway Charge\",\r\n    \"rate\": \"Conversion Rate\",\r\n    \"method_name\": \"Deposit Method Name\",\r\n    \"method_currency\": \"Deposit Method Currency\",\r\n    \"method_amount\": \"Deposit Method Amount After Conversion\",\r\n    \"journey_date\": \"journey date\",\r\n    \"seats\": \"Seat Number\",\r\n    \"total_seats\": \"Total Seats\",\r\n    \"source\": \"Starting point\",\r\n    \"destination\": \"Destination point\"\r\n}', 1, '{{site_name}} Billing', NULL, 0, NULL, 0, '2021-11-03 12:00:00', '2024-07-17 13:25:53'),
(7, 'PASS_RESET_CODE', 'Password - Reset - Code', 'Password Reset', '{{site_name}} Password Reset Code', '<div>We\'ve received a request to reset the password for your account on <b>{{time}}</b>. The request originated from\r\n            the following IP address: <b>{{ip}}</b>, using <b>{{browser}}</b> on <b>{{operating_system}}</b>.\r\n    </div><br>\r\n    <div><span>To proceed with the password reset, please use the following account recovery code</span>: <span><b><font size=\"6\">{{code}}</font></b></span></div><br>\r\n    <div><span>If you did not initiate this password reset request, please disregard this message. Your account security\r\n            remains our top priority, and we advise you to take appropriate action if you suspect any unauthorized\r\n            access to your account.</span></div>', 'To proceed with the password reset, please use the following account recovery code: {{code}}', 'To proceed with the password reset, please use the following account recovery code: {{code}}', '{\"code\":\"Verification code for password reset\",\"ip\":\"IP address of the user\",\"browser\":\"Browser of the user\",\"operating_system\":\"Operating system of the user\",\"time\":\"Time of the request\"}', 1, '{{site_name}} Authentication Center', NULL, 0, NULL, 0, '2021-11-03 12:00:00', '2024-05-08 07:24:57'),
(8, 'PASS_RESET_DONE', 'Password - Reset - Confirmation', 'Password Reset Successful', NULL, '<div><div><span>We are writing to inform you that the password reset for your account was successful. This action was completed at {{time}} from the following browser</span>: <span>{{browser}}</span><span>on {{operating_system}}, with the IP address</span>: <span>{{ip}}</span>.</div><br><div><span>Your account security is our utmost priority, and we are committed to ensuring the safety of your information. If you did not initiate this password reset or notice any suspicious activity on your account, please contact our support team immediately for further assistance.</span></div></div>', 'We are writing to inform you that the password reset for your account was successful.', 'We are writing to inform you that the password reset for your account was successful.', '{\"ip\":\"IP address of the user\",\"browser\":\"Browser of the user\",\"operating_system\":\"Operating system of the user\",\"time\":\"Time of the request\"}', 1, '{{site_name}} Authentication Center', NULL, 1, NULL, 0, '2021-11-03 12:00:00', '2024-04-25 03:27:24'),
(9, 'ADMIN_SUPPORT_REPLY', 'Support - Reply', 'Re: {{ticket_subject}} - Ticket #{{ticket_id}}', '{{site_name}} - Support Ticket Replied', '<div>\r\n    <div><span>Thank you for reaching out to us regarding your support ticket with the subject</span>:\r\n        <span>\"{{ticket_subject}}\"&nbsp;</span><span>and ticket ID</span>: {{ticket_id}}.</div><br>\r\n    <div><span>We have carefully reviewed your inquiry, and we are pleased to provide you with the following\r\n            response</span><span>:</span></div><br>\r\n    <div>{{reply}}</div><br>\r\n    <div><span>If you have any further questions or need additional assistance, please feel free to reply by clicking on\r\n            the following link</span>: <a href=\"{{link}}\" title=\"\" target=\"_blank\">{{link}}</a><span>. This link will take you to\r\n            the ticket thread where you can provide further information or ask for clarification.</span></div><br>\r\n    <div><span>Thank you for your patience and cooperation as we worked to address your concerns.</span></div>\r\n</div>', 'Thank you for reaching out to us regarding your support ticket with the subject: \"{{ticket_subject}}\" and ticket ID: {{ticket_id}}. We have carefully reviewed your inquiry. To check the response, please go to the following link: {{link}}', 'Re: {{ticket_subject}} - Ticket #{{ticket_id}}', '{\"ticket_id\":\"ID of the support ticket\",\"ticket_subject\":\"Subject  of the support ticket\",\"reply\":\"Reply made by the admin\",\"link\":\"URL to view the support ticket\"}', 1, '{{site_name}} Support Team', NULL, 1, NULL, 0, '2021-11-03 12:00:00', '2024-05-08 07:26:06'),
(10, 'EVER_CODE', 'Verification - Email', 'Email Verification Code', NULL, '<div>\r\n    <div><span>Thank you for taking the time to verify your email address with us. Your email verification code\r\n            is</span>: <b><font size=\"6\">{{code}}</font></b></div><br>\r\n    <div><span>Please enter this code in the designated field on our platform to complete the verification\r\n            process.</span></div><br>\r\n    <div><span>If you did not request this verification code, please disregard this email. Your account security is our\r\n            top priority, and we advise you to take appropriate measures if you suspect any unauthorized access.</span>\r\n    </div><br>\r\n    <div><span>If you have any questions or encounter any issues during the verification process, please don\'t hesitate\r\n            to contact our support team for assistance.</span></div><br>\r\n    <div><span>Thank you for choosing us.</span></div>\r\n</div>', '---', '---', '{\"code\":\"Email verification code\"}', 1, '{{site_name}} Verification Center', NULL, 0, NULL, 0, '2021-11-03 12:00:00', '2024-04-25 03:27:12'),
(11, 'SVER_CODE', 'Verification - SMS', 'Verify Your Mobile Number', NULL, '---', 'Your mobile verification code is {{code}}. Please enter this code in the appropriate field to verify your mobile number. If you did not request this code, please ignore this message.', '---', '{\"code\":\"SMS Verification Code\"}', 0, '{{site_name}} Verification Center', NULL, 1, NULL, 0, '2021-11-03 12:00:00', '2024-04-25 03:27:03'),
(15, 'DEFAULT', 'Default Template', '{{subject}}', '{{subject}}', '{{message}}', '{{message}}', '{{message}}', '{\"subject\":\"Subject\",\"message\":\"Message\"}', 1, NULL, NULL, 1, NULL, 1, '2019-09-14 13:14:22', '2024-05-16 01:32:53');

-- --------------------------------------------------------

--
-- Table structure for table `pages`
--

CREATE TABLE `pages` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slug` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tempname` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'template name',
  `secs` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `seo_content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pages`
--

INSERT INTO `pages` (`id`, `name`, `slug`, `tempname`, `secs`, `seo_content`, `is_default`, `created_at`, `updated_at`) VALUES
(1, 'HOME', '/', 'templates.basic.', '[\"how_it_works\",\"amenities\",\"testimonials\",\"blog\"]', '{\"image\":\"663212c9551ed1714557641.png\",\"description\":\"Et recusandae Minus\",\"social_title\":null,\"social_description\":\"Odit magna eos cons\",\"keywords\":null}', 1, '2020-07-11 06:23:58', '2024-07-04 04:48:18'),
(4, 'Blog', 'blog', 'templates.basic.', NULL, NULL, 1, '2020-10-22 01:14:43', '2020-10-22 01:14:43'),
(5, 'Contact', 'contact', 'templates.basic.', NULL, NULL, 1, '2020-10-22 01:14:53', '2020-10-22 01:14:53'),
(26, 'About', 'about', 'templates.basic.', '[\"about\",\"faq\"]', NULL, 0, '2024-07-04 06:48:11', '2024-07-04 06:48:32'),
(27, 'FAQs', 'faqs', 'templates.basic.', '[\"faq\",\"blog\"]', NULL, 0, '2024-07-04 06:48:20', '2024-07-04 06:49:35');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `id` bigint UNSIGNED NOT NULL,
  `start_from` time DEFAULT NULL,
  `end_at` time DEFAULT NULL,
  `status` tinyint NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seat_layouts`
--

CREATE TABLE `seat_layouts` (
  `id` bigint UNSIGNED NOT NULL,
  `layout` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscribers`
--

CREATE TABLE `subscribers` (
  `id` bigint UNSIGNED NOT NULL,
  `email` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `support_attachments`
--

CREATE TABLE `support_attachments` (
  `id` bigint UNSIGNED NOT NULL,
  `support_message_id` int UNSIGNED NOT NULL DEFAULT '0',
  `attachment` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `support_messages`
--

CREATE TABLE `support_messages` (
  `id` bigint UNSIGNED NOT NULL,
  `support_ticket_id` int UNSIGNED NOT NULL DEFAULT '0',
  `admin_id` int UNSIGNED NOT NULL DEFAULT '0',
  `message` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `support_tickets`
--

CREATE TABLE `support_tickets` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` int DEFAULT '0',
  `name` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ticket` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0: Open, 1: Answered, 2: Replied, 3: Closed',
  `priority` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = Low, 2 = medium, 3 = heigh',
  `last_reply` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ticket_prices`
--

CREATE TABLE `ticket_prices` (
  `id` bigint UNSIGNED NOT NULL,
  `fleet_type_id` int UNSIGNED NOT NULL DEFAULT '0',
  `vehicle_route_id` int UNSIGNED NOT NULL DEFAULT '0',
  `price` decimal(8,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ticket_price_by_stoppages`
--

CREATE TABLE `ticket_price_by_stoppages` (
  `id` bigint UNSIGNED NOT NULL,
  `ticket_price_id` int UNSIGNED NOT NULL DEFAULT '0',
  `source_destination` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` double(8,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL DEFAULT '0',
  `amount` decimal(28,8) NOT NULL DEFAULT '0.00000000',
  `charge` decimal(28,8) NOT NULL DEFAULT '0.00000000',
  `post_balance` decimal(28,8) NOT NULL DEFAULT '0.00000000',
  `trx_type` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `trx` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `details` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remark` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trips`
--

CREATE TABLE `trips` (
  `id` bigint UNSIGNED NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fleet_type_id` int UNSIGNED NOT NULL DEFAULT '0',
  `vehicle_route_id` int UNSIGNED NOT NULL DEFAULT '0',
  `schedule_id` int UNSIGNED NOT NULL DEFAULT '0',
  `start_from` int UNSIGNED NOT NULL DEFAULT '0',
  `end_to` int UNSIGNED NOT NULL DEFAULT '0',
  `day_off` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `update_logs`
--

CREATE TABLE `update_logs` (
  `id` bigint UNSIGNED NOT NULL,
  `version` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `update_log` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `firstname` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lastname` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `username` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `dial_code` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ref_by` int UNSIGNED NOT NULL DEFAULT '0',
  `balance` decimal(28,8) NOT NULL DEFAULT '0.00000000',
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `country_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_code` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zip` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0: banned, 1: active',
  `ev` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0: email unverified, 1: email verified',
  `sv` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0: mobile unverified, 1: mobile verified',
  `profile_complete` tinyint(1) NOT NULL DEFAULT '0',
  `ver_code` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'stores verification code',
  `ver_code_send_at` datetime DEFAULT NULL COMMENT 'verification send time',
  `ts` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0: 2fa off, 1: 2fa on',
  `tv` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0: 2fa unverified, 1: 2fa verified',
  `tsc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ban_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_logins`
--

CREATE TABLE `user_logins` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL DEFAULT '0',
  `user_ip` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_code` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `longitude` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `browser` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `os` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `id` bigint UNSIGNED NOT NULL,
  `nick_name` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fleet_type_id` bigint UNSIGNED NOT NULL DEFAULT '0',
  `register_no` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `engine_no` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `chasis_no` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model_no` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_routes`
--

CREATE TABLE `vehicle_routes` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_from` int UNSIGNED NOT NULL DEFAULT '0',
  `end_to` int UNSIGNED DEFAULT '0',
  `stoppages` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `distance` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `time` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`,`username`);

--
-- Indexes for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin_password_resets`
--
ALTER TABLE `admin_password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `assigned_vehicles`
--
ALTER TABLE `assigned_vehicles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `booked_tickets`
--
ALTER TABLE `booked_tickets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `counters`
--
ALTER TABLE `counters`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `deposits`
--
ALTER TABLE `deposits`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `device_tokens`
--
ALTER TABLE `device_tokens`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `extensions`
--
ALTER TABLE `extensions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fleet_types`
--
ALTER TABLE `fleet_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `forms`
--
ALTER TABLE `forms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `frontends`
--
ALTER TABLE `frontends`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `gateways`
--
ALTER TABLE `gateways`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `gateway_currencies`
--
ALTER TABLE `gateway_currencies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `general_settings`
--
ALTER TABLE `general_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `languages`
--
ALTER TABLE `languages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notification_logs`
--
ALTER TABLE `notification_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notification_templates`
--
ALTER TABLE `notification_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `seat_layouts`
--
ALTER TABLE `seat_layouts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `subscribers`
--
ALTER TABLE `subscribers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `support_attachments`
--
ALTER TABLE `support_attachments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `support_messages`
--
ALTER TABLE `support_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ticket_prices`
--
ALTER TABLE `ticket_prices`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ticket_price_by_stoppages`
--
ALTER TABLE `ticket_price_by_stoppages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `trips`
--
ALTER TABLE `trips`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `update_logs`
--
ALTER TABLE `update_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`,`email`);

--
-- Indexes for table `user_logins`
--
ALTER TABLE `user_logins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `vehicle_routes`
--
ALTER TABLE `vehicle_routes`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_password_resets`
--
ALTER TABLE `admin_password_resets`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assigned_vehicles`
--
ALTER TABLE `assigned_vehicles`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `booked_tickets`
--
ALTER TABLE `booked_tickets`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `counters`
--
ALTER TABLE `counters`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `deposits`
--
ALTER TABLE `deposits`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `device_tokens`
--
ALTER TABLE `device_tokens`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `extensions`
--
ALTER TABLE `extensions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fleet_types`
--
ALTER TABLE `fleet_types`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `forms`
--
ALTER TABLE `forms`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `frontends`
--
ALTER TABLE `frontends`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=114;

--
-- AUTO_INCREMENT for table `gateways`
--
ALTER TABLE `gateways`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `gateway_currencies`
--
ALTER TABLE `gateway_currencies`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `general_settings`
--
ALTER TABLE `general_settings`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `languages`
--
ALTER TABLE `languages`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `notification_logs`
--
ALTER TABLE `notification_logs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_templates`
--
ALTER TABLE `notification_templates`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `pages`
--
ALTER TABLE `pages`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seat_layouts`
--
ALTER TABLE `seat_layouts`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subscribers`
--
ALTER TABLE `subscribers`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `support_attachments`
--
ALTER TABLE `support_attachments`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `support_messages`
--
ALTER TABLE `support_messages`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ticket_prices`
--
ALTER TABLE `ticket_prices`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ticket_price_by_stoppages`
--
ALTER TABLE `ticket_price_by_stoppages`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trips`
--
ALTER TABLE `trips`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `update_logs`
--
ALTER TABLE `update_logs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_logins`
--
ALTER TABLE `user_logins`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vehicle_routes`
--
ALTER TABLE `vehicle_routes`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
