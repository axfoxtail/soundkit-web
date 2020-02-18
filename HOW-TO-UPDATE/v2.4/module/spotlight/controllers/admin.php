<?php
//include_once path('app/controllers/admin.php');
class AdminController extends Controller {
    public function __construct($request)
    {
        $this->adminRequired = true;
        parent::__construct($request);
        $this->addBreadCrumb(l('admin-panel'));
        $this->sideMenu = "admin/includes/side-menu";
    }

    public function add() {
        $id = $this->request->input('id');
        $type = $this->request->input('type', 'trackid');
        $added = $this->model('spotlight::spotlight')->addGlobal($id, $type);
        return json_encode(array(
            'type' => 'function',
            'message' => l('global-spotlight-list-updated'),
            'value' => 'spotlightGlobalAdded',
            'content' => json_encode(array(
                'id' => $id,
                'title' => ($added) ? l('remove-from-spotlight') : l('add-to-spolight'),
                'class' => ($added) ? 'btn-success' : 'btn-secondary'
            ))
        ));
    }
}