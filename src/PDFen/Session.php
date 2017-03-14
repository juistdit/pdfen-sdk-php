<?php
declare(strict_types=1);

namespace PDFen;

use PDFen\Exceptions\IllegalStateException;
use PDFen\Exceptions\InvalidArgumentException;
use PDFen\Exceptions\NoSuchValueException;
use PDFen\Rest\RestClientInterface;
use PDFen\Session\File;
use PDFen\Session\Options;
use PDFen\Session\Template;

class Session
{

    private $_apiClient;

    private $_token;
    private $_language;
    private $_data;
    private $_changedData;
    private $_ordering;
    private $_changedOrdering;
    private $_options;
    private $_templates;

    private $_allFiles;
    private $_isDeleted;

    private $_immediate_mode;

    public function __construct (RestClientInterface $apiClient, $token, $language, $immediate_mode) {
        $this->_apiClient = $apiClient;
        $this->_token = $token;
        $this->_language = $language;
        $this->_data = null;
        $this->_options = new Options($this->_apiClient, $this, $this->_language, "sessions/$token/options");
        $this->_changedData = [];
        $this->_changedOrdering = null;
        $this->_ordering = null;
        $this->_templates = null;
        $this->_isDeleted = false;
        $this->_allFiles = [];
        $this->_immediate_mode = $immediate_mode;
    }

    private function _ensureData () {
        if($this->_data === null) {
            $this->_refreshData();
        }
    }

    private function _ensureOrdering () {
        if($this->_changedOrdering === null && $this->_ordering === null) {
            $this->_refreshOrdering();
        }
    }

    private function _ensureTemplates() {
        if($this->_templates === null) {
            $this->_refreshTemplates();
        }
    }

    public function isDeleted() {
        return $this->_isDeleted;
    }

    public function getTemplates() {
        $this->_integrityChecks();
        $this->_ensureTemplates();
        return $this->_templates;
    }

    public function getTemplateByName($name) {
        $templates = $this->getTemplates();
        foreach($templates as $template) {
            if($template->getName() === $name) {
                return $template;
            }
        }
        throw new NoSuchValueException("No template with name $name exists.");
    }

    public function getOptions() {
        $this->_integrityChecks();
        return $this->_options;
    }

    public function getOrdering() {
        $this->_integrityChecks();
        $this->_ensureOrdering();
        if($this->_changedOrdering !== null){
            $ordering = $this->_changedOrdering;
        } else {
            $ordering = $this->_ordering;
        }
        return $this->_transformRawOrdering($ordering);
    }

    private function _transformRawOrdering($ordering, $createdFilesMapping = null) {
        if($createdFilesMapping === null) {
            foreach ($this->_allFiles as $file) {
                if ($file->exists()) {
                    $createdFilesMapping[$file->getUUID()] = $file;
                }
            }
        }

        $output = [];
        foreach($ordering as $file){
            if(is_string($file)){
                if(isset($createdFilesMapping[$file])) {
                    $output[] = $createdFilesMapping[$file];
                } else {
                    throw new IllegalStateException("The ordering contained files that did not exist.");
                }
            } else {
                $output[] = ["title" => $file['title'], 'children' => $this->_transformRawOrdering($file['children'], $createdFilesMapping)];
            }
        }
        return $output;
    }

    private function _makeOrderingRaw($ordering) {
        $output = [];
        foreach($ordering as $file) {
            if(!is_array($file)) {
                if(!$file->exists()) {
                    throw new InvalidArgumentException("The ordering can contain only files that are created.");
                }
                $output[] = $file->getUUID();
            } else {
                $output[] = ["title" => $file["title"], "children" => $this->_makeOrderingRaw($file['children'])];
            }
        }
        return $output;
    }

    public function setOrdering($ordering) {
        $this->_integrityChecks();
        $this->_changedOrdering = $this->_makeOrderingRaw($ordering);
    }

    public function fetchFiles() {
        $this->_integrityChecks();
        $apiClient = $this->_apiClient;
        $response = $apiClient->GET("sessions/" . $this->getUUID() . "/files", ['accept-language' => $this->_language]);
        if ($response->isError()) {
            throw $response->asException();
        }
        $createdFilesMapping = [];
        foreach ($this->_allFiles as $file) {
            if($file->exists()){
                $createdFilesMapping[$file->getUUID()] = $file;
            }
        }
        $raw_files = $response->body;
        $output = [];
        foreach ($raw_files as $raw_file) {
            if( isset($createdFilesMapping[$raw_file['file_id']]) ) {
                $file = $createdFilesMapping[$raw_files['file_id']];
                $file->_pushUpdate($raw_file);
                $output[$file->getUUID()] = $file;
            }  else {
                $file = new File($this->_apiClient, $this, 'session/' . $this->getUUID() . '/files/' . $raw_file['file_id']);
                $file->_pushUpdate($raw_file);
                $output[$file->getUUID()] = $file;
                $this->_allFiles[] = $file;
            }
        }
        return $output;
    }

    public function newFile($file = null,  $title = null, $extension = null) {
        $this->_integrityChecks();
        $pdfenFile = new File($this->_apiClient, $this, $this->_language);
        if($file !== null) {
            $pdfenFile->setData($file);
        }
        if(is_string($file)){
            $pdfenFile->setTitle(pathinfo($file, PATHINFO_FILENAME));
            $pdfenFile->setExtension(pathinfo($file, PATHINFO_EXTENSION));
        } else if($file instanceof \SplFileInfo){
            $pdfenFile->setExtension($file->getExtension());
            $pdfenFile->setTitle($file->getBasename($file->getExtension()));
        } else {
            $pdfenFile->setTitle($this->_language === "en-US" ? "untitled" : "naamloos");
        }

        if($title !== null) {
            $pdfenFile->setTitle($title);
        }
        if($extension !== null){
            $pdfenFile->setExtension($extension);
        }

        $this->_allFiles[] = $pdfenFile;
        return $pdfenFile;
    }

    public function newFileFromBlob($file, $title = null, $extension = null) {
        $this->_integrityChecks();
        $pdfenFile = new File($this->_apiClient, $this, $this->_language);
        if($file !== null) {
            $pdfenFile->setDataBlob($file);
        }
        if($title === null) {
            $pdfenFile->setTitle($this->_language === "en-US" ? "untitled" : "naamloos");
        } else {
            $pdfenFile->setTitle($title);
        }
        if($extension !== null){
            $pdfenFile->setExtension($extension);
        }
        $this->_allFiles[] = $pdfenFile;
        return $pdfenFile;
    }

    public function getUUID() {
        $this->_integrityChecks();
        return $this->_token;
    }

    public function getExpirationTime() {
        $this->_integrityChecks();
        $this->_ensureData();
        $expiration_time = $this->_data['expiration_time'];
        $parts = explode(":", $expiration_time);
        return new \DateInterval("P".$parts[0]."H".$parts[1]."M".$parts[2]."S");
    }

    public function setExpirationTime($expTime) {
        $this->_integrityChecks();
        if(is_string($expTime) && preg_match('/\d\d+:\d\d:\d\d/')) {
            $this->_changedData['expiration_time'] = $expTime;
            $this->_data['expiration_time'] = $expTime;
        } else if (is_object($expTime) && get_class($expTime) === "DateInterval" && $expTime->invert === 0) {
            $h = $expTime->h;
            if($expTime->days !== false && $expTime->days < 0) {
                $h += $expTime->days * 24;
            } else {
                $h += $expTime->d * 24;
                $h += $expTime->m * 30 * 24;
                $h += $expTime->y * 365.25* 24;
            }
            $expTime_str = $h . ":" . str_pad($expTime->i, "0", STR_PAD_LEFT) . ":" . str_pad($expTime->s, "0", STR_PAD_LEFT);
            $this->_changedData['expiration_time'] = $expTime_str;
            $this->_data['expiration_time'] = $expTime_str;
        } else {
            throw new InvalidArgumentException("The \$expTime parameter must be a string (hh:mm:ss) or positive DateInterval object.");
        }
    }

    public function getLastActivity() {
        $this->_integrityChecks();
        $this->_ensureData();
        $expiration_time = $this->_data['last_activity'];
        return new \DateTime($expiration_time);
    }

    public function isDeletedAfterPDFen() {
        $this->_integrityChecks();
        return $this->_data['auto_delete_after_pdfen'];
    }

    public function deleteAfterPDFen($delete) {
        $this->_integrityChecks();
        if(!is_bool($delete)) {
            throw new InvalidArgumentException("The \$delete parameter must be a boolean");
        }
        $this->_changedData['auto_delete_after_pdfen'] = $delete;
        $this->_data['auto_delete_after_pdfen'] = $delete;
    }

    public function getCreationDate() {
        $this->_integrityChecks();
        $this->_ensureData();
        $creation_date = $this->_data['creation_date'];
        return new \DateTime($creation_date);
    }

    public function getLicense() {
        $this->_integrityChecks();
        $this->_ensureData();
        return (object) $this->_data['license'];
    }

    public function update() {
        $this->_integrityChecks();
        if(count($this->_changedData) > 0) {
            $changedData = $this->_changedData;
            $apiClient = $this->_apiClient;
            $token = $this->_token;
            $response = $apiClient->PATCH("sessions/$token", $changedData, ['accept-language' => $this->_language]);
            if ($response->isError()) {
                throw $response->asException();
            }
            $this->_changedData = [];
        }
        if($this->_changedOrdering !== null) {
            $changedData = $this->_changedOrdering;
            $apiClient = $this->_apiClient;
            $token = $this->_token;
            $response = $apiClient->PUT("sessions/$token/ordering", $changedData, ['accept-language' => $this->_language]);
            if ($response->isError()) {
                throw $response->asException();
            }
            $this->_changedOrdering = null;
        }
        $this->refresh();
    }

    public function discardChanges () {
        $this->_integrityChecks();
        $this->_changedData = [];
        $this->_changedOrdering = null;
    }

    public function refresh() {
        $this->_integrityChecks();
        $this->_refreshData();
        $this->_refreshOrdering();
    }

    private function _refreshOrdering() {
        $token = $this->_token;
        $response = $this->_apiClient->GET("sessions/$token/ordering", ['accept-language' => $this->_language]);
        if ($response->isError()) {
            throw $response->asException();
        }
        $this->_ordering = $response->body;
    }

    private function _refreshData() {
        $token = $this->_token;
        $response = $this->_apiClient->GET("sessions/$token", ['accept-language' => $this->_language]);
        if ($response->isError()) {
            throw $response->asException();
        }
        $this->_data = $response->body;
    }

    private function _refreshTemplates() {
        //is probably only executed once...
        $token = $this->_token;
        $response = $this->_apiClient->GET("sessions/$token/templates", ['accept-language' => $this->_language]);
        if ($response->isError()) {
            throw $response->asException();
        }
        $templates = [];
        $raw_templates = $response->body;
        foreach ($raw_templates as $raw_template) {
            $template = new Template($this->_apiClient, $this, $this->_language, 'sessions/' . $this->getUUID() . '/templates/' . $raw_template['template_id']);
            $template->_pushUpdate($raw_template);
            $templates[] = $template;
        }
        $this->_templates = $templates;
    }

    public function batch($onProgress = null){
        $opt = $this->getOptions()->getOption("typeofaction");
        $this->getOptions()->update();

        $this->getOptions()->setOption("typeofaction", "batch");
        $this->getOptions()->update();

        $result = $this->convert($onProgress);

        $this->getOptions()->setOption("typeofaction", $opt);
        $this->getOptions()->update();

        return $result;
    }

    public function merge($onProgress = null) {
        $opt = $this->getOptions()->getOption("typeofaction");
        $this->getOptions()->update();

        $this->getOptions()->setOption("typeofaction", "merge");
        $this->getOptions()->update();

        $result = $this->convert($onProgress);

        $this->getOptions()->setOption("typeofaction", $opt);
        $this->getOptions()->update();

        return $result;
    }

    public function convert($onProgress = null) {
        $this->_integrityChecks();
        if(!is_callable($onProgress)) {
            $onProgress = function () { };
        }
        $data = ['process_settings' => ['process_synchronous' => false, 'immediate' => $this->_immediate_mode]];
        $token = $this->_token;
        $apiClient = $this->_apiClient;
        $response = $apiClient->POST("sessions/$token/processes?update_counter=0", $data, ['accept-language' => $this->_language]);
        if($response->isError()) {
            throw $response->asException();
        }
        $process_id = $response->body['process_id'];
        $update_counter = $response->body['process_progress']['update_counter'];
        //provide progress results
        while(!isset($response->body['process_result'])) {
            if(isset($response->body['process_progress']['lines']) &&
                (count($response->body['process_progress']['lines']) > 0 || isset($response->body['process_progress']['previous_line']))) {
                $prev_line = isset($response->body['process_progress']['previous_line']) ? $response->body['process_progress']['previous_line'] : null;
                $onProgress($response->body['process_progress']['lines'], $prev_line);
                $update_counter = $response->body['process_progress']['update_counter'];
            }
            $url = "sessions/$token/processes/$process_id?long_pull_timeout=10000&update_counter=$update_counter";
            $response = $apiClient->GET($url, ['accept-language' => $this->_language]);
            if($response->isError()) {
                throw $response->asException();
            }
        }
        //we still need to send the progress to the progress function...
        if(isset($response->body['process_progress']['lines']) &&
            (count($response->body['process_progress']['lines']) > 0 || isset($response->body['process_progress']['previous_line']))) {
            $prev_line = isset($response->body['process_progress']['previous_line']) ? $response->body['process_progress']['previous_line'] : null;
            $onProgress($response->body['process_progress']['lines'], $prev_line);
        }
        //the process has finished succesfully
        return new Session\ConversionResult($apiClient, $response->body['process_result']);
    }

    public function delete() {
        $this->_integrityChecks();
        $token = $this->_token;
        $response = $this->_apiClient->DELETE("sessions/$token", ['accept-language' => $this->_language]);

        if ($response->isError()) {
            throw $response->asException();
        }
        $this->_isDeleted = true;
    }

    protected function _integrityChecks() {
        if($this->_isDeleted) {
            throw new IllegalStateException("This session has been deleted.");
        }
    }

}