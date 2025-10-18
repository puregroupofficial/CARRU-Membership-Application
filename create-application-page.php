<?php
/**
 * Provides the 'Create New Application' admin page view.
 *
 * @package    CAARU_Membership
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

if ( isset( $_POST['caaru_create_app_nonce'] ) && wp_verify_nonce( $_POST['caaru_create_app_nonce'], 'caaru_create_application' ) ) {
    
    // Server-side validation
    $user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
    $post_data = isset( $_POST['caaru_app_data'] ) ? $_POST['caaru_app_data'] : [];
    $sanitized_data = caaru_sanitize_application_form_data($post_data);

    $errors = [];
    if ( empty( $user_id ) ) {
        $errors[] = 'You must select a user.';
    }
    if ( empty( $sanitized_data['personal_info']['first_name'] ) ) {
        $errors[] = 'First Name is required.';
    }
    if ( empty( $sanitized_data['personal_info']['last_name'] ) ) {
        $errors[] = 'Last Name is required.';
    }
    if ( empty( $sanitized_data['personal_info']['email'] ) ) {
        $errors[] = 'Email is required.';
    }
    
    if ( empty( $errors ) ) {
        $user = get_userdata($user_id);
        $post_id = wp_insert_post([
            'post_title'  => 'Application for ' . $user->display_name,
            'post_author' => $user_id,
            'post_type'   => 'caaru_application',
            'post_status' => 'publish', // Or 'pending' if you want review
        ]);

        if ( ! is_wp_error( $post_id ) ) {
            update_post_meta( $post_id, '_application_data', $sanitized_data );
            update_post_meta( $post_id, '_application_status', 'submitted' ); 
            echo '<div class="notice notice-success is-dismissible"><p>Application created successfully! You can <a href="' . get_edit_post_link( $post_id ) . '">edit it here</a>.</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Error creating application: ' . esc_html( $post_id->get_error_message() ) . '</p></div>';
        }
    } else {
        echo '<div class="notice notice-error"><p><strong>Error:</strong> ' . implode('<br>', array_map('esc_html', $errors)) . '</p></div>';
    }
}

$users = get_users( ['fields' => ['ID', 'display_name'], 'orderby' => 'display_name'] );
$sections = caaru_get_form_sections();
?>
<div class="wrap caaru-admin-page">
    <h1>Create New Membership Application</h1>
    <p>Create a new application on behalf of an existing user.</p>

    <div class="caaru-admin-card">
        <form method="post" action="" id="caaru-create-app-form">
            <?php wp_nonce_field( 'caaru_create_application', 'caaru_create_app_nonce' ); ?>
            
            <fieldset class="caaru-admin-fieldset">
                <legend class="caaru-admin-legend">Assign to User</legend>
                <div class="caaru-admin-grid">
                    <div class="caaru-admin-field-wrapper">
                        <label for="user_id">Select User <span class="required">*</span></label>
                        <select name="user_id" id="user_id" required>
                            <option value="">-- Select a User --</option>
                            <?php foreach ( $users as $user ) : ?>
                                <option value="<?php echo esc_attr( $user->ID ); ?>"><?php echo esc_html( $user->display_name ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </fieldset>

            <?php
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
                            echo '<div class="caaru-admin-field-wrapper">';
                            echo '<label>' . esc_html(ucfirst(str_replace('_', ' ', $field_key))) . '</label>';
                            echo '<input type="text" class="widefat" name="caaru_app_data[education]['.$degree_key.']['.$field_key.']" value="">';
                            echo '</div>';
                        }
                        echo '</div></div>';
                    }
                } else {
                    echo '<div class="caaru-admin-grid">';
                    foreach ($section['fields'] as $field_key => $field) {
                        $name = "caaru_app_data[{$section_key}][{$field_key}]";
                        $required_attr = !empty($field['required']) ? 'required' : '';
                        
                        echo '<div class="caaru-admin-field-wrapper ' . (!empty($field['full_width']) ? 'full-width' : '') . '">';
                        echo '<label for="' . esc_attr($name) . '">' . esc_html($field['label']) . (!empty($field['required']) ? ' <span class="required">*</span>' : '') . '</label>';

                        if ($field['type'] === 'radio' || $field['type'] === 'checkbox') {
                             echo '<div class="options-group">';
                            foreach($field['options'] as $opt_val => $opt_label) {
                                $input_name = $name . ($field['type'] === 'checkbox' ? '[]' : '');
                                echo '<label class="option-label"><input type="'.$field['type'].'" name="'.$input_name.'" value="'.esc_attr($opt_val).'" '.$required_attr.'> '.esc_html($opt_label).'</label>';
                                $required_attr = ''; // Only first radio/checkbox needs required
                            }
                            echo '</div>';
                        } else {
                            $extra_class = ($field_key === 'referrer_name') ? 'caaru-member-search' : '';
                            echo '<input type="'.esc_attr($field['type']).'" class="widefat ' . $extra_class . '" name="'.$name.'" value="" id="'.esc_attr($name).'" '.$required_attr.'>';
                        }
                        echo '</div>';
                    }
                    echo '</div>';
                }
                echo '</fieldset>';
            }
            ?>

            <?php submit_button('Create Application'); ?>
        </form>
    </div>
</div>

