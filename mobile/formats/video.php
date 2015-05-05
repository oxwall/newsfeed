<?php

class NEWSFEED_MFORMAT_Video extends NEWSFEED_FORMAT_Video
{
    public function getViewDir()
    {
        return $this->plugin->getMobileViewDir();
    }
    
    protected function initJs()
    {
        $js = UTIL_JsGenerator::newInstance();
        
        $code = BOL_TextFormatService::getInstance()->addVideoCodeParam($this->vars['embed'], "autoplay", 1);
        $code = BOL_TextFormatService::getInstance()->addVideoCodeParam($code, "play", 1);

        $js->addScript('$(".ow_format_video_play", "#" + {$uniqId}).click(function() { '
                . '$("#" + {$uniqId}).addClass("ow_video_playing"); '
                . '$(".owm_newsfeed_body_pic", "#" + {$uniqId}).html({$embed});'
                . 'return false; });', array(
            "uniqId" => $this->uniqId,
            "embed" => $code
        ));

        OW::getDocument()->addOnloadScript($js);
    }
}
