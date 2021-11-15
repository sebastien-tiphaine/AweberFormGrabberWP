<?php
/**
 * @package AweberFormsGrabber
 */
//$oGrab = new AweberFormGrabber('https://forms.aweber.com/form/03/1878096003.js');
//$oGrab->grabDatas();

namespace AweberFormsGrabber\Libs;

// loading required lib
WPLiteFrame\PluginConfig::loadLib('WPLiteFrame/BaseAbstract');
WPLiteFrame\PluginConfig::loadLib('GrabberCache');

/*
 * Grabb files for a given Aweber Form and copy them locally
 */
class Grabber extends WPLiteFrame\BaseAbstract {

    /*
     * Id of current form
     */
    private $_strId = false;

    /*
     * Url of the form at Aweber 
     */
    protected $_strAweberFormUrl = false;
    
    /*
     * Content of downloaded files
     */
    protected $_arrFiles = array();
    
    /*
     *  Array containing log messages
     */
    protected $_arrLog = array();
    
    /*
     * Id of the running process
     */
    protected $_intProcess = 0;
    
    /*
     * Id of the main file datas in $_arrFiles
     */
    protected $_strMainFileId = false;
    
    /*
     * Instance of GrabberCache object
     */
    protected $_oGrabberCache = null;
    
    /*
     * Constructor. 
     * @param string $strAweberFormUrl
     */
    public function __construct($strAweberFormUrl){
        
        // do we have a usable string
        if(!is_string($strAweberFormUrl) || empty($strAweberFormUrl)){
            // no
            throw new \Exception(__CLASS__.' : String expected');
        }
        
        // validating url
        $this->_validateUrlString($strAweberFormUrl);
        
        // setting url
        $this->_strAweberFormUrl = $strAweberFormUrl;
        
        // extracting id from url
        if(!($this->_strId = $this->_extractId())){
            throw new \Exception(__CLASS__.' : Not able to extract id');
        }
        
        return $this;
    }
    
    /*
     * Returns an instance of GrabberCache object
     * @return GrabberCache
     */
    protected function _getCache(){
    
        // do we have a cache object
        if(is_object($this->_oGrabberCache) && 
           $this->_oGrabberCache instanceof GrabberCache){
                // yes
                return $this->_oGrabberCache;
        }
        
        // no
        // setting cache object
        $this->_oGrabberCache = new GrabberCache($this->getId());
        
        $this->_log('>> Cache initialized');
        
        // done
        return $this->_oGrabberCache;
    }
    
    /*
     * Returns id of current form
     * @return string
    */
    public function getId(){
        return $this->_strId;
    }
    
    /*  
     * Extract a weber form id from aweber form url
     * return string | false
     * throws Exception
    */
    protected function _extractId(){
    
        // extracting file name
        preg_match('/\/form\/(([^\.])*)\.js/', $this->_strAweberFormUrl, $arrMatches);
        
        // do we have a result
        if(!is_array($arrMatches) || empty($arrMatches) || 
           !isset($arrMatches[1]) || empty($arrMatches[1]) || !is_string($arrMatches[1])){
            return false;
        }
        
        // cleaing id
        $strId = preg_replace('/[^a-zA-Z0-9]+/', '', $arrMatches[1]);
        
        // do we still have some chars ?
        if(empty($strId)){
            // no
            throw new \Exception(__CLASS__.'::_extractId : Unsecure string.');
        }
        
        // ensure this to be uniq
        return $strId;
    }
        
    /*
     * Returns true if $strStr is a secure string that can
     * be used as a path. It must not contains any special chars.
     * @param string $strStr
     * @return true
     * @throws Exception
     */
    protected function _validatePathString($strStr){
    
        // do we have s string ?
        if(!is_string($strStr) || empty($strStr)){
            // no
            $this->_getCache()->removeDir();
            throw new \Exception(__CLASS__.'::_validatePathString : Not a string.');
        }
    
        if(!$this->_validateString($strStr, 'pathstrict')){
            // no
            $this->_getCache()->removeDir();
            throw new \Exception(__CLASS__.'::_validatePathString : Unsecure string.');
        }
    
        return true;
    }
    
    /*
     * Returns true if $strStr is a secure string that can
     * be used as a filename. It must not contains any special chars nor /.
     * @param string $strStr
     * @return true
     * @throws Exception
     */
    protected function _validateFileNameString($strStr){
    
         // do we have s string ?
        if(!is_string($strStr) || empty($strStr)){
            // no
            $this->_getCache()->removeDir();
            throw new \Exception(__CLASS__.'::_validateFileNameString : Not a String.');
        }
            
        // is the string secure ?
        if(!$this->_validateString($strStr, 'file')){
            // no
            $this->_getCache()->removeDir();
            throw new \Exception(__CLASS__.'::_validatePathString : Unsecure string.');
        }
        
        return true;
    }
    
    /*
     * Returns true if $strStr is a secure string that can
     * be used as an url. It must not contains any special chars.
     * @param string $strStr
     * @return true
     * @throws Exception
     */
    protected function _validateUrlString($strStr){
    
         // do we have s string ?
        if(!is_string($strStr) || empty($strStr)){
            // no
            $this->_getCache()->removeDir();
            throw new \Exception(__CLASS__.'::_validateUrlString : Not a String.');
        }
    
        // is the string secure ?
        if(!$this->_validateString($strStr, 'httpsurl')){;
            // no
            $this->_getCache()->removeDir();
            throw new \Exception(__CLASS__.'::_validateUrlString : Unsecure string : '.$strStr);
        }
    
        return true;
    }
    
    /*
     * Returns true if $strUrl is a valid aweber domain
     * @param string $strUrl
     * @return true
     * @throws Exception
     */
    protected function _validateAweberDomain($strUrl){

        // checking domain
        if(!$this->_validateDomain($strUrl, array('www.aweber.com', 'forms.aweber.com', 'awas.aweber-static.com'))){
            // not an aweber domain
            $this->_getCache()->removeDir();
            throw new \Exception(__CLASS__.'::_validateAweberDomain : Not an aweber domain :'.$strUrl);
        }
    
        return true;
    }
    
    /*
     * Extract file datas from an url
     * @return array (
     *       'scheme'    => 'https', 
     *       'host'      => 'www.host.com', 
     *       'path'      => '/path/to/file.html',
     *       'dirname    => '/path/to',
     *       'basename'  => 'file'
     *       'extension' => 'html',
     *       'filename'  => 'file.html'
     *       'uname'     => 'path_to_file.html',
     *       'md5p'      => 'md5 of /path/to/file.html',
     *       'md5u'      => 'md5 of full url',
     *       'url'       => 'https://www.host.com/path/to/file.html'
     * )
     */
    protected function _getDatasFromUrl($strUrl){
    
        // do we have a string
        if(!is_string($strUrl) || empty($strUrl)){
            // no
            throw new \Exception(__CLASS__.'::_getDatasFromUrl : string expected.');
        }
    
        // cleaning url
        $strUrl = str_replace('\\', '', $strUrl);
    
        // ensure url to be secured
        $this->_validateUrlString($strUrl);
        // ensure that the file is comming from an aweber domain
        $this->_validateAweberDomain($strUrl);
        
        // gettings url infos
        $arrUrlInfo = parse_url($strUrl);
        // getting file infos
        $arrPathInfo = pathinfo($arrUrlInfo['path']);
        
        // checking file name
        $this->_validateFileNameString($arrPathInfo['basename']);
        
        // merging datas
        $arrDatas = array_merge($arrUrlInfo, $arrPathInfo);
        // inserting uniqname
        $arrDatas['uname'] = str_replace('/', '_', substr($arrUrlInfo['path'], 1));
        // inserting md5
        $arrDatas['md5p'] = md5($arrUrlInfo['path']);
        $arrDatas['md5u'] = md5($strUrl);
        // inserting cleaned url
        $arrDatas['url']  = $strUrl;
        
        // done
        return $arrDatas;
        
    }
    
    /*
     * Fetch file and return file id
     * @param string $strFileUrl
     * return string
     */
    protected function _fetchFile($strFileUrl){
    
        // getting file datas
        $arrDatas = $this->_getDatasFromUrl($strFileUrl);
        // extracting file unid file id
        $strFileId = $arrDatas['md5u'];
        
        // do we already have the file
        if(isset($this->_arrFiles[$strFileId])){
            //yes
            return $strFileId;
        }
        
        // getting file content
        $strFileContent = file_get_contents($arrDatas['url']);
        
        // checking content
        if($strFileContent === false || !is_string($strFileContent) || empty($strFileContent)){
            // cleaning working dir and delete it
            $this->_getCache()->removeDir();
            $this->_log('File not found : '.$arrDatas['url']);
            // exiting 
            throw new \Exception(__CLASS__.':_fetchFile : File not found : '.$arrDatas['url']);
        }
        
        // adding file content to main files array
        $this->_arrFiles[$strFileId] = array(
            'filename'  => $arrDatas['uname'],
            'id'        => $strFileId,
            'url'       => $this->_getCache()->getUrlDir($arrDatas['uname']),
            'orgurl'    => $arrDatas['url'],
            'type'      => $arrDatas['extension'],
            'content'   => $strFileContent,
            'parsed'    => false
        );
        
        // done
        return $strFileId;
    }
    
    /*
     * Find urls of all linked files inside content of file
     * previously fetched.
     * @param string $strFileId id of the file
     * @return array | false
     * @throws Exception
     */
    protected function _getInnerCalledUrl($strFileId){
    
        // logging datas
        $this->_log('>>>> Extracting inner urls : '.$strFileId);
    
        // do we have a valid id
        if(!is_string($strFileId) || empty($strFileId) || !isset($this->_arrFiles[$strFileId])){
            // no
            $this->_getCache()->removeDir();
            throw new \Exception(__CLASS__.'::_getInnerCalledUrl : invalid file id');
        }
    
        // do we have inner files
        if(!preg_match_all('/https:[\/\\\]+((forms)|(awas))\.((aweber-static)|(aweber))\.com([^\.]*\.[a-zA-Z0-9]{1,3})/', $this->_arrFiles[$strFileId]['content'], $arrMatches)){
            // no
            // logging datas
            $this->_log('>>>> Nothing found - End of inner urls filtering.');
            // done
            return false;
        }
    
        // is the result usable
        if(!is_array($arrMatches) || empty($arrMatches) || 
           !isset($arrMatches[0]) || !is_array($arrMatches[0]) || empty($arrMatches[0]) ||
           !isset($arrMatches[7]) || !is_array($arrMatches[7]) || empty($arrMatches[7])){
            // no
            return false;
        }
        
        // setting default result
        $arrResult = array();

        // rolling over extracted result
        // to filter datas.
        foreach($arrMatches[0] as $intKey => $strGrabbedUrl){

            // logging datas
            $this->_log('>>>> ['.$intKey.'] grabbed url : '.$strGrabbedUrl);
        
            // getting file datas
            $arrDatas = $this->_getDatasFromUrl($strGrabbedUrl);
                  
            // do we have to skip the file
            if($arrDatas['path'] == '/form/displays.htm' || $arrDatas['path'] == '/form/display.htm' ){
                // yes
                // this file is only required for statistics
                // logging datas
                $this->_log('>>>> ['.$intKey.'] pixel file found : display.htm. Skipping !');
                continue;
            }
        
            // extracting id
            $strId = $arrDatas['md5p'];
        
            // do we already have found this file
            if(isset($arrResult[$strId]) && !in_array($strGrabbedUrl, $arrResult[$strId]['strings'])){
                // yes
                // adding secondary url or string to be replaced to the list
                $arrResult[$strId]['strings'][] = $strGrabbedUrl;
                continue;
            }
        
            // adding data to result array
            $arrResult[$strId] = array(
                'strings'   => array($strGrabbedUrl),
                'url'       => $arrDatas['url'],
                'filename'  => $arrDatas['uname'],
                'localurl'  => $this->_getCache()->getUrlDir($arrDatas['uname']),
                'type'      => $arrDatas['extension']
            );
        }
        
        // logging datas
        $this->_log('>>>> End Of Inner Url');
        // returning result
        return $arrResult;
    }
    
    /* 
     * Replace the plPath value with local domain
     * @param string $strFileId
     * @return boolean
     * @throws Exception
     */
    protected function _filterPlPath($strFileId){
        
        // logging datas
        $this->_log('>>>> Filtering PlPath : '.$strFileId);
        
        // do we have a valid id
        if(!is_string($strFileId) || empty($strFileId) || !isset($this->_arrFiles[$strFileId])){
            // no
            $this->_getCache()->removeDir();
            throw new \Exception(__CLASS__.'::_filterPlPath : invalid file id');
        }
        
        // do we have inner files
        if(!preg_match_all('/plPath["\'\s=:]{1,4}((www|forms)\.aweber\.com)[\'"]/', $this->_arrFiles[$strFileId]['content'], $arrMatches)){
            // no
            // logging datas
            $this->_log('>>>> Nothing found - End of PlPath filtering.');
            return false;
        }
        
        // is the result usable
        if(!is_array($arrMatches) || empty($arrMatches) || 
           !isset($arrMatches[0]) || !is_array($arrMatches[0]) || empty($arrMatches[0]) ||
           !isset($arrMatches[1]) || !is_array($arrMatches[1]) || empty($arrMatches[1])){
            // no
            return false;
        }
        
        // setting plPathValue
        $strPlPath = str_replace('http://', '', str_replace('https://', '', $this->_getConfig()->PluginPublicUrl));
        
        foreach($arrMatches[0] as $intKey => $strPlString){
            // setting new expression
            $strLocalUrl = str_replace($arrMatches[1][$intKey], $strPlPath, $strPlString);
                                       
            // applying replacement inside file content
            $this->_arrFiles[$strFileId]['content'] = str_replace($strPlString, $strLocalUrl, $this->_arrFiles[$strFileId]['content']);
            // logging datas
            $this->_log('>>>> Replace done : '.$strPlString.' => '.$strLocalUrl);
        }
        
        // logging datas
        $this->_log('>>>> End of PlPath filtering');
        return true;
    }
    
    /*
     * Insert a script tag just before the form tag
     * This will allow pre validation of the form
     * NOTE: This is only used for unsupported browser and html only forms
     * @param string $strFileId file id
     * @return bool
     * @throw Exception
     */
    protected function _insertScript($strFileId){
    
        // logging datas
        $this->_log('>>>> Filtering form tag to insert a script : '.$strFileId);
    
        // do we have a valid id
        if(!is_string($strFileId) || empty($strFileId) || !isset($this->_arrFiles[$strFileId])){
            // no
            $this->_getCache()->removeDir();
            throw new \Exception(__CLASS__.'::_insertScript : invalid file id');
        }

        // do we have to insert a script
        if(!in_array(strtolower($this->_arrFiles[$strFileId]['type']), array('html', 'htm'))){
            // no, script tag only apply for html
            // logging datas
            $this->_log('>>>> Not an html file - Nothing to do - End of insert script filtering.');
            return false;
        }
        
        // do we have a form
        if(!preg_match('/<form[^>]+>/', $this->_arrFiles[$strFileId]['content'], $arrForm)){
            // no
            // logging datas
            $this->_log('>>>> No form tag found - End of insert script filtering.');
            return false;
        }
            
        // logging datas
        $this->_log('>>>> Found a form tag');
        
        // extracting form tag
        $strForm  = current($arrForm);
        // setting default quote
        $strQuote = '"';
            
        // do we have special sequence for quotes
        if(preg_match('/=(["\'\\\]+)/', $strForm, $arrQuotes)){
            // yes
            $strQuote = $arrQuotes[1];
        }
            
        // setting script tag
        $strScript = '<script type='.$strQuote.'text/javascript'.$strQuote.' src='.$strQuote.$this->_getConfig()->PluginPublicJsUrl.'/aweber-form-submit.js'.$strQuote.'></script>';
            
        // do we escape slashes
        if(preg_match('#\\\\\/#', $strForm)){
            // yes
            $strScriptTag = str_replace('/', '\/', $strScript);
        }
            
        // updating content
        $this->_arrFiles[$strFileId]['content'] = str_replace($strForm, $strScript.$strForm, $this->_arrFiles[$strFileId]['content']);
            
        // logging datas
        $this->_log('>>>> replace done : '.$strForm.' => '.$strScript.$strForm);
        $this->_log('>>>> Script tag inserted - End of insert script filtering.');
        
        // done
        return true;
    }
    
    /*
     * Change the submit input by a type button with a onclick action.
     * This avoid loosing client when form is submitted with inclompetes datas
     * @param string $strFileId id of the file to update
     * @return bool
     * @throw Exception
     */
    /*protected function _filterFormSubmit($strFileId){
    
        // logging datas
        $this->_log('>>>> Filtering form submit : '.$strFileId);
        
        // do we have a valid id
        if(!is_string($strFileId) || empty($strFileId) || !isset($this->_arrFiles[$strFileId])){
            // no
            $this->_getCache()->removeDir();
            throw new \Exception(__CLASS__.'::_insertFormOnSubmitAction : invalid file id');
        }
        
        // do we have to insert a script
        if(strtolower($this->_arrFiles[$strFileId]['type']) != 'js'){
            // not a js file
            // we have to insert the script
            $this->_insertScript($strFileId);
        }
        
        // do we have a submit button
        if(!preg_match_all('/type=(["\'\\\]+)submit[\'"\\\]+/', $this->_arrFiles[$strFileId]['content'], $arrMatches)){
            // no
            // logging datas
            $this->_log('>>>> Nothing input type submit found - End of form submit filtering.');
            return false;
        }
        
        // is the result usable
        if(!is_array($arrMatches) || empty($arrMatches) || 
           !isset($arrMatches[0]) || !is_array($arrMatches[0]) || empty($arrMatches[0]) ||
           !isset($arrMatches[1]) || !is_array($arrMatches[1]) || empty($arrMatches[1])){
            // no
            return false;
        }
        
        foreach($arrMatches[0] as $intKey => $strString){
            // setting new expression
            $strAction = str_replace('submit', 'button', $strString);
            $strAction.= ' onclick='.$arrMatches[1][$intKey].'AweberFormGrabberCheckMyForm(this.form)'.$arrMatches[1][$intKey].' ';
                      
            // applying replacement inside file content
            $this->_arrFiles[$strFileId]['content'] = str_replace($strString, $strAction, $this->_arrFiles[$strFileId]['content']);
            // logging datas
            $this->_log('>>>> Replace done : '.$strString.' => '.$strAction);
        }
    
        // logging datas
        $this->_log('>>>> End of End of form submit filtering.');
        return true; 
    }*/
    
    protected function _filterFormSubmit($strFileId){
    
        // logging datas
        $this->_log('>>>> Filtering form submit : '.$strFileId);
        
        // do we have a valid id
        if(!is_string($strFileId) || empty($strFileId) || !isset($this->_arrFiles[$strFileId])){
            // no
            $this->_getCache()->removeDir();
            throw new \Exception(__CLASS__.'::_filterFormSubmit : invalid file id');
        }
        
        // do we have a form
        if(!preg_match_all('/(<form)[^>]+>/', $this->_arrFiles[$strFileId]['content'], $arrForms)){
            // no
            // logging datas
            $this->_log('>>>> No form tag found - End of form submit filtering.');
            return false;
        }
        
        if(!is_array($arrForms) || empty($arrForms) || 
           !isset($arrForms[0]) || !is_array($arrForms[0]) || empty($arrForms[0]) ||
           !isset($arrForms[1]) || !is_array($arrForms[1]) || empty($arrForms[1])){
                // logging datas
                $this->_log('>>>> Empty filtering result - End of form submit filtering.');
                return false;
        }

        foreach($arrForms[0] as $intKey => $strForm){

            // do we have the tag
            if(!isset($arrForms[1][$intKey]) || empty($arrForms[1][$intKey])){
                // no
                continue;
            }

            // do we have to excape quotes
            preg_match('/=(["\'\\\]+)/', $strForm, $arrQuotes);
            // extracting quote
            $strQuote = (is_array($arrQuotes) && !empty($arrQuotes))? $arrQuotes[1]:'"';

            // setting onsubmit event
            $strEvent = 'onsubmit='.$strQuote.'return (AweberFormGrabberCheckMyForm && AweberFormGrabberCheckMyForm(this));'.$strQuote;
            
            // inserting event
            $strNewForm = str_replace($arrForms[1][$intKey], $arrForms[1][$intKey].' '.$strEvent, $strForm);
            
            $this->_arrFiles[$strFileId]['content'] = str_replace($strForm, $strNewForm, $this->_arrFiles[$strFileId]['content']);
            
            // logging datas
            $this->_log('>>>> Replace done : '.$strForm.' => '.$strNewForm);
        }
        
        // logging datas
        $this->_log('>>>> End of form submit filtering.');
        return true; 
    }
    
    /*
     * Filters content of the file $strUrl
     * @param string $strUrl
     * @return true
     */
    protected function _grabFileDatas($strUrl = false){
    
        // updating process number
        $this->_intProcess++;
        // getting local process number
        $intProcess = $this->_intProcess;
    
        // logging datas
        $this->_log('Starting grab process : '.$intProcess);
        // flag indicating if we are parsing the main file
        $intIsMainFile = false;
    
        // do we have an url
        if($strUrl === false){
            // using the default one
            $strUrl = $this->_strAweberFormUrl;
            $intIsMainFile = true;
        }
    
        // logging datas
        $this->_log('>> Url to grab : '.$strUrl);
    
        // getting datas
        $strId = $this->_fetchFile($strUrl);
        
        // do we have to store the id
        if($intIsMainFile){
            // yes
            $this->_strMainFileId = $strId;
        }
        
        // logging datas
        $this->_log('>> File fetched with id : '.$strId);
        
        // is file already parsed
        if($this->_arrFiles[$strId]['parsed']){
            // yes
            // logging datas
            $this->_log('>> File already parsed : skipping.');
            return true;
        }
        
        // filtering plPath
        $this->_filterPlPath($strId);
        // filtering submit button
        $this->_filterFormSubmit($strId);
        // inserting a script tag if required
        $this->_insertScript($strId);
        
        // getting urls
        $arrUrls = $this->_getInnerCalledUrl($strId);
        
        // do we have urls
        if(!is_array($arrUrls) || empty($arrUrls)){
            // no
            // setting file as parsed
            $this->_arrFiles[$strId]['parsed'] = true;
        
            // writing current file to disk
            if(!$this->_getCache()->saveContent($this->_arrFiles[$strId]['filename'], $this->_arrFiles[$strId]['content'])){
                // logging datas
                $this->_log('>> Error while writing file content to : '.$this->_arrFiles[$strId]['filename']);
                $this->_log('End of grab process : '.$intProcess);
                // removing cache
                $this->_getCache()->removeDir();
                throw new \Exception(__CLASS__.'::_grabFileDatas : Error while writing file content to : '.$this->_arrFiles[$strId]['filename']);
            }
            
            // logging datas
            $this->_log('>> [1] File content written to : '.$this->_arrFiles[$strId]['filename']);
            $this->_log('End of grab process : '.$intProcess);
            // no
            return true;
        }
        
        // keeping a trace files to grab later
        $arrToGrab = array();
        
        foreach($arrUrls as $arrUrlDatas){
        
            // applying replacement
            foreach($arrUrlDatas['strings'] as $strString){
                
                // extracting local url
                $strLocalUrl = $arrUrlDatas['localurl'];
                
                // do we have to escape chars ?
                if(preg_match('/[\/\\\]/', $strString)){
                    // yes
                    $strLocalUrl = str_replace('/', '\/', $strLocalUrl);
                }
                // updating file content
                $this->_arrFiles[$strId]['content'] = str_replace($strString, $arrUrlDatas['localurl'], $this->_arrFiles[$strId]['content']);
                // logging datas
                $this->_log('>> Replace done : '.$strString.' => '.$arrUrlDatas['localurl']);
            }
            
            // do we have something more to do with this file
            if(!in_array($arrUrlDatas['type'], array('js', 'css', 'htm', 'html'))){
                // no, writing file to the cache
                // do we already have the file localy
                if($this->_getCache()->fileExists($arrUrlDatas['filename'])){
                    // yes
                    continue;
                }
                
                // no
                // saving file
                if(!$this->_getCache()->downloadFile($arrUrlDatas['url'], $arrUrlDatas['filename'])){
                    // logging datas
                    $this->_log('>> Error while downloading file : '.$arrUrlDatas['url']);
                    $this->_log('End of grab process : '.$intProcess);
                    $this->_getCache()->removeDir();
                    throw new \Exception(__CLASS__.'::_grabFileDatas : Error while downloading file : '.$arrUrlDatas['url']);
                }
                
                continue;
            }
            
            // file has to be grabbed
            $arrToGrab[] = $arrUrlDatas['url'];
        }
        
        // setting file as parsed
        $this->_arrFiles[$strId]['parsed'] = true;
        
        // writing current file to disk
        if(!$this->_getCache()->saveContent($this->_arrFiles[$strId]['filename'], $this->_arrFiles[$strId]['content'])){
            // logging datas
            $this->_log('>> Error while writing file to : '.$this->_arrFiles[$strId]['filename']);
            $this->_log('End of grab process : '.$intProcess);
            // removing cache
            $this->_getCache()->removeDir();
            throw new \Exception(__CLASS__.'::_grabFileDatas : Error while writing file to : '.$this->_arrFiles[$strId]['filename']);
        }
        
        // logging datas
        $this->_log('>> [2] File content written to : '.$this->_arrFiles[$strId]['filename']);
        //$this->_log('>> Extracted files to grab : '.print_r($arrToGrab, true));
        
        // grabbing sub files
        foreach($arrToGrab as $strSubUrl){
            $this->_grabFileDatas($strSubUrl);
        }
        
        // logging datas
        $this->_log('End of grab process : '.$intProcess);
        return true;
    }
    
    /*
     * Store $strMessage into log array
     * @param string $strMessage 
     * @return bool
     */
    protected function _log($strMessage){
        
        if(!is_string($strMessage) || empty($strMessage)){
            return false;
        }
        
        $this->_arrLog[] = $strMessage;
        
        return true;
    }
    
    /*
     * Return the logged messages
     * @return array
     */
    public function getLog(){
        return $this->_arrLog;
    }
    
    /*
     * Returns file datas
     * @return array | false
     */
    public function getFileDatas(){
        
        // do we have the information
        if(!is_string($this->_strMainFileId) || empty($this->_strMainFileId) ||
           !isset($this->_arrFiles[$this->_strMainFileId])){
            // no
            return false;
        }
        
        return $this->_arrFiles[$this->_strMainFileId];
    }
    
    /*
     * Grab datas from the file and all contained urls
     * @ return true
     */
    public function grabDatas(){
        // cleaning log
        $this->_arrLog = array();
        // cleaning cache
        $this->_arrFiles = array();
        
        // logging datas
        $this->_log('Grabber ready with cache id : '.$this->getId());
        // init process
        $this->_intProcess = 0;
        // grabbing datas
        $this->_grabFileDatas();
        
        // getting file datas
        $arrDatas = $this->getFileDatas();
       
        // setting metadatas
        $strMetadatas = array(
            'filename'    => $arrDatas['filename'],
            'type'        => $arrDatas['type'],
            'url'         => $arrDatas['url'],
            'aweber-url'  => $arrDatas['orgurl'],
            'cache-id'    => $this->getId(),
        );
        
        // saving metadata
        $this->_getCache()->saveContent('metadatas.txt', base64_encode(serialize($strMetadatas)));
       
        // done
        return true;
    }

}
