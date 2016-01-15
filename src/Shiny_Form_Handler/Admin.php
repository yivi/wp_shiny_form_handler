<?php
namespace Shiny_Form_Handler;

/**
 * CMB2 Theme Options
 * @version 0.1.0
 */
class Admin {
	/**
	 * Option key, and option page slug
	 * @var string
	 */
	private $key = '';

	/**
	 * Options page metabox id
	 * @var string
	 */
	private $metabox_id = '';

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
	 * @param $title string
	 *
	 * @param string $key
	 *
	 * @since 0.1.0
	 */
	public function __construct( $title = 'Default', $key = 'defaultpr' ) {
		// Set our title (menu id and page title)
		$this->title = $title;

		// This we use to save the options, and as a prefix for the metaboxes
		$this->key        = $key;
		$this->metabox_id = $key . "option_metabox";
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
	 * Getting getter all the time
	 *
	 * @return string
	 */
	public function getMetaboxId() {
		return $this->metabox_id;
	}


	/**
	 * Register our setting to WP
	 * @since  0.1.0
	 */
	public function _init_settings() {
		register_setting( $this->key, $this->key );
	}

	public function _add_post_type() {
		register_post_type( 'shiny_form_handler', [
			'label'               => __( 'Formularios', 'shiny_form_handler' ),
			'labels'              => [
				'singular_name' => __( 'Formulario', 'shiny_form_handler' ),
				'add_new'       => _x( 'Configurar nuevo', 'formulario', 'shiny_form_handler' ),
				'add_new_item'  => __( 'Configurar nuevo formulario', 'shiny_form_handler' ),
				'new_item'      => __( 'Nuevo formulario', 'shiny_form_handler' ),
				'view_item'     => __( 'Ver formulario', 'shiny_form_handler' ),
			],
			'public'              => true,
			'exclude_from_search' => true,
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => false,
			'menu_position'       => 100,
			// FIXME: Capabilities
			'supports'            => [ 'title', 'author' ],
		] );
	}


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
	 * Generates
	 */
	public function _add_options_page_metabox() {

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
			'id'      => $cmb->cmb_id . 'redirect_type',
			'type'    => 'radio',
			'default' => 'pre',
			'options' => [
				'pre'  => 'Redirección a página predefinida (abajo, en "URL Redirect")',
				'self' => 'Intenta devolver a la misma página desde la que se envió el formulario (No funciona en HTTPS)',
				'ajax' => 'Respuesta AJAX. Devuelve un JSON con información de estado.',
			],
		] );

		$cmb->add_field( [
			'name' => __( 'URL Redirect después de envío (éxito)', 'shiny_form_handler' ),
			'desc' => __( 'Con dominio y query params incluidos. E.g.: http://www.example.com/thanks.php?param1=uno', 'shiny_form_handler' ),
			'id'   => $cmb->cmb_id . '_redirect_success',
			'type' => 'text_url',
		] );


		$cmb->add_field( [
			'name' => __( 'URL Redirect después de envío (fallo validación, sin implementar)', 'shiny_form_handler' ),
			'desc' => __( 'Con dominio y query params incluidos. E.g.: http://www.example.com/thanks.php?param1=uno', 'shiny_form_handler' ),
			'id'   => $cmb->cmb_id . 'redirect_fail',
			'type' => 'text_url',
		] );


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
			'id'    => $redbox->cmb_id . '_mail_enable',
			'type'  => 'checkbox',
			'value' => 1
		] );

		$redbox->add_field( [
			'name'       => __( 'Email de Destino', 'shiny_form_handler' ),
			'desc'       => __( 'Un correo válido, por favor', 'shiny_form_handler' ),
			'id'         => $redbox->cmb_id . 'email',
			'type'       => 'text_email',
			'repeatable' => true,
			'options'    => [
				'group_title' => 'correos',
				'add_button'  => 'Añadir un destinatario',
			],
		] );

		$redbox->add_field( [
			'name'         => __( 'Asunto del mensaje', 'shiny_form_handler' ),
			'desc'         => __( 'También se pueden insertar <code>[campos]</code>.' ),
			'type'         => 'text',
			'place_holder' => 'Subject...',
			'id'           => $redbox->cmb_id . '_'
		] );

		$redbox->add_field( [
			'name' => __( 'Plantilla', 'gorditpr' ),
			'desc' => __( 'Usar [campo] para insertar campos del formulario. Consultar nombres de los campos con el creador del form.', 'shiny_form_handler' ),
			'id'   => $redbox->cmb_id . 'template',
			'type' => 'textarea',
		] );


	}

	/**
	 * Render 'address' custom field type
	 *
	 * @since 0.1.0
	 *
	 * @param array $field The passed in `CMB2_Field` object
	 * @param mixed $value The value of this field escaped.
	 *                                   It defaults to `sanitize_text_field`.
	 *                                   If you need the unescaped value, you can access it
	 *                                   via `$field->value()`
	 * @param int $object_id The ID of the current object
	 * @param string $object_type The type of object you are working with.
	 *                                   Most commonly, `post` (this applies to all post-types),
	 *                                   but could also be `comment`, `user` or `options-page`.
	 * @param object $field_type_object The `CMB2_Types` object
	 */
	function validation_field_render_cb( $field, $value, $object_id, $object_type, $field_type_object ) {

	}


	/**
	 * Admin page markup. Mostly handled by CMB2
	 * @since  0.1.0
	 *
	 * @deprecated
	 */
	public function admin_page_display() {
		?>
		<div class="wrap cmb2-options-page <?php echo $this->key; ?>">
			<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
			<?php cmb2_metabox_form( $this->metabox_id, $this->key ); ?>
		</div>
		<?php
	}

	/**
	 * Register settings notices for display
	 *
	 * @since  0.1.0
	 *
	 * @param  int $object_id Option key
	 * @param  array $updated Array of updated fields
	 *
	 * @deprecated
	 *
	 */
	protected function settings_notices( $object_id, $updated ) {
		if ( $object_id !== $this->key || empty( $updated ) ) {
			return;
		}

		add_settings_error( $this->key . '-notices', '', __( 'Settings updated.', 'gorditpr' ), 'updated' );
		settings_errors( $this->key . '-notices' );
	}


}