<?php
/**
 * Created by PhpStorm.
 * User: kay
 * Date: 02-03-17
 * Time: 16:37
 */

namespace PDFen\Session;


use PDFen\Exceptions\IllegalStateException;

abstract class SessionObject
{
    protected $_apiClient;
    protected $_resource;
    protected $_session;
    protected $_language;

    private $_data;
    private $_changedData;
    private $_isDeleted;

    /**
     * SessionObject constructor.
     * @param \PDFen\Session $session
     * @param null $resource leave null if the file is not yet created.
     */
    public function __construct($apiClient, \PDFen\Session $session, $language, $resource = null){
        $this->_apiClient = $apiClient;
        $this->_session = $session;
        $this->_resource = $resource;
        $this->_data = null;
        $this->_changedData = [];
        $this->_isDeleted = false;
        $this->_language = $language;
    }

    private function _ensureData() {
        if($this->_data === null && $this->_resource !== null) {
            $this->refresh();
        }
    }

    protected function _getField($name) {
        $this->_ensureData();
        $this->_integrityChecks();
        if(is_string($name)){
            return $this->_getField([$name]);
        }
        $this->_ensureData();
        $data = $this->_changedData;
        $returnData = true;
        foreach($name as $field){
            if(isset($data[$field])) {
                $data = $data[$field];
            } else {
                $returnData = false;
                break;
            }
        }
        if($returnData){
            return $data;
        }
        if($this->_data === null){
            return null;
        }
        $data = $this->_data;
        $returnData = true;
        foreach($name as $field){
            if(isset($data[$field])) {
                $data = $data[$field];
            } else {
                $returnData = false;
                break;
            }
        }
        return $returnData ? $data : null;
    }

    protected function _setField($name, $value) {
        $this->_integrityChecks();
        if(is_string($name)){
            return $this->_setField([$name], $value);
        }
        $data = &$this->_changedData;
        $last_field = array_pop($name);
        foreach($name as $field) {
            if(isset($data[$field])) {
                $data = &$data[$field];
            } else {
                $data[$field] = [];
                $data = &$data[$field];
            }
        }
        $data[$last_field] = $value;
    }

    protected function _create($target) {
        $response = $this->_apiClient->POST($target, $this->_changedData, ['accept-language' => $this->_language]);
        if ($response->isError()) {
            throw $response->asException();
        }
        $this->_data = $response->body;
        $this->_changedData = null;
    }

    protected function _delete() {
        if($this->_resource === null){
            throw new IllegalStateException("This resource does not exist yet in the API, maybe it was not yet created?");
        }
        $response = $this->_apiClient->DELETE($this->_resource, ['accept-language' => $this->_language]);
        if ($response->isError()) {
            throw $response->asException();
        }
        $this->_resource = null;
    }

    protected function _isDeleted() {
        return $this->_isDeleted;
    }


    protected function _integrityChecks() {
        if($this->_isDeleted) {
            throw new IllegalStateException("This object has been deleted.");
        }
        if($this->_session->isDeleted()){
            throw new IllegalStateException("The session has been deleted.");
        }
    }

    public function refresh() {
        $this->_integrityChecks();
        if($this->_resource === null){
            throw new IllegalStateException("This resource does not exist yet in the API, maybe it was not yet created?");
        }
        $response = $this->_apiClient->GET($this->_resource, ['accept-language' => $this->_language]);
        if ($response->isError()) {
            throw $response->asException();
        }
        $this->_data = $response->body;
    }

    protected function _sendChangedData() {
        $this->_integrityChecks();
        if($this->_resource === null){
            throw new IllegalStateException("This resource does not exist yet in the API, maybe it was not yet created?");
        }
        if(count($this->_changedData) > 0) {
            $changedData = $this->_changedData;
            $apiClient = $this->_apiClient;
            $response = $apiClient->PATCH($this->_resource, $changedData, ['accept-language' => $this->_language]);
            if ($response->isError()) {
                throw $response->asException();
            }
            $this->_changedData = [];
        }
    }
    public function update() {
        $this->_sendChangedData();
        $this->refresh();
    }

    public function _pushUpdate($data) {
        if($this->_data !== null) {
            $this->_data = array_merge($this->_data, $data);
        } else {
            $this->_data = $data;
        }
    }

    public function discardChanges () {
        $this->_changedData = [];
    }

}