-- Since 2.9.0-fork, fixes the missing auto incrementation of texts

ALTER TABLE `archivedtexts` 
MODIFY COLUMN `AtID` SMALLINT(5) unsigned NOT NULL AUTO_INCREMENT;
