<?php
/**
 * Created by PhpStorm.
 * User: kay
 * Date: 03-03-17
 * Time: 16:28
 */

namespace PDFen\Session;


use PDFen\Exceptions\InvalidArgumentException;
use PDFen\Rest\Json\JsonResponse;

class ConversionResult
{
    private $_apiClient = null;
    public function __construct($apiClient, $data) {
        $this->_apiClient = $apiClient;
        foreach($data as $key => $value) {
            $this->$key = $value;
        }
    }

    public function download($target) {
        $error_body = '';
        $error_response = null;
        $error = false;
        if(is_bool($target)) {
            if($target) {
                $return = '';
                $this->_apiClient->GET($this->url, [], function ($response, $data) use (&$error_response, &$error, &$error_body, &$return) {
                    if ($response->status > 299) {
                        //error
                        $error = true;
                        $error_body .= $data;
                        $error_response = $response;
                    } else {
                        $return .= $data;
                    }
                    return true;
                });
                if(!$error) {
                    return $return;
                }
            } else {
                $this->_apiClient->GET($this->url, [], function ($response, $data) use (&$error_response, &$error, &$error_body, &$return) {
                    if ($response->status > 299) {
                        //error
                        $error = true;
                        $error_body .= $data;
                        $error_response = $response;
                    } else {
                        echo $data;
                    }

                    return true;
                });
                if (!$error) {
                    return;
                }
            }
        } else if(is_string($target)){
            $fh = fopen($target, "w");
            $this->download($fh);
            fclose($fh);
            return;
        } else if(is_resource($target) && get_resource_type($target) === 'stream') {
            $this->_apiClient->GET($this->url, [], function ($response, $data) use (&$error_response, &$error, &$error_body, &$target) {
                if ($response->status > 299) {
                    //error
                    $error = true;
                    $error_body .= $data;
                    $error_response = $response;
                } else {
                    fwrite($target, $data);
                }
                return true;
            });
            if (!$error) {
                return;
            }
        } else if($target instanceof \SplFileObject){
            $this->_apiClient->GET($this->url, [], function ($response, $data) use (&$error_response, &$error, &$error_body, &$target) {
                if ($response->status > 299) {
                    //error
                    $error = true;
                    $error_body .= $data;
                    $error_response = $response;
                } else {
                    $target->fwrite($data);
                }
                return true;
            });
            if (!$error) {
                return;
            }
        } else if($target instanceof \SplFileInfo) {
            $this->download($target->getRealPath());
            return;
        } else {
            throw new InvalidArgumentException("The parameter \$target must be of type bool, string, SplFileObject/SplFileInfo or stream");
        }
        if($error) {
            $error_response->raw_body = $error_body;
            $error_response->body = json_decode($error_body, true);
            throw $error_response->asException();
        }
    }
}