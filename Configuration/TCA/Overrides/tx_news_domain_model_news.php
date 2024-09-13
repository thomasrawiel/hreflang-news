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
 * Last modified: 24.11.22, 14:27
 */
defined('TYPO3') or die('Access denied.');
call_user_func(function ($_EXTKEY = 'hreflang_news', $table = 'tx_news_domain_model_news') {
    $LLL = 'LLL:EXT:hreflang_news/Resources/Private/Language/locallang_tca.xlf:';
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns($table, [
        'tx_hreflang_news_hreflanglist' => [
            'exclude' => true,
            'displayCond' => [
                'OR' => [
                    'FIELD:sys_language_uid:=:0',
                    'FIELD:sys_language_uid:REQ:false',
                ],
            ],
            'label' => $LLL . 'page.preview',
            'config' => [
                'type' => 'none',
                'renderType' => 'hreflanglistnews',
            ],
        ],
        'tx_hreflang_news_news' => [
            'exclude' => true,
            'displayCond' => [
                'OR' => [
                    'FIELD:sys_language_uid:=:0',
                    'FIELD:sys_language_uid:REQ:false',
                ],
            ],
            'label' => $LLL . 'connected-pages',
            'config' => [
                'type' => 'group',
                'allowed' => 'tx_news_domain_model_news',
                'foreign_table' => 'tx_news_domain_model_news',
                'MM' => 'tx_hreflang_news_news_news_mm',
                'size' => 6,
                'autoSizeMax' => 30,
                'minitems' => 0,
                'maxitems' => 9999,
                'suggestOptions' => [
                    'default' => [
                        'searchWholePhrase' => 1,
                    ],
                ],
            ],
        ],
        'tx_hreflang_news_news_2' => [
            'exclude' => true,
            'displayCond' => [
                'OR' => [
                    'FIELD:sys_language_uid:=:0',
                    'FIELD:sys_language_uid:REQ:false',
                ],
            ],
            'label' => $LLL . 'connected-pages-2',
            'config' => [
                'readOnly' => true,
                'type' => 'group',
                'allowed' => 'tx_news_domain_model_news',
                'foreign_table' => 'tx_news_domain_model_news',
                'foreign_table_where' => 'AND tx_news_domain_model_news.sys_language_uid = 0',
                'MM' => 'tx_hreflang_news_news_news_mm',
                'MM_opposite_field' => 'tx_hreflang_news_news',
                'size' => 6,
                'autoSizeMax' => 30,
                'minitems' => 0,
                'maxitems' => 9999,
                'fieldControl' => [
                    'elementBrowser' => [
                        'disabled' => true,
                    ],
                ],
            ],
        ],
        'tx_hreflang_news_xdefault' => [
            'exclude' => true,
            'displayCond' => [
                'OR' => [
                    'FIELD:sys_language_uid:=:0',
                    'FIELD:sys_language_uid:REQ:false',
                ],
            ],
            'label' => $LLL . 'force-x-default',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        'label' => $LLL . 'force-x-default.hint',
                    ],
                ],
                'default' => 0,
            ],
        ],
    ]);

    $GLOBALS['TCA'][$table]['palettes']['hreflang_connections'] = [
        'label' => $LLL . 'palette.hreflang_connections',
        'showitem' => 'linebreak--,tx_hreflang_news_news,tx_hreflang_news_news_2,--linebreak--,tx_hreflang_news_xdefault',
    ];
    $GLOBALS['TCA'][$table]['palettes']['hreflang_preview'] = [
        'label' => 'Hreflang Preview',
        'showitem' => 'tx_hreflang_news_hreflanglist',
    ];

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        $table,
        '--div--;' . $LLL . 'div.hreflang,
        --palette--;;hreflang_connections,
        --palette--;;hreflang_preview',
        '',
        'after:max_image_preview'
    );
});
