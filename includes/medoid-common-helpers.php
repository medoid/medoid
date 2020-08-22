<?php

if ( ! function_exists( 'array_get' ) ) {
	function array_get( $array, $key, $defaultValue = false ) {
		$keys = explode( '.', $key );
		foreach ( $keys as $key ) {
			if ( ! isset( $array[ $key ] ) ) {
				return $defaultValue;
			}
			$value = $array = $array[ $key ];
		}
		return $value;
	}
}

function is_medoid_debug() {
	return defined( 'MEDOID_DEBUG' ) && MEDOID_DEBUG;
}

function medoid_get_wp_image_sizes( $size ) {
	$sizes                        = array();
	$wp_additional_image_sizes    = wp_get_additional_image_sizes();
	$get_intermediate_image_sizes = get_intermediate_image_sizes();

	// Create the full array with sizes and crop info
	foreach ( $get_intermediate_image_sizes as $_size ) {
		if ( in_array( $_size, array( 'thumbnail', 'medium', 'large' ) ) ) {
			$sizes[ $_size ]['width']  = get_option( $_size . '_size_w' );
			$sizes[ $_size ]['height'] = get_option( $_size . '_size_h' );
			$sizes[ $_size ]['crop']   = (bool) get_option( $_size . '_crop' );
		} elseif ( isset( $wp_additional_image_sizes[ $_size ] ) ) {
			$sizes[ $_size ] = array(
				'width'  => $wp_additional_image_sizes[ $_size ]['width'],
				'height' => $wp_additional_image_sizes[ $_size ]['height'],
				'crop'   => $wp_additional_image_sizes[ $_size ]['crop'],
			);
		}
	}

	if ( $size ) {
		if ( isset( $sizes[ $size ] ) ) {
			return $sizes[ $size ];
		} else {
			return false;
		}
	}
	return $sizes;
}


function medoid_get_image_sizes( $size ) {
	if ( empty( $size ) ) {
		return false;
	}

	$height = 0;
	$width  = 0;
	if ( is_array( $size ) ) {
		$width  = array_get( $size, 0 );
		$height = array_get( $size, 1 );

		return array(
			'width'  => $width,
			'height' => $height,
		);
	}

	return medoid_get_wp_image_sizes( $size );
}

function medoid_create_file_name_unique( $new_file, $image, $medoid_cloud ) {
	if ( gettype( $image ) === 'object' ) {
		$attachment = get_post( $image->post_id );
		if ( ! $attachment ) {
			return false;
		}

		if ( $attachment->post_parent > 0 ) {
			$prefix = medoid_create_parent_prefix_from_post( $attachment );
		} else {
			$prefix = 'untils/';
		}
		$ret = sprintf( '%s%s', $prefix, $new_file );

		if ( ! $medoid_cloud instanceof Medoid_Cloud || $medoid_cloud->is_exists( $ret ) ) {
			$ret = sprintf( '%s/%s-%s', $prefix, date( 'Y-m-d-His' ), $new_file );
		}

		return $ret;
	}
	return sprintf( '%s-%s', date( 'Y-m-d-His' ), $new_file );
}

function medoid_create_parent_prefix_from_post( $post ) {
	$current_slug = '';
	if ( $post->post_parent > 0 ) {
		$parent = get_post( $post->post_parent );
		if ( $parent ) {
			$current_slug .= sprintf( '%s/', $parent->post_name );
			$current_slug  = sprintf( '%s%s', medoid_create_parent_prefix_from_post( $parent ), $current_slug );
		}
	}

	return $current_slug;
}

function update_image_guid_after_upload_success( $image, $response, $cloud ) {
	global $wpdb;
	if ( $wpdb->update( $wpdb->posts, array( 'guid' => $response->get_url() ), array( 'ID' => $image->post_id ) ) ) {
		delete_post_meta( $image->post_id, '_wp_attached_file' );
		Logger::get( 'medoid' )->debug(
			sprintf( 'Update attachment #%d with value "%s" is successful', $image->post_id, $response->get_url() )
		);
	} else {
		Logger::get( 'medoid' )->debug(
			sprintf( 'Update attachment #%d with value "%s" is failed', $image->post_id, $response->get_url() )
		);
	}
}
add_action( 'medoid_upload_cloud_image', 'update_image_guid_after_upload_success', 10, 3 );

function delete_image_files_after_upload( $image, $response, $cloud ) {
	if ( empty( $image->delete_local_file ) ) {
		return;
	}

	if ( $image->post_id > 0 ) {
		$attachment_id = $image->post_id;
		$meta          = wp_get_attachment_metadata( $attachment_id );
		$backup_sizes  = get_post_meta( $attachment_id, '_wp_attachment_backup_sizes', true );
		$file          = get_attached_file( $attachment_id );

		wp_delete_attachment_files( $attachment_id, $meta, $backup_sizes, $file );

		// Delete attachment sizes meta
		if ( isset( $meta['sizes'] ) ) {
			unset( $meta['sizes'] );
		}
		wp_update_attachment_metadata( $attachment_id, $meta );
	}
}
add_action( 'medoid_upload_cloud_image', 'delete_image_files_after_upload', 10, 3 );

// Convert file name to ASCII characters
function medoid_remove_accents_file_name( $filename ) {
	$extension = pathinfo( $filename, PATHINFO_EXTENSION );
	if ( $extension ) {
		$filename = str_replace( '.' . $extension, '', $filename );
		return sprint( '%s.%s', remove_accents( $filename ), $extension );
	}
	return remove_accents( $filename );
}
