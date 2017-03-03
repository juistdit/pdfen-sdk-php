<?php
declare(strict_types=1);

namespace PDFen;

class Sdk {

    const VERSION = '0.0-dev';
    const API_URL = 'https://www.pdfen.com/api/v1/';
    const HTTP_CLIENT = '\PDFen\Rest\Json\Clients\CurlClient';

    private $_config;
    private $_apiClient;

    public function __construct(array $config) {
        $this->_config = array_merge([
            'api_url' => static::API_URL,
            'http_client' => static::HTTP_CLIENT,
            'language' => 'en-US',
        ], $config);
        $clientClass = $this->_config['http_client'];
        $this->_apiClient = new $clientClass($this->_config['api_url']);
    }

    public function login($username, $password) {
        //retrieve the token
        $api = $this->_apiClient;
        $response = $api->POST('sessions', ['username'=>$username, 'password'=>$password], ['accept-language' => $this->_config['language']]);
        if ($response->isError()) {
            throw $response->asException();
        }
        $token = $response->body['session_id'];
        return new Session($this->_apiClient, $token, $this->_config['language']);
    }
}