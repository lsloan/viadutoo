<?php

interface StorageInterface {
    /**
     * @param string[] $headers
     * @param string $body
     * @return bool Success
     */
    public function store($headers, $body);

    /**
     * Return whatever type of information the storage gives as a result of storing the data
     *
     * @return mixed
     */
    public function getLastNativeResultFromStore();

    /** @return bool */
    public function getLastSuccessFromStore();
}