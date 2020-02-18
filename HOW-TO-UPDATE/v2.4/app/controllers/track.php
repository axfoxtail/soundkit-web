<?php
class TrackController extends Controller {
    public function before()
    {
        $this->activeMenu = 'discover';
    }

    public function upload() {
        $audio = $this->request->inputFile('track_file');

        include_once(path('app/vendor/getid3/getid3.php'));
        $uploader = new Uploader($audio, 'audio');
        $audioSize = $uploader->sourceSize;
        $val = array(
            'number' => $this->request->input('number')
        );
        $uploader->setPath("tracks/".$this->model('user')->authId.'/tracks/'.date('Y').'/');
        if ($uploader->passed()) {
            $val['size'] = $audioSize;
            $sourceFile = $audio['tmp_name'];

            $tmpMove = $uploader->uploadFile()->result();
            $file = path($tmpMove);
            $getID3 = new getID3;
            $ThisFileInfo = $getID3->analyze(path($tmpMove));
            $val['duration'] = $ThisFileInfo['playtime_seconds'];
            $dir = md5($file);
            if (!is_dir(path('uploads/waves/'.$dir))) {
                @mkdir(path('uploads/waves/'.$dir), 0777, true);
                $fileIndex = @fopen(path("uploads/waves/$dir/index.html"), 'x+');
                fclose($fileIndex);
            }

            if (config('wave-generator', 'browser') == 'server') {
                $wave = 'uploads/waves/'.$dir.'/wave_base'.time().'.png';
                $waveColored = 'uploads/waves/'.$dir.'/wave_top'.time().'.png';
                $outputWave = path($wave);
                $outputWaveColored = path($waveColored);
                $theme = config('theme', 'main');
                $color = config($theme.'-'.'wave_color', '#0F0710');
                $colored = config($theme.'-'.'wave_colored', '#FF5533');
                $scale = 'lin';
                $hookResult = Hook::getInstance()->fire('wave.generator', array('override' => false), array(
                    'input' => $file,
                    'wave' => $wave,
                    'waveColored' => $waveColored,
                ));
                if (!$hookResult['override']) {
                    try {
                        $result = shell_exec("".config('ffmpeg-path')."  -y -i $file -filter_complex \"aformat=channel_layouts=mono,compand=gain=-3,showwavespic=s=600x120:scale=$scale:colors=".$color.",drawbox=x=(iw-w)/2:y=(ih-h)/2:w=iw:h=1:color=white\" -frames:v 1 $outputWave 2>&1");
                        $result = shell_exec("".config('ffmpeg-path')." -y -i $file -filter_complex \"aformat=channel_layouts=mono,compand=gain=-3,showwavespic=s=600x120:scale=$scale:colors=".$colored.",drawbox=x=(iw-w)/2:y=(ih-h)/2:w=iw:h=1:color=white\" -frames:v 1 $outputWaveColored 2>&1");
                    }  catch (Exception $e) {
                        print_r($e);
                    }
                } else {
                    $wave = $hookResult['wave'];
                    $waveColored = $hookResult['waveColored'];
                }
                $val['wave'] = $wave;
                $val['waveColored'] = $waveColored;
            }
            $tmpMove = $this->uploadFile($tmpMove);
            Database::getInstance()->query("INSERT INTO tmp_files (path,time)VALUES(?,?)", $tmpMove, time()); //add the files do later delete
            $val['audio'] = $tmpMove;
        } else {
            return json_encode(array(
                'message' => $uploader->getError(),
                'type' => 'error'
            ));
        }
        return json_encode($val);
    }

    public function uploadWave() {
        $val = array(
            'number' => $this->request->input('number'),
            'type' => $this->request->input('type'),
            'demo' => $this->request->input('demo'),
        );
        $wave = $this->request->input('wave');
        $val['value'] = $this->model('track')->convertUriImageToFile($wave);

        //convert to second image
        $firstImage = $val['value'];
        $dir = md5($firstImage);
        if (!is_dir(path('uploads/waves/'.$dir))) {
            @mkdir(path('uploads/waves/'.$dir), 0777, true);
            $file = @fopen(path("uploads/waves/$dir/index.html"), 'x+');
            fclose($file);
        }
        $destImage = 'uploads/waves/'.$dir.'/wave_active.png';
        $theme = config('theme', 'main');
        list($baseR,$baseG,$baseB) = hexToRgb(config($theme.'-'.'wave_color', '#0F0710'));
        list($topR, $topG, $topB) = hexToRgb(config($theme.'-'.'wave_colored', '#FF5533'));
        colorizeKeepAplhaChannnel(path($firstImage),  $topR, $topG, $topB, path($destImage));
        //ReplaceImageColour(path($firstImage), path($firstImage), $baseR, $baseG, $baseB, $baseR, $baseG, $baseB);
        $val['value2'] = $destImage;
        return json_encode($val);
    }

    public function uploadPicture() {
        $val = array(
            'number' => $this->request->input('number'),
        );
        $imageContent = $this->request->input('picture');
        $imageContent = base64_decode(str_replace('data:image/jpeg;base64,','', $imageContent));
        $newUploader = new Uploader($imageContent, 'image', false, true, false, true);
        $newUploader->setPath("tracks/".$this->model('user')->authId.'/tracks/art/'.date('Y').'/');
        $val['value'] = $newUploader->resize()->result();
        return json_encode($val);
    }

    public function loadPlayerButtons() {
        $trackId = $this->request->input('track');

        $track = $this->model('track')->findTrack($trackId);

        if ($track) {
            echo $this->view('track/action-buttons', array('track' => $track));
        }
    }

    public function load() {
        $type = $this->request->input('type');
        $typeId = $this->request->input('type_id');
        $offset = $this->request->input('offset');
        $limit = ($this->request->input('limit')) ? $this->request->input('limit') : config('tracks-limit', 10);
        $viewType = $this->request->input('view', 'list');
        $newOffset = $offset + $limit;

        $tracks = $this->model('track')->getTracks($type, $typeId, $offset,$limit);
        if (!$tracks) {
            return $this->view('track/empty', array('type' => $type));
        }
        return $this->view('track/lists', array('tracks' => $tracks, 'type' => $type, 'typeId' => $typeId, 'viewType' => $viewType,'offset' => $offset));
    }

    public function paginate() {
        $type = $this->request->input('type');
        $typeId = $this->request->input('type_id');
        $offset = $this->request->input('offset');
        $limit = $this->request->input('limit', config('tracks-limit', 10));
        $newOffset = $offset + $limit;
        $viewType = $this->request->input('view', 'list');

        $result = array(
            'content' => '',
            'offset' => $newOffset
        );
        if (preg_match('#charts-#', $type) and $offset >= 40) {
            return json_encode(array(
                'content' => '',
                'offset' => $newOffset
            ));
        }
        $tracks = $this->model('track')->getTracks($type, $typeId, $newOffset,$limit);

        if($tracks) {
            $result['content'] = $this->view('track/lists', array('tracks' => $tracks, 'type' => $type, 'typeId' => $typeId, 'viewType' => $viewType,'offset' => $newOffset));
        }

        return json_encode($result);
    }

    public function playlist() {
        $playlists = $this->model('track')->getPlaylists();

        return $this->view('track/playlists', array('playlists' => $playlists, 'track' => $this->request->input('track')));
    }

    public function addPlaylist() {
        if ($val = $this->request->input('val')) {
            $this->model('track')->addPlaylist($val, array($val['id']));
            return json_encode(array(
                'type' => 'function',
                'value' => 'finishPlaylistCreate',
                'message' => l('playlist-added')
            ));
        }
    }

    public function addPlaylistTrack() {
        $added = $this->model('track')->addToPlaylist($this->request->input('id'), $this->request->input('track'));
        return json_encode(array(
            'type' => 'function',
            'value' => 'finishPlaylistAdd',
            'message' => ($added) ? l('track-added') : l('track-removed')
        ));
    }

    public function playlistPaginate() {
        $data = perfectUnserialize($this->request->input('data'));
        $type = $data['type'];
        $typeId = $data['typeId'];
        $offset = $this->request->input('offset');
        $dLimit = (isset($data['limit'])) ? $data['limit'] : 20;
        $limit = $dLimit*2;
        $theOffset = ($offset == 0) ? $dLimit : $offset;
        $newOffset = $offset + $limit;

        $playlists = $this->model('track')->listPlaylists($type, $typeId,  $limit, $theOffset);

        $content =  $this->view('playlist/paginate', array('playlists' => $playlists, 'type' => $type, 'typeId' => $typeId));
        $result = array(
            'content' => $content,
            'offset' => $newOffset
        );
        return json_encode($result);
    }

    public function addLater() {
        $added = $this->model('track')->addToLater($this->request->input('track'));
        return json_encode(array(
            'type' => 'function',
            'value' => 'finishLaterAdd',
            'content' => json_encode(array('track' => $this->request->input('track'), 'action' => $added, 'title' => $added ? l('remove-listen-later'): l('listen-later'))),
            'message' => ($added) ? l('track-added') : l('track-removed')
        ));
    }

    public function removeLater() {
        $added = $this->model('track')->addToLater($this->request->input('track'));
        return json_encode(array(
            'type' => 'reload',
            'message' => l('track-removed')
        ));
    }

    public function profile() {
        $this->loginRequired = false;
        $slug = explode('-', $this->request->segment(1));
        $trackId = $slug[0];
        $page = $this->request->segment(2, 'detail');
        $track = $this->model('track')->findTrack($trackId);
        if (!$track or !model('track')->displayItem($track, true)) return $this->errorPage();
        $this->setTitle(format_output_text($track['title']), true);
        $this->addBreadCrumb(format_output_text($track['title']) , ($page != 'detail') ? $this->model('track')->trackUrl($track) : '');
        if ($page != 'detail') $this->addBreadCrumb(l($page));

        $headerContent = '<meta property="og:image" content="'.$this->model('track')->getArt($track, 600).'"/>';
        $headerContent .= '<meta property="og:title" content="'.format_output_text($track['title']).'"/>';
        $headerContent .= '<meta property="og:url" content="'.$this->model('track')->trackUrl($track).'"/>';
        $headerContent .= '<meta property="og:description" content="'.$track['description'].'"/>';

        $headerContent .= '<meta property="twitter:image" content="'.$this->model('track')->getArt($track, 600).'"/>';
        $headerContent .= '<meta property="twitter:title" content="'.format_output_text($track['title']).'"/>';
        $headerContent .= '<meta property="title:description" content="'.$track['description'].'"/>';
        $this->addHeaderContent($headerContent);

        $this->useBreadcrumbs = false;
        switch ($page) {
            case 'likes':
                $content = $this->view('track/profile/people', array('type' => $page, 'pageType' => 'track', 'pageId' => $trackId));
                break;
            case 'reposters':
                $content = $this->view('track/profile/people', array('type' => $page, 'pageType' => 'track', 'pageId' => $trackId));
                break;
            case 'listeners':
                $content = $this->view('track/profile/people', array('type' => $page, 'pageType' => 'track', 'pageId' => $trackId));
                break;
            case 'edit':
                if ($track['userid'] != $this->model('user')->authId) return $this->request->redirect($this->model('track')->trackUrl($track));
                if ($val = $this->request->input('val')) {
                    $validator = Validator::getInstance()->scan($val, array(
                        'titles' => 'required',
                        'tags' => 'required',
                        'slug' => 'alphadash'
                    ));
                    if ($validator->passes()) {
                        if ($val['day'] and $val['month'] and $val['year']) {
                            $val['release'] = date("Y-m-d", mktime(0, 0, 0, $val['month'], $val['day'], $val['year']));
                        } else {
                            $val['release'] = '';
                        }

                        $artFile = $this->request->inputFile('img');
                        if ($artFile) {
                            $artUpload = new Uploader($artFile);
                            $artUpload->setPath("tracks/".$this->model('user')->authId.'/art/'.date('Y').'/');
                            if ($artUpload->passed()) {
                                $val['art'] = $artUpload->resize()->result();
                            } else {
                                return json_encode(array(
                                    'message' => $artUpload->getError(),
                                    'type' => 'error'
                                ));
                            }
                        }

                        if ($lyricsFile = $this->request->inputFile('lyrics_file')) {
                            $upload = new Uploader($lyricsFile, 'file');
                            $upload->setPath("tracks/".$this->model('user')->authId.'/lyric/'.date('Y').'/');
                            if ($upload->passed()) {
                                $val['lyrics'] = $upload->uploadFile()->result();
                            } else {
                                return json_encode(array(
                                    'message' => l('lyric-file-error'),
                                    'type' => 'error'
                                ));
                            }
                        }
                        $audioFiles = $this->request->input('val.trackfiles');
                        if ($this->model('track')->canChangeTrack() and $audioFiles) {

                            $i = 0;

                            foreach ($audioFiles as $audio) {

                                $val['size'] = $val['sizes'][$i];
                                $val['wave'] = $val['titlewaves'][$i];
                                $val['wave_colored'] = $val['titlewaves2'][$i];
                                $tmpMove = $audio;
                                $val['duration'] = $val['durations'][$i];
                                $val['audio'] = $audio;
                                $i++;
                            }
                        }

                        if ($val['license'] == 2) {
                            $val['license'] = '2-'.$val['noncommercial'].'-'.$val['creative_second'];
                        }

                        $this->model('track')->add($val, $track);

                        $track = $this->model('track')->findTrack($trackId);
                        return json_encode(array(
                            'type' => 'url',
                            'value' => $this->model('track')->trackUrl($track),
                            'message' => l('track-saved')
                        ));
                    } else {
                        return json_encode(array(
                            'message' => $validator->first(),
                            'type' => 'error'
                        ));
                    }
                }
                $content = $this->view('track/profile/edit', array('track' => $track));
                break;
            default:
                $content = $this->view('track/profile/details', array('track' => $track, 'pageType' => 'track', 'pageId' => $trackId));
                break;
        }


        return $this->render($this->view('track/profile/layout', array('track' => $track, 'content' => $content)), true);
    }

    public function setProfile() {
        $this->loginRequired = false;
        $slug = explode('-', $this->request->segment(1));
        $setId = $slug[0];
        $page = $this->request->segment(2, 'detail');
        $playlist = $this->model('track')->findPlaylist($setId);
        if (!$playlist  or !model('track')->displayItem($playlist, true) or !$this->model('track')->canViewPrivateAlbum($playlist)) return $this->errorPage();
        $this->setTitle(format_output_text($playlist['name']), false);
        $this->addBreadCrumb(format_output_text($playlist['name']) , ($page != 'detail') ? $this->model('track')->playlistUrl($playlist) : '');
        $this->useBreadcrumbs = false;
        if ($page != 'detail') $this->addBreadCrumb(l($page));
        $track = $this->model('track')->getPlaylistFirstTrack($playlist['id']);
        $headerContent = '<meta property="og:image" content="'.$this->model('track')->getArt($track, 600).'"/>';
        $headerContent .= '<meta property="og:title" content="'.format_output_text($playlist['name']).'"/>';
        $headerContent .= '<meta property="og:url" content="'.$this->model('track')->playlistUrl($playlist).'"/>';
        $headerContent .= '<meta property="og:description" content="'.$playlist['description'].'"/>';

        $headerContent .= '<meta property="twitter:image" content="'.$this->model('track')->getArt($track, 600).'"/>';
        $headerContent .= '<meta property="twitter:title" content="'.format_output_text($playlist['name']).'"/>';
        $headerContent .= '<meta property="twitter:description" content="'.$playlist['description'].'"/>';
        $this->addHeaderContent($headerContent);
        if ($page == 'edit' and $playlist['userid'] != $this->model('user')->authId) return $this->request->redirectBack();
        switch ($page) {
            case 'likes':
                $content = $this->view('track/profile/people', array('type' => $page, 'pageType' => 'playlist', 'pageId' => $setId));
                break;
            case 'reposters':
                $content = $this->view('track/profile/people', array('type' => $page, 'pageType' => 'playlist', 'pageId' => $setId));
                break;
            case 'edit':
                $content = $this->view('playlist/profile/edit', array('playlist' => $playlist));
                break;
            default:
                $content = $this->view('playlist/profile/details', array('playlist' => $playlist));
                break;
        }


        return $this->render($this->view('playlist/profile/layout', array('playlist' => $playlist, 'content' => $content)), true);

    }

    public function savePlaylistOrder() {
        $id = $this->request->input('id');
        $tracks = $this->request->input('tracks');

        $this->model('track')->saveTracksOrder($id, $tracks);
        return json_encode(array(
            'type' => 'success',
            'message' => l('tracks-order-saved')
        ));
    }

    public function paginatePeople() {
        $data = perfectUnserialize($this->request->input('data'));
        $type = $this->request->input('type');
        $pageId = $this->request->input('pageId');
        $pageType = $this->request->input('page');
        $offset = $this->request->input('offset');
        $limit = config('users-limit', 20)*2;
        $theOffset = ($offset == 0) ? config('users-limit', 20) : $offset;
        $newOffset = $offset + $limit;
        if ($data) {
            $type = $data['type'];
            $pageType = $data['page'];
            $pageId = $data['pageId'];
        }
        $result = array(
            'content' => '',
            'offset' => $newOffset
        );
        $users = $this->model('track')->getPeople($type, $pageType,$pageId, $theOffset, $limit);

        if($users) {
            $result['content'] = $this->view('track/profile/list-people', array('users' => $users));
        }

        return json_encode($result);
    }

    public function addComment() {
        if ($val = $this->request->input('val')) {
            $validator = Validator::getInstance()->scan($val, array(
                'id' => 'required',
                'comment_text' => 'required',

            ));
            if ($validator->passes()) {

                $commentId = $this->model('track')->addComment($val);
                if (isset($val['at'])) {
                    return json_encode(array(
                        'type' => 'function',
                        'value' => 'commentAddedAt',
                        'message' => (isset($val['replyto']) and $val['replyto']) ? l('reply-added') : l('comment-added'),
                        'content' => $val['id']
                    ));
                } else {
                    return json_encode(array(
                        'type' => 'function',
                        'value' => 'commentAdded',
                        'content' => json_encode(array(
                            'comment' => $this->view('track/comment/format', array('comment' => $this->model('track')->findComment($commentId))),
                            'type' => $val['type'],
                            'id' => $val['id'],
                            'count' => $this->model('track')->countComments($val['type'],$val['id'])
                        ))
                    ));
                }
            } else {
                return json_encode(array(
                    'message' => $validator->first(),
                    'type' => 'error'
                ));
            }
        }
    }

    public function commentTimeLoad() {
        $ids = explode(',', $this->request->input('ids'));
        $width = $this->request->input('width');

        $results = array();
        foreach($ids as $id) {
            $comments = $this->model('track')->getTimeComments($id, 500);
            $results[$id] = $this->view('track/comment/time-users', array('comments' => $comments, 'width' => $width));
        }

        return json_encode($results);
    }

    public function loadComment() {
        $type = $this->request->input('type');
        $typeId = $this->request->input('type_id');
        $offset = $this->request->input('offset');
        $limit = config('comment-limit', 5);
        $newOffset = $offset + $limit;

        $comments = $this->model('track')->getComments($type, $typeId, $offset);

        return $this->view('track/comment/lists', array('comments' => $comments, 'type' => $type, 'typeId' => $typeId));
    }

    public function deleteComment() {
        $id = $this->request->input('id');

        $comment = $this->model('track')->findComment($id);
        if ($this->model('track')->deleteComment($id)) {
            return json_encode(array(
                'type' => 'function',
                'message' => l('comment-deleted'),
                'value' => 'commentDeleted',
                'content' => json_encode(array(
                    'id' => $id,
                    'type' => $comment['type'],
                    'count' => $this->model('track')->countComments($comment['type'],$id)
                ))
            ));
        } else {
            return json_encode(array(
                'type' => 'error',
                'message' => l('permission-error-action')
            ));
        }
    }

    public function paginateComment() {
        $data = perfectUnserialize($this->request->input('data'));
        $type = $data['type'];
        $typeId = $data['typeId'];
        $offset = $this->request->input('offset');
        $limit = config('comment-limit', 5)*2;
        $theOffset = ($offset == 0) ? config('comment-limit', 5) : $offset;
        $newOffset = $offset + $limit;

        $comments = $this->model('track')->getComments($type, $typeId, $theOffset, $limit);

        $content =  $this->view('track/comment/lists', array('comments' => $comments, 'type' => $type, 'typeId' => $typeId, 'paginate' => true));
        $result = array(
            'content' => $content,
            'offset' => $newOffset
        );
        return json_encode($result);
    }

    public function reportComment() {
        $id = $this->request->input('id');
        $comment = $this->model('track')->findComment($id);
        $reported = $this->model('track')->report('comment', $id, $comment['message']);
        return json_encode(array(
            'type' => 'function',
            'value' => 'commentReported',
            'content' => $id,
            'message' => l('comment-reported')
        ));
    }

    public function reportTrack() {
        $val = $this->request->input('val');
        /**
         * @var $description
         * @var $id
         * @var $good
         * @var $info
         * @var $us
         */
        extract(array_merge(array(
            'description' => '',
            'good' => false,
            'info' => false,
            'us' => false
        ), $val));

        if (!$description or !$id or !$good or !$info or !$us) {
            return json_encode(array(
                'type' => 'error',
                'message' => l('all-fields-required')
            ));
        }

        $reported = $this->model('track')->report('track', $id, $description);
        return json_encode(array(
            'type' => 'modal-function',
            'modal' => '#reportTrack',
            'message' => l('track-reported')
        ));
    }

    public function likeItem() {
        $type = $this->request->input('type');
        $typeId = $this->request->input('type_id');

        $like = $this->model('track')->likeItem($type, $typeId);
        return json_encode(array(
            'type' => 'function',
            'value' => 'itemLiked',
            'content' => json_encode(array(
                'text' => $like ? l('liked') : l('like'),
                'count' => $this->model('track')->countLikes($type, $typeId),
                'action' => $like ? true : false,
                'type' => $type,
                'typeId' => $typeId,
                'likes' => $this->view('track/likes/inline-display', array('type' => $type, 'typeId' => $typeId))
            ))
        ));
    }

    public function repostItem() {
        $action = $this->request->input('action');
        $track = $this->request->input('track');
        $playlistId = $this->request->input('playlist_id');

        $repost = $this->model('track')->addStream($track, $playlistId, $action);
        $result = array(
            'type' => 'function',
            'value' => 'itemReposted',
            'content' => json_encode(array(
                'text' => $repost ? l('reposted') : l('repost'),
                'count' => $playlistId ? $this->model('track')->countReposts($track, true) : $this->model('track')->countReposts($track, false),
                'action' => $repost ? true : false,
                'track' => $track,
                'playlist' => $playlistId,

            )));

        if ($repost) {
            $result['message'] = ($playlistId) ? l('playlist-repost-on-profile') : l('track-repost-on-profile');
        }


        return json_encode($result);
    }

    public function setViews() {
        $this->model('track')->setViews($this->request->input('track'));
    }

    public function playlistForm(){
        $id = $this->request->input('id');
        $playlist = $this->model('track')->findPlaylist($id);
        if ($playlist['userid'] != $this->model('user')->authId) return false;
        if ($val = $this->request->input('val')) {
            if ($art = $this->request->inputFile('img')) {
                $artUpload = new Uploader($art);
                $artUpload->setPath("playlist/".$this->model('user')->authId.'/art/'.date('Y').'/');
                if ($artUpload->passed()) {
                    $val['art'] = $artUpload->resize()->result();
                } else {
                    return json_encode(array(
                        'message' => $artUpload->getError(),
                        'type' => 'error'
                    ));
                }
            }
            $this->model('track')->savePlaylist($id, $val);
            return json_encode(array(
                'type' => 'modal-url',
                'value' => $this->model('track')->playlistUrl($playlist),
                'content' => '#editPlaylistModal',
                'message' => l('playlist-saved-success')
            ));
        }
        return $this->view('playlist/edit', array('playlist' => $playlist));
    }

    public function download() {
        $id = $this->request->input('id');
        $this->model('track')->addDownload($id);
    }

    public function downloadAlbum() {
        $zip = new ZipArchive();
        if (!config('enable-zip-download-album', false)) exit('NOT ALLOWED');
        $playlistId = $this->request->input('id');
        $playlist = $this->model('track')->findPlaylist($playlistId);
        $tracks = model('track')->getPlaylistTracks($playlistId, 0);
        $base = 'uploads/tmp/zips/';
        if (!is_dir(path($base))) {
            mkdir(path($base), 0777,true);
        }
        $archive_file_name = path($base.toAscii($playlist['name']).count($tracks).'.zip');
        if (!file_exists($archive_file_name)) {
            if ($zip->open($archive_file_name, ZIPARCHIVE::CREATE )!==TRUE) {
                exit("cannot open <$archive_file_name>\n");
            }
            foreach($tracks as $track) {
                if ($this->model('track')->canDownload($track)) {
                    $file = $this->getFileUri($track['track_file'], false);
                    if (preg_match('#http#', $file)) {
                        $zip->addFromString(toAscii($track['title']).'.mp3', file_get_contents($file));
                    } else {
                        $zip->addFile(path($file), toAscii($track['title']).'.mp3');
                    }
                }
            }
            $zip->close();
        }
        $fileName = $playlist['name'].'.zip';
        header("Content-type: application/zip");
        header("Content-Disposition: attachment; filename=$fileName");
        header("Content-length: " . filesize($archive_file_name));
        header("Pragma: no-cache");
        header("Expires: 0");
        readfile("$archive_file_name");
    }

    public function trackAsTags() {
        $playlistToo = ($this->request->segment(1) === 'track-tags') ? false : true;
        return $this->model('track')->searchTrackTags($this->request->input('term'), $playlistToo);
    }

    public function play() {
        $trackId = $this->request->segment(2);
        $hash = $this->request->segment(3);

        if (!session_get($hash)) return 'FORBIDDEN';

        $sessionValue = session_get($hash);
        if ($sessionValue != 'set') {
            if (!isset($_SERVER['HTTP_RANGE'])) {
                return 'FORBIDDEN';
            }
        }

        $track = $this->model('track')->findTrack($trackId, false);
        //$file = $this->model('track')->getTrackFile($track);
        $track = Hook::getInstance()->fire("play.now", $track);
        $file = $track['track_file'];
        $filename = $track['track_file'];
        session_put($hash, 'used');
        smartReadFile(path($file), $filename, getMimeType($filename));

    }

    public function trackDetail() {
        $trackId = $this->request->segment(2);
        if (moduleExists('advert')) {
            $ad = model('advert::advert')->display('audio');
            if ($ad) {
               $trackId = $ad['track_id'];
                $result =  $this->_getTrackDetail($trackId);
                $result['sponsored'] = 1;
                return json_encode($result);
            }
        }
        $result =  $this->_getTrackDetail($trackId);
        return json_encode($result);
    }

    public  function _getTrackDetail($trackId) {
        $track = $this->model('track')->findTrack($trackId, false);
        $ids = md5(uniqueKey(20, 50).session_id().time());
        //if (!is_ajax()) exit('FORBIDDEN');
        session_put($ids, 'set');
        $url = url('track/play/'.$track['id'].'/'.$ids);
        $file = config('enable-chunk-play', false) ? $this->getFileUri($track['track_file'], false) : $this->getFileUri($track['track_file']);
        if (preg_match('#http#', $file)) $url = $file;

        $user = $this->model('user')->getUser($track['userid']);
        $result = array(
            'link' => $this->model('track')->trackUrl($track),
            'wave' => assetUrl($track['wave']),
            'title' => $track['title'],
            'art' => $this->model('track')->getArt($track),
            'owner' => $user['full_name'],
            'ownerLink' => model('user')->profileUrl($user),
            'url' => $url,
            'wave_colored' => assetUrl($track['wave_colored']),
            'limit' => 0,
            'lyrics' => ($track['lyrics']) ? file_get_contents(path($track['lyrics'])) : '',
            'duration' => $track['track_duration']
        );

        $result = Hook::getInstance()->fire('track.play.detail', $result, array($track));
        return $result;
    }

    public function deleteTrack() {
        $id = $this->request->input('id');

        $track = $this->model('track')->findTrack($id);
        if ($track['userid'] != $this->model('user')->authId) return $this->request->redirectBack();
        $this->model('admin')->deleteTrack($id);
        return $this->request->redirect(url());
    }

    public function deletePlaylist() {
        $id = $this->request->input('id');

        $playlist = $this->model('track')->findPlaylist($id);
        if ($playlist['userid'] != $this->model('user')->authId) return $this->request->redirectBack();
        $this->model('admin')->deletePlaylist($id);
        return $this->request->redirect(url());
    }

    public function downloadTrack() {
        $key = $this->request->input('key');
        $track = $this->model('track')->findTrack($key);

        if (!$track) return $this->request->redirect(url());
        //if ($track['downloads'])
        $file = $this->getFileUri($track['track_file'], false);

        //$file = Hook::getInstance()->fire('track.download.file', $file, array($track));
        //if (!$this->model('track')->canDownload($track)) exit('No Permission');
        //$file = assetUrl($file);
        if (preg_match('#http#', $file)) {
            //we need to download first to a local place and delete when done
            $ext = getMimeTypeExtension(getMimeType($file));
            $ext = ($ext) ? $ext : 'mp3';
            $filename = format_output_text($track['title']).'.'.$ext;
            ob_start();
            header('Pragma: public');
            header('Cache-Control: public, no-cache');
            header('Content-Type: application/octet-stream');
            header("Content-Transfer-Encoding: Binary");
            header("Content-disposition: attachment; filename=\"" . $filename . "\"");
            ob_clean(); flush();
            readfile($file); // do the double-download-dance (dirty but worky)
        } else{

            return download_file(path($file), format_output_text($track['title']));
        }
    }
}