<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ERB_Bookings {
    public function get_all( $args = array() ) { return ERB_DB::get_bookings( $args ); }
    public function get( $id ) { return ERB_DB::get_booking( $id ); }
    public function get_by_token( $token ) { return ERB_DB::get_booking_by_token( $token ); }
    public function create( $data ) { return ERB_DB::insert_booking( $data ); }
    public function update( $id, $data ) { ERB_DB::update_booking( $id, $data ); }
    public function log_history( $data ) { ERB_DB::add_booking_history( $data ); }
}
