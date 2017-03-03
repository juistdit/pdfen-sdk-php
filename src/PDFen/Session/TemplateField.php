<?php
/**
 * Created by PhpStorm.
 * User: kay
 * Date: 03-03-17
 * Time: 15:20
 */

namespace PDFen\Session;


use PDFen\Session;

class TemplateField
{

    private $_data;
    private $_session;

    public function __construct(Session $session, $data) {
        $this->_session = $session;
        $this->_data = $data;
    }

    public function isCorrectValue($value) {
        $this->_integrityChecks();
        if($this->_data['optional'] && $value === null) {
            return true;
        }
        $type = $this->_data['type'];
        if(is_string($type)) {
            switch($type) {
                case 'integer':
                    return ('' . intval($value)) === $value;
                case 'number':
                    return is_numeric($value);
                case 'single_line':
                    return strpos($value, "\n") !== false;
                case 'datetime':
                    if ($value instanceof \DateTime) {
                        return true;
                    }
                    if (preg_match("/^(\d{4})-([0,1]\d)-([0-3]\d)T([0-2]\d):([0-5]\d):([0-5]\d)$/", $value, $output_array)) {
                        $year = $output_array[1];
                        $month = $output_array[2];
                        $day = $output_array[3];
                        $hour = $output_array[4];
                        $minutes = $output_array[5];
                        $seconds = $output_array[6];
                        return checkdate($month, $day, $year) && $hour < 24;
                    } else {
                        return false;
                    }
                case 'time':
                    return !!(preg_match("/^([0-2]\d):([0-5]\d):([0-5]\d)$/", $value));
                case 'date':
                    if (preg_match("/^(\d{4})-([0,1]\d)-([0-3]\d)$/", $value, $output_array)) {
                        $year = $output_array[1];
                        $month = $output_array[2];
                        $day = $output_array[3];
                        return checkdate($month, $day, $year);
                    } else {
                        return false;
                    }
                case 'boolean':
                    return $value === true || $value === false;
                default:
                    return true;
            }
        } else {
            return in_array($value, array_keys($type['values']));
        }
		return true;
    }

    public function getId() {
        $this->_integrityChecks();
        return $this->_data['field_id'];
    }

    public function getName() {
        $this->_integrityChecks();
        return $this->_data['name'];
    }

    public function getDescription() {
        $this->_integrityChecks();
        return $this->_data['description'];
    }

    public function getType() {
        $this->_integrityChecks();
        return $this->_data['type'];
    }

    public function getMinimalLicenseLevel() {
        $this->_integrityChecks();
        return $this->_data['min_license_level'];
    }

    public function isReadOnly() {
        $this->_integrityChecks();
        return $this->_data['readonly'];
    }

    public function isAccessible() {
        $this->_integrityChecks();
        return !$this->_data['readonly'] && $this->_data['min_license_level'] <= $this->_session->getLicense()->level;
    }

    private function _integrityChecks() {
        if($this->_session->isDeleted()){
            throw new IllegalStateException("The session has been deleted.");
        }
    }
}