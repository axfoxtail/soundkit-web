<?php
class AdminController extends Controller {
    public function __construct($request)
    {
        $this->adminRequired = true;
        parent::__construct($request);
        $this->addBreadCrumb(l('admin-panel'));
        $this->sideMenu = "admin/includes/side-menu";
    }
    public function videos() {
        $this->adminSecure('manage-videos');
        $this->setTitle(l('videos'));
        $genre = $this->request->input('genre', '');
        $user = $this->request->input('user', '');
        $term = $this->request->input('term', '');
        if ($action= $this->request->input('action') == 'delete') {
            if ($this->isDemo()) $this->defendDemo();
            $video = $this->model('video::video')->find($this->request->input('id'));
            $this->model('video::video')->delete($this->request->input('id'));
        }

        if ($val = $this->request->input('val')) {
            if ($this->isDemo()) $this->defendDemo();
            $video = $this->model('video::video')->find($val['id']);
            $validator = Validator::getInstance()->scan($val, array(
                'titles' => 'required',
                'tags' => 'required'
            ));
            if ($validator->passes()) {

                $this->model('video::video')->addVideo($val, $video);

                return json_encode(array(
                    'type' => 'modal-url',
                    'value' => getFullUrl(true),
                    'message' => l('saved-success'),
                    'content' => "#editVideoModal-".$val['id']
                ));
            } else {
                return json_encode(array(
                    'message' => $validator->first(),
                    'type' => 'error'
                ));
            }

        }
        $videos = $this->model('video::video')->getAdminVideos($genre, $term, $user);
        return $this->render($this->view('video::admin/index', array('videos' => $videos, 'genre' => $genre, 'term' => $term, 'user' => $user)), true);
    }
}