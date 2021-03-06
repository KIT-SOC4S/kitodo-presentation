<?php

namespace Kitodo\Dlf\Plugin;
use DOMdocument;
use DOMattr;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Log\LogLevel;

class FullTextGenerator {

  protected $conf = [];

  /**
   * Returns local id of doc (i.e. is needed for fulltext storage)  
   * 
   * @access protected
   *
   * @param \Kitodo\Dlf\Common\Document doc
   *
   * @return string
   */
  protected static function getDocLocalId($doc) {
    return $doc->toplevelId;
  }

  /**
   * Returns local id of page   
   * 
   * @access protected
   *
   * @param \Kitodo\Dlf\Common\Document doc
   * @param int page_num
   *
   * @return string
   */
  protected static function getPageLocalId($doc, $page_num) {
    $doc_id = self::getDocLocalId($doc);
    return "{$doc_id}_$page_num";
  }
  
  /**
   * Returns doc's local path (for example can be a folder)
   * 
   * @access protected
   *
   * @param string ext_key
   * @param \Kitodo\Dlf\Common\Document doc
   *
   * @return string
   */
  protected static function getDocLocalPath($ext_key, $doc) {
    $conf = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ExtensionConfiguration::class)
      ->get($ext_key);
    $doc_id = self::getDocLocalId($doc);
    return $conf['fulltextFolder'] . "/$doc_id";
  }
  
  /**
   * Returns local path to the doc's page (uses getDocLocalPath)
   * 
   * @access public
   *
   * @param string ext_key
   * @param \Kitodo\Dlf\Common\Document doc
   * @param int page_num
   *
   * @return string
   */
  public static function getPageLocalPath($ext_key, $doc, $page_num) {
    $doc_path = self::getDocLocalPath($ext_key, $doc);
    $page_id = self::getPageLocalId($doc, $page_num);
    return "$doc_path/$page_id.xml";
  }

  /**
   * Checks whether local fulltext is present
   * 
   * @access public
   *
   * @param string ext_key
   * @param \Kitodo\Dlf\Common\Document doc
   * @param int page_num
   *
   * @return bool
   */
  public static function checkLocal($ext_key, $doc, $page_num) {
    $conf = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ExtensionConfiguration::class)
      ->get($ext_key);
    return file_exists(self::getPageLocalPath($ext_key, $doc, $page_num));
  }

  /**
   * Checks whether fulltext file is in progress (temporary file is present)
   * 
   * @access public
   *
   * @param string ext_key
   * @param \Kitodo\Dlf\Common\Document doc
   * @param int page_num
   *
   * @return bool
   */
  public static function checkInProgress($ext_key, $doc, $page_num) {
    $conf = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ExtensionConfiguration::class)
      ->get($ext_key);
    return file_exists($conf['fulltextTempFolder'] . '/' . self::getPageLocalId($doc, $page_num) . ".xml");
  }

  /**
   * Create fulltext for all pages in document
   * 
   * @access public
   *
   * @param string ext_key
   * @param \Kitodo\Dlf\Common\Document doc
   * @param array images_urls
   *
   * @return void
   */
  public static function createBookFullText($ext_key, $doc, $images_urls) { 
    $conf = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ExtensionConfiguration::class)
      ->get($ext_key);
    
    for ($i=1; $i <= $doc->numPages; $i++) {
      $delay = $i * $conf['ocrDelay'];
      if (!(self::checkLocal($ext_key, $doc, $i) || self::checkInProgress($ext_key, $doc, $i))) {
	self::generatePageOCR($ext_key, $conf, $doc, $images_urls[$i], $i, $delay);
      }
    }
  }

  /**
   * 
   * 
   * @access protected
   *
   * @param string ext_key
   * @param \Kitodo\Dlf\Common\Document doc
   * @param int page_num
   *
   * @return bool
   */
  public static function createPageFullText($ext_key, $doc, $image_url, $page_num) {
    $conf = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ExtensionConfiguration::class)
      ->get($ext_key);

    if (!(self::checkLocal($ext_key, $doc, $page_num) || self::checkInProgress($ext_key, $doc, $page_num))) {
      return self::generatePageOCR($ext_key, $conf, $doc, $image_url, $page_num);
    }
  }

  /**
   * Main Method for creation of new Fulltext for a page
   * Saves a XML file with fulltext
   * 
   * @access protected
   *
   * @param string ext_key
   * @param array conf
   * @param \Kitodo\Dlf\Common\Document doc
   * @param string image_url
   * @param int page_num 
   *
   * @return void
   */
  protected static function generatePageOCR($ext_key, $conf, $doc, $image_url, $page_num, $sleep_interval = 0) { 

    
    $page_id = self::getPageLocalId($doc, $page_num);
    $image_path = $conf['fulltextImagesFolder'] . "/$page_id";

    $doc_path = self::getDocLocalPath($ext_key, $doc);
    if (!file_exists($doc_path)) mkdir($doc_path);
    $xml_path = self::getPageLocalPath($ext_key, $doc, $page_num);
    $temp_xml_path = $conf['fulltextTempFolder'] . "/$page_id";

    $lock_folder = $conf['fulltextTempFolder'] . "/lock"; // Folder used to lock ocr command

    $image_download_command = "wget $image_url -O $image_path";  
    if ($conf['ocrDummyText']) {
      // tesseract fileadmin/test_images/test.jpg fileadmin/temp_xmls/test_temp.xml -l de alto && mv -f fileadmin/temp_xmls/test.xml fileadmin/test_xmls/test.xml
      $ocr_shell_command = self::getDummyOCRCommand($conf, $image_path, $temp_xml_path, $xml_path);
    } else {
      // tesseract fileadmin/test_images/test.jpg fileadmin/test_xmls/test.xml -l de alto 
      $ocr_shell_command = 
	$conf['ocrEngine'] . " $image_path $xml_path " . " -l " . $conf['ocrLanguages'] . " " . $conf['ocrOptions'] . ";";
    }
    // Removing used image
    $ocr_shell_command .= " rm $image_path";
    // Locking command, so that only one instance of tesseract can run in one time moment
    if ($conf['ocrLock']) {
      $ocr_shell_command= "while ! mkdir \"$lock_folder\"; do sleep 3; done; $ocr_shell_command rm -r $lock_folder;" ;
    }
    exec("($image_download_command && sleep $sleep_interval && ($ocr_shell_command)) > /dev/null 2>&1 &");
  }

  protected static function getDummyOCRCommand($conf, $image_path, $temp_xml_path, $xml_path) {
      self::createDummyOCR($xml_path, $conf['ocrDummyText']);
      return $conf['ocrEngine'] . " $image_path $temp_xml_path " . " -l " . $conf['ocrLanguages'] . " " . $conf['ocrOptions'] . " && mv -f $temp_xml_path.xml $xml_path;";
  }

  /**
   * Create dummy (WIP file) at given path with given text
   * 
   * @access protected
   *
   * @param string path
   * @param string text
   *
   * @return void
   */
  protected static function createDummyOCR($path, $text) {

    $dom = new DOMdocument();

    $root = $dom->createelement("alto");
    $fulltext_dummy= $dom->createElement("Fulltext", "WIP");

    $layout = $dom->createelement("Layout");
    $page = $dom->createelement("Page");
    $print_space = $dom->createelement("PrintSpace");
    $textblock = $dom->createelement("TextBlock");
  
    $text = ["\n","\n","\n","\n","\n","\n","\n","\n", $text];
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
}
?>
