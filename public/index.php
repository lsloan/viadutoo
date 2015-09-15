<?php
require_once '../lib/Viadutoo/Proxy.php';
require_once 'Viadutoo/db/MysqlStorage.php';
require_once 'Viadutoo/db/SQLite3Storage.php';

$requestMethod = strtoupper($_SERVER['REQUEST_METHOD']);
if ($requestMethod != 'POST') {
    http_response_code(405); // "Method not allowed"
    exit;
}

$headers = getallheaders();
$body = file_get_contents('php://input');

http_response_code(100);

foreach ($headers as $name => $value) {
    error_log("${name}: ${value}");
}
error_log("Raw post data:\n${body}");
error_log('Received ' . strlen($body) . ' bytes');

$proxy = (new Proxy())
    ->setEndpointUrl('http://lti.tools/caliper/event?key=viadutoo')
    ->setTimeoutSeconds(15)
    ->setAutostoreOnSendFailure(false)
//    ->setStorageInterface(new SQLite3Storage('mysqlitedb.db'));
    ->setStorageInterface(new MysqlStorage('127.0.0.1', 'root', 'root', 'media'));

$success = false;
try {
    $success = $proxy->setHeaders($headers)
        ->setBody($body)
        ->send();
} catch (Exception $exception) {
}

// fixme: remove this statement, only for debugging to force storage
$success = false;

if (($success !== true) && !$proxy->isAutostoreOnSendFailure()) {
    error_log('Send not successful, storing data...');
    $proxy->store();
}