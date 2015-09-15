<?php

interface StorageInterface {
    /**
     * @param string[] $headers
     * @param string $body
     * @return mixed
     */
    function store($headers, $body);
}