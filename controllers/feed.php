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
 * @package ow_plugins.newsfeed.controllers
 * @since 1.0
 */
class NEWSFEED_CTRL_Feed extends OW_ActionController
{
    /**
     *
     * @var NEWSFEED_BOL_Service
     */
    protected $service;

    public function __construct()
    {
        $this->service = NEWSFEED_BOL_Service::getInstance();
    }

    /**
     * 
     * @param NEWSFEED_CLASS_Driver $driver
     * @param string $feedType
     * @param string $feedId
     * @return NEWSFEED_CMP_Feed
     */
    protected function getFeed( NEWSFEED_CLASS_Driver $driver, $feedType, $feedId )
    {
        return OW::getClassInstance("NEWSFEED_CMP_Feed", $driver, $feedType, $feedId);
    }
    
    public function viewItem( $params )
    {
        $actionId = (int) $params['actionId'];
        $feedType = empty($_GET['ft']) ? 'site' : $_GET['ft'];
        $feedId = empty($_GET['fi']) ? null : $_GET['fi'];

        $driverClasses = array(
            "site" => "NEWSFEED_CLASS_SiteDriver",
            "my" => "NEWSFEED_CLASS_UserDriver"
        );
        
        $driverClass = empty($driverClasses[$feedType]) 
                ? "NEWSFEED_CLASS_FeedDriver"
                : $driverClasses[$feedType];
        
        $driver = OW::getClassInstance($driverClass);
        
        $driver->setup(array(
            'feedType' => $feedType,
            'feedId' => $feedId
        ));

        $action = $driver->getActionById($actionId);

        if ( empty($action) )
        {
            throw new Redirect404Exception();
        }

        $feed = $this->getFeed($driver, $feedType, $feedId);
        $feed->setup(array(
            'viewMore' => false
        ));

        $feed->setDisplayType(NEWSFEED_CMP_Feed::DISPLAY_TYPE_PAGE);
        $feed->addAction($action);

        $this->addComponent('action', $feed);
        
        $this->assign("entity", array(
            "type" => $action->getEntity()->type,
            "id" => $action->getEntity()->id
        ));
    }

    public function follow()
    {
        $userId = (int) $_GET['userId'];
        $backUri = $_GET['backUri'];

        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }

        if ( empty($userId) )
        {
            throw new InvalidArgumentException('Invalid parameter `userId`');
        }

        $eventParams = array(
            'userId' => OW::getUser()->getId(),
            'feedType' => 'user',
            'feedId' => $userId
        );

        OW::getEventManager()->trigger( new OW_Event('feed.add_follow', $eventParams) );

        $backUrl = OW_URL_HOME . $backUri;
        $username = BOL_UserService::getInstance()->getDisplayName($userId);

        if ( OW::getRequest()->isAjax() )
        {
            exit(json_encode(array(
                'message' => OW::getLanguage()->text('newsfeed', 'follow_complete_message', array('username' => $username))
            )));
        }
        else
        {
            OW::getFeedback()->info(OW::getLanguage()->text('newsfeed', 'follow_complete_message', array('username' => $username)));
            $this->redirect($backUrl);
        }
    }

    public function unFollow()
    {
        $userId = (int) $_GET['userId'];
        $backUri = $_GET['backUri'];

        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }

        if ( empty($userId) )
        {
            throw new InvalidArgumentException('Invalid parameter `userId`');
        }

        $this->service->removeFollow(OW::getUser()->getId(), 'user', $userId);

        $backUrl = OW_URL_HOME . $backUri;
        $username = BOL_UserService::getInstance()->getDisplayName($userId);

        if ( OW::getRequest()->isAjax() )
        {
            exit(json_encode(array(
                'message' => OW::getLanguage()->text('newsfeed', 'unfollow_complete_message', array('username' => $username))
            )));
        }
        else
        {
            OW::getFeedback()->info(OW::getLanguage()->text('newsfeed', 'unfollow_complete_message', array('username' => $username)));
            $this->redirect($backUrl);
        }
    }
}