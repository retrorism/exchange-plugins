<?php
/**
 * Related Content Grid Class File
 * Author: Willem Prins | SOMTIJDS
 * Project: Tandem
 * Date created: 12/04/2016
 *
 * @package Exchange Plugin
 **/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
};

/**
 * Related Grid Class
 *
 * This class serves as the basis for Related post views.
 *
 * @since 0.1.0
 **/
class RelatedGrid extends BaseGrid {
	/**
	 * Overwrite initial output value for Grid blocks
	 *
	 * @since 0.1.0
	 * @access protected
	 **/
	 protected function create_output() {

		// If a grid is created inside a story, make this into an aside class.
		if ( is_single() || is_page() ) {
			$el = 'aside';
		} else {
			$el = 'div';
		}
		$colour = '#' . $GLOBALS['EXCHANGE_PLUGIN_CONFIG']['COLOURS']['salmon-1-web'];
		$this->set_grid_items();
		 // Create grid with posts embedded.
		if ( $this->has_grid_items ) {
			$this->set_modifier_class( 'colour', $colour );
			$this->set_attribute( 'data', 'background-colour', $colour );
			$this->set_attribute( 'data', 'equalizer', true );
			$this->set_attribute( 'data', 'equalize-on', 'medium' );
			$this->set_attribute('data','children', count( $this->grid_items ) );
			$header_text = __( 'Read more','exchange' );
			if ( 'collaboration' === $this->context && 'has_stories' === $this->modifiers['related'] ) {
				$header_text = __( 'Shared stories','exchange');
			}
			$header = new SectionHeader( $header_text, $this->element );
			$this->output .= $header->embed();
			$this->output_tag_open( $el );
			foreach ( $this->grid_items as $item ) {
				$this->output .= $item->embed();
			}
			$this->output_tag_close( $el );
		}
	}
}
