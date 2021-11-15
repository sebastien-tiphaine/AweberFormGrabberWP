<?php
/**
 * @package AweberFormsGrabber
 */
 
namespace AweberFormsGrabber\Libs\WPLiteFrame;

/*
 * Root class tha embed all tools required in all classes
 */
abstract class BaseAbstract{

    /*
     * Instance of PluginConfig
     */
    protected static $_oPluginConfig = null;
        
    /*
     * Returns current config object : PluginConfig
     * @return PluginConfig
     * @throw Exception
     */
    protected static function _getConfig(){
        
        // is instance set
        if(!self::isConfigSet()){
            throw new \Exception(__CLASS__.'::_getConfig : Instance of PluginConfig not set');
        }
        
        return self::$_oPluginConfig;
    }
    
    /*
     * Sets an instance of PluginConfig locally
     * This is mainly an easy way to access the object
     * Note that config could not be overwritten once set
     * @param PluginConfig $oConfig
     * @return true
     * @throw Exception
     */
    public static function setConfig($oConfig){
    
        // is $oConfig instance of PluginConfig
        if(get_class($oConfig) != __NAMESPACE__.'\\PluginConfig'){
            throw new \Exception(__CLASS__.'::setConfig : Invalid object instance. PluginConfig required');
        }
        
        // is PluginConfig already Loaded
        if(self::isConfigSet()){
            throw new \Exception(__CLASS__.'::_getConfig : Instance of PluginConfig already set');
        }
        
        self::$_oPluginConfig = $oConfig;
        // done
        return true;
    }
    
    /*
     * Return true if an instance of PluginConfig is set
     * @return bool
     */
    public static function isConfigSet(){
    
        // do we have a config object set
        if(!is_object(self::$_oPluginConfig) ||
           get_class(self::$_oPluginConfig) != __NAMESPACE__.'\\PluginConfig'){
            // no
            return false;
        }
    
        return true;
    }
    
    /*
     * Validate that a string does not contains unwanted sequences
     * @param string $strString string to validate
     * @param string $strType type of regexp to use : path, alnum
     * @return bool
     * @throws Exception
     */
    protected function _validateString($strString, $strType = 'alnum'){
    
        // do we have a secure string
        if(!is_string($strString)){
            throw new \Exception(__CLASS__.'::_validateString : string expected !');
        }
        
        // setting regexp following the given level
        switch($strType){
            case 'path':      
                $strRegExp = '/^[a-zA-Z0-9\/-_\.]+$/';
                break;
            case 'pathstrict':
                $strRegExp = '/^[a-zA-Z0-9_\-\/]+$/';
                break;
            case 'file':
                $strRegExp = '/^[a-zA-Z0-9_\-]+\.[a-zA-Z-0-9]{2,5}$/';
                break;
            case 'httpsurl':
                $strRegExp = '/https:\/\/[a-zA-Z0-9_\-\.\/]+$/';
                break;
            default:
            case 'alnum':
                // string alphanum with underscore and -
                $strRegExp = '/^[a-zA-Z0-9-_]+$/';
        }
         
        if(!preg_match($strRegExp, $strString)){
            return false;
        }
       
        return true;
    }
    
    /*
     * Returns true if $strUrl is in any domain set in $arrDomains 
     * @param string $strUrl
     * @param array  $arrDomains array string. Domains must not contains protocole
     * @return bool
     * @throws Exception
     */
    protected function _validateDomain($strUrl, $arrDomains){

        // do we have a string
        if(!is_string($strUrl) || empty($strUrl)){
            // no
            throw new \Exception(__CLASS__.'::_validateDomain : String Expected');
        }
    
        // do we have a domain list
        if(!is_array($arrDomains) || empty($arrDomains)){
            // no
            throw new \Exception(__CLASS__.'::_validateDomain : No domain given');
        }
    
        foreach($arrDomains as $strDomain){
            // do we have a valid domain
            if(!is_string($strDomain) || empty($strDomain)){
                // no
                throw new \Exception(__CLASS__.'::_validateDomain : invalid domain found. String expected');
            }
            
            // do we have a valid domain 
            preg_match('/(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9][a-z0-9-]{0,61}[a-z0-9]/', $strDomain, $arrMatches);
            
            if(!is_array($arrMatches) || empty($arrMatches) || $arrMatches[0]!= $strDomain){
                // no
                throw new \Exception(__CLASS__.'::_validateDomain : invalid domain given');
            }
        }
    
        // ensure no white spaces around
        $strUrl = trim($strUrl);
    
        // do we have the protocole
        if(preg_match('/^https:\/\/([a-z\.-]+\.com)/', $strUrl, $arrMatches)){
            // ok we have to extract domain name
            if(is_array($arrMatches) && !empty($arrMatches) && 
               isset($arrMatches[1]) && !empty($arrMatches[1]) && is_string($arrMatches[1])){
                    // updating url
                    $strUrl = $arrMatches[1];
            }
        } // do we have to extract the domain anyway
        else if(preg_match('/^([a-z\.-]+\.com)/', $strUrl, $arrMatches)){
            // yes
            if(is_array($arrMatches) && !empty($arrMatches) && 
               isset($arrMatches[1]) && !empty($arrMatches[1]) && is_string($arrMatches[1])){
                    // updating url
                    $strUrl = $arrMatches[1];
            }
        }
        
        // is the url valid
        return in_array($strUrl, $arrDomains);
    }
}
