<?php
class AdminModel extends Model {

    public function loadSettings() {
        $query = $this->db->query("SELECT settings_key,settings_value FROM settings ");
        while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
            Request::instance()->setConfig($fetch['settings_key'], $fetch['settings_value']);
        }
    }

    public function saveSettings($val) {
        foreach($val as $key => $value) {
            if(is_array($value)) $value  = implode(',', $value);
            $query = $this->db->query("SELECT * FROM settings WHERE settings_key=?", $key);
            if ($query->rowCount() > 0) {
                $this->db->query("UPDATE settings SET settings_value=? WHERE settings_key=? ", $value, $key);
            } else {
                $this->db->query("INSERT INTO settings (settings_key,settings_value) VALUES(?,?)", $key, $value);
            }
        }

        $this->loadSettings(); //to silently load admin settings
        return true;
    }


    public function getGenres($term = '') {
        $sql = "SELECT * FROM genre  ";
        $param = array();
        if ($term) {
            $term = '%'.$term.'%';
            $sql .= "WHERE name LIKE ? ";
            $param[] = $term;

        }
        $query = $this->db->query($sql, $param);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addGenre($val, $id = null) {
        /**
         * @var $name
         */
        extract($val);

        if ($id) {
            $this->db->query("UPDATE genre SET name=? WHERE id=?", $name, $id);
        } else {
            if (!$this->genreExists($name)) {
                $this->db->query("INSERT INTO genre (name) VALUES(?)", $name);
                return true;
            }
        }
        return false;
    }

    public function genreExists($name) {
        $query = $this->db->query("SELECT id from genre WHERE name=?", $name);
        return $query->rowCount();
    }

    public function findGenre($id) {
        $query = $this->db->query("SELECT * from genre WHERE id=?", $id);
        return $query->fetch(PDO::FETCH_ASSOC);
    }
    public function deleteGenre($id) {
        return $this->db->query("DELETE FROM genre WHERE id=? ", $id);
    }

    public function getPredefinedRoles() {
        $roles = array(
            'manage-tracks' => l('manage-tracks'),
            'manage-videos' => l('manage-videos'),
            'manage-albums' => l('manage-albums-playlists'),
            'manage-genres' => l('manage-genres'),
            'manage-user-roles' => l('manage-user-roles'),
            'manage-users' => l('manage-users'),
            'manage-settings' => l('manage-settings'),
            'manage-plugins' => l('manage-plugins'),
            'manage-sales' => l('manage-sales'),
            'manage-payments' => l('manage-payments'),
            'manage-site-design' => l('manage-site-design'),
            'manage-report' => l('manage-reports'),
            'manage-info' => l('manage-info-pages'),
            'send-newsletter' => l('send-newsletter'),
            'manage-ads' => l('manage-ads'),
            'verify-request' => l('verification-requests'),
        );

        $roles = Hook::getInstance()->fire('user.roles', $roles);
        return $roles;
    }
    /**
     * User roles functions
     */
    public function getRoles($term = '') {
        $sql = "SELECT * FROM user_roles  ";
        $param = array();
        if ($term) {
            $term = '%'.$term.'%';
            $sql .= "WHERE title LIKE ? ";
            $param[] = $term;

        }
        $query = $this->db->query($sql, $param);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addRole($val, $permissions, $id = null) {
        /**
         * @var $name
         */
        extract($val);
        $permissions = perfectSerialize($permissions);
        if ($id) {
            $this->db->query("UPDATE user_roles SET title=?, permissions=? WHERE id=?", $name, $permissions, $id);
        } else {
            if (!$this->roleExists($name)) {
                $this->db->query("INSERT INTO user_roles (title,permissions) VALUES(?,?)", $name, $permissions);
                return true;
            }
        }
        return false;
    }

    public function roleExists($name) {
        $query = $this->db->query("SELECT id from user_roles WHERE title=?", $name);
        return $query->rowCount();
    }

    public function findRole($id) {
        $query = $this->db->query("SELECT * from user_roles WHERE id=?", $id);
        return $query->fetch(PDO::FETCH_ASSOC);
    }
    public function deleteRoles($id) {
        return $this->db->query("DELETE FROM user_roles WHERE id=? ", $id);
    }

    public function countRoleUsers($roleId) {
        $query = $this->db->query("SELECT * FROM users WHERE role_id=?", $roleId);
        return $query->rowCount();
    }

    /**
     * @param $details
     */
    public function addTransaction($details) {
        $ex = array(
            'name' => '',
            'currency' => config('currency', 'USD'),
            'email' => '',
            'country' => '',
            'status' => 1,
            'amount' => '',
            'sale_id' => '',
            'type' => '',
            'type_id' => '',
            'userid' => '',
        );
        /**
         * @var $name
         * @var $currency
         * @var $email
         * @var $country
         * @var $status
         * @var $amount
         * @var $sale_id
         * @var $type
         * @var $type_id
         * @var $userid
         */
        extract(array_merge($ex, $details));

        $validTime = '';
        if ($type == 'pro' or $type == 'pro-users') {
            $validTime = $type_id == 'yearly' ? time() + (2629746 * 12) : time() + (2629746 * 1);
            if ($email == 'Trial') {
                $days = config('trial-days', 14);
                $validTime = time() + (60*60*24 * $days);
            }
        }
         $this->db->query("INSERT INTO transactions (userid,name,email,country,sale_id,type,type_id,amount,currency,status,valid_time,time)VALUES(?,?,?,?,?,?,?,?,?,?,?,?)",
            $userid,$name,$email,$country,$sale_id,$type,$type_id,$amount,$currency,$status,$validTime, time());
        $transactionId = $this->db->lastInsertId();
        if ($status == 1) Hook::getInstance()->fire('transaction.add', null , array($transactionId, $type, $type_id, $amount));
    }

    public function getLastTransaction($userid, $type, $typeId = '') {
        $sql = "SELECT * FROM transactions WHERE userid=? AND type=?  ";
        $param = array($userid, $type);
        if ($typeId) {
            $sql .= ' AND type_id=? ';
            $param[] = $typeId;
        }

        $sql .= " ORDER BY id DESC LIMIT 1";
        $query = $this->db->query($sql, $param);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function listPlugins() {
        $path = path('module');
        $handle = opendir($path);
        $result = array();
        while($read = readdir($handle)) {

            if (!is_file($path.$read) and substr($read, 0, 1) != '.') {
                $pluginFolder = $path.'/'.$read.'/';
                $pluginId = $read;
                $infoFile = $pluginFolder.'info.php';
                if (file_exists($infoFile)) {
                    $info = include $infoFile;
                    $info['icon'] = assetUrl('module/'.$read.'/icon.png');
                    $result[$pluginId] = $info;
                }
            }
        }
        return $result;
    }

    public function getThemes() {
        $path = path('styles');
        $handle = opendir($path);
        $result = array();
        while($read = readdir($handle)) {

            if (substr($read, 0, 1) != '.') {
                $result[] = $read;
            }
        }
        return $result;
    }

    public function getActivePlugins() {
        $query = $this->db->query("SELECT id FROM plugins");
        $result = array();
        while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $fetch['id'];
        }
        return $result;
    }

    public function savePlugins($val) {
        $activePlugins = $this->getActivePlugins();
        $this->db->query("DELETE FROM plugins");
        foreach($val as $plugin) {
            if (!in_array($plugin, $activePlugins)) {
                //its new activate plugin
                $installFile = path('module/'.$plugin.'/install.php');
                $updateFile = path('module/'.$plugin.'/update.php');
                if (file_exists($installFile)) require_once $installFile;
                if (file_exists($updateFile)) require_once  $updateFile;
            }
            $this->db->query("INSERT INTO plugins(id,active)VALUES(?,?)", $plugin, 1);
        }
    }

    public function getTracks($genre = '', $term = '', $user = '') {
        $sql = "SELECT * FROM tracks WHERE id!=? ";
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

    public function deleteTrack($id) {
        $track = $this->C->model('track')->findTrack($id);
        $this->db->query("DELETE FROM comments WHERE type=? AND typeid=?", 'track', $id);
        $this->db->query("DELETE FROM likes WHERE type=? AND typeid=?", 'track', $id);
        $this->db->query("DELETE FROM playlistentries WHERE track=?",$id);
        $this->db->query("DELETE FROM listen_later WHERE trackid=?",$id);
        $this->db->query("DELETE FROM listen_history WHERE trackid=?",$id);
        $this->db->query("DELETE FROM notifications WHERE typeid=? and (type = ? or type=?)",$id, 'like-track','comment-track');
        $this->db->query("DELETE FROM reports WHERE type=? and typeid=?",'track',$id);
        $this->db->query("DELETE FROM stream WHERE trackid=?",$id);
        $this->db->query("DELETE FROM views WHERE track=?",$id);
        $this->db->query("DELETE FROM downloads WHERE track=?",$id);
        $this->db->query("DELETE FROM tracks WHERE id=?",$id);
        $this->db->query("DELETE FROM transactions WHERE type_id=? and type=?",$id, 'track');

        Hook::getInstance()->fire('track.delete', null, array($track));
        if ($track['art']) {
            delete_file(path($track['art']));
        }

        $this->C->deleteFile($track['track_file']);
    }

    public function getPayments($user = '') {
        $sql = "SELECT * FROM transactions WHERE id!=? ";
        $param = array('');

        if ($user) {
            $sql .= ' AND userid=? ';
            $param[] = $user;
        }
        $sql .= " ORDER BY id DESC ";
        return $this->db->paginate($sql, $param, 20);
    }

    public function getReports() {
        $sql = "SELECT * FROM reports WHERE id!=? ";
        $param = array('');

        $sql .= " ORDER BY id DESC ";
        return $this->db->paginate($sql, $param, 20);
    }

    public function getPlaylists($page, $term = null) {
        $type = ($page == 'albums') ? 0 : 1;
        $sql = "SELECT * FROM playlist WHERE public=? AND playlist_type=?";
        $param = array(1, $type);

        if ($term) {
            $term = '%'.$term.'%';
            $sql .= " AND (name LIKE ?) ";
            $param[] = $term;
        }

        $sql .= " ORDER BY id DESC ";
        return $this->db->paginate($sql, $param, 20);
    }

    public function savePlaylist($val) {
        $this->db->query("UPDATE playlist SET name=?,description=? WHERE id=?",
            $val['name'],$val['desc'], $val['id']);
        Hook::getInstance()->fire('admin.edit.playlist', null, array($val));
        return true;
    }


    public function findPlaylist($id) {
        $query = $this->db->query("SELECT * FROM playlist WHERE id=? ", $id);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function deletePlaylist($playlist) {
        $id = (is_numeric($playlist)) ? $playlist : $playlist['id'];
        $playlist = (is_numeric($playlist)) ? $this->findPlaylist($playlist) : $playlist;
        $this->db->query("DELETE FROM playlist WHERE id=?", $id);
        $this->db->query("DELETE FROM likes WHERE typeid=? AND type=?", $id,'playlist');

        if ($playlist['playlist_type'] == 0) {
            //we need to delete each tracks
            $query = $this->db->query("SELECT track  FROM playlistentries WHERE playlist=? ", $id);
            while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
                $this->deleteTrack($fetch['track']);
            }
            $this->db->query("DELETE FROM playlistentries WHERE playlist=?", $id);
        } else {
            $this->db->query("DELETE FROM playlistentries WHERE playlist=?", $id);
        }

        return true;
    }

    public function findReport($id) {
        $query = $this->db->query("SELECT * FROM reports WHERE id=? ", $id);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function reportAction($action,$id) {
        $report = $this->findReport($id);
        if (!$report) return false;
        switch($action) {
            case 'delete-comment':
                $this->C->model('track')->deleteComment($report['typeid'], true);
                break;
            case 'suspend-track':
                $this->db->query("UPDATE tracks SET status=? WHERE id=?", 0, $report['typeid']);
                break;
            case 'delete-track':
                $this->deleteTrack($report['typeid']);
                break;
        }

        //delete report in any action
        return  $this->db->query("DELETE FROM reports WHERE id=? ", $report['id']);
    }

    public function countReports() {
        $query = $this->db->query("SELECT id FROM reports");
        return $query->rowCount();
    }

    public function getPaymentDetails($type, $typeId) {
        if ($type == 'pro' or $type == 'pro-users') {
            return array('title' => l('pro-member'), 'desc' => l($typeId));
        }else {
            return Hook::getInstance()->fire('payment.detail', array('title' => '', 'desc' => ''), array($type, $typeId));
        }
    }

    public function findTransaction($id) {
        $query = $this->db->query("SELECT * FROM transactions WHERE id=?", $id);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function updatePayment($id, $status) {
        $status = ($status) ? $status : 0;

        $this->db->query("UPDATE transactions SET status=? WHERE id=? ", $status, $id);
        $transaction = $this->findTransaction($id);
        Hook::getInstance()->fire('transaction.updated', null, array($transaction));
    }

    public function getPages() {
        $query = $this->db->query("SELECT * FROM info_pages ");
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function savePages($val) {
        $ex = array(
            'title' => '',
            'url' => '',
            'content' => '',
            'location' => '',
            'id' => ''
        );
        /**
         * @var $title
         * @var $url
         * @var $content
         * @var $location
         * @var $id
         */
        extract(array_merge($ex, $val));

        if ($id) {
            $this->db->query("UPDATE info_pages SET title=?,url=?,content=?,location=? WHERE id=?", $title,$url,$content,$location, $id);
        } else {
            $this->db->query("INSERT INTO info_pages (title,url,content,location)VALUES(?,?,?,?)", $title,$url,$content,$location);
        }
        return true;
    }

    public function deletePage($id) {
        return $this->db->query("DELETE FROM info_pages WHERE id=? ", $id);
    }

    public function getPagesMenu($location) {
        $query = $this->db->query("SELECT * FROM info_pages WHERE location=? ", $location);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findPage($id) {
        $query = $this->db->query("SELECT * FROM info_pages WHERE url=? or id=?", $id, $id);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function sendNewsLetter($to,$subject, $content) {
        $sql = "SELECT * FROM users ";
        if ($to) {
            $to = implode(',', $to[0]);
            $sql .= " WHERE id IN ($to) ";
        }

        $query = $this->db->query($sql);
        while($user = $query->fetch(PDO::FETCH_ASSOC)) {
            if ($user['email_letter']) {
                //we can send mail
                $mailer = Email::getInstance();
                $mailer->setAddress($user['email'], $user['full_name']);
                $mailer->setSubject($subject);
                $mailer->setMessage($content);
                $mailer->send();
            }
        }

        return true;
    }

    public function countUsers($type) {
        $query = $this->db->query("SELECT * FROM users WHERE user_type=?", $type);
        return $query->rowCount();
    }

    public function countTracks() {
        $query = $this->db->query("SELECT * FROM tracks");
        return $query->rowCount();
    }

    public function countPlaylists() {
        $query = $this->db->query("SELECT * FROM playlist");
        return $query->rowCount();
    }

    public function countPayments() {
        $query = $this->db->query("SELECT * FROM transactions");
        return $query->rowCount();
    }

    public function countComments() {
        $query = $this->db->query("SELECT * FROM comments");
        return $query->rowCount();
    }

    public function countLikes() {
        $query = $this->db->query("SELECT * FROM likes");
        return $query->rowCount();
    }

    public function saveBankTransfer($val) {
        /**
         * @var $file
         * @var $type
         * @var $typeid
         * @var $price
         */
        extract($val);
        if (!$this->transferIsAvailable($type,$typeid)) {
            $price = convertBackToBase($price);
            $this->db->query("INSERT INTO bank_transfers (userid,type,typeid,price,file)VALUES(?,?,?,?,?)",
                model('user')->authId, $type, $typeid, $price, $file);
            return json_encode(array(
                'type' => 'modal-function',
                'message' => l('bank-receipt-uploaded-success'),
                'modal' => '#bankTransferModal'
            ));
        } else  {
            return json_encode(array('type' => 'error', 'message' => l('you-already-uploaded-receipt')));
        }
    }

    public function transferIsAvailable($type,$typeid) {
        $query = $this->db->query("SELECT * FROM bank_transfers WHERE userid=? AND type=? AND typeid=? ", model('user')->authId, $type, $typeid);
        return $query->rowCount();
    }

    public function getBankTransfers() {
        $sql = "SELECT * FROM bank_transfers WHERE id!=? ";
        $param = array('');
        $sql .= " ORDER BY id DESC ";
        return $this->db->paginate($sql, $param, 20);
    }

    public function findBankRequest($id) {
        $query = $this->db->query("SELECT * FROM bank_transfers WHERE id=?", $id);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function doBankAction($id, $action) {
        $details = $this->findBankRequest($id);
        $user = model('user')->getUser($details['userid']);
        if ($action == 'delete') {
            model('user')->addNotification($user['id'], 'bank-transfer-cancel');
        } else {

            model('admin')->addTransaction(array(
                'amount' =>  $details['price'],
                'type' => $details['type'],
                'type_id' => $details['typeid'],
                'sale_id' => time(),
                'name' => $user['full_name'],
                'country' => $user['country'],
                'email' => $user['email'],
                'userid' => $user['id']
            ));
            Hook::getInstance()->fire('payment.success', null, array($details['type'], $details['typeid']));
            model('user')->addNotification($user['id'], 'bank-transfer-success');
        }
        $this->db->query("DELETE FROM bank_transfers WHERE id=?", $id);
    }
}