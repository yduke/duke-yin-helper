<?php

require("import.save.php");


// Find posts without a meta entry for the fruit custom field
$posts_without_movies = posts_without_meta( '_zmovies_json', 'film_review', 'ids' );

$posts = array();

// If the result didn't come back false
if( $posts_without_movies ) {
	// Pass the IDs returned into get_posts
	$args = array(
        'posts_per_page'   => 9999,
        'offset'           => 0,
        'category'         => '',
        'orderby'          => 'post_date',
        'order'            => 'ASC',
        'include'          => implode( ',', $posts_without_movies),
        'exclude'          => '',
        'meta_key'         => '',
        'meta_value'       => '',
        'post_type'        => 'film_review',
        'post_mime_type'   => '',
        'post_parent'      => '',
        'post_status'      => '',
        'suppress_filters' => true
    );
	$posts = get_posts( $args );
}

?>
<?php if( $data_saved ) { ?>
<style>
#importing-message { display:none; }
</style>
<div class="updated">
    <p><strong><?php _e('Your import was completed.', 'duke-yin-helper' ); ?></strong></p>
</div>
<?php } ?>
<div class="wrap">
    <h2><?php echo _e( 'Movie Import Tool', 'duke-yin-helper' ) ?></h2>
<?php if( !Movies::is_configured() ) { ?>
    <p><?php _e('You cannot use the import tool without an API key. Please go to Functions in the DukeYin Options and enter your TMDB API key.','duke-yin-helper') ?></p>
<?php } else if(empty($posts)) { ?>
    <p><?php _e('There were no posts found without movie data attached to them.','duke-yin-helper') ?></p>
<?php } else { ?>
<br />
<style>
#zmovies-import th { font-weight:bold; }
#zmovies-import td select { width:100%; }
#zmovies-import td, #zmovies-import td select option { font-size:medium; }
#zmovies-import th.images { width:60px; }
#zmovies-import td.poster, #zmovies-import td.backdrop { width:30px;font-size:xx-large;line-height:50%; }
#zmovies-import td a.inactive { color:#333; }
</style>
<script>
function update_preview_link(is_poster, post_id) {
    var dropdown = document.getElementById('tmdb_'+post_id);
    var opt = dropdown.options[dropdown.selectedIndex];
    if(opt.className == 'enter-id') {
        var custom_id;
        if(custom_id = prompt('Enter the TMDb ID you want')) {
            var custom_opt = document.createElement('option');
            custom_opt.setAttribute('value', post_id+'|'+custom_id);
            custom_opt.innerHTML = 'TMDb ID '+custom_id;
            dropdown.appendChild(custom_opt);
            dropdown.selectedIndex = dropdown.options.length - 1;
            return true;
        } else {
            return false;
        }
    }
    if(is_poster) {
        var url = opt.getAttribute('data-poster');
        var tag = document.getElementById('poster_'+post_id);
    } else {
        var url = opt.getAttribute('data-backdrop');
        var tag = document.getElementById('backdrop_'+post_id);
    }
    if(url) {
        tag.href = url;
        tag.className = 'active';
    } else {
        tag.href = 'javascript:alert("No image available");';
        tag.className = 'inactive';
    }
}
function update_preview_links(post_id) {
    update_preview_link(true, post_id);
    update_preview_link(false, post_id);
}
</script>
<form method="post" action="">
    <table id="zmovies-import" class="wp-list-table widefat fixed posts">
    <thead>
        <tr>
            <th class="title"><?php _e('Movie Title','duke-yin-helper'); ?></th>
            <th class="tmdb"><?php _e('Best TMDb Match(es)','duke-yin-helper'); ?></th>
            <th colspan="2" class="images"><?php _e('Images','duke-yin-helper'); ?></th>
        </tr>
    </thead>
    <tbody>
<?php $c = true; foreach($posts as $post){ ?>
        <tr<?php if($c = !$c) { ?> class="alternate"<?php } ?>>
            <td class="title"><?php echo $post->post_title ?></td>
            <td class="tmdb">
                <select name="posts[]" id="tmdb_<?php echo $post->ID ?>"
                        onchange="update_preview_links(<?php echo $post->ID ?>);">
<?php
$search = Movies::$TMDB->searchMovie($post->post_title,'cl');
if( count($search) < 1 && preg_match("@:@", $post->post_title) ) {
    $parts = explode(":", $post->post_title, 2);
    if( count($parts) > 1 )
    {
        $title = trim($parts[1]);
        $search = Movies::$TMDB->searchMovie($title,'cl');
    }
}
foreach($search as $movie) {
?>
                    <option
                        value="<?php echo $post->ID ?>|<?php echo $movie->getID() ?>"
                        <?php if($movie->getPoster()) { ?>
                        data-poster="<?php echo Movies::$TMDB->getImageURL($size = "w500").$movie->getPoster() ?>"
                        <?php } ?>
                        <?php if($movie->getBackdrop()) { ?>
                        data-backdrop="<?php echo Movies::$TMDB->getImageURL($size = "w1280").$movie->getBackdrop() ?>"
                        <?php } ?>
                    >
                        <?php echo $movie->getTitle() ?>
                        <?php if($movie->getYear()) { ?>
                            (<?php echo $movie->getYear() ?>)
                        <?php } ?>
                    </option>
<?php } ?>
                    <option value="" class="enter-id"><?php _e('Enter a TMDb ID','duke-yin-helper');?></option>
                    <option value=""><?php _e('None (Leave Blank)','duke-yin-helper');?></option>
                </select>
            </td>
            <td class="poster">
                <a id="poster_<?php echo $post->ID ?>" href="#" target="_blank">&#9859;</a>
            </td>
            <td class="backdrop">
                <a id="backdrop_<?php echo $post->ID ?>" href="#" target="_blank">&#9873;</a>
                <script>
                    update_preview_links(<?php echo $post->ID ?>);
                </script>
            </td>
        </tr>
<?php } ?>
    </tbody>
    </table>
    <p class="submit">
        <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Import Data','duke-yin-helper') ?>" />
    </p>
</form>
<?php } ?>
</div>