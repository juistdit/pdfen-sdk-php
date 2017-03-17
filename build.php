#!/usr/bin/env php
<?php
unlink('pdfen-sdk-php.phar');
$archive = new Phar('pdfen-sdk-php.phar', 0, 'pdfen-sdk-php.phar');
$archive->setStub('<?php
  Phar::mapPhar();
  spl_autoload_register(function ($class) {
    $class = str_replace("\\\\", "/", $class);
if(file_exists(\'phar://pdfen-sdk-php.phar/src/\'.$class.\'.php\') !== false) {
        include \'phar://pdfen-sdk-php.phar/src/\'.$class.\'.php\';
    }
  },true, true);
  __HALT_COMPILER();');
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
$filterIterator4 = new CallbackFilterIterator($filterIterator3, function ($file) {

    echo $file;
    return true;
});
$archive->buildFromIterator($filterIterator4, __DIR__);
