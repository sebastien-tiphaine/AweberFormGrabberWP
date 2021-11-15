<?php
/**
 * @package AweberFormsGrabber
 */
namespace AweberFormsGrabber\Admin;

// loading required lib
\AweberFormsGrabber\Libs\WPLiteFrame\PluginConfig::loadLib('WPLiteFrame/AdminAbstract');

Class Settings extends \AweberFormsGrabber\Libs\WPLiteFrame\AdminAbstract{
    
    /**
     * Registers a new settings page under Settings.
     */
    public function admin_menu() {
        add_submenu_page(
            // Parent slug
            'tools.php',
            // page title
            __( 'Aweber Forms Grabber', 'aweberformsgrabber' ),
            // menu title
            __( 'Aweber Forms Grabber', 'aweberformsgrabber' ),
            // required capacities
            'manage_options',
            // page slug
            'aweber-forms-grabber-settings',
            // callback
            array($this, 'render')
        );
    }
 
    /**
     * Settings page display callback.
     */
    protected function _page_logic() {
        
        // can we have a grab action
        $this->_validateGrabAction();
       
        // do we have action on cache
        $this->_validateCacheAction();
        
        // call a function to display cached forms
        $this->_getView()->cachedForms = $this->_getCachedForms();
    }
    
    /*
     * Return true if formpath has been posted and is usable
     * @return bool
     */
    protected function _validateGrabAction(){
    
        // do we have datas
        if(!$this->isPost() || !$this->hasPostVar('aweber-form-grabber-formpath')){
            // no
            return false;
        }
    
        // extracting value
        $strFormPath = $this->getPostVar('aweber-form-grabber-formpath');
    
        // setting main flag
        $this->_getView()->grabSuccess = false;
    
        // checking string
        if(!$this->_validateString($strFormPath, 'path')){
            // adding view error message
            $this->_getView()->grabErrorMessage = __('Invalid form path', 'aweberformsgrabber');
            // returning post content
            $this->_getView()->formpath = $strFormPath;
            // done
            return false;
        }
    
        if(!preg_match('/\.js$/', $strFormPath)){
            // adding view error message
            $this->_getView()->grabErrorMessage = __('Url should ends with a .js file', 'aweberformsgrabber');
            // returning post content
            $this->_getView()->formpath = $strFormPath;
            // done
            return false;
        }
        
        // loading grabber
        \AweberFormsGrabber\Libs\WPLiteFrame\PluginConfig::loadLib('Grabber');
        
        // setting form url
        $strFormUrl = 'https://forms.aweber.com/form/'.$strFormPath;
        
        // building new object
        try{
            $oGrabber = new \AweberFormsGrabber\Libs\Grabber($strFormUrl);
            // grabbing datas
            $oGrabber->grabDatas();
            // updating flag
            $this->_getView()->grabSuccess = true;
        }
        catch(\Exception $oException){
            // setting message
            $this->_getView()->grabErrorMessage = $oException->getMessage();
        }
            
        // getting grab datas
        $this->_getView()->logContent = $oGrabber->getLog();
            
        // do we succed ?
        if($this->_getView()->grabSuccess){
            // yes
            // getting files infos
            $arrMainFile = $oGrabber->getFileDatas();
            // init view vars
            $this->_getView()->localFormUrl = (isset($arrMainFile['url']) && is_string($arrMainFile['url']) && !empty($arrMainFile['url']))?$arrMainFile['url']:false ;
            $this->_getView()->aweberFormUrl = (isset($arrMainFile['orgurl']) && is_string($arrMainFile['orgurl']) && !empty($arrMainFile['orgurl']))?$arrMainFile['orgurl']:false ;
        }
        
        // done
        return $this->_getView()->grabSuccess;
    }
    
    /*
     * Apply cache action from post commandes
     * @return bool
     */
    protected function _validateCacheAction(){
    
        // do we have datas
        if(!$this->isPost()){
            // no
            return false;
        }
        
        // do we have a clear all action
        if($this->hasPostVar('aweber-form-grabber-clear-cache')){
            // cleaning cache
            return $this->_clearCachedForms();
        }
        
        // do we have a cache id
        if(!$this->hasPostVar('aweber-form-grabber-cache-manager-cacheid')){
            // no
            return false;
        }
        
        // extracting cache id
        $strCacheId = $this->getPostVar('aweber-form-grabber-cache-manager-cacheid');
        
        // do we have a remove action
        if($this->hasPostVar('aweber-form-grabber-remove')){
            // yes
            return $this->_removeCacheId($strCacheId);
        }
        
        // do we have an update action
        if($this->hasPostVar('aweber-form-grabber-update')){
            // yes
            return $this->_updateCacheId($strCacheId);
        }
        
        // no valid action found
        return false;
    }
    
    /*
     * Update a form in the cache
     * @param string $strCacheId
     * @return bool
     */
    protected function _updateCacheId($strCacheId){
    
        // setting main flag
        $this->_getView()->updateSuccess = false;
    
        // do we have a cache id
        try{
            if(!$this->_hasCacheId($strCacheId)){
                // adding view error message
                $this->_getView()->cacheErrorMessage = __('No cache id.', 'aweberformsgrabber');
                // nothing to update
                return false;
            }
        }catch(\Exception $oException){
            // adding view error message
            $this->_getView()->cacheErrorMessage = __('Invalid cache id.', 'aweberformsgrabber');
            // nothing to update
            return false;
        }
    
        // getting cache datas for id
        $arrDatas = $this->_getCachedFormData($strCacheId);
            
        // do we have datas
        if(!is_array($arrDatas) || empty($arrDatas)){
            // no
            // adding view error message
            $this->_getView()->cacheErrorMessage= __('No datas found. Update is not possible.', 'aweberformsgrabber');
            // nothing to update
            return false;
        }
            
        // do we have aweber url stored in metadatas
        if(!isset($arrDatas['metadatas']['aweber-url']) || empty($arrDatas['metadatas']['aweber-url'])){
            // no
            // adding view error message
            $this->_getView()->cacheErrorMessage = __('Aweber url has not been cached. Update is not possible.', 'aweberformsgrabber');
            // nothing to update
            return false;
        }
           
        // loading grabber
        \AweberFormsGrabber\Libs\WPLiteFrame\PluginConfig::loadLib('Grabber');
            
        try{
            // building new object
            $oGrabber = new \AweberFormsGrabber\Libs\Grabber($arrDatas['metadatas']['aweber-url']);
            // grabbing datas
            $oGrabber->grabDatas();
            // setting flag
            $this->_getView()->updateSuccess = true;
        }
        catch(\Exception $oException){
            // adding view error message
            $this->_getView()->cacheErrorMessage = $oException->getMessage();
        }
            
        // getting grab log
        $this->_getView()->logContent = $oGrabber->getLog();
     
        // done
        return $this->_getView()->updateSuccess;
    }
    
    /*
     * Removes a form from the cache
     * @param string $strCacheId
     * @return bool
     */
    protected function _removeCacheId($strCacheId){
    
        // setting main flag
        $this->_getView()->removeSuccess = false;
    
        // do we have a cache id
        try{
            if(!$this->_hasCacheId($strCacheId)){
                // adding view error message
                $this->_getView()->cacheErrorMessage = __('No cache id.', 'aweberformsgrabber');
                // nothing to remove
                return false;
            }
        }catch(\Exception $oException){
            $this->_getView()->cacheErrorMessage = __('Invalid cache id.', 'aweberformsgrabber');
            // nothing to remove
            return false;
        }
    
        // loading grabber cache
        \AweberFormsGrabber\Libs\WPLiteFrame\PluginConfig::loadLib('GrabberCache');
            
        // getting object
        // this re-init the cache
        $oCache = new \AweberFormsGrabber\Libs\GrabberCache($strCacheId);
        // remove all files
        $oCache->removeDir();
        // setting log
        $this->_getView()->logContent = array(
            'Cache id removed : '.$strCacheId
        );
        
        // updating flag
        $this->_getView()->removeSuccess = true;
        
        return true;
    }
    
    /*
     * Removes all forms from the cache
     * @return true
     */
    protected function _clearCachedForms(){
    
        // getting cached forms
        $arrDatas = $this->_getCachedForms();
            
        // loading grabber cache
        \AweberFormsGrabber\Libs\WPLiteFrame\PluginConfig::loadLib('GrabberCache');
            
        $arrLog = array();
            
        // removing each cache entry
        foreach($arrDatas as $strCacheId => $arrCachedDatas){
            
            // getting object
            // this re-init the cache
            $oCache = new \AweberFormsGrabber\Libs\GrabberCache($strCacheId);
            // remove all files
            $oCache->removeDir();
            // updating log
            $arrLog[] = 'Cache id Removed : '.$strCacheId;
        }
            
        // setting log
        $this->_getView()->logContent = $arrLog;
        // setting main flag
        $this->_getView()->clearSuccess = true;

        // done
        return true;
    }
    
    /*
     * Return datas and metadatas of $strCacheId
     * @param string $strCacheId
     * @return array
     */
    protected function _getCachedFormData($strCacheId){
    
        // checking cache id
        if(!$this->_hasCacheId($strCacheId)){
            return false;
        }
        
        // setting cache full path
        $strFullPath = $this->_getConfig()->PluginPublicCachePath.'/'.$strCacheId;
            
        // init datas
        $arrDatas = array(
            'path' => $strFullPath,
            'id'   => $strCacheId,
            'metadatas' => array()
        );
            
        // do we have metadatas
        if(!file_exists($strFullPath.'/metadatas.txt')){
            return $arrDatas;
        }
            
        // is file readable
        if(!($strContent = file_get_contents($strFullPath.'/metadatas.txt'))){
            // no
            return $arrDatas;
        }
            
        // getting metadatas
        $arrMetas = unserialize(base64_decode($strContent));
        
        if(is_array($arrMetas) && !empty($arrMetas)){
            $arrDatas['metadatas'] = $arrMetas;
        }
        
        // done
        return $arrDatas;
    }
    
    /*
     * Return true if $strCacheId is a valid cacheId and it exists
     * @param string $strCacheId
     * @return bool
     */
    protected function _hasCacheId($strCacheId){
        
        // checking cache id
        if(!$this->_validateString($strCacheId)){
            throw new \Exception(__CLASS__.'::_getCachedFormData : invalid cache id');
        }
        
        // extracting cache dir
        $strCacheDir = $this->_getConfig()->PluginPublicCachePath.'/'.$strCacheId;

        return file_exists($strCacheDir);
    }    
    /*
     * Returns list of cached forms with metadatas
     * @return array
     */
    protected function _getCachedForms(){
    
        // extracting cache public path
        $strCachePath = $this->_getConfig()->PluginPublicCachePath;
        // getting list
        $arrIds   = scandir($strCachePath);
        // setting default datas
        $arrDatas = array();
        
        foreach($arrIds as $intKey => $strCacheId){
            // do we have a undesired name
            if(in_array($strCacheId, array('..', '.'))){
                // yes
                continue;
            }
            
            // getting cached datas
            $arrCacheDatas = $this->_getCachedFormData($strCacheId);
            
            // do we have datas for this cache id
            if(!is_array($arrCacheDatas) || empty($arrCacheDatas)){
                // no
                continue;
            }
            
            // updating main datas array
            $arrDatas[$strCacheId] = $arrCacheDatas;
        }
        
        return $arrDatas;
    }
}
