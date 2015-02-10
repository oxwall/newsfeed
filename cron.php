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

/**
 * Newsfeed cron job.
 *
 * @author Kambalin Sergey <greyexpert@gmail.com>
 * @package ow.ow_plugins.newsfeed
 * @since 1.0
 */
class NEWSFEED_Cron extends OW_Cron
{
    /**
     *
     * @var NEWSFEED_BOL_Service
     */
    private $service;
    private $commands = array();

    public function __construct()
    {
        parent::__construct();

        $this->addJob('deleteActionSet', 60); // 1 hour
        $this->addJob('deleteExpired', 7 * 3600 * 24); // once a week

        $this->service = NEWSFEED_BOL_Service::getInstance();

        $this->commands['changePrivacy'] = 'changePrivacy';
        $this->commands['updateFollowPermission'] = 'updateFollowPermission';
        $this->commands['update3500CronJob'] = 'update3500CronJob';
        $this->commands['deleteActions'] = 'deleteActions';
    }

    private function getActionInactivePeriod()
    {
        return 1;
    }

    public function run()
    {
        $commands = $this->service->findCronCommands();
        $completedCommands = array();

        foreach ( $commands as $commandDto )
        {
            /* @var $commandDto NEWSFEED_BOL_CronCommand */
            $command = trim($commandDto->command);

            if ( empty($this->commands[$command]) )
            {
                continue;
            }

            $method = $this->commands[$command];

            $data = json_decode($commandDto->data, true);
            $processData = json_decode($commandDto->processData, true);
            $r = $this->$method($data, $processData);

            if ( $r === true )
            {
                $completedCommands[] = $commandDto->id;
            }
            else
            {
                $commandDto->processData = json_encode($r);
                $this->service->saveCronCommand($commandDto);
            }
        }

        if ( !empty($completedCommands) )
        {
            $this->service->deleteCronCommands($completedCommands);
        }
    }

    // Commands

    private function deleteActions( $data, $processData )
    {
        $actionsCount = 10;

        $actionIds = empty($data['actionIds']) ? array() : $data['actionIds'];
        $processData = empty($processData) ? array() : $processData;

        $currentActions = array_diff($actionIds, $processData);
        $currentActions = array_values($currentActions);

        if ( empty($currentActions) )
        {
            return true;
        }

        $iterationsCount = count($currentActions);
        $iterationsCount = $iterationsCount > $actionsCount ? $actionsCount : $iterationsCount;

        for ( $i = 0; $i < $iterationsCount; $i++ )
        {
            $this->service->removeActionById($currentActions[$i]);
            $processData[] = $currentActions[$i];
        }

        return $processData;
    }

    private function changePrivacy( $data, $processData )
    {
        $userId = (int) $data['userId'];
        $privacyList = $data['privacy'];

        foreach ( $privacyList as $privacy => $activityKeys )
        {
            foreach ( $activityKeys as & $key )
            {
                $key = $userId . ':' . $key;
            }

            $this->service->setActivityPrivacyByKeyList($activityKeys, $privacy);
        }

        return true;
    }

    private function updateFollowPermission( $data, $processData )
    {
        $event = new BASE_CLASS_EventCollector('feed.collect_follow');
        OW::getEventManager()->trigger($event);

        foreach ( $event->getData() as $follow )
        {
            $follow['permission'] = empty($follow['permission']) ? NEWSFEED_BOL_Service::PRIVACY_EVERYBODY : $follow['permission'];

            $this->service->addFollow((int) $follow['userId'], trim($follow['feedType']), (int) $follow['feedId'], $follow['permission']);
        }

        return true;
    }

    private function update3500CronJob( $data, $processData )
    {
        $friends = OW::getEventManager()->call('plugin.friends.find_all_active_friendships');

        foreach ( $friends as $f )
        {
            $this->service->addFollow((int) $f->userId, 'user', (int) $f->friendId, 'friends_only');
            $this->service->addFollow((int) $f->friendId, 'user', (int) $f->userId, 'friends_only');
        }

        return true;
    }

    public function deleteActionSet()
    {
       NEWSFEED_BOL_Service::getInstance()->deleteActionSetByTimestamp( time() - (60 * 60) );
    }

    public function deleteExpired()
    {
        $this->service->markExpiredForDelete();
    }

}