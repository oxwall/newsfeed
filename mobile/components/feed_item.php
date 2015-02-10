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
 * Feed Item component
 *
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package ow_plugins.newsfeed.components
 * @since 1.0
 */
class NEWSFEED_MCMP_FeedItem extends NEWSFEED_CMP_FeedItem
{
    protected $itemPermalink = null;

    public function getContextMenu($data) 
    {
        $items = array();
        
        $order = 1;
        
        foreach( $data['contextMenu'] as $action )
        {
            $items[] = array_merge(array(
                "group" => "newsfeed",
                'label' => null,
                'order' => $order,
                'class' => null,
                'url' => null,
                'id' => null,
                'attributes' => array()
            ), $action);

            $order++;
        }
        
        $menu = new BASE_MCMP_ContextAction($items);
        
        return $menu->render();
    }

    public function generateJs( $data )
    {
        $js = UTIL_JsGenerator::composeJsString('
            window.ow_newsfeed_feed_list[{$feedAutoId}].actions[{$uniq}] = new NEWSFEED_MobileFeedItem({$autoId}, window.ow_newsfeed_feed_list[{$feedAutoId}]);
            window.ow_newsfeed_feed_list[{$feedAutoId}].actions[{$uniq}].construct({$data});
        ', array(
            'uniq' => $data['entityType'] . '.' . $data['entityId'],
            'feedAutoId' => $this->sharedData['feedAutoId'],
            'autoId' => $this->autoId,
            'id' => $this->action->getId(),
            'data' => array(
                'entityType' => $data['entityType'],
                'entityId' => $data['entityId'],
                'id' => $data['id'],
                'updateStamp' => $this->action->getUpdateTime(),
                'displayType' => $this->displayType
            )
        ));

        OW::getDocument()->addOnloadScript($js, 50);
    }

    protected function getFeatures( $data )
    {
        $configs = $this->sharedData['configs'];
        $feturesData = $this->getFeaturesData($data);
        
        $featureDefaults = array(
            "uniqId" => uniqid("nf-feature-"),
            "class" => "",
            "active" => false,
            "count" => null,
            "error" => null,
            "url" => "javascript://",
            "hideButton" => false,
            "innerHtml" => null,
            "html" => null
        );

        $features = array();
        $js = UTIL_JsGenerator::newInstance();
        
        // Likes
        if ( !empty($feturesData["system"]["likes"]) )
        {
            $feature = $feturesData["system"]["likes"];
            
            $features["likes"] = array_merge($featureDefaults, array(
                "uniqId" => uniqid("nf-feature-"),
                "class" => "owm_newsfeed_control_like",
                "active" => $feature["liked"],
                "count" => $feature["count"],
                "error" => $feature["error"],
                "url" => "javascript://"
            ));
           
            $js->newObject("likeFeature", "NEWSFEED_MobileFeatureLikes", array(
                $feature["entityType"], $feature["entityId"], $features["likes"]
            ));
        }
        
        // Comments
        if ( !empty($feturesData["system"]["comments"]) )
        {
            $feature = $feturesData["system"]["comments"];
            
            $comments = array_merge($featureDefaults, array(
                "uniqId" => uniqid("nf-feature-"),
                "class" => "owm_newsfeed_control_comment",
                "active" => false,
                "count" => $feature["count"],
                "url" => OW::getRequest()->buildUrlQueryString($this->itemPermalink, array(), "comments")
            ));
            
            if ( $this->displayType == NEWSFEED_MCMP_Feed::DISPLAY_TYPE_PAGE )
            {
                $comments["hideButton"] = true;

                $commentsParams = new BASE_CommentsParams($feature["authGroup"], $feature["entityType"]);
                $commentsParams->setEntityId($feature["entityId"]);
                $commentsParams->setCommentCountOnPage($configs['comments_count']);
                $commentsParams->setBatchData($feature["comments"]);
                //$commentsParams->setDisplayType(BASE_CommentsParams::DISPLAY_TYPE_BOTTOM_FORM_WITH_PARTIAL_LIST_AND_MINI_IPC);
                $commentsParams->setOwnerId($data['action']['userId']);
                $commentsParams->setWrapInBox(false);

                if ( !empty($feature['error']) )
                {
                    $commentsParams->setErrorMessage($feature['error']);
                }

                if ( isset($feature['allow']) )
                {
                    $commentsParams->setAddComment($feature['allow']);
                }

                $commentCmp = new BASE_MCMP_Comments($commentsParams);
                $comments['html'] = $commentCmp->render();
            }
            
            $features[] = $comments;
        }
        
        $jsString = $js->generateJs();
        if ( trim($jsString) )
        {
            OW::getDocument()->addOnloadScript($js);
        }
        
        foreach ( $feturesData["custom"] as $customFeature )
        {
            $features[] = array_merge($featureDefaults, $customFeature);
        }
        
        $visibleCount = 0;
        foreach ( $features as $f )
        {
            if ( empty($f["hideButton"]) )
            {
                $visibleCount++;
            }
        }
        
        return array(
            "items" => $features,
            "buttonsCount" => $visibleCount
        );
    }
    
    protected function applyRespond( $data, $activity )
    {
        if ( empty($activity["data"]["string"]) )
        {
            return $data;
        }
        
        $userId = empty($activity["data"]["action"]["userId"])
                ? $activity["userId"]
                : $activity["data"]["action"]["userId"];
        
        $data["respond"] = array(
            "user" => $this->getUserInfo($userId),
            "text" => $this->getLocalizedText($activity["data"]["string"])
        );
        
        return $data;
    }
    
    public function getTplData( $cycle = null )
    {
        $action = $this->action;
        $data = $this->getActionData($action);
        
        $permalink = empty($data['permalink'])
            ? NEWSFEED_BOL_Service::getInstance()->getActionPermalink($action->getId(), $this->sharedData['feedType'], $this->sharedData['feedId'])
            : $data['permalink'];
        
        $this->itemPermalink = $permalink;

        $userId = (int) $data['action']['userId'];

        $content = null;
        if ( is_array($data["content"]) && !empty($data["content"]["format"]) )
        {
            $vars = empty($data["content"]["vars"]) ? array() : $data["content"]["vars"];
            $content = $this->renderFormat($data["content"]["format"], $vars);
        }
        
        $respond = empty($data["respond"]) ? array() : $data["respond"];
        $creatorsInfo = $this->getActionUsersInfo($data);
        
        $desktopUrl = $permalink;
        
        if ( strpos($permalink, OW_URL_HOME) === 0 )
        {
            $permalinkUri = str_replace(OW_URL_HOME, "", $permalink);
            
            $desktopUrl = OW::getRequest()->buildUrlQueryString(OW::getRouter()->urlForRoute("base.desktop_version"), array(
                "back-uri" => urlencode($permalinkUri)
            ));
        }
        
        $item = array(
            'id' => $action->getId(),
            'view' => $data['view'],
            'toolbar' => $data['toolbar'],
            'string' => $this->getLocalizedText($data['string']),
            'line' => $this->getLocalizedText($data['line']),
            'content' => $content,
            'context' => $data['context'],
            'entityType' => $data['action']['entityType'],
            'entityId' => $data['action']['entityId'],
            'createTime' => UTIL_DateTime::formatDate($data['action']['createTime']),
            'updateTime' => $action->getUpdateTime(),
            'respond' => $respond,
            "responded" => !empty($respond),

            "user" => reset($creatorsInfo),
            'users' => $creatorsInfo,
            'permalink' => $permalink,

            'cycle' => $cycle,
            "disabled" => !empty($data["disabled"]) && $data["disabled"],
            "desktopUrl" => $desktopUrl
        );
        
        $item['autoId'] = $this->autoId;

        $item['features'] = $this->getFeatures($data);
        $item['contextActionMenu'] = $this->getContextMenu($data);
        
        return $item;
    }
    
    public function onBeforeRender()
    {
        parent::onBeforeRender();
        
        // Switch to mobile template
        $plugin = OW::getPluginManager()->getPlugin("newsfeed");
        $this->setTemplate($plugin->getMobileCmpViewDir() . "feed_item.html");
    }
}