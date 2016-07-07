<?php
/**
 * Collaboration Class
 * Author: Willem Prins | SOMTIJDS
 * Project: Tandem
 * Date created: 11/2/2016
 *
 * @package Exchange Plugin
 **/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
};

/**
 * Collaboration CPT Class
 *
 * This class serves as the foundation for Tandem collaborations and other
 * storytellers.
 *
 * @since 0.1.0
 **/
class Collaboration extends Exchange {

	/**
	 * Ordered array for use in grid / single display.
	 *
	 * @since 0.1.0
	 * @access public
	 * @var array Ordered tag-list.
	 **/
	public $ordered_tag_list = array();

	/**
	 * The programme round this collaboration was a part of.
	 *
	 * @since 0.1.0
	 * @access public
	 * @var integer $programme_round Programme round post ID, defined as parent_id.
	 */
	public $programme_round;

	/**
	 * The participants that formed this collaboration.
	 *
	 * @since 0.1.0
	 * @access public
	 * @var array $participants List of 2-4 participant IDs
	 */
	public $participants = array();

	/**
	 * Participant check.
	 *
	 * @since 0.1.0
	 * @access public
	 * @var boolean $has_participants Whether there are any connected participants. Defaults to false.
	 */
	public $has_participants = false;

	/**
	 * Geo locations stored in associative array where participant IDs are key, and values
	 * are the organisation's names, lat, and long.
	 *
	 * @since 0.1.0
	 * @access public
	 * @var array $locations List that holds participants' location details.
	 */
	public $locations;

	/**
	 * Geo check.
	 *
	 * @since 0.1.0
	 * @access public
	 * @var boolean $has_locations When there's two or more geolocations added for mapping. Defaults to false.
	 */
	public $has_locations = false;

	/**
	 * Stories list.
	 *
	 * @since 0.1.0
	 * @access public
	 * @var array $stories For gathering all related stories
	 */
	public $stories = array();

	/**
	 * Story check.
	 *
	 * @since 0.1.0
	 * @access public
	 * @var boolean $has_stories When there's one or more stories shared by this collaboration.
	 */
	public $has_stories = false;

	/**
	 * Collab description
	 *
	 * @since 0.1.0
	 * @access public
	 * @var string $description Text describing the collaboration's (final) plan / outcome.
	 */
	public $description;

	/**
	 * Collab description check
	 *
	 * @since 0.1.0
	 * @access public
	 * @var bool $has_description See if description is available.
	 */
	public $has_description;

	/**
	 * Map
	 *
	 * @since 0.1.0
	 * @access public
	 * @var bool $has_description See if description is available.
	 */
	public $map_data;


	/**
	 * Constructor for collaboration objects.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param object $post Collaboration post object.
	 * @param string $context Optional. Added context for modifications.
	 * @param object $controller Optional. Add existing controller if you want.
	 **/
	public function __construct( $post, $context = '', $controller = null ) {
		Parent::__construct( $post, $context, $controller );
		// Add standard WordPress data
		$this->controller->map_collaboration_basics( $post );
		// Add featured image.

		if ( ! in_array( $context, array( 'griditem', 'simplemap' ) ) ) {
			$this->controller->map_full_collaboration();
		}
	}

	public function publish_related_stories( $context = '' ) {
		$grid_mods = array(
			'related' => 'has_stories'
		);
		if ( ! $this->has_stories ) {
			return;
		}
		$grid = new RelatedGrid( $this->stories, $this->type, $grid_mods );
		$grid->publish( $context );

	}

	/**
	 * undocumented function summary
	 *
	 * Undocumented function long description
	 *
	 * @param type var Description
	 * @return {11:return type}
	 */
	public function publish_collab_map( $context = '' ) {
		$input = array(
			'map_style' => 'network',
			'map_size'  => 'wide',
			'map_markers' => false,
			'map_collaborations' => array(
				0 => $this->post_id
			),
			'map_caption' => __( 'Showing a connection between two cities', 'exchange' ),
		);
		$map = new SimpleMap( $input, 'collaboration' );
		if ( ! empty( $map ) ) {
			$map->publish();
		}
	}
}
