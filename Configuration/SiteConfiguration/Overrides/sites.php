<?php
/**
 * Copyright notice
 *
 * (c) 2022 Thomas Rawiel <t.rawiel@lingner.com>
 *
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 *
 * Last modified: 05.12.22, 14:50
 */

// Configure a new simple required input field to site
$GLOBALS['SiteConfiguration']['site']['columns']['defaultNewsDetailPid'] = [
    'label' => 'Default News Detail Page Uid',
    'config' => [
        'type' => 'input',
        'eval' => 'trim',
    ],
];
// And add it to showitem
$GLOBALS['SiteConfiguration']['site']['types']['0']['showitem'] .= ',--div--;Hreflang News,defaultNewsDetailPid';