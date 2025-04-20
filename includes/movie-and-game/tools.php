<?php
//  * Movie and Game Tools Page
if (!defined('ABSPATH')) exit;

add_action('admin_menu', function () {
    add_menu_page(
        __('Movies & Games','duke-yin-helper'),
        __('Movies & Games','duke-yin-helper'),
        'manage_options', 
        'movie-and-game-importer',
        'movie_and_game_importer_page',
        'dashicons-games',
        9
    );
});

add_action('admin_notices', function () {
    if (!empty($_GET['cid_message'])) {
        $class = isset($_GET['cid_success']) && $_GET['cid_success'] === '1' ? 'updated' : 'error';
        $msg = esc_html($_GET['cid_message']);
        echo "<div class='$class notice is-dismissible'><p>$msg</p></div>";
    }
});

function movie_and_game_importer_page() {

?>
    <div class="wrap">
        <h2><?php _e( 'Movies and Games', 'duke-yin-helper' ) ?></h2>
        <p><?php _e('Import movies, tv-shows or games from the movie database or Steam grid database.','duke-yin-helper') ?></p>
        <a class="button" href="<?php echo admin_url( '/admin.php?page=dk-movie-importer' );?>"><?php _e( 'Import a movie or tv-show', 'duke-yin-helper' ) ?></a>
        <a class="button" href="<?php echo admin_url( '/admin.php?page=dk-game-importer' );?>"><?php _e( 'Import a game', 'duke-yin-helper' ) ?></a>
        <h3><?php _e( 'Movies and TV shows Tools', 'duke-yin-helper' ) ?></h3>
        <form method="post" action="<?= admin_url('admin-post.php') ?>" onsubmit="if(!confirm('<?php _e('Are you sure you want to do this?','duke-yin-helper');?>')){ return false; }">
            <input type="hidden" name="action" value="cid_run_movie_function">
            <?php wp_nonce_field('cid_run_movie_function'); ?>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th><?php _e("Operation:","duke-yin-helper"); ?></th>
                        <td>
                            <select name="function_name">
                                <option selected><?php _e('Select Action','duke-yin-helper') ?></option>
                                <option value="clear_movie_for_post_id"><?php _e('Clear movie or tv-show data for a specific post ID','duke-yin-helper') ?></option>
                                <option value="clear_movie_for_tmdb_id"><?php _e('Clear movie or tv-show data for a specific TMDb ID','duke-yin-helper') ?></option>
                                <option value="clear_movie_zero"><?php _e('Clear cast crew languages and categories with zero posts.','duke-yin-helper') ?></option>
                                <option value="clear_all_movie_data"><?php _e('Clear all movies and tv-shows data for all posts','duke-yin-helper') ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                    <th><?php _e("ID:"); ?> </th>
                        <td><input type="number" name="val" value="" size="20"><br />
                            <small><?php _e('enter an ID when applicable; if not, leave blank','duke-yin-helper') ?></small>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p class="submit">
                <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Proceed',"duke-yin-helper") ?>" />
            </p>
        </form>
<hr>
        <h3><?php _e( 'Game Tools', 'duke-yin-helper' ) ?></h3>
        <form method="post" action="" onsubmit="if(!confirm('<?php _e('Are you sure you want to do this?','duke-yin-helper');?>')){ return false; }">
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th><?php _e("Operation:","duke-yin-helper"); ?></th>
                        <td>
                            <select name="operation">
                                <option selected><?php _e('Select Action','duke-yin-helper') ?></option>
                                <option value="clear_game_for_post_id"><?php _e('Clear game data for a specific post ID','duke-yin-helper') ?></option>
                                <option value="clear_game_for_steamgrid_id"><?php _e('Clear game data for a specific SteamGridDb ID','duke-yin-helper') ?></option>
                                <option value="clear_all_game_data"><?php _e('Clear all game data for all posts','duke-yin-helper') ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                    <th><?php _e("ID:"); ?> </th>
                        <td><input type="number" name="val" value="" size="20"><br />
                            <small><?php _e('enter an ID when applicable; if not, leave blank','duke-yin-helper') ?></small>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p class="submit">
                <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Proceed',"duke-yin-helper") ?>" />
            </p>
        </form>
        <hr>
        <h3><?php _e( 'Image download', 'duke-yin-helper' ) ?></h3>
            <input type="hidden" name="action" value="cid_download_image">
            <?php wp_nonce_field('cid_download_image'); ?>
            <form method="post" action="<?= admin_url('admin-post.php') ?>">
                <table class="form-table" role="presentation">
                    <tr>
                        <th>
                            <input type="hidden" name="action" value="cid_download_image">
                            <?php wp_nonce_field('cid_download_image'); ?>
                            <?php _e('External Image URL','duke-yin-helper') ?>
                        </th>
                        <td>
                            <input type="text" name="image_url" required>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <?php _e('Post ID','duke-yin-helper') ?>
                        </th>
                        <td>
                            <input type="number" name="post_id" required>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <?php _e('Meta key','duke-yin-helper') ?>
                        </th>
                        <td>
                            <input type="text" name="meta_key">
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <?php _e('Set as post thumbnail','duke-yin-helper') ?>
                        </th>
                        <td>
                            <input name="set_post_thumb" type="checkbox" id="set_post_thumb" value="0">
                        </td>
                    </tr>
                    
                </table>
                <p class="submit"><input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Download Image',"duke-yin-helper") ?>" />
                </form></p>
            </form>
    </div>
<?php
} 

add_action('admin_post_cid_download_image', 'cid_download_image_callback');
function cid_download_image_callback() {
    check_admin_referer('cid_download_image');

    $image_url = esc_url_raw($_POST['image_url']);
    $post_id = intval($_POST['post_id']);
    $meta_key = sanitize_text_field($_POST['meta_key']);

    if (!$image_url || !$post_id || !$meta_key) {
        cid_admin_notice_redirect(__('All required fields must be filled.','duke-yin-helper'));
    }

    $post = get_post($post_id);
    if (!$post) {
        cid_admin_notice_redirect(__('Invalid Post ID.','duke-yin-helper'));
    }

    $post_type = get_post_type($post);
    $upload_dir = wp_upload_dir();
    $subdir = '';
    switch ($post_type) {
        case 'game_review':
            $subdir = 'stemgriddb';
            break;
        case 'tvshow_review':
        case 'film_review':
            $subdir = 'tmdb';
            break;
        default:
            cid_admin_notice_redirect(__('Unsupported post type.','duke-yin-helper'));
    }

    $target_dir = trailingslashit($upload_dir['basedir']) . $subdir;
    if (!file_exists($target_dir)) wp_mkdir_p($target_dir);

    $image_data = file_get_contents($image_url);
    if (!$image_data) cid_admin_notice_redirect(__('Failed to download image.','duke-yin-helper'));

    $filename = wp_unique_filename($target_dir, basename($image_url, '.' . pathinfo($image_url, PATHINFO_EXTENSION)) . '.webp');
    $filepath = trailingslashit($target_dir) . $filename;

    $tmp_path = wp_tempnam($image_url);
    file_put_contents($tmp_path, $image_data);

    $image = imagecreatefromstring(file_get_contents($tmp_path));
    if (!$image) cid_admin_notice_redirect(__('Unsupported Image format.','duke-yin-helper'));

    imagewebp($image, $filepath);
    imagedestroy($image);

    $attachment = [
        'post_mime_type' => 'image/webp',
        'post_title'     => sanitize_file_name($filename),
        'post_content'   => '',
        'post_status'    => 'inherit'
    ];

    $attach_id = wp_insert_attachment($attachment, $filepath, $post_id);
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $attach_data = wp_generate_attachment_metadata($attach_id, $filepath);
    wp_update_attachment_metadata($attach_id, $attach_data);

    update_post_meta($post_id, $meta_key, $attach_id);

    cid_admin_notice_redirect(__('Image downloaded successfully.','duke-yin-helper'), true);
}

add_action('admin_post_cid_run_movie_function', 'cid_run_movie_function_callback');
function cid_run_movie_function_callback() {
    check_admin_referer('cid_run_movie_function');
    $func = sanitize_text_field($_POST['function_name']);
    $val = (int)$_POST['val'];
    if (in_array($func, [
        'clear_movie_for_post_id', 
        'clear_movie_for_tmdb_id', 
        'clear_movie_zero',
        'clear_all_movie_data'
        ]) && function_exists($func)) {
        call_user_func($func, $val);
        cid_admin_notice_redirect(__('Operations completed.','duke-yin-helper'), true);
    }
    cid_admin_notice_redirect(__('Invalid function or function does not exist.','duke-yin-helper'));
}

function cid_admin_notice_redirect($msg, $success = false) {
    $url = add_query_arg([
        'page' => 'movie-and-game-importer',
        'cid_message' => urlencode($msg),
        'cid_success' => $success ? '1' : '0',
    ], admin_url('admin.php'));
    wp_redirect($url);
    exit;
}

