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
 * Data Access Object for `newsfeed_action_feed` table.
 *
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package ow_plugins.newsfeed.bol
 * @since 1.0
 */
class NEWSFEED_BOL_ActionFeedDao extends OW_BaseDao
{
    /**
     * Singleton instance.
     *
     * @var NEWSFEED_BOL_ActionFeedDao
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return NEWSFEED_BOL_ActionFeedDao
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
        return 'NEWSFEED_BOL_ActionFeed';
    }

    /**
     * @see OW_BaseDao::getTableName()
     *
     */
    public function getTableName()
    {
        return OW_DB_PREFIX . 'newsfeed_action_feed';
    }

    public function addIfNotExists( NEWSFEED_BOL_ActionFeed $dto )
    {
        $example = new OW_Example();
        $example->andFieldEqual('activityId', $dto->activityId);
        $example->andFieldEqual('feedId', $dto->feedId);
        $example->andFieldEqual('feedType', $dto->feedType);

        $existingDto = $this->findObjectByExample($example);

        if ( $existingDto === null )
        {
            $this->save($dto);
        }
        else
        {
            $dto->id = $existingDto->id;
        }
    }

    public function deleteByFeedAndActivityId( $feedType, $feedId, $activityId )
    {
        $example = new OW_Example();
        $example->andFieldEqual('activityId', $activityId);
        $example->andFieldEqual('feedId', $feedId);
        $example->andFieldEqual('feedType', $feedType);

        $this->deleteByExample($example);
    }

    public function deleteByActivityIds( $activityIds )
    {
        if ( empty($activityIds) )
        {
            return;
        }

        $example = new OW_Example();
        $example->andFieldInArray('activityId', $activityIds);

        $this->deleteByExample($example);
    }
    
    public function findByActivityIds( $activityIds )
    {
        if ( empty($activityIds) )
        {
            return array();
        }
        
        $example = new OW_Example();
        $example->andFieldInArray('activityId', $activityIds);

        return $this->findListByExample($example);
    }
    
    public function findByFeed( $feedType, $feedId )
    {
        $example = new OW_Example();
        $example->andFieldEqual('feedType', $feedType);
        $example->andFieldEqual('feedId', $feedId);

        return $this->findListByExample($example);
    }
}