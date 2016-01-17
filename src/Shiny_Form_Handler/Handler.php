<?php
namespace Shiny_Form_Handler;

/**
 * Class Handler
 * @package Shiny_Form_Handler
 */
class Handler {

	/**
	 * @var int El key que usaremos para generar las metaboxes y los custom meta. poca cosa la verdad.
	 */
	private $key;

	/**
	 * @var array Guardamos el request aquí, no sé para qué
	 */
	private $args;

	/**
	 * Handler constructor.
	 *
	 * @param $key
	 */
	public function __construct( $key ) {
		$this->key = $key;
	}

	/**
	 * @param array $args
	 *
	 * @param \WP_Post $form
	 *
	 * @return mixed
	 * @todo hacer que esto haga algo
	 */
	protected function validate( $args, $form ) {

		$args = apply_filters( 'shiny_pre_validate', $args, $form->key );


		return $args;
	}

	/**
	 * Mandamos los correos y aplicamos los filtros. Devolvemos $args, que no debiera haber sufrido cambios.
	 *
	 * @param \WP_Post $form
	 * @param array $args
	 *
	 * @return array
	 */
	protected function email( $form, $args ) {
		/**
		 * Se dispara al cargar la configuración de direcciones de correo.
		 *
		 * @param string $email_address Direcciones de e-mail a las que querían escribir
		 * @param array $args Los parámetros que hemos recibido vía POST
		 *
		 * @since 1.0
		 */
		$emails = apply_filters( 'shiny_form_emails', get_post_meta( $form->ID, $this->key . '_email_addresses', true ), $args['params'] );


		/**
		 * Se dispara al cargar la el string para el subject
		 *
		 * @param string $email_address Direcciones de e-mail a las que querían escribir
		 * @param array $args Los parámetros que hemos recibido vía POST
		 *
		 * @since 1.0
		 */
		$subject = apply_filters( 'shiny_form_subject', get_post_meta( $form->ID, $this->key . '_email_subject', true ), $args['params'] );


		/**
		 * Se dispara al cargar la configuración de direcciones de correo.
		 *
		 * @param string $email_address Direcciones de e-mail a las que querían escribir
		 * @param array $args Los parámetros que hemos recibido vía POST
		 *
		 * @since 1.0
		 */
		$template = apply_filters( 'shiny_form_template', get_post_meta( $form->ID, $this->key . '_email_template', true ), $args['params'] );

		$new_msg = str_replace(
			array_map(
				function ( $item ) {
					return "[" . $item . "]";
				},
				array_keys( $args['params'] )
			), // convertimos todos los params keys a formato [param_key], para buscarlos en el array de parámetros.
			$args['params'],
			$template
		);

		// enviamos correos
		foreach ( $emails as $email ) {
			wp_mail( $email, $subject, $new_msg );
			$args['mails_sent'] ++;
		}

		return $args;
	}

	/**
	 *  Comporobamos las redirecciones, aplicamos los filtros, y redirigimos al infinito y más allá
	 *
	 * @param array $args
	 *
	 * @param \WP_Post $form
	 *
	 * @return null
	 */
	protected function redirect( $args, $form ) {

		// Pillamos el url para redirigir de las opciones del formulario
		$redirect_url = get_post_meta( $form->ID, $this->key . '_redirect_url_success', true );


		// si viene un query param 'redirect' y parece ser un post id, sobreescribimos $redirect_url
		if ( isset( $_REQUEST['redirect'] ) && is_int( $_REQUEST['redirect'] ) ) {
			$permalink    = get_the_permalink( $_REQUEST['redirect'] );
			$redirect_url = $permalink;
		}

		// y si alguien aplicó un filtro específicamente para este
		/**
		 * Se dispara después de ver si el url venía definido por configuración o si venía en el form post.
		 *
		 * @param string $redirect_url El URL tal como llegó hasta aquí
		 * @param array $args Los parámetros que hemos recibido vía POST
		 *
		 * @since 1.0
		 */
		$redirect_url = apply_filters( 'shiny_form_redirect_success', $redirect_url, $args['params'] );

		// Y todo igual para el redirect en caso de error de validación
		$redirect_url_fail = get_post_meta( $form->ID, $this->key . '_redirect_url_fail', true );


		if ( isset( $_REQUEST['redirect_fail'] ) && is_int( $_REQUEST['redirect_fail'] ) ) {
			$permalink         = get_the_permalink( $_REQUEST['redirect_fail'] );
			$redirect_url_fail = $permalink;
		}

		// y si alguien aplicó un filtro específicamente para este
		/**
		 * Se dispara después de ver si el url venía definido por configuración o si venía en el form post.
		 *
		 * @param string $redirect_url_fail El URL tal como llegó hasta aquí
		 * @param array $args Los parámetros que hemos recibido vía POST
		 *
		 * @since 1.0
		 */
		$redirect_url_fail = apply_filters( 'shiny_form_redirect_fail', $redirect_url_fail, $args['params'] );


		// y si pese a todos nuestros esfuerzos sigue vacío...
		if ( empty( $redirect_url_fail ) ) {
			$redirect_url_fail = $redirect_url;
		}

		if ( filter_var( $redirect_url, FILTER_VALIDATE_URL ) && 'http' === substr( $redirect_url, 0, 4 ) ) {


			if ( $args['validated'] ) {
				wp_redirect( add_query_arg( $args['extra_args'], $redirect_url ) );
				die();
			} else {
				wp_redirect( add_query_arg( $args['extra_args'], $redirect_url_fail ) );
				die();
			}


		} else {
			wp_die( 'Nowhere to hide. Nowhere to go.' );
		}

		return null;
	}

	/**
	 * Imprimimos el JSON
	 *
	 * @param array $args
	 * @param \WP_Post $form
	 */
	protected function output_json( $args, $form ) {

		do_action( 'shiny_form_print_output', $form, $args );
		header( 'Content-type: application/json' );
		echo json_encode( $args );
		die();
	}

	/**
	 * Procesamos el form request
	 *
	 */
	public function _handle_form() {
		$this->args = $_REQUEST;
		// ??

		// Si no estamos en Debuglandia, sólo queremos posts.
		if ( $_SERVER['REQUEST_METHOD'] !== 'POST' && ! WP_DEBUG ) {
			wp_die( 'Post before get, such is the nature of this game.' );
		}

		// si no estamos procesando un form handler, salimos de aquí y dejamos que la vida siga su curso en WP.
		if ( 'shiny_form_handler' !== get_post()->post_type ) {
			return;
		}

		$form = get_post();
		$args = [
			'result'       => 0,
			'params'       => $_REQUEST,
			'validated'    => true,
			'fields_faild' => [ ],
			'mails_sent'   => 0,
			'extra_params' => [ ],
		];


		// fixme: A ver cómo se resuelve la validación...
		$args = $this->validate( $args, $form );

		$args = apply_filters( 'shiny_post_validate', $args, $this->key );

		// fixme: en uno de los filtros anteriores aprovecharíamos para mandar las cosas al Servicio de Datos


		if ( get_post_meta( $form->ID, $this->key . '_email_enable', true ) ) {
			$this->email( $form, $args );
		}

		// si han elegido una redirección
		if ( 'pre' === get_post_meta( $form->ID, $this->key . '_redirect_type', true ) ) {
			$this->redirect( $args, $form );
		} elseif ( 'ajax' === get_post_meta( $form->ID, $this->key . '_redirect_type', true ) ) {
			// Y si en lugar de redirección quieren respuesta ajax
			$this->output_json( $args, $form );
		}
	}
}