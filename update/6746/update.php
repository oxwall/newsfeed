<?php

$updateDir = dirname(__FILE__) . DS;

$sql = array();

$sql[] = "ALTER TABLE  `" . OW_DB_PREFIX . "newsfeed_action` ADD  `format` VARCHAR( 255 ) NULL DEFAULT NULL";
$sql[] = "ALTER TABLE  `" . OW_DB_PREFIX . "newsfeed_action` CHANGE `entityId` `entityId` VARCHAR( 100 ) NOT NULL";

foreach ( $sql as $query )
{
    try {
        Updater::getDbo()->query($query);
    }
    catch ( Exception $e ) { }
}