<?php

namespace Shiny_Form_Handler;

/**
 * Class ValidationField
 *
 * Implementación del campo de reglas de validación usando el API de CMB2
 *
 * @package Shiny_Form_Handler
 */
class ValidationField {

	private $key;

	public function __construct( $key ) {
		$this->key = $key;
	}

	/**
	 * Enganchamos en los filters apropiados.
	 */
	public function setup() {

		add_filter( 'cmb2_sanitize_validation', [ $this, 'sanitize_validation_field' ], 10, 5 );
		add_filter( 'cmb2_types_esc_validation', [ $this, 'escape_validation_field' ], 10, 4 );
		add_filter( 'cmb2_render_validation', [ $this, 'render_validation_field' ], 10, 5 );
	}

	/**
	 * Función que pinta el campo en el metabox
	 *
	 * @param $field
	 * @param $value
	 * @param $object_id
	 * @param $object_type
	 * @param  $field_type
	 */
	function render_validation_field( $field, $value, $object_id, $object_type, $field_type ) {
		// fixme: eliminar los estilos inline. Much sadness.

		$value = wp_parse_args( $value, array(
			'fieldname'  => '',
			'validation' => '',
			'regexp'     => '',
		) );

		?>
		<div class="validation-box" style="float: left"><p><label for="<?php echo $field_type->_id( '_fieldname' ); ?>">Nombre
					Regla</label></p>
			<?php echo $field_type->text_medium( [
				'name'  => $field_type->_name( '[fieldname]' ),
				'id'    => $field_type->_id( '_fieldname' ),
				'value' => $value['fieldname'],
			] );
			?>
		</div>

		<div class="validation-box" style="float: left;"><p><label
					for="<?php echo $field_type->_id( '_validation' ); ?>">Tipo Validación</label></p>
			<?php echo $field_type->select( [
				'name'    => $field_type->_name( '[validation]' ),
				'id'      => $field_type->_id( '_validation' ),
				'options' => $this->get_validation_options( $value['validation'] ),
			] );
			?>
		</div>

		<div class="validation-box"><p><label for="<?php echo $field_type->_id( '_regexp' ); ?>">Regexp</label></p>
			/<?php echo $field_type->text_medium( [
				'name'  => $field_type->_name( '[regexp]' ),
				'id'    => $field_type->_id( '_regexp' ),
				'value' => $value['regexp'],

			] );
			?>/
		</div>

		<?php
	}

	/**
	 * Generamos el listado de <options> para el <select> de tipos de validación
	 *
	 * @param bool|false $selected Si le pasamos un option, intenta pillar poner el selected dónde corresponde
	 *
	 * @return string Los options.
	 */
	protected function get_validation_options( $selected = false ) {

		$validation_list = [
			'none'     => __( 'Sin Validación', 'shiny_form_handler' ),
			'required' => __( 'Obligatorio', 'shiny_form_handler' ),
			'email'    => __( 'Correo Electrónico', 'shiny_form_handler' ),
			'phone'    => __( 'Teléfono (genérico)', 'shiny_form_handler' ),
			'regexp'   => __( 'Expresión Regular', 'shiny_form_handler' )
		];

		$validation_options = '';
		foreach ( $validation_list as $value => $label ) {
			$validation_options .= '<option value="' . $value . '" ' . selected( $value, $selected, false ) . '>' . $label . '</option>';
		}

		return $validation_options;
	}


	/**
	 *
	 * @param $check
	 * @param $meta_value
	 * @param $object_id
	 * @param $field_args
	 * @param $sanitize_object
	 *
	 * @return array
	 */
	function sanitize_validation_field( $check, $meta_value, $object_id, $field_args, $sanitize_object ) {
		// if not repeatable, bail out.
		if ( ! is_array( $meta_value ) || ! $field_args['repeatable'] ) {
			return $check;
		}
		foreach ( $meta_value as $key => $val ) {
			$meta_value[ $key ] = array_map( 'sanitize_text_field', $val );
		}

		return $meta_value;
	}

	/**
	 * @param $check
	 * @param $meta_value
	 * @param $field_args
	 * @param $field_object
	 *
	 * @return array
	 */
	function escape_validation_field( $check, $meta_value, $field_args, $field_object ) {
		// if not repeatable, bail out.
		if ( ! is_array( $meta_value ) || ! $field_args['repeatable'] ) {
			return $check;
		}
		foreach ( $meta_value as $key => $val ) {
			$meta_value[ $key ] = array_map( 'esc_attr', $val );
		}
		// fixme: validar el regexp o algo?

		return $meta_value;
	}


}