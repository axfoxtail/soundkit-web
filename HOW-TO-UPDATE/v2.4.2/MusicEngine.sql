-- phpMyAdmin SQL Dump
-- version 4.8.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Nov 24, 2019 at 11:52 AM
-- Server version: 10.1.33-MariaDB
-- PHP Version: 7.2.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `app1`
--

-- --------------------------------------------------------

--
-- Table structure for table `bank_transfers`
--

CREATE TABLE IF NOT EXISTS `bank_transfers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `type` varchar(255) DEFAULT NULL,
  `typeid` varchar(255) DEFAULT NULL,
  `price` varchar(255) DEFAULT NULL,
  `file` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `blocked_users`
--

CREATE TABLE IF NOT EXISTS `blocked_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `blocked` int(11) NOT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `blogs`
--

CREATE TABLE IF NOT EXISTS `blogs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `content` text COLLATE utf8_unicode_ci,
  `tags` text COLLATE utf8_unicode_ci,
  `category` int(11) NOT NULL DEFAULT '0',
  `image` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `status` int(11) NOT NULL DEFAULT '1',
  `views` int(11) NOT NULL DEFAULT '0',
  `time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat`
--

CREATE TABLE IF NOT EXISTS `chat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from_uid` int(11) NOT NULL,
  `to_uid` int(11) NOT NULL,
  `cid` int(11) NOT NULL,
  `message` text COLLATE utf8_unicode_ci,
  `trackid` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `playlistid` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_read` int(11) NOT NULL DEFAULT '0',
  `time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE IF NOT EXISTS `comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  `typeid` int(11) NOT NULL,
  `message` text NOT NULL,
  `track_at` varchar(255) DEFAULT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=121 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `conversations`
--

CREATE TABLE IF NOT EXISTS `conversations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user1` int(11) NOT NULL,
  `user2` int(11) NOT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `custom_lyrics`
--

CREATE TABLE IF NOT EXISTS `custom_lyrics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `track_id` int(11) NOT NULL,
  `width` varchar(255) NOT NULL,
  `left_x` varchar(255) NOT NULL,
  `start_percent` varchar(255) NOT NULL,
  `end_percent` varchar(255) NOT NULL,
  `start_duration` varchar(255) NOT NULL,
  `end_duration` varchar(255) NOT NULL,
  `text_value` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `downloads`
--

CREATE TABLE IF NOT EXISTS `downloads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `track` int(11) NOT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `follow`
--

CREATE TABLE IF NOT EXISTS `follow` (
  `fid` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `follow_id` int(11) NOT NULL,
  PRIMARY KEY (`fid`)
) ENGINE=InnoDB AUTO_INCREMENT=118 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `genre`
--

CREATE TABLE IF NOT EXISTS `genre` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `uses` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `genre`
--

INSERT INTO `genre` (`id`, `name`, `uses`) VALUES
(1, 'Classical', 0),
(2, 'Comedy', 0),
(3, 'Hip-hop', 0),
(4, 'Jazz', 0),
(5, 'Pop', 0),
(6, 'Reggae', 0);

-- --------------------------------------------------------

--
-- Table structure for table `info_pages`
--

CREATE TABLE IF NOT EXISTS `info_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `url` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8_unicode_ci NOT NULL,
  `location` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `likes`
--

CREATE TABLE IF NOT EXISTS `likes` (
  `likeid` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  `typeid` int(11) NOT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`likeid`)
) ENGINE=InnoDB AUTO_INCREMENT=102 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `listen_history`
--

CREATE TABLE IF NOT EXISTS `listen_history` (
  `listener` int(11) NOT NULL,
  `trackid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `listen_later`
--

CREATE TABLE IF NOT EXISTS `listen_later` (
  `listener` int(11) NOT NULL,
  `trackid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mobile_keys`
--

CREATE TABLE IF NOT EXISTS `mobile_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `apikey` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `device` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from_userid` int(11) NOT NULL,
  `to_userid` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  `typeid` varchar(255) NOT NULL,
  `is_read` int(11) NOT NULL DEFAULT '0',
  `time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=120 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `playlist`
--

CREATE TABLE IF NOT EXISTS `playlist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` varchar(255) DEFAULT NULL,
  `description` text,
  `art` varchar(255) DEFAULT NULL,
  `public` int(11) NOT NULL DEFAULT '1',
  `playlist_type` int(11) NOT NULL DEFAULT '1',
  `release_date` varchar(255) DEFAULT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `playlistentries`
--

CREATE TABLE IF NOT EXISTS `playlistentries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `track` int(11) NOT NULL,
  `playlist` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=243 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `plugins`
--

CREATE TABLE IF NOT EXISTS `plugins` (
  `id` varchar(255) NOT NULL,
  `active` int(11) NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `purchased`
--

CREATE TABLE IF NOT EXISTS `purchased` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `trackid` varchar(255) NOT NULL,
  `videoid` int(11) NOT NULL DEFAULT '0',
  `time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `radios`
--

CREATE TABLE IF NOT EXISTS `radios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text,
  `slug` varchar(255) DEFAULT NULL,
  `link` text,
  `link_type` varchar(255) NOT NULL,
  `art` varchar(255) DEFAULT NULL,
  `genre` int(11) DEFAULT NULL,
  `tags` text,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `radio_listeners`
--

CREATE TABLE IF NOT EXISTS `radio_listeners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `userip` varchar(255) DEFAULT NULL,
  `radio_id` int(11) NOT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `rasheed_ads`
--

CREATE TABLE IF NOT EXISTS `rasheed_ads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ad_type` int(11) NOT NULL DEFAULT '1',
  `ad_title` varchar(255) NOT NULL,
  `ad_media` varchar(255) NOT NULL,
  `ad_duration` varchar(255) NOT NULL,
  `ad_link` text NOT NULL,
  `ad_country` varchar(255) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '1',
  `start_date` int(11) DEFAULT '0',
  `end_date` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE IF NOT EXISTS `reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  `typeid` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE IF NOT EXISTS `settings` (
  `settings_key` varchar(255) NOT NULL,
  `settings_value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `spotlight`
--

CREATE TABLE IF NOT EXISTS `spotlight` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trackid` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `playlistid` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `userid` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_global` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stream`
--

CREATE TABLE IF NOT EXISTS `stream` (
  `streamid` int(11) NOT NULL AUTO_INCREMENT,
  `poster` int(11) NOT NULL,
  `trackid` varchar(255) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `playlist_id` varchar(255) DEFAULT NULL,
  `streamtime` int(11) NOT NULL,
  PRIMARY KEY (`streamid`)
) ENGINE=InnoDB AUTO_INCREMENT=246 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

CREATE TABLE IF NOT EXISTS `tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `uses` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=171 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `tmp_files`
--

CREATE TABLE IF NOT EXISTS `tmp_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `path` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=481 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tracks`
--

CREATE TABLE IF NOT EXISTS `tracks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `slug` varchar(255) DEFAULT NULL,
  `tag` text NOT NULL,
  `genre` int(11) NOT NULL,
  `art` text,
  `buy` varchar(255) DEFAULT NULL,
  `record` text,
  `track_release` text,
  `license` text,
  `size` varchar(255) DEFAULT NULL,
  `track_file` varchar(255) DEFAULT NULL,
  `featuring` text,
  `lyrics` varchar(255) DEFAULT NULL,
  `demo_file` varchar(255) DEFAULT NULL,
  `demo_duration` varchar(255) DEFAULT NULL,
  `demo_wave` varchar(255) DEFAULT NULL,
  `demo_wave_colored` varchar(255) DEFAULT NULL,
  `price` float(11,2) DEFAULT NULL,
  `track_duration` varchar(100) DEFAULT NULL,
  `public` int(11) NOT NULL DEFAULT '1',
  `downloads` varchar(255) DEFAULT NULL,
  `views` varchar(255) DEFAULT NULL,
  `wave` text,
  `wave_colored` text,
  `status` int(11) NOT NULL DEFAULT '1',
  `release_date` varchar(100) DEFAULT NULL,
  `comments` int(11) NOT NULL DEFAULT '1',
  `stats` int(11) NOT NULL DEFAULT '1',
  `embed` int(11) NOT NULL DEFAULT '1',
  `approved` int(11) NOT NULL DEFAULT '1',
  `wav` varchar(255) DEFAULT NULL,
  `stems` varchar(255) DEFAULT NULL,
  `download_hash` varchar(255) DEFAULT NULL,
  `only_premium` int(11) NOT NULL DEFAULT '0',
  `time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=423 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE IF NOT EXISTS `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `country` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sale_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `type_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `amount` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `amount_credited` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `currency` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT '0',
  `valid_time` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `bio` text,
  `avatar` varchar(255) DEFAULT NULL,
  `cover` varchar(255) DEFAULT NULL,
  `role` int(11) NOT NULL DEFAULT '1',
  `role_id` int(11) NOT NULL DEFAULT '0',
  `country` varchar(200) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `facebook` varchar(150) DEFAULT NULL,
  `twitter` varchar(150) DEFAULT NULL,
  `youtube` varchar(150) DEFAULT NULL,
  `vimeo` varchar(150) DEFAULT NULL,
  `soundcloud` varchar(150) DEFAULT NULL,
  `instagram` varchar(255) DEFAULT NULL,
  `banned` int(11) NOT NULL DEFAULT '0',
  `active` int(11) NOT NULL DEFAULT '1',
  `gender` varchar(100) DEFAULT NULL,
  `ip` varchar(50) DEFAULT NULL,
  `notifyl` int(11) NOT NULL DEFAULT '1',
  `notifyc` int(11) NOT NULL DEFAULT '1',
  `notifym` int(11) NOT NULL DEFAULT '1',
  `notifyf` int(11) NOT NULL DEFAULT '1',
  `email_c` int(11) NOT NULL DEFAULT '1',
  `email_l` int(11) NOT NULL DEFAULT '1',
  `email_f` int(11) NOT NULL DEFAULT '1',
  `email_letter` int(11) NOT NULL DEFAULT '1',
  `privacy` text,
  `user_type` int(11) NOT NULL DEFAULT '1',
  `featured` int(11) NOT NULL DEFAULT '0',
  `token` varchar(100) DEFAULT NULL,
  `has_tried` int(11) NOT NULL DEFAULT '0',
  `facebookid` varchar(255) DEFAULT NULL,
  `is_seller` int(11) NOT NULL DEFAULT '0',
  `balance` varchar(255) DEFAULT NULL,
  `wallet` varchar(255) DEFAULT NULL,
  `currency` varchar(255) DEFAULT NULL,
  `funds` float(11,2) DEFAULT NULL,
  `payment_details` text,
  `stripe_customer_id` varchar(255) DEFAULT NULL,
  `stripe_subscription_id` varchar(255) DEFAULT NULL,
  `verify_details` text,
  `two_factor_auth` varchar(255) DEFAULT NULL,
  `phone_number` varchar(255) DEFAULT NULL,
  `old_id` int(11) DEFAULT NULL,
  `date_created` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `username` (`username`),
  KEY `email` (`email`),
  KEY `full_name` (`full_name`),
  KEY `user_type` (`user_type`)
) ENGINE=InnoDB AUTO_INCREMENT=69 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `full_name`, `bio`, `avatar`, `cover`, `role`, `role_id`, `country`, `city`, `website`, `facebook`, `twitter`, `youtube`, `vimeo`, `soundcloud`, `instagram`, `banned`, `active`, `gender`, `ip`, `notifyl`, `notifyc`, `notifym`, `notifyf`, `email_c`, `email_l`, `email_f`, `email_letter`, `privacy`, `user_type`, `featured`, `token`, `has_tried`, `facebookid`, `is_seller`, `balance`, `wallet`, `currency`, `funds`, `payment_details`, `stripe_customer_id`, `stripe_subscription_id`, `verify_details`, `two_factor_auth`, `phone_number`, `old_id`, `date_created`) VALUES
(1, 'adminuser', 'e10adc3949ba59abbe56e057f20f883e', 'admin@gmail.com', 'John doe', '', '', '', 2, 0, '', '', '', '', '', '', '', '', '', 0, 1, 'male', '', 1, 1, 1, 1, 1, 1, 1, 0, '', 2, 0, '74b599b77f0de467105af3646569ed1e', 1, '', 0, '', '0', '', 1000.00, '', '', '', NULL, '0', NULL, NULL, 0);
-- --------------------------------------------------------

--
-- Table structure for table `user_ads`
--

CREATE TABLE IF NOT EXISTS `user_ads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ad_slug` varchar(255) NOT NULL,
  `ad_link` text,
  `userid` int(11) NOT NULL,
  `ad_type` varchar(255) DEFAULT NULL,
  `track_id` int(255) DEFAULT NULL,
  `ad_image` varchar(255) DEFAULT NULL,
  `ad_title` varchar(255) DEFAULT NULL,
  `ad_desc` text,
  `ad_placement` int(11) DEFAULT '1',
  `pay_type` varchar(255) DEFAULT NULL,
  `target` text,
  `status` int(11) DEFAULT '1',
  `admin_status` int(11) NOT NULL DEFAULT '0',
  `date_created` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_logins`
--

CREATE TABLE IF NOT EXISTS `user_logins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `hash_key` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE IF NOT EXISTS `user_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `info` text COLLATE utf8_unicode_ci NOT NULL,
  `permissions` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `videos`
--

CREATE TABLE IF NOT EXISTS `videos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `genre` int(11) NOT NULL,
  `upload_file` text COLLATE utf8_unicode_ci,
  `size` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `demo_file` text COLLATE utf8_unicode_ci,
  `video_link` text COLLATE utf8_unicode_ci,
  `video_source` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `duration` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `price` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `track_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `downloads` int(11) NOT NULL DEFAULT '0',
  `comments` int(11) NOT NULL DEFAULT '1',
  `download_hash` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `art` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `tags` text COLLATE utf8_unicode_ci,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `status` int(11) NOT NULL DEFAULT '1',
  `public` int(11) NOT NULL DEFAULT '1',
  `views` int(11) DEFAULT '0',
  `time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `video_plays`
--

CREATE TABLE IF NOT EXISTS `video_plays` (
  `userid` int(11) NOT NULL,
  `videoid` int(11) NOT NULL,
  `time` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `views`
--

CREATE TABLE IF NOT EXISTS `views` (
  `viewid` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `ip` varchar(11) DEFAULT NULL,
  `track` int(11) NOT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`viewid`)
) ENGINE=InnoDB AUTO_INCREMENT=1497 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `watch_later`
--

CREATE TABLE IF NOT EXISTS `watch_later` (
  `userid` int(11) NOT NULL,
  `videoid` int(11) NOT NULL,
  KEY `userid` (`userid`),
  KEY `videoid` (`videoid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `withdrawals`
--

CREATE TABLE IF NOT EXISTS `withdrawals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `amount` int(11) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '0',
  `time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
COMMIT;
