<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Hreflang News',
    'description' => 'Extends TYPO3 EXT:seo hreflang functionality for news records',
    'category' => 'fe',
    'author' => 'Thomas Rawiel',
    'author_email' => 'thomas.rawiel@gmail.com',
    'state' => 'stable',
    'clearCacheOnLoad' => 0,
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-12.99.99',
            'seo' => '12.4.0-12.99.99',
            'news_seo' => '2.0.0-2.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
