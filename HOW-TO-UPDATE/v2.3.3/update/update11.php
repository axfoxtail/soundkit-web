<?php
$db = Database::getInstance();

/**
 * VERSION 1.1 Database Updates
 */
try{
    $db->query("ALTER TABLE `comments` ADD `track_at` VARCHAR(255) NOT NULL AFTER `message`;");

} catch(Exception $e){}
try{
    $db->query("ALTER TABLE `tracks` ADD `track_duration` VARCHAR(100) NOT NULL AFTER `track_file`;");
} catch(Exception $e){}
try{
    $db->query("ALTER TABLE `tracks` ADD `approved` INT NOT NULL DEFAULT '1' AFTER `embed`;");
} catch(Exception $e){}
try{
    $db->query("ALTER TABLE `tracks` ADD `demo_file` VARCHAR(255) NOT NULL AFTER `track_file`, ADD `price` FLOAT(11,2) NOT NULL AFTER `demo_file`;");
} catch (Exception $e){}

try{
    $db->query("ALTER TABLE `playlist` ADD `price` FLOAT(11,2) NOT NULL AFTER `name`;");
} catch(Exception $e){}
try{
    $db->query("ALTER TABLE `users` ADD `is_seller` INT NOT NULL DEFAULT '0' AFTER `facebookid`, ADD `balance` FLOAT(11,2) NOT NULL AFTER `is_seller`, ADD `funds` FLOAT(11,2) NOT NULL AFTER `balance`;");
} catch(Exception $e){}
try{
    $db->query("CREATE TABLE IF NOT EXISTS `spotlight` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trackid` int(11) NOT NULL,
  `playlistid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `is_global` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
} catch(Exception $e){}

try {
    $db->query("CREATE TABLE IF NOT EXISTS `withdrawals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `amount` int(11) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '0',
  `time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
} catch(Exception $e){}
try {
    $db->query("ALTER TABLE `transactions` ADD `amount_credited` FLOAT(11,2) NOT NULL AFTER `amount`;");
} catch(Exception $e) {}

try{
    $db->query("ALTER TABLE `tracks` ADD `demo_wave` VARCHAR(255) NOT NULL AFTER `demo_file`, ADD `demo_wave_colored` VARCHAR(255) NOT NULL AFTER `demo_wave`;");
} catch (Exception $e) {}

try{
    $db->query("ALTER TABLE `tracks` ADD `demo_duration` INT NOT NULL AFTER `demo_file`;");
} catch (Exception $e){}

try{
    $db->query("CREATE TABLE IF NOT EXISTS `purchased` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `trackid` varchar(255) NOT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    $db->query("ALTER TABLE `users` ADD `payment_details` TEXT NOT NULL AFTER `funds`;");
} catch(Exception $e){}



$query = $db->query("SELECT * FROM tracks ");
include_once(path('app/vendor/getid3/getid3.php'));
function downloadContent($url) {
    set_time_limit(0);
    if (!is_dir(path('uploads/tmp/'))) {
        @mkdir(path('uploads/tmp/'), 0777, true);
        $file = @fopen(path("uploads/tmp/index.html"), 'x+');
        fclose($file);
    }
    $uploadPath = "uploads/tmp/";
    $file = $uploadPath . md5(uniqid().time().$url) . '.mp3';
//This is the file where we save the    information
    $fp = fopen (path($file), 'w+');
//Here is the file we are downloading, replace spaces with %20
    $ch = curl_init(str_replace(" ","%20",$url));
    curl_setopt($ch, CURLOPT_TIMEOUT, 50);
// write curl response to file
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
// get curl response
    curl_exec($ch);
    curl_close($ch);
    fclose($fp);
    return $file;
}
while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
    try{
        $trackFile = $fetch['track_file'];

        if (preg_match('#s3-#',$file)) {
            $url = model('track')->getTrackFile($fetch);

            $file = downloadContent($url);
            exit(file_get_contents($file));
            $trackFile = $file;
        }
        if (file_exists(path($trackFile))) {

            $getID3 = new getID3;
            $ThisFileInfo = $getID3->analyze(path($trackFile));
            $duration = $ThisFileInfo['playtime_seconds'];
            $wave = ($fetch['wave'] and !preg_match('#uploads/waves#', $fetch['wave'])) ? model('track')->convertUriImageToFile($fetch['wave']) : $fetch['wave'];
            $waveColored = ($fetch['wave_colored']  and !preg_match('#uploads/waves#', $fetch['wave_colored'])) ? model('track')->convertUriImageToFile($fetch['wave_colored']) : $fetch['wave_colored'];

            $db->query("UPDATE tracks SET track_duration=?,wave=?,wave_colored=? WHERE id=?", $duration, $wave, $waveColored, $fetch['id']);
        }
        if (isset($file)) {
            delete_file(path($file));
        }
    } catch(Exception $e){}
}