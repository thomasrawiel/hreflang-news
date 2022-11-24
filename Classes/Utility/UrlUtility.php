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

use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

class UrlUtility
{
    /**
     * @param string $url
     * @param SiteLanguage $siteLanguage
     * @return string
     */
    public static function getAbsoluteUrl(string $url, SiteLanguage $siteLanguage): string
    {
        $uri = new Uri($url);
        if (empty($uri->getHost())) {
            $url = $siteLanguage->getBase()->withPath(str_replace('//', '/', $siteLanguage->getBase()->getPath() . $uri->getPath()));

            if ($uri->getQuery()) {
                $url = $url->withQuery($uri->getQuery());
            }
        }

        return (string)$url;
    }
}