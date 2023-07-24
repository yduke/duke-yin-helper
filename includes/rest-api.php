<?php
//all together
// [domain]/wp-json/dk/v1/all?per_page=10&page=1
add_action( 'rest_api_init', function() {
	register_rest_route( 'dk/v1', '/all', [
		'method'   => WP_REST_Server::READABLE,
		'callback' => 'all_posts_combined',
	] );
} );

function all_posts_combined( $request ) {
    global $post;
	$arr = array(
		'post_type' => array( 
            'post',
            'slideshow',
            'portfolio',
            'photo',
            'selfie',
            'product',
            'film_review',
            'tvshow_review',
            'game_review',
            'book_review',
            'product_review',
            'testimonial',
            'service-type',
            'videos',
        ),
		'post_status' => 'publish',
		'orderby' => 'publish_date',
		'order' => 'DESC',
		'posts_per_page' => ($_REQUEST['per_page'] ?? 10),
        'paged' => ($_REQUEST['page'] ?? 1),
    );
	$query = new WP_Query($arr);
	$posts = $query->get_posts();

    if ( $posts ) {
        foreach ( $posts as $k=>$post ) {
            setup_postdata( $post );
            $post_id = $post->ID;
            $title = $post->post_title;
            $subtitle = get_post_meta( $post_id, '_headline', true );
			$post_type = $post->post_type;
            if($post_type=='post'){$post_type='blog';}
            $review_status = get_post_meta( $post_id, '_r_now', true );
			$data[] = array(
				// 'site'              =>site_url(),
				'content_type'              =>$post_type,
				'link'              =>get_permalink($post_id),
				'name'              =>$post->post_title,
				'subtitle'          =>$subtitle,
				'time'				=>get_post_time('U',false,$post_id,false),
				'description'		=>get_the_excerpt($post_id)??'',
				'cover'             =>array(
                    's' =>get_the_post_thumbnail_url($post_id,'thumbnail'),
                    'f' =>get_the_post_thumbnail_url($post_id,'full'),
                ),
			);
            switch($post_type){
                case 'blog':
                    $cat = get_the_category($post_id)[0]->name;
                    if($cat==='poetry'){
                        $data[$k]['status'] = __('Added a poem','duke-yin-helper');
                    }else{
                        $data[$k]['status'] = __('Added a blog post','duke-yin-helper');
                    }
                    break;
                case 'slideshow':
                    $data[$k]['status'] = __('Added a cover post','duke-yin-helper');
                    break;
                case 'portfolio':
                    $data[$k]['status'] = __('Added a portfolio','duke-yin-helper');
                    break;
                case 'selfie':
                    $data[$k]['status'] = __('Added a selfie','duke-yin-helper');
                    break;
                case 'product':
                    $data[$k]['status'] = __('Added a product','duke-yin-helper');
                    break;
                case 'film_review':
                    $data[$k]['status'] = __('Added a film review','duke-yin-helper');
                    switch($review_status){
                        case '0';
                            $data[$k]['review_status'] = __('Watched','duke-yin-helper');
                            break;
                        case '1';
                            $data[$k]['review_status'] = __('Watching','duke-yin-helper');
                            break;
                        case '2';
                            $data[$k]['review_status'] = __('Wish to watch','duke-yin-helper');
                            break;
                        case '3';
                            $data[$k]['review_status'] = __('Watched times','duke-yin-helper');
                            break;
                        case '4';
                            $data[$k]['review_status'] = __('Half watched','duke-yin-helper');
                            break;
                        default:
                            $data[$k]['review_status'] = __('Watched','duke-yin-helper');
                    }
                    $data[$k]['score'] = get_post_meta($post_id, 'ranking-score', true);
                    break;
                case 'tvshow_review':
                    $data[$k]['status'] = __('Added a TV show review','duke-yin-helper');
                    switch($review_status){
                        case '0';
                            $data[$k]['review_status'] = __('Watched','duke-yin-helper');
                            break;
                        case '1';
                            $data[$k]['review_status'] = __('Watching','duke-yin-helper');
                            break;
                        case '2';
                            $data[$k]['review_status'] = __('Wish to watch','duke-yin-helper');
                            break;
                        case '3';
                            $data[$k]['review_status'] = __('Watched times','duke-yin-helper');
                            break;
                        case '4';
                            $data[$k]['review_status'] = __('Half watched','duke-yin-helper');
                            break;
                        default:
                            $data[$k]['review_status'] = __('Watched','duke-yin-helper');
                    }
                    $data[$k]['score'] = get_post_meta($post_id, 'ranking-score', true);
                    break;
                case 'game_review':
                    $data[$k]['status'] = __('Added a game review','duke-yin-helper');
                    switch($review_status){
                        case '0';
                            $data[$k]['review_status'] = __('Played','duke-yin-helper');
                            break;
                        case '1';
                            $data[$k]['review_status'] = __('Playing','duke-yin-helper');
                            break;
                        case '2';
                            $data[$k]['review_status'] = __('Wish to play','duke-yin-helper');
                            break;
                        case '3';
                            $data[$k]['review_status'] = __('Cleared','duke-yin-helper');
                            break;
                        case '4';
                            $data[$k]['review_status'] = __('Not Cleared','duke-yin-helper');
                            break;
                        default:
                            $data[$k]['review_status'] = __('Played','duke-yin-helper');
                    }
                    $data[$k]['score'] = get_post_meta($post_id, 'ranking-score', true);
                    break;
                case 'book_review':
                    $data[$k]['status'] = __('Added a book review','duke-yin-helper');
                    switch($review_status){
                        case '0';
                            $data[$k]['review_status'] = __('Read','duke-yin-helper');
                            break;
                        case '1';
                            $data[$k]['review_status'] = __('Reading','duke-yin-helper');
                            break;
                        case '2';
                            $data[$k]['review_status'] = __('Wish to read','duke-yin-helper');
                            break;
                        case '3';
                            $data[$k]['review_status'] = __('Have read','duke-yin-helper');
                            break;
                        case '4';
                            $data[$k]['review_status'] = __('Unfinished reading','duke-yin-helper');
                            break;
                        default:
                            $data[$k]['review_status'] = __('Read','duke-yin-helper');
                    }
                    $data[$k]['score'] = get_post_meta($post_id, 'ranking-score', true);
                    break;
                case 'product_review':
                    $data[$k]['status'] = __('Added a product review','duke-yin-helper');
                    switch($review_status){
                        case '0';
                            $data[$k]['review_status'] = __('Tried','duke-yin-helper');
                            break;
                        case '1';
                            $data[$k]['review_status'] = __('Trying','duke-yin-helper');
                            break;
                        case '2';
                            $data[$k]['review_status'] = __('Wish to buy','duke-yin-helper');
                            break;
                        case '3';
                            $data[$k]['review_status'] = __('Owned','duke-yin-helper');
                            break;
                        case '4';
                            $data[$k]['review_status'] = __('Unfinished Trying','duke-yin-helper');
                            break;
                        default:
                            $data[$k]['review_status'] = __('Tried','duke-yin-helper');
                    }
                    $data[$k]['score'] = get_post_meta($post_id, 'ranking-score', true);
                    break;
                case 'testimonial':
                    $data[$k]['status'] = __('Added a testimonial','duke-yin-helper');
                    break;
                case 'service-type':
                    $data[$k]['status'] = __('Added a service','duke-yin-helper');
                    break;
                case 'videos':
                    $data[$k]['status'] = __('Added a video','duke-yin-helper');
                    break;
                default:
                $data[$k]['status'] = 'Added a post';
            }
        }
		$total_posts = $query->found_posts;
		$max_pages = $query->max_num_pages;
		$returned =  new WP_REST_Response($data, 200);
		$returned->header( 'X-WP-Total', $total_posts );
		$returned->header( 'X-WP-TotalPages', $max_pages );
		return $returned;
    }
	return false;
}