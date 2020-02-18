<?php
$db = Database::getInstance();


try {
    $db->query("ALTER TABLE `users` CHANGE `wallet` `wallet` VARCHAR(255) NULL DEFAULT NULL;");
    $db->query("ALTER TABLE `users` ADD `role_id` INT NOT NULL DEFAULT '0' AFTER `role`;");
    $db->query("CREATE TABLE IF NOT EXISTS `user_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `info` text COLLATE utf8_unicode_ci NOT NULL,
  `permissions` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
} catch (Exception $e){}

try {
    $db->query("ALTER TABLE `user_roles`
  DROP `id`;");
    $db->query("ALTER TABLE `user_roles` ADD `id` INT NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (`id`);");
} catch (Exception $e){}