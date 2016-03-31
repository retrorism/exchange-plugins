<?php
/**
 * Paragraph Class
 * Author: Willem Prins | SOMTIJDS
 * Project: Tandem
 * Date created: 07/03/2016
 *
 * @package Exchange Plugin
 **/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Paragraph pattern class.
 *
 * This class serves to build paragraph elements.
 *
 * @since 0.1.0
 **/
class Paragraph extends BasePattern {

	/**
	 * Constructor for Paragraphs.
	 *
	 * At instantiation this method checks if input is a string and is not empty.
	 *
	 * @since 0.1.0
	 * @access public
	 *
	 * @param mixed  $input Pattern content as defined in ACF input values.
	 * @param string $parent Optional. String referring to pattern.
	 * @param array  $modifiers Optional. Additional modifiers that influence look and functionality.
	 **/
	function __construct( $input, $parent = '', $modifiers = array() ) {
		Parent::__construct( $input );

		if ( 'string' === gettype( $input ) && ! empty( $input ) ) {
			$this->output_tag_open();
			$this->output .= $input;
			$this->output_tag_close();
		}
	}
}
