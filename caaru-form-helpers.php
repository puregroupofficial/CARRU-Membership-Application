<?php
/**
 * Helper functions for form structure and sanitization.
 *
 * @package    CAARU_Membership
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Defines the structure of the application form.
 *
 * @return array
 */
function caaru_get_form_sections() {
    return [
        'personal_info' => [
            'title'  => 'Personal Information',
            'fields' => [
                'first_name' => ['label' => 'First Name', 'type' => 'text', 'required' => true],
                'middle_name' => ['label' => 'Middle Name', 'type' => 'text'],
                'last_name' => ['label' => 'Last Name', 'type' => 'text', 'required' => true],
                'preferred_name' => ['label' => 'Preferred Name', 'type' => 'text'],
                'dob' => ['label' => 'Date of Birth (DD/MM)', 'type' => 'text', 'required' => true],
                'gender' => ['label' => 'Gender', 'type' => 'radio', 'options' => ['male' => 'Male', 'female' => 'Female'], 'required' => true],
                'phone' => ['label' => 'Phone', 'type' => 'tel', 'required' => true],
                'email' => ['label' => 'Email Address', 'type' => 'email', 'required' => true],
                'marital_status' => ['label' => 'Marital Status', 'type' => 'radio', 'options' => ['married' => 'Married', 'single' => 'Single'], 'required' => true],
                'facebook_id' => ['label' => 'Facebook/Messenger ID', 'type' => 'text', 'full_width' => true],
            ],
        ],
        'home_address' => [
            'title' => 'Home Address',
            'fields' => [
                'street' => ['label' => 'Street Address', 'type' => 'text', 'full_width' => true],
                'apartment' => ['label' => 'Apartment, suite, etc', 'type' => 'text', 'full_width' => true],
                'city' => ['label' => 'City', 'type' => 'text', 'required' => true],
                'province' => ['label' => 'State/Province', 'type' => 'text', 'required' => true],
                'postal' => ['label' => 'ZIP / Postal Code', 'type' => 'text', 'required' => true],
            ]
        ],
        'mailing_address' => [
            'title' => 'Mailing Address (If different)',
            'fields' => [
                'street' => ['label' => 'Street Address', 'type' => 'text', 'full_width' => true],
                'apartment' => ['label' => 'Apartment, suite, etc', 'type' => 'text', 'full_width' => true],
                'city' => ['label' => 'City', 'type' => 'text'],
                'province' => ['label' => 'State/Province', 'type' => 'text'],
                'postal' => ['label' => 'ZIP / Postal Code', 'type' => 'text'],
            ]
        ],
        'spouse_info' => [
            'title' => 'Spouse Information',
            'fields' => [
                'is_alumni' => ['label' => 'Is your Spouse an Alumni of Rajshahi University?', 'type' => 'radio', 'options' => ['yes' => 'Yes', 'no' => 'No'], 'full_width' => true],
                'first_name' => ['label' => 'First Name', 'type' => 'text'],
                'last_name' => ['label' => 'Last Name', 'type' => 'text'],
                'dob' => ['label' => 'Date of Birth (DD/MM)', 'type' => 'text'],
                'phone' => ['label' => 'Phone', 'type' => 'tel'],
                'email' => ['label' => 'Email Address', 'type' => 'email'],
            ]
        ],
        'education' => [ 'title' => 'Educational Information' ],
        'current_status' => [
            'title' => 'Current Status',
            'fields' => [
                'occupation' => ['label' => 'Occupation', 'type' => 'text', 'full_width' => true],
                'employer' => ['label' => 'Employer/Organization', 'type' => 'text', 'full_width' => true],
                'work_address' => ['label' => 'Work Address', 'type' => 'text', 'full_width' => true],
            ]
        ],
        'volunteer_interests' => [
            'title' => 'Volunteer Interests',
            'fields' => [
                'interests' => [
                    'label' => 'Would you like to be involved in any of the following?',
                    'type' => 'checkbox',
                    'options' => [
                        'event_planning' => 'Event Planning',
                        'fundraising' => 'Fundraising',
                        'social_media' => 'Social Media/Outreach',
                        'mentorship' => 'Mentorship Programs',
                        'newsletter' => 'Newsletter/Editorial',
                    ],
                    'full_width' => true,
                ]
            ]
        ],
        'final_details' => [
            'title' => 'Final Details',
            'fields' => [
                'membership_plan' => ['label' => 'Choose a Membership Plan', 'type' => 'radio', 'options' => ['regular' => 'Regular Member', 'lifetime' => 'Lifetime Member'], 'required' => true],
                'digital_signature' => ['label' => 'Your Digital Signature', 'type' => 'text', 'required' => true],
                'signature_date' => ['label' => 'Date', 'type' => 'date', 'required' => true],
                'declaration' => ['label' => 'Declaration', 'type' => 'checkbox', 'options' => ['agree' => 'I certify that the above information is true and correct to the best of my knowledge. I agree to abide by the CAARU constitution and support its mission.'], 'required' => true],
                'referrer_name' => ['label' => 'Referred By (Name)', 'type' => 'text', 'required' => true],
                'referrer_id' => ['label' => 'Membership Number', 'type' => 'text', 'required' => true],
            ]
        ]
    ];
}

/**
 * Sanitizes the application form data from a POST request.
 *
 * @param array $post_data The raw $_POST data for the form.
 * @return array Sanitized data.
 */
function caaru_sanitize_application_form_data( $post_data ) {
    $sanitized_data = [];
    $sections = caaru_get_form_sections();

    foreach ($sections as $section_key => $section) {
        if (isset($post_data[$section_key]) && is_array($post_data[$section_key])) {
            foreach ($section['fields'] as $field_key => $field) {
                if (isset($post_data[$section_key][$field_key])) {
                    $value = $post_data[$section_key][$field_key];
                    if (is_array($value)) {
                        $sanitized_data[$section_key][$field_key] = array_map('sanitize_text_field', $value);
                    } elseif ($field['type'] === 'email') {
                        $sanitized_data[$section_key][$field_key] = sanitize_email($value);
                    } else {
                        $sanitized_data[$section_key][$field_key] = sanitize_text_field($value);
                    }
                }
            }
        }
    }
    
    // Sanitize education separately due to its nested structure
    if (isset($post_data['education']) && is_array($post_data['education'])) {
        foreach ($post_data['education'] as $degree_key => $fields) {
            if (is_array($fields)) {
                foreach ($fields as $field_key => $value) {
                    $sanitized_data['education'][sanitize_key($degree_key)][sanitize_key($field_key)] = sanitize_text_field($value);
                }
            }
        }
    }

    return $sanitized_data;
}

