<?php

namespace TRAW\HreflangNews\Utility;

/*
 * This file is part of the "hreflang_news" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use PDO;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheGroupException;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class RelationUtility
 */
class RelationUtility
{
    /**
     * @var CacheManager
     */
    protected $cacheManager;

    /**
     * @var array|mixed|string|null
     */
    protected $getParameters;

    public function __construct()
    {
        $this->cacheManager = GeneralUtility::makeInstance(CacheManager::class);
    }

    /**
     * Get hreflang relations from cache or generate the list and cache them
     *
     * @param $newsUid
     *
     * @return array $relations
     * @throws NoSuchCacheGroupException|NoSuchCacheException
     */
    public function getCachedRelations($newsUid): array
    {
        $relations = $this->getCacheInstance()->get($newsUid);
        if ($relations === false) {
            $relations = $this->buildRelations($newsUid);
            $this->resetRelationCache($newsUid, $relations);
        }

        return $this->getAllRelationUids($relations, $newsUid);
    }

    /**
     * @param int   $newsUid
     * @param array $relations
     *
     * @throws NoSuchCacheGroupException|NoSuchCacheException
     */
    public function resetRelationCache(int $newsUid, array $relations)
    {
        $tags = array_map(function ($value) {
            return 'newsId_' . $value['uid_foreign'];
        }, $relations);
        if (!empty($tags)) {
            $this->cacheManager->flushCachesInGroupByTags('tx_news_domain_model_news', $tags);
            $this->getCacheInstance()->set((string)$newsUid, $relations, $tags, 7 * 24 * 60 * 60);
        }
    }

    /**
     * @param int $newsUid
     *
     * @throws NoSuchCacheGroupException|NoSuchCacheException
     */
    public function removeRelations(int $newsUid)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_hreflang_news_news_news_mm');
        $relations = $this->getCachedRelations($newsUid);

        $affectedRows = $queryBuilder
            ->delete('tx_hreflang_news_news_news_mm')
            ->where($queryBuilder->expr()->or(
                $queryBuilder->expr()->eq('uid_local', $queryBuilder->createNamedParameter($newsUid, PDO::PARAM_INT)),
                $queryBuilder->expr()->eq('uid_foreign', $queryBuilder->createNamedParameter($newsUid, PDO::PARAM_INT))
            ))
            ->execute();
        if ((int)$affectedRows > 0) {
            $this->flushRelationCacheForPage($newsUid);
            foreach ($relations as $relationUid) {
                $this->flushRelationCacheForPage($relationUid);
            }
        }
    }

    /**
     * @param int $newsUid
     *
     * @throws NoSuchCacheGroupException
     */
    public function flushRelationCacheForPage(int $newsUid)
    {
        $this->cacheManager->flushCachesInGroupByTag('tx_news_domain_model_news', 'newsId_' . $newsUid);
    }

    /**
     * Get hreflang relations recursively
     *
     * @param int $newsUid
     *
     * @return array
     */
    public function buildRelations(int $newsUid): array
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_news_domain_model_news');
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $relations = $queryBuilder
            ->select('mm.*')
            ->from('tx_hreflang_news_news_news_mm', 'mm')
            ->leftJoin('mm', 'tx_news_domain_model_news', 'n', 'mm.uid_foreign = n.uid')
            ->where($queryBuilder->expr()->or(
                $queryBuilder->expr()->eq('mm.uid_local', $newsUid),
                $queryBuilder->expr()->eq('mm.uid_foreign', $newsUid)
            ))
            ->execute()
            ->fetchAllAssociative();

        foreach ($relations as $relation) {
            $queryBuilder2 = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tx_news_domain_model_news');
            $queryBuilder2->getRestrictions()->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $indirectRelations = $queryBuilder2
                ->select('mm.*')
                ->from('tx_hreflang_news_news_news_mm', 'mm')
                ->leftJoin('mm', 'tx_news_domain_model_news', 'n', 'mm.uid_foreign = n.uid')
                ->where($queryBuilder2->expr()->and(
                    $queryBuilder2->expr()->eq('mm.uid_local', (int)$relation['uid_local']),
                    $queryBuilder2->expr()->neq('mm.uid_foreign', (int)$newsUid)
                ))
                ->execute()
                ->fetchAllAssociative();
            $relations = array_merge($relations, $indirectRelations);
        }

        //eliminate duplicates
        return array_map('unserialize', array_unique(array_map('serialize', $relations)));
    }

    /**
     * Merge uid_local and uid_forein from all relations into an array
     * and return the unique uid array, excluding the current page uid
     *
     * @param array $relations
     * @param int   $pageId
     *
     * @return array
     */
    protected function getAllRelationUids(array $relations, int $pageId): array
    {
        $uidArray = [];
        foreach ($relations as $relation) {
            array_push($uidArray, (int)$relation['uid_local'], (int)$relation['uid_foreign']);
        }
        return array_diff(array_unique($uidArray), [$pageId]);
    }

    /**
     * @param string $cacheIdentifier
     *
     * @return FrontendInterface
     * @throws NoSuchCacheException
     */
    protected function getCacheInstance(string $cacheIdentifier = 'tx_hreflang_news_cache'): FrontendInterface
    {
        return $this->cacheManager->getCache($cacheIdentifier);
    }
}
