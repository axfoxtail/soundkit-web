<?php
function getFullUrl($queryStr = false)
{
    $request = $_SERVER;
    $host = (isset($request['HTTP_HOST'])) ? $request['HTTP_HOST'] : $request['SERVER_NAME'];
    $isSecure = (isset($request['HTTPS']) and $request['HTTPS'] == "on") ? true : false;
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $queryString = (isset($_SERVER['QUERY_STRING']) and $_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : null;
    $scheme = (config('https')) ? "https://" : "http://";
    $fullUrl = $scheme . $host . $uri;
    return $fullUrl = ($queryStr) ? $fullUrl . $queryString : $fullUrl;
}

function getQueryString() {
    return (isset($_SERVER['QUERY_STRING']) and $_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : null;
}

function isSecure()
{
    return $isSecure = (isset($_SERVER['HTTPS']) and $_SERVER['HTTPS'] == "on") ? true : false;
}

function getScheme()
{
    return (config('https')) ? 'https' : 'http';
}

function getHost()
{
    $request = $_SERVER;
    $host = (isset($request['HTTP_HOST'])) ? $request['HTTP_HOST'] : $request['SERVER_NAME'];

    //remove unwanted characters
    $host = strtolower(preg_replace('/:\d+$/', '', trim($host)));
    //prevent Dos attack
    if ($host && '' !== preg_replace('/(?:^\[)?[a-zA-Z0-9-:\]_]+\.?/', '', $host)) {
        die();
    }

    return $host;
}

function server($name, $default = null)
{
    if (isset($_SERVER[$name])) return $_SERVER[$name];
    return $default;
}

function getRoot()
{
    $base = getBase();

    return getScheme() . '://' . getHost() . $base;
}

function getBase()
{
    $filename = basename(server('SCRIPT_FILENAME'));
    if (basename(server('SCRIPT_NAME')) == $filename) {
        $baseUrl = server('SCRIPT_NAME');
    } elseif (basename(server('PHP_SELF')) == $filename) {
        $baseUrl = server('PHP_SELF');
    } elseif (basename(server('ORIG_SCRIPT_NAME')) == $filename) {
        $baseUrl = server('ORIG_SCRIPT_NAME');
    } else {
        $baseUrl = server('SCRIPT_NAME');
    }

    $baseUrl = str_replace('index.php', '', $baseUrl);

    return $baseUrl;
}

/**
 * Function to get the request method
 * @return string
 */
function get_request_method()
{
    return strtoupper($_SERVER['REQUEST_METHOD']);
}

/**
 * Method to get path
 */
function path($path = "")
{
    if(preg_match('#http#', $path)) return $path;
    $base = APP_BASE_PATH ;
    return $base . $path;
}

function get_ip()
{
    //Just get the headers if we can or else use the SERVER global
    if (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
    } else {
        $headers = $_SERVER;
    }

    //Get the forwarded IP if it exists
    if (array_key_exists('X-Forwarded-For', $headers) && filter_var($headers['X-Forwarded-For'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $the_ip = $headers['X-Forwarded-For'];
    } elseif (array_key_exists('HTTP_X_FORWARDED_FOR', $headers) && filter_var($headers['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
    ) {
        $the_ip = $headers['HTTP_X_FORWARDED_FOR'];
    } else {
        $the_ip = filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    }

    return $the_ip;
}

function convertToAscii($str, $replace = array(), $delimiter = '-', $charset = 'ISO-8859-1')
{


    $str = str_replace(
        array(chr(145), chr(146), chr(147), chr(148), chr(150), chr(151), chr(133)),
        array("'", "'", '"', '"', '-', '--', '...'),
        $str); // by mordomiamil
    try {
        $str = iconv($charset, 'UTF-8', $str); // by lelebart
        if (!empty($replace)) {
            $str = str_replace((array)$replace, ' ', $str);
        }
        $clean = $str;
    } catch (Exception $e) {
    }

    try {
        $clean = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
    } catch (Exception $e) {

    }

    $str = preg_replace('/[^\x{0600}-\x{06FF}A-Za-z !@#$%^&*()]/u', '', $str);
    $clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
    $clean = strtolower(trim($clean, '-'));
    $clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);
    $clean = strtolower(trim($clean, '-'));
    return $clean;
}

function str_limit($text, $length, $ad = '...')
{
    /**
     * @var $ending
     * @var $exact
     * @var $html
     */
    $ad = is_string($ad) ? array('ending' => $ad) : $ad;
    $default = array('ending' => '...', 'exact' => true, 'html' => false);
    $options = array_merge($default, $ad);
    extract($options);

    if ($html) {
        if (mb_strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
            return $text;
        }
        $totalLength = mb_strlen(strip_tags($ending));
        $openTags = array();
        $truncate = '';

        preg_match_all('/(<\/?([\w+]+)[^>]*>)?([^<>]*)/', $text, $tags, PREG_SET_ORDER);
        foreach ($tags as $tag) {
            if (!preg_match('/img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param/s', $tag[2])) {
                if (preg_match('/<[\w]+[^>]*>/s', $tag[0])) {
                    array_unshift($openTags, $tag[2]);
                } else if (preg_match('/<\/([\w]+)[^>]*>/s', $tag[0], $closeTag)) {
                    $pos = array_search($closeTag[1], $openTags);
                    if ($pos !== false) {
                        array_splice($openTags, $pos, 1);
                    }
                }
            }
            $truncate .= $tag[1];

            $contentLength = mb_strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', ' ', $tag[3]));
            if ($contentLength + $totalLength > $length) {
                $left = $length - $totalLength;
                $entitiesLength = 0;
                if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', $tag[3], $entities, PREG_OFFSET_CAPTURE)) {
                    foreach ($entities[0] as $entity) {
                        if ($entity[1] + 1 - $entitiesLength <= $left) {
                            $left--;
                            $entitiesLength += mb_strlen($entity[0]);
                        } else {
                            break;
                        }
                    }
                }

                $truncate .= mb_substr($tag[3], 0, $left + $entitiesLength);
                break;
            } else {
                $truncate .= $tag[3];
                $totalLength += $contentLength;
            }
            if ($totalLength >= $length) {
                break;
            }
        }
    } else {
        if (mb_strlen($text) <= $length) {
            return $text;
        } else {
            $truncate = mb_substr($text, 0, $length - mb_strlen($ending));
        }
    }
    if (!$exact) {
        $spacepos = mb_strrpos($truncate, ' ');
        if (isset($spacepos)) {
            if ($html) {
                $bits = mb_substr($truncate, $spacepos);
                preg_match_all('/<\/([a-z]+)>/', $bits, $droppedTags, PREG_SET_ORDER);
                if (!empty($droppedTags)) {
                    foreach ($droppedTags as $closingTag) {
                        if (!in_array($closingTag[1], $openTags)) {
                            array_unshift($openTags, $closingTag[1]);
                        }
                    }
                }
            }
            $truncate = mb_substr($truncate, 0, $spacepos);
        }
    }
    $truncate .= $ending;

    if ($html) {
        foreach ($openTags as $tag) {
            $truncate .= '</' . $tag . '>';
        }
    }

    return $truncate;
}

function format_bytes($bytes, $force_unit = NULL, $format = NULL, $si = TRUE)
{
// Format string
    $format = ($format === NULL) ? '%01.2f %s' : (string)$format;

    // IEC prefixes (binary)
    if ($si == FALSE OR strpos($force_unit, 'i') !== FALSE) {
        $units = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB');
        $mod = 1024;
    } // SI prefixes (decimal)
    else {
        $units = array('B', 'kB', 'MB', 'GB', 'TB', 'PB');
        $mod = 1000;
    }

    // Determine unit to use
    if (($power = array_search((string)$force_unit, $units)) === FALSE) {
        $power = ($bytes > 0) ? floor(log($bytes, $mod)) : 0;
    }

    return sprintf($format, $bytes / pow($mod, $power), $units[$power]);
}
function autoLinkUrls($text, $popup = true)
{
    $target = false;
    $str = $text;
    if ($target) {
        $target = ' target="' . $target . '"';
    } else {
        $target = '';
    }
    // find and replace link
    $str = preg_replace('@((https?://)?([-\w]+\.[-\w\.]+)+\w(:\d+)?(/([-\w/_\.]*(\?\S+)?)?)*)@', "<a onclick=\"return window.open('http://$1')\" nofollow='nofollow' href='javascript::void(0)' {$target}>$1</a>", $str);
    // add "http://" if not set
    $str = preg_replace('/<a\s[^>]*href\s*=\s*"((?!https?:\/\/)[^"]*)"[^>]*>/i', "<a onclick=\"return window.open('$1')\" nofollow='nofollow' href='javascript::void(0)' {$target}>$1</a>", $str);
    //return $str;
    $regexB = '(?:[^-\\/"\':!=a-z0-9_@＠]|^|\\:)';
    $regexUrl = '(?:[^\\p{P}\\p{Lo}\\s][\\.-](?=[^\\p{P}\\p{Lo}\\s])|[^\\p{P}\\p{Lo}\\s])+\\.[a-z]{2,}(?::[0-9]+)?';
    $regexUrlChars = '(?:(?:\\([a-z0-9!\\*\';:=\\+\\$\\/%#\\[\\]\\-_,~]+\\))|@[a-z0-9!\\*\';:=\\+\\$\\/%#\\[\\]\\-_,~]+\\/|[\\.\\,]?(?:[a-z0-9!\\*\';:=\\+\\$\\/%#\\[\\]\\-_~]|,(?!\s)))';
    $regexURLPath = '[a-z0-9=#\\/]';
    $regexQuery = '[a-z0-9!\\*\'\\(\\);:&=\\+\\$\\/%#\\[\\]\\-_\\.,~]';
    $regexQueryEnd = '[a-z0-9_&=#\\/]';

    $regex = '/(?:' # $1 Complete match (preg_match already matches everything.)
        . '(' . $regexB . ')' # $2 Preceding character
        . '(' # $3 Complete URL
        . '((?:https?:\\/\\/|www\\.)?)' # $4 Protocol (or www)
        . '(' . $regexUrl . ')' # $5 Domain(s) (and port)
        . '(\\/' . $regexUrlChars . '*' # $6 URL Path
        . $regexURLPath . '?)?'
        . '(\\?' . $regexQuery . '*' # $7 Query String
        . $regexQueryEnd . ')?'
        . ')'
        . ')/iux';
//    return $text;
    return preg_replace_callback($regex, function ($matches) {

        list($all, $before, $url, $protocol, $domain, $path, $query) = array_pad($matches, 7, '');
        $href = ((!$protocol || strtolower($protocol) === 'www.') ? 'http://' . $url : $url);
        //if (!$protocol && !preg_match('/\\.(?:com|net|org|gov|edu)$/iu' , $domain)) return $all;
        return $before . "<a onclick=\"return window.open('" . $href . "')\" nofollow='nofollow' href='javascript:void(0)' >" . $url . "</a>";
    }, $text);
}

function curl_get_content($url, $javascript_loop = 0, $timeout = 100)
{
    $url = str_replace("&amp;", "&", urldecode(trim($url)));

    $cookie = @tempnam("/tmp", "CURLCOOKIE");
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1");
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
    //curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
    curl_setopt($ch, CURLOPT_ENCODING, "");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_AUTOREFERER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); # required for https urls
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
    $content = curl_exec($ch);
    $response = curl_getinfo($ch);
    curl_close($ch);

    return $content;
}
function cu($url) {
    return curl_get_content($url);
}

function hex2rgba($color, $opacity = false) {

    $default = 'rgb(0,0,0)';

    //Return default if no color provided
    if(empty($color))
        return $default;

    //Sanitize $color if "#" is provided
    if ($color[0] == '#' ) {
        $color = substr( $color, 1 );
    }

    //Check if color has 6 or 3 characters and get values
    if (strlen($color) == 6) {
        $hex = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] );
    } elseif ( strlen( $color ) == 3 ) {
        $hex = array( $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] );
    } else {
        return $default;
    }

    //Convert hexadec to rgb
    $rgb =  array_map('hexdec', $hex);

    //Check if opacity is set(rgba or rgb)
    if($opacity){
        if(abs($opacity) > 1)
            $opacity = 1.0;
        $output = 'rgba('.implode(",",$rgb).','.$opacity.')';
    } else {
        $output = 'rgb('.implode(",",$rgb).')';
    }

    //Return rgb(a) color string
    return $output;
}

function adjustBrightness($hex, $steps) {
    // Steps should be between -255 and 255. Negative = darker, positive = lighter
    $steps = max(-255, min(255, $steps));

    // Normalize into a six character long hex string
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $hex = str_repeat(substr($hex,0,1), 2).str_repeat(substr($hex,1,1), 2).str_repeat(substr($hex,2,1), 2);
    }

    // Split into three parts: R, G and B
    $color_parts = str_split($hex, 2);
    $return = '#';

    foreach ($color_parts as $color) {
        $color   = hexdec($color); // Convert to decimal
        $color   = max(0,min(255,$color + $steps)); // Adjust color
        $return .= str_pad(dechex($color), 2, '0', STR_PAD_LEFT); // Make two char hex code
    }

    return $return;
}

function hexToRgb($hex, $alpha = false) {
    $hex      = str_replace('#', '', $hex);
    $length   = strlen($hex);
    $rgb = array();
    $rgb[] = hexdec($length == 6 ? substr($hex, 0, 2) : ($length == 3 ? str_repeat(substr($hex, 0, 1), 2) : 0));
    $rgb[] = hexdec($length == 6 ? substr($hex, 2, 2) : ($length == 3 ? str_repeat(substr($hex, 1, 1), 2) : 0));
    $rgb[] = hexdec($length == 6 ? substr($hex, 4, 2) : ($length == 3 ? str_repeat(substr($hex, 2, 1), 2) : 0));
    if ( $alpha ) {
        $rgb[] = $alpha;
    }
    return $rgb;
}
function ReplaceImageColour($img, $destImg, $r1, $g1, $b1, $r2, $g2, $b2)
{
    $img = imagecreatefrompng($img);

    if(!imageistruecolor($img))
        imagepalettetotruecolor($img);
    $col1 = (($r1 & 0xFF) << 16) + (($g1 & 0xFF) << 8) + ($b1 & 0xFF);
    $col2 = (($r2 & 0xFF) << 16) + (($g2 & 0xFF) << 8) + ($b2 & 0xFF);

    $width = imagesx($img);
    $height = imagesy($img);
    for($x=0; $x < $width; $x++) {
        for ($y = 0; $y < $height; $y++) {
            $colrgb = imagecolorat($img, $x, $y);
            if ($col1 !== $colrgb)
                continue;
            imagesetpixel($img, $x, $y, $col2);
        }
    }

    imagealphablending($img, FALSE);
    imagesavealpha($img, TRUE);
    //imagecopyresampled($img, $source, 0, 0, 0, 0, $target_width, $target_height, $asset->a_image_width, $asset->a_image_height);
    // $r = @imagepng($img,'dave.png');
    return imagepng($img, $destImg);
}

function colorizeKeepAplhaChannnel( $inputFilePathIn, $targetRedIn, $targetGreenIn, $targetBlueIn, $outputFilePathIn ) {
    $im_src =  imagecreatefrompng( $inputFilePathIn );
    $im_dst = imagecreatefrompng( $inputFilePathIn );
    $width = imagesx($im_src);
    $height = imagesy($im_src);

    // Note this: FILL IMAGE WITH TRANSPARENT BG
    imagefill($im_dst, 0, 0, IMG_COLOR_TRANSPARENT);
    imagesavealpha($im_dst,true);
    imagealphablending($im_dst, true);

    $flagOK = 1;
    for( $x=0; $x<$width; $x++ ) {
        for( $y=0; $y<$height; $y++ ) {
            $rgb = imagecolorat( $im_src, $x, $y );
            $colorOldRGB = imagecolorsforindex($im_src, $rgb);
            $alpha = $colorOldRGB["alpha"];
            $colorNew = imagecolorallocatealpha($im_src, $targetRedIn, $targetGreenIn, $targetBlueIn, $alpha);

            $flagFoundColor = true;
            // uncomment next 3 lines to substitute only 1 color (in this case, BLACK/greys)
            /*
                        $colorOld = imagecolorallocatealpha($im_src, $colorOldRGB["red"], $colorOldRGB["green"], $colorOldRGB["blue"], 0); // original color WITHOUT alpha channel
                        $color2Change = imagecolorallocatealpha($im_src, 0, 0, 0, 0); // opaque BLACK - change to desired color
                        $flagFoundColor = ($color2Change == $colorOld);
            */

            if ( false === $colorNew ) {
                //echo( "FALSE COLOR:$colorNew alpha:$alpha<br/>" );
                $flagOK = 0;
            } else if ($flagFoundColor) {
                imagesetpixel( $im_dst, $x, $y, $colorNew );
                //echo "x:$x y:$y col=$colorNew alpha:$alpha<br/>";
            }
        }
    }
    $flagOK2 = imagepng($im_dst, $outputFilePathIn);
    imagedestroy($im_dst);
    imagedestroy($im_src);
}
function url_img($path, $size = null) {
    $path = ($size) ? str_replace('%w', $size, $path) : $path;

    if(stripos('%d', $path) != -1) {
        if($size < 200) {
            $size = 200;
        } elseif($size < 700) {
            $size = 600;
        } else {
            $size = 960;
        }
        $path = ($size) ? str_replace('%d', $size, $path) : $path;
    }
    return assetUrl($path);

}
if(!function_exists('sanitizeText')) {
    function sanitizeText($string, $limit = false, $output = false) {
        if(!is_string($string)) return $string;
        $string = html_purifier_purify($string);
        $string = trim($string);
        //$string = htmlspecialchars($string, ENT_QUOTES);

        $string = str_replace('&amp;#', '&#', $string);
        $string = str_replace('&amp;', '&', $string);
        if($limit) {
            $string = substr($string, 0, $limit);
        }
        return $string;
    }

}

function format_output_text($content) {
    $content = str_replace("\r\n", '<br />', $content);
    $content = str_replace("\n", '<br />', $content);
    $content = str_replace("\r", '<br />', $content);
    $content = stripslashes($content);
    //$content = autoLinkUrls($content);
    $content = html_entity_decode($content);
    $content = html_purifier_purify($content);
    //replace bad words
    $badWords = config('ban_filters_words', '');
    if($badWords) {
        $badWords = explode(',', $badWords);
        foreach($badWords as $word) {
            $content = str_replace($word, '***', $content);
        }
    }
    return $content;
}



function html_purifier_purify($dirty_html, $params = null, $input = false) {
    require_once path('app/vendor/htmlpurifier/HTMLPurifier.auto.php');
    require_once path('app/vendor/htmlpurifier/HTMLPurifier.config-extend-Iframe.php');
    $config = HTMLPurifier_Config::createDefault();
    $cache_serializer_path = path('uploads/tmp/htmlpurifier');
    if(!is_dir($cache_serializer_path)) {
        @mkdir($cache_serializer_path, 0777, true);
    }
    $config->set('Cache.SerializerPath', $cache_serializer_path);
    if(isset($params)) {
        foreach($params as $key => $value) {
            $config->set($key, $value);
        }
    }
    $purifier = new HTMLPurifier($config);
    $clean_html = $purifier->purify($dirty_html);
    if($input) {
        $clean_html = stripslashes($clean_html);
    }
    return $clean_html;
}

function session_put($name, $value = "") {
    $_SESSION[$name] = $value;
    return true;
}
function session_get($name, $default = false) {
    if(!isset($_SESSION[$name])) return $default;
    return $_SESSION[$name];
}
function session_forget($name) {
    if(isset($_SESSION[$name])) unset($_SESSION[$name]);
    return true;
}

function url($url = '', $param = array(), $direct = false) {
    return Request::instance()->url($url, $param, $direct);
}

function toAscii($str, $replace=array(), $delimiter='-', $charset='ISO-8859-1') {


    try{
        $str = str_replace(
            array(chr(145), chr(146), chr(147), chr(148), chr(150), chr(151), chr(133)),
            array("'", "'", '"', '"', '-', '--', '...'),
            $str); // by mordomiamil
    } catch(Exception $e){}

    try{
        if (function_exists('iconv')) $str = @iconv($charset, 'UTF-8', $str); // by lelebart
        if( !empty($replace) ) {
            $str = str_replace((array)$replace, ' ', $str);
        }
        $clean = $str;
    } catch(Exception $e) {}

    try {
        if (function_exists('iconv')) $clean = @iconv('UTF-8', 'ASCII//TRANSLIT', $str);
    } catch( Exception $e) {

    }



    $str = preg_replace('/[^\x{0600}-\x{06FF}A-Za-z !@#$%^&*()]/u','', $str);
    $clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
    $clean = strtolower(trim($clean, '-'));
    $clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);
    $clean = strtolower(trim($clean, '-'));
    return $clean;
}


function assetUrl($url = '') {
    return Request::instance()->url($url, array(), true);
}

function get_file_extension($path) {
    return strtolower(pathinfo($path, PATHINFO_EXTENSION));
}
function is_gif($path) {
    return (get_file_extension($path) == 'gif');
}

function hash_check($content, $hash)
{
    return (md5($content) == $hash);
}

function hash_value($content) {
    return md5($content);
}

function config($key, $default = null)
{
    return Request::instance()->config($key, $default);
}

function l($name, $replace = array(), $default = null)
{
    return Translator::instance()->translate($name, $replace, $default);
}

function _l($name, $replace = array(), $default = null)
{
    echo l($name, $replace, $default);
}
function is_ajax()
{
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == "xmlhttprequest") {
        return true;
    }
    return false;
}
function getCountries() {
    $countries = array("Afghanistan", "Albania", "Algeria", "American Samoa", "Andorra", "Angola", "Anguilla", "Antarctica", "Antigua and Barbuda", "Argentina", "Armenia", "Aruba", "Australia", "Austria", "Azerbaijan", "Bahamas", "Bahrain", "Bangladesh", "Barbados", "Belarus", "Belgium", "Belize", "Benin", "Bermuda", "Bhutan", "Bolivia", "Bosnia and Herzegowina", "Botswana", "Bouvet Island", "Brazil", "British Indian Ocean Territory", "Brunei Darussalam", "Bulgaria", "Burkina Faso", "Burundi", "Cambodia", "Cameroon", "Canada", "Cape Verde", "Cayman Islands", "Central African Republic", "Chad", "Chile", "China", "Christmas Island", "Cocos (Keeling) Islands", "Colombia", "Comoros", "Congo", "Congo, the Democratic Republic of the", "Cook Islands", "Costa Rica", "Cote d'Ivoire", "Croatia (Hrvatska)", "Cuba", "Cyprus", "Czech Republic", "Denmark", "Djibouti", "Dominica", "Dominican Republic", "East Timor", "Ecuador", "Egypt", "El Salvador", "Equatorial Guinea", "Eritrea", "Estonia", "Ethiopia", "Falkland Islands (Malvinas)", "Faroe Islands", "Fiji", "Finland", "France", "France Metropolitan", "French Guiana", "French Polynesia", "French Southern Territories", "Gabon", "Gambia", "Georgia", "Germany", "Ghana", "Gibraltar", "Greece", "Greenland", "Grenada", "Guadeloupe", "Guam", "Guatemala", "Guinea", "Guinea-Bissau", "Guyana", "Haiti", "Heard and Mc Donald Islands", "Holy See (Vatican City State)", "Honduras", "Hong Kong", "Hungary", "Iceland", "India", "Indonesia", "Iran (Islamic Republic of)", "Iraq", "Ireland", "Israel", "Italy", "Jamaica", "Japan", "Jordan", "Kazakhstan", "Kenya", "Kiribati", "Korea, Democratic People's Republic of", "Korea, Republic of", "Kuwait", "Kyrgyzstan", "Lao, People's Democratic Republic", "Latvia", "Lebanon", "Lesotho", "Liberia", "Libyan Arab Jamahiriya", "Liechtenstein", "Lithuania", "Luxembourg", "Macau", "Macedonia, The Former Yugoslav Republic of", "Madagascar", "Malawi", "Malaysia", "Maldives", "Mali", "Malta", "Marshall Islands", "Martinique", "Mauritania", "Mauritius", "Mayotte", "Mexico", "Micronesia, Federated States of", "Moldova, Republic of", "Monaco", "Mongolia", "Montserrat", "Morocco", "Mozambique", "Myanmar", "Namibia", "Nauru", "Nepal", "Netherlands", "Netherlands Antilles", "New Caledonia", "New Zealand", "Nicaragua", "Niger", "Nigeria", "Niue", "Norfolk Island", "Northern Mariana Islands", "Norway", "Oman", "Pakistan", "Palau", "Panama", "Papua New Guinea", "Paraguay", "Peru", "Philippines", "Pitcairn", "Poland", "Portugal", "Puerto Rico", "Qatar", "Reunion", "Romania", "Russian Federation", "Rwanda", "Saint Kitts and Nevis", "Saint Lucia", "Saint Vincent and the Grenadines", "Samoa", "San Marino", "Sao Tome and Principe", "Saudi Arabia", "Senegal", "Seychelles", "Sierra Leone", "Singapore", "Slovakia (Slovak Republic)", "Slovenia", "Solomon Islands", "Somalia", "South Africa", "South Georgia and the South Sandwich Islands", "Spain", "Sri Lanka", "St. Helena", "St. Pierre and Miquelon", "Sudan", "Suriname", "Svalbard and Jan Mayen Islands", "Swaziland", "Sweden", "Switzerland", "Syrian Arab Republic", "Taiwan, Province of China", "Tajikistan", "Tanzania, United Republic of", "Thailand", "Togo", "Tokelau", "Tonga", "Trinidad and Tobago", "Tunisia", "Turkey", "Turkmenistan", "Turks and Caicos Islands", "Tuvalu", "Uganda", "Ukraine", "United Arab Emirates", "United Kingdom", "United States", "United States Minor Outlying Islands", "Uruguay", "Uzbekistan", "Vanuatu", "Venezuela", "Vietnam", "Virgin Islands (British)", "Virgin Islands (U.S.)", "Wallis and Futuna Islands", "Western Sahara", "Yemen", "Yugoslavia", "Zambia", "Zimbabwe");
    return $countries;
}

function getMonths() {
    return array(
        'january',
        'february',
        'march',
        'april',
        'may',
        'june',
        'july',
        'august',
        'september',
        'october',
        'november',
        'december'
    );
}
if(!function_exists('perfectSerialize')) {
    function perfectSerialize($string) {
        return base64_encode(serialize($string));
    }
}

if(!function_exists('perfectUnserialize')) {
    function perfectUnserialize($string) {

        if(base64_decode($string, true) == true) {

            return @unserialize(base64_decode($string));
        } else {
            return @unserialize($string);
        }
    }
}

function getLanguageName($lang) {
    $language = array(
        'en' => 'English' ,
        'aa' => 'Afar' ,
        'ab' => 'Abkhazian' ,
        'af' => 'Afrikaans' ,
        'am' => 'Amharic' ,
        'ar' => 'Arabic' ,
        'as' => 'Assamese' ,
        'ay' => 'Aymara' ,
        'az' => 'Azerbaijani' ,
        'ba' => 'Bashkir' ,
        'be' => 'Byelorussian' ,
        'bg' => 'Bulgarian' ,
        'bh' => 'Bihari' ,
        'bi' => 'Bislama' ,
        'bn' => 'Bengali/Bangla' ,
        'bo' => 'Tibetan' ,
        'br' => 'Breton' ,
        'ca' => 'Catalan' ,
        'co' => 'Corsican' ,
        'cs' => 'Czech' ,
        'cy' => 'Welsh' ,
        'da' => 'Danish' ,
        'de' => 'German' ,
        'dz' => 'Bhutani' ,
        'el' => 'Greek' ,
        'eo' => 'Esperanto' ,
        'es' => 'Spanish' ,
        'et' => 'Estonian' ,
        'eu' => 'Basque' ,
        'fa' => 'Persian' ,
        'fi' => 'Finnish' ,
        'fj' => 'Fiji' ,
        'fo' => 'Faeroese' ,
        'fr' => 'French' ,
        'fy' => 'Frisian' ,
        'ga' => 'Irish' ,
        'gd' => 'Scots/Gaelic' ,
        'gl' => 'Galician' ,
        'gn' => 'Guarani' ,
        'gu' => 'Gujarati' ,
        'ha' => 'Hausa' ,
        'hi' => 'Hindi' ,
        'hr' => 'Croatian' ,
        'hu' => 'Hungarian' ,
        'hy' => 'Armenian' ,
        'ia' => 'Interlingua' ,
        'ie' => 'Interlingue' ,
        'ik' => 'Inupiak' ,
        'in' => 'Indonesian' ,
        'is' => 'Icelandic' ,
        'it' => 'Italian' ,
        'iw' => 'Hebrew' ,
        'ja' => 'Japanese' ,
        'ji' => 'Yiddish' ,
        'jw' => 'Javanese' ,
        'ka' => 'Georgian' ,
        'kk' => 'Kazakh' ,
        'kl' => 'Greenlandic' ,
        'km' => 'Cambodian' ,
        'kn' => 'Kannada' ,
        'ko' => 'Korean' ,
        'ks' => 'Kashmiri' ,
        'ku' => 'Kurdish' ,
        'ky' => 'Kirghiz' ,
        'la' => 'Latin' ,
        'ln' => 'Lingala' ,
        'lo' => 'Laothian' ,
        'lt' => 'Lithuanian' ,
        'lv' => 'Latvian/Lettish' ,
        'mg' => 'Malagasy' ,
        'mi' => 'Maori' ,
        'mk' => 'Macedonian' ,
        'ml' => 'Malayalam' ,
        'mn' => 'Mongolian' ,
        'mo' => 'Moldavian' ,
        'mr' => 'Marathi' ,
        'ms' => 'Malay' ,
        'mt' => 'Maltese' ,
        'my' => 'Burmese' ,
        'na' => 'Nauru' ,
        'ne' => 'Nepali' ,
        'nl' => 'Dutch' ,
        'no' => 'Norwegian' ,
        'oc' => 'Occitan' ,
        'om' => '(Afan)/Oromoor/Oriya' ,
        'pa' => 'Punjabi' ,
        'pl' => 'Polish' ,
        'ps' => 'Pashto/Pushto' ,
        'pt' => 'Portuguese' ,
        'qu' => 'Quechua' ,
        'rm' => 'Rhaeto-Romance' ,
        'rn' => 'Kirundi' ,
        'ro' => 'Romanian' ,
        'ru' => 'Russian' ,
        'rw' => 'Kinyarwanda' ,
        'sa' => 'Sanskrit' ,
        'sd' => 'Sindhi' ,
        'sg' => 'Sangro' ,
        'sh' => 'Serbo-Croatian' ,
        'si' => 'Singhalese' ,
        'sk' => 'Slovak' ,
        'sl' => 'Slovenian' ,
        'sm' => 'Samoan' ,
        'sn' => 'Shona' ,
        'so' => 'Somali' ,
        'sq' => 'Albanian' ,
        'sr' => 'Serbian' ,
        'ss' => 'Siswati' ,
        'st' => 'Sesotho' ,
        'su' => 'Sundanese' ,
        'sv' => 'Swedish' ,
        'sw' => 'Swahili' ,
        'ta' => 'Tamil' ,
        'te' => 'Tegulu' ,
        'tg' => 'Tajik' ,
        'th' => 'Thai' ,
        'ti' => 'Tigrinya' ,
        'tk' => 'Turkmen' ,
        'tl' => 'Tagalog' ,
        'tn' => 'Setswana' ,
        'to' => 'Tonga' ,
        'tr' => 'Turkish' ,
        'ts' => 'Tsonga' ,
        'tt' => 'Tatar' ,
        'tw' => 'Twi' ,
        'uk' => 'Ukrainian' ,
        'ur' => 'Urdu' ,
        'uz' => 'Uzbek' ,
        'vi' => 'Vietnamese' ,
        'vo' => 'Volapuk' ,
        'wo' => 'Wolof' ,
        'xh' => 'Xhosa' ,
        'yo' => 'Yoruba' ,
        'zh' => 'Chinese' ,
        'zu' => 'Zulu' ,
    );

    return isset($language[$lang]) ? $language[$lang] : '';
}

function get_time_relative_format($time) {
    $timeStr = 'this week';
    switch ($time) {
        case 'this-month':
            $timeStr = 'first day of this month';
            break;
        case 'last-month':
            $timeStr = 'first day of last month';
            break;
        case 'last-week':
            $timeStr = 'last week';
            break;
        case 'this-year':
            $timeStr = 'first day of january this year';
            break;
    }
    return strtotime($timeStr);
}

function delete_file($path, $check_images = true) {
    if($check_images && preg_match('/%w/', $path)) {
        $image_sizes = array(75, 200, 600, 920);
        foreach($image_sizes as $size) {
            delete_file(str_replace('%w', $size, $path), false);
        }
    }
    $basePath = path();
    $basePath2 = $basePath.'/';

    if($path == $basePath or $path == $basePath2 or !trim($path)) return false;

    if(is_dir($path) === true) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::CHILD_FIRST);

        foreach($files as $file) {
            if(in_array($file->getBasename(), array('.', '..')) !== true) {
                if($file->isDir() === true) {
                    @rmdir($file->getPathName());
                } else if(($file->isFile() === true) || ($file->isLink() === true)) {
                    unlink($file->getPathname());
                }
            }
        }

        return @rmdir($path);
    } else if((is_file($path) === true) || (is_link($path) === true)) {
        return unlink($path);
    }

    return false;
}

function getController() {
    return Request::instance()->controller;
}
function view($view, $param = array()) {
    return getController()->view($view, $param);
}
function model($model) {
    return getController()->model($model);
}

function getConversionValue($currency) {
    $file =  path('uploads/conversion/conversions.txt');
    if (file_exists($file)) {
        $quotes = json_decode(file_get_contents($file), true);
        return (isset($quotes[$currency])) ? $quotes[$currency] : 1;
    }
    return 1;
}

function convertBackToBase($total) {
    if (!config('enable-multi-currency', false)) return $total;
    $userCurrency = model('user')->getUserCurrency();
    $sourceCurrency = config('currency-converter-source', 'USD');
    if ($userCurrency == $sourceCurrency) return $total;

    $conversionValue = getConversionValue($userCurrency);
    return $total / $conversionValue;
}
function formatMoneyTotal($total) {
    if (!config('enable-multi-currency', false)) return $total;
    $userCurrency = model('user')->getUserCurrency();
    $sourceCurrency = config('currency-converter-source', 'USD');
    if ($userCurrency == $sourceCurrency) return $total;

    $conversionValue = getConversionValue($userCurrency);
    return $total * $conversionValue;
}
function userCurrencySupportStripe() {
    $supported = explode(',', config('stripe-supported-currency','AFN,ALL,DZD,AOA,ARS,AMD,AWG,AUD,AZN,BSD,BDT,BBD,BZD,BMD,BOB,BAM,BWP,BRL,GBP,BND,BGN,BIF,KHR,CAD,CVE,KYD,XAF,XPF,CLP,CNY,COP,KMF,CDF,CRC,HRK,CZK,DKK,DJF,DOP,XCD,EGP,ETB,EUR,FKP,FJD,GMD,GEL,GIP,GTQ,GNF,GYD,HTG,HNL,HKD,HUF,ISK,INR,IDR,ILS,JMD,JPY,KZT,KES,KGS,LAK,LBP,LSL,LRD,MOP,MKD,MGA,MWK,MYR,MVR,MRO,MUR,MXN,MDL,MNT,MAD,MZN,MMK,NAD,NPR,ANG,TWD,NZD,NIO,NGN,NOK,PKR,PAB,PGK,PYG,PEN,PHP,PLN,QAR,RON,RUB,RWF,STD,SHP,SVC,WST,SAR,RSD,SCR,SLL,SGD,SBD,SOS,ZAR,KRW,LKR,SRD,SZL,SEK,CHF,TJS,TZS,THB,TOP,TTD,TRY,UGX,UAH,AED,USD,UYU,UZS,VUV,VND,XOF,YER,ZMW'));
    $currency = model('user')->getUserCurrency();
    return in_array($currency, $supported);
}
function userCurrencySupportPaypal() {
    $supported = explode(',', config('paypal-supported-currency', 'ARS,AUD,BRL,CAD,CHF,CZK,DKK,EUR,GBP,HKD,HUF,INR,ILS,JPY,MXN,MYR,NOK,NZD,PHP,PLN,RUB,SEK,SGD,THB,TWD,USD'));
    $currency = model('user')->getUserCurrency();
    return in_array($currency, $supported);
}
function userCurrencySupportMollie() {
    $supported = explode(',', config('mollie-supported-currency', 'ARS,AUD,BRL,CAD,CHF,CZK,DKK,EUR,GBP,HKD,HUF,INR,ILS,JPY,MXN,MYR,NOK,NZD,PHP,PLN,RUB,SEK,SGD,THB,TWD,USD'));
    $currency = model('user')->getUserCurrency();
    return in_array($currency, $supported);
}
function getDefaultCurrencySymbol() {
    $currencies = getRawCurriencies();
    return $currencies[config('currency','USD')]['symbol'];
}
function formatMoney($total, $symbol = '') {

    if (!is_numeric($total) && $total != 0) {
        return $total;
    }

    $total = formatMoneyTotal($total);
    if (config('enable-multi-currency', false)) {
        $userCurrency = model('user')->getUserCurrency();
        $symbol = getCurrencySymbol($userCurrency);
    }

    $decimal_separator  = config('decimal-separator', '.');
    $thousand_separator = config('thousand-separator', ',');
    $currency_placement = config('currency-placement', 'before');
    $d                  = 3;
    $symbol = ($symbol) ? $symbol : getDefaultCurrencySymbol();

    if (config('remove-zero-decimals', true)) {
        if (!is_decimal($total)) {
            $d = 0;
        }
    }

    $total = number_format($total, $d, $decimal_separator, $thousand_separator);

    $split = explode($decimal_separator, $total);
    if (count($split) > 1) {
        if (substr($total, strlen($total) - 1, 1) == '0') {
            $total = substr($total, 0, strlen($total) - 1);
        }
    }
    //$total = Hook::getInstance()->fire('money_after_format_without_currency', $total);

    if ($currency_placement === 'after') {
        $_formatted = $total . '' . $symbol;
    } else {
        $_formatted = $symbol . '' . $total;
    }

    //$_formatted = Hook::getInstance()->fire('money_after_format_with_currency', $_formatted);

    return $_formatted;
}
function is_decimal($val)
{
    return is_numeric($val) && floor($val) != $val;
}

function getThemeMode() {
    $mode = config('default-theme-mode', 'light-mode');
    if (session_get('theme-mode')) {
        $mode = session_get('theme-mode');
    }
    return $mode;
}

function download_file($path, $base_name = null, $speed = null, $multipart = true, $deleteOnDone = false) {
    if(!$path) {
        return false;
    }

    if(preg_match('/^(https?\:\/\/)([^\.]{1,63}\.)?(.*?)(.+)/i', $path)) {
        return Request::instance()->redirect($path);
    }
    $base_path = str_replace(array('\\', '/'), DIRECTORY_SEPARATOR, path());
    $real_path = realpath($path);
    if(!$real_path || !is_file($real_path)) {
        return false;
    } else {
        $rel_path = preg_replace('/'.preg_quote($base_path, '/').'/i', '', $real_path);
        if(!preg_match('#^uploads(/|\\\)#', $rel_path)) {
            return false;
        }
    }

    while(ob_get_level() > 0) {
        ob_end_clean();
    }


    $ext = pathinfo($path, PATHINFO_EXTENSION);
    $base_name = ($base_name) ? $base_name.'.'.$ext : md5(basename($path).time().time()).'.'.$ext;

    if(is_file($path = realpath($path)) === true) {
        $file = @fopen($path, 'rb');
        $size = sprintf('%u', filesize($path));
        $speed = (empty($speed) === true) ? 1024 : floatval($speed);
        if(is_resource($file) === true) {
            set_time_limit(0);
            if(strlen(session_id()) > 0) {
                session_write_close();
            }
            if($multipart === true) {
                $range = array(0, $size - 1);
                if(array_key_exists('HTTP_RANGE', $_SERVER) === true) {
                    $range = array_map('intval', explode('-', preg_replace('~.*=([^,]*).*~', '$1', $_SERVER['HTTP_RANGE'])));
                    if(empty($range[1]) === true) {
                        $range[1] = $size - 1;
                    }
                    foreach($range as $key => $value) {
                        $range[$key] = max(0, min($value, $size - 1));
                    }
                    if(($range[0] > 0) || ($range[1] < ($size - 1))) {
                        header(sprintf('%s %03u %s', 'HTTP/1.1', 206, 'Partial Content'), true, 206);
                    }
                }
                header('Accept-Ranges: bytes');
                header('Content-Range: bytes '.sprintf('%u-%u/%u', $range[0], $range[1], $size));
            } else {
                $range = array(0, $size - 1);
            }
            header('Pragma: public');
            header('Cache-Control: public, no-cache');
            header('Content-Type: application/octet-stream');
            header('Content-Length: '.sprintf('%u', $range[1] - $range[0] + 1));
            header('Content-Disposition: attachment; filename="'.$base_name.'"');
            header('Content-Transfer-Encoding: binary');
            if($range[0] > 0) {
                fseek($file, $range[0]);
            }
            while((feof($file) !== true) && (connection_status() === CONNECTION_NORMAL)) {
                echo fread($file, round($speed * 1024));
                flush();
                sleep(1);
            }
            fclose($file);
        }
        exit;
    } else {
        header(sprintf('%s %03u %s', 'HTTP/1.1', 404, 'Not Found'), true, 404);
    }

    return false;
}

function generateHash($u) {
    $time = time();
    return md5(mt_rand(0, 9999).$time.mt_rand(0, 9999).$u.mt_rand(0, 9999));
}

function downloadUrlContent($url, $ext = 'mp3') {
    set_time_limit(0);
    if (!is_dir(path('uploads/tmp/'))) {
        @mkdir(path('uploads/tmp/'), 0777, true);
        $file = @fopen(path("uploads/tmp/index.html"), 'x+');
        fclose($file);
    }
    $uploadPath = "uploads/tmp/";
    $file = $uploadPath . md5(uniqid().time().$url) . '.'.$ext;
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

function uniqueKey($minlength = 20, $maxlength = 20, $uselower = true, $useupper = true, $usenumbers = true, $usespecial = false) {
    $charset = '';
    if ($uselower) {
        $charset .= "abcdefghijklmnopqrstuvwxyz";
    }
    if ($useupper) {
        $charset .= "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    }
    if ($usenumbers) {
        $charset .= "123456789";
    }
    if ($usespecial) {
        $charset .= "~@#$%^*()_+-={}|][";
    }
    if ($minlength > $maxlength) {
        $length = mt_rand($maxlength, $minlength);
    } else {
        $length = mt_rand($minlength, $maxlength);
    }
    $key = '';
    for ($i = 0; $i < $length; $i++) {
        $key .= $charset[(mt_rand(0, strlen($charset) - 1))];
    }
    return $key;
}
function covtime($youtube_time) {
    $start = new DateTime('@0'); // Unix epoch
    $start->add(new DateInterval($youtube_time));
    return $start->format('H:i:s');
}
function curl_get_file_size( $url ) {
    // Assume failure.
    $result = -1;

    $curl = curl_init( $url );

    // Issue a HEAD request and follow any redirects.
    curl_setopt( $curl, CURLOPT_NOBODY, true );
    curl_setopt( $curl, CURLOPT_HEADER, true );
    curl_setopt($curl, CURLOPT_TIMEOUT, 50);
    curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, true );
        curl_setopt( $curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1" );

    $data = curl_exec( $curl );
    curl_close( $curl );

    if( $data ) {
        $content_length = "unknown";
        $status = "unknown";

        if( preg_match( "/^HTTP\/1\.[01] (\d\d\d)/", $data, $matches ) ) {
            $status = (int)$matches[1];
        }

        if( preg_match( "/Content-Length: (\d+)/", $data, $matches ) ) {
            $content_length = (int)$matches[1];
        }

        // http://en.wikipedia.org/wiki/List_of_HTTP_status_codes
        if( $status == 200 || ($status > 300 && $status <= 308) ) {
            $result = $content_length;
        }
    }

    return $result;
}

function smartReadFile($location, $filename, $mimeType = 'application/octet-stream')
{
    $size	= filesize($location);
    $time	= date('r', filemtime($location));
    //$time	= date('r', filemtime($location));
    $fm		= @fopen($location, 'rb');
    if (!$fm)
    {
        header ("HTTP/1.1 505 Internal server error");
        return;
    }

    $begin	= 0;
    $end	= $size - 1;

    if (isset($_SERVER['HTTP_RANGE']))
    {
        if (preg_match('/bytes=\h*(\d+)-(\d*)[\D.*]?/i', $_SERVER['HTTP_RANGE'], $matches))
        {
            $begin	= intval($matches[1]);
            if (!empty($matches[2]))
            {
                $end	= intval($matches[2]);
            }
        }
    }

    if (isset($_SERVER['HTTP_RANGE']))
    {
        header('HTTP/1.1 206 Partial Content');
    }
    else
    {
        header('HTTP/1.1 200 OK');
    }
    //exit('sdsd');

    if (isset($_SERVER['HTTP_RANGE']))
    {

        header("Content-Range: bytes $begin-$end/$size");
    }
    header("Content-Type: $mimeType");
    header('Cache-Control: public, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Accept-Ranges: bytes');
    header('Content-Length:' . (($end - $begin) + 1));
    header("Content-Encoding: none");
    header("Content-Disposition: inline; filename=$filename");
    header("Content-Transfer-Encoding: binary");
    header("Last-Modified: $time");

    $cur	= $begin;
    fseek($fm, $begin, 0);

    while(!feof($fm) && $cur <= $end && (connection_status() == 0))
    {
        print fread($fm, min(1024 * 16, ($end - $cur) + 1));
        ob_flush();
        flush();
        $cur += 1024 * 16;
    }
}

function  getMimeType($filename) {
    $ext = get_file_extension($filename);
    $type = null;
    switch ($ext) {
        case 'mp3':
            $type = 'audio/mpeg';
            break;
        case 'ogg':
            $type = 'audio/ogg';
            break;
        case 'oga':
            $type = 'audio/ogg';
            break;
        case 'm4a':
            $type = 'audio/mp4';
            break;
        case 'm4v':
            $type = 'video/mp4';
            break;
        case 'mp4':
            $type = 'video/mp4';
            break;
        case 'webm':
            $type = 'video/webm';
            break;
        case 'ogv':
            $type = 'video/ogg';
            break;
    }
    return $type;
}

function getMimeTypeExtension($mime) {
    $type  = null;
    switch ($mime) {
        case 'audio/mpeg':
            $type = 'mp3';
            break;
        case 'audio/ogg':
            $type = 'ogg';
            break;
    }
    return $type;
}

function moduleExists($module) {
    return Request::instance()->moduleIsLoaded($module);
}
function ffmpeg_duration($filename = false){
    global $pt;

    $ffmpeg_b = config('ffmpeg-path');
    $output   = shell_exec("$ffmpeg_b -i {$filename} 2>&1");
    $ptrn     = '/Duration: ([0-9]{2}):([0-9]{2}):([^ ,])+/';
    $time     = 30;
    if (preg_match($ptrn, $output, $matches)) {
        $time = str_replace("Duration: ", "", $matches[0]);
        $time_breakdown = explode(":", $time);
        $time = round(($time_breakdown[0]*60*60) + ($time_breakdown[1]*60) + $time_breakdown[2]);
    }

    return $time;
}

function get_video_attributes($video, $ffmpeg) {
    if(!is_executable($ffmpeg)) {
        exit('FFMPEG is not executable');
    }
    $video_attributes = array(
        'codec' => 'undefined',
        'width' => '0',
        'height' => '0',
        'hours' => '0',
        'mins' => '0',
        'secs' => '0',
        'ms' => '0',
    );
    $command = $ffmpeg.' -i '.$video.' -vstats 2>&1';
    $output = shell_exec($command);
    for($i = 2; $i <= 3; $i++) {
        $regex = '([^,]*),';
        for($j = 1; $j < $i; $j++) {
            $regex .= '([^,]*),';
        }
        $k = false;
        $regex_sizes = "/Video: ".$regex." ([0-9]{1,4})x([0-9]{1,4})/";
        if(preg_match($regex_sizes, $output, $regs)) {
            $video_attributes['codec'] = $regs [1] ? $regs [1] : null;
            $video_attributes['width'] = $regs [(count($regs) - 2)] ? $regs [(count($regs) - 2)] : null;
            $video_attributes['height'] = $regs [(count($regs) - 1)] ? $regs [(count($regs) - 1)] : null;
            $k = true;
        }
        if($k) break;
    }
    $regex_duration = "/Duration: ([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2}).([0-9]{1,2})/";
    if(preg_match($regex_duration, $output, $regs)) {
        $video_attributes['hours'] = $regs [1] ? $regs [1] : null;
        $video_attributes['mins'] = $regs [2] ? $regs [2] : null;
        $video_attributes['secs'] = $regs [3] ? $regs [3] : null;
        $video_attributes['ms'] = $regs [4] ? $regs [4] : null;
    }
    $video_attributes['length'] = (float) strtotime('1970-01-01 '.$video_attributes['hours'].':'.$video_attributes['mins'].':'.$video_attributes['secs'].' UTC').'.'.$video_attributes['ms'];
    return $video_attributes;
}

function getRemoteMimeType($url) {
    $ch = curl_init();
    $url = $url;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_NOBODY, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $results = explode("\n", trim(curl_exec($ch)));
    foreach($results as $line) {
        if (strtok($line, ':') == 'Content-Type') {
            $parts = explode(":", $line);
            return  trim($parts[1]);
        }
    }
    return null;
}

function getRawCurriencies() {
    return json_decode('{"AFN":{"name":"Afghan Afghani","symbol":"\u060b"},"ALL":{"name":"Albanian Lek","symbol":"Lek"},"DZD":{"name":"Algerian Dinar","symbol":"DZD"},"AOA":{"name":"Angolan Kwanza","symbol":"AOA"},"ARS":{"name":"Argentine Peso","symbol":"$"},"AMD":{"name":"Armenian Dram","symbol":"AMD"},"AWG":{"name":"Aruban Florin","symbol":"\u0192"},"AUD":{"name":"Australian Dollar","symbol":"$"},"AZN":{"name":"Azerbaijani Manat","symbol":"\u043c\u0430\u043d"},"BSD":{"name":"Bahamian Dollar","symbol":"$"},"BDT":{"name":"Bangladeshi Taka","symbol":"BDT"},"BBD":{"name":"Barbadian Dollar","symbol":"$"},"BZD":{"name":"Belize Dollar","symbol":"BZ$"},"BMD":{"name":"Bermudian Dollar","symbol":"BMD"},"BOB":{"name":"Bolivian Boliviano","symbol":"$b"},"BAM":{"name":"Bosnia And Herzegovina Konvertibilna Marka","symbol":"KM"},"BWP":{"name":"Botswana Pula","symbol":"P"},"BRL":{"name":"Brazilian Real","symbol":"R$"},"GBP":{"name":"British Pound","symbol":"\u00a3"},"BND":{"name":"Brunei Dollar","symbol":"$"},"BGN":{"name":"Bulgarian Lev","symbol":"\u043b\u0432"},"BIF":{"name":"Burundi Franc","symbol":"BIF"},"KHR":{"name":"Cambodian Riel","symbol":"\u17db"},"CAD":{"name":"Canadian Dollar","symbol":"$"},"CVE":{"name":"Cape Verdean Escudo","symbol":"CVE"},"KYD":{"name":"Cayman Islands Dollar","symbol":"$"},"XAF":{"name":"Central African CFA Franc","symbol":"XAF"},"XPF":{"name":"CFP Franc","symbol":"XPF"},"CLP":{"name":"Chilean Peso","symbol":"$"},"CNY":{"name":"Chinese Yuan","symbol":"\u00a5"},"COP":{"name":"Colombian Peso","symbol":"$"},"KMF":{"name":"Comorian Franc","symbol":"KMF"},"CDF":{"name":"Congolese Franc","symbol":"CDF"},"CRC":{"name":"Costa Rican Colon","symbol":"\u20a1"},"HRK":{"name":"Croatian Kuna","symbol":"kn"},"CZK":{"name":"Czech Koruna","symbol":"K\u010d"},"DKK":{"name":"Danish Krone","symbol":"kr"},"DJF":{"name":"Djiboutian Franc","symbol":"DJF"},"DOP":{"name":"Dominican Peso","symbol":"RD$"},"XCD":{"name":"East Caribbean Dollar","symbol":"$"},"EGP":{"name":"Egyptian Pound","symbol":"\u00a3"},"ETB":{"name":"Ethiopian Birr","symbol":"ETB"},"EUR":{"name":"Euro","symbol":"\u20ac"},"FKP":{"name":"Falkland Islands Pound","symbol":"\u00a3"},"FJD":{"name":"Fijian Dollar","symbol":"$"},"GHS":{"name":"Ghana Cedis","symbol":"GH₵"},"GMD":{"name":"Gambian Dalasi","symbol":"GMD"},"GEL":{"name":"Georgian Lari","symbol":"GEL"},"GIP":{"name":"Gibraltar Pound","symbol":"\u00a3"},"GTQ":{"name":"Guatemalan Quetzal","symbol":"Q"},"GNF":{"name":"Guinean Franc","symbol":"GNF"},"GYD":{"name":"Guyanese Dollar","symbol":"$"},"HTG":{"name":"Haitian Gourde","symbol":"HTG"},"HNL":{"name":"Honduran Lempira","symbol":"L"},"HKD":{"name":"Hong Kong Dollar","symbol":"$"},"HUF":{"name":"Hungarian Forint","symbol":"Ft"},"ISK":{"name":"Icelandic Kr\u00f3na","symbol":"kr"},"INR":{"name":"Indian Rupee","symbol":"\u20b9"},"IDR":{"name":"Indonesian Rupiah","symbol":"Rp"},"ILS":{"name":"Israeli New Sheqel","symbol":"\u20aa"},"JMD":{"name":"Jamaican Dollar","symbol":"J$"},"JPY":{"name":"Japanese Yen","symbol":"\u00a5"},"KZT":{"name":"Kazakhstani Tenge","symbol":"\u043b\u0432"},"KES":{"name":"Kenyan Shilling","symbol":"KSh"},"KGS":{"name":"Kyrgyzstani Som","symbol":"\u043b\u0432"},"LAK":{"name":"Lao Kip","symbol":"\u20ad"},"LBP":{"name":"Lebanese Lira","symbol":"\u00a3"},"LSL":{"name":"Lesotho Loti","symbol":"LSL"},"LRD":{"name":"Liberian Dollar","symbol":"$"},"MOP":{"name":"Macanese Pataca","symbol":"MOP"},"MKD":{"name":"Macedonian Denar","symbol":"\u0434\u0435\u043d"},"MGA":{"name":"Malagasy Ariary","symbol":"MGA"},"MWK":{"name":"Malawian Kwacha","symbol":"MWK"},"MYR":{"name":"Malaysian Ringgit","symbol":"RM"},"MVR":{"name":"Maldivian Rufiyaa","symbol":"MVR"},"MRO":{"name":"Mauritanian Ouguiya","symbol":"MRO"},"MUR":{"name":"Mauritian Rupee","symbol":"\u20a8"},"MXN":{"name":"Mexican Peso","symbol":"$"},"MDL":{"name":"Moldovan Leu","symbol":"MDL"},"MNT":{"name":"Mongolian Tugrik","symbol":"\u20ae"},"MAD":{"name":"Moroccan Dirham","symbol":"MAD"},"MZN":{"name":"Mozambican Metical","symbol":"MZN"},"MMK":{"name":"Myanma Kyat","symbol":"MMK"},"NAD":{"name":"Namibian Dollar","symbol":"$"},"NPR":{"name":"Nepalese Rupee","symbol":"\u20a8"},"ANG":{"name":"Netherlands Antillean Gulden","symbol":"\u0192"},"TWD":{"name":"New Taiwan Dollar","symbol":"NT$"},"NZD":{"name":"New Zealand Dollar","symbol":"$"},"NIO":{"name":"Nicaraguan Cordoba","symbol":"C$"},"NGN":{"name":"Nigerian Naira","symbol":"\u20a6"},"NOK":{"name":"Norwegian Krone","symbol":"kr"},"PKR":{"name":"Pakistani Rupee","symbol":"\u20a8"},"PAB":{"name":"Panamanian Balboa","symbol":"B\/."},"PGK":{"name":"Papua New Guinean Kina","symbol":"PGK"},"PYG":{"name":"Paraguayan Guarani","symbol":"Gs"},"PEN":{"name":"Peruvian Nuevo Sol","symbol":"S\/."},"PHP":{"name":"Philippine Peso","symbol":"\u20b1"},"PLN":{"name":"Polish Zloty","symbol":"z\u0142"},"QAR":{"name":"Qatari Riyal","symbol":"\ufdfc"},"RON":{"name":"Romanian Leu","symbol":"lei"},"RUB":{"name":"Russian Ruble","symbol":"\u0440\u0443\u0431"},"RWF":{"name":"Rwandan Franc","symbol":"RWF"},"STD":{"name":"Sao Tome And Principe Dobra","symbol":"STD"},"SHP":{"name":"Saint Helena Pound","symbol":"\u00a3"},"SVC":{"name":"Salvadoran Col\u00f3n","symbol":"SVC"},"WST":{"name":"Samoan Tala","symbol":"WST"},"SAR":{"name":"Saudi Riyal","symbol":"\ufdfc"},"RSD":{"name":"Serbian Dinar","symbol":"\u0414\u0438\u043d."},"SCR":{"name":"Seychellois Rupee","symbol":"\u20a8"},"SLL":{"name":"Sierra Leonean Leone","symbol":"SLL"},"SGD":{"name":"Singapore Dollar","symbol":"$"},"SBD":{"name":"Solomon Islands Dollar","symbol":"$"},"SOS":{"name":"Somali Shilling","symbol":"S"},"ZAR":{"name":"South African Rand","symbol":"R"},"KRW":{"name":"South Korean Won","symbol":"\u20a9"},"LKR":{"name":"Sri Lankan Rupee","symbol":"\u20a8"},"SRD":{"name":"Surinamese Dollar","symbol":"$"},"SZL":{"name":"Swazi Lilangeni","symbol":"SZL"},"SEK":{"name":"Swedish Krona","symbol":"kr"},"CHF":{"name":"Swiss Franc","symbol":"Fr."},"TJS":{"name":"Tajikistani Somoni","symbol":"TJS"},"TZS":{"name":"Tanzanian Shilling","symbol":"TSh"},"THB":{"name":"Thai Baht","symbol":"\u0e3f"},"TOP":{"name":"Paanga","symbol":"TOP"},"TTD":{"name":"Trinidad and Tobago Dollar","symbol":"TT$"},"TRY":{"name":"Turkish New Lira","symbol":"TRY"},"UGX":{"name":"Ugandan Shilling","symbol":"USh"},"UAH":{"name":"Ukrainian Hryvnia","symbol":"\u20b4"},"AED":{"name":"UAE Dirham","symbol":"AED"},"USD":{"name":"United States Dollar","symbol":"$"},"UYU":{"name":"Uruguayan Peso","symbol":"$U"},"UZS":{"name":"Uzbekistani Som","symbol":"\u043b\u0432"},"VUV":{"name":"Vanuatu Vatu","symbol":"VUV"},"VND":{"name":"Vietnamese Dong","symbol":"\u20ab"},"XOF":{"name":"West African CFA Franc","symbol":"XOF"},"YER":{"name":"Yemeni Rial","symbol":"\ufdfc"},"ZMW":{"name":"Zambian Kwacha","symbol":"ZMW"}}', true);
}

function getCurrencySymbol($currency) {
    $curnencies = getRawCurriencies();
    return $curnencies[$currency]['symbol'];
}