<?php
$db = Database::getInstance();

try{
    $db->query("ALTER TABLE `tracks` ADD `download_hash` VARCHAR(255) NULL DEFAULT NULL AFTER `approved`;");
} catch (Exception $e) {}

$query = $db->query("SELECT * FROM tracks ");
while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
    try{
        $trackFile = $fetch['track_file'];
        $db->query("UPDATE tracks SET download_hash=? WHERE id=?", generateHash($fetch['id']), $fetch['id']);
    } catch(Exception $e){}
}