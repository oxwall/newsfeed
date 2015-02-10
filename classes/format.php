<?php

class NEWSFEED_CLASS_Format extends OW_Component
{
    protected $vars = array();
    
    /**
     *
     * @var OW_Plugin
     */
    protected $plugin;
    
    public function __construct($vars, $formatName = null) 
    {
        parent::__construct();

        $this->vars = $vars;
        $this->plugin = OW::getPluginManager()->getPlugin(OW::getAutoloader()->getPluginKey(get_class($this)));
        
        if ( $formatName !== null )
        {
            $this->setTemplate($this->getViewDir() . "formats" . DS . $formatName . ".html");
        }
    }
    
    public function render()
    {
        if ( $this->getTemplate() === null )
        {
            $template = OW::getAutoloader()->classToFilename(get_class($this), false);
            $this->setTemplate($this->getViewDir() . "formats" . DS . $template . '.html');
        }
        
        return parent::render();
    }
    
    protected function getLocalizedText( $value )
    {
        if ( !is_array($value) )
        {
            return $value;
        }

        list($prefix, $key) = explode("+", $value["key"]);
        
        return OW::getLanguage()->text($prefix, $key, $value['vars']);
    }
    
    protected function getUrl( $value )
    {
        if ( !is_array($value) )
        {
            return $value;
        }
        
        if ( OW::getRouter()->getRoute($value["routeName"]) === null )
        {
            return null;
        }
        
        return OW::getRouter()->urlForRoute($value["routeName"], empty($value["vars"]) ? array() : $value["vars"]);
    }
    
    protected function getViewDir()
    {
        return $this->plugin->getViewDir();
    }
}