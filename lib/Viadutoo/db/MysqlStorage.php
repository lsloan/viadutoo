<?php

class MysqlStorage implements StorageInterface{
    /** @var mysqli */
    private $_databaseHandle;
    /** @var string */
    private $_tableName;

    function __construct($host, $userName, $password, $dbName, $tableName = 'events') {
        $this->_tableName = strval($tableName);
        $this->_databaseHandle = new mysqli($host, $userName, $password, $dbName);

        if ($this->_databaseHandle->connect_error) {
            throw new Exception('Unable to connect to DB.');
        }

        $this->_databaseHandle->query(<<<"EOT"
            CREATE TABLE IF NOT EXISTS $tableName (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                headers text NOT NULL,
                body text NOT NULL
            )
EOT
        );
    }

    /**
     * @param string[] $headers
     * @param string $body
     * @return mixed
     */
    function store($headers, $body) {
        $tableName = $this->_tableName;
        $encodedHeaders = json_encode($headers);
        $statement = $this->_databaseHandle
            ->prepare("INSERT INTO $tableName (id, headers, body) VALUES (null, ?, ?)");
        $statement->bind_param('ss', $encodedHeaders, $body);
        $result = $statement->execute();

        return $result;
    }
}
