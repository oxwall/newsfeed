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

require_once dirname(__FILE__) . DS . 'lib.php';

$dbo = Updater::getDbo();

$source = new NEWSFEED_TableList('source', $dbo);

$source->addTable('action');
$source->addTable('action_feed');
$source->changePrefix('newsfeed_tmp_');

$dist = new NEWSFEED_TableList('dist', $dbo);

$dist->createTable('action', "CREATE TABLE `%action%` (
  `id` int(11) NOT NULL auto_increment,
  `entityId` int(11) NOT NULL,
  `entityType` varchar(100) NOT NULL,
  `pluginKey` varchar(100) NOT NULL,
  `data` longtext NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `entity` (`entityType`,`entityId`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");

$dist->createTable('action_feed', "CREATE TABLE `%action_feed%` (
  `id` int(11) NOT NULL auto_increment,
  `feedType` varchar(100) NOT NULL,
  `feedId` int(11) NOT NULL,
  `activityId` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `feedId` (`feedType`,`feedId`,`activityId`),
  KEY `actionId` (`activityId`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");

$dist->createTable('activity', "CREATE TABLE `%activity%` (
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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");

$dist->createTable('cron_command', "CREATE TABLE IF NOT EXISTS `%cron_command%` (
  `id` int(11) NOT NULL auto_increment,
  `command` varchar(100) NOT NULL,
  `data` text NOT NULL,
  `processData` text NOT NULL,
  `timeStamp` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");

$dist->addTable('follow');
$dist->query("ALTER TABLE `%follow%` ADD `permission` VARCHAR( 255 ) NOT NULL DEFAULT 'everybody'");

$r = $source->mysqlQuery("SELECT * FROM `%action%`");
while ( $sAction = mysql_fetch_assoc($r) )
{
    $dActionId = $dist->insertRow('action', array(
        'entityType' => $sAction['entityType'],
        'entityId' => $sAction['entityId'],
        'pluginKey' => $sAction['pluginKey'],
        'data' => $sAction['data']
    ));

    $dActivityCreateId = $dist->insertRow('activity', array(
        'actionId' => $dActionId,
        'activityType' => 'create',
        'activityId' => $sAction['userId'],
        'userId' => $sAction['userId'],
        'data' => json_encode(array()),
        'userId' => $sAction['userId'],
        'timeStamp' => $sAction['createTime'],
        'privacy' => 'everybody',
        'visibility' => $sAction['privacy'],
        'status' => $sAction['status']
    ));

    $dActivitySubscribeId = $dist->insertRow('activity', array(
        'actionId' => $dActionId,
        'activityType' => 'subscribe',
        'activityId' => $sAction['userId'],
        'userId' => $sAction['userId'],
        'data' => json_encode(array()),
        'userId' => $sAction['userId'],
        'timeStamp' => $sAction['updateTime'],
        'privacy' => 'everybody',
        'visibility' => $sAction['privacy'],
        'status' => $sAction['status']
    ));

    $actionFeeds = $source->queryForList('SELECT * FROM `%action_feed%` WHERE actionId=:a', array('a' => $sAction['id']));

    foreach ( $actionFeeds as $feed )
    {
        $dist->insertRow('action_feed', array(
            'feedType' => $feed['feedType'],
            'feedId' => $feed['feedId'],
            'activityId' => $dActivityCreateId
        ));

        $dist->insertRow('action_feed', array(
            'feedType' => $feed['feedType'],
            'feedId' => $feed['feedId'],
            'activityId' => $dActivitySubscribeId
        ));
    }
}

$dist->insertRow('cron_command', array(
    'command' => 'update3500CronJob',
    'data' => json_encode(array()),
    'processData' => json_encode(array()),
    'timeStamp' => time()
));

$source->dropTables();

Updater::getConfigService()->addConfig('newsfeed', 'disabled_action_types', '');

$updateDir = dirname(__FILE__) . DS;
Updater::getLanguageService()->importPrefixFromZip($updateDir . 'langs.zip', 'newsfeed');

$newsfeed = OW::getPluginManager()->getPlugin('newsfeed');

$staticDir = OW_DIR_STATIC_PLUGIN . $newsfeed->getModuleName() . DS;
$staticJsDir = $staticDir  . 'js' . DS;

@copy($newsfeed->getStaticJsDir() . 'newsfeed.js', $staticJsDir . 'newsfeed.js');