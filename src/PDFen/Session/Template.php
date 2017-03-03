<?php
/**
 * Created by PhpStorm.
 * User: kay
 * Date: 03-03-17
 * Time: 13:30
 */

namespace PDFen\Session;


use PDFen\Exceptions\NoSuchValueException;

class Template extends SessionObject
{

    private $_fields;

    private function _ensureFields() {
        if(!isset($this->_fields) || $this->_fields === null){
            $raw_fields = $this->_getField('fields');
            $fields = [];
            foreach($raw_fields as $raw_field){
                $fields[$raw_field['field_id']] = new TemplateField($this->_session, $raw_field);
            }
            $this->_fields = $fields;
        }
    }
    public function select() {
        $this->_integrityChecks();
        $this->_session->getOptions()->loadTemplate($this);
    }

    public function hasField($name) {
        $this->_integrityChecks();
        $this->_ensureFields();
        return isset($this->_fields[$name]);
    }

    public function getField($name) {
        $this->_integrityChecks();
        $this->_ensureFields();
        if(isset($this->_fields[$name])){
            return $this->_fields[$name];
        }
        throw new NoSuchValueException("The field $name did not exist in this template.");
    }

    public function getFields() {
        $this->_integrityChecks();
        $this->_ensureFields();
        return array_values($this->_fields);
    }

    public function getUUID() {
        $this->_integrityChecks();
        return $this->_getField('template_id');
    }

    public function getName() {
        $this->_integrityChecks();
        return $this->_getField('name');
    }

    public function getType() {
        $this->_integrityChecks();
        return $this->_getField('type');
    }

    public function isUserDefined() {
        $this->_integrityChecks();
        return $this->_getField('user_defined');
    }

    public function getMinimalLicenseLevel() {
        $this->_integrityChecks();
        return $this->_getField('min_license_level');
    }

    public function isAccessible() {
        $this->_integrityChecks();
        return $this->_getField('min_license_level') <= $this->_session->getLicense()->level;
    }

    public function isSelected() {
        $this->_integrityChecks();
        return $this->_session->getOptions()->getCurrentTemplate()->getUUID() === $this->getUUID();
    }
}