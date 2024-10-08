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
 * Last modified: 05.12.22, 14:10
 */

namespace TRAW\HreflangNews\Seo;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class NewsAvailability
 */
class NewsAvailability extends \GeorgRinger\News\Seo\NewsAvailability
{
    /**
     * @param int $newsUid
     * @param int $language
     *
     * @return false|mixed[]|null
     */
    public function fetchNewsRecord(int $newsUid, int $language)
    {
        return $this->getNewsRecord($newsUid, $language);
    }

    /**
     * @param int $newsId
     * @param int $language
     *
     * @return false|mixed[]|null
     * @throws \Doctrine\DBAL\Exception
     */
    protected function getNewsRecord(int $newsId, int $language)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_news_domain_model_news');
        if ($language === 0) {
            $where = [
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($language, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter(-1, \PDO::PARAM_INT))
                ),
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($newsId, \PDO::PARAM_INT)),
            ];
        } else {
            $where = [
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->and(
                        $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter(-1, \PDO::PARAM_INT)),
                        $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($newsId, \PDO::PARAM_INT))
                    ),
                    $queryBuilder->expr()->and(
                        $queryBuilder->expr()->eq('l10n_parent', $queryBuilder->createNamedParameter($newsId, \PDO::PARAM_INT)),
                        $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($language, \PDO::PARAM_INT))
                    ),
                    $queryBuilder->expr()->and(
                        $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($newsId, \PDO::PARAM_INT)),
                        $queryBuilder->expr()->eq('l10n_parent', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)),
                        $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($language, \PDO::PARAM_INT))
                    )
                ),
            ];
        }

        $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);

        $row = $queryBuilder
            ->select('uid', 'pid', 'l10n_parent', 'sys_language_uid', 'path_segment', 'tx_hreflang_news_xdefault', 'robots_index', 'robots_follow')
            ->from('tx_news_domain_model_news')
            ->where(...$where)
            ->executeQuery()->fetchAssociative();

        return $row ?: null;
    }

    /**
     * @param int        $languageId
     * @param int|string $newsId
     *
     * @return bool
     * @throws SiteNotFoundException
     */
    public function check(int $languageId, $newsId = 0): bool
    {
        if (str_contains($newsId, 'NEW')) {
            return false;
        }
        // get it from current request
        if ($newsId === 0) {
            $newsId = $this->getNewsIdFromRequest();
        }
        if ($newsId === 0) {
            throw new \UnexpectedValueException('No news id provided', 1586431984);
        }

        $site = $this->getRequest()->getAttribute('site');
        if (is_a($site, \TYPO3\CMS\Core\Site\Entity\NullSite::class)) {
            $newsRecord = $this->getNewsRecord($newsId, 0);
            $site = (GeneralUtility::makeInstance(SiteFinder::class))->getSiteByPageId($newsRecord['pid']);
        }

        $allAvailableLanguagesOfSite = $site->getAllLanguages();

        $targetLanguage = $this->getLanguageFromAllLanguages($allAvailableLanguagesOfSite, $languageId);
        if (!$targetLanguage) {
            throw new \UnexpectedValueException('Target language could not be found', 1586431985);
        }
        return $this->mustBeIncluded($newsId, $targetLanguage);
    }
}
