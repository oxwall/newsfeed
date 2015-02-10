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
 * Feed List component
 *
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package ow_plugins.newsfeed.components
 * @since 1.0
 */
class NEWSFEED_CMP_FeedList extends OW_Component
{
    private $feed = array();
    private $sharedData = array();
    private $displayType;

    public function __construct( $actionList, $data )
    {
        parent::__construct();

        $this->feed = $actionList;
        $this->displayType = NEWSFEED_CMP_Feed::DISPLAY_TYPE_ACTION;

        $this->sharedData['feedAutoId'] = $data['feedAutoId'];
        $this->sharedData['displayType'] = $data['displayType'];
        $this->sharedData['feedType'] = $data['feedType'];
        $this->sharedData['feedId'] = $data['feedId'];
        $this->sharedData['configs'] = OW::getConfig()->getValues('newsfeed');

        $userIds = array();
        $entityList = array();
        foreach ( $this->feed as $action )
        {
            /* @var $action NEWSFEED_CLASS_Action */
            $userIds[$action->getUserId()] = $action->getUserId();
            $entityList[] = array(
                'entityType' => $action->getEntity()->type,
                'entityId' => $action->getEntity()->id,
                'pluginKey' => $action->getPluginKey(),
                'userId' => $action->getUserId(),
                'countOnPage' => $this->sharedData['configs']['comments_count']
            );
        }

        $userIds = array_values($userIds);
        $this->sharedData['usersIdList'] = $userIds;

        $this->sharedData['usersInfo'] = array(
            'avatars' => array(),
            'urls' => array(),
            'names' => array(),
            'roleLabels' => array()
        );

        if ( !empty($userIds) )
        {
            $usersInfo = BOL_AvatarService::getInstance()->getDataForUserAvatars($userIds);

            foreach ( $usersInfo as $uid => $userInfo )
            {
                $this->sharedData['usersInfo']['avatars'][$uid] = $userInfo['src'];
                $this->sharedData['usersInfo']['urls'][$uid] = $userInfo['url'];
                $this->sharedData['usersInfo']['names'][$uid] = $userInfo['title'];
                $this->sharedData['usersInfo']['roleLabels'][$uid] = array(
                    'label' => $userInfo['label'],
                    'labelColor' => $userInfo['labelColor']
                );
            }
        }


        $this->sharedData['commentsData'] = BOL_CommentService::getInstance()->findBatchCommentsData($entityList);
        $this->sharedData['likesData'] = NEWSFEED_BOL_Service::getInstance()->findLikesByEntityList($entityList);
    }

    public function setDisplayType( $type )
    {
        $this->displayType = $type;
    }

    /**
     * 
     * @param NEWSFEED_CLASS_Action $action
     * @param array $sharedData
     * @return NEWSFEED_CMP_FeedItem
     */
    protected function createItem( NEWSFEED_CLASS_Action $action, $sharedData )
    {
        return OW::getClassInstance("NEWSFEED_CMP_FeedItem", $action, $sharedData);
    }
    
    public function tplRenderItem( $params = array() )
    {
        $action = $this->feed[$params['action']];

        $cycle = array(
            'lastItem' => $params['lastItem']
        );

        $feedItem = $this->createItem($action, $this->sharedData);
        $feedItem->setDisplayType($this->displayType);

        return $feedItem->renderMarkup($cycle);
    }

    public function render()
    {
        $out = array();
        foreach ( $this->feed as $action )
        {
            $out[] = $action->getId();
        }

        $this->assign('feed', $out);

	OW_ViewRenderer::getInstance()->registerFunction('newsfeed_item', array( $this, 'tplRenderItem' ) );
        $out = parent::render();
	OW_ViewRenderer::getInstance()->unregisterFunction('newsfeed_item');

	return $out;
    }
}