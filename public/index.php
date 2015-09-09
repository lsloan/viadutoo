<?php
require_once '../lib/Viadutoo/Proxy.php';

$headers = getallheaders();
foreach ($headers as $name => $value) {
    error_log("${name}: ${value}");
}

$body = file_get_contents('php://input');
error_log("Raw post data:\n${body}");
error_log('Received ' . strlen($body) . ' bytes');

$proxy = (new Proxy())
    ->setEndpointUrl('http://lti.tools/caliper/event?key=viadutoo')
    ->setTimeoutSeconds(15);

$proxy->setHeaders($headers)
    ->setBody($body)
    ->send();