<?php
require_once 'Viadutoo/transport/BaseTransport.php';

class PeclHttpTransport extends BaseTransport {
    /** @var http\Client\Response */
    protected $_lastNativeResultFromSend;

    public function __construct() {
        if (!extension_loaded('http')) {
            throw new RuntimeException('The "http" (AKA "pecl_http") extension for PHP is required.');
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

        $request = (new http\Client\Request(
            'POST',
            $this->getEndpointUrl(),
            $headers,
            (new http\Message())
                ->getBody()
                ->append($body)
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

        $this->_lastNativeResultFromSend = $response;

        $responseHttpCode = $response->getResponseCode();
        // Any HTTP response code in the 200s is considered success
        $this->_lastSuccessFromSend = (($responseHttpCode >= 200) && ($responseHttpCode <= 299));

        return $this->_lastSuccessFromSend;
    }
}