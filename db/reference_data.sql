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
-- Data that the app must have in order to function.
-- This file must be idempotent (re-runnable).
-- --------------------------------------------------------------

-- ----------------------
-- Settings

CREATE TEMPORARY TABLE temp_settings (`StKey` varchar(40) NOT NULL, `StValue` varchar(40) DEFAULT NULL);

INSERT INTO temp_settings VALUES ('dbversion','v002000000');
INSERT INTO temp_settings VALUES ('showallwords','0');
INSERT INTO temp_settings VALUES ('currentlanguage', NULL);
INSERT INTO temp_settings VALUES ('lastscorecalc','2020-10-03');
INSERT INTO temp_settings VALUES ('set-text-h-frameheight-no-audio','140');
INSERT INTO temp_settings VALUES ('set-text-h-frameheight-with-audio','200');
INSERT INTO temp_settings VALUES ('set-text-l-framewidth-percent','50');
INSERT INTO temp_settings VALUES ('set-text-r-frameheight-percent','50');
INSERT INTO temp_settings VALUES ('set-test-h-frameheight','140');
INSERT INTO temp_settings VALUES ('set-test-l-framewidth-percent','50');
INSERT INTO temp_settings VALUES ('set-test-r-frameheight-percent','50');
INSERT INTO temp_settings VALUES ('set-test-main-frame-waiting-time','0');
INSERT INTO temp_settings VALUES ('set-test-edit-frame-waiting-time','500');
INSERT INTO temp_settings VALUES ('set-test-sentence-count','1');
INSERT INTO temp_settings VALUES ('set-term-sentence-count','1');
INSERT INTO temp_settings VALUES ('set-archivedtexts-per-page','100');
INSERT INTO temp_settings VALUES ('set-texts-per-page','10');
INSERT INTO temp_settings VALUES ('set-terms-per-page','100');
INSERT INTO temp_settings VALUES ('set-tags-per-page','100');
INSERT INTO temp_settings VALUES ('set-show-text-word-counts','1');
INSERT INTO temp_settings VALUES ('set-term-translation-delimiters','/;|');
INSERT INTO temp_settings VALUES ('set-mobile-display-mode','0');
INSERT INTO temp_settings VALUES ('set-similar-terms-count','0');
INSERT INTO temp_settings VALUES ('currenttext', NULL);

-- load missing records:
insert into settings (StKey, StValue) select StKey, StValue from temp_settings where temp_settings.StKey not in (select StKey from Settings);

-- don't update existing records, as users may have set their own settings.

drop table temp_settings;


-- ----------------------
-- Tags

CREATE TEMPORARY TABLE temp_tags (`TgText` varchar(20) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL);

INSERT INTO temp_tags VALUES ('masc');
INSERT INTO temp_tags VALUES ('fem');
INSERT INTO temp_tags VALUES ('1p-sg');
INSERT INTO temp_tags VALUES ('2p-sg');
INSERT INTO temp_tags VALUES ('verb');
INSERT INTO temp_tags VALUES ('3p-sg');
INSERT INTO temp_tags VALUES ('1p-pl');
INSERT INTO temp_tags VALUES ('2p-pl');
INSERT INTO temp_tags VALUES ('3p-pl');
INSERT INTO temp_tags VALUES ('adj');
INSERT INTO temp_tags VALUES ('adv');
INSERT INTO temp_tags VALUES ('interj');
INSERT INTO temp_tags VALUES ('conj');
INSERT INTO temp_tags VALUES ('num');
INSERT INTO temp_tags VALUES ('infinitive');
INSERT INTO temp_tags VALUES ('noun');
INSERT INTO temp_tags VALUES ('pronoun');
INSERT INTO temp_tags VALUES ('informal');
INSERT INTO temp_tags VALUES ('colloc');
INSERT INTO temp_tags VALUES ('pres');
INSERT INTO temp_tags VALUES ('impf');
INSERT INTO temp_tags VALUES ('subj');
INSERT INTO temp_tags VALUES ('pastpart');
INSERT INTO temp_tags VALUES ('prespart');
INSERT INTO temp_tags VALUES ('name');
INSERT INTO temp_tags VALUES ('greeting');

-- load missing records:
insert into tags (TgText) select TgText from temp_tags where temp_tags.TgText not in (select TgText from tags);

-- don't update existing records, as users may have set their own comments.

drop table temp_tags;
