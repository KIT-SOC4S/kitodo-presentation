<?php
use TYPO3\CMS\Core\Log\LogLevel;

class OCRCommand 
{
  public function generateOCR($xmlurl) {
    $ocr_command = 'tesseract';
    // TODO: remove with path variable
    $in_file = '/var/www/dfgviewer_local/';
    //$this->logger = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class)->getLogger(__CLASS__);
    //$this->logger->log(LogLevel::WARNING, "FulltextTool " . $fullTextFile);
    $out_file = '';
    $params = ['-l deu', 'hocr'];

    $command = $ocr_command . " " . $in_file . " " . $out_file . " " . implode(" ", $params);
    $output = shell_exec($command);
  }
}
?>

