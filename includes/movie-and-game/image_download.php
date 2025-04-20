<?php
/**
 * Plugin Name: Custom Image Downloader & Function Runner
 * Description: 手动下载图像并转换为 WebP，附加到指定文章并保存至指定 post meta；同时可运行自定义函数。
 * Version: 1.0
 * Author: 你
 */

if (!defined('ABSPATH')) exit;

add_action('admin_menu', function () {
    add_menu_page('图像下载器', '图像下载器', 'manage_options', 'custom-image-downloader', 'cid_render_admin_page');
});

add_action('admin_notices', function () {
    if (!empty($_GET['cid_message'])) {
        $class = isset($_GET['cid_success']) && $_GET['cid_success'] === '1' ? 'updated' : 'error';
        $msg = esc_html($_GET['cid_message']);
        echo "<div class='$class notice is-dismissible'><p>$msg</p></div>";
    }
});

function cid_render_admin_page() {
    ?>
    <div class="wrap">
        <h1>图像下载器</h1>
        <form method="post" action="<?= admin_url('admin-post.php') ?>">
            <input type="hidden" name="action" value="cid_download_image">
            <?php wp_nonce_field('cid_download_image'); ?>
            <p><label>图像URL：<input type="text" name="image_url" required></label></p>
            <p><label>Post ID：<input type="number" name="post_id" required></label></p>
            <p><label>自定义字段名：<input type="text" name="meta_key" required></label></p>
            <p><button class="button button-primary">下载并附加图像</button></p>
        </form>

        <hr>

        <h2>运行自定义函数</h2>
        <form method="post" action="<?= admin_url('admin-post.php') ?>">
            <input type="hidden" name="action" value="cid_run_function">
            <?php wp_nonce_field('cid_run_function'); ?>
            <p>
                <label>选择函数：
                    <select name="function_name">
                        <option value="clear_movie_for_post_id"><?php _e('Clear movie or tv-show data for a specific post ID','duke-yin-helper') ?></option>
                        <option value="clear_movie_for_tmdb_id"><?php _e('Clear movie or tv-show data for a specific TMDb ID','duke-yin-helper') ?></option>
                        <option value="clear_zero"><?php _e('Clear cast crew languages and categories with zero posts.','duke-yin-helper') ?></option>
                        <option value="clear_all_movie_data"><?php _e('Clear all movies and tv-shows data for all posts','duke-yin-helper') ?></option>
                    </select>
                </label>
            </p>
            <p><button class="button">运行函数</button></p>
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
        cid_admin_notice_redirect('字段不完整');
    }

    $post = get_post($post_id);
    if (!$post) {
        cid_admin_notice_redirect('无效的 Post ID');
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
            cid_admin_notice_redirect('不支持的文章类型');
    }

    $target_dir = trailingslashit($upload_dir['basedir']) . $subdir;
    if (!file_exists($target_dir)) wp_mkdir_p($target_dir);

    $image_data = file_get_contents($image_url);
    if (!$image_data) cid_admin_notice_redirect('图像下载失败');

    $filename = wp_unique_filename($target_dir, basename($image_url, '.' . pathinfo($image_url, PATHINFO_EXTENSION)) . '.webp');
    $filepath = trailingslashit($target_dir) . $filename;

    $tmp_path = wp_tempnam($image_url);
    file_put_contents($tmp_path, $image_data);

    $image = imagecreatefromstring(file_get_contents($tmp_path));
    if (!$image) cid_admin_notice_redirect('图像格式不受支持');

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

    cid_admin_notice_redirect('图像下载并附加成功', true);
}

add_action('admin_post_cid_run_function', 'cid_run_function_callback');
function cid_run_function_callback() {
    check_admin_referer('cid_run_function');
    $func = sanitize_text_field($_POST['function_name']);
    if (in_array($func, ['first', 'second', 'third']) && function_exists($func)) {
        call_user_func($func);
        cid_admin_notice_redirect("函数 $func() 执行完毕", true);
    }
    cid_admin_notice_redirect('无效函数或函数不存在');
}

function cid_admin_notice_redirect($msg, $success = false) {
    $url = add_query_arg([
        'page' => 'custom-image-downloader',
        'cid_message' => urlencode($msg),
        'cid_success' => $success ? '1' : '0',
    ], admin_url('admin.php'));
    wp_redirect($url);
    exit;
}

// 示例函数
function first() { error_log('first() 被调用'); }
function second() { error_log('second() 被调用'); }
function third() { error_log('third() 被调用'); }
