(function($) {
    'use strict';

    function validateAdminForm($form) {
        var isValid = true;
        $form.find('[required]').each(function() {
            var $field = $(this);
            $field.css('border-color', '#8c8f94'); // Reset border color

            if ($.trim($field.val()) === '') {
                isValid = false;
                $field.css('border-color', '#d63638');
            }
        });

        if (!isValid) {
            alert('Please fill out all required fields marked with *');
            var $firstError = $form.find('[style*="border-color: rgb(214, 54, 56)"]').first();
            if ($firstError.length) {
                $('html, body').animate({
                    scrollTop: $firstError.offset().top - 100
                }, 500);
            }
        }
        return isValid;
    }

    $(document).ready(function() {
        
        // --- Form Validation for Create New Application ---
        $('#caaru-create-app-form').on('submit', function(e) {
            if (!validateAdminForm($(this))) {
                e.preventDefault(); // Stop form submission if validation fails
            }
        });

        // --- Debounced AJAX Member Search for Referrals in Admin ---
        var searchTimeout;
        $('.caaru-member-search').autocomplete({
            source: function(request, response) {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    $.ajax({
                        url: caaruAdminAjax.ajax_url,
                        dataType: "json",
                        data: {
                            action: 'caaru_search_members',
                            nonce: caaruAdminAjax.nonce,
                            term: request.term
                        },
                        success: function(data) {
                            response(data);
                        },
                        error: function() {
                            response([]); // Return empty array on error
                        }
                    });
                }, 300); // Wait 300ms after user stops typing
            },
            minLength: 2,
            select: function(event, ui) {
                event.preventDefault(); 
                
                var $nameInput = $(this);
                $nameInput.val(ui.item.value); 

                // Find the ID field within the same grid and set its value.
                var $idInput = $nameInput.closest('.caaru-admin-grid').find('input[name*="[referrer_id]"]');
                if($idInput.length) {
                    $idInput.val(ui.item.membership_id);
                }
            },
            focus: function(event, ui) {
                event.preventDefault();
                $(this).val(ui.item.value);
            }
        });
    });
})(jQuery);

