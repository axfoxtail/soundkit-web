<?php
Hook::getInstance()->register('admin.settings.limit', function() {
    echo view('spotlight::settings/embed');
});

Hook::getInstance()->register('admin.track.action', function($track){
    $has = model('spotlight::spotlight')->hasGlobalTrack($track['id']);
    $btn = ($has) ? 'btn-success' : 'btn-secondary';
    $title = ($has) ? l('remove-from-spotlight') : l('add-to-spolight');
    $url = url('admin/spotlight/add', array('id' => $track['id']));
    echo '<a id="admin-track-list-'.$track['id'].'" href="'.$url.'" title="'.$title.'" class="btn btn-sm mtitle ajax-action '.$btn.'"><i class="la la-star"></i></a>';
});

Hook::getInstance()->register('admin.playlist.action', function($playlist){
    $has = model('spotlight::spotlight')->hasGlobalTrack($playlist['id'], 'playlistid');
    $btn = ($has) ? 'btn-success' : 'btn-secondary';
    $title = ($has) ? l('remove-from-spotlight') : l('add-to-spolight');
    $url = url('admin/spotlight/add', array('id' => $playlist['id'], 'type' => 'playlistid'));
    echo '<a id="admin-track-list-'.$playlist['id'].'" href="'.$url.'" title="'.$title.'" class="btn btn-sm mtitle ajax-action '.$btn.'"><i class="la la-star"></i></a>';
});

Hook::getInstance()->register('user.profile.all.detail', function($user) {
    echo view('spotlight::profile/embed', array('user' => $user));
});

Hook::getInstance()->register("track.query", function($result, $type, $typeId) {
    if ($type == 'my-spotlight') {

        $result['sql'] = "SELECT * FROM spotlight  WHERE userid=? AND is_global=? ORDER BY id DESC";
        $result['param'] = array($typeId, 0);
    } elseif($type == 'global-spotlight') {
        $result['sql'] = "SELECT * FROM spotlight  WHERE is_global=? ORDER BY id DESC ";
        $result['param'] = array(1);
    }
    return $result;
});

Hook::getInstance()->register('get.lists.track', function($result, $type, $typeId,$track) {
   if ($type == 'my-spotlight' or $type == 'global-spotlight') {

       if ($track['trackid']) {
           $track = model('track')->findTrack($track['trackid']);;
           if ($track) $result['track'] =  $track;
       } elseif($track['playlistid']) {
          // print_r($track);
           $result['track'] = model('track')->getPlaylistFirstTrack($track['playlistid']);
           $result['track']['playlist_id'] = $track['playlistid'];
       }
   }
   return $result;
});

Hook::getInstance()->register('my-stream.query', function($sql, $type, $userid) {
    $trackIds = model('spotlight::spotlight')->getTrackIds($userid);
    $playlistIds = model('spotlight::spotlight')->getPlaylistIds($userid);
    $sql .= " AND (trackid NOT IN ($trackIds) OR playlist_id NOT IN ($playlistIds)) ";
    return $sql;
});

Hook::getInstance()->register('global.side', function() {
    if (config('display-spotlight', 1) == '1') {
        echo view('spotlight::global');
    }
});

Hook::getInstance()->register('pro.features', function() {
    echo '<div class="form-group">
                        <label class="custom-control custom-checkbox">
                            <input checked disabled type="checkbox" class="custom-control-input">
                            <span class="custom-control-indicator"></span>
                            <span class="custom-control-description">'.l('spotlight').'</span>
                        </label>
                    </div>';
});
Hook::getInstance()->register('main.menu.home', function() {
    $active = getController()->activeMenu == 'spotlight' ? 'active' : null;
    $dcount = model('spotlight::spotlight')->countGlobal();
    $count = ($dcount) ? "<span class='badge badge-secondary float-right mt-2'>$dcount</span>" : '';

    if ($dcount > 0) {
        echo '<li><a id="spotlight-menu" class="sub-menu  '.$active.' " href="'.url('spotlight').'"  data-ajax="true"><i class="la la-fire "></i> <span>'.l('spotlight').'</span>  '.$count.'</a></li>';
    }
});

Hook::getInstance()->register('track.delete', function($track) {
   Database::getInstance()->query("DELETE FROM spotlight WHERE trackid=?", $track['id']);
});
$request->any('admin/spotlight/add', array('uses' => 'spotlight::admin@add'));
$request->any('spotlight/save', array('uses' => 'spotlight::spotlight@save'));
$request->any('spotlight/remove', array('uses' => 'spotlight::spotlight@remove'));
$request->any('spotlight', array('uses' => 'spotlight::spotlight@index', 'secure' => false));