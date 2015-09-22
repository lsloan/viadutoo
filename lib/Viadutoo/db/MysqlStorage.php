<?php
require_once 'Viadutoo/db/BaseStorage.php';

class MysqlStorage extends BaseStorage {
    /** @var mysqli_result|bool mysqli_result on success, FALSE on failure */
    protected $_lastNativeResultFromStore;
    /** @var mysqli */
    private $_databaseHandle;
    /** @var string */
    private $_tableName;

    function __construct($host, $userName, $password, $dbName, $tableName = 'events') {
        $tableName = strval($tableName);

        $this->_tableName = $tableName;
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
     * @return bool Success
     */
    function store($headers, $body) {
        if (!is_array($headers)) {
            $headers = [$headers];
        }
        $body = strval($body);

        $tableName = $this->_tableName;
        $encodedHeaders = json_encode($headers);
        $statement = $this->_databaseHandle
            ->prepare("INSERT INTO $tableName (id, headers, body) VALUES (null, ?, ?)");
        $statement->bind_param('ss', $encodedHeaders, $body);
        $success = $statement->execute();

        $this->_lastNativeResultFromStore = $statement->get_result(); // TODO: Causes dupes?
        $this->_lastSuccessFromStore = $success;

        return $success;
    }
}
