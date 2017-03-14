<?php
/**
 * Created by PhpStorm.
 * User: kay
 * Date: 03-03-17
 * Time: 16:38
 */

namespace PDFen\Rest\Json;

use PDFen\Rest\RestResponse;

class EmptyResponse extends RestResponse
{

    public function __construct(array $opts = null)
    {
        if (isset($opts['status'])) {
            $this->status = $opts['status'];
        }
        if(isset($opts['url'])) {
            $this->url = $opts['url'];
        }
        if (isset($opts['header'])) {
            //split header and body
            $headers = $opts['header'];
            //parse headers
            $headers = explode("\r\n", $headers);
            //remove http/1.1 header
            array_shift($headers);
            //split into key and value values
            $headers = array_map(function ($v) {
                return explode(":", $v, 2);
            }, $headers);
            //zip the tuples into an associative array
            $keys = array_map('strtolower', array_column($headers, 0));
            $values = array_column($headers, 1);
            $headers = array_combine($keys, $values);

            $this->headers = $headers;
            $this->raw_body = '';
            $this->body = null;
        }
    }
}