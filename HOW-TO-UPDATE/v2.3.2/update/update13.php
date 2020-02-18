<?php
$db = Database::getInstance();
try{
    $db->query("CREATE TABLE IF NOT EXISTS `tmp_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `path` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
} catch (Exception $e){}
try{
    $db->query("ALTER TABLE `purchased` ADD `videoid` INT NOT NULL DEFAULT '0' AFTER `trackid`;");
} catch (Exception $e){}

try{
    $db->query("ALTER TABLE `users` CHANGE `balance` `balance` VARCHAR(255) NULL DEFAULT NULL;");
} catch (Exception $e){}
try{
    $db->query("ALTER TABLE `users` ADD `verify_details` TEXT NULL DEFAULT NULL AFTER `payment_details`;");
} catch (Exception $e){}
try{
    $db->query("ALTER TABLE `info_pages` CHANGE `content` `content` LONGTEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;");
} catch (Exception $e){}
try{
    $db->query("CREATE TABLE IF NOT EXISTS `videos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `genre` int(11) NOT NULL,
  `upload_file` text COLLATE utf8_unicode_ci,
  `demo_file` text COLLATE utf8_unicode_ci,
  `video_link` text COLLATE utf8_unicode_ci,
  `video_source` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `duration` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `price` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `track_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");

} catch (Exception $e){}
try{
    $db->query("CREATE TABLE IF NOT EXISTS `video_plays` (
  `userid` int(11) NOT NULL,
  `videoid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
} catch (Exception $e){}
try{
    $db->query("CREATE TABLE IF NOT EXISTS `watch_later` (
  `userid` int(11) NOT NULL,
  `videoid` int(11) NOT NULL,
  KEY `userid` (`userid`),
  KEY `videoid` (`videoid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
} catch (Exception $e){}


try{
    $db->query("ALTER TABLE `tracks` ADD `slug` VARCHAR(255) NULL DEFAULT NULL AFTER `description`;");
} catch (Exception $e) {}
