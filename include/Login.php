<?php

namespace Ftek\GoogleSync;

class Login {
    public static function init() {
        add_action('init', function() {
            if (self::is_login_page()) {
                if (self::is_logout_action()) {
                    return;
                } else if (self::is_logged_out()) {
                    wp_redirect(home_url());
                    exit;
                }

                $google_oauth = self::get_google_oauth();
                if ($google_oauth) {
                    if (isset($_REQUEST['redirect_to'])) {
                        setcookie(
                            'ftek_gsync_redirect',
                            $_REQUEST['redirect_to'],
                            [
                                'path' => '/',
                                'secure' => is_ssl(),
                                'httponly' => true,
                                'samesite' => 'Lax'
                            ]
                        );
                    }

                    wp_redirect($google_oauth->buildFullAuthorizationUri([
                        'nonce' => bin2hex(random_bytes(128 / 8)),
                        'prompt' => 'select_account'
                    ]));
                    exit;
                }
            }

            if (isset($_GET['ftek_gsync_openid'])) {
                $google_oauth = self::get_google_oauth();
                if ( 
                    isset(
                        $_GET['ftek_gsync_openid'], 
                        $_GET['code'], 
                        $_GET['state']
                    ) &&
                    $google_oauth &&
                    $_GET['state'] == $google_oauth->getState()
                ) {
                    $google_oauth->setGrantType('authorization_code');
                    $google_oauth->setCode(sanitize_text_field($_GET['code']));
                    $google_oauth->fetchAuthToken();

                    $google_verify = new \Google\AccessToken\Verify();
                    $id_token_payload = $google_verify->verifyIdToken($google_oauth->getIdToken());

                    $domain = Settings::get('domain');
                    $user_domain = explode('@', $id_token_payload['email'])[1];
                    if (empty($domain) || $user_domain == $domain) {
                        $user = self::update_or_create_user($id_token_payload);
                        if ($user) {
                            wp_set_current_user($user->ID, $user->user_login);
                            wp_set_auth_cookie($user->ID);
                            do_action('wp_login', $user->user_login, $user);
                        }
                    }
                }

                $redirect_to = $_COOKIE['ftek_gsync_redirect'] ?? admin_url();
                if (basename($redirect_to) == 'profile.php') {
                    $redirect_to = admin_url();
                }

                $redirect = apply_filters(
                    'login_redirect', 
                    $_COOKIE['ftek_gsync_redirect'] ?? admin_url(), 
                    $_COOKIE['ftek_gsync_redirect'] ?? '', 
                    $user ?? new \WP_Error('ftek_gsync_login_failed', __('Login failed', 'ftek_gsync'))
                );


                setcookie('ftek_gsync_state', null, -1, '/');
                setcookie('ftek_gsync_redirect', null, -1, '/');

                wp_safe_redirect($redirect);
                exit;
            }
        });

        // print_r(basename('http://test.as/profile.php'));

        add_filter(
            'login_redirect',
            function($redirect_to, $requested_redirect_to, $user) {
                if (
                    basename($redirect_to) == 'profile.php' && 
                    basename($requested_redirect_to) != 'profile.php'
                ) {
                    return admin_url();
                }
                return $redirect_to;
            },
            20,
            3
        );

        add_action('admin_menu', function() {
            if (defined('IS_PROFILE_PAGE')) {
                if (!is_user_logged_in()) {
                    global $wp;
                    wp_redirect(wp_login_url($wp->request));
                }
                $user = wp_get_current_user();
                wp_redirect('https://accounts.google.com/AccountChooser?Email=' . $user->user_email . '&continue=https://myaccount.google.com/');
                exit;
            }
        });

        add_filter(
            'get_avatar',
            function($avatar, $id_or_email, $size, $default, $alt) {
                if (!is_numeric($id_or_email)) {
                    $user = get_user_by('email', $id_or_email);
                    if (!$user){
                        return $avatar;
                    }
                    $user_id = $user->ID;
                } else {
                    $user_id = $id_or_email;
                }

                $meta = self::get_meta($id_or_email);
                if (empty($meta['profile'])) {
                    return $avatar;
                }

                ob_start();
                ?>
                <img class="avatar avatar-<?php echo esc_attr($size); ?> photo" loading="lazy" src="<?php echo esc_attr($meta['profile']); ?>" width="<?php echo esc_attr($size); ?>" height="<?php echo esc_attr($size); ?>" alt="<?php echo esc_attr($alt); ?>" />
                <?php
                return ob_get_clean();
            },
            9,
            5
        );
    }

    private static function update_or_create_user($data) {
        $user = get_user_by('email', $data['email']);

        if ($user) {
            update_user_meta($user->ID, 'first_name', $data['given_name']);
            update_user_meta($user->Id, 'last_name', $data['family_name']);
        } else {
            $user_id = wp_insert_user([
                'user_pass' => wp_generate_password(24),
                'user_login' => $data['email'],
                'user_email' => $data['email'],
                'display_name' => $data['name'],
                'first_name' => $data['given_name'],
                'last_name' => $data['family_name'],
                'user_registered' => gmdate( 'Y-m-d H:i:s' ),
                'role' => 'subscriber'
            ]);

            if (is_wp_error($user_id)) {
                return false;
            }

            $user = get_user_by('id', $user_id);
        }

        $meta = self::get_meta($user->ID);
        $meta['profile'] = $data['picture'];
        update_user_meta($user->ID, 'ftek_gsync', $meta);

        return $user;
    }

    public static function get_google_oauth() {
        $oauth_client_path = Settings::get('oauth_client_path');
        if (empty($oauth_client_path)) {
            return NULL;
        }

        $oauth_client_json = file_get_contents($oauth_client_path);
        $oauth_client = json_decode($oauth_client_json);

        if (isset($_COOKIE['ftek_gsync_state'])) {
            $state = $_COOKIE['ftek_gsync_state'];
        } else {
            $state = bin2hex(random_bytes(128 / 8));
            setcookie(
                'ftek_gsync_state', 
                $state, 
                [
                    'path' => '/',
                    'secure' => is_ssl(),
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]
            );
        }

        return new \Google\Auth\OAuth2([
            'authorizationUri' => $oauth_client->web->auth_uri,
            'tokenCredentialUri' => $oauth_client->web->token_uri,
            'clientId' => $oauth_client->web->client_id,
            'clientSecret' => $oauth_client->web->client_secret,
            'scope' => ['openid', 'email', 'profile'],
            'state' => $state,
            'redirectUri' => site_url('?ftek_gsync_openid')
        ]);
    }

    public static function get_meta($user_id) {
        $meta = get_user_meta($user_id, 'ftek_gsync', true);
        return empty($meta) ? [] : $meta;
    }

    public static function uninstall() {
        foreach (get_users() as $user) {
            delete_user_meta($user->ID, 'ftek_gsync');
        }
    }

    public static function is_logged_out() {
        return isset($_GET['loggedout']) && $_GET['loggedout'] == 'true';
    }

    public static function is_logout_action() {
        return isset($_GET['action']) && $_GET['action'] == 'logout';
    }

    public static function is_login_page() {
        global $pagenow;
        return in_array($pagenow, ['wp-login.php', 'wp-register.php']);
    }
}