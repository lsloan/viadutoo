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
            error_log('Unable to connect to DB: ' . $this->_databaseHandle->connect_error);
            throw new Exception('Unable to connect to DB: ' . $this->_databaseHandle->connect_error);
        }

        // fixme: Causes trouble if DB user doesn't have table create permission
        /*
        $success = $this->_databaseHandle->query(<<<"EOT"
            CREATE TABLE IF NOT EXISTS $tableName (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                message_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                headers text NOT NULL,
                body text NOT NULL
            )
EOT
        );

        if (!$success) {
            error_log('Unable to create table ' . $tableName . ': ' . $this->_databaseHandle->error);
            throw new Exception('Unable to create table ' . $tableName . ': ' . $this->_databaseHandle->error);
        }
        */
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
        $encodedHeaders = strval(json_encode($headers));
        $statement = $this->_databaseHandle
            ->prepare("INSERT INTO $tableName (id, headers, body) VALUES (null, ?, ?)");

        if (!$statement) {
            error_log('Unable to prepare statement: ' . $this->_databaseHandle->error);
            throw new Exception('Unable to prepare statement: ' . $this->_databaseHandle->error);
        }

        $bindSuccess = $statement->bind_param('ss', $encodedHeaders, $body);

        if (!$bindSuccess) {
            error_log('Unable to bind statement params: ' . $this->_databaseHandle->error);
            throw new Exception('Unable to bind statement params: ' . $this->_databaseHandle->error);
        }

        $success = $statement->execute();

        $this->_lastNativeResultFromStore = null; // No response available for mysqli
        $this->_lastSuccessFromStore = $success;

        return $success;
    }
}
