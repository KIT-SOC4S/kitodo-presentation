<?php

namespace Kitodo\Dlf\Plugin;
use DOMdocument;
use DOMattr;
use TYPO3\CMS\Core\Log\LogLevel;

class FullTextGenerator {

  const xmls_dir = "fileadmin/test_xmls/";
  const temp_xmls_dir = "fileadmin/_temp_/test_xmls/";
  const temp_text_dir = "fileadmin/_temp_/test_texts/";
  const temp_images_dir = "fileadmin/_temp_/test_images/";
  
  const ocr_engine = "tesseract";
  const ocr_options = ["-l um alto"];
  const ocr_delay = 5;

  static function getDocLocalPath($doc, $page_num) {
    $page_id = FullTextGenerator::getPageLocalId($doc, $page_num);
    return "fileadmin/test_xmls/$page_id.xml";
  }

  static function getDocLocalId($doc) {
    return $doc->toplevelId;
  }

  static function getPageLocalId($doc, $page_num) {
    $doc_id = self::getDocLocalId($doc);
    return "{$doc_id}_$page_num";
  }

  static function checkLocal($doc, $page_num) {
    return file_exists(self::xmls_dir . self::getPageLocalId($doc, $page_num) . ".xml");
  }

  static function checkTemporary($doc, $page_num) {
    $doc_id = FullTextGenerator::getDocLocalId($doc);
    return file_exists(self::temp_xmls_dir . self::getPageLocalId($doc, $page_num) . ".xml");
  }

  static function createBookFullText($doc, $images) {
    for ($i=1; $i <= $doc->numPages; $i++) {
      $text_path = self::createFullText($doc, $images[$i], $i, false, $i * self::ocr_delay);
    }
  }

  /*
      Main Method for cration of new Fulltext for a page
      Saves a XML file with fulltext
  */
  static function createFullText($doc, $image, $page_num, $create_dummy, $sleep_interval = 0) {
    if (!(self::checkLocal($doc, $page_num) || self::checkTemporary($doc, $page_num))) {
      $page_id = self::getPageLocalId($doc, $page_num);
      $image_path = self::temp_images_dir . $page_id;
      file_put_contents($image_path, file_get_contents($image["url"]));

      $temp_xml_path = self::temp_xmls_dir . $page_id;
      $xml_path = self::xmls_dir . $page_id;

      if ($create_dummy) {
	self::generatePageOCRWithDummy($image_path, $xml_path, $temp_xml_path);
      } else {
	self::generatePageOCR($image_path, $xml_path, $sleep_interval);
      }
      return $xml_path; 
    }
  }

  static function generatePageOCR($image_path, $xml_path, $sleep_interval) {
    $ocr_shell_command = "(sleep $sleep_interval && " . self::ocr_engine . " $image_path $xml_path " . implode(" ", self::ocr_options) 
      . " ) > /dev/null 2>&1 &";
    exec($ocr_shell_command);
  }

  static function generatePageOCRWithDummy($image_path, $xml_path, $temp_xml_path) {
    self::createDummyOCR($xml_path . ".xml");
    $ocr_shell_command = "(" . self::ocr_engine . " $image_path $temp_xml_path " . implode(" ", self::ocr_options) 
      . " && mv $temp_xml_path.xml $xml_path.xml) > /dev/null 2>&1 &";

    exec($ocr_shell_command);
  }

  static function createDummyOCR($path) {

    $dom = new DOMdocument();

    $root = $dom->createelement("alto");
    $fulltext_dummy= $dom->createElement("Fulltext");
    $xmlns = new DOMattr("xmlns", "http://www.loc.gov/standards/alto/ns-v2#");
    $xmlns_xlink = new DOMattr("xmlns:xlink", "http://www.w3.org/1999/xlink");
    $xmlns_xsi = new DOMattr("xmlns:xlink", "http://www.w3.org/2001/XMLSchema-instance");

    $layout = $dom->createelement("Layout");
    $page = $dom->createelement("Page");
    $print_space = $dom->createelement("PrintSpace");
    $textblock = $dom->createelement("TextBlock");
  
    $text = ["\n","\n","\n","\n","\n","\n","\n","\n","OCR is getting prepared, please try to refresh the page"];
    foreach($text as $line) {
      $textline = $dom->createelement("TextLine");
      $string = $dom->createelement("String");
      $content_attr = new DOMattr("CONTENT", $line);
      $string->setattributenode($content_attr);
      $textline->appendchild($string);
      $textblock->appendchild($textline);
    }
    
    $print_space->appendchild($textblock);
    $page->appendchild($print_space);
    $layout->appendchild($page);
    $root->appendChild($fulltext_dummy);
    $root->appendchild($layout);
    $dom->appendchild($root);
    $dom->formatOutput = true;
    $dom->save($path);
  }

  //static function checkWIP($doc, $page_num) {
    //$wip_path = basename(FullTextGenerator::getDocLocalPath($doc, $page_num), ".xml");
    //// TODO deal with @
    //$xml = @simplexml_load_string(file_get_contents($wip_path));
    //if ($xml === "WIP") {
      //return true;
    //}
    //return false;

  //}

}
?>
