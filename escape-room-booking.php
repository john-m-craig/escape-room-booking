<?php
/**
 * Plugin Name: Ettrick Escape Room Booking
 * Plugin URI:  https://ettrickintelligence.com/escape-room-booking
 * Description: A complete booking system for escape room venues. Manage games, take bookings and collect payments via Stripe — all from your own WordPress website. Upgrade to Pro for unlimited games, promo codes, reports and more.
 * Version:     1.1.1
 * Author:      Ettrick Intelligence
 * Author URI:  https://ettrickintelligence.com
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: ettrick-escape-room-booking
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ─── Constants ────────────────────────────────────────────────────────────────

define( 'ERB_VERSION',     '1.1.1' );
define( 'ERB_LITE',        true );  // Lite version flag

define( 'ERB_PLUGIN_FILE', __FILE__ );
define( 'ERB_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'ERB_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'ERB_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// ─── Activation / Deactivation ────────────────────────────────────────────────

function erb_activate() {
    require_once ERB_PLUGIN_DIR . 'includes/class-erb-activator.php';
    ERB_Activator::activate();
}
register_activation_hook( __FILE__, 'erb_activate' );

function erb_deactivate() {
    require_once ERB_PLUGIN_DIR . 'includes/class-erb-deactivator.php';
    ERB_Deactivator::deactivate();
}
register_deactivation_hook( __FILE__, 'erb_deactivate' );

// ─── Bootstrap ────────────────────────────────────────────────────────────────

require_once ERB_PLUGIN_DIR . 'includes/class-erb-loader.php';

/**
 * Runs lightweight upgrade checks on every load when version changes.
 * Handles cases where activation hook doesn't fire on plugin update.
 */
function erb_maybe_upgrade() {
    $installed = get_option( 'erb_version', '0' );
    if ( version_compare( $installed, ERB_VERSION, '<' ) ) {
        require_once ERB_PLUGIN_DIR . 'includes/class-erb-activator.php';
        ERB_Activator::upgrade();
        update_option( 'erb_version', ERB_VERSION );
    }
}
add_action( 'plugins_loaded', 'erb_maybe_upgrade' );

function erb_run() {
    $plugin = new ERB_Loader();
    $plugin->run();
}
erb_run();
