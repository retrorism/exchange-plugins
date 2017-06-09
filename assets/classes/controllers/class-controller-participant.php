<?php
/**
 * Participant Controller
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

/**
 * Participant Controller class
 *
 * This controller contains all Participant logic
 *
 * @since 0.1.0
 **/
class ParticipantController extends BaseController {

	/**
	 * Map participant properties.
	 *
	 * @param object $participant Newly instantiated participant class object.
	 * @param object $post Participant post type object.
	 **/
	public function map_participant_basics() {

		// Mapping / aliasing title to name.
		$this->container->name = $this->container->title;

		// Set participant type
		if ( current_theme_supports( 'exchange_participant_types' ) ) {
			$this->set_participant_type();
		}

		// Mapping organisation data.
		if ( ! current_theme_supports( 'exchange_participant_profiles' ) ) {
			$this->set_organisation_data();

		} else {
			$this->set_participant_details();
			$this->set_featured_image('participant');
			$this->set_participant_location();
			$this->set_ordered_tag_list();
		}



		// Add update token
		$this->set_participant_update_form_link();
	}

	public function set_organisation_data() {
		$post_id = $this->container->post_id;
		$org_name = get_post_meta( $post_id, 'organisation_name', true );
		$org_short_name = get_post_meta( $post_id, 'organisation_short_name', true );
		$org_coords = get_post_meta( $post_id, 'organisation_location', true );
		$org_city = get_post_meta( $post_id, 'organisation_city', true );
		$org_country = get_post_meta( $post_id, 'organisation_country', true );
		$org_description = get_post_meta( $post_id, 'organisation_description', true );
		$org_website = get_post_meta( $post_id, 'organisation_website', true );
		$p_contactme = get_post_meta( $post_id, 'participant_email', true );

		if ( !empty( $org_name ) ) {
			$this->container->org_name = $org_name;
		}
		if ( !empty( $org_short_name ) ) {
			$this->container->org_short_name = $org_short_name;
		}
		if ( ! empty( $org_coords['address'] ) || ( ! empty( $org_coords['lat'] ) && ! empty( $org_coords['lng'] ) ) ) {
			$this->container->org_coords = $org_coords;
		}
		if ( ! empty( $org_city ) ) {
			$this->container->org_city = $org_city;
		}
		if ( ! empty( $org_country ) ) {
			$this->container->org_country = $org_country;
		}
		if ( ! empty( $org_description ) ) {
			$this->container->org_description = $org_description;
		}
		if ( ! empty( $org_website ) ) {
			$this->container->org_website = $org_website;
		}
		if ( !empty( $p_contactme ) ) {
			$this->container->set_contactme( $p_contactme );
		}
	}

	public function set_participant_details() {
		$post_id = $this->container->post_id;
		$p_meta = get_post_meta( $post_id, '', true );
		if ( empty( $p_meta ) ) {
			return;
		}
		foreach( $p_meta as $key => $value ) {
			if ( 0 !== strpos( $key, 'participant_' ) && 'participant_location' !== $key ) {
				continue;
			}
			if ( 'participant_email' === $key ) {
				$this->container->set_contactme( $value[0] );
			} elseif ( 'participant_location' === $key ) {
				// Double unserialization to allow for complicated ACF imports using a CSV importer
				$coords = maybe_unserialize( maybe_unserialize( $value[0] ) );
				if ( is_array( $coords )
					&& ! empty( $coords['lat'] ) 
					&& ! empty( $coords['lng'] ) ) {
					$this->container->org_coords = $coords;
				}
			} else {
				$this->container->details[ $key ] = $value[0];
			}
		}
	}

	protected function set_participant_type() {
		if ( ! current_theme_supports( 'exchange_participant_types' ) ) {
			return;
		}
		$post_id = $this->container->post_id;
		$terms = get_the_terms( $post_id, 'participant_type' );
		if ( ! empty( $terms ) && $terms[0] instanceof WP_Term ) {
			$this->container->participant_type = $terms[0];
		}
	}

	public function set_collaboration() {
		$post_id = $this->container->post_id;
		$collaboration = CollaborationController::get_collaboration_by_participant_id( $post_id );
		if ( ! empty( $collaboration ) ) {
			$this->container->collaboration = $collaboration;
		}
	}

	protected function set_participant_update_form_link() {
		$post_id = $this->container->post_id;
		$link = get_post_meta( $post_id, 'participant_update_form_link', true );
		$this->container->set_update_form_link( $link );
	}

	protected function set_participant_location() {
		$locations = array(
			'title' => $this->container->title,
			'link' => $this->container->link,
			'locations' => array(),
		);
		if ( $this->container->has_featured_image && ! empty( $this->container->featured_image->input['sizes'] ) ) {
			$locations['image'] = $this->container->featured_image->input['sizes']['thumbnail'];
		}
		$p['exchange_id'] = $this->container->post_id;
		if ( ! empty( $this->container->name ) ) {
			$p['name'] = $this->container->name;
		}
		if ( ! empty( $this->container->org_name ) ) {
			$p['org_name'] = $this->container->org_name;
		}
		if ( ! empty( $this->container->org_coords ) ) {
			$lat = floatval( $this->container->org_coords['lat'] );
			$lng = floatval( $this->container->org_coords['lng'] );
		}
		if ( ! empty( $lat ) && ! empty( $lng ) ) {
			$p['latlngs'] = array( $lat, $lng );
		} elseif ( ! empty( $this->container->org_city ) || ! empty( $this->container->org_coords['address'] ) ) {
			$geocoded_latlngs = $this->get_location_coords( $this->container );
			if ( ! empty( $geocoded_latlngs ) ) {
				$p['latlngs'] = $geocoded_latlngs;
			}
		}
		if ( ! empty( $this->container->org_city ) ) {
			$p['org_city'] = $this->container->org_city;
		}
		if ( current_theme_supports('exchange_participant_profiles') && ! empty( $this->container->details['participant_country'] ) ) {
			$p['country'] = $this->container->details['participant_country'];
		}
		if ( current_theme_supports('exchange_participant_types') && ! empty( $this->container->participant_type ) ) {
			$p['participant_type'] = $this->container->participant_type->slug;
		}
		if ( $p['latlngs'] ) {
			$locations['locations'][] = $p;
		}
		if ( count( $locations['locations'] ) >= 1 ) {
			$this->container->locations = $locations;
			$this->container->has_locations = true;
		}
	}
}
