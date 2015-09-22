<?php
require_once 'Viadutoo/transport/BaseTransport.php';

class CurlTransport extends BaseTransport {
    /** @var array */
    protected $_lastNativeResultFromSend;

    public function __construct() {
        if (!extension_loaded('curl')) {
            throw new RuntimeException('The "curl" extension for PHP is required.');
        }
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

        $client = curl_init($this->getEndpointUrl());

        curl_setopt_array($client, [
            CURLOPT_POST => true,
            CURLOPT_NOSIGNAL => true, // required for timeouts to work properly
            CURLOPT_HTTPHEADER => $headerStrings,
            CURLOPT_HEADER => true, // required to return response text
            CURLOPT_RETURNTRANSFER => true, // required to return response text
            CURLOPT_POSTFIELDS => $body,
        ]);

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
        $this->_lastSuccessFromSend = ($responseText !== false);

        return $this->_lastSuccessFromSend;
    }
}