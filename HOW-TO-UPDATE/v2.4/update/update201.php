<?php
$db = Database::getInstance();


try {
    $db->query("ALTER TABLE `users` ADD `wallet` INT(255) NULL DEFAULT NULL AFTER `balance`;");
} catch (Exception $e){}