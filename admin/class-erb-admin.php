<?php
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
if ( ! defined( 'ABSPATH' ) ) exit;

class ERB_Admin {

    // ─── Admin Menu ───────────────────────────────────────────────────────────

    public function register_admin_menu() {
        add_menu_page( __( 'Escape Room Booking', 'ettrick-escape-room-booking' ), __( 'Escape Rooms', 'ettrick-escape-room-booking' ), 'manage_options', 'erb-dashboard', array( $this, 'page_dashboard' ), 'dashicons-calendar-alt', 30 );
        add_submenu_page( 'erb-dashboard', __( 'Dashboard',        'ettrick-escape-room-booking' ), __( 'Dashboard',              'ettrick-escape-room-booking' ), 'manage_options', 'erb-dashboard', array( $this, 'page_dashboard' ) );
        add_submenu_page( 'erb-dashboard', __( 'Games',            'ettrick-escape-room-booking' ), __( 'Games',                  'ettrick-escape-room-booking' ), 'manage_options', 'erb-games',     array( $this, 'page_games' ) );
        add_submenu_page( 'erb-dashboard', __( 'Bookings',         'ettrick-escape-room-booking' ), __( 'Bookings',               'ettrick-escape-room-booking' ), 'manage_options', 'erb-bookings',  array( $this, 'page_bookings' ) );
        add_submenu_page( 'erb-dashboard', __( 'Customers',        'ettrick-escape-room-booking' ), __( 'Customers',              'ettrick-escape-room-booking' ), 'manage_options', 'erb-customers', array( $this, 'page_customers' ) );
        add_submenu_page( 'erb-dashboard', __( 'Settings',         'ettrick-escape-room-booking' ), __( 'Settings',               'ettrick-escape-room-booking' ), 'manage_options', 'erb-settings',  array( $this, 'page_settings' ) );
        add_submenu_page( 'erb-dashboard', __( 'Upgrade to Pro',   'ettrick-escape-room-booking' ), __( 'Upgrade to Pro &#x1F680;', 'ettrick-escape-room-booking' ), 'manage_options', 'erb-upgrade',   array( $this, 'page_upgrade' ) );
    }

    // ─── Settings ─────────────────────────────────────────────────────────────

    public function register_settings() {
        foreach ( array(
            'erb_currency','erb_currency_symbol','erb_slot_hold_minutes',
            'erb_slot_available_color','erb_slot_booked_color','erb_stripe_mode',
            'erb_stripe_test_pk','erb_stripe_test_sk','erb_stripe_live_pk','erb_stripe_live_sk',
            'erb_stripe_webhook_secret','erb_admin_email','erb_email_from_name','erb_email_from_address','erb_booking_page_url','erb_manage_page_url','erb_calendar_home_url','erb_date_format',
        ) as $key ) {
            register_setting( 'erb_settings_group', $key, array( 'sanitize_callback' => 'sanitize_text_field' ) );
        }
    }

    // ─── Assets ───────────────────────────────────────────────────────────────

    public function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'erb-' ) === false && $hook !== 'toplevel_page_erb-dashboard' ) return;
        wp_enqueue_style(  'erb-admin', ERB_PLUGIN_URL . 'admin/css/erb-admin.css', array(), ERB_VERSION );
        wp_enqueue_script( 'erb-admin', ERB_PLUGIN_URL . 'admin/js/erb-admin.js',  array( 'jquery' ), ERB_VERSION, true );
        wp_localize_script( 'erb-admin', 'erbAdmin', array(
            'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
            'nonce'          => wp_create_nonce( 'erb_admin_nonce' ),
            'currencySymbol' => get_option( 'erb_currency_symbol', '£' ),
        ) );
        if ( strpos( $hook, 'erb-games' ) !== false ) {
            wp_enqueue_script( 'erb-games', ERB_PLUGIN_URL . 'admin/js/erb-games.js', array( 'erb-admin' ), ERB_VERSION, true );
        }
    }

    // ─── Pages ────────────────────────────────────────────────────────────────

    public function page_dashboard()   { include ERB_PLUGIN_DIR . 'admin/views/dashboard.php'; }
    public function page_games()       { include ERB_PLUGIN_DIR . 'admin/views/games.php'; }
    public function page_bookings()    { include ERB_PLUGIN_DIR . 'admin/views/bookings.php'; }
    public function page_customers()   { include ERB_PLUGIN_DIR . 'admin/views/customers.php'; }
    public function page_settings()    { include ERB_PLUGIN_DIR . 'admin/views/settings.php'; }
    public function page_upgrade()     { include ERB_PLUGIN_DIR . 'admin/views/upgrade.php'; }

    // ─── AJAX: Rooms ──────────────────────────────────────────────────────────

    public function ajax_save_room() {
        ERB_Helpers::verify_nonce( $_POST['nonce'] ?? '', 'erb_admin_nonce' );
        if ( ! current_user_can( 'manage_options' ) ) ERB_Helpers::json_error( 'Unauthorised', 403 );
        $name = sanitize_text_field( wp_unslash( $_POST['name'] ) );
        if ( empty( $name ) ) ERB_Helpers::json_error( __( 'Room name is required.', 'ettrick-escape-room-booking' ) );
        $data = array( 'name' => $name, 'description' => sanitize_text_field( wp_unslash( $_POST['description'] ) ) );
        if ( ! empty( $_POST['id'] ) ) $data['id'] = (int) $_POST['id'];
        $id = ERB_DB::upsert_room( $data );
        ERB_Helpers::json_success( array( 'id' => $id ) );
    }

    public function ajax_delete_room() {
        ERB_Helpers::verify_nonce( $_POST['nonce'] ?? '', 'erb_admin_nonce' );
        if ( ! current_user_can( 'manage_options' ) ) ERB_Helpers::json_error( 'Unauthorised', 403 );
        $id = (int) ( $_POST['id'] ?? 0 );
        if ( ! $id ) ERB_Helpers::json_error( 'Invalid ID' );
        global $wpdb; $wpdb->delete( $wpdb->prefix . 'erb_rooms', array( 'id' => $id ) );
        ERB_Helpers::json_success();
    }

    // ─── AJAX: Games ──────────────────────────────────────────────────────────

    public function ajax_save_game() {
        ERB_Helpers::verify_nonce( $_POST['nonce'] ?? '', 'erb_admin_nonce' );
        if ( ! current_user_can( 'manage_options' ) ) ERB_Helpers::json_error( 'Unauthorised', 403 );
        // Lite version: enforce 2-game limit
        if ( defined( 'ERB_LITE' ) ) {
            global $wpdb;
            $game_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}erb_games" );
            $is_new     = empty( $_POST['id'] ) || ! ERB_DB::get_game( (int) $_POST['id'] );
            if ( $is_new && $game_count >= 2 ) {
                ERB_Helpers::json_error( 'You have reached the 2-game limit of the free version. Upgrade to Pro for unlimited games.' );
            }
        }
        $name = sanitize_text_field( wp_unslash( $_POST['name'] ) );
        if ( empty( $name ) ) ERB_Helpers::json_error( __( 'Game name is required.', 'ettrick-escape-room-booking' ) );
        if ( empty( $_POST['room_id'] ) ) ERB_Helpers::json_error( __( 'Please select a physical room.', 'ettrick-escape-room-booking' ) );
        $data = array(
            'room_id'              => (int) $_POST['room_id'],
            'name'                 => $name,
            'slug'                 => sanitize_title( $_POST['slug'] ?? $name ),
            'description'          => sanitize_textarea_field( $_POST['description'] ?? '' ),
            'image_url'            => esc_url_raw( $_POST['image_url'] ?? '' ),
            'duration_minutes'     => max( 15, (int) ( $_POST['duration_minutes'] ?? 60 ) ),
            'setup_minutes'        => max( 0,  (int) ( $_POST['setup_minutes'] ?? 30 ) ),
            'min_notice_hours'     => max( 0,  (int) ( $_POST['min_notice_hours'] ?? 2 ) ),
            'booking_horizon_date' => ! empty( $_POST['booking_horizon_date'] ) ? sanitize_text_field( wp_unslash( $_POST['booking_horizon_date'] ) ) : null,
            'status'               => in_array( $_POST['status'] ?? '', array( 'active','inactive' ) ) ? $_POST['status'] : 'active',
        );
        if ( ! empty( $_POST['id'] ) ) $data['id'] = (int) $_POST['id'];
        $id = ERB_DB::upsert_game( $data );
        ERB_Helpers::json_success( array( 'id' => $id ) );
    }

    public function ajax_delete_game() {
        ERB_Helpers::verify_nonce( $_POST['nonce'] ?? '', 'erb_admin_nonce' );
        if ( ! current_user_can( 'manage_options' ) ) ERB_Helpers::json_error( 'Unauthorised', 403 );
        $id = (int) ( $_POST['id'] ?? 0 );
        if ( ! $id ) ERB_Helpers::json_error( 'Invalid ID' );
        global $wpdb; $wpdb->delete( $wpdb->prefix . 'erb_games', array( 'id' => $id ) );
        ERB_Helpers::json_success();
    }

    public function ajax_get_game() {
        ERB_Helpers::verify_nonce( $_POST['nonce'] ?? '', 'erb_admin_nonce' );
        $id = (int) ( $_POST['id'] ?? 0 );
        $game = ERB_DB::get_game( $id );
        if ( ! $game ) ERB_Helpers::json_error( 'Not found', 404 );
        $game->hours  = ERB_DB::get_game_hours( $id );
        $game->prices = ERB_DB::get_prices( $id );
        ERB_Helpers::json_success( $game );
    }

    // ─── AJAX: Hours ──────────────────────────────────────────────────────────

    public function ajax_save_hours() {
        ERB_Helpers::verify_nonce( $_POST['nonce'] ?? '', 'erb_admin_nonce' );
        if ( ! current_user_can( 'manage_options' ) ) ERB_Helpers::json_error( 'Unauthorised', 403 );
        $game_id = (int) ( $_POST['game_id'] ?? 0 );
        if ( ! $game_id ) ERB_Helpers::json_error( 'Invalid game ID' );
        $hours = array();
        foreach ( ( $_POST['hours'] ?? array() ) as $day => $h ) {
            $hours[ (int) $day ] = array(
                'open_time'  => sanitize_text_field( $h['open_time']  ?? '' ),
                'close_time' => sanitize_text_field( $h['close_time'] ?? '' ),
                'is_closed'  => ! empty( $h['is_closed'] ) ? 1 : 0,
            );
        }
        ERB_DB::save_game_hours( $game_id, $hours );
        ERB_Helpers::json_success();
    }

    // ─── AJAX: Pricing ────────────────────────────────────────────────────────

    public function ajax_save_pricing() {
        ERB_Helpers::verify_nonce( $_POST['nonce'] ?? '', 'erb_admin_nonce' );
        if ( ! current_user_can( 'manage_options' ) ) ERB_Helpers::json_error( 'Unauthorised', 403 );
        $game_id = (int) ( $_POST['game_id'] ?? 0 );
        if ( ! $game_id ) ERB_Helpers::json_error( 'Invalid game ID' );
        $prices = array();
        foreach ( ( $_POST['prices'] ?? array() ) as $players => $price_pounds ) {
            $players     = (int) $players;
            $price_pence = (int) round( (float) $price_pounds * 100 );
            if ( $players >= 2 && $players <= 8 && $price_pence > 0 ) $prices[ $players ] = $price_pence;
        }
        ERB_DB::save_prices( $game_id, $prices );
        ERB_Helpers::json_success();
    }

    // ─── AJAX: Bookings ──────────────────────────────────────────────────────────

    public function ajax_admin_get_booking() {
        ERB_Helpers::verify_nonce( $_POST['nonce'] ?? '', 'erb_admin_nonce' );
        if ( ! current_user_can( 'manage_options' ) ) ERB_Helpers::json_error( 'Unauthorised', 403 );
        $id      = (int) ( $_POST['id'] ?? 0 );
        $booking = ERB_DB::get_booking( $id );
        if ( ! $booking ) ERB_Helpers::json_error( 'Not found', 404 );
        ERB_Helpers::json_success( $booking );
    }

    public function ajax_admin_cancel_booking() {
        ERB_Helpers::verify_nonce( $_POST['nonce'] ?? '', 'erb_admin_nonce' );
        if ( ! current_user_can( 'manage_options' ) ) ERB_Helpers::json_error( 'Unauthorised', 403 );
        $id      = (int) ( $_POST['id'] ?? 0 );
        $booking = ERB_DB::get_booking( $id );
        if ( ! $booking ) ERB_Helpers::json_error( 'Not found', 404 );
        ERB_DB::update_booking( $id, array( 'status' => 'cancelled', 'updated_at' => current_time( 'mysql' ) ) );
        ERB_DB::add_booking_history( array(
            'booking_id' => $id, 'action' => 'cancelled',
            'changed_by' => 'admin', 'created_at' => current_time( 'mysql' ),
        ) );
        $emails = new ERB_Emails();
        $emails->send_cancellation( ERB_DB::get_booking( $id ) );
        ERB_Helpers::json_success();
    }

    // ─── AJAX: Blocked slots (admin calendar management) ─────────────────────

    public function ajax_block_slot()        { ERB_Helpers::verify_nonce( $_POST['nonce'] ?? '', 'erb_admin_nonce' ); ERB_Helpers::json_success(); }
    public function ajax_unblock_slot()      { ERB_Helpers::verify_nonce( $_POST['nonce'] ?? '', 'erb_admin_nonce' ); ERB_Helpers::json_success(); }
    public function ajax_save_promo()        { ERB_Helpers::json_error( 'Pro feature.' ); }
    public function ajax_delete_promo()      { ERB_Helpers::json_error( 'Pro feature.' ); }
    public function ajax_save_gamekeeper()   { ERB_Helpers::json_error( 'Pro feature.' ); }
    public function ajax_delete_gamekeeper() { ERB_Helpers::json_error( 'Pro feature.' ); }
    public function ajax_get_bookings()      { ERB_Helpers::verify_nonce( $_POST['nonce'] ?? '', 'erb_admin_nonce' ); ERB_Helpers::json_success( ERB_DB::get_bookings() ); }
    public function ajax_update_booking()    { ERB_Helpers::verify_nonce( $_POST['nonce'] ?? '', 'erb_admin_nonce' ); ERB_Helpers::json_success(); }
}
