<?php
/*
Plugin Name: Reverse Proxy Auth
Plugin URI: https://github.com/Dherlou/Yourls-Reverse-Proxy-Auth
Description: Authenticate users based on a username passed by a reverse proxy in a custom header.
Version: 1.0
Author: Dherlou
Author URI: https://github.com/Dherlou
*/

// No direct call
if (!defined('YOURLS_ABSPATH')) {
    die();
}

class Reverse_Proxy_Auth {

    /* configuration */

    const ENV = [
        'header' => [
            'var' => 'YOURLS_AUTH_USERNAME_HEADER',
            /**
             * use default 'X-authentik-username' header of authentik by default,
             * which arrives as 'HTTP_X_AUTHENTIK_USERNAME' via PHP in yourls
             */
            'default' => 'HTTP_X_AUTHENTIK_USERNAME'
        ],
        'SLO' => [
            'var' => 'YOURLS_AUTH_SLO_URL',
            /**
             * use default authentik outpost's SLO routine by default
             */
            'default' => '/outpost.goauthentik.io/sign_out'
        ]
    ];

    /* hooks */

    /**
     * Register our hooks.
     */
    function __construct() {
        yourls_add_filter('is_valid_user', [$this, 'is_valid_user']);
        yourls_add_action('logout', [$this, 'logout']);
    }

    /**
     * Extends the default valid user check by making it dependent
     * on the presence of the configured username header.
     * 
     * If the header value is found, log the user in,
     * otherwise fall back to the default internal authentication.
     */
    function is_valid_user(bool $is_valid): bool {
        try {
            # get username from reverse proxy
            $username = $this->get_username();

            # authenticate user in yourls
            define('YOURLS_USER', $username);
            return true;
        } catch (Exception $exc) {
            return $is_valid; # fall back to default internal auth
        }
    }

    function logout(): void {
        $slo_url = $this->get_SLO_URL();

        # delete local yourls session
        yourls_store_cookie('');

        # redirect to reverse proxy logout flow
        header('Location: ' . $slo_url);
        exit;
    }

    /* utility functions */

    /**
     * Returns the username passed from the reverse proxy.
     * If the configured header is not found, returns null instead.
     */
    function get_username(): string {
        $header_name = getenv(self::ENV['header']['var']) ?: self::ENV['header']['default']; 
        return $_SERVER[$header_name] ?? throw new Exception('Username header not found!');
    }
    
    /**
     * Returns the configured SLO URL to be redirected after logout.
     */
    function get_SLO_URL(): string {
        return getenv(self::ENV['SLO']['var']) ?: self::ENV['SLO']['default'];
    }

}

new Reverse_Proxy_Auth();
