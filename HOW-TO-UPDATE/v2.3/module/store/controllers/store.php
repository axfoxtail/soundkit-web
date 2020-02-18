<?php
class StoreController extends Controller {
    public function confirmBuy() {
        $type = $this->request->input('type');
        $id = $this->request->input('id');
        switch($type) {
            case 'track':
                $track = $this->model('track')->findTrack($id);
                if (!$track) {
                    return json_encode(array(
                        'status' => 0,
                        'message' => l('cannot-find-item-details'),
                        'type' => 'error'
                    ));
                }
                if ($track['userid'] == model('user')->authId) {
                    return json_encode(array(
                        'status' => 0,
                        'message' => l('you-cannot-buy-your-track'),
                        'type' => 'error'
                    ));
                }
                if ($this->model('store::store')->hasPurchased($id, $type, true)) {
                    return json_encode(array(
                        'status' => 0,
                        'message' => l('you-already-own-track'),
                        'type' => 'success'
                    ));
                }
                return json_encode(array(
                    'type' => 'track',
                    'id' => $id,
                    'price' => $track['price'],
                    'status' => 1
                ));
                break;
            case 'video':
                $video = $this->model('video::video')->find($id);
                if (!$video) {
                    return json_encode(array(
                        'status' => 0,
                        'message' => l('cannot-find-item-details'),
                        'type' => 'error'
                    ));
                }
                if ($video['userid'] == model('user')->authId) {
                    return json_encode(array(
                        'status' => 0,
                        'message' => l('you-cannot-buy-your-video'),
                        'type' => 'error'
                    ));
                }
                if ($this->model('store::store')->hasPurchased($id, $type, true)) {
                    return json_encode(array(
                        'status' => 0,
                        'message' => l('you-already-own-video'),
                        'type' => 'success'
                    ));
                }
                return json_encode(array(
                    'type' => 'video',
                    'id' => $id,
                    'price' => $video['price'],
                    'status' => 1
                ));
                break;
            case 'album':
                $playlist = $this->model('track')->findPlaylist($id);
                if (!$playlist) {
                    return json_encode(array(
                        'status' => 0,
                        'message' => l('cannot-find-item-details'),
                        'type' => 'error'
                    ));
                }

                if ($playlist['userid'] == model('user')->authId) {
                    return json_encode(array(
                        'status' => 0,
                        'message' => l('you-cannot-buy-your-playlist'),
                        'type' => 'error'
                    ));
                }
                if ($this->model('store::store')->hasPurchased($id, $type, true)) {
                    return json_encode(array(
                        'status' => 0,
                        'message' => l('you-already-own-playlist'),
                        'type' => 'success'
                    ));
                }
                return json_encode(array(
                    'type' => 'album',
                    'status' => 1,
                    'id' => $id,
                    'price' => $playlist['price']
                ));
                break;
        }
    }

    public function dashboard() {
        $this->setTitle(l('your-store'));
        $this->addBreadCrumb(l('your-store'));
        $this->activeMenu = 'store-dashboard';
        if(!model('user')->subscriptionActive() and config('disable-tracks', true)) return $this->request->redirect(url('store'));
        $type = $this->request->input('type', 'sales');
        switch($type) {
            case 'withdrawals':
                if ($val = $this->request->input('val')) {
                    $amount = $val['amount'];
                    if (empty($amount) or !is_numeric($amount)) return json_encode(array(
                        'type' => 'error',
                        'message' => l('please-enter-valid-amount')
                    ));

                    if ($amount < config('minimum-withdraw-request', 50)) {
                        return json_encode(array(
                            'type' => 'error',
                            'message' => l('minimum-amount-withdraw', array('amount' => formatMoney(config('minimum-withdraw-request', 50))))
                        ));
                    }

                    if ($amount < $this->model('user')->authUser['balance']) {
                        if ($this->model('store::store')->hasPendingWithdraw()) {
                            return json_encode(array(
                                'type' => 'error',
                                'message' => l('you-already-have-apending-withdraw')
                            ));
                        }
                        $this->model('store::store')->addWithdraw($amount);
                        return json_encode(array(
                            'type' => 'url',
                            'message' => l('withdraw-request-sent'),
                            'value' => url('store/dashboard', array('type' => 'withdrawals'))
                        ));
                    } else {
                        return json_encode(array(
                            'type' => 'error',
                            'message' => l('your-account-balance-low')
                        ));
                    }
                }
                $content = $this->view('store::dashboard/withdrawals');
                break;
            default:
                $page = $this->request->input('w', 'track');
                $content = $this->view('store::dashboard/sales', array('page' => $page));
                break;
        }

        return $this->render($this->view('store::dashboard/index', array('content' => $content, 'type' => $type)), true);
    }

    public function purchased() {
        $this->setTitle(l('purchased'));
        $this->addBreadCrumb(l('purchased'));
        $this->activeMenu = 'store-purchased';
        return $this->render($this->view('store::purchased/index'), true);
    }

    public function store() {
        $this->setTitle(l('store'));
        $this->activeMenu = 'store-browse';
        $this->addBreadCrumb(l('store'));

        $type = $this->request->segment(1, 'store');

        switch($type) {
            case 'top-songs':
                $content = $this->view('store::store/top-songs');
                break;
            case 'top-albums':
                $content = $this->view('store::store/top-albums');
                break;
            case 'browse':

                $which = $this->request->input('which', 'tracks');
                $genre = $this->request->input('genre', 'all');
                $content = $this->view('store::store/browse', array('which' => $which, 'genre' => $genre));
                break;
            default:
                $content = $this->view('store::store/overview');
                break;
        }

        return $this->render($this->view('store::store/index', array('type' => $type, 'content' => $content)), true);
    }
}