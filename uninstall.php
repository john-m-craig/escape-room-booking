<?php
/**
 * Uninstall — runs when the plugin is deleted from WP Admin.
 * Removes all plugin tables and options.
 * WARNING: This permanently deletes all booking data.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

global $wpdb;

$tables = array(
    'erb_rooms', 'erb_games', 'erb_game_hours', 'erb_prices',
    'erb_blocked_slots', 'erb_customers', 'erb_bookings',
    'erb_booking_history', 'erb_slot_holds', 'erb_promo_codes', 'erb_gamekeepers',
);

foreach ( $tables as $table ) {
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}{$table}" );
}

$options = array(
    'erb_db_version', 'erb_currency', 'erb_currency_symbol',
    'erb_slot_hold_minutes', 'erb_slot_available_color', 'erb_slot_booked_color',
    'erb_stripe_mode', 'erb_stripe_test_pk', 'erb_stripe_test_sk',
    'erb_stripe_live_pk', 'erb_stripe_live_sk', 'erb_stripe_webhook_secret',
    'erb_admin_email', 'erb_email_from_name', 'erb_email_from_address',
);

foreach ( $options as $option ) {
    delete_option( $option );
}
