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
 *
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package ow_plugins.newsfeed.classes
 * @since 1.0
 */
class NEWSFEED_CLASS_Action
{
    private $activity = array();
    private $properties = array();
    private $createActivity, $lastActivity;
    private $creatorIdList = array();
    private $feeds = array();

    public function setDataValue( $name, $value )
    {
        $this->properties['data'][$name] = $value;
    }

    public function getDataValue( $name )
    {
        if ( empty($this->properties['data'][$name]) )
        {
            return null;
        }

        return $this->properties['data'][$name];
    }

    public function getData()
    {
        return $this->properties['data'];
    }

    public function setData( $data )
    {
        $this->properties['data'] = $data;
    }

    public function setEntity( $entityType, $entityId )
    {
        $this->properties['entityType'] = $entityType;
        $this->properties['entityId'] = $entityId;
    }

    /**
     *
     * @return NEWSFEED_CLASS_Identifier
     */
    public function getEntity()
    {
        if ( empty($this->properties['entityType']) || empty($this->properties['entityId']) )
        {
            return null;
        }

        return new NEWSFEED_CLASS_Identifier($this->properties['entityType'], $this->properties['entityId']);
    }

    public function setCreateTime( $time )
    {
        $this->properties['createTime'] = $time;
    }

    public function getCreateTime()
    {
        return empty($this->properties['createTime']) ? null : (int) $this->properties['createTime'];
    }

    public function setUserId( $userId )
    {
        $this->properties['userId'] = $userId;
    }

    public function getUserId()
    {
        return (int) $this->properties['userId'];
    }
    
    public function getCreatorIdList()
    {
        return $this->creatorIdList;
    }
    
    public function setFeedList( $feedList )
    {
        $this->feeds = $feedList;
    }
    
    public function getFeedList()
    {
        return $this->feeds;
    }

    public function getUpdateTime()
    {
        return (int) $this->getLastActivity()->timeStamp;
    }

    public function setId( $id )
    {
        return $this->properties['id'] = $id;
    }

    public function getId()
    {
        return $this->properties['id'];
    }

    public function setPluginKey( $key )
    {
        $this->properties['pluginKey'] = $key;
    }

    public function getPluginKey()
    {
        return $this->properties['pluginKey'];
    }
    
    public function getFormat()
    {
        return $this->properties['format'];
    }
    
    public function setFormat( $format )
    {
        return $this->properties['format'] = $format;
    }

    public function getActivityList( $type = null )
    {
        if ( $type === null )
        {
            return $this->activity;
        }
        
        $out = array();
        foreach ( $this->activity as $activity )
        {
            /* @var $activity NEWSFEED_BOL_Activity */
            if ( $activity->activityType == $type )
            {
                $out[] = $activity;
            }
        }

        return $out;
    }

    /**
     *
     * @return NEWSFEED_BOL_Activity
     */
    public function getCreateActivity()
    {
        return $this->createActivity;
    }
    
    /**
     *
     * @return NEWSFEED_BOL_Activity
     */
    public function getLastActivity()
    {
        return $this->lastActivity;
    }

    public function setActivityList( array $list )
    {
        $this->activity = $list;

        $createActivityList = $this->getActivityList(NEWSFEED_BOL_Service::SYSTEM_ACTIVITY_CREATE);
        
        foreach ( array_reverse($createActivityList) as $a )
        {
            $this->creatorIdList[] = $a->userId;
        }
        
        $this->createActivity = end($createActivityList);
        $this->lastActivity = reset($this->activity);
        $this->setCreateTime($this->createActivity->timeStamp);
        $this->setUserId($this->createActivity->userId);
    }
    
    /**
     *
     * @param $type
     * @param $id
     * @return NEWSFEED_BOL_Activity
     */
    public function getActivity( $type, $id = null )
    {
        $activities = $this->getActivityList($type);
        
        if ( empty($id) )
        {
            return end($activities);
        }
        
        $activity = null;
        
        foreach ( $activities as $a )
        {
            /* @var $a NEWSFEED_BOL_Activity */
            if ( $a->id == $id )
            {
                $activity = $a;
            }
        }

        return $activity;
    }
}