<?php

//must check that the user has the required capability 
if (!current_user_can('manage_options'))
{
  wp_die( __('You do not have sufficient permissions to access this page.') );
}

$operation_completed = false;
$operation_error = false;
require plugin_dir_path( __FILE__ ) . 'movie_game_functions.php';

if( isset($_POST['operation']) ) {
    if( in_array($_POST['operation'], array('clear_movie_for_post_id', 'clear_movie_for_tmdb_id','clear_game_for_post_id','clear_game_for_steamgrid_id')) ) {
        if( !isset($_POST['val']) || empty($_POST['val']) ) {
            $operation_error = true;
            $error_msg = "You didn't enter an ID.";
        } else {
            if( $_POST['operation'] == 'clear_movie_for_tmdb_id' ) {
                clear_data_for_tmdb_id( $_POST['val'] );
                $operation_completed = true;
            } else if( $_POST['operation'] == 'clear_movie_for_post_id' ) {
                clear_movie_data_for_post( $_POST['val'] );
                $operation_completed = true;
            }
        }
    } else if( $_POST['operation'] == 'clear_all_movie_data' ) {
        clear_all_movie_data();
        $operation_completed = true;
    } else if( $_POST['operation'] == 'clear_zero' ) {
        clear_movie_zero();
        $operation_completed = true;
    }
}

if ( $operation_error ) { ?>
<div class="updated">
    <p><strong><?php _e("There was an error. $error_msg", 'duke-yin-helper' ); ?></strong></p>
</div>
<? } else if( $operation_completed ) { ?>
<div class="updated">
    <p><strong><?php _e('The operation was completed.', 'duke-yin-helper' ); ?></strong></p>
</div>
<?php } ?>

<div class="wrap">
    <h2><?php _e( 'Movies and Games', 'duke-yin-helper' ) ?></h2>
    <p><?php _e('Import movies, tv-shows or games from the movie database or Steam grid database.','duke-yin-helper') ?></p>
    <a class="button" href="<?php echo admin_url( '/admin.php?page=dk-movie-importer' );?>"><?php _e( 'Import a movie or tv-show', 'duke-yin-helper' ) ?></a>
    <a class="button" href="<?php echo admin_url( '/admin.php?page=dk-game-importer' );?>"><?php _e( 'Import a game', 'duke-yin-helper' ) ?></a>
    <h3><?php _e( 'Movies and TV shows Tools', 'duke-yin-helper' ) ?></h3>
    <form method="post" action="" onsubmit="if(!confirm('Are you sure you want to do this?')){ return false; }">
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th><?php _e("Operation:","duke-yin-helper"); ?></th>
                    <td>
                        <select name="operation">
                            <option selected><?php _e('Select Action','duke-yin-helper') ?></option>
                            <option value="clear_movie_for_post_id"><?php _e('Clear movie or tv-show data for a specific post ID','duke-yin-helper') ?></option>
                            <option value="clear_movie_for_tmdb_id"><?php _e('Clear movie or tv-show data for a specific TMDb ID','duke-yin-helper') ?></option>
                            <option value="clear_zero"><?php _e('Clear cast crew languages and categories with zero posts.','duke-yin-helper') ?></option>
                            <option value="clear_all_movie_data"><?php _e('Clear all movies and tv-shows data for all posts','duke-yin-helper') ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                <th><?php _e("ID:"); ?> </th>
                    <td><input type="text" name="val" value="" size="20"><br />
                        <small><?php _e('enter an ID when applicable; if not, leave blank','duke-yin-helper') ?></small>
                    </td>
                </tr>
            </tbody>
        </table>
        <p class="submit">
            <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Proceed',"duke-yin-helper") ?>" />
        </p>
    </form>

    <h3><?php _e( 'Game Tools', 'duke-yin-helper' ) ?></h3>
    <form method="post" action="" onsubmit="if(!confirm('Are you sure you want to do this?')){ return false; }">
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
                    <td><input type="text" name="val" value="" size="20"><br />
                        <small><?php _e('enter an ID when applicable; if not, leave blank','duke-yin-helper') ?></small>
                    </td>
                </tr>
            </tbody>
        </table>
        <p class="submit">
            <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Proceed',"duke-yin-helper") ?>" />
        </p>
    </form>
</div>