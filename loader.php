<?php 
/*
Plugin Name:		BuddyPress Elevator Pitch - Enhanced Member Cards
Plugin URI:        https://wordpress.org/plugins/bp-group-members-data/
Description:		This plugin allows you to choose which fields to display about group members, from the fields available on their user profile pages.
Version:			1.3
Author:				sooskriszta
Author URI: https://profiles.wordpress.org/sooskriszta
Text Domain:		BP-spdig
*/

if ( !defined( 'ABSPATH' ) ) exit; 

function bp_show_profile_data_include() { 
	require( dirname( __FILE__ ) . '/bp-show-profile-data.php' ); 
	load_plugin_textdomain( 'BP-spdig', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' ); 
} 
add_action( 'bp_include', 'bp_show_profile_data_include' ); 

/* Check activate or not BP plugin */
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

add_action('admin_init', function() {
    if ( !is_plugin_active( 'buddypress/bp-loader.php' ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );

        add_action( 'admin_notices', function() {
            ?>
            <div class="notice notice-error is-dismissible">
                <p><?php _e( '<a href="https://buddypress.org/" target="_blank">BuddyPress</a> plugin is required for "BP show profile data in group member" to work', 'BP-spdig' ); ?></p>
            </div>
            <?php
        });

        if ( isset( $_GET['activate'] ) ) {
            unset( $_GET['activate'] );
        }
    } 
});

?>
