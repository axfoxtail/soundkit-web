<?php
$db = Database::getInstance();

try{
    $db->query("CREATE TABLE IF NOT EXISTS `mobile_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `apikey` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `device` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
} catch(Exception $e) {}

try{
    $db->query("ALTER TABLE `users` ADD `instagram` VARCHAR(255) NOT NULL AFTER `soundcloud`;");
    $db->query("ALTER TABLE `users` ADD `stripe_customer_id` VARCHAR(255) NOT NULL AFTER `payment_details`;");
    $db->query("ALTER TABLE `users` ADD `stripe_subscription_id` VARCHAR(255) NOT NULL AFTER `stripe_customer_id`;");
} catch (Exception $e){}