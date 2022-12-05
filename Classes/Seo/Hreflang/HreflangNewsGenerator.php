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

use TRAW\HreflangNews\Seo\NewsAvailability;
use TRAW\HreflangNews\Utility\FetchUtility;
use TRAW\HreflangNews\Utility\PageUtility;
use TRAW\HreflangNews\Utility\RelationUtility;
use TRAW\HreflangNews\Utility\UrlUtility;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheGroupException;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\LinkHandling\RecordLinkHandler;
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
     * @var NewsAvailability
     */
    protected $newsAvailability;

    /** @var ContentObjectRenderer */
    public $cObj;

    /**
     * HreflangPagesGenerator constructor.
     *
     * @param ContentObjectRenderer $cObj
     * @param LanguageMenuProcessor $languageMenuProcessor
     */
    public function __construct(ContentObjectRenderer $cObj, LanguageMenuProcessor $languageMenuProcessor)
    {
        parent::__construct($cObj, $languageMenuProcessor);
        $this->relationUtility = GeneralUtility::makeInstance(RelationUtility::class);
        $this->newsAvailability = GeneralUtility::makeInstance(NewsAvailability::class);
    }

    /**
     * @param ModifyHrefLangTagsEvent $event
     *
     * @throws NoSuchCacheException
     * @throws NoSuchCacheGroupException
     * @throws SiteNotFoundException
     */
    public function __invoke(ModifyHrefLangTagsEvent $event): void
    {
        $hrefLangs = $event->getHrefLangs();
        $newsId = $this->newsAvailability->getNewsIdFromRequest();

        if ($newsId > 0) {
            if (FetchUtility::isNoIndex($newsId)) {
                return;
            }
            //remove the x-default, we will determine that later
            unset($hrefLangs['x-default']);

            $languages = $this->languageMenuProcessor->process($this->cObj, [], [], []);

            $connectedNews = $this->getConnectedNewsHreflang($newsId);
            if (!empty($connectedNews)) {
                foreach ($connectedNews as $relationUid => $relationHreflang) {
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
    }

    /**
     * @param int $newsUid
     *
     * @return array
     * @throws NoSuchCacheException
     * @throws NoSuchCacheGroupException
     * @throws SiteNotFoundException
     */
    protected function getConnectedNewsHreflang(int $newsUid): array
    {
        $relationUids = $this->relationUtility->getCachedRelations($newsUid);
        $hreflangs = [];

        foreach ($relationUids as $relationUid) {
            $newsRecord = $this->newsAvailability->fetchNewsRecord($relationUid, 0);
            if ($newsRecord['no_index'] ?? 0) continue;
            $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($newsRecord['pid']);
            /** @var SiteLanguage $language */
            foreach ($site->getLanguages() as $language) {
                $translation = $this->newsAvailability->fetchNewsRecord($relationUid, $language->getLanguageId());

                if (empty($translation) || (int)$site->getConfiguration()['defaultNewsDetailPid'] === 0) continue;

                //get url for detail page and attach news path_segment
                $page = PageUtility::getPageTranslationRecord((int)$site->getConfiguration()['defaultNewsDetailPid'], $language->getLanguageId(), $site);
                $href = UrlUtility::getAbsoluteUrl($page['slug'] . '/' . $translation['path_segment'], $language);

                $hreflangs[$relationUid][$language->getHreflang()] = $href;

                if ($language->getLanguageId() === 0 && !isset($hreflangs['x-default']) && $translation['tx_hreflang_news_xdefault']) {
                    $hreflangs[$relationUid]['x-default'] = $href;
                }
            }
        }

        return $hreflangs;
    }

}