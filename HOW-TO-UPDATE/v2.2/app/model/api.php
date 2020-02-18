<?php
class ApiModel extends Model {
    public function userKeyExists($userid, $key) {
        $query = $this->db->query("SELECT id FROM mobile_keys WHERE userid=? AND apikey=? ", $userid, $key);
        return $query->rowCount();
    }

    public function generateKey($userid, $device) {
        $key = generateHash($userid.$device);
        $this->db->query("INSERT INTO mobile_keys (userid,device,apikey) VALUES(?,?,?)", $userid,$device,$key);
        return $key;
    }

    public function formatUser($user, $full = false) {
        $result = array(
            'id' => $user['id'],
            'avatar' => model('user')->getAvatar($user, 200),
            'cover' => model('user')->getCover($user),
            'username' => $user['username'],
            'full_name' => $user['full_name'],
            'bio' => $user['bio'],
            'country' => $user['country'],
            'city' => $user['city'],
            'website' => $user['website'],
            'facebook' => $user['facebook'],
            'twitter' => $user['twitter'],
            'youtube' => $user['youtube'],
            'vimeo' => $user['vimeo'],
            'soundcloud' => $user['soundcloud'],
            'gender' => $user['gender'],
            'isOwner' => ($user['id'] == model('user')->authId) ? 1 : 0,
            'link' => model('user')->profileUrl($user),
            'is_following' => (model('user')->isLoggedIn() and model('user')->isFollowing($user['id'])) ? 1 : 0,
            'has_premium' => ((config('enable-premium-listeners', false) and model('user')->listenerSubscriptionActive()) or
                (config('enable-premium-listeners', false) and config('enable-premium', false) and config('allow-premium-artists', true) and  model('user')->subscriptionActive())) ? 1 : 0

        );

        if ($full) {
            $result = array_merge($user, $result);
        }

        return $result;
    }

    private function canPlay($track) {
        if ($track['price'] == '0.00') return true;
        $price = (float) $track['price'];
        if (moduleExists('store')  and $price > 0) {
            if (model('store::store')->hasPurchased($track['id'], 'track',false)) {
                return true;
            }
            return false;
        }
        return true;
    }

    public function formatTrack($track, $type, $typeId) {
        if ($type == 'feed' or $type == 'my-stream' or $type == 'my-reposts') {
            $stream = model('track')->findStream($track['id']);
            if ($stream['playlist_id']) {
                $track = model('track')->getPlaylistFirstTrack($stream['playlist_id']);
            } else {
                $track = model('track')->findTrack($stream['trackid']);
            }
            if ($track) {
                $track = array_merge($stream, $track);
            }
            if ($track['public'] == 3) $track = false;
        } else {
            $result = Hook::getInstance()->fire('get.lists.track', array('track' => null), array($type, $typeId, $track));
            if ($result['track']) {
                $track = $result['track'];
            }
        }

        if (!$track or !isset($track['art'])) return false;
        if (isset($track['playlist_id'])) {
            $playlist = model('track')->findPlaylist($track['playlist_id']);
            $track['playlist'] = $this->formatPlaylist($playlist);
        }
        $track['art'] = model('track')->getArt($track, 200);
        $track['art_md'] = model('track')->getArt($track, 600);
        $track['art_lg'] = model('track')->getArt($track, 920);
        $track['lyrics'] = ($track['lyrics']) ? file_get_contents(path($track['lyrics'])) : '';
        $track['time'] = date('c', $track['time']);
        $track['wave'] = assetUrl($track['wave']);
        $track['wave_colored'] = assetUrl($track['wave_colored']);
        $track['canDownload'] = model('track')->canDownload($track);
        $track['duration'] = model('track')->formatDuration($track['track_duration']);
        $track['canShare'] = ($track['embed'] and $track['public'] != 2) ? 1 : 0;
        $track['canRepost'] = 1;
        $track['likeCount'] = model('track')->countLikes('track', $track['id']);
        $track['repostCount'] = model('track')->countReposts($track['id']);
        $track['downloadsCount'] = model('track')->countDownloads($track['id']);
        $track['viewCount'] = model('track')->countViews($track['id']);
        $track['commentsCount'] = model('track')->countComments('track', $track['id']);
        $track['hasLiked'] = model('track')->hasLiked('track', $track['id']) ? 1 : 0;
        $track['hasReposted'] = model('track')->hasReposted($track['id'], 0, 'reposted') ? 1 : 0;
        $track['isOwner'] = ($track['userid'] == model('user')->authId) ? 1 : 0;
        $track['actionType'] = '';
        $track['actionId'] = '';
        $track['repostAction'] = '';
        $track['canPlay'] = ($this->canPlay($track)) ? 1 : 0;

        $track = Hook::getInstance()->fire("play.now", $track);
        $track['streamurl'] = getController()->getFileUri($track['track_file'], true);;

        $user = model('user')->getUser($track['userid']);
        $reposter = (isset($track['poster'])) ? model('user')->getUser($track['poster']) : $user;
        $track['user'] = $this->formatUser($user);
        $track['reposter'] = $this->formatUser($reposter);
        $track['link'] = model('track')->trackUrl($track);

        if(isset($track['playlist_id']) and $track['playlist_id']) {
            //we need to add the playlist tracks
            $playlist = model('track')->findPlaylist($track['playlist_id']);
            $playlistOwner = model('user')->getUser($playlist['userid']);
            $track['playlistOwner'] = $this->formatUser($playlistOwner);

            $track['tracksInPlaylist'] = model('track')->countPlaylistTracks($track['playlist_id']);
        }

        if (model('user')->isLoggedIn()) {
            $track['actionType'] = $actionType = 'track';
            $track['actionId'] =$actionId = $track['id'];
            $track['repostAction'] =$repostAction = "repost-track";

            if(isset($track['playlist_id']) and $track['playlist_id']) {
                $track['actionType'] =$actionId = $track['playlist_id'];
                $track['actionId'] =$actionType = 'playlist';
                $track['repostAction'] =$repostAction = ($playlist['playlist_type'] == 1) ? 'repost-playlist' : 'repost-album';
            }
        }

        $allowOwner = false;
        if ($type == 'my-tracks') $allowOwner = true;
        $canDisplay = model('track')->displayItem($track, $allowOwner);
        if (!$canDisplay) return false;

        return $track;
    }

    public function formatComment($comment) {
        $user = model('user')->getUser($comment['userid']);
        $result = array(
            'message' => $comment['message'],
            'at' => '',
            'time' => date('c', $comment['time']),
            'user' => $this->formatUser($user),
            'id' => $comment['id'],
            'isOwner' => (model('user')->isLoggedIn() and $comment['userid'] == model('user')->authId) ? 1 : 0,
            'replies' => model('track')->countComments('comment', $comment['id']),
        );

        return $result;
    }

    public function formatPlaylist($playlist) {
        $track = model('track')->getPlaylistFirstTrack($playlist['id']);
        $result = array(
            'name' => $playlist['name'],
            'price' => $playlist['price'],
            'description' => $playlist['description'],
            'time' => date('c', $playlist['time']),
            'user' => $this->formatUser(model('user')->getUser($playlist['userid'])),
            'id' => $playlist['id'],
            'total_tracks' => count(model('track')->getPlaylistEntries($playlist['id'])),
            'art' => model('track')->getArt($track, 200),
            'track' => $this->formatTrack($track, 'playlist', $playlist['id']),
            
        );
        $result['likeCount'] = model('track')->countLikes('playlist', $result['id']);
        $result['hasLiked'] = model('track')->hasLiked('playlist', $playlist['id']) ? 1 : 0;
        $result['isOwner'] = ($playlist['userid'] == model('user')->authId) ? 1 : 0;
        $result['link'] = model('track')->playlistUrl($playlist);
        return $result;
    }

    public function formatNotification($notification) {
        model('user')->markRead($notification['notifyid']);
        $notification = model('user')->formatNotification($notification, true);
        switch($notification['type']) {
            case 'comment-video':
                $video = model('video::video')->find($notification['typeid']);
                $notification['click'] = 'video';
                $notification['video'] = $this->formatVideo($video);
                break;
            case 'reply-comment-video':
                $video = model('video::video')->find($notification['typeid']);
                $notification['click'] = 'video';
                $notification['video'] = $this->formatVideo($video);
                break;
            case 'like-video':
                $video = model('video::video')->find($notification['typeid']);
                $notification['click'] = 'video';
                $notification['video'] = $this->formatVideo($video);
                break;
        }
        return $notification;
    }

    public function formatMessage($message) {
        $message['user'] = $this->formatUser($message);
        $message['chattime'] = date('c', $message['chattime']);
        if (isset($message['track'])) $message['track'] = $this->formatTrack($message['track'], '', '');
        if (isset($message['playlist'])) $message['playlist'] = $this->formatPlaylist($message['playlist']);

        return $message;
    }

    public function formatVideo($video) {
        $user = model('user')->getUser($video['userid']);
        $video['user'] = $this->formatUser($user);
        $video['art'] = model('video::video')->getArt($video, 200);
        $video['player'] = model('video::video')->getPlayerDetails($video);
        $video['plays'] = model('video::video')->countPlays($video['id']);
        $video['likeCount'] = model('track')->countLikes('video', $video['id']);
        $video['commentsCount'] = model('track')->countComments('video', $video['id']);
        $video['hasLiked'] = model('track')->hasLiked('video', $video['id']) ? 1 : 0;
        $video['isOwner'] = ($video['userid'] == model('user')->authId) ? 1 : 0;
        $video['link'] = model('video::video')->videoUrl($video);
        return $video;
    }
    
    public function formatRadio($radio, $platform = 'andriod') {
        $user = model('user')->getUser($radio['userid']);
        $radio['user'] = $this->formatUser($user);
        $radio['art'] = model('radio::radio')->getArt($radio, 200);
        $radio['art_md'] = model('radio::radio')->getArt($radio, 600);
        $radio['art_lg'] = model('radio::radio')->getArt($radio, 920);
        $radio['likeCount'] = model('track')->countLikes('radio', $radio['id']);
        $radio['commentsCount'] = model('track')->countComments('radio', $radio['id']);
        $radio['hasLiked'] = model('track')->hasLiked('radio', $radio['id']) ? 1 : 0;
        $radio['isOwner'] = ($radio['userid'] == model('user')->authId) ? 1 : 0;
        $radio['views'] = model('radio::radio')->countViews($radio['id']);
        $link = $radio['link'];
        //if (!preg_match('$https:$', $link)) $link = str_replace('http:', 'https:', $link);
        if (!preg_match( '/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', $link, $ip_match) and !preg_match('$https:$', $link) and $platform == 'ios') return false;
        $radio['streamurl'] = $link;
        $radio['reposter'] = $radio['user'];
        return $radio;
    }
    
    public function formatBlog($blog) {
        $blog['art'] = url_img($blog['image'], 600);
        $blog['likeCount'] = model('track')->countLikes('blog', $blog['id']);
        $blog['commentsCount'] = model('track')->countComments('blog', $blog['id']);
        $blog['hasLiked'] = model('track')->hasLiked('blog', $blog['id']) ? 1 : 0;
        return $blog;
    }


}