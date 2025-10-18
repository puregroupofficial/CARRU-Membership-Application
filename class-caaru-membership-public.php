<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @package    CAARU_Membership
 */
class CAARU_Membership_Public {

    private $plugin_name;
    private $version;

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function enqueue_styles_scripts() {
        global $post;
        
        if( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'caaru_application_form' ) ) {
            wp_enqueue_style( 'jquery-ui-css', '//ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/base/jquery-ui.css' );
            wp_enqueue_style( $this->plugin_name, CAARU_MEMBERSHIP_PLUGIN_URL . 'public/css/caaru-membership-public.css', [], $this->version, 'all' );
            wp_enqueue_script( $this->plugin_name, CAARU_MEMBERSHIP_PLUGIN_URL . 'public/js/caaru-membership-public.js', ['jquery', 'jquery-ui-autocomplete'], $this->version, true );
            
            wp_localize_script( $this->plugin_name, 'caaru_ajax_object', [
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'caaru_application_nonce' ),
                'search_nonce' => wp_create_nonce( 'caaru_member_search_nonce' ),
                'redirect_url' => get_option('caaru_redirect_url', ''),
                'text'     => [
                    'saving' => 'Saving...', 'save_changes' => 'Save Changes',
                    'submitting' => 'Submitting...',
                    'submitBtnText' => get_option('caaru_submit_button_text', 'Submit Application'),
                ]
            ]);
        }
    }

    public function render_application_form() {
        if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
            return '<div style="padding: 20px; border: 2px dashed #ccc; background-color: #f9f9f9; text-align: center;"><strong>CAARU Application Form Shortcode</strong><br>The form will be displayed here on the live page.</div>';
        }

        if (!is_user_logged_in()) {
            echo '<div class="caaru-alert caaru-alert-info">Please log in or register to access the application form.</div>';
            return;
        }

        ob_start();
        $application = $this->get_user_application( get_current_user_id() );

        if ( ! is_a( $application, 'WP_Post' ) ) {
            echo '<div class="caaru-alert caaru-alert-error">Could not find or create an application for your account. Please contact an administrator.</div>';
            return ob_get_clean();
        }
        
        $data = get_post_meta( $application->ID, '_application_data', true );
        $data = is_array($data) ? $data : [];

        // Pre-fill data for brand new applications
        $app_status = get_post_meta($application->ID, '_application_status', true);
        if ($app_status === 'new') {
            $current_user = wp_get_current_user();
            if ($current_user) {
                $data['personal_info']['first_name'] = $data['personal_info']['first_name'] ?? $current_user->user_firstname;
                $data['personal_info']['last_name'] = $data['personal_info']['last_name'] ?? $current_user->user_lastname;
                $data['personal_info']['email'] = $data['personal_info']['email'] ?? $current_user->user_email;
            }
        }

        require 'partials/application-form-display.php';
        
        return ob_get_clean();
    }

    private function get_user_application( $user_id ) {
        if(!$user_id) return null;

        $posts = get_posts(['post_type' => 'caaru_application', 'author' => $user_id, 'post_status' => 'any', 'numberposts' => 1, 'orderby' => 'ID', 'order' => 'DESC']);
        if ( ! empty( $posts ) ) return $posts[0];

        $user = get_userdata($user_id);
        if(!$user) return null;

        $post_id = wp_insert_post([
            'post_title'  => 'Application for ' . $user->display_name,
            'post_author' => $user_id,
            'post_type'   => 'caaru_application',
            'post_status' => 'draft',
        ]);

        if ( is_wp_error($post_id) || $post_id === 0 ) return null;
        
        update_post_meta($post_id, '_application_status', 'new');
        return get_post( $post_id );
    }

    public function ajax_save_application_section() {
        check_ajax_referer( 'caaru_application_nonce', 'nonce' );
        if ( ! is_user_logged_in() ) wp_send_json_error( [ 'message' => 'Not logged in.' ] );

        $application = $this->get_user_application( get_current_user_id() );
        parse_str(wp_unslash($_POST['formData']), $form_data);
        $section_key = isset($_POST['section']) ? sanitize_key($_POST['section']) : '';
        
        if ( empty( $section_key ) || empty( $form_data ) ) wp_send_json_error( [ 'message' => 'Missing data.' ] );

        $all_data = get_post_meta( $application->ID, '_application_data', true );
        $all_data = is_array($all_data) ? $all_data : [];
        $sanitized_section_data = caaru_sanitize_application_form_data($form_data);
        
        $all_data[$section_key] = $sanitized_section_data[$section_key] ?? [];
        
        update_post_meta( $application->ID, '_application_data', $all_data );
        wp_send_json_success( [ 'message' => 'Section saved!' ] );
    }

    public function ajax_submit_full_application() {
        check_ajax_referer( 'caaru_application_nonce', 'nonce' );
        if ( ! is_user_logged_in() ) wp_send_json_error( [ 'message' => 'Not logged in.' ] );

        $application = $this->get_user_application( get_current_user_id() );
        parse_str(wp_unslash($_POST['formData']), $form_data);

        // Server-side validation
        $errors = [];
        $sections = caaru_get_form_sections();
        $submitted_data = caaru_sanitize_application_form_data($form_data);
        
        foreach($sections as $s_key => $s_val) {
            if(isset($s_val['fields'])) {
                foreach($s_val['fields'] as $f_key => $f_val) {
                    if(!empty($f_val['required'])) {
                        if(empty($submitted_data[$s_key][$f_key])) {
                            $errors[] = $f_val['label'] . ' is a required field.';
                        }
                    }
                }
            }
        }

        if(!empty($errors)) {
            wp_send_json_error(['message' => implode("\n", $errors)]);
            return;
        }

        $existing_data = get_post_meta($application->ID, '_application_data', true);
        $existing_data = is_array($existing_data) ? $existing_data : [];
        $all_data = array_replace_recursive($existing_data, $submitted_data);

        update_post_meta( $application->ID, '_application_data', $all_data );
        wp_update_post( [ 'ID' => $application->ID, 'post_status' => 'pending' ] );
        update_post_meta( $application->ID, '_application_status', 'submitted' );

        wp_send_json_success( [ 'message' => 'Application submitted successfully!' ] );
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

