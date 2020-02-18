<?php
class TrackModel extends Model {
    private $s3 = null;

    public function getS3() {
        if ($this->s3) return $this->s3;
        include_once path('app/vendor/autoload.php');
        $this->s3 = new \Aws\S3\S3Client(array(
            'credentials'	=> array(
                'key'		=> config('s3-key-id'),
                'secret'	=> config('s3-secret-key')
            ),
            'region'		=> config('s3-region'),
            'version'		=> 'latest'
        ));

        return $this->s3;
    }

    public function getWasabiS3() {
        include_once path('app/vendor/autoload.php');
        $wasabi = new \Aws\S3\S3Client(array(
            'endpoint' => 'https://s3.'.config('wasabi-region').'.wasabisys.com',
            'credentials'	=> array(
                'key'		=> config('wasabi-key-id'),
                'secret'	=> config('wasabi-secret-key')
            ),
            'region'		=> config('wasabi-region'),
            'version'		=> 'latest'
        ));

        return $wasabi;
    }

    public function getFtp() {
        include_once path("app/vendor/ftp/vendor/autoload.php");
        $ftp = new \FtpClient\FtpClient();
        $ftp->connect(config('ftp-hostname'), false, config('ftp-port'));
        $login = $ftp->login(config('ftp-username'), config('ftp-password'));

        if ($login) {
            if (!empty(config('ftp-path', '/'))) {
                if (config('ftp-path','/') != "/") {
                    $ftp->chdir(config('ftp-path'));
                }
            }
            return $ftp;
        }

        return false;
    }

    public function displayItem($track, $allowOwner = false) {
        if (!config('disable-tracks', true) or !config('enable-premium', false)) return true;

        $result = model('user')->lastTransactionExpired($track['userid']);
        if (!$result) return true;
        if ($result == 'expired') {
            if ($allowOwner and $track['userid'] == model('user')->authId) return true;
            //return false;
        }
        return true;
    }

    public function canViewPrivateAlbum($playlist) {
        if ($playlist['public'] == 2) {
            if (!model('user')->isLoggedIn()) return false;
            if (model('user')->authId == $playlist['userid'])  return true;
            return false;
        }
        return true;
    }

    public function formatDuration($duration) //as hh:mm:ss
    {
        //return sprintf("%d:%02d", $duration/60, $duration%60);
        if (!$duration) return 0;
        $hours = floor($duration / 3600);
        $minutes = floor( ($duration - ($hours * 3600)) / 60);
        $seconds = $duration - ($hours * 3600) - ($minutes * 60);
        $result = sprintf("%02d:%02d", $minutes, $seconds);
        if ($hours) $result = sprintf("%02d:%02d", $hours, $result);
        return $result;
    }


    public function getTrackFile($track, $local = false) {
        $file = $track['track_file'];
        return $file;
    }



    public function add($val, $track = null, $isAdmin= false, $userid = null) {
        /**
         * @var $title
         * @var $audio
         * @var $description
         * @var $tags
         * @var $art
         * @var $privacy
         * @var $label
         * @var $link
         * @var $download
         * @var $license
         * @var $size
         * @var $release
         * @var $wave
         * @var $wave_colored
         * @var $genre
         * @var $comments
         * @var $release_date
         * @var $stats
         * @var $embed
         * @var $duration
         * @var $slug
         * @var $featuring
         * @var $lyrics
         */
        $ex = array(
            'title' => '',
            'description' => '',
            'tags' => '',
            'privacy' => 1,
            'label' => '',
            'link' => '',
            'download' => 0,
            'license' => 1,
            'size' => 0,
            'release' => '',
            'art' => '',
            'wave' => '',
            'wave_colored' => '',
            'genre' => '',
            'comments' => 1,
            'release_date' => '',
            'stats' => 1,
            'embed' => 1,
            'duration' => '',
            'slug' => '',
            'featuring' => '',
            'lyrics' => ''
        );
        extract(array_merge($ex, $val));
        $userid = ($userid) ? $userid : $this->C->model('user')->authId;

        if ($release_date) {
            list($day,$month,$year) = explode('/', $release_date);
            $release_date = mktime(0,0,0, $month,$day,$year);
        }
        $embed = ($embed) ? 1 : 0;
        if (!$track) {
            $time = time();
            try{

                $query = $this->db->query("INSERT INTO tracks (featuring, lyrics, download_hash,release_date,comments,stats,embed,title,userid,description,tag,genre,art,buy,record,track_release,license,size,track_file,public,downloads,wave,wave_colored,time,track_duration,slug)VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                    $featuring, $lyrics, generateHash(time()),$release_date, $comments, $stats, $embed,$title,$userid,$description,$tags,$genre,$art,$link,$label,$release,$license,$size,$audio,$privacy,$download,$wave,$wave_colored,$time,$duration,$slug);

                $trackId = $this->db->lastInsertId();

                //delete the audio file insert in the tmp_files table to prevent future delete of the file
                $this->db->query("DELETE FROM tmp_files WHERE path=? ", $audio);
            } catch(Exception $e) {
                print_r($e->getMessage());
                exit;
            }

        } else {
            $trackId = $track['id'];
            $art = ($art) ? $art : $track['art'];
            if ($isAdmin) {
                $privacy = $track['public'];
                $license = $track['license'];
                $release_date = $track['release_date'];
            }

            $lyrics = ($lyrics) ? $lyrics : $track['lyrics'];

            $this->db->query("UPDATE tracks SET featuring=?,lyrics=?, release_date=?,comments=?,stats=?,embed=?,title=?,description=?,tag=?,genre=?,art=?,buy=?,record=?,track_release=?,license=?,public=?,downloads=?,slug=? WHERE id=? ",
                $featuring,$lyrics,$release_date, $comments, $stats, $embed,$title,$description,$tags,$genre,$art,$link,$label,$release,$license,$privacy,$download,$slug,$track['id']);

            if (isset($audio)) {
                $this->db->query("UPDATE tracks SET size=?,track_file=?,wave=?,wave_colored=?,track_duration=? WHERE id=?", $size, $audio,$wave,$wave_colored,$duration, $track['id']);
            }
            $oldTags = explode('-', $track['tag']);
            foreach($oldTags as $tag) {
                $this->removeTag($tag);
            }

            if ($release_date) {
                $this->setTrackPlaylistDate($trackId, $release_date);
            }


        }
        $tags = explode(',', $tags);

        foreach($tags as $tag) {
            $this->addTag($tag);
        }

        if ($track) {
            Hook::getInstance()->fire('track.edit', null, array($track, $val));
        } else {
            Hook::getInstance()->fire('track.added', null, array($trackId, $val));
        }
        return $trackId;
    }

    public function canChangeTrack() {
        $settings = config('can-update-track',2);
        if ($settings == 1) return true;
        if (model('user')->subscriptionActive()) return true;
        return false;
    }

    public function canDownload($track) {
        if (!model('user')->isLoggedIn() and config('enable-logged-user-only-download', false)) return false;
        $result = array('canDownload' => true);
        $result = Hook::getInstance()->fire('can.download', $result, array($track));
        if (!$result['canDownload']) return false;
        if (moduleExists('store') and $track['price'] > 0) {
            if (model('store::store')->hasPurchased($track['id'], 'track', true)) {
                return true;
            }
        } else {
            if ($track['downloads']) return true;
        }
        return false;
    }

    public function canDownloadZip($id) {
        $tracks = $this->getPlaylistTracks($id, 0);
        $result = array();
        $playlist = $this->findPlaylist($id);
        if (moduleExists('store') and $playlist['playlist_type'] == 0 and !model('store::store')->hasPurchased($id, 'album')) return false;
        foreach($tracks as $track) {
            if ($this->canDownload($track) or $track['price'] <= 0) $result[] = $track;
        }
        return $result;
    }

    public function convertUriImageToFile($img, $dir = 'wave') {
        $img = str_replace('data:image/png;base64,', '', $img);
        $img = str_replace(' ', '+', $img);
        $data = base64_decode($img);
        if (!is_dir(path('uploads/waves/'.$dir))) {
            @mkdir(path('uploads/waves/'.$dir), 0777, true);
            $file = @fopen(path("uploads/waves/$dir/index.html"), 'x+');
            fclose($file);
        }
        $uploadPath = "uploads/waves/$dir/";
        $file = $uploadPath . md5(uniqid().time().$img) . '.png';
        $success = file_put_contents($file, $data);
        return $file;
    }

    public function addTag($tag) {

        if (!$this->tagExists($tag)) {
            return $this->db->query("INSERT INTO tags (title, uses)VALUES(?,?)", $tag, 1);
        } else {
            return $this->db->query("UPDATE tags SET uses=uses+1 WHERE title=?", $tag);
        }
    }

    public function removeTag($tag) {
        return $this->db->query("UPDATE tags SET uses=uses-1 WHERE title=?", $tag);
    }

    public function tagExists($tag) {
        $query = $this->db->query("SELECT id from tags WHERE title=?", $tag);
        return $query->rowCount();
    }

    public function getTrendingTags($limit = 10) {
        $query = $this->db->query("SELECT * from tags  ORDER BY uses DESC LIMIT $limit");
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getExpiredArtists($typeId = null) {
        $array = array(0);
        $time = time();
        if (config('disable-tracks', true) and config('enable-premium', false)) {
            $query = $this->db->query("SELECT DISTINCT(userid) FROM transactions as tab1 WHERE type = ? AND valid_time < $time AND (SELECT COUNT(id) as c FROM transactions as tab2 WHERE valid_time > $time AND tab2.userid = tab1.userid AND type='pro' AND status='1') < 1 ORDER BY userid DESC ", 'pro');
            while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
                if ($fetch['userid'] != $typeId) $array[] = $fetch['userid'];
            }
        }
        return implode(',', $array);
    }
    public function getTracks($type,$typeId,$offset,$limit = 10) {
        $blockedIds = $this->C->model('user')->blockIds();
        $expiredArtists = $this->getExpiredArtists();
        $userid = $this->C->model('user')->authId;
        $time = time();
        if ($type == 'feed') {
            $followingIds = $this->C->model('user')->getFollowingIds();
            $followingIds[] = $this->C->model('user')->authId;
            if ($followingIds and $this->C->model('user')->isLoggedIn()) {
                $followingIds = implode(',', $followingIds);

                $sql = "SELECT  MAX(streamid) as id FROM stream  WHERE poster NOT IN ($expiredArtists) AND  poster NOT IN ($blockedIds) AND  stream.poster IN ($followingIds) GROUP BY trackid,playlist_id ORDER BY id DESC";
                $param = array();

            } else {
                return array(); //we don't show anything in the stream
            }
            //exit($sql);
        } elseif($type === 'charts-new-hot'){
            //$limit = 50;
            list($genre, $time) = explode('/', $typeId);
            $time = config('chart-new-hot-time', 'this-week');
            $time = get_time_relative_format($time);
            $currentTime = time();

            $sql = "SELECT *,(SELECT count(track) FROM views WHERE views.track=tracks.id ) as count FROM tracks WHERE tracks.status=? AND  userid NOT IN ($blockedIds) AND userid NOT IN ($expiredArtists) AND  tracks.time BETWEEN $time AND $currentTime  AND (public != ? AND public != ?)  AND approved=?";
            $param = array(1, 3,2,1);
            if ($genre != 'all') {
                $param[] = $genre;
                $sql .= ' AND genre=? ';
            }
            $sql .= " ORDER BY count DESC";


        }elseif($type === 'charts-top'){
            //$limit = 50;
            list($genre, $time) = explode('/', $typeId);
            if (!$time) $time = config('chart-top-time', 'this-week');
            $time = get_time_relative_format($time);
            $currentTime = time();

            $sql = "SELECT *,(SELECT count(track) FROM views WHERE views.track=tracks.id AND views.time BETWEEN $time AND $currentTime ) as count FROM tracks WHERE tracks.status=? AND userid NOT IN ($expiredArtists)  AND  userid NOT IN ($blockedIds)   AND (tracks.public != ? AND tracks.public != ?)  AND approved=?";
            $param = array(1, 3,2,1);
            if ($genre != 'all') {
                $param[] = $genre;
                $sql .= ' AND genre=? ';
            }
            $sql .= " ORDER BY count DESC";
        }elseif($type == 'genre') {
            $sql = "SELECT * FROM  tracks WHERE  userid NOT IN ($blockedIds)AND userid NOT IN ($expiredArtists)  AND status=? AND genre=? AND (public != ? AND public != ?)  AND approved=?  ORDER BY id DESC";
            $param = array(1, $typeId, 3,2,1);
        }elseif($type == 'latest') {
            $sql = "SELECT * FROM  tracks WHERE userid NOT IN ($blockedIds) AND userid NOT IN ($expiredArtists)  AND status=? AND (public != ? AND public != ?)  AND approved=?  ORDER BY id DESC";
            $param = array(1, 3,2,1);
        }elseif ($type == 'my-stream') {
            $followingIds = array($typeId);
            if (model('user')->isLoggedIn() and $typeId = model('user')->authId) {
                $expiredArtists = $this->getExpiredArtists($typeId);
            }
            if ($followingIds) {
                $followingIds = implode(',', $followingIds);
                $sql = "SELECT  MAX(streamid) as id FROM stream  WHERE poster NOT IN ($blockedIds) AND poster NOT IN ($expiredArtists)  AND  stream.poster IN ($followingIds) ";
                $param = array();
                $sql = Hook::getInstance()->fire('my-stream.query', $sql, array($type, $typeId));
                $sql .= "GROUP BY trackid,playlist_id ORDER BY id DESC";


            } else {
                return array(); //we don't show anything in the stream
            }
        } elseif($type == 'my-reposts') {
            $followingIds = array($typeId);
            if ($followingIds) {
                $followingIds = implode(',', $followingIds);
                $sql = "SELECT  MAX(streamid) as id FROM stream  WHERE poster NOT IN ($blockedIds) AND poster NOT IN ($expiredArtists)  AND  stream.poster IN ($followingIds) AND (action=? OR action=? OR action=?) GROUP BY trackid,playlist_id ORDER BY id DESC";
                $param = array('repost-track','repost-playlist','repost-album');

            } else {
                return array(); //we don't show anything in the stream
            }
        } elseif($type == 'my-liked') {
            $sql = "SELECT * FROM  tracks WHERE id IN (SELECT typeid FROM likes WHERE type=? AND userid=? ) AND  tracks.userid NOT IN ($blockedIds) AND tracks.userid NOT IN ($expiredArtists)  AND tracks.status=? AND (tracks.public != ? AND tracks.public !=?)  ORDER BY id DESC";
            $param = array('track',$typeId,1, 3, 2);
        }elseif($type == 'listen-later') {
            $sql = "SELECT * FROM listen_later LEFT JOIN tracks ON listen_later.trackid=tracks.id WHERE userid NOT IN ($blockedIds) AND userid NOT IN ($expiredArtists)  AND tracks.status=? AND listen_later.listener=? AND (tracks.public != ? AND tracks.public !=?)  ORDER BY id DESC";
            $param = array(1, $this->C->model('user')->authId, 3, 2);
        } elseif($type == 'history') {
            $sql = "SELECT * FROM views LEFT JOIN tracks ON views.track=tracks.id WHERE tracks.userid NOT IN ($blockedIds) AND tracks.userid NOT IN ($expiredArtists)  AND tracks.status=? AND views.userid=? AND (tracks.public != ? AND tracks.public !=? ) ORDER BY views.viewid DESC";
            $param = array(1, $this->C->model('user')->authId, 3, 2);
        } elseif($type == 'likes'){
            $sql = "SELECT * FROM  tracks WHERE id IN (SELECT typeid FROM likes WHERE type=? AND userid=? ) AND  tracks.userid NOT IN ($blockedIds) AND tracks.userid NOT IN ($expiredArtists)  AND tracks.status=? AND (tracks.public != ? AND tracks.public !=?)  ORDER BY id DESC";
            $param = array('track',$this->C->model('user')->authId,1, 3, 2);
        }  elseif($type == 'playlist') {
            $entries = $this->getPlaylistEntries($typeId);
            if ($entries) {
                $entries = implode(',', $entries);
                $tracks = $this->getPlaylistTracks($typeId,0, $limit , $offset);
                return $tracks;
            } else {
                return array();
            }
        }elseif($type == 'search'){
            $sql = "SELECT * FROM  tracks  WHERE status=? AND ((public != ? AND public !=?) OR userid = ?) AND  (title LIKE ? OR tag LIKE ? OR description LIKE ? OR featuring LIKE ? )  AND approved=? AND userid NOT IN ($expiredArtists)  ORDER BY id DESC";
            $type = str_replace('#', '', $typeId);
            $term = "%$typeId%";
            $param = array(1,3,2,model('user')->authId, $term, $term,$term, $term,1);
        }else {

            $result = Hook::getInstance()->fire('track.query', array('sql' => '', 'param' => array()), array($type, $typeId,$limit,$offset));
            if ($result['sql']) {
                $sql = $result['sql'];
                $param = $result['param'];
            } else {
                if ($type == 'my-tracks' and (model('user')->isLoggedIn() and $typeId == model('user')->authId) ) {
                    $expiredArtists = $this->getExpiredArtists($typeId);
                }
                $sql = "SELECT * FROM tracks WHERE status=? AND ((public != ? AND public !=?) OR userid=?) AND approved=? AND userid NOT IN ($expiredArtists) ";
                $param = array(1, 3,2, $userid,1);
                if ($type == 'my-tracks') {

                    $sql .= ' AND userid=? ';
                    if ($typeId) {
                        if (preg_match("#-#", $typeId)) {
                            list($typeId, $trackId ) = explode('-', $typeId);
                            $param[] = $typeId;
                            $sql .= ' AND id !=? ';
                            $param[] = $trackId;
                        } else {

                            $param[] = $typeId;
                        }
                    } else {

                        $param[] = $this->C->model('user')->authId;
                    }

                }

                $sql .= " ORDER BY id DESC";
            }
        }



        $sql .= "  LIMIT {$limit} OFFSET {$offset} ";
        //exit($sql);
        //if (Request::instance()->segment(0) === 'api') exit($sql);
        try{
            $query = $this->db->query($sql, $param);
            $result =  $query->fetchAll(PDO::FETCH_ASSOC);
            return $result;
        } catch (Exception $e) {
            print_r($e);
            exit;
        }
    }

    public function clearHistory() {
        return $this->db->query("DELETE FROM views WHERE userid=?", $this->C->model('user')->authId);
    }

    public function canComment($track) {
        if ($track['userid'] == $this->C->model('user')->authId) return true;
        if ($track['comments'] == 1) return true;
        if ($track['comments'] == 2) {
            if ($this->C->model('user')->isFollowing($track['userid'])) return true;
        }
        return false;
    }
    public function countTracks($userid) {
        $query = $this->db->query("SELECT id FROM tracks WHERE userid=?", $userid);
        return $query->rowCount();
    }

    public function getArt($track, $size = 200) {
        $image = assetUrl('assets/images/track.png');
        if ($track['art']) {
            $image = url_img($track['art'], $size);
        }
        return $image;
    }

    public function findTrack($trackId, $approved = true) {
        $userid = $this->C->model('user')->authId;
        //$condition = (is_numeric($trackId)) ?  :
        $blockedIds = $this->C->model('user')->blockIds();
        if ($approved) {
            if (is_numeric($trackId)) {
                $query = $this->db->query("SELECT * from tracks WHERE  (id=?) AND status=? AND ((public != ? AND public!=?) OR userid=?) AND approved=? AND userid NOT IN ($blockedIds)",$trackId, 1, 3,2,  $userid, 1);
            } else {
                $query = $this->db->query("SELECT * from tracks WHERE  (download_hash=?) AND status=? AND ((public != ? AND public!=?) OR userid=?) AND approved=? AND userid NOT IN ($blockedIds)",$trackId, 1, 3,2,  $userid, 1);
            }
        } else {
            if (is_numeric($trackId)) {
                $query = $this->db->query("SELECT * from tracks WHERE  (id=?) AND status=? AND (public != ? OR userid=?) AND userid NOT IN ($blockedIds)",$trackId, 1, 3, $userid);
            } else {
                $query = $this->db->query("SELECT * from tracks WHERE  (download_hash=?) AND status=? AND (public != ? OR userid=?) AND userid NOT IN ($blockedIds)",$trackId, 1, 3, $userid);
            }
        }
        $result = $query->fetch(PDO::FETCH_ASSOC);
        $result = Hook::getInstance()->fire('format.track', $result);
        return $result;
    }



    public function getDownloadUrl($track) {


        $trackUrl = getController()->getFileUri($track['track_file'], false);
        if (isset($track['download_hash'])) {
            return  url('track/download', array('key' => $track['download_hash']));
        }
        if (preg_match("#http#", $trackUrl)) {
            return $trackUrl;
        } else {
            if (isset($track['download_hash'])) {
                return  url('track/download', array('key' => $track['download_hash']));
            }
        }

        $trackUrl = $this->getTrackFile($trackUrl);
        $trackUrl = Hook::getInstance()->fire('track.download.url', $trackUrl, array($track));
        return $trackUrl;
    }

    public function trackUrl($track = null, $trackId = null, $slug = '') {
        $track = ($track) ? $track : $this->findTrack($trackId);
        $dslug = ($track['slug']) ? $track['slug'] : toAscii($track['title']);
        $url = 'track/'.$track['id'].'-'.$dslug;
        if ($slug) $url .= '/'.$slug;
        return $this->C->request->url($url);
    }

    public function getPlaylistEntries($typeId) {
        $query = $this->db->query("SELECT track FROM playlistentries WHERE playlist=? ", $typeId);
        $ids = array();
        while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
            $ids[] = $fetch['track'];
        }

        return $ids;
    }

    public function addPlaylist($val, $tracks = array(), $userid = null) {
        /**
         * @var $title
         * @var $desc
         * @var $public
         * @var $type
         * @var $release_date
         * @var $art
         */
        extract(array_merge(array(
            'public' => 1,
            'type' => 1,
            'desc' => '',
            'release_date' => '',
            'art' => ''
        ), $val));
        $userid = ($userid) ? $userid : $this->C->model('user')->authId;
        if ($release_date) {
            $data = explode('/', $release_date);
            if (count($data) > 2) {
                list($day,$month,$year) = $data;
                $release_date = mktime(0,0,0, $month,$day,$year);
            }
        }
        //$type = 1;
        $this->db->query("INSERT INTO playlist(userid, name, description,public,playlist_type,release_date,art,time)VALUES(?,?,?,?,?,?,?,?)",
            $userid,$title,$desc,$public,$type,$release_date,$art, time() );

        $playlistId = $this->db->lastInsertId();
        foreach ($tracks as $track){
            $this->addToPlaylist($playlistId, $track);
        }
        return $playlistId;
    }

    public function savePlaylist($id, $val) {
        /**
         * @var $title
         * @var $desc
         * @var $public
         * @var $type
         * @var $art
         */
        extract(array_merge(array(
            'public' => 1,
            'desc' => '',
            'art' => ''
        ), $val));
        $userid = $this->C->model('user')->authId;

        $this->db->query("UPDATE playlist SET name=?,description=?,public=? WHERE id=?", $title,$desc,$public,$id);
        if ($art) {
            $this->db->query("UPDATE playlist SET art=? WHERE id=?", $art,$id);
        }
        Hook::getInstance()->fire('playlist.save', null, array($id,$val));
        return true;
    }

    public function setTrackPlaylistDate($trackId, $date) {
        $query = $this->db->query("SELECT playlist FROM playlistentries WHERE track=? LIMIT 1", $trackId);
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $this->db->query("UPDATE playlist SET release_date=?  WHERE id=?", $date, $result['playlist']);
    }

    public function getAlbumIds($typeId) {
        $query = $this->db->query("SELECT id FROM playlist WHERE playlist_type=? AND userid=?", 0,$typeId);
        $ids = array();
        while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
            $ids[] = $fetch['id'];
        }
        return $ids;
    }

    public function getPlaylistIds($typeId) {
        $query = $this->db->query("SELECT id FROM playlist WHERE playlist_type=?  AND userid=?", 1,$typeId);
        $ids = array();
        while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
            $ids[] = $fetch['id'];
        }
        return $ids;
    }

    public function addToPlaylist($playlistId, $track) {
        if ($this->existInPlaylist($playlistId, $track)) {
            //then they must be removing it then
            $this->db->query("DELETE FROM playlistentries WHERE track=? AND playlist=? ", $track, $playlistId);
            return false;
        }
        $this->db->query("INSERT INTO playlistentries (track,playlist)VALUES(?,?)", $track, $playlistId);
        return true;
    }

    public function existInPlaylist($playlistId, $track) {
        $query = $this->db->query("SELECT id from playlistentries WHERE track=? AND playlist=?", $track, $playlistId);
        return $query->rowCount();
    }

    public function saveTracksOrder($id, $tracks) {
        $this->db->query("DELETE FROM playlistentries WHERE playlist=? ", $id);

        foreach($tracks as $track) {
            $this->addToPlaylist($id, $track);
        }

        return true;
    }

    public function findPlaylist($playList) {
        $userid = $this->C->model('user')->authId;
        $query = $this->db->query("SELECT * from playlist WHERE  id=? AND (public !=? OR userid=?)",$playList, 3, $userid);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function countPlaylistTracks($id) {
        $query = $this->db->query("SELECT * FROM playlistentries WHERE playlist=? ", $id);
        return $query->rowCount();
    }


    public function playlistUrl($playlist, $slug = '') {
        $url = 'set/'.$playlist['id'].'-'.toAscii($playlist['name']);
        if ($slug) $url .= '/'.$slug;
        return $this->C->request->url($url);
    }

    public function getPlaylists() {
        $userid  = $this->C->model('user')->authId;
        $track = $this->findTrack(Request::instance()->input('track'));
        if ($track['userid'] == model('user')->authId) {
            $query = $this->db->query("SELECT * from playlist WHERE userid=? ORDER BY time DESC", $userid);
        } else {
            $query = $this->db->query("SELECT * from playlist WHERE userid=? AND playlist_type=? ORDER BY time DESC", $userid, 1);
        }
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listPlaylists($type, $typeId, $limit = 20, $offset = 0) {
        $userid  = $this->C->model('user')->authId;
        $blockedIds = $this->C->model('user')->blockIds();
        if ($typeId == 'collection') {
            $sql = "SELECT * FROM playlist WHERE (userid=? OR id IN (SELECT typeid FROM likes WHERE type=? AND typeid=playlist.id AND userid=? )) AND playlist_type=? AND userid NOT IN ($blockedIds)";
            $sql .= " ORDER BY id DESC LIMIT $limit OFFSET $offset";
            $playlistType = ($type == 'playlist') ? 1 : 0;
            $query = $this->db->query($sql, $userid, 'playlist', $userid, $playlistType);
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($typeId == 'discover') {
            $sql = "SELECT *,(SELECT count(typeid) as types FROM likes WHERE type=? AND typeid=playlist.id) as count FROM playlist WHERE  playlist_type=? AND public = ? AND userid NOT IN ($blockedIds)";
            $sql .= " ORDER BY id DESC LIMIT $limit OFFSET $offset";
            $playlistType = ($type == 'playlist') ? 1 : 0;
            $query = $this->db->query($sql,'playlist', $playlistType, 1);
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } elseif(preg_match("#profile-#", $typeId)) {
            list($profile, $profileId)  = explode('-', $typeId);
            $sql = "SELECT * FROM playlist WHERE (userid=?) AND playlist_type=? AND (public != ? OR userid=?)  AND (public !=? OR userid=?) AND userid NOT IN ($blockedIds)";
            $sql .= " ORDER BY time DESC LIMIT $limit OFFSET $offset";
            $playlistType = ($type == 'playlist') ? 1 : 0;
            $query = $this->db->query($sql, $profileId, $playlistType, 3, $userid, 2, $userid);
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } elseif(preg_match("#search-#", $typeId)) {
            list($search, $term)  = explode('-', $typeId);
            $sql = "SELECT * FROM playlist WHERE (name LIKE ? OR description LIKE ? ) AND playlist_type=? AND public =? AND userid NOT IN ($blockedIds)";
            $sql .= " ORDER BY time DESC LIMIT $limit OFFSET $offset";
            $term = "%$term%";
            $playlistType = ($type == 'playlist') ? 1 : 0;
            $query = $this->db->query($sql, $term,$term, $playlistType, 1);
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    public function getPlaylistFirstTrack($id) {
        $query = $this->db->query("SELECT track FROM playlistentries WHERE playlist=?  ORDER BY id ASC", $id);
        if ($query) {
            $fetch = $query->fetch(PDO::FETCH_ASSOC);
            $track = $this->findTrack($fetch['track']);
            return $track;
        }
        return null;
    }

    public function getPlaylistTracks($id,$trackId, $limit = null, $offset = null) {
        $sql = "SELECT track FROM playlistentries WHERE playlist=? AND track !=?  ";

        $sql .= " ORDER BY id ASC";
        if ($limit) {
            $sql .= " LIMIT $limit OFFSET $offset ";
        }
        $query = $this->db->query($sql , $id, $trackId);

        $result = array();
        if ($query) {
            while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
                $track = $this->findTrack($fetch['track']);
                $result[] = $track;
            }
        }
        return $result;
    }
    public function getPlaylistArt($playlist, $user, $track = null, $size = 75) {
        if (isset($playlist['art']) and $playlist['art']) {
            return url_img($playlist['art'], $size);
        }
        $art = $this->C->model('user')->getAvatar($user, $size);
        if ($track) {
            $art = $this->getArt($track, $size);
        }
        return $art;
    }

    public function addToLater($track) {
        if ($this->existInLater($track)) {
            $this->db->query("DELETE FROM listen_later WHERE trackid=? AND listener=? ", $track, $this->C->model('user')->authId);
            return false;
        }
        return $this->db->query("INSERT INTO listen_later (trackid,listener)VALUES(?,?)", $track, $this->C->model('user')->authId);
    }

    public function existInLater($track) {
        $query = $this->db->query("SELECT * from listen_later WHERE trackid=? AND listener=?", $track, $this->C->model('user')->authId);
        return $query->rowCount();
    }

    public function countInLater() {
        $query = $this->db->query("SELECT * from listen_later WHERE listener=?", $this->C->model('user')->authId);
        return $query->rowCount();
    }

    public function addComment($val) {
        /**
         * @var $type
         * @var $id
         * @var $comment_text
         * @var $at
         * @var $replyto
         */
        $ex = array(
            'at' => '',
            'replyto' => ''
        );
        extract(array_merge($ex, $val));

        if ($at or $at == '0') {
            $type = 'track';
            if ($replyto) {
                $type = 'comment';
                $id = $replyto;
            }
        }
        $userid = $this->C->model('user')->authId;
        $time = time();
        $this->db->query("INSERT INTO comments (userid,type,typeid,message,track_at,time)VALUES(?,?,?,?,?,?)", $userid,$type,$id,$comment_text,$at,$time);
        $commentId = $this->db->lastInsertId();
        if ($type == 'comment') {
            $theComment = $this->findComment($id);
            if ($theComment['type'] == 'track') {
                $track  = $this->findTrack($theComment['typeid']);
                if ($theComment['userid'] != $this->C->model('user')->authId) {
                    $theUser = $this->C->model('user')->getUser($theComment['userid']);
                    if ($theUser['notifyc']) {
                        $this->C->model('user')->addNotification($theUser['id'], 'reply-comment-track', $id);
                        $this->C->model('user')->sendSocialMail($theUser, 'comment','reply-comment-track', $id);
                    }
                }
            } else{
                Hook::getInstance()->fire("comment.reply", null, array($commentId, $type, $id));
            }
        } else {
            if ($type == 'track') {
                $track  = $this->findTrack($id);
                if ($track['userid'] != $this->C->model('user')->authId) {
                    $theUser = $this->C->model('user')->getUser($track['userid']);
                    if ($theUser['notifyc']) {
                        $this->C->model('user')->addNotification($theUser['id'], 'comment-track', $id);
                        $this->C->model('user')->sendSocialMail($theUser, 'comment','comment-track', $id);
                    }
                }
            } else{
                Hook::getInstance()->fire("comment.added", null, array($commentId, $type, $id));
            }
        }

        return $commentId;
    }

    public function findComment($id) {
        $query = $this->db->query("SELECT * FROM comments WHERE id=?", $id);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function getComments($type, $typeId, $offset = 0, $limit = 0 ) {
        $limit = ($limit) ? $limit : config('comment-limit', 5);
        $blockedIds = $this->C->model('user')->blockIds();
        $sql = "SELECT * FROM comments WHERE type=? AND typeid=? AND userid NOT IN ($blockedIds) ";

        $reportedIds = $this->reportedCommentsId();
        if ($reportedIds) {
            $reportedIds = implode(',', $reportedIds);
            $sql .= " AND id NOT IN ($reportedIds) ";
        }

        $sql .= "ORDER BY id desc LIMIT {$limit} OFFSET {$offset}";
        $query = $this->db->query($sql, $type, $typeId);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTimeComments($typeId, $limit = 500) {
        $blockedIds = $this->C->model('user')->blockIds();
        $type = 'track';
        $sql = "SELECT * FROM comments WHERE type=? AND typeid=? AND userid NOT IN ($blockedIds)  AND track_at !=''";

        $reportedIds = $this->reportedCommentsId();
        if ($reportedIds) {
            $reportedIds = implode(',', $reportedIds);
            $sql .= " AND id NOT IN ($reportedIds) ";
        }

        $sql .= "ORDER BY id desc LIMIT {$limit} ";
        $query = $this->db->query($sql, $type, $typeId);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteComment($id, $isAdmin = false) {
        if (!$isAdmin) {
            $comment = $this->findComment($id);
            if ($comment['userid'] != $this->C->model('user')->authId) return false;
        }

        return $this->db->query("DELETE FROM comments WHERE id=? ", $id);
    }

    public function countComments($type, $typeId) {
        $query = $this->db->query("SELECT id FROM comments WHERE type=? AND typeid=? ", $type, $typeId);
        return $query->rowCount();
    }


    public function likeItem($type, $typeId) {
        $userid = $userid = $this->C->model('user')->authId;
        if ($this->hasLiked($type, $typeId)) {
            //delete the like
            $this->db->query("DELETE FROM likes WHERE type=? AND typeid=? AND userid=?", $type, $typeId, $userid);
            return false;
        } else {
            $this->db->query("INSERT INTO likes (typeid,type,userid,time) VALUES(?,?,?,?)", $typeId, $type, $userid,time());
            if ($type == 'track') {
                $track  = $this->findTrack($typeId);
                if ($track['userid'] != $this->C->model('user')->authId) {
                    $theUser = $this->C->model('user')->getUser($track['userid']);
                    if ($theUser['notifyl']) {
                        $this->C->model('user')->addNotification($theUser['id'], 'like-track', $typeId);
                    }
                    $this->C->model('user')->sendSocialMail($theUser, 'like','like-track', $typeId);
                }
            } elseif($type == 'playlist') {
                $playlist  = $this->findPlaylist($typeId);
                if ($playlist['userid'] != $this->C->model('user')->authId) {
                    $theUser = $this->C->model('user')->getUser($playlist['userid']);
                    if ($theUser['notifyl']) {
                        $this->C->model('user')->addNotification($theUser['id'], 'like-playlist', $typeId);
                    }
                    $this->C->model('user')->sendSocialMail($theUser, 'like','like-playlist', $typeId);
                }
            }

            Hook::getInstance()->fire("like.item", null, array($type,$typeId));
            return true;
        }
    }

    public function getLikes($type, $typeId, $limit = 15, $offset = 0) {
        $blockedIds = $this->C->model('user')->blockIds();
        $query = $this->db->query("SELECT * FROM likes INNER JOIN users ON users.id = likes.userid WHERE id NOT IN($blockedIds) AND type=? AND typeid=?  ORDER BY id DESC LIMIT {$limit} OFFSET {$offset}", $type, $typeId);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function hasLiked($type, $typeId) {
        $userid = $userid = $this->C->model('user')->authId;
        $query = $this->db->query("SELECT likeid FROM likes WHERE type=? AND typeid=? AND userid=?", $type, $typeId, $userid);
        return $query->rowCount();
    }
    public function countLikes($type, $typeId) {
        $query = $this->db->query("SELECT likeid FROM likes WHERE type=? AND typeid=? ", $type, $typeId);
        return $query->rowCount();
    }

    public function countViews($trackId) {
        $query = $this->db->query("SELECT DISTINCT(userid) as userid FROM views WHERE track=? ", $trackId);
        return $query->rowCount();
    }


    public function getReposts($id , $isPlaylist  = false, $limit = 10, $offset = 0) {
        $sql = "SELECT * FROM stream INNER JOIN users ON stream.poster=users.id WHERE ";
        $param = array();
        if ($isPlaylist) {
            $sql .= " playlist_id=? ";
            $param[] = $id;
            $sql .= " AND (action=? OR action=?)";
            $param[] = 'repost-playlist';
            $param[] = 'repost-album';
        } else {
            $sql .= " trackid=? ";
            $param[] = $id;
            $sql .= " AND (action=?)";
            $param[] = 'repost-track';
        }
        $sql .= " ORDER BY stream.streamid DESC LIMIT {$limit} OFFSET {$offset} ";
        $query = $this->db->query($sql, $param);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countReposts($id, $isPlaylist = false) {
        $sql = "SELECT * FROM stream  WHERE ";
        $param = array();
        if ($isPlaylist) {
            $sql .= " playlist_id=? ";
            $param[] = $id;
            $sql .= " AND (action=? OR action=?)";
            $param[] = 'repost-playlist';
            $param[] = 'repost-album';
        } else {
            $sql .= " trackid=? ";
            $param[] = $id;
            $sql .= " AND (action=?)";
            $param[] = 'repost-track';
        }

        $query = $this->db->query($sql, $param);
        return $query->rowCount();
    }

    public function addStream($trackId, $playlistId = 0, $action, $userid = null) {
        $userid = ($userid) ? $userid : $this->C->model('user')->authId;
        if ($playlistId != 0)  $trackId = 0;
        //@TODO prevent repost by user here as well
        if ($this->isInStream($trackId, $playlistId, $action)) {

            $this->db->query("DELETE FROM stream WHERE poster=? AND trackid=? AND action=? AND playlist_id=? ", $userid, $trackId, $action, $playlistId);
            return false;
        } else {
            $this->db->query("INSERT INTO stream (poster,trackid,action,playlist_id,streamtime)VALUES(?,?,?,?,?)",$userid, $trackId, $action, $playlistId, time());
            return true;
        }
    }

    public function isInStream($trackId, $playlistId, $action) {
        $userid = $this->C->model('user')->authId;
        if ($playlistId != 0)  $trackId = 0;
        $query = $this->db->query("SELECT *  FROM stream WHERE poster=? AND trackid=? AND action=? AND playlist_id=? ", $userid, $trackId, $action, $playlistId);
        return $query->rowCount();
    }

    public function hasReposted($trackId, $playListId, $action) {
        return $this->isInStream($trackId,$playListId,$action);
    }

    public function findStream($id) {
        $query = $this->db->query("SELECT poster,trackid,action,playlist_id,streamtime as time FROM stream WHERE streamid=?", $id);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function setViews($trackId) {
        $ip = get_ip();
        $userid = $this->C->model('user')->authId ? $this->C->model('user')->authId : 0;
        if ($this->hasView($userid, $trackId)) return false;
        if (model('user')->isLoggedIn()) $ip = '';
        $this->db->query("INSERT INTO views (userid,ip,track,time) VALUES(?,?,?,?)", $userid, $ip, $trackId, time());
    }

    public function hasView($userid, $trackId) {
        $ip = get_ip();
        if (!model('user')->isLoggedIn()) {
            $param = array($ip);
            $sql = " SELECT viewid FROM views WHERE ip=? AND track=? AND userid='0' ";
            $param[] = $trackId;
            $query = $this->db->query($sql, $param);
            return $query->rowCount();
        }
        $param = array($userid);
        $sql = " SELECT viewid FROM views WHERE userid=? AND track=? ";
        $param[] = $trackId;
        $query = $this->db->query($sql, $param);
        return $query->rowCount();
    }

    public function getViews($id ,$limit = 10, $offset = 0) {
        $sql = "SELECT DISTINCT(userid) as userid FROM views WHERE ";
        $param = array();
        $sql .= " track=? AND userid !=0 ";
        $param[] = $id;

        $sql .= " ORDER BY userid DESC LIMIT {$limit} OFFSET {$offset} ";

        $query = $this->db->query($sql, $param);
        return $result = $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPeople($type,$typeId='track', $trackId = null, $offset = 0, $limit = 0) {
        $limit = ($limit) ? $limit : config('users-limit', 20);
        switch ($type) {
            case 'likes':
                return $this->getLikes($typeId, $trackId,$limit, $offset);
                break;
            case 'reposters':
                return ($typeId == 'track') ? $this->getReposts($trackId, false,$limit, $offset) : $this->getReposts($trackId, true,$limit, $offset);
                break;
            case 'listeners':
                return $this->getViews($trackId,$limit, $offset);
                break;
            case 'followers':
                return $this->C->model('user')->getFollows($trackId,2,$limit, $offset);
                break;
            case 'following':
                return $this->C->model('user')->getFollows($trackId,1,$limit, $offset);
                break;
        }
        return array();
    }

    public function report($type, $typeId, $content) {
        $userid = $this->C->model('user')->authId;
        if (!$this->hasReported($type,$typeId,$userid)) {
            $this->db->query("INSERT INTO reports (userid,type,typeid,content,time)VALUES(?,?,?,?,?)", $userid,$type,$typeId,$content,time());
            return true;
        }
        return false;
    }

    public function hasReported($type, $typeId, $userid) {
        $query = $this->db->query("SELECT id FROM reports WHERE userid=? AND type=? AND typeid=?", $userid,$type,$typeId);
        return $query->rowCount();
    }
    public function reportedCommentsId() {
        $query = $this->db->query("SELECT typeid FROM reports WHERE  type=? ", 'comment');
        $ids = array();
        while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
            $ids[] = $fetch['typeid'];
        }
        return $ids;
    }

    public function countGenreTracks($id) {
        $query = $this->db->query("SELECT id FROM tracks WHERE genre=? ", $id);
        return $query->rowCount();
    }

    public function searchTags($term = null) {
        if (!$term) return json_encode(array());
        $query = $this->db->query("SELECT * FROM tags WHERE title LIKE ? ", "%$term%");
        $result = array();
        while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
            $result[] = array('value' => $fetch['title'], 'text' => $fetch['title']);
        }
        return json_encode($result);
    }

    public function searchTrackTags($term = null, $playlistToo  = true) {
        if (!$term) return json_encode(array());
        $query = $this->db->query("SELECT * FROM tracks WHERE title LIKE ? AND userid=? ", "%$term%", model('user')->authId);
        $result = array();
        while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
            $result[] = array('value' => ($playlistToo) ? 'track-'.$fetch['id'] : $fetch['id'], 'text' => str_limit($fetch['title'], 30));
        }
        if ($playlistToo) {
            //query playlist as well
            $query = $this->db->query("SELECT * FROM playlist WHERE public=? AND userid=?", 1, model('user')->authId);
            while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
                $result[] = array('value' => 'playlist-'.$fetch['id'], 'text' => str_limit($fetch['name'], 30));
            }
        }
        return json_encode($result);
    }

    public function addDownload($id) {
        $this->db->query("INSERT INTO downloads (userid,track,time)VALUES(?,?,?)", $this->C->model('user')->authId, $id, time());
    }

    public function getMyTracksId() {
        $query = $this->db->query("SELECT id FROM tracks WHERE userid=?", $this->C->model('user')->authId);
        $ids = array();
        while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
            $ids[] = $fetch['id'];
        }
        return $ids;
    }

    public function countTrackStatistics($id, $start, $end = null) {
        $end = ($end) ? $end : time();
        $tracks = ($id != 'all') ? array($id) : $this->getMyTracksId();
        $tracks[] = 0;
        $tracks = implode(',', $tracks);

        $query = $this->db->query("SELECT(SELECT COUNT(track)  FROM views WHERE track IN ($tracks) AND time BETWEEN $start AND $end) as plays,
        (SELECT COUNT(track) FROM downloads WHERE track IN ($tracks) AND time BETWEEN $start AND $end) as downloads,
        (SELECT COUNT(typeid) FROM likes WHERE type='track' AND typeid IN ($tracks) AND time BETWEEN $start AND $end) as likes,
        (SELECT COUNT(typeid) FROM comments WHERE type='track' AND typeid IN ($tracks) AND time BETWEEN $start AND $end) as comments");
        return $query->fetch(PDO::FETCH_ASSOC);

    }

    public function getStatisticsTracks($type, $start, $limit = 10, $id = null) {
        $tracks =  $this->getMyTracksId();
        $tracks[] = 0;
        if ($id) $tracks = array($id);
        $end = time();
        $tracks = implode(',', $tracks);
        switch($type) {
            case 'plays':
                $query = $this->db->query("SELECT views.track,tracks.title,tracks.art,tracks.slug,tracks.id,COUNT(views.userid) as count FROM views,tracks WHERE views.track IN ($tracks) AND views.track=tracks.id AND views.time BETWEEN $start AND $end GROUP BY views.track,tracks.title,tracks.art ORDER BY count DESC LIMIT $limit ");
                return $query->fetchAll(PDO::FETCH_ASSOC);
                break;
            case 'likes':
                $query = $this->db->query("SELECT likes.typeid,tracks.title,tracks.art,tracks.slug,tracks.id,COUNT(likes.userid) as count FROM likes,tracks WHERE likes.type='track' AND likes.typeid IN ($tracks) AND likes.typeid=tracks.id AND likes.time BETWEEN $start AND $end GROUP BY likes.typeid,tracks.title,tracks.art ORDER BY count DESC LIMIT $limit ");
                return $query->fetchAll(PDO::FETCH_ASSOC);
                break;
            case 'comments':
                $query = $this->db->query("SELECT comments.typeid,tracks.title,tracks.art,tracks.slug,tracks.id,COUNT(comments.userid) as count FROM comments,tracks WHERE comments.type='track' AND comments.typeid IN ($tracks) AND comments.typeid=tracks.id AND comments.time BETWEEN $start AND $end GROUP BY comments.typeid,tracks.title,tracks.art ORDER BY count DESC LIMIT $limit ");
                return $query->fetchAll(PDO::FETCH_ASSOC);
                break;
            case 'users.played':
                $query = $this->db->query("SELECT views.userid,users.id,users.full_name,users.avatar,users.username,COUNT(views.userid) as count FROM views,users WHERE views.track IN ($tracks) AND views.userid=users.id AND views.time BETWEEN $start AND $end GROUP BY views.userid,users.id,users.full_name,users.username,users.avatar ORDER BY count DESC LIMIT $limit ");
                return $query->fetchAll(PDO::FETCH_ASSOC);
                break;
            case 'users.download':
                $query = $this->db->query("SELECT downloads.userid,users.id,users.full_name,users.avatar,users.username,COUNT(downloads.userid) as count FROM downloads,users WHERE downloads.track IN ($tracks) AND downloads.userid=users.id AND downloads.time BETWEEN $start AND $end GROUP BY downloads.userid,users.id,users.full_name,users.username,users.avatar ORDER BY count DESC LIMIT $limit ");
                return $query->fetchAll(PDO::FETCH_ASSOC);
                break;
            case 'geo.country':
                $query = $this->db->query("SELECT users.country,COUNT(users.country) as count FROM views,users WHERE views.track IN ($tracks) AND views.userid=users.id AND users.country!='' AND views.time BETWEEN $start AND $end GROUP BY users.country ORDER BY count DESC LIMIT $limit ");
                return $query->fetchAll(PDO::FETCH_ASSOC);
                break;
            case 'geo.city':
                $query = $this->db->query("SELECT users.city,COUNT(users.city) as count FROM views,users WHERE views.track IN ($tracks) AND views.userid=users.id AND users.city!='' AND views.time BETWEEN $start AND $end GROUP BY users.city ORDER BY count DESC LIMIT $limit ");
                return $query->fetchAll(PDO::FETCH_ASSOC);
                break;
        }
    }

    public function getStrTime($time) {
        switch($time) {
            case 'today':
                return strtotime('today');
                break;
            case '7days':
                return strtotime('-7 day');
                break;
            case '30days':
                return strtotime('-30 day');
                break;
            case '12month':
                return strtotime('-12 month');
                break;
            case 'total':
                return strtotime('first day of January 2018');
                break;
        }
    }

    public function countDownloads($id) {
        $query = $this->db->query("SELECT id FROM downloads WHERE track=? ", $id);
        return $query->rowCount();
    }

    public function getTrackFeaturing($track) {
        $result = '';
        if ($track['featuring']) {
            $result .= '<strong>'.l('feat').'.</strong> ';
            $features = explode(',', $track['featuring']);
            $v = '';
            foreach($features as $feature) {
                $v .= ($v) ? ', '.'<a class="colored" style="font-weight:bold" data-ajax="true" href="'.url('search', array('term' => $feature)).'">'.$feature.'</a>' : '<a style="font-weight:bold" class="colored" data-ajax="true" href="'.url('search', array('term' => $feature)).'">'.$feature.'</a>';
            }
            $result .= $v;
        }
        return $result;
    }

    public function replaceWaveImages($limit = 50, $offset = 0, $type = 0) {
        $query = $this->db->query("SELECT track_file,demo_file,id,wave,wave_colored,demo_wave,demo_wave_colored FROM tracks ORDER BY id DESC LIMIT $limit OFFSET $offset");
        $result = false;
        while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
            try {
                $wave = $fetch['wave'];
                $waveColored = $fetch['wave_colored'];
                $demowave = $fetch['demo_wave'];
                $demowaveColored = $fetch['demo_wave_colored'];
                $theme = config('theme', 'main');
                list($baseR,$baseG,$baseB) = hexToRgb(config($theme.'-'.'wave_color', '#0F0710'));
                list($topR, $topG, $topB) = hexToRgb(config($theme.'-'.'wave_colored', '#FF5533'));

                $color = config($theme.'-'.'wave_color', '#0F0710');
                $colored = config($theme.'-'.'wave_colored', '#FF5533');

                $trackFile = path(getController()->getFileUri($fetch['track_file'], false));
                $demoFile = ($fetch['demo_file']) ? path(getController()->getFileUri($fetch['demo_file'], false)) : null;
                $scale = 'lin';
                $toDelete = null;
                if (preg_match('#http#', $trackFile)) {
                    $trackFile = downloadUrlContent($trackFile);
                    $toDelete = path($trackFile);
                }
                if (($fetch['wave'] and file_exists(path($fetch['wave']))) or $type) {
                    $dir = md5($fetch['wave']);
                    if (!is_dir(path('uploads/waves/'.$dir))) {
                        @mkdir(path('uploads/waves/'.$dir), 0777, true);
                        $file = @fopen(path("uploads/waves/$dir/index.html"), 'x+');
                        fclose($file);
                    }
                    $wave = 'uploads/waves/'.$dir.'/wave_base'.time().'.png';
                    if (!$type) {
                        colorizeKeepAplhaChannnel(path($fetch['wave']),  $baseR, $baseG, $baseB, path($wave));
                    } else {
                        $output = path($wave);
                        $hookedResult = Hook::getInstance()->fire('wave.auto.generator', array('override' => false), array(
                            'input' => $trackFile,
                            'wave' => $wave,
                            'colored' => false
                        ));
                        if (!$hookedResult['override']) {
                            $output  = shell_exec("".config('ffmpeg-path')." -y -i $trackFile -filter_complex \"aformat=channel_layouts=mono,compand=gain=-3,showwavespic=s=600x120:scale=$scale:colors=".$color.",drawbox=x=(iw-w)/2:y=(ih-h)/2:w=iw:h=1:color=white\" -frames:v 1 $output 2>&1");
                        } else {
                            $wave = $hookedResult['wave'];
                        }
                    }
                    delete_file(path($fetch['wave']));
                }
                if (($fetch['wave_colored']  and file_exists(path($fetch['wave_colored']))) or $type)  {
                    $dir = md5($fetch['wave_colored']);
                    if (!is_dir(path('uploads/waves/'.$dir))) {
                        @mkdir(path('uploads/waves/'.$dir), 0777, true);
                        $file = @fopen(path("uploads/waves/$dir/index.html"), 'x+');
                        fclose($file);
                    }
                    $waveColored = 'uploads/waves/'.$dir.'/wave_top'.time().'.png';
                    if (!$type) {
                        colorizeKeepAplhaChannnel(path($fetch['wave_colored']),  $topR, $topG, $topB, path($waveColored));
                    } else {
                        $output = path($waveColored);
                        $hookedResult = Hook::getInstance()->fire('wave.auto.generator', array('override' => false), array(
                            'input' => $trackFile,
                            'wave' => $waveColored,
                            'colored' => true
                        ));
                        if (!$hookedResult['override']) {
                            shell_exec("".config('ffmpeg-path')." -y -i $trackFile -filter_complex \"aformat=channel_layouts=mono,compand=gain=-3,showwavespic=s=600x120:scale=$scale:colors=".$colored.",drawbox=x=(iw-w)/2:y=(ih-h)/2:w=iw:h=1:color=white\" -frames:v 1 $output 2>&1");
                        } else {
                            $waveColored = $hookedResult['wave'];
                        }
                    }
                    delete_file(path($fetch['wave_colored']));
                }

                if ($fetch['demo_file']) {
                    $dir = md5($fetch['demo_wave']);
                    if (!is_dir(path('uploads/waves/'.$dir))) {
                        @mkdir(path('uploads/waves/'.$dir), 0777, true);
                        $file = @fopen(path("uploads/waves/$dir/index.html"), 'x+');
                        fclose($file);
                    }
                    $demowave = 'uploads/waves/'.$dir.'/wave_base'.time().'.png';

                    if (!$type) {
                        colorizeKeepAplhaChannnel(path($fetch['demo_wave']),  $baseR, $baseG, $baseB, path($demowave));
                    } else {
                        $output = path($demowave);
                        $hookedResult = Hook::getInstance()->fire('wave.auto.generator', array('override' => false), array(
                            'input' => $demoFile,
                            'wave' => $demowave,
                            'colored' => false
                        ));
                        if (!$hookedResult['override']) {
                            shell_exec("".config('ffmpeg-path')." -y -i $demoFile -filter_complex \"aformat=channel_layouts=mono,compand=gain=-3,showwavespic=s=600x120:scale=$scale:colors=".$color.",drawbox=x=(iw-w)/2:y=(ih-h)/2:w=iw:h=1:color=white\" -frames:v 1 $output 2>&1");
                        } else {
                            $demowave = $hookedResult['wave'];
                        }
                    }
                    delete_file(path($fetch['demo_wave']));
                }

                if ($fetch['demo_file']) {
                    $dir = md5($fetch['demo_wave_colored']);
                    if (!is_dir(path('uploads/waves/'.$dir))) {
                        @mkdir(path('uploads/waves/'.$dir), 0777, true);
                        $file = @fopen(path("uploads/waves/$dir/index.html"), 'x+');
                        fclose($file);
                    }
                    $demowaveColored = 'uploads/waves/'.$dir.'/wave_top'.time().'.png';

                    if (!$type) {
                        colorizeKeepAplhaChannnel(path($fetch['demo_wave_colored']),  $topR, $topG, $topB, path($demowaveColored));
                    } else {
                        $output = path($demowaveColored);
                        $hookedResult = Hook::getInstance()->fire('wave.auto.generator', array('override' => false), array(
                            'input' => $demoFile,
                            'wave' => $demowaveColored,
                            'colored' => true
                        ));
                        if (!$hookedResult['override']) {
                            shell_exec("".config('ffmpeg-path')." -y -i $demoFile -filter_complex \"aformat=channel_layouts=mono,compand=gain=-3,showwavespic=s=600x120:scale=$scale:colors=".$colored.",drawbox=x=(iw-w)/2:y=(ih-h)/2:w=iw:h=1:color=white\" -frames:v 1 $output 2>&1");
                        } else {
                            $demowaveColored = $hookedResult['wave'];
                        }
                    }
                    delete_file(path($fetch['demo_wave_colored']));
                }

                if ($toDelete) delete_file(path($toDelete));
                $this->db->query("UPDATE tracks SET wave=?,wave_colored=?,demo_wave=?,demo_wave_colored=? WHERE id=? ", $wave,$waveColored,$demowave,$demowaveColored, $fetch['id']);
                $result = true;
            } catch (Exception $e){}
        }
        return $result;
    }

    public function countTotalTracks() {
        $query = $this->db->query("SELECT id FROM tracks");
        return $query->rowCount();
    }

    public function getRandomTrackArtByGenre($genre) {
        $query = $this->db->query("SELECT * FROM tracks WHERE genre=? AND art !='' ORDER BY rand() LIMIT 1", $genre);
        return $query->fetch(PDO::FETCH_ASSOC);
    }
}