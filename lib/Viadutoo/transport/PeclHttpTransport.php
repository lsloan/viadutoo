<?php
require_once 'Viadutoo/transport/BaseTransport.php';

class PeclHttpTransport extends BaseTransport {
    /**
     * @param string[] $headers
     * @param string $body
     * @return int|null HTTP response code
     */
    public function send($headers, $body) {
        $responseCode = null;
        $responseText = null;

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

        $responseCode = $response->getResponseCode();
        $responseText = $responseCode;

        return $responseCode;
    }
}