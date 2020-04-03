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
function replace_gravatar($avatar) {
$avatar = str_replace(array("//gravatar.com/", "//secure.gravatar.com/", "//www.gravatar.com/", "//0.gravatar.com/", "//1.gravatar.com/", "//2.gravatar.com/", "//cn.gravatar.com/"), "//dn-qiniu-avatar.qbox.me/", $avatar);
return $avatar;}
add_filter( 'get_avatar', 'replace_gravatar' );

//let the custom taxonomy review_categories pagination work
add_filter( 'option_posts_per_page', 'duke_tax_filter_posts_per_page' );
function duke_tax_filter_posts_per_page( $value ) {
return (is_tax('review_categories')) ? 1 : $value;
}