<?php

$authorization = OW::getAuthorization();
$groupName = 'newsfeed';
$authorization->addAction($groupName, 'allow_status_update');

$updateDir = dirname(__FILE__) . DS;
Updater::getLanguageService()->importPrefixFromZip($updateDir . 'langs.zip', 'newsfeed');

