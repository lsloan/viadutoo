<?php

interface TransportInterface {
    /**
     * @param string $endpointUrl
     * @return $this
     */
    public function setEndpointUrl($endpointUrl);

    /**
     * The number of seconds to wait for the data to be sent.
     *
     * Fractions of a second, down to the millisecond, may be specified.  Setting this to
     * null would use the default timeout value specified by the transport.
     *
     * @param float|null $timeoutSeconds
     * @return $this
     */
    public function setTimeoutSeconds($timeoutSeconds);

    /**
     * @param string[] $headers
     * @param string $body
     * @return bool Success
     */
    public function send($headers, $body);

    /**
     * Return whatever type of information the transport gives as a result of posting the data
     *
     * @return mixed
     */
    public function getLastNativeResultFromSend();

    /** @return bool */
    public function getLastSuccessFromSend();
}