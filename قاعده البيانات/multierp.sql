-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: 13 ديسمبر 2025 الساعة 18:02
-- إصدار الخادم: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `multierp`
--

-- --------------------------------------------------------

--
-- بنية الجدول `accounts`
--

CREATE TABLE `accounts` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('asset','liability','equity','revenue','expense') NOT NULL,
  `balance` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `api_tokens`
--

CREATE TABLE `api_tokens` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token` varchar(500) NOT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `api_tokens`
--

INSERT INTO `api_tokens` (`id`, `user_id`, `token`, `expires_at`, `created_at`) VALUES
(1, 1, '39a1473182c4a019509bca7b0e6b8ee0ca8791f770bdfb9b632b200d45aa6527', '2025-12-19 01:37:39', '2025-12-12 00:37:39'),
(2, 1, '15a187ecfa540600463defff7750d7fdb22b3156cfc21eb3deed4ee8db5cc3dc', '2025-12-19 01:41:30', '2025-12-12 00:41:30'),
(3, 1, '3429f25ddfc0aefa5ffec5dccd89756957732f24cacb6328c5564e2630857ec2', '2025-12-19 01:42:34', '2025-12-12 00:42:34'),
(4, 1, 'bf24f7c3687eea2f6a6395c7e12003a3da77b669bed73bc51eebcd3bd6a55e10', '2025-12-19 01:42:45', '2025-12-12 00:42:45'),
(5, 1, '08f933745e3ad0c975fda4111ee50d89b5dbfdbaab33017d041420820007f7c6', '2025-12-19 01:44:08', '2025-12-12 00:44:08'),
(6, 1, '2c16f02813ef2dd736cbd377cc1db711be0f46bb705478e808a19452fc7bacde', '2025-12-19 01:44:40', '2025-12-12 00:44:40'),
(7, 1, 'acbf5b306e0d4d4097bddbe813680c4417e8a6f7c394bea54efc90761c208ec2', '2025-12-19 01:51:02', '2025-12-12 00:51:02'),
(8, 1, '22d94bb0c4af1a523fd7b4d28c12f22972db815346ff48be724ab36e8e712662', '2025-12-19 01:51:03', '2025-12-12 00:51:03'),
(9, 1, 'c5e9349f4c2742639b17764e510776c978f3fe3928cbee4247f1e2e4cc4ad3ed', '2025-12-19 01:51:13', '2025-12-12 00:51:13'),
(10, 1, 'a9a26155f73d0555d39d19e04a6bb3bd63b51b1d7b300dd5f332a9bd9a4c6b14', '2025-12-19 01:51:26', '2025-12-12 00:51:26'),
(11, 1, '88136e42e5c8320a6095121fb513143aa5592b58566b0756bc788b48c54f7be0', '2025-12-19 01:52:50', '2025-12-12 00:52:50'),
(12, 1, 'd056d99f67afc1a83338dbe86798994c62273f97396e3b49436ad2cf03b43d32', '2025-12-19 01:58:21', '2025-12-12 00:58:21'),
(13, 1, '27b6f0ee816b0f9aa704e84a205966c290235a5fb45283b479c7ae4c3f9c182c', '2025-12-19 02:05:29', '2025-12-12 01:05:29'),
(14, 1, '87849b3faa219fe20350e4c127403e445f5be8d5201466d6a947f9d70907348b', '2025-12-19 02:05:30', '2025-12-12 01:05:30'),
(15, 1, '2ca5de9df2b9cd459ecb6867e02282be87e48a54cd5a3b7d591dafe5c1235ee6', '2025-12-19 02:06:19', '2025-12-12 01:06:19'),
(16, 1, '33e310462a1065299ab87f5d8867ae91ccc84327111368feab9c75807c09cbcc', '2025-12-19 02:06:58', '2025-12-12 01:06:58'),
(17, 1, '6ec9e082070824d559f4b8bfa77e3e92b46d346311f40392d3601300052765f5', '2025-12-19 14:31:31', '2025-12-12 13:31:31'),
(18, 1, '7531307f5084f78669f79070b105abd9a62da8d46645e7dedfc8147cf95bf3d7', '2025-12-19 14:57:03', '2025-12-12 13:57:03'),
(19, 1, '6ff71be22420e3d17b820b34600803428e5a4b463a2a47c9d0eb6f8122beb998', '2025-12-19 15:01:26', '2025-12-12 14:01:26'),
(20, 1, '70177b44b967be58cd54dbd7fe94c40fc18fb80bb51f1af11bb9a756aa07b34a', '2025-12-19 15:55:57', '2025-12-12 14:55:57'),
(21, 1, 'decfb566c3326ec6d664eecf4806a5d52f4a8144c298f0547cba1c84c2b24c1b', '2025-12-19 17:03:54', '2025-12-12 16:03:54'),
(22, 1, 'f504288e9f6c67049ffeafd5b4c0b2664953e775eeefd91fe541967194ec74fd', '2025-12-19 18:35:16', '2025-12-12 17:35:16'),
(23, 1, '416baee392866ba615f99d1585572450a5e44c7ae4200797dd5d1f7434303f29', '2025-12-19 18:39:18', '2025-12-12 17:39:18'),
(25, 1, '67d4635b9e1369ac427dd538ab0367a9f9179f3d4ceacf50edf2c59284e93dbd', '2025-12-19 19:03:59', '2025-12-12 18:03:59'),
(26, 1, '28f2a18bfd7ce7cf49f6b0ec18f0a91509ec082a1b813959f02fbac6e3c86b72', '2025-12-19 21:18:30', '2025-12-12 20:18:30'),
(28, 1, '6a53e0b3ab1ea2b167468feffe0a6a556d04a7d8977ed2f7f2efc92400d25c14', '2025-12-19 22:37:54', '2025-12-12 21:37:54'),
(29, 1, '53fb7e32e6b93fb3b899d227ac0026665092f0f1c9401128f6f7df4c06805ca0', '2025-12-19 22:38:10', '2025-12-12 21:38:10'),
(31, 1, 'f337b958c9a0336a74e74e428e33e9d13f24a85f92b52cdee51766ee582d75dd', '2025-12-19 22:38:41', '2025-12-12 21:38:41'),
(32, 1, 'daf9761814fcdeb42cd71d1f4317334f74bf3e9bf76a1c0a2a2f4827dfb26f1b', '2025-12-19 22:41:32', '2025-12-12 21:41:32'),
(33, 1, '8abecaa5039461869ffaf35ffb5cce365eed74401f1305ce03baa55153bc2c34', '2025-12-19 22:43:07', '2025-12-12 21:43:07'),
(34, 1, '801c2aa618eea15c6d4d84f2ffd20283faff4e20a24572aa9c9240df635eb894', '2025-12-19 22:44:48', '2025-12-12 21:44:48'),
(35, 1, 'cbc434ba3cf87c058d860688f495bb687f98201e4157a570357ac9450c592b56', '2025-12-19 22:45:01', '2025-12-12 21:45:01'),
(36, 2, 'b848492a6cc3d8a7bd035cc3cd4793c376f71911c582aca611cc4903556ac2e6', '2025-12-19 22:45:40', '2025-12-12 21:45:40'),
(37, 2, '21bd696125769416f11835314aa202906f51753dc1d46b71cddb88850d5fc00e', '2025-12-19 22:45:48', '2025-12-12 21:45:48'),
(38, 1, '7c40cc9bf57da1e467acf3ec14476699d7aab4830ab1a348b9c9f9f1e5145041', '2025-12-19 22:47:39', '2025-12-12 21:47:39'),
(40, 2, '77954868896106c2fa463a97cf27714de867a89a72aec4536caae7e6f55794f6', '2025-12-19 22:49:31', '2025-12-12 21:49:31'),
(41, 1, '5c223461bc3570d8654b4a36d79cf374f5ec1c237cbfb47343eff72186e825eb', '2025-12-19 22:52:42', '2025-12-12 21:52:42'),
(42, 5, 'e8b845df5486f10e2e15d38dbedd956e2b4f0efa381a7db7f1620dde9c9c431f', '2025-12-19 22:53:49', '2025-12-12 21:53:49'),
(43, 1, '7a5c0c7ffacd8700cb9ae733f2d912b7bc66570c2b329a6c37a3b776aa39c5c8', '2025-12-19 22:54:56', '2025-12-12 21:54:56'),
(44, 1, '173c47ed2198342d59839a9240b5a1207a8a7c976eccb87e5ba7444b2629a31a', '2025-12-19 23:15:44', '2025-12-12 22:15:44'),
(45, 6, 'c4e90b310946386aa573eb4a0da808a6de05b5ac4651acc757cdccc7d79194e8', '2025-12-19 23:17:21', '2025-12-12 22:17:21'),
(46, 1, '0dad28873f7619d7bc4ac18fdddc688455d35968100a8ac6ef832a4b87af1a70', '2025-12-19 23:18:08', '2025-12-12 22:18:08'),
(47, 6, '9ca46d1fa22d985fcb468eb0283c3cafe6060c9d34f321a60e39ef959f9bef90', '2025-12-19 23:18:56', '2025-12-12 22:18:56'),
(48, 1, 'b35aba10ab4152f523056d11176c535dd7cca6e258973485db513341af3b9cae', '2025-12-19 23:19:24', '2025-12-12 22:19:24'),
(49, 1, '423a8fac7389c4771c7c3052996def8a16677d15e479fa0528fcf213ce0f797b', '2025-12-20 13:26:36', '2025-12-13 12:26:36'),
(50, 1, '054ffd7d966d1e6dc78a9b82e0ea5f9cc672219eeb0c9914d385be9c50f61f48', '2025-12-20 14:36:21', '2025-12-13 13:36:21'),
(51, 1, 'b93ab851cd6eb9defeefe0bce38a4eac8e5d115c6b479b8ffc09a27cb3f421fc', '2025-12-20 17:43:59', '2025-12-13 16:43:59'),
(52, 1, '949556570ea25e4f6ee0cd7e0d53dbf9143f8922e2e791e5f1a330dfb1f53f3f', '2025-12-20 17:44:06', '2025-12-13 16:44:06'),
(53, 1, 'a9bbb89479f4079ef2ad2ec7e61033711233d0d4ed69e94effe10bc222cce868', '2025-12-20 17:44:22', '2025-12-13 16:44:22'),
(54, 1, '73cd7e8f8fc3e55e284275bbcfb411c7a4f1c223b7749eabb560f1f8a5c08f71', '2025-12-20 17:44:25', '2025-12-13 16:44:25'),
(55, 1, 'fed27ae604d18757dc46755be3eae239b1d234572ef2f0b4e1eabd075b47e724', '2025-12-20 17:44:42', '2025-12-13 16:44:42'),
(56, 1, '8b89f5f5de559ce836b4d493133d905b17ee46da0a300e85347a49cc83f98f06', '2025-12-20 17:45:49', '2025-12-13 16:45:49'),
(57, 1, '36d67cfd2052d90bad2263e0c4dbd13503d14ffd7db4e2eaad38551d53ada57f', '2025-12-20 17:46:40', '2025-12-13 16:46:40'),
(58, 1, 'e4aad9a8ab8253f48f156849ceb60b8d9e8446cca3f4d26865defba3bdf722ff', '2025-12-20 17:53:32', '2025-12-13 16:53:32');

-- --------------------------------------------------------

--
-- بنية الجدول `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED DEFAULT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `company_id`, `user_id`, `action`, `table_name`, `record_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 00:37:39'),
(2, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 00:41:30'),
(3, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 00:42:34'),
(4, NULL, 1, 'login_failed', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 00:42:37'),
(5, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 00:42:45'),
(6, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-12 00:44:08'),
(7, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-12 00:44:40'),
(8, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-12 00:51:02'),
(9, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-12 00:51:03'),
(10, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-12 00:51:13'),
(11, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-12 00:51:26'),
(12, NULL, 1, 'login_failed', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-12 00:51:31'),
(13, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-12 00:52:50'),
(14, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 00:58:21'),
(15, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-12 01:05:29'),
(16, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-12 01:05:30'),
(17, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 01:06:19'),
(18, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-12 01:06:58'),
(19, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 13:31:31'),
(20, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-12 13:57:03'),
(21, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-12 14:01:26'),
(22, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-12 14:55:57'),
(23, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 16:03:54'),
(24, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-12 17:35:16'),
(25, NULL, 1, 'login_failed', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-12 17:39:09'),
(26, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-12 17:39:18'),
(27, 1, 3, 'login', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-12 18:02:26'),
(28, NULL, 1, 'login_failed', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-12 18:03:52'),
(29, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-12 18:03:59'),
(30, NULL, 1, 'login_failed', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 20:18:22'),
(31, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 20:18:30'),
(32, 1, 1, 'user_deactivated', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 21:20:07'),
(33, 1, 1, 'user_activated', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 21:20:11'),
(34, 1, 3, 'login', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 21:20:46'),
(35, NULL, 1, 'login_failed', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 21:37:46'),
(36, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 21:37:54'),
(37, NULL, 1, 'login_failed', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 21:38:00'),
(38, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 21:38:10'),
(39, 1, 3, 'login', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 21:38:30'),
(40, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 21:38:41'),
(41, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 21:41:32'),
(42, NULL, 1, 'login_failed', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 21:42:58'),
(43, NULL, 1, 'login_failed', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 21:43:00'),
(44, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 21:43:07'),
(45, NULL, 1, 'login_failed', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 21:44:41'),
(46, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 21:44:48'),
(47, NULL, 1, 'login_failed', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 21:44:51'),
(48, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 21:45:01'),
(49, NULL, 1, 'login_failed', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 21:45:28'),
(50, 1, 2, 'login', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 21:45:40'),
(51, NULL, 2, 'login_failed', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 21:45:43'),
(52, 1, 2, 'login', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 21:45:48'),
(53, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 21:47:39'),
(54, 1, 3, 'login', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 21:48:00'),
(55, 1, 2, 'login', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 21:49:31'),
(56, 1, 2, 'user_created', 'users', 4, NULL, '{\"username\":\"admin2\",\"email\":\"alipaidvip2@gmail.com\",\"role_id\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 21:51:20'),
(57, 1, 2, 'user_deleted', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 21:51:34'),
(58, 1, 2, 'user_created', 'users', 5, NULL, '{\"username\":\"yousef\",\"email\":\"sales.egybella@gmail.com\",\"role_id\":3}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 21:52:12'),
(59, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 21:52:42'),
(60, 1, 5, 'login', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 21:53:49'),
(61, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 21:54:56'),
(62, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 22:15:44'),
(63, 1, 1, 'user_created', 'users', 6, NULL, '{\"username\":\"amr\",\"email\":\"alivip2@hotmail.com\",\"role_id\":8}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 22:17:05'),
(64, 1, 6, 'login', 'users', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 22:17:21'),
(65, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 22:18:08'),
(66, 1, 6, 'login', 'users', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 22:18:56'),
(67, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-12 22:19:24'),
(68, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-13 12:26:36'),
(69, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-13 13:36:21'),
(70, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-13 16:43:59'),
(71, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-13 16:44:06'),
(72, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-13 16:44:22'),
(73, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-13 16:44:25'),
(74, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-13 16:44:42'),
(75, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-13 16:45:49'),
(76, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-13 16:46:40'),
(77, 1, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-13 16:53:32');

-- --------------------------------------------------------

--
-- بنية الجدول `branches`
--

CREATE TABLE `branches` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `name_en` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `manager_id` int(10) UNSIGNED DEFAULT NULL,
  `is_main` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `branches`
--

INSERT INTO `branches` (`id`, `company_id`, `code`, `name`, `name_en`, `address`, `city`, `phone`, `email`, `manager_id`, `is_main`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 'BR-001', 'الفرع الرئيسي', NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, NULL, '2025-12-12 21:42:40', '2025-12-12 21:42:40'),
(2, 1, 'BR-002', 'فرع الاسكندرية', NULL, 'الاسكندرية', 'الاسكندرية', '01201214479', NULL, NULL, 1, 1, 1, '2025-12-12 22:00:43', '2025-12-12 22:00:43'),
(3, 1, 'BR-003', 'الغردقة', NULL, 'الغردقة', 'الغردقة', '0125465489', NULL, NULL, 0, 1, 1, '2025-12-13 13:37:47', '2025-12-13 13:37:47');

-- --------------------------------------------------------

--
-- بنية الجدول `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `parent_id` int(10) UNSIGNED DEFAULT NULL,
  `type` enum('product','expense','income') DEFAULT 'product',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `companies`
--

CREATE TABLE `companies` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `name_en` varchar(255) DEFAULT NULL,
  `logo` varchar(500) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `tax_number` varchar(100) DEFAULT NULL,
  `commercial_registry` varchar(100) DEFAULT NULL,
  `currency` varchar(10) DEFAULT 'SAR',
  `currency_symbol` varchar(10) DEFAULT 'ر.س',
  `tax_rate` decimal(5,2) DEFAULT 15.00,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `companies`
--

INSERT INTO `companies` (`id`, `name`, `name_en`, `logo`, `address`, `phone`, `email`, `tax_number`, `commercial_registry`, `currency`, `currency_symbol`, `tax_rate`, `status`, `created_at`) VALUES
(1, 'ايجي بيلا', 'egybella', NULL, 'القاهرة', '01002206642', 'aliaivip2@gmail.com', '564646464', '56454864645613164', 'ج.م', 'ج.م', 1.00, 'active', '2025-12-12 00:26:59');

-- --------------------------------------------------------

--
-- بنية الجدول `company_modules`
--

CREATE TABLE `company_modules` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED NOT NULL,
  `module_id` int(10) UNSIGNED NOT NULL,
  `status` enum('enabled','disabled') DEFAULT 'enabled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `company_modules`
--

INSERT INTO `company_modules` (`id`, `company_id`, `module_id`, `status`, `created_at`) VALUES
(1, 1, 1, 'enabled', '2025-12-12 00:26:59'),
(2, 1, 2, 'enabled', '2025-12-12 00:26:59'),
(3, 1, 3, 'enabled', '2025-12-12 00:26:59'),
(4, 1, 4, 'enabled', '2025-12-12 00:26:59'),
(5, 1, 5, 'enabled', '2025-12-12 00:26:59'),
(6, 1, 6, 'enabled', '2025-12-12 00:26:59'),
(9, 1, 7, 'enabled', '2025-12-12 16:45:32'),
(10, 1, 8, 'enabled', '2025-12-12 16:45:32'),
(12, 1, 9, 'enabled', '2025-12-12 17:15:18');

-- --------------------------------------------------------

--
-- بنية الجدول `customers`
--

CREATE TABLE `customers` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `mobile` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `tax_number` varchar(100) DEFAULT NULL,
  `credit_limit` decimal(15,2) DEFAULT 0.00,
  `balance` decimal(15,2) DEFAULT 0.00,
  `status` enum('active','inactive') DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `opening_balance` decimal(15,2) DEFAULT 0.00 COMMENT 'رصيد أول المدة'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `customers`
--

INSERT INTO `customers` (`id`, `company_id`, `code`, `name`, `email`, `phone`, `mobile`, `address`, `city`, `tax_number`, `credit_limit`, `balance`, `status`, `notes`, `created_by`, `created_at`, `opening_balance`) VALUES
(1, 1, NULL, 'علي', 'sales.egybella@gmail.com', '01557399117', NULL, 'ببباب', NULL, NULL, 0.00, 0.00, 'active', NULL, NULL, '2025-12-12 16:34:15', 0.00),
(2, 1, NULL, '‪ali Mohamed Ebrahim', 'ali@factory.com', '01002206642', NULL, 'الاسكندريه', NULL, NULL, 0.00, 0.00, 'active', NULL, NULL, '2025-12-13 15:09:49', 0.00);

-- --------------------------------------------------------

--
-- بنية الجدول `departments`
--

CREATE TABLE `departments` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `manager_id` int(10) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `departments`
--

INSERT INTO `departments` (`id`, `company_id`, `name`, `description`, `manager_id`, `is_active`, `created_at`) VALUES
(1, 1, 'الإدارة', 'الادارة', NULL, 1, '2025-12-12 16:47:26'),
(2, 1, 'المبيعات', 'المبيعات', NULL, 1, '2025-12-12 16:47:26'),
(3, 1, 'الحسابات', 'الحسابات', NULL, 1, '2025-12-13 15:17:51');

-- --------------------------------------------------------

--
-- بنية الجدول `employees`
--

CREATE TABLE `employees` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED NOT NULL,
  `employee_number` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `national_id` varchar(50) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female') DEFAULT NULL,
  `marital_status` enum('single','married','divorced','widowed') DEFAULT NULL,
  `nationality` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `department_id` int(10) UNSIGNED DEFAULT NULL,
  `position_id` int(10) UNSIGNED DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `contract_type` enum('permanent','contract','part_time','probation') DEFAULT 'permanent',
  `salary` decimal(10,2) DEFAULT 0.00,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account` varchar(50) DEFAULT NULL,
  `iban` varchar(50) DEFAULT NULL,
  `status` enum('active','inactive','on_leave','terminated') DEFAULT 'active',
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `employees`
--

INSERT INTO `employees` (`id`, `company_id`, `employee_number`, `first_name`, `last_name`, `email`, `phone`, `mobile`, `national_id`, `date_of_birth`, `gender`, `marital_status`, `nationality`, `address`, `department_id`, `position_id`, `hire_date`, `contract_type`, `salary`, `bank_name`, `bank_account`, `iban`, `status`, `created_by`, `created_at`) VALUES
(1, 1, '1', 'علي', 'محمد', '', '', '', '15646546', '1985-01-02', 'male', 'married', 'مصري', '', 1, 1, '2000-01-01', 'permanent', 10000.00, '', '', '', 'active', 1, '2025-12-12 17:30:58');

-- --------------------------------------------------------

--
-- بنية الجدول `inventory_movements`
--

CREATE TABLE `inventory_movements` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `warehouse_id` int(10) UNSIGNED NOT NULL,
  `movement_type` enum('in','out','transfer_out','transfer_in','adjustment','opening_balance','production_in','production_out') NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(10) UNSIGNED DEFAULT NULL,
  `quantity` decimal(15,4) NOT NULL,
  `unit_cost` decimal(15,4) DEFAULT 0.0000,
  `balance_before` decimal(15,4) DEFAULT 0.0000,
  `balance_after` decimal(15,4) DEFAULT 0.0000,
  `notes` text DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `journal_entries`
--

CREATE TABLE `journal_entries` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `entry_number` varchar(50) NOT NULL,
  `entry_date` date NOT NULL,
  `date` date NOT NULL,
  `description` text DEFAULT NULL,
  `debit` decimal(15,2) DEFAULT 0.00,
  `credit` decimal(15,2) DEFAULT 0.00,
  `status` enum('draft','posted','void') DEFAULT 'posted',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `journal_entry_lines`
--

CREATE TABLE `journal_entry_lines` (
  `id` int(11) NOT NULL,
  `entry_id` int(11) NOT NULL,
  `account_id` int(11) DEFAULT NULL,
  `account_name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `debit` decimal(15,2) DEFAULT 0.00,
  `credit` decimal(15,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `modules`
--

CREATE TABLE `modules` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `name_ar` varchar(100) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `required_permission` varchar(50) DEFAULT NULL COMMENT 'الصلاحية المطلوبة للوصول',
  `icon` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_system` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `modules`
--

INSERT INTO `modules` (`id`, `name`, `name_ar`, `slug`, `required_permission`, `icon`, `description`, `sort_order`, `is_system`, `created_at`) VALUES
(1, 'Dashboard', 'لوحة التحكم', 'dashboard', NULL, 'fas fa-tachometer-alt', NULL, 1, 1, '2025-12-12 00:26:59'),
(2, 'Sales', 'المبيعات', 'sales', 'sales.view', 'fas fa-shopping-cart', NULL, 2, 0, '2025-12-12 00:26:59'),
(3, 'Purchases', 'المشتريات', 'purchases', 'purchases.view', 'fas fa-truck', NULL, 3, 0, '2025-12-12 00:26:59'),
(4, 'Inventory', 'المخازن', 'inventory', 'inventory.view', 'fas fa-warehouse', NULL, 4, 0, '2025-12-12 00:26:59'),
(5, 'Accounting', 'الحسابات', 'accounting', 'accounting.view', 'fas fa-calculator', NULL, 5, 0, '2025-12-12 00:26:59'),
(6, 'Settings', 'الإعدادات', 'settings', 'settings.view', 'fas fa-cog', NULL, 8, 1, '2025-12-12 00:26:59'),
(7, 'HR', 'شؤون العاملين', 'hr', 'hr.view', 'fas fa-users', NULL, 6, 0, '2025-12-12 16:45:32'),
(8, 'Reports', 'التقارير', 'reports', 'reports.view', 'fas fa-chart-bar', NULL, 7, 0, '2025-12-12 16:45:32'),
(9, 'Production', 'الإنتاج', 'production', 'production.view', 'fas fa-industry', NULL, 5, 0, '2025-12-12 17:15:18');

-- --------------------------------------------------------

--
-- بنية الجدول `notifications`
--

CREATE TABLE `notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text DEFAULT NULL,
  `type` enum('info','success','warning','error') DEFAULT 'info',
  `link` varchar(255) DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `opening_stock`
--

CREATE TABLE `opening_stock` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `warehouse_id` int(10) UNSIGNED NOT NULL,
  `quantity` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `unit_cost` decimal(15,4) DEFAULT 0.0000,
  `total_cost` decimal(15,4) DEFAULT 0.0000,
  `opening_date` date NOT NULL,
  `fiscal_year` int(11) DEFAULT NULL COMMENT 'السنة المالية',
  `notes` text DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `payment_allocations`
--

CREATE TABLE `payment_allocations` (
  `id` int(10) UNSIGNED NOT NULL,
  `payment_id` int(10) UNSIGNED NOT NULL COMMENT 'رقم سند القبض/الصرف',
  `invoice_type` enum('sales','purchase') NOT NULL,
  `invoice_id` int(10) UNSIGNED NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `permissions`
--

CREATE TABLE `permissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `module_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `name_ar` varchar(150) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `permissions`
--

INSERT INTO `permissions` (`id`, `module_id`, `name`, `name_ar`, `slug`, `created_at`) VALUES
(1, 1, 'View Dashboard', 'عرض لوحة التحكم', 'dashboard.view', '2025-12-12 17:59:10'),
(2, 2, 'View Sales', 'عرض المبيعات', 'sales.view', '2025-12-12 17:59:10'),
(3, 2, 'Create Invoice', 'إضافة فاتورة', 'sales.create', '2025-12-12 17:59:10'),
(4, 2, 'Edit Invoice', 'تعديل فاتورة', 'sales.edit', '2025-12-12 17:59:10'),
(5, 2, 'Delete Invoice', 'حذف فاتورة', 'sales.delete', '2025-12-12 17:59:10'),
(6, 2, 'View Customers', 'عرض العملاء', 'customers.view', '2025-12-12 17:59:10'),
(7, 2, 'Manage Customers', 'إدارة العملاء', 'customers.manage', '2025-12-12 17:59:10'),
(8, 3, 'View Purchases', 'عرض المشتريات', 'purchases.view', '2025-12-12 17:59:10'),
(9, 3, 'Create Purchase', 'إضافة فاتورة مشتريات', 'purchases.create', '2025-12-12 17:59:10'),
(10, 3, 'Edit Purchase', 'تعديل فاتورة مشتريات', 'purchases.edit', '2025-12-12 17:59:10'),
(11, 3, 'Delete Purchase', 'حذف فاتورة مشتريات', 'purchases.delete', '2025-12-12 17:59:10'),
(12, 3, 'View Suppliers', 'عرض الموردين', 'suppliers.view', '2025-12-12 17:59:10'),
(13, 3, 'Manage Suppliers', 'إدارة الموردين', 'suppliers.manage', '2025-12-12 17:59:10'),
(14, 4, 'View Products', 'عرض المنتجات', 'products.view', '2025-12-12 17:59:10'),
(15, 4, 'Create Product', 'إضافة منتج', 'products.create', '2025-12-12 17:59:10'),
(16, 4, 'Edit Product', 'تعديل منتج', 'products.edit', '2025-12-12 17:59:10'),
(17, 4, 'Delete Product', 'حذف منتج', 'products.delete', '2025-12-12 17:59:10'),
(18, 4, 'Manage Inventory', 'إدارة المخزون', 'inventory.manage', '2025-12-12 17:59:10'),
(19, 5, 'View Entries', 'عرض القيود', 'accounting.view', '2025-12-12 17:59:10'),
(20, 5, 'Create Entry', 'إضافة قيد', 'accounting.create', '2025-12-12 17:59:10'),
(21, 5, 'Edit Entry', 'تعديل قيد', 'accounting.edit', '2025-12-12 17:59:10'),
(22, 5, 'Delete Entry', 'حذف قيد', 'accounting.delete', '2025-12-12 17:59:10'),
(23, 5, 'Manage Accounts', 'إدارة الحسابات', 'accounts.manage', '2025-12-12 17:59:10'),
(24, 6, 'Manage Settings', 'إدارة الإعدادات', 'settings.manage', '2025-12-12 17:59:10'),
(25, 6, 'Manage Users', 'إدارة المستخدمين', 'users.manage', '2025-12-12 17:59:10'),
(26, 6, 'Manage Roles', 'إدارة الأدوار', 'roles.manage', '2025-12-12 17:59:10'),
(27, 6, 'Manage Backup', 'النسخ الاحتياطي', 'backup.manage', '2025-12-12 17:59:10'),
(28, 6, 'Import Export', 'استيراد/تصدير', 'import_export.manage', '2025-12-12 17:59:10'),
(29, 7, 'View Employees', 'عرض الموظفين', 'employees.view', '2025-12-12 17:59:10'),
(30, 7, 'Create Employee', 'إضافة موظف', 'employees.create', '2025-12-12 17:59:10'),
(31, 7, 'Edit Employee', 'تعديل موظف', 'employees.edit', '2025-12-12 17:59:10'),
(32, 7, 'Delete Employee', 'حذف موظف', 'employees.delete', '2025-12-12 17:59:10'),
(33, 7, 'Manage Payroll', 'إدارة الرواتب', 'payroll.manage', '2025-12-12 17:59:10'),
(34, 8, 'View Sales Report', 'تقرير المبيعات', 'reports.sales', '2025-12-12 17:59:10'),
(35, 8, 'View Purchases Report', 'تقرير المشتريات', 'reports.purchases', '2025-12-12 17:59:10'),
(36, 8, 'View Inventory Report', 'تقرير المخزون', 'reports.inventory', '2025-12-12 17:59:10'),
(37, 8, 'View Profit Report', 'تقرير الأرباح', 'reports.profit', '2025-12-12 17:59:10'),
(38, 8, 'View Employees Report', 'تقرير الموظفين', 'reports.employees', '2025-12-12 17:59:10'),
(39, 9, 'View Production', 'عرض الإنتاج', 'production.view', '2025-12-12 17:59:10'),
(40, 9, 'Manage Orders', 'إدارة أوامر الإنتاج', 'production.manage', '2025-12-12 17:59:10'),
(41, 9, 'Manage BOM', 'إدارة قوائم المواد', 'bom.manage', '2025-12-12 17:59:10'),
(42, 2, 'View Sales', 'عرض المبيعات', 'sales.view', '2025-12-12 22:13:01'),
(43, 2, 'Create Sales', 'إنشاء مبيعات', 'sales.create', '2025-12-12 22:13:01'),
(44, 2, 'Edit Sales', 'تعديل المبيعات', 'sales.edit', '2025-12-12 22:13:01'),
(45, 2, 'Delete Sales', 'حذف المبيعات', 'sales.delete', '2025-12-12 22:13:01'),
(46, 3, 'View Purchases', 'عرض المشتريات', 'purchases.view', '2025-12-12 22:13:01'),
(47, 3, 'Create Purchases', 'إنشاء مشتريات', 'purchases.create', '2025-12-12 22:13:01'),
(48, 3, 'Edit Purchases', 'تعديل المشتريات', 'purchases.edit', '2025-12-12 22:13:01'),
(49, 3, 'Delete Purchases', 'حذف المشتريات', 'purchases.delete', '2025-12-12 22:13:01'),
(50, 4, 'View Inventory', 'عرض المخزون', 'inventory.view', '2025-12-12 22:13:01'),
(51, 4, 'Create Inventory', 'إضافة منتجات', 'inventory.create', '2025-12-12 22:13:01'),
(52, 4, 'Edit Inventory', 'تعديل المخزون', 'inventory.edit', '2025-12-12 22:13:01'),
(53, 4, 'Delete Inventory', 'حذف المخزون', 'inventory.delete', '2025-12-12 22:13:01'),
(54, 5, 'View Accounting', 'عرض المحاسبة', 'accounting.view', '2025-12-12 22:13:01'),
(55, 5, 'Create Accounting', 'إنشاء قيود', 'accounting.create', '2025-12-12 22:13:01'),
(56, 5, 'Edit Accounting', 'تعديل المحاسبة', 'accounting.edit', '2025-12-12 22:13:01'),
(57, 5, 'Delete Accounting', 'حذف المحاسبة', 'accounting.delete', '2025-12-12 22:13:01'),
(58, 7, 'View HR', 'عرض الموارد البشرية', 'hr.view', '2025-12-12 22:13:01'),
(59, 7, 'Create HR', 'إضافة موظفين', 'hr.create', '2025-12-12 22:13:01'),
(60, 7, 'Edit HR', 'تعديل الموظفين', 'hr.edit', '2025-12-12 22:13:01'),
(61, 7, 'Delete HR', 'حذف الموظفين', 'hr.delete', '2025-12-12 22:13:01'),
(62, 8, 'View Reports', 'عرض التقارير', 'reports.view', '2025-12-12 22:13:01'),
(63, 8, 'Export Reports', 'تصدير التقارير', 'reports.export', '2025-12-12 22:13:01'),
(64, 6, 'View Settings', 'عرض الإعدادات', 'settings.view', '2025-12-12 22:13:01'),
(65, 6, 'Edit Settings', 'تعديل الإعدادات', 'settings.edit', '2025-12-12 22:13:01'),
(66, 6, 'Manage Users', 'إدارة المستخدمين', 'settings.users', '2025-12-12 22:13:01'),
(67, 6, 'Manage Roles', 'إدارة الأدوار', 'settings.roles', '2025-12-12 22:13:01'),
(68, 9, 'View Production', 'عرض الإنتاج', 'production.view', '2025-12-12 22:13:01'),
(69, 9, 'Create Production', 'إنشاء أوامر إنتاج', 'production.create', '2025-12-12 22:13:01'),
(70, 2, 'POS Access', 'الوصول لنقطة البيع', 'pos.access', '2025-12-12 22:13:01'),
(71, 2, 'View Sales', 'عرض المبيعات', 'sales.view', '2025-12-12 22:14:12'),
(72, 2, 'Create Sales', 'إنشاء مبيعات', 'sales.create', '2025-12-12 22:14:12'),
(73, 2, 'Edit Sales', 'تعديل المبيعات', 'sales.edit', '2025-12-12 22:14:12'),
(74, 2, 'Delete Sales', 'حذف المبيعات', 'sales.delete', '2025-12-12 22:14:12'),
(75, 3, 'View Purchases', 'عرض المشتريات', 'purchases.view', '2025-12-12 22:14:12'),
(76, 3, 'Create Purchases', 'إنشاء مشتريات', 'purchases.create', '2025-12-12 22:14:12'),
(77, 3, 'Edit Purchases', 'تعديل المشتريات', 'purchases.edit', '2025-12-12 22:14:12'),
(78, 3, 'Delete Purchases', 'حذف المشتريات', 'purchases.delete', '2025-12-12 22:14:12'),
(79, 4, 'View Inventory', 'عرض المخزون', 'inventory.view', '2025-12-12 22:14:12'),
(80, 4, 'Create Inventory', 'إضافة منتجات', 'inventory.create', '2025-12-12 22:14:12'),
(81, 4, 'Edit Inventory', 'تعديل المخزون', 'inventory.edit', '2025-12-12 22:14:12'),
(82, 4, 'Delete Inventory', 'حذف المخزون', 'inventory.delete', '2025-12-12 22:14:12'),
(83, 5, 'View Accounting', 'عرض المحاسبة', 'accounting.view', '2025-12-12 22:14:12'),
(84, 5, 'Create Accounting', 'إنشاء قيود', 'accounting.create', '2025-12-12 22:14:12'),
(85, 5, 'Edit Accounting', 'تعديل المحاسبة', 'accounting.edit', '2025-12-12 22:14:12'),
(86, 5, 'Delete Accounting', 'حذف المحاسبة', 'accounting.delete', '2025-12-12 22:14:12'),
(87, 7, 'View HR', 'عرض الموارد البشرية', 'hr.view', '2025-12-12 22:14:12'),
(88, 7, 'Create HR', 'إضافة موظفين', 'hr.create', '2025-12-12 22:14:12'),
(89, 7, 'Edit HR', 'تعديل الموظفين', 'hr.edit', '2025-12-12 22:14:12'),
(90, 7, 'Delete HR', 'حذف الموظفين', 'hr.delete', '2025-12-12 22:14:12'),
(91, 8, 'View Reports', 'عرض التقارير', 'reports.view', '2025-12-12 22:14:12'),
(92, 8, 'Export Reports', 'تصدير التقارير', 'reports.export', '2025-12-12 22:14:12'),
(93, 6, 'View Settings', 'عرض الإعدادات', 'settings.view', '2025-12-12 22:14:12'),
(94, 6, 'Edit Settings', 'تعديل الإعدادات', 'settings.edit', '2025-12-12 22:14:12'),
(95, 6, 'Manage Users', 'إدارة المستخدمين', 'settings.users', '2025-12-12 22:14:12'),
(96, 6, 'Manage Roles', 'إدارة الأدوار', 'settings.roles', '2025-12-12 22:14:12'),
(97, 9, 'View Production', 'عرض الإنتاج', 'production.view', '2025-12-12 22:14:12'),
(98, 9, 'Create Production', 'إنشاء أوامر إنتاج', 'production.create', '2025-12-12 22:14:12'),
(99, 2, 'POS Access', 'الوصول لنقطة البيع', 'pos.access', '2025-12-12 22:14:12'),
(100, 4, 'Manage Stock Transfers', 'إدارة التحويلات المخزنية', 'inventory.transfers', '2025-12-13 13:32:47'),
(101, 4, 'Opening Stock', 'أرصدة أول المدة', 'inventory.opening', '2025-12-13 13:32:47'),
(102, 2, 'View Agent Reports', 'تقارير المندوبين', 'sales.agent_reports', '2025-12-13 13:32:47'),
(103, 4, 'Manage Stock Transfers', 'إدارة التحويلات المخزنية', 'inventory.transfers', '2025-12-13 13:34:26'),
(104, 4, 'Opening Stock', 'أرصدة أول المدة', 'inventory.opening', '2025-12-13 13:34:26'),
(105, 2, 'View Agent Reports', 'تقارير المندوبين', 'sales.agent_reports', '2025-12-13 13:34:26'),
(106, 4, 'Manage Stock Transfers', 'إدارة التحويلات المخزنية', 'inventory.transfers', '2025-12-13 13:44:33'),
(107, 4, 'Opening Stock', 'أرصدة أول المدة', 'inventory.opening', '2025-12-13 13:44:33'),
(108, 2, 'View Agent Reports', 'تقارير المندوبين', 'sales.agent_reports', '2025-12-13 13:44:33');

-- --------------------------------------------------------

--
-- بنية الجدول `positions`
--

CREATE TABLE `positions` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `positions`
--

INSERT INTO `positions` (`id`, `company_id`, `name`, `is_active`, `created_at`) VALUES
(1, 1, 'مدير', 1, '2025-12-12 16:47:26'),
(2, 1, 'موظف', 1, '2025-12-12 16:47:26');

-- --------------------------------------------------------

--
-- بنية الجدول `production_bom`
--

CREATE TABLE `production_bom` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL COMMENT 'المنتج النهائي',
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `quantity_per_batch` decimal(10,2) DEFAULT 1.00,
  `estimated_time` int(11) DEFAULT NULL COMMENT 'الوقت المقدر بالدقائق',
  `labor_cost` decimal(10,2) DEFAULT 0.00,
  `overhead_cost` decimal(10,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `production_bom`
--

INSERT INTO `production_bom` (`id`, `company_id`, `product_id`, `name`, `description`, `quantity_per_batch`, `estimated_time`, `labor_cost`, `overhead_cost`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(2, 1, 2, 'ارز مربوحة 1ك', '', 1.00, NULL, 0.00, 0.00, 1, 1, '2025-12-13 16:23:20', '2025-12-13 16:23:20');

-- --------------------------------------------------------

--
-- بنية الجدول `production_bom_items`
--

CREATE TABLE `production_bom_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `bom_id` int(10) UNSIGNED NOT NULL,
  `material_id` int(10) UNSIGNED NOT NULL COMMENT 'المادة الخام (منتج)',
  `quantity` decimal(10,3) NOT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `waste_percentage` decimal(5,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `production_bom_items`
--

INSERT INTO `production_bom_items` (`id`, `bom_id`, `material_id`, `quantity`, `unit`, `waste_percentage`, `notes`) VALUES
(1, 2, 3, 1.000, 'كيلو', 0.00, NULL),
(2, 2, 1, 1.000, 'كيس', 0.00, NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `production_orders`
--

CREATE TABLE `production_orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `bom_id` int(10) UNSIGNED DEFAULT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `produced_quantity` decimal(10,2) DEFAULT 0.00,
  `start_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `status` enum('draft','pending','in_progress','completed','cancelled') DEFAULT 'draft',
  `priority` enum('low','normal','high','urgent') DEFAULT 'normal',
  `notes` text DEFAULT NULL,
  `total_material_cost` decimal(12,2) DEFAULT 0.00,
  `total_labor_cost` decimal(12,2) DEFAULT 0.00,
  `total_cost` decimal(12,2) DEFAULT 0.00,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `output_warehouse_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'مخزن استلام المنتج النهائي',
  `raw_material_warehouse_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'مخزن صرف المواد الخام'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `production_orders`
--

INSERT INTO `production_orders` (`id`, `company_id`, `order_number`, `bom_id`, `product_id`, `quantity`, `produced_quantity`, `start_date`, `due_date`, `completion_date`, `status`, `priority`, `notes`, `total_material_cost`, `total_labor_cost`, `total_cost`, `created_by`, `created_at`, `updated_at`, `output_warehouse_id`, `raw_material_warehouse_id`) VALUES
(1, 1, 'PO-20251213-2766', 2, 2, 1000.00, 1000.00, NULL, '2025-12-13', NULL, 'completed', 'normal', '', 0.00, 0.00, 0.00, 1, '2025-12-13 16:30:36', '2025-12-13 16:31:48', NULL, NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `production_order_materials`
--

CREATE TABLE `production_order_materials` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `material_id` int(10) UNSIGNED NOT NULL,
  `required_quantity` decimal(10,3) NOT NULL,
  `consumed_quantity` decimal(10,3) DEFAULT 0.000,
  `unit_cost` decimal(10,2) DEFAULT 0.00,
  `total_cost` decimal(12,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `name_en` varchar(255) DEFAULT NULL,
  `code` varchar(100) DEFAULT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `unit_id` int(10) UNSIGNED DEFAULT NULL,
  `purchase_price` decimal(15,2) DEFAULT 0.00,
  `selling_price` decimal(15,2) DEFAULT 0.00,
  `min_selling_price` decimal(15,2) DEFAULT 0.00,
  `wholesale_price` decimal(15,2) DEFAULT 0.00,
  `tax_rate` decimal(5,2) DEFAULT 0.00,
  `is_taxable` tinyint(1) DEFAULT 1,
  `min_stock` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `is_service` tinyint(1) DEFAULT 0,
  `track_inventory` tinyint(1) DEFAULT 1,
  `image` varchar(500) DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `product_type` enum('raw_material','finished_product','packaging') DEFAULT 'finished_product' COMMENT 'نوع المنتج: مادة خام، منتج نهائي، تعبئة'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `products`
--

INSERT INTO `products` (`id`, `company_id`, `name`, `name_en`, `code`, `barcode`, `description`, `category_id`, `unit_id`, `purchase_price`, `selling_price`, `min_selling_price`, `wholesale_price`, `tax_rate`, `is_taxable`, `min_stock`, `is_active`, `is_service`, `track_inventory`, `image`, `created_by`, `created_at`, `product_type`) VALUES
(1, 1, 'كيس أرز مربوحة', NULL, 'P001', NULL, NULL, NULL, NULL, 190.00, 190.00, 0.00, 0.00, 0.00, 1, 50, 1, 0, 1, NULL, NULL, '2025-12-12 16:04:29', 'finished_product'),
(2, 1, 'ارز مربوحة 1ك', NULL, 'EMP-1757695836944', NULL, NULL, NULL, NULL, 19.00, 20.00, 0.00, 0.00, 0.00, 1, 1000, 1, 0, 1, NULL, NULL, '2025-12-12 21:21:31', 'finished_product'),
(3, 1, 'ارز خام ٨٪', NULL, '45655', NULL, NULL, NULL, NULL, 16.00, 17.00, 0.00, 0.00, 0.00, 1, 1000, 1, 0, 1, NULL, NULL, '2025-12-13 12:29:49', 'finished_product');

-- --------------------------------------------------------

--
-- بنية الجدول `product_stock`
--

CREATE TABLE `product_stock` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `warehouse_id` int(10) UNSIGNED NOT NULL,
  `quantity` decimal(15,3) DEFAULT 0.000,
  `avg_cost` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `purchase_invoices`
--

CREATE TABLE `purchase_invoices` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `supplier_id` int(10) UNSIGNED DEFAULT NULL,
  `invoice_date` date NOT NULL,
  `subtotal` decimal(15,2) DEFAULT 0.00,
  `tax_amount` decimal(15,2) DEFAULT 0.00,
  `total` decimal(15,2) DEFAULT 0.00,
  `paid_amount` decimal(15,2) DEFAULT 0.00,
  `payment_status` enum('unpaid','partial','paid') DEFAULT 'unpaid',
  `status` enum('draft','confirmed','cancelled') DEFAULT 'draft',
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `warehouse_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'المخزن الوجهة',
  `supplier_invoice_number` varchar(100) DEFAULT NULL COMMENT 'رقم فاتورة المورد'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `purchase_invoice_items`
--

CREATE TABLE `purchase_invoice_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `invoice_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED DEFAULT NULL,
  `quantity` decimal(15,3) DEFAULT 1.000,
  `unit_price` decimal(15,2) DEFAULT 0.00,
  `total` decimal(15,2) DEFAULT 0.00,
  `warehouse_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'المخزن'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `roles`
--

CREATE TABLE `roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `name_ar` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_system` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `roles`
--

INSERT INTO `roles` (`id`, `name`, `name_ar`, `description`, `is_system`, `created_at`) VALUES
(1, 'super_admin', 'مدير النظام', NULL, 1, '2025-12-12 00:26:59'),
(2, 'manager', 'مدير الشركة', NULL, 1, '2025-12-12 00:26:59'),
(3, 'accountant', 'محاسب', NULL, 1, '2025-12-12 00:26:59'),
(4, 'sales', 'موظف مبيعات', NULL, 1, '2025-12-12 00:26:59'),
(5, 'storekeeper', 'أمين مخزن', NULL, 1, '2025-12-12 00:26:59'),
(6, 'purchasing', 'موظف مشتريات', 'إدارة المشتريات والموردين', 0, '2025-12-12 17:59:10'),
(7, 'hr', 'موظف موارد بشرية', 'إدارة شؤون الموظفين والرواتب', 0, '2025-12-12 17:59:10'),
(8, 'customer_service', 'خدمة عملاء', 'خدمة عملاء', 0, '2025-12-12 18:01:00');

-- --------------------------------------------------------

--
-- بنية الجدول `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `role_id` int(10) UNSIGNED NOT NULL,
  `permission_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `role_permissions`
--

INSERT INTO `role_permissions` (`id`, `role_id`, `permission_id`, `created_at`) VALUES
(1, 1, 1, '2025-12-12 17:59:10'),
(2, 1, 2, '2025-12-12 17:59:10'),
(3, 1, 3, '2025-12-12 17:59:10'),
(4, 1, 4, '2025-12-12 17:59:10'),
(5, 1, 5, '2025-12-12 17:59:10'),
(6, 1, 6, '2025-12-12 17:59:10'),
(7, 1, 7, '2025-12-12 17:59:10'),
(8, 1, 8, '2025-12-12 17:59:10'),
(9, 1, 9, '2025-12-12 17:59:10'),
(10, 1, 10, '2025-12-12 17:59:10'),
(11, 1, 11, '2025-12-12 17:59:10'),
(12, 1, 12, '2025-12-12 17:59:10'),
(13, 1, 13, '2025-12-12 17:59:10'),
(14, 1, 14, '2025-12-12 17:59:10'),
(15, 1, 15, '2025-12-12 17:59:10'),
(16, 1, 16, '2025-12-12 17:59:10'),
(17, 1, 17, '2025-12-12 17:59:10'),
(18, 1, 18, '2025-12-12 17:59:10'),
(19, 1, 19, '2025-12-12 17:59:10'),
(20, 1, 20, '2025-12-12 17:59:10'),
(21, 1, 21, '2025-12-12 17:59:10'),
(22, 1, 22, '2025-12-12 17:59:10'),
(23, 1, 23, '2025-12-12 17:59:10'),
(24, 1, 24, '2025-12-12 17:59:10'),
(25, 1, 25, '2025-12-12 17:59:10'),
(26, 1, 26, '2025-12-12 17:59:10'),
(27, 1, 27, '2025-12-12 17:59:10'),
(28, 1, 28, '2025-12-12 17:59:10'),
(29, 1, 29, '2025-12-12 17:59:10'),
(30, 1, 30, '2025-12-12 17:59:10'),
(31, 1, 31, '2025-12-12 17:59:10'),
(32, 1, 32, '2025-12-12 17:59:10'),
(33, 1, 33, '2025-12-12 17:59:10'),
(34, 1, 34, '2025-12-12 17:59:10'),
(35, 1, 35, '2025-12-12 17:59:10'),
(36, 1, 36, '2025-12-12 17:59:10'),
(37, 1, 37, '2025-12-12 17:59:10'),
(38, 1, 38, '2025-12-12 17:59:10'),
(39, 1, 39, '2025-12-12 17:59:10'),
(40, 1, 40, '2025-12-12 17:59:10'),
(41, 1, 41, '2025-12-12 17:59:10'),
(64, 2, 1, '2025-12-12 17:59:10'),
(65, 2, 2, '2025-12-12 17:59:10'),
(66, 2, 3, '2025-12-12 17:59:10'),
(67, 2, 4, '2025-12-12 17:59:10'),
(68, 2, 5, '2025-12-12 17:59:10'),
(69, 2, 6, '2025-12-12 17:59:10'),
(70, 2, 7, '2025-12-12 17:59:10'),
(71, 2, 8, '2025-12-12 17:59:10'),
(72, 2, 9, '2025-12-12 17:59:10'),
(73, 2, 10, '2025-12-12 17:59:10'),
(74, 2, 11, '2025-12-12 17:59:10'),
(75, 2, 12, '2025-12-12 17:59:10'),
(76, 2, 13, '2025-12-12 17:59:10'),
(77, 2, 14, '2025-12-12 17:59:10'),
(78, 2, 15, '2025-12-12 17:59:10'),
(79, 2, 16, '2025-12-12 17:59:10'),
(80, 2, 17, '2025-12-12 17:59:10'),
(81, 2, 18, '2025-12-12 17:59:10'),
(82, 2, 19, '2025-12-12 17:59:10'),
(83, 2, 20, '2025-12-12 17:59:10'),
(84, 2, 21, '2025-12-12 17:59:10'),
(85, 2, 22, '2025-12-12 17:59:10'),
(86, 2, 23, '2025-12-12 17:59:10'),
(87, 2, 24, '2025-12-12 17:59:10'),
(88, 2, 25, '2025-12-12 17:59:10'),
(89, 2, 28, '2025-12-12 17:59:10'),
(90, 2, 29, '2025-12-12 17:59:10'),
(91, 2, 30, '2025-12-12 17:59:10'),
(92, 2, 31, '2025-12-12 17:59:10'),
(93, 2, 32, '2025-12-12 17:59:10'),
(94, 2, 33, '2025-12-12 17:59:10'),
(95, 2, 34, '2025-12-12 17:59:10'),
(96, 2, 35, '2025-12-12 17:59:10'),
(97, 2, 36, '2025-12-12 17:59:10'),
(98, 2, 37, '2025-12-12 17:59:10'),
(99, 2, 38, '2025-12-12 17:59:10'),
(100, 2, 39, '2025-12-12 17:59:10'),
(101, 2, 40, '2025-12-12 17:59:10'),
(102, 2, 41, '2025-12-12 17:59:10'),
(158, 4, 14, '2025-12-12 17:59:10'),
(159, 4, 15, '2025-12-12 17:59:10'),
(160, 4, 16, '2025-12-12 17:59:10'),
(161, 4, 17, '2025-12-12 17:59:10'),
(162, 4, 18, '2025-12-12 17:59:10'),
(165, 5, 2, '2025-12-12 17:59:10'),
(166, 5, 3, '2025-12-12 17:59:10'),
(167, 5, 4, '2025-12-12 17:59:10'),
(168, 5, 5, '2025-12-12 17:59:10'),
(169, 5, 6, '2025-12-12 17:59:10'),
(170, 5, 7, '2025-12-12 17:59:10'),
(172, 6, 8, '2025-12-12 17:59:10'),
(173, 6, 9, '2025-12-12 17:59:10'),
(174, 6, 10, '2025-12-12 17:59:10'),
(175, 6, 11, '2025-12-12 17:59:10'),
(176, 6, 12, '2025-12-12 17:59:10'),
(177, 6, 13, '2025-12-12 17:59:10'),
(179, 7, 29, '2025-12-12 17:59:10'),
(180, 7, 30, '2025-12-12 17:59:10'),
(181, 7, 31, '2025-12-12 17:59:10'),
(182, 7, 32, '2025-12-12 17:59:10'),
(183, 7, 33, '2025-12-12 17:59:10'),
(189, 2, 26, '2025-12-12 22:13:01'),
(190, 2, 27, '2025-12-12 22:13:01'),
(191, 2, 42, '2025-12-12 22:13:01'),
(192, 2, 43, '2025-12-12 22:13:01'),
(193, 2, 44, '2025-12-12 22:13:01'),
(194, 2, 45, '2025-12-12 22:13:01'),
(195, 2, 46, '2025-12-12 22:13:01'),
(196, 2, 47, '2025-12-12 22:13:01'),
(197, 2, 48, '2025-12-12 22:13:01'),
(198, 2, 49, '2025-12-12 22:13:01'),
(199, 2, 50, '2025-12-12 22:13:01'),
(200, 2, 51, '2025-12-12 22:13:01'),
(201, 2, 52, '2025-12-12 22:13:01'),
(202, 2, 53, '2025-12-12 22:13:01'),
(203, 2, 54, '2025-12-12 22:13:01'),
(204, 2, 55, '2025-12-12 22:13:01'),
(205, 2, 56, '2025-12-12 22:13:01'),
(206, 2, 57, '2025-12-12 22:13:01'),
(207, 2, 58, '2025-12-12 22:13:01'),
(208, 2, 59, '2025-12-12 22:13:01'),
(209, 2, 60, '2025-12-12 22:13:01'),
(210, 2, 61, '2025-12-12 22:13:01'),
(211, 2, 62, '2025-12-12 22:13:01'),
(212, 2, 63, '2025-12-12 22:13:01'),
(213, 2, 64, '2025-12-12 22:13:01'),
(214, 2, 65, '2025-12-12 22:13:01'),
(215, 2, 66, '2025-12-12 22:13:01'),
(216, 2, 68, '2025-12-12 22:13:01'),
(217, 2, 69, '2025-12-12 22:13:01'),
(218, 2, 70, '2025-12-12 22:13:01'),
(251, 4, 2, '2025-12-12 22:13:01'),
(252, 4, 3, '2025-12-12 22:13:01'),
(253, 4, 4, '2025-12-12 22:13:01'),
(254, 4, 42, '2025-12-12 22:13:01'),
(255, 4, 43, '2025-12-12 22:13:01'),
(256, 4, 44, '2025-12-12 22:13:01'),
(257, 4, 50, '2025-12-12 22:13:01'),
(258, 4, 62, '2025-12-12 22:13:01'),
(259, 4, 70, '2025-12-12 22:13:01'),
(266, 2, 71, '2025-12-12 22:14:12'),
(267, 2, 72, '2025-12-12 22:14:12'),
(268, 2, 73, '2025-12-12 22:14:12'),
(269, 2, 74, '2025-12-12 22:14:12'),
(270, 2, 75, '2025-12-12 22:14:12'),
(271, 2, 76, '2025-12-12 22:14:12'),
(272, 2, 77, '2025-12-12 22:14:12'),
(273, 2, 78, '2025-12-12 22:14:12'),
(274, 2, 79, '2025-12-12 22:14:12'),
(275, 2, 80, '2025-12-12 22:14:12'),
(276, 2, 81, '2025-12-12 22:14:12'),
(277, 2, 82, '2025-12-12 22:14:12'),
(278, 2, 83, '2025-12-12 22:14:12'),
(279, 2, 84, '2025-12-12 22:14:12'),
(280, 2, 85, '2025-12-12 22:14:12'),
(281, 2, 86, '2025-12-12 22:14:12'),
(282, 2, 87, '2025-12-12 22:14:12'),
(283, 2, 88, '2025-12-12 22:14:12'),
(284, 2, 89, '2025-12-12 22:14:12'),
(285, 2, 90, '2025-12-12 22:14:12'),
(286, 2, 91, '2025-12-12 22:14:12'),
(287, 2, 92, '2025-12-12 22:14:12'),
(288, 2, 93, '2025-12-12 22:14:12'),
(289, 2, 94, '2025-12-12 22:14:12'),
(290, 2, 95, '2025-12-12 22:14:12'),
(291, 2, 97, '2025-12-12 22:14:12'),
(292, 2, 98, '2025-12-12 22:14:12'),
(293, 2, 99, '2025-12-12 22:14:12'),
(312, 4, 71, '2025-12-12 22:14:12'),
(313, 4, 72, '2025-12-12 22:14:12'),
(314, 4, 73, '2025-12-12 22:14:12'),
(315, 4, 79, '2025-12-12 22:14:12'),
(316, 4, 91, '2025-12-12 22:14:12'),
(317, 4, 99, '2025-12-12 22:14:12'),
(319, 8, 1, '2025-12-12 22:18:41'),
(320, 8, 2, '2025-12-12 22:18:41'),
(321, 8, 6, '2025-12-12 22:18:41'),
(322, 3, 1, '2025-12-13 17:00:24'),
(323, 3, 2, '2025-12-13 17:00:24'),
(324, 3, 3, '2025-12-13 17:00:24'),
(325, 3, 4, '2025-12-13 17:00:24'),
(326, 3, 5, '2025-12-13 17:00:24'),
(327, 3, 6, '2025-12-13 17:00:24'),
(328, 3, 7, '2025-12-13 17:00:24'),
(329, 3, 8, '2025-12-13 17:00:24'),
(330, 3, 9, '2025-12-13 17:00:24'),
(331, 3, 10, '2025-12-13 17:00:24'),
(332, 3, 11, '2025-12-13 17:00:24'),
(333, 3, 12, '2025-12-13 17:00:24'),
(334, 3, 13, '2025-12-13 17:00:24'),
(335, 3, 14, '2025-12-13 17:00:24'),
(336, 3, 15, '2025-12-13 17:00:24'),
(337, 3, 16, '2025-12-13 17:00:24'),
(338, 3, 17, '2025-12-13 17:00:24'),
(339, 3, 18, '2025-12-13 17:00:24'),
(340, 3, 19, '2025-12-13 17:00:24'),
(341, 3, 20, '2025-12-13 17:00:24'),
(342, 3, 21, '2025-12-13 17:00:24'),
(343, 3, 22, '2025-12-13 17:00:24'),
(344, 3, 23, '2025-12-13 17:00:24'),
(345, 3, 24, '2025-12-13 17:00:24'),
(346, 3, 25, '2025-12-13 17:00:24'),
(347, 3, 26, '2025-12-13 17:00:24'),
(348, 3, 27, '2025-12-13 17:00:24'),
(349, 3, 28, '2025-12-13 17:00:24'),
(350, 3, 29, '2025-12-13 17:00:24'),
(351, 3, 30, '2025-12-13 17:00:24'),
(352, 3, 41, '2025-12-13 17:00:24'),
(353, 3, 42, '2025-12-13 17:00:24'),
(354, 3, 43, '2025-12-13 17:00:24'),
(355, 3, 46, '2025-12-13 17:00:24'),
(356, 3, 47, '2025-12-13 17:00:24'),
(357, 3, 50, '2025-12-13 17:00:24'),
(358, 3, 54, '2025-12-13 17:00:24'),
(359, 3, 55, '2025-12-13 17:00:24'),
(360, 3, 56, '2025-12-13 17:00:24'),
(361, 3, 62, '2025-12-13 17:00:24'),
(362, 3, 63, '2025-12-13 17:00:24'),
(363, 3, 71, '2025-12-13 17:00:24'),
(364, 3, 72, '2025-12-13 17:00:24'),
(365, 3, 75, '2025-12-13 17:00:24'),
(366, 3, 76, '2025-12-13 17:00:24'),
(367, 3, 79, '2025-12-13 17:00:24'),
(368, 3, 83, '2025-12-13 17:00:24'),
(369, 3, 84, '2025-12-13 17:00:24'),
(370, 3, 85, '2025-12-13 17:00:24'),
(371, 3, 91, '2025-12-13 17:00:24'),
(372, 3, 92, '2025-12-13 17:00:24');

-- --------------------------------------------------------

--
-- بنية الجدول `sales_agents`
--

CREATE TABLE `sales_agents` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `commission_rate` decimal(5,2) DEFAULT 0.00 COMMENT 'نسبة العمولة',
  `commission_type` enum('percentage','fixed') DEFAULT 'percentage',
  `is_active` tinyint(1) DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `sales_agents`
--

INSERT INTO `sales_agents` (`id`, `company_id`, `code`, `name`, `phone`, `email`, `address`, `commission_rate`, `commission_type`, `is_active`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 'AG-0001', 'عمرو', '012588', '', '', 1.00, 'percentage', 1, '', 1, '2025-12-13 17:01:02', '2025-12-13 17:01:02');

-- --------------------------------------------------------

--
-- بنية الجدول `sales_invoices`
--

CREATE TABLE `sales_invoices` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `customer_id` int(10) UNSIGNED DEFAULT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `subtotal` decimal(15,2) DEFAULT 0.00,
  `discount_amount` decimal(15,2) DEFAULT 0.00,
  `tax_amount` decimal(15,2) DEFAULT 0.00,
  `total` decimal(15,2) DEFAULT 0.00,
  `paid_amount` decimal(15,2) DEFAULT 0.00,
  `payment_status` enum('unpaid','partial','paid') DEFAULT 'unpaid',
  `status` enum('draft','confirmed','cancelled') DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `warehouse_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'المخزن المصدر',
  `delivery_driver_name` varchar(100) DEFAULT NULL COMMENT 'اسم مندوب التوصيل',
  `vehicle_info` varchar(100) DEFAULT NULL COMMENT 'بيانات السيارة',
  `sales_agent_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'مندوب التعاقد'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `sales_invoice_items`
--

CREATE TABLE `sales_invoice_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `invoice_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `quantity` decimal(15,3) DEFAULT 1.000,
  `unit_id` int(10) UNSIGNED DEFAULT NULL,
  `unit_price` decimal(15,2) DEFAULT 0.00,
  `discount_amount` decimal(15,2) DEFAULT 0.00,
  `tax_rate` decimal(5,2) DEFAULT 0.00,
  `tax_amount` decimal(15,2) DEFAULT 0.00,
  `total` decimal(15,2) DEFAULT 0.00,
  `warehouse_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'المخزن'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `stock_transfers`
--

CREATE TABLE `stock_transfers` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED NOT NULL,
  `transfer_number` varchar(50) NOT NULL,
  `from_warehouse_id` int(10) UNSIGNED NOT NULL COMMENT 'المخزن المصدر',
  `to_warehouse_id` int(10) UNSIGNED NOT NULL COMMENT 'المخزن الوجهة',
  `from_branch_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'الفرع المصدر',
  `to_branch_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'الفرع الوجهة',
  `transfer_date` date NOT NULL,
  `status` enum('pending','in_transit','completed','cancelled') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `received_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `stock_transfer_items`
--

CREATE TABLE `stock_transfer_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `transfer_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `quantity` decimal(15,4) NOT NULL,
  `unit_cost` decimal(15,4) DEFAULT 0.0000,
  `received_quantity` decimal(15,4) DEFAULT 0.0000 COMMENT 'الكمية المستلمة',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `tax_number` varchar(100) DEFAULT NULL,
  `balance` decimal(15,2) DEFAULT 0.00,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `opening_balance` decimal(15,2) DEFAULT 0.00 COMMENT 'رصيد أول المدة'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `suppliers`
--

INSERT INTO `suppliers` (`id`, `company_id`, `code`, `name`, `email`, `phone`, `address`, `tax_number`, `balance`, `status`, `created_at`, `opening_balance`) VALUES
(1, 1, NULL, 'Test Product', 'ali@gmail.com', '01002206548', NULL, NULL, 0.00, 'active', '2025-12-12 15:23:27', 0.00),
(2, 1, NULL, 'علي مشتري', 'salessegybella@gmail.com', '054564613', NULL, NULL, 0.00, 'active', '2025-12-12 16:29:54', 0.00),
(3, 1, NULL, 'غبور', '', '01251215456', NULL, NULL, 0.00, 'active', '2025-12-13 12:27:37', 0.00),
(4, 1, NULL, 'مورد سكر', 'assd@gmail.com', '0126456464', NULL, NULL, 0.00, 'active', '2025-12-13 13:46:18', 0.00);

-- --------------------------------------------------------

--
-- بنية الجدول `units`
--

CREATE TABLE `units` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `symbol` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `units`
--

INSERT INTO `units` (`id`, `company_id`, `name`, `symbol`, `created_at`) VALUES
(1, 1, 'قطعة', 'حبة', '2025-12-12 00:26:59'),
(2, 1, 'كيلو', 'كغ', '2025-12-12 00:26:59');

-- --------------------------------------------------------

--
-- بنية الجدول `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `avatar` varchar(500) DEFAULT NULL,
  `role_id` int(10) UNSIGNED NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `theme` enum('light','dark') DEFAULT 'dark',
  `language` varchar(10) DEFAULT 'ar',
  `last_login` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `phone`, `avatar`, `role_id`, `is_active`, `theme`, `language`, `last_login`, `last_login_ip`, `created_at`) VALUES
(1, 'admin', 'admin@system.com', '$2y$10$duIqIoL26NPipxjkNkuJoeUOn9nAE/2047eYky4fXvfa/H6MHxO8m', 'علي محمد', '', NULL, 1, 1, 'dark', 'ar', '2025-12-13 17:53:32', '::1', '2025-12-12 00:26:59'),
(2, 'ali', 'ali@gmail.com', '$2y$10$oiTJpDR3uJ/hc01xTZvQLuq1HgJSI816T1ZtjzrCYzjI/iTjnhe2S', 'ali  mohamed', '01002545646', NULL, 2, 1, 'dark', 'ar', '2025-12-12 22:49:31', '::1', '2025-12-12 17:47:38'),
(4, 'admin2', 'alipaidvip2@gmail.com', '$2y$10$OWwQmczA0FKMKNMK6aVQPuK4ZrhfC1nzaLFVqo5cbL2uh5EQMHccW', 'admin2', '', NULL, 1, 1, 'dark', 'ar', NULL, NULL, '2025-12-12 21:51:20'),
(5, 'yousef', 'sales.egybella@gmail.com', '$2y$10$r3mmcbHH1m9dvr4643uu9eRzqMRkanRj9mC0Q98BTM4Gwq2jV.IbW', 'yousef mo', '', NULL, 3, 1, 'dark', 'ar', '2025-12-12 22:53:49', '::1', '2025-12-12 21:52:12'),
(6, 'amr', 'alivip2@hotmail.com', '$2y$10$RQnrE5xXogoibdJvZBAn4OyhU5.SaAlW1nmfOGcJt0sJsSxUfra3C', 'amr', '', NULL, 8, 1, 'dark', 'ar', '2025-12-12 23:18:56', '::1', '2025-12-12 22:17:05');

-- --------------------------------------------------------

--
-- بنية الجدول `user_companies`
--

CREATE TABLE `user_companies` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED NOT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `user_companies`
--

INSERT INTO `user_companies` (`id`, `user_id`, `company_id`, `is_default`, `created_at`) VALUES
(1, 1, 1, 1, '2025-12-12 00:26:59'),
(2, 2, 1, 1, '2025-12-12 17:47:38'),
(4, 4, 1, 1, '2025-12-12 21:51:20'),
(5, 5, 1, 1, '2025-12-12 21:52:12'),
(6, 6, 1, 1, '2025-12-12 22:17:05');

-- --------------------------------------------------------

--
-- بنية الجدول `warehouses`
--

CREATE TABLE `warehouses` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `branch_id` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `warehouses`
--

INSERT INTO `warehouses` (`id`, `company_id`, `name`, `address`, `is_default`, `status`, `created_at`, `branch_id`) VALUES
(1, 1, 'المخزن الرئيسي', NULL, 1, 'active', '2025-12-12 00:26:59', NULL),
(2, 1, 'مخزن تام القاهرة', 'القاهرة', 0, 'active', '2025-12-13 13:38:40', 1),
(3, 1, 'مخزن خامات القاهره', 'القاهرة', 0, 'active', '2025-12-13 13:39:36', 1),
(4, 1, 'مخزن تام الاسكندرية', 'الاسكندرية', 1, 'active', '2025-12-13 13:40:00', 2);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_id` (`company_id`,`code`);

--
-- Indexes for table `api_tokens`
--
ALTER TABLE `api_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_company` (`company_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `company_modules`
--
ALTER TABLE `company_modules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_module_unique` (`company_id`,`module_id`),
  ADD KEY `module_id` (`module_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_company` (`company_id`),
  ADD KEY `idx_product` (`product_id`),
  ADD KEY `idx_warehouse` (`warehouse_id`),
  ADD KEY `idx_type` (`movement_type`),
  ADD KEY `idx_reference` (`reference_type`,`reference_id`);

--
-- Indexes for table `journal_entries`
--
ALTER TABLE `journal_entries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `journal_entry_lines`
--
ALTER TABLE `journal_entry_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `entry_id` (`entry_id`);

--
-- Indexes for table `modules`
--
ALTER TABLE `modules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_company` (`company_id`),
  ADD KEY `idx_read` (`is_read`);

--
-- Indexes for table `opening_stock`
--
ALTER TABLE `opening_stock`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_product_warehouse_year` (`product_id`,`warehouse_id`,`fiscal_year`),
  ADD KEY `idx_company` (`company_id`),
  ADD KEY `idx_warehouse` (`warehouse_id`),
  ADD KEY `idx_product` (`product_id`);

--
-- Indexes for table `payment_allocations`
--
ALTER TABLE `payment_allocations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payment` (`payment_id`),
  ADD KEY `idx_invoice` (`invoice_type`,`invoice_id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `module_id` (`module_id`);

--
-- Indexes for table `positions`
--
ALTER TABLE `positions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `production_bom`
--
ALTER TABLE `production_bom`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_company` (`company_id`),
  ADD KEY `idx_product` (`product_id`);

--
-- Indexes for table `production_bom_items`
--
ALTER TABLE `production_bom_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bom` (`bom_id`);

--
-- Indexes for table `production_orders`
--
ALTER TABLE `production_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_company` (`company_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_order_number` (`order_number`);

--
-- Indexes for table `production_order_materials`
--
ALTER TABLE `production_order_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `product_stock`
--
ALTER TABLE `product_stock`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_warehouse` (`product_id`,`warehouse_id`),
  ADD KEY `warehouse_id` (`warehouse_id`);

--
-- Indexes for table `purchase_invoices`
--
ALTER TABLE `purchase_invoices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `purchase_invoice_items`
--
ALTER TABLE `purchase_invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_permission_unique` (`role_id`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Indexes for table `sales_agents`
--
ALTER TABLE `sales_agents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_company` (`company_id`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `sales_invoices`
--
ALTER TABLE `sales_invoices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `sales_invoice_items`
--
ALTER TABLE `sales_invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `stock_transfers`
--
ALTER TABLE `stock_transfers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_transfer_number` (`company_id`,`transfer_number`),
  ADD KEY `idx_company` (`company_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_from_warehouse` (`from_warehouse_id`),
  ADD KEY `idx_to_warehouse` (`to_warehouse_id`);

--
-- Indexes for table `stock_transfer_items`
--
ALTER TABLE `stock_transfer_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_transfer` (`transfer_id`),
  ADD KEY `idx_product` (`product_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `units`
--
ALTER TABLE `units`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `user_companies`
--
ALTER TABLE `user_companies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_company_unique` (`user_id`,`company_id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `warehouses`
--
ALTER TABLE `warehouses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `idx_branch` (`branch_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `api_tokens`
--
ALTER TABLE `api_tokens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `company_modules`
--
ALTER TABLE `company_modules`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `journal_entries`
--
ALTER TABLE `journal_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `journal_entry_lines`
--
ALTER TABLE `journal_entry_lines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `modules`
--
ALTER TABLE `modules`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `opening_stock`
--
ALTER TABLE `opening_stock`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_allocations`
--
ALTER TABLE `payment_allocations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- AUTO_INCREMENT for table `positions`
--
ALTER TABLE `positions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `production_bom`
--
ALTER TABLE `production_bom`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `production_bom_items`
--
ALTER TABLE `production_bom_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `production_orders`
--
ALTER TABLE `production_orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `production_order_materials`
--
ALTER TABLE `production_order_materials`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `product_stock`
--
ALTER TABLE `product_stock`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_invoices`
--
ALTER TABLE `purchase_invoices`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_invoice_items`
--
ALTER TABLE `purchase_invoice_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=373;

--
-- AUTO_INCREMENT for table `sales_agents`
--
ALTER TABLE `sales_agents`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sales_invoices`
--
ALTER TABLE `sales_invoices`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sales_invoice_items`
--
ALTER TABLE `sales_invoice_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_transfers`
--
ALTER TABLE `stock_transfers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_transfer_items`
--
ALTER TABLE `stock_transfer_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `units`
--
ALTER TABLE `units`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `user_companies`
--
ALTER TABLE `user_companies`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `warehouses`
--
ALTER TABLE `warehouses`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- قيود الجداول المُلقاة.
--

--
-- قيود الجداول `api_tokens`
--
ALTER TABLE `api_tokens`
  ADD CONSTRAINT `api_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `company_modules`
--
ALTER TABLE `company_modules`
  ADD CONSTRAINT `company_modules_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `company_modules_ibfk_2` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `departments_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `journal_entry_lines`
--
ALTER TABLE `journal_entry_lines`
  ADD CONSTRAINT `journal_entry_lines_ibfk_1` FOREIGN KEY (`entry_id`) REFERENCES `journal_entries` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `permissions`
--
ALTER TABLE `permissions`
  ADD CONSTRAINT `permissions_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `positions`
--
ALTER TABLE `positions`
  ADD CONSTRAINT `positions_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `production_bom`
--
ALTER TABLE `production_bom`
  ADD CONSTRAINT `production_bom_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `production_bom_items`
--
ALTER TABLE `production_bom_items`
  ADD CONSTRAINT `production_bom_items_ibfk_1` FOREIGN KEY (`bom_id`) REFERENCES `production_bom` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `production_orders`
--
ALTER TABLE `production_orders`
  ADD CONSTRAINT `production_orders_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `production_order_materials`
--
ALTER TABLE `production_order_materials`
  ADD CONSTRAINT `production_order_materials_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `production_orders` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `product_stock`
--
ALTER TABLE `product_stock`
  ADD CONSTRAINT `product_stock_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_stock_ibfk_2` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `purchase_invoices`
--
ALTER TABLE `purchase_invoices`
  ADD CONSTRAINT `purchase_invoices_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `purchase_invoice_items`
--
ALTER TABLE `purchase_invoice_items`
  ADD CONSTRAINT `purchase_invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `purchase_invoices` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `sales_invoices`
--
ALTER TABLE `sales_invoices`
  ADD CONSTRAINT `sales_invoices_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `sales_invoice_items`
--
ALTER TABLE `sales_invoice_items`
  ADD CONSTRAINT `sales_invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `sales_invoices` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `stock_transfer_items`
--
ALTER TABLE `stock_transfer_items`
  ADD CONSTRAINT `stock_transfer_items_ibfk_1` FOREIGN KEY (`transfer_id`) REFERENCES `stock_transfers` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `suppliers`
--
ALTER TABLE `suppliers`
  ADD CONSTRAINT `suppliers_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `units`
--
ALTER TABLE `units`
  ADD CONSTRAINT `units_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);

--
-- قيود الجداول `user_companies`
--
ALTER TABLE `user_companies`
  ADD CONSTRAINT `user_companies_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_companies_ibfk_2` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `warehouses`
--
ALTER TABLE `warehouses`
  ADD CONSTRAINT `warehouses_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
