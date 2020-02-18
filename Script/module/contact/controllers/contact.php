<?php
class ContactController extends Controller {
    public function index() {
        $this->setTitle(l('contact-us'));
        $this->addBreadCrumb(l('contact-us'));
        $this->collapsed = true;
        if ($val = $this->request->input('val')) {
            $validator = Validator::getInstance()->scan($val, array(
                'first_name' => 'required',
                'email' => 'required|email',
                'message' => 'required'
            ));

            if ($validator->passes()) {
                $receiverEmail = config('contact-email');
                $mailer = Email::getInstance();
                $name = $val['first_name'].' '.$val['last_name'];
                $mailer->setFrom($val['email'], $name);
                $mailer->setAddress($receiverEmail);
                $mailer->setMessage($val['message']);
                $mailer->send();
                return json_encode(array(
                    'message' => l('contact-message-sent'),
                    'type' => 'url',
                    'value' => url('contact')
                ));
            } else {
                return json_encode(array(
                    'message' => $validator->first(),
                    'type' => 'error'
                ));
            }
        }
        return $this->render($this->view('contact::index'), true);
    }
}