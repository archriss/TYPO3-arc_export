<?php

namespace Archriss\ArcExport\ViewHelpers;

/**
 * change charset view helper
 * default is UTF-8 to UTF-16LE
 *
 * eg: {namespace arcExport=Archriss\ArcExport\ViewHelpers}
 * <arcExport:charsetConverter tocs="ISO-8859-1">....</arcExport:charsetConverter>
 *
 * @author Christophe Monard
 * @package TYPO3
 * @subpackage arc_export
 */
class CharsetConverterViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

    /**
     * change charset view helper
     * default is UTF-8 to UTF-16LE
     *
     * @param string $fromcs
     * @param string $tocs
     * @return string
     */
    public function render($fromcs = 'UTF-8', $tocs = 'UTF-16LE') {
        /* @var $cc \TYPO3\CMS\Core\Charset\CharsetConverter */
        $cc = $this->objectManager->get('TYPO3\\CMS\\Core\\Charset\\CharsetConverter');
        if ($tocs == 'UTF-16LE') {
            $string = chr(255) . chr(254);
        } else {
            $string = '';
        }
        $string .= $cc->conv($this->renderChildren(), $fromcs, $tocs);
        return $string;
    }

}
