# Viadutoo

## About

Viadutoo (from the Portuguese word for viaduct, "viaduto") is a kind of caching proxy endpoint for Caliper event data.
As a proxy, its purpose is to accept Caliper event payloads from sensors as quickly as possible, then resend them to a
preconfigured upstream endpoint.  If the upstream endpoint is unable to accept the event payload for some reason, the
caching feature will store the event data in a database for future processing.

## Requirements

* PHP 5.4 or greater
* One of the following PHP HTTP client extensions:
    * pecl_http
    * curl

## Example

An example application is included in the `public` directory.  Execute it as:

    cd public
    php -S 127.0.0.1:8989

The application is written to send all data to `http://lti.tools/caliper/event?key=viadutoo`.  Simply configure a
Caliper sensor to send events to `http://127.0.0.1:8989/` and they will be sent on to the `lti.tools` endpoint.