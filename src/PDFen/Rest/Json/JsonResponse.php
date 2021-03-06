<?php
//declare(strict_types=1);

namespace PDFen\Rest\Json;


use PDFen\Rest\RestResponse;

class JsonResponse extends RestResponse
{

    public function __construct(array $opts = null) {
        if(isset($opts['status'])) {
            $this->status = $opts['status'];
        }
        if(isset($opts['url'])) {
            $this->url = $opts['url'];
        }
        if(isset($opts['raw_body'])) {
            $raw_body = $opts['raw_body'];
            //split header and body
            list($headers, $body) = explode("\r\n\r\n", $raw_body, 2);
            //parse headers
            $headers = explode("\r\n", $headers);
            //remove http/1.1 header
            array_shift($headers);
            //split into key and value values
            $headers = array_map(function($v){return explode(":", $v, 2);}, $headers);
            //zip the tuples into an associative array
            $keys = array_map('strtolower', array_column($headers, 0));
            $values = array_column($headers, 1);
            $headers = array_combine($keys, $values);

            $this->headers = $headers;
            $this->raw_body = $body;
            $this->body = json_decode($body, true);
        }
    }

}