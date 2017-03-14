<?php
declare(strict_types=1);

namespace PDFen\Tests;

use PDFen\Exceptions\NoSuchValueException;
use PHPUnit\Framework\TestCase;
use PDFen\Sdk as PDFenSdk;
use PDFen\Session;

/**
 * Created by PhpStorm.
 * User: kay
 * Date: 17-02-17
 * Time: 09:01
 */
class TemplateTest extends TestCase
{
    private $_config;
    private $_session;

    public function setUp() {
        $this->_config = (include __DIR__ . '/config.php');
        $sdk = new PDFenSdk($this->_config);
        $this->_session = $sdk->login($this->_config['username'], $this->_config['password']);

    }
    public function testListTemplates() {
        $templates = $this->_session->getTemplates();
        $count = 0;
        foreach($templates as $template) {
            if(!$template->isUserDefined()){
                $count++;
            }
        }
        $this->assertGreaterThanOrEqual(5, $count);
    }

    public function testGetTemplateByName(){
        $template = $this->_session->getTemplateByName('Merge with bookmarks');
        $this->assertEquals($template->getName(), 'Merge with bookmarks');
        $this->assertFalse($template->isUserDefined());

        try{
            $this->_session->getTemplateByName('This template does not exist.');
            $this->fail("It should not be possible to request a template with an invalid name.");
        } catch (NoSuchValueException $e) {
            $this->assertTrue(true);
        }

    }

    public function testFields() {
        $templates = $this->_session->getTemplates();
        $maintitlefound = false;
        $typeofactionfound = false;
        $pdftypefound = false;

        $locatiefound = false;

        foreach ($templates as $template) {
            if($template->getName() === 'Merge with bookmarks') {
                foreach($template->getFields() as $field) {
                    if($field->getId() === 'maintitle') {
                        $maintitlefound = true;
                        $this->assertEquals("single_line", $field->getType());
                        $this->assertEquals(-1, $field->getMinimalLicenseLevel());
                        $this->assertTrue($field->isAccessible());
                        try{
                            $field->getAllowedValues();
                            $this->fail("The field maintitle should not support allowed values.");
                        } catch (NoSuchValueException $e){
                            $this->assertTrue(true);
                        }
                    } else if ($field->getId() === 'printversion') {
                        $typeofactionfound = true;
                        $this->assertEquals("boolean", $field->getType());
                        $this->assertEquals(0, $field->getMinimalLicenseLevel());
                        $this->assertTrue($field->isAccessible());
                        try{
                            $field->getAllowedValues();
                            $this->fail("The field printversion should not support allowed values.");
                        } catch (NoSuchValueException $e){
                            $this->assertTrue(true);
                        }
                    } else if($field->getId() === 'pdftype') {
                        $pdftypefound = true;
                        $this->assertEquals("select", $field->getType());
                        $allowedvalues = $field->getAllowedValues();
                        $this->assertTrue(isset($allowedvalues['normal']));
                        $this->assertTrue(isset($allowedvalues['pdfa']));
                    }
                }
            }

            if($template->getName() === "Meeting"){
                $locatiefound = true;
                $this->assertEquals("meeting", $template->getType());
                $this->assertFalse($template->isUserDefined());
                foreach($template->getFields() as $field) {
                    if($field->getId() === "locatie"){
                        $this->assertEquals("single_line", $field->getType());
                        $this->assertEquals(0, $field->getMinimalLicenseLevel());
                        $this->assertTrue($field->isAccessible());
                        try{
                            $field->getAllowedValues();
                            $this->fail("The field locatie should not support allowed values.");
                        } catch (NoSuchValueException $e){
                            $this->assertTrue(true);
                        }
                    }
                }
            }
        }

        $this->assertTrue($maintitlefound);
        $this->assertTrue($typeofactionfound);
        $this->assertTrue($pdftypefound);
        $this->assertTrue($locatiefound);
    }

    public function testConversion() {
        $session = $this->_session;
        $testfile = $session->newFileFromBlob("test content", "test file", "txt");
        $testfile->create();

        $testfile1 = $session->newFileFromBlob("test content", "test file", "txt");
        $testfile1->create();

        $result = $session->convert();

        $this->assertEquals("merge", $result->type_of_action);
        $this->assertEquals("pdf", $result->type_of_output_file);

        $session->getTemplateByName("Separated files (zip)")->select();

        $result = $session->convert();

        $this->assertEquals("batch", $result->type_of_action);
        $this->assertEquals("zip", $result->type_of_output_file);

    }
}