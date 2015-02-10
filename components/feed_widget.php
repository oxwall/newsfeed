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
 * Feed Widget
 *
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package ow_plugins.newsfeed.components
 * @since 1.0
 */
abstract class NEWSFEED_CMP_FeedWidget extends BASE_CLASS_Widget
{
    private $feedParams = array();
    /**
     *
     * @var NEWSFEED_CMP_Feed
     */
    private $feed;

    /**
     * @return Constructor.
     */
    public function __construct( BASE_CLASS_WidgetParameter $paramObj, $template = null )
    {
        parent::__construct();

        $template = empty($template) ? 'feed_widget' : $template;
        $this->setTemplate(OW::getPluginManager()->getPlugin('newsfeed')->getCmpViewDir() . $template . '.html');

        $this->feedParams['customizeMode'] = $paramObj->customizeMode;

        $this->feedParams['viewMore'] = $paramObj->customParamList['view_more'];
        $this->feedParams['displayCount'] = (int) $paramObj->customParamList['count'];

        $this->feedParams['displayCount'] = $this->feedParams['displayCount'] > 20
                ? 20
                : $this->feedParams['displayCount'];
    }

    public function setFeed( NEWSFEED_CMP_Feed $feed )
    {
        $this->feed = $feed;
    }

    public function onBeforeRender()
    {
        $this->feed->setup($this->feedParams);

        $this->addComponent('feed', $this->feed);
    }

    public static function getStandardSettingValueList()
    {
        return array(
            self::SETTING_SHOW_TITLE => true,
            self::SETTING_TITLE => OW::getLanguage()->text('newsfeed', 'widget_feed_title'),
            self::SETTING_WRAP_IN_BOX => false,
            self::SETTING_ICON => self::ICON_CLOCK
        );
    }

    public static function getAccess()
    {
        return self::ACCESS_ALL;
    }

    public static function getSettingList()
    {
        $settingList['count'] = array(
            'presentation' => self::PRESENTATION_SELECT,
            'label' => OW::getLanguage()->text('newsfeed', 'widget_settings_count'),
            'optionList' => array(5 => '5', '10' => 10, '20' => 20),
            'value' => 10
        );

        $settingList['view_more'] = array(
            'presentation' => self::PRESENTATION_CHECKBOX,
            'label' => OW::getLanguage()->text('newsfeed', 'widget_settings_view_more'),
            'value' => true
        );

        return $settingList;
    }
}