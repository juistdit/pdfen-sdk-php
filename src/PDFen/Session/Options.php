<?php
/**
 * Created by PhpStorm.
 * User: kay
 * Date: 03-03-17
 * Time: 13:16
 */

namespace PDFen\Session;


use PDFen\Exceptions\IllegalStateException;
use PDFen\Exceptions\InvalidArgumentException;

class Options extends SessionObject
{
    private $_template;

    public function getOption($name) {
        $this->_integrityChecks();
        $temp = $this->getCurrentTemplate();
        if($temp === null || !$temp->isSelected()){
            throw new IllegalStateException("This object hasn't synced properly with the server");
        }
        if(!$temp->hasField($name)){
            throw new InvalidArgumentException("No field $name exists using the current template.");
        }
        return $this->_getField($name);
    }

    public function setOption($name, $val) {
        $this->_integrityChecks();
        $temp = $this->getCurrentTemplate();
        if($temp === null || !$temp->isSelected()){
            throw new IllegalStateException("This object hasn't synced properly with the server");
        }

        if(!$temp->hasField($name)){
            throw new InvalidArgumentException("No field $name exists using the current template.");
        }

        if(!$temp->getField($name)->isCorrectValue($val)){
            $type = $temp->getField($name)->getType();
            throw new InvalidArgumentException("The value $val was not in the format corresponding to $type");
        }
        if($val instanceof \DateTime){
            $val = $val->format(DATE_ATOM);
        }
        $this->_setField($name, $val);
    }

    public function getCurrentTemplate() {
        $this->_integrityChecks();
        $template_id = $this->_getField('template_id');
        if(!isset($this->_template) || $this->_template->getUUID() !== $template_id) {
            $this->_template = new Template($this->_apiClient, $this->_session, $this->_language, 'sessions/'. $this->_session->getUUID() . '/templates/' . $template_id);
        }
        return $this->_template;
    }

    public function loadTemplate(Template $template) {
        $this->_integrityChecks();
        $this->_setField('template_id', $template->getUUID());
        $this->update();
        $this->_template = $template;
    }
}