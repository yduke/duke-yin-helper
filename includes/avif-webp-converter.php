<?php
/*
Plugin Name: AVIF/WebP Image Converter
Description: Automatically converts uploaded images to AVIF/WebP format with alpha channel support.
Version: 1.0
Author: Duke Yin
Author URI: https://www.dukeyin.com
*/

// 检测服务器支持情况
function awc_check_support() {
    $support = [
        'gd' => [
            'installed' => extension_loaded('gd'),
            'avif'      => false,
            'webp'      => false,
        ],
        'imagick' => [
            'installed' => extension_loaded('imagick'),
            'avif'      => false,
            'webp'      => false,
        ]
    ];

    // 检测GD库支持
    if ($support['gd']['installed']) {
        $gd_info = gd_info();
        $support['gd']['webp'] = isset($gd_info['WebP Support']) && $gd_info['WebP Support'];
        $support['gd']['avif'] = function_exists('imageavif') && defined('IMG_AVIF') && (imagetypes() & IMG_AVIF);
    }

    // 检测Imagick支持
    if ($support['imagick']['installed']) {
        try {
            $imagick = new Imagick();
            $formats = array_map('strtoupper', $imagick->queryFormats());
            $support['imagick']['avif'] = in_array('AVIF', $formats);
            $support['imagick']['webp'] = in_array('WEBP', $formats);
        } catch (Exception $e) {
            error_log('Imagick检测失败: ' . $e->getMessage());
        }
    }

    return $support;
}

// 后台显示检测结果
function awc_add_admin_page() {
    add_submenu_page(
        'upload.php',
        __('PHP image format support','duke-yin-helper'),
        __('Image format support','duke-yin-helper'),
        'manage_options',
        'image-format-support',
        'awc_display_support_page'
    );
}
add_action('admin_menu', 'awc_add_admin_page');

function awc_display_support_page() {
    $support = awc_check_support();
    ?>
    <div class="wrap">
        <h1><?php _e('PHP image format support','duke-yin-helper'); ?></h1>
        <table class="form-table">
            <tbody>
                <tr>
                    <th colspan="2"><h2>GD</h2></th>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Installed','duke-yin-helper'); ?></th>
                    <td><?php echo $support['gd']['installed'] ? '✅' : '❌'; ?></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('AVIF support','duke-yin-helper'); ?></th>
                    <td><?php echo $support['gd']['avif'] ? '✅' : '❌'; ?></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('WebP support','duke-yin-helper'); ?></th>
                    <td><?php echo $support['gd']['webp'] ? '✅' : '❌'; ?></td>
                </tr>
                <tr>
                    <th colspan="2"><h2>Imagick</h2></th>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Installed','duke-yin-helper'); ?></th>
                    <td><?php echo $support['imagick']['installed'] ? '✅' : '❌'; ?></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('AVIF support','duke-yin-helper'); ?></th>
                    <td><?php echo $support['imagick']['avif'] ? '✅' : '❌'; ?></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('WebP support','duke-yin-helper'); ?></th>
                    <td><?php echo $support['imagick']['webp'] ? '✅' : '❌'; ?></td>
                </tr>
                <tr>
                    <th colspan="2"><h2><?php _e('Conversion policy','duke-yin-helper'); ?></h2></th>
                </tr>
                <tr>
                    <th colspan="2"><?php
                if($support['gd']['avif']){
                    echo __('to avif by GD','duke-yin-helper');
                }elseif($support['gd']['webp']){
                    echo __('to WebP by GD','duke-yin-helper');
                }elseif($support['imagick']['avif']){
                    echo __('to avif by Imagick','duke-yin-helper');
                }elseif($support['imagick']['webp']){
                    echo __('to WebP by Imagick','duke-yin-helper');
                }
                ?></th>
                </tr>
            </tbody>
        </table>
    </div>
    <?php
}

// 处理图片上传
add_filter('wp_handle_upload_prefilter', 'awc_convert_image');

function awc_convert_image($file) {
    // 仅处理图片文件
    if (!str_starts_with($file['type'], 'image/')) {
        return $file;
    }

    $support = awc_check_support();
    $tmp_path = $file['tmp_name'];
    $converted = false;
    $target_format = null;

    // 确定目标格式和转换方式
    if (($support['gd']['avif'] || $support['imagick']['avif'])) {
        $target_format = 'avif';
    } elseif (($support['gd']['webp'] || $support['imagick']['webp'])) {
        $target_format = 'webp';
    }

    if (!$target_format) return $file;

    // 优先使用GD库
    if ($target_format === 'avif' && $support['gd']['avif']) {
        $converted = awc_convert_with_gd($tmp_path, 'avif');
    } elseif ($target_format === 'avif' && $support['imagick']['avif']) {
        $converted = awc_convert_with_imagick($tmp_path, 'avif');
    }

    if (!$converted && $target_format === 'webp') {
        if ($support['gd']['webp']) {
            $target_format = 'webp';
            $converted = awc_convert_with_gd($tmp_path, 'webp');
        } elseif ($support['imagick']['webp']) {
            $target_format = 'webp';
            $converted = awc_convert_with_imagick($tmp_path, 'webp');
        }
    }

    if ($converted) {
        // 更新文件信息
        $file['name'] = preg_replace('/\.(jpe?g|png|gif)$/i', '.' . $target_format, $file['name']);
        $file['type'] = ($target_format === 'avif') ? 'image/avif' : 'image/webp';
    }

    return $file;
}

// GD库转换函数
function awc_convert_with_gd($path, $format) {
    try {
        $image = null;
        $mime = mime_content_type($path);

        switch ($mime) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($path);
                break;
            case 'image/png':
                $image = imagecreatefrompng($path);
                imagealphablending($image, false);
                imagesavealpha($image, true);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($path);
                break;
            default:
                return false;
        }

        if (!$image) return false;

        $success = false;
        switch ($format) {
            case 'avif':
                $success = imageavif($image, $path, 80);
                break;
            case 'webp':
                $success = imagewebp($image, $path, 80);
                break;
        }

        imagedestroy($image);
        return $success;
    } catch (Exception $e) {
        error_log('GD转换错误: ' . $e->getMessage());
        return false;
    }
}

// Imagick转换函数
function awc_convert_with_imagick($path, $format) {
    try {
        $imagick = new Imagick($path);
        $format = strtoupper($format);

        // 保留透明度
        $imagick->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);
        $imagick->setFormat($format);
        $imagick->setImageCompressionQuality(80);

        // 删除EXIF数据
        $imagick->stripImage();

        $success = $imagick->writeImage($path);
        $imagick->clear();
        return $success;
    } catch (Exception $e) {
        error_log('Imagick转换错误: ' . $e->getMessage());
        return false;
    }
}