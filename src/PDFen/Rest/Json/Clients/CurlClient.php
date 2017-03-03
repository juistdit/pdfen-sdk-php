<?php
declare(strict_types=1);

namespace PDFen\Rest\Json\Clients;

use PDFen\Rest\Json\EmptyResponse;
use PDFen\Rest\Json\JsonResponse;
use PDFen\Exceptions\RestException;

class CurlClient extends AbstractClient
{

    private $_curl;
    private $_default_curl_config;
    private $_api_url;

    public function __construct($api_url) {
        //save the curl handle to enable pipelining.
        $this->_api_url = $api_url;
        $this->_curl = curl_init();
        $this->_default_curl_config = [
            CURLOPT_RETURNTRANSFER => true,
        ];
    }

    public function sendRequest($method, $url, array $headers = [], $data = null, $write_function = null){
        //if data is an array then we do json
        //if data is a string then we post this string
        //if data is a splfileinfo, then we post this file info.
        $curl = $this->_curl;
        $config = $this->_default_curl_config;

        //make it an non associative array
        $headers = array_map(function($key, $value) { return "$key: $value";},
            array_keys($headers), array_values($headers));
        if ($data !== null && is_array($data)) {
            $headers[] = 'Content-Type: application/json';
            $config[CURLOPT_POSTFIELDS] = json_encode($data);
        } else if(is_callable($data)) {
            $config[CURLOPT_READFUNCTION] = function ($ch, $fp, $len) use ($data) { return $data($len);};
        }

        if(is_callable($write_function)) {
            $header = '';
            $response = null;
            $config[CURLOPT_HEADERFUNCTION] = function ($ch, $header_data) use (&$header) {
                $header += $header_data;
                return strlen($header_data);
            };
            $config[CURLOPT_WRITEFUNCTION] = function ($ch, $body_data) use (&$response, $write_function, &$header) {
                if($response === null){
                    $response = new EmptyResponse(['status' => curl_getinfo($ch, CURLINFO_HTTP_CODE), 'header' => $header]);
                }
                $len = strlen($body_data);
                if(!$write_function($response, $body_data)) {
                    return false;
                }
                return $len;
            };
        }

        $url = $this->_api_url . $url;
        //we need to use the + operator: CURLOPT constants are numerical, array_merge renumbers them
        $config = [
                CURLOPT_RETURNTRANSFER => !is_callable($write_function),
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_URL => $url,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_HEADER => !is_callable($write_function),
                CURLOPT_FOLLOWLOCATION => false
            ] + $config;
        curl_reset($curl);
        curl_setopt_array($curl, $config);
        if(is_callable($write_function)) {
            $content = curl_exec($curl);
        } else {
            return null;
        }
        if($content === false) {
            throw new RestException(curl_error($curl), curl_errno($curl));
        }
        return new JsonResponse(['status' => curl_getinfo($curl, CURLINFO_HTTP_CODE), 'raw_body' => $content]);
    }
}