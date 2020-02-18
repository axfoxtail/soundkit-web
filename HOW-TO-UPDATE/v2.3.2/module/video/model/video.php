<?php

class VideoModel extends Model {
    public function canAddVideo($user = null) {
        //if (!model('user')->isLoggedIn()) return false;
        $option = config('can-add-video', 1);
        if ($option == 1) return true;
        $user = ($user) ? $user : model('user')->authUser;

        if ($option == 2) {
            if ($user['user_type'] == 2) return true;
        }
        if ($option == 3) {
            if (model('user')->subscriptionActive($user['id'])) return true;
        }
        return false;
    }

    public function getSupportedTypes() {
        if (config('ffmpeg-path', '') == '') return 'mp4';
        return config('video-file-types', 'mp4,mov,wmv,3gp,avi,flv,f4v,webm');
    }

    public function canSell() {
        $option = config('allow-video-sell', 1);
        if($option == 0) return false;

        $user = model('user')->authUser;
        if ($option == 1) {
            if ($user['user_type'] == 2) return true;
        }
        if ($option == 2) {
            if (model('user')->subscriptionActive()) return true;
        }
        return false;
    }

    public function getMyVideosId($id = null) {
        $id = ($id) ? $id : $this->C->model('user')->authId;
        $query = $this->db->query("SELECT id FROM videos WHERE userid=?", $id);
        $ids = array();
        while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
            $ids[] = $fetch['id'];
        }
        return $ids;
    }

    public function deleteUserVideos($id) {
        $videos = $this->getMyVideosId($id);
        foreach($videos as $video) {
            $this->delete($video);
        }
        return true;
    }

    public function countStatistics($id, $start, $end = null) {
        $end = ($end) ? $end : time();
        $videos = ($id != 'all') ? array($id) : $this->getMyVideosId();
        $videos[] = 0;
        $videos = implode(',', $videos);

        $query = $this->db->query("SELECT(SELECT COUNT(videoid)  FROM video_plays WHERE videoid IN ($videos) AND time BETWEEN $start AND $end) as plays,
        (SELECT COUNT(typeid) FROM likes WHERE type='video' AND typeid IN ($videos) AND time BETWEEN $start AND $end) as likes,
        (SELECT COUNT(typeid) FROM comments WHERE type='track' AND typeid IN ($videos) AND time BETWEEN $start AND $end) as comments");
        return $query->fetch(PDO::FETCH_ASSOC);

    }

    public function getStatistics($type, $start, $limit = 10, $id = null) {
        $videos =  $this->getMyVideosId();
        $videos[] = 0;
        if ($id) $videos = array($id);
        $end = time();
        $videos = implode(',', $videos);
        switch($type) {
            case 'plays':
                $query = $this->db->query("SELECT video_plays.videoid,videos.slug,videos.title,videos.art,videos.id,COUNT(video_plays.userid) as count FROM video_plays,videos WHERE video_plays.videoid IN ($videos) AND video_plays.videoid=videos.id AND video_plays.time BETWEEN $start AND $end GROUP BY video_plays.videoid,videos.title,videos.art ORDER BY count DESC LIMIT $limit ");
                return $query->fetchAll(PDO::FETCH_ASSOC);
                break;
            case 'likes':
                $query = $this->db->query("SELECT likes.typeid,videos.title,videos.slug,videos.art,videos.id,COUNT(likes.userid) as count FROM likes,videos WHERE likes.type='video' AND likes.typeid IN ($videos) AND likes.typeid=videos.id AND likes.time BETWEEN $start AND $end GROUP BY likes.typeid,videos.title,videos.art ORDER BY count DESC LIMIT $limit ");
                return $query->fetchAll(PDO::FETCH_ASSOC);
                break;
            case 'comments':
                $query = $this->db->query("SELECT comments.typeid,videos.title,videos.slug,videos.art,videos.id,COUNT(comments.userid) as count FROM comments,videos WHERE comments.type='video' AND comments.typeid IN ($videos) AND comments.typeid=videos.id AND comments.time BETWEEN $start AND $end GROUP BY comments.typeid,videos.title,videos.art ORDER BY count DESC LIMIT $limit ");
                return $query->fetchAll(PDO::FETCH_ASSOC);
                break;
        }
    }

    public function addVideo($val, $videoFile = false, $upload = false) {
        $exp = array(
            'title' => '',
            'desc' => '',
            'genre' => '',
            'price' => 0,
            'video_demo' => '',
            'video' => '',
            'tags' => '',
            'privacy' => '',
            'art' => '',
            'comments' => '',
            'video_link' => '',
            'video_source' => '',
            'duration' => '',
            'track_id' => '',
            'userid' => ''
        );

        /**
         * @var $title
         * @var $desc
         * @var $genre
         * @var $price
         * @var $video_demo
         * @var $video
         * @var $tags
         * @var $privacy
         * @var $art
         * @var $comments
         * @var $video_link
         * @var $video_source
         * @var $duration
         * @var $track_id
         * @var $userid
         */
        extract(array_merge($exp, $val));

        $userid = ($userid and model('user')->isAdmin()) ? $userid : model('user')->authId;
        if ($videoFile) {

            $videoId = $videoFile['id'];
            $art = ($art) ? $art : $videoFile['art'];
            $this->db->query("UPDATE videos SET title=?,description=?,genre=?,tags=?,art=?,public=?,comments=? WHERE id=?", $title,$desc,$genre,$tags,$art,$privacy,$comments,$videoId);
            if ($this->canSell()) {
                $this->db->query("UPDATE videos SET price=? WHERE id=? ", $price, $videoId);
            }
            if ($track_id) {
                $track = model('track')->findTrack($track_id);
                if ($track['userid'] == $userid) {
                    $this->db->query("UPDATE videos SET track_id=? WHERE id=? ", $track_id, $videoId);
                }
            }
        } else {
            $slug  = uniqueKey(15,15);
            while($this->slugExists($slug)) {
                $slug = uniqueKey(15,15);
            }

            $query = $this->db->query("INSERT INTO videos (userid,title,description,genre,tags,art,upload_file,public,duration,slug,video_link,video_source,comments,time)VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                $userid,$title,$desc,$genre,$tags,$art,$video,$privacy,$duration,$slug,$video_link,$video_source,$comments,time());
            $videoId = $this->db->lastInsertId();
            if ($video and $this->canSell()) {
                $this->db->query("UPDATE videos SET price=? WHERE id=? ", $price,  $videoId);
            }

            if ($track_id) {
                $track = model('track')->findTrack($track_id);
                if ($track['userid'] == $userid) {
                    $this->db->query("UPDATE videos SET track_id=? WHERE id=? ", $track_id, $videoId);
                }
            }
        }

        return $videoId;
    }

    public function slugExists($slug) {
        $query = $this->db->query("SELECT id FROM videos WHERE slug =? ", $slug);
        return $query->rowCount();
    }

    public function find($id) {
        $query = $this->db->query("SELECT * FROM videos WHERE id =?", $id);
        return $query->fetch(PDO::FETCH_ASSOC);
    }
    public function findBySlug($id) {
        $query = $this->db->query("SELECT * FROM videos WHERE slug =?", $id);
        return $query->fetch(PDO::FETCH_ASSOC);
    }
    public function videoUrl($video, $segments = '') {
        return url('watch/'.$video['slug'].$segments);
    }

    public function getArt($video, $size = 200) {
        $art = ($video['art']) ? $video['art'] : 'assets/images/video.png';
        return url_img($art, $size);
    }

    public function getPlayerDetails($video) {
        $result = array(
            'src' => '',
            'type' => '',
        );
        if ($video['upload_file']) {
            if (moduleExists('store') and $video['price'] > 0 and !model('store::store')->hasPurchased($video['id'], 'video')) {
                //it depends if user can play now

                if (!config('video-allow-play-before-buy', true)) return $result;
                if ($video['demo_file']) {
                    $result['src'] = $this->C->getFileUri($video['demo_file']);
                    $result['type'] = "video/mp4";
                    return $result;
                }
            }
            $result['src'] = $this->C->getFileUri($video['upload_file']);
            $result['type'] = "video/mp4";
            return $result;
        } else {
            switch($video['video_source']) {
                case 'youtube':
                    $result['type'] = 'video/youtube';
                    $result['src'] = 'https://www.youtube.com/watch?v='.$video['video_link'];
                    break;
                case 'vimeo':
                    $result['type'] = 'video/vimeo';
                    $result['src'] = 'https://player.vimeo.com/video/'.$video['video_link'];
                    break;
                case 'dailymotion':
                    $result['type'] = 'video/dailymotion';
                    $result['src'] = 'https://www.dailymotion.com/video/'.$video['video_link'];
                    break;
                case 'facebook':
                    $result['type'] = 'video/facebook';
                    $result['src'] = 'https://www.facebook.com/facebook/videos/'.$video['video_link'];
                    break;
                case 'mp4':
                    $result['type'] = 'video/mp4';
                    $result['src'] = $video['video_link'];
                    break;
            }
        }

        return $result;
    }

    public function countPlays($id) {
        $query = $this->db->query("SELECT videoid FROM video_plays WHERE videoid=?", $id);
        return $query->rowCount();
    }

    public function addPlays($videoId) {
        $userid = model('user')->authId;
        $query = $this->db->query("SELECT * FROM video_plays WHERE userid=? AND videoid=? ", $userid, $videoId);
        if ($query->rowCount() < 1) {
            $this->db->query("INSERT INTO video_plays (userid,videoid,time) VALUES(?,?,?)", $userid, $videoId,time());
            $video = $this->find($videoId);
            if ($video['price'] > 0 and $video['userid'] != model('user')->authId) {
                if (config('artist-play-share', '0.001') > 0) {
                    $user = model('user')->getUser($video['userid']);
                    $balance = $user['balance'] + config('artist-play-share', '0.001');
                    Database::getInstance()->query("UPDATE users SET balance=? WHERE id=?", $balance, $user['id']);
                }
            }
        }
    }
    public function updateViews($video) {
        $views = $video['views'] + 1;
        return $this->db->query("UPDATE videos SET views=? WHERE id=?", $views, $video['id']);
    }

    public function getSuggestedVideos($videoId = '', $limit = 5) {
        $sql = "SELECT * FROM videos WHERE id !='' ";
        $param = array();
        if ($videoId) {
            $sql .= " AND id != ? ";
            $param[] = $videoId;
        }
        $sql .= " ORDER BY rand() LIMIT $limit ";
        $query = $this->db->query($sql, $param);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function delete($video) {
        $video = $this->find($video);
        if ($video['art']) {
            delete_file(path($video['art']));
        }

        if ($video['upload_file']) {
            $this->C->deleteFile($video['upload_file']);
        }

        $this->db->query("DELETE FROM likes WHERE type=? AND typeid=?", 'video', $video['id']);
        $this->db->query("DELETE FROM comments WHERE type=? AND typeid=?", 'video', $video['id']);
        $this->db->query("DELETE FROM video_plays WHERE  videoid=?", $video['id']);
        $this->db->query("DELETE FROM videos WHERE  id=?", $video['id']);
        return true;
    }

    public function lastVideo() {
        $query = $this->db->query("SELECT * FROM videos ORDER BY id DESC LIMIT 1");
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function getVideos($type, $typeId = null, $offset = 0, $limit = 10) {
        $sql = "SELECT * FROM videos WHERE id != '' ";
        $param = array();
        $blockedIds = model('user')->blockIds();
        $expiredArtists = model('track')->getExpiredArtists();

        switch($type) {
            case 'search':
                $sql .= " AND public=? ";
                $sql .= " AND (title LIKE ? OR description LIKE ? ) AND  userid NOT IN ($blockedIds) AND userid NOT IN ($expiredArtists)";
                $param[] = 1;
                if ($typeId) {
                    $param[] = "%$typeId%";
                    $param[] = "%$typeId%";
                    $sql .= " ORDER BY id DESC ";
                } else {
                    return array();
                }
                break;
            case 'latest':
                $sql .= " AND public=? ";
                if ($typeId) $sql .= " AND price > 0 AND  userid NOT IN ($blockedIds) AND userid NOT IN ($expiredArtists)";
                $param[] = 1;
                $sql .= " ORDER BY id DESC ";
                break;
            case 'category':
                $sql .= " AND public=? AND genre=? AND  userid NOT IN ($blockedIds) AND userid NOT IN ($expiredArtists)";
                $param[] = 1;
                $param[] = $typeId;
                $sql .= " ORDER BY id DESC ";
                break;
            case 'top':
                $sql = "SELECT *,(SELECT count(videoid) FROM video_plays WHERE video_plays.videoid=videos.id ) as count FROM videos WHERE public=?  AND  userid NOT IN ($blockedIds) AND userid NOT IN ($expiredArtists)";
                $sql .= " ORDER BY count DESC ";
                $param[] = 1;
                break;
            case 'later':
                $ids = implode(',', $this->getLaterIds());
                $sql .= " AND id IN ($ids) ";
                $sql .= " ORDER BY id DESC ";
                break;
            case 'history':
                $ids = implode(',',$this->getHistoryIds());
                $sql .= " AND id IN ($ids) ";
                $sql .= " ORDER BY id DESC ";
                break;
            case 'profile':
                $sql .= " AND userid=? ";
                $param[] = $typeId;
                if ($typeId != model('user')->authId) {
                    $sql .= " AND public=? ";
                    $param[] = 1;
                }
                $sql .= " ORDER BY id DESC ";
                break;
        }
        if (!$sql) {
            return array();
        } else {
            $sql .= " LIMIT $limit OFFSET $offset";
            $query = $this->db->query($sql, $param);
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    public function addLater($id) {
        $userid = model('user')->authId;
        if ($this->laterExists($id)) {
            $this->db->query("DELETE FROM watch_later WHERE videoid=? AND userid=?", $id, $userid);
            return false;
        } else {
            $this->db->query("INSERT INTO watch_later (userid,videoid)VALUES(?,?)", $userid, $id);
            return true;
        }
    }

    public function laterExists($id) {
        $userid = model('user')->authId;
        $query = $this->db->query("SELECT * FROM watch_later WHERE videoid=? AND userid=?", $id, $userid);
        return $query->rowCount();
    }

    public function getLaterIds() {
        $userid = model('user')->authId;
        $query = $this->db->query("SELECT videoid FROM watch_later WHERE userid=?", $userid);
        $ids = array(0);
        while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
            $ids[] = $fetch['videoid'];
        }
        return $ids;
    }

    public function getHistoryIds() {
        $userid = model('user')->authId;
        $query = $this->db->query("SELECT videoid FROM video_plays WHERE userid=?", $userid);
        $ids = array(0);
        while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
            $ids[] = $fetch['videoid'];
        }
        return $ids;
    }

    public function getAdminVideos($genre, $term, $user) {
        $sql = "SELECT * FROM videos WHERE id!=? ";
        $param = array('');
        if ($genre) {
            $sql .= " AND genre=? ";
            $param[] = $genre;
        }
        if ($user) {
            $sql .= " AND userid=? ";
            $param[] = $user;
        }
        if ($term ) {
            $term = '%'.$term.'%';
            $sql .= " AND (title LIKE ?) ";
            $param[] = $term;
        }

        $sql .= " ORDER BY id DESC ";
        return $this->db->paginate($sql, $param, 10);
    }

    public function getTrackVideo($track) {
        $query = $this->db->query("SELECT * FROM videos WHERE track_id=? ", $track);
        return $query->fetch(PDO::FETCH_ASSOC);
    }
}