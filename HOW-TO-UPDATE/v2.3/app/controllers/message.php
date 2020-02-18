<?php
class MessageController extends Controller {
    public function form() {
        $id = $this->request->input('id');
        $user = $this->model('user')->getUser($id);

        if ($val = $this->request->input('val')) {
            /**
             * @var $text
             * @var $track
             * @var $playlist
             * @var $to
             * @var $modal
             */
            extract(array_merge(array(
                'text' => '',
                'track' => '',
                'playlist' => '',
                'to' => '',
                'modal' => false
            ), $val));

            if (empty($text) and empty($track) and empty($playlist)) {
                return json_encode(array(
                    'type' => 'error',
                    'message' => l('message-empty')
                ));
            } else {
                if (!$to) {
                    return json_encode(array(
                        'type' => 'error',
                        'message' => l('no-friend-selected')
                    ));
                } else {
                    if (!is_array($to)) $to = array($to);
                    foreach ($to as $t) {
                        if ($this->model('user')->hasBlock($t)) {
                            return json_encode(array(
                                'type' => 'error',
                                'message' => l('you-can-send-message-to-user')
                            ));
                        }
                        $messageId = $this->model('message')->send($t, $text, $track, $playlist);
                    }
                    if (!$modal) {
                        return json_encode(array(
                            'type' => 'function',
                            'value' => 'messageSent',
                            'content' => $this->view('message/format', array('message' => $this->model('message')->getMessage($messageId)))
                        ));
                    } else {
                        return json_encode(array(
                            'type' => 'modal-function',
                            'modal' => $modal,
                            'message' => l('message-sent')
                        ));
                    }
                }
            }
        }
        return $this->view('message/form', array('user' => $user));
    }

    public function messages() {
        $this->setTitle(l('messages'));
        $this->useBreadcrumbs = false;
        $cid = $this->request->input('cid', $this->model('message')->getLastCid());

        return $this->render($this->view('message/index', array('cid' => $cid)), true);
    }

    public function search() {
        $term = $this->request->input('term');

        $users = $this->model('user')->searchConnections($term);

        $result = array();
        foreach($users as $user) {
            $result[] = array('value' => $user['id'], 'text' => $user['full_name']);
        }

        return json_encode($result);
    }

    public function paginate() {
        $data = perfectUnserialize($this->request->input('data'));
        $cid = $data['cid'];
        $offset = $this->request->input('offset');
        $limit = 10;
        $theOffset = ($offset == 0) ? 10 : $offset;
        $newOffset = $offset + $limit;

        $messages = $this->model('message')->getMessages($cid, $newOffset, 10);

        $content =  $this->view('message/lists', array('messages' => $messages,));
        $result = array(
            'content' => $content,
            'offset' => $newOffset
        );
        return json_encode($result);
    }
}