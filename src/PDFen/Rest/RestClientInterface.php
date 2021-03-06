<?php
/**
 * Created by PhpStorm.
 * User: kay
 * Date: 25-02-17
 * Time: 15:29
 */

namespace PDFen\Rest;


interface RestClientInterface
{
    public function __construct($api_url);

    public function GET($url, $headers = [], $writeFunction = null) ;

    public function PATCH($url, $data, $headers = []) ;

    public function POST($url, $data, $headers = []) ;

    public function PUT($url, $data, $headers = []) ;

    public function DELETE($url, $headers = []) ;

    public function HEAD($url, $headers = []) ;
}