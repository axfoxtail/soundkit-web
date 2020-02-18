<?php
class MessageModel extends Model {
    public function send($to, $message='', $track = '', $playlist='') {
        $userid = $this->C->model('user')->authId;
        $cid = $this->getConversationId($userid, $to);
        $query = $this->db->query("INSERT INTO chat (from_uid,to_uid,cid,message,trackid,playlistid,time)VALUES(?,?,?,?,?,?,?)",
            $userid, $to, $cid, $message, $track, $playlist, time());

        $messageId = $this->db->lastInsertId();
        $this->db->query("UPDATE conversations SET time=? WHERE id=?", time(), $cid);
        return $messageId;
    }

    public function getConversationId($userid, $to) {
        $query = $this->db->query("SELECT id FROM conversations WHERE (user1=? AND user2=?) OR (user1=? AND user2=?) LIMIT 1 ", $userid,$to, $to, $userid);
        if ($query->rowCount() > 0) {
            $result = $query->fetch(PDO::FETCH_ASSOC);
            return $result['id'];
        } else {
            $query = $this->db->query("INSERT INTO conversations (user1,user2,time)VALUES(?,?,?)", $userid, $to, time());
            return $this->db->lastInsertId();
        }
    }

    public function countUnread() {
        $query = $this->db->query("SELECT id FROM chat WHERE to_uid=? AND is_read=? ", $this->C->model('user')->authId, 0);
        return $query->rowCount();
    }

    public function getNewUnreadMessages($time) {
        $query = $this->db->query("SELECT *,chat.id as chatid,chat.time as chattime FROM chat LEFT JOIN users ON users.id=chat.from_uid WHERE to_uid=? AND chat.time > ?  ORDER BY chattime DESC LIMIT 1",
            $this->C->model('user')->authId, $time);
        $results = $query->fetchAll(PDO::FETCH_ASSOC);
        $formatResults = array();
        foreach($results as $result){
            $formatResults[] = $this->formatMessage($result);
        }

        return $formatResults;
    }

    public function getMessages($cid, $offset= 0, $limit = 10) {
        $query = $this->db->query("SELECT *,chat.id as chatid,chat.time as chattime FROM chat LEFT JOIN users ON users.id=chat.from_uid WHERE chat.cid=? ORDER BY chattime  DESC LIMIT {$limit} OFFSET $offset",
            $cid);
        $result = array();
        while($fetch= $query->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $this->formatMessage($fetch);
        }
        return array_reverse($result);
    }

    public function getMessage($messageId) {
        $query = $this->db->query("SELECT *,chat.id as chatid,chat.time as chattime FROM chat LEFT JOIN users ON users.id=chat.from_uid WHERE chat.id=?   LIMIT 1",
            $messageId);
        return $this->formatMessage($query->fetch(PDO::FETCH_ASSOC));
    }

    public function formatMessage($message) {
        if ($message['trackid']) {
            $message['track'] = $this->C->model('track')->findTrack($message['trackid']);
        }

        if ($message['playlistid']) {
            $message['playlist'] = $this->C->model('track')->findPlaylist($message['playlistid']);
        }
        $message['avatar'] = $this->C->model('user')->getAvatar($message, 200);
        return $message;
    }

    public function getLastCid() {
        $userid = $this->C->model('user')->authId;
        $query = $this->db->query("SELECT id FROM conversations WHERE user1=? OR user2=? ORDER BY time DESC LIMIT 1", $userid,$userid);
        if ($query->rowCount() > 0) {
            $fetch = $query->fetch(PDO::FETCH_ASSOC);
            return $fetch['id'];
        }
        return false;
    }

    public function getConversations() {
        $userid = $this->C->model('user')->authId;
        $query = $this->db->query("SELECT * FROM conversations WHERE user1=? OR user2=? ORDER BY time DESC ", $userid,$userid);
        $result = array();
        while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {

            $result[] = $this->formatConversation($fetch);
        }
        return $result;
    }

    public function formatConversation($fetch) {
        $userid = $this->C->model('user')->authId;
        $fetch['user'] = ($fetch['user1'] == $userid) ? $this->C->model('user')->getUser($fetch['user2'], false) : $this->C->model('user')->getUser($fetch['user1'], false);
        $fetch['lastMessage'] = $this->getLastMessage($fetch['id']);
        $fetch['unread'] = $this->countConversationUnread($fetch['id']);
        return $fetch;
    }

    public function getLastMessage($cid) {
        $query = $this->db->query("SELECT * FROM chat WHERE cid=? ORDER BY time DESC LIMIT 1", $cid);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function findConversation($cid) {
        $query = $this->db->query("SELECT * FROM conversations WHERE id=? ", $cid);
        return $this->formatConversation($query->fetch(PDO::FETCH_ASSOC));
    }

    public function countConversationUnread($cid) {
        $query = $this->db->query("SELECT id FROM chat WHERE cid=? AND from_uid !=? AND is_read=? ORDER BY time DESC", $cid,
            $this->C->model('user')->authId, 0);
        return $query->rowCount();
    }

    public function markSeen($id) {
        return $this->db->query("UPDATE chat SET is_read=? WHERE id=?", 1, $id);
    }
}