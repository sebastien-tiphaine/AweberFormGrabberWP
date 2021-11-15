<?php
/**
 * @package AweberFormsGrabber
 */
 
namespace AweberFormsGrabber\Libs\WPLiteFrame;

// loading required libs
PluginConfig::loadLib('WPLiteFrame/BaseAbstract');
PluginConfig::loadLib('WPLiteFrame/PageAbstract');
PluginConfig::loadLib('WPLiteFrame/AdminAbstract');

/*
 * Class used to render a View
 * A view is a simple phtml file.
 * The content is rendered with html and php variables
 */
class View extends BaseAbstract{

    /*
     * name of current rendering phtml file
     */
    private $_strFileName = false;
    
    /*
     * Base path for loading a partial
     */
    private $_strPartialBase = false;
    
    /*
     * list of variable
     */
    protected $_arrVars = array();
    
    /*
     * Is an admin view ?
     */
    private $_intAdmin = false;
    
    /*
     * constructor
     * Sets the view name using the class name of the calling object
     * @param object set the file name
     */
    public function __construct($oCallingObject){
        
        // set the path of the view file
        $this->_configure($oCallingObject);

        // done
        return $this;
    }
    
    /*
     * Configure the current view object 
     * Sets the file of the view from classname of $oCallingObject
     * @param object $oCallingObject
     * @throw Exception
     * @return $this
     */
    protected function _configure($oCallingObject){
    
        // checking that we are using the same root namespace
        if(!is_object($oCallingObject) || !PluginConfig::isObjectInRootNS($oCallingObject)){
            throw new \Exception(__CLASS__.'::_configure : Not using the same root name space.');
        }
        
        // extracting real class name of the calling object
        $strClassName = get_class($oCallingObject);
        
        // getting root namespace
        $strRootNS    = self::_getConfig()->PluginRootNS;
        
        // is view in wp admin
        $this->_intAdmin   = $oCallingObject instanceof AdminAbstract;
        
        // From now we use the isAdmin method as the main flag has been set :)
        
        // updating class root NS
        $strRootNS    = ($this->isAdmin())? $strRootNS.'\\Admin':$strRootNS.'\\Pages';
        
        // is object directly in the root namespace
        if(strlen($strClassName) < strlen($strRootNS)){
            // yes
            // object should not have view
            throw new \Exception(__CLASS__.'::_configure : This object cannot have view.');
        }
        
        // extracting path as an array
        $arrPath = explode('\\', str_replace($strRootNS.'\\', '', $strClassName));
        
        // is path an array
        if(!is_array($arrPath) || empty($arrPath)){
            // no !
            throw new \Exception(__CLASS__.'::_configure : Not able to extract path from classname.');
        }
        
        // extracting file name
        $strFileName = array_pop($arrPath);
        
        // is path an array
        if(!is_string($strFileName) || empty($strFileName)){
            // no !
            throw new \Exception(__CLASS__.'::_configure : Not able to extract filename from classname.');
        }
        
        // filtering path
        foreach($arrPath as $intKey => $strDirName){
            $arrPath[$intKey] = strtolower($strDirName);
        }
        
        // setting relative file path without extension
        $strFilePath = implode('/', $arrPath).'/'.$strFileName;
        
        // setting default view path
        $strViewFile = self::_getConfig()->PluginPagesViewsPath.$strFilePath;
        
        // is an admin pages
        if($this->isAdmin()){
            $strViewFile = self::_getConfig()->PluginAdminViewsPath.$strFilePath;
        }
        
        // setting partialBase
        $this->_strPartialBase = $strViewFile;
        
        // adding extension to the file name
        $strViewFile = $strViewFile.'.phtml';
  
        // checking if file exists
        if(!file_exists($strViewFile)){
            throw new \Exception(__CLASS__.'::_configure : view file not found : '.$strViewFile);
        }
        
        // setting file name
        $this->_strFileName = $strViewFile;
        // done
        return $this;
    }
        
    /*
     * Validate that a string does not contains unwanted sequences
     * @param string $strString string to validate
     * @return true
     * @throws Exception
     */
    protected function _validateVarName($strString){
    
        // do we have a secure string
        /*if(!is_string($strString) || empty($strString) || !preg_match('/^[a-zA-Z0-9]+$/', ($strString)))   {
            throw new Exception(__CLASS__.'::_validateVarName : Unsecure string !');
        }*/
        
         // checking name
        if(empty($strString) || !$this->_validateString($strString, 'alnum')){
            throw new \Exception(__CLASS__.'::_validateVarName : Unsecure string !');
        }
        
        // done
        return true;
    }
    
    /* 
     * Returns true if current view is an admin view
     */
    public function isAdmin(){
        
        if($this->_intAdmin && !is_admin()){
            throw new \Exception(__CLASS__.'::isAdmin : This View cannot be called outside wp admin !');
        }
        
        return $this->_intAdmin;
    }
    
    /*
     * set a variable to the current object
     * @param string $strName
     * @param mixed $mValue
     * @return $this
     */
    public function __set($strName, $mValue){
        
        // checking name
        $this->_validateVarName($strName);
        
        $this->_arrVars[$strName] = $mValue;
        
        return $this;
    }
    
    /*
     * return a variable from current object
     * @param string $strName
     * @return mixed
     * @throw Exception
     */
    public function __get($strName){
    
        // checking name
        $this->_validateVarName($strName);
    
        // do we have a variable named $strName
        if(!array_key_exists($strName, $this->_arrVars)){
            // no
            throw new \Exception(__CLASS__.'::__get: No variable named : '.$strName);
        }
        
        return $this->_arrVars[$strName];
    }
    
     /*
     * return true if a variable exists
     * @param string $strName
     * @param mixed $mValue
     * @return $this
     */
    public function __isset($strName){
        
         if(!is_string($strName) || empty($strName) || !preg_match('/^[a-zA-Z0-9]+$/', $strName)){
            return false;
        }
        
        return array_key_exists($strName, $this->_arrVars);
    }
    
    /*
     * displays the current url
     * @return void
     */
    public function theUrl(){
        echo $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    }
     
    
    /*
     * include a partial with same env than the current view
     * @param string $strName partial name
     * @return string
     */
    protected function _includePartial($strName){
        
        // checking name
        if(!$this->_validateString($strName, 'alnum')){
            throw new \Exception(__CLASS__.'::_includePartial : invalid partial name.');
        }
        
        // setting partial file name
        $strFileName = $this->_strPartialBase.'-'.$strName.'.phtml';
        
        // checking if file exists
        if(!file_exists($strFileName)){
            throw new \Exception(__CLASS__.'::_includePartial : partial file file not found : '.$strFileName);
        }
        
        // starting output buffuring
        ob_start();
        // executing template content
        require($strFileName);
        // getting content
        $strContent = ob_get_contents();
        // cleaning buffer
        ob_end_clean();
        // returuns the content
        return $strContent;
    }
    
    /*
     * Enqueue a CSS stylesheet.
     * @param string $strName file name without extension
     * @param array  $deps see wp_enqueue_style
     * @param bool|string $ver see wp_enqueue_style
     * @param string $media see wp_enqueue_style
     * @return void
     */
    public function enqueueStyle($strName, $deps = array(), $ver = false, $media = 'all'){
    
        // checking name
        if(!$this->_validateString($strName, 'alnum')){
            throw new \Exception(__CLASS__.'::enqueueStyle : invalid name.');
        }
    
        // defining base url for the style
        $strBaseUrl = ($this->isAdmin())? $this->_getConfig()->PluginAdminCssUrl:$this->_getConfig()->PluginPublicCssUrl; 
    
        // defining type
        $strType    = ($this->isAdmin())? 'public':'admin';
    
        // calling wordpress methode
        return wp_enqueue_style(
            $this->_getConfig()->PluginName.'-'.$strType.'-style-'.$strName, 
            $strBaseUrl.'/'.$strName.'.css', 
            $deps, $ver, $media);
    }
    
    /*
     * Enqueue a CSS stylesheet from the public dir only.
     * @param string $strName file name without extension
     * @param array  $deps see wp_enqueue_style
     * @param bool|string $ver see wp_enqueue_style
     * @param string $media see wp_enqueue_style
     * @return void
     */
    public function enqueuePublicStyle($strName, $deps = array(), $ver = false, $media = 'all'){
    
        // checking name
        if(!$this->_validateString($strName, 'alnum')){
            throw new \Exception(__CLASS__.'::enqueuePublicStyle : invalid name.');
        }
    
        // calling wordpress methode
        return wp_enqueue_style(
            $this->_getConfig()->PluginName.'-style-'.$strName, 
            $this->_getConfig()->PluginPublicCssUrl.'/'.$strName.'.css', 
            $deps, $ver, $media);
    }
    
    /*
     * Enqueue a script file.
     * @param string $strName file name without extension
     * @param array  $deps see wp_enqueue_script
     * @param bool|string $ver see wp_enqueue_script
     * @param bool $in_footer see wp_enqueue_script
     * @return void
     */
    public function enqueueScript($strName, $deps = array(), $ver = false, $in_footer = false){
    
        // checking name
        if(!$this->_validateString($strName, 'alnum')){
            throw new \Exception(__CLASS__.'::enqueueScript : invalid name.');
        }
        
        // defining base url for the style
        $strBaseUrl = ($this->isAdmin())? $this->_getConfig()->PluginAdminJsUrl:$this->_getConfig()->PluginPublicJsUrl; 
        // defining type
        $strType    = ($this->isAdmin())? 'public':'admin';
        
        return wp_enqueue_script(
            $this->_getConfig()->PluginName.'-'.$strType.'-js-'.$strName, 
            $strBaseUrl.'/'.$strName.'.js', 
            $deps, $ver, $in_footer);
    }
    
    /*
     * Enqueue a script file from the public dir only.
     * @param string $strName file name without extension
     * @param array  $deps see wp_enqueue_script
     * @param bool|string $ver see wp_enqueue_script
     * @param bool $in_footer see wp_enqueue_script
     * @return void
     */
    public function enqueuePublicScript($strName, $deps = array(), $ver = false, $in_footer = false){
    
        // checking name
        if(!$this->_validateString($strName, 'alnum')){
            throw new \Exception(__CLASS__.'::enqueuePublicScript : invalid name.');
        }
    
        return wp_enqueue_script(
            $this->_getConfig()->PluginName.'-js-'.$strName, 
            $this->_getConfig()->PluginPublicJsUrl.'/'.$strName.'.js', 
            $deps, $ver, $in_footer);
    }
        
    /* 
     * output the current view
     * @return void
     */ 
    public function __toString(){
        return $this->render();
    }
    
    /*
     * render the view and return it content as a string
     * @return string
     */
    public function render(){
    
        // starting output buffuring
        ob_start();
        // executing template content
        require($this->_strFileName);
        // getting content
        $strContent = ob_get_contents();
        // cleaning buffer
        ob_end_clean();
        // returuns the content
        return $strContent;
    }
}
