<?php

abstract class BaseStorage implements StorageInterface {
    /** @var mixed */
    protected $_lastNativeResultFromStore;
    /** @var bool */
    protected $_lastSuccessFromStore;

    /** @return mixed */
    public function getLastNativeResultFromStore() {
        return $this->_lastNativeResultFromStore;
    }

    /** @return bool */
    public function getLastSuccessFromStore() {
        return $this->_lastSuccessFromStore;
    }
}