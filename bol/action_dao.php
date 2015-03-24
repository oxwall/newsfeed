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
 * Data Access Object for `newsfeed_action` table.
 *
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package ow_plugins.newsfeed.bol
 * @since 1.0
 */
class NEWSFEED_BOL_ActionDao extends OW_BaseDao
{
    const CACHE_TIMESTAMP_PREFERENCE = 'newsfeed_generate_action_set_timestamp';
    const CACHE_TIMEOUT = 300; // 5 min
    const CACHE_LIFETIME = 86400;
    const CACHE_TAG_INDEX = 'newsfeed_index';
    const CACHE_TAG_USER = 'newsfeed_user';
    const CACHE_TAG_USER_PREFIX = 'newsfeed_user_';
    const CACHE_TAG_FEED = 'newsfeed_feed';
    const CACHE_TAG_FEED_PREFIX = 'newsfeed_feed_';
    const CACHE_TAG_ALL = 'newsfeed_all';

    /**
     * Singleton instance.
     *
     * @var NEWSFEED_BOL_ActionDao
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return NEWSFEED_BOL_ActionDao
     */
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    /**
     * @see OW_BaseDao::getDtoClassName()
     *
     */
    public function getDtoClassName()
    {
        return 'NEWSFEED_BOL_Action';
    }

    /**
     * @see OW_BaseDao::getTableName()
     *
     */
    public function getTableName()
    {
        return OW_DB_PREFIX . 'newsfeed_action';
    }

    /**
     *
     * @param $entityType
     * @param $entityId
     * @return NEWSFEED_BOL_Action
     */
    public function findAction( $entityType, $entityId )
    {
        $example = new OW_Example();
        $example->andFieldEqual('entityType', $entityType);
        $example->andFieldEqual('entityId', $entityId);

        return $this->findObjectByExample($example);
    }

    public function findByPluginKey( $pluginKey )
    {
        $example = new OW_Example();
        $example->andFieldEqual('pluginKey', $pluginKey);

        return $this->findListByExample($example);
    }

    public function setStatusByPluginKey( $pluginKey, $status )
    {
        $activityDao = NEWSFEED_BOL_ActivityDao::getInstance();

        $query = "UPDATE " . $this->getTableName() . " action
            INNER JOIN " . $activityDao->getTableName() . " activity ON action.id = activity.actionId
            SET activity.`status`=:s
            WHERE activity.activityType=:ca AND action.pluginKey=:pk";

        $this->dbo->query($query, array(
            's' => $status,
            'pk' => $pluginKey,
            'ca' => NEWSFEED_BOL_Service::SYSTEM_ACTIVITY_CREATE
        ));
    }

    public function findByFeed( $feedType, $feedId, $limit = null, $startTime = null, $formats = null )
    {
        $actionFeedDao = NEWSFEED_BOL_ActionFeedDao::getInstance();
        $activityDao = NEWSFEED_BOL_ActivityDao::getInstance();

        $limitStr = '';
        if ( !empty($limit) )
        {
            $limitStr = "LIMIT " . intval($limit[0]) . ", " . intval($limit[1]);
        }

        $cacheStartTime = OW::getCacheManager()->load('newsfeed.feed_cache_time_' . $feedType . $feedId);
        if ( $cacheStartTime === null )
        {
            OW::getCacheManager()->save($startTime, 'newsfeed.feed_cache_time_' . $feedType . $feedId, array(
                self::CACHE_TAG_ALL,
                self::CACHE_TAG_FEED,
                self::CACHE_TAG_FEED_PREFIX . $feedType . $feedId
            ), self::CACHE_LIFETIME);
        }
        else
        {
            $startTime = $cacheStartTime;
        }

        $queryParts = BOL_UserDao::getInstance()->getUserQueryFilter("cactivity", "userId", array(
            "method" => "NEWSFEED_BOL_ActionDao::findByFeed"
        ));
        
        if ( $formats !== null )
        {
            $queryParts["where"] .= " AND action.format IN ( '" . implode("','", $formats) . "' )";
        }
        
        $query = 'SELECT action.id FROM ' . $this->getTableName() . ' action
            INNER JOIN ' . $activityDao->getTableName() . ' activity ON action.id = activity.actionId
            INNER JOIN ' . $activityDao->getTableName() . ' cactivity ON action.id = cactivity.actionId
            ' . $queryParts["join"] . '
            INNER JOIN ' . $actionFeedDao->getTableName() . ' action_feed ON activity.id=action_feed.activityId

            WHERE ' . $queryParts["where"] . '
                AND activity.status=:s
                AND activity.timeStamp<:st
                AND activity.privacy=:peb
                AND action_feed.feedType=:ft
                AND action_feed.feedId=:fi
                AND activity.visibility & :v

                AND cactivity.status=:s
                AND cactivity.activityType=:ac
                AND cactivity.privacy=:peb
                AND cactivity.visibility & :v

            GROUP BY action.id ORDER BY MAX(activity.timeStamp) DESC ' . $limitStr;

        $idList = $this->dbo->queryForColumnList($query, array(
            'ft' => $feedType,
            'fi' => $feedId,
            's' => NEWSFEED_BOL_Service::ACTION_STATUS_ACTIVE,
            'v' => NEWSFEED_BOL_Service::VISIBILITY_FEED,
            'st' => empty($startTime) ? time() : $startTime,
            'peb' => NEWSFEED_BOL_Service::PRIVACY_EVERYBODY,
            'ac' => NEWSFEED_BOL_Service::SYSTEM_ACTIVITY_CREATE
        ), self::CACHE_LIFETIME, array(
            self::CACHE_TAG_ALL,
            self::CACHE_TAG_FEED,
            self::CACHE_TAG_FEED_PREFIX . $feedType . $feedId
        ));

        return $this->findOrderedListByIdList($idList);
    }

    public function findCountByFeed( $feedType, $feedId, $startTime = null, $formats = null )
    {
        $actionFeedDao = NEWSFEED_BOL_ActionFeedDao::getInstance();
        $activityDao = NEWSFEED_BOL_ActivityDao::getInstance();

        $queryParts = BOL_UserDao::getInstance()->getUserQueryFilter("activity", "userId", array(
            "method" => "NEWSFEED_BOL_ActionDao::findCountByFeed"
        ));
        
        if ( $formats !== null )
        {
            $queryParts["where"] .= " AND action.format IN ( '" . implode("','", $formats) . "' )";
        }
        
        /*$query = 'SELECT COUNT(DISTINCT action.id) FROM ' . $this->getTableName() . ' action
            INNER JOIN ' . $activityDao->getTableName() . ' activity ON action.id = activity.actionId
            INNER JOIN ' . $actionFeedDao->getTableName() . ' action_feed ON activity.id=action_feed.activityId
            ' . $queryParts["join"] . '

            LEFT JOIN ' . $activityDao->getTableName() . ' pactivity ON activity.actionId = pactivity.actionId
                AND (pactivity.status=:s AND pactivity.activityType=:ac AND pactivity.privacy!=:peb AND pactivity.visibility & :v)

            WHERE ' . $queryParts["where"] . ' AND pactivity.id IS NULL AND activity.status=:s AND activity.activityType=:ac AND activity.privacy=:peb AND action_feed.feedType=:ft AND action_feed.feedId=:fi AND activity.visibility & :v';
         * */
        
        $query = 'SELECT COUNT(DISTINCT action.id) FROM ' . $this->getTableName() . ' action
            INNER JOIN ' . $activityDao->getTableName() . ' activity ON action.id = activity.actionId
            INNER JOIN ' . $activityDao->getTableName() . ' cactivity ON action.id = cactivity.actionId
            ' . $queryParts["join"] . '
            INNER JOIN ' . $actionFeedDao->getTableName() . ' action_feed ON activity.id=action_feed.activityId

            WHERE ' . $queryParts["where"] . '
                AND activity.status=:s
                AND activity.timeStamp<:st
                AND activity.privacy=:peb
                AND action_feed.feedType=:ft
                AND action_feed.feedId=:fi
                AND activity.visibility & :v

                AND cactivity.status=:s
                AND cactivity.activityType=:ac
                AND cactivity.privacy=:peb
                AND cactivity.visibility & :v';

        return (int) $this->dbo->queryForColumn($query, array(
            'ft' => $feedType,
            'fi' => $feedId,
            's' => NEWSFEED_BOL_Service::ACTION_STATUS_ACTIVE,
            'v' => NEWSFEED_BOL_Service::VISIBILITY_FEED,
            'peb' => NEWSFEED_BOL_Service::PRIVACY_EVERYBODY,
            'ac' => NEWSFEED_BOL_Service::SYSTEM_ACTIVITY_CREATE,
            'st' => empty($startTime) ? time() : $startTime
        ), self::CACHE_LIFETIME, array(
            self::CACHE_TAG_ALL,
            self::CACHE_TAG_FEED,
            self::CACHE_TAG_FEED_PREFIX . $feedType . $feedId
        ));
    }

    public function findByUser( $userId, $limit = null, $startTime = null, $formats = null )
    {
        $cacheKey = md5('user_feed' . $userId . ( empty($limit) ? '' : implode('', $limit) ) );

        $cachedIdList = OW::getCacheManager()->load($cacheKey);

        if ( $cachedIdList !== null )
        {
            $idList = json_decode($cachedIdList, true);

            return $this->findOrderedListByIdList($idList);
        }

        $followDao = NEWSFEED_BOL_FollowDao::getInstance();
        $actionFeedDao = NEWSFEED_BOL_ActionFeedDao::getInstance();
        $activityDao = NEWSFEED_BOL_ActivityDao::getInstance();
        $actionSetDao = NEWSFEED_BOL_ActionSetDao::getInstance();

        $limitStr = '';
        if ( !empty($limit) )
        {
            $limitStr = "LIMIT " . intval($limit[0]) . ", " . intval($limit[1]);
        }

        $actionSetDao->deleteActionSetUserId($userId);
        $actionSetDao->generateActionSet($userId, $startTime);

        $queryParts = BOL_UserDao::getInstance()->getUserQueryFilter("cactivity", "userId", array(
            "method" => "NEWSFEED_BOL_ActionDao::findByUser"
        ));
        
        if ( $formats !== null )
        {
            $queryParts["where"] .= " AND action.format IN ( '" . implode("','", $formats) . "' )";
        }
        
        $query = ' SELECT  b.`id` FROM
            ( SELECT  action.`id`, action.`entityId`, action.`entityType`, action.`pluginKey`, action.`data`, activity.timeStamp FROM ' . $this->getTableName() . ' action
            INNER JOIN ' . $activityDao->getTableName() . ' activity ON action.id = activity.actionId
            INNER JOIN `' . $actionSetDao->getTableName() . '` cactivity ON action.id = cactivity.actionId
            ' . $queryParts["join"] . '
            INNER JOIN ' . $actionFeedDao->getTableName() . ' action_feed ON activity.id=action_feed.activityId
            LEFT JOIN ' . $followDao->getTableName() . ' follow ON action_feed.feedId = follow.feedId AND action_feed.feedType = follow.feedType
            WHERE ' . $queryParts["where"] . ' AND cactivity.userId = :u AND activity.status=:s AND activity.timeStamp<:st AND (
                    ( follow.userId=:u AND activity.visibility & :vf ) )

            UNION

            SELECT action.`id`, action.`entityId`, action.`entityType`, action.`pluginKey`, action.`data`, activity.timeStamp FROM ' . $this->getTableName() . ' action
                INNER JOIN ' . $activityDao->getTableName() . ' activity ON action.id = activity.actionId
                INNER JOIN `' . $actionSetDao->getTableName() . '` cactivity ON action.id = cactivity.actionId
                ' . $queryParts["join"] . '
                WHERE ' . $queryParts["where"] . ' AND cactivity.userId = :u AND activity.status=:s AND activity.timeStamp<:st AND (
                        ( activity.userId=:u AND activity.visibility & :va ) )

            UNION

            SELECT action.`id`, action.`entityId`, action.`entityType`, action.`pluginKey`, action.`data`, activity.timeStamp FROM ' . $this->getTableName() . ' action
                INNER JOIN ' . $activityDao->getTableName() . ' activity ON action.id = activity.actionId
                INNER JOIN `' . $actionSetDao->getTableName() . '` cactivity ON action.id = cactivity.actionId
                ' . $queryParts["join"] . '
                INNER JOIN ' . $actionFeedDao->getTableName() . ' action_feed ON activity.id=action_feed.activityId
                WHERE ' . $queryParts["where"] . ' AND cactivity.userId = :u AND activity.status=:s AND activity.timeStamp<:st
                AND ( ( action_feed.feedId=:u AND action_feed.feedType="user" AND activity.visibility & :vfeed ) )

            UNION

            SELECT action.`id`, action.`entityId`, action.`entityType`, action.`pluginKey`, action.`data`, activity.timeStamp FROM ' . $this->getTableName() . ' action
                INNER JOIN ' . $activityDao->getTableName() . ' activity ON action.id = activity.actionId
                INNER JOIN `' . $actionSetDao->getTableName() . '` cactivity ON action.id = cactivity.actionId
                ' . $queryParts["join"] . '
                INNER JOIN ' . $activityDao->getTableName() . ' subscribe ON activity.actionId=subscribe.actionId and subscribe.activityType=:as AND subscribe.userId=:u
                WHERE ' . $queryParts["where"] . ' AND cactivity.userId = :u AND activity.status=:s AND activity.timeStamp<:st

                ) b

            GROUP BY b.`id` ORDER BY MAX(b.timeStamp) DESC ' . $limitStr;

        $idList = array_unique($this->dbo->queryForColumnList($query, array(
            'u' => $userId,
            'va' => NEWSFEED_BOL_Service::VISIBILITY_AUTHOR,
            'vf' => NEWSFEED_BOL_Service::VISIBILITY_FOLLOW,
            'vfeed' => NEWSFEED_BOL_Service::VISIBILITY_FEED,
            's' => NEWSFEED_BOL_Service::ACTION_STATUS_ACTIVE,
            'st' => empty($startTime) ? time() : $startTime,
            'peb' => NEWSFEED_BOL_Service::PRIVACY_EVERYBODY,
            'ac' => NEWSFEED_BOL_Service::SYSTEM_ACTIVITY_CREATE,
            'as' => NEWSFEED_BOL_Service::SYSTEM_ACTIVITY_SUBSCRIBE
        )));
        
        if ( $limit[0] == 0 )
        {
            $cacheLifeTime = self::CACHE_LIFETIME;
            $cacheTags = array(
                self::CACHE_TAG_ALL,
                self::CACHE_TAG_USER,
                self::CACHE_TAG_USER_PREFIX . $userId
            );

            OW::getCacheManager()->save(json_encode($idList), $cacheKey, $cacheTags, $cacheLifeTime);
        }

        return $this->findOrderedListByIdList($idList);
    }

    public function findCountByUser( $userId, $startTime, $formats = null )
    {
        $cacheKey = md5('user_feed_count' . $userId );
        $cachedCount = OW::getCacheManager()->load($cacheKey);

        if ( $cachedCount !== null )
        {
            return $cachedCount;
        }

        $followDao = NEWSFEED_BOL_FollowDao::getInstance();
        $actionFeedDao = NEWSFEED_BOL_ActionFeedDao::getInstance();
        $activityDao = NEWSFEED_BOL_ActivityDao::getInstance();
        $actionSetDao = NEWSFEED_BOL_ActionSetDao::getInstance();

        /*$actionSetDao->deleteActionSetUserId($userId);
        $actionSetDao->generateActionSet($userId, $startTime);*/

        $queryParts = BOL_UserDao::getInstance()->getUserQueryFilter("cactivity", "userId", array(
            "method" => "NEWSFEED_BOL_ActionDao::findCountByUser"
        ));
        
        if ( $formats !== null )
        {
            $queryParts["where"] .= " AND action.format IN ( '" . implode("','", $formats) . "' )";
        }
        
        $query = 'SELECT COUNT(DISTINCT `id`) FROM ( SELECT action.`id` FROM ' . $this->getTableName() . ' action
            INNER JOIN ' . $activityDao->getTableName() . ' activity ON action.id = activity.actionId
            INNER JOIN `' . $actionSetDao->getTableName() . '` cactivity ON action.id = cactivity.actionId
            ' . $queryParts["join"] . '
            INNER JOIN ' . $actionFeedDao->getTableName() . ' action_feed ON activity.id=action_feed.activityId
            LEFT JOIN ' . $followDao->getTableName() . ' follow ON action_feed.feedId = follow.feedId AND action_feed.feedType = follow.feedType
            WHERE ' . $queryParts["where"] . ' AND cactivity.userId = :u AND activity.status=:s AND activity.timeStamp<:st AND (
                    ( follow.userId=:u AND activity.visibility & :vf ) )

        UNION

        SELECT action.`id` FROM ' . $this->getTableName() . ' action
            INNER JOIN ' . $activityDao->getTableName() . ' activity ON action.id = activity.actionId
            INNER JOIN `' . $actionSetDao->getTableName() . '` cactivity ON action.id = cactivity.actionId
            ' . $queryParts["join"] . '
            WHERE ' . $queryParts["where"] . ' AND cactivity.userId = :u AND activity.status=:s AND activity.timeStamp<:st AND (
                    ( activity.userId=:u AND activity.visibility & :va ) )

        UNION

        SELECT action.`id` FROM ' . $this->getTableName() . ' action
            INNER JOIN ' . $activityDao->getTableName() . ' activity ON action.id = activity.actionId
            INNER JOIN `' . $actionSetDao->getTableName() . '` cactivity ON action.id = cactivity.actionId
            ' . $queryParts["join"] . '
            INNER JOIN ' . $actionFeedDao->getTableName() . ' action_feed ON activity.id=action_feed.activityId
            WHERE ' . $queryParts["where"] . ' AND cactivity.userId = :u AND activity.status=:s AND activity.timeStamp<:st
            AND ( ( action_feed.feedId=:u AND action_feed.feedType="user" AND activity.visibility & :vfeed ) )

        UNION

        SELECT action.`id` FROM ' . $this->getTableName() . ' action
            INNER JOIN ' . $activityDao->getTableName() . ' activity ON action.id = activity.actionId
            INNER JOIN `' . $actionSetDao->getTableName() . '` cactivity ON action.id = cactivity.actionId
            ' . $queryParts["join"] . '
            INNER JOIN ' . $activityDao->getTableName() . ' subscribe ON activity.actionId=subscribe.actionId and subscribe.activityType=:as AND subscribe.userId=:u
            WHERE ' . $queryParts["where"] . ' AND cactivity.userId = :u AND activity.status=:s AND activity.timeStamp<:st ) a ';

        $count = $this->dbo->queryForColumn($query, array(
            'u' => $userId,
            'va' => NEWSFEED_BOL_Service::VISIBILITY_AUTHOR,
            'vf' => NEWSFEED_BOL_Service::VISIBILITY_FOLLOW,
            'vfeed' => NEWSFEED_BOL_Service::VISIBILITY_FEED,
            's' => NEWSFEED_BOL_Service::ACTION_STATUS_ACTIVE,
            'st' => empty($startTime) ? time() : $startTime,
            'peb' => NEWSFEED_BOL_Service::PRIVACY_EVERYBODY,
            'ac' => NEWSFEED_BOL_Service::SYSTEM_ACTIVITY_CREATE,
            'as' => NEWSFEED_BOL_Service::SYSTEM_ACTIVITY_SUBSCRIBE
        ));

        $cacheLifeTime = self::CACHE_LIFETIME;
        $cacheTags = array(
            self::CACHE_TAG_ALL,
            self::CACHE_TAG_USER,
            self::CACHE_TAG_USER_PREFIX . $userId
        );

        OW::getCacheManager()->save($count, $cacheKey, $cacheTags, $cacheLifeTime);

        return $count;
    }

    public function findSiteFeed( $limit = null, $startTime = null, $formats = null )
    {
        $limitStr = '';
        if ( !empty($limit) )
        {
            $limitStr = "LIMIT " . intval($limit[0]) . ", " . intval($limit[1]);
        }

        $cacheStartTime = OW::getCacheManager()->load('newsfeed.site_cache_time');
        if ( $cacheStartTime === null )
        {
            OW::getCacheManager()->save($startTime, 'newsfeed.site_cache_time', array(
                self::CACHE_TAG_ALL,
                self::CACHE_TAG_INDEX,
            ), self::CACHE_LIFETIME);
        }
        else
        {
            $startTime = $cacheStartTime;
        }

        $activityDao = NEWSFEED_BOL_ActivityDao::getInstance();

        $queryParts = BOL_UserDao::getInstance()->getUserQueryFilter("cactivity", "userId", array(
            "method" => "NEWSFEED_BOL_ActionDao::findSiteFeedCount"
        ));
        
        if ( $formats !== null )
        {
            $queryParts["where"] .= " AND action.format IN ( '" . implode("','", $formats) . "' )";
        }
        
        $query = 'SELECT action.id FROM ' . $this->getTableName() . ' action
            INNER JOIN ' . $activityDao->getTableName() . ' activity ON action.id = activity.actionId
            INNER JOIN ' . $activityDao->getTableName() . ' cactivity ON action.id = cactivity.actionId
            ' . $queryParts["join"] . '
            WHERE ' . $queryParts["where"] . ' AND
                (cactivity.status=:s AND cactivity.activityType=:ac AND cactivity.privacy=:peb AND cactivity.visibility & :v)
                AND
                (activity.status=:s AND activity.privacy=:peb AND activity.visibility & :v AND activity.timeStamp < :st)
              GROUP BY action.id
              ORDER BY MAX(activity.timeStamp) DESC ' . $limitStr;

        $idList = $this->dbo->queryForColumnList($query, array(
            'v' => NEWSFEED_BOL_Service::VISIBILITY_SITE,
            's' => NEWSFEED_BOL_Service::ACTION_STATUS_ACTIVE,
            'st' => empty($startTime) ? time() : $startTime,
            'peb' => NEWSFEED_BOL_Service::PRIVACY_EVERYBODY,
            'ac' => NEWSFEED_BOL_Service::SYSTEM_ACTIVITY_CREATE
        ), self::CACHE_LIFETIME, array(
            self::CACHE_TAG_ALL,
            self::CACHE_TAG_INDEX
        ));

        return $this->findOrderedListByIdList($idList);
    }

    private function findOrderedListByIdList( $idList )
    {
        if ( empty($idList) )
	    {
	          return array();
	    }
	    
        $unsortedDtoList = $this->findByIdList($idList);
        $unsortedList = array();
        foreach ( $unsortedDtoList as $dto )
        {
            $unsortedList[$dto->id] = $dto;
        }

        $sortedList = array();
        foreach ( $idList as $id )
        {
            if ( !empty($unsortedList[$id]) )
            {
            	$sortedList[] = $unsortedList[$id];
            }
        }

        return $sortedList;
    }

    public function findSiteFeedCount( $startTime = null, $formats = null )
    {
        $activityDao = NEWSFEED_BOL_ActivityDao::getInstance();

        $queryParts = BOL_UserDao::getInstance()->getUserQueryFilter("activity", "userId", array(
            "method" => "NEWSFEED_BOL_ActionDao::findSiteFeedCount"
        ));
        
        if ( $formats !== null )
        {
            $queryParts["where"] .= " AND action.format IN ( '" . implode("','", $formats) . "' )";
        }

        $query = 'SELECT COUNT(DISTINCT action.id) FROM ' . $this->getTableName() . ' action
                    INNER JOIN ' . $activityDao->getTableName() . ' activity ON action.id = activity.actionId
                    LEFT JOIN ' . $activityDao->getTableName() . ' pactivity ON activity.actionId = pactivity.actionId
                        AND (pactivity.status=:s AND pactivity.activityType=:ac AND pactivity.privacy!=:peb AND pactivity.visibility & :v)
                    ' . $queryParts["join"] . '

                    WHERE ' . $queryParts["where"] . ' AND pactivity.id IS NULL AND activity.status=:s AND activity.activityType=:ac AND activity.privacy=:peb AND activity.visibility & :v';

        return $this->dbo->queryForColumn($query, array(
            'v' => NEWSFEED_BOL_Service::VISIBILITY_SITE,
            's' => NEWSFEED_BOL_Service::ACTION_STATUS_ACTIVE,
            'peb' => NEWSFEED_BOL_Service::PRIVACY_EVERYBODY,
            'ac' => NEWSFEED_BOL_Service::SYSTEM_ACTIVITY_CREATE
        ), self::CACHE_LIFETIME, array(
            self::CACHE_TAG_ALL,
            self::CACHE_TAG_INDEX
        ));
    }

    public function findListByUserId( $userId )
    {
        $activityDao = NEWSFEED_BOL_ActivityDao::getInstance();

        $query = "SELECT DISTINCT action.* FROM " . $this->getTableName() . " action
            INNER JOIN " . $activityDao->getTableName() . " activity ON action.id=activity.actionId
            WHERE activity.activityType=:ca AND activity.userId=:u";

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array(
            'ca' => NEWSFEED_BOL_Service::SYSTEM_ACTIVITY_CREATE,
            'u' => $userId
        ));
    }

    public function setPrivacyByEntityType( $userId, array $entityTypes, $privacy )
    {
        if ( empty($entityTypes) )
        {
            return;
        }

        $query = "UPDATE " . $this->getTableName() . " SET privacy=:p WHERE userId=:u AND entityType IN (" . $this->dbo->mergeInClause($entityTypes) . ")";

        $this->dbo->query($query, array(
            'u' => $userId,
            'p' => $privacy
        ));
    }

    /**
     *
     * @param $actionId
     * @return NEWSFEED_BOL_Action
     */
    public function findActionById( $actionId )
    {
        $example = new OW_Example();
        $example->andFieldEqual('id', $actionId);

        return $this->findObjectByExample($example);
    }

    public function findExpiredIdList( $inactivePeriod, $count = null )
    {
        $activityDao = NEWSFEED_BOL_ActivityDao::getInstance();
        $systemActivities = NEWSFEED_BOL_Service::getInstance()->SYSTEM_ACTIVITIES;
        $limit = '';

        if ( !empty($count) )
        {
            $limit = ' LIMIT ' . $count;
        }

        $query = 'SELECT DISTINCT cactivity.actionId FROM ' . $activityDao->getTableName() . ' cactivity
            LEFT JOIN ' . $activityDao->getTableName() . ' activity
                    ON cactivity.actionId=activity.actionId AND activity.activityType NOT IN ("' . implode('", "', $systemActivities) . '")
                WHERE activity.id IS NULL AND cactivity.activityType=:c AND cactivity.timeStamp < :ts' . $limit;

        return $this->dbo->queryForColumnList($query, array(
            'c' => NEWSFEED_BOL_Service::SYSTEM_ACTIVITY_CREATE,
            'ts' => time() - $inactivePeriod
        ));
    }
}