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
        wp_enqueue_script('tmdb-importer-js', plugin_dir_url(__FILE__) . 'assets/movie-importer.js', ['jquery'], null, true);
        wp_localize_script('tmdb-importer-js', 'tmdb_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tmdb_nonce')
        ]);
    }
});

// 添加后台菜单和设置页面
add_action('admin_menu', function () {
    add_menu_page('电影导入器', '电影导入器', 'manage_options', 'tmdb-movie-importer', 'tmdb_movie_importer_page');
    add_submenu_page('tmdb-movie-importer', '设置', '设置', 'manage_options', 'tmdb-movie-importer-settings', 'tmdb_movie_importer_settings_page');
});


function tmdb_movie_importer_settings_page() {
    ?>

    <?php
}

// 插件主页面内容
function tmdb_movie_importer_page() {
    ?>
    <div class="wrap">
        <h1>TMDB 电影 / 电视剧 导入器</h1>
        <input type="text" id="tmdb-movie-name" placeholder="输入电影或电视剧名称" style="width: 300px;">
        <select id="tmdb-content-type">
            <option value="movie">电影</option>
            <option value="tv">电视剧</option>
        </select>
        <button id="tmdb-search-btn" class="button">搜索</button>
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
    $type = $_POST['type'] === 'tv' ? 'tv' : 'movie';
    $api_key = $dukeyin_options['tmdb-key'];
    $lang_info = $dukeyin_options['tmdb-lang'];
    $lang_img = $dukeyin_options['tmdb-lang-poster']??'en';

    $info_url = "https://api.themoviedb.org/3/{$type}/{$id}?api_key={$api_key}&language={$lang_info}&append_to_response=credits";
    $info_res = wp_remote_get($info_url);
    if (is_wp_error($info_res)) wp_send_json_error();
    $info = json_decode(wp_remote_retrieve_body($info_res), true);
    $img_url = "https://api.themoviedb.org/3/{$type}/{$id}/images?api_key={$api_key}";
    $img_res = wp_remote_get($img_url);

    $poster_path = '';
    $logo_path = '';
    $backdrop_path = '';
    if (!is_wp_error($img_res)) {
        $img_data = json_decode(wp_remote_retrieve_body($img_res), true);
        if (!empty($img_data['posters'][0]['file_path'])) $poster_path = $img_data['posters'][0]['file_path'];
        if (!empty($img_data['logos'][0]['file_path'])) $logo_path = $img_data['logos'][0]['file_path'];
        if (!empty($img_data['backdrops'][0]['file_path'])) $backdrop_path = $img_data['backdrops'][0]['file_path'];
    }

    $title = $type === 'tv' ? $info['name'] : $info['title'];
    $original_title = $type === 'tv' ? $info['original_name'] : $info['original_title'];
    $slug = sanitize_title($original_title);

    $post_type = $type === 'tv' ? 'tvshow_review' : 'film_review';
    $overview = $info['overview'];
    $post_id = wp_insert_post([
        'post_title' => $original_title,
        'post_name' => $slug,
        'post_status' => 'publish',
        'post_type' => $post_type
    ]);

    update_post_meta( $post_id, '_r_f_overview', $overview );

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


    function download_and_attach_image($img_url, $post_id) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $tmp = download_url($img_url);
        if(is_wp_error($tmp)){
            @unlink($tmp);
        }else{
            $file = [
                'name' => basename($img_url),
                'type' => mime_content_type( $tmp ),
                'tmp_name' => $tmp,
                'error' => 0,
                'size' => filesize($tmp),
            ];
            return media_handle_sideload($file, $post_id);
        }
        return false;
    }

    if ($poster_path) {
        $poster_url = 'https://image.tmdb.org/t/p/original' . $poster_path;
        $poster_id = download_and_attach_image($poster_url, $post_id);
        $poster_url = wp_get_attachment_image_src( $poster_id, 'full' )[0];
        if ($poster_id) update_post_meta($post_id, '_r_f_poster', $poster_url);
        
    }
    if ($logo_path) {
        $logo_url = 'https://image.tmdb.org/t/p/original' . $logo_path;
        $logo_id = download_and_attach_image($logo_url, $post_id);
        $lgo_url = wp_get_attachment_image_src( $logo_id, 'full' )[0];
        if ($logo_id) update_post_meta($post_id, '_r_f_logo', $lgo_url);
        
    }
    if ($backdrop_path) {
        $backdrop_url = 'https://image.tmdb.org/t/p/original' . $backdrop_path;
        $backdrop_id = download_and_attach_image($backdrop_url, $post_id);
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
    // update_post_meta($post_id, '导演', implode(', ', $directors));
    // update_post_meta($post_id, '编剧', implode(', ', $writers));
    // update_post_meta($post_id, '演员', implode(', ', $actors));

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

    wp_send_json_success(['post_id' => $post_id]);
});
