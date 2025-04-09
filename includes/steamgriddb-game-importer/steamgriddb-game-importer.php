<?php
/**
 * Plugin Name: SteamGridDB 游戏导入器（自动）
 * Description: 自动查询并导入 SteamGridDB 的 hero、logo、grid、icon 图片为文章。
 * Version: 1.4
 * Author: DukeYin
 */


if (!defined('ABSPATH')) exit;




add_action('admin_menu', function () {
    add_menu_page(__('Games Import Tool','duke-yin-helper'), __('Games Import Tool','duke-yin-helper'), 'manage_options', 'steamgriddb-import', 'sgdb_import_page','dashicons-games',9);
});

add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook != 'toplevel_page_steamgriddb-import') return;
    wp_enqueue_script('sgdb-script', plugin_dir_url(__FILE__) . 'js/sgdb-auto.js?ver='.DUKE_YIN_HELPER_VERSION, ['jquery'], null, true);
    wp_localize_script('sgdb-script', 'sgdb_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('sgdb_nonce'),
    ]);
});

function sgdb_import_page() {
   ?>
   <div class="wrap">
        <h1><?php _e('Games Import Tool','duke-yin-helper'); ?></h1>
        <input type="text" id="sgdb-game-name" placeholder="<?php _e('Games title here','duke-yin-helper'); ?>">
        <button id="sgdb-search-btn" class="button"><?php _e('Search','duke-yin-helper'); ?></button>
        <div id="sgdb-results"></div>
    </div>
    <?php
}

add_action('wp_ajax_sgdb_search_game', function () {
    check_ajax_referer('sgdb_nonce', 'nonce');
    $dukeyin_options=get_site_option( 'options-page', true, false);
    $api_key = $dukeyin_options['sgdb-key'];

    $term = sanitize_text_field($_POST['term']);
    $response = wp_remote_get("https://www.steamgriddb.com/api/v2/search/autocomplete/" . urlencode($term), [
        'headers' => ['Authorization' => 'Bearer '.$api_key]
    ]);
    if (is_wp_error($response)) wp_send_json_error(['message' => '查询失败']);
    $body = json_decode(wp_remote_retrieve_body($response), true);
    wp_send_json_success($body['data']);
});

add_action('wp_ajax_sgdb_fetch_and_create', function () {
    check_ajax_referer('sgdb_nonce', 'nonce');
    $game_id = intval($_POST['game_id']);
    $game_name = sanitize_text_field($_POST['game_name']);
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
    $logo_response = wp_remote_get("https://www.steamgriddb.com/api/v2/logos/game/{$game_id}?limit=1&styles=official&type=static&mimes=image/webp,image/png", [
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

    $post_id = wp_insert_post([
        'post_title' => $game_name,
        'post_status' => 'publish',
        'post_type' => 'game_review',
    ]);

    update_post_meta($post_id, 'game_id', $game_id);
	
	    function download_and_attach_game_image($img_url, $post_id, $game_id) {
			require_once(ABSPATH . 'wp-admin/includes/file.php');
			require_once(ABSPATH . 'wp-admin/includes/media.php');
			require_once(ABSPATH . 'wp-admin/includes/image.php');
		
			$tmp = download_url($img_url);
			if (is_wp_error($tmp)) return false;
		
			$image = imagecreatefromstring(file_get_contents($tmp));
			if (!$image) return false;
		
			$upload_dir = wp_upload_dir();
			$target_dir = trailingslashit($upload_dir['basedir']) . 'stemgriddb/' . $game_id;
			if (!file_exists($target_dir)) {
				wp_mkdir_p($target_dir);
			}
		
			$ext = pathinfo(parse_url($img_url, PHP_URL_PATH), PATHINFO_EXTENSION);
			$filename_base = basename($img_url, ".{$ext}");
			$filename = $filename_base . '.webp';
			$webp_path = trailingslashit($target_dir) . $filename;
		
			imagewebp($image, $webp_path, 85);
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
		
			return $attachment_id;
		}
	
// print_r($image_urls);
    foreach ($image_urls as $type => $url) {
		
		
        // $attachment_id = media_sideload_image(esc_url_raw($url), $post_id, null, 'id');
        $attachment_id = download_and_attach_game_image($url, $post_id, $game_id);
		
        if (!is_wp_error($attachment_id)) {
            update_post_meta($post_id, $type, wp_get_attachment_url($attachment_id));
        }
    }

    wp_send_json_success(['post_id' => $post_id, 'title' => $game_name]);
});