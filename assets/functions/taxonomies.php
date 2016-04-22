<?php
/**
 * Functions for taxonomy creation.
 * Author: Willem Prins | SOMTIJDS
 * Project: Tandem
 * Date created: 31/03/2016
 *
 * @package Exchange Plugin
 **/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
};

/* Hook taxonomy creation to init. */
add_action( 'init', 'exchange_connect_category_to_story' );
add_action( 'init', 'exchange_create_tax_language' );
add_action( 'init', 'exchange_create_tax_location' );
add_action( 'init', 'exchange_create_tax_topic' );
add_action( 'init', 'exchange_create_tax_discipline' );
add_action( 'init', 'exchange_create_tax_methodology' );
add_action( 'init', 'exchange_create_tax_output' );

add_action( 'save_post_programme_round', 'exchange_create_tax_for_programme_round', 10, 3 );



function exchange_connect_category_to_story() {
	register_taxonomy_for_object_type( 'category', 'story' );
}

// Register language as taxonomy.
function exchange_create_tax_language() {
	register_taxonomy(
		'language',  // The name of the taxonomy. Name should be in slug form (must not contain capital letters or spaces).
		'story',	// Post type name.
		array(
			'hierarchical' => false,
			'sort'         => true,
			'label'        => 'Languages',  // Display name.
			'show_ui'      => true,
			'show_in_menu' => true,
			'show_in_quick_edit' => false,
			'meta_box_cb'  => false,
			'public'       => true,
			'query_var'    => true,
			'rewrite'      => array(
				'slug'       => 'language', // This controls the base slug that will display before each term.
				'with_front' => false, // Don't display the category base before.
			),
			'labels'       => array(
				'add_new_item' => 'Add new language tag',
			),
		)
	);
}

// Register theme as taxonomy.
function exchange_create_tax_topic() {
	register_taxonomy(
		'topic',  // The name of the taxonomy. Name should be in slug form (must not contain capital letters or spaces).
		array( 'collaboration', 'story' ), // Post type name.
		array(
			'hierarchical' => false,
			'sort'         => true,
			'label'        => __( 'Topics', EXCHANGE_PLUGIN ),  // Display name.
			'show_ui'      => true,
			'show_in_menu' => true,
			'show_admin_column' => true,
			'show_in_quick_edit' => false,
			'meta_box_cb'  => false,
			'public'       => true,
			'query_var'    => true,
			'rewrite'      => array(
				'slug'       => 'topics', // This controls the base slug that will display before each term.
				'with_front' => false, // Don't display the category base before.
			),
		)
	);
}

// Register methodologies as taxonomy.
function exchange_create_tax_methodology() {
	register_taxonomy(
		'methodology',  // The name of the taxonomy. Name should be in slug form (must not contain capital letters or spaces).
		array( 'collaboration' ), // Post type name.
		array(
			'hierarchical' => false,
			'sort'         => true,
			'label'        => __( 'Methodologies', EXCHANGE_PLUGIN ),  // Display name.
			'show_ui'      => true,
			'show_in_menu' => true,
			'show_in_quick_edit' => false,
			'meta_box_cb'  => false,
			'public'       => true,
			'query_var'    => true,
			'rewrite'      => array(
				'slug'       => 'methodologies', // This controls the base slug that will display before each term
				'with_front' => false, // Don't display the category base before.
			),
		)
	);
}

// Register discipline as taxonomy.
function exchange_create_tax_discipline() {
	register_taxonomy(
		'discipline',  // The name of the taxonomy. Name should be in slug form (must not contain capital letters or spaces).
		array( 'collaboration' ),	// Post type name.
		array(
			'hierarchical' => false,
			'sort'         => true,
			'label'        => __( 'Disciplines', EXCHANGE_PLUGIN ),  // Display name.
			'show_ui'      => true,
			'show_in_menu' => true,
			'show_in_quick_edit' => false,
			'meta_box_cb'  => false,
			'choose_from_most_used' => null,
			'public'       => true,
			'query_var'    => true,
			'rewrite'      => array(
				'slug'       => 'disciplines', // This controls the base slug that will display before each term.
				'with_front' => false, // Don't display the category base before.
			),
		)
	);
	register_taxonomy_for_object_type( 'discipline', 'collaboration' );

}

// Register output as taxonomy.
function exchange_create_tax_output() {
	register_taxonomy(
		'output', // The name of the taxonomy. Name should be in slug form (must not contain capital letters or spaces).
		'collaboration', // Post type name.
		array(
			'hierarchical' => false,
			'sort'         => true,
			'label'        => __( 'Output Types', EXCHANGE_PLUGIN ),  // Display name.
			'show_ui'      => true,
			'show_in_menu' => true,
			'show_in_quick_edit' => false,
			'meta_box_cb'  => false,
			'public'       => true,
			'query_var'    => true,
			'rewrite'      => array(
				'slug'       => 'output', // This controls the base slug that will display before each term.
				'with_front' => false, // Don't display the category base before.
			),
		)
	);
}

// Register location as taxonomy.
function exchange_create_tax_location() {
	register_taxonomy(
		'location',  // The name of the taxonomy. Name should be in slug form (must not contain capital letters or spaces).
		array( 'collaboration', 'story' ), // Post type name.
		array(
			'hierarchical' => false,
			'sort'         => true,
			'label'        => __( 'Locations', EXCHANGE_PLUGIN ),  // Display name.
			'show_ui'      => true,
			'show_in_menu' => true,
			'show_in_quick_edit' => false,
			'meta_box_cb'  => false,
			'public'       => true,
			'query_var'    => true,
			'rewrite'      => array(
				'slug'       => 'locations', // This controls the base slug that will display before each term.
				'with_front' => false, // Don't display the category base before.
			),
		)
	);
}

// Add taxonomies by checking against a sluggified $term name.
function add_taxo( $taxonomy, $term ) {
	$term_id = term_exists( htmlspecialchars( $term ), $taxonomy );
	if ( $term_id > 0 ) {
		//echo "existing term found";
		return $term_id;
	} else {
		//echo "adding " . $term . " to " . $taxonomy ;
		$result = wp_insert_term( htmlspecialchars( $term ), $taxonomy );
	}
}


// Create a new term when a new programme round is saved.
function exchange_create_tax_for_programme_round( $post_id, $post, $update ) {
	$name = $post->post_title;
	add_taxo( 'topic', $name );
};