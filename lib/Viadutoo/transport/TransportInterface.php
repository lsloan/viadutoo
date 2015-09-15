<?php

interface TransportInterface {
    /**
     * @param Proxy $proxy
     * @return bool
     */
    public function send($proxy);
}