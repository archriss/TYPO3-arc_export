<?php

namespace Archriss\ArcExport\ViewHelpers;

/* * ************************************************************
 *  Copyright notice
 *
 *  (c) 2016 Christophe Monard <cmonard@archriss.com>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 * ************************************************************* */

/**
 * Class GetTableNameViewHelper
 *
 * @author Christophe Monard <cmonard@archriss.com>
 * @package arc_export
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class GetTableNameViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

    /**
     * Get TCA label of a table
     *
     * @param string $table
     * @return string
     */
    public function render($table) {
        $label = '';
        if ($GLOBALS['TCA'][$table]['ctrl']['title'] && $GLOBALS['TCA'][$table]['ctrl']['title'] != '') {
            $label = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($GLOBALS['TCA'][$table]['ctrl']['title'], 'ArcExport');
        }
        return $label;
    }

}
