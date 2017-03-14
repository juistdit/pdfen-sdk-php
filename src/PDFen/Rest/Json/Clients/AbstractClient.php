<?php
declare(strict_types=1);

namespace PDFen\Rest\Json\Clients;

use PDFen\Rest\RestClientInterface;

abstract class AbstractClient implements RestClientInterface
{

    abstract protected function sendRequest($method, $url, array $headers = [], $data = null, $absolute = false);

    public function GET($url, $headers = [], $writeFunction = null, $absolute = false) {
        return $this->sendRequest('GET', $url, $headers, null, $writeFunction, $absolute);
    }

    public function PATCH($url, $data, $headers = [], $absolute = false) {
        return $this->sendRequest('PATCH', $url, $headers, $data, $absolute);
    }

    public function POST($url, $data, $headers = [], $absolute = false) {
        return $this->sendRequest('POST', $url, $headers, $data, $absolute);
    }

    public function PUT($url, $data, $headers = [], $absolute = false){
        return $this->sendRequest('PUT', $url, $headers, $data, $absolute);
    }

    public function DELETE($url, $headers = [], $absolute = false){
        return $this->sendRequest('DELETE', $url, $headers, $absolute);
    }

    public function HEAD($url, $headers = [], $absolute = false){
        return $this->sendRequest('HEAD', $url, $headers, $absolute);
    }
}