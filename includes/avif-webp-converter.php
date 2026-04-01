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
        __('AVIF WebP Image Converter','duke-yin-helper'),
        __('AVIF WebP Image Converter','duke-yin-helper'),
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
        <h1><?php _e('AVIF WebP Image Converter','duke-yin-helper'); ?></h1>
        <h2><?php _e('Features','duke-yin-helper'); ?></h2>
        <p><?php _e('This plug-in will convert all images upload to media library to AVIF or WebP format depending on your PHP situation. Preferentially it uses Imagick to convert uploaded images to AVIF format. If the server does not support, it will try to convert images to AVIF with GD, if not support, it will try to convert images to WebP with Imagick, lastly try to convert images to WebP with GD. If none of them are supported, the original image will be uploaded. If the uploaded image format is AVIF or WebP, no conversion will be performed.','duke-yin-helper');?></p>
        <h2>Imagick</h2>
        <table class="widefat striped">
            <tbody>
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
            </tbody>
        </table>
        
        <h2>GD</h2>
        <table class="widefat striped">
            <tbody>
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
            </tbody>
        </table>

        <h2><?php _e('Conversion policy','duke-yin-helper'); ?></h2>
        <table class="widefat striped">
            <tbody>
                <tr>
                    <th scope="row"><?php _e('Current policy','duke-yin-helper'); ?></th>
                    <td><strong><?php
                    if($support['imagick']['avif']){
                        echo __('to avif by Imagick','duke-yin-helper');
                    }elseif($support['gd']['avif']){
                        echo __('to avif by GD','duke-yin-helper');
                    }elseif($support['imagick']['webp']){
                        echo __('to WebP by Imagick','duke-yin-helper');
                    }elseif($support['gd']['webp']){
                        echo __('to WebP by GD','duke-yin-helper');
                    }
                    ?></strong>
                    </td>
                </tr>
            </tbody>
        </table>
        <h2><?php _e('More to read','duke-yin-helper'); ?></h2>
        <p><?php _e('Both AVIF and WebP are modern image formats designed to reduce file sizes while maintaining quality. AVIF is newer and offers more efficient compression, often resulting in smaller file sizes than WebP. It means you can get faster download speed at the same image quality for images on web page by using AVIF images.','duke-yin-helper');?></p>
        <p><?php _e('Imagick and GD are both PHP extension, they both capable of converting traditional image format to AVIF or WebP on latest versions. Imagick is not limited by PHP memory, and the converted image quality is better than GD, but it usually needs to be installed separately, and the converted image size is slightly larger than GD. GD is more popular, and the conversion speed is faster than Imagick, the result image size is slightly smaller, but the result image quality is usually slightly worse than Imagick.','duke-yin-helper');?></p>
    </div>
    <?php
}

// 处理图片上传
add_filter('wp_handle_upload_prefilter', 'awc_convert_image');

function awc_convert_image($file) {
    if (!str_starts_with($file['type'], 'image/')) {
        return $file;
    }

    // AVIF/WebP 已是最优格式，无需转换
    if (in_array($file['type'], ['image/avif', 'image/webp'])) {
        return $file;
    }

    $support  = awc_check_support();
    $tmp_path = $file['tmp_name'];

    // 提前检测源图片是否含有 Alpha 通道
    $has_alpha = awc_image_has_alpha($tmp_path, $file['type']);

    $target_format = null;
    $use_imagick   = false;

    if ($has_alpha) {
        // 含 Alpha 的图片：先验证 Imagick 的 AVIF 编码器是否真正支持 Alpha
        // 当前服务器 ImageMagick 6.9.11 的 AVIF 编码器不支持 Alpha，会回退到 WebP
        if ($support['imagick']['avif'] && awc_imagick_supports_avif_alpha()) {
            $target_format = 'avif';
            $use_imagick   = true;
        } elseif ($support['gd']['avif']) {
            $target_format = 'avif';
            $use_imagick   = false;
        } elseif ($support['imagick']['webp']) {
            $target_format = 'webp';
            $use_imagick   = true;
        } elseif ($support['gd']['webp']) {
            $target_format = 'webp';
            $use_imagick   = false;
        }
    } else {
        // 不含 Alpha 的图片（如 JPEG）：按原有优先级 AVIF → WebP
        if ($support['imagick']['avif']) {
            $target_format = 'avif';
            $use_imagick   = true;
        } elseif ($support['gd']['avif']) {
            $target_format = 'avif';
            $use_imagick   = false;
        } elseif ($support['imagick']['webp']) {
            $target_format = 'webp';
            $use_imagick   = true;
        } elseif ($support['gd']['webp']) {
            $target_format = 'webp';
            $use_imagick   = false;
        }
    }

    if (!$target_format) return $file;

    $converted = $use_imagick
        ? awc_convert_with_imagick($tmp_path, $target_format)
        : awc_convert_with_gd($tmp_path, $target_format);

    if ($converted) {
        $file['name'] = preg_replace('/\.(jpe?g|png|gif)$/i', '.' . $target_format, $file['name']);
        $file['type'] = ($target_format === 'avif') ? 'image/avif' : 'image/webp';
    }

    return $file;
}

// 检测图片是否含有 Alpha 通道
function awc_image_has_alpha($path, $mime_type) {
    // JPEG 没有 Alpha，直接返回
    if ($mime_type === 'image/jpeg') return false;

    if (extension_loaded('imagick')) {
        try {
            $im  = new Imagick($path);
            $has = (bool) $im->getImageAlphaChannel();
            $im->destroy();
            return $has;
        } catch (Exception $e) {}
    }

    if (extension_loaded('gd')) {
        $img = null;
        if ($mime_type === 'image/png') $img = @imagecreatefrompng($path);
        if ($mime_type === 'image/gif') $img = @imagecreatefromgif($path);
        if ($img) {
            $w      = imagesx($img);
            $h      = imagesy($img);
            $points = [
                [$w/4, $h/4], [$w/2, $h/2], [$w*3/4, $h/4],
                [$w/4, $h*3/4], [$w*3/4, $h*3/4],
            ];
            foreach ($points as [$x, $y]) {
                $alpha = (imagecolorat($img, (int)$x, (int)$y) >> 24) & 0x7F;
                if ($alpha > 0) {
                    imagedestroy($img);
                    return true;
                }
            }
            imagedestroy($img);
        }
    }

    // PNG/GIF 大概率有 Alpha，保守返回 true
    return in_array($mime_type, ['image/png', 'image/gif']);
}

// 实际转换一张小图来验证当前服务器的 Imagick AVIF 编码器是否支持 Alpha
// 使用 static 缓存，每次请求只执行一次
function awc_imagick_supports_avif_alpha() {
    static $result = null;
    if ($result !== null) return $result;

    try {
        $gd = imagecreatetruecolor(2, 2);
        imagealphablending($gd, false);
        imagesavealpha($gd, true);
        $transparent = imagecolorallocatealpha($gd, 0, 0, 0, 127);
        imagefilledrectangle($gd, 0, 0, 1, 1, $transparent);

        $tmp_png  = sys_get_temp_dir() . '/awc_alpha_test.png';
        $tmp_avif = sys_get_temp_dir() . '/awc_alpha_test.avif';
        imagepng($gd, $tmp_png);
        imagedestroy($gd);

        $im = new Imagick($tmp_png);
        $im->stripImage();
        $im->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);
        $im->setImageBackgroundColor(new ImagickPixel('transparent'));
        $im->setFormat('AVIF');
        $im->setImageFormat('AVIF');
        $im->writeImage($tmp_avif);
        $im->clear();
        $im->destroy();

        // 文件大于 500 字节且 Alpha 标志为真，才算真正支持
        if (file_exists($tmp_avif) && filesize($tmp_avif) > 500) {
            $verify = new Imagick($tmp_avif);
            $result = (bool) $verify->getImageAlphaChannel();
            $verify->destroy();
        } else {
            $result = false;
        }

        @unlink($tmp_png);
        @unlink($tmp_avif);

    } catch (Exception $e) {
        $result = false;
    }

    return $result;
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
        $imagick_format = strtoupper($format);

        // 判断源文件是否含有 Alpha 通道
        // getImageAlphaChannel() 返回 true/false 或通道常量，统一用布尔判断
        $has_alpha = (bool) $imagick->getImageAlphaChannel();

        if ($has_alpha) {
            // ① 先 strip 掉 EXIF（strip 会清除 Alpha 信息，所以必须在它之后重新激活）
            $imagick->stripImage();

            // ② strip 之后立即重新激活 Alpha 通道，防止被清零
            $imagick->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);

            // ③ 设置背景为完全透明，避免 Imagick 内部合成时用黑/白填充空白区域
            $imagick->setImageBackgroundColor(new ImagickPixel('transparent'));
        } else {
            // 不含透明通道的图片（如 JPEG）：strip 后压平到白色背景，防止伪 Alpha 干扰
            $imagick->stripImage();
            $imagick->setImageBackgroundColor(new ImagickPixel('white'));
            $imagick->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
        }

        // 统一转换到 sRGB 色彩空间（AVIF/WebP 均要求 sRGB 或 Display P3）
        // 若源文件已是 sRGB 则无开销
        if ($imagick->getImageColorspace() !== Imagick::COLORSPACE_SRGB) {
            $imagick->transformImageColorspace(Imagick::COLORSPACE_SRGB);
        }

        $imagick->setFormat($imagick_format);
        $imagick->setImageFormat($imagick_format);
        $imagick->setImageCompressionQuality(80);

        $success = $imagick->writeImage($path);
        $imagick->clear();
        $imagick->destroy();
        return $success;
    } catch (Exception $e) {
        error_log('Imagick转换错误: ' . $e->getMessage());
        return false;
    }
}