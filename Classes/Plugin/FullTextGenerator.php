<?php

namespace Kitodo\Dlf\Plugin;
use DOMdocument;
use DOMattr;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

class FullTextGenerator {

  protected $conf = [];

  //const xmls_dir = "fileadmin/test_xmls/";
  //const temp_xmls_dir = "fileadmin/_temp_/test_xmls/";
  //const temp_text_dir = "fileadmin/_temp_/test_texts/";
  //const temp_images_dir = "fileadmin/_temp_/test_images/";
  
  
  //const ocr_engine = "tesseract";
  //const ocr_options = ["-l um alto"];
  //const ocr_delay = 6;

  private static function getDocLocalId($doc) {
    return $doc->toplevelId;
  }

  private static function getPageLocalId($doc, $page_num) {
    $doc_id = self::getDocLocalId($doc);
    return "{$doc_id}_$page_num";
  }
  
  public static function getDocLocalPath($ext_key, $doc, $page_num) {
    $conf = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ExtensionConfiguration::class)
      ->get($ext_key)['fulltextOCR'];
    $page_id = self::getPageLocalId($doc, $page_num);
    return $conf['fulltextFolder'] . "/$page_id.xml";
  }

  public static function checkLocal($ext_key, $doc, $page_num) {
    $conf = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ExtensionConfiguration::class)
      ->get($ext_key)['fulltextOCR'];
    return file_exists($conf["fulltextFolder"] . '/' . self::getPageLocalId($doc, $page_num) . ".xml");
  }

  public static function checkInProgress($ext_key, $doc, $page_num) {
    $conf = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ExtensionConfiguration::class)
      ->get($ext_key)['fulltextOCR'];
    return file_exists($conf['fulltextTempFolder'] . '/' . self::getPageLocalId($doc, $page_num) . ".xml");
  }

  public static function createBookFullText($ext_key, $doc, $images) { 
    $conf = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ExtensionConfiguration::class)
      ->get($ext_key)['fulltextOCR'];
    
    for ($i=1; $i <= $doc->numPages; $i++) {
      if (!(self::checkLocal($ext_key, $doc, $page_num) || self::checkInProgress($ext_key, $doc, $page_num))) {
	self::generatePageOCR($conf, $doc, $images[$i], $i, $i * $conf['ocrDelay']);
      }
    }
  }

  public static function createPageFullText($ext_key, $doc, $image, $page_num) {
    $conf = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ExtensionConfiguration::class)
      ->get($ext_key)['fulltextOCR'];
    
    if (!(self::checkLocal($ext_key, $doc, $page_num) || self::checkInProgress($ext_key, $doc, $page_num))) {
      return self::generatePageOCR($conf, $doc, $image, $page_num);
    }
  }

  /*
      Main Method for cration of new Fulltext for a page
      Saves a XML file with fulltext
  */
  private static function generatePageOCR($conf, $doc, $image, $page_num, $sleep_interval = 0) { 
    $page_id = self::getPageLocalId($doc, $page_num);
    $image_path = $conf['fulltextImagesFolder'] . $page_id;
    file_put_contents($image_path, file_get_contents($image["url"]));

    $xml_path = $conf['fulltextFolder'] . "/" . $page_id;
    $temp_xml_path = $conf['fulltextTempFolder'] . "/" . $page_id;

    $ocr_shell_command = "";
    if ($conf['fulltextDummy']) {
      self::createDummyOCR($xml_path . ".xml");
      $ocr_shell_command = $conf['ocrEngine'] . " $image_path $temp_xml_path " . $conf['ocrOptions'] . " && mv $temp_xml_path.xml $xml_path.xml";
    } else {
      $ocr_shell_command = $conf['ocrEngine'] . " $image_path $xml_path " . $conf['ocrOptions'];
    }

    $logger = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class)->getLogger(__CLASS__);
    $logger->log(LogLevel::WARNING, "conf:" . implode(' ', $conf));
    $logger->log(LogLevel::WARNING, "ocr command:" . $ocr_shell_command);

    exec("( sleep $sleep_interval && $ocr_shell_command ) > /dev/null 2>&1 &");
  }

  private static function createDummyOCR($path) {

    $dom = new DOMdocument();

    $root = $dom->createelement("alto");
    $fulltext_dummy= $dom->createElement("Fulltext", "WIP");
    $xmlns = new DOMattr("xmlns", "http://www.loc.gov/standards/alto/ns-v2#");
    $xmlns_xlink = new DOMattr("xmlns:xlink", "http://www.w3.org/1999/xlink");
    $xmlns_xsi = new DOMattr("xmlns:xlink", "http://www.w3.org/2001/XMLSchema-instance");

    $layout = $dom->createelement("Layout");
    $page = $dom->createelement("Page");
    $print_space = $dom->createelement("PrintSpace");
    $textblock = $dom->createelement("TextBlock");
  
    $text = ["\n","\n","\n","\n","\n","\n","\n","\n","OCR is being prepared, please try to refresh the page"];
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
    //$xml = @simplexml_load_string(file_get_contents($wip_path));
    //if ($xml === "WIP") {
      //return true;
    //}
    //return false;

  //}

}
?>
