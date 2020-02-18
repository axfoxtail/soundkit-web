<?php
class Controller {
    public $request;
    public $db;

    private  $pageTitle;
    private  $titleSeparator = "|";

    private $metaTags = array();

    private $headerContent = "";
    private $footerBeforeContent = "";
    private $footerAfterContent = "";

    public $pageType = 'frontend';
    private $mainLayout = "main::includes/layout";
    private $wrapLayout = 'main::includes/wrapper';
    public $wrapRight = 'main::includes/wrap-right';
    public $sideMenu = 'main::includes/side-menu';

    public $theme = "main";
    public $showHeader = true;

    public $activeMenu = "discover";

    public $keywords = "";
    public $description = "";
    public $favicon = '';

    public $loginRequired = true;
    public $adminRequired = false;
    private $models = array();

    public $breadcrumbs = array();
    public $useBreadcrumbs = true;

    public $collapsed = false;
    public $useEditor = false;
    public function __construct($request)
    {
        $this->request = $request;
        $this->db = Database::getInstance();
        $this->theme = config('theme', 'main');
        $this->setTitle();
        $this->keywords = config('site-keywords', '');
        $this->description = config('site-description', '');
        $this->favicon = (config('favicon', 'favicon.png')) ? assetUrl(config('favicon', 'favicon.png')) : '';
        $this->model('user')->processLogin();
        $this->addBreadCrumb(l('home'), url());

        $headerContent = '<meta property="og:image" content="'.assetUrl(config('site_logo', 'assets/images/logo.png')).'"/>';
        $headerContent .= '<meta property="og:title" content="'.config('site-title').'"/>';
        $headerContent .= '<meta property="og:url" content="'.url().'"/>';
        $headerContent .= '<meta property="og:description" content="'.config('site-description', '').'"/>';
        $this->addHeaderContent($headerContent);
    }

    public function isDemo() {
        return config('demo');
    }
    public function defendDemo() {
        if (config('demo')) {
            if ($this->model('user')->authId != 1) {
                if (is_ajax()) {
                    exit(json_encode(array(
                        'type' => 'error',
                        'message' => 'Action is disabled on demo'
                    )));
                } else {
                    $this->request->redirect_back();
                }
            }
        }
    }

    public function addBreadCrumb($title, $url = '') {
        $this->breadcrumbs[$title] = $url;
    }

    public function setLayout($layout) {
        $this->mainLayout = $layout;
    }


    public function render($content, $wrap = false) {

        if ($wrap) {
            $content = $this->view($this->wrapLayout, array('content' => $content));
        }

        $sideMenu = $this->view($this->sideMenu);
        if (is_ajax()) {
            //if (!$this->model('user')->isLoggedIn()) exit('login');
            return json_encode(array(
                'title' => $this->pageTitle,
                'content' => $content,
                'container' => '#page-container',
                'active_menu' => $this->activeMenu,
                'menu' => $sideMenu,
                'collapsed' => $this->collapsed
            ));
        }

        //if ($this->request->segment(0) == 'admin') $this->mainLayout = "main::admin/includes/layout";
        $content = $this->view($this->mainLayout, array('content' => $content, 'sideMenu' => $sideMenu));
        $output = "";

        $output .= $this->view("main::includes/header", array(
            'title' => $this->pageTitle,
            'header_content' => $this->headerContent,
            'active_menu' => $this->activeMenu,
            'keywords' => $this->keywords,
            'description' => $this->description,
            'favicon' => $this->favicon,
            'collapsed' => $this->collapsed
        ));

        $output .= $content;

        $output .= $this->view( "main::includes/footer", array('beforeContent' => $this->footerBeforeContent, 'afterContent' => $this->footerAfterContent));
        //exit($output.'sddss');
        return $output;
    }

    public function setTitle($title = "", $removeBase = false) {
        $titleStr = config('site-title', '');
        if ($removeBase) {
            $this->pageTitle = $title;
        } else {
            if ($title) $titleStr .= " ".$this->titleSeparator." {$title}";
            $this->pageTitle = $titleStr;
        }
        return $this;
    }

    public function addHeaderContent($content = "") {
        $this->headerContent = $content;
        return $this;
    }

    public function addFooterBeforeContent($content = "") {
        $this->footerBeforeContent .= $content;
        return $this;
    }

    public function addFooterAfterContent($content = "") {
        $this->footerAfterContent .= $content;
        return $this;
    }

    public function view($view, $param = array()) {
        $param = array_merge(array(
            'C' => $this,
            'request' => $this->request
        ), $param);
        return View::instance()->find($view, $param);
    }

    public function model($model) {
        if (isset($this->models[$model])) return $this->models[$model];
        $modelObj = $this->loadModel($model);
        if ($model) {
            $this->models[$model] = $modelObj;
            return $this->models[$model];
        } else {
            exit($model.' class does not exists');
        }

    }

    public function loadModel($model) {
        $base = path('app/model/');
        if (preg_match("@::@", $model)) {
            list($module, $model) = explode("::", $model);
            $base = path('module/'.$module.'/model/');
        }
        $modelFile = $base.$model.'.php';
        include_once $modelFile;
        $model = ucwords($model).'Model';
        if (class_exists($model)) {
            return  new $model($this);
        }
        return false;
    }

    public function after(){}

    public function before() {
    }

    public function securePage() {
        if (!$this->model('user')->isLoggedIn()) {
            if (is_ajax()) {
                exit('login');
            } else {
                $this->request->redirect(url());
            }
        }
        if ($this->adminRequired) {
            if (!$this->model('user')->isAdmin()) $this->request->redirect(url());
        }
    }

    public function adminSecure($role) {
        if ($this->model('user')->hasRole($role)) return true;
        $this->request->redirect(url('admin'));
    }

    public function uploadFile($tmpMove, $delete = true) {

        if(config('enable-ftp', false)) {
            $ftp = $this->model('track')->getFtp();
            $pathInfo = pathinfo($tmpMove);
            $path = $pathInfo['dirname'];
            if (!$ftp->isDir($path)) {
                $mkdir = $ftp->mkdir($path);
            }
            $ftp->chdir($path);
            $ftp->pasv(true);
            if ($ftp->putFromPath(path($tmpMove))) {
                if ($delete) delete_file(path($tmpMove));
                $ftp->close();
            }
            $ftp->close();
            return 'ftp-'.$tmpMove;
        } elseif(config('enable-wasabi', false)) {
            $s3 = $this->model('track')->getWasabiS3();
            $pathInfo = pathinfo($tmpMove);
            $extension = strtolower($pathInfo['extension']);
            $key = 'w3-'.md5($tmpMove).'.'.$extension;
            try{
                $s3->putObject(array(
                    'Bucket' => config('wasabi-bucket'),
                    'Key'	 => 'uploads/tracks/'.$key,
                    'Body'	 => fopen(path($tmpMove), 'rb')
                ));

                //delete the file now
                if ($delete) delete_file(path($tmpMove));
                return $key;
            } catch (\Aws\S3\Exception\S3Exception $e) {
                exit($e->getMessage());
                //will fallback to the server maybe the s3 is not available
                return $tmpMove;
            }
        }
        else if (config('enable-s3', false)) {
            $s3 = $this->model('track')->getS3();
            $pathInfo = pathinfo($tmpMove);
            $extension = strtolower($pathInfo['extension']);
            $key = 's3-'.md5($tmpMove).'.'.$extension;
            try{
                $s3->putObject(array(
                    'Bucket' => config('s3-bucket'),
                    'Key'	 => 'uploads/tracks/'.$key,
                    'Body'	 => fopen(path($tmpMove), 'rb')
                ));

                //delete the file now
                if ($delete) delete_file(path($tmpMove));
                return $key;
            } catch (\Aws\S3\Exception\S3Exception $e) {
                exit($e->getMessage());
                //will fallback to the server maybe the s3 is not available
                return $tmpMove;
            }
        } else {
            return  $tmpMove;
        }

    }

    public function getFileUri($path, $http = true) {
        $file = $path;
        if (preg_match('#s3-#',$file)) {
            $s3 = $this->model('track')->getS3();
            $cmd = $s3->getCommand('GetObject', array(
                'Bucket' => config('s3-bucket'),
                'Key'    => 'uploads/tracks/'.$file
            ));

            $req = $s3->createPresignedRequest($cmd, '1 week');
            $url = (string) $req->getUri();
            return $url;

        }

        if (preg_match('#w3-#',$file)) {
            $s3 = $this->model('track')->getWasabiS3();
            $cmd = $s3->getCommand('GetObject', array(
                'Bucket' => config('wasabi-bucket'),
                'Key'    => 'uploads/tracks/'.$file
            ));

            $req = $s3->createPresignedRequest($cmd, '1 week');
            $url = (string) $req->getUri();
            return $url;

        }

        if (preg_match('#ftp-#', $file)) {
            $endpoint = config('ftp-endpoint');
            $file = str_replace('ftp-','', $file);
            $middle = (config('ftp-path', '/') != '/') ? config('ftp-path') : '';
            return $endpoint.$middle.'/'.$file;
        }
        return ($http) ? assetUrl($file) : $file;
    }

    public function deleteFile($file) {
        if (preg_match('#s3-#', $file)) {
            try {
                $s3 = $this->model('track')->getS3();
                $s3->deleteObjects(array(
                    'Bucket' => config('s3-bucket'),
                    'Delete' => array(
                        'Objects' => array(
                            array('Key' => 'uploads/tracks/'.$file)
                        ))
                ));
                return true;
            } catch(Exception $e) {
                // echo $e;
                return true;
            }
        }

        elseif (preg_match('#w3-#', $file)) {
            try {
                $s3 = $this->model('track')->getWasabiS3();
                $s3->deleteObjects(array(
                    'Bucket' => config('wasabi-bucket'),
                    'Delete' => array(
                        'Objects' => array(
                            array('Key' => 'uploads/tracks/'.$file)
                        ))
                ));
                return true;
            } catch(Exception $e) {
                // echo $e;
                return true;
            }
        }

        elseif (preg_match('#ftp-#', $file)) {
            $ftp = $this->model('track')->getFtp();
            $file = str_replace('ftp-','', $file);
            $pathInfo = pathinfo($file);
            $path = $pathInfo['dirname'];
            if (!$ftp->isDir($path)) {
                $mkdir = $ftp->mkdir($path);
            }
            $ftp->chdir($path);
            $ftp->pasv(true);
            if ($ftp->remove($file)) {
                return true;
            }
            return true;
        } else{
            delete_file(path($file));
        }
    }

    public function errorPage() {
        $this->setLayout("includes/plain-layout");
        return $this->render($this->view('error/404'));
    }
}