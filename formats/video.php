<?php

class NEWSFEED_FORMAT_Video extends NEWSFEED_CLASS_Format
{
    protected $uniqId;
    
    public function __construct($vars, $formatName = null) 
    {
        parent::__construct($vars, $formatName);
        
        $this->uniqId = uniqid("vf-");
        $this->vars = $this->prepareVars($vars);
    }
    
    protected function prepareVars( $vars )
    {
        $defaults = array(
            "image" => null,
            "iconClass" => null,
            "title" => '',
            "description" => '',
            "status" => null,
            "url" => null,
            "embed" => ''
        );

        $out = array_merge($defaults, $vars);
        $out["url"] = $this->getUrl($out["url"]);
        $out['blankImg'] = OW::getThemeManager()->getCurrentTheme()->getStaticUrl() . 'mobile/images/1px.png';
        
        return $out;
    }

    protected function initJs()
    {
        $js = UTIL_JsGenerator::newInstance();
        
        $code = BOL_TextFormatService::getInstance()->addVideoCodeParam($this->vars['embed'], "autoplay", 1);
        $code = BOL_TextFormatService::getInstance()->addVideoCodeParam($code, "play", 1);

        $js->addScript('$(".ow_oembed_video_cover", "#" + {$uniqId}).click(function() { '
                . '$("#" + {$uniqId}).addClass("ow_video_playing"); '
                . '$(".ow_newsfeed_item_picture", "#" + {$uniqId}).html({$embed});'
                . 'return false; });', array(
            "uniqId" => $this->uniqId,
            "embed" => $code
        ));

        OW::getDocument()->addOnloadScript($js);
    }
    
    public function onBeforeRender()
    {
        parent::onBeforeRender();
        
        $this->assign("uniqId", $this->uniqId);
        $this->assign('vars', $this->vars);
        
        if ( $this->vars['embed'] )
        {
            $this->initJs();
        }
    }
}
