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
class OrderingTest extends TestCase
{
    private $_config;
    private $_session;
    private $_testfile;

    public function setUp() {
        $this->_config = (include __DIR__ . '/config.php');
        $sdk = new PDFenSdk($this->_config);
        $this->_session = $sdk->login($this->_config['username'], $this->_config['password']);
        $this->_testfile = $this->_session->newFileFromBlob("test content", "test file", "txt");
        $this->_testfile->create();
    }
    public function testOrdering() {
        $session = $this->_session;
        $testordering = [
            ["title" => "chapter1",
                "children" =>
                    [["title" => "section 1",
                       "children" =>
                       [
                            ["title" => "subsection1", "children" => []],
                            ["title" => "subsection2",
                             "children" => [
                                 ["title" => "subsubsection",
                                 "children" => [$this->_testfile]]
                                ]
                            ]
                       ]
                    ]]
            ],
            ["title" =>"chapter2",
                "children" => [["title" => "section", "children" => []]]
            ]
        ];
        $session->setOrdering($testordering);

        $this->assertEquals($session->getOrdering(), $testordering);

        $session->update();

        $this->assertEquals($session->getOrdering(), $testordering);

        $result = $session->batch();

        $tmpfile = tempnam(sys_get_temp_dir(), '');
        $result->download($tmpfile);

        $zip = new \ZipArchive;
        $zip->open($tmpfile);
        $this->assertEquals(2, $zip->numFiles);//file indicating all files and the test file
        $this->assertEquals("chapter1/section 1/subsection2/subsubsection/test file.pdf",$zip->getNameIndex(0));
        $zip->close();

        unlink($tmpfile);
    }

}