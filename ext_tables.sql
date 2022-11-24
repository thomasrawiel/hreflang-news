CREATE TABLE tx_news_domain_model_news
(
    `tx_hreflang_news_news` int(11) unsigned DEFAULT '0' NOT NULL,
    `tx_hreflang_news_news_2` int(11) unsigned DEFAULT '0' NOT NULL,
    `tx_hreflang_news_xdefault` tinyint(4) unsigned DEFAULT '0' NOT NULL
);

CREATE TABLE tx_hreflang_news_news_news_mm
(
    `uid_local`       int(11)      DEFAULT '0' NOT NULL,
    `uid_foreign`     int(11)      DEFAULT '0' NOT NULL,
    `sorting`         int(11)      DEFAULT '0' NOT NULL,
    `sorting_foreign` int(11)      DEFAULT '0' NOT NULL,

    KEY uid_local_foreign (uid_local, uid_foreign),
    KEY uid_local (uid_local),
    KEY uid_foreign (uid_foreign)
);