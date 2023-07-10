<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Hreflang News',
    'description' => 'Extends TYPO3 EXT:seo hreflang functionality for news records',
    'category' => 'fe',
    'author' => 'Thomas Rawiel',
    'author_email' => 'thomas.rawiel@gmail.com',
    'state' => 'beta',
    'clearCacheOnLoad' => 0,
    'version' => '0.2.2',
    'constraints' => [
        'depends' => [
            'typo3' => '10.0.0-11.99.99',
            'seo' => '10.0.0-11.99.99',
            'news_seo' => '2.0.0-2.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];