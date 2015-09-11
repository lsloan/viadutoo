<?php


class SQLite3Storage implements StorageInterface {
    /** @var SQLite3 */
    private $_databaseHandle;

    /**
     * @param string $databaseFilename
     */
    public function __construct($databaseFilename) {
        $this->_databaseHandle = new SQLite3($databaseFilename);

        $this->_databaseHandle->exec(<<<'EOT'
            CREATE TABLE IF NOT EXISTS events (
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
        $statement = $this->_databaseHandle
            ->prepare('INSERT INTO events (id, headers, body) VALUES (null, :headers, :body)');
        $statement->bindValue(':headers', json_encode($headers), SQLITE3_TEXT);
        $statement->bindValue(':body', $body, SQLITE3_TEXT);
        $result = $statement->execute();
        error_log(print_r($result->fetchArray(), true));

        return $result;
    }
}