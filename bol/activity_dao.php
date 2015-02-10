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
 * Data Access Object for `newsfeed_activity` table.
 *
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package ow_plugins.newsfeed.bol
 * @since 1.0
 */
class NEWSFEED_BOL_ActivityDao extends OW_BaseDao
{
    /**
     * Singleton instance.
     *
     * @var NEWSFEED_BOL_ActivityDao
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return NEWSFEED_BOL_ActivityDao
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
        return 'NEWSFEED_BOL_Activity';
    }

    /**
     * @see OW_BaseDao::getTableName()
     *
     */
    public function getTableName()
    {
        return OW_DB_PREFIX . 'newsfeed_activity';
    }

    public function deleteByActionIds( $actionIds )
    {
        if ( empty($actionIds) )
        {
            return array();
        }

        $example = new OW_Example();
        $example->andFieldInArray('actionId', $actionIds);

        return $this->deleteByExample($example);
    }

    public function deleteByUserId( $userId )
    {
        $example = new OW_Example();
        $example->andFieldEqual('userId', $userId);

        return $this->deleteByExample($example);
    }

    public function findIdListByActionIds( $actionIds )
    {
        if ( empty($actionIds) )
        {
            return array();
        }

        $example = new OW_Example();
        $example->andFieldInArray('actionId', $actionIds);

        return $this->findIdListByExample($example);
    }

    public function findByActionIds( $actionIds )
    {
        if ( empty($actionIds) )
        {
            return array();
        }

        $example = new OW_Example();
        $example->andFieldInArray('actionId', $actionIds);

        return $this->findListByExample($example);
    }

    private function getQueryParts( $conts )
    {
        $actionDao = NEWSFEED_BOL_ActionDao::getInstance();
        $or = array();
        $join = '';

        foreach ( $conts as $cond )
        {
            $action = array_filter($cond['action']);
            $activity = array_filter($cond['activity']);

            $where = array();

            if ( empty($activity['id']) )
            {
                if ( !empty($action['id']) )
                {
                    $activity['actionId'] = $action['id'];
                }
                else if ( !empty($action) )
                {
                    $join = 'INNER JOIN ' . $actionDao->getTableName() . ' action ON activity.actionId=action.id';

                    foreach ( $action as $k => $v )
                    {
                        $where[] = 'action.' . $k . "='" . $this->dbo->escapeString($v) . "'";
                    }
                }
            }

            foreach ( $activity as $k => $v )
            {
                $where[] = 'activity.' . $k . "='" . $this->dbo->escapeString($v) . "'";
            }

            $or[] = implode(' AND ', $where);
        }

        return array(
            'join' => $join,
            'where' => empty($or) ? '1' : '( ' . implode(' ) OR ( ', $or) . ' )'
        );
    }

    public function findActivity( $params )
    {
        $qp = $this->getQueryParts($params);

        $query = 'SELECT activity.* FROM ' . $this->getTableName() . ' activity ' . $qp['join'] . ' WHERE ' . $qp['where'];

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName());
    }

    public function deleteActivity( $params )
    {
        $qp = $this->getQueryParts($params);

        $query = 'DELETE activity FROM ' . $this->getTableName() . ' activity ' . $qp['join'] . ' WHERE ' . $qp['where'];

        return $this->dbo->query($query);
    }

    public function updateActivity( $params, $updateFields )
    {
        if ( empty($updateFields) )
        {
            return;
        }

        $set = array();
        foreach ( $updateFields as $k => $v )
        {
            $set[] = 'activity.`' . $k . "`='" . $this->dbo->escapeString($v) . "'";
        }

        $qp = $this->getQueryParts($params);
        $query = 'UPDATE ' . $this->getTableName() . ' activity ' . $qp['join'] . ' SET ' . implode(', ', $set) . ' WHERE ' . $qp['where'];

        return $this->dbo->query($query);
    }

    /**
     *
     * @param string $activityType
     * @param int $activityId
     * @param int $actionId
     * @return NEWSFEED_BOL_Activity
     */
    public function findActivityItem( $activityType, $activityId, $actionId )
    {
        $example = new OW_Example();
        $example->andFieldEqual('activityType', $activityType);
        $example->andFieldEqual('activityId', $activityId);
        $example->andFieldEqual('actionId', $actionId);

        return $this->findObjectByExample($example);
    }

    public function findSiteFeedActivity( $actionIds )
    {
        $unionQueryList = array();

        $queryParts = BOL_UserDao::getInstance()->getUserQueryFilter("activity", "userId", array(
            "method" => "NEWSFEED_BOL_ActivityDao::findSiteFeedActivity"
        ));
        
        $unionQueryList[] = 'SELECT activity.* FROM ' . $this->getTableName() . ' activity ' . $queryParts["join"] . '
            WHERE ' . $queryParts["where"] . ' AND activity.actionId IN(' . implode(', ', $actionIds) . ')
                AND activity.activityType IN ("' . implode('", "', NEWSFEED_BOL_Service::getInstance()->SYSTEM_ACTIVITIES) . '")';

        foreach ( $actionIds as $actionId )
        {
                $unionQueryList[] = 'SELECT a.* FROM (
                SELECT activity.* FROM ' . $this->getTableName() . ' activity ' . $queryParts["join"] . ' WHERE ' . $queryParts["where"] . ' AND  activity.actionId = ' . $actionId . ' AND activity.status=:s AND activity.privacy=:peb AND activity.visibility & :v ORDER BY activity.timeStamp DESC, activity.id DESC LIMIT 100
                        ) a';
        }

        $query = implode( ' UNION ', $unionQueryList ) . " ORDER BY 7 DESC, 1 DESC";

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array(
            'v' => NEWSFEED_BOL_Service::VISIBILITY_SITE,
            's' => NEWSFEED_BOL_Service::ACTION_STATUS_ACTIVE,
            'peb' => NEWSFEED_BOL_Service::PRIVACY_EVERYBODY
        ));
    }

    public function findUserFeedActivity( $userId, $actionIds )
    {
        $followDao = NEWSFEED_BOL_FollowDao::getInstance();
        $actionFeedDao = NEWSFEED_BOL_ActionFeedDao::getInstance();

        $unionQueryList = array();

        $queryParts = BOL_UserDao::getInstance()->getUserQueryFilter("activity", "userId", array(
            "method" => "NEWSFEED_BOL_ActivityDao::findUserFeedActivity"
        ));
        
        $unionQueryList[] = 'SELECT activity.* FROM ' . $this->getTableName() . ' activity 
            WHERE activity.actionId IN(' . implode(', ', $actionIds) . ') 
            AND activity.activityType IN ("' . implode('", "', NEWSFEED_BOL_Service::getInstance()->SYSTEM_ACTIVITIES) . '")';

        foreach ( $actionIds as $actionId )
        {
            $unionQueryList[] = ' SELECT a.* FROM ( SELECT DISTINCT activity.* FROM ' . $this->getTableName() . ' activity
                ' . $queryParts["join"] . '
                
                LEFT JOIN ' . $actionFeedDao->getTableName() . ' action_feed ON activity.id=action_feed.activityId
                LEFT JOIN ' . $followDao->getTableName() . ' follow ON action_feed.feedId = follow.feedId AND action_feed.feedType = follow.feedType
                WHERE ' . $queryParts["where"] . ' AND activity.actionId = ' . $actionId . ' AND
                (
                    (activity.status=:s AND
                    (
                        ( follow.userId=:u AND activity.visibility & :vf AND ( activity.privacy=:peb OR activity.privacy=follow.permission ) )
                        OR
                        ( activity.userId=:u AND activity.visibility & :va )
                        OR
                        ( action_feed.feedId=:u AND action_feed.feedType="user" AND activity.visibility & :vfeed )
                    ))
                ) ORDER BY activity.timeStamp DESC, activity.id DESC LIMIT 100 ) a' ;
        }

        $query = implode( ' UNION ', $unionQueryList ) . " ORDER BY 7 DESC, 1 DESC";

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array(
            'u' => $userId,
            'va' => NEWSFEED_BOL_Service::VISIBILITY_AUTHOR,
            'vf' => NEWSFEED_BOL_Service::VISIBILITY_FOLLOW,
            'vfeed' => NEWSFEED_BOL_Service::VISIBILITY_FEED,
            's' => NEWSFEED_BOL_Service::ACTION_STATUS_ACTIVE,
            'peb' => NEWSFEED_BOL_Service::PRIVACY_EVERYBODY
        ));
    }

    public function findFeedActivity( $feedType, $feedId, $actionIds )
    {
        $actionFeedDao = NEWSFEED_BOL_ActionFeedDao::getInstance();

        $unionQueryList = array();

        $queryParts = BOL_UserDao::getInstance()->getUserQueryFilter("activity", "userId", array(
            "method" => "NEWSFEED_BOL_ActivityDao::findFeedActivity"
        ));
        
        $unionQueryList[] = 'SELECT activity.* FROM ' . $this->getTableName() . ' activity
            WHERE activity.actionId IN(' . implode(', ', $actionIds) . ')
            AND activity.activityType IN ("' . implode('", "', NEWSFEED_BOL_Service::getInstance()->SYSTEM_ACTIVITIES) . '")';

        foreach ( $actionIds as $actionId )
        {
            $unionQueryList[] = 'SELECT a.* FROM ( SELECT DISTINCT activity.* FROM ' . $this->getTableName() . ' activity
                ' . $queryParts["join"] . '
                INNER JOIN ' . $actionFeedDao->getTableName() . ' action_feed ON activity.id=action_feed.activityId
                WHERE ' . $queryParts["where"] . ' AND activity.actionId = ' . $actionId . ' AND
                    (
                        activity.status=:s
                        AND activity.privacy=:peb
                        AND action_feed.feedType=:ft
                        AND action_feed.feedId=:fi
                        AND activity.visibility & :v
                    )
                ORDER BY activity.timeStamp DESC, activity.id DESC LIMIT 100 ) a';
        }

        $query = implode( ' UNION ', $unionQueryList ) . " ORDER BY 7 DESC, 1 DESC ";
        
        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array(
            'ft' => $feedType,
            'fi' => $feedId,
            's' => NEWSFEED_BOL_Service::ACTION_STATUS_ACTIVE,
            'v' => NEWSFEED_BOL_Service::VISIBILITY_FEED,
            'peb' => NEWSFEED_BOL_Service::PRIVACY_EVERYBODY
        ));
    }

    public function saveOrUpdate( NEWSFEED_BOL_Activity $activity )
    {
        $dto = $this->findActivityItem($activity->activityType, $activity->activityId, $activity->actionId);
        if ( $dto !== null )
        {
            $activity->id = $dto->id;
        }
        
        $this->save($activity);
    }

    public function batchSaveOrUpdate( array $dtoList )
    {
        $this->dbo->batchInsertOrUpdateObjectList($this->getTableName(), $dtoList);
    }
}