<?php
class Database {
    private static $instance;

    private $host;
    private $dbName;
    private $username;
    private $password;

    private $db;
    private $driver;

    private $dbPrefix;

    public function __construct() {
        $expected = array(
            'db_host' => '',
            'db_name' => '',
            'db_username' => '',
            'db_password' => '',
            'driver' => 'mysql',
            'port' => '',
        );
        $databaseDetails = include(path()."config.php");
        $databaseDetails = array_merge($expected, $databaseDetails);

        $this->host = $databaseDetails['db_host'];
        $this->dbName = $databaseDetails['db_name'];
        $this->username = $databaseDetails['db_username'];
        $this->password = $databaseDetails['db_password'];
        $this->driver = $databaseDetails['driver'];

        /**
         * Try make connection to the database
         */
        try {
            $this->db = new \PDO("{$this->driver}:host={$this->host};dbname={$this->dbName}", $this->username, $this->password);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            if (config('dbcharacter_encode', false)) {
                $this->db->query("SET NAMES 'utf8'");
                $this->db->query('SET CHARACTER SET utf8');
                $this->db->query('SET collation_connection = utf8_unicode_ci	');
            }

            //$this->db->query("SET GLOBAL sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));");

        } catch (\Exception $e) {
            exit($e->getMessage());
        }
    }

    public static function getInstance() {
        if (static::$instance) return static::$instance;
        static::$instance = new Database();
        return static::$instance;
    }

    public function query($query) {
        $args = func_get_args();
        array_shift($args);

        if (isset($args[0]) and is_array($args[0])) {
            $args = $args[0];
        }

        $response = $this->db->prepare($query);
        $response->execute($args);

        return $response;
    }

    public function lastInsertId(){
        return $this->db->lastInsertId();
    }

    public function prepare($query) {

        $args = func_get_args();
        $response = $this->db->prepare($query);
        return $response;
    }

    public function paginate($query, $param = array(), $limit = 10, $links = 7, $page = null) {
        include_once (path('app/vendor/classes/paginator.php'));
        return new Paginator($query, $param, $limit, $links, $page);
    }
}