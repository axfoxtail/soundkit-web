<?php
$db = Database::getInstance();

try{
    $db->query("ALTER TABLE `users` ADD `two_factor_auth` VARCHAR(255) NULL DEFAULT NULL AFTER `verify_details`;");
    $db->query("ALTER TABLE `users` CHANGE `stripe_customer_id` `stripe_customer_id` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `stripe_subscription_id` `stripe_subscription_id` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `verify_details` `verify_details` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;");
    $db->query("ALTER TABLE `users` CHANGE `instagram` `instagram` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;");
} catch(Exception $e) {}
