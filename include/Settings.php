<?php

namespace Ftek\GoogleSync;

class Settings {

    public static function init() {
        add_action('admin_init', function() {
            $text_settings_callback = function(string $setting, string $placeholder = '') {
                return function() use ($setting, $placeholder) {
                    $path = self::get($setting, '');
                    ?>
                    <input type="text" name="ftek_gsync_settings[<?php echo esc_attr($setting); ?>]" value="<?php echo esc_attr($path); ?>" placeholder="<?php echo esc_attr($placeholder); ?>">
                    <?php
                };
            };

            register_setting('pluginPage', 'ftek_gsync_settings');
    
            add_settings_section(
                'ftek_gsync_settings_section',
                __('Google Sync Settings', 'ftek_gsync'),
                function() {},
                'pluginPage'
            );
    
            add_settings_field(
                'ftek_gsync_credentials_path',
                __('Absolute path to the Google Service account key', 'ftek_gsync'),
                $text_settings_callback('credentials_path', __('/path/to/credentials.json', 'ftek_gsync')),
                'pluginPage',
                'ftek_gsync_settings_section'
            );

            add_settings_field(
                'ftek_gsync_oauth_client_path',
                __('Absolute path to the Google OAuth2 client', 'ftek_gsync'),
                $text_settings_callback('oauth_client_path', __('/path/to/credentials.json', 'ftek_gsync')),
                'pluginPage',
                'ftek_gsync_settings_section'
            );

            add_settings_field(
                'ftek_gsync_domain',
                __('Google Workspace domain', 'ftek_gsync'),
                $text_settings_callback('domain', __('mydomain.com', 'ftek_gsync')),
                'pluginPage',
                'ftek_gsync_settings_section'
            );

            add_settings_field(
                'ftek_gsync_customer_id',
                __('Google Workspace customer ID', 'ftek_gsync'),
                $text_settings_callback('customer_id', __('AB01cd23', 'ftek_gsync')),
                'pluginPage',
                'ftek_gsync_settings_section'
            );

            add_settings_field(
                'ftek_gsync_admin_email',
                __('Email of a Google Workspace admin', 'ftek_gsync'),
                $text_settings_callback('admin_email', __('admin@mydomain.com', 'ftek_gsync')),
                'pluginPage',
                'ftek_gsync_settings_section'
            );
        });

        add_action('admin_menu', function() {
            add_options_page(
                __('Google Sync', 'ftek_gsync'),
                __('Google Sync', 'ftek_gsync'),
                'manage_options',
                'ftek_gsync_menu',
                function () {
                    ?>
                    <form method="post" action="options.php">
                        <?php
                        settings_fields('pluginPage');
                        do_settings_sections('pluginPage');
                        submit_button();
                        ?>
                    </form>
                    <?php
                }
            );
        });
    }

    public static function uninstall() {
        delete_option('ftek_gsync_settings');
    }

    public static function get(string $name, $default = NULL) {
        $setting = get_option('ftek_gsync_settings', []);
        return isset($setting[$name]) ? $setting[$name] : $default;
    }
}