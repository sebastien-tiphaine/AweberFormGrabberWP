<?php
/**
 * @package AweberFormsGrabber
 */

namespace AweberFormsGrabber\Libs;

// loading required lib
WPLiteFrame\PluginConfig::loadLib('WPLiteFrame/BaseAbstract');

/*
 * The goal of this class is to administrate the cache of the grabber
 */
class GrabberCache extends WPLiteFrame\BaseAbstract {

    /*
     * Id of current form
     */
    private $_strId = false; 

    /*
     *  Dir where file are stored
     */
    protected $_strWorkingDir = false;
    
    /*
     * Constructor
     * @param string $strId Cache id
     */
    public function __construct($strId){
    
        // setting id
        $this->_setId($strId);
        
        // setting cache dir
        $this->_setWorkingDir();
        // clean working
        $this->_cleanWorkingDir();
    
    }
    
    /*
     * Sets Id of the cache
     */
    protected function _setId($strId){
        // checking string
        $this->_validateString($strId);
        $this->_strId = $strId;
        return $this;
    }
    
    /*
     * Returns id of current form
     * @return string
    */
    public function getId(){
        return $this->_strId;
    }
    
    /*
     * Return working dir of current form
     * If working dir does not exists, it will be created
     * @return string
     */
    protected function _getWorkingDir(){
    
        // is the value set
        if(!is_string($this->_strWorkingDir) || empty($this->_strWorkingDir)){
            // no
            $this->_strWorkingDir = $this->_getConfig()->PluginPublicCachePath.'/'.$this->getId();
        }
    
        return $this->_strWorkingDir;
    }
    
    /*
     * Ensure that the working dir for current
     * form exist and is usable. If the folder does not exists
     * it will be created.
     * @return true
     * @throw Exception
     */
    protected function _setWorkingDir(){
        
        // getting cache path
        $strCachePath = $this->_getConfig()->PluginPublicCachePath;
        
        // is cache path usable ?
        if(!is_dir($strCachePath) && 
           !mkdir($strCachePath, 0755, true)){
                // no
                $this->_cleanWorkingDir(true);
                throw new \Exception(__CLASS__.'::_setWorkingDir : Not able to create dir : '.$this->_getConfig()->PluginPublicCachePath);
        }
        
        // getting working dir
        $strWorkingDir = $this->_getWorkingDir();
        
        // checking if dir exists or can be created
        if(!is_dir($strWorkingDir) && 
           !mkdir($strWorkingDir, 0755, true)){
            // no
            $this->_cleanWorkingDir(true);
            throw new \Exception(__CLASS__.'::_setWorkingDir : Not able to create working dir : '.$strWorkingDir);
        }
        
        // done
        return true;
    }
    
    /*
     * Removes all files of the working dir
     * @param bool $intWithFolder do we have to also delete the working dir
     * @return true
     */
    protected function _cleanWorkingDir($intWithFolder =  false){
        
        // is the folder existing 
        if(!is_dir($this->_getWorkingDir())){
            // nothing more to do
            return true;
        }
        
        // getting files
        $arrFiles = array_diff(scandir($this->_getWorkingDir()), array('..', '.'));
        
        // do we have files
        if(is_array($arrFiles) && !empty($arrFiles)){
            // yes
            foreach($arrFiles as $strFile){
                // getting file realpath
                $strRealPath = realpath($this->_getWorkingDir().'/'.$strFile);
                // is the file in the working dir
                if(strpos($strRealPath, $this->_getWorkingDir()) === 0){
                    // yes
                    unlink($strRealPath);
                }
            }
        }
        
        // do we have to remove the main folder
        if($intWithFolder){
            // yes
            rmdir($this->_getWorkingDir());
        }
        
        return true;
    }
    
    /*
     * Removes the folder of current cache id
     * This will also delete all files inside
     */
    public function removeDir(){
        return $this->_cleanWorkingDir(true);
    }
    
    /*
     * Removes all files in current cache id
     * The folder of current id will not be deleted
     */
    public function cleanDir(){
        return $this->_cleanWorkingDir(false);
    }
    
    /*
     * Returns the path for file strname in the cache
     * @param string $strName file name
     * @return string
     * @throw Exception
     */
    protected function _getPath($strName){
        
        // do we have a content
        if(!is_string($strName) || empty($strName)){
            // no
            throw new \Exception(__CLASS__.'::_getPath : string expected');
        }
        
        // checking file name
        if(!$this->_validateString($strName, 'file')){
            throw new \Exception(__CLASS__.'::_getPath : unsecure string given : '.$strName);
        }
        
        return $this->_getWorkingDir().'/'.$strName;
    }
    
    /*
     * Returns the public url of current cache
     * @param string $strName filename to include at the end of the cache url(optional)
     * @return string
     * @throw Exception
     */
    public function getUrlDir($strName = false){
    
        // extracting dir
        $strUrl = $this->_getConfig()->PluginPublicCacheUrl.'/'.$this->getId();
    
        if($strName !== false){
            // checking string
            if(!$this->_validateString($strName, 'file')){
                throw new \Exception(__CLASS__.'::getUrlDir : unsecure string given : '.$strName);
            }
            // done
            return $strUrl.'/'.$strName;
        }
       
        return $strUrl; 
    }
    
    /*
     * write $strContent to $strFileName into current cache id
     * @param string $strFileName file name
     * @param string $strContent file content
     * @return true | false
     * @throw Exception
     */
    public function saveContent($strFileName, $strContent){
    
        // do we have a content
        if(!is_string($strContent) || empty($strContent)){
            // no
            throw new \Exception(__CLASS__.'::saveContent : empty content given or content is not a string');
        }
    
        // writing current file to disk
        return file_put_contents($this->_getPath($strFileName), $strContent);
    }
    
    /*
     * Download a file and copy it into the cache
     * @param string $strSrcFile source file
     * @param string $strDestName destination name (optional)
     * @return bool
     */
    public function downloadFile($strSrcFile, $strDestName = false){
    
         // checking domain
        if(!$this->_validateDomain($strSrcFile, array('www.aweber.com', 'forms.aweber.com', 'awas.aweber-static.com'))){
            // not an aweber domain
            throw new \Exception(__CLASS__.'::downloadFile: file is not on an aweber domain :'.$strSrcFile);
        }
    
        // do we have to set destination name
        if($strDestName == false){
            // yes
            // extracting file datas
            $arrInfos = pathinfo($strDestName);
        
            // getting file name
            $strDestName = $arrInfos['basename'];
        }
    
        return copy($strSrcFile, $this->_getPath($strDestName));
    }
    
    /*
     * Returns true if a file exists in cache dir
     * @param $strFileName
     * @return string
     */
    public function fileExists($strFileName){
    
        return file_exists($this->_getPath($strFileName));
    }
}    
