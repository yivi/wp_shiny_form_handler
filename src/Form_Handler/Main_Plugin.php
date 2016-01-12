<?php
namespace Form_Handler;

class Main_Plugin {
	private $admin;
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
		add_action( 'admin_init', [ $this->admin, '_init_settings' ] );
		add_action( 'admin_menu', [ $this->admin, '_add_options_page' ] );
		add_action( 'cmb2_admin_init', [ $this->admin, '_add_options_page_metabox' ] );

		add_action( "cmb2_save_options-page_fields_{$this->admin->getMetaboxId()}", [
			$this->admin,
			'settings_notices',
		], 10, 2 );
	}

	/**
	 * Engancho el hook para el ajax URL
	 */
	public function hookup_ajax() {
		add_action( "wp_ajax_form_handler", [ $this->handler, '_handle_form' ] );
		add_action( "wp_ajax_nopriv_form_handler", [ $this->handler, '_handle_form' ] );
	}
}