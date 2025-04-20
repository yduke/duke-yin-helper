<?php
/**
 * Plugin Name: SteamGridDB 游戏导入器（自动）
 * Description: 自动查询并导入 SteamGridDB 的 hero、logo、grid、icon 图片为文章。
 * Version: 1.4
 * Author: DukeYin
 */


if (!defined('ABSPATH')) exit;


add_action('admin_enqueue_scripts', function ($hook) {
    if (strpos($hook, 'dk-game-importer') !== false) {
        wp_enqueue_script('sgdb-script', plugin_dir_url(__FILE__) . 'assets/game-importer.js?ver='.DUKE_YIN_HELPER_VERSION, ['jquery'], null, true);
        wp_localize_script('sgdb-script', 'sgdb_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sgdb_nonce'),
        ]);
    }
});

function dk_game_importer_page() {
   ?>
   <div class="wrap">
        <h1><?php _e('Games Import Tool','duke-yin-helper'); ?></h1>
        <input type="text" id="sgdb-game-name" placeholder="<?php _e('Games title here','duke-yin-helper'); ?>">
        <select id="game-status">
            <option value="0"><?php _e('Played','duke-yin-helper');?></option>
            <option value="1"><?php _e('Playing','duke-yin-helper');?></option>
            <option value="2"><?php _e('Wish to play','duke-yin-helper');?></option>
            <option value="3"><?php _e('Cleared','duke-yin-helper');?></option>
            <option value="4"><?php _e('Not Cleared','duke-yin-helper');?></option>
        </select>
        <button id="sgdb-search-btn" class="button"><?php _e('Search','duke-yin-helper'); ?></button>
        <div id="sgdb-results"></div>
    </div>
    <?php
}

function game_post_exist($meta_key, $meta_value) {
    $args = array(
        'post_type'  => 'game_review', // e.g. 'post', 'page', or a custom post type
        'meta_query' => array(
            array(
                'key'   => $meta_key,   // The meta key you're searching for
                'value' => $meta_value, // The value you're matching
            )
        ),
        'posts_per_page' => 1, // Only need to know if one exists
        'fields' => 'ids'      // Return only post IDs for performance
    );
    $query = new WP_Query($args);
    if ( $query->have_posts() ) {
        $existing_post_id = $query->posts[0];
        return $existing_post_id; // Return the ID of the existing post
    } else {
        return false; // No post found
    }
    return false;
}


add_action('wp_ajax_sgdb_search_game', function () {
    check_ajax_referer('sgdb_nonce', 'nonce');
    $dukeyin_options=get_site_option( 'options-page', true, false);
    $api_key = $dukeyin_options['sgdb-key'];

    $term = sanitize_text_field($_POST['term']);
    $response = wp_remote_get("https://www.steamgriddb.com/api/v2/search/autocomplete/" . urlencode($term), [
        'headers' => ['Authorization' => 'Bearer '.$api_key]
    ]);
    // var_dump($response);
    if (is_wp_error($response)) wp_send_json_error(['message' => '查询失败','response'=>$response,'code' => $response->get_error_code()]);
    $body = json_decode(wp_remote_retrieve_body($response), true);
    wp_send_json_success($body['data']);
});

add_action('wp_ajax_sgdb_fetch_and_create', function () {
    check_ajax_referer('sgdb_nonce', 'nonce');
    $game_id = intval($_POST['game_id']);
    $game_name = sanitize_text_field($_POST['game_name']);
    $status = intval($_POST['status'])??0;
    $platforms = [];
    $types = sanitize_text_field($_POST['platform']);
    $platforms = explode(',', $types);
    $release_date = intval($_POST['release_date']);
    $image_urls = [];
    $dukeyin_options=get_site_option( 'options-page', true, false);
    $api_key = $dukeyin_options['sgdb-key'];

    // Hero
    $hero_response = wp_remote_get("https://www.steamgriddb.com/api/v2/heroes/game/{$game_id}?limit=1&dimensions=1600x650,1920x620&type=static&mimes=image/webp,image/jpeg,image/png", [
        'headers' => ['Authorization' => 'Bearer '.$api_key]
    ]);
    if (!is_wp_error($hero_response)) {
        $hero_data = json_decode(wp_remote_retrieve_body($hero_response), true);
        if (!empty($hero_data['data'][0]['url'])) {
            $image_urls['hero'] = $hero_data['data'][0]['url'];
        }
    }

    // grid
    $grid_response = wp_remote_get("https://www.steamgriddb.com/api/v2/grids/game/{$game_id}?limit=1&dimensions=600x900&type=static&mimes=image/webp,image/jpeg,image/png", [
        'headers' => ['Authorization' => 'Bearer '.$api_key]
    ]);
    if (!is_wp_error($grid_response)) {
        $grid_data = json_decode(wp_remote_retrieve_body($grid_response), true);
        if (!empty($grid_data['data'][0]['url'])) {
            $image_urls['grid'] = $grid_data['data'][0]['url'];
        }
    }

    // logo
    $logo_response = wp_remote_get("https://www.steamgriddb.com/api/v2/logos/game/{$game_id}?limit=1&styles=official&type=static&mimes=image/png", [
        'headers' => ['Authorization' => 'Bearer '.$api_key]
    ]);
    if (!is_wp_error($logo_response)) {
        $logo_data = json_decode(wp_remote_retrieve_body($logo_response), true);
        if (!empty($logo_data['data'][0]['url'])) {
            $image_urls['logo'] = $logo_data['data'][0]['url'];
        }
    }

    // icon
    $icon_response = wp_remote_get("https://www.steamgriddb.com/api/v2/icons/game/{$game_id}?limit=1&styles=official&type=static&mimes=image/png", [
        'headers' => ['Authorization' => 'Bearer '.$api_key]
    ]);
    if (!is_wp_error($icon_response)) {
        $icon_data = json_decode(wp_remote_retrieve_body($icon_response), true);
        if (!empty($icon_data['data'][0]['url'])) {
            $image_urls['icon'] = $icon_data['data'][0]['url'];
        }
    }
    $post_id = game_post_exist('game_id', $game_id);
    if(!$post_id){
        $post_id = wp_insert_post([
            'post_title' => $game_name,
            'post_status' => 'publish',
            'post_type' => 'game_review',
        ]);
        update_post_meta( $post_id, 'game_id', $game_id);
        update_post_meta( $post_id, '_r_now', $status );
        update_post_meta( $post_id, '_r_rdate', $release_date );
        update_post_meta( $post_id, 'ranking-score', '5.0' );
        update_post_meta( $post_id, 'gameplay-score', '5.0' );
        update_post_meta( $post_id, 'technical-score', '5.0' );
        update_post_meta( $post_id, 'narrative-score', '5.0' );
        update_post_meta( $post_id, 'audios-score', '5.0' );
        update_post_meta( $post_id, 'graphic-score', '5.0' );

        foreach($platforms as $platform){
            $term_id = term_exists( $platform, 'game_review_platforms' );
            if($term_id){
                wp_set_object_terms( $post_id, $platform, 'game_review_platforms', true );
            }else{
                wp_insert_term(
                    $platform,
                'game_review_platforms',
                array(
                    'description' => '',
                    'slug'        => $platform,
                    'parent'      => '',
                ));
                $term_id = term_exists( $platform, 'game_review_platforms' );
                wp_set_object_terms( $post_id, $platform, 'game_review_platforms', true );
            }
        }
    }


    function download_and_attach_game_image($img_url, $post_id, $game_id) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
    
        $tmp = download_url($img_url);
        if (is_wp_error($tmp)) return false;
    
        $image = imagecreatefromstring(file_get_contents($tmp));
        if (!$image) return false;
    
        $upload_dir = wp_upload_dir();
        if (!file_exists($upload_dir['basedir'].'/stemgriddb')) { //create stemgriddb folder if not exists
            mkdir($upload_dir['basedir'].'/stemgriddb', 0777, true);
        }
        $target_dir = trailingslashit($upload_dir['basedir']) . 'stemgriddb/' . $game_id;
        if (!file_exists($target_dir)) {
            wp_mkdir_p($target_dir);
        }
    
        $ext = pathinfo(parse_url($img_url, PHP_URL_PATH), PATHINFO_EXTENSION);
        $filename_base = basename($img_url, ".{$ext}");
        $filename = $filename_base . '.webp';
        $webp_path = trailingslashit($target_dir) . $filename;
    
        @imagewebp($image, $webp_path, 85);
        imagedestroy($image);
        unlink($tmp);
    
        // 构建 WordPress 所需的文件数组，并修正 uploads 目录路径以保持 stemgriddb/{id} 结构
        $file = [
            'name' => $filename,
            'type' => 'image/webp',
            'tmp_name' => $webp_path,
            'error' => 0,
            'size' => filesize($webp_path),
        ];
    
        // 过滤器强制 WordPress 使用我们提供的路径
        add_filter('upload_dir', function ($dirs) use ($game_id) {
            $custom_subdir = '/stemgriddb/' . $game_id;
            $dirs['subdir'] = $custom_subdir;
            $dirs['path'] = $dirs['basedir'] . $custom_subdir;
            $dirs['url'] = $dirs['baseurl'] . $custom_subdir;
            return $dirs;
        });
    
        $attachment_id = media_handle_sideload($file, $post_id);
    
        // 移除过滤器，避免影响其他上传
        remove_all_filters('upload_dir');
        if (is_wp_error($attachment_id)) return false;
        return $attachment_id;
    }
	
    // 下载并附加图片
    foreach ($image_urls as $type => $url) {
        if (empty($url)) continue;
        $meta= get_post_meta($post_id, $type, true);
        if(empty($meta)){
            $attachment_id = @download_and_attach_game_image($url, $post_id, $game_id);
            if($attachment_id){
                update_post_meta($post_id, $type, wp_get_attachment_url($attachment_id));
                if($type==='hero' && !has_post_thumbnail($post_id)){
                    set_post_thumbnail($post_id, $attachment_id);

                }
            }
        }
    }
    $link = get_permalink( $post_id );

    wp_send_json_success(['post_id' => $post_id, 'title' => $game_name,'link' => $link]);
});