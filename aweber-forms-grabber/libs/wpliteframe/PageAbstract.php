<?php
/**
 * @package AweberFormsGrabber
 */
 
namespace AweberFormsGrabber\Libs\WPLiteFrame;

// loading required libs
PluginConfig::loadLib('WPLiteFrame/BaseAbstract');
PluginConfig::loadLib('WPLiteFrame/View');

/*
 * Class aim to have the page logic and scripts
 * The View object will be used for the rendering
 */
abstract class PageAbstract extends BaseAbstract{

    /*
     * Instance of the view object
     */
    protected $_oView = null;
    
    /*
     * User logic called on page preprocessing
     */
    protected abstract function _page_logic();
    
    /*
     * Return the view attached to current object
     * It will build and configure the view if not set
     */
    protected function _getView(){
        
        // checking if View has PluginConfig config set
        if(!View::isConfigSet()){
            View::setConfig(self::_getConfig());
        }
        
        // is the view set
        if(!$this->_oView instanceof View){
            $this->_oView = new View($this);
        }
        
        return $this->_oView;
    }
    
    /*
     * Returns true if datas have been send with post method
     * @return bool
     */
    public function isPost(){
        if(isset($_POST) && is_array($_POST) && !empty($_POST)){
            return true;
        }
        
        return false;
    }
    
    /*
     * Return true if Post array has a key named $strName
     * @param string $strName
     * @return bool
     */
    public function hasPostVar($strName){
    
        if(!is_string($strName) || empty($strName)){
            throw new \Exception(__CLASS__.'::hasPostVar : missing var name');
        }
        
        return array_key_exists($strName, $_POST);
    }
    
    /*
     * Returns the value of $strName in $_POST
     * @param string $strName
     * @throw Exception
     */
    public function getPostVar($strName){
    
        if(!$this->hasPostVar($strName)){
            throw new \Exception(__CLASS__.'::getPostVar : No var named : '.$strName);
        }
        
        return $_POST[$strName];
    }
    
    /*
     * Excute the page logic and render the view
     * @return void
     */
    public function render(){
        // calling page logic
        $this->_page_logic();
        // rendering the view
        echo $this->_getView();
    }
}
