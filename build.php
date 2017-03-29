#!/usr/bin/env php
<?php

unlink('pdfen-sdk-php.phar');
$archive = new Phar('pdfen-sdk-php.phar', 0, 'pdfen-sdk-php.phar');

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(__DIR__, FilesystemIterator::SKIP_DOTS)
);
$filterIterator = new CallbackFilterIterator($iterator , function ($file) {
    return (strpos($file, "src/") !== false);
});
$filterIterator2 = new CallbackFilterIterator($filterIterator, function ($file) {
    return substr($file,-4) === ".php";
});
$filterIterator3 = new CallbackFilterIterator($filterIterator2, function ($file) {
    return realpath($file) !== realpath(__FILE__);
});

$classMap = [];
$filterIterator4 = new CallbackFilterIterator($filterIterator3, function ($file) use(&$classMap) {
    $file = substr($file, strlen(__DIR__ . '/src/'));
    echo $file;
    $className = str_replace("/", '\\', strtolower($file));
    $className = substr($className, 0 , (strrpos($className, ".")));
    $classMap[$className] = $file;
    return true;
});

$archive->buildFromIterator($filterIterator4, __DIR__ );


$archive->setStub('<?php
  Phar::mapPhar();
  spl_autoload_register(function ($class) {
    static $classMap = null;
    if($classMap === null) {
        $classMap = '.var_export($classMap, true) .';
    }
    $class = strtolower($class);
    if(isset($classMap[$class])) {
        include_once \'phar://pdfen-sdk-php.phar/src/\'.$classMap[$class];
    }
  },true, true);
  __HALT_COMPILER();');
//deleteDir(__DIR__  ."/build");