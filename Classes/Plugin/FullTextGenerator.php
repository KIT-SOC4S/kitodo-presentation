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

  static function getDocLocalId($doc) {
    return $doc->toplevelId;
  }

  static function getPageLocalId($doc, $page_num) {
    $doc_id = FullTextGenerator::getDocLocalId($doc);
    return "{$doc_id}_$page_num";
  }

  static function checkLocal($doc, $page_num) {
    return file_exists(self::xmls_dir . FullTextGenerator::getPageLocalId($doc, $page_num) . ".xml");
  }

  static function checkTemporary($doc, $page_num) {
    $doc_id = FullTextGenerator::getDocLocalId($doc);
    return file_exists(self::temp_xmls_dir . FullTextGenerator::getPageLocalId($doc, $page_num) . ".xml");
  }

  /*
      Main Method for cration of new Fulltext for a page
      Saves a XML file with fulltext
  */
  static function createFullText($doc, $image, $page_num) {
    if (!(FullTextGenerator::checkLocal($doc, $page_num) || FullTextGenerator::checkTemporary($doc, $page_num))) {
      $page_id = FullTextGenerator::getPageLocalId($doc, $page_num);
      $logger = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class)->getLogger(__CLASS__);
      $logger->log(LogLevel::WARNING, "creating fulltext for {$page_id}");
      $text_path = FullTextGenerator::generatePageOCR($image, $page_id);
      return $text_path; 
    }
  }
 
  static function generatePageOCR($image, $page_id) {
    $image_path = self::temp_images_dir . $page_id;
    file_put_contents($image_path, file_get_contents($image["url"]));

    $temp_xml_path = self::temp_xmls_dir . $page_id;
    $xml_path = self::xmls_dir . $page_id;  

    FullTextGenerator::createDummyOCR($xml_path . ".xml");
    $ocr_shell_command = "(" . self::ocr_engine . " $image_path $temp_xml_path " . implode(" ", self::ocr_options) . 
	" && mv $temp_xml_path.xml $xml_path.xml) > /dev/null 2>&1 &";

    $logger = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class)->getLogger(__CLASS__);
    $logger->log(LogLevel::WARNING, "command {$ocr_shell_command}");
    exec($ocr_shell_command);
    return $xml_path;
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

  static function getDocLocalPath($doc, $page_num) {
    $page_id = FullTextGenerator::getPageLocalId($doc, $page_num);
    //$logger = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class)->getLogger(__CLASS__);
    //$logger->log(LogLevel::WARNING, "Returning local .xml");

    return "fileadmin/test_xmls/$page_id.xml";
  }
}
?>
