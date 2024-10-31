<?php
if ( $_POST['s8-update-settings'] != '' ) {
    $endpoint = strip_tags(preg_replace( '/[^a-zA-Z0-9\-_]/', '', str_replace( ' ', '-', trim( strtolower( $_POST['s8-endpoint'] ) ) ) ) );
    $redirect = ( 'yes' == $_POST['s8-pp-redirect'] ) ? 'yes' : 'no';
    update_option( 's8_pp_endpoint', $endpoint );
    update_option( 's8_pp_redirect', $redirect );
}
$endpoint = get_option( 's8_pp_endpoint' );
$redirect = get_option( 's8_pp_redirect' );

$checked = ' checked="checked"';
?>
<div class="wrap">
    <div id="icon-options-general" class="icon32"></div><h2><?php _e( 'Private Page Settings' ); ?></h2>
    <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
        <table class="">
            <tr valign="top">
                <th scope="row">
                    <label for="s8-endpoint"><?php _e( 'URL Endpoint' ); ?></label>
                </th>
                <td>
                    <input type="text" name="s8-endpoint" id="s8-endpoint" value="<?php echo $endpoint; ?>">
                    <p class="description">This allows you to customize your URL. For example, setting it to "private-pages" would make the URL "<?php echo home_url(); ?>/private-pages/YOUR-PAGE-NAME/". Only lowercase letters, numbers, hyphens (-) and underscores (_) are allowed.</p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">
                    <label for="s8-pp-redirect"><?php _e( 'Auto Redirect Users' ); ?></label>
                </th>
                <td>
                    <input type="checkbox" name="s8-pp-redirect" id="s8-pp-redirect" value="yes"<?php if ( 'yes' == $redirect ) echo $checked; ?> /> <label for="s8-pp-redirect"><?php _e( 'Redirect private page users to their private page' ); ?></label>
                    <p class="description">Redirect any users with a private page to their private page upon login. Any users without a private page will NOT be redirected!</p>
                </td>
            </tr>
        </table>
        <input class='button-primary' type='submit' name='s8-update-settings' value='<?php _e( 'Save Settings' ); ?>' id='submitbutton' />
    </form>
</div>
