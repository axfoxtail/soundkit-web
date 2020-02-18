<?php
Hook::getInstance()->register('main.menu.home', function () {
   echo view('video::user-menu');
});

Hook::getInstance()->register("main.menu.collection", function() {
    $active = (getController()->activeMenu == 'watch-later') ? 'active' : null;
    echo '<li><a id="watch-later-menu" class="sub-menu  '.$active.' " href="'.url('watch/later').'"  data-ajax="true"> <i class="la-clock-o la "></i> <span>'.l('watch-later').'</span></a></li>';

    $active = (getController()->activeMenu == 'watch-history') ? 'active' : null;
    echo '<li><a id="watch-history-menu" class="sub-menu  '.$active.' " href="'.url('watch/history').'"  data-ajax="true"> <i class="la-calendar la "></i> <span>'.l('watch-history').'</span></a></li>';
});

Hook::getInstance()->register('admin.settings.integrations', function() {
    echo view('video::admin/settings/integration');
});

Hook::getInstance()->register('admin.menu.middle', function() {
    $active = getController()->activeMenu == 'videos' ? 'active' : '';
    if(model('user')->hasRole('manage-videos')) {
        echo '<li><a id="videos-menu" data-ajax="true" class="sub-menu '.$active.'" href="'.url('admin/videos').'"><i class="la la-film"></i>  '.l('videos').'</a></li>';
    }
});

Hook::getInstance()->register('uploads.type', function() {
    echo view('video::upload/extend');
});

Hook::getInstance()->register("comment.added", function($commentId, $type, $id) {
    if ($type == 'video') {
        $video  = model('video::video')->find($id);
        if ($video['userid'] != model('user')->authId) {
            $theUser = model('user')->getUser($video['userid']);
            if ($theUser['notifyc']) {
                model('user')->addNotification($theUser['id'], 'comment-video', $id);
                model('user')->sendSocialMail($theUser, 'comment','comment-video', $id);
            }
        }
    }
});

Hook::getInstance()->register("comment.reply", function($commentId, $type, $id) {
    $theComment = model('track')->findComment($id);
    if ($theComment['type'] == 'video') {
        //$video  = model('video::video')->find($theComment['typeid']);
        if ($theComment['userid'] != model('user')->authId) {
            $theUser = model('user')->getUser($theComment['userid']);
            if ($theUser['notifyc']) {
                model('user')->addNotification($theUser['id'], 'reply-comment-video', $id);
                model('user')->sendSocialMail($theUser, 'comment','reply-comment-video', $id);
            }
        }
    }
});

Hook::getInstance()->register('social.mail.comment', function($type, $id,$theUser) {
    switch($type) {
        case 'comment-video':
            $video = model('video::video')->find($id);
            $link = model('video::video')->videoUrl($video);
            $mailer = Email::getInstance();
            $mailer->setAddress($theUser['email'],$theUser['full_name']);
            $mailer->setSubject(l('new-comment-video'));
            $content = l('new-comment-video-mail', array('video' => $video['title'], 'link' => $link));
            $mailer->setMessage($content);
            $mailer->send();
            break;
        case 'reply-comment-video':
            $comment = model('track')->findComment($id);
            $video = model('video::video')->find($comment['typeid']);
            $link = model('video::video')->videoUrl($video);
            $mailer = Email::getInstance();
            $mailer->setAddress($theUser['email'],$theUser['full_name']);
            $mailer->setSubject(l('new-reply-video'));
            $content = l('new-reply-video-mail', array('video' => $video['title'], 'link' => $link));
            $mailer->setMessage($content);
            $mailer->send();
            break;
    }
});

Hook::getInstance()->register('social.mail.like', function($type, $id,$theUser) {
    switch($type) {
        case 'like-video':
            $video = model('video::video')->find($id);
            $link = model('video::video')->videoUrl($video);
            $mailer = Email::getInstance();
            $mailer->setAddress($theUser['email'],$theUser['full_name']);
            $mailer->setSubject(l('new-like-video'));
            $content = l('new-like-video-mail', array('video' => $video['title'], 'link' => $link));
            $mailer->setMessage($content);
            $mailer->send();
            break;
    }
});

Hook::getInstance()->register('like.item', function($type,$typeId) {
    if($type == 'video') {
        $video  = model('video::video')->find($typeId);
        if ($video['userid'] != model('user')->authId) {
            $theUser = model('user')->getUser($video['userid']);
            if ($theUser['notifyl']) {
                model('user')->addNotification($theUser['id'], 'like-video', $typeId);
            }
            model('user')->sendSocialMail($theUser, 'like','like-video', $typeId);
        }
    }
});

Hook::getInstance()->register('statistics.tabs.extend', function($id) {
    if($id == 'all') {
        echo '<li class="nav-item">
                    <a class="nav-link  " data-toggle="tab"    href="#videos" >'.l('videos').'</a>
                </li>';
    }
});

Hook::getInstance()->register('statistics.tabs.content', function($id,$strtime) {
   if ($id == 'all') {
       echo view('video::statistics/index', array('strtime' => $strtime));
   }
});

Hook::getInstance()->register('delete.user', function($id) {
   model('video::video')->deleteUserVideos($id);
});

Hook::getInstance()->register('notification.format', function($notification){
    switch($notification['type']) {
        case 'comment-video':
            $video = model('video::video')->find($notification['typeid']);
            if($video['userid'] == model('user')->authId) {
                $notification['title'] = l('commented-your-video').' <strong>'.str_limit($video['title'], 35).'</strong>';
            } else {
                $notification['title'] = l('commented-this-video').' <strong>'.str_limit($video['title'], 35).'</strong>';
            }
            $notification['link'] = model('video::video')->videoUrl($video);
            break;
        case 'reply-comment-video':
            $comment = model('track')->findComment($notification['typeid']);
            $video = model('video::video')->find($comment['typeid']);
            $notification['link'] = model('video::video')->videoUrl($video);
            $notification['title'] = l('replied-your-comment-video').' <strong>'.str_limit($video['title'], 35).'</strong>';
            break;
        case 'like-video':
            $video = model('video::video')->find($notification['typeid']);
            $notification['title'] = l('like-your-video').' <strong>'.str_limit($video['title'], 35).'</strong>';
            $notification['link'] = model('video::video')->videoUrl($video);
            break;
    }
    return $notification;
});

Hook::getInstance()->register("store.overview.middle", function() {
   echo view('video::store/extend');
});

Hook::getInstance()->register('profile.menus', function($user, $page) {
    $active = ($page == 'videos') ? 'active' : null;
    if(model('video::video')->canAddVideo($user)) {
        echo '<li class="nav-item">
                <a class="nav-link '.$active.'"  data-ajax="true"  href="'.model('user')->profileUrl($user,'videos').'" >'.l('videos').'</a>
            </li>';
    }
});

Hook::getInstance()->register("profile.content", function($result, $user,$page) {
   if ($page == 'videos') {
       $result['content'] = view('video::profile/content', array('user' => $user));
   }
   return $result;
});

Hook::getInstance()->register("track.side.details", function($track) {
    if ($video = model('video::video')->getTrackVideo($track['id'])) {
        $url = model('video::video')->videoUrl($video);
        echo "<a href='".$url."' data-ajax='true' class='btn btn-primary btn-block btn-sm'>".l('watch-video-here')."</a>";
    }
});


$request->any("videos", array('uses' => 'video::video@index', 'secure' => false));
$request->any("videos/latest", array('uses' => 'video::video@index', 'secure' => false));
$request->any("videos/category/{id}", array('uses' => 'video::video@index', 'secure' => false))->where(array('id' => '[a-zA-Z0-9\_\-]+'));
$request->any("videos/top", array('uses' => 'video::video@index', 'secure' => false));
$request->any("video/reload/suggestion", array('uses' => 'video::video@reload', 'secure' => false));
$request->any("videos/paginate", array('uses' => 'video::video@paginate', 'secure' => false));
$request->any("videos/upload", array('uses' => 'video::video@upload'));
$request->any("videos/import", array('uses' => 'video::video@upload'));
$request->any("video/add/later", array('uses' => 'video::video@addLater'));
$request->any("video/fetch", array('uses' => 'video::video@fetch'));
$request->any("video/play", array('uses' => 'video::video@play'));
$request->any("watch/later", array('uses' => 'video::video@later'));
$request->any("watch/history", array('uses' => 'video::video@history'));
$request->any("watch/{id}", array('uses' => 'video::video@page', 'secure' => false))->where(array('id' => '[a-zA-Z0-9\_\-]+'));
$request->any("watch/{id}/edit", array('uses' => 'video::video@page'))->where(array('id' => '[a-zA-Z0-9\_\-]+'));
$request->any("watch/{id}/delete", array('uses' => 'video::video@page'))->where(array('id' => '[a-zA-Z0-9\_\-]+'));

$request->any("admin/videos", array('uses' => 'video::admin@videos'));
