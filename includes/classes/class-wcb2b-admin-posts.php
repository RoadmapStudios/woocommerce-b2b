<?php

/**
 * WooCommerce B2B Posts
 *
 * @version 4.0.5
 */

defined( 'ABSPATH' ) || exit;

/**
 * WCB2B_Admin_Posts
 */
class WCB2B_Admin_Posts {

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Init current class hooks
     */
    public function init_hooks() {
        add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ), 10, 2 );
        add_action( 'save_post', array( $this, 'save_meta_box' ) );
        add_action( 'save_post_page', array( $this, 'has_group_shortcode' ), 10, 2 );
        add_filter( 'display_post_states', array( $this, 'post_state' ), 10, 2 );
        add_filter( 'manage_page_posts_columns', array( $this, 'manage_columns' ) );
        add_filter( 'manage_page_posts_custom_column', array( $this, 'manage_columns_values' ), 10, 2 );
        add_filter( 'default_hidden_columns', array( $this, 'hidden_columns' ), 10, 2 );
        add_action( 'bulk_edit_custom_box', array( $this, 'bulk_edit_posts' ), 10, 2 );
    }

    /**
     * Add page/post visibility by group
     *
     * @param string $post_type Post type
     * @param obejct $post Post object
     */
    public function add_meta_box( $post_type, $post ) {
        add_meta_box( 'wcb2b_group-visibility-meta_box', __( 'Group visibility', 'woocommerce-b2b' ), function( $post ) {
            // Make sure the form request comes from WordPress
            wp_nonce_field( basename( __FILE__ ), 'wcb2b_group-visibility-nonce' );

            // Retrieve post visibility current value and groups
            $groups = wcb2b_get_groups();
            $visibility = false;
            if ( metadata_exists( 'post', $post->ID, 'wcb2b_group_visibility' ) ) {
                $visibility = get_post_meta( $post->ID, 'wcb2b_group_visibility', true );
            }
            include_once WCB2B_ABSPATH . 'includes/views/html-admin-posts.php';
        }, array( 'page' ), 'side', 'low' );
    }

    /**
     * Save page/post visibility by group
     *
     * @param integer $post_id Current post ID
     */
    public function save_meta_box( $post_id ) {
        if ( in_array( get_post_type( $post_id ), array( 'page' ) ) && ! in_array( $post_id, wcb2b_get_always_visible_pages() ) ) {

            // Check bulk edit nonce
            if ( isset( $_REQUEST['bulk_edit'] ) ) {
                if ( in_array( get_post_type( $post_id ), array( 'page' ) ) && ! in_array( $post_id, wcb2b_get_always_visible_pages() ) ) {
                    // Store group tax exemption flag
                    $visibility = $_REQUEST['wcb2b_group_visibility-bulk'] ?? -1;
                    if ( '-1' !== $visibility ) {
                        update_post_meta( $post_id, 'wcb2b_group_visibility', array_map( 'intval', array_filter( $visibility ) ) );
                    }
                }
            }

            // Verify meta box nonce
            if ( ! isset( $_POST['wcb2b_group-visibility-nonce'] ) || ! wp_verify_nonce( $_POST['wcb2b_group-visibility-nonce'], basename( __FILE__ ) ) ) { return; }

            $visibility = false;
            if ( isset( $_POST['wcb2b_group_visibility'] ) ) {
                // Sanitize values
                $visibility = array_map( 'intval', array_filter( $_POST['wcb2b_group_visibility'] ) );
            }
            update_post_meta( $post_id, 'wcb2b_group_visibility', $visibility );
        }
    }

    /**
     * Check if is a shortcode wcb2bloginform
     * 
     * @param integer $post_id Current page ID
     * @param WP_Post $post Current page object
     */
    public function has_group_shortcode( $post_id, $post ) {
        if ( has_shortcode( $post->post_content, 'wcb2bloginform' ) ) {
            if ( false !== preg_match( '/wcb2b_group="([0-9]+?)"/', $post->post_content, $matches ) ) {
                $group_id = $matches[1] ?? 0;
                if ( get_post_type( $group_id ) == 'wcb2b_group' ) {
                    return update_post_meta( $post_id, 'wcb2bloginform', $group_id );
                }
            }
        }
        delete_post_meta( $post_id, 'wcb2bloginform' );
    }

    /**
     * Add post state to the quick order page
     *
     * @param array $post_states List of states
     * @param object $post Current post object
     * @return array
     */
    public function post_state( $post_states, $post ) {
        if ( $post->ID == get_option( 'wcb2b_quick_order_page' ) ) {
            $post_states[] = __( 'Quick Order Page', 'woocommerce-b2b' );
        }
        return $post_states;
    }

    /**
     * Add custom columns
     *
     * @param array $columns Columns
     * @return array
     */
    public function manage_columns( $columns ) {
        return  array_slice( $columns, 0, -1, true ) + 
                array( 'wcb2b_visibility' => __( 'Visibility', 'woocommerce-b2b' ) ) +
                array_slice( $columns, -1, null, true );
    }

    /**
     * Custom columns values
     *
     * @param string $column Current column
     * @param integer $post_id Current post ID
     */
    public function manage_columns_values( $column, $post_id ) {
        if ( $column === 'wcb2b_visibility' ) {
            if ( in_array( $post_id, wcb2b_get_always_visible_pages() ) ) {
                esc_html_e( 'Always visible page', 'woocommerce-b2b' );
            } else {
                if ( $visibility = get_post_meta( $post_id, 'wcb2b_group_visibility', true ) ) {
                    $groups = array_map( function( $id ) {
                        return get_the_title( $id );
                    }, $visibility );
                    asort( $groups );
                    printf( '%s', implode( ', ', $groups ) );
                }
            }
        }
    }

    /**
     * Hidden columns
     *
     * @param array $hidden Hidden columns list
     * @param string $screen Current screen
     * @return array
     */
    public function hidden_columns( $hidden, $screen ) {
        if ( 'page' == $screen->post_type ) {
            $hidden[] = 'wcb2b_visibility';
        }
        return $hidden;
    }

    /**
     * Bulk manage post data
     *
     * @param string $column_name Current column name
     * @param string $post_type Current post type
     */
    public function bulk_edit_posts( $column_name, $post_type ) {
        if ( 'page' !== $post_type ) { return false; }
        switch( $column_name ) {
            case 'wcb2b_visibility' :
                $groups = array_reduce( wcb2b_get_groups()->posts, function( $result, $item ) {
                    $item = (array)$item;
                    $result[$item['ID']] = $item['post_title'];
                    return $result;
                } );
                $options_html = '';
                foreach ( $groups as $key => $value ) {
                    $options_html .= sprintf( '<li><label><input value="%s" type="checkbox" name="wcb2b_group_visibility-bulk[]"> %s</label></li>',
                        $key,
                        $value
                    );
                }
                printf( '<fieldset class="inline-edit-col-left"><div class="inline-edit-col"><label>%s%s</label></div></fieldset>',
                        sprintf( '<span class="title">%s</span>',
                            esc_html__( 'Visibility', 'woocommerce-b2b' )
                        ),
                        sprintf( '<ul class="cat-checklist category-checklist">%s</ul>',
                            $options_html
                        )
                    );

                break;
        }
    }

}

return new WCB2B_Admin_Posts();