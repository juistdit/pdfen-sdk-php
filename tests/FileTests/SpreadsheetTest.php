<?php
/**
 * Created by PhpStorm.
 * User: kay
 * Date: 07-03-17
 * Time: 12:03
 */

namespace PDFen\Tests\UseCases;


use PHPUnit\Framework\TestCase;
use PDFen\Sdk as PDFenSdk;
use PDFen\Tests\PdfInfo;

class SpreadsheetTest extends TestCase
{
    private $_config;
    private $_session;

    public function setUp() {
        $this->_config = (include __DIR__ . '/../config.php');
        $this->_config['__immediate_mode'] = true;
        $sdk = new PDFenSdk($this->_config);
        $this->_session = $sdk->login($this->_config['username'], $this->_config['password']);

    }

    public function testSingleCel() {
        $session = $this->_session;
        foreach(['csv', 'ods', 'xls', 'xlsx'] as $extension) {
            if(!$this->_config['test_open_office'] && $extension[0] === 'o' && $extension[1] === 'd'){
                continue;
            }
            $file = $session->newFile(__DIR__ . "/../TestFiles/spreadsheets/1cel.$extension");
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
            $file->delete();
        }

        $session->delete();
    }

    public function testThreeTabs() {
        $session = $this->_session;
        foreach(['ods', 'xls', 'xlsx'] as $extension) {
            if(!$this->_config['test_open_office'] && $extension[0] === 'o' && $extension[1] === 'd'){
                continue;
            }
            $file = $session->newFile(__DIR__ . "/../TestFiles/spreadsheets/3tabs.$extension");
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
            $this->assertEquals(3, $info->pageCount());
            unlink($nam);
            $file->delete();
        }

        $session->delete();
    }

    public function testBalance() {
        $session = $this->_session;
        foreach(['csv', 'ods', 'xls', 'xlsx'] as $extension) {
            if(!$this->_config['test_open_office'] && $extension[0] === 'o' && $extension[1] === 'd'){
                continue;
            }
            $file = $session->newFile(__DIR__ . "/../TestFiles/spreadsheets/balance.$extension");
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
            $this->assertEquals(0, count($info->getPDFenErrors()));
            $this->assertLessThan(100, $info->pageCount());
            unlink($nam);
            $file->delete();
        }

        $session->delete();
        
    }

    public function testLargeSpreadsheet() {
        $session = $this->_session;
        foreach(['ods', 'xls', 'xlsx'] as $extension) {
            if(!$this->_config['test_open_office'] && $extension[0] === 'o' && $extension[1] === 'd'){
                continue;
            }
            $file = $session->newFile(__DIR__ . "/../TestFiles/spreadsheets/large-spreadsheet.$extension");
            $file->create();
            $this->assertEquals(1, count($file->getWarnings()));
            $this->assertRegexp('/'.preg_quote('not been pre-checked').'/', $file->getWarnings()[0]);
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
            $file->delete();
        }


        $session->delete();
    }

    public function testWarningRows() {
        $session = $this->_session;
        foreach(['ods', 'xls', 'xlsx'] as $extension) {
            if(!$this->_config['test_open_office'] && $extension[0] === 'o' && $extension[1] === 'd'){
                continue;
            }
            $file = $session->newFile(__DIR__ . "/../TestFiles/spreadsheets/warning-rows.$extension");
            $file->create();
            $this->assertEquals(1, count($file->getWarnings()));
            $this->assertRegexp('/uploaded contains \d+ rows/', $file->getWarnings()[0]);
            $file->delete();
        }

        $session->delete();
    }

    public function testErrorTooMuchRows() {
        $session = $this->_session;
        foreach(['ods', 'xls', 'xlsx'] as $extension) {
            if(!$this->_config['test_open_office'] && $extension[0] === 'o' && $extension[1] === 'd'){
                continue;
            }
            try {
                $file = $session->newFile(__DIR__ . "/../TestFiles/spreadsheets/error-rows.$extension");
                $file->create();
                $this->fail("Uploading a spreadsheet with too many files should cause an error implying that there are too many rows.");
            } catch (\PDFen\Exceptions\ApiException $e) {
                $this->assertRegexp('/contains too many rows/',$e->getMessage());
            }
        }

        $session->delete();
    }

}