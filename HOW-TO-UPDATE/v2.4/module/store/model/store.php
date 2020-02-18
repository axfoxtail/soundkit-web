<?php
class StoreModel extends Model {
    public function hasPurchased($id, $type = 'track', $force = false) {
        if (!model('user')->isLoggedIn()) return false;
        if (!$force) {
            if (config('enable-premium-listeners', false) and model('user')->listenerSubscriptionActive()) return true;

            if (config('enable-premium-listeners', false) and config('enable-premium', false) and config('allow-premium-artists', true) and  model('user')->subscriptionActive()) return true;
        }

        if ($type == 'track') {
            $track = model('track')->findTrack($id);
            if ($track['userid'] == model('user')->authId) return true;
            $query = $this->db->query("SELECT id FROM transactions WHERE type_id=? AND userid=? AND type=? AND status =?", $id, model('user')->authId, 'track', 1);
            return $query->rowCount();
            $query = $this->db->query("SELECT id FROM purchased WHERE trackid=? AND userid=?", $id, model('user')->authId);
            return $query->rowCount();
        } elseif($type == 'video'){
            $video = model('video::video')->find($id);
            if ($video['userid'] == model('user')->authId) return true;
            $query = $this->db->query("SELECT id FROM transactions WHERE type_id=? AND userid=? AND type=?  AND status =?", $id, model('user')->authId, 'video', 1);
            return $query->rowCount();
            $query = $this->db->query("SELECT id FROM purchased WHERE videoid=? AND userid=?", $id, model('user')->authId);
            return $query->rowCount();
        }else {
            $album = model('track')->findPlaylist($id);
            if ($album['userid'] == model('user')->authId) return true;
            $query = $this->db->query("SELECT id FROM transactions WHERE type_id=? AND userid=? AND type=?  AND status =?", $id, model('user')->authId, 'album', 1);
            return $query->rowCount();
        }
    }

    public function addPurchase($type, $typeId) {
        if ($type == 'track') {
            if (!$this->hasPurchased($typeId, $type)) {
                $this->db->query("INSERT INTO purchased (userid,trackid,time)VALUES(?,?,?)", model('user')->authId, $typeId, time());
            }
        } elseif($type == 'video'){
            if (!$this->hasPurchased($typeId, $type)) {
                $this->db->query("INSERT INTO purchased (userid,videoid,time)VALUES(?,?,?)", model('user')->authId, $typeId, time());
            }
        } else {
            $tracks = model('track')->getPlaylistEntries($typeId);
            foreach ($tracks as $track) {
                if (!$this->hasPurchased($track, 'track')) {
                    $this->db->query("INSERT INTO purchased (userid,trackid,time)VALUES(?,?,?)", model('user')->authId, $track, time());
                }
            }
        }
        return true;
    }

    public function getTransactions($type) {
        $sql = "SELECT * FROM transactions ";
        if ($type == 'track') {
            $tracks = implode(',', $this->getAllMyTracks());
            $sql .= " WHERE type=? AND type_id IN ($tracks) ";
            $param = array('track');
        } elseif($type == 'video') {
            $videos = implode(',', $this->getAllMyVideos());
            $sql .= " WHERE type=? AND type_id IN ($videos) ";
            $param = array('video');
        } else {
            $albums = implode(',', $this->getAllMAlbums());
            $sql .= " WHERE type=? AND type_id IN ($albums) ";
            $param = array('album');
        }
        $sql .= " ORDER BY id DESC";

        return $this->db->paginate($sql, $param, 20);
    }

    public function getAllTransactions($type) {
        $sql = "SELECT * FROM transactions ";
        if ($type == 'tracks') {
            $sql .= " WHERE type=?";
            $param = array('track');
        }elseif($type == 'video'){
            $sql .= " WHERE type=?";
            $param = array('video');
        } else {
            $sql .= " WHERE type=? ";
            $param = array('album');
        }
        $sql .= " ORDER BY id DESC";

        return $this->db->paginate($sql, $param, 20);
    }

    public function countTotalSold($type) {
        $sql = "SELECT * FROM transactions ";
        if ($type == 'tracks') {
            $sql .= " WHERE type=?";
            $param = array('track');
        } elseif($type == 'video'){
            $sql .= " WHERE type=?";
            $param = array('video');
        } else {
            $sql .= " WHERE type=? ";
            $param = array('album');
        }
        $sql .= "AND status=? ORDER BY id DESC";
        $param[] = 1;

        $query = $this->db->query($sql, $param);
        return $query->rowCount();
    }

    public function countTotalAmountSold($type) {
        $sql = "SELECT SUM(amount) as total FROM transactions ";
        if ($type == 'tracks') {
            $sql .= " WHERE type=?";
            $param = array('track');
        }elseif($type == 'video'){
            $sql .= " WHERE type=?";
            $param = array('video');
        } else {
            $sql .= " WHERE type=? ";
            $param = array('album');
        }
        $sql .= "AND status=? ORDER BY id DESC";
        $param[] = 1;
        $query = $this->db->query($sql, $param);
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    public function getMyWithdraws() {
        $sql = "SELECT * FROM withdrawals WHERE userid=? ";
        $sql .= " ORDER BY id DESC";

        return $this->db->paginate($sql, array(model('user')->authId), 20);
    }

    public function getAllMyTracks() {
        $query = $this->db->query("SELECT id FROM tracks WHERE userid=? AND price > 0 ", model('user')->authId);
        $result = array(0);
        while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $fetch['id'];
        }
        return $result;
    }

    public function getAllMyVideos() {
        $query = $this->db->query("SELECT id FROM videos WHERE userid=? AND price > 0 ", model('user')->authId);
        $result = array(0);
        while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $fetch['id'];
        }
        return $result;
    }

    public function getTotalItemSold() {
        $tracks = implode(',', $this->getAllMyTracks());
        $albums = implode(',', $this->getAllMAlbums());
        $query = $this->db->query("SELECT id FROM transactions WHERE (type=? AND type_id IN ($tracks)) OR (type=? AND type_id IN ($albums)) AND status=?", 'track', 'album', 1);
        return $query->rowCount();
    }

    public function getAllMAlbums() {
        $query = $this->db->query("SELECT id FROM playlist WHERE userid=? AND price > 0 ", model('user')->authId);
        $result = array(0);
        while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $fetch['id'];
        }
        return $result;
    }

    public function addWithdraw($amount) {
        $this->db->query("INSERT INTO withdrawals (userid,amount,time) VALUES(?,?,?)", model('user')->authId, $amount, time());
        $amount = model('user')->authUser['balance'] - $amount;
        return $this->db->query("UPDATE users SET balance=? WHERE id=? ", $amount, model('user')->authId);
    }

    public function hasPendingWithdraw() {
        $query = $this->db->query("SELECT id FROM withdrawals WHERE userid=? AND status=?", model('user')->authId, 0);
        return $query->rowCount();
    }

    public function getPendingWithdrawals() {
        $sql = "SELECT * FROM withdrawals WHERE status=? ";
        $param = array(0);
        $sql .= " ORDER BY id DESC";

        return $this->db->paginate($sql, $param, 20);
    }

    public function getWithdrawsHistory() {
        $sql = "SELECT * FROM withdrawals WHERE status>? ";
        $param = array(0);
        $sql .= " ORDER BY id DESC";
        return $this->db->paginate($sql, $param, 20);
    }

    public function findWithdraw($id) {
        $query = $this->db->query("SELECT * FROM withdrawals WHERE id=?", $id);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function countPendingWithdraw() {
        $query = $this->db->query("SELECT * FROM withdrawals WHERE status=?", 0);
        return $query->rowCount();
    }
    public function markPaid($id) {
        $withdraw = $this->findWithdraw($id);
        $user = model('user')->getUser($withdraw['userid']);
        $this->db->query("UPDATE withdrawals SET status=? WHERE id=? ", 1, $id);

        //send notification to user for email and site notification
        $this->C->model('user')->addNotification($user['id'], 'withdraw-request-processed', $id);
        $mailer = Email::getInstance();
        $mailer->setAddress($user['email'],$user['full_name']);
        $mailer->setSubject(config('site-title').' '.l('withdraw-request-processed'));
        $content = l('approval::withdraw-request-processed-message', array('name' => $user['full_name']));
        $mailer->setMessage($content);
        $mailer->send();
    }

    public function getPurchased($type) {
        $sql = "SELECT * FROM transactions WHERE userid=? AND type=? ";
        $query = $this->db->query($sql, model('user')->authId, $type);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
}