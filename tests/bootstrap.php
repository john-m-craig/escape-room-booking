<?php
/**
 * PHPUnit bootstrap for Escape Room Booking (Lite)
 */

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// WordPress constants
if ( ! defined( 'ABSPATH' ) )       define( 'ABSPATH', '/tmp/wordpress/' );
if ( ! defined( 'ERB_VERSION' ) )   define( 'ERB_VERSION', '1.1.4' );
if ( ! defined( 'ERB_LITE' ) )      define( 'ERB_LITE', true );
if ( ! defined( 'ERB_PLUGIN_DIR' ) ) define( 'ERB_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
if ( ! defined( 'ERB_PLUGIN_URL' ) ) define( 'ERB_PLUGIN_URL', 'https://example.com/wp-content/plugins/escape-room-booking/' );
if ( ! defined( 'WP_DEBUG' ) )      define( 'WP_DEBUG', true );

// Minimal $wpdb stub
global $wpdb;
$wpdb = new class {
    public string $prefix = 'wp_';
    public function get_var( $sql ) { return null; }
    public function get_row( $sql, $output = OBJECT, $y = 0 ) { return null; }
    public function get_results( $sql, $output = OBJECT ) { return []; }
    public function prepare( $sql, ...$args ) { return vsprintf( str_replace( ['%d','%s','%f'], ['%d','%s','%f'], $sql ), $args ); }
    public function insert( $table, $data, $format = null ) { return 1; }
    public function update( $table, $data, $where, $format = null, $where_format = null ) { return 1; }
    public function delete( $table, $where, $format = null ) { return 1; }
    public function query( $sql ) { return 1; }
    public function esc_like( $text ) { return addcslashes( $text, '_%\\' ); }
};

// WordPress function stubs
if ( ! function_exists( 'get_option' ) ) {
    function get_option( $key, $default = '' ) {
        $options = [
            'erb_currency'          => 'GBP',
            'erb_currency_symbol'   => '£',
            'erb_date_format'       => 'j F Y',
            'erb_slot_hold_minutes' => 15,
        ];
        return $options[ $key ] ?? $default;
    }
}
if ( ! function_exists( 'update_option' ) )    { function update_option( $k, $v, $a = true ) { return true; } }
if ( ! function_exists( 'delete_option' ) )    { function delete_option( $k ) { return true; } }
if ( ! function_exists( 'home_url' ) )         { function home_url( $p = '' ) { return 'https://example.com' . $p; } }
if ( ! function_exists( 'add_query_arg' ) )    { function add_query_arg( $a, $u = '' ) { return $u . '?' . http_build_query( $a ); } }
if ( ! function_exists( 'get_posts' ) )        { function get_posts( $a ) { return []; } }
if ( ! function_exists( 'get_post_meta' ) )    { function get_post_meta( $i, $k, $s = false ) { return ''; } }
if ( ! function_exists( 'wp_remote_post' ) )   { function wp_remote_post( $u, $a = [] ) { return []; } }
if ( ! function_exists( 'is_wp_error' ) )      { function is_wp_error( $t ) { return false; } }
if ( ! function_exists( 'sanitize_text_field' ) ) { function sanitize_text_field( $v ) { return trim( strip_tags( $v ) ); } }
if ( ! function_exists( 'wp_unslash' ) )       { function wp_unslash( $v ) { return is_array( $v ) ? array_map( 'stripslashes_deep', $v ) : stripslashes( $v ); } }
if ( ! function_exists( 'absint' ) )           { function absint( $v ) { return abs( (int) $v ); } }
if ( ! function_exists( 'trailingslashit' ) )  { function trailingslashit( $v ) { return rtrim( $v, '/\\' ) . '/'; } }
if ( ! function_exists( 'current_time' ) )     { function current_time( $type ) { return $type === 'timestamp' ? time() : gmdate( 'Y-m-d H:i:s' ); } }
if ( ! function_exists( 'date_i18n' ) )        { function date_i18n( $format, $ts = false ) { return gmdate( $format, $ts ?: time() ); } }
if ( ! function_exists( 'wp_count_posts' ) )   { function wp_count_posts( $type = 'post' ) { return (object)['publish' => 0]; } }
