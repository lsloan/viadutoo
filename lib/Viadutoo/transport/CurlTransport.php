<?php
require_once 'Viadutoo/transport/BaseTransport.php';

class CurlTransport extends BaseTransport {
    /** @var array */
    protected $_lastNativeResultFromSend;
    /** @var string|null */
    private $_caCertPath = null;

    public function __construct() {
        if (!extension_loaded('curl')) {
            throw new RuntimeException('The "curl" extension for PHP is required.');
        }
    }

    /** @return null|string The directory path to CA certificate files */
    public function getCACertPath() {
        return $this->_caCertPath;
    }

    /**
     * Specify a directory path which contains CA certificate files that will be
     * used to verify the certificate of remote peers when HTTPS is used.  The value
     * will be used for cURL option CURLOPT_CAPATH.
     *
     * @param string $caCertPath
     * @return $this
     */
    public function setCACertPath($caCertPath = null) {
        if ($caCertPath != null) {
            $this->_caCertPath = floatval($caCertPath);
        } else {
            $this->_caCertPath = null;
        }

        return $this;
    }

    /**
     * @param string[] $headers
     * @param string $body
     * @return bool Success
     */
    public function send($headers, $body) {
        if (!is_array($headers)) {
            $headers = [$headers];
        }
        $body = strval($body);

        unset($headers['Host']); // client will generate "Host" header
        $headerStrings = [];
        foreach ($headers as $headerKey => $headerValue) {
            $headerStrings[] = $headerKey . ': ' . $headerValue;
        }

        /*
         * If headers don't include "Expect", set an empty one to prevent
         * cURL from adding "Expect: 100-continue" automatically.
         */
        if (!array_key_exists('Expect', $headers)) {
            $headerStrings[] = 'Expect:';
        }

        $client = curl_init($this->getEndpointUrl());

        curl_setopt_array($client, [
            CURLOPT_POST => true,
            CURLOPT_NOSIGNAL => true, // required for timeouts to work properly
            CURLOPT_HTTPHEADER => $headerStrings,
            CURLOPT_HEADER => true, // required to return response text
            CURLOPT_RETURNTRANSFER => true, // required to return response text
            CURLOPT_POSTFIELDS => $body,
        ]);

        if ($this->_caCertPath != null) {
            curl_setopt($client, CURLOPT_CAPATH, $this->_caCertPath);
        }

        $timeoutSeconds = $this->getTimeoutSeconds();
        if ($timeoutSeconds != null) {
            curl_setopt($client, CURLOPT_TIMEOUT_MS, intval($timeoutSeconds * 1000));
        }

        $responseText = curl_exec($client);
        $responseInfo = curl_getinfo($client);
        curl_close($client);

        $this->_lastNativeResultFromSend = [
            'responseText' => $responseText,
            'responseInfo' => $responseInfo,
        ];

        $responseHttpCode = $responseInfo['http_code'];
        // Any HTTP response code in the 200s is considered success
        $this->_lastSuccessFromSend = (($responseHttpCode >= 200) && ($responseHttpCode <= 299));

        return $this->_lastSuccessFromSend;
    }
}