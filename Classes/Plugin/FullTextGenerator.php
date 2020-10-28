<?php

namespace Kitodo\Dlf\Plugin;
use DOMdocument;
use DOMattr;
use TYPO3\CMS\Core\Log\LogLevel;

class FullTextGenerator extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

  const xmls_dir = "fileadmin/test_xmls/";
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
    $text = FullTextGenerator::generateOCR($image, $doc_id, $page_num);

    //FulltextGenerator::saveXML($doc_id, $text, $page_num);
  }

  # @return scanned text
  //static function executeOCR($image, $doc_id, $page_num) {
    //$page_id = basename($image["url"]);
    //$image_path = self::temp_images_dir . "{$page_id}_$";
    //file_put_contents($image_path, file_get_contents($image["url"]));
    //$text_path = self::temp_text_dir . $page_id; 
    //$ocr_shell_command = "tesseract $image_path $text_path -l deu";
    //exec($ocr_shell_command);
    //// Removing unacceptable characters
    //$text = preg_replace('/[\x00-\x1F\x7F]/', '', file_get_contents($text_path . ".txt"));
    //return $text;
  //}

  static function generateOCR($image, $doc_id, $page_num) {
    $page_id = basename($image["url"]);
    $image_path = self::temp_images_dir . "{$page_id}";
    file_put_contents($image_path, file_get_contents($image["url"]));
    $xml_path = self::xmls_dir . "{$doc_id}_$page_num"; 
    $ocr_shell_command = "tesseract $image_path $xml_path -l deu alto >/dev/null 2>&1 &";
    shell_exec($ocr_shell_command);
  }

  static private function deleteTemporaryFiles() {
    
  }

  static function getDocLocalPath($doc, $page_num) {
    if (FullTextGenerator::checkLocal($doc, $page_num)) {
      $doc_id = FullTextGenerator::getDocLocalId($doc);
      $logger = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class)->getLogger(__CLASS__);
      $logger->log(LogLevel::WARNING, "Returning local .xml");

      return "fileadmin/test_xmls/{$doc_id}_$page_num.xml";
    } else return ""; 
  }
}
?>
