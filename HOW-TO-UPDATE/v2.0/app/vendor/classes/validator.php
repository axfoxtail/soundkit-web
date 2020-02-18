<?php
class Validator {

    static $instance;
    public $inputs = array();
    public $rules = array();
    public $errorBag = array();
    public $extendRulesFunctions = array();
    public $messages = array();

    public function __construct() {
        $this->messages = array(
            'required' => l('validation-required'),
            'min' => l('validation-min'),
            'max' => l('validation-max'),
            'between' => l('validation-between'),
            'alphanum' => l('validation-alphanum'),
            'integer' => l('validation-integer'),
            'alpha' => l('validation-alpha'),
            'numeric' => l('validation-numeric'),
            'url' => l('validation-url'),
            'email' => l('validation-email'),
            'unique' => l('validation-unique'),
            'date' => l('validation-date'),
            'datetime' => l('validation-datetime')
        );
    }

    public static function getInstance() {
        if(!isset(static::$instance)) static::$instance = new Validator();
        return static::$instance;
    }

    public function scan(array $inputs, array $rules) {
        $this->inputs = array_merge($this->inputs, $inputs);
        $this->rules = array_merge($this->rules, $rules);
        $obj = $this;

//        if(count($this->inputs) != count($this->rules)) die('Validator Error: Numbers of parameters / rules supplied does not match');

        array_walk($this->inputs, function($item, $key) use ($rules, $inputs, $obj) {

            /**field to validate**/
            $field = $key;

            /**value supplied for field**/
            $value = $item;

            if(!array_key_exists($key, $rules)) return true;

            /**rules to validate field**/
            $rules = $rules[$key];

            $ruleSets = explode("|", $rules);

            $filteredRules = $obj->validRuleset($ruleSets, $value);

            /**
             * Iterate through all validation rules
             * available for a specific field, then
             * call with cal_user_func_array
             */
            array_walk($filteredRules, function($rules, $key) use ($obj, $field) {
                $method = array_shift($rules);
                $param = $rules;
                $param = $param['param'];

                /*add field to validator method*/
                if(is_array($param)) $param['field'] = $field;
                if(!is_array($param)) $param = array($param, $field);

                if(isset($obj->extendRulesFunctions[$method])) {
                    $result = call_user_func_array($obj->extendRulesFunctions[$method], array_values($param));
                    if(!$result) {
                        $obj->errorBag[] = array('field' => $field, 'error' => $obj->getError($method, array($field)));
                    }
                } else {
                    call_user_func_array(array($obj, strtolower($method)), array_values($param));
                }

            });

        });

        return $this;

    }

    /**
     * Takes array of ruleset to return as action with param
     * @param array $ruleSets
     * @param string $value
     * @return array
     */
    public function validRuleset($ruleSets, $value) {
        $validation = array();

        foreach($ruleSets as $rule) {
            if(preg_match('#:#', $rule)) {
                $validation[] = $this->getExtendedRule($rule, $value);
                continue;
            }
            $validation[] = array('action' => $rule, 'param' => $value);
        }

        return $validation;
    }

    /**
     * Take an extended rule
     * then breakdown to method and values
     * @param string $rule
     * @param $value
     * @return array
     */
    public function getExtendedRule($rule, $value) {
        /**
         * explode rule to return method as
         * first index, arguments as other
         * indexes, add value to validate as extra param
         */
        $param = explode(':', $rule);
        $rule = array_shift($param);
        $argue = $param;
        $argue[] = $value;

        return array('action' => $rule, 'param' => $argue);
    }

    /**
     * Function to return error for a specific rule
     * @param string type
     * @param array $arguments
     * @internal param \arguments $array
     * @return array
     */
    public function getError($type, array $arguments) {
        $message = $this->error_messages();
        $message = $message[$type];

        preg_match_all("#:[a-z]+#", $message, $matches);
        $args = array();
        foreach($arguments as $a) {
            $args[] = l($a);
        }

        return str_replace(array_shift($matches), $args, $message);
    }

    /**
     * Validation error messages
     */
    public function error_messages() {
        return $this->messages;
    }

    /**
     * Check if validation passes
     * @return bool
     */
    public function passes() {
        if(empty($this->errorBag)) return TRUE;
        return FALSE;
    }

    /**
     * Check if validation fails
     * @return bool
     */
    public function fails() {
        if(!empty($this->errorBag)) return TRUE;
        return FALSE;
    }

    /**
     * Return errorBag
     * @return array
     */
    public function errors() {
        return $this->errorBag;
    }

    /**
     * Validation rule :required
     * @param string value
     * return array
     */
    public function required($value, $field) {
        $value = strip_tags($value);
        $field = strip_tags($field);
        $contain_image = preg_match('/<img/', $value);

        if(strlen($value) == 0 && !$contain_image) {
            $this->errorBag[] = array('field' => $field, 'error' => $this->getError('required', array($field)));
        }
    }

    /**
     * Validation rule :min
     * @param int min
     * @param string value
     * return array
     */
    public function min($min, $value, $field) {
        $value = strip_tags($value);
        $field = strip_tags($field);
        if(strlen($value) < $min) {
            $this->errorBag[] = array('field' => $field, 'error' => $this->getError('min', array($field, $min)));
        }
    }

    /**
     * Validation rule :max
     * @param int max
     * @param string value
     * return array
     */
    public function max($max, $value, $field) {
        $value = strip_tags($value);
        $field = strip_tags($field);

        if(strlen($value) > $max) {
            $this->errorBag[] = array('field' => $field, 'error' => $this->getError('max', array($field, $max)));
        }
    }

    /**
     * Validation rule :between
     * @param int min
     * @param int max
     * @param string value
     * @param string field
     * return array
     */
    public function between($min, $max, $value, $field) {
        $value = strip_tags($value);
        $field = strip_tags($field);
        if(strlen($value) < $min || strlen($value) > $max) {
            $this->errorBag[] = array('field' => $field, 'error' => $this->getError('max', array($field, $min, $max)));
        }
    }

    /**
     * Validation rule :numeric
     * @param string value
     * @param string field
     * return array
     */
    public function numeric($value, $field) {
        $value = strip_tags($value);
        $field = strip_tags($field);

        $valid = is_numeric($value);
        if(!$valid) {
            $this->errorBag[] = array('field' => $field, 'error' => $this->getError('numeric', array($field)));
        }
    }

    /**
     * Validation rule :alpha
     * @param string value
     * @param string field
     * return array
     */
    public function alpha($value, $field) {
        $value = strip_tags($value);
        $field = strip_tags($field);

        $valid = preg_match('/^\pL+$/u', $value);
        if(!$valid) {
            $this->errorBag[] = array('field' => $field, 'error' => $this->getError('alpha', array($field)));
        }
    }

    /**
     * Validation rule :alphanum
     * @param string value
     * @param string field
     * return array
     */
    public function alphanum($value, $field) {
        $value = strip_tags($value);
        $field = strip_tags($field);

        $valid = preg_match('/^[\pL\pN]+$/u', $value);
        $slug = toAscii($value);

        if(!$valid or empty($slug) or strlen($value) != strlen($slug)) {
            $this->errorBag[] = array('field' => $field, 'error' => $this->getError('alphanum', array($field)));
        }
    }

    /**
     * Validation rule :slug
     * @param string $value
     * @param string $field
     * @return array
     */
    public function alphadash($value, $field) {
        $value = strip_tags($value);
        $field = strip_tags($field);

        $valid = preg_match('/^[\pL\pN_-]+$/u', $value);
        $slug = toAscii($value);

        if (!empty($value)) {
            if(!$valid or empty($slug) or strlen($value) != strlen($slug)) {
                $this->errorBag[] = array('field' => $field, 'error' => $this->getError('alphanum', array($field)));
            }
        }
    }

    /**
     * Validation rule :email
     * @param int min
     * @param int max
     * @param string value
     * @param string field
     * return array
     */
    public function email($value, $field) {
        $value = strip_tags($value);
        $field = strip_tags($field);

        if(filter_var($value, FILTER_VALIDATE_EMAIL) == false) {
            $this->errorBag[] = array('field' => $field, 'error' => $this->getError('email', array($field)));
        }
    }

    /**
     * Validation rule :url
     * @param string value
     * @param string field
     * return array
     */
    public function url($value, $field) {
        $value = strip_tags($value);
        $field = strip_tags($field);

        if(!filter_var($value, FILTER_VALIDATE_URL)) ;
        {
            $this->errorBag[] = array('field' => $field, 'error' => $this->getError('url', array($field)));
        }
    }

    /*
     * Validate against a given date
     */

    /**
     * Validation rule :integer
     * @param string value
     * @param string field
     * return array
     */
    public function integer($value, $field) {
        $value = strip_tags($value);
        $field = strip_tags($field);

        if(!is_int($value)) ;
        {
            $this->errorBag[] = array('field' => $field, 'error' => $this->getError('integer', array($field)));
        }
    }

    /**
     * Check if a field is unique against
     * a column from the db
     */
    public function unique($table, $field, $value) {
        $value = strip_tags($value);
        $field = strip_tags($field);
        $table = strip_tags($table);

        $query = Database::getInstance()->query('SELECT '.$value.' FROM '.$table.' WHERE '.$value.' = ? LIMIT 1', $field);

        /**return error if match found*/
        if($query->rowCount() > 0) {
            $this->errorBag[] = array('field' => $value, 'error' => $this->getError('unique', array($value)));
        }
    }

    function date($value, $field) {
        $date = date_parse($value);
        if(!checkdate($date['month'], $date['day'], $date['year']) || is_null(strtotime($value))) {
            $this->errorBag[] = array('field' => $field, 'error' => $this->getError('date', array($field)));
        }
    }

    function datetime($value, $field) {
        $datetime = explode(' ', $value);
        if(count($datetime) == 2) {
            $date = $datetime[0];
            $parsed_date = date_parse($date);
            if(checkdate($parsed_date['month'], $parsed_date['day'], $parsed_date['year']) && !is_null(strtotime($date))) {
                if(date('Y-m-d H:i:s', strtotime($value)) == $value) {
                    return;
                }
            }
        }
        $this->errorBag[] = array('field' => $field, 'error' => $this->getError('datetime', array($field)));
    }

    /**
     * Get the first error message
     * or a first error message of param provided
     */
    public function first($param = null) {
        if(empty($this->errorBag)) return "";

        if(empty($param)) {
            $array = array_shift($this->errorBag);
            if(isset($array['error'])) $first = $array['error'];
        }
        foreach($this->errorBag as $errors) {
            if($errors['field'] == $param) {
                $first = $errors['error'];
                break;
            }
        }

        if(!empty($first)) {
            return $first;
        } else {
            return "";
        }
    }


    /**
     * Function add extended Rules functions right from anywhere
     * @param string $rule
     * @param string $message
     * @param mixed $callable
     * @return mixed
     */
    public function extendValidation($rule, $message, $callable) {
        $this->extendRulesFunctions[$rule] = $callable;
        $this->messages[$rule] = $message;
    }
}