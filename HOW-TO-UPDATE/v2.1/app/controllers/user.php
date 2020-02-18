<?php
class UserController extends Controller {
    public function settings() {
        $this->setTitle(l('account-settings'));
        $this->addBreadCrumb(l('account-settings'));

        $type = $this->request->segment(1 , 'account');
        $this->addBreadCrumb(l($type));


        switch ($type) {
            case 'delete':

                if ($password = $this->request->input('password')) {
                    if (!hash_check($password, $this->model('user')->authUser['password'])) {
                        return json_encode(array(
                            'type' => 'error',
                            'message' => l('wrong-current-password')
                        ));
                    }

                    $this->model('user')->delete($this->model('user')->authId);
                    return json_encode(array(
                        'type' => 'url',
                        'value' => url(),
                        'message' => l('account-deleted')
                    ));
                }
                $content = $this->view('user/settings/delete');
                break;
            case 'blocked':
                if ($action = $this->request->input('action')) {
                    $this->model('user')->unblock($this->request->input('id'));
                    return json_encode(array(
                        'type' => 'url',
                        'message' => l('user-unblocked'),
                        'value' => url('settings/blocked')
                    ));
                }
                $content = $this->view('user/settings/blocked');
                break;
            case 'two-factor':
                if ($action = $this->request->input('action')) {
                    $this->model('user')->toggleTwoFactor($action);
                    return json_encode(array(
                        'type' => 'url',
                        'message' => ($action) ? l('two-factor-authentication-enabled') : l('two-factor-authentication-disabled'),
                        'value' => url('settings/two-factor')
                    ));
                }
                $content = $this->view('user/settings/two-factor');
                break;
            case 'password':
                if ($val = $this->request->input('val')) {
                    /**
                     * @var $current
                     * @var $new
                     * @var $confirm
                     */
                    extract($val);
                    if (!hash_check($current, $this->model('user')->authUser['password'])) {
                        return json_encode(array(
                            'type' => 'error',
                            'message' => l('wrong-current-password')
                        ));
                    }

                    if ($new != $confirm) {
                        return json_encode(array(
                            'type' => 'error',
                            'message' => l('new-password-not-match')
                        ));
                    }

                    $this->model('user')->changePassword($new);
                    return json_encode(array(
                        'type' => 'success',
                        'message' => l('password-changed')
                    ));
                }
                $content = $this->view('user/settings/password');

                break;
            case 'social':
                if($val = $this->request->input('val')) {
                    $this->model('user')->saveUser($val, $this->model('user')->authUser);
                    return json_encode(array(
                        'type' => 'success',
                        'message' => l('account-settings-saved')
                    ));
                }

                $content = $this->view('user/settings/social');
                break;
            case 'notification':
                if($val = $this->request->input('val')) {
                    $this->model('user')->saveUser($val, $this->model('user')->authUser);
                    return json_encode(array(
                        'type' => 'success',
                        'message' => l('notification-settings-saved')
                    ));
                }
                $content = $this->view('user/settings/notification');
                break;
            case 'pro':
                $content = $this->view('user/settings/pro');
                break;
            case 'profile':
                if ($this->request->inputFile('avatar') or $this->request->inputFile('cover')) {
                    $avatar = $this->request->inputFile('avatar');
                    $cover = $this->request->inputFile('cover');
                    if ($avatar) {
                        
                        $uploader = new Uploader($avatar);
                        $uploader->setPath('avatar/'.$this->model('user')->authId.'/');
                        if ($uploader->passed()) {
                            $avatar = $uploader->resize()->result();
                            $user = $this->model('user')->getUser($this->model('user')->authId);
                            $this->model('user')->saveUser(array('avatar' => $avatar), $user);
                        } else {
                            return json_encode(array('type' => 'error', 'message' => $uploader->getError()));
                        }
                    }

                    if ($cover) {
                        $uploader = new Uploader($cover);
                        $uploader->setPath('cover/'.$this->model('user')->authId.'/');
                        if ($uploader->passed()) {
                            $cover = $uploader->uploadFile()->result();
                            $user = $this->model('user')->getUser($this->model('user')->authId);
                            $this->model('user')->saveUser(array('cover' => $cover), $user);
                        } else {
                            return json_encode(array('type' => 'error', 'message' => $uploader->getError()));
                        }
                    }

                    return json_encode(array(
                        'type' => 'success',
                        'message' => l('account-settings-saved')
                    ));
                }
                $content = $this->view('user/settings/profile');
                break;
            default:
                $result  = Hook::getInstance()->fire('settings.content', array('content' => ''), array($type));
                if ($result['content']) {
                    $content = $result['content'];
                } else {
                    if($val = $this->request->input('val')) {
                        $validator = Validator::getInstance()->scan($val, array(
                           'username' => 'required|username|predefined|alphanum',
                            'email' => 'required|email'
                        ));

                        if($validator->passes()) {
                            $user = model('user')->authUser;
                            if ($user['username'] != $val['username']) {
                                //check for other users with same name
                                if (model('user')->otherUseUsername($val['username'], $user['id'])) {
                                    return json_encode(array(
                                        'message' => l('selected-username-is-used-by-others'),
                                        'type' => 'error'
                                    ));
                                }
                            }

                            if($val['email'] != $user['email']) {
                                if (model('user')->otherUseEmail($val['email'], $user['id'])) {
                                    return json_encode(array(
                                        'message' => l('selected-email-is-used-by-others'),
                                        'type' => 'error'
                                    ));
                                }
                            }
                            $this->model('user')->saveUser($val, $this->model('user')->authUser);
                            return json_encode(array(
                                'type' => 'success',
                                'message' => l('account-settings-saved')
                            ));
                        } else {
                            return json_encode(array(
                                'message' => $validator->first(),
                                'type' => 'error'
                            ));
                        }


                    }
                    $content = $this->view('user/settings/account');
                }
                break;
        }
        return $this->render($this->view('user/settings/layout', array('type' => $type, 'content' => $content)), true);
    }

    public function profile() {
        $slug = explode('-', $this->request->segment(1));
        $userId = $this->request->segment(0);
        $page = $this->request->segment(1, 'stream');
        $user = $this->model('user')->getUser($userId);
        if (!$user) {
            return $this->errorPage();
        }
        $this->setTitle($user['full_name'], true);
        $this->addBreadCrumb($user['full_name'] , ($page != 'stream') ? $this->model('user')->profileUrl($user) : '');
        if ($page != 'stream') $this->addBreadCrumb(l($page));
        $this->useBreadcrumbs = false;

        $headerContent = '<meta property="og:image" content="'.$this->model('user')->getAvatar($user, 600).'"/>';
        $headerContent .= '<meta property="og:title" content="'.$user['full_name'].'"/>';
        $headerContent .= '<meta property="og:url" content="'.$this->model('user')->profileUrl($user).'"/>';
        $headerContent .= '<meta property="og:description" content="'.config('site-description', '').'"/>';
        $this->addHeaderContent($headerContent);

        switch ($page) {
            case 'tracks':
                $content = $this->view('user/profile/tracks', array('type' => 'my-tracks','user' => $user));
                break;
            case 'reposts':
                $content = $this->view('user/profile/tracks', array('type' => 'my-reposts','user' => $user));
                break;
            case 'albums':
                $content = $this->view('playlist/lists', array('type' => 'album', 'typeId' => 'profile-'.$user['id']));
                break;
            case 'playlists':
                $content = $this->view('playlist/lists', array('type' => 'playlist', 'typeId' => 'profile-'.$user['id']));
                break;
            case 'followers':
                $content = $this->view('track/profile/people', array('type' => $page, 'pageType' => 'track', 'pageId' => $user['id']));
                break;
            case 'following':
                $content = $this->view('track/profile/people', array('type' => $page, 'pageType' => 'track', 'pageId' => $user['id']));
                break;
            case 'liked':
                $content = $this->view('user/profile/tracks', array('type' => 'my-liked','user' => $user));
                break;
            default:
                $result = Hook::getInstance()->fire('profile.content', array('content' => ''), array($user,$page));
                if ($result['content']) {
                    $content = $result['content'];
                } else {
                    $content = $this->view('user/profile/details', array('user' => $user));
                }
                break;
        }


        return $this->render($this->view('user/profile/layout', array('user' => $user, 'content' => $content, 'page' => $page)), true);

    }

    public function follow() {
        $id = $this->request->input('id');


        $action = $this->model('user')->follow($id);
        $user = $this->model('user')->getUser($id);
        return json_encode(array(

            'type' => 'function',
            'value' => 'followFinished',
            'content' => array(
                'id' => $user['id'],
                'count' => $this->model('user')->countFollowers($id),
                'text' => ($action) ? l('following') : l('follow'),
                'action' => ($action) ? true : false,
            ),
            'message' => ($action) ? l('you-follow').' '.$user['full_name']  : ''
        ));
    }

    public function block() {
        $id = $this->request->input('id');
        if ($id != $this->model('user')->authId) {
            $action = $this->model('user')->block($id);
            return json_encode(array(
                'type' => 'url',
                'value' => url('settings/blocked')
            ));
        }

    }

    public function paginateArtists() {
        $data = perfectUnserialize($this->request->input('data'));
        $offset = $this->request->input('offset');
        $dLimit = (isset($data['limit'])) ? $data['limit'] : 20;
        $type = (isset($data['type'])) ? $data['type'] : 'top';
        $term = (isset($data['term'])) ? $data['term'] : '';
        $limit = $dLimit*2;
        $theOffset = ($offset == 0) ? $dLimit : $offset;
        $newOffset = $offset + $limit;

        $users = $this->model('user')->getPeople('top', $term,  $limit, $theOffset);

        $content =  $this->view('user/paginate', array('users' => $users));
        $result = array(
            'content' => $content,
            'offset' => $newOffset
        );
        return json_encode($result);
    }

    public function notificationDropdown() {
        return $this->view('notification/dropdown');
    }

    public function notifications() {
        $this->setTitle(l('notifications'));
        $this->addBreadCrumb(l('notifications'));

        return $this->render($this->view('notification/list'), true);
    }

    public function notificationPaginate() {
        $data = perfectUnserialize($this->request->input('data'));
        $offset = $this->request->input('offset');
        $dLimit = (isset($data['limit'])) ? $data['limit'] : 20;
        $limit = $dLimit*2;
        $theOffset = ($offset == 0) ? $dLimit : $offset;
        $newOffset = $offset + $limit;

        $notifications = $this->model('user')->getNotifications($theOffset,$limit);

        $content =  $this->view('notification/paginate', array('notifications' => $notifications));
        $result = array(
            'content' => $content,
            'offset' => $newOffset
        );
        return json_encode($result);
    }

    public function checkNotification() {
        $lastTime = $this->request->input('time');
        $push = $this->request->input('push', false);
        if (!$this->model('user')->isLoggedIn()) exit('login');
        $result = array(
            'notification' => $this->model('user')->countUnreadNotifications(),
            'messages' => $this->model('message')->countUnread(),
            'time' => time(),
            'latestNotifications' => array()
        );

        if ($push) {
            $latestNotifications  = $this->model('user')->getNewUnreadNotifications($lastTime);
            $result['latestNotifications'] = $latestNotifications;
            $latestMessages = $this->model('message')->getNewUnreadMessages($lastTime);
            $result['latestMessages'] = $latestMessages;
        }
        return json_encode($result);
    }

    public function userCard() {
        $userid = $this->request->input('id');
        $user = $this->model('user')->getUser($userid);

        return $this->view('user/card', array('user' => $user));
    }

    public function userSearch() {
        $term = $this->request->input('term');

        $users = $this->model('user')->searchConnections($term, true);

        $result = array();
        foreach($users as $user) {
            $result[] = array('value' => $user['id'], 'text' => $user['full_name']);
        }

        return json_encode($result);
    }

    public function twoFactor() {
        $this->setTitle(l('two-factor-authentication'));
        $userid = $this->request->input('id');
        $code = $this->request->input('code');
        $message = null;

        $this->setLayout("includes/plain-layout");
        if ($code) {
            $user = $this->model('user')->findUserWithCode($code);
            if ($user) {
                model('user')->loginWithObject($user);
                $url = url();
                return $this->request->redirect($url);
            } else {
                $message = l('code-expired');
            }
        }
        if ($userid) {

            $user = $this->model('user')->getUser($userid);
            if (!$user) return $this->request->redirect(url());
            if ($this->request->input('resend')) {
                $this->model('user')->sendTwoFactorLink($userid, $user['email'], $user['full_name']);
            }
            return $this->render($this->view('user/two-factor', array('user' => $user)));
        }
    }

    public function activate() {
        $this->setTitle(l('activate-account'));
        $code = $this->request->input('code');
        $message = null;

        if ($code) {
            $user = $this->model('user')->findUserWithCode($code);
            if ($user) {
                $this->model('user')->activateUser($code, $user);
                $url = url();
                if (config('enable-welcome', true)) {
                    $url = url('welcome');
                }
                return $this->request->redirect($url);
            } else {
                $message = l('code-expired');
            }
        }

        $userid = $this->request->input('id');
        $this->setLayout("includes/plain-layout");
        if ($userid) {
            $user = $this->model('user')->getUser($this->request->input('id'), true, 0);
            if (!$user) return $this->request->redirect(url());
            if ($user['active']) return $this->request->redirect(url());
            if ($this->request->input('resend')) {
                $this->model('user')->sendActivationLink($userid, $user['email'], $user['full_name']);
            }
            return $this->render($this->view('user/activate', array('user' => $user)));
        }
    }

    public function forgot() {
        $email = $this->request->input('email');
        $user = $this->model('user')->getUser($email, false);

        if ($user) {
            $this->model('user')->sendPasswordResetLink($user);
            return json_encode(array(
                'message' => l('forgot-link-sent'),
                'type' => 'modal-function',
                'modal' => '#authModal'
            ));
        } else {
            return json_encode(array(
                'message' => l('provide-credential-not-match'),
                'type' => 'error'
            ));
        }
    }
    public function reset() {
        $this->setTitle(l('reset-password'));
        $this->setLayout("includes/plain-layout");
        $code = $this->request->input('code');

        $user = $this->model('user')->findUserWithCode($code);
        if (!$code or !$user) {
            exit(l('code-expired'));
        }
        if ($val = $this->request->input('val')) {
            $password = $val['password'];
            $password2 = $val['password2'];
            if (!$password or !$password2) {
                return json_encode(array(
                    'message' => l('all-field-required'),
                    'type' => 'error'
                ));
            }

            if ($password2 != $password) {
                return json_encode(array(
                    'message' => l('the-password-does-notmatch'),
                    'type' => 'error'
                ));
            }
            $this->model('user')->resetPassword($password,$user);
            $this->model('user')->loginUser($user['username'], $password, true);
            return json_encode(array(
                'message' => l('password-changed'),
                'type' => 'normal-url',
                'value' => url()
            ));
        }
        return $this->render($this->view('user/reset'));
    }

    public function userSuggests() {
        $people = $this->model('user')->getPeople('suggestion', null, 3);
        return $this->view('user/suggest', array('users' => $people));
    }

    public function verify() {
        if ($val = $this->request->input('val')) {
            $validator = Validator::getInstance()->scan($val, array(
                'name' => 'required',
                'record' => 'required'
            ));

            if ($validator->passes()) {
                $file = $this->request->inputFile('file');
                if (!$file) {
                    return json_encode(array(
                        'type' => 'error',
                        'message' => l('upload-your-password')
                    ));
                }
                $uploader = new Uploader($file, 'image');
                $uploader->setPath("verification/".$this->model('user')->authId.'/passport/');
                if ($uploader->passed()) {
                    $val['passport'] = $uploader->uploadFile()->result();
                } else {
                    return json_encode(array(
                        'message' => $uploader->getError(),
                        'type' => 'error'
                    ));
                }
                $this->model('user')->verify($val);
                return json_encode(array(
                    'type' => 'modal-url',
                    'content' => '#artistVerification',
                    'value' => url('settings'),
                    'message' => l('verification-request-sent'),

                ));
            } else {
                return json_encode(array(
                    'message' => $validator->first(),
                    'type' => 'error'
                ));
            }


        }
    }
}