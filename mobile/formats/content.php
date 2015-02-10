<?php

class NEWSFEED_MFORMAT_Content extends NEWSFEED_FORMAT_Content
{
    public function getViewDir()
    {
        return $this->plugin->getMobileViewDir();
    }
}
