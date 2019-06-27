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
 * Class IsDateFieldViewHelper
 *
 * @author Christophe Monard <cmonard@archriss.com>
 * @package arc_export
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class IsDateFieldViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

    /**
     * Get TCA label of a field
     *
     * @param string $table
     * @param string $field
     * @return string
     */
    public function render($table, $field) {
        $isDate = FALSE;
        if ($GLOBALS['TCA'][$table]['columns'][$field]['config']['type'] == 'input') {
            if ($GLOBALS['TCA'][$table]['columns'][$field]['config']['eval'] && $GLOBALS['TCA'][$table]['columns'][$field]['config']['eval'] != '') {
                foreach (array('date', 'datetime', 'time', 'timesec') as $test) {
                    if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList($GLOBALS['TCA'][$table]['columns'][$field]['config']['eval'], $test)) {
                        $isDate = TRUE;
                        break;
                    }
                }
            }
        }
        return $isDate;
    }

}