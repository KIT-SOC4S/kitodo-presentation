<?php

namespace Kitodo\Dlf\Plugin;
use DOMdocument;
use DOMattr;
use TYPO3\CMS\Core\Log\LogLevel;

class FullTextGenerator {
  
  static function getDocLocalId($doc) {
    return $doc->toplevelId;
  }

  static function checkLocal($doc) {
    $doc_id = FullTextGenerator::getDocLocalId($doc);

    return file_exists("/var/www/html/test_xmls/" . $doc_id . ".xml");
    
  }
  static function createFullText($doc) {
    $doc_id = FullTextGenerator::getDocLocalId($doc);
    FulltextGenerator::saveXML($doc_id, "test " . $doc_id);
    $logger = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class)->getLogger(__CLASS__);
    $logger->log(LogLevel::WARNING, "FullTextGenerator creating .xml");
  }
  
  static function getLocalPath($doc) {
    if (FullTextGenerator::checkLocal($doc)) {
      $doc_id = FullTextGenerator::getDocLocalId($doc);
      $logger = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class)->getLogger(__CLASS__);
      $logger->log(LogLevel::WARNING, "Returning local .xml");

      return "test_xmls/" . $doc_id . ".xml";
    } else return ""; 
  }

static function saveXML($doc_id, $text) {
  $dom = new DOMdocument();

  $root = $dom->createelement("alto");
  $layout = $dom->createelement("Layout");
  $page = $dom->createelement("Page");
  $print_space = $dom->createelement("PrintSpace");
  $textblock = $dom->createelement("TextBlock");

  $textline = $dom->createelement("TextLine");
  $string = $dom->createelement("String");
  $content_attr = new DOMattr("CONTENT", $text);
  $string->setattributenode($content_attr);
  $textline->appendchild($string);

  $textblock->appendchild($textline);
  $print_space->appendchild($textblock);
  $page->appendchild($print_space);
  $layout->appendchild($page);
  $root->appendchild($layout);
  $dom->appendchild($root);
  $dom->save("/var/www/html/test_xmls/" . $doc_id . ".xml");
}
}
?>
