<?php

namespace TRAW\HreflangNews\Seo\Hreflang;

/*
 * This file is part of the "hreflang_news" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use GeorgRinger\News\Seo\NewsAvailability;
use Psr\Http\Message\ServerRequestInterface;
use TRAW\HreflangPages\Utility\PageUtility;
use TRAW\HreflangPages\Utility\RelationUtility;
use TRAW\HreflangPages\Utility\UrlUtility;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheGroupException;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\DataProcessing\LanguageMenuProcessor;
use TYPO3\CMS\Frontend\Event\ModifyHrefLangTagsEvent;
use TYPO3\CMS\Seo\HrefLang\HrefLangGenerator;

/**
 * Class HreflangNewsGenerator
 * @package TRAW\HreflangNews\Seo\Hreflang
 */
class HreflangNewsGenerator extends HrefLangGenerator
{
    /**
     * @var RelationUtility
     */
    protected $relationUtility;

    /**
     * HreflangPagesGenerator constructor.
     * @param ContentObjectRenderer $cObj
     * @param LanguageMenuProcessor $languageMenuProcessor
     */
    public function __construct(ContentObjectRenderer $cObj, LanguageMenuProcessor $languageMenuProcessor)
    {
        parent::__construct($cObj, $languageMenuProcessor);
        $this->relationUtility = GeneralUtility::makeInstance(RelationUtility::class);
    }

    /**
     * @param ModifyHrefLangTagsEvent $event
     * @throws NoSuchCacheGroupException
     */
    public function __invoke(ModifyHrefLangTagsEvent $event): void
    {
        $hrefLangs = $event->getHrefLangs();
        if ((int)$this->getTypoScriptFrontendController()->page['no_index'] === 1) {
            return;
        }
        //remove the x-default, we will determine that later
        unset($hrefLangs['x-default']);
        $newsAvailabilityChecker = GeneralUtility::makeInstance(NewsAvailability::class);

        $languages = $this->languageMenuProcessor->process($this->cObj, [], [], []);
        $pageId = (int)$this->getTypoScriptFrontendController()->id;

        $connectedPages = $this->getConnectedPagesHreflang($pageId);
        if (!empty($connectedPages)) {
            foreach ($connectedPages as $relationUid => $relationHreflang) {
                foreach ($relationHreflang as $hreflang => $url) {
                    if (!isset($hrefLangs[$hreflang])) {
                        $hrefLangs[$hreflang] = $url;
                    } else {
                        //don't render duplicates
                        //$hrefLangs[$hreflang . '_' . $relationUid] = $url;
                    }
                }
            }
            ksort($hrefLangs);
        }
        if (count($hrefLangs) > 1 && !isset($hrefLangs['x-default'])) {
            if (array_key_exists($languages['languagemenu'][0]['hreflang'], $hrefLangs)) {
                $hrefLangs['x-default'] = $hrefLangs[$languages['languagemenu'][0]['hreflang']];
            }
        }

        $event->setHrefLangs($hrefLangs);
    }

    /**
     * @param int $pageId
     * @param int $languageId
     * @param ServerRequestInterface|null $request
     * @return array
     */
    protected function getTranslatedPageRecord(int $pageId, int $languageId, ServerRequestInterface $request = null): array
    {
        if (!empty($request)) {
            $site = $request->getAttribute('site');
            if (!$site instanceof SiteInterface) {
                return $this->getTypoScriptFrontendController()->page;
            }
        }

        return PageUtility::getPageTranslationRecord($pageId, $languageId, $site ?? null);
    }

    /**
     * @param $pageUid
     * @return array
     * @throws NoSuchCacheGroupException|NoSuchCacheException
     */
    protected function getConnectedPagesHreflang($pageUid): array
    {
        $relationUids = $this->relationUtility->getCachedRelations($pageUid);
        $hreflangs = [];

        foreach ($relationUids as $relationUid) {
            $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($relationUid);
            /** @var SiteLanguage $language */
            foreach ($site->getLanguages() as $language) {
                $translation = $this->getTranslatedPageRecord($relationUid, $language->getLanguageId());
                if (empty($translation)) continue;
                $href = UrlUtility::getAbsoluteUrl($translation['slug'], $language);
                $hreflangs[$relationUid][$language->getHreflang()] = $href;

                if ($language->getLanguageId() === 0 && !isset($hreflangs['x-default']) && $translation['tx_hreflang_pages_xdefault']) {
                    $hreflangs[$relationUid]['x-default'] = $href;
                }
            }
        }

        return $hreflangs;
    }
}