<?php
$db = Database::getInstance();

try{
    $db->query("ALTER TABLE `tracks` CHANGE `description` `description` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;");
   } catch(Exception $e) {}
try {
    $db->query("ALTER TABLE `tracks` ADD `featuring` TEXT NULL DEFAULT NULL AFTER `track_file`, ADD `lyrics` VARCHAR(255) NULL DEFAULT NULL AFTER `featuring`;");
}catch(Exception $e) {}

try{
    $db->query("ALTER TABLE `playlist` ADD `art` VARCHAR(255) NULL DEFAULT NULL AFTER `description`;");
} catch(Exception $e) {}
try {
    $db->query("ALTER TABLE `video_plays` ADD `time` VARCHAR(255) NULL DEFAULT NULL AFTER `videoid`;");
} catch(Exception $e) {}

try {
    $db->query("ALTER TABLE `users` ADD `currency` VARCHAR(255) NULL DEFAULT NULL AFTER `balance`;");
}  catch(Exception $e) {}
try {
    $db->query("ALTER TABLE `videos` ADD `size` VARCHAR(255) NULL DEFAULT NULL AFTER `upload_file`;");
} catch (Exception $e){}