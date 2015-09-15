<?php

class MysqlStorage implements StorageInterface{
    /** @var mysqli */
    private $_databaseHandle;

    function __construct($host, $userName, $password, $dbName) {
        $this->_databaseHandle = new mysqli($host, $userName, $password, $dbName);

        if ($this->_databaseHandle->connect_error) {
            throw new Exception('Unable to connect to DB.');
        }
    }

    /**
     * @param string[] $headers
     * @param string $body
     * @return mixed
     */
    function store($headers, $body) {
        $encodedHeaders = json_encode($headers);
        $statement = $this->_databaseHandle
            ->prepare('INSERT INTO caliper_events (id, headers, body) VALUES (null, ?, ?)');
        $statement->bind_param('ss', $encodedHeaders, $body);
        $result = $statement->execute();

        return $result;
    }
}
