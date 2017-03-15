<?php
/**
 * Created by PhpStorm.
 * User: kay
 * Date: 02-03-17
 * Time: 16:37
 */

namespace PDFen\Session;

use PDFen\Exceptions\InvalidArgumentException;

class File extends SessionObject
{
    private $_data = null;
    private $_dataType;

    public function exists() {
        return $this->_isDeleted() || $this->getUUID() !== null;
    }

    //expose the isDeleted function
    public function isDeleted() {
        return $this->_isDeleted();
    }

    public function getUUID() {
        $this->_integrityChecks();
        return $this->_getField('file_id');
    }

    public function getURL(){
        $this->_integrityChecks();
        if($this->_data === null) {
            return null;
        }
        return $this->_getField('url');
    }

    public function setURL($url) {
        $this->_integrityChecks();
        //unset the data that could be uploaded.
        $this->_data = null;
        $this->_dataType = null;
        $this->_setField('url', $url);
    }

    public function isPartial(){
        $this->_integrityChecks();
        return $this->_getField('partial');
    }

    public function getTitle() {
        $this->_integrityChecks();
        return $this->_getField(['file_settings', 'title']);
    }

    public function setTitle($title){
        $this->_integrityChecks();
        $this->_setField(['file_settings', 'title'], $title);
    }

    public function getExtension() {
        $this->_integrityChecks();
        return $this->_getField(['file_settings', 'extension']);
    }

    public function setExtension($extension) {
        $this->_integrityChecks();
        $this->_setField(['file_settings', 'extension'], $extension);
    }

    public function getWarnings() {
        return $this->_getField('warnings');
    }

    public function setData($file) {
        $this->_integrityChecks();
        //we don't need to unset the url, this will be automatically done by uploading new data.
        if(is_object($file) && $file instanceof \SplFileObject) {
            $this->_data = $file;
            $this->_dataType = "SplFileObject";
            return;
        } else if(is_object($file) && $file instanceof \SplFileInfo) {
            $this->_data = $file->getRealPath();
            $this->_dataType = "path";
            return;
        } else if (is_string($file) && file_exists($file)) {
            $this->_data = $file;
            $this->_dataType = "path";
            return;
        }
        throw new InvalidArgumentException("The \$file parameter must be either a path to a file or a SplFileInfo/SplFileObject");
    }

    public function setDataBlob($byte_string) {
        $this->_integrityChecks();
        if(!is_string($byte_string)){
            throw new InvalidArgumentException("The \$byte_string parameter must a byte string.");
        }
        $this->_data = $byte_string;
        $this->_dataType = "blob";
    }

    public function create(){
        $this->_integrityChecks();
        $this->_create("sessions/" . $this->_session->getUUID() . "/files");
        //point the resource to the right location, now we have the correct uuid.
        $this->_resource = 'sessions/' . $this->_session->getUUID() . '/files/' . $this->getUUID();
        if(isset($this->_data) && $this->_data !== null) {
            $this->_uploadFile();
        }
        $this->refresh();
    }

    public function delete(){
        $this->_integrityChecks();
        //expose the delete operation
        $this->_delete();
    }

    public function update() {
        $this->_integrityChecks();
        $this->_sendChangedData();
        if(isset($this->_data) && $this->_data !== null) {
            $this->_uploadFile();
        }
        $this->refresh();
    }

    private function _uploadFile() {
        $apiClient = $this->_apiClient;
        $session_id = $this->_session->getUUID();
        $file_id = $this->getUUID();

        $data = $this->_data;
        $length = 0;
        switch($this->_dataType){
            case "blob":
                $counter = 0;
                $length = strlen($data);
                $read_function = function ($len) use (&$data, &$counter) {
                    if ($counter > strlen($data)) {
                        return "";
                    }
                    $result = substr($data, $counter, $len);
                    if ($result === false){
                        return "";
                    }
                    $counter += $len;
                    return $result;
                };
                break;
            case "path":
                $data = new \SplFileObject($data);
            case "SplFileObject":
                $length = $data->getSize() - $data->ftell();
                $read_function = function ($len) use (&$data) {
                    if($data->eof()) {
                        return "";
                    }
                    return $data->fread($len);
                };
                break;
        }

        $response = $apiClient->PUT("sessions/$session_id/files/$file_id/data", ['read' => $read_function, 'length' => $length],  ['accept-language' => $this->_language]);

        if($response->isError()){
            throw $response->asException();
        }

        if($response->status === 204){
            return;
        } else {
            $this->_warnings = $response->body['warnings'];
        }
        $this->_data = null;
        $this->_dataType = null;
    }

    public function discardChanges () {
        parent::discardChanges();
        $this->_data = null;
        $this->_dataType = null;
    }

    public function __toString()
    {
        return 'PDFen\\Session\\File <uuid => ' . $this->getUUID() . ', extension => ' . $this->getExtension() . ', title => ' . $this->getTitle() . '>';
    }
}