<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Centralises all direct database queries.
 * All other classes call through here rather than hitting $wpdb directly.
 */
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// Reason: This class is the dedicated database layer. Caching is handled at the application level.
class ERB_DB {

    private static $wpdb;

    private static function db() {
        global $wpdb;
        return $wpdb;
    }

    // ─── Table name helpers ───────────────────────────────────────────────────

    public static function table( $name ) {
        return self::db()->prefix . 'erb_' . $name;
    }

    // ─── Games ────────────────────────────────────────────────────────────────

    public static function get_games( $active_only = true ) {
        $db     = self::db();
        $table_games = self::table('games');
        $table_rooms = self::table('rooms');
        $where  = $active_only ? "WHERE g.status = 'active'" : '';
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table names from self::table(), not user input
        return $db->get_results( $db->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
            "SELECT g.*, r.name AS room_name
             FROM {$table_games} g
             LEFT JOIN {$table_rooms} r ON r.id = g.room_id
             {$where}
             ORDER BY g.sort_order ASC, g.name ASC"
        ) );
    }

    public static function get_game( $game_id ) {
        $db = self::db();
        return $db->get_row( $db->prepare(
            "SELECT g.*, r.name AS room_name 
             FROM " . self::table('games') . " g
             LEFT JOIN " . self::table('rooms') . " r ON r.id = g.room_id
             WHERE g.id = %d",
            $game_id
        ) );
    }

    public static function get_game_by_slug( $slug ) {
        $db = self::db();
        return $db->get_row( $db->prepare(
            "SELECT * FROM " . self::table('games') . " WHERE slug = %s AND status = 'active'",
            $slug
        ) );
    }

    /** Returns the sibling game that shares the same physical room, or null. */
    public static function get_room_sibling( $game_id ) {
        $db   = self::db();
        $game = self::get_game( $game_id );
        if ( ! $game ) return null;

        return $db->get_row( $db->prepare(
            "SELECT * FROM " . self::table('games') . " 
             WHERE room_id = %d AND id != %d AND status = 'active'",
            $game->room_id,
            $game_id
        ) );
    }

    public static function upsert_game( $data ) {
        $db = self::db();
        if ( ! empty( $data['id'] ) ) {
            $id = (int) $data['id'];
            unset( $data['id'] );
            $db->update( self::table('games'), $data, array( 'id' => $id ) );
            return $id;
        }
        $db->insert( self::table('games'), $data );
        return $db->insert_id;
    }

    // ─── Rooms ────────────────────────────────────────────────────────────────

    public static function get_rooms() {
        return self::db()->get_results( "SELECT * FROM " . self::table('rooms') . " ORDER BY name ASC" );
    }

    public static function upsert_room( $data ) {
        $db = self::db();
        if ( ! empty( $data['id'] ) ) {
            $id = (int) $data['id'];
            unset( $data['id'] );
            $db->update( self::table('rooms'), $data, array( 'id' => $id ) );
            return $id;
        }
        $db->insert( self::table('rooms'), $data );
        return $db->insert_id;
    }

    // ─── Game Hours ───────────────────────────────────────────────────────────

    public static function get_game_hours( $game_id ) {
        $db = self::db();
        return $db->get_results( $db->prepare(
            "SELECT * FROM " . self::table('game_hours') . " WHERE game_id = %d ORDER BY day_of_week ASC",
            $game_id
        ) );
    }

    public static function save_game_hours( $game_id, array $hours_by_day ) {
        $db    = self::db();
        $table = self::table('game_hours');
        foreach ( $hours_by_day as $day => $hours ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table is from self::table(), not user input
            $existing = $db->get_var( $db->prepare(
                "SELECT id FROM $table WHERE game_id = %d AND day_of_week = %d",
                $game_id, $day
            ) );
            $row = array(
                'game_id'     => $game_id,
                'day_of_week' => (int) $day,
                'open_time'   => $hours['open_time']  ?? null,
                'close_time'  => $hours['close_time'] ?? null,
                'is_closed'   => empty( $hours['open_time'] ) ? 1 : 0,
            );
            if ( $existing ) {
                $db->update( $table, $row, array( 'id' => $existing ) );
            } else {
                $db->insert( $table, $row );
            }
        }
    }

    // ─── Prices ───────────────────────────────────────────────────────────────

    public static function get_prices( $game_id ) {
        $db = self::db();
        return $db->get_results( $db->prepare(
            "SELECT * FROM " . self::table('prices') . " WHERE game_id = %d ORDER BY player_count ASC",
            $game_id
        ) );
    }

    public static function get_price( $game_id, $player_count ) {
        $db = self::db();
        return $db->get_var( $db->prepare(
            "SELECT price_pence FROM " . self::table('prices') . " WHERE game_id = %d AND player_count = %d",
            $game_id, $player_count
        ) );
    }

    public static function save_prices( $game_id, array $prices ) {
        $db    = self::db();
        $table = self::table('prices');
        foreach ( $prices as $player_count => $price_pence ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table is from self::table(), not user input
            $existing = $db->get_var( $db->prepare(
                "SELECT id FROM $table WHERE game_id = %d AND player_count = %d",
                $game_id, $player_count
            ) );
            $row = array(
                'game_id'      => $game_id,
                'player_count' => (int) $player_count,
                'price_pence'  => (int) $price_pence,
            );
            if ( $existing ) {
                $db->update( $table, $row, array( 'id' => $existing ) );
            } else {
                $db->insert( $table, $row );
            }
        }
    }

    // ─── Blocked Slots ────────────────────────────────────────────────────────

    public static function get_blocked_slots( $game_id, $date_from, $date_to ) {
        $db = self::db();
        return $db->get_results( $db->prepare(
            "SELECT * FROM " . self::table('blocked_slots') . "
             WHERE game_id = %d AND slot_start < %s
AND slot_end > %s",
$game_id, $date_to, $date_from
        ) );
    }

    public static function add_blocked_slot( $data ) {
        self::db()->insert( self::table('blocked_slots'), $data );
        return self::db()->insert_id;
    }

    public static function delete_blocked_slot( $id ) {
        self::db()->delete( self::table('blocked_slots'), array( 'id' => (int) $id ) );
    }

    // ─── Bookings ─────────────────────────────────────────────────────────────

    public static function get_bookings( $args = array() ) {
        $db      = self::db();
        $where   = array( '1=1' );
        $prepare = array();

        if ( ! empty( $args['game_id'] ) ) {
            $where[]   = 'b.game_id = %d';
            $prepare[] = $args['game_id'];
        }
        if ( ! empty( $args['status'] ) ) {
            $where[]   = 'b.status = %s';
            $prepare[] = $args['status'];
        }
        if ( ! empty( $args['date_from'] ) ) {
            $where[]   = 'b.slot_start >= %s';
            $prepare[] = $args['date_from'];
        }
        if ( ! empty( $args['date_to'] ) ) {
            $where[]   = 'b.slot_start <= %s';
            $prepare[] = $args['date_to'];
        }
        if ( ! empty( $args['search'] ) ) {
            $where[]   = '(b.booking_ref LIKE %s OR c.email LIKE %s OR c.last_name LIKE %s)';
            $s         = '%' . $db->esc_like( $args['search'] ) . '%';
            $prepare[] = $s;
            $prepare[] = $s;
            $prepare[] = $s;
        }

        $where_sql = implode( ' AND ', $where );
        $limit_sql = '';
        if ( ! empty( $args['limit'] ) ) {
            $limit_sql = ' LIMIT ' . (int) $args['limit'];
            if ( ! empty( $args['offset'] ) ) {
                $limit_sql .= ' OFFSET ' . (int) $args['offset'];
            }
        }

        $t_bookings  = self::table('bookings');
        $t_customers = self::table('customers');
        $t_games     = self::table('games');

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
        // Reason: table names from self::table() are safe; $where_sql built from literals and %placeholders; $limit_sql uses (int) cast
        if ( ! empty( $prepare ) ) {
            return $db->get_results( $db->prepare(
                "SELECT b.*, c.first_name, c.last_name, c.email, c.mobile, g.name AS game_name
                 FROM {$t_bookings} b
                 LEFT JOIN {$t_customers} c ON c.id = b.customer_id
                 LEFT JOIN {$t_games} g ON g.id = b.game_id
                 WHERE {$where_sql}
                 ORDER BY b.created_at DESC{$limit_sql}",
                ...$prepare
            ) );
        }
        return $db->get_results(
            "SELECT b.*, c.first_name, c.last_name, c.email, c.mobile, g.name AS game_name
             FROM {$t_bookings} b
             LEFT JOIN {$t_customers} c ON c.id = b.customer_id
             LEFT JOIN {$t_games} g ON g.id = b.game_id
             WHERE {$where_sql}
             ORDER BY b.created_at DESC{$limit_sql}"
        );
        // phpcs:enable
    }

    public static function get_booking( $booking_id ) {
        $db = self::db();
        return $db->get_row( $db->prepare(
            "SELECT b.*, c.first_name, c.last_name, c.email, c.mobile, g.name AS game_name, g.slug AS game_slug
             FROM " . self::table('bookings') . " b
             LEFT JOIN " . self::table('customers') . " c ON c.id = b.customer_id
             LEFT JOIN " . self::table('games') . " g ON g.id = b.game_id
             WHERE b.id = %d",
            $booking_id
        ) );
    }

    public static function get_booking_by_token( $token ) {
        $db = self::db();
        return $db->get_row( $db->prepare(
            "SELECT b.*, c.first_name, c.last_name, c.email, c.mobile, g.name AS game_name, g.slug AS game_slug
             FROM " . self::table('bookings') . " b
             LEFT JOIN " . self::table('customers') . " c ON c.id = b.customer_id
             LEFT JOIN " . self::table('games') . " g ON g.id = b.game_id
             WHERE b.manage_token = %s",
            $token
        ) );
    }

    public static function get_booked_slots( $game_id, $date_from, $date_to ) {
        $db = self::db();
        return $db->get_results( $db->prepare(
            "SELECT slot_start, slot_end FROM " . self::table('bookings') . "
             WHERE game_id = %d
               AND status IN ('confirmed','pending')
               AND slot_start >= %s
               AND slot_start <= %s",
            $game_id, $date_from, $date_to
        ) );
    }

    public static function insert_booking( $data ) {
        self::db()->insert( self::table('bookings'), $data );
        return self::db()->insert_id;
    }

    public static function update_booking( $id, $data ) {
        self::db()->update( self::table('bookings'), $data, array( 'id' => (int) $id ) );
    }

    public static function add_booking_history( $data ) {
        self::db()->insert( self::table('booking_history'), $data );
    }

    // ─── Slot Holds ───────────────────────────────────────────────────────────

    public static function get_held_slots( $game_id, $date_from, $date_to ) {
        $db = self::db();
        return $db->get_results( $db->prepare(
            "SELECT slot_start FROM " . self::table('slot_holds') . "
             WHERE game_id = %d AND expires_at > NOW() AND slot_start >= %s AND slot_start <= %s",
            $game_id, $date_from, $date_to
        ) );
    }

    public static function get_hold_by_session( $game_id, $slot_start, $session_key ) {
        $db = self::db();
        return $db->get_row( $db->prepare(
            "SELECT * FROM " . self::table('slot_holds') . "
             WHERE game_id = %d AND slot_start = %s AND session_key = %s AND expires_at > NOW()",
            $game_id, $slot_start, $session_key
        ) );
    }

    public static function get_any_active_hold( $game_id, $slot_start ) {
        $db = self::db();
        return $db->get_row( $db->prepare(
            "SELECT * FROM " . self::table('slot_holds') . "
             WHERE game_id = %d AND slot_start = %s AND expires_at > NOW()
             ORDER BY expires_at DESC LIMIT 1",
            $game_id, $slot_start
        ) );
    }

    public static function insert_hold( $data ) {
        $db    = self::db();
        $table = self::table('slot_holds');
        $db->query( $db->prepare(
            "REPLACE INTO {$table} (game_id, slot_start, session_key, expires_at)
             VALUES (%d, %s, %s, %s)",
            $data['game_id'],
            $data['slot_start'],
            $data['session_key'],
            $data['expires_at']
        ) );
        return $db->insert_id;
    }

    public static function upsert_hold( $game_id, $slot_start, $session_key, $minutes ) {
        $db    = self::db();
        $table = self::table('slot_holds');
        // Calculate expires_at using MySQL NOW() so it matches the timezone
        // used in all lookup queries — avoids PHP/MySQL timezone mismatches
        $db->query( $db->prepare(
            "REPLACE INTO {$table} (game_id, slot_start, session_key, expires_at)
             VALUES (%d, %s, %s, DATE_ADD(NOW(), INTERVAL %d MINUTE))",
            $game_id, $slot_start, $session_key, $minutes
        ) );
        return $db->insert_id;
    }

    public static function delete_hold( $game_id, $slot_start ) {
        self::db()->delete( self::table('slot_holds'), array(
            'game_id'    => $game_id,
            'slot_start' => $slot_start,
        ) );
    }

    public static function delete_expired_holds() {
        self::db()->query( "DELETE FROM " . self::table('slot_holds') . " WHERE expires_at <= NOW()" );
    }

    // ─── Customers ────────────────────────────────────────────────────────────

    public static function get_customer_by_email( $email ) {
        $db = self::db();
        return $db->get_row( $db->prepare(
            "SELECT * FROM " . self::table('customers') . " WHERE email = %s",
            $email
        ) );
    }

    public static function get_customer( $id ) {
        $db = self::db();
        return $db->get_row( $db->prepare(
            "SELECT * FROM " . self::table('customers') . " WHERE id = %d",
            $id
        ) );
    }

    public static function insert_customer( $data ) {
        self::db()->insert( self::table('customers'), $data );
        return self::db()->insert_id;
    }

    public static function update_customer( $id, $data ) {
        self::db()->update( self::table('customers'), $data, array( 'id' => (int) $id ) );
    }

    // ─── Promo Codes ─────────────────────────────────────────────────────────

    public static function get_promo_codes() {
        return self::db()->get_results(
            "SELECT * FROM " . self::table('promo_codes') . " ORDER BY created_at DESC"
        );
    }

    public static function get_promo_code( $code ) {
        $db = self::db();
        return $db->get_row( $db->prepare(
            "SELECT * FROM " . self::table('promo_codes') . " WHERE code = %s AND is_active = 1",
            strtoupper( $code )
        ) );
    }

    public static function increment_promo_use( $promo_id ) {
        self::db()->query( self::db()->prepare(
            "UPDATE " . self::table('promo_codes') . " SET use_count = use_count + 1 WHERE id = %d",
            $promo_id
        ) );
    }

    public static function upsert_promo( $data ) {
        $db = self::db();
        if ( ! empty( $data['id'] ) ) {
            $id = (int) $data['id'];
            unset( $data['id'] );
            $db->update( self::table('promo_codes'), $data, array( 'id' => $id ) );
            return $id;
        }
        $db->insert( self::table('promo_codes'), $data );
        return $db->insert_id;
    }

    public static function delete_promo( $id ) {
        self::db()->delete( self::table('promo_codes'), array( 'id' => (int) $id ) );
    }

    // ─── Gamekeepers ─────────────────────────────────────────────────────────

    public static function get_gamekeepers( $active_only = true ) {
        $where = $active_only ? " WHERE is_active = 1" : "";
        return self::db()->get_results(
            "SELECT * FROM " . self::table('gamekeepers') . "$where ORDER BY name ASC"
        );
    }

    public static function upsert_gamekeeper( $data ) {
        $db = self::db();
        if ( ! empty( $data['id'] ) ) {
            $id = (int) $data['id'];
            unset( $data['id'] );
            $db->update( self::table('gamekeepers'), $data, array( 'id' => $id ) );
            return $id;
        }
        $db->insert( self::table('gamekeepers'), $data );
        return $db->insert_id;
    }

    public static function delete_gamekeeper( $id ) {
        self::db()->delete( self::table('gamekeepers'), array( 'id' => (int) $id ) );
    }

    // ─── Reports ─────────────────────────────────────────────────────────────

    public static function get_revenue_summary( $date_from, $date_to ) {
        $db = self::db();
        return $db->get_results( $db->prepare(
            "SELECT g.name AS game_name,
                    COUNT(b.id)       AS booking_count,
                    SUM(b.total_pence) AS revenue_pence
             FROM " . self::table('bookings') . " b
             LEFT JOIN " . self::table('games') . " g ON g.id = b.game_id
             WHERE b.status = 'confirmed'
               AND b.slot_start BETWEEN %s AND %s
             GROUP BY b.game_id
             ORDER BY revenue_pence DESC",
            $date_from, $date_to
        ) );
    }
}
