<?php

interface StorageInterface {
    /**
     * @param string[] $headers
     * @param string $body
     * @return mixed
     */
    public function store($headers, $body);
}