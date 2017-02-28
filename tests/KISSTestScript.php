<?php
/**
 * Created by PhpStorm.
 * User: kay
 * Date: 15-02-17
 * Time: 12:10
 */
require_once __DIR__ . '/../vendor/autoload.php';

use PDFen\Sdk;

$config = (include __DIR__ . '/config.php');

if(php_sapi_name() !== "cli") {
    echo "<html>";
    echo "<body>";
    echo "<pre>";
}
$sdk = new Sdk($config);

echo "Logging in with wrong credentials.", PHP_EOL;
try {
    $sdk->login("Username", "doesn't exist");
} catch (\PDFen\Exceptions\AuthorizationException $e){
    echo $e;
}
echo PHP_EOL;

echo "Logging in...", PHP_EOL;
$session = $sdk->login($config['username'], $config['password']);
echo "Logged in.", PHP_EOL;
echo PHP_EOL;


if(php_sapi_name() !== "cli") {
    echo "</pre>";
    echo "</body>";
    echo "</html>";
}