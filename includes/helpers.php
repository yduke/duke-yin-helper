<?php
//auto fill ima title for SEO
function image_alt_tag($content){
    global $post;preg_match_all('/<img (.*?)\/>/', $content, $images);
    if(!is_null($images)) {foreach($images[1] as $index => $value)
    {$new_img = str_replace('<img', '<img alt="'.get_the_title().'" title="'.get_the_title().'"', $images[0][$index]);
    $content = str_replace($images[0][$index], $new_img, $content);}}
    return $content;
}
add_filter('the_content', 'image_alt_tag', 99999);

// gravatar to qiniu CDN

function dk_replace_gravatar($avatar)
{
$dukeyin_options = get_site_option( 'options-page', true, false);
$dk_gravatar_cdn = ($dukeyin_options['gravatar-cdn'] ?? '0');
 if ($dk_gravatar_cdn == '0') {
return $avatar;
 }

 if ($dk_gravatar_cdn == '1') {
  $avatar = str_replace(array("//gravatar.com/", "//www.gravatar.com/", "//0.gravatar.com/", "//1.gravatar.com/", "//2.gravatar.com/", "//cn.gravatar.com/"), "//secure.gravatar.com/", $avatar);}

 if ($dk_gravatar_cdn == '2') {
  $avatar = str_replace(array("//gravatar.com/", "//secure.gravatar.com/", "//www.gravatar.com/", "//0.gravatar.com/", "//1.gravatar.com/", "//2.gravatar.com/", "//cn.gravatar.com/"), "//dn-qiniu-avatar.qbox.me/", $avatar);}

 if ($dk_gravatar_cdn == '4') {
  $avatar = str_replace(array("//gravatar.com/", "//secure.gravatar.com/", "//www.gravatar.com/", "//0.gravatar.com/", "//1.gravatar.com/", "//2.gravatar.com/", "//cn.gravatar.com/"), "//gravatar.inwao.com/", $avatar);}

 if ($dk_gravatar_cdn == '3') {
  $avatar = str_replace(array("//gravatar.com/", "//secure.gravatar.com/", "//www.gravatar.com/", "//0.gravatar.com/", "//1.gravatar.com/", "//2.gravatar.com/", "//cn.gravatar.com/"), "//gravatar.wp-china-yes.net/", $avatar);}

 if ($dk_gravatar_cdn == '5') {
  $avatar = str_replace(array("//gravatar.com/", "//secure.gravatar.com/", "//www.gravatar.com/", "//0.gravatar.com/", "//1.gravatar.com/", "//2.gravatar.com/", "//cn.gravatar.com/"), "//gravatar.loli.net/", $avatar);}

 return $avatar;}
add_filter('get_avatar', 'dk_replace_gravatar');

//let the custom taxonomy review_categories pagination work
add_filter( 'option_posts_per_page', 'duke_tax_filter_posts_per_page' );
function duke_tax_filter_posts_per_page( $value ) {
return (is_tax('review_categories')) ? 1 : $value;
}

//Indicator

if (!function_exists('dukeyin_theme_dashboard_widgets')) {
add_action('wp_dashboard_setup', 'dukeyin_theme_dashboard_widgets');
function dukeyin_theme_dashboard_widgets() {
global $wp_meta_boxes;
 
wp_add_dashboard_widget('dukeyin_help_widget', __('Duke Yin Theme Support','duke-yin-helper'), 'dukeyin_dashboard_help');
}
}

if (!function_exists('dukeyin_dashboard_help')) {
function dukeyin_dashboard_help() {
$theme = wp_get_theme('dukeyin');
echo '<div><p><span>';
echo __('Theme Name: ','duke-yin-helper');
echo '</span><span id="dk_theme_name"><b>';
echo $theme->get( 'Name' );
echo '</b></span></p><p><span>';
echo __('Installed Version: ','duke-yin-helper');
echo '</span><span id="dk_theme_version"><b>';
echo $theme->get( 'Version' );
echo '</b></span></p><p><span>';
echo __('Current Version: ','duke-yin-helper');
echo '</span><strong><span id="dk_current_version"></span></strong></p><p><span>';
echo __('Last Update: ','duke-yin-helper');
echo '</span><span id="dk_last_updated"></span></p><p><span>';
echo __('Change log: ','duke-yin-helper');
echo '</span><span><a id="dk_change_log" target="_blank">';
echo __('View detail','duke-yin-helper');
echo'<a></span>';
echo '</div>';

echo <<<ETO
<script>
jQuery.ajax({
  url: "https://update.dukeyin.com/?action=get_metadata&slug=dukeyin",
  type: "GET",
  dataType: "json",
  success: function (data) {
		var utc = data.last_updated + 'Z';
		var date = new Date(Date.parse(utc));
        jQuery("#dk_current_version").append( data.version );
        jQuery("#dk_last_updated").append( date.toLocaleString() );
        jQuery("#dk_change_log").attr("href", data.details_url );
		}
});
</script>
ETO;
}
}

//replace jQuery to cdn
function replace_core_jquery() {
	$dukeyin_options=get_site_option( 'options-page', true, false);
	$dk_jquery_cdn = ($dukeyin_options['jquery-cdn'] ?? 'off');
	if($dk_jquery_cdn === 'on'){
		wp_deregister_script( 'jquery-core' );
		wp_register_script( 'jquery-core', "//cdn.staticfile.org/jquery/3.6.0/jquery.min.js", array(), '3.6.0' );
		wp_deregister_script( 'jquery-migrate' );
		wp_register_script( 'jquery-migrate', "//cdn.staticfile.org/jquery-migrate/3.3.2/jquery-migrate.min.js", array(), '3.3.2' );
	}
}
add_action( 'wp_enqueue_scripts', 'replace_core_jquery' );

// Enable link manager
add_filter( 'pre_option_link_manager_enabled', '__return_true' );

//covert image to webp
// add_filter( 'wp_handle_upload', 'create_webp' );

function create_webp($file) {

    if ($file['type'] === "image/png") {
     // Create and save
        $img = imagecreatefrompng($file['file']);
        imagepalettetotruecolor($img);  
        imagealphablending($img, true);
        imagesavealpha($img, true);
        imagewebp($img, str_replace(".png" ,".webp", $file['file']), 75);
        imagedestroy($img);
   
    }elseif($file['type'] === "image/jpg" || $file['type'] === "image/jpeg"){
        $img = imagecreatefromjpeg($file['file']); 
        imagepalettetotruecolor($img);  
        imagealphablending($img, true);
        imagesavealpha($img, true);
        if($file['type'] === "image/jpg"){
            imagewebp($img, str_replace(".jpg" ,".webp", $file['file']), 100);
        }
        else{
            imagewebp($img, str_replace(".jpeg" ,".webp", $file['file']), 100);
        }
        imagedestroy($img);
      
    }
    return $file;
}

add_filter( 'wp_delete_file', 'delete_webp' );
