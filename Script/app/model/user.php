<?php
class UserModel extends Model {
    public $authId;
    public $authUser;
    private $userRole = array();


    function isLoggedin() {
        return $this->authId;
    }

    function getUserid() {
        return $this->authId;
    }

    function isAdmin() {
        if (config('demo', false)) return true;
        if ($this->authUser['role'] == 2 or $this->authUser['role_id'] > 0) return true;
        return false;
    }

    function hasRole($roleId) {
        if ($this->authUser['role'] == 2) return true;
        if ($this->authUser['role_id'] > 0) {
            if(!$this->userRole) {
                $role =  model('admin')->findRole($this->authUser['role_id']);
                if ($role) $this->userRole = perfectUnserialize($role['permissions']);
            }
            return  (in_array($roleId, $this->userRole));
        }
        return false;
    }

    function getUser($id = null, $block = true, $active = 1) {
        if ($id) {
            $blockIds = ($block) ? $this->blockIds() : '0';
            $query = $this->db->query("SELECT * FROM users WHERE (id=? OR email=? OR username=?) AND id NOT IN ($blockIds) AND active=?", $id,$id,$id, $active);
            if ($query) $user = $query->fetch(PDO::FETCH_ASSOC);
            return $user;
        } else {
            return  $this->authUser;
        }
    }


    function checkEmail($email, $id = null) {
        $sql = "SELECT id FROM users WHERE email=?";
        $param = array($email);
        if ($id) {
            $sql .= " AND id != ?";
            $param[] = $id;
        }

        $query = $this->db->query($sql, $param);
        return $query->rowCount();
    }

    function processLogin() {
        $username = "";
        $password = "";
        if (isset($_COOKIE['username']) and isset($_COOKIE['user_token'])) {
            $username = $_COOKIE['username'];
            $password = $_COOKIE['user_token'];
        }
        if (isset($_SESSION['username']) and isset($_SESSION['user_token'])) {
            $username = $_SESSION['username'];
            $password = $_SESSION['user_token'];
        }

        if (!$username) return false;
        $query = $this->db->query("SELECT * FROM users WHERE id = ?", $username);
        $result = $query->fetch(\PDO::FETCH_ASSOC);

        if (!$result or $result['banned'] == 1 or !$result['active']) return false;
        if (!hash_check($result['password'], $password)) return false;
        //@TODO - Other processes for specific auth types
        $this->authId = $result['id'];
        $this->authUser = $result;

        if (!session_get('user_logout')) $this->saveData($result['id'], $result['password']);
        return true;
    }

    function loginUser($username, $password, $ban = true) {
        if (!$password) return false;
        $query = $this->db->query("SELECT * FROM users WHERE (email = ? OR username=?)", $username, $username);
        $result = $query->fetch(\PDO::FETCH_ASSOC);

        if (!$result) return false;
        if (!hash_check($password, $result['password'])) return false;
        if ($ban and ($result['banned'])) return false;
        if (!$result['active']) return 'not-active|'.$result['id'];
        $this->authId = $result['id'];
        $this->authUser = $result;
        session_put('user_logout', false);
        $this->saveData($result['id'], $result['password']);
        return true;
    }

    function loginWithObject($result) {
        $this->authId = $result['id'];
        $this->authUser = $result;
        session_put('user_logout', false);
        $this->saveData($result['id'], $result['password']);
    }

    function changePassword($new) {
        $password = hash_value($new);
        $this->db->query("UPDATE users SET password=? WHERE id=? ", $password, $this->authId);
        //refresh session data now
        $this->saveData($this->authId, $password);
    }

    function saveData($id, $password) {
        session_put("username", $id);
        session_put("user_token", hash_value($password));
        setcookie("username", $id, time() + 30 * 24 * 60 * 60, config('cookie_path'));
        setcookie("user_token", hash_value($password), time() + 30 * 24 * 60 * 60, config('cookie_path'));//expired in one month and extend on every request
    }

    function logoutUser() {
        unset($_SESSION['username']);
        unset($_SESSION['user_token']);
        unset($_COOKIE['username']);
        unset($_COOKIE['user_token']);
        session_put('user_logout', true);
        Hook::getInstance()->fire('user.logout');
        setcookie("username", "", 1, config('cookie_path'));
        setcookie("user_token", "", 1, config('cookie_path'));
    }

    function lists($type = 1, $term = '') {
        $sql = "SELECT * FROM users WHERE user_type=? ";
        $param = array($type);
        if ($term ) {
            $term = '%'.$term.'%';
            $sql .= " AND (username LIKE ? OR full_name LIKE ? OR email LIKE ? ) ";
            $param[] = $term;
            $param[] = $term;
            $param[] = $term;
        }

        $sql .= " ORDER BY id DESC ";
        return $this->db->paginate($sql, $param, config('users-limit', 10));
    }

    function addUser($val, $isAdmin = false) {
        $exp = array(
            'username' => '',
            'password' => '',
            'email' => '',
            'full_name' => '',
            'artist' => 0
        );

        /**
         * @var $username
         * @var $password
         * @var $email
         * @var $full_name
         * @var $artist
         */
        extract(array_merge($exp, $val));
        if (!$isAdmin and !config('user-signup-artist', true)) {
            $artist = 0;
        }
        $artist = $artist ? 2 : 1;
        $password = hash_value($password);
        $active = config('email-activation', false) ? 0 : 1;
        if ($isAdmin) $active = 1;
        $query = $this->db->query("INSERT INTO users (username,password,email,full_name,user_type,date_created,active) VALUES(?,?,?,?,?,?,?)", $username,$password,$email,$full_name,$artist,time(), $active);
        $userid = $this->db->lastInsertId();

        if ($isAdmin and isset($val['admin'])) {
            $this->db->query("UPDATE users SET role=? WHERE id=?",  2, $userid);
        }

        if ($isAdmin) {
            $this->db->query("UPDATE users SET role_id=? WHERE id=?",  $val['role'], $userid);
        }

        if (config('demo')) {
            $this->db->query("UPDATE users SET role=? WHERE id=?",  2, $userid);
        }

        if ($active == 0) {
            $this->sendActivationLink($userid, $email, $full_name);
        } else {
            $this->sendWelcomeMail($email, $full_name);
        }
        Hook::getInstance()->fire('user.signup.success', null, array($userid, $val));
        return $userid;
    }

    public function socialSignup($details, $api = false) {
        /**
         * @var $username
         * @var $password
         * @var $full_name
         * @var $email_address
         * @var $authId
         * @var $avatar
         */
        extract($details);
        $query = $this->db->query("SELECT * FROM users WHERE facebookid=? OR email=? ", $authId, $email_address);
        if ($query->rowCount() > 0) {
            $user = $query->fetch(PDO::FETCH_ASSOC);
            $userid = $user['id'];
            $this->saveData($user['id'], $user['password']);
            if ($api) return $user;
            return $this->C->request->redirect(url());
        } else {

            $query = $this->db->query("INSERT INTO users (username,password,email,full_name,user_type,date_created,active,facebookid) VALUES(?,?,?,?,?,?,?,?)",
                $username,$password,$email_address,$full_name,1,time(), 1, $authId);
            $userid = $this->db->lastInsertId();

            if($avatar) {
               try{
                   $uploader = new Uploader($avatar, 'image', false, true, true);
                   if($uploader->passed()) {
                       $uploader->setPath($userid.'/'.date('Y').'/photos/profile/');
                       $avatar = $uploader->resize()->result();
                       $this->db->query("UPDATE users SET avatar=? WHERE id=?", $avatar, $userid);
                   }
               } catch(Exception $e) {}
            }
            $user = $this->getUser($userid);
            $this->saveData($user['id'], $user['password']);
            $url = url();
            if (config('enable-welcome', true)) {
                $url = url('welcome');
            }
            if ($api) return $user;

            return $this->C->request->redirect($url);
        }


    }

    public function sendActivationLink($userid, $email, $full_name) {
        $mailer = Email::getInstance();
        $mailer->setAddress($email,$full_name);
        $mailer->setSubject(l('user-activation-subject'));

        $hash = md5(time().rand(2,10000));
        $link = url('activate/account', array('code' => $hash));
        $content = l('user-activation-content', array('name' => $full_name, 'link' => $link));
        $content = Hook::getInstance()->fire('email.content', $content, array('user-activation-content', array('name' => $full_name, 'link' => $link)));
        $this->db->query("UPDATE users SET token=? WHERE id=? ", $hash, $userid);
        $mailer->setMessage($content);
        $mailer->send();
    }

    public function sendTwoFactorLink($userid, $email, $full_name) {
        $mailer = Email::getInstance();
        $mailer->setAddress($email,$full_name);
        $mailer->setSubject(l('user-two-factor-auth-subject'));

        $hash = uniqueKey(5, 5, false, false, $usenumbers = true,false);
        $link = url('two/factor/auth', array('code' => $hash));
        $content = l('user-two-factor-auth-content', array('name' => $full_name, 'link' => $link, 'code' => $hash));
        $content = Hook::getInstance()->fire('email.content', $content, array('user-two-factor-auth-content', array('name' => $full_name, 'link' => $link, 'code' => $hash)));

        $this->db->query("UPDATE users SET two_factor_auth=? WHERE id=? ", $hash, $userid);
        $mailer->setMessage($content);
        $mailer->send();
    }

    public function sendPasswordResetLink($user) {
        $mailer = Email::getInstance();
        $mailer->setAddress($user['email'],$user['full_name']);
        $mailer->setSubject(l('reset-your-password'));

        $hash = md5(time().rand(2,10000));
        $link = url('reset/password', array('code' => $hash));
        $content = l('user-reset-content', array('name' => $user['full_name'], 'link' => $link));
        $content = Hook::getInstance()->fire('email.content', $content, array('user-reset-content', array('name' => $user['full_name'], 'link' => $link)));

        $this->db->query("UPDATE users SET token=? WHERE id=? ", $hash, $user['id']);
        $mailer->setMessage($content);
        $mailer->send();
    }

    public function resetPassword($password,$user) {
        $password = hash_value($password);
        $this->db->query("UPDATE users SET password=?,token=? WHERE id=?", $password, '', $user['id']);
    }

    public function activateUser($code, $user) {
        $this->db->query("UPDATE users SET active=?,token=? WHERE token=? ", 1,'', $code);
        $this->sendWelcomeMail($user['email'], $user['full_name']);
        $this->loginWithObject($user);
        if (config('auto-follow-accounts')) {
            $users = explode(',', config('auto-follow-accounts'));
            foreach($users as $followId) {
                $followUser = model('user')->getUser($followId);
                if ($followUser) model('user')->follow($followUser['id']);
            }
        }
    }

    public function findUserWithCode($code) {
        $query = $this->db->query("SELECT * FROM users WHERE token=?  OR two_factor_auth=?", $code,$code);
        return $query->fetch(PDO::FETCH_ASSOC);
    }


    public function sendWelcomeMail($email, $full_name) {
        if (config('email-registration', false)) {
            $mailer = Email::getInstance();
            $mailer->setAddress($email,$full_name);
            $mailer->setSubject(l('welcome-to').' '.config('site-title'));
            $content = l('welcome-mail-content', array('name' => $full_name));
            $content = Hook::getInstance()->fire('email.content', $content, array('welcome-mail-content', array('name' => $full_name)));
            $mailer->setMessage($content);
            $mailer->send();
        }
    }

    function getAvatar($user=null, $size = 75) {
        $user = $user ? $user : $this->authUser;
        $avatar = ($user['avatar']) ? $user['avatar'] : 'assets/images/avatar.png';
        return url_img($avatar, $size);
    }

    function getCover($user = null) {
        $user = $user ? $user : $this->authUser;
        $cover = ($user['cover']) ? $user['cover'] : 'assets/images/cover.png';
        return url_img($cover);   
    }
    function countUsers($type) {
        $query = $this->db->query("SELECT id FROM users WHERE user_type=?", $type);
        return $query->rowCount();
    }

    public function profileUrl($user = null, $slug = null, $param = array()) {
        $user = $user ? $user : $this->authUser;
        if ($slug) $slug = '/'.$slug;
        return url($user['username'].$slug, $param);
    }

    public function ban($id) {
        if ($id == 1) return false;
       return $this->db->query("UPDATE users SET banned=? WHERE id=? ", 1, $id);
    }

    public function unban($id) {
        if ($id == 1) return false;
        return $this->db->query("UPDATE users SET banned=? WHERE id=? ", 0, $id);
    }

    public function delete($id) {
        if ($id == 1) return false;
        $user = $this->getUser($id);
        if ($user['avatar']) {
            delete_file(path($user['avatar']));
        }
        //lets delete tracks
        $query = $this->db->query("SELECT id FROM tracks WHERE userid=? ", $id);
        while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
            $this->C->model('admin')->deleteTrack($fetch['id']);
        }

        $query = $this->db->query("SELECT id FROM playlist WHERE userid=? ", $id);
        while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
            $this->C->model('admin')->deletePlaylist($fetch['id']);
        }
        $this->db->query("DELETE FROM reports WHERE userid=?", $id);
        $this->db->query("DELETE FROM transactions WHERE userid=?", $id);
        $this->db->query("DELETE FROM comments WHERE userid=?", $id);
        $this->db->query("DELETE FROM blocked_users WHERE userid=? OR blocked=?", $id, $id);
        $this->db->query("DELETE FROM chat WHERE from_uid=? OR to_uid=?", $id, $id);
        $this->db->query("DELETE FROM conversations WHERE user1=? OR user2=?", $id, $id);
        $this->db->query("DELETE FROM follow WHERE userid=? or follow_id=?", $id, $id);
        Hook::getInstance()->fire('delete.user', null, array($id));
        return $this->db->query("DELETE FROM users WHERE id=? ", $id);
    }

    public function saveUser($val, $user, $isAdmin = false) {
        /**
         * @var $full_name
         * @var $gender
         * @var $country
         * @var $city
         * @var $website
         * @var $facebook
         * @var $twitter
         * @var $youtube
         * @var $vimeo
         * @var $soundcloud
         * @var $artist
         * @var $bio
         * @var $avatar
         * @var $cover
         * @var $notifyl
         * @var $notifyc
         * @var $notifym
         * @var $notifyf
         * @var $email_l
         * @var $email_c
         * @var $email_f
         * @var $email_letter
         * @var $featured
         * @var $username
         * @var $email
         * @var $instagram
         * @var $currency
         * @var $wallet
         */
        $exp = array(
            'full_name' => $user['full_name'],
            'gender' => $user['gender'],
            'country' => $user['country'],
            'city' => $user['city'],
            'website' => $user['website'],
            'facebook' => $user['facebook'],
            'instagram' => $user['instagram'],
            'twitter' => $user['twitter'],
            'youtube' => $user['youtube'],
            'vimeo' => $user['vimeo'],
            'soundcloud' => $user['soundcloud'],
            'artist' => $user['user_type'] == 2 ? 1 : 0,
            'bio' => $user['bio'],
            'avatar' => $user['avatar'],
            'cover' => $user['cover'],
            'notifyl' => $user['notifyl'],
            'notifyc' => $user['notifyc'],
            'notifym' => $user['notifym'],
            'notifyf' => $user['notifyf'],
            'email_l' => $user['email_l'],
            'email_c' => $user['email_c'],
            'email_f' => $user['email_f'],
            'email_letter' => $user['email_letter'],
            'featured' => 0,
            'username' => $user['username'],
            'email' => $user['email'],
            'currency' => $user['currency'],
            'wallet' => $user['wallet']
        );
        extract(array_merge($exp, $val));
        $userid = $user['id'];


        $wallet = ($wallet) ? $wallet : 0;
        $this->db->query("UPDATE users SET wallet=?,currency=?,username=?,email=?,full_name=?,bio=?,avatar=?,cover=?, gender=?,country=?,city=?,website=?,facebook=?,twitter=?,youtube=?,vimeo=?,soundcloud=?,
          notifyl=?,notifyc=?,notifym=?,notifyf=?,email_l=?,email_c=?,email_f=?, email_letter=?,instagram=? WHERE id=?",
                $wallet,$currency,$username, $email,$full_name,$bio,$avatar, $cover, $gender,$country,$city,$website,$facebook,$twitter,$youtube,$vimeo,$soundcloud,
                $notifyl, $notifyc,$notifym,$notifyf,$email_l,$email_c,$email_f,$email_letter,$instagram, $userid);

        if (isset($val['artist'])) {
            if (config('user-signup-artist', true) or $isAdmin) {
                $artist = ($val['artist'] == 2) ? 2 : 1;
                $this->db->query("UPDATE users SET user_type=? WHERE id=?", $artist, $userid);
            }
        }

        if ($isAdmin) {
            $role = ($admin) ? 2 : 1;
            $this->db->query("UPDATE users SET featured=?,role=?,role_id=? WHERE id=?", $featured,$role,$val['role'], $userid);
        }

        Hook::getInstance()->fire('user.save', null, array($userid, $val));
        return true;
    }

    public function canUpload() {
        if (!$this->isLoggedin()) return true;
        $userType = $this->authUser['user_type'];
        if (config('can-upload', 1) == 1) return true;
        if($userType == 2) return true;
        return false;
    }

    public function isAuthor($user = null) {
        $user = ($user) ? $user : $this->authUser;
        if (config('can-upload',1) == 1) return true;
        if ($user['user_type'] == 2) return true;
        return false;
    }

    public function getTotalTrackSize() {
        if (config('enable-premium', false)) {
            $userType = $this->authUser['user_type'];
            if ($this->subscriptionActive()) {
                if (config('pro-artist-total-track-size', 1024) == '-1') return 500000;
                if ($userType == 1) {
                    return config('pro-artist-total-track-size', 1024);
                } else {
                    return config('pro-artist-total-track-size', 1024);
                }
            } else {
                if ($userType == 1) {
                    return config('free-artist-total-track-size', 100);
                } else {
                    return config('free-artist-total-track-size', 100);
                }
            }
        }
        return 0;
    }

    public function subscriptionActive($userid = null) {
        if (!config('enable-premium', false)) return true;
        $userid = $userid ? $userid : $this->authId;
        $lastTransaction = $this->C->model('admin')->getLastTransaction($userid, 'pro');
        if ($lastTransaction) {
            if ($lastTransaction['valid_time'] > time() and $lastTransaction['status']) return true;
        }
        return false;
    }

    public function listenerSubscriptionActive($userid = null) {
        if (!config('enable-premium-listeners', false)) return true;
        $userid = $userid ? $userid : $this->authId;
        $lastTransaction = $this->C->model('admin')->getLastTransaction($userid, 'pro-users');
        if ($lastTransaction) {
            if ($lastTransaction['valid_time'] > time() and $lastTransaction['status']) return true;
        }
        return false;
    }

    public function lastTransactionExpired($userid) {
        $userid = $userid ? $userid : $this->authId;
        $lastTransaction = $this->C->model('admin')->getLastTransaction($userid, 'pro');
        if ($lastTransaction) {
            if ($lastTransaction['valid_time'] > time() and $lastTransaction['status']) return 'active';
            return 'expired';
        }
        return false;
    }

    public function getTrackSize() {

        $size = config('audio-file-size', 55);
        if (config('enable-premium', false)) {
            $userType = $this->authUser['user_type'];
            if ($this->subscriptionActive()) {
                if ($userType == 1) {
                    return config('pro-user-track-size', 50);
                } else {
                    return config('pro-artist-track-size', 50);
                }
            } else {
                if ($userType == 1) {
                    return config('free-artist-track-size', 5);
                } else {
                    return config('free-artist-track-size', 5);
                }
            }
        }
        return $size;
    }

    public function countTracks() {
        $query = $this->db->query("SELECT id FROM tracks WHERE userid=? ", $this->authId);
        return $query->rowCount();
    }

    public function getTracksSpace() {
        $query = $this->db->query("SELECT SUM(`size`) as uploaded_size  FROM `tracks` WHERE userid=? ", $this->authId);
        $result = $query->fetch(PDO::FETCH_ASSOC);
        $size = $result['uploaded_size'];
        if (moduleExists('video')) {
            $query = $this->db->query("SELECT SUM(`size`) as uploaded_size  FROM `videos` WHERE userid=? ", $this->authId);
            $result = $query->fetch(PDO::FETCH_ASSOC);
            $size += $result['uploaded_size'];
        }
        return $size;
    }

    public function getBlockedUsers() {
        $query = $this->db->query("SELECT blocked FROM blocked_users WHERE userid=? ", $this->authId);
        $ids = array();
        while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
            $ids[] = $fetch['blocked'];
        }

        if ($ids) {
            $ids = implode(',', $ids);
            $query = $this->db->query("SELECT * FROM users WHERE id IN ($ids) ");
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }

        return array();
    }

    public function unblock($id) {
        return $this->db->query("DELETE FROM blocked_users WHERE userid=? AND blocked=? ", $this->authId, $id);
    }

    public function block($id) {
        $this->unblock($id);//to prevent duplicate
        //we need to unfollow each other
        $this->db->query("DELETE FROM follow WHERE (userid=? AND follow_id=?) OR (userid=? AND follow_id=?)", $this->authId, $id, $id, $this->authId);
        return $this->db->query("INSERT INTO blocked_users (userid,blocked) VALUES(?,?)", $this->authId, $id);
    }

    public function toggleTwoFactor($action) {
        $action = ($action == 2) ? 1 : 0;
        return $this->db->query("UPDATE users SET two_factor_auth=? WHERE id=?", $action, $this->authId);
    }

    public function hasBlock($id) {
        $query = $this->db->query("SELECT id FROM blocked_users WHERE userid=? AND blocked=?", $this->authId, $id);
        return $query->rowCount();
    }

    public function blockIds($string = true) {
        $query = $this->db->query("SELECT * FROM blocked_users WHERE userid=? OR blocked=? ", $this->authId,$this->authId);
        $ids = array(0);
        while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
            $ids[] = ($fetch['userid'] == $this->authId) ? $fetch['blocked'] : $fetch['userid'];
        }
        return ($string) ? implode(',', $ids) : $ids;
    }

    public function getFollowingIds($userid = null, $block= true) {
        $userid = ($userid) ? $userid : $this->authId;
        $blockedIds = ($block) ? $this->blockIds() : '0';
        $query = $this->db->query("SELECT follow_id FROM follow WHERE userid=? AND follow_id NOT IN ({$blockedIds})", $userid);
        $ids = array();
        while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
            $ids[] = $fetch['follow_id'];
        }

        return $ids;
    }

    public function getFollowerIds($userid = null, $block = true) {
        $userid = ($userid) ? $userid : $this->authId;
        $blockedIds = ($block) ? $this->blockIds() : '0';
        $query = $this->db->query("SELECT userid FROM follow WHERE follow_id=? AND userid NOT IN ({$blockedIds})", $userid);
        $ids = array();
        while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
            $ids[] = $fetch['userid'];
        }

        return $ids;
    }

    public function searchConnections($term, $all = false) {
        $ids = $this->getFollowingIds();
        $ids = array_merge($ids, $this->getFollowerIds());
        $ids = implode(',', $ids);
        $term = '%'.$term.'%';
        if ($all) {
            $query = $this->db->query("SELECT * FROM users WHERE (full_name LIKE ? OR username LIKE ? or email =? )",
                 $term, $term,$term);
        } else {
            $query = $this->db->query("SELECT * FROM users WHERE id != ? AND id IN($ids) AND  (full_name LIKE ? OR username LIKE ? or email =? )",
                $this->authId, $term, $term,$term);
        }
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
    public function countFollowing($userid = null) {
        $userid = ($userid) ? $userid : $this->authId;

        $query = $this->db->query("SELECT fid FROM follow WHERE userid=? ", $userid);
        return $query->rowCount();
    }

    public function isFollowing($userid) {
        $query = $this->db->query("SELECT fid FROM follow WHERE userid=? AND follow_id=? ", $this->authId, $userid);
        return $query->rowCount();
    }

    public function countFollowers($userid = null) {
        $userid = ($userid) ? $userid : $this->authId;

        $query = $this->db->query("SELECT fid FROM follow WHERE follow_id=? ", $userid);
        return $query->rowCount();
    }

    public function follow($id) {
        if ($this->isFollowing($id)) {
            $this->db->query("DELETE FROM follow WHERE userid=? AND follow_id=? ", $this->authId, $id);
            return false;
        } else {
            $this->db->query("INSERT INTO follow (userid,follow_id) VALUES(?,?)", $this->authId, $id);
            $theUser = $this->C->model('user')->getUser($id);
            if ($theUser['notifyf']) {
                $this->C->model('user')->addNotification($theUser['id'], 'follow-you', '');
            }
            $this->sendSocialMail($theUser, 'follow','follow', $id);
            return true;
        }
    }

    public function sendSocialMail($theUser, $which,$type, $id) {
        $user = $this->authUser;
        switch ($which) {
            case 'like':
                if (!$theUser['email_l'] or !config('email-like', false)) return false;
                switch($type) {
                    case 'like-track':
                        $track = $this->C->model('track')->findTrack($id);
                        $link = $this->C->model('track')->trackUrl($track);
                        $mailer = Email::getInstance();
                        $mailer->setAddress($theUser['email'],$theUser['full_name']);
                        $mailer->setSubject(l('new-like-track'));
                        $content = l('new-like-track-mail', array('track' => $track['title'], 'link' => $link));
                        $content = Hook::getInstance()->fire('email.content', $content, array('new-like-track-mail', array('track' => $track['title'], 'link' => $link)));

                        $mailer->setMessage($content);
                        $mailer->send();
                        break;
                    case 'like-playlist':
                        $playlist = $this->C->model('track')->findPlaylist($id);
                        $link = $this->C->model('track')->playlistUrl($playlist);
                        $mailer = Email::getInstance();
                        $mailer->setAddress($theUser['email'],$theUser['full_name']);
                        $mailer->setSubject(l('new-like-playlist'));
                        $content = l('new-like-playlist-mail', array('playlist' => $playlist['name'], 'link' => $link));
                        $content = Hook::getInstance()->fire('email.content', $content, array('new-like-playlist-mail', array('playlist' => $playlist['name'], 'link' => $link)));

                        $mailer->setMessage($content);
                        $mailer->send();
                        break;
                    default:
                        Hook::getInstance()->fire('social.mail.like', null, array($type, $id, $theUser));
                        break;
                }
                break;
            case 'comment':
                if (!$theUser['email_c'] or !config('email-comment', false)) return false;
                switch($type) {
                    case 'comment-track':
                        $track = $this->C->model('track')->findTrack($id);
                        $link = $this->C->model('track')->trackUrl($track);
                        $mailer = Email::getInstance();
                        $mailer->setAddress($theUser['email'],$theUser['full_name']);
                        $mailer->setSubject(l('new-comment-track'));
                        $content = l('new-comment-track-mail', array('track' => $track['title'], 'link' => $link));
                        $content = Hook::getInstance()->fire('email.content', $content, array('new-comment-track-mail', array('track' => $track['title'], 'link' => $link)));

                        $mailer->setMessage($content);
                        $mailer->send();
                        break;
                    case 'reply-comment-track':
                        $comment = $this->C->model('track')->findComment($id);
                        $track = $this->C->model('track')->findTrack($comment['typeid']);
                        $link = $this->C->model('track')->trackUrl($track);
                        $mailer = Email::getInstance();
                        $mailer->setAddress($theUser['email'],$theUser['full_name']);
                        $mailer->setSubject(l('new-reply-track'));
                        $content = l('new-reply-track-mail', array('track' => $track['title'], 'link' => $link));
                        $content = Hook::getInstance()->fire('email.content', $content, array('new-reply-track-mail', array('track' => $track['title'], 'link' => $link)));
                        $mailer->setMessage($content);
                        $mailer->send();
                        break;
                    default:
                        Hook::getInstance()->fire('social.mail.comment', null, array($type, $id, $theUser));
                        break;
                }
                break;
            case 'follow':
                if (!$theUser['email_f'] or !config('email-follow', false)) return false;
                $mailer = Email::getInstance();
                $mailer->setAddress($theUser['email'],$theUser['full_name']);
                $mailer->setSubject(l('new-follower'));
                $content = l('new-follower-mail', array('thisname' => $theUser['full_name'], 'name' => $user['full_name'], 'link' => $this->profileUrl($user)));
                $content = Hook::getInstance()->fire('email.content', $content, array('new-follower-mail', array('thisname' => $theUser['full_name'], 'name' => $user['full_name'], 'link' => $this->profileUrl($user))));

                $mailer->setMessage($content);
                $mailer->send();
                break;
        }
    }

    public function getFollows($userid = null, $type = 1, $limit = 10, $offset = 0) {
        $userid = ($userid) ? $userid : $this->authId;

        $sql = "SELECT * FROM follow INNER JOIN users ";
        $param = array();
        $blockedIds = $this->blockIds();
        if ($type == 1 ) {
            //thats following
            $sql .= " ON follow.follow_id=users.id WHERE userid=?  AND id NOT IN ({$blockedIds})";
            $param[] = $userid;
        } else {
            $sql .= " ON follow.userid=users.id WHERE follow_id=? AND id NOT IN ({$blockedIds})";
            $param[] = $userid;
        }

        $sql .= " ORDER BY follow.fid DESC LIMIT {$limit} OFFSET {$offset} ";
        $query = $this->db->query($sql, $param);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCompleteness() {
        $result = 30;
        if ($this->authUser['country']) $result +=10;
        if ($this->authUser['city']) $result +=10;
        if ($this->authUser['bio']) $result +=10;
        if ($this->authUser['avatar']) $result +=10;
        if ($this->authUser['cover']) $result +=10;
        if ($this->authUser['gender']) $result +=5;
        if ($this->authUser['website']) $result +=5;
        if ($this->countFollowing() > 0) $result += 10;

        return $result;
    }

    public function getPeople($type, $term = null, $limit = 10, $offset = 0, $param = array()) {
        if ($type == 'suggestion') {
            $followingIds = $this->getFollowingIds();
            $followingIds[] = $this->authId;
            $blockIds = $this->blockIds();
            $followingIds = implode(',', $followingIds);
            $query = $this->db->query("SELECT * FROM users WHERE id NOT IN ($followingIds) AND id NOT IN ($blockIds) AND avatar !=''  ORDER BY rand() LIMIT $limit OFFSET $offset");
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } elseif($type == 'top') {

            $trackSql = "SELECT id FROM tracks WHERE tracks.userid = users.id";
            if (isset($param['genres'])) {
                $genres = $param['genres'];
                $trackSql .= " AND tracks.genre IN ($genres) ";
            }
            $hideUsers = $this->blockIds(false);
            if (isset($param['users'])) {
                $hideUsers = array_merge($hideUsers, $param['users']);
            }
            $hideusers = implode(',', $hideUsers);
            $sql = "SELECT *, (SELECT count(id) as count FROM tracks WHERE tracks.userid = users.id) as count FROM users WHERE id NOT IN ($hideusers) AND user_type=?  ";
            $param = array(2);
            if ($term and $term != 'random') {
                $term = "%$term%";
                $sql .= " AND (full_name LIKE ? OR username LIKE ? ) ";
                $param[] = $term;
                $param[] = $term;
            }
            $sql .= ($term == 'random') ? " ORDER BY rand() " : " ORDER BY count DESC";
            $sql .= " LIMIT $limit OFFSET $offset ";
            $query  = $this->db->query($sql, $param);
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } elseif($type == 'people') {
            $blockIds = $this->blockIds();
            $sql = "SELECT * FROM users WHERE  id NOT IN ($blockIds) AND user_type=? ";
            $param = array(1);
            if ($term) {
                $term = "%$term%";
                $sql .= " AND (full_name LIKE ? OR username LIKE ? ) ";
                $param[] = $term;
                $param[] = $term;
            } else {
                return array();
            }

            $sql .= " ORDER BY id DESC";
            $sql .= " LIMIT $limit OFFSET $offset ";
            $query  = $this->db->query($sql, $param);
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }
    }


    public function getNotifications($offset = 0, $limit = 10) {
        $query = $this->db->query("SELECT *,notifications.id as notifyid,notifications.time as notifytime FROM notifications LEFT JOIN users ON users.id=notifications.from_userid WHERE to_userid=? ORDER BY notifytime DESC LIMIT {$limit} OFFSET {$offset} ",
            $this->C->model('user')->authId);
        $results = $query->fetchAll(PDO::FETCH_ASSOC);
        $formatResults = array();
        foreach($results as $result){
            $formatResults[] = $this->formatNotification($result);
        }

        return $formatResults;
    }

    public function formatNotification($notification, $api = false) {
        $notification['link'] = $this->C->model('user')->profileUrl($notification);
        $notification['title'] = '';
        $notification['avatar'] = $this->C->model('user')->getAvatar($notification, 200);
        if($api) {
            $notification['user'] = model('api')->formatUser($notification);
            $notification['click'] = 'user';
        }
        switch($notification['type']) {
            case 'comment-track':
                $track = $this->C->model('track')->findTrack($notification['typeid']);
                if($track['userid'] == $this->C->model('user')->authId) {
                    $notification['title'] = l('commented-your-track').' <strong>'.str_limit($track['title'], 35).'</strong>';
                } else {
                    $notification['title'] = l('commented-this-track').' <strong>'.str_limit($track['title'], 35).'</strong>';
                }
                $notification['link'] = $this->C->model('track')->trackUrl($track);
                if($api) {
                    $notification['track'] = model('api')->formatTrack($track, '', '');
                    $notification['click'] = 'track';
                }
                break;
            case 'reply-comment-track':
                $comment = $this->C->model('track')->findComment($notification['typeid']);
                $track = $this->C->model('track')->findTrack($comment['typeid']);
                $notification['link'] = $this->C->model('track')->trackUrl($track);
                $notification['title'] = l('replied-your-comment-track').' <strong>'.str_limit($track['title'], 35).'</strong>';
                if($api) {
                    $notification['track'] = model('api')->formatTrack($track, '', '');
                    $notification['click'] = 'track';
                }
                break;
            case 'like-track':
                $track = $this->C->model('track')->findTrack($notification['typeid']);
                $notification['title'] = l('like-your-track').' <strong>'.str_limit($track['title'], 35).'</strong>';
                $notification['link'] = $this->C->model('track')->trackUrl($track);
                if($api) {
                    $notification['track'] = model('api')->formatTrack($track, '', '');
                    $notification['click'] = 'track';
                }
                break;
            case 'like-playlist':
                $playlist = $this->C->model('track')->findPlaylist($notification['typeid']);
                $notification['title'] = l('like-your-playlist').' <strong>'.str_limit($playlist['name'], 35).'</strong>';
                $notification['link'] = $this->C->model('track')->playlistUrl($playlist);
                if($api) {
                    $notification['track'] = model('api')->formatPlaylist($playlist);
                    $notification['click'] = 'playlist';
                }
                break;
            case 'follow-you':
                $notification['title'] = l('following-you');
                break;
            case 'bank-transfer-cancel':
                $notification['title'] = l('bank-transfer-declined');
                break;
            case 'bank-transfer-success':
                $notification['title'] = l('bank-transfer-success');
                break;
        }
        $notification = Hook::getInstance()->fire('notification.format', $notification);

        //full title
        $notification['full_title'] = $notification['full_name'].' '.str_replace(array('<strong>','</strong>'), '', $notification['title']);

        return $notification;
    }

    public function countUnreadNotifications() {
        $query = $this->db->query("SELECT id from notifications  WHERE to_userid=? AND is_read=? ", $this->C->model('user')->authId, 0);
        return $query->rowCount();
    }

    public function getNewUnreadNotifications($time) {
        $query = $this->db->query("SELECT *,notifications.id as notifyid,notifications.time as notifytime FROM notifications LEFT JOIN users ON users.id=notifications.from_userid WHERE to_userid=? AND notifications.time > ?  ORDER BY notifytime DESC ",
            $this->C->model('user')->authId, $time);
        $results = $query->fetchAll(PDO::FETCH_ASSOC);
        $formatResults = array();
        foreach($results as $result){
            $formatResults[] = $this->formatNotification($result);
        }

        return $formatResults;
    }

    public function addNotification($to, $type, $typeId = '', $from = null) {
        $from = ($from) ? $from : $this->C->model('user')->authId;
        return $this->db->query("INSERT INTO notifications (from_userid,to_userid,type,typeid,time)VALUES(?,?,?,?,?)", $from, $to, $type,$typeId, time());
    }

    public function markRead($notId) {
        return $this->db->query("UPDATE notifications SET is_read=? WHERE id=? AND to_userid=? ", 1, $notId, $this->C->model('user')->authId);
    }

    public function deleteNotification($notId) {
        return $this->db->query("DELETE FROM notifications  WHERE id=? AND to_userid=? ",  $notId, $this->C->model('user')->authId);
    }

    public function verify($val) {
        $ex = array(
            'name' => '',
            'record' => '',
            'passport' => '',
            'info' => ''
        );
        $ex = array_merge($ex, $val);
        return $this->db->query("UPDATE users SET verify_details=? WHERE id=? ", perfectSerialize($ex), $this->authId);
    }

    public function countVerificationRequest() {
        $query = $this->db->query("SELECT id FROM users WHERE user_type=? AND verify_details!='' ", 1);
        return $query->rowCount();
    }

    public function getVeirificationRequests() {
        $sql = "SELECT * FROM users WHERE user_type=? AND verify_details!='' ";
        $param = array('1');
        $sql .= " ORDER BY id DESC ";
        return $this->db->paginate($sql, $param, 10);
    }

    public function otherUseUsername($username, $id) {
        $query = $this->db->query("SELECT id FROM users WHERE username=? AND id !=? ", $username, $id);
        return $query->rowCount();
    }

    public function otherUseEmail($email, $id) {
        $query = $this->db->query("SELECT id FROM users WHERE email=? AND id !=? ", $email, $id);
        return $query->rowCount();
    }

    public function getUserCurrency() {
        if (!$this->isLoggedin() or !config('enable-multi-currency', false)) return config('currency', 'USD');
        if ($this->authUser['currency']) return $this->authUser['currency'];
        return config('currency', 'USD');
    }

    public function getRandomArtists($limit = 10) {
        $query = $this->db->query("SELECT * FROM users WHERE user_type=? AND avatar!='' ORDER BY rand() LIMIT $limit", 2);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
}