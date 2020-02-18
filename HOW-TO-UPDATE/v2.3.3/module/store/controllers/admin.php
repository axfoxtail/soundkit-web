<?php
class AdminController extends Controller {
    public function __construct($request)
    {
        $this->adminRequired = true;
        parent::__construct($request);
        $this->addBreadCrumb(l('admin-panel'));
        $this->sideMenu = "admin/includes/side-menu";
    }

    public function sales() {
        $this->setTitle(l('manage-sales'));
        $this->addBreadCrumb(l('manage-sales'));
        $this->activeMenu = "sales";

        $type = $this->request->input('type', 'tracks');

        return $this->render($this->view('store::admin/sales', array('type' => $type)), true);
    }

    public function markPaid() {
        $id = $this->request->input('id');
        $this->model('store::store')->markPaid($id);
        return json_encode(array(
            'type' => 'url',
            'value' => url('admin/withdraws'),
            'message' => l('withdraw-request-marked-paid')
        ));
    }

    public function index() {
        $this->setTitle(l('manage-payments'));
        $this->addBreadCrumb(l('manage-payments'));
        $this->activeMenu = 'payments';

        $type = $this->request->segment(1, 'withdraws');
        switch ($type) {
            case 'withdraw-history':
                $content = $this->view('store::admin/withdraw-history');
                break;
            case 'funds':
                $content = $this->view('store::admin/funds');
                break;
            default:
                $content = $this->view('store::admin/withdrawals');
                break;
        }

        return $this->render($this->view('store::admin/payment', array('content' => $content, 'type' => $type)), true);
    }
}