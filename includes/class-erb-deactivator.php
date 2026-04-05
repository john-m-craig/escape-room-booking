<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Runs on plugin deactivation.
 * Note: Tables and data are intentionally preserved on deactivation.
 * Full removal only happens on uninstall (uninstall.php).
 */
class ERB_Deactivator {

    public static function deactivate() {
        // Clear any scheduled cron jobs
        wp_clear_scheduled_hook( 'erb_cleanup_expired_holds' );
        flush_rewrite_rules();
    }
}
