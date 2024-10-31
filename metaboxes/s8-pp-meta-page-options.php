<?php
wp_nonce_field( plugin_basename( S8_PP_FILE ), 's8_pp_nonce' );
$s8_pp_user = get_post_meta( $post->ID, $this->meta_post_uid, true );
// Output form HTML here!
if ( $post->post_parent == 0 && $s8_pp_user && $s8_pp_user > 0 ) {
$author_info = get_userdata( $post->post_author );
?><p>This is the primary private page for "<?php echo $author_info->user_login; ?>". You cannot reassign this page to someone else.</p>
<input type="hidden" name="private_user" value="<?php echo $s8_pp_user; ?>" /><?php
} elseif ( $s8_pp_user && $s8_pp_user > 0 ) {
    $author_info = get_userdata( $post->post_author );
    ?><p>This is currently a private subpage for "<?php echo $author_info->user_login; ?>".</p><?php
    $private_users = get_users(array('meta_key' => $this->meta_user_pp, 'meta_value' => 'yes'));
    $private_ids = array();
    $selected = ' selected="selected"';
    ?><p>You may use the options below to change to whom this page belongs.</p><?php
    if($private_users) {
        ?><p>
        <label for="private_user">Pick an existing private user to add this as a subpage:</label><br/>
        <select name="private_user" id="private_user">
            <option value="0"></option>
            <?php
            foreach($private_users as $user) {
                echo '<option value="'.$user->ID.'"';
                if ( $s8_pp_user && $s8_pp_user == $user->ID )
                    echo $selected;
                echo '>'.$user->user_login.'</option>';
                $private_ids[] = $user->ID;
            }
            ?>
        </select>
        </p>
    <?php }
    $non_private_users = get_users(array('exclude' => $private_ids));
    if($non_private_users) {
        ?><p>
        <label for="new_private_user">Pick a user to make a private page user and make this their primary private page:</label><br/>
        <select name="new_private_user" id="new_private_user">
            <option value="0"></option>
            <?php
            foreach($non_private_users as $user) {
                echo '<option value="'.$user->ID.'">'.$user->user_login.'</option>';
            }
            ?>
        </select>
        </p>
    <?php }
} else {
    $private_users = get_users(array('meta_key' => $this->meta_user_pp, 'meta_value' => 'yes'));
    $private_ids = array();
    if($private_users) {
        ?><p>
        <label for="private_user">Pick an existing private user to add this as a subpage:</label><br/>
        <select name="private_user" id="private_user">
            <option value="0"></option>
            <?php
            foreach($private_users as $user) {
                echo '<option value="'.$user->ID.'">'.$user->user_login.'</option>';
                $private_ids[] = $user->ID;
            }
            ?>
        </select>
    </p>
    <?php }
    else {
        echo 'There are no private page users yet!';
    }
    $non_private_users = get_users(array('exclude' => $private_ids));
    if($non_private_users) {
        ?><p>
        <label for="new_private_user">Pick a user to make a private page user and make this their primary private page:</label><br/>
        <select name="new_private_user" id="new_private_user">
            <option value="0"></option>
            <?php
            foreach($non_private_users as $user) {
                echo '<option value="'.$user->ID.'">'.$user->user_login.'</option>';
            }
            ?>
        </select>
    </p>
    <?php }
}
$templates = get_page_templates();
if ( is_array( $templates ) && 0 < count( $templates ) ) { ?>
    <p>
        <label for="page_template"><? _e( 'Page Template' ); ?></label><br/>
        <select name="_wp_page_template" id="page_template">
            <option value="default"><? _e( 'Default' ); ?></option>
            <?php
            $selected = ' selected="selected"';
            $current_template = get_post_meta( $post->ID, '_wp_page_template', true );
            foreach ( $templates as $label=>$file ) {
                echo '<option value="' . $file . '"';
                if ( $file == $current_template ) echo $selected;
                echo '>' . $label . '</option>';
            }
            ?>
        </select>
    </p>
<?php } ?>
