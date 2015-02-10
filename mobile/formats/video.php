<?php

class NEWSFEED_MFORMAT_Video extends NEWSFEED_FORMAT_Video
{
    public function getViewDir()
    {
        return $this->plugin->getMobileViewDir();
    }
}
