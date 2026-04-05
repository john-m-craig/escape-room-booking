<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ERB_Games {
    public function get_all( $active_only = true ) { return ERB_DB::get_games( $active_only ); }
    public function get( $id ) { return ERB_DB::get_game( $id ); }
    public function get_by_slug( $slug ) { return ERB_DB::get_game_by_slug( $slug ); }
    public function get_sibling( $game_id ) { return ERB_DB::get_room_sibling( $game_id ); }
}
