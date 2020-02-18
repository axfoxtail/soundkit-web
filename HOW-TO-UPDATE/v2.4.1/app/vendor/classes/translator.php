<?php
class Translator {
    private $lang = "en";
    private $fallbackLang = "en";

    private $namespace = array();


    private $phrases = array();
    private $defaultPhrases = array();
    public static $instance;

    public function lang() {
        return $this->lang;
    }

    public function __construct()
    {
        $this->addNamespace("global", path("languages/"));
        $this->lang = config('default_language', 'en');
        if (isset($_COOKIE['language'])) {
            $this->lang = $_COOKIE['language'];
        }

    }

    public static function instance() {
        if (self::$instance) return self::$instance;
        return self::$instance = new Translator();
    }

    public function addNamespace($name, $path) {
        $this->namespace[$name] = $path;
    }

    public function translate($name, $param = array(), $default = null) {

        if (!preg_match("#::#", $name)) $name = "global::$name";


        list($namespace, $slug) = explode("::", $name);
        $default = ($default) ? $default : $slug;
        if (!isset($this->namespace[$namespace])) return $default;

        $languagePath = $this->namespace[$namespace].$this->lang.'.php';
        $defaultPath = $this->namespace[$namespace].$this->fallbackLang.'.php';
        $this->defaultPhrases = include($defaultPath); //default language
        $phrases = (file_exists($languagePath)) ? include($languagePath) : include($defaultPath);
        $this->phrases[$namespace] = $phrases;

        if (isset($this->phrases[$namespace][$slug])) {
            return $this->result($this->phrases[$namespace][$slug], $param, $default);
        } elseif(isset($this->defaultPhrases[$slug])){
            return $this->result($this->defaultPhrases[$slug], $param, $default);
        }
        return $default;
    }

    public function result($result, $param = array(), $default = null) {
        if (!empty($param)) {
            foreach($param as $replace => $value) {
                $result = str_replace(":".$replace, $value, $result);
            }
        }
        return ($result) ? $result : $default;
    }

    public function getAvailableLanguages() {
        $handle = opendir(path('languages/'));
        $result = array();
        while($read = readdir($handle)) {
            if (substr($read,0, 1) != '.') {
                $result[] = str_replace('.php','', $read);
            }
        }
        return $result;
    }
}
