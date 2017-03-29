<?php
//declare(strict_types=1);

namespace PDFen\Rest;

abstract class RestResponse
{
    public $url;
    public $status;
    public $headers;
    public $body;
    public $raw_body;

    public function isError() {
        return $this->status > 299;
    }

    private static $_exceptionTranslationTable = [
        'DEFAULT' => '\PDFen\Exceptions\ApiException',
        401 => '\PDFen\Exceptions\AuthorizationException',
    ];

    private function _getExceptionType() {
        if(isset($this->body['code']) && isset(self::$_exceptionTranslationTable[$this->body['code']])) {
            return self::$_exceptionTranslationTable[$this->body['code']];
        }
        return self::$_exceptionTranslationTable['DEFAULT'];
    }

    private function _getExceptionMessage() {
        if (isset($this->body['message'])) {
            $message = $this->body['message'];
            return $message;
        } else {
            var_dump($this);
            return "Something went wrong when communicating with the API";
        }
    }

    public function asException() {
        $code = $this->status;
        $exception_type = $this->_getExceptionType();
        $message = $this->_getExceptionMessage();
        return new $exception_type($message, $code);
    }
}