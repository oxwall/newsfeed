<?php

$sql = array();
$sql[] = "ALTER TABLE `" . OW_DB_PREFIX . "newsfeed_follow` CHANGE `permission` `permission` VARCHAR(60) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'everybody';";
$sql[] = "ALTER TABLE `" . OW_DB_PREFIX . "newsfeed_follow` CHANGE `feedType` `feedType` VARCHAR(60) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;";
$sql[] = "ALTER TABLE `" . OW_DB_PREFIX . "newsfeed_follow` DROP INDEX `feedId`, ADD UNIQUE `feedId` (`feedId`, `userId`, `feedType`, `permission`) COMMENT ''";

foreach ( $sql as $query )
{
    try {
        Updater::getDbo()->query($query);
    }
    catch ( Exception $e ) { }
}