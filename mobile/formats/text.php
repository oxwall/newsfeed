<?php

class NEWSFEED_MFORMAT_Text extends NEWSFEED_FORMAT_Text
{
    public function getViewDir()
    {
        return $this->plugin->getMobileViewDir();
    }
}
