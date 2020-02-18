<?php
class CronController extends Controller {
    public function run() {
        $this->db = Database::getInstance();

        Hook::getInstance()->fire('cronjob.start');

        //release the due tracks with their playlists
        $query = $this->db->query("SELECT id FROM playlist WHERE public=?  AND (release_date < ? OR release_date=? )", 3, time(), time());
        while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
            $playlistId = $fetch['id'];
            $entries = $this->model('track')->getPlaylistEntries($playlistId);
            foreach($entries as $trackId) {

                $this->db->query("UPDATE tracks SET public=?,time=? WHERE id=? ", 1,time(), $trackId);
                $this->db->query("UPDATE stream SET streamtime=? WHERE trackid=?", time(), $trackId);
            }

            //publish this playlist as well
            $this->db->query("UPDATE playlist SET public=?,time=?  WHERE id=?", 1,time(), $playlistId);
            //we need to update the streamtime to latest
            $this->db->query("UPDATE stream SET streamtime=? WHERE playlist_id=?", time(), $playlistId);
        }


        //lets now do for tracks without playlist
        $query = $this->db->query("SELECT id FROM tracks WHERE public=?  AND (release_date < ? OR release_date=? )", 3, time(), time());
        while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
            $trackId = $fetch['id'];
            $this->db->query("UPDATE tracks SET public=?,time=? WHERE id=? ", 1, time(), $trackId);
            $this->db->query("UPDATE stream SET streamtime=? WHERE trackid=?", time(), $trackId);
        }

        //lets delete the dormant tracks in the tmp folder
        $limit = config('delete-tmp-tracks', 5);
        $time = strtotime("$limit days ago");
        $query = Database::getInstance()->query("SELECT * FROM tmp_files WHERE time < $time ");
        while($file = $query->fetch(PDO::FETCH_ASSOC)) {
            $this->deleteFile($file['path']);
        }

        Hook::getInstance()->fire('cronjob.finished');
        exit('JOB DONE!!!');
    }
}