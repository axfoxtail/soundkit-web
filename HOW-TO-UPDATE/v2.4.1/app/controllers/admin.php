<?php
class AdminController extends Controller {

    public function __construct($request)
    {
        $this->adminRequired = true;
        parent::__construct($request);
        $this->addBreadCrumb(l('admin-panel'));
        $this->sideMenu = "admin/includes/side-menu";
    }

    public function index() {
        $this->setTitle(l('dashboard'));
        $this->addBreadCrumb(l('dashboard'));
        $this->activeMenu = 'dashboard';
        return $this->render($this->view('admin/dashboard'),true);
    }


    public function settings() {
        $this->adminSecure('manage-settings');
        $this->setTitle(l('site-settings'));
        $this->activeMenu = "settings";
        $this->addBreadCrumb(l('site-settings'));
        $message = null;
        $messageType = 'url';
        if ($val = $this->request->input("val", null, false)) {
            $images = $this->request->input('img');
            if ($this->isDemo()) $this->defendDemo();

            foreach ($images as $image => $value) {
                $val[$image] = $value;
                if ($imageFile = $this->request->inputFile($image)) {
                    $uploader = new Uploader($imageFile);
                    $uploader->setPath("settings/");
                    if ($uploader->passed()) {
                        $val[$image] = $uploader->uploadFile()->result();
                    } else {
                        //there is problem
                        $message  = $uploader->getError();
                        $messageType = 'error';
                    }
                }
            }

            if ($val['enable-artist-verification']) {
                $val['user-signup-artist']  = 0;
            }

            if ($val['enable-premium-listeners']) {
                $val['can-upload'] = 2;
            }

            $this->model('admin')->saveSettings($val);
            $message  = l('settings-saved');
            return json_encode(array(
                'type' => 'url',
                'message' => $message,
                'value' => url('admin/settings')
            ));
        }
        return $this->render($this->view('admin/settings', array('message' => $message,'messageType' => $messageType)),true);
    }

    public function users() {
        $this->adminSecure('manage-users');
        $this->setTitle(l('manage-users'));
        $this->addBreadCrumb(l('manage-users'));
        $this->activeMenu = 'users';

        $type = $this->request->input('type', 1);
        $users = $this->model('user')->lists($type, $this->request->input('term', null));
        $message = null;
        if ($val = $this->request->input('val')) {
            if ($this->isDemo()) $this->defendDemo();
            $required = array(
                'full_name' => 'required',
                'username' => 'required|alphanum|unique:users',
                'password' => 'required',
                'email' => 'required|email|unique:users'
            );
            $required = Hook::getInstance()->fire('user.required.data', $required, array($val));
            $val  = Hook::getInstance()->fire('admin.user.data', $val);
            $validator = Validator::getInstance()->scan($val, $required);

            if ($validator->passes()) {
                $userid = $this->model('user')->addUser($val, true);

                return json_encode(array(
                    'type' => 'url',
                    'value' => url("admin/users"),
                    'message' => l('user-created')
                ));
            } else {
                $message = $validator->first();
                return json_encode(array(
                    'type' => 'error',
                    'message' => $message
                ));
            }
        }
        return $this->render($this->view('admin/users/lists', array('type' => $type, 'users' => $users, 'message' => $message)),true);
    }

    public function userAction() {
        $action = $this->request->input('action');
        $id = $this->request->input('id');
        if ($this->isDemo()) $this->defendDemo();
        switch($action) {
            case 'ban':
                $this->model('user')->ban($id);
                break;
            case 'unban':
                $this->model('user')->unban($id);
                break;
            case 'delete':
                $this->model('user')->delete($id);
                break;
        }

        return $this->request->redirectBack();
    }

    public function userEdit() {
        $this->activeMenu = 'users';
        $this->setTitle(l('edit-user'));
        $this->addBreadCrumb(l('edit-user'));
        $user = $this->model('user')->getUser($this->request->input('id'));

        if (!$user) $this->request->redirectBack();
        $message = null;
        $messageType = 'danger';

        if ($val = $this->request->input('val')) {
            if ($this->isDemo()) $this->defendDemo();
            $required = array(
                'full_name' => 'required',
                'username' => 'required|username|predefined|alphanum',
                'email' => 'required|email'
            );
            $required = Hook::getInstance()->fire('user.required.data', $required, array($val));
            $val  = Hook::getInstance()->fire('admin.user.data', $val);
            $required['username'] = 'required|username|predefined|alphanum';

            unset($required['email']);

            $validator = Validator::getInstance()->scan($val, $required);

            if ($validator->passes()) {
                if ($user['username'] != $val['username']) {
                    //check for other users with same name
                    if (model('user')->otherUseUsername($val['username'], $user['id'])) {
                        return json_encode(array(
                            'message' => l('selected-username-is-used-by-others'),
                            'type' => 'error'
                        ));
                    }
                }

                if($val['email'] and $val['email'] != $user['email']) {
                    if (model('user')->otherUseEmail($val['email'], $user['id'])) {
                        return json_encode(array(
                            'message' => l('selected-email-is-used-by-others'),
                            'type' => 'error'
                        ));
                    }
                }
                $user = $this->model('user')->getUser($this->request->input('id'));
                $this->model('user')->saveUser($val, $user, true);
                $message = l('user-saved-success');
                $messageType = 'success';
                $user = $this->model('user')->getUser($this->request->input('id'));
                return json_encode(array(
                    'type' => 'success',
                    'message' => l('user-saved')
                ));
            } else {
                $message = $validator->first();
                return json_encode(array(
                    'type' => 'error',
                    'message' => $message
                ));
            }

        }

        return $this->render($this->view('admin/users/edit', array('user' => $user, 'message' => $message,'messageType' => $messageType)),true);
    }

    public function roles() {
        $this->adminSecure('manage-user-roles');
        $this->activeMenu = 'roles';
        $this->setTitle(l('user-roles'));
        $this->addBreadCrumb(l('user-roles'));

        $roles = $this->model('admin')->getRoles($this->request->input('term', null));
        $message = null;
        $role = null;
        $roleId = null;

        if ($roleId = $this->request->input('id')) {
            $role = $this->model('admin')->findRole($roleId);
        }

        if ($this->request->input('action') == 'delete' ) {
            if ($this->isDemo()) $this->defendDemo();
            $this->model('admin')->deleteRoles($this->request->input('id'));
            $this->request->redirect(url("admin/roles"));
        }
        if ($val = $this->request->input('val')) {
            if ($this->isDemo()) $this->defendDemo();
            $validator = Validator::getInstance()->scan($val, array(
                'name' => 'required',
            ));


            if ($validator->passes()) {
                $this->model('admin')->addRole($val, $this->request->input('permissions'), $roleId);

                //$this->request->redirect(url("admin/genres"));
                return json_encode(array(
                    'type' => 'url',
                    'value' => url("admin/roles"),
                    'message' => ($roleId) ? l('role-saved') : l('role-added')
                ));
            } else {
                $message = $validator->first();
                return json_encode(array(
                    'type' => 'error',
                    'message' => $message
                ));
            }
        }
        return $this->render($this->view('admin/roles/lists', array('roles' => $roles, 'message' => $message, 'role' => $role)), true);
    }

    public function genres() {
        $this->adminSecure('manage-genres');
        $this->activeMenu = 'genres';
        $this->setTitle(l('manage-genres'));
        $this->addBreadCrumb(l('manage-genres'));

        $genres = $this->model('admin')->getGenres($this->request->input('term', null));
        $message = null;
        $genre = null;
        $genreId = null;

        if ($genreId = $this->request->input('id')) {
            $genre = $this->model('admin')->findGenre($genreId);
        }
        if ($this->request->input('action') == 'delete' ) {
            if ($this->isDemo()) $this->defendDemo();
            $this->model('admin')->deleteGenre($this->request->input('id'));
            $this->request->redirect(url("admin/genres"));
        }
        if ($val = $this->request->input('val')) {
            if ($this->isDemo()) $this->defendDemo();
            $validator = Validator::getInstance()->scan($val, array(
                'name' => 'required',
            ));

            if ($validator->passes()) {
                $this->model('admin')->addGenre($val, $genreId);

                //$this->request->redirect(url("admin/genres"));
                return json_encode(array(
                    'type' => 'url',
                    'value' => url("admin/genres"),
                    'message' => ($genreId) ? l('genre-saved') : l('genre-added')
                ));
            } else {
                $message = $validator->first();
                return json_encode(array(
                    'type' => 'error',
                    'message' => $message
                ));
            }
        }
        return $this->render($this->view('admin/genre/lists', array('genres' => $genres, 'message' => $message, 'dgenre' => $genre)), true);
    }

    public function plugins() {
        $this->adminSecure('manage-plugins');
        $this->setTitle(l('plugins-manager'));
        $this->addBreadCrumb(l('plugins-manager'));
        $this->activeMenu = 'plugins';

        if ($val = $this->request->input('val')) {
            if ($this->isDemo()) $this->defendDemo();
            $this->model('admin')->savePlugins($val);
            return json_encode(array(
                'type' => 'function',
                'message' => l('plugins-list-updated')
            ));
        }

        return $this->render($this->view('admin/plugins/index'), true);
    }

    public function tracks() {
        $this->adminSecure('manage-tracks');
        $this->setTitle(l('tracks'));
        $this->addBreadCrumb(l('tracks'));
        $this->activeMenu = 'tracks';
        $genre = $this->request->input('genre', '');
        $user = $this->request->input('user', '');
        $term = $this->request->input('term', '');
        if ($action= $this->request->input('action') == 'delete') {
            if ($this->isDemo()) $this->defendDemo();
            $this->model('admin')->deleteTrack($this->request->input('id'));
        }

        if ($val = $this->request->input('val')) {
            if ($this->isDemo()) $this->defendDemo();
            $track = $this->model('track')->findTrack($val['id']);
            $validator = Validator::getInstance()->scan($val, array(
                'titles' => 'required',
                'tags' => 'required'
            ));
            if ($validator->passes()) {
                if ($val['day'] and $val['month'] and $val['year']) {
                    $val['release'] = date("Y-m-d", mktime(0, 0, 0, $val['month'], $val['day'], $val['year']));
                } else {
                    $val['release'] = '';
                }

                $artFile = $this->request->inputFile('img');
                if ($artFile) {
                    $artUpload = new Uploader($artFile);
                    $artUpload->setPath("tracks/".$this->model('user')->authId.'/art/'.date('Y').'/');
                    if ($artUpload->passed()) {
                        $val['art'] = $artUpload->resize()->result();
                    } else {
                        return json_encode(array(
                            'message' => $artUpload->getError(),
                            'type' => 'error'
                        ));
                    }
                }

                if ($lyricsFile = $this->request->inputFile('lyrics_file')) {
                    $upload = new Uploader($lyricsFile, 'file');
                    $upload->setPath("tracks/".$this->model('user')->authId.'/lyric/'.date('Y').'/');
                    if ($upload->passed()) {
                        $val['lyrics'] = $upload->uploadFile()->result();
                    } else {
                        return json_encode(array(
                            'message' => l('lyric-file-error'),
                            'type' => 'error'
                        ));
                    }
                }

                $this->model('track')->add($val, $track, true);

                return json_encode(array(
                    'type' => 'modal-url',
                    'value' => getFullUrl(true),
                    'message' => l('saved-success'),
                    'content' => "#editTrackModal-".$val['id']
                ));
            } else {
                return json_encode(array(
                    'message' => $validator->first(),
                    'type' => 'error'
                ));
            }

        }
        $tracks = $this->model('admin')->getTracks($genre, $term, $user);
        return $this->render($this->view('admin/tracks/index', array('tracks' => $tracks, 'genre' => $genre, 'term' => $term, 'user' => $user)), true);
    }

    public function payments() {
        $this->adminSecure('manage-payments');
        $this->setTitle(l('manage-payments'));
        $this->addBreadCrumb(l('manage-payments'));
        $this->activeMenu = 'payments';

        if ($id = $this->request->input('id')) {
            if ($this->isDemo()) $this->defendDemo();
            $status = $this->request->input('status', 0);
            $this->model('admin')->updatePayment($id, $status);
        }
        $payments = $this->model('admin')->getPayments($this->request->input('user', ''));
        return $this->render($this->view('admin/payments/index', array('payments' => $payments)), true);
    }

    public function reports() {
        $this->adminSecure('manage-report');
        $this->setTitle(l('manage-reports'));
        $this->addBreadCrumb(l('manage-reports'));
        $this->activeMenu = 'reports';

        if ($action = $this->request->input('action')) {
            if ($this->isDemo()) $this->defendDemo();
            $this->model('admin')->reportAction($action, $this->request->input('id'));
        }

        $reports = $this->model('admin')->getReports();
        return $this->render($this->view('admin/reports/index', array('reports' => $reports)), true);
    }

    public function ads() {
        $this->adminSecure('manage-ads');
        $this->setTitle(l('manage-ads'));
        $this->addBreadCrumb(l('manage-ads'));
        $this->activeMenu = 'ads';


        if ($val = $this->request->input('val', null, false)) {
            if ($this->isDemo()) $this->defendDemo();
            $this->model('admin')->saveSettings($val);
            return json_encode(array(
                'type' => 'url',
                'message' => l('ads-unit-saved'),
                'value' => url('admin/ads')
            ));
        }
        return $this->render($this->view('admin/ads/index'), true);
    }

    public function pages() {
        $this->adminSecure('manage-info');
        $this->setTitle(l('info-pages'));
        $this->addBreadCrumb(l('info-pages'));
        $this->activeMenu = 'pages';
        if ($action = $this->request->input('action')) {
            $this->model('admin')->deletePage($this->request->input('id'));
        }
        $pages = $this->model('admin')->getPages();
        return $this->render($this->view('admin/pages/index', array('pages' => $pages)), true);
    }

    public function addPage() {
        $this->adminSecure('manage-info');
        $this->setTitle(l('info-pages'));
        $this->addBreadCrumb(l('info-pages'));
        $this->activeMenu = 'pages';
        $this->useEditor = true;

        if ($val = $this->request->input('val', null, false)) {
            if ($this->isDemo()) $this->defendDemo();
            $validator = Validator::getInstance()->scan($val, array(
                'title' => 'required',
                'url' => 'required|alphadash',
                'content' => 'required',
            ));

            if ($validator->passes()) {
                $this->model('admin')->savePages($val);
                return json_encode(array(
                    'type' => 'url',
                    'value' => url('admin/pages'),
                    'message' => l('info-page-saved')
                ));
            } else {
                return json_encode(array(
                    'message' => $validator->first(),
                    'type' => 'error'
                ));
            }

        }
        return $this->render($this->view('admin/pages/add'), true);
    }

    public function editPage() {
        $this->adminSecure('manage-info');
        $this->setTitle(l('info-pages'));
        $this->addBreadCrumb(l('info-pages'));
        $this->activeMenu = 'pages';
        $this->useEditor = true;

        $page = $this->model('admin')->findPage($this->request->input('id'));
        if (!$page) return $this->request->redirect(url('admin/pages'));

        if ($val = $this->request->input('val', null, false)) {
            if ($this->isDemo()) $this->defendDemo();
            $validator = Validator::getInstance()->scan($val, array(
                'title' => 'required',
                'url' => 'required|alphadash',
                'content' => 'required',
            ));

            if ($validator->passes()) {
                $this->model('admin')->savePages($val);
                return json_encode(array(
                    'type' => 'url',
                    'value' => url('admin/pages'),
                    'message' => l('info-page-saved')
                ));
            } else {
                return json_encode(array(
                    'message' => $validator->first(),
                    'type' => 'error'
                ));
            }

        }
        return $this->render($this->view('admin/pages/edit', array('page' => $page)), true);
    }

    public function newsletter() {
        $this->adminSecure('send-newsletter');
        $this->setTitle(l('newsletter'));
        $this->addBreadCrumb(l('newsletter'));
        $this->activeMenu = 'newsletter';
        $this->useEditor = true;
        if ($val = $this->request->input('val', null, false)) {
            if ($this->isDemo()) $this->defendDemo();
            $to = $val['to'];
            $content = nl2br($val['text']);
            $subject = $val['subject'];
            if (empty($content) or empty($subject)) {
                return json_encode(array(
                    'type' => 'error',
                    'message' => l('you-cannot-empty-newsletter')
                ));
            } else {
                $this->model('admin')->sendNewsLetter($to, $subject, $content);
                return json_encode(array(
                    'type' => 'url',
                    'value' => url('admin/newsletter'),
                    'message' => l('newsletter-sent')
                ));
            }
        }
        return $this->render($this->view('admin/newsletter/index'), true);
    }

    public function design() {
        $this->adminSecure('manage-site-design');
        $this->setTitle(l('site-design'));
        $this->addBreadCrumb(l('site-design'));
        $this->activeMenu = 'design';

        if ($val = $this->request->input('val', null, false)) {
            if ($this->isDemo()) $this->defendDemo();
            $theme = $val['theme'];
            $options = array('theme' => $theme, 'custom-js' => $val['custom-js'], 'custom-css' => $val['custom-css'],'separate-login' => $val['separate-login']);
            foreach($val as $key => $value) {
                if ($key != 'theme' and $key != 'custom-css' and $key != 'custom-js' and $key != 'separate-login') {
                    $options[$theme.'-'.$key] = $value;
                }
            }

            $this->model('admin')->saveSettings($options);
            return $this->request->redirect(url('admin/design'));
        }

        if ($restore = $this->request->input('restore')) {
            $theme = config('theme', 'main');
            $colors = include(path('styles/'.config('theme', 'main').'/colors.php'));

            foreach($colors as $segment => $info) {
                foreach($info['colors']  as $id => $detail) {
                    $options[$theme.'-'.$id] = $detail['color'];
                }
            }
            $this->model('admin')->saveSettings($options);

            return $this->request->redirect(url('admin/design'));
        }
        return $this->render($this->view('admin/design/index'), true);
    }

    public function promote() {
        $id = $this->request->input('id');
        $user = $this->model('user')->getUser($id);
        if ($this->isDemo()) $this->defendDemo();
        $this->model('admin')->addTransaction(array(
            'name' => $user['full_name'],
            'email' => 'Promoted',
            'country' =>'Promoted',
            'amount' => 'Promoted',
            'sale_id' => 'Promoted',
            'type' => ($user['user_type'] == 2) ? 'pro' : 'pro-users',
            'type_id' => 'monthly',
            'userid' => $user['id']
        ));
        return $this->request->redirect(url('admin/user/edit', array('id' => $id)));
    }

    public function update() {
        $currentVersion = str_replace('.', '', VERSION);
        if (file_exists(path("update/update$currentVersion.php"))) {
            if ($this->isDemo()) $this->defendDemo();

            include(path("update/update$currentVersion.php"));

            if (is_ajax()) {
                return json_encode(array(
                    'type' => 'url',
                    'value' => url('admin/settings'),
                    'message' => l('database-updated')
                ));
            } else {
                return $this->request->redirectBack();
            }
        } else {
            return $this->request->redirectBack();
        }
    }

    public function playlist() {
        $this->adminSecure('manage-albums');
        $this->setTitle(l('manage-playlist-album'));
        $this->addBreadCrumb(l('manage-playlist-album'));
        $this->activeMenu  = "playlist";

        if ($val = $this->request->input('val')) {
            if ($this->isDemo()) $this->defendDemo();
            $this->model('admin')->savePlaylist($val);
            return json_encode(array(
                'type' => 'modal-url',
                'value' => getFullUrl(true),
                'message' => l('saved-success'),
                'content' => "#editPlaylistModal-".$val['id']
            ));
        }
        $page = $this->request->segment(1, 'playlists');

        return $this->render($this->view('admin/playlist/index', array('page' => $page)), true);
    }

    public function deletePlaylist() {
        $playlist = model('admin')->findPlaylist($this->request->input('id'));
        if ($this->isDemo()) $this->defendDemo();
        $this->model('admin')->deletePlaylist($playlist);
        $url = ($playlist['playlist_type'] == 0) ? url('admin/albums') : url('admin/playlists');
        $this->request->redirect($url);
        return json_encode(array(
            'type' => 'url',
            'value' => $url,
            'message' => l('deleted-success')
        ));
    }

    public function verify() {
        $this->setTitle(l('verification-requests'));
        $this->activeMenu = "verify";

        if ($action = $this->request->input('action')) {
            if ($action == "approve") {
                Database::getInstance()->query("UPDATE users SET user_type=?,verify_details=? WHERE id=?", 2, '', $this->request->input('id'));
            } else {
                Database::getInstance()->query("UPDATE users SET verify_details=? WHERE id=?",'', $this->request->input('id'));
            }
        }

        return $this->render(view('admin/users/verify'), true);
    }

    public function updateWave() {
        $from = $this->request->input('from');
        $start = $this->request->input('start', false);
        $total = $this->request->input('total', 0);
        $type = $this->request->input('server', 0);
        $limit = ($type) ? 3 : 50;
        if ($this->isDemo()) $this->defendDemo();
        if (!$start) {
            return json_encode(array(
                'total' => model('track')->countTotalTracks(),
                'from' => 0,
                'continue' => true,
                'message' => l('update-waves-starting')
            ));
        } else {
            $result = $this->model('track')->replaceWaveImages($limit,$from, $type);
            return json_encode(array(
                'from' => $from + $limit,
                'total' => $total,
                'continue' => $result,
                'message' => ($result) ? l('update-waves-success') : l('update-waves-finished')
            ));
        }
    }

    public function bankTransfer() {
        $this->adminSecure('manage-payments');
        $this->setTitle(l('manage-payments'));
        $this->addBreadCrumb(l('manage-payments'));
        $this->activeMenu = 'payments';

        if ($action= $this->request->input('action')) {
            if ($this->isDemo()) $this->defendDemo();
            $this->model('admin')->doBankAction($this->request->input('id'), $action);
        }

        return $this->render($this->view('admin/payments/bank'), true);
    }
}