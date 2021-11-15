<?php
/**
 * @package AweberFormsGrabber
 */
/*
Plugin Name: Aweber Forms Grabber
Plugin URI: https://linuxstairway.com
Description: Grab Aweber forms datas and files in order to use them locally and avoid ad blocking
Version: 1
Author: Sebastien Tiphaine
Author URI: https://linuxstairway.com
License: GPLv2 or later
Text Domain: AweberFormsGrabber
*/
// setting main namespace
namespace AweberFormsGrabber;

/*
 * loading PluginConfig Lib
 */
require_once dirname(__FILE__).'/libs/wpliteframe/PluginConfig.php';

/*
 * Sets your plugin var here
 * 
 */
//Libs\WPLiteFrame\PluginConfig::set($strVarName, $mValue);
 
/*
 * Initializing object
 * This will auto detect all parameters required by the plugin
 */
Libs\WPLiteFrame\PluginConfig::init(__NAMESPACE__);

// are we in admin mode
if(is_admin()){
    // yes
    // loading admin class
    Libs\WPLiteFrame\PluginConfig::loadAdmin('Settings', true);
}
else{
    // no
    wp_enqueue_script(
        'aweber-form-submit', 
        Libs\WPLiteFrame\PluginConfig::getInstance()->PluginPublicJsUrl.'/aweber-form-submit.js');
}
