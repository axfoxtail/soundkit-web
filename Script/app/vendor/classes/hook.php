<?php
class Hook {

    static $instance;
    private $events = array();

    /**
     * @return Hook
     */
    public static function getInstance() {
        if(!static::$instance) static::$instance = new Hook();
        return static::$instance;
    }

    /**
     * @param $event
     * @param null $values
     * @param null $callback
     * @param array $param
     * @return mixed|null
     */
    public function attachOrFire($event, $values = NULL, $callback = NULL, $param = array()) {
        if(!is_array($param)) $param = array($param);
        if($callback !== NULL) {
            if(!isset($this->events[$event])) $this->events[$event] = array();
            $this->events[$event][] = $callback;
        } else {
            $theValue = $values;
            $result = $values;
            if(isset($this->events[$event])) {
                foreach($this->events[$event] as $callbacks) {
                    $newParam = ($values) ? array_merge(array($theValue), $param) : $param;
                    $v = call_user_func_array($callbacks, $newParam);
                    $theValue = ($values) ? $v : $theValue;
                    $result = ($v) ? $v : $result;
                }
            }
            return ($values) ? $theValue : $result;
        }
    }

    /**
     * Function to attach several callback to an event
     * @param $event
     * @param null $callback
     * @return mixed|null
     */
    public function register($event, $callback) {
        $hook = Hook::getInstance();
        $hook->attachOrFire($event, $values = null, $callback);
    }

    /**
     * Function to fire several events  attached to a hook
     * @param $event
     * @param null $values
     * @param array $param
     * @return mixed|null
     * @internal param null $callback
     */
    function fire($event, $values = null, $param = array()) {
        $hook = Hook::getInstance();
        return $hook->attachOrFire($event, $values, $callback = null, $param);
    }
}
