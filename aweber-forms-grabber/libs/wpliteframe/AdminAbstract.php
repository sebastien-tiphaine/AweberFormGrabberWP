<?php
/**
 * @package AweberFormsGrabber
 */
 
namespace AweberFormsGrabber\Libs\WPLiteFrame;

// loading required libs
PluginConfig::loadLib('WPLiteFrame/PageAbstract');

/*
 * Base class containing all common tools
 * for building a setting page
 */
abstract class AdminAbstract extends PageAbstract{
    
    /**
     * Constructor.
     */
    public function __construct() {
    
        // are we in wp admin
        if(!is_admin()){
            // no
            throw new \Exception(__CLASS__.': This class cannot be called outside wp admin !');
        }
        
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
    }
    
    /*
     * Declare the menu using wordpress add_options_page function
     * use the following code to declare the rendrering function 
     * array(
     *           $this,
     *           'render'
     *       )
     */
    public abstract function admin_menu();

}
