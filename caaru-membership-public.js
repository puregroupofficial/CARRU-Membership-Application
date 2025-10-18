(function($) {
    'use strict';

    function validateForm($form) {
        var isValid = true;
        $form.find('[required]').each(function() {
            var $field = $(this);
            var $wrapper = $field.closest('.field-wrapper');
            $wrapper.removeClass('input-error');

            if ($field.is(':radio') || $field.is(':checkbox')) {
                var name = $field.attr('name');
                if ($form.find('input[name="' + name + '"]:checked').length === 0) {
                    isValid = false;
                    $wrapper.addClass('input-error');
                }
            } else {
                if ($.trim($field.val()) === '') {
                    isValid = false;
                    $wrapper.addClass('input-error');
                }
            }
        });

        if (!isValid) {
            alert('Please fill out all required fields marked with *');
            var $firstError = $form.find('.input-error').first();
            if ($firstError.length) {
                $('html, body').animate({
                    scrollTop: $firstError.offset().top - 100
                }, 500);
            }
        }
        return isValid;
    }

    $(document).ready(function() {
        
        // --- Set Initial Submit Button Text ---
        $('#caaru-submit-application').text(caaru_ajax_object.text.submitBtnText);

        // --- Accordion Logic for Editable Form ---
        $('#caaru-editable-form .caaru-section-header').on('click', function(e) {
            e.preventDefault();
            var $section = $(this).closest('.caaru-section');
            $section.toggleClass('open');
            $('#caaru-editable-form .caaru-section.open').not($section).removeClass('open');
        });

        // --- Collapsible Education Section ---
        $('#add-education-button').on('click', function(e) {
            e.preventDefault();
            var $hiddenBlocks = $('.hidden-education-block');
            if ($hiddenBlocks.length > 0) {
                $hiddenBlocks.first().slideDown().removeClass('hidden-education-block');
            }
            if ($('.hidden-education-block').length === 0) {
                $(this).hide();
            }
        });

        // --- AJAX Save Section ---
        $('.caaru-save-section').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var $button = $(this);
            var $section = $button.closest('.caaru-section');
            var sectionKey = $section.data('section');
            var $confirmation = $button.siblings('.save-confirmation');
            var formData = $section.find('input, select, textarea').serialize();

            $.ajax({
                url: caaru_ajax_object.ajax_url,
                type: 'POST',
                data: { action: 'save_application_section', nonce: caaru_ajax_object.nonce, section: sectionKey, formData: formData },
                beforeSend: function() {
                    $button.text(caaru_ajax_object.text.saving).prop('disabled', true);
                    $confirmation.css('opacity', 0);
                },
                success: function(response) {
                    if (response.success) {
                        $confirmation.css('opacity', 1);
                        setTimeout(function() { $confirmation.css('opacity', 0); }, 2500);
                    } else {
                        alert('Error: ' + (response.data.message || 'Unknown error'));
                    }
                },
                error: function() { alert('A network error occurred. Please try again.'); },
                complete: function() { $button.text(caaru_ajax_object.text.save_changes).prop('disabled', false); }
            });
        });

        // --- AJAX Full Submission for Initial Form ---
        $('#caaru-submit-application').on('click', function(e) {
            e.preventDefault();
            var $button = $(this);
            var $form = $('#caaru-initial-form');
            
            if (!validateForm($form)) return;

            if (!confirm('Are you sure you want to submit your application? This will take you to the payment page and you will not be able to edit membership or referral details later.')) {
                return;
            }

            $.ajax({
                url: caaru_ajax_object.ajax_url,
                type: 'POST',
                data: { action: 'submit_full_application', nonce: caaru_ajax_object.nonce, formData: $form.serialize() },
                beforeSend: function() { $button.html(caaru_ajax_object.text.submitting + '<span class="spinner"></span>').prop('disabled', true); },
                success: function(response) {
                    if (response.success) {
                        var redirectUrl = caaru_ajax_object.redirect_url;
                        if (redirectUrl) {
                            window.location.href = redirectUrl;
                        } else {
                            alert("Application submitted! Please contact an administrator for payment instructions.");
                            window.location.reload();
                        }
                    } else {
                        alert('Error submitting application:\n\n' + (response.data.message || 'Please check the form for errors.'));
                        $button.html(caaru_ajax_object.text.submitBtnText).prop('disabled', false);
                    }
                },
                error: function() {
                    alert('A network error occurred. Please try again.');
                    $button.html(caaru_ajax_object.text.submitBtnText).prop('disabled', false);
                }
            });
        });
        
        // --- Debounced AJAX Member Search for Referrals ---
        var searchTimeout;
        $('.caaru-member-search').autocomplete({
            source: function(request, response) {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    $.ajax({
                        url: caaru_ajax_object.ajax_url, dataType: "json",
                        data: { action: 'caaru_search_members', nonce: caaru_ajax_object.search_nonce, term: request.term },
                        success: function(data) { response(data); },
                        error: function() { response([]); }
                    });
                }, 300);
            },
            minLength: 2,
            select: function(event, ui) {
                event.preventDefault();
                $(this).val(ui.item.value);
                $('#caaru_referrer_id_field').val(ui.item.membership_id);
            },
            focus: function(event, ui) {
                event.preventDefault();
                $(this).val(ui.item.value);
            }
        });
    });
})(jQuery);

