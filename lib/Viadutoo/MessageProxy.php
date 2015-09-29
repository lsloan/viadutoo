<?php
set_include_path(get_include_path() . PATH_SEPARATOR . realpath(dirname(__FILE__) . '/..'));

require_once 'Viadutoo/db/StorageInterface.php';

class MessageProxy {
    /** @var bool */
    private $_haveExtensionCurl = false;
    /** @var bool */
    private $_haveExtensionHttp = false;
    /** @var TransportInterface */
    private $_transportInterface = null;
    /** @var StorageInterface */
    private $_storageInterface = null;
    /** @var string */
    private $_endpointUrl;
    /** @var string[] */
    private $_headers;
    /** @var string */
    private $_body;
    /** @var float|null */
    private $_timeoutSeconds = null;
    /** @var bool */
    private $_autostoreOnSendFailure = true;

    /**
     * Send the data to the specified endpoint.
     *
     * If isAutostoreOnSendFailure() is true, then this method will automatically
     * call store() if the send fails.
     *
     * @return bool Success
     */
    public function send() {
        $transportInterface = $this->getTransportInterface();
        if ($transportInterface == null) {
            throw new RuntimeException('Transport interface not specified.  Use setTransportInterface() before calling ' . __FUNCTION__ . '.');
        }

        $transportInterface
            ->setEndpointUrl($this->getEndpointUrl())
            ->setTimeoutSeconds($this->getTimeoutSeconds());

        $success = $transportInterface
            ->send($this->getHeaders(), $this->getBody());

        if ($success !== true) {
            if ($this->isAutostoreOnSendFailure()) {
                $this->store();
            } else {
                throw new RuntimeException('Failure: HTTP error: ' . $this->getLastNativeResultFromSend());
            }
        }

        return $success;
    }

    /** @return TransportInterface */
    public function getTransportInterface() {
        return $this->_transportInterface;
    }

    /**
     * @param TransportInterface $transportInterface
     * @return $this
     */
    public function setTransportInterface($transportInterface) {
        if (!($transportInterface instanceof TransportInterface)) {
            throw new InvalidArgumentException(__METHOD__ . ': instance of TransportInterface expected.');
        }

        $this->_transportInterface = $transportInterface;
        return $this;
    }

    /** @return string */
    public function getEndpointUrl() {
        return $this->_endpointUrl;
    }

    /**
     * @param string $endpointUrl
     * @return $this
     */
    public function setEndpointUrl($endpointUrl) {
        $this->_endpointUrl = strval($endpointUrl);
        return $this;
    }

    /** @return float|null */
    public function getTimeoutSeconds() {
        return $this->_timeoutSeconds;
    }

    /**
     * The number of seconds to wait for the data to be sent.
     *
     * Fractions of a second, down to the millisecond, may be specified.  Setting this to
     * null would use the default timeout value specified by the transport.
     *
     * @param float|null $timeoutSeconds
     * @return $this
     */
    public function setTimeoutSeconds($timeoutSeconds) {
        if ($timeoutSeconds != null) {
            $this->_timeoutSeconds = floatval($timeoutSeconds);
        } else {
            $this->_timeoutSeconds = null;
        }
        return $this;
    }

    /** @return string[] */
    public function getHeaders() {
        return $this->_headers;
    }

    /**
     * @param string[] $headers
     * @return $this
     */
    public function setHeaders($headers) {
        if (!is_array($headers)) {
            $headers = [$headers];
        }

        $this->_headers = $headers;
        return $this;
    }

    /** @return string */
    public function getBody() {
        return $this->_body;
    }

    /**
     * @param string $body
     * @return $this
     */
    public function setBody($body) {
        $this->_body = strval($body);
        return $this;
    }

    /** @return bool */
    public function isAutostoreOnSendFailure() {
        return $this->_autostoreOnSendFailure;
    }

    /**
     * @param bool $autostoreOnSendFailure
     * @return $this
     */
    public function setAutostoreOnSendFailure($autostoreOnSendFailure) {
        $this->_autostoreOnSendFailure = filter_var($autostoreOnSendFailure, FILTER_VALIDATE_BOOLEAN);
        return $this;
    }

    /**
     * @return bool Success
     */
    public function store() {
        $storageInterface = $this->getStorageInterface();

        if ($storageInterface == null) {
            throw new RuntimeException('Storage interface not specified.  Use setStorageInterface() before calling ' . __FUNCTION__ . '.');
        }

        return $this->getStorageInterface()->store($this->getHeaders(), $this->getBody());
    }

    /** @return StorageInterface */
    public function getStorageInterface() {
        return $this->_storageInterface;
    }

    /**
     * Specify an object that implements StorageInterface to store data.
     *
     * @param StorageInterface $storageInterface
     * @return $this
     */
    public function setStorageInterface($storageInterface) {
        if (!($storageInterface instanceof StorageInterface)) {
            throw new InvalidArgumentException(__METHOD__ . ': instance of StorageInterface expected.');
        }

        $this->_storageInterface = $storageInterface;
        return $this;
    }
}