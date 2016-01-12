<?php
namespace Form_Handler;

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

		// Necesitamos una configuración de formulario? En realidad, debiera tener un handler por defecto.
		if ( ! isset( $_REQUEST['FRM'] ) || ! $_REQUEST['FRM'] ) {
			wp_die( 'You need to know where you are going before you go there' );
		}

		// Pillo la configuración para los handlers
		$options = get_option( $this->pluginId );

		// Ver con qué "Form" estamos trabajando.
		$request_frm = isset( $_REQUEST['FRM'] ) ? $_REQUEST['FRM'] : 'default';


		if ( 'default' !== $request_frm && isset( $options['forms'] ) ) {

			// Filtro para pillar el formulario que venga en el request.
			$filter_forms = function ( $input, $callback = null ) use ( $request_frm ) {
				if ( isset( $input['slug'] ) && $input['slug'] === $request_frm ) {
					return $input;
				}

				return false;


			};

			$new_opts = array_filter( $options['forms'], $filter_forms );

			if (empty($new_opts)) {
				wp_die('No existe ningún formulario para esta dirección');
			}
			$value = $new_opts[0];
		}
		else {

		}

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
			$value['template']
		);

		foreach ( $value['email'] as $email ) {
			wp_mail( $email, 'mensaje de prueba', $new_msg );
		}

		wp_die( 998 );
	}
}