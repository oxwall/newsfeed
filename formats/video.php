<?php

class NEWSFEED_FORMAT_Video extends NEWSFEED_CLASS_Format
{
    public function onBeforeRender()
    {
        parent::onBeforeRender();
        
        $uniqId = uniqid("vf-");
        $this->assign("uniqId", $uniqId);

        $defaults = array(
            "image" => null,
            "iconClass" => null,
            "title" => '',
            "description" => '',
            "status" => null,
            "url" => null,
            "embed" => ''
        );

        $tplVars = array_merge($defaults, $this->vars);
        
        $tplVars["url"] = $this->getUrl($tplVars["url"]);
        $tplVars['blankImg'] = OW::getThemeManager()->getCurrentTheme()->getStaticUrl() . 'mobile/images/1px.png';
        
        $this->assign('vars', $tplVars);
        
        if ( $tplVars['embed'] )
        {
            $js = UTIL_JsGenerator::newInstance();
        
            $code = BOL_TextFormatService::getInstance()->addVideoCodeParam($tplVars['embed'], "autoplay", 1);
            $code = BOL_TextFormatService::getInstance()->addVideoCodeParam($code, "play", 1);

            $js->addScript('$(".ow_oembed_video_cover", "#" + {$uniqId}).click(function() { '
                    . '$("#" + {$uniqId}).addClass("ow_video_playing"); '
                    . '$(".ow_newsfeed_item_picture", "#" + {$uniqId}).html({$embed});'
                    . 'return false; });', array(
                "uniqId" => $uniqId,
                "embed" => $code
            ));

            OW::getDocument()->addOnloadScript($js);
        }
    }
}
