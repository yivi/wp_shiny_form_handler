<?php
/**
 * @category     WordPress_Plugin
 * @package      Shiny_Form_Handler
 * @author       Yivi
 * @license      GPL-2.0+
 * @link         http://www.yivoff.com
 *
 * Plugin Name: Super Gordit
 * Plugin URI: http://www.yivoff.com
 * Description: blah, blah, blah
 * Author: yivi
 * Version: 1.0.1
 * @version 1.0.1
 *
 * Text domain: shiny_form_handler
 */

namespace Shiny_Form_Handler;

/**
 * ID único que usamos en algún sitio del plugin.
 */
define( 'SHINY_FORM_ID', 'shinyform' );


/**
 * Número de versión, por si sirivera para algo.
 */
define( 'SHINY_FORM_VERSION', '1.0' );

// Librería CMB2, que uso para generar los forms
require_once( dirname( __FILE__ ) . '/inc/CMB2/init.php' );

// Pretty Autoloader
require_once( dirname( __FILE__ ) . '/autoload.php' );

// Cuando está todo cargado empezamos a trabajar.
add_action( 'plugins_loaded', 'Shiny_Form_Handler\form_generic_handler_startup' );


/**
 *  Main Plugin Routine
 */
function form_generic_handler_startup() {

	$key = 'shinyform';

	$admin   = new Admin( $key );
	$handler = new Handler( $key );

	$plugin = new Main_Plugin( $admin, $handler );

	if ( is_admin() ) {
		$plugin->hookup_admin();
	};

	$plugin->hookup_ctps();
	$plugin->hookup_ajax();
}

/**
 * Rewrite rules on activation, for greater victory.
 */
function form_generic_handler_activation() {
	$admin = new Admin( 'Formularios', 'shiny_form_handler' );

	$admin->_add_post_type();

	flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'Shiny_Form_Handler\form_generic_handler_activation' );
