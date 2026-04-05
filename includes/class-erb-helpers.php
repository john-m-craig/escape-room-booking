<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Static utility helpers used across the plugin.
 */
class ERB_Helpers {

    /**
     * Format a price in pence to a localised currency string.
     * e.g. 10500 → "£105.00"
     */
    public static function format_price( $pence ) {
        $symbol = get_option( 'erb_currency_symbol', '£' );
        return $symbol . number_format( $pence / 100, 2 );
    }

    /**
     * Generate a unique booking reference: ERB-YYYY-NNNNN
     */
    public static function generate_booking_ref() {
        global $wpdb;
        $year  = gmdate( 'Y' );
        $table = $wpdb->prefix . 'erb_bookings';
        $last  = $wpdb->get_var( "SELECT MAX(id) FROM $table" );
        $seq   = str_pad( ( (int) $last ) + 1, 5, '0', STR_PAD_LEFT );
        return "ERB-{$year}-{$seq}";
    }

    /**
     * Generate a cryptographically random token (for manage-booking links).
     */
    public static function generate_token( $bytes = 32 ) {
        return bin2hex( random_bytes( $bytes ) );
    }

    /**
     * Generate a random session key for slot holds.
     */
    public static function generate_session_key() {
        return self::generate_token( 24 );
    }

    /**
     * Return a sanitised datetime string for DB storage.
     */
    public static function sanitize_datetime( $value ) {
        $ts = strtotime( $value );
        return $ts ? gmdate( 'Y-m-d H:i:s', $ts ) : null;
    }

    /**
     * Check if a slot_start datetime is within the min-notice window for a game.
     */
    public static function is_within_min_notice( $game, $slot_start_dt ) {
        $notice_seconds = (int) $game->min_notice_hours * 3600;
        return ( strtotime( $slot_start_dt ) - time() ) < $notice_seconds;
    }

    /**
     * Check if a slot_start date is beyond the game's booking horizon.
     */
    public static function is_beyond_horizon( $game, $slot_start_dt ) {
        if ( empty( $game->booking_horizon_date ) ) return false;
        return gmdate( 'Y-m-d', strtotime( $slot_start_dt ) ) > $game->booking_horizon_date;
    }

    /**
     * Generate all time slots for a given game on a given date.
     * Returns array of ['start' => 'H:i', 'end' => 'H:i'] arrays.
     */
    public static function generate_slots_for_day( $game, $hours_row ) {
        if ( ! $hours_row || $hours_row->is_closed ) return array();

        $slot_duration = ( (int) $game->duration_minutes + (int) $game->setup_minutes ) * 60;
        $open  = strtotime( $hours_row->open_time );
        $close = strtotime( $hours_row->close_time );
        $slots = array();

        for ( $t = $open; $t + $slot_duration <= $close; $t += $slot_duration ) {
            $slots[] = array(
                'start' => gmdate( 'H:i', $t ),
                'end'   => gmdate( 'H:i', $t + (int) $game->duration_minutes * 60 ),
            );
        }
        return $slots;
    }

    /**
     * Build the manage-booking URL for a given booking token.
     */
    public static function manage_booking_url( $token ) {
        // 1. Use the explicitly configured URL if set — but ignore it if it's
        //    just the home URL (means auto-discovery previously cached the wrong thing)
        $base     = trim( get_option( 'erb_manage_page_url', '' ) );
        $home     = trailingslashit( home_url( '/' ) );
        if ( trailingslashit( $base ) === $home ) {
            $base = ''; // discard bad cached value
        }

        // 2. Auto-discover the page containing [erb_manage_booking] shortcode
        if ( empty( $base ) ) {
            global $wpdb;
            $page_id = $wpdb->get_var(
                "SELECT ID FROM {$wpdb->posts}
                 WHERE post_status = 'publish'
                   AND post_type = 'page'
                   AND post_content LIKE '%erb_manage_booking%'
                 LIMIT 1"
            );
            if ( $page_id ) {
                $discovered = get_permalink( $page_id );
                // Only cache if it's a real page URL, not just the home URL
                if ( $discovered && trailingslashit( $discovered ) !== $home ) {
                    $base = $discovered;
                    update_option( 'erb_manage_page_url', $base );
                }
            }
        }

        // 3. Final fallback — append token to home URL so at least the token survives
        if ( empty( $base ) ) {
            $base = $home;
        }

        return add_query_arg( array(
            'erb_action' => 'manage',
            'token'      => rawurlencode( $token ),
        ), trailingslashit( $base ) );
    }

    /**
     * Returns the best available URL for "go browse games / book again" buttons.
     * Uses the configured Calendar Home URL, auto-discovers a calendar page, or falls back to home.
     */
    public static function get_browse_url() {
        $url = trim( get_option( 'erb_calendar_home_url', '' ) );
        if ( $url ) return $url;

        // Auto-discover a page containing [erb_calendar]
        global $wpdb;
        $page_id = $wpdb->get_var(
            "SELECT ID FROM {$wpdb->posts}
             WHERE post_status = 'publish'
               AND post_type   = 'page'
               AND post_content LIKE '%erb_calendar%'
             LIMIT 1"
        );
        if ( $page_id ) {
            $found = get_permalink( $page_id );
            if ( $found ) {
                update_option( 'erb_calendar_home_url', $found );
                return $found;
            }
        }

        return home_url( '/' );
    }

    /**
     * Send a JSON success response and exit.
     */
    public static function json_success( $data = array() ) {
        wp_send_json_success( $data );
    }

    /**
     * Send a JSON error response and exit.
     */
    public static function json_error( $message, $code = 400 ) {
        wp_send_json_error( array( 'message' => $message ), $code );
    }

    /**
     * Verify a nonce and die on failure.
     */
    public static function verify_nonce( $nonce, $action ) {
        if ( ! wp_verify_nonce( $nonce, $action ) ) {
            self::json_error( __( 'Security check failed.', 'escape-room-booking' ), 403 );
        }
    }

    /**
     * Get or create the visitor's session key for slot holds.
     * Stored in a PHP session.
     */
    public static function get_session_key() {
        if ( ! session_id() ) {
            session_start();
        }
        if ( empty( $_SESSION['erb_session_key'] ) ) {
            $_SESSION['erb_session_key'] = self::generate_session_key();
        }
        return $_SESSION['erb_session_key'];
    }
}
