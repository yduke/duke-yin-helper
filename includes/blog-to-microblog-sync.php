<?php
/*
Plugin Name: Blog To Microblog Sync
Description: Sync posts to microblog site.
Version: 1.0
*/

// delay sync for 10 minutes to avoid wrong posts.

add_action('publish_post', 'btm_schedule_sync', 10, 2);

function btm_schedule_sync($post_id, $post)
{
    if (wp_is_post_revision($post_id)) {
        return;
    }

    if (get_post_meta($post_id, '_sync_scheduled', true)) {
        return;
    }

    wp_schedule_single_event(
        time() + 60,
        'btm_delayed_sync',
        [$post_id]
    );

    update_post_meta($post_id, '_sync_scheduled', 1);
}



add_action('btm_delayed_sync', 'btm_execute_sync');

function btm_execute_sync($post_id)
{
    // 避免修订版本触发
    if (wp_is_post_revision($post_id)) {
        return;
    }

    $post = get_post($post_id);
    if(!$post){
        return;
    }

    // 避免自动草稿
    if ($post->post_status !== 'publish') {
        return;
    }

    if (get_post_meta($post_id, '_synced_to_microblog', true)) {
        return;
    }
    // 获取特色图
    $thumbnail = get_the_post_thumbnail_url($post_id, 'large');

    // 原文链接
    $permalink = get_permalink($post_id);

    // 摘要
    $excerpt = get_the_excerpt($post_id);

    // 微博内容
    $content = '';

    if ($thumbnail) {
        $content .= '<p><img src="' . esc_url($thumbnail) . '"></p>';
    }

    $content .= '<p>新长文发布：</p>';

    $content .= '<p><strong>' . esc_html($post->post_title) . '</strong></p>';

    if ($excerpt) {
        $content .= '<p>' . esc_html($excerpt) . '</p>';
    }

    $content .= '<p><a href="' . esc_url($permalink) . '">阅读全文</a></p>';

    // B站 REST API
    $api_url = 'http://192.168.2.209/km/wp-json/wp/v2/notification';

    // Application Password 用户
    $username = 'blog_sync';

    // Application Password
    $app_password = 'ZIzs opUR zbSn OxNK AEkX UVCU';

    // Basic Auth
    $auth = base64_encode($username . ':' . $app_password);

    // 请求数据
    $body = [
        'title'   => $post->post_title,
        'content' => $content,
        'status'  => 'publish',
        'meta'=> [
                    'source_post_id' => $post_id,
                    'source_site' => home_url(),
                    'source_permalink' => get_permalink($post_id),
                    'source_modified_gmt' => get_post_modified_time('c', true, $post_id),
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
    update_post_meta($post_id, '_synced_to_microblog', 1);
    // 调试日志
    if (is_wp_error($response)) {
        error_log($response->get_error_message());
    } else {
        error_log(wp_remote_retrieve_body($response));
    }
}