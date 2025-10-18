<div class="wrap caaru-admin-page">
    <h1>CAARU Membership Settings</h1>
    <p>Manage plugin settings below.</p>
    
    <div class="caaru-admin-card">
        <form method="post" action="options.php">
            <?php
                settings_fields( 'caaru_settings_group' );
                do_settings_sections( 'caaru-settings' );
                submit_button('Save Settings');
            ?>
        </form>
    </div>
</div>

