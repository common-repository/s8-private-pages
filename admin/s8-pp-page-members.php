<?php /** THIS FILE IS CURRENTLY NOT IN USE! **/ ?>
<div class="wrap">
    <div id="icon-edit-pages" class="icon32"></div><h2><?php _e('Manage Private Page Users'); ?></h2>
    <form>
        Add new page for <select name="new_page_user"><option value="0"></option><?php
        $users = get_users();
        if($users) {
            foreach($users as $user) {
                echo '<option value="'.$user->ID.'">'.$user->user_login.'</option>';
            }
        }
        ?></select> <input type="submit" class="button-secondary" name="s8_new_page" value="Add Page">
    </form>
</div>
