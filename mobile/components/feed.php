<?php

class NEWSFEED_MCMP_Feed extends NEWSFEED_CMP_Feed
{
    public function setup($data) 
    {
        parent::setup($data);
        
        $this->driver->setFormats(NEWSFEED_CLASS_FormatManager::getInstance()->getFormatNames());
    }


    protected function createNativeStatusForm($autoId, $type, $id, $visibility)
    {
        return OW::getClassInstance("NEWSFEED_MCMP_UpdateStatus", $autoId, $type, $id, $visibility);
    }

    protected function createFeedList($actionList, $data) 
    {
        return OW::getClassInstance("NEWSFEED_MCMP_FeedList", $actionList, $data);
    }
    
    protected function initializeJs( $jsConstructor = "NEWSFEED_MobileFeed", $rsp = "NEWSFEED_MCTRL_Ajax", $scriptFile = null )
    {
        $script = OW::getPluginManager()->getPlugin('newsfeed')->getStaticJsUrl() . 'mobile.js';
        
        parent::initializeJs("NEWSFEED_MobileFeed", "NEWSFEED_MCTRL_Ajax", $script);
    }
    
    public function onBeforeRender()
    {
        parent::onBeforeRender();
        
        // Switch to mobile template
        $plugin = OW::getPluginManager()->getPlugin("newsfeed");
        $this->setTemplate($plugin->getMobileCmpViewDir() . "feed.html");
    }
}