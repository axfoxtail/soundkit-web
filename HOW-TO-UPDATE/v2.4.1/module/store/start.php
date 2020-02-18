<?php
Hook::getInstance()->register('main.menu.top', function () {
    echo view('store::menus/user-side');
});

Hook::getInstance()->register('admin.settings.integrations', function() {
    echo view('store::settings/embed');
});

Hook::getInstance()->register('admin.menu.middle', function() {
    $active = getController()->activeMenu == 'sales' ? 'active' : '';
    if(model('user')->hasRole('manage-sales')) {
        echo '<li><a id="sales-menu" data-ajax="true" class="sub-menu '.$active.'" href="'.url('admin/sales').'"><i class="la la-shopping-cart"></i>  '.l('sales').'</a></li>';
    }
});


Hook::getInstance()->register('user.settings.menu', function($type) {
    $active = $type == 'payment-details' ? 'active' : null;
    if ( model('user')->isAuthor() and model('user')->subscriptionActive()) {
        echo '<li><a data-ajax="true" class="sub-menu '.$active.'" href="'.url('settings/payment-details').'"><i class="la la-money"></i> '.l('payment-details').'</a></li>';
    }
});
Hook::getInstance()->register('settings.content', function($result, $type) {
    if ($type == 'payment-details') {
        if (model('user')->isAuthor() and model('user')->subscriptionActive()) {
            if ($val = Request::instance()->input('val')) {
                if ($val['paypal'] or (isset($val['bank-details']) and $val['bank-details'])) {
                    $paypal = $val['paypal'];
                    $details = 'paypal-'.$paypal;
                    if (isset($val['bank-details']) and $val['bank-details']) {
                        $details = 'bank-'.$val['bank-details'];
                    }
                    Database::getInstance()->query("UPDATE users SET payment_details=? WHERE id=?", $details, model('user')->authId);
                }

                exit(json_encode(array(
                    'type' => 'url',
                    'value' => url('settings/payment-details'),
                    'message' => l('account-settings-saved')
                )));
            }
            $result['content'] = view('store::settings/index');
        }

    }
    return $result;
});


Hook::getInstance()->register('admin.payments.tab', function($type = null) {
    $withdraw = ($type == 'withdraws') ? 'active' : null;
    $history = ($type == 'withdraw-history') ? 'active' : null;
    $funds = ($type == 'funds') ? 'active' : null;
    echo '<li class="nav-item"><a class="nav-link '.$withdraw.'" data-ajax="true" href="'.url('admin/withdraws').'" >'.l('pending-withdrawals').'</a></li>';
    echo '<li class="nav-item"><a class="nav-link '.$history.' " data-ajax="true" href="'.url('admin/withdraw-history').'" >'.l('withdrawal-history').'</a></li>';
    //echo '<li class="nav-item"><a class="nav-link '.$funds.'"  data-ajax="true" href="'.url('admin/funds').'" >'.l('funds-history').'</a></li>';

});



Hook::getInstance()->register('upload.tabs.content', function() {
    if (model('user')->subscriptionActive()) {
        echo view('store::upload/embed');
    }
});

Hook::getInstance()->register('track.upload.data', function($newval, $i) {
    $C = getController();
    $val = Request::instance()->input('val');
    $price = (isset($val['prices'][$i])) ? $val['prices'][$i] : '';
    if ($price) {
        $newval['price'] = $price;
    } else {
        $newval['price'] = '';
    }

    if (isset($val['demofiles'][$i]) and $val['demofiles'][$i]) {
        $newval['demo_file'] = $val['demofiles'][$i];
        $newval['demo_wave'] = $val['demowave'][$i];
        $newval['demo_wave_colored'] = $val['demowave2'][$i];
        $newval['demo_duration'] = $val['demoduration'][$i];
    } else {
        $newval['demo_file'] = "";
    }

    return $newval;
});


Hook::getInstance()->register('track.added', function($trackId, $val) {
    if (isset($val['price']) and $val['price']) {
        $price = $val['price'];
        $audio = (isset($val['demo_file'])) ? $val['demo_file'] : '';
        $wave = (isset($val['demo_wave'])) ? $val['demo_wave'] : '';
        $waveColored = (isset($val['demo_wave_colored'])) ? $val['demo_wave_colored'] : '';
        $duration = (isset($val['demo_duration'])) ? $val['demo_duration'] :'';
        Database::getInstance()->query("UPDATE tracks SET demo_file=?,price=?,demo_wave=?,demo_wave_colored=?,demo_duration=? WHERE id=?",$audio,$price,$wave,$waveColored,$duration,$trackId);
    }
});

Hook::getInstance()->register('upload.album', function($albumId) {
    $C = getController();
    if ($price = $C->request->input('album_price')) {
        Database::getInstance()->query("UPDATE playlist SET price=? WHERE id=?", $price, $albumId);
    }
});

Hook::getInstance()->register("play.now", function($track) {
    if ($track['price'] > 0) {
        //check if user has purchase
        if (!model('store::store')->hasPurchased($track['id'])) {

            if ($track['demo_file'] and file_exists(path($track['demo_file']))) {
                $track['track_file'] = $track['demo_file'];

            }
        }
    }
    return $track;
});


Hook::getInstance()->register('track.play.detail', function($result, $track) {
    if ($track['price'] > 0) {
        //check if user has purchase
        if (!model('store::store')->hasPurchased($track['id'])) {
            $result['limit'] = 1;
            $result['buyType'] = 'track';
            $result['buyTrack'] = $track['id'];
            if ($track['demo_file']) {
                $ids = md5(uniqueKey(20, 50).session_id().time());
                //if (!is_ajax()) exit('FORBIDDEN');
                session_put($ids, 'set');
                $url = url('track/play/'.$track['id'].'/'.$ids);
                $file = config('enable-chunk-play', false) ? getController()->getFileUri($track['demo_file'], false) : getController()->getFileUri($track['demo_file']);
                if (preg_match('#http#', $file)) $url = $file;
                $result['url'] = $url;
                $result['wave'] = assetUrl($track['demo_wave']);
                $result['wave_colored'] = assetUrl($track['demo_wave_colored']);

            } else {
                if (!config('allow-play-before-buy', true)) {
                    $result['url'] = '';
                } else {
                    $result['percentage'] = config('play-percentage', 30);
                }
            }
        } else {
            if(!model('track')->hasView(model('user')->authId, $track['id']) and $track['userid'] != model('user')->authId) {
                if (config('artist-play-share', '0.001') > 0) {
                    $user = model('user')->getUser($track['userid']);
                    $balance = $user['balance'] + config('artist-play-share', '0.001');
                    Database::getInstance()->query("UPDATE users SET balance=? WHERE id=?", $balance, $user['id']);
                }
            }
        }
    }
    return $result;
});

Hook::getInstance()->register("track.download.url", function($url , $track) {
    if ($track['price'] > 0) {
        //check if user has purchase
        if (!model('store::store')->hasPurchased($track['id'])) {
            if ($track['demo_file']) {
                $url = '';
            }
        }
    }
    return $url;
});
Hook::getInstance()->register("track.download.file", function($url , $track) {
    if ($track['price'] > 0) {
        //check if user has purchase
        if (!model('store::store')->hasPurchased($track['id'])) {
            $url = '';
        }
    }
    return $url;
});


Hook::getInstance()->register('admin.track.edit', function($track) {
    $price = formatMoneyTotal($track['price']);
    echo "<div class='form-group'><label>".l('price')."</label><input type='' value='".$price."' name='val[price]' class='form-control'/></div>";
});

Hook::getInstance()->register('admin.playlists.edit', function($playlist, $page) {
    if ($page == 'albums') {
        $price = formatMoneyTotal($playlist['price']);
        echo "<div class='form-group'><label>".l('price')."</label><input type='' value='".$price."' name='val[price]' class='form-control'/></div>";
    }
});

Hook::getInstance()->register('admin.edit.playlist', function($val) {
    if (isset($val['price'])) {
        $price = convertBackToBase($val['price']);
        Database::getInstance()->query("UPDATE playlist SET price=? WHERE id=?", $price, $val['id']);
    }
});

Hook::getInstance()->register('track.left.below.art', function($track) {
    if (isset($track['playlist_id']) and $track['playlist_id']) {
        $playlist = model('track')->findPlaylist($track['playlist_id']);
        if ($playlist['playlist_type'] == 0 and $playlist['price'] > 0) {
            $price = formatMoney($playlist['price']);
            echo '<button onclick="return buy_item(\'album\',\''.$playlist['id'].'\')" title="'.l('buy-now').'" class=" hide-mobile btn btn-sm btn-primary round-sm mt-3 btn-block mtitle" style="width:80%"><i class="la la-cart-plus"></i>   <span class="">'.l('buy-now').'</span> ('.$price.') </button>';

        }
    } else{
        if ($track['price'] > 0) {
            $price = formatMoney($track['price']);
            echo '<button onclick="return buy_item(\'track\',\''.$track['id'].'\')" title="'.l('buy-now').'" class="hide-mobile btn btn-sm btn-primary round-sm mt-3 btn-block mtitle" style="width:80%"><i class="la la-cart-plus"></i>   <span class="hide-mobile">'.l('buy-now').'</span>  ('.$price.') </button>';
        }
    }
});
Hook::getInstance()->register('track.album.mini.list', function($track) {
    if ($track['price'] > 0){
        $price = formatMoney($track['price']);
        echo '<button onclick="return buy_item(\'track\',\''.$track['id'].'\')" title="'.l('buy-now').'" style="height:20px;position:relative;font-size:11px;padding:0px 5px" class="btn btn-sm btn-outline-secondary round-sm mtitle" onclick=""> <i class="la la-cart-plus"></i> <strong>'.$price.'</strong> </button> ';
    }
});

Hook::getInstance()->register('track.inline.actions', function($track) {
    if ($track['price'] > 0){
        $price = formatMoney($track['price']);
        echo '<button onclick="return buy_item(\'track\',\''.$track['id'].'\')" title="'.l('buy-now').'" style="position:relative;font-size:11px;padding:5px" class="btn btn-sm btn-outline-secondary round-sm mtitle" onclick=""> <i class="la la-cart-plus"></i> <strong>'.$price.'</strong> </button> ';
    }
});
Hook::getInstance()->register('playlist.inline.actions', function($track) {
    if ($track['price'] > 0){
        $price = formatMoney($track['price']);
        echo '<button onclick="return buy_item(\'album\',\''.$track['id'].'\')" title="'.l('buy-now').'" style="position:relative;font-size:11px;padding:5px" class="btn btn-sm btn-outline-secondary round-sm mtitle" onclick=""> <i class="la la-cart-plus"></i> <strong>'.$price.'</strong> </button> ';
    }
});

Hook::getInstance()->register('playlist.profile.actions', function($track) {
    if ($track['price'] > 0){
        $price = formatMoney($track['price']);
        echo '<button onclick="return buy_item(\'album\',\''.$track['id'].'\')" title="'.l('buy-now').'"  class="btn btn-sm btn-outline-secondary round-sm mtitle" onclick=""> <i class="la la-cart-plus"></i> <strong> '.l('buy-now').' '.$price.'</strong> </button> ';
    }
});
Hook::getInstance()->register('track.profile.action', function($track) {
    if ($track['price'] > 0){
        $price = formatMoney($track['price']);
        echo '<button onclick="return buy_item(\'track\',\''.$track['id'].'\')" title="'.l('buy-now').'" style="position:relative;" class="btn btn-sm btn-outline-secondary round-sm mtitle" > <i class="la la-cart-plus"></i>   '.l('buy-now').' ('.$price.')  </button> ';
    }
});

Hook::getInstance()->register('track.edit.content', function($track) {
    if ($track['price'] > 0) {
        $price = formatMoneyTotal($track['price']);
        echo '<div class="form-group"><label>'.l('price').' ('.model('user')->getUserCurrency().')</label><input value="'.$price.'" type="number" step="0.05" class="form-control" name="val[price]"/> </div>';
    }
});

Hook::getInstance()->register('track.edit', function($track, $val) {
    if (isset($val['price'])) {
        $price = convertBackToBase($val['price']);
        Database::getInstance()->query("UPDATE tracks SET price=? WHERE id=?", $price, $track['id']);
    }
});
Hook::getInstance()->register('playlist.edit.content', function($playlist) {
    if ($playlist['price'] > 0) {
        echo '<div class="form-group"><label>'.l('price').' ('.model('user')->getUserCurrency().')</label><input value="'.formatMoneyTotal($playlist['price']).'" type="number" step="0.05" class="form-control" name="val[price]"/> </div>';
    }
});
Hook::getInstance()->register('playlist.save', function($id, $val) {
    if (isset($val['price'])) {
        $price = convertBackToBase($val['price']);
        Database::getInstance()->query("UPDATE playlist SET price=? WHERE id=?", $price, $id);
    }
});

Hook::getInstance()->register('payment.detail', function($details, $type, $typeId) {
    if ($type == 'track') {
        $track = model('track')->findTrack($typeId);
        $details['title'] = $track['title'];
        $details['desc'] = l('buy-track').' - '.$track['title'];
    } elseif($type == 'album') {
        $playlist = model('track')->findPlaylist($typeId);
        $details['title'] = $playlist['name'];
        $details['desc'] = l('buy-album').' - '.$playlist['name'];
    }
    return $details;
});

Hook::getInstance()->register('pro.features', function() {
    echo '<div class="form-group">
                        <label class="custom-control custom-checkbox">
                            <input checked disabled type="checkbox" class="custom-control-input">
                            <span class="custom-control-indicator"></span>
                            <span class="custom-control-description">'.l('sell-your-tracks').'</span>
                        </label>
                    </div>';
});

Hook::getInstance()->register('payment.success.url', function($url, $type, $typeId) {
    if ($type == 'track') {
        $track = model('track')->findTrack($typeId);
        return model('track')->trackUrl($track);
    } elseif($type == 'album') {
        $playlist = model('track')->findPlaylist($typeId);
        return model('track')->playlistUrl($playlist);
    } elseif($type == 'video') {
        $video = model('video::video')->find($typeId);
        return model('video::video')->videoUrl($video);
    }
    return $url;
});
Hook::getInstance()->register('payment.success', function($type, $typeId) {
    if ($type == 'track' or $type == 'album' or $type == 'video') {
        model('store::store')->addPurchase($type, $typeId);
    }
});

Hook::getInstance()->register('notification.format', function($notification) {
    switch ($notification['type']) {
        case 'withdraw-request-processed':
            $notification['title'] = l('withdraw-request-processed');
            $notification['link'] = url('store/dashboard', array('type' => 'withdrawals'));
            break;
    }
    return $notification;
});

Hook::getInstance()->register('transaction.add', function($id, $type,$typeId, $amount) {
    if ($type == 'track' or $type == 'album') {
        $share = ($type == 'track') ? config('admin-track-share', 10) : config('admin-album-share', 10);
        $credit = ($share) ? $amount -  (($share * $amount) / 100)  : $amount;
        if ($type == 'track') {
            $track = model('track')->findTrack($typeId);
            $user = model('user')->getUser($track['userid']);
        } else {
            $album= model('track')->findPlaylist($typeId);
            $user = model('user')->getUser($album['userid']);
        }
        Database::getInstance()->query("UPDATE transactions SET amount_credited=? WHERE id=?", $credit, $id);
        $balance = $user['balance'] + $credit;
        Database::getInstance()->query("UPDATE users SET balance=? WHERE id=?", $balance, $user['id']);
    } elseif($type == 'video') {
        $share = config('admin-video-share', 10);
        $credit = ($share) ? $amount -  (($share * $amount) / 100)  : $amount;
        $video = model('video::video')->find($typeId);
        $user = model('user')->getUser($video['userid']);
        Database::getInstance()->query("UPDATE transactions SET amount_credited=? WHERE id=?", $credit, $id);
        $balance = $user['balance'] + $credit;
        Database::getInstance()->query("UPDATE users SET balance=? WHERE id=?", $balance, $user['id']);
    }
});

Hook::getInstance()->register('transaction.updated', function($transaction) {
    $type = $transaction['type'];
    $typeId = $transaction['type_id'];
    $credit = $transaction['amount_credited'];
    if ($type == 'track' or $type == 'album') {
        if ($type == 'track') {
            $track = model('track')->findTrack($typeId);
            $user = model('user')->getUser($track['userid']);
        } else {
            $album= model('track')->findPlaylist($typeId);
            $user = model('user')->getUser($album['userid']);
        }
        $balance = ($transaction['status'] == 0) ? $user['balance'] - $credit : $user['balance'] + $credit;
        Database::getInstance()->query("UPDATE users SET balance=? WHERE id=?", $balance, $user['id']);
    } elseif($type == 'video') {
        $video = model('video::video')->find($typeId);
        $user = model('user')->getUser($video['userid']);
        $balance = ($transaction['status'] == 0) ? $user['balance'] - $credit : $user['balance'] + $credit;
        Database::getInstance()->query("UPDATE users SET balance=? WHERE id=?", $balance, $user['id']);
    }
});

Hook::getInstance()->register('admin.dashboard.top', function() {
    echo view('store::admin/stats');
});

Hook::getInstance()->register('track.query', function($result, $type, $typeId) {
    $userid = model('user')->authId;
    $blockedIds = model('user')->blockIds();
    $expiredArtists = model('track')->getExpiredArtists();
    switch($type) {
        case 'store-browse':
            list($gType, $genre) = explode('-', $typeId);
            if ($gType == 'tracks') {
                $sql = "SELECT * FROM tracks WHERE status=? AND public = ?  AND approved=? AND price > 0 AND  userid NOT IN ($blockedIds) AND userid NOT IN ($expiredArtists) ";
                $param = array(1, 1, 1);
                if (isset($genre) and $genre != 'all') {
                    $sql .= " AND genre=? ";
                    $param[] = $genre;
                }
                $sql .= " ORDER BY id DESC ";

                $result['sql'] = $sql;
                $result['param'] = $param;
            } else {

                $sql = "SELECT * FROM playlist WHERE  playlist_type=? AND public = ? AND price > 0 AND  userid NOT IN ($blockedIds) AND userid NOT IN ($expiredArtists) ";
                $sql .= " ORDER BY id DESC ";
                $playlistType = 0;
                $param[] = $playlistType;
                $param[] = 1;
                $result['sql'] = $sql;
                $result['param'] = $param;
            }
            break;
        case 'store-best-week':
            $time = get_time_relative_format('this-week');
            $currentTime = time();
            $blockedIds = model('user')->blockIds();
            $sql = "SELECT *,(SELECT count(track) FROM views WHERE views.track=tracks.id AND views.time BETWEEN $time AND $currentTime ) as count FROM tracks WHERE tracks.status=? AND  userid NOT IN ($blockedIds) AND price > 0 AND  userid NOT IN ($blockedIds) AND userid NOT IN ($expiredArtists)  AND (tracks.public != ?)  AND approved=?";
            $param = array(1, 3,1);

            $sql .= " ORDER BY count DESC";
            $result['sql'] = $sql;
            $result['param'] = $param;
            break;
        case 'store-top-songs':
            $time = get_time_relative_format('this-year');
            $currentTime = time();
            $blockedIds = model('user')->blockIds();
            $sql = "SELECT *,(SELECT count(track) FROM views WHERE views.track=tracks.id AND views.time BETWEEN $time AND $currentTime ) as count FROM tracks WHERE tracks.status=? AND  userid NOT IN ($blockedIds) AND price > 0 AND  userid NOT IN ($blockedIds) AND userid NOT IN ($expiredArtists)  AND (tracks.public != ?)  AND approved=?";
            $param = array(1, 3,1);

            $sql .= " ORDER BY count DESC";
            $result['sql'] = $sql;
            $result['param'] = $param;
            break;
        case 'store-top-albums':
            $sql = "SELECT *,(SELECT count(typeid) as types FROM likes WHERE type=? AND typeid=playlist.id) as count FROM playlist WHERE  playlist_type=? AND public = ? AND price > 0 AND  userid NOT IN ($blockedIds) AND userid NOT IN ($expiredArtists)";
            $sql .= " ORDER BY count DESC ";
            $param = array('playlist',0,1);
            $result['sql'] = $sql;
            $result['param'] = $param;
            break;
    }
    return $result;
});

Hook::getInstance()->register('get.lists.track', function($result, $type, $typeId, $track) {
    if (isset($track['playlist_type'])) {
        $playlistId = $track['id'];
        $track = model('track')->getPlaylistFirstTrack($track['id']);
        $track['playlist_id'] = $playlistId;
        $result['track'] = $track;
    }
    return $result;
});

$request->any('admin/sales', array('uses' => 'store::admin@sales'));
$request->any('admin/withdraws', array('uses' => 'store::admin@index'));
$request->any('admin/withdraw/mark', array('uses' => 'store::admin@markPaid'));
$request->any('admin/withdraw-history', array('uses' => 'store::admin@index'));
$request->any('admin/funds', array('uses' => 'store::admin@index'));
$request->any("store/confirm/buy", array('uses' => 'store::store@confirmBuy'));
$request->any("store/dashboard", array('uses' => 'store::store@dashboard'));
$request->any("store/purchased", array('uses' => 'store::store@purchased'));
$request->any("store", array('uses' => 'store::store@store', 'secure' => false));
$request->any("store/top-songs", array('uses' => 'store::store@store', 'secure' => false));
$request->any("store/top-albums", array('uses' => 'store::store@store', 'secure' => false));
$request->any("store/browse", array('uses' => 'store::store@store', 'secure' => false));
