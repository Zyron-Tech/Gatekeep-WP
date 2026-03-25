<?php
/**
 * Plugin Name: Z Gatekeep Wp
 * Plugin URI:  https://zyron-portfolio.vercel.app/
 * Description: Configure login redirection for any user role. Set where each user type lands after a successful login — from any login form. Also redirect users away from restricted URLs based on their role.
 * Version:     2.0.0
 * Author:      Zyron Tech
 * Author URI:  https://zyron-portfolio.vercel.app/
 * License:     GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: zyron-login-redirect
 */

defined( 'ABSPATH' ) || exit;

define( 'ZLR_VERSION',      '2.0.0' );
define( 'ZLR_PLUGIN_DIR',   plugin_dir_path( __FILE__ ) );
define( 'ZLR_PLUGIN_URL',   plugin_dir_url( __FILE__ ) );
define( 'ZLR_OPTION_KEY',   'zlr_redirect_rules' );
define( 'ZLR_DEFAULT_KEY',  'zlr_default_redirect' );
define( 'ZLR_ACCESS_KEY',   'zlr_access_rules' );   // NEW: access-guard rules

// Load components
require_once ZLR_PLUGIN_DIR . 'includes/class-zlr-admin.php';
require_once ZLR_PLUGIN_DIR . 'includes/class-zlr-redirector.php';
require_once ZLR_PLUGIN_DIR . 'includes/class-zlr-access-guard.php';  // NEW

// Boot
add_action( 'plugins_loaded', function () {
    new ZLR_Admin();
    new ZLR_Redirector();
    new ZLR_Access_Guard();   // NEW
} );

// Activation
register_activation_hook( __FILE__, function () {
    if ( false === get_option( ZLR_OPTION_KEY ) ) {
        update_option( ZLR_OPTION_KEY, [] );
    }
    if ( false === get_option( ZLR_DEFAULT_KEY ) ) {
        update_option( ZLR_DEFAULT_KEY, admin_url() );
    }
    if ( false === get_option( ZLR_ACCESS_KEY ) ) {
        update_option( ZLR_ACCESS_KEY, [] );
    }
} );
