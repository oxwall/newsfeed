<?php

/**
 * This software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is
 * licensed under The BSD license.

 * ---
 * Copyright (c) 2011, Oxwall Foundation
 * All rights reserved.

 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the
 * following conditions are met:
 *
 *  - Redistributions of source code must retain the above copyright notice, this list of conditions and
 *  the following disclaimer.
 *
 *  - Redistributions in binary form must reproduce the above copyright notice, this list of conditions and
 *  the following disclaimer in the documentation and/or other materials provided with the distribution.
 *
 *  - Neither the name of the Oxwall Foundation nor the names of its contributors may be used to endorse or promote products
 *  derived from this software without specific prior written permission.

 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED
 * AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
$plugin = OW::getPluginManager()->getPlugin('newsfeed');

$dbPrefix = OW_DB_PREFIX;

$sql[] ="
CREATE TABLE IF NOT EXISTS `{$dbPrefix}newsfeed_action` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entityId` VARCHAR(100) NOT NULL,
  `entityType` varchar(100) NOT NULL,
  `pluginKey` varchar(100) NOT NULL,
  `data` longtext NOT NULL,
  `format` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `entity` (`entityType`,`entityId`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";

$sql[] ="
CREATE TABLE `{$dbPrefix}newsfeed_action_feed` (
  `id` int(11) NOT NULL auto_increment,
  `feedType` varchar(100) NOT NULL,
  `feedId` int(11) NOT NULL,
  `activityId` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `feedId` (`feedType`,`feedId`,`activityId`),
  KEY `actionId` (`activityId`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";

$sql[] ="
CREATE TABLE `{$dbPrefix}newsfeed_activity` (
  `id` int(11) NOT NULL auto_increment,
  `activityType` varchar(100) NOT NULL,
  `activityId` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `data` text NOT NULL,
  `actionId` int(11) NOT NULL,
  `timeStamp` int(11) NOT NULL,
  `privacy` varchar(100) NOT NULL,
  `visibility` int(11) NOT NULL,
  `status` varchar(100) NOT NULL default 'active',
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `activityId` (`activityId`,`activityType`,`actionId`),
  KEY `actionId` (`actionId`),
  KEY `userId` (`userId`),
  KEY `activityType` ( `activityType`),
  KEY `timeStamp` (`timeStamp`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";

$sql[] ="
CREATE TABLE IF NOT EXISTS `{$dbPrefix}newsfeed_follow` (
  `id` int(11) NOT NULL,
  `feedId` int(11) NOT NULL,
  `feedType` varchar(60) NOT NULL,
  `userId` int(11) NOT NULL,
  `permission` varchar(60) NOT NULL DEFAULT 'everybody',
  `followTime` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

$sql[] ="ALTER TABLE `{$dbPrefix}newsfeed_follow` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `feedId` (`feedId`,`userId`,`feedType`,`permission`), ADD KEY `userId` (`userId`);";
$sql[] ="ALTER TABLE `{$dbPrefix}newsfeed_follow` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;";

$sql[] ="
CREATE TABLE IF NOT EXISTS `{$dbPrefix}newsfeed_cron_command` (
  `id` int(11) NOT NULL auto_increment,
  `command` varchar(100) NOT NULL,
  `data` text NOT NULL,
  `processData` text NOT NULL,
  `timeStamp` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";

$sql[] ="
CREATE TABLE IF NOT EXISTS `{$dbPrefix}newsfeed_like` (
  `id` int(11) NOT NULL auto_increment,
  `entityType` varchar(100) NOT NULL,
  `entityId` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `timeStamp` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `entityType` (`entityType`,`entityId`,`userId`),
  KEY `userId` (`userId`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";

$sql[] ="
CREATE TABLE IF NOT EXISTS `{$dbPrefix}newsfeed_status` (
  `id` int(11) NOT NULL auto_increment,
  `feedType` varchar(100) NOT NULL,
  `feedId` int(11) NOT NULL,
  `timeStamp` int(11) NOT NULL,
  `status` text NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `feedType` (`feedType`,`feedId`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";

$sql[] ="
CREATE TABLE IF NOT EXISTS `{$dbPrefix}newsfeed_action_set` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `actionId` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `timestamp` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `actionId` (`actionId`,`userId`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0 ;";

foreach ( $sql as $query )
{
    OW::getDbo()->query($query);
}

BOL_LanguageService::getInstance()->importPrefixFromZip($plugin->getRootDir() . 'langs.zip', 'newsfeed');

OW::getConfig()->addConfig('newsfeed', 'allow_likes', 1, 'Allow Likes');
OW::getConfig()->addConfig('newsfeed', 'allow_comments', 1, 'Allow Comments');

OW::getConfig()->addConfig('newsfeed', 'comments_count', 3, 'Count of comments');
OW::getConfig()->addConfig('newsfeed', 'index_status_enabled', 1, 'Index status is enabled');
OW::getConfig()->addConfig('newsfeed', 'features_expanded', 1, 'Comments and likes box is expanded');
OW::getConfig()->addConfig('newsfeed', 'disabled_action_types', '');

OW::getPluginManager()->addPluginSettingsRouteName('newsfeed', 'newsfeed_admin_settings');

$authorization = OW::getAuthorization();
$groupName = 'newsfeed';
$authorization->addGroup($groupName);
$authorization->addAction($groupName, 'add_comment');
$authorization->addAction($groupName, 'allow_status_update');

$event = new BASE_CLASS_EventCollector('feed.collect_follow');
OW::getEventManager()->trigger($event);

foreach ( $event->getData() as $follow )
{
    $dbTbl = OW_DB_PREFIX . 'newsfeed_follow';
    $follow['permission'] = empty($follow['permission']) ? 'everybody' : $follow['permission'];

    $query = "REPLACE INTO $dbTbl SET feedType=:ft, feedId=:f, userId=:u, followTime=:t, permission=:p";
    OW::getDbo()->query($query, array(
        'ft' => trim($follow['feedType']),
        'u' => (int) $follow['feedId'],
        'f' => (int) $follow['userId'],
        'p' => $follow['permission'],
        't' => time()
    ));
}

$preference = BOL_PreferenceService::getInstance()->findPreference('newsfeed_generate_action_set_timestamp');

if ( empty($preference) )
{
    $preference = new BOL_Preference();
}

$preference->key = 'newsfeed_generate_action_set_timestamp';
$preference->sectionName = 'general';
$preference->defaultValue = 0;
$preference->sortOrder = 10000;

BOL_PreferenceService::getInstance()->savePreference($preference);