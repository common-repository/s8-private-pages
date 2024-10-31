<?php
/**
 * Plugin Name: Sideways8 Private Pages
 * Plugin URI: http://sideways8.com/plugins/s8-private-pages/
 * Description: This plugin from the guys at Sideways8 Interactive allows admins to create private pages (a custom post type) for a specific user that only that user and admins can access. Other users are simply redirected to the home page or to their private page if one exists.
 * Tags: s8, private, private pages, s8 private pages, customer page, customer pages, client pages, client page, client, hide, client area, private area, user pages
 * Version: 0.8.2
 * Author: Sideways8 Interactive
 * Author URI: http://sideways8.com/
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

define( 'S8_PP_FILE', __FILE__ );
define( 'S8_PP_VERSION', '0.8.2' );

class s8_private_pages {
    private $default_ep = 'private',
            $endpoint = false,
            $ep_option = 's8_pp_endpoint',
            $ep_option_check = 's8_pp_endpoint_current',
            $meta_post_uid = '_s8_pp_user_id',
            $meta_user_pp = 's8_pp_user',
            $post_type = 's8_private_pages',
            $internal_query = false;

    /**
     * Setup plugin and call our actions
     * @since 0.8.0
     */
    function s8_private_pages() {
        // Update settings if new version
        if ( get_option( 's8_private_pages_version' ) != S8_PP_VERSION ) $this->update();
        // Add our activation/deactivation hooks
        register_activation_hook( S8_PP_FILE, array( $this, 'activation' ) );
        register_deactivation_hook( S8_PP_FILE, array( $this, 'deactivation' ) );
        // Run our init actions
        add_action( 'init', array( $this, 'init' ) );
        // Add our other functionality actions/filters
        add_action( 'template_include', array( $this, 'template_include' ) );
        add_action( 'template_redirect', array( $this, 'user_redirect' ) );
        add_filter( 'the_posts', array( $this, 'pp_admin_page' ) );
        add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
        // Add our menu
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        // Add our custom error messages in the admin interface
        add_action( 'admin_notices', array( $this, 'admin_notices' ) );
        // Add our metaboxes!
        $this->metaboxes();
        // Keep parent private pages from being deleted, leaving orphaned children
        add_action( 'wp_trash_post', array( $this, 'trash_posts' ) );
        // Add our login action
        add_action( 'wp_login', array( $this, 'login_redirect' ), 20, 2 );
    }

    /**
     * Stuff to run on init
     * @since 0.8.0
     */
    function init() {
        // Verify our endpoint
        $endpoint = get_option( $this->ep_option );
        $endpoint_check = get_option( $this->ep_option_check );
        if ( $endpoint && ! empty( $endpoint ) ) {
            $this->endpoint = $endpoint;
        } else {
            $this->endpoint = $this->default_ep;
        }
        $this->register_cpt();
        $this->endpoint();
        if ( $this->endpoint !== $endpoint_check ) {
            update_option( $this->ep_option_check, $this->endpoint );
            flush_rewrite_rules();
        }
    }

    /**
     * Register our CPT for our "pages"
     * @since 0.8.0
     */
    function register_cpt() {
        register_post_type( $this->post_type, array(
            'labels' => array(
                'name' => 'Private Pages',
                'singular_name' => 'Private Page',
            ),
            'public' => 'false',
            'show_ui' => true,
            'capability_type' => 'page',
            'hierarchical' => true,
            'supports' => array( 'title', 'editor' ),
            'rewrite' => array( 'slug' => $this->endpoint ),
        ) );
    }

    /**
     * Adds our admin menu pages
     * @since 0.8.0
     */
    function admin_menu() {
        //add_submenu_page('edit.php?post_type='.$this->post_type, 'Manage Private Page Users', 'Private Users', 'publish_pages', 's8-private-pages-add', array($this, 'page_members'));
        add_submenu_page( 'edit.php?post_type='.$this->post_type, 'Private Page Settings', 'Settings', 'manage_options', 's8-private-pages-settings', array( $this, 'settings_page' ) );
    }
    function page_members() {
        include( plugin_dir_path( S8_PP_FILE ) . '/admin/s8-pp-page-members.php' );
    }
    function settings_page() {
        include( plugin_dir_path( S8_PP_FILE ) . '/admin/s8-pp-settings.php' );
    }

    /**
     * Add the actions needed for our metaboxes
     * @since 0.8.0
     */
    function metaboxes() {
        add_action('add_meta_boxes', array($this, 'meta_add_boxes'));
        add_action('save_post', array($this, 'meta_save_post'));
    }

    /**
     * Add our meta boxes
     * @since 0.8.0
     */
    function meta_add_boxes() {
        add_meta_box('s8_pp_user_select', 'Private Page Options', array($this, 'meta_private_pages'), $this->post_type, 'side');
    }

    /**
     * Manage the display of our private pages meta box.
     * @param $post
     * @since 0.8.0
     */
    function meta_private_pages($post) {
        include(plugin_dir_path(S8_PP_FILE).'/metaboxes/s8-pp-meta-page-options.php');
    }

    /**
     * Save our post meta on post save
     * @param $post_id
     * @since 0.8.0
     */
    function meta_save_post( $post_id ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
            return;
        if ( defined( 'S8_SAVING_POST' ) && S8_SAVING_POST )
            return;
        // Check permissions
        if ( ! current_user_can( 'edit_page', $post_id ) )
            return;
        elseif ( ! current_user_can( 'edit_post', $post_id ) )
            return;
        if ( $this->post_type != $_POST['post_type'] )
            return;
        // Verify our nonce is intact!
        if ( wp_verify_nonce( $_POST['s8_pp_nonce'], plugin_basename( S8_PP_FILE ) ) ) {
            if ( ! defined( 'S8_SAVING_POST' ) )
                define('S8_SAVING_POST', true); // Set so we don't infinite loop
            $args = array( 'ID' => $post_id, 'post_parent' => 0 );
            // Find our author ID and set any needed USER meta
            if ( 0 < absint( $_POST['private_user'] ) ) {
                $author = absint( $_POST['private_user'] );
            } elseif ( 0 < absint( $_POST['new_private_user'] ) ) {
                $author = absint( $_POST['new_private_user'] );
                update_user_meta( $author, $this->meta_user_pp, 'yes' );
            } else {
                $author = 0;
            }
            // Verify if this user already has a private page or not
            if ( 0 < $author && 'yes' == get_user_meta( $author, $this->meta_user_pp, true ) ) {
                // Check if they already have a page
                $pages = new WP_Query( array( 'post_type' => $this->post_type, 'author' => $author, 'posts_per_page' => 1, 'post_parent' => 0 ) );
                if ( $pages->have_posts() ) {
                    while ( $pages->have_posts() ) {
                        $pages->next_post();
                        $args['post_parent'] = $pages->post->ID;
                    }
                }
            }
            // Update the post meta and the post with our findings
            $args['post_author'] = $author;
            update_post_meta( $post_id, $this->meta_post_uid, $author );
            wp_update_post( $args ); // This is what the S8_SAVING_POST constant is defined for, this calls this function

            // Save our page template if we have one, otherwise set it to default
            if ( esc_attr( $_POST['_wp_page_template'] ) ) {
                update_post_meta( $post_id, '_wp_page_template', esc_attr( $_POST['_wp_page_template'] ) );
            } else {
                update_post_meta( $post_id, '_wp_page_template', 'default' );
            }
        }
    }

    /**
     * Make the necessary checks on trash to prevent parent posts (of our type) from leaving orphaned children.
     * @param $post_id
     * @since 0.8.0
     */
    function trash_posts($post_id) {
        $post = get_post($post_id);
        if($post) {
            if($post->post_status == 'trash') return;
            elseif($post->post_type != $this->post_type) return;
            elseif($post->post_parent != 0) return;
            else {
                $args = array(
                    'post_type' => $this->post_type,
                    'numberposts' => -1,
                    'post_parent' => $post_id,
                    'post_status' => array('publish', 'pending', 'draft', 'future', 'private', 'trash'),
                );
                $posts = get_posts($args);
                if($posts && count($posts) > 0) {
                    wp_redirect(admin_url('/edit.php?post_type='.$this->post_type.'&s8pp_err=1'));
                    exit;
                }
                else {
                    wp_update_post(array('ID' => $post_id, 'post_author' => 0)); // This doesn't seem to be working!
                    delete_user_meta($post->post_author, $this->meta_user_pp);
                    delete_post_meta($post_id, $this->meta_post_uid);
                }
            }
        }
    }


    /**
     * Changes the WP template used for showing the page to page.php (WP defaults it to single.php)
     * @param $template
     * @return string
     * @since 0.8.0
     */
    function template_include($template) {
        global $wp_query;
        $template_search = array( 's8-private-page.php', 'page.php', 'index.php' );
        if ( is_single() && get_post_type() == $this->post_type ) {
            // Check the DB for a page template
            $default = get_post_meta( get_the_ID(), '_wp_page_template', true );
            // If this is a subpage, add in our subpage specific template to the beginning of the search array
            if ( 0 < $wp_query->post->post_parent )
                $template_search = array_merge( array( 's8-private-subpage.php' ), $template_search );
            // If we found a template in the DB, add it to the beginning of our search array
            if ( '' != $default && 'default' != $default )
                $template_search = array_merge( (array) $default, $template_search );
            // Actually find our template
            $template = locate_template( $template_search );
        } elseif ( isset( $wp_query->query_vars[$this->endpoint] ) && current_user_can( 'edit_pages' ) ) {
            // This only applies to our archive page, we filter the content appropriately there
            $template = locate_template( $template_search );
        }
        return $template;
    }

    /**
     * Overrides the post results ONLY on the /OUR_ENDPOINT/ page which should only be visible to editors and above
     * @param $posts
     * @return mixed
     * @since 0.8.0
     */
    function pp_admin_page( $posts ) {
        global $wp_query;
        if ( isset( $wp_query->query_vars[$this->endpoint] ) && ! is_single() && $this->internal_query ) {
            $this->internal_query = false; // Reset this back to false so this only runs once
            if ( current_user_can('edit_pages') ) {
                $pages = get_pages( array( 'post_type' => $this->post_type, 'authors' => 0 ) );
                $exclude = array();
                if ( $pages ) {
                    foreach( $pages as $page ) {
                        $exclude[] = $page->ID;
                    }
                }
                $title = 'Private Pages';
                $content = wp_list_pages( array(
                    'post_type' => $this->post_type,
                    'echo' => 0,
                    'exclude' => $exclude,
                    'title_li' => '',
                ) );
            } else {
                $title = 'Oops!';
                $content = '<p>If you are seeing this then something unexpected happened! You may return to the homepage by clicking <a href="'.home_url().'">here</a>.</p>';
            }
            $post = array(
                'ID' => 0,
                'post_author' => 0,
                'post_date' => current_time('mysql'),
                'post_modified' => current_time('mysql'),
                'post_date_gmt' => current_time('mysql', 1),
                'post_title' => $title,
                'post_content' => $content,
                'post_status' => 'static',
                'comment_status' => 'closed',
                'ping_status' => 'closed',
                'post_name' => $this->endpoint . '/',
                'post_parent' => 0,
                'post_type' => 'page'
            );
            $posts = array( (object) $post );
        }
        return $posts;
    }

    /**
     * This function allows us better control over when the "the_posts" filter executes by making sure we are in the right place and have the right query.
     * @param $query
     * @since 0.8.2
     */
    function pre_get_posts( $query ) {
        global $wp_query;
        if ( ! is_admin() && $query->is_main_query() && ! is_single() && isset( $wp_query->query_vars[$this->endpoint] ) ) {
            $this->internal_query = true;
        }
    }

    /**
     * Redirects the user if they are not allowed on this private page
     * Does nothing if not a private page
     * @since 0.8.0
     */
    function user_redirect() {
        global $wp_query;
        // Return if we aren't on our endpoint or post type
        if ( ! isset( $wp_query->query_vars[$this->post_type] ) && ! isset( $wp_query->query_vars[$this->endpoint] ) )
            return;
        // Redirect the user if they aren't logged in
        if ( ! is_user_logged_in() ) {
            wp_redirect( wp_login_url( $this->get_current_url() ) );
            exit;
        } else {
            global $post;
            $valid = get_post_meta( $post->ID, $this->meta_post_uid, true );
            // Return if the user is editor or above or is the "owner" of this page
            if ( current_user_can( 'edit_pages' ) || ( $post->post_author == get_current_user_id() && $valid == get_current_user_id() ) ) {
                return;
            } else {
                // Check and see if a private page exists for the current user
                $args = array( 'post_type' => $this->post_type, 'posts_per_page' => 1, 'post_parent' => 0, 'author' => get_current_user_id() );
                $pages = new WP_Query( $args );
                if ( $pages->have_posts() ) {
                    while ( $pages->have_posts() ) {
                        $pages->next_post();
                        wp_redirect( get_permalink( $pages->post->ID ) );
                        exit;
                    }
                }
            }
            wp_redirect( home_url() );
            exit;
        }
    }

    /**
     * Redirects users of a certain level to their private page upon login
     * @param $user_login
     * @param $user
     */
    function login_redirect( $user_login, $user ) {
        $redirect = get_option( 's8_pp_redirect' );
        if ( $redirect && 'yes' == $redirect && ! user_can( $user, 'manage_options' ) ) {
            $args = array(
                'post_type' => $this->post_type,
                'author' => $user->ID,
                'post_parent' => 0,
                'posts_per_page' => 1,
            );
            $private_page = get_posts( $args );
            if ( $private_page ) {
                foreach ( $private_page as $page ) {
                    $url = get_permalink( $page->ID );
                    if ( $url ) {
                        wp_redirect( $url, 302 );
                        exit();
                    }
                }
            }
        }
    }

    /**
     * Our own system to display custom error messages in the admin
     * @since 0.8.0
     */
    function admin_notices() {
        $s8_err_var = 's8pp_err';
        if(isset($_GET[$s8_err_var])) {
            $errors = array(
                1 => 'You can\'t trash parent private pages until ALL child private pages have been deleted! This includes removing the child private pages from the trash!',
            );
            if(isset($errors[$_GET[$s8_err_var]]))
                echo '<div class="error"><p>'.$errors[$_GET[$s8_err_var]].'</p></div>';
            unset($_GET[$s8_err_var]);
        }
    }

    /**
     * Returns current URL with SSL and port detection
     * @return string
     * @since 0.8.0
     */
    function get_current_url() {
        $protocol = is_ssl()?'https':'http';
        $port = ($_SERVER['SERVER_PORT'] == '80' || $_SERVER['SERVER_PORT'] == '443')?'':':'.$_SERVER['SERVER_PORT'];
        return $protocol . "://" . $_SERVER['SERVER_NAME'] . $port . $_SERVER['REQUEST_URI'];
    }

    /**
     * Adds our endpoint(s)
     * @since 0.8.0
     */
    function endpoint() {
        add_rewrite_endpoint( $this->endpoint, EP_ROOT );
    }

    /**
     * Run our actions on activation
     * @since 0.8.0
     */
    function activation() {
        // Add our default EP if it doesn't exist
        add_option( $this->ep_option, $this->default_ep );
        add_option( $this->ep_option_check, $this->default_ep );
        $this->init();
        flush_rewrite_rules();
    }

    /**
     * Run our actions on deactivation
     * @since 0.8.0
     */
    function deactivation() {
        flush_rewrite_rules();
    }

    /**
     * Update our plugin settings, if applicable
     * @since 0.8.0
     */
    function update() {
        update_option('s8_private_pages_version', S8_PP_VERSION);
    }
}
new s8_private_pages(); // Boot our plugin!
