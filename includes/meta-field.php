<?php
/** Music METABOX **/
add_action( 'cmb2_admin_init', 'music_metaboxes' );
function music_metaboxes() {

	$cmb = new_cmb2_box( array(
		'id'            => 'music',
		'title'         => __( 'Music', 'duke-yin-helper' ),
		'object_types'  => array( 'music', ), // Post type
		'context'       => 'normal',
		'priority'      => 'low',
		'show_names'    => true, 
	) );

	$cmb->add_field( array(
		'name' => esc_html__( 'Music file', 'duke-yin-helper' ),
		'desc' => esc_html__( 'The music file or URL for the song, if the URL is external for your website, obtaining information from file may fail due to the CORS policy.', 'duke-yin-helper' ),
		'id'   => '_music_file',
		'type' => 'file',
		'text'    => array('add_upload_file_text' => esc_html__( 'Upload file', 'duke-yin-helper' )),
		'after_field' => '<a id="getid3" class="button button-primary button-large">'.esc_html__( 'Get info from file', 'duke-yin-helper' ).'</a><span class="spinner"></span>',
		
		'query_args' => array(
			'type' => array(
				'audio',
			),
		),
	) );
	
	$cmb->add_field( array(
		'name' => esc_html__( 'Album', 'duke-yin-helper' ),
		'desc' => esc_html__( 'The album name that this music belong to.', 'duke-yin-helper' ),
		'id'   => '_album',
		'type' => 'text',
	) );

	$cmb->add_field( array(
		'name' => esc_html__( 'Album Art', 'duke-yin-helper' ),
		'desc' => esc_html__( 'The album artwork.', 'duke-yin-helper' ),
		'id'   => '_album_art',
		'type' => 'file',
		'text'    => array('add_upload_file_text' => esc_html__( 'Upload file', 'duke-yin-helper' )),
		'query_args' => array(
		'type' => array('image/gif','image/jpeg','image/png',),
		'preview_size' => 'small',
		),
	) );

	$cmb->add_field( array(
		'name' => esc_html__( 'Year', 'duke-yin-helper' ),
		'desc' => esc_html__( 'The year this music was released.', 'duke-yin-helper' ),
		'id'   => '_year',
		'type' => 'text_date_timestamp',
		'date_format' => 'Y',
		'attributes' => array(
			'data-datepicker' => json_encode( array(
				'yearRange' => '-100:+0',
			) ),
		),
	) );
	
	$cmb->add_field( array(
		'name' => esc_html__( 'Lyrics', 'duke-yin-helper' ),
		'desc' => esc_html__( 'The lyrics file url of this song, accept .lrc file.', 'duke-yin-helper' ),
		'id'   => '_lrc',
		'type' => 'file',
		'text'    => array('add_upload_file_text' => esc_html__( 'Add file', 'duke-yin-helper' )),
	) );

}