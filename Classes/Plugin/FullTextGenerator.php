<?php

namespace Kitodo\Dlf\Plugin;
use DOMdocument;
use DOMattr;
use TYPO3\CMS\Core\Log\LogLevel;

class FullTextGenerator extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

  const xmls_dir = "fileadmin/test_xmls/";
  const temp_xmls_dir = "fileadmin/_temp_/test_xmls/";
  const temp_text_dir = "fileadmin/_temp_/test_texts/";
  const temp_images_dir = "fileadmin/_temp_/test_images/";
  
  static function getDocLocalId($doc) {
    return $doc->toplevelId;
  }

  static function checkLocal($doc, $page_num) {
    $doc_id = FullTextGenerator::getDocLocalId($doc);
    return file_exists(self::xmls_dir . "{$doc_id}_$page_num.xml");
  }

  /*
  Main Method for cration of new Fulltext for a page
  Saves a XML file with fulltext
  */
  static function createFullText($doc, $image, $page_num) {
    $doc_id = FullTextGenerator::getDocLocalId($doc);
    $text_path = FullTextGenerator::generateOCR($image, $doc_id, $page_num);
    
    //$logger = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class)->getLogger(__CLASS__);
    //$logger->log(LogLevel::WARNING, "WIP: " . FullTextGenerator::checkWIP($doc, $page_num));
    return $text_path;
  }
 
  static function generateOCR($image, $doc_id, $page_num) {
    $page_id = basename($image["url"]);
    $image_path = self::temp_images_dir . "{$page_id}";
    file_put_contents($image_path, file_get_contents($image["url"]));
    $xml_name =  "{$doc_id}_$page_num";

    $temp_xml_path = self::temp_xmls_dir . $xml_name;  
    $xml_path = self::xmls_dir . $xml_name;  
    //$wip_path = "$xml_path-wip.xml";

    FullTextGenerator::createDummyOCR($xml_path . ".xml");
    $ocr_shell_command = "(tesseract $image_path $temp_xml_path -l um alto > /dev/null 2>&1 ;  mv $temp_xml_path.xml $xml_path.xml) &";
    $logger = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class)->getLogger(__CLASS__);

    $logger->log(LogLevel::WARNING, "shell command: " . $ocr_shell_command);
    //$ocr_shell_command = "echo 222";
    shell_exec($ocr_shell_command);
    $logger->log(LogLevel::WARNING, "shell_exec successful");
    return $xml_path;
  }

  static function createDummyOCR($path) {
    $dom = new DOMdocument();
    $root = $dom->createelement("Fulltext", "WIP");
    $dom->appendchild($root);
    $dom->formatOutput = true;
    $dom->save($path);
  }

  static function checkWIP($doc, $page_num) {
    $wip_path = basename(FullTextGenerator::getDocLocalPath($doc, $page_num), ".xml") . "-wip.xml";
    // TODO deal with @
    $xml = @simplexml_load_string(file_get_contents($wip_path));
    if ($xml === "WIP") {
      return true;
    }
    return false;

  }

  static function getDocLocalPath($doc, $page_num) {
    $doc_id = FullTextGenerator::getDocLocalId($doc);
    $logger = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class)->getLogger(__CLASS__);
    $logger->log(LogLevel::WARNING, "Returning local .xml");

    return "fileadmin/test_xmls/{$doc_id}_$page_num.xml";
  }
}
?>
