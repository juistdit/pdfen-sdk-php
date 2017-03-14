<?php
declare(strict_types=1);

namespace PDFen\Tests;

use PDFen\Exceptions\IllegalStateException;
use PHPUnit\Framework\TestCase;
use PDFen\Sdk as PDFenSdk;
use PDFen\Session;

/**
 * Created by PhpStorm.
 * User: kay
 * Date: 17-02-17
 * Time: 09:01
 */
class SdkTest extends TestCase
{
    private $_config;

    public function setUp() {
        $this->_config = (include __DIR__ . '/config.php');

    }
    public function testLoginCorrect() {
        $sdk = new PDFenSdk($this->_config);
        //testing with valid password
        try {
            $this->assertInstanceOf(Session::class, $sdk->login($this->_config['username'], $this->_config['password']));
        } catch (\PDFen\Exceptions\AuthorizationException $e){
            $this->fail(sprintf("Failed logging in with username %s and password %s on api %s.",
                $this->_config['api_url'], $this->_config['username'], $this->_config['password']));
        }
        //testing with valid password for the second time
        try {
            $this->assertInstanceOf(Session::class, $sdk->login($this->_config['username'], $this->_config['password']));
        } catch (\PDFen\Exceptions\AuthorizationException $e){
            $this->fail(sprintf("Failed logging in two times using the same sdk object with username %s and password %s on api %s.",
                $this->_config['api_url'], $this->_config['username'], $this->_config['password']));
        }
    }

    public function testLoginInvalid(){
        $sdk = new PDFenSdk($this->_config);
        //testing with an invalid password
        try {
            $sdk->login("Invalid Username", "Invalid Password");
            $this->fail("Logging in with incorrect credentials should fail.");
        } catch (\PDFen\Exceptions\AuthorizationException $e){
            $this->assertTrue(true);
        }
    }

    public function testLanguage() {
        $config = $this->_config;
        $sdk = new PDFenSdk($config);
        $msg = "";
        try {
            $sdk->login("Invalid Username", "Invalid Password");
            $this->fail("Logging in with incorrect credentials should fail.");
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            $this->assertRegExp("/The/", $msg);
            $this->assertRegExp("/and/", $msg);
            $this->assertRegExp("/did/", $msg);
        }

        $config['language'] = 'nl-NL';
        $sdk = new PDFenSdk($config);
        $msg = "";
        try {
            $sdk->login("Invalid Username", "Invalid Password");
            $this->fail("Logging in with incorrect credentials should fail.");
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            $this->assertRegExp("/De/", $msg);
            $this->assertRegExp("/en/", $msg);
            $this->assertRegExp("/van/", $msg);
        }
    }

    public function testDelete() {
        $sdk = new PDFenSdk($this->_config);
        $session = $sdk->login($this->_config['username'], $this->_config['password']);

        $templates = $session->getTemplates();
        $templatefield = $templates[0];
        $templatefield = $templatefield->getFields()[0];

        $testfile = $session->newFileFromBlob("test content", "test file", "txt");
        $options = $session->getOptions();

        $session->delete();

        try{
            $session->getOptions();
            $this->fail("It should not be possible to access objects once the session has been deleted.");
        } catch (IllegalStateException $e){
            $this->assertTrue(true);
        }

        try{
            $templates[1]->getType();
            $this->fail("It should not be possible to access objects once the session has been deleted.");
        } catch (IllegalStateException $e){
            $this->assertTrue(true);
        }

        try{
            $templatefield->getType();
            $this->fail("It should not be possible to access objects once the session has been deleted.");
        } catch (IllegalStateException $e){
            $this->assertTrue(true);
        }

        try{
            $testfile->create();
            $this->fail("It should not be possible to access objects once the session has been deleted.");
        } catch (IllegalStateException $e){
            $this->assertTrue(true);
        }

        try{
            $options->getOption("maintitle");
            $this->fail("It should not be possible to access objects once the session has been deleted.");
        } catch (IllegalStateException $e){
            $this->assertTrue(true);
        }


    }
}