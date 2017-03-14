<?php
declare(strict_types=1);

namespace PDFen\Tests;

use PHPUnit\Framework\TestCase;
use PDFen\Sdk as PDFenSdk;
use PDFen\Session;

/**
 * Created by PhpStorm.
 * User: kay
 * Date: 17-02-17
 * Time: 09:01
 */
class OptionsTest extends TestCase
{
    private $_config;
    private $_session;

    public function setUp() {
        $this->_config = (include __DIR__ . '/config.php');
        $sdk = new PDFenSdk($this->_config);
        $this->_session = $sdk->login($this->_config['username'], $this->_config['password']);

    }
    public function testDualMainTitle() {
        $session = $this->_session;
        $options1 = $session->getOptions();
        $options2 = $session->getOptions();

        $origTitle = $options1->getOption("maintitle");
        $this->assertEquals($origTitle, $options2->getOption("maintitle"));


        $this->assertInternalType("string", $options1->getOption("maintitle"));
        $this->assertInternalType("string", $options2->getOption("maintitle"));

        $options1->setOption("maintitle", "SDK test title 1");
        $this->assertEquals("SDK test title 1", $options1->getOption("maintitle"));
        $this->assertEquals("SDK test title 1", $options2->getOption("maintitle"));

        $options1->update();

        $this->assertEquals("SDK test title 1", $options1->getOption("maintitle"));
        $this->assertEquals("SDK test title 1", $options2->getOption("maintitle"));

        $options2->refresh();

        $this->assertEquals("SDK test title 1", $options2->getOption("maintitle"));
        $this->assertEquals("SDK test title 1", $options1->getOption("maintitle"));

        $session->delete();
    }

    public function testAction() {
        $session = $this->_session;

        $options = $session->getOptions();

        $options->setOption("typeofaction", "batch");
        $options->update();

        $this->assertEquals("batch", $options->getOption("typeofaction"));

        $session->newFileFromBlob("text file content", "text file", "txt")->create();
        $session->newFileFromBlob("text file content", "text file", "txt")->create();

        $result = $session->convert();

        $this->assertEquals("batch", $result->type_of_action);
        $this->assertEquals("zip", $result->type_of_output_file);


        $session->delete();
    }
}