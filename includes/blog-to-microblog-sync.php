<?php
/*
Plugin Name: Blog To Keep Minutes Sync
Description: Sync posts to keepmins site.
Version: 1.0
*/

$dukeyin_options = get_site_option( 'options-page', true, false);
$app_password = isset($dukeyin_options['km-app-password']) ? $dukeyin_options['km-app-password'] : null;
$km_url = isset($dukeyin_options['km-url']) ? $dukeyin_options['km-url'] : null;
$username = isset($dukeyin_options['km_username']) ? $dukeyin_options['km_username'] : null;
if($app_password && $km_url && $username){ //prevent functions goes to memory if app password is not set.

//Main functionality: Schedule a sync when a post is published, and execute the sync after a delay.

// post types to sync, add more custom post types here if needed.
    $post_types = [ 
        'post', 
        'film_review',
        'tvshow_review',
        'book_review',
        'game_review',
        'product_review',
        'portfolio',
        'poem',
    ]; // custom post types


    foreach ( $post_types as $type ) {
        add_action( "publish_{$type}", 'btm_schedule_sync', 10, 2 );
    }

    function btm_schedule_sync($post_id, $post)
    {
        
        // 避免修订版本触发
        if (wp_is_post_revision($post_id)) {
            return;
        }

        // 避免自动草稿
        if ($post->post_status !== 'publish') {
            return;
        }

        // 避免重复同步
        if (get_post_meta($post_id, '_synced_to_keepmins', true)) {
            return;
        }

        // delay sync for 10 minutes to avoid wrong posts.
        wp_schedule_single_event(
            time() + 600,   //in seconds 600 is 10 mins.
            'btm_delayed_sync',
            [$post_id]
        );

        update_post_meta($post_id, '_sync_scheduled', 1);
    }

    add_action('btm_delayed_sync', 'btm_execute_sync');

    function btm_execute_sync($post_id)
    {
        //避免空文章 已删除文章触发
        $post = get_post($post_id);
        if(!$post){
            return;
        }

        // 原文链接
        $permalink = get_permalink($post_id);

        // 摘要
        $excerpt = get_the_excerpt($post_id) ? get_the_excerpt($post_id) : wp_trim_words($post->post_content, 55, '...');

        //getting options
        $dukeyin_options = get_site_option( 'options-page', true, false);
        $app_password = isset($dukeyin_options['km-app-password']) ? $dukeyin_options['km-app-password'] : null;
        $km_url = isset($dukeyin_options['km-url']) ? $dukeyin_options['km-url'] : null;
        $username = isset($dukeyin_options['km_username']) ? $dukeyin_options['km_username'] : null;
        // keep mins REST API
        $api_url = $km_url.'/wp-json/wp/v2/notification';

        // Basic Auth
        $auth = base64_encode($username . ':' . $app_password);

        // prepare meta date
        $source_logo ='';
        switch (get_post_type($post_id)) {
            case 'film_review':
            case 'tvshow_review':
                $source_logo = get_post_meta($post_id, '_r_f_logo', true) ? get_post_meta($post_id, '_r_f_logo', true) : null;
                break;
            case 'game_review':
                $source_logo = get_post_meta($post_id, 'logo', true) ? get_post_meta($post_id, 'logo', true) : null;
                break;
            default:
                $source_logo = null;
        }

        // 请求数据
        $body = [
            'title'   => $post->post_title,
            'content' => $excerpt ? $excerpt : '',
            'status'  => 'publish',
            'meta'=> [
                        'source_post_id'        => $post_id,
                        'source_site'           => home_url(),
                        'source_author'         => get_the_author_meta('display_name', $post->post_author),
                        'source_author_email'   => get_the_author_meta('user_email', $post->post_author),
                        'source_post_type'      => get_post_type($post_id),
                        'source_permalink'      => wp_get_shortlink($post_id) ? wp_get_shortlink($post_id) : get_permalink($post_id),
                        'source_image'          => get_the_post_thumbnail_url($post_id, 'large') ? get_the_post_thumbnail_url($post_id, 'large') : null,
                        'source_logo'           => $source_logo,
                        'source_modified_gmt'   => get_post_modified_time('c', true, $post_id),
                        'source_subtitle'       => get_post_meta($post_id, '_headline', true) ? get_post_meta($post_id, '_headline', true) : null,
                        'source_rating_score'   => get_post_meta($post_id, 'ranking-score', true) ? get_post_meta($post_id, 'ranking-score', true) : null,
                        'source_rating_now'     => null !== get_post_meta($post_id, '_r_now', true) ? get_post_meta($post_id, '_r_now', true) : null,
                    ],
        ];

        $response = wp_remote_post($api_url, [
            'headers' => [
                'Authorization' => 'Basic ' . $auth,
                'Content-Type'  => 'application/json',
            ],
            'body' => wp_json_encode($body),
            'timeout' => 20,
        ]);
        update_post_meta($post_id, '_synced_to_keepmins', 1);
        // 调试日志
        if (is_wp_error($response)) {
            error_log($response->get_error_message());
        } else {
            error_log('Something went wrong with syncing.');
        }
    }

}else{
    error_log('Blog To Keep Minutes Sync: Application password, KM URL, or username not set. Please set them in the Duke theme settings.');
    return;
} //end if app password