<?php
namespace Shiny_Form_Handler;

/**
 * Class Main_Plugin
 * @package Shiny_Form_Handler
 */
class Main_Plugin {
	/**
	 * @var Admin
	 */
	private $admin;
	/**
	 * @var Handler
	 */
	private $handler;

	/**
	 * Main_Plugin constructor.
	 *
	 * @param Admin $admin
	 * @param Handler $handler
	 */
	public function __construct( Admin $admin, Handler $handler ) {
		$this->admin   = $admin;
		$this->handler = $handler;
	}

	/**
	 * Engancho los hooks administrativos
	 *
	 */
	public function hookup_admin() {
		add_action( 'cmb2_admin_init', [ $this->admin, '_add_options_page_metabox' ] );

		add_action( 'admin_head', [ $this->admin, '_help_tab' ] );
	}

	/**
	 * Engancho el hook para el ajax URL
	 */
	public function hookup_ajax() {
		add_action( "wp_ajax_form_handler", [ $this->handler, '_handle_form' ] );
		add_action( "wp_ajax_nopriv_form_handler", [ $this->handler, '_handle_form' ] );

		add_filter( 'template_redirect', [$this->handler, '_handle_form' ], 99 );
	}

	/**
	 * Activo el custom post type
	 */
	public function hookup_ctps() {
		add_action( 'init', [ $this->admin, '_add_post_type' ] );
	}

}