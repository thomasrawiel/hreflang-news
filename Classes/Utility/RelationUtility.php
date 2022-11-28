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
        $this->getParameters = GeneralUtility::_GET();
    }

    /**
     * Get hreflang relations from cache or generate the list and cache them
     *
     * @param int $pageId
     * @return array $relations
     * @throws NoSuchCacheGroupException|NoSuchCacheException
     */
    public function getCachedRelations(int $pageId): array
    {
        $relations = $this->getCacheInstance()->get($pageId);
        if (false === $relations) {
            $relations = $this->buildRelations($pageId);
            $this->resetRelationCache($pageId, $relations);
        }

        return $this->getAllRelationUids($relations, $pageId);
    }

    /**
     * @param int $pageId
     * @param array $relations
     * @throws NoSuchCacheGroupException|NoSuchCacheException
     */
    public function resetRelationCache(int $pageId, array $relations)
    {
        $tags = array_map(function ($value) {
            return 'pageId_' . $value['uid_foreign'];
        }, $relations);
        if (!empty($tags)) {
            $this->cacheManager->flushCachesInGroupByTags('pages', $tags);
            $this->getCacheInstance()->set((string)$pageId, $relations, $tags, 7 * 24 * 60 * 60);
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
            ->where($queryBuilder->expr()->orX(
                $queryBuilder->expr()->eq('uid_local', $queryBuilder->createNamedParameter($newsUid, PDO::PARAM_INT)),
                $queryBuilder->expr()->eq('uid_foreign', $queryBuilder->createNamedParameter($newsUid, PDO::PARAM_INT))
            ))
            ->execute();
        if ($affectedRows > 0) {
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
        $this->cacheManager->flushCachesInGroupByTag('pages', 'pageId_' . $newsUid);
    }

    /**
     * Get hreflang relations recursively
     *
     * @param int $pageId
     * @return array
     */
    public function buildRelations(int $pageId): array
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $relations = $queryBuilder
            ->select('mm.*')
            ->from('tx_hreflang_pages_page_page_mm', 'mm')
            ->leftJoin('mm', 'pages', 'p', 'mm.uid_foreign = p.uid')
            ->where($queryBuilder->expr()->orX(
                $queryBuilder->expr()->eq('mm.uid_local', $pageId),
                $queryBuilder->expr()->eq('mm.uid_foreign', $pageId)
            ))
            ->execute()
            ->fetchAllAssociative();

        foreach ($relations as $relation) {
            $queryBuilder2 = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('pages');
            $queryBuilder2->getRestrictions()->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $indirectRelations = $queryBuilder2
                ->select('mm.*')
                ->from('tx_hreflang_pages_page_page_mm', 'mm')
                ->leftJoin('mm', 'pages', 'p', 'mm.uid_foreign = p.uid')
                ->where($queryBuilder2->expr()->andX(
                    $queryBuilder2->expr()->eq('mm.uid_local', (int)$relation['uid_local']),
                    $queryBuilder2->expr()->neq('mm.uid_foreign', (int)$pageId)
                ))
                ->execute()
                ->fetchAllAssociative();
            $relations = array_merge($relations, $indirectRelations);
        }

        //eliminate duplicates
        return array_map("unserialize", array_unique(array_map("serialize", $relations)));
    }

    /**
     * Merge uid_local and uid_forein from all relations into an array
     * and return the unique uid array, excluding the current page uid
     *
     * @param array $relations
     * @param int $pageId
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
     * @return FrontendInterface
     * @throws NoSuchCacheException
     */
    protected function getCacheInstance(string $cacheIdentifier = 'tx_hreflang_pages_cache'): FrontendInterface
    {
        return $this->cacheManager->getCache($cacheIdentifier);
    }
}
