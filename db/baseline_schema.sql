-- "Learning with Texts" (LWT) is free and unencumbered software 
-- released into the PUBLIC DOMAIN.
-- 
-- Anyone is free to copy, modify, publish, use, compile, sell, or
-- distribute this software, either in source code form or as a
-- compiled binary, for any purpose, commercial or non-commercial,
-- and by any means.
-- 
-- In jurisdictions that recognize copyright laws, the author or
-- authors of this software dedicate any and all copyright
-- interest in the software to the public domain. We make this
-- dedication for the benefit of the public at large and to the 
-- detriment of our heirs and successors. We intend this 
-- dedication to be an overt act of relinquishment in perpetuity
-- of all present and future rights to this software under
-- copyright law.
-- 
-- THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
-- EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE 
-- WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE
-- AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS BE LIABLE 
-- FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
-- OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN 
-- CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN 
-- THE SOFTWARE.
-- 
-- For more information, please refer to [http://unlicense.org/].
-- --------------------------------------------------------------
-- 
-- --------------------------------------------------------------
-- Installing an LWT demo database, empty schema only.
-- --------------------------------------------------------------

DROP TABLE IF EXISTS archivedtexts;
CREATE TABLE `archivedtexts` (   `AtID` int(11) unsigned NOT NULL AUTO_INCREMENT,   `AtLgID` int(11) unsigned NOT NULL,   `AtTitle` varchar(200) NOT NULL,   `AtText` text NOT NULL,   `AtAnnotatedText` longtext NOT NULL,   `AtAudioURI` varchar(200) DEFAULT NULL,   `AtSourceURI` varchar(1000) DEFAULT NULL,   PRIMARY KEY (`AtID`),   KEY `AtLgID` (`AtLgID`) ) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS archtexttags;
CREATE TABLE `archtexttags` (   `AgAtID` int(11) unsigned NOT NULL,   `AgT2ID` int(11) unsigned NOT NULL,   PRIMARY KEY (`AgAtID`,`AgT2ID`),   KEY `AgAtID` (`AgAtID`),   KEY `AgT2ID` (`AgT2ID`) ) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS newsfeeds;
CREATE TABLE `newsfeeds` (   `NfID` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,   `NfLgID` tinyint(3) unsigned NOT NULL,   `NfName` varchar(40) NOT NULL,   `NfSourceURI` varchar(200) NOT NULL,   `NfArticleSectionTags` text NOT NULL,   `NfFilterTags` text NOT NULL,   `NfUpdate` int(12) unsigned NOT NULL,   `NfOptions` varchar(200) NOT NULL,   PRIMARY KEY (`NfID`),KEY `NfLgID` (`NfLgID`),KEY `NfUpdate` (`NfUpdate`) ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS languages;
CREATE TABLE `languages` (   `LgID` int(11) unsigned NOT NULL AUTO_INCREMENT,   `LgName` varchar(40) NOT NULL,   `LgDict1URI` varchar(200) NOT NULL,   `LgDict2URI` varchar(200) DEFAULT NULL,   `LgGoogleTranslateURI` varchar(200) DEFAULT NULL,   `LgExportTemplate` varchar(1000) DEFAULT NULL,   `LgTextSize` int(5) unsigned NOT NULL DEFAULT '100',   `LgCharacterSubstitutions` varchar(500) NOT NULL,   `LgRegexpSplitSentences` varchar(500) NOT NULL,   `LgExceptionsSplitSentences` varchar(500) NOT NULL,   `LgRegexpWordCharacters` varchar(500) NOT NULL,   `LgRemoveSpaces` int(1) unsigned NOT NULL DEFAULT '0',   `LgSplitEachChar` int(1) unsigned NOT NULL DEFAULT '0',   `LgRightToLeft` int(1) unsigned NOT NULL DEFAULT '0',   PRIMARY KEY (`LgID`),   UNIQUE KEY `LgName` (`LgName`) ) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS sentences;
CREATE TABLE `sentences` (   `SeID` int(11) unsigned NOT NULL AUTO_INCREMENT,   `SeLgID` int(11) unsigned NOT NULL,   `SeTxID` int(11) unsigned NOT NULL,   `SeOrder` int(11) unsigned NOT NULL,   `SeText` text,   PRIMARY KEY (`SeID`),   KEY `SeLgID` (`SeLgID`),   KEY `SeTxID` (`SeTxID`),   KEY `SeOrder` (`SeOrder`) ) ENGINE=MyISAM AUTO_INCREMENT=357 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS settings;
CREATE TABLE `settings` (   `StKey` varchar(40) NOT NULL,   `StValue` varchar(40) DEFAULT NULL,   PRIMARY KEY (`StKey`) ) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS tags;
CREATE TABLE `tags` (   `TgID` int(11) unsigned NOT NULL AUTO_INCREMENT,   `TgText` varchar(20) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,   `TgComment` varchar(200) NOT NULL DEFAULT '',   PRIMARY KEY (`TgID`),   UNIQUE KEY `TgText` (`TgText`) ) ENGINE=MyISAM AUTO_INCREMENT=29 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS tags2;
CREATE TABLE `tags2` (   `T2ID` int(11) unsigned NOT NULL AUTO_INCREMENT,   `T2Text` varchar(20) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,   `T2Comment` varchar(200) NOT NULL DEFAULT '',   PRIMARY KEY (`T2ID`),   UNIQUE KEY `T2Text` (`T2Text`) ) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS textitems;
CREATE TABLE `textitems` (   `TiID` int(11) unsigned NOT NULL AUTO_INCREMENT,   `TiLgID` int(11) unsigned NOT NULL,   `TiTxID` int(11) unsigned NOT NULL,   `TiSeID` int(11) unsigned NOT NULL,   `TiOrder` int(11) unsigned NOT NULL,   `TiWordCount` int(1) unsigned NOT NULL,   `TiText` varchar(250) NOT NULL,   `TiTextLC` varchar(250) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,   `TiIsNotWord` tinyint(1) NOT NULL,   PRIMARY KEY (`TiID`),   KEY `TiLgID` (`TiLgID`),   KEY `TiTxID` (`TiTxID`),   KEY `TiSeID` (`TiSeID`),   KEY `TiOrder` (`TiOrder`),   KEY `TiTextLC` (`TiTextLC`),   KEY `TiIsNotWord` (`TiIsNotWord`) ) ENGINE=MyISAM AUTO_INCREMENT=12761 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS textitems2;
CREATE TABLE `textitems2` (   `Ti2WoID` mediumint(8) unsigned NOT NULL,   `Ti2LgID` tinyint(3) unsigned NOT NULL,   `Ti2TxID` smallint(5) unsigned NOT NULL,   `Ti2SeID` mediumint(8) unsigned NOT NULL,   `Ti2Order` smallint(5) unsigned NOT NULL,   `Ti2WordCount` tinyint(3) unsigned NOT NULL,   `Ti2Text` varchar(250) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,   `Ti2Translation` varchar(250) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,   PRIMARY KEY (`Ti2TxID`,`Ti2Order`,`Ti2WordCount`), KEY `Ti2WoID` (`Ti2WoID`)) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS temptextitems;
CREATE TABLE `temptextitems` (   `TiCount` smallint(5) unsigned NOT NULL,   `TiSeID` mediumint(8) unsigned NOT NULL,   `TiOrder` smallint(5) unsigned NOT NULL,   `TiWordCount` tinyint(3) unsigned NOT NULL,   `TiText` varchar(250) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL   ) ENGINE=MEMORY DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS tempwords;
CREATE TABLE `tempwords` (   `WoText` varchar(250) DEFAULT NULL,   `WoTextLC` varchar(250) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,   `WoTranslation` varchar(500) NOT NULL DEFAULT '*',   `WoRomanization` varchar(100) DEFAULT NULL,   `WoSentence` varchar(1000) DEFAULT NULL,   `WoTaglist` varchar(255) DEFAULT NULL,   PRIMARY KEY (`WoTextLC`)  ) ENGINE=MEMORY DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS texts;
CREATE TABLE `texts` (   `TxID` int(11) unsigned NOT NULL AUTO_INCREMENT,   `TxLgID` int(11) unsigned NOT NULL,   `TxTitle` varchar(200) NOT NULL,   `TxText` text NOT NULL,   `TxAnnotatedText` longtext NOT NULL,   `TxAudioURI` varchar(200) DEFAULT NULL,   `TxSourceURI` varchar(1000) DEFAULT NULL,   PRIMARY KEY (`TxID`),   KEY `TxLgID` (`TxLgID`) ) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS texttags;
CREATE TABLE `texttags` (   `TtTxID` int(11) unsigned NOT NULL,   `TtT2ID` int(11) unsigned NOT NULL,   PRIMARY KEY (`TtTxID`,`TtT2ID`),   KEY `TtTxID` (`TtTxID`),   KEY `TtT2ID` (`TtT2ID`) ) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS words;
CREATE TABLE `words` (   `WoID` int(11) unsigned NOT NULL AUTO_INCREMENT,   `WoLgID` int(11) unsigned NOT NULL,   `WoText` varchar(250) NOT NULL,   `WoTextLC` varchar(250) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,   `WoStatus` tinyint(4) NOT NULL,   `WoTranslation` varchar(500) NOT NULL DEFAULT '*',   `WoRomanization` varchar(100) DEFAULT NULL,   `WoSentence` varchar(1000) DEFAULT NULL,   `WoCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,   `WoStatusChanged` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',   `WoTodayScore` double NOT NULL DEFAULT '0',   `WoTomorrowScore` double NOT NULL DEFAULT '0',   `WoRandom` double NOT NULL DEFAULT '0',   PRIMARY KEY (`WoID`),   UNIQUE KEY `WoLgIDTextLC` (`WoLgID`,`WoTextLC`),   KEY `WoLgID` (`WoLgID`),   KEY `WoStatus` (`WoStatus`),   KEY `WoTextLC` (`WoTextLC`),   KEY `WoTranslation` (`WoTranslation`(333)),   KEY `WoCreated` (`WoCreated`),   KEY `WoStatusChanged` (`WoStatusChanged`),   KEY `WoTodayScore` (`WoTodayScore`),   KEY `WoTomorrowScore` (`WoTomorrowScore`),   KEY `WoRandom` (`WoRandom`) ) ENGINE=MyISAM AUTO_INCREMENT=221 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS wordtags;
CREATE TABLE `wordtags` (   `WtWoID` int(11) unsigned NOT NULL,   `WtTgID` int(11) unsigned NOT NULL,   PRIMARY KEY (`WtWoID`,`WtTgID`),   KEY `WtTgID` (`WtTgID`),   KEY `WtWoID` (`WtWoID`) ) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS feedlinks;
CREATE TABLE `feedlinks` (`FlID` smallint(5) unsigned NOT NULL AUTO_INCREMENT,`FlTitle` varchar(200) NOT NULL,`FlLink` varchar(400) NOT NULL,`FlDescription` text NOT NULL,`FlDate` datetime NOT NULL,`FlAudio` varchar(200) NOT NULL,`FlText` longtext NOT NULL,`FlNfID` tinyint(3) unsigned NOT NULL,PRIMARY KEY (`FlID`),KEY `FlLink` (`FlLink`),KEY `FlDate` (`FlDate`),UNIQUE KEY `FlTitle` (`FlNfID`,`FlTitle`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
