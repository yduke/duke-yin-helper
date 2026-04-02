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
    $has_alpha = awc_image_has_alpha($tmp_path);

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
/**
 * 检测图片是否包含透明部分
 *
 * @param string $imagePath 图片路径
 * @return bool true = 有透明部分，false = 无透明部分
 */
function awc_image_has_alpha(string $imagePath): bool
{
    // -------------------------------------------------------------------------
    // 1. 基础校验
    // -------------------------------------------------------------------------
    if (!file_exists($imagePath) || !is_readable($imagePath)) {
        return false;
    }

    // -------------------------------------------------------------------------
    // 2. 快速排除：不可能携带 Alpha 通道的格式 → 直接返回 false
    //    使用 finfo（魔数检测）而非扩展名，防止扩展名被篡改
    // -------------------------------------------------------------------------
    $noAlphaFormats = [
        'image/jpeg',
        'image/jpg',
        'image/bmp',
        'image/x-bmp',
        'image/x-ms-bmp',
        'image/x-windows-bmp',
        'image/vnd.ms-bmp',
        'image/x-tga',          // TGA 理论上支持 alpha，但实际极少使用且 PHP 扩展均不支持
        'image/x-xcf',
        'image/jp2',
        'image/jpx',
    ];

    $mime = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = (string) finfo_file($finfo, $imagePath);
        finfo_close($finfo);
    } elseif (function_exists('mime_content_type')) {
        $mime = (string) mime_content_type($imagePath);
    }

    $mime = strtolower(trim($mime));
    if ($mime !== '' && in_array($mime, $noAlphaFormats, true)) {
        return false;
    }

    // -------------------------------------------------------------------------
    // 3. Imagick 检测（优先）
    // -------------------------------------------------------------------------
    if (extension_loaded('imagick') && class_exists('Imagick')) {
        return _hasTransparencyImagick($imagePath);
    }

    // -------------------------------------------------------------------------
    // 4. GD 检测（备选）
    // -------------------------------------------------------------------------
    if (extension_loaded('gd') && function_exists('imagecreatefromstring')) {
        return _hasTransparencyGD($imagePath);
    }

    // -------------------------------------------------------------------------
    // 5. 两种扩展都不可用 → 保守地返回 true（假设有透明）
    // -------------------------------------------------------------------------
    return true;
}


// =============================================================================
// 内部辅助函数
// =============================================================================

/**
 * 使用 Imagick 检测透明度
 */
function _hasTransparencyImagick(string $imagePath): bool
{
    try {
        $imagick = new Imagick();

        // 只读第一帧（GIF / APNG / WebP 动图），避免遍历所有帧浪费资源
        $imagick->pingImage($imagePath . '[0]');

        // 先用通道信息快速判断：是否存在 Alpha 通道
        $hasAlphaChannel = $imagick->getImageAlphaChannel();
        if (!$hasAlphaChannel) {
            $imagick->destroy();
            return false;
        }

        // 有 Alpha 通道 → 进一步验证是否真的有像素是半透明/全透明
        // 重新完整读取图像（pingImage 不解码像素数据）
        $imagick->destroy();
        $imagick = new Imagick($imagePath . '[0]');

        // 获取 Alpha 通道的统计信息（最小值）
        // Alpha 在 Imagick 中：0.0 = 完全透明，1.0 = 完全不透明（归一化后）
        $channelStats = $imagick->getImageChannelStatistics();
        $imagick->destroy();

        if (isset($channelStats[Imagick::CHANNEL_ALPHA])) {
            $alphaMin = $channelStats[Imagick::CHANNEL_ALPHA]['minima'] ?? 1.0;
            $alphaMax = $channelStats[Imagick::CHANNEL_ALPHA]['maxima'] ?? 1.0;

            // Imagick 内部量子值范围为 0 ~ QUANTUM（通常 65535）
            $quantum = defined('Imagick::QUANTUM') ? Imagick::QUANTUM : 65535;

            // 最小值 < QUANTUM 说明至少有一个像素不完全不透明
            // 全部像素最大值也 < QUANTUM 说明整图都是透明/半透明
            // 只要 minima < quantum，就存在透明像素
            return $alphaMin < $quantum;
        }

        return false;

    } catch (ImagickException $e) {
        // Imagick 处理失败，降级到 GD
        if (extension_loaded('gd') && function_exists('imagecreatefromstring')) {
            return _hasTransparencyGD($imagePath);
        }
        return true; // 无法判断，保守返回 true
    }
}


/**
 * 使用 GD 检测透明度
 */
function _hasTransparencyGD(string $imagePath): bool
{
    try {
        $imageData = file_get_contents($imagePath);
        if ($imageData === false) {
            return true;
        }

        $image = @imagecreatefromstring($imageData);
        if ($image === false) {
            return true; // GD 无法解析，保守返回 true
        }

        // 检查是否为真彩色图像（含 Alpha）
        if (imageistruecolor($image)) {
            $result = _gdScanAlphaPixels($image);
            imagedestroy($image);
            return $result;
        }

        // 调色板图像：检查是否有透明色索引
        $transparentIndex = imagecolortransparent($image);
        if ($transparentIndex >= 0) {
            imagedestroy($image);
            return true;
        }

        imagedestroy($image);
        return false;

    } catch (Throwable $e) {
        return true; // 异常时保守返回 true
    }
}


/**
 * 扫描 GD 真彩色图像的像素，寻找非完全不透明的像素
 * GD Alpha：0 = 完全不透明，127 = 完全透明
 *
 * 为了性能，采用网格采样：先粗粒度扫描，发现候选后精扫描
 */
function _gdScanAlphaPixels($image): bool
{
    $width  = imagesx($image);
    $height = imagesy($image);

    if ($width <= 0 || $height <= 0) {
        return false;
    }

    // 第一轮：步长16粗扫，快速排除大多数无透明的图像
    $step = max(1, (int) min(16, min($width, $height) / 4));
    for ($y = 0; $y < $height; $y += $step) {
        for ($x = 0; $x < $width; $x += $step) {
            $color = imagecolorat($image, $x, $y);
            $alpha = ($color >> 24) & 0x7F; // 提取 alpha 分量
            if ($alpha > 0) {
                return true; // 粗扫命中，直接返回
            }
        }
    }

    // 第二轮：如果步长 > 1，对剩余未采样像素做补充扫描
    // （确保不遗漏小面积的透明区域）
    if ($step > 1) {
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                // 跳过第一轮已扫描的格点
                if ($x % $step === 0 && $y % $step === 0) {
                    continue;
                }
                $color = imagecolorat($image, $x, $y);
                $alpha = ($color >> 24) & 0x7F;
                if ($alpha > 0) {
                    return true;
                }
            }
        }
    }

    return false;
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