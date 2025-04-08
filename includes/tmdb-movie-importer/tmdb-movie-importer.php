<?php
/*
Plugin Name: TMDB 电影与电视剧导入器
Description: 通过 TMDB API 搜索并导入电影或电视剧为文章。
Version: 1.0
Author: ChatGPT
*/

// 加载 JS 和样式
add_action('admin_enqueue_scripts', function ($hook) {
    if (strpos($hook, 'tmdb-movie-importer') !== false) {
        wp_enqueue_script('tmdb-importer-js', plugin_dir_url(__FILE__) . 'assets/movie-importer.js?ver='.DUKE_YIN_HELPER_VERSION, ['jquery'], null, true);
        wp_localize_script('tmdb-importer-js', 'tmdb_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tmdb_nonce')
        ]);
    }
});

// 添加后台菜单和设置页面
add_action('admin_menu', function () {
    add_menu_page(__('Movies Import Tool','duke-yin-helper'), __('Movies Import Tool','duke-yin-helper'), 'manage_options', 'tmdb-movie-importer', 'tmdb_movie_importer_page','dashicons-editor-video',9);
    add_submenu_page('tmdb-movie-importer', __('Tools','duke-yin-helper'), __('Tools','duke-yin-helper'), 'manage_options', 'tmdb-movie-importer-settings', 'tmdb_movie_importer_settings_page');
});


function tmdb_movie_importer_settings_page() {
    require plugin_dir_path( __FILE__ ) . 'tools.php';
}

// 插件主页面内容
function tmdb_movie_importer_page() {
    ?>
    <div class="wrap">
        <h1><?php _e('TMDB Movie and TV importer','duke-yin-helper');?></h1>
        <input type="text" id="tmdb-movie-name" placeholder="<?php _e('Movie or TV title here','duke-yin-helper');?>" style="width: 300px;">
        <select id="tmdb-content-type">
            <option value="movie"><?php _e('Movie','duke-yin-helper');?></option>
            <option value="tv"><?php _e('TV','duke-yin-helper');?></option>
        </select>
        <select id="tmdb-status">
            <option value="0"><?php _e('Watched','duke-yin-helper');?></option>
            <option value="1"><?php _e('Watching','duke-yin-helper');?></option>
            <option value="2"><?php _e('Want to watch','duke-yin-helper');?></option>
            <option value="3"><?php _e('Watched many times','duke-yin-helper');?></option>
            <option value="4"><?php _e('Partly watched','duke-yin-helper');?></option>
        </select>
        <input type="number" min="0" max="10" id="score" placeholder="<?php _e('Score','duke-yin-helper');?>" style="width: 100px;">
        <button id="tmdb-search-btn" class="button"><?php _e('Search','duke-yin-helper');?></button>
        <div id="tmdb-results" style="margin-top: 20px;"></div>
    </div>
    <?php
}

// AJAX 搜索
add_action('wp_ajax_tmdb_search', function () {
    $dukeyin_options=get_site_option( 'options-page', true, false);
    check_ajax_referer('tmdb_nonce', 'nonce');
    $type = $_POST['type'] === 'tv' ? 'tv' : 'movie';
    $name = sanitize_text_field($_POST['name']);
    $api_key = $dukeyin_options['tmdb-key'];
    $lang_info = $dukeyin_options['tmdb-lang'];
    $url = "https://api.themoviedb.org/3/search/{$type}?api_key={$api_key}&language={$lang_info}&query=" . urlencode($name);
    $response = wp_remote_get($url);
    if (is_wp_error($response)) wp_send_json_error();
    $data = json_decode(wp_remote_retrieve_body($response), true);
    wp_send_json_success($data['results']);
});

// AJAX 选择并导入
add_action('wp_ajax_tmdb_select', function () {
    $dukeyin_options=get_site_option( 'options-page', true, false);
    check_ajax_referer('tmdb_nonce', 'nonce');
    $id = intval($_POST['id']);
    $status = intval($_POST['status'])??0;
    $score = number_format($_POST['score'], 1) ?? 5;
    $type = $_POST['type'] === 'tv' ? 'tv' : 'movie';
    $api_key = $dukeyin_options['tmdb-key'];
    $lang_info = $dukeyin_options['tmdb-lang'];
    $lang_img = $dukeyin_options['tmdb-lang-poster']??'en';

    // check if the post already exists
    function movie_post_exist($type, $meta_key, $meta_value) {
        $post_type = $type === 'tv' ? 'tvshow_review' : 'film_review';
        $args = array(
            'post_type'  => $post_type, // e.g. 'post', 'page', or a custom post type
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



    $info_url = "https://api.themoviedb.org/3/{$type}/{$id}?api_key={$api_key}&language={$lang_info}&append_to_response=credits";
    $info_res = wp_remote_get($info_url);
    if (is_wp_error($info_res)) wp_send_json_error();
    $info = json_decode(wp_remote_retrieve_body($info_res), true);

    $img_url = "https://api.themoviedb.org/3/{$type}/{$id}/images?api_key={$api_key}&include_image_language={$lang_img}";
    $img_res = wp_remote_get($img_url);

 

    $poster_path = '';
    $logo_path = '';
    $backdrop_path = '';
    if (!is_wp_error($img_res)) {
        $img_data = json_decode(wp_remote_retrieve_body($img_res), true);
        if (!empty($img_data['posters'][0]['file_path'])) $poster_path = $img_data['posters'][0]['file_path'];
        if (!empty($img_data['logos'][0]['file_path'])) $logo_path = $img_data['logos'][0]['file_path'];
    }

    $title = $type === 'tv' ? $info['name'] : $info['title'];
    $original_title = $type === 'tv' ? $info['original_name'] : $info['original_title'];
    $slug = sanitize_title($original_title);

    
    $overview = $info['overview'];

    $post_id = movie_post_exist($type, 'tmdb_id', $id);
    
    if($post_id===false || $post_id==='' || $post_id===null ){
        $post_type = $type === 'tv' ? 'tvshow_review' : 'film_review';
        $post_id = wp_insert_post([
            'post_title' => $original_title,
            'post_name' => $slug,
            'post_status' => 'publish',
            'post_type' => $post_type
        ]);
    }

    update_post_meta( $post_id, '_r_f_overview', $overview );
    update_post_meta( $post_id, 'ranking-score', $score );
    update_post_meta( $post_id, '_r_now', $status );
    update_post_meta( $post_id, 'tmdb_id', $id );

    $release_date = $type === 'tv' ? $info['first_air_date'] : $info['release_date'];
    $date = strtotime($release_date);
    update_post_meta( $post_id, '_r_rdate', $date );


    update_post_meta( $post_id, '_headline', $title );

    update_post_meta( $post_id, '_r_original_title', $original_title );
    if($original_title !== $title){
        update_post_meta( $post_id, '_r_f_original_title', $original_title );
    }

    $year = $type === 'tv' ? substr($info['first_air_date'], 0, 4) : substr($info['release_date'], 0, 4);
    update_post_meta( $post_id, '_r_f_year', $year );

    $imdbid = $type === 'tv' ? $info['external_ids']['imdb_id'] : $info['imdb_id'];

    update_post_meta( $post_id, '_r_f_imdb_id', $imdbid );

    $runtime = $type === 'tv' ? $info['episode_run_time'][0] : $info['runtime'];
    update_post_meta($post_id,'_r_f_runtime',$runtime);

    $tagline = $info['tagline'];
    update_post_meta($post_id,'_r_f_tagline',$tagline);


    // download images
    function download_and_attach_image($img_url, $post_id, $tmdb_id) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
    
        $tmp = download_url($img_url);
        if (is_wp_error($tmp)) return false;
    
        $image = imagecreatefromstring(file_get_contents($tmp));
        if (!$image) return false;
    
        $upload_dir = wp_upload_dir();
        $target_dir = trailingslashit($upload_dir['basedir']) . 'tmdb/' . $tmdb_id;
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
    
        // 构建 WordPress 所需的文件数组，并修正 uploads 目录路径以保持 tmdb/{id} 结构
        $file = [
            'name' => $filename,
            'type' => 'image/webp',
            'tmp_name' => $webp_path,
            'error' => 0,
            'size' => filesize($webp_path),
        ];
    
        // 过滤器强制 WordPress 使用我们提供的路径
        add_filter('upload_dir', function ($dirs) use ($tmdb_id) {
            $custom_subdir = '/tmdb/' . $tmdb_id;
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

    if ($poster_path) {
        $poster_url = 'https://image.tmdb.org/t/p/w500' . $poster_path;
        $poster_id = download_and_attach_image($poster_url, $post_id, $id);
        $poster_url = wp_get_attachment_image_src( $poster_id, 'full' )[0];
        if ($poster_id) update_post_meta($post_id, '_r_f_poster', $poster_url);
        
    }
    if ($logo_path) {
        $logo_url = 'https://image.tmdb.org/t/p/w500' . $logo_path;
        $logo_id = download_and_attach_image($logo_url, $post_id, $id);
        $lgo_url = wp_get_attachment_image_src( $logo_id, 'full' )[0];
        if ($logo_id) update_post_meta($post_id, '_r_f_logo', $lgo_url);
        
    }

// needs clean backdrop
    $backdrop_url ="https://api.themoviedb.org/3/{$type}/{$id}/images?api_key={$api_key}";
    $backdrop_res = wp_remote_get($backdrop_url);
    if (!is_wp_error($backdrop_res)) {
        $backdrop_data = json_decode(wp_remote_retrieve_body($backdrop_res), true);
        if (!empty($backdrop_data['backdrops'][0]['file_path'])) $backdrop_path = $backdrop_data['backdrops'][0]['file_path'];
        
    }

    if ($backdrop_path) {
        $backdrop_url = 'https://image.tmdb.org/t/p/w1280' . $backdrop_path;
        $backdrop_id = download_and_attach_image($backdrop_url, $post_id, $id);
        if(!has_post_thumbnail($post_id) && $backdrop_id){
            set_post_thumbnail($post_id, $backdrop_id);
        }
    }

    $directors = []; $writers = []; $actors = [];
    if (!empty($info['credits'])) {
        foreach ($info['credits']['crew'] as $person) {
            if ($person['job'] == 'Director') $directors[] = $person['name'];
            if ($person['job'] == 'Writer' || $person['job'] == 'Screenplay') $writers[] = $person['name'];
        }
        foreach (array_slice($info['credits']['cast'], 0, 10) as $actor) {
            $actors[] = $actor['name'];
        }
    }


    // directors
    foreach($directors as $director){
        $term_id = term_exists( $director, 'directors' );
        if($term_id){
            wp_set_object_terms( $post_id, $director, 'directors', true );
        }else{
            wp_insert_term(
                $director,
            'directors',
            array(
                'description' => '',
                'slug'        => $director,
                'parent'      => '',
            ));
            $term_id = term_exists( $director, 'directors' );
            wp_set_object_terms( $post_id, $director, 'directors', true );
        }
    }

    // writers
    foreach($writers as $writer){
        $term_id = term_exists( $writer, 'screenplay' );
        if($term_id){
            wp_set_object_terms( $post_id, $writer, 'screenplay', true );
        }else{
            wp_insert_term(
                $writer,
            'screenplay',
            array(
                'description' => '',
                'slug'        => $writer,
                'parent'      => '',
            ));
            $term_id = term_exists( $writer, 'screenplay' );
            wp_set_object_terms( $post_id, $writer, 'screenplay', true );
        }
    }

    // actors
    foreach($actors as $cas){
        $term_id = term_exists( $cas, 'cast' );
        if($term_id){
            wp_set_object_terms( $post_id, $cas, 'cast', true );
        }else{
            wp_insert_term(
                $cas,
            'cast',
            array(
                'description' => '',
                'slug'        => $cas,
                'parent'      => '',
            ));
            $term_id = term_exists( $cas, 'cast' );
            wp_set_object_terms( $post_id, $cas, 'cast', true );
        }
    }

    // genres
    $genres = array_column($info['genres'], 'name');
    foreach($genres as $genre){
        $term_id = term_exists( $genre, 'film_review_categories' );
        if($term_id){
            wp_set_post_terms( $post_id, array( $term_id['term_id'] ), 'film_review_categories', true );
        }else{
            wp_insert_term(
            $genre,
            'film_review_categories',
            array(
                'description' => '',
                'slug'        => $genre,
                'parent'      => '',
            ));
            $term_id = term_exists( $genre, 'film_review_categories' );
            wp_set_post_terms( $post_id, array( $term_id['term_id'] ), 'film_review_categories', true );
        }
    }

    //Languages
    $languages = array_column($info['spoken_languages'], 'name');
    foreach($languages as $language){
        $term_id = term_exists( $language, 'languages' );
        if($term_id){
            wp_set_object_terms( $post_id, $language, 'languages', true );
        }else{
            wp_insert_term(
                $language,
            'languages',
            array(
                'description' => '',
                'slug'        => $language,
                'parent'      => '',
            ));
            $term_id = term_exists( $language, 'languages' );
            wp_set_object_terms( $post_id, $language, 'languages', true );
        }
    }

    // tv show seasons

    if ($type === 'tv' && !empty($info['number_of_seasons'])) {
        $season_info = [];
        for ($s = 1; $s <= $info['number_of_seasons']; $s++) {
            $season_url = "https://api.themoviedb.org/3/tv/{$id}/season/{$s}?api_key={$api_key}&language={$lang_info}";
            $season_res = wp_remote_get($season_url);
            
            if (!is_wp_error($season_res)) {
                $season_data = json_decode(wp_remote_retrieve_body($season_res), true);
                $season_number = $season_data['season_number'];
                $season_id = $season_data['id'];
                $air_date = $season_data['air_date'];
                $season_overview = $season_data['overview'];
                $season_name = $season_data['name'];
                $episode_count = count($season_data['episodes']);
                $poster = $season_data['poster_path'] ?? '';
                $season_info[] = [
                    'season_number' => $season_number,
                    'air_date' => $air_date,
                    'name' => $season_name,
                    'id' => $season_id,
                    'overview' => $season_overview,
                    'poster_path' => $poster,
                    'episode_count' => $episode_count
                ];
            }
        }
       
        update_post_meta($post_id, '_r_t_seasons', $season_info);
    }
    $link = get_permalink( $post_id );
    wp_send_json_success(['link' => $link]);
});
