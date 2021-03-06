<?php
require_once 'Viadutoo/transport/BaseTransport.php';

class CurlTransport extends BaseTransport {
    const
        // Authorization types
        AUTHZ_TYPE_BASICAUTH = 'BASICAUTH',
        AUTHZ_TYPE_OAUTH1 = 'OAUTH1',
        // Important HTTP header names
        HTTP_HEADER_AUTHORIZATION = 'Authorization',
        HTTP_HEADER_EXPECT = 'Expect',
        HTTP_HEADER_HOST = 'Host',
        // HTTP method used for sending
        HTTP_METHOD_POST = 'POST';

    /** @var array */
    protected $_lastNativeResultFromSend;
    /** @var string|null */
    private $_caCertPath = null;
    /** @var string|null */
    private $_authZType = null;
    /** @var string|null */
    private $_authZUserOrKey = null;
    /** @var string|null */
    private $_authZPasswordOrSecret = null;

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
            $this->_caCertPath = strval($caCertPath);
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

        $client = curl_init($this->getEndpointUrl());

        curl_setopt_array($client, [
            CURLOPT_POST => true,
            CURLOPT_NOSIGNAL => true, // required for timeouts to work properly
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

        if ($this->_authZType === self::AUTHZ_TYPE_BASICAUTH) {
            unset($headers[self::HTTP_HEADER_AUTHORIZATION]); // Let cURL create a new "Authorization" header
            curl_setopt($client, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($client, CURLOPT_USERPWD, $this->_authZUserOrKey . ':' . $this->_authZPasswordOrSecret);
        } elseif ($this->_authZType === self::AUTHZ_TYPE_OAUTH1) {
            $headers[self::HTTP_HEADER_AUTHORIZATION] = $this->makeOAuthHeaderValue(); // Replace any existing "Authorization" header
        }

        unset($headers[self::HTTP_HEADER_HOST]); // cURL will generate "Host" header

        /*
         * If headers don't include "Expect", set an empty one to prevent
         * cURL from adding "Expect: 100-continue" automatically.
         */
        @$headers[self::HTTP_HEADER_EXPECT] .= null;

        $headerStrings = [];
        foreach ($headers as $headerKey => $headerValue) {
            $headerStrings[] = $headerKey . ': ' . $headerValue;
        }

        curl_setopt($client, CURLOPT_HTTPHEADER, $headerStrings);

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

    /**
     * @return string
     * @throws \Eher\OAuth\OAuthException
     */
    protected function makeOAuthHeaderValue() {
        $consumer = new Eher\OAuth\Consumer($this->_authZUserOrKey, $this->_authZPasswordOrSecret);
        $token = null;
        $httpMethod = self::HTTP_METHOD_POST;
        $parameters = null;

        $request = Eher\OAuth\Request::from_consumer_and_token(
            $consumer, $token, $httpMethod, $this->getEndpointUrl(), $parameters
        );
        $request->sign_request((new Eher\OAuth\HmacSha1()), $consumer, $token);

        return substr($request->to_header(), strlen(self::HTTP_HEADER_AUTHORIZATION . ': '));
    }

    /**
     * @param null|string $authZType Use <u>null</u>, <u>self::AUTHZ_TYPE_BASICAUTH</u>, or
     *      <u>self::AUTHZ_TYPE_OAUTH1</u>
     * @param null|string $authZUserOrKey Required only when <u>$authType !== null</u>: username when
     *      <u>$authType === self::AUTHZ_TYPE_BASICAUTH</u> or key when <u>$authType === self::AUTHZ_TYPE_OAUTH1</u>
     * @param null|string $authZPasswordOrSecret Required only when <u>$authType !== null</u>: password when
     *      <u>$authType === self::AUTHZ_TYPE_BASICAUTH</u> or secret when <u>$authType === self::AUTHZ_TYPE_OAUTH1</u>
     * @return $this
     * @throws InvalidArgumentException For unknown authorization type or null arguments
     */

    public function setAuthZType($authZType, $authZUserOrKey = null, $authZPasswordOrSecret = null) {
        if (is_null($authZType)) {
            $this->_authZType = null;
            $this->_authZUserOrKey = null;
            $this->_authZPasswordOrSecret = null;
        } elseif (($authZType === self::AUTHZ_TYPE_BASICAUTH) || ($authZType === self::AUTHZ_TYPE_OAUTH1)) {
            if (is_null($authZUserOrKey) || is_null($authZPasswordOrSecret)) {
                throw new InvalidArgumentException(__METHOD__ .
                    ': $authZUserOrKey and $authZPasswordOrSecret must not be null.');
            } else {
                $this->_authZType = $authZType;
                $this->_authZUserOrKey = $authZUserOrKey;
                $this->_authZPasswordOrSecret = $authZPasswordOrSecret;
            }
        } else {
            throw new InvalidArgumentException(__METHOD__ . ': unknown authorization type, "' . $authZType . '".');
        }

        return $this;
    }
}