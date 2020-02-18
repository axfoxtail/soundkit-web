<?php
class SpotlightController extends Controller {
    public function save() {
        if ($val = $this->request->input('val')) {
            $tracks = $val['tracks'];
            $add = $this->model('spotlight::spotlight')->add($tracks);
            if ($add) {
                return json_encode(array(
                    'type' => 'function',
                    'value' => 'spotlightlistUpdated',
                    'message' => l('spotlight-list-updated'),
                    'content' => $this->view('spotlight::profile/edit-list')
                ));
            } else {
                return json_encode(array(
                    'type' => 'error',
                    'message' => l('spotlight-save-error')
                ));
            }
        }
    }

    public function remove() {
        $id = $this->request->input('id');
        $this->model('spotlight::spotlight')->remove($id);
        return json_encode(array(
            'type' => 'function',
            'value' => 'spotlightlistUpdated',
            'message' => l('spotlight-list-updated'),
            'content' => $this->view('spotlight::profile/edit-list')
        ));
    }

    public function index() {
        $this->setTitle(l('spotlight'));
        $this->activeMenu = 'spotlight';
        $this->addBreadCrumb(l('spotlight'));

        return $this->render($this->view('spotlight::index'), true);
    }
}