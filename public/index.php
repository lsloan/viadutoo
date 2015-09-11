<?php
require_once '../lib/Viadutoo/Proxy.php';
require_once 'Viadutoo/db/SQLite3Storage.php';

$headers = getallheaders();
$body = file_get_contents('php://input');

foreach ($headers as $name => $value) {
    error_log("${name}: ${value}");
}
error_log("Raw post data:\n${body}");
error_log('Received ' . strlen($body) . ' bytes');

$proxy = (new Proxy())
    ->setEndpointUrl('http://lti.tools/caliper/event?key=viadutoo')
    ->setTimeoutSeconds(15)
    ->setStorageInterface(new SQLite3Storage('mysqlitedb.db'));

$success = false;
try {
    $success = $proxy->setHeaders($headers)
        ->setBody($body)
        ->send();
} catch (Exception $exception) {
}

// fixme: remove this statement, only for debugging to force storage
$success = false;

if ($success !== true) {
    error_log('Send not successful, storing data...');
    $proxy->store();
}