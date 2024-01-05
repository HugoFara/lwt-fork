-- Migrations already present in the original LWT?

ALTER TABLE words 
    ADD WoTodayScore DOUBLE NOT NULL DEFAULT 0, 
    ADD WoTomorrowScore DOUBLE NOT NULL DEFAULT 0, 
    ADD WoRandom DOUBLE NOT NULL DEFAULT 0;

ALTER TABLE words 
    ADD WoWordCount tinyint(3) unsigned NOT NULL DEFAULT 0 AFTER WoSentence;

ALTER TABLE words 
    ADD INDEX WoTodayScore (WoTodayScore), 
    ADD INDEX WoTomorrowScore (WoTomorrowScore), 
    ADD INDEX WoRandom (WoRandom);

ALTER TABLE languages 
    ADD LgRightToLeft tinyint(1) UNSIGNED NOT NULL DEFAULT 0;

ALTER TABLE texts 
    ADD TxAnnotatedText LONGTEXT NOT NULL AFTER TxText;

ALTER TABLE archivedtexts 
    ADD AtAnnotatedText LONGTEXT NOT NULL AFTER AtText;

ALTER TABLE tags 
    CHANGE TgComment TgComment VARCHAR(200) 
    CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE tags2 
    CHANGE T2Comment T2Comment VARCHAR(200) 
    CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE languages 
    CHANGE LgGoogleTTSURI LgExportTemplate VARCHAR(1000) 
    CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE texts 
    ADD TxSourceURI VARCHAR(1000) 
    CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE archivedtexts 
    ADD AtSourceURI VARCHAR(1000) 
    CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE texts 
    ADD TxPosition smallint(5) NOT NULL DEFAULT 0;

ALTER TABLE texts 
    ADD TxAudioPosition float NOT NULL DEFAULT 0;

ALTER TABLE `wordtags` 
    DROP INDEX WtWoID;

ALTER TABLE `texttags` 
    DROP INDEX TtTxID;

ALTER TABLE `archtexttags` 
    DROP INDEX AgAtID;
    