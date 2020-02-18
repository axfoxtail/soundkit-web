<?php
class Model {
    public $db;
    public $C;
    public function __construct($controller)
    {
        $this->db = Database::getInstance();
        $this->C = $controller;
    }
}