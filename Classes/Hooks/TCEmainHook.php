<?php

namespace TRAW\HreflangNews\Hooks;

/*
 * This file is part of the "hreflang_pages" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TRAW\HreflangNews\Utility\RelationUtility;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheGroupException;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class TCEmainHook
 * @package TRAW\HreflangNews\Hooks
 */
class TCEmainHook
{
    /**
     * @var RelationUtility
     */
    protected $relationUtility;

    public function __construct()
    {
        $this->relationUtility = GeneralUtility::makeInstance(RelationUtility::class);
    }

    /**
     * @param $table
     * @param $id
     * @param $recordToDelete
     * @param null $recordWasDeleted
     * @param DataHandler $pObj
     * @throws NoSuchCacheGroupException
     * @throws NoSuchCacheException
     */
    public function processCmdmap_deleteAction($table, $id, $recordToDelete, $recordWasDeleted, DataHandler &$pObj)
    {
        if ($table === 'tx_news_domain_model_news') {
            $this->relationUtility->removeRelations($recordToDelete['uid']);
        }
    }

    /**
     * @param DataHandler $pObj
     * @throws NoSuchCacheGroupException
     */
    public function processDatamap_afterAllOperations(DataHandler &$pObj)
    {
        if (isset($pObj->datamap['tx_news_domain_model_news'])) {
            foreach ($pObj->datamap['tx_news_domain_model_news'] as $uid => $news) {
                if ((int)$uid > 0) {
                    $relations = $this->relationUtility->getCachedRelations($uid);
                    array_push($relations, $uid);
                    foreach($relations as $relationUid) {
                        $this->relationUtility->flushRelationCacheForPage($relationUid);
                    }

                }
            }
        }
    }
}
