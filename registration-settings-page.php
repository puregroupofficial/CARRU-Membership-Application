<div class="wrap">
    <h1>Registration Helper Settings</h1>
    <p>Customize the Profile Builder registration form behavior.</p>
    <form method="post" action="options.php">
        <?php settings_fields( 'caaru_reg_settings_group' ); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><label for="caaru_reg_page_slug">Registration Page Slug</label></th>
                <td>
                    <input type="text" id="caaru_reg_page_slug" name="caaru_reg_page_slug" value="<?php echo esc_attr( get_option( 'caaru_reg_page_slug', 'register' ) ); ?>" class="regular-text" />
                    <p class="description">Enter the page slug (e.g., "register") where your Profile Builder form is. This is needed to load scripts.</p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="caaru_reg_button_text">Button Text</label></th>
                <td>
                    <input type="text" id="caaru_reg_button_text" name="caaru_reg_button_text" value="<?php echo esc_attr( get_option( 'caaru_reg_button_text', 'Next' ) ); ?>" class="regular-text" />
                    <p class="description">The text for the registration form's submit button.</p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="caaru_reg_redirect_url">Redirect URL After Registration</label></th>
                <td>
                    <input type="url" id="caaru_reg_redirect_url" name="caaru_reg_redirect_url" value="<?php echo esc_attr( get_option( 'caaru_reg_redirect_url' ) ); ?>" class="regular-text" placeholder="https://your-site.com/apply" />
                    <p class="description">The URL where users are sent immediately after successful registration (e.g., the application page).</p>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
</div>

