<?php

class MysqlStorage implements StorageInterface{
    public function store() {
        $db = new mysqli();
    }
}