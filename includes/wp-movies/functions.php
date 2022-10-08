<?php

function file_path_from_url( $url ) {
    $wp_upload_dir = wp_upload_dir();
    return str_replace($wp_upload_dir['baseurl'], $wp_upload_dir['basedir'], $url);
}

function url_from_file_path( $file_path ) {
    $wp_upload_dir = wp_upload_dir();
    return str_replace($wp_upload_dir['basedir'], $wp_upload_dir['baseurl'], $file_path);
}

function attach_media_to_post( $post_id, $image_url, $set_as_thumbnail=false, $title='', $content='', $status='inherit' ) {
    $filename = file_path_from_url( $image_url );
    $filetype = wp_check_filetype( basename( $filename ), null );
    $wp_upload_dir = wp_upload_dir();
    $attachment = array(
        'guid'           => $image_url, 
        'post_mime_type' => $filetype['type'],
        'post_title'     => $title,
        'post_content'   => $content,
        'post_status'    => $status
    );
    $attach_id = wp_insert_attachment( $attachment, $filename, $post_id );
    require_once( ABSPATH . 'wp-admin/includes/image.php' );
    $attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
    wp_update_attachment_metadata( $attach_id, $attach_data );
    if( $set_as_thumbnail ) {
        set_post_thumbnail( $post_id, $attach_id );
    }
    return $attach_id;
}

function get_attach_ids_for_post( $post_id ) {
    $attach_ids = array();
    $existing_media = get_post_meta( $post_id, '_zmovies_attach_ids', true );
    if($existing_media) {
        $attach_ids = explode(",", $existing_media);
    }
    return $attach_ids;
}

/*
    -- Credit to: http://pastebin.com/kgLt1RrG
	$meta_key    - What meta key to check against (required)
	$post_type   - What post type to check (default - post)
	$fields      - Whether to query all the post table columns, or just a select one ... all, titles, ids, or guids (all returns an array of objects, others return an array of values)
*/
function posts_without_meta( $meta_key = '', $post_type = 'post', $fields = 'all' ) {
	global $wpdb;
	
	if( !isset( $meta_key ) || !isset( $post_type ) || !isset( $fields ) )
		return false;
	
	// Meta key is required
	if( empty( $meta_key ) )
		return false;
	
	// All parameters are expected to be strings
	if( !is_string( $meta_key ) || !is_string( $post_type ) || !is_string( $fields ) )
		return false;
	
	if( empty( $post_type ) )
		$post_type = 'post';

	if( empty( $fields ) )
		$fields = 'all';
	
	// Since all parameters are strings, bind them into one for a cheaper preg match (rather then doing one for each)
	$possibly_unsafe_text = $meta_key . $post_type . $fields;
	
	// Simply die if anything not a letter, number, underscore or hyphen is present
	if( preg_match( '/([^a-zA-Z0-9_-]+)/', $possibly_unsafe_text ) ) {
		wp_die( 'Invalid characters present in call to function (valid chars are a-z, 0-9, A-Z, underscores and hyphens).' );
		exit;
	}
	
	switch( $fields ) :
		case 'ids':
			$cols = 'p.ID';
			break;
		case 'titles':
			$cols = 'p.post_title';
			break;
		case 'guids':
			$cols = 'p.guid';
			break;
		case 'all':
		default:
			$cols = 'p.*';
			break;
	endswitch;
	
	if( 'all' == $fields )
		$result = $wpdb->get_results( $wpdb->prepare( "
			SELECT $cols FROM {$wpdb->posts} p
			WHERE NOT EXISTS
			(
				SELECT pm.* FROM {$wpdb->postmeta} pm
				WHERE p.ID = pm.post_id
				AND pm.meta_key = '%s'
			)
			AND p.post_type = '%s'
			", 
			$meta_key, 
			$post_type
		) );
	// get_col is nicer for single column selection (less data to traverse)
	else 
		$result = $wpdb->get_col( $wpdb->prepare( "
			SELECT $cols FROM {$wpdb->posts} p
			WHERE NOT EXISTS
			(
				SELECT pm.* FROM {$wpdb->postmeta} pm
				WHERE p.ID = pm.post_id
				AND pm.meta_key = '%s'
			)
			AND p.post_type = '%s'
			", 
			$meta_key, 
			$post_type
		) );
	
	return $result;
}

/**
 * Generate Webp image format
 *
 * Uses either Imagick or imagewebp to generate webp image
 *
 * @param string $file Path to image being converted.
 * @param int $compression_quality Quality ranges from 0 (worst quality, smaller file) to 100 (best quality, biggest file).
 *
 * @return false|string Returns path to generated webp image, otherwise returns false.
 */
function dk_generate_webp_image($file, $compression_quality = 80)
{
 // check if file exists
 if (!file_exists($file)) {
  return false;
 }

 // If output file already exists return path
 $output_file = str_replace(array('.jpg', '.jpeg', '.png', '.gif'), '', $file) . '.webp';
 if (file_exists($output_file)) {
  return $output_file;
 }

 $file_type = str_replace('image/', '', strtolower(mime_content_type($file)));
 if (function_exists('imagewebp')) {
  switch ($file_type) {
    case 'jpeg':
    case 'jpg':
        $image = imagecreatefromjpeg($file);
    break;

    case 'png':
      $image = imagecreatefrompng($file);
      imagepalettetotruecolor($image);
      imagealphablending($image, true);
      imagesavealpha($image, true);
    break;

    case 'gif':
      $image = imagecreatefromgif($file);
    break;
    
    default:
    return false;
  }

  // Save the image
  $result = imagewebp($image, $output_file, $compression_quality);
  if (false === $result) {
   return false;
  }

  // Free up memory
  imagedestroy($image);

  return $output_file;
 } elseif (class_exists('Imagick')) {
  $image = new Imagick();
  $image->readImage($file);

  if ($file_type === 'png' || $file_type === 'jpg') {
   $image->setImageFormat('webp');
   $image->setImageCompressionQuality($compression_quality);
   $image->setOption('webp:lossless', 'true');
  }

  $image->writeImage($output_file);
  return $output_file;
 }
 return false;
}
?>