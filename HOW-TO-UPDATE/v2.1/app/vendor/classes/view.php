<?php
class View {
    private   $namespaces = array();
    private   $defaultTheme;
    public static $instance;
    public   function __construct() {
        $this->defaultTheme = config('theme', 'main');

        $this->addNamespace("main", path("app/views/"));
        $this->addNamespace("style", path("styles/".$this->defaultTheme.'/views/'));
    }

    public static  function instance() {
        if (self::$instance) return self::$instance;
        return self::$instance = new View();
    }

    public  function addNamespace($name, $path) {
        $this->namespaces[$name] = $path;
    }

    public   function find($view, $param = array()) {
        $view = Hook::getInstance()->fire('get.view', $view);
        $viewPath = $this->getViewPath($view);
        if (!$viewPath) return false;
        ob_start();

        /**
         * make the parameters available to the views
         */
        //$app->config = array();
        extract($param);

        if (file_exists($viewPath)) //trigger_error(Error::viewNotFound($viewPath));
            include $viewPath;
        $content = ob_get_clean();

        return $content;
    }


    public  function getViewPath($view) {
        if (!preg_match("@::@", $view)) $view = "main::".$view;
        list($namespace, $path) = explode("::", $view);
        if (!$this->namespaceExists($namespace)) return false;

        $namespacePath = $this->namespaces[$namespace];
        $viewPath = $namespacePath.$path.'.phtml';

        $styleOverridePath = $this->namespaces['style'].$this->stripOffBasePath($path).'.phtml';
        if (file_exists($styleOverridePath)) return $styleOverridePath;
        return $viewPath;
    }

    public  function namespaceExists($namespace) {
        return (isset($this->namespaces[$namespace]));
    }

    public  function stripOffBasePath($path) {
        return str_replace(path(), "", $path);
    }
}
