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
 * Feed component
 *
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package ow_plugins.newsfeed.components
 * @since 1.0
 */
class NEWSFEED_CMP_Feed extends OW_Component
{
    private static $feedCounter = 0;

    /**
     *
     * @var NEWSFEED_CLASS_Driver
     */
    protected $driver;

    protected $data = array();
    protected $displayType = 'action';
    protected $autoId;
    protected $focused = false;

    protected $actionList = null;
    
    /**
     *
     * @var NEWSFEED_CMP_UpdateStatus
     */
    protected $statusCmp;

    const DISPLAY_TYPE_ACTION = 'action';
    const DISPLAY_TYPE_ACTIVITY = 'activity';
    const DISPLAY_TYPE_PAGE = 'page';

    public function __construct( NEWSFEED_CLASS_Driver $driver, $feedType, $feedId )
    {
        parent::__construct();
        self::$feedCounter++;

        $this->autoId = 'feed' . self::$feedCounter;
        $this->driver = $driver;

        $this->data['feedType'] = $feedType;
        $this->data['feedId'] = $feedId;
        $this->data['feedAutoId'] = $this->autoId;
        $this->data['startTime'] = time();
        $this->data['displayType'] = $this->displayType;
    }

    public function addAction( NEWSFEED_CLASS_Action $action )
    {
        if ( $this->actionList === null )
        {
            $this->actionList = array();
        }

        $this->actionList[$action->getId()] = $action;
    }

    public function focusOnInput( $focused = true )
    {
        $this->focused = $focused;
    }
    
    public function setDisplayType( $type )
    {
        $this->displayType = $type;
    }

    public function addStatusForm( $type, $id, $visibility = null )
    {
        $event = new OW_Event('feed.get_status_update_cmp', array(
            'entityType' => $type,
            'entityId' => $id,
            'feedAutoId' => $this->autoId,
            'visibility' => $visibility
        ));
        
        OW::getEventManager()->trigger($event);
        
        $status = $event->getData();

        if ( $status === null )
        {
            $cmp = $this->createNativeStatusForm($this->autoId, $type, $id, $visibility);
        }
        else
        {
            $cmp = $status;
        }
        
        
        
        if ( !empty($cmp) )
        {
            $this->statusCmp = $cmp;
        }
    }
    
    /**
     * 
     * @param string $autoId
     * @param string $type
     * @param int $id
     * @param int $visibility
     * @return NEWSFEED_CMP_UpdateStatus
     */
    protected function createNativeStatusForm($autoId, $type, $id, $visibility)
    {
        return OW::getClassInstance("NEWSFEED_CMP_UpdateStatus", $autoId, $type, $id, $visibility);
    }
    
    public function addStatusMessage( $message )
    {
        $this->assign('statusMessage', $message);
    }

    public function setup( $data )
    {
        $this->data = array_merge($this->data, $data);
        $driverOptions = $this->data;

        $driverOptions['offset'] = 0;

        $this->driver->setup($driverOptions);
    }

    protected function initJsConstants( $rsp = 'NEWSFEED_CTRL_Ajax' )
    {
        $js = UTIL_JsGenerator::composeJsString('
            window.ow_newsfeed_const.LIKE_RSP = {$like};
            window.ow_newsfeed_const.UNLIKE_RSP = {$unlike};
            window.ow_newsfeed_const.DELETE_RSP = {$delete};
            window.ow_newsfeed_const.LOAD_ITEM_RSP = {$loadItem};
            window.ow_newsfeed_const.LOAD_ITEM_LIST_RSP = {$loadItemList};
            window.ow_newsfeed_const.REMOVE_ATTACHMENT = {$removeAttachment};
        ', array(
            'like' => OW::getRouter()->urlFor($rsp, 'like'),
            'unlike' => OW::getRouter()->urlFor($rsp, 'unlike'),
            'delete' => OW::getRouter()->urlFor($rsp, 'remove'),
            'loadItem' => OW::getRouter()->urlFor($rsp, 'loadItem'),
            'loadItemList' => OW::getRouter()->urlFor($rsp, 'loadItemList'),
            'removeAttachment' => OW::getRouter()->urlFor($rsp, 'removeAttachment')
        ));

        OW::getDocument()->addOnloadScript($js, 50);
    }
    
    protected function initializeJs( $jsConstructor = "NEWSFEED_Feed", $ajaxRsp = 'NEWSFEED_CTRL_Ajax', $scriptFile = null )
    {
        if ( $scriptFile === null )
        {
            OW::getDocument()->addScript( OW::getPluginManager()->getPlugin('newsfeed')->getStaticJsUrl() . 'newsfeed.js' );
        }
        else
        {
            OW::getDocument()->addScript($scriptFile);
        }
        
        $this->initJsConstants($ajaxRsp);

        $total = $this->getActionsCount();
        
        $js = UTIL_JsGenerator::composeJsString('
            window.ow_newsfeed_feed_list[{$autoId}] = new ' . $jsConstructor . '({$autoId}, {$data});
            window.ow_newsfeed_feed_list[{$autoId}].totalItems = {$total};
        ', array(
            'total' => $total,
            'autoId' => $this->autoId,
            'data' => array( 'data' => $this->data, 'driver' => $this->driver->getState() )
        ));

        OW::getDocument()->addOnloadScript($js, 50);
    }

    protected function getActionsList()
    {
        if ( $this->actionList === null )
        {
            $this->actionList = $this->driver->getActionList();
        }

        return $this->actionList;
    }

    protected function getActionsCount()
    {
        return $this->driver->getActionCount();
    }

    /**
     * 
     * @param array $actionList
     * @param array $data
     * @return NEWSFEED_CMP_FeedList
     */
    protected function createFeedList( $actionList, $data )
    {
        return OW::getClassInstance("NEWSFEED_CMP_FeedList", $actionList, $data);
    }
    
    public function onBeforeRender() 
    {
        parent::onBeforeRender();
        
        if ( $this->statusCmp !== null )
        {
            if ( method_exists($this->statusCmp, "focusOnInput") )
            {
                $this->statusCmp->focusOnInput($this->focused);
            }
            
            $this->addComponent('status', $this->statusCmp);
        }
    }
    
    public function render()
    {
        $this->data['displayType'] = $this->displayType;
        
        $this->actionList = $this->getActionsList();
        $this->initializeJs();

        $list = $this->createFeedList($this->actionList, $this->data);
        $list->setDisplayType($this->displayType);

        $this->assign('list', $list->render());
        $this->assign('autoId', $this->autoId);
        $this->assign('data', $this->data);

        if ( $this->displayType == self::DISPLAY_TYPE_PAGE || !$this->data['viewMore'] )
        {
            $viewMore = 0;
        }
        else
        {
            $viewMore = $this->getActionsCount() - $this->data['displayCount'];
            $viewMore = $viewMore < 0 ? 0 : $viewMore;
        }
        
        $this->assign('viewMore', $viewMore);

        return parent::render();
    }
}