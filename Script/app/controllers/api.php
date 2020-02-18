<?php
class ApiController extends Controller {

    private $userid;
    private $user;

    public function __construct($request = null) {
        parent::__construct($request);
        $apiKey = config('api-access-key', 'o0q0aOmm4M0JEA');
        $providedKey = $this->request->segment(1);
        if ($apiKey != $providedKey) return json_encode(array('status' => 0));
        header('Access-Control-Allow-Origin: http://localhost:9080');
    }

    private function userAuth($force = true) {
        $this->userid = $this->request->input('userid');
        $key = $this->request->input('key');
        $this->user = $this->model('user')->getUser($this->userid);
        if ($this->user and $this->model('api')->userKeyExists($this->userid, $key)) {
            $this->model('user')->loginWithObject($this->user);
        } else {
            $this->model('user')->logoutUser();
            if ($force) exit(json_encode(array('status' => 0)));
        }
    }

    public function setup() {
        $rgba = implode(',', hexToRgb(config('api-primary-color', '#FA0052')));
        $result = array(
            'default_language' => config('api-default-language', 'en'),
            'default_mode' => config('api-default-mode', 'dark'),
            'in_app_purchase' => config('api-enable-in-purchase', false),
            'premium_account' => (config('api-enable-premium', true)) ? true : false,
            'enable_store' => (moduleExists('store') and config('api-enable-store', true)) ? true : false,
            'enable_blogs' => (moduleExists('blog') and config('api-enable-blogs', true)) ? true : false,
            'enable_radios' => (moduleExists('radio') and config('api-enable-radios', true)) ? true : false,
            'enable_video' => (moduleExists('video') and config('api-enable-video', true)) ? true : false,
            'primary_color' => config('api-primary-color', '#FA0052'),
            'accent_color' => config('api-accent-color', '#141821'),
            'transparent_primary' => "rgba($rgba,0.4)",
            'slide_image_1' => (config('api_slide_image_1','')) ? assetUrl(config('api_slide_image_1','')) : '',
            'slide_image_2' => (config('api_slide_image_2','')) ? assetUrl(config('api_slide_image_2','')) : '',
            'slide_image_3' => (config('api_slide_image_3','')) ? assetUrl(config('api_slide_image_3','')) : '',
            'privacy_link' => config('api-privacy-link', ''),
            'terms_link' => config('api-terms-link', ''),
            'contact_link' => config('api-contact-link', ''),
            'progress' => (config('player-progress-type','bar') == 'bar') ? 'bar' : 'wave',
        );

        return json_encode($result);
    }

    public function login() {
        $username = $this->request->input('username');
        $password = $this->request->input('password');
        $platform = $this->request->input('device');
        if ($this->model('user')->loginUser($username, $password, true)) {
            $this->userid = $this->model('user')->authId;
            $this->user = $this->model('user')->authUser;
            return json_encode(array_merge(array(
                'status' => 1,
                'key'  => $this->model('api')->generateKey($this->userid,$platform)
            ), $this->model('api')->formatUser($this->user, true)));
        } else {
            return json_encode(array('status' => 0));
        }
    }

    public function socialSignup() {
        $fbId = $this->request->input('fbid');
        $username = 'fb_'.$fbId;
        $username = str_replace(array(' ', '.', '-'), array('', '', ''), $username);
        $email  = $this->request->input('email');
        $details = array(
            'username' => $username,
            'full_name' => $this->request->input('full_name'),
            'email' => ($email) ? $email : 'fb_'.$fbId.'@facebook.com',
            'email_address' => ($email) ? $email : 'fb_'.$fbId.'@facebook.com',
            'social_email' => 'fb_'.$fbId.'@facebook.com',
            'password' => time(),
            'auth' => 'facebook',
            'authId' => $fbId,
            'avatar' => ''
        );
        $platform = $this->request->input('device');
        try {
            $url = 'https://graph.facebook.com/'.$fbId.'/picture?redirect=false&width=600&height=600';
            $ch = curl_init();
            $headers = array(
                'Referer: https://www.google.com.ng/_/chrome/newtab-serviceworker.js',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.59 Safari/537.36'
            );
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            if(!ini_get('open_basedir')) {
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            }
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            $contents = curl_exec($ch);
            curl_close($ch);
            $avatar = json_decode($contents, true);
            if($avatar and isset($avatar['data']['url'])) {
                $avatar = $avatar['data']['url'];
                $details['avatar'] = $avatar;
            }
        } catch(\Exception $e) {
        }

        $validator = Validator::getInstance()->scan($details, array(
            'full_name' => 'required',
            'username' => 'required|username|predefined',
            'password' => 'required',
            'email' => 'required|email'
        ));
        if ($validator->passes()) {
            $user =  $this->model('user')->socialSignup($details, true);
            $this->userid = $user['id'];
            $this->user = $user;
            return json_encode(array_merge(array(
                'status' => 1,
                'key'  => $this->model('api')->generateKey($this->userid,$platform)
            ), $this->model('api')->formatUser($this->user, true)));
        } else {
            return json_encode(array(
                'message' => $validator->first(),
                'status' => 0
            ));
        }

    }

    public function signup() {
        $val = array(
            'username' => $this->request->input('username'),
            'full_name' => $this->request->input('full_name'),
            'email' => $this->request->input('email'),
            'password' => $this->request->input('password')
        );
        $platform = $this->request->input('device');
        $validator = Validator::getInstance()->scan($val, array(
            'full_name' => 'required',
            'username' => 'required|username|predefined|alphanum|unique:users',
            'password' => 'required',
            'email' => 'required|email|unique:users'
        ));


        if ($validator->passes()) {
            $userid = $this->model('user')->addUser($val, false);
            if (config('email-activation', false)) {
                return json_encode(array(
                    'message' => l('signup-successful-activate'),
                    'status' => 2 //activate accoun my mail
                ));
            }


            $this->model('user')->loginUser($val['username'], $val['password'], true);
            if (config('auto-follow-accounts')) {
                $users = explode(',', config('auto-follow-accounts'));
                foreach($users as $followId) {
                    $followUser = $this->model('user')->getUser($followId);
                    if ($followUser) $this->model('user')->follow($followUser['id']);
                }
            }
            $this->userid = $this->model('user')->authId;
            $this->user = $this->model('user')->authUser;
            return json_encode(array_merge(array(
                'status' => 1,
                'key'  => $this->model('api')->generateKey($this->userid,$platform)
            ), $this->model('api')->formatUser($this->user, true)));
        } else {
            return json_encode(array(
                'message' => $validator->first(),
                'status' => 0
            ));
        }
    }

    public function checkAuth() {
        $this->userAuth();
        $notifications = $this->model('user')->countUnreadNotifications();
        $messages = $this->model('message')->countUnread();
        return json_encode(array_merge(array(
            'notifications' => $notifications,
            'messages' => $messages,
            'total' => $notifications + $messages,
            'status' => 1
        ), $this->model('api')->formatUser($this->user, true)));

    }

    public function loadTracks() {
        $this->userAuth(false);
        $type = $this->request->input('type');
        $typeId = $this->request->input('type_id');
        $offset = $this->request->input('offset');
        $limit = ($this->request->input('limit')) ? $this->request->input('limit') : config('tracks-limit', 10);
        $tracks = $this->model('track')->getTracks($type, $typeId, $offset,$limit);
        $result = array();
        foreach($tracks as $track) {
            $track = $this->model('api')->formatTrack($track, $type, $typeId);
            if ($track) $result[] = $track;
        }

        return json_encode($result);
    }

    public function activatePro() {
        $this->userAuth();
        $type = $this->request('type');
        $price = $this->request('price');
        $this->model('admin')->addTransaction(array(
            'amount' =>  $price,
            'type' => 'pro-users',
            'type_id' => $type,
            'sale_id' => time(),
            'name' => $this->model('user')->authUser['full_name'],
            'country' => $this->model('user')->authUser['country'],
            'email' => $this->model('user')->authUser['email'],
            'userid' => $this->model('user')->authId
        ));
        return json_encode(array('status' => 1));
    }

    public function getGenres() {
        $this->userAuth(false);
        $term = $this->request->input('term', '');
        $result = array();
        foreach($this->model('admin')->getGenres($term) as $genre) {
            $result[] = array(
                'id' => $genre['id'],
                'name' => $genre['name'],
                'uses' => $genre['uses']
            );
        }

        return json_encode($result);
    }

    public function loadComments() {
        $offset = $this->request->input('offset');
        $limit = ($this->request->input('limit')) ? $this->request->input('limit') : config('comment-limit', 5);
        $this->userAuth(false);
        $type = $this->request->input('type');
        $typeId = $this->request->input('type_id');
        $comments = $this->model('track')->getComments($type, $typeId, $offset,$limit);
        //exit($offset .'-'.$limit.'-'.$type.'-'.$typeId);
        $result = array();
        foreach($comments as $comment) {
            $comment = $this->model('api')->formatComment($comment, $type, $typeId);
            if ($comment) $result[] = $comment;
        }

        return json_encode($result);
    }

    public function addComment() {
        $type = $this->request->input('type');
        $typeId = $this->request->input('type_id');
        $this->userAuth();
        $commentId = $this->model('track')->addComment(array(
            'type' => $type,
            'id' => $typeId,
            'comment_text' => $this->request->input('message'),
            'at' => $this->request->input('at')
        ));

        $comment = $this->model('track')->findComment($commentId);
        return json_encode($this->model('api')->formatComment($comment, $type, $typeId));
    }

    public function removeComment(){
        $id = $this->request->input('id');
        $this->userAuth();

        $comment = $this->model('track')->findComment($id);
        if ($comment['userid'] != $this->model('user')->authId) exit(json_encode(array('status' => 0)));
        $this->model('track')->deleteComment($id);
        exit(json_encode(array('status' => 1)));
    }

    public function trackDetails() {
        $id = $this->request->input('id');
        $this->userAuth(false);
        $track = $this->model('track')->findTrack($id);
        return json_encode($this->model('api')->formatTrack($track,null, null));
    }

    public function likeItem() {
        $type = $this->request->input('type');
        $typeId = $this->request->input('type_id');
        $this->userAuth(true);
        $like = $this->model('track')->likeItem($type, $typeId);
        return json_encode(array(
            'status' => 1
        ));
    }

    public function repostItem() {
        $action = $this->request->input('action');
        $track = $this->request->input('track');
        $playlistId = $this->request->input('playlist_id', '');
        $this->userAuth(true);
        $repost = $this->model('track')->addStream($track, $playlistId, $action);
        return json_encode(array(
            'status' => 1
        ));
    }

    public function navigatePlayer() {

        $offset = 0;
        $limit = 200;

        $trackId = $this->request->input('id');
        $track = $this->helperNavigate($offset, $limit, $trackId);
        return ($track) ? json_encode($track) : json_encode(array('id' => 0));
    }

    private function helperNavigate($offset, $limit,$trackId) {

        $type = $this->request->input('type');
        $typeId = $this->request->input('type_id');
        $this->userAuth(false);
        $navType = $this->request->input('nav_type');
        $tracks = $this->model('track')->getTracks($type, $typeId, $offset,$limit);
        $index = 'notfound';
        $i = 0;
        foreach($tracks as $track) {
            $trackid = (isset($track['trackid'])) ? $track['trackid'] : $track['id'];
            if ($trackid == $trackId) {
                $index = $i;
                break;
            }
            $i++;
        }
        $newIndex = ($navType == 'next') ? $index + 1 : $index - 1;
        if ($newIndex < 0) $newIndex = count($tracks) - 1;
        return (isset($tracks[$newIndex])) ? $this->model('api')->formatTrack($tracks[$newIndex],$type,$typeId) : null;

    }

    public function getMyPlaylists() {
        $playlists = $this->model('track')->getPlaylists();
        $trackId = $this->request->input('track');
        $result = array();
        $this->userAuth(false);
        foreach($playlists as $list) {
            $track = model('track')->getPlaylistFirstTrack($list['id']);
            $result[] =  array(
                'name' => $list['name'],
                'art' => $this->model('track')->getArt($track),
                'count' => model('track')->countPlaylistTracks($list['id']),
                'id' => $list['id'],
                'contain' => (model('track')->existInPlaylist($list['id'], $trackId)) ? 1 : 0
            );
        }
        return json_encode($result);
    }

    public function addToPlaylist() {
        $this->userAuth();
        $trackId = $this->request->input('track');
        $playlistId = $this->request->input('playlist');

        $this->model('track')->addToPlaylist($playlistId, $trackId);
        return json_encode(array('status' => 1));
    }

    public function addPlaylist() {
        $name = $this->request->input('name');
        $desc = $this->request->input('desc');
        $public = $this->request->input('public');
        $track = $this->request->input('track');
        $this->userAuth();

        $this->model('track')->addPlaylist(array(
            'title' => $name,
            'desc' => $desc,
            'public' => $public
        ), array($track));

        return json_encode(array('status' => 1));
    }

    public function getTrackDownloadDetail() {
        $this->userAuth(false);
        $track = $this->model('track')->findTrack($this->request->input('id'));
        $file = $this->getFileUri($track['track_file'], false);
        $url = $this->getFileUri($track['track_file'], true);
        $result = array(
            'url' => $url,
            'name' => ''
        );

        if (preg_match('#http#', $file)) {
            //we need to download first to a local place and delete when done
            $ext = getMimeTypeExtension(getMimeType($file));
            $ext = ($ext) ? $ext : 'mp3';
            $filename = md5($track['title']).'.'.$ext;

        } else{
            $ext = get_file_extension($file);
            $ext = ($ext) ? $ext : 'mp3';
            $filename = md5($track['title']).'.'.$ext;
        }
        $result['name'] = $filename;
        return json_encode($result);

    }

    public function listPeople() {
        $type = $this->request->input('type');
        $offset = $this->request->input('offset');
        $limit = $this->request->input('limit');
        $term = $this->request->input('term');
        $userid = $this->request->input('theuserid', null);
        $this->userAuth(false);
        switch($type) {
            case 'artists':
                $users = $this->model('user')->getPeople('top',$term,  $limit, $offset);
                break;
            case 'people':
                $users = $this->model('user')->getPeople('people',$term,  $limit, $offset);
                break;
            case 'followers':
                $users = $this->model('user')->getFollows($userid, 2, $limit, $offset);
                break;
            case 'following':
                $users = $this->model('user')->getFollows($userid, 1, $limit, $offset);
                break;
        }
        $result = array();
        foreach($users as $user) {
            $result[] = $this->model('api')->formatUser($user);
        }

        return json_encode($result);
    }

    public function listPlaylist() {
        $type = $this->request->input('type');
        $typeId = $this->request->input('type_id');

        $offset = $this->request->input('offset');
        $limit = $this->request->input('limit');
        $this->userAuth(false);
        $playlists = $this->model('track')->listPlaylists($type, $typeId,  $limit, $offset);
        $result = array();
        foreach($playlists as $playlist) {
            $result[] = $this->model('api')->formatPlaylist($playlist);
        }

        return json_encode($result);
    }

    public function hasSpotlight() {
        $this->userAuth(false);
        $userid = $this->request->input('theuserid');

        $tracks = model('track')->getTracks('my-spotlight', $userid,0, 5);
        if (count($tracks) > 0) {
            return json_encode(array('status' => 1));
        } else {
            return json_encode(array('status' => 0));
        }
    }

    public function getPurchased() {
        $this->userAuth();
        $type = $this->request->input('type');
        $transactions = model('store::store')->getPurchased($type);
        $result = array();
        foreach($transactions as $transaction) {
            $details = array(
                'time' => date('c', $transaction['time'])
            );
            switch($type) {
                case 'track':
                    $track = model('track')->findTrack($transaction['type_id']);
                    $details['track'] = $this->model('api')->formatTrack($track,null,null);
                    break;
                case 'album':
                    $playlist = model('track')->findPlaylist($transaction['type_id']);
                    $details['playlist'] = $this->model('api')->formatPlaylist($playlist);
                    break;
                case 'video':
                    $video = model('video::video')->find($transaction['type_id']);
                    $details['video'] = $this->model('api')->formatVideo($video);
                    break;
            }
        }
        return json_encode($result);
    }

    public function listVideo() {
        $type = $this->request->input('type');
        $typeId = $this->request->input('type_id');

        $offset = $this->request->input('offset');
        $limit = $this->request->input('limit');
        $this->userAuth(false);
        $videos = $this->model('video::video')->getVideos($type,$typeId,$offset, $limit);
        $result = array();
        foreach($videos as $video) {
            $result[] = $this->model('api')->formatVideo($video);
        }

        return json_encode($result);
    }

    public function changeAvatar() {
        $this->userAuth();
        $avatar = $this->request->inputFile('avatar');
        $uploader = new Uploader($avatar);
        $uploader->setPath('avatar/'.$this->model('user')->authId.'/');
        if ($uploader->passed()) {
            $avatar = $uploader->resize()->result();
            $user = $this->model('user')->getUser($this->model('user')->authId);
            $this->model('user')->saveUser(array('avatar' => $avatar), $user);
            return json_encode(array('status' => 1, 'avatar' => $this->model('user')->getAvatar(model('user')->getUser(model('user')->authId), 200)));
        } else {
            return json_encode(array('status' => 0, 'message' => $uploader->getError()));
        }
    }

    public function saveAccount() {
        $this->userAuth();
        $val = array(
            'full_name' => $this->request->input('name'),
            'website' => $this->request->input('website'),
            'bio' => $this->request->input('bio'),
            'facebook' => $this->request->input('facebook'),
            'instagram' => $this->request->input('instagram'),
            'twitter' => $this->request->input('twitter'),
            'soundcloud' => $this->request->input('soundcloud'),
            'youtube' => $this->request->input('youtube'),
            'vimeo' => $this->request->input('vimeo'),
            'city' => $this->request->input('city')
        );

        $user = $this->model('user')->getUser($this->model('user')->authId);
        $this->model('user')->saveUser($val, $user);

        $user = $this->model('user')->getUser($this->model('user')->authId);
        return json_encode($this->model('api')->formatUser($user));
    }

    public function userDetail() {
        $this->userAuth(false);
        $userid = $this->request->input('id');
        $user = $this->model('user')->getUser($userid);
        return json_encode($this->model('api')->formatUser($user));
    }

    public function notificationList() {
        $this->userAuth();
        $offset = $this->request->input('offset');
        $limit = $this->request->input('limit');
        $notifications = $this->model('user')->getNotifications($offset, $limit);
        $result = array();
        foreach($notifications as $notification) {
            $result[] = $this->model('api')->formatNotification($notification);
        }
        return json_encode($result);
    }

    public function deleteNotification() {
        $this->userAuth();
        $notificationId = $this->request->input('id');
        $this->model('user')->deleteNotification($notificationId);
        return json_encode(array('status' => 1));
    }

    public function listMessages() {
        $this->userAuth();
        $conversations = $this->model('message')->getConversations();
        $result = array();
        foreach($conversations as $conversation) {
            $conversation['user'] = $this->model('api')->formatUser($conversation['user']);
            $message = model('message')->getLastMessage($conversation['id']);
            $message['chattime'] = date('c', $message['time']);
            $conversation['message'] = $message;
            $result[] = $conversation;
        }
        return json_encode($result);
    }

    public function chats() {
        $this->userAuth();
        $offset = $this->request->input('offset');
        $limit = $this->request->input('limit');
        $cid = $this->request->input('cid');
        $to = $this->request->input('to');
        if (!$cid) $cid = $this->model('message')->getConversationId($this->model('user')->authId, $to);
        $messages = $this->model('message')->getMessages($cid, $offset, $limit);
        $result = array();
        foreach($messages as $message) {
            $result[] = $this->model('api')->formatMessage($message);
        }

        return json_encode($result);
    }

    public function sendChat() {
        $this->userAuth();
        $text = $this->request->input('text');
        $to = $this->request->input('to');
        $messageId = $this->model('message')->send($to, $text, '', '');
        $message = $this->model('message')->getMessage($messageId);
        $message = $this->model('api')->formatMessage($message);
        return json_encode($message);
    }

    public function priceDetail() {
        $price = array(
            'yearly' => config('pro-user-yearly-price', '55'),
            'monthly' => config('pro-user-month-price', '5')
        );

        if (!config('enable-premium-listeners', false)) {
            $price = array(
                'yearly' => config('pro-artist-yearly-price', '55'),
                'monthly' => config('pro-artist-month-price', '5')
            );
        }
        return json_encode($price);
    }

    public function trackPlay() {
        $id = $this->request->input('id');
        $this->userAuth();
        if (!$this->model('track')->hasView($this->model('user')->authId, $id)) $this->model('track')->setViews($id);
        return json_encode(array('status' => 1));
    }

    public function videoPlay() {
        $id = $this->request->input('id');
        $this->userAuth();
        //$video = $this->model('video::video')->find($id);
        $this->model('video::video')->addPlays($id);
        return json_encode(array('status' => 1));
    }

    public function videoView() {
        $id = $this->request->input('id');
        $this->userAuth(false);
        $video = $this->model('video::video')->find($id);
        $this->model('video::video')->updateViews($video);
        return json_encode(array('status' => 1));
    }

    public function playVideo() {
        $id = $this->request->input('id');
        $this->userAuth(false);
        $video = $this->model('video::video')->find($id);

        return $this->view('api/video-player', array('video' => $video));
    }

    public function suggestVideos() {
        $id = $this->request->input('id');
        $this->userAuth(false);
        $videos = model('video::video')->getSuggestedVideos($id, 10);
        $result = array();
        $result = array();
        foreach($videos as $video) {
            $result[] = $this->model('api')->formatVideo($video);
        }

        return json_encode($result);
    }

    public function userFollow() {
        $this->userAuth();
        $userid = $this->request->input('id');
        $action = $this->model('user')->follow($userid);
        return json_encode(array('status' => 1));
    }

    public function pay() {
        $type = $this->request->input('type');
        $typeId = $this->request->input('type_id');
        $price = $this->request->input('price');
        if ($type == 'pro-users' and !config('enable-premium-listeners', false)) $type = 'pro';
        $this->userAuth();

        session_put("about-pay-type", $type);
        session_put('about-pay-typeid', $typeId);
        session_put("about-pay-price", $price);
        session_put("mobile-pay", 1);

        $view = $this->view('payment/methods', array(
            'type' => $type,
            'typeId' => $typeId,
            'price' => $price,
            'detail' => $this->model('admin')->getPaymentDetails($type, $typeId)
        ));
        return $this->view('api/pay', array('view' => $view)) ;
    }

    public function radio() {
        $type = $this->request->input('type');
        $typeId = $this->request->input('type_id');
        $platform = $this->request->input('platform', 'android');
        $offset = $this->request->input('offset');
        $limit = $this->request->input('limit');
        $this->userAuth(false);
        $radios = $this->model('radio::radio')->getRadios($type,$typeId,$offset, $limit);
        $result = array();
        foreach($radios as $radio) {
            $radio = $this->model('api')->formatRadio($radio, $platform);
            if ($radio) $result[] = $radio;
        }

        return json_encode($result);
    }

    public function blogs() {
        $type = $this->request->input('type');
        $typeId = $this->request->input('type_id');

        $offset = $this->request->input('offset');

        $this->userAuth(false);
        $blogs = $this->model('blog::blog')->getBlogs($type,$typeId,$offset);
        $result = array();
        foreach($blogs as $blog) {
            $result[] = $this->model('api')->formatBlog($blog);
        }

        return json_encode($result);
    }

    public function uploadPicture() {
        $this->userAuth();
        $type = $this->request->input('type');
        if ($type == 'avatar') {
            $avatar = $this->request->inputFile('avatar');
            $uploader = new Uploader($avatar);
            $uploader->setPath('avatar/'.$this->model('user')->authId.'/');
            if ($uploader->passed()) {
                $avatar = $uploader->resize()->result();
                $user = $this->model('user')->getUser($this->model('user')->authId);
                $this->model('user')->saveUser(array('avatar' => $avatar), $user);
                return json_encode(array('status' => 1, 'avatar' => $this->model('user')->getAvatar(model('user')->getUser(model('user')->authId), 200)));
            } else {
                return json_encode(array('status' => 0, 'message' => $uploader->getError()));
            }
        } else {
            $avatar = $this->request->inputFile('avatar');
            $uploader = new Uploader($avatar);
            $uploader->setPath('cover/'.$this->model('user')->authId.'/');
            if ($uploader->passed()) {
                $avatar = $uploader->uploadFile()->result();
                $user = $this->model('user')->getUser($this->model('user')->authId);
                $this->model('user')->saveUser(array('cover' => $avatar), $user);
                return json_encode(array('status' => 1, 'cover' => assetUrl($avatar)));
            } else {
                return json_encode(array('status' => 0, 'message' => $uploader->getError()));
            }
        }
    }


}