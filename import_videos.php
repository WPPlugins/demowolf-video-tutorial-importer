<?php

    /*
    Plugin Name: Import Videos
    Plugin URI: http://www.prismitsystems.com
    Version: 1.0.0
    Description: Imports videos in wordpress in form of either posts or pages.
    Author: Prism I.T. Systems
    Author URI: http://www.prismitsystems.com
    */

    define('IMPORT_VIDEOS_PLUGIN_DIR', plugin_dir_path(__FILE__) );
    define('IMPORT_VIDEOS_PLUGIN_URL',plugin_dir_url( __FILE__ ));

    /* Include all files here */
    require_once(IMPORT_VIDEOS_PLUGIN_DIR."/includes/functions.php");
  
    /* Add menu to import video file */
    add_action('admin_menu', 'import_videos_admin_menu_hook');
    
    /* Perform actions on init */
    add_action('init','import_videos_init_hook');
    
    /* Hook to add scripts and style */
    add_action('admin_enqueue_scripts','import_videos_admin_enqueue_scripts_hook');
    
    add_action('wp_head','import_vide_wp_head_hook');
    