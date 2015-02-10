<?php

class NEWSFEED_MCMP_FeedList extends NEWSFEED_CMP_FeedList
{
    protected function createItem(NEWSFEED_CLASS_Action $action, $sharedData) 
    {
        return OW::getClassInstance("NEWSFEED_MCMP_FeedItem", $action, $sharedData);
    }

    public function onBeforeRender() 
    {
        parent::onBeforeRender();
        
        // Switch to mobile template
        $plugin = OW::getPluginManager()->getPlugin("newsfeed");
        $this->setTemplate($plugin->getMobileCmpViewDir() . "feed_list.html");
    }
}