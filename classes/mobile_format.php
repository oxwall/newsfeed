<?php

class NEWSFEED_CLASS_MobileFormat extends NEWSFEED_CLASS_Format
{
    protected function getViewDir()
    {
        return $this->plugin->getMobileViewDir();
    }
}