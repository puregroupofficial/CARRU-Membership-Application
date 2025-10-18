<?php
/**
 * Provides the public-facing view for the application form.
 */
if ( ! defined( 'WPINC' ) ) die;

$status = get_post_meta( $application->ID, '_application_status', true );
$post_status = get_post_status($application->ID);
$is_new = ($status === 'new');
$sections = caaru_get_form_sections();

function caaru_required_marker() { echo '<span class="required-asterisk">*</span>'; }
?>

<div id="caaru-application-form-container" class="caaru-form-wrapper">
    
    <?php if ( $is_new ): ?>
    <!-- ========================= INITIAL FORM FOR NEW USERS ========================= -->
    <div class="caaru-form-card">
        <div class="form-header">
             <h2 class="form-title">Membership Application</h2>
             <p class="form-subtitle">Please fill out all sections to join CAARU.</p>
        </div>
        <form id="caaru-initial-form" class="space-y-10">
            <?php foreach ($sections as $section_key => $section): ?>
                <fieldset class="form-section">
                    <legend class="section-title"><?php echo esc_html($section['title']); ?></legend>
                    
                    <?php if ($section_key === 'education'): ?>
                        <?php 
                        $degrees = ['Graduation', 'Masters', 'Ph.D.', 'Others'];
                        foreach($degrees as $index => $degree_name):
                            $degree_key = strtolower(str_replace('.', '', $degree_name)); 
                            $is_hidden = $index > 0; // Keep 'Graduation' visible
                        ?>
                        <div class="education-block <?php if($is_hidden) echo 'hidden-education-block'; ?>" data-degree-block="<?php echo $degree_key; ?>">
                            <h4 class="education-degree-title"><?php echo esc_html($degree_name); ?></h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-4">
                                <?php foreach(['subject', 'session', 'faculty', 'hall', 'year', 'held_in'] as $field_key): ?>
                                <div>
                                    <label class="form-label"><?php echo esc_html(ucfirst(str_replace('_', ' ', $field_key))); ?></label>
                                    <input type="text" name="education[<?php echo $degree_key; ?>][<?php echo $field_key; ?>]" class="form-input">
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <div class="mt-4">
                            <button type="button" id="add-education-button" class="add-more-button">+ Add Another Degree</button>
                        </div>
                    <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
                        <?php foreach ($section['fields'] as $field_key => $field): 
                             $value = $data[$section_key][$field_key] ?? '';
                        ?>
                             <div class="field-wrapper <?php echo !empty($field['full_width']) ? 'col-span-1 md:col-span-2' : ''; ?>">
                                 <label class="form-label">
                                     <?php echo esc_html($field['label']); ?>
                                     <?php if (!empty($field['required'])) caaru_required_marker(); ?>
                                 </label>
                                 <?php if ($field['type'] === 'checkbox' || $field['type'] === 'radio'): ?>
                                    <div class="options-group">
                                        <?php foreach ($field['options'] as $val => $label): ?>
                                            <label class="option-label">
                                                <input type="<?php echo esc_attr($field['type']); ?>" name="<?php echo esc_attr("{$section_key}[{$field_key}]" . ($field['type'] === 'checkbox' ? '[]' : '')); ?>" value="<?php echo esc_attr($val); ?>" class="form-<?php echo esc_attr($field['type']); ?>" <?php if (!empty($field['required'])) echo 'required'; ?>>
                                                <span><?php echo esc_html($label); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: 
                                    $extra_class = ($field_key === 'referrer_name') ? 'caaru-member-search' : '';
                                    $is_readonly = ($field_key === 'referrer_id');
                                ?>
                                    <input type="<?php echo esc_attr($field['type']); ?>" name="<?php echo esc_attr("{$section_key}[{$field_key}]"); ?>" class="form-input <?php echo $extra_class; ?>" <?php if ($is_readonly) echo 'id="caaru_referrer_id_field" readonly'; ?> <?php if (!empty($field['required'])) echo 'required'; ?> value="<?php echo esc_attr($value); ?>">
                                <?php endif; ?>
                             </div>
                         <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </fieldset>
            <?php endforeach; ?>
        </form>
         <div class="form-footer">
            <button id="caaru-submit-application" class="submit-button">Submit Application</button>
        </div>
    </div>

    <?php else: ?>
    <!-- ========================= ACCORDION FORM FOR EDITING ========================= -->
    <?php if ($post_status === 'pending') echo '<div class="status-banner banner-pending"><p>Thank you. Your application has been submitted and is under review. You can edit your information below.</p></div>'; ?>
    <?php if ($post_status === 'publish') echo '<div class="status-banner banner-published"><p>Congratulations! Your membership application has been approved. You can review or update your details below.</p></div>'; ?>

    <div id="caaru-editable-form" class="space-y-3">
        <?php 
            $editable_sections = array_diff_key($sections, array_flip(['final_details']));
            foreach ($editable_sections as $section_key => $section): ?>
            <div class="caaru-section" data-section="<?php echo esc_attr($section_key); ?>">
                <div class="caaru-section-header">
                    <h3 class="section-accordion-title"><?php echo esc_html($section['title']); ?></h3>
                    <div class="plus-minus-icon"><span class="plus">+</span><span class="minus">-</span></div>
                </div>
                <div class="caaru-section-content">
                    <?php if ($section_key === 'education'): ?>
                        <?php foreach(['Graduation', 'Masters', 'Ph.D.', 'Others'] as $degree_name):
                            $degree_key = strtolower(str_replace('.', '', $degree_name)); ?>
                        <div class="education-block">
                            <h4 class="education-degree-title"><?php echo esc_html($degree_name); ?></h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-4">
                                <?php foreach(['subject', 'session', 'faculty', 'hall', 'year', 'held_in'] as $field_key_edu):
                                    $value = $data['education'][$degree_key][$field_key_edu] ?? ''; ?>
                                <div>
                                    <label class="form-label"><?php echo esc_html(ucfirst(str_replace('_', ' ', $field_key_edu))); ?></label>
                                    <input type="text" name="education[<?php echo $degree_key; ?>][<?php echo $field_key_edu; ?>]" value="<?php echo esc_attr($value); ?>" class="form-input">
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
                        <?php foreach ($section['fields'] as $field_key => $field): 
                            $value = $data[$section_key][$field_key] ?? ''; ?>
                            <div class="field-wrapper <?php echo !empty($field['full_width']) ? 'col-span-1 md:col-span-2' : ''; ?>">
                                <label class="form-label">
                                    <?php echo esc_html($field['label']); ?>
                                    <?php if (!empty($field['required'])) caaru_required_marker(); ?>
                                </label>
                                <?php if ($field['type'] === 'checkbox' || $field['type'] === 'radio'): ?>
                                    <div class="options-group">
                                        <?php foreach ($field['options'] as $val => $label): 
                                            $checked = (is_array($value) && in_array($val, $value)) || ($value == $val) ? 'checked' : ''; ?>
                                            <label class="option-label">
                                                <input type="<?php echo esc_attr($field['type']); ?>" name="<?php echo esc_attr("{$section_key}[{$field_key}]" . ($field['type'] === 'checkbox' ? '[]' : '')); ?>" value="<?php echo esc_attr($val); ?>" <?php echo $checked; ?> class="form-<?php echo esc_attr($field['type']); ?>">
                                                <span><?php echo esc_html($label); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <input type="<?php echo esc_attr($field['type']); ?>" name="<?php echo esc_attr("{$section_key}[{$field_key}]"); ?>" value="<?php echo esc_attr($value); ?>" class="form-input">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <div class="section-footer">
                        <span class="save-confirmation">Saved!</span>
                        <button class="caaru-save-section save-button">Save Changes</button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <div class="caaru-section-locked">
            <h3 class="section-accordion-title">Locked Information</h3>
            <div class="locked-content">
                <?php 
                $final_details = $data['final_details'] ?? [];
                $plan = $final_details['membership_plan'] ?? 'N/A';
                echo '<div class="locked-item"><span class="locked-label">Membership Plan:</span><span>' . esc_html(ucfirst($plan)) . '</span></div>';
                echo '<div class="locked-item"><span class="locked-label">Referred By:</span><span>' . esc_html($final_details['referrer_name'] ?? 'N/A') . '</span></div>';
                echo '<div class="locked-item"><span class="locked-label">Referrer ID:</span><span>' . esc_html($final_details['referrer_id'] ?? 'N/A') . '</span></div>';
                echo '<div class="locked-item"><span class="locked-label">Signature:</span><span>' . esc_html($final_details['digital_signature'] ?? 'N/A') . ' on ' . esc_html($final_details['signature_date'] ?? 'N/A') . '</span></div>';
                ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

