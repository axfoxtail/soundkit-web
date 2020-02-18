<?php
class Request {
    private   $routes = array();

    private   $filters = array();

    private  $currentRoute;

    public  static $instance;

    private $config = array();

    public $requestUrl;
    private $segments = array();

    public $controller = null;

    public $pagesStr = "admin,signp,login,reset,tracks,playlist,profile,account,settings,discover,collection,store,explore,search,terms,privacy,contact,upload,pro,notifications,notification,messages,chat,message,statistics,charts,spotlight";

    public $loadedModules = array();
    public function __construct()
    {

    }

    public static  function instance() {
        if (self::$instance) return self::$instance;
        return self::$instance = new Request();
    }

    public function start() {

        $config = include(path()."config.php");
        $this->config = array_merge($this->config, $config);
        $this->config['base_url'] = getRoot();
        $this->config['cookie_path'] = getBase();

        $this->requestUrl = getFullUrl();

        $request = $this;
        //load classes

        include_once (path('app/vendor/classes/database.php'));
        include_once (path('app/vendor/classes/email.php'));
        include_once (path('app/vendor/classes/translator.php'));
        include_once (path('app/vendor/classes/view.php'));
        include_once (path('app/vendor/classes/controller.php'));
        include_once (path('app/vendor/classes/model.php'));
        include_once (path('app/vendor/omnipay/autoload.php'));
        include_once (path('app/vendor/classes/validator.php'));
        include_once (path('app/vendor/classes/uploader.php'));
        include_once (path('app/vendor/classes/hook.php'));

        $this->loadSettings();

        //Database::getInstance()->query("UPDATE settings SET settings_value=? WHERE settings_key=?", 'main', 'theme');
        //load activated plugins
        $query = Database::getInstance()->query("SELECT id FROM plugins");
        while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
            include path('module/'.$fetch['id'].'/start.php');
            $this->loadedModules[] = $fetch['id'];
            View::instance()->addNamespace($fetch['id'], path("module/".$fetch['id'].'/views/'));
            Translator::instance()->addNamespace($fetch['id'], path("module/".$fetch['id'].'/languages/'));
        }



        /**
         * Extend validation
         */
        Validator::getInstance()->extendValidation("predefined", l("validation-predefined-words"), function($value, $field) {
            $predefined = $this->pagesStr;
            $predefinedArray = explode(',', $predefined);

            if(in_array(strtolower($value), $predefinedArray)) return false;

            return true;
        });

        Validator::getInstance()->extendValidation('username', l('validation-username'), function($value, $field = null) {
            $badWords = config('ban-username-filter', 'fuck,sex,admin');
            if($badWords) {
                $badWords = explode(',', $badWords);
                if(in_array($value, $badWords)) return false;
            }
            if(is_numeric($value)) return false;
            if(!preg_match('/^[\pL\pN_\-\.]+$/u', $value)) return false;
            return true;
        });

        include_once(path("app/routes.php"));

        //refresh conversion rates
        $this->refreshConversionRate();

        $this->run();
    }

    public function moduleIsLoaded($module) {
        return in_array($module, $this->loadedModules);
    }

    public function refreshConversionRate() {
        if (!config('enable-multi-currency', false)) return false;
        $force = false;
        if (!file_exists(path('uploads/conversion/conversions.txt'))) {
            if (!is_dir(path('uploads/conversion'))) {
                @mkdir(path('uploads/conversion/'), 0777, true);
                $file = @fopen(path('uploads/conversion/conversions.txt'), 'x+');
                fclose($file);
            }
            $force = true;
        }

        $file = path('uploads/conversion/conversions.txt');
        $filentime = filemtime($file);
        $time = time();

        if (($time - $filentime) > 86400 or $force) {
            //https://currencylayer.com/
            $apiContent = json_decode(curl_get_content("http://www.apilayer.net/api/live?access_key=". config('currency-converter-api-key','').'&format=1&source='.config('currency-converter-source', 'USD')), true);
            try {
                $conversions = $apiContent['quotes'];

                $result = array();
                foreach($conversions as $key => $value) {
                    $result[str_replace(config('currency-converter-source', 'USD'),'', $key)] = $value;
                }
                file_put_contents(path('uploads/conversion/conversions.txt'), json_encode($result));
            } catch (Exception $e) {}
        }
    }

    public function loadSettings() {
        $query = Database::getInstance()->query("SELECT settings_key,settings_value FROM settings ");
        while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
            $this->setConfig($fetch['settings_key'], $fetch['settings_value']);
        }
        if ($this->config('wave-generator', 'browser') == 'server') {
           if (config('player-progress-type','bar') == 'wave-revert') $this->setConfig('player-progress-type', 'wave');
        }

        $sessionTheme = session_get('theme');
        if ($sessionTheme) {
            $this->setConfig('theme', $sessionTheme);
        }

        if ($theme = $this->input('overidetheme')) {
            session_put('theme', $theme);
            $this->setConfig('theme', $theme);
        }

    }

    public function url($url = '', $param = array(), $direct = false) {
        if (preg_match('#http|https#', $url)) {
            return $url;
        }

        $queryString = "";
        foreach($param as $key => $value) {
            $queryString .= ($queryString) ? '&'.$key.'='.$value : $key.'='.$value;
        }

        $mainUrl = ($this->config('permalink') or $direct or !$url) ? $url : '?p='.$url;
        if (!$url and !$this->config('permalink')) {
            if ($queryString) $mainUrl .= '?'.$queryString;
        } else {
            if ($queryString) $mainUrl .= ($this->config('permalink')) ? '?'.$queryString : '&'.$queryString;

        }

        return $this->getBase().$this->cleanUrl($mainUrl);
    }

    function redirect($url)
    {
        @session_write_close();
        @session_regenerate_id(true);
        header("Location:" . $url);
        exit;
    }
    function redirect_back()
    {
        $back = (isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : '';
        if (empty($back) and !preg_match("#" . config("base_url") . "#", $back)) $this->redirect(url());
        $this->redirect($back);
    }

    function redirectBack() {
        return $this->redirect_back();
    }

    public  function getBase() {
        return $this->config['base_url'];
    }

    public function getUri() {
        if ($this->config('permalink')) {
            $uri = str_replace(strtolower($this->getBase()), "", strtolower($this->requestUrl));
        } else {
            $uri = $this->input('p');
        }
        if (!empty($uri)) $this->segments = explode('/', $uri);
        return $uri;
    }

    public  function segment($index, $default = false) {
        $this->getUri();
        if (isset($this->segments[$index])) return $this->segments[$index];
        return $default;
    }
    public function config($key, $default = '') {
        if (!isset($this->config[$key])) return $default;
        return $this->config[$key];
    }

    public  function setConfig($key, $value) {
        $this->config[$key] = $value;
        return true;
    }

    function inputFile($name) {
        if(isset($_FILES[$name])) {
            if(is_array($_FILES[$name]['name'])) {
                $files = array();
                $index = 0;
                foreach($_FILES[$name]['name'] as $n) {
                    if($_FILES[$name]['name'] != 0) {
                        $files[] = array(
                            'name' => $n,
                            'type' => $_FILES[$name]['type'][$index],
                            'tmp_name' => $_FILES[$name]['tmp_name'][$index],
                            'error' => $_FILES[$name]['error'][$index],
                            'size' => $_FILES[$name]['size'][$index]
                        );
                    }
                    $index++;
                }

                if(empty($files)) return false;
                return $files;
            } else {
                if(isset($_FILES[$name]['size']) && $_FILES[$name]['size'] == 0) return false;
                return $_FILES[$name];
            }
        }
        return false;
    }

    function input($name, $default = "", $escape = true)
    {
        //if (!isset($_POST[$name]) and !isset($_GET[$name])) return $default;
        //for all admin lets escape be off
        //if (segment(0) == 'admincp') $escape = false;
        if ($name == "val" and get_request_method() != "POST") return false;
        $index = "";
        if (preg_match("#\.#", $name)) list($name, $index) = explode(".", $name);

        $result = (isset($_GET[$name])) ? $_GET[$name] : $default;
        $result = (isset($_POST[$name])) ? $_POST[$name] : $result;

        if (is_array($result)) {
            if (empty($index)) {
                $nR = array();
                foreach ($result as $k => $v) {
                    if (is_array($v)) {
                        $newResult = array();
                        foreach ($v as $n => $a) {
                            $newResult[$n] = ((!is_array($a) and $escape === true) || (is_array($escape) && !in_array($k, $escape))) ? str_replace("\\\"", "\"", str_replace("\\n", "\n", str_replace("\\r", "\r", sanitizeText($a)))) : str_replace(PHP_EOL, '', str_replace("'", '&#39;', $v));
                        }
                        $nR[$k] = $newResult;
                    } else {
                        $nR[$k] = ($escape === true || (is_array($escape) && !in_array($k, $escape))) ? str_replace("\\\"", "\"", str_replace("\\n", "\n", str_replace("\\r", "\r", sanitizeText($v)))) : str_replace(PHP_EOL, '', str_replace("'", '&#39;', $v));
                    }
                }
                $result = $nR;
            } else {
                if (!isset($result[$index])) return $default;
                if (is_array($result[$index])) {
                    $newResult = array();
                    foreach ($result[$index] as $n => $v) {
                        $newResult[$n] = ((!is_array($v) and $escape === true) || (is_array($escape) && !in_array($index, $escape))) ? str_replace("\\\"", "\"", str_replace("\\n", "\n", str_replace("\\r", "\r", sanitizeText($v)))) : str_replace(PHP_EOL, '', str_replace("'", '&#39;', $v));
                    }
                    $result = $newResult;
                } else {
                    $result = ((!is_array($result[$index]) and $escape === true) || (is_array($escape) && !in_array($index, $escape))) ? str_replace("\\\"", "\"", str_replace("\\n", "\n", str_replace("\\r", "\r", sanitizeText($result[$index])))) : str_replace(PHP_EOL, '', str_replace("'", '&#39;', $result[$index]));
                }

            }
        } else {
            $result = ((!is_array($result) and $escape === true) || (is_array($escape) && !in_array($name, $escape))) ? str_replace("\\\"", "\"", str_replace("\\n", "\n", str_replace("\\r", "\r", sanitizeText($result)))) : str_replace("'", '&#39;', $result);
        }

        return $result;
    }

    public  function cleanUrl($url) {
        $url = str_replace("//", "/", $url);
        return $url;
    }
    /**
     * Method to add routes of any type
     * @param $slug
     * @param $param
     * @return Route
     */
    public  function add($slug, $param) {
        $expectedParam = array(
            'uses' => '',
            'filter' => '',
            'as' => $slug,
            'method' => 'ANY',
            'secure' => true
        );

        /**
         * @var $uses
         * @var $filter
         * @var $as
         * @var $method
         * @var $secure
         */
        extract(array_merge($expectedParam, $param));

        $route = new Route($as, $slug, $uses,$method, $filter, $secure);

        $this->routes[$as] = $route;
        return $route;
    }

    /**
     * Method to add routes of any type
     * @param $slug
     * @param $param
     * @return Route
     */
    public  function any($slug, $param) {
        return $this->add($slug, array_merge($param, array('method' => 'ANY')));
    }

    /**
     * Method to add routes of get type
     * @param $slug
     * @param $param
     * @return Route
     */
    public  function get($slug, $param) {
        return $this->add($slug, array_merge($param, array('method' => 'GET')));
    }

    /**
     * Method to add routes of post type
     * @param $slug
     * @param $param
     * @return Route
     */
    public  function post($slug, $param) {
        return $this->add($slug, array_merge($param, array('method' => 'POST')));
    }

    /**
     * Method to find route
     * @param $id
     * @return bool
     */
    public   function findRoute($id) {
        if (isset($this->routes[$id])) return $this->routes[$id];
        return false;
    }

    public function run() {
        $uri = $this->getUri();
        $uri = str_replace('%2F', '/', $uri);
        if (substr($uri, -1) == '/') $uri = rtrim($uri, '/');
        if (!$uri) $uri = "/";

        $content = "";
        $resultFound = false;

        foreach($this->routes as $id => $route) {
            $slug = $route->slug;
            $slug = $route->getPattern();
            if (!$content and preg_match("!^".$slug."$!", $uri)) {
                $requestMethod = get_request_method();
                $thisMethod = strtoupper($route->method);


                if (($thisMethod == "ANY" or $requestMethod == $thisMethod) and $this->passFilter($route)) {
                    $content = $this->load($route);

                    if ($content) {
                        //$content = "";
                        $resultFound = true;
                        echo ($content == 'found') ? '' : $content;
                    }

                }

            }
        }
        if (!$resultFound) {
            exit(View::instance()->find('main::404'));

        }
    }

    /**
     * Method to add route filter
     * @param $name
     * @param $function
     */
    public  function addFilter($name, $function) {
        $this->filters[$name] = $function;
    }

    public  function passFilter($route) {
        $filter = $route->filter;
        $filters = explode("|", $filter);
        $passed = true;
        foreach($filters as $filter) {
            if (isset($this->filters[$filter])) {
                $callableFunction = $this->filters[$filter];
                if (is_callable($callableFunction)) {
                    if (!call_user_func_array($callableFunction, array())) {
                        $passed = false;
                    }
                }
            }
        }

        return $passed;
    }

    public  function load($route, $active = true, $filter = false, $permission = false) {

        if ($filter and !$this->passFilter($route)) return false;

        if($active) $this->currentRoute = $route;
        $uses = $route->uses;
        $moduleController  =  $uses;
        list($controller, $method) = explode("@", $moduleController);

        $modulesPath = path("app/controllers/");
        if (preg_match('#::#', $controller)) {
            list($theModule,$controller) = explode("::", $controller);
            $modulesPath = path('module/'.$theModule.'/controllers/');
        }
        if (file_exists($modulesPath.$controller.".php") ) {
            require_once $modulesPath.$controller.".php";
        }

        $controllerClass = ucwords($controller).'Controller';
        if (!class_exists($controllerClass)) return false;

        $controllerClass = new $controllerClass($this);
        $this->controller = $controllerClass;
        if ($controllerClass->request == null) exit('Failed : Controller must call parent::__construct($request)');
        if ($route->secure) $controllerClass->securePage();
        $controllerClass->before(); //before method to call before calling the actual route method
        if (method_exists($controllerClass, "get".ucfirst($method))) {
            $method = "get".ucfirst($method);
        } elseif (method_exists($controllerClass, "post".ucfirst($method))) {
            $method = "post".ucfirst($method);
        }
        $result = call_user_func_array(array($controllerClass, $method), $route->param);
        $controllerClass->after();

        return ($result) ? $result : 'found';

    }


    public  function getCurrentRoute() {
        return $this->currentRoute;
    }
}

class Route {
    public $id;
    public $uses;
    public $filter;
    public $slug;
    public $method = "ANY";
    public $where = array();
    public $columns = '';
    public $param = array();
    public $secure = true;

    public function __construct($id, $slug, $uses, $method = "", $filter = "", $secure = true) {
        $this->id = $id;
        $this->slug = $slug;
        $this->uses = $uses;
        $this->filter = $filter;
        $this->method = $method;
        $this->secure = $secure;
    }

    public function where($whereClause) {
        $this->where = $whereClause;
        return $this;
    }
    public function getPattern()
    {
        if ($this->slug != "/" and substr($this->slug, -1) == '/') $this->slug = rtrim($this->slug, '/');
        if (empty($this->where)) return $this->slug;
        $slug = $this->slug;
        foreach($this->where as $replace => $p) {
            $slug = str_replace("{".$replace."}", $p, $slug);
        }
        return $slug;
    }

    public function addFilter($filter) {
        $this->filter = $filter;
        return $this;
    }

    public function addParam($param = array()) {
        $this->param = $param;
        return $this;
    }
}