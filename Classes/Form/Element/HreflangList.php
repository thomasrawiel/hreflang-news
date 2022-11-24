<?php

namespace TRAW\HreflangNews\Form\Element;

/*
 * This file is part of the "hreflang_news" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TRAW\HreflangNews\Utility\HreflangListUtility;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class HreflangList extends AbstractFormElement
{
    /**
     * @var HreflangListUtility
     */
    protected $hreflangListUtility;

    public function __construct(NodeFactory $nodeFactory, array $data)
    {
        parent::__construct($nodeFactory, $data);
        $this->hreflangListUtility = GeneralUtility::makeInstance(HreflangListUtility::class, $data);
    }

    /**
     * @inheritDoc
     */
    public function render()
    {
        $result = $this->initializeResultArray();
        $result['html'] = $this->hreflangListUtility->generateHrefLangList();

        return $result;
    }
}