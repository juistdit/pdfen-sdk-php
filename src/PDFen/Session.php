<?php
declare(strict_types=1);

namespace PDFen;

use PDFen\Rest\RestClientInterface;

class Session
{

    private $_apiClient;

    private $_token;

    public function __construct(RestClientInterface $apiClient, $token){
        $this->_apiClient = $apiClient;
        $this->_token = $token;
    }
}