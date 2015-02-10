<?php

class NEWSFEED_MFORMAT_ImageContent extends NEWSFEED_FORMAT_ImageContent
{
    public function getViewDir()
    {
        return $this->plugin->getMobileViewDir();
    }

    protected function getUserList( $data )
    {
        return array(
            "label" => $this->getLocalizedText($data['label']),
            "list" => BOL_AvatarService::getInstance()->getDataForUserAvatars($data["ids"], true, true, true, false)
        );
    }

    public function onBeforeRender()
    {
        parent::onBeforeRender();

        $this->assign('blankImg', OW::getThemeManager()->getCurrentTheme()->getStaticUrl() . 'mobile/images/1px.png');
    }
}
