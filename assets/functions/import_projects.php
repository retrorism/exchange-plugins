<?php
/**
 * Import functions
 * Author: Willem Prins | SOMTIJDS
 * Project: Tandem
 * Date created: 10/04/2016
 *
 * @package Exchange Plugin
 * TODO Cleanup and automation / cron_scheduling / hooking.
 **/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
};

function exchange_get_collab_args() {
	$collab_args = array(
		'post_type' => 'collaboration',
		'posts_per_page' => 1,
		'post_status' => 'draft',
		'meta_key' => 'collaboration_id',
		'fields' => 'ids'
	);
	return $collab_args;
}

function exchange_attach_participant_to_collab( $participant ) {
	$cid = get_post_meta( $participant->ID, 'collaboration_id', true);
	// Set as metavalue.
	$collab_args = exchange_get_collab_args();
	$collab_args['meta_value'] = $cid;
	// Create new query with this metavalue.
	$collab_query = new WP_Query( $collab_args );
	// Result is collab_post_id.
	$collab = $collab_query->posts[0];
	if ( empty( $collab ) ) {
		return;
	}

	// Get relationship data from collab post.
	$party = get_post_meta($collab,'participants',true);

	// Skip to next participant if collaboration already has this participant ID.
	if ( ! empty( $party ) && in_array( strval( $participant->ID ), $party, true ) ) {
		return;
	} else {
		if ( ! empty( $party ) ) {
			array_push( $party, $participant->ID );
		} else {
			$party[0] = $participant->ID;
		}
		update_field( $GLOBALS['EXCHANGE_PLUGIN_CONFIG']['ACF']['fields']['collaboration-participants'], $party, $collab );
		return $participant->ID;
	}
}

function exchange_participant_matcher() {
	$results = '';
	$args = array(
		'post_type' => 'participant',
		'posts_per_page' => -1,
		'post_status' => 'draft'
	);
	$query = new WP_Query( $args );
	// Find all participants.
	$participants = $query->posts;
	if( ! empty( $participants ) ) {
		foreach( $participants as $participant) {
			// Lookup collab ID.
			if ( ! $participant instanceof WP_Post ) {
				continue;
			}
			$pid = exchange_attach_participant_to_collab( $participant );
			if ( is_int( $pid ) ) {
				$results .= $pid . ', ';
			}
		}
	}
	if ( $results === '' ) {
		$results = "No unmatched or new participants found";
	}
	return $results;
}

function exchange_image_importer() {
	$result = array();
	$collab_args = array(
		'post_type' => 'collaboration',
		'posts_per_page' => -1,
		'post_status' => 'publish',
	);
	$query = new WP_Query($collab_args);
	$collabs = $query->posts;
	$upload_path = wp_get_upload_dir();
	if(!empty($collabs)) {
		$fail = 0;
		$success = 0;
		foreach($collabs as $collab) {
			//print_r( $collab );
			// lookup collab ID
			$name = $collab->post_name;
			// set as metavalue
			$name_lower = sanitize_title( strtolower( $name ) );
			$name_clean = str_replace( '-','_', $name_lower );
			$path = ABSPATH . 'collaborations/' . $name_clean . '.jpg';
			$file_clean = $name_clean . '.jpg';
			$path_clean = ABSPATH . 'collaborations/' . $file_clean ;
			$post_id = $collab->ID;
			$post_tag = exchange_get_post_tag_from_parent_id( $post_id );
			$post_tag_folder = $post_tag->slug;
			$upload_path_clean = $upload_path['path'] . '/' . $post_tag_folder . '/' . $file_clean ;

			echo 'post_id: ' . $post_id . '<br />';
			echo 'path: ' . $path . '<br />';
			echo 'file_clean: ' . $file_clean . '<br />';
			echo 'path_clean: ' . $path_clean . '<br />';
			echo 'upload_path_clean: ' . $upload_path_clean . '<br />';
			if ( file_exists( $path_clean ) ) {

				if ( has_post_thumbnail( $post_id ) ) {
					echo "yes<br />";
				} else {
					rename( $path_clean, $upload_path_clean );

					// Check the type of file. We'll use this as the 'post_mime_type'.
					$filetype = wp_check_filetype( $upload_path_clean, null );

					// Prepare an array of post data for the attachment.
					$attachment = array(
						'guid'           => $upload_path_clean,
						'post_mime_type' => $filetype['type'],
						'post_title'     => preg_replace( '/\.[^.]+$/', '', $collab->post_title ),
						'post_content'   => '',
						'post_status'    => 'inherit',
					);

					print_r( $attachment );
					//
					// // Insert the attachment.
					$attach_id = wp_insert_attachment( $attachment, $upload_path_clean, $post_id );
					//
					// // Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
					require_once( ABSPATH . 'wp-admin/includes/image.php' );
					//
					// // Generate the metadata for the attachment, and update the database record.
					$attach_data = wp_generate_attachment_metadata( $attach_id, $upload_path_clean );
					wp_update_attachment_metadata( $attach_id, $attach_data );
					//
					set_post_thumbnail( $post_id, $attach_id );
					exchange_check_for_post_tag( $attach_id );
				}
			} else {
				echo "no<br />";
				$fail++;
			}
		}
		echo "<hr>" . $success . " successes and " . $fail . " failures.";
	}
}

function exchange_image_tag_swap() {
	$attachment_args = array(
		'post_type' => 'attachment',
		'posts_per_page' => -1,
		'post_status' => 'any',
	);
	$query = new WP_Query($attachment_args);
	$attachments = $query->posts;
	//print_r( $query );
	if(!empty($attachments)) {
		foreach($attachments as $attachment) {
			echo "getting an attachment:";
			$terms = wp_get_post_terms( $attachment->ID, 'post_tag' );
			$tag = get_term_by('name', $terms[0]->name, 'media_category');
			//print_r( $tag );
			$result = wp_set_object_terms( $attachment->ID, $tag->slug, 'media_category', true );
			print_r( $result );
			echo "<hr />";
		}
	} else {
		echo "attachment not found";
	}
}

function exchange_collab_tagger() {
	$result = array();
	$collab_args = array(
		'post_type' => 'collaboration',
		'posts_per_page' => -1,
		'post_status' => 'publish',
	);
	$query = new WP_Query($collab_args);
	$collabs = $query->posts;
	$upload_path = wp_get_upload_dir();
	if(!empty($collabs)) {
		foreach($collabs as $collab) {
			$parent_id = wp_get_post_parent_id( $collab->ID );
			echo "parent_id: " . $parent_id . "<br />";
			if ( !empty( $parent_id ) ) {
				$parent_name = get_the_title( $parent_id );
				echo "parent_name: " . $parent_name . "<br />";
				echo "trying to attach<hr />";
				$term = get_term_by('name', $parent_name, 'post_tag' );
				if ( is_object( $term ) && get_class( $term ) === 'WP_Term' ) {
					$term = wp_set_object_terms( $collab->ID, $term->term_id, 'post_tag', true );
					echo $term->name;
				} else {
					echo "no term object< br />";
				}
			} else {
				echo "no parent<br />";
			}
		}
	}
}

function exchange_image_tagger() {
	$result = array();
	$collab_args = array(
		'post_type' => 'collaboration',
		'posts_per_page' => -1,
		'post_status' => 'publish',
	);
	$query = new WP_Query($collab_args);
	$collabs = $query->posts;
	$upload_path = wp_get_upload_dir();
	if(!empty($collabs)) {
		foreach($collabs as $collab) {
			if ( has_post_thumbnail( $collab ) ) {
				$parent_id = wp_get_post_parent_id( $collab->ID );
				echo "parent_id: " . $parent_id . "<br />";
				if ( !empty( $parent_id ) ) {
					$parent_name = get_the_title( $parent_id );
					echo "parent_name: " . $parent_name . "<br />";
					$thumb_id = get_post_thumbnail_id( $collab );
					echo "thumb_id : " . $thumb_id . "<br />";
					if ( $thumb_id > 0 ) {
						echo "trying to attach<hr />";
						$term = get_term_by('name', $parent_name, 'media_category' );
						if ( is_object( $term ) && get_class( $term ) === 'WP_Term' ) {
							$term = wp_set_object_terms( $thumb_id, $term->term_id, 'media_category', true );
						} else {
							echo "no term object< br />";
						}
						echo $term->name;
					}
				} else {
					echo "no parent<br />";
					$fail++;
				}
			} else {
				echo "no thumbnail<br />";
				continue;
			}

		}
		echo "<hr>" . $success . " successes and " . $fail . " failures.";
	}
}


function add_term_to_cid( $cid, $arr ) {
	$collab_args = array(
	  'post_type' => 'collaboration',
	  'posts_per_page' => 1,
	  'post_status' => 'draft',
	  'meta_key' => 'collaboration_id',
	);
	$collab_args['meta_value'] = $cid;
	// create new query with this metavalue
	$collab_query = new WP_Query($collab_args);
	// result is collab_post_id;
	if ( ! empty( $collab_query->posts ) ) {
		$collab = $collab_query->posts[0];
		if ( $collab->post_type == 'collaboration' ) {
			//print_r( $arr );
			foreach ( $arr as $tax => $terms ) {
				$ints = array();
				foreach ( $terms as $term ) {
					add_taxo( $tax, $term );
					$obj = get_term_by( 'name', htmlspecialchars( $term ), $tax, 'slug');
					if ( is_object( $obj ) && get_class( $obj ) === 'WP_Term' ) {
						if ( ! empty( $obj->term_id ) ) {
							$ints[] = intval( $obj->term_id );
						}
					}
				}
				$ints = array_unique( $ints );
				echo $collab->post_title . '<br />';
				$result = wp_set_object_terms( $collab->ID, $ints, $tax, true );
				print_r($result);
				echo '<hr />';
			}
		}
	}
}

function exchange_tag_importer() {
	$fh = fopen( EXCHANGE_PLUGIN_PATH . 'assets/taxonomies.csv', 'r' );

	if ($fh) {
		$line = 0; // 1
		$headers = array();
		while ( ( $row = fgetcsv( $fh ) ) !== false ) {
			// csv headings, so continue/ignore this iteration:
			if ($line == 0) { // 2
				$headers = $row;
				$line++; // 3
				continue; // 4
			}
			$col = 0;
			$cid = $row[0];
			$arr = array();
			foreach ( $row as $term ) {
				if ( ! empty( $term ) && $col > 0 ) {
					$tax = explode( '_', $headers[$col] )[0];
					echo $cid . ': ' . $tax . ': ' . $term . '<br />';
					$arr[$tax][] = $term;
					echo add_taxo( $tax, $term );
					add_term_to_cid( $cid, $arr );
				}
				$col++;
			}
			// Keep logic here to add to database, line 1 onwards
		}
	}

	fclose($fh);
}

function exchange_location_tags_by_participants() {
	$result = array();
	$collab_args = array(
		'post_type' => 'collaboration',
		'posts_per_page' => -1,
		'post_status' => 'draft',
	);
	$query = new WP_Query($collab_args);
	$collabs = $query->posts;
	if(!empty($collabs)) {
		foreach($collabs as $collab) {
			$cid = get_field( 'collaboration_id', $collab->ID );
			$locations = array();
			$arr = array();
			$exchange = BaseController::exchange_factory( $collab );
			foreach( $exchange->participants as $p ) {
				if ( ! empty( $p->org_city ) ) {
					$locations[] = $p->org_city;
				}
			}
			if ( ! empty( $locations ) ) {
				$arr['location'] = $locations;
				add_term_to_cid($cid, $arr);
			}
		}
	}
}

// function match_organisation_by_pid( $orgs, $pid ) {
// 	$participant_args = array(
// 	  'post_type' => 'participant',
// 	  'posts_per_page' => 1,
// 	  'post_status' => 'draft',
// 	  'meta_key' => 'participant_id',
// 	  'meta_value' => $pid,
// 	);
// 	$participant_query = new WP_Query($participant_args);
// 	if ( empty( $participant_query->posts ) ) {
// 		return;
// 	}
// 	$participant = $participant_query->posts[0];
//
// 	if ( $participant->post_type !== 'participant' ) {
// 		return;
// 	}
//
// 	if ( empty( $orgs[ $pid ] ) ) {
// 		return;
// 	}
// 	$org = $orgs[$pid];
// 	echo htmlspecialchars( $org ) .'<br />';
// 	update_field( 'organisation_name', $org, $participant->ID );
// }

// function exchange_organisation_name_matcher() {
// 	$fh = fopen( EXCHANGE_PLUGIN_PATH . 'organisation_names.csv', 'r' );
//
// 	if ($fh) {
// 		echo "yup";
// 		$orgs = array();
// 		while ( ( $row = fgetcsv( $fh ) ) !== false ) {
// 			print_r( $row);
// 			// csv headings, so continue/ignore this iteration:
// 			if ($line == 0) { // 2
// 				$line++; // 3
// 				continue; // 4
// 			}
// 			$col = 0;
// 			$pid = $row[0];
// 			$org = $row[1];
// 			$orgs[$pid] = $org;
// 			match_organisation_by_pid($orgs, $pid);
// 			// Keep logic here to add to database, line 1 onwards
// 			$line++;
// 		}
// 	}
//
// 	fclose($fh);
// }
