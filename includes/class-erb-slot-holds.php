<?php if ( ! defined( 'ABSPATH' ) ) exit;
class ERB_Slot_Holds {
    public function hold( $game_id, $slot_start, $session_key ) {
        $minutes = (int) get_option( 'erb_slot_hold_minutes', 15 );
        // Use MySQL DATE_ADD(NOW(), ...) so expiry is always calculated
        // in the same timezone as the NOW() used in lookup queries.
        // This avoids PHP/MySQL timezone mismatches.
        ERB_DB::upsert_hold( $game_id, $slot_start, $session_key, $minutes );
        // Return expiry time for the JS timer using WordPress time
        return gmdate( 'Y-m-d H:i:s', strtotime( "+{$minutes} minutes", current_time('timestamp') ) );
    }
    public function release( $game_id, $slot_start ) {
        ERB_DB::delete_hold( $game_id, $slot_start );
    }
    public function cleanup_expired() {
        ERB_DB::delete_expired_holds();
    }
    public function verify( $game_id, $slot_start, $session_key ) {
        return (bool) ERB_DB::get_hold_by_session( $game_id, $slot_start, $session_key );
    }
}
