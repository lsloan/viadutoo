# Viadutoo

## About

Viadutoo (from the Portuguese word for viaduct, "viaduto") is a kind of caching proxy endpoint for Caliper event data.
As a proxy, its purpose is to accept Caliper event payloads from sensors as quickly as possible, then resend them to a
preconfigured upstream endpoint.  If the upstream endpoint is unable to accept the event payload for some reason, the
caching feature will store the event data in a database for future processing.

