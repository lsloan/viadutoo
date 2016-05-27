# Viadutoo

## About

Viadutoo (From the Portuguese word for viaduct, "viaduto". Pronounced as "vee-uh-DOO-too" or in IPA: \[vi.a.dˈu.tʊ\].)
is a kind of caching proxy endpoint for Caliper event data. As a proxy, its purpose is to accept Caliper event payloads
from sensors as quickly as possible, then resend them to a preconfigured upstream endpoint.  If the upstream endpoint is
unable to accept the event payload for some reason, the caching feature will store the event data in a database for
future processing.

## Requirements

* PHP 5.4 or greater
* One of the following PHP HTTP client extensions:
    * curl (usually this is already included with PHP interpreter)
    * pecl_http
* Composer (not strictly required, but highly recommended)
    * For installation details, see: https://getcomposer.org/download/

## Usage

* Update the `composer.json` file in the root directory of your project as follows:
    * The `require` section should include the following:

        ```json
        {
            "require": {
                "php": ">=5.4",
                "umich-its-tl/viadutoo": "1.0.1"
            }
        }
        ```

    * If a repository is not specified, Composer will install Viadutoo from
        Packagist (https://packagist.org/packages/umich-its-tl/viadutoo).
        However, to specify a GitHub repository as the source instead, include
        the following in the `repositories` section:

        ```json
        {
            "repositories": [
                {
                    "type": "vcs",
                    "url": "https://github.com/tl-its-umich-edu/viadutoo"
                }
            ]
        }
        ```

        This is one way to specify your own repository, for example, if
        you want to modify your own fork of Viadutoo.  You would specify
        the URL of your own repository.
* Use Composer to install the package with the command:

    ```sh
    composer install
    ```

    Composer will create the `vendor` directory to hold the package and
    other related information.
* Composer will create PHP classes to help you load Viadutoo (and any other
    packages it has loaded) into your application.  In your PHP code, use it like:

    ```php
    /*
     * If necessary, use set_include_path() to ensure the directory
     * containing the "vendor" directory is in the PHP include path.
     */
    require_once 'vendor/autoload.php';  // Composer loader for Viadutoo, etc.
    ```

* Initialize and use Viadutoo in your PHP code like this:

    ```php
    $proxy = (new MessageProxy())
        ->setTransportInterface(
            (new CurlTransport()) // Recommended transport with cURL, supports Basic Auth and OAuth 1.0
                ->setAuthZType(CurlTransport::AUTHZ_TYPE_OAUTH1, 'OAuth 1.0 key', 'OAuth 1.0 secret') // Optional authZ
        )
        ->setEndpointUrl('http://example.com/endpoint')
        ->setTimeoutSeconds(10)
        ->setAutostoreOnSendFailure(true)
        ->setStorageInterface(
            new MysqlStorage(
                'mysql.example.org', 'dbUser', 'dbPassword',
                'dbName', 'viadutoo_events'
            )
        );
    // ...
    try {
        $proxy
            ->setHeaders($headers)
            ->setBody($jsonBody)
            ->send();
    } catch (Exception $exception) {
        error_log($exception->getMessage());
    }
    ```

    For a more detailed example of using Viadutoo in an application, see
    `public/index.php`.

## Example

An example application is included in the `public` directory.  Execute it as:

    cd public
    php -S 127.0.0.1:8989

The application is written to send all data to `http://lti.tools/caliper/event?key=viadutoo`.  Simply configure a
Caliper sensor to send events to `http://127.0.0.1:8989/` and they will be sent on to the `lti.tools` endpoint.