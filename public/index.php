<?php
/*
 * This application is an example of using Viadutoo's MessageProxy class along
 * with its TransportInterface and StorageInterface implementations to
 * accept JSON payloads, attempt to send them via the specified transport,
 * and store them via the specified storage interface if the send fails.
 */

require_once '../lib/Viadutoo/MessageProxy.php';
require_once 'Viadutoo/transport/PeclHttpTransport.php';
require_once 'Viadutoo/transport/CurlTransport.php';
require_once 'Viadutoo/db/MysqlStorage.php';
require_once 'Viadutoo/db/SQLite3Storage.php';

/**
 * Send response to remote client and close the connection.
 *
 * This is important since this program needs to accept input and respond as quickly as possible.
 * It may take time for the sending of data to complete and that shouldn't be allowed to get in
 * the way.
 *
 * @return bool Request is valid
 */
function respondAndCloseConnection() {
    ob_end_clean(); // Discard any previous output
    ob_start(); // Start output buffer so it can be flushed on demand

    $validRequestHost = (@$_SERVER['SERVER_NAME'] === @$_SERVER['REMOTE_ADDR']) &&
        (@$_SERVER['SERVER_NAME'] === '127.0.0.1');
    $validRequestMethod = (strtoupper($_SERVER['REQUEST_METHOD']) === 'POST');

    $validRequest = false;

    if ($validRequestHost !== true) {
        http_response_code(403);
        echo '403 Forbidden';
    } elseif ($validRequestMethod !== true) {
        http_response_code(405);
        echo '405 Method not allowed';
    } else {
        http_response_code(200); //OK
        $validRequest = true;
    }

    header('Content-Length: ' . ob_get_length());
    header('Connection: close');  // Tell client to close connection *now*

    ob_end_flush(); // End output buffer and flush it to client (part 1)
    flush(); // Part 2 of complete flush

    if (session_id()) {
        session_write_close(); // Closing session prevents blocking on later requests
    }

    return $validRequest;
}

$validRequest = respondAndCloseConnection($validMethod);

if ($validRequest !== true) {
    exit;
}

$headers = getallheaders();
$body = file_get_contents('php://input');

foreach ($headers as $name => $value) {
    error_log("${name}: ${value}");
}
error_log("Raw post data:\n${body}");
error_log('Received ' . strlen($body) . ' bytes');

$proxy = (new MessageProxy())
    ->setTransportInterface(new PeclHttpTransport())
//      ->setTransportInterface(new CurlTransport())
    ->setEndpointUrl('http://lti.tools/caliper/event?key=viadutoo')
    ->setTimeoutSeconds(15)
    ->setAutostoreOnSendFailure(false)
    ->setStorageInterface(new SQLite3Storage('viadutoo_example.db'));
//      ->setStorageInterface(new MysqlStorage('127.0.0.1', 'root', 'root', 'media'));

$success = false;
try {
    $success = $proxy
        ->setHeaders($headers)
        ->setBody($body)
        ->send();
} catch (Exception $exception) {
    error_log($exception->getMessage());
}

if (($success !== true) && !$proxy->isAutostoreOnSendFailure()) {
    error_log('Send not successful, storing data...');
    $proxy->store();
}