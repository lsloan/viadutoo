<?php
/*
 * This application is an example of using Viadutoo's Proxy class along
 * with its TransportInterface and StorageInterface implementations to
 * accept JSON payloads, attempt to send them via the specified transport,
 * and store them via the specified storage interface if the send fails.
 */

require_once '../lib/Viadutoo/Proxy.php';
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
 * @param bool $validMethod
 */
function respondAndCloseConnection($validMethod) {
    ob_end_clean(); // Discard any previous output
    ob_start(); // Start output buffer so it can be flushed on demand

    if ($validMethod !== true) {
        http_response_code(405);
        echo '405 Method not allowed';
    } else {
        http_response_code(100);
        // Don't send message; it may change response code
    }

    header('Content-Length: ' . ob_get_length());
    header('Connection: close');  // Tell remote client to close connection

    ob_end_flush(); // End output buffer and flush it to client (part 1)
    flush(); // Part 2 of complete flush

    // Close any session to prevent blocking
    if (session_id()) session_write_close();
}

$validMethod = (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST');
respondAndCloseConnection($validMethod);

if ($validMethod === true) {
    $headers = getallheaders();
    $body = file_get_contents('php://input');

    foreach ($headers as $name => $value) {
        error_log("${name}: ${value}");
    }
    error_log("Raw post data:\n${body}");
    error_log('Received ' . strlen($body) . ' bytes');

    $proxy = (new Proxy())
        ->setTransportInterface(new PeclHttpTransport())
//      ->setTransportInterface(new CurlTransport())
        ->setEndpointUrl('http://lti.tools/caliper/event?key=viadutoo')
        ->setTimeoutSeconds(15)
        ->setAutostoreOnSendFailure(false)
        ->setStorageInterface(new SQLite3Storage('mysqlitedb.db'));
//      ->setStorageInterface(new MysqlStorage('127.0.0.1', 'root', 'root', 'media'));

    $success = null;
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
}