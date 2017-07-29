<?php
/*
Plugin Name: r7k12 Integration plugin
Plugin URI:  http://in-soft.pro/soft/r7k12/
Description: r7k12 Integration plugin
Version:     0.1
Author:      IvanNikitin.com
Author URI:  https://ivannikitin.com/
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: r7k12
Domain Path: /lang
Namespace:	R7K12
*/
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/* Plugin global consts */
define( 'R7K12', 		'r7k12' );							// Text Domain
define( 'R7K12_FOLDER', plugin_dir_path( __FILE__ ) );		// Plugin folder
define( 'R7K12_URL', 	plugin_dir_url( __FILE__ ) );		// Plugin URL

/* Initialization plugin */
add_action( 'plugins_loaded', 'r7k12_init' );
function r7k12_init()
{
	require( R7K12_FOLDER . 'classes/plugin.php' );
	require( R7K12_FOLDER . 'classes/settings.php' );
	require( R7K12_FOLDER . 'classes/crm.php' );
	require( R7K12_FOLDER . 'classes/cf7.php' );
	new R7K12\Plugin( R7K12_FOLDER, R7K12_URL );
}

