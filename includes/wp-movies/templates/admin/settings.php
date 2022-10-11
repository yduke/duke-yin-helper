<?php

//must check that the user has the required capability 
if (!current_user_can('manage_options'))
{
  wp_die( __('You do not have sufficient permissions to access this page.','duke-yin-helper') );
}

// variables for the field and option names 
$opts = Movies::$settings;

$settings_updated = false;

foreach(array_keys($opts) as $opt_name) {
    // Read in existing option value from database
    $opt_val = get_option( $opt_name );

    if( isset($_POST[ $opt_name ]) ) {
        $opt_val = $_POST[ $opt_name ];
        update_option( $opt_name, $opt_val );
        $settings_updated = true;
    }
    
    $opts[$opt_name]['value'] = $opt_val;
}

if( $settings_updated ) {
?>
<div class="updated">
    <p><strong><?php _e('Your settings have been saved.', 'duke-yin-helper' ); ?></strong></p>
</div>
<?php } ?>

<div class="wrap">
    <h2><?php _e( 'Movies Plugin Settings', 'duke-yin-helper' ) ?></h2>
    <form method="post" action="">
        <table class="form-table" role="presentation">
            <?php foreach($opts as $opt_name => $opt) { ?>
                    <tr>
                    <th> <?php _e($opt['label'] . ":", $opt_name ); ?> </th>
                    <td><input type="text" name="<?php echo $opt_name; ?>" value="<?php echo $opt['value']; ?>" size="35"><br />
                        <small><?php echo $opt['description'] ?></small></td>
            </tr>
            <?php } ?>
        </table>
        <p class="submit">
            <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes','duke-yin-helper') ?>" />
        </p>

    </form>
</div>