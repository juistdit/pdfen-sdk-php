<?php
/**
 * Created by PhpStorm.
 * User: kay
 * Date: 07-03-17
 * Time: 13:13
 */

namespace PDFen\Tests;

use \Imagick;


class PdfInfo
{

    private $_file;
    private $_errors;
    private $_count;

    public function __construct($file){
        $this->_file = $file;
        $this->_count = null;
    }

    public function getPDFenErrors() {
        if($this->_errors === null) {
            $this->_errors = [];
            $imagick = new Imagick();

            $imagick->setResolution(200,200);
            $tmpfname = tempnam(sys_get_temp_dir(), "ocr");
            $tmpdebug = tempnam(sys_get_temp_dir(), "log");
            unlink($tmpfname);
            for($i = 0; $i < $this->_count; $i++) {

                $tmpfname .= ".jpg";
                $imagick->readImage($this->_file . "[$i]");
                $imagick->writeImage($tmpfname);

                $ocr = new \TesseractOCR($tmpfname);
                $ocr->config("debug_file", $tmpdebug);
                $text = $ocr->run();

                if(preg_match("/WARNING: .* \(code: (?P<code>[a-zA-Z0-9]*)\)/", $text, $output_array)){
                    $this->_errors[] = ["page" => $i+1, "msg" => $output_array[0], "code" => $output_array['code']];
                }
            }
            unlink($tmpfname);
            unlink($tmpdebug);
        }
        return $this->_errors;
    }

    public function pageCount() {
        if($this->_count === null) {
            $imagick = new Imagick($this->_file);
            $this->_count = $imagick->getNumberImages();
        }
        return $this->_count;
    }

}