<?php
namespace Shiny_Form_Handler;

class Handler {

	private $pluginId;

	public function __construct( $pluginid ) {
		$this->pluginId = $pluginid;
	}

	/**
	 *
	 */
	public function _handle_form() {

		// Si no estamos en Debuglandia, sólo queremos posts.
		if ( $_SERVER['REQUEST_METHOD'] !== 'POST' && ! WP_DEBUG ) {
			wp_die( 'Post before get, such is the nature of this game.' );
		}

		$form         = get_post();
		$type         = get_post_meta( $form->ID, 'redirect_type', true );
		$redirect_url = get_post_meta( $form->ID, 'redirect', true );
		$emails       = get_post_meta( $form->ID, 'email', true );
		$template     = get_post_meta( $form->ID, 'template', true );
		$args = [
			'result' => 0,
			'params' => $_GET,
		];

		$args = apply_filters( 'gordit_sanitize', $args, $this->pluginId );

		$args = apply_filters( 'gordit_validate', $args, $this->pluginId );



		$new_msg = str_replace(
			array_map( function ( $item, $key ) {
				return "[" . $item . "]";
			}, array_keys( $args['params'] ) ),
			$args['params'],
			$template
		);

		foreach ( $emails as $email ) {
			wp_mail( $email, 'mensaje de prueba', $new_msg );
		}

		wp_die( 998 );
	}
}