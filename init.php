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

$plugin = OW::getPluginManager()->getPlugin("newsfeed");

OW::getRouter()->addRoute(new OW_Route('newsfeed_admin_settings', 'admin/plugins/newsfeed', 'NEWSFEED_CTRL_Admin', 'index'));
OW::getRouter()->addRoute(new OW_Route('newsfeed_admin_customization', 'admin/plugins/newsfeed/customization', 'NEWSFEED_CTRL_Admin', 'customization'));

OW::getRouter()->addRoute(new OW_Route('newsfeed_view_item', 'newsfeed/:actionId', 'NEWSFEED_CTRL_Feed', 'viewItem'));

$eventHandler = NEWSFEED_CLASS_EventHandler::getInstance();
$eventHandler->genericInit();

OW::getEventManager()->bind(OW_EventManager::ON_BEFORE_PLUGIN_DEACTIVATE, array($eventHandler, 'onPluginDeactivate'));
OW::getEventManager()->bind(OW_EventManager::ON_AFTER_PLUGIN_ACTIVATE, array($eventHandler, 'onPluginActivate'));
OW::getEventManager()->bind(OW_EventManager::ON_BEFORE_PLUGIN_UNINSTALL, array($eventHandler, 'onPluginUninstall'));
OW::getEventManager()->bind('feed.on_item_render', array($eventHandler, 'desktopItemRender'));
OW::getEventManager()->bind(BASE_CMP_ProfileActionToolbar::EVENT_NAME, array($eventHandler, 'onCollectProfileActions'));
OW::getEventManager()->bind('feed.on_item_render', array($eventHandler, 'feedItemRenderFlagBtn'));

// Formats
NEWSFEED_CLASS_FormatManager::getInstance()->init();

/* Built-in Formats */
NEWSFEED_CLASS_FormatManager::getInstance()->addFormat("text", "NEWSFEED_FORMAT_Text");
NEWSFEED_CLASS_FormatManager::getInstance()->addFormat("image", "NEWSFEED_FORMAT_Image");
NEWSFEED_CLASS_FormatManager::getInstance()->addFormat("image_list", "NEWSFEED_FORMAT_ImageList");
NEWSFEED_CLASS_FormatManager::getInstance()->addFormat("image_content", "NEWSFEED_FORMAT_ImageContent");
NEWSFEED_CLASS_FormatManager::getInstance()->addFormat("content", "NEWSFEED_FORMAT_Content");
NEWSFEED_CLASS_FormatManager::getInstance()->addFormat("video", "NEWSFEED_FORMAT_Video");