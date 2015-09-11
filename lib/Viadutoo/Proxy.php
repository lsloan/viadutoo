<?php
set_include_path(get_include_path() . PATH_SEPARATOR . realpath(dirname(__FILE__) . '/..'));

require_once 'Viadutoo/db/StorageInterface.php';

class Proxy {
    /** @var bool */
    private $_haveExtensionCurl = false;
    /** @var bool */
    private $_haveExtensionHttp = false;
    /** @var string */
    private $_endpointUrl;
    /** @var string[] */
    private $_headers;
    /** @var string */
    private $_body;
    /** @var float|null */
    private $_timeoutSeconds = null;

    public function __construct() {
        $this->_haveExtensionCurl = extension_loaded('curl');
        $this->_haveExtensionHttp = extension_loaded('http');

        if (!$this->_haveExtensionCurl && !$this->_haveExtensionHttp) {
            throw new RuntimeException('One of these PHP extensions is required: "http" (AKA pecl_http) or "curl".');
        }
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
        $this->_endpointUrl = $endpointUrl;
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
        $this->_body = $body;
        return $this;
    }

    /** @return float|null */
    public function getTimeoutSeconds() {
        return $this->_timeoutSeconds;
    }

    /**
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

    /** @return StorageInterface */
    public function getStorageInterface() {
        return $this->_StorageInterface;
    }

    /**
     * @param StorageInterface $StorageInterface
     * @return $this
     */
    public function setStorageInterface($StorageInterface) {
        $this->_StorageInterface = $StorageInterface;
        return $this;
    }

    /**
     * @return bool
     */
    public function send() {
        $status = false;
        $responseCode = null;
        $responseText = null;

        $headers = $this->getHeaders();

        // Remove "Host" header; it will be generated by client
        if (array_key_exists('Host', $headers)) {
            unset($headers['Host']);
        }

        if ($this->_haveExtensionHttp) {
            $request = (new http\Client\Request(
                'POST',
                $this->getEndpointUrl(),
                $headers,
                (new http\Message())
                    ->getBody()
                    ->append($this->getBody())
            ));

            $timeoutSeconds = $this->getTimeoutSeconds();
            if ($timeoutSeconds != null) {
                $request->setOptions([
                    'timeout' => $timeoutSeconds
                ]);
            }

            $response = (new http\Client)
                ->enqueue($request)
                ->send()
                ->getResponse($request);

            $responseCode = $response->getResponseCode();
            $responseText = $responseCode;
        } elseif (extension_loaded('curl')) {
            $client = curl_init($this->getEndpointUrl());

            $headerStrings = [];
            foreach ($headers as $headerKey => $headerValue) {
                $headerStrings[] = $headerKey . ': ' . $headerValue;
            }

            $curlOptions = [
                CURLOPT_POST => true,
                CURLOPT_NOSIGNAL => true, // required for timeouts to work properly
                CURLOPT_HTTPHEADER => $headerStrings,
                CURLOPT_USERAGENT => 'Caliper (PHP curl extension)',
                CURLOPT_HEADER => true, // required to return response text
                CURLOPT_RETURNTRANSFER => true, // required to return response text
                CURLOPT_POSTFIELDS => $this->getBody(),
            ];

            $timeoutSeconds = $this->getTimeoutSeconds();
            if ($timeoutSeconds != null) {
                $curlOptions[CURLOPT_TIMEOUT_MS] = intval($timeoutSeconds * 1000);
            }

            curl_setopt_array($client, $curlOptions);

            $responseText = curl_exec($client);
            $responseInfo = curl_getinfo($client);
            curl_close($client);

            if ($responseText) {
                $responseCode = $responseInfo['http_code'];
            } else {
                $responseCode = null;
            }
        }

        if ($responseCode != 200) {
            throw new RuntimeException('Failure: HTTP error: ' . $responseText);
        } else {
            $status = true;
        }

        return $status;
    }

    public function store() {
        $this->getStorageInterface()->store($this->getHeaders(), $this->getBody());
    }
}