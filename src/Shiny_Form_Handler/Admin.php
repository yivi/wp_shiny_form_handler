<?php
namespace Shiny_Form_Handler;

/**
 * Class Admin
 * @package Shiny_Form_Handler
 */
class Admin {
	/**
	 * Option key, and option page slug
	 * @var string
	 */
	private $key = '';

	/**
	 * Options Page title
	 * @var string
	 */
	private $title = '';

	/**
	 * Options Page hook
	 * @var string
	 */
	private $options_page = '';


	/**
	 * Constructor
	 *
	 * @param string $key
	 *
	 * @since 0.1.0
	 */
	public function __construct( $key = '' ) {
		$this->key = $key;
	}


	/**
	 * Lil' getter.
	 *
	 * @return string
	 */
	public function getKey() {
		return $this->key;
	}


	/**
	 * Creamos el post-type pertinente
	 */
	public function _add_post_type() {
		register_post_type( 'shiny_form_handler', [
			'label'               => __( 'Formularios', 'shiny_form_handler' ),
			'labels'              => [
				'singular_name' => __( 'Formulario', 'shiny_form_handler' ),
				'add_new'       => _x( 'Configurar nuevo', 'formulario', 'shiny_form_handler' ),
				'add_new_item'  => __( 'Configurar nuevo formulario', 'shiny_form_handler' ),
				'new_item'      => __( 'Nuevo formulario', 'shiny_form_handler' ),
				'view_item'     => __( 'Ver formulario', 'shiny_form_handler' ),
				'edit_item'     => __( 'Editar Formulario', 'shiny_form_handler' )
			],
			'public'              => true,
			'exclude_from_search' => true,
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => false,
			'menu_position'       => 100,
			// FIXME: Capabilities wtf?
			'supports'            => [ 'title', 'author' ],
		] );
	}


	/**
	 * Renders the help tabs on top.
	 *
	 */
	public function _help_tab() {

		$screen = get_current_screen();

		// Return early if we're not on the book post type.
		if ( 'shiny_form_handler' != $screen->post_type ) {
			return;
		}

		// Setup help tab args.
		$args = [
			'id'      => 'shiny_form_handler_help',
			'title'   => 'Ayuda Formularios',
			'content' => '<h3>Punto de entrada</h3><p>El <code>ACTION</code> del form tiene que hacer un <code>POST</code> a el URL del permalink del formulario.</p><h3>Correos</h3><p>Se enviarán correos a todos lo especificados, usando la plantilla como cuerpo del mensaje, y reemplazando <code>[CODIGOS]</code> por el parámetro de post correspondiente (post validación y sanitización).</p>',
		];

		// Add the help tab.
		$screen->add_help_tab( $args );

	}

	/**
	 * Add menu options page
	 *
	 * Depends on CMB2.
	 *
	 * @since 0.1.0
	 */
	public function _add_options_page() {
		$this->options_page = add_menu_page( $this->title, $this->title, 'manage_options', $this->key, [
			$this,
			'admin_page_display',
		] );

		// Include CMB CSS in the head to avoid FOUC
		add_action( "admin_print_styles-{$this->options_page}", [ 'CMB2_hookup', 'enqueue_cmb_css' ] );
	}

	/**
	 * Generamos las metaboxes con CMB2.
	 *
	 * @todo Usar la llamada OOP de CMB2 y tener menos dependencias escondidas
	 * @todo Dejar de usar CMB2 y listo.
	 */
	public function _add_options_page_metabox() {

		/** @var \CMB2 $cmb */
		$cmb = new_cmb2_box( [
			'id'           => $this->getKey() . '_redirect',
			'object_types' => [ 'shiny_form_handler' ],
			'context'      => 'normal',
			'priority'     => 'high',
			'show_names'   => true,
			'title'        => 'Redirección'
		] );


		$cmb->add_field( [
			'name'    => __( 'Tipo respuesta', 'shiny_form_handler' ),
			'desc'    => __( 'Redirección o respuesta AJAX', 'shiny_form_handler' ),
			'id'      => $cmb->cmb_id . '_type',
			'type'    => 'radio',
			'default' => 'pre',
			'options' => [
				'pre'  => __( 'Redirección a página predefinida (abajo, en "URL Redirect"; o vía parámetro "redirect" en POST)', 'shiny_form_handler' ),
				'ajax' => __( 'Respuesta AJAX. Devuelve un JSON con información de estado.', 'shiny_form_handler' ),
			],
		] );


		$cmb->add_field( [
			'name' => __( 'URL Redirect después de envío (éxito)', 'shiny_form_handler' ),
			'desc' => __( 'Con dominio y query params incluidos. E.g.: http://www.example.com/thanks.php?param1=uno', 'shiny_form_handler' ),
			'id'   => $cmb->cmb_id . '_url_success',
			'type' => 'text_url',
		] );


		$cmb->add_field( [
			'name' => __( 'URL Redirect después de envío (fallo validación)', 'shiny_form_handler' ),
			'desc' => __( 'Con dominio y query params incluidos. E.g.: http://www.example.com/thanks.php?param1=uno', 'shiny_form_handler' ),
			'id'   => $cmb->cmb_id . '_url_fail',
			'type' => 'text_url',
		] );

		/** @var \CMB2 $validation */
		$validation = new_cmb2_box(
			[
				'id'           => $this->getKey() . '_validation',
				'object_types' => [ 'shiny_form_handler' ],
				'context'      => 'normal',
				'priority'     => 'high',
				'show_names'   => true,
				'title'        => __( 'Validación', 'shiny_form_handler' )
			]
		);

		$validation->add_field( [
			'desc'  => __( '¿Activar validación en el backend?', 'shiny_form_handler' ),
			'id'    => $validation->cmb_id . '_enable',
			'type'  => 'checkbox',
			'value' => 1
		] );

		$validation->add_field( [
			'desc'       => __( 'Reglas de validación', 'shiny_form_handler' ),
			'id'         => $validation->cmb_id . '_rules',
			'type'       => 'validation',
			'repeatable' => true,
		] );


		/** @var \CMB2 $redbox */
		$redbox = new_cmb2_box( [
			'id'           => $this->getKey() . '_email',
			'object_types' => [ 'shiny_form_handler' ],
			'context'      => 'normal',
			'priority'     => 'high',
			'show_names'   => true,
			'title'        => __( 'Ajustes Correo', 'shiny_form_handler' ),
		] );

		$redbox->add_field( [
			'name'  => __( 'Enviar correo', 'shiny_form_handler' ),
			'desc'  => __( 'Marcar para enviar correos después de procesar el formulario', 'shiny_form_handler' ),
			'id'    => $redbox->cmb_id . '_enable',
			'type'  => 'checkbox',
			'value' => 1
		] );

		$redbox->add_field( [
			'name'       => __( 'Email de Destino', 'shiny_form_handler' ),
			'desc'       => __( 'Un correo válido, por favor', 'shiny_form_handler' ),
			'id'         => $redbox->cmb_id . '_addresses',
			'type'       => 'text_email',
			'repeatable' => true,
			'options'    => [
				'group_title' => 'correos',
				'add_button'  => 'Añadir un destinatario',
			],
		] );

		$redbox->add_field( [
			'name'        => __( 'Asunto del mensaje', 'shiny_form_handler' ),
			'desc'        => __( 'También se pueden insertar <code>[campos]</code>.' ),
			'type'        => 'text',
			'placeholder' => 'Subject...',
			'id'          => $redbox->cmb_id . '_subject'
		] );

		$redbox->add_field( [
			'name' => __( 'Plantilla', 'gorditpr' ),
			'desc' => __( 'Usar [campo] para insertar campos del formulario. Consultar nombres de los campos con el creador del form.', 'shiny_form_handler' ),
			'id'   => $redbox->cmb_id . '_template',
			'type' => 'textarea',
		] );


	}


}