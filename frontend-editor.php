<?php

/* start-wp-plugin-header */
/*
 Plugin Name: Bulk Edit Posts on Frontend
 Description: Edit Posts and Pages on the frontend.
 Version:     1.1.0
 Author:      VegaCorp
 Author URI:  http://vegacorp.me
 Plugin URI: https://wpsheeteditor.com
 License:     GPL2
 License URI: https://www.gnu.org/licenses/gpl-2.0.html
 @fs_premium_only /plugins/, /inc/freemius-init-addon.php, /inc/freemius-mockup.php
*/
/* end-wp-plugin-header */
if ( !defined( 'VGSE_FRONTEND_EDITOR_DIR' ) ) {
    define( 'VGSE_FRONTEND_EDITOR_DIR', __DIR__ );
}
if ( !defined( 'VGSE_EDITORS_POST_TYPE' ) ) {
    define( 'VGSE_EDITORS_POST_TYPE', 'vgse_editors' );
}
require 'vendor/freemius/start.php';
require 'vendor/TGM-Plugin-Activation-2.5.2/class-tgm-plugin-activation.php';
require 'vendor/vg-plugin-sdk/index.php';
require 'inc/freemius-init.php';
require 'inc/tgm-init.php';
if ( !class_exists( 'WP_Sheet_Editor_Frontend_Editor' ) ) {
    /**
     * Filter rows in the spreadsheet editor.
     */
    class WP_Sheet_Editor_Frontend_Editor
    {
        private static  $instance = false ;
        public  $plugin_url = null ;
        public  $plugin_dir = null ;
        public  $current_editor_columns = null ;
        public  $shortcode_key = 'vg_sheet_editor' ;
        public  $textname = 'vg_sheet_editor_frontend' ;
        public  $buy_link = null ;
        public  $version = '1.1.0' ;
        public  $settings = null ;
        public  $args = null ;
        public  $vg_plugin_sdk = null ;
        private function __construct()
        {
        }
        
        public function init_plugin_sdk()
        {
            $this->vg_plugin_sdk = new VG_Freemium_Plugin_SDK( $this->args );
        }
        
        public function auto_setup()
        {
            $flag_key = 'vg_sheet_editor_frontend_auto_setup';
            $already_setup = get_option( $flag_key, 'no' );
            if ( $already_setup === 'yes' ) {
                return;
            }
            update_option( $flag_key, 'yes' );
            $default_post_type = 'post';
            wp_insert_post( array(
                'post_type'   => VGSE_EDITORS_POST_TYPE,
                'post_title'  => __( 'Edit posts', $this->textname ),
                'post_status' => 'publish',
                'meta_input'  => array(
                'vgse_post_type' => $default_post_type,
            ),
            ) );
        }
        
        public function _get_first_post()
        {
            $editors = new WP_Query( array(
                'post_type'      => VGSE_EDITORS_POST_TYPE,
                'posts_per_page' => 1,
                'fields'         => 'ids',
            ) );
            return ( $editors->have_posts() ? current( $editors->posts ) : false );
        }
        
        public function init()
        {
            $this->plugin_url = plugins_url( '/', __FILE__ );
            $this->plugin_dir = __DIR__;
            $this->buy_link = vgsefe_freemius()->pricing_url();
            $this->args = array(
                'main_plugin_file'         => __FILE__,
                'show_welcome_page'        => true,
                'welcome_page_file'        => $this->plugin_dir . '/views/welcome-page-content.php',
                'upgrade_message_file'     => $this->plugin_dir . '/views/upgrade-message.php',
                'logo'                     => plugins_url( '/assets/imgs/logo-248x102.png', __FILE__ ),
                'buy_link'                 => $this->buy_link,
                'plugin_name'              => 'Frontend Editor',
                'plugin_prefix'            => 'vgsefe_',
                'show_whatsnew_page'       => true,
                'whatsnew_pages_directory' => $this->plugin_dir . '/views/whats-new/',
                'plugin_version'           => $this->version,
                'plugin_options'           => $this->settings,
                'allowed_post_types'       => array(
                'post' => __( 'Posts', $this->textname ),
                'page' => __( 'Pages', $this->textname ),
            ),
            );
            $this->init_plugin_sdk();
            $this->register_post_type();
            // Allow core editor on frontend
            add_filter( 'vg_sheet_editor/allowed_on_frontend', '__return_true' );
            // After core has initialized
            add_filter( 'vg_sheet_editor/initialized', array( $this, 'after_core_init' ) );
        }
        
        public function after_core_init()
        {
            // Override core buy link with this plugin´s
            VGSE()->buy_link = $this->buy_link;
            // Register shortcode
            add_shortcode( $this->shortcode_key, array( $this, 'get_frontend_editor_html' ) );
            // Register metaboxes
            add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
            add_action( 'save_post', array( $this, 'save_meta_box' ) );
            // Enqueue metabox css and js
            add_action(
                'admin_enqueue_scripts',
                array( $this, 'enqueue_metabox_assets' ),
                10,
                1
            );
            // Disable core plugin welcome page
            add_action( 'admin_init', array( $this, 'disable_core_plugin_welcome_page' ), 20 );
            load_plugin_textdomain( $this->textname, false, basename( dirname( __FILE__ ) ) . '/lang/' );
        }
        
        public function disable_core_plugin_welcome_page()
        {
            
            if ( isset( $_GET['page'] ) && $_GET['page'] === 'vg_sheet_editor_setup' ) {
                $core_flag_key = 'vgse_welcome_redirect';
                $core_flag = get_option( $core_flag_key, '' );
                $flag_key = 'vgsefe_core_welcome_redirect_disabled';
                $flag = get_option( $flag_key, '' );
                // If this plugin´s welcome page has displayed and we haven´t redirected to the home once, redirect
                
                if ( $core_flag === 'no' && $flag !== 'yes' ) {
                    update_option( $flag_key, 'yes' );
                    wp_redirect( admin_url( 'index.php' ) );
                    die;
                }
            
            }
        
        }
        
        /**
         * Enqueue metabox assets
         * @global obj $post
         * @param str $hook
         */
        public function enqueue_metabox_assets( $hook )
        {
            global  $post ;
            if ( $hook == 'post-new.php' || $hook == 'post.php' ) {
                
                if ( VGSE_EDITORS_POST_TYPE === $post->post_type ) {
                    VGSE()->_register_styles();
                    VGSE()->_register_scripts( 'post' );
                }
            
            }
        }
        
        /**
         * Register meta box(es).
         */
        public function register_meta_boxes()
        {
            add_meta_box(
                'vgse-columns-visibility-metabox',
                __( 'General settings', $this->textname ),
                array( $this, 'render_settings_metabox' ),
                VGSE_EDITORS_POST_TYPE
            );
        }
        
        /**
         * Meta box display callback.
         *
         * @param WP_Post $post Current post object.
         */
        public function render_settings_metabox( $post )
        {
            echo  '<div id="vgse-wrapper">' ;
            wp_nonce_field( 'bep-nonce', 'bep-nonce' );
            echo  __( '<h3>Shortcode</h3>', $this->textname ) ;
            echo  __( '<p>Add this shortcode to a post or page to display the spreadsheet editor. It looks better on a full width page.</p>', $this->textname ) ;
            echo  '<code>[vg_sheet_editor editor_id="' . $post->ID . '"]</code><br/><hr/><br/>' ;
            echo  __( '<h3>Settings</h3>', $this->textname ) ;
            $allowed_post_types = $this->args['allowed_post_types'];
            $post_type = get_post_meta( $post->ID, 'vgse_post_type', true );
            if ( empty($post_type) || !is_string( $post_type ) || !isset( $allowed_post_types[$post_type] ) ) {
                $post_type = '';
            }
            $sanitized_post_type = sanitize_text_field( $post_type );
            // Post type field
            echo  '<label>Post type</label><br/>' ;
            $all_post_types = VGSE()->helpers->get_all_post_types();
            if ( !empty($all_post_types) ) {
                foreach ( $all_post_types as $post_type_obj ) {
                    $post_type_key = $post_type_obj->name;
                    $post_type_label = $post_type_obj->labels->name;
                    $is_disabled = '';
                    $label_suffix = '';
                    
                    if ( !isset( $allowed_post_types[$post_type_key] ) ) {
                        $is_disabled = 'disabled';
                        $label_suffix = sprintf( __( ' - <a href="%s" target="_blank">Premium</a>', $this->textname ), $this->buy_link );
                    }
                    
                    ?>
					<label><input type="radio" <?php 
                    echo  $is_disabled ;
                    ?>
 value="<?php 
                    echo  esc_attr( $post_type_key ) ;
                    ?>
"  name="vgse_post_type" <?php 
                    checked( $post_type_key, $sanitized_post_type );
                    ?>
 /> <?php 
                    echo  $post_type_label . $label_suffix ;
                    ?>
</label><br/>
					<?php 
                }
            }
            
            if ( !$post_type ) {
                _e( '<p>Please select the post type and save changes. After you save changes you will be able to see the rest of the settings and instructions.</p>', $this->textname );
                return;
            }
            
            $is_disabled = '';
            $label_suffix = '';
            $is_disabled = 'disabled';
            $label_suffix = sprintf( __( ' - <a href="%s" target="_blank">Premium</a>' ), $this->buy_link );
            // Toolbar items section
            _e( '<h3>Display toolbar items: </h3>', $this->textname );
            _e( '<p>Select the toolbar items that you want to display.</p>', $this->textname );
            $all_toolbars = VGSE()->toolbar->get_items();
            if ( empty($all_toolbars) || !is_array( $all_toolbars ) ) {
                $all_toolbars = array();
            }
            
            if ( isset( $all_toolbars[$post_type] ) ) {
                $post_type_toolbars = $all_toolbars[$post_type];
            } else {
                $post_type_toolbars = array();
            }
            
            $current_toolbars = array();
            if ( empty($current_toolbars) || !is_array( $current_toolbars ) ) {
                $current_toolbars = array();
            }
            foreach ( $post_type_toolbars as $toolbar_key => $toolbar_items ) {
                if ( empty($toolbar_items) || !is_string( $toolbar_key ) || !is_array( $toolbar_items ) ) {
                    continue;
                }
                echo  '<h4>' . esc_html( $toolbar_key ) . '</h4>' ;
                $filtered_toolbar_items = wp_list_filter( $toolbar_items, array(
                    'allow_to_hide'     => true,
                    'allow_in_frontend' => true,
                ) );
                $filtered_toolbar_items_keys = wp_list_pluck( $filtered_toolbar_items, 'key' );
                if ( $toolbar_key === 'primary' && in_array( 'add_rows', $filtered_toolbar_items_keys ) ) {
                    $current_toolbars[$toolbar_key] = array( 'add_rows' );
                }
                // avoid php warnings
                if ( !isset( $current_toolbars[$toolbar_key] ) ) {
                    $current_toolbars[$toolbar_key] = array();
                }
                foreach ( $filtered_toolbar_items as $toolbar_item ) {
                    if ( empty($toolbar_item) || !is_array( $toolbar_item ) || !isset( $toolbar_item['key'] ) || empty($toolbar_item['label']) ) {
                        continue;
                    }
                    ?>
 
					<label><input type="checkbox" <?php 
                    echo  $is_disabled ;
                    ?>
 value="<?php 
                    echo  esc_attr( $toolbar_item['key'] ) ;
                    ?>
"  name="vgse_toolbar_item[<?php 
                    echo  esc_attr( $toolbar_key ) ;
                    ?>
][]" <?php 
                    checked( in_array( $toolbar_item['key'], $current_toolbars[$toolbar_key] ) );
                    ?>
 /> <?php 
                    echo  esc_html( strip_tags( $toolbar_item['label'] ) ) . $label_suffix ;
                    ?>
</label><br/>
					<?php 
                }
            }
            ?>
				<h3><?php 
            _e( 'Columns visibility', $this->textname );
            ?>
</h3>
				<p><?php 
            _e( 'Select the columns that you want to display and change the columns order.', $this->textname );
            ?>
 <?php 
            echo  $label_suffix ;
            ?>
</p>
			<?php 
            echo  '<div class="clear"></div>' ;
            echo  __( '<h3>User authentication</h3>', $this->textname ) ;
            echo  __( '<p>The editor is available only for logged in users. Unknown users will see a message asking them to log in, and a link to the login page.</p>', $this->textname ) ;
            echo  sprintf(
                __( '<p>You can use the free plugin <a href="%s" target="_blank">"Custom Login Page Customizer"</a> to change the login page design, the free plugin <a href="%s" target="_blank">"Custom Login URL"</a> to use a custom login / registration URL, and the free plugin <a href="%s" target="_blank">"Hide Admin Bar from Frontend"</a> to hide the wp admin bar on the frontend.</p>', $this->textname ),
                'https://wordpress.org/plugins/login-customizer/',
                'https://wordpress.org/plugins/custom-login-url/',
                'https://wordpress.org/plugins/hide-admin-bar-from-front-end/'
            ) ;
            echo  __( '<h3>User roles</h3>', $this->textname ) ;
            echo  '<ul>' ;
            echo  __( '<li>Subscriber role is not allowed to use the editor.</li><li>Contributor role can view and edit his own posts only, but he can´t upload images.</li><li>Author role can view and edit his own posts only, he can upload images.</li><li>Editor role can view and edit all posts and pages.</li>', $this->textname ) ;
            echo  __( '<li>Administrator role can view and edit everything.</li>', $this->textname ) ;
            echo  '</ul>' ;
            ?>


			<div class="clear"></div>
			<style>
				.modal-columns-visibility .vg-refresh-needed,
				.modal-columns-visibility .vgse-sorter .fa-refresh,
				.modal-columns-visibility .vgse-save-settings,
				.modal-columns-visibility .vgse-allow-save-settings,
				.modal-columns-visibility .remodal-confirm,
				.modal-columns-visibility .remodal-cancel
				{
					display: none !important;
				}


			</style>
			<?php 
            echo  '</div>' ;
        }
        
        /**
         * Save meta box content.
         *
         * @param int $post_id Post ID
         */
        public function save_meta_box( $post_id )
        {
            $data = VGSE()->helpers->clean_data( $_POST );
            if ( !isset( $data['bep-nonce'] ) || !wp_verify_nonce( $data['bep-nonce'], 'bep-nonce' ) ) {
                return $post_id;
            }
            // Verify if this is an auto save routine. If it is our form has not been submitted, so we dont want
            // to do anything
            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
                return $post_id;
            }
            $post = get_post( $post_id );
            if ( $post->post_type !== VGSE_EDITORS_POST_TYPE ) {
                return $post_id;
            }
            $allowed_post_types = $this->args['allowed_post_types'];
            if ( empty($data['vgse_post_type']) || !is_string( $data['vgse_post_type'] ) || !isset( $allowed_post_types[$data['vgse_post_type']] ) ) {
                return;
            }
            update_post_meta( $post_id, 'vgse_post_type', sanitize_text_field( $data['vgse_post_type'] ) );
        }
        
        // Register Custom Post Type
        public function register_post_type()
        {
            $labels = array(
                'name'                  => _x( 'Sheet Editors', 'Post Type General Name', $this->textname ),
                'singular_name'         => _x( 'Sheet Editor', 'Post Type Singular Name', $this->textname ),
                'menu_name'             => __( 'Frontend Editors', $this->textname ),
                'name_admin_bar'        => __( 'Post Type', $this->textname ),
                'archives'              => __( 'Item Archives', $this->textname ),
                'attributes'            => __( 'Item Attributes', $this->textname ),
                'parent_item_colon'     => __( 'Parent Item:', $this->textname ),
                'all_items'             => __( 'All Items', $this->textname ),
                'add_new_item'          => __( 'Add New Item', $this->textname ),
                'add_new'               => __( 'Add New', $this->textname ),
                'new_item'              => __( 'New Item', $this->textname ),
                'edit_item'             => __( 'Edit Item', $this->textname ),
                'update_item'           => __( 'Update Item', $this->textname ),
                'view_item'             => __( 'View Item', $this->textname ),
                'view_items'            => __( 'View Items', $this->textname ),
                'search_items'          => __( 'Search Item', $this->textname ),
                'not_found'             => __( 'Not found', $this->textname ),
                'not_found_in_trash'    => __( 'Not found in Trash', $this->textname ),
                'featured_image'        => __( 'Featured Image', $this->textname ),
                'set_featured_image'    => __( 'Set featured image', $this->textname ),
                'remove_featured_image' => __( 'Remove featured image', $this->textname ),
                'use_featured_image'    => __( 'Use as featured image', $this->textname ),
                'insert_into_item'      => __( 'Insert into item', $this->textname ),
                'uploaded_to_this_item' => __( 'Uploaded to this item', $this->textname ),
                'items_list'            => __( 'Items list', $this->textname ),
                'items_list_navigation' => __( 'Items list navigation', $this->textname ),
                'filter_items_list'     => __( 'Filter items list', $this->textname ),
            );
            $args = array(
                'label'               => __( 'Frontend Editors', $this->textname ),
                'labels'              => $labels,
                'supports'            => array( 'title' ),
                'hierarchical'        => false,
                'public'              => true,
                'menu_icon'           => plugins_url( '/assets/imgs/icon-20x20.png', __FILE__ ),
                'show_ui'             => true,
                'show_in_menu'        => true,
                'menu_position'       => 99,
                'show_in_admin_bar'   => false,
                'show_in_nav_menus'   => false,
                'can_export'          => true,
                'has_archive'         => false,
                'exclude_from_search' => true,
                'publicly_queryable'  => false,
                'rewrite'             => false,
                'capability_type'     => 'page',
            );
            register_post_type( VGSE_EDITORS_POST_TYPE, $args );
        }
        
        /**
         * Get frontend editor html
         * @param array $atts
         * @param str $content
         * @return str
         */
        public function get_frontend_editor_html( $atts = array(), $content = '' )
        {
            $a = shortcode_atts( array(
                'editor_id' => '',
            ), $atts );
            if ( empty($a['editor_id']) || !function_exists( 'VGSE' ) ) {
                return;
            }
            if ( !is_user_logged_in() ) {
                return sprintf( __( '<p>Please <a href="%s">log in</a> to be able to use the frontend editor.</p>', $this->textname ), wp_login_url( get_permalink() ) );
            }
            $editor_id = (int) $a['editor_id'];
            $post_type = get_post_meta( $editor_id, 'vgse_post_type', true );
            $allowed_post_types = $this->args['allowed_post_types'];
            if ( empty($post_type) || !is_string( $post_type ) || !isset( $allowed_post_types[$post_type] ) ) {
                return;
            }
            $columns = 'all';
            $toolbars = array(
                'primary' => array( 'add_rows' ),
            );
            // Cache editor settings for later
            $this->current_editor_settings = array(
                'toolbars'  => $toolbars,
                'columns'   => $columns,
                'post_type' => $post_type,
                'editor_id' => $editor_id,
            );
            // Hide editor logo on frontend
            add_filter( 'vg_sheet_editor/editor_page/allow_display_logo', '__return_false' );
            // Filter toolbar items based on shortcode settings
            add_filter( 'vg_sheet_editor/toolbar/get_items', array( $this, 'filter_toolbar_items' ) );
            // Enqueue css and js on frontend
            VGSE()->_register_styles();
            wp_enqueue_media();
            VGSE()->_register_scripts( $post_type );
            // Get editor page
            $current_post_type = $post_type;
            ob_start();
            require VGSE_DIR . '/views/editor-page.php';
            $content = ob_get_clean();
            return $content;
        }
        
        /**
         * Filter toolbar items based on shortcode settings
         * @param array $items
         * @return array
         */
        public function filter_toolbar_items( $items )
        {
            if ( is_string( $this->current_editor_settings['toolbars'] ) && $this->current_editor_settings['toolbars'] === 'all' ) {
                return $items;
            }
            foreach ( $items[$this->current_editor_settings['post_type']] as $toolbar => $toolbar_items ) {
                if ( isset( $this->current_editor_settings['toolbars'][$toolbar] ) && is_string( $this->current_editor_settings['toolbars'][$toolbar] ) && $this->current_editor_settings['toolbars'][$toolbar] === 'all' ) {
                    continue;
                }
                if ( !isset( $this->current_editor_settings['toolbars'][$toolbar] ) ) {
                    $this->current_editor_settings['toolbars'][$toolbar] = array();
                }
                foreach ( $toolbar_items as $index => $item ) {
                    
                    if ( !in_array( $item['key'], $this->current_editor_settings['toolbars'][$toolbar] ) && $item['allow_to_hide'] ) {
                        unset( $items[$this->current_editor_settings['post_type']][$toolbar][$index] );
                    } else {
                    }
                
                }
            }
            return $items;
        }
        
        /**
         * Creates or returns an instance of this class.
         */
        public static function get_instance()
        {
            
            if ( null == WP_Sheet_Editor_Frontend_Editor::$instance ) {
                WP_Sheet_Editor_Frontend_Editor::$instance = new WP_Sheet_Editor_Frontend_Editor();
                WP_Sheet_Editor_Frontend_Editor::$instance->init();
            }
            
            return WP_Sheet_Editor_Frontend_Editor::$instance;
        }
        
        public function __set( $name, $value )
        {
            $this->{$name} = $value;
        }
        
        public function __get( $name )
        {
            return $this->{$name};
        }
    
    }
}
add_action( 'after_setup_theme', 'vgse_frontend_editor', 99 );
if ( !function_exists( 'vgse_frontend_editor' ) ) {
    function vgse_frontend_editor()
    {
        return WP_Sheet_Editor_Frontend_Editor::get_instance();
    }

}