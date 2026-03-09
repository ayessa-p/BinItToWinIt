-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 09, 2026 at 12:16 PM
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
-- Database: `binittowinit`
--

-- --------------------------------------------------------

--
-- Table structure for table `api_keys`
--

CREATE TABLE `api_keys` (
  `id` int(11) NOT NULL,
  `device_id` varchar(100) NOT NULL,
  `api_key` varchar(255) NOT NULL,
  `device_name` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_used` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `api_keys`
--

INSERT INTO `api_keys` (`id`, `device_id`, `api_key`, `device_name`, `location`, `is_active`, `created_at`, `last_used`) VALUES
(4, 'ESP32', 'b5140d65cd7b0504c2eefa9c144c3a24152f5ba7eb5b45d6133ba9014922e7ab', 'BinItToWinIt', 'MTICS Office', 1, '2026-02-22 04:04:32', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `automation_rules`
--

CREATE TABLE `automation_rules` (
  `id` int(11) NOT NULL,
  `rule_name` varchar(255) NOT NULL,
  `rule_type` enum('approval','notification','scheduling','pricing') NOT NULL,
  `conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`conditions`)),
  `actions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`actions`)),
  `is_active` tinyint(1) DEFAULT 1,
  `priority` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `automation_rules`
--

INSERT INTO `automation_rules` (`id`, `rule_name`, `rule_type`, `conditions`, `actions`, `is_active`, `priority`, `created_at`, `updated_at`) VALUES
(1, 'Auto-approve low-risk reservations', 'approval', '{\"max_duration_hours\": 2, \"user_level\": \"student\", \"resource_category\": \"equipment\"}', '{\"auto_approve\": true, \"notify_admin\": false}', 1, 1, '2026-02-22 04:51:11', '2026-02-22 04:51:11'),
(2, 'Auto-charge printing services', 'pricing', '{\"service_type\": \"printing\", \"user_level\": \"student\"}', '{\"charge_tokens\": true, \"use_standard_pricing\": true}', 1, 2, '2026-02-22 04:51:11', '2026-02-22 04:51:11'),
(3, 'Auto-notify high urgency requests', 'notification', '{\"urgency\": \"high\", \"service_type\": \"equipment_repair\"}', '{\"send_immediate_notification\": true, \"notify_admins\": true}', 1, 3, '2026-02-22 04:51:11', '2026-02-22 04:51:11');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `thread_id` int(11) NOT NULL,
  `sender_type` enum('user','admin','guest') NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `message_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `thread_id`, `sender_type`, `user_id`, `message_text`, `created_at`) VALUES
(1, 1, 'user', 4, 'hi', '2026-03-04 12:28:49'),
(2, 1, 'admin', 1, 'hello', '2026-03-04 12:29:13'),
(3, 1, 'user', 4, 'hi', '2026-03-04 16:41:19');

-- --------------------------------------------------------

--
-- Table structure for table `contact_threads`
--

CREATE TABLE `contact_threads` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `status` enum('open','answered','closed') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contact_threads`
--

INSERT INTO `contact_threads` (`id`, `user_id`, `name`, `email`, `subject`, `status`, `created_at`, `updated_at`) VALUES
(1, 4, 'Julia Datang', 'juliadatang@tup.edu.ph', 'Seminar', 'answered', '2026-03-04 12:28:49', '2026-03-04 16:41:19');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `event_date` datetime DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `thumbnail_url` varchar(500) DEFAULT NULL,
  `gallery_json` text DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `participant_count` int(11) DEFAULT 0,
  `attendance_enabled` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `title`, `description`, `event_date`, `location`, `image_url`, `thumbnail_url`, `gallery_json`, `is_published`, `created_at`, `updated_at`, `participant_count`, `attendance_enabled`) VALUES
(1, '2nd General Assembly', '𝐖𝐇𝐀𝐓 𝐀 𝐁𝐀𝐑𝐁𝐈𝐄-𝐓𝐀𝐒𝐓𝐈𝐂 𝐀𝐒𝐒𝐄𝐌𝐁𝐋𝐘!🎀💖⁣\r\n⁣\r\nOn 𝗪𝗲𝗱𝗻𝗲𝘀𝗱𝗮𝘆, 𝗝𝗮𝗻𝘂𝗮𝗿𝘆 𝟮𝟭, 𝟮𝟬𝟮𝟲, our 𝘽𝙖𝙧𝙗𝙞𝙚-𝙩𝙝𝙚𝙢𝙚𝙙 𝙎𝙚𝙘𝙤𝙣𝙙 𝙂𝙚𝙣𝙚𝙧𝙖𝙡 𝘼𝙨𝙨𝙚𝙢𝙗𝙡𝙮 took place and was a total success at the IT Dreamhouse, also known as the IT Function Hall. 🏠💻✨⁣\r\n⁣\r\nThe MTICS fam came together for a meaningful gathering that highlighted the 𝗔𝗰𝗰𝗼𝗺𝗽𝗹𝗶𝘀𝗵𝗺𝗲𝗻𝘁𝘀 𝗥𝗲𝗽𝗼𝗿𝘁 and a 𝘁𝗿𝗮𝗻𝘀𝗽𝗮𝗿𝗲𝗻𝘁 𝗙𝗶𝗻𝗮𝗻𝗰𝗶𝗮𝗹 𝗥𝗲𝗽𝗼𝗿𝘁—all while bringing that gorge-tastic Barbie energy. 💄👠⁣\r\n⁣\r\nThank you to everyone who showed up, stayed involved, and helped make the assembly both productive and fun! Just like Barbie, we proved that we can be everything or anything! 💅🏻🌟⁣\r\n⁣\r\n𝘔𝘛𝘐𝘊𝘚 𝘧𝘢𝘮, 𝘺𝘰𝘶𝘳 𝘴𝘶𝘱𝘱𝘰𝘳𝘵 𝘢𝘯𝘥 𝘦𝘯𝘨𝘢𝘨𝘦𝘮𝘦𝘯𝘵 𝘮𝘦𝘢𝘯𝘵 𝘢 𝘭𝘰𝘵 𝘧𝘰𝘳 𝘵𝘩𝘪𝘴 𝘢𝘴𝘴𝘦𝘮𝘣𝘭y 𝘴𝘶𝘤𝘤𝘦𝘴𝘴—𝘴𝘦𝘦 𝘺𝘰𝘶 𝘢𝘨𝘢𝘪𝘯 𝘢𝘵 𝘵𝘩𝘦 𝘯𝘦𝘹𝘵 𝘎𝘦𝘯𝘦𝘳𝘢𝘭 𝘈𝘴𝘴𝘦𝘮𝘣𝘭𝘺! 💙✨⁣', '2026-01-21 16:00:00', 'IT Function Hall', 'uploads/events/1770454434_mtics.jpg', 'uploads/events/1770459033_thumb_ga.jpg', '[\"uploads\\/events\\/gallery_17704590333152_0.jpg\",\"uploads\\/events\\/gallery_17704590333157_1.jpg\",\"uploads\\/events\\/gallery_17704590333162_2.jpg\",\"uploads\\/events\\/gallery_17704590333169_3.jpg\",\"uploads\\/events\\/gallery_17704590333173_4.jpg\",\"uploads\\/events\\/gallery_17704590333178_5.jpg\",\"uploads\\/events\\/gallery_17704597313448_0.jpg\",\"uploads\\/events\\/gallery_17704597313455_1.jpg\",\"uploads\\/events\\/gallery_17704597313460_2.jpg\",\"uploads\\/events\\/gallery_17704597313465_3.jpg\",\"uploads\\/events\\/gallery_17704597313472_4.jpg\",\"uploads\\/events\\/gallery_17704597313478_5.jpg\",\"uploads\\/events\\/gallery_17704597313485_6.jpg\",\"uploads\\/events\\/gallery_17704597313490_7.jpg\",\"uploads\\/events\\/gallery_17704597313498_8.jpg\",\"uploads\\/events\\/gallery_17704597313505_9.jpg\",\"uploads\\/events\\/gallery_17704597313511_10.jpg\",\"uploads\\/events\\/gallery_17704597313517_11.jpg\",\"uploads\\/events\\/gallery_17704597313523_12.jpg\",\"uploads\\/events\\/gallery_17704597313528_13.jpg\",\"uploads\\/events\\/gallery_17704597313536_14.jpg\"]', 1, '2026-02-07 08:05:14', '2026-02-22 03:47:05', 1, 1),
(4, 'Year-End Party', '🧇⚡ 𝙸𝚗𝚝𝚘 𝚝𝚑𝚎 𝚄𝚙𝚜𝚒𝚍𝚎 𝙳𝚘𝚠𝚗: 𝙼𝚃𝙸𝙲𝚂 𝚈𝚎𝚊𝚛-𝙴𝚗𝚍 𝙿𝚊𝚛𝚝𝚢 2025 ⚡🧇\r\n\r\n𝖫𝖺𝗌𝗍 𝖶𝖾𝖽𝗇𝖾𝗌𝖽𝖺𝗒, the 𝙈𝙏𝙄𝘾𝙎 𝙛𝙖𝙢𝙞𝙡𝙮 took a wild trip to 𝗛𝗮𝘄𝗸𝗶𝗻𝘀 for an 𝘶𝘯𝘧𝘰𝘳𝘨𝘦𝘵𝘵𝘢𝘣𝘭𝘦 𝙎𝙩𝙧𝙖𝙣𝙜𝙚𝙧 𝙏𝙝𝙞𝙣𝙜𝙨–themed 𝘠𝘦𝘢𝘳-𝘌𝘯𝘥 𝘗𝘢𝘳𝘵𝘺! From iconic fits to electrifying moments, the night was packed with 𝘭𝘢𝘶𝘨𝘩𝘴, 𝘦𝘯𝘦𝘳𝘨𝘺, and 𝘜𝘱𝘴𝘪𝘥𝘦-𝘋𝘰𝘸𝘯 𝘷𝘪𝘣𝘦𝘴. 🕶️🎶\r\n\r\nIt was a night of 𝘤𝘦𝘭𝘦𝘣𝘳𝘢𝘵𝘪𝘰𝘯, 𝘤𝘰𝘯𝘯𝘦𝘤𝘵𝘪𝘰𝘯, and 𝘮𝘦𝘮𝘰𝘳𝘪𝘦𝘴 that felt straight out of the 𝙐𝙥𝙨𝙞𝙙𝙚 𝘿𝙤𝙬𝙣 — the perfect way to close the year together. 🖤✨\r\nHere’s to friendships that stood strong through the thrill and adventures waiting in the next season 🚲🌌', '2025-12-17 16:00:00', 'IT Function Hall', 'uploads/events/1770454500_bottle.jpg', 'uploads/events/1770459945_thumb_594955907_1461580922640939_9068287744093888586_n.jpg', '[\"uploads\\/events\\/gallery_17704602123736_0.jpg\",\"uploads\\/events\\/gallery_17704602123743_1.jpg\",\"uploads\\/events\\/gallery_17704602123908_2.jpg\",\"uploads\\/events\\/gallery_17704602123917_3.jpg\",\"uploads\\/events\\/gallery_17704602123924_4.jpg\",\"uploads\\/events\\/gallery_17704602123930_5.jpg\",\"uploads\\/events\\/gallery_17704602123938_6.jpg\",\"uploads\\/events\\/gallery_17704602123945_7.jpg\",\"uploads\\/events\\/gallery_17704602123950_8.jpg\",\"uploads\\/events\\/gallery_17704602123956_9.jpg\",\"uploads\\/events\\/gallery_17704602123961_10.jpg\",\"uploads\\/events\\/gallery_17704602123967_11.jpg\",\"uploads\\/events\\/gallery_17704602123973_12.jpg\",\"uploads\\/events\\/gallery_17704602123978_13.jpg\",\"uploads\\/events\\/gallery_17704602123984_14.jpg\",\"uploads\\/events\\/gallery_17704602123995_16.jpg\",\"uploads\\/events\\/gallery_17704602124001_17.jpg\",\"uploads\\/events\\/gallery_17704602124009_18.jpg\"]', 1, '2026-02-07 08:16:38', '2026-02-23 16:33:02', 1, 1),
(5, 'MTICS x Gen AI Philippines', '𝗠𝗧𝗜𝗖𝗦 𝘅 𝗚𝗲𝗻 𝗔𝗜 𝗣𝗵𝗶𝗹𝗶𝗽𝗽𝗶𝗻𝗲𝘀: 𝗦𝗽𝗮𝗿𝗸𝗶𝗻𝗴 𝘁𝗵𝗲 𝗙𝘂𝘁𝘂𝗿𝗲 𝘄𝗶𝘁𝗵 𝗔𝗜! 🤖💡\r\n\r\nInnovation met inspiration as the 𝐌𝐚𝐧𝐢𝐥𝐚 𝐓𝐞𝐜𝐡𝐧𝐢𝐜𝐢𝐚𝐧 𝐈𝐧𝐬𝐭𝐢𝐭𝐮𝐭𝐞 𝐂𝐨𝐦𝐩𝐮𝐭𝐞𝐫 𝐒𝐨𝐜𝐢𝐞𝐭𝐲 (𝐌𝐓𝐈𝐂𝐒) teamed up with Gen AI Philippines for an exciting AI Innovation Seminar! The event opened new perspectives on how Artificial Intelligence can empower students to 𝙘𝙧𝙚𝙖𝙩𝙚, 𝙚𝙭𝙥𝙡𝙤𝙧𝙚, 𝙖𝙣𝙙 𝙡𝙚𝙖𝙙 in the fast-evolving tech world. \r\n\r\n“𝙁𝙧𝙤𝙢 𝙄𝙢𝙖𝙜𝙞𝙣𝙖𝙩𝙞𝙤𝙣 𝙩𝙤 𝙄𝙣𝙣𝙤𝙫𝙖𝙩𝙞𝙤𝙣” — this theme perfectly captured the spirit of the seminar, reminding everyone that great ideas begin with creativity and grow through technology. \r\n\r\nA huge thanks to 𝐌𝐫. 𝐀𝐫𝐣𝐚𝐲 𝐑𝐨𝐬𝐞𝐥, a founding board member of Gen AI Philippines, for sharing his valuable insights and inspiring the students to embrace the limitless possibilities of AI. \r\n\r\n𝐈𝐭 𝐰𝐚𝐬 𝐭𝐫𝐮𝐥𝐲 𝐚 𝐝𝐚𝐲 𝐟𝐢𝐥𝐥𝐞𝐝 𝐰𝐢𝐭𝐡 𝐤𝐧𝐨𝐰𝐥𝐞𝐝𝐠𝐞, 𝐢𝐧𝐬𝐩𝐢𝐫𝐚𝐭𝐢𝐨𝐧, 𝐚𝐧𝐝 𝐢𝐧𝐧𝐨𝐯𝐚𝐭𝐢𝐨𝐧!', '2025-10-10 16:00:00', 'IT Function Hall', '', 'uploads/events/1770460687_thumb_seminar.jpg', '[\"uploads\\/events\\/gallery_17704606879238_0.jpg\",\"uploads\\/events\\/gallery_17704606879245_1.jpg\",\"uploads\\/events\\/gallery_17704606879256_2.jpg\",\"uploads\\/events\\/gallery_17704606879263_3.jpg\",\"uploads\\/events\\/gallery_17704606879268_4.jpg\",\"uploads\\/events\\/gallery_17704606879274_5.jpg\",\"uploads\\/events\\/gallery_17704606879281_6.jpg\",\"uploads\\/events\\/gallery_17704606879287_7.jpg\",\"uploads\\/events\\/gallery_17704606879292_8.jpg\",\"uploads\\/events\\/gallery_17704606879299_9.jpg\",\"uploads\\/events\\/gallery_17704606879304_10.jpg\",\"uploads\\/events\\/gallery_17704606879314_11.jpg\",\"uploads\\/events\\/gallery_17704606879321_12.jpg\",\"uploads\\/events\\/gallery_17704606879328_13.jpg\",\"uploads\\/events\\/gallery_17704606879335_14.jpg\",\"uploads\\/events\\/gallery_17704606879341_15.jpg\"]', 1, '2026-02-07 10:38:07', '2026-02-24 10:14:24', 1, 1),
(6, '1st General Assembly', '𝗕𝗿𝗶𝗱𝗴𝗶𝗻𝗴 𝗧𝗿𝗮𝗻𝘀𝗶𝘁𝗶𝗼𝗻𝘀, 𝗦𝘁𝗿𝗲𝗻𝗴𝘁𝗵𝗲𝗻𝗶𝗻𝗴 𝗖𝗼𝗻𝗻𝗲𝗰𝘁𝗶𝗼𝗻𝘀: 𝗠𝗧𝗜𝗖𝗦 𝗙𝗶𝗿𝘀𝘁 𝗚𝗲𝗻𝗲𝗿𝗮𝗹 𝗔𝘀𝘀𝗲𝗺𝗯𝗹𝘆 𝗔.𝗬. 𝟮𝟬𝟮𝟱-𝟮𝟬𝟮𝟲 💙✨\r\n\r\nThe first general assembly of the 𝐌𝐚𝐧𝐢𝐥𝐚 𝐓𝐞𝐜𝐡𝐧𝐢𝐜𝐢𝐚𝐧 𝐈𝐧𝐬𝐭𝐢𝐭𝐮𝐭𝐞 𝐂𝐨𝐦𝐩𝐮𝐭𝐞𝐫 𝐒𝐨𝐜𝐢𝐞𝐭𝐲 was filled with joy and delightful moments, highlighted by the active participation of our MTICS members throughout the program.\r\n\r\nOur utmost appreciation goes to our MTICS Adviser, 𝗣𝗿𝗼𝗳. 𝗣𝗼𝗽𝘀 𝗩. 𝗠𝗮𝗱𝗿𝗶𝗮𝗴𝗮, for her heartfelt welcome remarks, and to 𝗣𝗿𝗼𝗳. 𝗝𝗼𝗮𝗻 𝗖. 𝗠𝗮𝗴-𝗶𝘀𝗮 for delivering hers on behalf of our Section Head, 𝗣𝗿𝗼𝗳. 𝗝𝘂𝗹𝗶𝘂𝘀 𝗗𝗲𝗹𝗳𝗶𝗻 𝗔. 𝗦𝗶𝗹𝗮𝗻𝗴 that inspired every BSIT student to feel motivated and a true sense of belonging in this new academic term.\r\n\r\nTo all the 𝗠𝗧𝗜𝗖𝗦 𝗺𝗲𝗺𝗯𝗲𝗿𝘀 who joined us, we extend our grateful hearts for your energy and enthusiasm, which made this event a true success.\r\n𝙃𝘦𝘳𝘦’𝘴 𝘵𝘰 𝘣𝘳𝘪𝘥𝘨𝘪𝘯𝘨 𝘯𝘦𝘸 𝘣𝘦𝘨𝘪𝘯𝘯𝘪𝘯𝘨𝘴 𝘢𝘯𝘥 𝘴𝘵𝘳𝘦𝘯𝘨𝘵𝘩𝘦𝘯𝘪𝘯𝘨 𝘦𝘷𝘦𝘳𝘺 𝘤𝘰𝘯𝘯𝘦𝘤𝘵𝘪𝘰𝘯 𝘪𝘯 𝘵ℎ𝑒 𝑀𝑎𝑛𝑖𝑙𝑎 𝑇𝑒𝑐ℎ𝑛𝑖𝑐𝑖𝑎𝑛 𝐼𝑛𝑠𝑡𝑖𝑡𝑢𝑡𝑒 𝐶𝑜𝑚𝑝𝑢𝑡𝑒𝑟 𝑆𝑜𝑐𝑖𝑒𝑡𝑦! 🌉🤝\r\n\r\n𝙏𝙃𝘼𝙉𝙆 𝙔𝙊𝙐 𝙁𝙊𝙍 𝙏𝙃𝙄𝙎 𝙐𝙉𝙁𝙊𝙍𝙂𝙀𝙏𝙏𝘼𝘽𝙇𝙀 𝘿𝘼𝙔, 𝙈𝙏𝙄𝘾𝙎 𝙁𝘼𝙈! 🫂🫶', '2025-08-27 16:00:00', 'IT Function Hall', '', 'uploads/events/1770461217_thumb_539190136_1367131445419221_8374732977728404548_n.jpg', '[\"uploads\\/events\\/gallery_17704612171505_0.jpg\",\"uploads\\/events\\/gallery_17704612171512_1.jpg\",\"uploads\\/events\\/gallery_17704612171518_2.jpg\",\"uploads\\/events\\/gallery_17704612171526_3.jpg\",\"uploads\\/events\\/gallery_17704612171532_4.jpg\",\"uploads\\/events\\/gallery_17704612171540_5.jpg\",\"uploads\\/events\\/gallery_17704612171553_6.jpg\",\"uploads\\/events\\/gallery_17704612171561_7.jpg\",\"uploads\\/events\\/gallery_17704612171568_8.jpg\",\"uploads\\/events\\/gallery_17704612171574_9.jpg\",\"uploads\\/events\\/gallery_17704612171580_10.jpg\",\"uploads\\/events\\/gallery_17704612171585_11.jpg\",\"uploads\\/events\\/gallery_17704612171591_12.jpg\",\"uploads\\/events\\/gallery_17704612171597_13.jpg\",\"uploads\\/events\\/gallery_17704612171602_14.jpg\",\"uploads\\/events\\/gallery_17704612171608_15.jpg\",\"uploads\\/events\\/gallery_17704612171614_16.jpg\",\"uploads\\/events\\/gallery_17704612171620_17.jpg\",\"uploads\\/events\\/gallery_17704612171625_18.jpg\"]', 1, '2026-02-07 10:46:57', '2026-02-07 10:46:57', 0, 1),
(7, 'ACSO Week', '𝐀𝐂𝐒𝐎 𝐖𝐄𝐄𝐊 𝟐𝟎𝟐𝟓 𝐄𝐏𝟏 - 𝐃𝐀𝐘 𝐎𝐍𝐄: 𝐒𝐰𝐞𝐞𝐭 𝐇𝐚𝐩𝐩𝐞𝐧𝐢𝐧𝐠𝐬, 𝐒𝐰𝐞𝐞𝐭 𝐁𝐞𝐠𝐢𝐧𝐧𝐢𝐧𝐠𝐬 🍀🍦\r\n\r\nYesterday, 𝘕𝘰𝘷𝘦𝘮𝘣𝘦𝘳 24, 2025, we officially started and welcomed the 𝗔𝗖𝗦𝗢 𝗪𝗲𝗲𝗸 𝟮𝟬𝟮𝟱. Throughout the day, we filled it with new memories and fun moments within the campus in TUP-Taguig. Starting the day with pride in the ACSO parade marks a fresh and new beginning to serve students with our partners for this event.\r\n\r\nWith the 𝙋𝙚𝙧𝙛𝙚𝙘𝙩 𝙎𝙪𝙣𝙙𝙖𝙚’𝙨 perfect sweet and savory treats, and 𝙏𝙝𝙚 𝙁𝙤𝙪𝙧-𝙇𝙚𝙖𝙛 𝘾𝙡𝙤𝙫𝙚𝙧 𝘾𝙤𝙛𝙛𝙚𝙚’𝙨 refreshing and aromatic drinks, day one could never be better for the MTICS family. 🥳🙌\r\n\r\n𝘛𝘩𝘢𝘯𝘬 𝘺𝘰𝘶 𝘧𝘰𝘳 𝘺𝘰𝘶𝘳 𝘱𝘢𝘳𝘵𝘪𝘤𝘪𝘱𝘢𝘵𝘪𝘰𝘯, 𝘦𝘷𝘦𝘳𝘺𝘰𝘯𝘦! 𝘞𝘦 𝘢𝘳𝘦 𝘭𝘰𝘰𝘬𝘪𝘯𝘨 𝘧𝘰𝘳𝘸𝘢𝘳𝘥 𝘵𝘰 𝘮𝘰𝘳𝘦 𝘰𝘧 𝘺𝘰𝘶𝘳 𝘦𝘢𝘨𝘦𝘳𝘯𝘦𝘴𝘴 𝘧𝘰𝘳 𝘵𝘩𝘦 𝘯𝘦𝘹𝘵 𝘵𝘸𝘰 𝘥𝘢𝘺𝘴. 𝘚𝘦𝘦 𝘺𝘰𝘶, 𝘛𝘜𝘗𝘛𝘪𝘢𝘯𝘴! 💙👀\r\n\r\n\r\n𝐀𝐂𝐒𝐎 𝐖𝐄𝐄𝐊 𝟐𝟎𝟐𝟓 𝐄𝐏𝟐 - 𝐃𝐀𝐘 𝐓𝐖𝐎: 𝐀 𝐃𝐚𝐲 𝐨𝐟 𝐄𝐧𝐣𝐨𝐲𝐦𝐞𝐧𝐭 𝐚𝐧𝐝 𝐄𝐯𝐞𝐧𝐭𝐬 🎉💙\r\n\r\nToday, 𝘕𝘰𝘷𝘦𝘮𝘣𝘦𝘳 25, 2025, marks the second day of ACSO Week 2025. We continue serving the TUPTians drinks brewed and mixed with luck by 𝙏𝙝𝙚 𝙁𝙤𝙪𝙧-𝙇𝙚𝙖𝙛 𝘾𝙡𝙤𝙫𝙚𝙧 𝘾𝙤𝙛𝙛𝙚𝙚 ☕🍀, and desserts perfectly intricate from 𝙋𝙚𝙧𝙛𝙚𝙘𝙩 𝙎𝙪𝙣𝙙𝙖𝙚 🍨✨.\r\n\r\nWith events flowing throughout the day, we are looking forward to serving more and accommodating everyone to satisfy their thirst and hunger for more 😋. Don’t miss out—keep looking forward to the foods and drinks we offer 🍽️💙.\r\n\r\n𝘌𝘯𝘫𝘰𝘺 𝘋𝘢𝘺 𝘛𝘸𝘰, 𝘔𝘛𝘐𝘊𝘚 𝘧𝘢𝘮! 💙 \r\n\r\n\r\n𝐀𝐂𝐒𝐎 𝐖𝐄𝐄𝐊 𝟐𝟎𝟐𝟓 𝐄𝐏𝟑 – 𝐃𝐀𝐘 𝐓𝐇𝐑𝐄𝐄: 𝐓𝐡𝐞 𝐋𝐚𝐬𝐭 𝐃𝐚𝐲 𝐖𝐚𝐬 𝐚 𝐁𝐥𝐚𝐬𝐭 🎉💙\r\n\r\nYesterday, 𝘕𝘰𝘷𝘦𝘮𝘣𝘦𝘳 26, 2025, we wrapped up the 𝘧𝘪𝘯𝘢𝘭 𝘥𝘢𝘺 of 𝘼𝘾𝙎𝙊 𝙒𝙚𝙚𝙠 2025 — and what an unforgettable ending it was! MTICS ended the celebration strong with the crowd enjoying their last chance to savor the lucky brews from 𝙏𝙝𝙚 𝙁𝙤𝙪𝙧-𝙇𝙚𝙖𝙛 𝘾𝙡𝙤𝙫𝙚𝙧 𝘾𝙤𝙛𝙛𝙚𝙚 ☕🍀 and the delightful desserts of 𝙋𝙚𝙧𝙛𝙚𝙘𝙩 𝙎𝙪𝙣𝙙𝙖𝙚 🍨✨.\r\n\r\nThank you, 𝙏𝙐𝙋𝙏𝙩𝙞𝙖𝙣𝙨, for the 𝘦𝘯𝘦𝘳𝘨𝘺, 𝘴𝘶𝘱𝘱𝘰𝘳𝘵, and 𝘯𝘰𝘯𝘴𝘵𝘰𝘱 𝘤𝘳𝘢𝘷𝘪𝘯𝘨𝘴 throughout all three days. Your smiles and excitement fueled the success of this year’s ACSO Week 💙🔥\r\n\r\nUntil next time, 𝙈𝙏𝙄𝘾𝙎 𝘧𝘢𝘮 💙✨', '2025-11-24 08:00:00', 'TUP-Taguig Campus', '', 'uploads/events/1770462630_thumb_acso.jpg', '[\"uploads\\/events\\/gallery_17704626842857_0.jpg\",\"uploads\\/events\\/gallery_17704626842864_1.jpg\",\"uploads\\/events\\/gallery_17704626842882_2.jpg\",\"uploads\\/events\\/gallery_17704626842898_3.jpg\",\"uploads\\/events\\/gallery_17704626842912_4.jpg\",\"uploads\\/events\\/gallery_17704626842929_5.jpg\",\"uploads\\/events\\/gallery_17704626842946_6.jpg\",\"uploads\\/events\\/gallery_17704626842956_7.jpg\",\"uploads\\/events\\/gallery_17704626842970_8.jpg\",\"uploads\\/events\\/gallery_17704626842985_9.jpg\",\"uploads\\/events\\/gallery_17704626843000_10.jpg\",\"uploads\\/events\\/gallery_17704626843007_11.jpg\",\"uploads\\/events\\/gallery_17704626843012_12.jpg\",\"uploads\\/events\\/gallery_17704626843021_13.jpg\",\"uploads\\/events\\/gallery_17704626843032_14.jpg\",\"uploads\\/events\\/gallery_17704626843044_15.jpg\",\"uploads\\/events\\/gallery_17704626843050_16.jpg\",\"uploads\\/events\\/gallery_17704626843060_17.jpg\",\"uploads\\/events\\/gallery_17704626843076_18.jpg\",\"uploads\\/events\\/gallery_17704626843081_19.jpg\",\"uploads\\/events\\/gallery_17704628073190_0.jpg\",\"uploads\\/events\\/gallery_17704628073196_1.jpg\",\"uploads\\/events\\/gallery_17704628073204_2.jpg\",\"uploads\\/events\\/gallery_17704628073209_3.jpg\",\"uploads\\/events\\/gallery_17704628073215_4.jpg\"]', 1, '2026-02-07 11:10:30', '2026-02-07 11:13:27', 0, 1),
(8, 'CodeChum', '𝗖𝗼𝗱𝗲𝗖𝗵𝘂𝗺: 𝗡𝗮𝘁𝗶𝗼𝗻𝗮𝗹 𝗣𝗿𝗼𝗴𝗿𝗮𝗺𝗺𝗶𝗻𝗴 𝗖𝗵𝗮𝗹𝗹𝗲𝗻𝗴𝗲 𝗦𝗲𝗮𝘀𝗼𝗻 2\r\n\r\nLast October 22, 2025, selected participants from BSIT proudly represented our university in the 𝗖𝗼𝗱𝗲𝗖𝗵𝘂𝗺: 𝗡𝗮𝘁𝗶𝗼𝗻𝗮𝗹 𝗣𝗿𝗼𝗴𝗿𝗮𝗺𝗺𝗶𝗻𝗴 𝗖𝗵𝗮𝗹𝗹𝗲𝗻𝗴𝗲 𝗦𝗲𝗮𝘀𝗼𝗻 2.\r\n\r\nAs they entered the competition, they carried with them not only their knowledge and skills, but also 𝒃𝒓𝒂𝒗𝒆𝒓𝒚, 𝒑𝒂𝒔𝒔𝒊𝒐𝒏 𝒇𝒐𝒓 𝑰𝑻, and the 𝒅𝒆𝒕𝒆𝒓𝒎𝒊𝒏𝒂𝒕𝒊𝒐𝒏 to prove that being a TUPTian means having excellence and a strong foundation in technology.\r\n\r\nLet us all extend our best wishes and full support as they advance beyond the elimination round and prepare to compete in the 𝐒𝐄𝐌𝐈𝐅𝐈𝐍𝐀𝐋𝐒. 𝙂𝙤𝙤𝙙 𝙡𝙪𝙘𝙠, 𝙏𝙐𝙋𝙏𝙞𝙖𝙣𝙨 — 𝙢𝙖𝙠𝙚 𝙪𝙨 𝙥𝙧𝙤𝙪𝙙!', '2025-10-22 16:30:00', 'IT Room 302', '', 'uploads/events/1770463254_thumb_619299166_1503379771794387_48385158049834157_n.jpg', '[\"uploads\\/events\\/gallery_17704632547085_0.jpg\",\"uploads\\/events\\/gallery_17704632547093_1.jpg\",\"uploads\\/events\\/gallery_17704632547098_2.jpg\",\"uploads\\/events\\/gallery_17704632547102_3.jpg\",\"uploads\\/events\\/gallery_17704632547110_4.jpg\"]', 1, '2026-02-07 11:20:54', '2026-02-07 11:22:12', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `event_attendance`
--

CREATE TABLE `event_attendance` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `proof_image` varchar(500) DEFAULT NULL,
  `attendance_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `tokens_awarded` decimal(10,2) DEFAULT 0.00,
  `admin_notes` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `event_attendance`
--

INSERT INTO `event_attendance` (`id`, `event_id`, `user_id`, `proof_image`, `attendance_status`, `tokens_awarded`, `admin_notes`, `submitted_at`, `reviewed_at`, `reviewed_by`) VALUES
(1, 1, 2, 'uploads/attendance/event_1_user_2_1771732001.jpg', 'approved', 5.00, 'Approved by admin', '2026-02-22 03:46:41', '2026-02-22 03:47:05', 1),
(2, 4, 2, 'uploads/attendance/event_4_user_2_1771864325.jpg', 'approved', 10.00, 'Approved by admin', '2026-02-23 16:32:05', '2026-02-23 16:33:02', 1),
(3, 5, 2, 'uploads/attendance/event_5_user_2_1771928022.jpg', 'approved', 10.00, 'Approved by admin', '2026-02-24 10:13:42', '2026-02-24 10:14:24', 1),
(4, 4, 4, 'uploads/attendance/event_4_user_4_1772590861.jpg', 'rejected', 0.00, 'Rejected by admin', '2026-03-04 02:21:01', '2026-03-04 02:33:38', 1);

-- --------------------------------------------------------

--
-- Stand-in structure for view `event_statistics`
-- (See below for the actual view)
--
CREATE TABLE `event_statistics` (
`id` int(11)
,`title` varchar(255)
,`event_date` datetime
,`total_attendees` bigint(21)
,`approved_attendees` bigint(21)
,`pending_attendees` bigint(21)
,`total_tokens_awarded` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Table structure for table `internet_plans`
--

CREATE TABLE `internet_plans` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `duration_minutes` int(11) NOT NULL,
  `token_cost` decimal(10,2) NOT NULL,
  `speed_mbps` int(11) DEFAULT NULL,
  `data_limit_mb` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `internet_plans`
--

INSERT INTO `internet_plans` (`id`, `name`, `description`, `duration_minutes`, `token_cost`, `speed_mbps`, `data_limit_mb`, `is_active`, `created_at`) VALUES
(1, 'Basic Internet Access', '1 hour basic internet access for research and browsing', 60, 30.00, 10, 1000, 1, '2026-02-22 04:51:11'),
(2, 'Premium Internet Access', 'High-speed internet access for downloads and streaming', 120, 50.00, 50, 5000, 1, '2026-02-22 04:51:11'),
(3, 'Extended Internet Access', 'Full day internet access for projects and assignments', 480, 150.00, 100, 10000, 1, '2026-02-22 04:51:11');

-- --------------------------------------------------------

--
-- Table structure for table `news`
--

CREATE TABLE `news` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `news`
--

INSERT INTO `news` (`id`, `title`, `content`, `author`, `image_url`, `is_published`, `created_at`, `updated_at`) VALUES
(1, 'Welcome to Bin It to Win It!', 'We are excited to launch our new recycling initiative that rewards students for their environmental efforts. Start recycling today and earn Eco-Tokens!', 'MTICS Admin', NULL, 1, '2026-02-07 05:15:51', '2026-02-07 05:15:51'),
(2, 'THE TRUST PROTOCOL: Understanding the Blockchain Basics', 'Join Team1 Avalanche  University Connect @Technological University of the Philippines - Taguig 🔺\r\n\r\nLearn the Basics of Blockchain, explore exciting opportunities on Avalanche!\r\n\r\nCo-Presented by: CrypTita Plays \r\nCommunity Partners: The SafeHouse | Bitget Blockchain4Youth | The Round Table | Stocksify | GN Club | NEN Digital | Manila Technician Institute Computer Society - MTICS \r\n\r\nStep in, learn, and start building on Avalanche! 🔺🇵🇭', 'MTICS Officers', NULL, 1, '2026-02-07 05:15:51', '2026-03-04 16:14:28');

-- --------------------------------------------------------

--
-- Table structure for table `org_officers`
--

CREATE TABLE `org_officers` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `position` varchar(255) NOT NULL,
  `profile_image` varchar(500) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `org_officers`
--

INSERT INTO `org_officers` (`id`, `full_name`, `position`, `profile_image`, `display_order`, `is_active`, `created_at`) VALUES
(1, 'Jerome Steven Rosario', 'Chief Executive President', NULL, 1, 1, '2026-03-04 12:35:34'),
(2, 'Hanna Clerdee Cruz', 'Chief Executive Vice President', NULL, 2, 1, '2026-03-04 12:35:34'),
(3, 'Ianzae Ryan Ego', 'Chief Executive VP for Internal Affairs', NULL, 3, 1, '2026-03-04 12:35:34'),
(4, 'Sachzie Sofia Ilagan', 'Chief Executive VP for External Affairs', NULL, 4, 1, '2026-03-04 12:35:34'),
(5, 'Cristel Kate Famini', 'Executive Officer for Documentation', NULL, 5, 1, '2026-03-04 12:35:34'),
(6, 'Kimberly Eledia', 'Executive Officer for Finance', NULL, 6, 1, '2026-03-04 12:35:35'),
(7, 'Mary Pauline Calungsod', 'Executive Officer for Audit', NULL, 7, 1, '2026-03-04 12:35:35'),
(8, 'Lord Cedric Vila', 'Executive Officer for Information', NULL, 8, 1, '2026-03-04 12:35:35'),
(9, 'Kim Jensen Yebes', 'Executive Officer for Activities and Programs', NULL, 9, 1, '2026-03-04 12:35:35'),
(10, 'Krsmur Chelvin Lacorte', 'Executive Officer for Logistics', NULL, 10, 1, '2026-03-04 12:35:35'),
(11, 'Ayessa Denisse Pili', 'AVP for Internal Affairs', NULL, 11, 1, '2026-03-04 12:35:35'),
(12, 'Dion Ongaria', 'AVP for External Affairs', NULL, 12, 1, '2026-03-04 12:35:35'),
(13, 'Lance Grant Haboc', 'Asst. EO for Documentation', NULL, 13, 1, '2026-03-04 12:35:35'),
(14, 'Elijah Neil Gallardo', 'Asst. EO for Finance', NULL, 14, 1, '2026-03-04 12:35:35'),
(15, 'Julia Faye Datang', 'Asst. EO for Audit', NULL, 15, 1, '2026-03-04 12:35:35'),
(16, 'Trisha Mia Morales', 'Asst. EO for Information', NULL, 16, 1, '2026-03-04 12:35:35'),
(17, 'John Regan Asino', 'Asst. EO for Activities & Programs', NULL, 17, 1, '2026-03-04 12:35:35'),
(18, 'Marcus Iñigo Aristain', 'Asst. EO for Logistics', NULL, 18, 1, '2026-03-04 12:35:35');

-- --------------------------------------------------------

--
-- Table structure for table `printing_services`
--

CREATE TABLE `printing_services` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price_per_page` decimal(10,2) NOT NULL,
  `color_options` enum('bw','color') DEFAULT 'bw',
  `paper_size` enum('a4','a3','legal','letter') DEFAULT 'a4',
  `max_pages_per_day` int(11) DEFAULT 10,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `printing_services`
--

INSERT INTO `printing_services` (`id`, `name`, `description`, `price_per_page`, `color_options`, `paper_size`, `max_pages_per_day`, `is_active`, `created_at`) VALUES
(1, 'Black and White Printing', 'Black and White Printing', 1.00, 'bw', 'a4', 100, 1, '2026-02-22 04:51:11'),
(2, 'Color Printing', 'Color Printing', 5.00, 'color', 'a4', 100, 1, '2026-02-22 04:51:11');

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'active',
  `image_url` varchar(500) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `name`, `description`, `status`, `image_url`, `is_featured`, `created_at`, `updated_at`) VALUES
(2, 'Community Service', 'The MTICS organization proudly partnered with St. Bernadette Day Care Center to host an engaging and impactful community service outreach for the young learners.', 'active', NULL, 1, '2026-03-04 04:58:50', '2026-03-08 05:09:30');

-- --------------------------------------------------------

--
-- Table structure for table `recycling_activities`
--

CREATE TABLE `recycling_activities` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `sensor_id` varchar(50) DEFAULT NULL,
  `bottle_type` varchar(50) DEFAULT NULL,
  `tokens_earned` decimal(10,2) NOT NULL,
  `device_timestamp` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `device_id` varchar(50) DEFAULT NULL,
  `device_paired_by_user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `recycling_activities`
--

INSERT INTO `recycling_activities` (`id`, `user_id`, `sensor_id`, `bottle_type`, `tokens_earned`, `device_timestamp`, `created_at`, `device_id`, `device_paired_by_user_id`) VALUES
(1, 1, NULL, NULL, 1.00, NULL, '2026-02-22 07:45:45', 'TEST_001', NULL),
(2, 1, NULL, NULL, 1.00, NULL, '2026-02-22 07:46:21', 'TEST_001', NULL),
(3, 1, NULL, NULL, 1.00, NULL, '2026-02-22 07:46:38', 'ESP32_001', NULL),
(4, 1, NULL, NULL, 1.00, NULL, '2026-02-22 07:46:55', 'ESP32_001', NULL),
(5, 1, NULL, NULL, 1.00, NULL, '2026-02-22 07:47:16', 'ESP32_001', NULL),
(6, 1, NULL, NULL, 2.00, NULL, '2026-02-22 07:47:39', 'ESP32_001', NULL),
(7, 1, NULL, NULL, 1.00, NULL, '2026-02-22 07:47:46', 'ESP32_001', NULL),
(8, 1, NULL, NULL, 1.00, NULL, '2026-02-22 07:55:05', 'ESP32_001', NULL),
(9, 1, NULL, NULL, 1.00, NULL, '2026-02-22 07:57:18', 'ESP32_001', NULL),
(10, 1, NULL, NULL, 1.00, NULL, '2026-02-22 07:57:51', 'ESP32_001', NULL),
(11, 2, NULL, NULL, 1.00, NULL, '2026-02-22 08:10:46', 'ESP32_001', 2),
(12, 2, NULL, NULL, 1.00, NULL, '2026-02-22 08:13:49', 'ESP32_001', 2),
(13, 2, NULL, NULL, 1.00, NULL, '2026-02-22 08:21:11', 'ESP32_001', 2),
(14, 2, NULL, NULL, 1.00, NULL, '2026-02-22 08:57:41', 'ESP32_001', 2),
(15, 2, NULL, NULL, 1.00, NULL, '2026-02-22 09:04:08', 'ESP32_001', 2),
(16, 2, NULL, NULL, 1.00, NULL, '2026-02-22 09:04:41', 'ESP32_001', 2),
(17, 2, NULL, NULL, 1.00, NULL, '2026-02-22 09:11:23', 'ESP32_001', 2),
(18, 2, 'ESP32_001', 'Rejected: 13.9g (metal detected)', 0.00, NULL, '2026-02-22 09:14:15', NULL, NULL),
(19, 2, NULL, NULL, 4.00, NULL, '2026-02-22 09:17:32', 'ESP32_001', 2),
(20, 2, 'ESP32_001', 'Rejected: 12g (metal detected)', 0.00, NULL, '2026-02-22 09:18:51', NULL, NULL),
(21, 2, 'ESP32_001', 'Rejected: 13.3g (metal detected)', 0.00, NULL, '2026-02-22 09:19:46', NULL, NULL),
(22, 2, 'ESP32_001', 'Accepted: 16.8g PET', 2.00, NULL, '2026-02-22 09:31:07', NULL, NULL),
(23, 2, 'ESP32_001', 'Rejected: 13.1g (metal detected)', 0.00, NULL, '2026-02-22 09:31:41', NULL, NULL),
(24, 2, 'ESP32_001', 'Rejected: 11.4g (metal detected)', 0.00, NULL, '2026-02-22 09:34:32', NULL, NULL),
(25, 2, 'ESP32_001', 'Accepted: 14.2g PET', 2.00, NULL, '2026-02-22 09:34:58', NULL, NULL),
(26, 2, 'ESP32_001', 'Accepted: 17.9g PET', 3.00, NULL, '2026-02-22 09:40:24', NULL, NULL),
(27, 2, 'ESP32_001', 'Rejected: 11.8g (metal detected)', 0.00, NULL, '2026-02-22 09:40:48', NULL, NULL),
(28, 2, 'ESP32_001', 'Accepted: 20.8g PET', 3.00, NULL, '2026-02-22 09:41:56', NULL, NULL),
(29, 2, 'ESP32_001', 'Accepted: 12.5g PET', 1.00, NULL, '2026-02-22 09:42:52', NULL, NULL),
(30, 2, 'ESP32_001', 'Rejected: 13g (metal detected)', 0.00, NULL, '2026-02-22 09:44:03', NULL, NULL),
(31, 2, 'ESP32_001', 'Accepted: 20.2g PET', 3.00, NULL, '2026-02-22 09:45:05', NULL, NULL),
(32, 2, 'ESP32_001', 'Accepted: 19.1g PET', 3.00, NULL, '2026-02-22 09:45:21', NULL, NULL),
(33, 2, 'ESP32_001', 'Accepted: 19.7g PET', 3.00, NULL, '2026-02-22 09:45:43', NULL, NULL),
(34, 2, 'ESP32_001', 'Rejected: 12.1g (metal detected)', 0.00, NULL, '2026-02-22 09:45:57', NULL, NULL),
(35, 2, 'ESP32_001', 'Accepted: 16.5g PET', 2.00, NULL, '2026-02-22 09:46:40', NULL, NULL),
(36, 2, 'ESP32_001', 'Rejected: 10.4g (metal detected)', 0.00, NULL, '2026-02-22 09:46:54', NULL, NULL),
(37, 2, 'ESP32_001', 'Accepted: 22.6g PET', 4.00, NULL, '2026-02-22 09:47:08', NULL, NULL),
(38, 3, 'ESP32_001', 'Accepted: 22.8g PET', 4.00, NULL, '2026-02-22 09:50:01', NULL, NULL),
(39, 3, 'ESP32_001', 'Rejected: 14.5g (metal detected)', 0.00, NULL, '2026-02-22 09:50:56', NULL, NULL),
(40, 3, 'ESP32_001', 'Accepted: 19.1g PET', 3.00, NULL, '2026-02-22 09:51:51', NULL, NULL),
(41, 3, 'ESP32_001', 'Accepted: 14.2g PET', 2.00, NULL, '2026-02-22 09:52:48', NULL, NULL),
(42, 3, 'ESP32_001', 'Accepted: 18.7g PET', 3.00, NULL, '2026-02-22 10:01:20', NULL, NULL),
(43, 3, 'ESP32_001', 'Accepted: 21.3g PET', 3.00, NULL, '2026-02-22 10:01:53', NULL, NULL),
(44, 3, 'ESP32_001', 'Accepted: 12.9g PET', 1.00, NULL, '2026-02-22 10:02:02', NULL, NULL),
(45, 3, 'ESP32_001', 'Accepted: 13.5g PET', 2.00, NULL, '2026-02-22 10:02:19', NULL, NULL),
(46, 3, 'ESP32_001', 'Rejected: 12.2g (metal detected)', 0.00, NULL, '2026-02-22 10:02:48', NULL, NULL),
(47, 3, 'ESP32_001', 'Accepted: 18.2g PET', 3.00, NULL, '2026-02-22 10:03:00', NULL, NULL),
(48, 3, 'ESP32_001', 'Rejected: 12.9g (metal detected)', 0.00, NULL, '2026-02-22 10:03:10', NULL, NULL),
(49, 3, 'ESP32_001', 'Accepted: 19.2g PET', 3.00, NULL, '2026-02-22 10:03:23', NULL, NULL),
(50, 3, 'ESP32_001', 'Rejected: 13.2g (metal detected)', 0.00, NULL, '2026-02-22 10:03:49', NULL, NULL),
(51, 3, 'ESP32_001', 'Rejected: 12.1g (metal detected)', 0.00, NULL, '2026-02-22 10:06:56', NULL, NULL),
(52, 3, 'ESP32_001', 'Accepted: 15g PET', 2.00, NULL, '2026-02-22 10:08:51', NULL, NULL),
(53, 3, 'ESP32_001', 'Rejected: 8g (not in range)', 0.00, NULL, '2026-02-22 10:08:51', NULL, NULL),
(54, 3, 'ESP32_001', 'Accepted: 20g PET', 3.00, NULL, '2026-02-22 10:09:22', NULL, NULL),
(55, 3, 'ESP32_001', 'Accepted: 15g PET', 2.00, NULL, '2026-02-22 10:09:29', NULL, NULL),
(56, 3, 'ESP32_001', 'Rejected: 8g (not in range)', 0.00, NULL, '2026-02-22 10:09:29', NULL, NULL),
(57, 3, 'ESP32_001', 'Accepted: 18.3g PET', 3.00, NULL, '2026-02-22 10:09:37', NULL, NULL),
(58, 3, 'ESP32_001', 'Rejected: 14.9g (metal detected)', 0.00, NULL, '2026-02-22 10:09:46', NULL, NULL),
(59, 3, 'ESP32_001', 'Rejected: 4.6g (metal detected)', 0.00, NULL, '2026-02-22 10:10:04', NULL, NULL),
(60, 3, 'ESP32_001', 'Accepted: 15g PET', 2.00, NULL, '2026-02-22 10:10:25', NULL, NULL),
(61, 3, 'ESP32_001', 'Rejected: 8g (not in range)', 0.00, NULL, '2026-02-22 10:10:25', NULL, NULL),
(62, 3, 'ESP32_001', 'Accepted: 17.5g PET', 3.00, NULL, '2026-02-22 10:10:30', NULL, NULL),
(63, 3, 'ESP32_001', 'Accepted: 19.6g PET', 3.00, NULL, '2026-02-22 10:18:58', NULL, NULL),
(64, 3, 'ESP32_001', 'Accepted: 20.6g PET', 3.00, NULL, '2026-02-22 10:23:58', NULL, NULL),
(65, 3, 'ESP32_001', 'Rejected: 12.3g (metal detected)', 0.00, NULL, '2026-02-22 10:30:59', NULL, NULL),
(66, 3, 'ESP32_001', 'Accepted: 18.3g PET', 3.00, NULL, '2026-02-22 10:37:18', NULL, NULL),
(67, 3, 'ESP32_001', 'Accepted: 18.6g PET', 3.00, NULL, '2026-02-22 10:44:27', NULL, NULL),
(68, 3, 'ESP32_001', 'Rejected: 12.5g (metal detected)', 0.00, NULL, '2026-02-22 10:45:36', NULL, NULL),
(69, 3, 'ESP32_001', 'Accepted: 20.8g PET', 3.00, NULL, '2026-02-22 10:48:08', NULL, NULL),
(70, 3, 'ESP32_001', 'Accepted: 12.3g PET', 1.00, NULL, '2026-02-22 10:48:22', NULL, NULL),
(71, 3, 'ESP32_001', 'Rejected: 0.2g (not in range)', 0.00, NULL, '2026-02-24 09:55:26', NULL, NULL),
(72, 3, 'ESP32_001', 'Rejected: -0.2g (not in range)', 0.00, NULL, '2026-02-24 09:55:39', NULL, NULL),
(73, 3, 'ESP32_001', 'Rejected: -0.3g (not in range)', 0.00, NULL, '2026-02-24 09:59:26', NULL, NULL),
(74, 3, 'ESP32_001', 'Accepted: 18.8g PET', 3.00, NULL, '2026-02-24 10:00:55', NULL, NULL),
(75, 3, 'ESP32_001', 'Rejected: -0.1g (not in range)', 0.00, NULL, '2026-02-24 10:01:13', NULL, NULL),
(76, 3, 'ESP32_001', 'Accepted: 19.4g PET', 3.00, NULL, '2026-02-24 10:01:42', NULL, NULL),
(77, 3, 'ESP32_001', 'Rejected: 13.8g (metal detected)', 0.00, NULL, '2026-02-24 10:02:35', NULL, NULL),
(78, 3, 'ESP32_001', 'Accepted: 19.3g PET', 3.00, NULL, '2026-02-24 10:03:35', NULL, NULL),
(79, 3, 'ESP32_001', 'Rejected: 11.6g (metal detected)', 0.00, NULL, '2026-02-24 10:03:43', NULL, NULL),
(80, 2, 'ESP32_001', 'Accepted: 19.1g PET', 3.00, NULL, '2026-02-24 10:40:22', NULL, NULL),
(81, 2, 'ESP32_001', 'Rejected: 11.2g (metal detected)', 0.00, NULL, '2026-02-24 10:41:32', NULL, NULL),
(82, 2, 'ESP32_001', 'Accepted: 15.6g PET', 2.00, NULL, '2026-02-24 10:42:50', NULL, NULL),
(83, 2, 'ESP32_001', 'Rejected: -0g (not in range)', 0.00, NULL, '2026-02-24 10:42:55', NULL, NULL),
(84, 2, 'ESP32_001', 'Rejected: 0.6g (not in range)', 0.00, NULL, '2026-02-24 10:42:59', NULL, NULL),
(85, 2, 'ESP32_001', 'Rejected: -0.5g (not in range)', 0.00, NULL, '2026-02-24 10:43:03', NULL, NULL),
(86, 2, 'ESP32_001', 'Rejected: 0.2g (not in range)', 0.00, NULL, '2026-02-24 10:43:06', NULL, NULL),
(87, 2, 'ESP32_001', 'Rejected: 0.2g (not in range)', 0.00, NULL, '2026-02-24 10:44:33', NULL, NULL),
(88, 2, 'ESP32_001', 'Rejected: -14g (not in range)', 0.00, NULL, '2026-02-24 10:45:01', NULL, NULL),
(89, 2, 'ESP32_001', 'Rejected: 12.3g (metal detected)', 0.00, NULL, '2026-02-24 10:46:33', NULL, NULL),
(90, 2, 'ESP32_001', 'Accepted: 19g PET', 3.00, NULL, '2026-02-24 10:46:45', NULL, NULL),
(91, 2, 'ESP32_001', 'Rejected: 12.9g (metal detected)', 0.00, NULL, '2026-02-24 10:47:14', NULL, NULL),
(92, 2, 'ESP32_001', 'Accepted: 19.2g PET', 3.00, NULL, '2026-02-24 10:47:29', NULL, NULL),
(93, 2, 'ESP32_001', 'Accepted: 22.3g PET', 4.00, NULL, '2026-02-24 10:50:02', NULL, NULL),
(94, 2, 'ESP32_001', 'Rejected: 11.1g (metal detected)', 0.00, NULL, '2026-02-24 10:50:09', NULL, NULL),
(95, 2, 'ESP32_001', 'Accepted: 10.4g PET', 1.00, NULL, '2026-03-08 09:03:12', NULL, NULL),
(96, 2, 'ESP32_001', 'Accepted: 10.3g PET', 1.00, NULL, '2026-03-08 09:03:16', NULL, NULL),
(97, 2, 'ESP32_001', 'Accepted: 16.7g PET', 2.00, NULL, '2026-03-08 09:03:30', NULL, NULL),
(98, 2, 'ESP32_001', 'Rejected: 7.6g (not in range)', 0.00, NULL, '2026-03-08 09:03:59', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `redemptions`
--

CREATE TABLE `redemptions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reward_id` int(11) NOT NULL,
  `tokens_spent` decimal(10,2) NOT NULL,
  `status` enum('pending','approved','fulfilled','cancelled') DEFAULT 'pending',
  `redemption_code` varchar(50) DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `fulfilled_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `redemptions`
--

INSERT INTO `redemptions` (`id`, `user_id`, `reward_id`, `tokens_spent`, `status`, `redemption_code`, `admin_notes`, `created_at`, `fulfilled_at`) VALUES
(1, 2, 3, 30.00, 'cancelled', 'BDA8FBB8', NULL, '2026-02-22 09:46:21', NULL),
(2, 4, 6, 25.00, 'fulfilled', 'FFA05956', NULL, '2026-03-04 02:49:20', '2026-03-04 02:50:05');

-- --------------------------------------------------------

--
-- Table structure for table `resources`
--

CREATE TABLE `resources` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` enum('equipment','facility','service','material') NOT NULL,
  `type` varchar(100) DEFAULT NULL,
  `total_quantity` int(11) DEFAULT 0,
  `available_quantity` int(11) DEFAULT 0,
  `location` varchar(255) DEFAULT NULL,
  `condition_status` enum('excellent','good','fair','poor') DEFAULT 'good',
  `acquisition_date` date DEFAULT NULL,
  `last_maintenance_date` date DEFAULT NULL,
  `next_maintenance_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `requires_approval` tinyint(1) DEFAULT 0,
  `min_user_level` enum('student','officer','admin') DEFAULT 'student',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `resources`
--

INSERT INTO `resources` (`id`, `name`, `description`, `category`, `type`, `total_quantity`, `available_quantity`, `location`, `condition_status`, `acquisition_date`, `last_maintenance_date`, `next_maintenance_date`, `is_active`, `requires_approval`, `min_user_level`, `created_at`, `updated_at`) VALUES
(1, 'Stapler', '', 'equipment', 'borrowable', 4, 4, 'MTICS Office', 'good', NULL, NULL, NULL, 1, 1, 'student', '2026-03-02 14:23:07', '2026-03-04 05:41:30');

-- --------------------------------------------------------

--
-- Table structure for table `resource_reservations`
--

CREATE TABLE `resource_reservations` (
  `id` int(11) NOT NULL,
  `resource_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `purpose` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','cancelled','completed','no_show') DEFAULT 'pending',
  `approval_notes` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `auto_approval` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resource_usage_logs`
--

CREATE TABLE `resource_usage_logs` (
  `id` int(11) NOT NULL,
  `resource_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `usage_type` enum('reservation','checkout','maintenance','repair') NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `tokens_charged` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `resource_utilization`
-- (See below for the actual view)
--
CREATE TABLE `resource_utilization` (
`name` varchar(9)
,`total_reservations` bigint(21)
,`approved_reservations` bigint(21)
,`avg_duration_hours` decimal(24,4)
);

-- --------------------------------------------------------

--
-- Table structure for table `rewards`
--

CREATE TABLE `rewards` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `token_cost` decimal(10,2) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `stock_quantity` int(11) DEFAULT -1,
  `is_active` tinyint(1) DEFAULT 1,
  `image_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rewards`
--

INSERT INTO `rewards` (`id`, `name`, `description`, `token_cost`, `category`, `stock_quantity`, `is_active`, `image_url`, `created_at`) VALUES
(3, 'Internet Access (1 hour)', '1 hour of premium internet access', 30.00, 'Services', -1, 1, NULL, '2026-02-07 05:15:51'),
(5, 'MTICS T-Shirt', 'Official MTICS organization t-shirt', 200.00, 'Merchandise', 50, 1, NULL, '2026-02-07 05:15:51'),
(6, 'MTICS Sticker Pack', 'Set of official MTICS stickers', 25.00, 'Merchandise', 99, 1, NULL, '2026-02-07 05:15:51'),
(7, 'USB Flash Drive (16GB)', '16GB USB flash drive', 150.00, 'Electronics', 20, 1, NULL, '2026-02-07 05:15:51'),
(8, 'Wireless Mouse', 'Ergonomic wireless mouse', 180.00, 'Electronics', 15, 1, NULL, '2026-02-07 05:15:51');

-- --------------------------------------------------------

--
-- Table structure for table `room_reservations`
--

CREATE TABLE `room_reservations` (
  `id` int(11) NOT NULL,
  `room_code` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `purpose` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','cancelled','completed','no_show') DEFAULT 'pending',
  `approval_notes` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `room_reservations`
--

INSERT INTO `room_reservations` (`id`, `room_code`, `user_id`, `start_date`, `end_date`, `purpose`, `status`, `approval_notes`, `rejection_reason`, `approved_by`, `approved_at`, `created_at`, `updated_at`) VALUES
(1, 'RM203', 2, '2026-02-24 15:55:00', '2026-02-24 15:59:00', 'Class', 'approved', '', NULL, 1, '2026-02-23 19:56:25', '2026-02-23 19:55:57', '2026-02-23 19:56:25'),
(2, 'RM203', 2, '2026-02-24 18:21:00', '2026-02-24 18:24:00', 'MTICS Meeting', 'approved', '', NULL, 1, '2026-02-24 10:22:40', '2026-02-24 10:22:06', '2026-02-24 10:22:40'),
(3, 'RM203', 4, '2026-03-04 10:00:00', '2026-03-04 11:00:00', 'Class with Prof. Prof. Joan Mag-isa - Class', 'approved', '', NULL, 1, '2026-03-04 02:44:52', '2026-03-04 02:25:34', '2026-03-04 02:44:52');

-- --------------------------------------------------------

--
-- Table structure for table `sensor_readings`
--

CREATE TABLE `sensor_readings` (
  `id` int(11) NOT NULL,
  `device_id` varchar(50) NOT NULL,
  `weight` decimal(10,2) NOT NULL,
  `distance` decimal(10,2) NOT NULL,
  `is_metal` tinyint(1) NOT NULL DEFAULT 0,
  `accepted` tinyint(1) NOT NULL DEFAULT 0,
  `reading_time` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sensor_readings`
--

INSERT INTO `sensor_readings` (`id`, `device_id`, `weight`, `distance`, `is_metal`, `accepted`, `reading_time`, `created_at`) VALUES
(1, 'TEST_001', 15.50, 8.20, 0, 1, '2026-02-22 15:43:26', '2026-02-22 07:43:26'),
(2, 'TEST_001', 15.50, 8.20, 0, 1, '2026-02-22 15:43:57', '2026-02-22 07:43:57'),
(3, 'TEST_001', 15.50, 8.20, 0, 1, '2026-02-22 15:44:34', '2026-02-22 07:44:34'),
(4, 'TEST_001', 15.50, 8.20, 0, 1, '2026-02-22 15:45:07', '2026-02-22 07:45:07'),
(5, 'TEST_001', 15.50, 8.20, 0, 1, '2026-02-22 15:45:45', '2026-02-22 07:45:45'),
(6, 'TEST_001', 15.50, 8.20, 0, 1, '2026-02-22 15:46:21', '2026-02-22 07:46:21'),
(7, 'ESP32_001', 18.41, 4.85, 0, 1, '1970-01-01 08:05:09', '2026-02-22 07:46:38'),
(8, 'ESP32_001', -0.79, 4.85, 0, 0, '1970-01-01 08:05:14', '2026-02-22 07:46:41'),
(9, 'ESP32_001', 19.02, 3.58, 0, 1, '1970-01-01 08:05:28', '2026-02-22 07:46:55'),
(10, 'ESP32_001', -0.05, 3.58, 0, 0, '1970-01-01 08:05:32', '2026-02-22 07:46:59'),
(11, 'ESP32_001', 18.97, 3.26, 0, 1, '1970-01-01 08:05:50', '2026-02-22 07:47:16'),
(12, 'ESP32_001', -0.26, 3.26, 0, 0, '1970-01-01 08:05:53', '2026-02-22 07:47:24'),
(13, 'ESP32_001', 23.41, 9.38, 0, 1, '1970-01-01 08:06:10', '2026-02-22 07:47:39'),
(14, 'ESP32_001', 19.38, 4.85, 0, 1, '1970-01-01 08:06:20', '2026-02-22 07:47:46'),
(15, 'ESP32_001', 18.88, 4.85, 0, 1, '1970-01-01 08:00:20', '2026-02-22 07:55:05'),
(16, 'ESP32_001', 13.84, 3.28, 1, 0, '1970-01-01 08:00:43', '2026-02-22 07:55:31'),
(17, 'ESP32_001', 11.17, 3.26, 1, 0, '1970-01-01 08:00:55', '2026-02-22 07:55:34'),
(18, 'ESP32_001', 11.54, 4.22, 1, 0, '1970-01-01 08:00:16', '2026-02-22 07:57:09'),
(19, 'ESP32_001', 18.32, 3.58, 0, 1, '1970-01-01 08:00:24', '2026-02-22 07:57:18'),
(20, 'ESP32_001', 19.03, 4.54, 0, 1, '1970-01-01 08:00:50', '2026-02-22 07:57:51'),
(21, 'ESP32_001', 19.07, 4.22, 0, 1, '1970-01-01 08:13:53', '2026-02-22 08:10:46'),
(22, 'ESP32_001', 13.65, 3.89, 0, 1, '1970-01-01 08:16:56', '2026-02-22 08:13:49'),
(23, 'ESP32_001', 18.81, 4.22, 0, 1, '1970-01-01 08:24:01', '2026-02-22 08:21:11'),
(24, 'ESP32_001', 12.36, 3.26, 0, 1, '1970-01-01 09:00:27', '2026-02-22 08:57:41'),
(25, 'ESP32_001', 19.15, 6.45, 0, 1, '1970-01-01 08:00:27', '2026-02-22 09:04:08'),
(26, 'ESP32_001', 18.60, 4.22, 0, 1, '1970-01-01 08:01:02', '2026-02-22 09:04:41'),
(27, 'ESP32_001', 11.28, 6.77, 1, 0, '1970-01-01 08:01:29', '2026-02-22 09:05:11'),
(28, 'ESP32_001', 12.19, 6.77, 0, 1, '1970-01-01 08:07:43', '2026-02-22 09:11:23'),
(29, 'ESP32_001', 13.95, 6.45, 1, 0, '1970-01-01 08:10:33', '2026-02-22 09:14:15'),
(30, 'ESP32_001', 22.59, 4.54, 0, 1, '1970-01-01 08:13:49', '2026-02-22 09:17:32'),
(31, 'ESP32_001', 12.05, 3.26, 1, 0, '1970-01-01 08:15:03', '2026-02-22 09:18:51'),
(32, 'ESP32_001', 13.29, 5.51, 1, 0, '1970-01-01 08:16:02', '2026-02-22 09:19:46'),
(33, 'ESP32_001', 16.80, 6.14, 0, 1, '2026-02-22 17:31:07', '2026-02-22 09:31:07'),
(34, 'ESP32_001', 13.11, 5.18, 1, 0, '2026-02-22 17:31:41', '2026-02-22 09:31:41'),
(35, 'ESP32_001', 11.41, 4.22, 1, 0, '2026-02-22 17:34:32', '2026-02-22 09:34:32'),
(36, 'ESP32_001', 14.20, 3.89, 0, 1, '2026-02-22 17:34:58', '2026-02-22 09:34:58'),
(37, 'ESP32_001', 17.89, 3.26, 0, 1, '2026-02-22 17:40:24', '2026-02-22 09:40:24'),
(38, 'ESP32_001', 11.75, 3.26, 1, 0, '2026-02-22 17:40:48', '2026-02-22 09:40:48'),
(39, 'ESP32_001', 20.85, 3.26, 0, 1, '2026-02-22 17:41:56', '2026-02-22 09:41:56'),
(40, 'ESP32_001', 12.54, 5.49, 0, 1, '2026-02-22 17:42:52', '2026-02-22 09:42:52'),
(41, 'ESP32_001', 13.00, 9.72, 1, 0, '2026-02-22 17:44:03', '2026-02-22 09:44:03'),
(42, 'ESP32_001', 20.20, 4.54, 0, 1, '2026-02-22 17:45:05', '2026-02-22 09:45:05'),
(43, 'ESP32_001', 19.14, 3.26, 0, 1, '2026-02-22 17:45:21', '2026-02-22 09:45:21'),
(44, 'ESP32_001', 19.70, 3.26, 0, 1, '2026-02-22 17:45:43', '2026-02-22 09:45:43'),
(45, 'ESP32_001', 12.11, 3.26, 1, 0, '2026-02-22 17:45:57', '2026-02-22 09:45:57'),
(46, 'ESP32_001', 16.50, 7.08, 0, 1, '2026-02-22 17:46:40', '2026-02-22 09:46:40'),
(47, 'ESP32_001', 10.44, 4.85, 1, 0, '2026-02-22 17:46:54', '2026-02-22 09:46:54'),
(48, 'ESP32_001', 22.59, 5.18, 0, 1, '2026-02-22 17:47:08', '2026-02-22 09:47:08'),
(49, 'ESP32_001', 22.78, 4.54, 0, 1, '2026-02-22 17:50:01', '2026-02-22 09:50:01'),
(50, 'ESP32_001', 14.49, 4.54, 1, 0, '2026-02-22 17:50:56', '2026-02-22 09:50:56'),
(51, 'ESP32_001', 19.09, 4.85, 0, 1, '2026-02-22 17:51:51', '2026-02-22 09:51:51'),
(52, 'ESP32_001', 14.24, 9.72, 0, 1, '2026-02-22 17:52:48', '2026-02-22 09:52:48'),
(53, 'ESP32_001', 18.66, 4.54, 0, 1, '2026-02-22 18:01:20', '2026-02-22 10:01:20'),
(54, 'ESP32_001', 21.30, 2.95, 0, 1, '2026-02-22 18:01:53', '2026-02-22 10:01:53'),
(55, 'ESP32_001', 12.85, 4.54, 0, 1, '2026-02-22 18:02:02', '2026-02-22 10:02:02'),
(56, 'ESP32_001', 13.48, 6.77, 0, 1, '2026-02-22 18:02:19', '2026-02-22 10:02:19'),
(57, 'ESP32_001', 12.23, 3.26, 1, 0, '2026-02-22 18:02:48', '2026-02-22 10:02:48'),
(58, 'ESP32_001', 18.19, 3.89, 0, 1, '2026-02-22 18:03:00', '2026-02-22 10:03:00'),
(59, 'ESP32_001', 12.95, 3.28, 1, 0, '2026-02-22 18:03:10', '2026-02-22 10:03:10'),
(60, 'ESP32_001', 19.24, 9.72, 0, 1, '2026-02-22 18:03:23', '2026-02-22 10:03:23'),
(61, 'ESP32_001', 13.20, 2.93, 1, 0, '2026-02-22 18:03:49', '2026-02-22 10:03:49'),
(62, 'ESP32_001', 12.12, 7.72, 1, 0, '2026-02-22 18:06:56', '2026-02-22 10:06:56'),
(63, 'ESP32_001', 15.00, 5.00, 0, 1, '2026-02-22 18:08:51', '2026-02-22 10:08:51'),
(64, 'ESP32_001', 8.00, 5.00, 0, 0, '2026-02-22 18:08:51', '2026-02-22 10:08:51'),
(65, 'ESP32_001', 19.97, 2.95, 0, 1, '2026-02-22 18:09:22', '2026-02-22 10:09:22'),
(66, 'ESP32_001', 15.00, 5.00, 0, 1, '2026-02-22 18:09:29', '2026-02-22 10:09:29'),
(67, 'ESP32_001', 8.00, 5.00, 0, 0, '2026-02-22 18:09:29', '2026-02-22 10:09:29'),
(68, 'ESP32_001', 18.32, 9.72, 0, 1, '2026-02-22 18:09:37', '2026-02-22 10:09:37'),
(69, 'ESP32_001', 14.86, 5.49, 1, 0, '2026-02-22 18:09:46', '2026-02-22 10:09:46'),
(70, 'ESP32_001', 4.62, 8.35, 1, 0, '2026-02-22 18:10:04', '2026-02-22 10:10:04'),
(71, 'ESP32_001', 15.00, 5.00, 0, 1, '2026-02-22 18:10:25', '2026-02-22 10:10:25'),
(72, 'ESP32_001', 8.00, 5.00, 0, 0, '2026-02-22 18:10:25', '2026-02-22 10:10:25'),
(73, 'ESP32_001', 17.52, 2.93, 0, 1, '2026-02-22 18:10:30', '2026-02-22 10:10:30'),
(74, 'ESP32_001', 19.55, 4.85, 0, 1, '2026-02-22 18:18:58', '2026-02-22 10:18:58'),
(75, 'ESP32_001', 20.59, 4.22, 0, 1, '2026-02-22 18:23:58', '2026-02-22 10:23:58'),
(76, 'ESP32_001', 12.25, 3.26, 1, 0, '2026-02-22 18:30:59', '2026-02-22 10:30:59'),
(77, 'ESP32_001', 18.27, 4.22, 0, 1, '2026-02-22 18:37:18', '2026-02-22 10:37:18'),
(78, 'ESP32_001', 18.60, 3.58, 0, 1, '2026-02-22 18:44:27', '2026-02-22 10:44:27'),
(79, 'ESP32_001', 12.51, 5.49, 1, 0, '2026-02-22 18:45:36', '2026-02-22 10:45:36'),
(80, 'ESP32_001', 20.80, 4.54, 0, 1, '2026-02-22 18:48:08', '2026-02-22 10:48:08'),
(81, 'ESP32_001', 12.26, 8.04, 0, 1, '2026-02-22 18:48:22', '2026-02-22 10:48:22'),
(82, 'ESP32_001', 0.24, 8.35, 0, 0, '2026-02-24 17:55:26', '2026-02-24 09:55:26'),
(83, 'ESP32_001', -0.21, 8.71, 0, 0, '2026-02-24 17:55:39', '2026-02-24 09:55:39'),
(84, 'ESP32_001', -0.29, 8.35, 0, 0, '2026-02-24 17:59:26', '2026-02-24 09:59:26'),
(85, 'ESP32_001', 18.78, 3.58, 0, 1, '2026-02-24 18:00:55', '2026-02-24 10:00:55'),
(86, 'ESP32_001', -0.13, 8.71, 0, 0, '2026-02-24 18:01:13', '2026-02-24 10:01:13'),
(87, 'ESP32_001', 19.36, 3.26, 0, 1, '2026-02-24 18:01:42', '2026-02-24 10:01:42'),
(88, 'ESP32_001', 13.78, 4.85, 1, 0, '2026-02-24 18:02:35', '2026-02-24 10:02:35'),
(89, 'ESP32_001', 19.34, 3.26, 0, 1, '2026-02-24 18:03:35', '2026-02-24 10:03:35'),
(90, 'ESP32_001', 11.61, 4.22, 1, 0, '2026-02-24 18:03:43', '2026-02-24 10:03:43'),
(91, 'ESP32_001', 19.13, 4.22, 0, 1, '2026-02-24 18:40:22', '2026-02-24 10:40:22'),
(92, 'ESP32_001', 11.17, 4.85, 1, 0, '2026-02-24 18:41:32', '2026-02-24 10:41:32'),
(93, 'ESP32_001', 15.61, 4.22, 0, 1, '2026-02-24 18:42:50', '2026-02-24 10:42:50'),
(94, 'ESP32_001', -0.04, 0.00, 0, 0, '2026-02-24 18:42:55', '2026-02-24 10:42:55'),
(95, 'ESP32_001', 0.63, 0.00, 0, 0, '2026-02-24 18:42:59', '2026-02-24 10:42:59'),
(96, 'ESP32_001', -0.52, 0.00, 0, 0, '2026-02-24 18:43:03', '2026-02-24 10:43:03'),
(97, 'ESP32_001', 0.24, 0.00, 0, 0, '2026-02-24 18:43:06', '2026-02-24 10:43:06'),
(98, 'ESP32_001', 0.21, 8.71, 0, 0, '2026-02-24 18:44:33', '2026-02-24 10:44:33'),
(99, 'ESP32_001', -14.01, 3.28, 0, 0, '2026-02-24 18:45:01', '2026-02-24 10:45:01'),
(100, 'ESP32_001', 12.34, 4.54, 1, 0, '2026-02-24 18:46:33', '2026-02-24 10:46:33'),
(101, 'ESP32_001', 18.99, 4.22, 0, 1, '2026-02-24 18:46:45', '2026-02-24 10:46:45'),
(102, 'ESP32_001', 12.89, 6.45, 1, 0, '2026-02-24 18:47:14', '2026-02-24 10:47:14'),
(103, 'ESP32_001', 19.20, 5.49, 0, 1, '2026-02-24 18:47:29', '2026-02-24 10:47:29'),
(104, 'ESP32_001', 22.26, 4.54, 0, 1, '2026-02-24 18:50:02', '2026-02-24 10:50:02'),
(105, 'ESP32_001', 11.06, 3.91, 1, 0, '2026-02-24 18:50:09', '2026-02-24 10:50:09'),
(106, 'ESP32_001', 10.40, 5.81, 0, 1, '2026-03-08 17:03:12', '2026-03-08 09:03:12'),
(107, 'ESP32_001', 10.29, 5.81, 0, 1, '2026-03-08 17:03:16', '2026-03-08 09:03:16'),
(108, 'ESP32_001', 16.70, 5.18, 0, 1, '2026-03-08 17:03:30', '2026-03-08 09:03:30'),
(109, 'ESP32_001', 7.62, 7.08, 0, 0, '2026-03-08 17:03:59', '2026-03-08 09:03:59');

-- --------------------------------------------------------

--
-- Table structure for table `service_requests`
--

CREATE TABLE `service_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `service_type` enum('printing','internet_access','equipment_borrowing','consultation','other') NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `urgency` enum('low','medium','high','critical') DEFAULT 'medium',
  `status` enum('pending','in_progress','completed','cancelled','rejected') DEFAULT 'pending',
  `assigned_to` int(11) DEFAULT NULL,
  `estimated_completion_date` date DEFAULT NULL,
  `actual_completion_date` date DEFAULT NULL,
  `completion_notes` text DEFAULT NULL,
  `tokens_required` decimal(10,2) DEFAULT 0.00,
  `tokens_charged` decimal(10,2) DEFAULT 0.00,
  `file_path` varchar(500) DEFAULT NULL,
  `auto_approval` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `service_requests`
--

INSERT INTO `service_requests` (`id`, `user_id`, `service_type`, `title`, `description`, `urgency`, `status`, `assigned_to`, `estimated_completion_date`, `actual_completion_date`, `completion_notes`, `tokens_required`, `tokens_charged`, `file_path`, `auto_approval`, `created_at`, `updated_at`) VALUES
(2, 2, 'printing', '', '', 'medium', 'rejected', NULL, NULL, NULL, '', 0.00, 0.00, '/uploads/printing/print_2_1771736827_L13-15 ABBRE+ENUM.pdf', 0, '2026-02-22 05:07:07', '2026-02-23 18:16:12'),
(3, 2, 'printing', '', '', 'medium', 'rejected', NULL, NULL, NULL, '', 0.00, 0.00, '/uploads/printing/print_2_1771736942_L13-15 ABBRE+ENUM.pdf', 0, '2026-02-22 05:09:02', '2026-02-23 18:16:09'),
(4, 2, 'equipment_borrowing', 'Projector', 'Class', 'medium', 'completed', NULL, NULL, '2026-02-22', 'Service completed successfully', 0.00, 0.00, '', 0, '2026-02-22 05:33:43', '2026-02-22 06:03:55'),
(5, 2, 'equipment_borrowing', 'Projector', 'Class', 'medium', 'rejected', NULL, NULL, NULL, 'Request rejected by admin', 0.00, 0.00, '', 0, '2026-02-22 05:38:52', '2026-02-22 12:05:06'),
(6, 2, 'internet_access', '', '', 'medium', 'completed', NULL, NULL, '2026-02-22', 'Service completed successfully', 0.00, 0.00, '', 0, '2026-02-22 06:20:14', '2026-02-22 06:20:44'),
(7, 2, 'equipment_borrowing', 'HDMI Cable', 'class', 'medium', 'completed', NULL, NULL, '2026-02-24', 'Service completed successfully', 0.00, 0.00, '', 0, '2026-02-23 17:33:30', '2026-02-23 17:34:25'),
(8, 2, 'equipment_borrowing', 'Audio Speaker', 'class', 'medium', 'completed', 1, NULL, '2026-02-24', '', 0.00, 0.00, '', 0, '2026-02-23 18:12:55', '2026-02-23 20:18:40'),
(9, 2, 'printing', '', '', 'medium', 'completed', 1, NULL, '2026-02-24', '', 0.00, 0.00, '/uploads/printing/print_2_1771870611_COAA GROUP 9 PPT DOCU (2).pdf', 0, '2026-02-23 18:16:51', '2026-02-23 20:18:45'),
(10, 2, 'printing', 'Printing', '', 'medium', 'completed', 1, NULL, '2026-02-24', '', 0.00, 0.00, '/uploads/printing/print_2_1771870755_COAA GROUP 9 PPT DOCU (2).pdf', 0, '2026-02-23 18:19:15', '2026-02-23 20:18:52'),
(11, 2, 'printing', 'Printing', '', 'medium', 'completed', 1, NULL, '2026-02-24', '', 0.00, 0.00, '/uploads/printing/print_2_1771877693_COAA GROUP 9 PPT DOCU (2).pdf', 0, '2026-02-23 20:14:53', '2026-02-23 20:18:57'),
(12, 2, 'printing', 'Printing', '', 'medium', 'completed', 1, NULL, '2026-02-24', '', 5.00, 5.00, '/uploads/printing/print_2_1771878029_COAA GROUP 9 PPT DOCU (2).pdf', 0, '2026-02-23 20:20:29', '2026-02-23 20:21:18'),
(13, 2, 'printing', 'Printing', '', 'medium', 'completed', 1, NULL, '2026-02-24', '', 10.00, 10.00, '/uploads/printing/print_2_1771928288_G6.pdf', 0, '2026-02-24 10:18:08', '2026-02-24 10:18:58'),
(14, 2, 'equipment_borrowing', 'Projector', 'Class', 'medium', 'completed', 1, NULL, '2026-02-24', '', 0.00, 0.00, '', 0, '2026-02-24 10:20:05', '2026-02-24 10:20:48'),
(15, 4, 'printing', 'Printing', '', 'medium', 'completed', 1, NULL, '2026-03-04', '', 3.00, 3.00, '/uploads/printing/print_4_1772590961_GROUP 1 SA&D - CHAPTER 1-3 (2).pdf', 0, '2026-03-04 02:22:41', '2026-03-04 02:45:36'),
(16, 4, 'equipment_borrowing', 'Projector', 'Class', 'medium', 'completed', 1, NULL, '2026-03-04', '', 0.00, 0.00, '', 0, '2026-03-04 02:23:55', '2026-03-04 02:45:58'),
(17, 2, 'printing', 'Printing', '', 'medium', 'pending', NULL, NULL, NULL, NULL, 50.00, 50.00, '/uploads/printing/print_2_1772626779_GROUP 1 SA&D - CHAPTER 1-3 (2).pdf', 0, '2026-03-04 12:19:39', '2026-03-04 12:19:39');

-- --------------------------------------------------------

--
-- Stand-in structure for view `service_request_stats`
-- (See below for the actual view)
--
CREATE TABLE `service_request_stats` (
`service_type` enum('printing','internet_access','equipment_borrowing','consultation','other')
,`total_requests` bigint(21)
,`completed_requests` bigint(21)
,`pending_requests` bigint(21)
,`avg_completion_hours` decimal(24,4)
);

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `transaction_type` enum('earned','redeemed','admin_adjustment') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `related_reward_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `user_id`, `transaction_type`, `amount`, `description`, `related_reward_id`, `created_at`) VALUES
(1, 2, 'earned', 5.00, 'Event attendance reward: 2nd General Assembly', NULL, '2026-02-22 03:47:05'),
(2, 1, 'earned', 1.00, 'Recycling: 15.5g via TEST_001', NULL, '2026-02-22 07:45:45'),
(3, 1, 'earned', 1.00, 'Recycling: 15.5g via TEST_001', NULL, '2026-02-22 07:46:21'),
(4, 1, 'earned', 1.00, 'Recycling: 18.40511g via ESP32_001', NULL, '2026-02-22 07:46:38'),
(5, 1, 'earned', 1.00, 'Recycling: 19.02349g via ESP32_001', NULL, '2026-02-22 07:46:55'),
(6, 1, 'earned', 1.00, 'Recycling: 18.96768g via ESP32_001', NULL, '2026-02-22 07:47:16'),
(7, 1, 'earned', 2.00, 'Recycling: 23.41394g via ESP32_001', NULL, '2026-02-22 07:47:39'),
(8, 1, 'earned', 1.00, 'Recycling: 19.38162g via ESP32_001', NULL, '2026-02-22 07:47:46'),
(9, 1, 'earned', 1.00, 'Recycling: 18.8773g via ESP32_001', NULL, '2026-02-22 07:55:05'),
(10, 1, 'earned', 1.00, 'Recycling: 18.31567g via ESP32_001', NULL, '2026-02-22 07:57:18'),
(11, 1, 'earned', 1.00, 'Recycling: 19.03044g via ESP32_001', NULL, '2026-02-22 07:57:51'),
(12, 2, 'redeemed', 30.00, 'Redeemed: Internet Access (1 hour)', 3, '2026-02-22 09:46:21'),
(13, 2, 'earned', 10.00, 'Event attendance reward: Year-End Party', NULL, '2026-02-23 16:33:02'),
(14, 2, 'redeemed', 5.00, 'Service: Printing', NULL, '2026-02-23 20:21:18'),
(15, 2, 'earned', 10.00, 'Event attendance reward: MTICS x Gen AI Philippines', NULL, '2026-02-24 10:14:24'),
(16, 2, 'redeemed', 10.00, 'Service: Printing', NULL, '2026-02-24 10:18:58'),
(17, 4, 'admin_adjustment', 100.00, 'Testing', NULL, '2026-03-04 02:31:57'),
(18, 4, 'redeemed', 3.00, 'Service: Printing', NULL, '2026-03-04 02:45:36'),
(19, 4, 'redeemed', 25.00, 'Redeemed: MTICS Sticker Pack', 6, '2026-03-04 02:49:20');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `course` varchar(100) DEFAULT NULL,
  `year_level` varchar(20) DEFAULT NULL,
  `eco_tokens` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `is_admin` tinyint(1) DEFAULT 0,
  `profile_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `student_id`, `email`, `password_hash`, `full_name`, `course`, `year_level`, `eco_tokens`, `created_at`, `last_login`, `is_active`, `is_admin`, `profile_image`) VALUES
(1, 'mtics.official', 'mtics.official@mtics.edu.ph', '$2y$10$x6jIuYXyhc6V477JM0iNkuxhmRfinCK5D8BcRxW.yUCbT0nKPNPqq', 'MTICS Official Admin', NULL, NULL, 0.00, '2026-02-07 05:15:51', '2026-03-09 11:10:31', 1, 1, NULL),
(2, 'TUPT-24-0428', 'aye@gmail.com', '$2y$10$ei01QsAkb66jAje1H00DkeXsKM5schIV0/pXbLX5T0MLe1ZXcWS0K', 'Ayessa Pili', 'IT', '2nd Year', 32.00, '2026-02-07 05:30:55', '2026-03-04 11:54:59', 1, 0, NULL),
(3, 'TUPT-25-0406', 'email123@gmail.com', '$2y$10$kd8CE529L/o5vM8ocb8Tz.69nXvPCVt0wAv3/5LkkYr52pbf0ELGG', 'Jonel A. Caisip', 'BSIT', '2nd Year', 64.00, '2026-02-22 09:48:26', '2026-02-24 10:38:49', 1, 0, NULL),
(4, 'TUPT-24-1234', 'juliadatang@tup.edu.ph', '$2y$10$ejfdLEP.cBxnRoOFt/sj3O52SdFleOWxudbJgPhtJfRyGPy.hfUVu', 'Julia Datang', 'BSIT', '2nd Year', 72.00, '2026-03-04 02:19:58', '2026-03-04 16:41:03', 1, 0, 'http://localhost/BinItToWinIt/uploads/avatars/avatar_4_1772631667.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `user_devices`
--

CREATE TABLE `user_devices` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `device_id` varchar(50) NOT NULL,
  `device_name` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `paired_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_activity_at` timestamp NULL DEFAULT NULL,
  `total_recycled_weight` decimal(10,2) DEFAULT 0.00,
  `total_tokens_earned` int(11) DEFAULT 0,
  `unpaired_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_devices`
--

INSERT INTO `user_devices` (`id`, `user_id`, `device_id`, `device_name`, `is_active`, `paired_at`, `last_activity_at`, `total_recycled_weight`, `total_tokens_earned`, `unpaired_at`) VALUES
(4, 2, 'ESP32_001', 'BinItToWinIt', 1, '2026-03-04 02:39:51', '2026-03-08 09:03:31', 813.71, 116, NULL);

-- --------------------------------------------------------

--
-- Structure for view `event_statistics`
--
DROP TABLE IF EXISTS `event_statistics`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `event_statistics`  AS SELECT `e`.`id` AS `id`, `e`.`title` AS `title`, `e`.`event_date` AS `event_date`, count(distinct `ea`.`user_id`) AS `total_attendees`, count(distinct case when `ea`.`attendance_status` = 'approved' then `ea`.`user_id` end) AS `approved_attendees`, count(distinct case when `ea`.`attendance_status` = 'pending' then `ea`.`user_id` end) AS `pending_attendees`, sum(case when `ea`.`attendance_status` = 'approved' then `ea`.`tokens_awarded` else 0 end) AS `total_tokens_awarded` FROM (`events` `e` left join `event_attendance` `ea` on(`e`.`id` = `ea`.`event_id`)) GROUP BY `e`.`id`, `e`.`title`, `e`.`event_date` ;

-- --------------------------------------------------------

--
-- Structure for view `resource_utilization`
--
DROP TABLE IF EXISTS `resource_utilization`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `resource_utilization`  AS SELECT 'Equipment' AS `name`, count(0) AS `total_reservations`, count(case when `sr`.`status` = 'completed' then 1 end) AS `approved_reservations`, avg(case when `sr`.`status` = 'completed' and `sr`.`actual_completion_date` is not null then timestampdiff(HOUR,`sr`.`created_at`,`sr`.`actual_completion_date`) else NULL end) AS `avg_duration_hours` FROM `service_requests` AS `sr` WHERE `sr`.`service_type` = 'equipment_borrowing' GROUP BY `sr`.`service_type` ;

-- --------------------------------------------------------

--
-- Structure for view `service_request_stats`
--
DROP TABLE IF EXISTS `service_request_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `service_request_stats`  AS SELECT `sr`.`service_type` AS `service_type`, count(0) AS `total_requests`, count(case when `sr`.`status` = 'completed' then 1 end) AS `completed_requests`, count(case when `sr`.`status` = 'pending' then 1 end) AS `pending_requests`, avg(case when `sr`.`status` = 'completed' and `sr`.`actual_completion_date` is not null then timestampdiff(HOUR,`sr`.`created_at`,`sr`.`actual_completion_date`) else NULL end) AS `avg_completion_hours` FROM `service_requests` AS `sr` GROUP BY `sr`.`service_type` ORDER BY count(0) DESC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `api_keys`
--
ALTER TABLE `api_keys`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `device_id` (`device_id`),
  ADD UNIQUE KEY `api_key` (`api_key`),
  ADD KEY `idx_api_key` (`api_key`),
  ADD KEY `idx_device_id` (`device_id`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `automation_rules`
--
ALTER TABLE `automation_rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rule_type` (`rule_type`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_thread_id` (`thread_id`);

--
-- Indexes for table `contact_threads`
--
ALTER TABLE `contact_threads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_event_date` (`event_date`),
  ADD KEY `idx_is_published` (`is_published`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `event_attendance`
--
ALTER TABLE `event_attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_event_user` (`event_id`,`user_id`),
  ADD KEY `reviewed_by` (`reviewed_by`),
  ADD KEY `idx_event_id` (`event_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`attendance_status`),
  ADD KEY `idx_submitted_at` (`submitted_at`);

--
-- Indexes for table `internet_plans`
--
ALTER TABLE `internet_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_is_published` (`is_published`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `org_officers`
--
ALTER TABLE `org_officers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_display_order` (`display_order`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `printing_services`
--
ALTER TABLE `printing_services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_is_featured` (`is_featured`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `recycling_activities`
--
ALTER TABLE `recycling_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_sensor_id` (`sensor_id`);

--
-- Indexes for table `redemptions`
--
ALTER TABLE `redemptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `redemption_code` (`redemption_code`),
  ADD KEY `reward_id` (`reward_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_redemption_code` (`redemption_code`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `resources`
--
ALTER TABLE `resources`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_available_quantity` (`available_quantity`);

--
-- Indexes for table `resource_reservations`
--
ALTER TABLE `resource_reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_resource_id` (`resource_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_start_date` (`start_date`),
  ADD KEY `idx_end_date` (`end_date`);

--
-- Indexes for table `resource_usage_logs`
--
ALTER TABLE `resource_usage_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_resource_id` (`resource_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_usage_type` (`usage_type`),
  ADD KEY `idx_start_time` (`start_time`);

--
-- Indexes for table `rewards`
--
ALTER TABLE `rewards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_token_cost` (`token_cost`);

--
-- Indexes for table `room_reservations`
--
ALTER TABLE `room_reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_room_code` (`room_code`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_start_date` (`start_date`),
  ADD KEY `idx_end_date` (`end_date`);

--
-- Indexes for table `sensor_readings`
--
ALTER TABLE `sensor_readings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_device_time` (`device_id`,`reading_time`),
  ADD KEY `idx_accepted` (`accepted`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `service_requests`
--
ALTER TABLE `service_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_service_type` (`service_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_urgency` (`urgency`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_transaction_type` (`transaction_type`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_is_admin` (`is_admin`);

--
-- Indexes for table `user_devices`
--
ALTER TABLE `user_devices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_device` (`user_id`,`device_id`),
  ADD KEY `idx_device` (`device_id`),
  ADD KEY `idx_active` (`is_active`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `api_keys`
--
ALTER TABLE `api_keys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `automation_rules`
--
ALTER TABLE `automation_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `contact_threads`
--
ALTER TABLE `contact_threads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `event_attendance`
--
ALTER TABLE `event_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `internet_plans`
--
ALTER TABLE `internet_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `news`
--
ALTER TABLE `news`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `org_officers`
--
ALTER TABLE `org_officers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `printing_services`
--
ALTER TABLE `printing_services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `recycling_activities`
--
ALTER TABLE `recycling_activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- AUTO_INCREMENT for table `redemptions`
--
ALTER TABLE `redemptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `resources`
--
ALTER TABLE `resources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `resource_reservations`
--
ALTER TABLE `resource_reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `resource_usage_logs`
--
ALTER TABLE `resource_usage_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rewards`
--
ALTER TABLE `rewards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `room_reservations`
--
ALTER TABLE `room_reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sensor_readings`
--
ALTER TABLE `sensor_readings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=110;

--
-- AUTO_INCREMENT for table `service_requests`
--
ALTER TABLE `service_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `user_devices`
--
ALTER TABLE `user_devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD CONSTRAINT `contact_messages_ibfk_1` FOREIGN KEY (`thread_id`) REFERENCES `contact_threads` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `contact_threads`
--
ALTER TABLE `contact_threads`
  ADD CONSTRAINT `contact_threads_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `event_attendance`
--
ALTER TABLE `event_attendance`
  ADD CONSTRAINT `event_attendance_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_attendance_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_attendance_ibfk_3` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `recycling_activities`
--
ALTER TABLE `recycling_activities`
  ADD CONSTRAINT `recycling_activities_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `redemptions`
--
ALTER TABLE `redemptions`
  ADD CONSTRAINT `redemptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `redemptions_ibfk_2` FOREIGN KEY (`reward_id`) REFERENCES `rewards` (`id`);

--
-- Constraints for table `resource_reservations`
--
ALTER TABLE `resource_reservations`
  ADD CONSTRAINT `resource_reservations_ibfk_1` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `resource_reservations_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `resource_reservations_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `resource_usage_logs`
--
ALTER TABLE `resource_usage_logs`
  ADD CONSTRAINT `resource_usage_logs_ibfk_1` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `resource_usage_logs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `room_reservations`
--
ALTER TABLE `room_reservations`
  ADD CONSTRAINT `room_reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `room_reservations_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `service_requests`
--
ALTER TABLE `service_requests`
  ADD CONSTRAINT `service_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `service_requests_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_devices`
--
ALTER TABLE `user_devices`
  ADD CONSTRAINT `user_devices_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
