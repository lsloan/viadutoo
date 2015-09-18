<?php


class SQLite3Storage implements StorageInterface {
    /** @var SQLite3 */
    private $_databaseHandle;
    /** @var string */
    private $_tableName;

    /**
     * @param string $databaseFilename
     */
    public function __construct($databaseFilename, $tableName = 'events') {
        $this->_tableName = strval($tableName);
        $this->_databaseHandle = new SQLite3($databaseFilename);

        if ($this->_databaseHandle->lastErrorCode() != 0) {
            throw new Exception('Unable to connect to DB.');
        }

        $this->_databaseHandle->exec(<<<"EOT"
            CREATE TABLE IF NOT EXISTS $tableName (
                id INTEGER PRIMARY KEY,
                headers STRING,
                body STRING
            )
EOT
        );
    }

    /**
     * @param string[] $headers
     * @param string $body
     * @return SQLite3Result
     */
    public function store($headers, $body) {
        $tableName = $this->_tableName;
        $statement = $this->_databaseHandle
            ->prepare("INSERT INTO $tableName (id, headers, body) VALUES (null, :headers, :body)");
        $statement->bindValue(':headers', json_encode($headers), SQLITE3_TEXT);
        $statement->bindValue(':body', $body, SQLITE3_TEXT);
        $result = $statement->execute();

        return $result;
    }
}