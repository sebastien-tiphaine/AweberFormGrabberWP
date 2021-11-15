<?php
/**
 * @package AweberFormsGrabber
 */

// setting namespace
namespace AweberFormsGrabber\Libs\WPLiteFrame; 

/*
 * Class used to render a View
 * A view is a simple phtml file.
 * The content is rendered with html and php variables
 */
class PluginConfig{

    /*
     * Instance of current object
     */
    private static $_oInstance = null;
    
    /*
     * List of class loaded
     */
    protected static $_arrLoadedClass = array();
    
    /*
     * Array containing configuration vars
     */
    protected $_arrVars = array();
    
    /*
     * Boolean value indicating if object id locked
     */
    private $_intLocked = false;
    
    /*
     * Private constructor. This class is aimed to be a singleton
     */
    private function __construct(){}
    
    /*
     * Returns the instance of Config
     * The returned instance is locked, so variables can be changed
     * @return Config
     */
    public static function getInstance(){
        
        // getting object
        $oConfig = self::_getInstance();
        
        // is object locked
        if(!$oConfig->_intLocked){
            throw new \Exception(__CLASS__.'::getInstance : Init Sequences not called.');
        }
        
        // returns object
        return $oConfig;
    }
    
    /*
     * Returns an instance of current object
     * @return Config
     */
    protected static function _getInstance(){
        
        if(self::$_oInstance === null){
            self::$_oInstance = new self();
        }
        
        return self::$_oInstance;
    }
    
    /*
     * Sets a varible
     * @param string $strName variable name
     * @param mixed  $mValue  variable value
     * @return true
     */
    public static function set($strName, $mValue){
    
        // getting instance
        $oConfig = self::_getInstance();
        
        // is object locked
        $oConfig->_checkLock(false);
        
        // setting value
        $oConfig->__set($strName, $mValue);
        
        // done
        return true;
    }
    
    /*
     * Init configuration with given vars
     * @param string $strPluginNameSpace
     * @param array $arrVars associative array of variables
     * @return true
     */
    public static function init(){
        
        // getting instance
        $oConfig = self::_getInstance();
       
        // is object locked
        $oConfig->_checkLock(false);
        
        // extracting plugin name
        $strPluginName      = current(explode('/', plugin_basename(__FILE__)));
        // extracting plugin root path
        $strPluginRootPath  = current(explode('/'.$strPluginName, plugin_dir_path(__FILE__))).'/'.$strPluginName;
        // extracting plugin url
        $strPluginUrl       = plugin_dir_url($strPluginRootPath).$strPluginName;
        // extracting plugin uri
        $strPluginUri       = '/wp-content/plugins/'.$strPluginName;
        // extracting plugin namespace
        $strPluginNameSpace = current(explode('\\', get_class($oConfig)));
    
        // setting common vars
        $oConfig->_arrVars['PluginName']             = $strPluginName;
        
        $oConfig->_arrVars['PluginRootNS']           = $strPluginNameSpace;
        $oConfig->_arrVars['PluginWPLiteFramePath']  = dirname(__FILE__);
        $oConfig->_arrVars['PluginWPLiteFrameNS']    = __NAMESPACE__;
        
        $oConfig->_arrVars['PluginRootPath']         = $strPluginRootPath;
        $oConfig->_arrVars['PluginRootUrl']          = $strPluginUrl;
        
        $oConfig->_arrVars['PluginLibsPath']         = $strPluginRootPath.'/libs';
        
        $oConfig->_arrVars['PluginPagesPath']        = $strPluginRootPath.'/pages';
        $oConfig->_arrVars['PluginPagesViewsPath']   = $strPluginRootPath.'/pages/views';
        
        $oConfig->_arrVars['PluginPublicPath']       = $strPluginRootPath.'/public';
        $oConfig->_arrVars['PluginPublicUrl']        = $strPluginUrl.'/public';
        $oConfig->_arrVars['PluginPublicUri']        = $strPluginUri.'/public';
        
        $oConfig->_arrVars['PluginPublicImagesPath'] = $strPluginRootPath.'/public/images';
        $oConfig->_arrVars['PluginPublicImagesUrl']  = $strPluginUrl.'/public/images';
        $oConfig->_arrVars['PluginPublicImagesUri']  = $strPluginUri.'/public/images';
        
        $oConfig->_arrVars['PluginPublicCssPath'] = $strPluginRootPath.'/public/css';
        $oConfig->_arrVars['PluginPublicCssUrl']  = $strPluginUrl.'/public/css';
        $oConfig->_arrVars['PluginPublicCssUri']  = $strPluginUri.'/public/css';
        
        $oConfig->_arrVars['PluginPublicJsPath'] = $strPluginRootPath.'/public/js';
        $oConfig->_arrVars['PluginPublicJsUrl']  = $strPluginUrl.'/public/js';
        $oConfig->_arrVars['PluginPublicJsUri']  = $strPluginUri.'/public/js';
        
        $oConfig->_arrVars['PluginPublicCachePath']  = $strPluginRootPath.'/public/cache';
        $oConfig->_arrVars['PluginPublicCacheUrl']   = $strPluginUrl.'/public/cache';
        $oConfig->_arrVars['PluginPublicCacheUri']   = $strPluginUri.'/public/cache';
        
        // can we set admin vars
        if(is_admin()){
            // yes
            $oConfig->_arrVars['PluginAdminPath']        = $strPluginRootPath.'/admin';
            $oConfig->_arrVars['PluginAdminViewsPath']   = $strPluginRootPath.'/admin/views';
            $oConfig->_arrVars['PluginAdminCssUrl']      = $strPluginUrl.'/admin/css';
            $oConfig->_arrVars['PluginAdminJsUrl']       = $strPluginUrl.'/admin/js';
            $oConfig->_arrVars['PluginAdminImagesUrl']   = $strPluginUrl.'/admin/images';
        }
        
        // locking object
        $oConfig->_intLocked = true;
        
        // done
        return true;
    }
    
    /*
     * Throws an Exception if object is locked/not locked
     * follwing $intWantLock var
     * @param bool $intWantLock
     * @return true
     * @throws Exception
     */
    protected function _checkLock($intWantLock = true){
        // is object locked
        if($intWantLock && !$this->_intLocked){
            throw new \Exception(__CLASS__.'::_checkLock : Object is not locked');
        }
        
        if(!$intWantLock && $this->_intLocked){
            throw new \Exception(__CLASS__.'::_checkLock : Object is locked');
        }
        
        // done
        return true;
    }
    
    /*
     * Validate that a string does not contains unwanted sequences
     * @param string $strString string to validate
     * @return true
     * @throws Exception
     */
    protected function _validateString($strString){
    
        // do we have a secure string
        if(!is_string($strString) || empty($strString) || !preg_match('/^[a-zA-Z0-9]+$/', ($strString)))   {
            throw new \Exception(__CLASS__.'::_validateString : Unsecure string !');
        }
        // done
        return true;
    }
    
    /*
     * Return the value of $strName
     * @param string $strName
     * @return mixed
     * @throws Exception
     */
    public function __get($strName){
    
        // checking variable
        if(!isset($strName)){
            throw new \Exception(__CLASS__.'::__get : not a variable');
        }
        
        return $this->_arrVars[$strName];
    }
    
    /*
     * Sets a variable
     * @param string $strName
     * @param mixed $mValue
     * @return $this
     */
    public function __set($strName, $mValue){
    
        // is object locked
        $this->_checkLock(false);
        // checking string
        $this->_validateString($strName);
        
        // is $strName a reserved word
        if(strpos(trim(strtolower($strName)), 'plugin') === 0){
            // yes
            throw new \Exception(__CLASS__.'::__set : '.$strName.' is a reserved keyword. Please use something else.');
        }
        
        // setting var
        $this->_arrVars[$strName] = $mValue;
        // done
        return $this;
    }
    
    /*
     * Return true if $strName is a variable
     * and exists
     * @param string $strName
     */
    public function __isset($strName){
 
        // checking string
        $this->_validateString($strName);
        return isset($this->_arrVars[$strName]);
    }
    
    /*
     * Returns true if $oObject is in current root namespace
     * @param object $oObject any object
     * @return bool
     */
    public static function isObjectInRootNS($oObject){
        
        return self::isClassInRootNS(get_class($oObject));
    }
    
    /*
     * Returns true if $strClassName is in current root namespace
     * @param string $strClassName
     * @return Boolean
     * @throw Exception
     */
    public static function isClassInRootNS($strClassName){
    
        // do we have a string
        if(!is_string($strClassName) || empty($strClassName)){
            // no
            throw new \Exception(__CLASS__.'::isClassInRootNS : not a class name.');
        }
        
        // extracting class root Namespace
        $arrNS     = explode('\\', $strClassName);
        $strRootNS = array_shift($arrNS);
        
        // getting object instance
        $oConfig = self::getInstance();
        $oConfig->_checkLock(true);
        
        return ($strRootNS == $oConfig->PluginRootNS);
    }
    
    /*
     * Returns an array containing class infos like path and namespace
     * extracted from class name given as arg
     * @param string $strName class name with relative namespace path
     * @return array
     * @throw Exception
     */
    protected static function _getInfosFromClassName($strName){

        // do we have slash as namespace/folde separator
        $intSlashSep = (strpos($strName, '/') !== false);

        // setting specials strings
        $strRegExp   = ($intSlashSep)? '/^[a-zA-Z0-9\/]+$/':'/^[a-zA-Z0-9\\\]+$/';
        $strExplChar = ($intSlashSep)? '/':'\\';

        // checking string content
        if(!preg_match($strRegExp, $strName)){
            throw new \Exception(__CLASS__.'::_getInfoFromClassName : invalid class name');
        }

        // extracting namespace if any
        $arrNS = explode($strExplChar, $strName);
        // extracting class name
        $strClassName = array_pop($arrNS);
        // setting default subpath (from lib, page or admin root)
        $strClassPath   = '';
        // setting default class namespace
        $strClassNS     = '';
        // setting default class file name
        $strFileName = $strClassName;

        // do we still avec something
        if(is_array($arrNS) && !empty($arrNS)){
            // yes
            $arrClassPath = array();
            $arrClassNS = array();
            
            foreach($arrNS as $strNS){
                
                // checking namespace
                if(!is_string($strNS) || empty($strNS) || !preg_match('/^[a-zA-Z0-9]+$/', $strNS)){
                    throw new \Exception(__CLASS__.'::_getInfoFromClassName : invalid Namespace');
                }
                // adding subpath
                $arrClassPath[] = strtolower(trim($strNS));
                $arrClassNS[] = $strNS;
            }
            
            $strClassPath   = implode('/', $arrClassPath);
            $strClassNS     = implode('\\', $arrClassNS);
        }
        
        return array(
            // namespace in which the class is
            'classNamespace' => $strClassNS,
            // simple class name
            'simpleClassName' => $strClassName,
            // class name including namespace
            'realClassName' => !empty($strClassNS)? $strClassNS.'\\'.$strClassName:$strClassName,
            // folder in which the file is
            'fileDir' => $strClassPath,
            // relative file path from parent folder
            'filePath'  => (!empty($strClassPath))? $strClassPath.'/'.$strFileName.'.php':$strFileName.'.php',
            // simple file name
            'fileName'  => $strFileName.'.php'
        );
    }
    
    /*
     * Load the class $strName of Type $strType (Lib, Admin, Page)
     * If $intInstance is set to tue, the method will return an object of the given class
     * When building the object, a reference of the current config object will be inserted
     * statically into the class if it implement the required methods
     * @param string $strName
     * @param string $strType (Lib, Amdin or Page)
     * @param bool   $intInstance (default to false)
     * @return true | object
     */
    protected static function _loadClass($strName, $strType, $intInstance = false){
    
        // getting object instance
        $oConfig = self::getInstance();
        
        // is object locked
        $oConfig->_checkLock(true);
        
        // basic type checking
        $oConfig->_validateString($strType);
        
        // formating type
        $strType = ucfirst(trim(strtolower($strType)));
                
        // checking type
        if(!in_array($strType, array('Lib', 'Page', 'Admin'))){
            throw new \Exception(__CLASS__.'::_loadClass : invalid type : '.$strType);
        }
        
        // extracting infos from classname
        $arrClassInfos = self::_getInfosFromClassName($strName);
        // extracting classname with relative namespace
        $strClassName = $arrClassInfos['realClassName'];
        $strFilePath  = $arrClassInfos['filePath'];
        
        // updating with parent datas
        switch($strType){
            case 'Lib':
                $strFilePath  = $oConfig->PluginLibsPath.'/'.$strFilePath;
                $strClassName = $oConfig->PluginRootNS.'\\Libs\\'.$strClassName;
                break;
            case 'Page':
                $strFilePath = $oConfig->PluginPagesPath.'/'.$strFilePath;;
                $strClassName   = $oConfig->PluginRootNS.'\\Pages\\'.$strClassName;
                break;
            case 'Admin';
                $strFilePath = $oConfig->PluginAdminPath.'/'.$strFilePath;;
                $strClassName   = $oConfig->PluginRootNS.'\\Admin\\'.$strClassName;
                break;
            default:
                throw new \Exception(__CLASS__.'::_loadClass : invalid type (case test) : '.$strType);
        }
        
        // is class loaded
        if(!in_array($strClassName, self::$_arrLoadedClass)){
            // no
            // does the file exists
            if(!file_exists($strFilePath)){
                // no
                throw new \Exception(__CLASS__.'::_loadClass : file does not exists : '.$strFilePath);
            }
            
            // setting class as loaded to block new calls
            self::$_arrLoadedClass[] = $strClassName;
        
            // loading file
            require_once $strFilePath;
        
            // checking that the class exists
            if(!class_exists($strClassName)){
                throw new \Exception(__CLASS__.'::_loadClass : no class named : '.$strClassName.' in file');
            }
        }
        
        // do we have to return an instance of the loaded class
        if(!$intInstance){
            // no
            return true;
        }
        
        // does the class implement the config methods
        if(is_callable($strClassName.'::isConfigSet') &&
           is_callable($strClassName.'::setConfig')){
            // yes
            // is config already set
            if(!call_user_func($strClassName.'::isConfigSet')){
                // no 
                // setting it
                call_user_func($strClassName.'::setConfig', $oConfig);
            }
        }
        
        // making object
        return new $strClassName;
    }
    
    /*
     * Load a library in the Path and sub namespace Libs
     * If $intInstance is set to tue, the method will return an object of the given class
     * @param string $strName
     * @param bool $intInstance
     * @return true
     * @throw Exception
     */
    public static function loadLib($strName, $intInstance = false){
        return self::_loadClass($strName, 'Lib', $intInstance);
    }
    
    /*
     * Load a class in admin path
     * if $intInstance is set to true, the function will return an instance of the loaded class
     * @param string $strName
     * @param bool $intInstance
     * @return true | object
     */
    public static function loadAdmin($strName, $intInstance = true){
         return self::_loadClass($strName, 'Admin', $intInstance);
    }
    
    /*
     * Load a class in pages path
     * if $intInstance is set to true, the function will return an instance of the loaded class
     * @param string $strName
     * @param bool $intInstance
     * @return true | object
     */
    public static function loadPage($strName, $intInstance = true){
        return self::_loadClass($strName, 'Page', $intInstance);
    }
}
