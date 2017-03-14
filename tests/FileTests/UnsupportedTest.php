<?php
/**
 * Created by PhpStorm.
 * User: kay
 * Date: 07-03-17
 * Time: 14:32
 */

namespace PDFen\Tests\UseCases;

use PDFen\Sdk as PDFenSdk;
use PDFen\Tests\PdfInfo;
use PHPUnit\Framework\TestCase;

class UnsupportedTest extends TestCase
{
    private $_config;
    /**
     * @var \PDFen\Session
     */
    private $_session;

    public function setUp() {
        $this->_config = (include __DIR__ . '/../config.php');
        $this->_config['__immediate_mode'] = true;
        $sdk = new PDFenSdk($this->_config);
        $this->_session = $sdk->login($this->_config['username'], $this->_config['password']);

    }

    public function testUnsupportedFiles(){
        $session = $this->_session;
        try {
            $mp4file = $session->newFile(__DIR__ . "/../TestFiles/unsupported/unsupported.mp4");
            $mp4file->create();
            $this->fail("Creating an unsupported extension (mp4) should fail.");
        } catch (\PDFen\Exceptions\ApiException $e) {
            $this->assertTrue(true);
        }
        try {
            $sdaflfile = $session->newFile(__DIR__ . "/../TestFiles/unsupported/unsupported1.sdafl");
            $sdaflfile->create();
            $this->fail("Creating an unsupported extension (sdafl) should fail.");
        } catch (\PDFen\Exceptions\ApiException $e) {
            $this->assertTrue(true);
        }
        $emptydocfile = $session->newFile(__DIR__ . "/../TestFiles/unsupported/unsupported.docx");
        $emptydocfile->create();

        $line_count = 0;
        $result = $session->convert(function($lines, $prev_line) use (&$line_count) { $line_count +=  count($lines);});
        $this->assertGreaterThan(0, $line_count);

        //we must get an error in this case
        $this->assertEquals('WARNING', $result->status, "The status was not OK, conversion messages: " . PHP_EOL .
            join("", array_map(function($m) { return " - " . $m . PHP_EOL; }, $result->messages)));

        $nam = tempnam(sys_get_temp_dir(), "pdf");
        $result->download($nam);
        //use PdfInfo to check if we indeed have an error in the output document.
        $info = new PdfInfo($nam);
        $this->assertEquals(1, $info->pageCount());
        $this->assertEquals(1, count($info->getPDFenErrors()));
        unlink($nam);
        $session->delete();
    }
}