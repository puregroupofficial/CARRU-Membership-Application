<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    CAARU_Membership
 */
class CAARU_Membership_Admin {

    private $plugin_name;
    private $version;

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }
    
    public function enqueue_styles_scripts( $hook ) {
        $screen = get_current_screen();
        // Correctly target the 'Create New' page and the CPT edit screen
        if ( ( $screen && $screen->post_type === 'caaru_application' ) || $hook === 'applications_page_caaru-create-application' ) {
             wp_enqueue_style( 'jquery-ui-css', '//ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/base/jquery-ui.css' );
             wp_enqueue_style( $this->plugin_name . '_admin', CAARU_MEMBERSHIP_PLUGIN_URL . 'admin/css/caaru-membership-admin.css', [], $this->version, 'all' );
             
             wp_enqueue_script( $this->plugin_name . '_admin', CAARU_MEMBERSHIP_PLUGIN_URL . 'admin/js/caaru-membership-admin.js', ['jquery', 'jquery-ui-autocomplete'], $this->version, true );
             wp_localize_script( $this->plugin_name . '_admin', 'caaruAdminAjax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('caaru_member_search_nonce')
             ]);
        }
    }

    public function register_cpt_application() {
         $labels = [
            'name'                  => _x( 'Applications', 'Post Type General Name', 'caaru-membership' ),
            'singular_name'         => _x( 'Application', 'Post Type Singular Name', 'caaru-membership' ),
            'menu_name'             => __( 'Applications', 'caaru-membership' ),
            'all_items'             => __( 'All Applications', 'caaru-membership' ),
            'edit_item'             => __( 'Edit Application', 'caaru-membership' ),
            'view_item'             => __( 'View Application', 'caaru-membership' ),
            'search_items'          => __( 'Search Application', 'caaru-membership' ),
            'not_found'             => __( 'No applications found', 'caaru-membership' ),
            'not_found_in_trash'    => __( 'No applications found in Trash', 'caaru-membership' ),
        ];
        $args = [
            'label'                 => __( 'Application', 'caaru-membership' ),
            'labels'                => $labels,
            'supports'              => [ 'title', 'author', 'revisions' ],
            'hierarchical'          => false,
            'public'                => false,
            'show_ui'               => true,
            'show_in_menu'          => 'edit.php?post_type=caaru_application',
            'menu_icon'             => 'dashicons-media-document',
            'can_export'            => true,
            'has_archive'           => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => false,
            'capability_type'       => 'post',
            'capabilities'          => [ 'create_posts' => 'do_not_allow' ],
            'map_meta_cap'          => true,
        ];
        register_post_type( 'caaru_application', $args );
    }

    public function add_admin_menu() {
        add_menu_page( 'Applications', 'Applications', 'manage_options', 'edit.php?post_type=caaru_application', '', 'dashicons-media-document', 20 );
        add_submenu_page( 'edit.php?post_type=caaru_application', 'Create Application', 'Create New', 'manage_options', 'caaru-create-application', [ $this, 'display_create_application_page' ] );
        add_submenu_page( 'edit.php?post_type=caaru_application', 'Settings', 'Settings', 'manage_options', 'caaru-settings', [ $this, 'display_settings_page' ] );
    }
    
    public function register_settings() {
        register_setting( 'caaru_settings_group', 'caaru_redirect_url', 'esc_url_raw' );
        register_setting( 'caaru_settings_group', 'caaru_submit_button_text', 'sanitize_text_field' );
        add_settings_section( 'caaru_main_section', 'Application Form Settings', null, 'caaru-settings' );
        add_settings_field( 'caaru_redirect_url_field', 'Payment Page URL', [ $this, 'redirect_url_field_callback' ], 'caaru-settings', 'caaru_main_section' );
        add_settings_field( 'caaru_submit_button_text_field', 'Submit Button Text', [ $this, 'submit_button_text_field_callback' ], 'caaru-settings', 'caaru_main_section' );
    }

    public function redirect_url_field_callback() {
        printf(
            '<input type="url" name="caaru_redirect_url" value="%s" class="regular-text" placeholder="https://your-site.com/checkout"><p class="description">%s</p>',
            esc_attr( get_option('caaru_redirect_url') ),
            'Enter the full URL where users should be redirected after submitting their initial application.'
        );
    }
    
    public function submit_button_text_field_callback() {
        printf(
            '<input type="text" name="caaru_submit_button_text" value="%s" class="regular-text" placeholder="Submit Application">',
            esc_attr( get_option('caaru_submit_button_text', 'Submit Application') )
        );
    }

    public function display_create_application_page() { require_once 'partials/create-application-page.php'; }
    public function display_settings_page() { require_once 'partials/settings-page.php'; }

    public function add_application_meta_box() {
        add_meta_box( 'caaru_application_details', 'Application Details (Editable)', [ $this, 'render_application_meta_box' ], 'caaru_application', 'normal', 'high' );
    }

    public function render_application_meta_box( $post ) {
        wp_nonce_field( 'caaru_save_application_data', 'caaru_admin_nonce' );
        $data = get_post_meta( $post->ID, '_application_data', true );
        $data = is_array($data) ? $data : [];
        $sections = caaru_get_form_sections();
        
        echo '<div class="caaru-admin-form-container">';
        foreach ($sections as $section_key => $section) {
            echo '<fieldset class="caaru-admin-fieldset">';
            echo '<legend class="caaru-admin-legend">' . esc_html($section['title']) . '</legend>';
            
            if ($section_key === 'education') {
                foreach(['Graduation', 'Masters', 'Ph.D.', 'Others'] as $degree_name) {
                    $degree_key = strtolower(str_replace('.', '', $degree_name));
                    echo '<div class="caaru-admin-sub-section">';
                    echo '<h4>' . esc_html($degree_name) . '</h4>';
                    echo '<div class="caaru-admin-grid">';
                    foreach(['subject', 'session', 'faculty', 'hall', 'year', 'held_in'] as $field_key) {
                        $value = $data['education'][$degree_key][$field_key] ?? '';
                        echo '<div class="caaru-admin-field-wrapper">';
                        echo '<label>' . esc_html(ucfirst(str_replace('_', ' ', $field_key))) . '</label>';
                        echo '<input type="text" class="widefat" name="caaru_app_data[education]['.$degree_key.']['.$field_key.']" value="' . esc_attr($value) . '">';
                        echo '</div>';
                    }
                    echo '</div></div>';
                }
            } else {
                echo '<div class="caaru-admin-grid">';
                foreach ($section['fields'] as $field_key => $field) {
                    $value = $data[$section_key][$field_key] ?? '';
                    $name = "caaru_app_data[{$section_key}][{$field_key}]";
                    
                    echo '<div class="caaru-admin-field-wrapper ' . (!empty($field['full_width']) ? 'full-width' : '') . '">';
                    echo '<label>' . esc_html($field['label']) . (!empty($field['required']) ? ' <span class="required">*</span>' : '') . '</label>';

                    if ($field['type'] === 'radio' || $field['type'] === 'checkbox') {
                        echo '<div class="options-group">';
                        foreach($field['options'] as $opt_val => $opt_label) {
                            $checked = (is_array($value) && in_array($opt_val, $value)) || ($value == $opt_val) ? 'checked' : '';
                            $input_name = $name . ($field['type'] === 'checkbox' ? '[]' : '');
                            echo '<label class="option-label"><input type="'.$field['type'].'" name="'.$input_name.'" value="'.esc_attr($opt_val).'" '.$checked.'> '.esc_html($opt_label).'</label>';
                        }
                        echo '</div>';
                    } else {
                        $extra_class = ($field_key === 'referrer_name') ? 'caaru-member-search' : '';
                         echo '<input type="'.esc_attr($field['type']).'" class="widefat ' . $extra_class . '" name="'.$name.'" value="'.esc_attr($value).'">';
                    }
                    echo '</div>';
                }
                echo '</div>';
            }
            echo '</fieldset>';
        }
        echo '</div>';
    }

    public function save_application_data( $post_id ) {
        if ( ! isset( $_POST['caaru_admin_nonce'] ) || ! wp_verify_nonce( $_POST['caaru_admin_nonce'], 'caaru_save_application_data' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;
        if ( get_post_type($post_id) !== 'caaru_application' ) return;

        if ( isset( $_POST['caaru_app_data'] ) ) {
            $sanitized_data = caaru_sanitize_application_form_data( $_POST['caaru_app_data'] );
            update_post_meta( $post_id, '_application_data', $sanitized_data );
        }
    }

    public function add_custom_columns( $columns ) {
        unset($columns['date']);
        $new_columns = [];
        foreach ($columns as $key => $title) {
            $new_columns[$key] = $title;
            if ($key === 'author') { $new_columns['application_status'] = 'Status'; $new_columns['date'] = 'Date'; }
        }
        return $new_columns;
    }

    public function render_custom_columns( $column, $post_id ) {
        if ($column === 'application_status') {
            $post_status = get_post_status($post_id);
            $meta_status = get_post_meta($post_id, '_application_status', true);
            $display_status = 'Unknown'; $class = 'status-default';
            if ($post_status === 'publish') { $display_status = 'Published'; $class = 'status-published'; } 
            elseif ($post_status === 'pending') { $display_status = 'Submitted'; $class = 'status-pending'; } 
            elseif ($meta_status === 'new' || $post_status === 'draft') { $display_status = 'New'; $class = 'status-new'; }
            echo '<span class="caaru-status-badge ' . $class . '">' . esc_html($display_status) . '</span>';
        }
    }

    public function ajax_search_members() {
        check_ajax_referer('caaru_member_search_nonce', 'nonce');

        $term = isset($_GET['term']) ? sanitize_text_field(wp_unslash($_GET['term'])) : '';
        if (empty($term)) {
            wp_send_json([]);
        }

        global $wpdb;
        $search_term = '%' . $wpdb->esc_like($term) . '%';
        
        $query = $wpdb->prepare(
            "SELECT u.ID, u.display_name, um.meta_value as membership_id
            FROM {$wpdb->users} u
            LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = 'membership_id'
            WHERE (u.user_login LIKE %s OR u.display_name LIKE %s)
            LIMIT 10",
            $search_term, $search_term
        );
        $users = $wpdb->get_results($query);

        $results = [];
        if($users){
            foreach ($users as $user) {
                $results[] = [
                    'label' => $user->display_name . ' (' . ($user->membership_id ?: 'No ID') . ')',
                    'value' => $user->display_name,
                    'membership_id' => $user->membership_id ?: '',
                ];
            }
        }
        wp_send_json($results);
    }
}

