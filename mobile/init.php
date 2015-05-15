<?php

$plugin = OW::getPluginManager()->getPlugin("newsfeed");

OW::getRouter()->addRoute(new OW_Route('newsfeed_view_item', 'newsfeed/:actionId', 'NEWSFEED_MCTRL_Feed', 'viewItem'));
OW::getRouter()->addRoute(new OW_Route('newsfeed_view_feed', 'newsfeed', 'NEWSFEED_MCTRL_Feed', 'feed'));

NEWSFEED_CLASS_EventHandler::getInstance()->genericInit();

$eventHandler = NEWSFEED_MCLASS_EventHandler::getInstance();
OW::getEventManager()->bind(BASE_MCMP_ProfileActionToolbar::EVENT_NAME, array($eventHandler, "onCollectProfileActions"));
OW::getEventManager()->bind("mobile.content.profile_view_bottom", array($eventHandler, "onProfileBottomContentCollect"));
OW::getEventManager()->bind('base.mobile_top_menu_add_options', array($eventHandler, 'onMobileTopMenuAddLink'));
OW::getEventManager()->bind('mobile.notifications.on_item_render', array($eventHandler, 'onNotificationRender'));

OW::getEventManager()->bind('feed.on_item_render', array($eventHandler, 'feedItemRenderFlagBtn'));

// Formats
NEWSFEED_CLASS_FormatManager::getInstance()->init();

/* Built-in Formats */
NEWSFEED_CLASS_FormatManager::getInstance()->addFormat("text", "NEWSFEED_MFORMAT_Text");
NEWSFEED_CLASS_FormatManager::getInstance()->addFormat("image", "NEWSFEED_MFORMAT_Image");
NEWSFEED_CLASS_FormatManager::getInstance()->addFormat("image_list", "NEWSFEED_MFORMAT_ImageList");
NEWSFEED_CLASS_FormatManager::getInstance()->addFormat("image_content", "NEWSFEED_MFORMAT_ImageContent");
NEWSFEED_CLASS_FormatManager::getInstance()->addFormat("content", "NEWSFEED_MFORMAT_Content");
NEWSFEED_CLASS_FormatManager::getInstance()->addFormat("video", "NEWSFEED_MFORMAT_Video");