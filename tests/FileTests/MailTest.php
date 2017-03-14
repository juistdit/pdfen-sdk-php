<?php
/**
 * Created by PhpStorm.
 * User: kay
 * Date: 07-03-17
 * Time: 12:02
 */

namespace PDFen\Tests\UseCases;


use PHPUnit\Framework\TestCase;
use PDFen\Sdk as PDFenSdk;
use PDFen\Tests\PdfInfo;

class MailTest extends TestCase
{
    private $_config;
    private $_session;

    public function setUp() {
        $this->_config = (include __DIR__ . '/../config.php');
        $this->_config['__immediate_mode'] = true;
        $sdk = new PDFenSdk($this->_config);
        $this->_session = $sdk->login($this->_config['username'], $this->_config['password']);

    }

    public function testEML() {
        $session = $this->_session;
        $file = $session->newFile(__DIR__ . "/../TestFiles/mails/sample-mail.eml");
        $file->create();
        $this->assertEquals(0, count($file->getWarnings()));

        $line_count = 0;
        $result = $session->convert(function ($lines, $prev_line) use (&$line_count) {
            $line_count += count($lines);
        });
        $this->assertGreaterThan(0, $line_count);

        //we must get no error in this case
        $this->assertEquals('OK', $result->status, "The status was not OK, conversion messages: " . PHP_EOL .
            join("", array_map(function($m) { return " - " . $m . PHP_EOL; }, $result->messages)));

        $nam = tempnam(sys_get_temp_dir(), "pdf");
        $result->download($nam);
        //use PdfInfo to check if we indeed have no error in the output document.
        $info = new PdfInfo($nam);
        $this->assertEquals(1, $info->pageCount());
        $this->assertEquals(0, count($info->getPDFenErrors()));
        unlink($nam);
        $session->delete();
    }

    public function testPST() {
        $session = $this->_session;
        $file = $session->newFile(__DIR__ . "/../TestFiles/mails/test-pdfen-outlookdir.pst");
        $file->create();
        $this->assertEquals(0, count($file->getWarnings()));

        $line_count = 0;
        $result = $session->convert(function ($lines, $prev_line) use (&$line_count) {
            $line_count += count($lines);
        });
        $this->assertGreaterThan(0, $line_count);

        //we must get no error in this case
        $this->assertEquals('OK', $result->status, "The status was not OK, conversion messages: " . PHP_EOL .
            join("", array_map(function($m) { return " - " . $m . PHP_EOL; }, $result->messages)));

        $nam = tempnam(sys_get_temp_dir(), "pdf");
        $result->download($nam);
        //use PdfInfo to check if we indeed have no error in the output document.
        $info = new PdfInfo($nam);
        $this->assertGreaterThan(1, $info->pageCount());
        unlink($nam);
        $session->delete();
    }
}