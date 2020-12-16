<?php
namespace Kitodo\Dlf\Plugin\Tools;

use TYPO3\CMS\Core\Log\LogLevel;
use Kitodo\Dlf\Plugin\FullTextGenerator;

class FulltextGeneratorTool extends \Kitodo\Dlf\Common\AbstractPlugin {

    public $scriptRelPath = 'Classes/Plugin/Tools/FulltextGeneratorTool.php';

    /**
     * The main method of the PlugIn
     *
     * @access public
     *
     * @param string $content: The PlugIn content
     * @param array $conf: The PlugIn configuration
     *
     * @return string The content that is displayed on the website
     */
    public function main($content, $conf)
    {
	$this->logger = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class)->getLogger(__CLASS__);
	$this->init($conf);
        // Merge configuration with conf array of toolbox.
	if (!empty($this->cObj->data['conf'])) {
	    $this->conf = Helper::mergeRecursiveWithOverrule($this->cObj->data['conf'], $this->conf);
	}
	$this->loadDocument();
        if (
            $this->doc === null
            || $this->doc->numPages < 1
	    || empty($this->conf['fileGrpFulltext'])
        ) {
            // Quit without doing anything if required variables are not set.
            return $content;
	  }
	
	$this->getTemplate();
        $fullTextFile = $this->doc->physicalStructureInfo[$this->doc->physicalStructure[$this->piVars['page']]]['files'][$this->conf['fileGrpFulltext']];
	if (empty($fullTextFile) && !FullTextGenerator::checkLocal($this->extKey, $this->doc, $this->piVars['page'])) {
	    //FIXME: $content .= "<a onclick=\"document.getElementById('tx-dlf-fulltext-create-page').submit()\">" . $this->pi_getLL('ocr-page', '') . "</a>";
	    $content .= "<a class='ocr-page' onclick=\"document.getElementById('tx-dlf-fulltext-create-page').submit()\"> Volltext An </a>";
	    $content .= "<a class='ocr-book' onclick=\"document.getElementById('tx-dlf-fulltext-create-book').submit()\"> Volltext An </a>";
	}

        return $this->pi_wrapInBaseClass($content);
    }
}
?>
