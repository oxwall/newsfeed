<?php

$updateDir = dirname(__FILE__) . DS;
Updater::getLanguageService()->importPrefixFromZip($updateDir . 'langs.zip', 'newsfeed');

OW::getNavigation()->addMenuItem(OW_Navigation::MOBILE_TOP, 'newsfeed_view_feed', 'newsfeed', 'newsfeed_feed', OW_Navigation::VISIBLE_FOR_ALL);