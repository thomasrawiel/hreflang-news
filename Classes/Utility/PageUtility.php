<?php

namespace TRAW\HreflangNews\Utility;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspectFactory;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class PageUtility
 * @package TRAW\HreflangPages\Utility
 */
class PageUtility
{
    /**
     * @param $pageId
     * @param $site
     * @param $languageId
     * @return mixed
     */
    public static function getPageTranslationRecord($pageId, $languageId, $site = null)
    {
        if (empty($site)) {
            $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($pageId);
        }
        $targetSiteLanguage = $site->getLanguageById($languageId);
        $languageAspect = LanguageAspectFactory::createFromSiteLanguage($targetSiteLanguage);

        /** @var Context $context */
        $context = clone GeneralUtility::makeInstance(Context::class);
        $context->setAspect('language', $languageAspect);

        $pageRepository = GeneralUtility::makeInstance(PageRepository::class, $context);
        if ($languageId > 0) {
            return $pageRepository->getPageOverlay($pageId, $languageId);
        }
        return $pageRepository->getPage($pageId);
    }
}