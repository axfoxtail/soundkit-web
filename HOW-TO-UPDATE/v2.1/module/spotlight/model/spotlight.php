<?php
class SpotlightModel extends Model {
    public function hasGlobalTrack($trackid, $field = 'trackid') {
        $query = $this->db->query("SELECT id FROM spotlight WHERE $field=? AND is_global=?", $trackid, 1);
        return $query->rowCount();
    }

    public function countGlobal() {
        $query = $this->db->query("SELECT id FROM spotlight WHERE is_global=?",  1);
        return $query->rowCount();
    }

    public function addGlobal($id, $field='trackid') {
        if ($this->hasGlobalTrack($id, $field)) {
            $this->db->query("DELETE FROM spotlight WHERE $field=? AND is_global=?", $id, 1);
            return false;
        } else {
            $this->db->query("INSERT INTO spotlight ($field,userid,is_global)VALUES(?,?,?)", $id,0, 1);
            return true;
        }
    }

    public function add($tracks) {
        $currentCount = $this->countTracks();
        $newCount = $currentCount + count($tracks);
        if ($newCount > config('max-spotlight-tracks', 5)) return false;
        foreach($tracks as $track) {
            list($type, $typeId) = explode('-', $track);

            if (($type == 'track' AND !$this->trackExists($typeId)) or ($type == 'playlist' AND !$this->playlistExists($typeId))) {
                $trackid = ($type == 'track')  ? $typeId : 0;
                $playlistid = ($type == 'playlist') ? $typeId : 0;
                $this->db->query("INSERT INTO spotlight (userid,trackid,playlistid)VALUES(?,?,?)", model('user')->authId, $trackid,$playlistid);
            }
        }
        return true;
    }

    public function trackExists($track) {
        $userid = model('user')->authId;
        $query = $this->db->query("SELECT id FROM spotlight WHERE userid=? AND trackid=? ", $userid, $track);
        return $query->rowCount();
    }

    public function playlistExists($track) {
        $userid = model('user')->authId;
        $query = $this->db->query("SELECT id FROM spotlight WHERE userid=? AND playlistid=? ", $userid, $track);
        return $query->rowCount();
    }

    public function getTracks($userid = null, $global = false, $limit = 10) {
        $userid = ($userid) ? $userid : model('user')->authId;
        $sql = "SELECT * FROM spotlight WHERE spotlight.userid=? AND is_global=? ORDER BY id DESC";
        $param = array( $userid, 0);
        if ($global) {
            $sql = "SELECT * FROM spotlight  WHERE  is_global=? ORDER BY id DESC LIMIT $limit";
            $param = array(1);
        }
        $query = $this->db->query($sql,$param);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countTracks($userid =  null) {
        $userid = ($userid) ? $userid : model('user')->authId;
        $query = $this->db->query("SELECT id FROM spotlight WHERE userid=? AND is_global=?", $userid, 0);
        return $query->rowCount();
    }

    public function remove($id) {
        $this->db->query("DELETE FROM spotlight WHERE userid=? AND id=?", model('user')->authId, $id);
    }

    public function getTrackIds($userid) {
        $query  = $this->db->query("SELECT trackid FROM spotlight WHERE userid=?", $userid);
        $result = array(0);
        while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $fetch['trackid'];
        }

        return implode(',', $result);
    }

    public function getPlaylistIds($userid) {
        $query  = $this->db->query("SELECT playlistid FROM spotlight WHERE userid=?", $userid);
        $result = array(0);
        while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $fetch['playlistid'];
        }

        return implode(',', $result);
    }
    public function canSpotlight() {
        if (config('use-spotlight', 'all-author') == 'all-author') return true;
        if (model('user')->subscriptionActive()) return true;
        return false;
    }
}