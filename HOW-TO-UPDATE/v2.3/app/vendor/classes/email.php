<?php
class Email {
    protected $driver = 'mail';
    protected $fromName = '';
    protected $fromAddress = '';
    protected $smtp_host = '';
    protected $smtp_username = '';
    protected $smtp_password = '';
    protected $smtp_port = '';
    protected $ssl = '';
    protected $queue = false;
    protected $charset = 'utf-8';
    protected static $instance;

    protected $localTemplates = array();
    protected $dbTemplates = array();
    public $mailer = null;

    //queue
    private $queueFromName = '';
    private $queueFromMail = '';
    private $queueSubject = '';
    private $queueMessage = '';
    private $queueAddress = array();

    public static function getInstance()
    {
        static::$instance = new Email();
        return static::$instance;
    }

    public function __construct() {
        $this->init();
    }

    public function init() {
        $this->driver = config('email-driver', 'mail');
        $this->smtp_host = config('smtp-host');
        $this->smtp_username = config('smtp-username');
        $this->smtp_password = config('smtp-password');
        $this->smtp_port = config('smtp-port');
        $this->charset = config('email-charset', 'utf-8');
        $this->fromAddress = config('from_address', '');
        $this->fromName = config('site-title', 'MusicEngine');
        include_once path('app/vendor/phpmailer/PHPMailerAutoload.php');
        try {
            $this->mailer = new \PHPMailer(true);
            if ($this->driver == 'smtp') {
                $this->mailer->isSMTP();
                $this->mailer->Host = $this->smtp_host;
                $this->mailer->Port = $this->smtp_port;
                $this->mailer->CharSet = $this->charset;
                $this->mailer->Encoding = "base64";
                $this->mailer->SMTPAutoTLS = false;
                //$this->mailer->SMTPSecure = 'ssl';
                if (!empty($this->smtp_username) and !empty($this->smtp_password)) {
                    $this->mailer->Username = $this->smtp_username;
                    $this->mailer->Password = $this->smtp_password;
                    $this->mailer->SMTPAuth = true;
                }
            }
            $this->mailer->setFrom($this->fromAddress, $this->fromName);
        } catch(\Exception $e) {}


    }



    public function setFrom($fromEmail, $fromName) {
        $this->queueFromName = $fromName;
        $this->queueFromMail = $fromEmail;
        try{
            $this->mailer->setFrom($fromEmail, $fromName);
        } catch(\Exception $e){}
    }

    public function addCC($address, $name = null) {
        try{
            $this->mailer->addCC($address, $name);
        } catch(\Exception $e){}
        return $this;
    }
    public function setAddress($address, $name = null)
    {
        $this->queueAddress[$name] = $address;
        try{
            $this->mailer->addAddress($address, $name);
        } catch(\Exception $e){}
        return $this;
    }

    public function setSubject($subject)
    {
        $this->mailer->Subject = $subject;
        $this->queueSubject = $subject;
        return $this;
    }

    public function setMessage($message)
    {
        $message = str_replace(array("\\n","\\r"), array('', ''), $message);
        $message = html_entity_decode($message, ENT_QUOTES);
        $this->queueMessage = $message;
        $this->mailer->msgHTML($message);
        return $this;
    }

    public function addAttachment($path)
    {
        $this->mailer->addAttachment($path);
        return $this;
    }

    public function send()
    {
        try {
            $this->mailer->send();
            return true;
        } catch(Exception $e) {
            return false;
        }
    }

}