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
 * Likes Widget
 *
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package ow_plugins.newsfeed.components
 * @since 1.0
 */
class NEWSFEED_CMP_Likes extends OW_Component
{
    private $count = 0;
    
    public function __construct( $entityType, $entityId, $likes = null )
    {
        parent::__construct();
        
        if ( $likes === null )
        {
            $likes = NEWSFEED_BOL_Service::getInstance()->findEntityLikes($entityType, $entityId);
        }
        
        $this->count = count($likes);
        
        if ( $this->count == 0 )
        {
            $this->setVisible(false);
            
            return;
        }
        
        $userIds = array();
        foreach ( $likes as $like )
        {
            $userIds[] = (int) $like->userId;    
        }
        
        if ( $this->count <= 3 )
        {
            $displayNames = BOL_UserService::getInstance()->getDisplayNamesForList($userIds);
            $urls = BOL_UserService::getInstance()->getUserUrlsForList($userIds);
            
            $langVars = array();
            
            foreach( $userIds as $i => $userId )
            {
                $langVars['user' . ($i + 1)] = '<a href="' . $urls[$userId] . '">' . $displayNames[$userId] . '</a>';
            }
            
            $string = OW::getLanguage()->text('newsfeed', 'feed_likes_' . $this->count . '_label', $langVars);
        } 
        else 
        {
            $url = "javascript: OW.showUsers(" . json_encode($userIds) . ")";
            $string = OW::getLanguage()->text('newsfeed', 'feed_likes_list_label', array('count' => $this->count, 'url' => $url));
        }
        
        $this->assign('string', $string);
    }
    
    public function getCount()
    {
        return $this->count;
    }
}