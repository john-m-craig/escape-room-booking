<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Runs on plugin activation.
 * Creates all custom database tables and sets default options.
 */
class ERB_Activator {

    /**
     * Called on every plugin load when version changes.
     * Safe to run repeatedly — only updates values that need changing.
     */
    public static function upgrade() {
        self::create_tables();       // dbDelta handles existing tables safely
        self::set_default_options(); // add_option skips if already set
        self::clear_bad_cached_urls();
        self::fix_date_format();
        flush_rewrite_rules();
    }

    public static function activate() {
        self::create_tables();
        self::set_default_options();
        self::clear_bad_cached_urls();
        self::fix_date_format();
        // Flush rewrite rules so front-end shortcode pages resolve correctly
        flush_rewrite_rules();
    }

    /**
     * Clear any manage page URL that was incorrectly cached as the home URL.
     * This runs on every activation/update so bad values are automatically corrected.
     */
    private static function fix_date_format() {
        $current = get_option( 'erb_date_format', null );

        // Option has never been set at all — initialise to j F Y
        if ( $current === null || $current === false ) {
            add_option( 'erb_date_format', 'j F Y' );
            return;
        }

        // Option exists but looks like a US/ambiguous format that was
        // auto-inherited from the WP global setting — replace with j F Y.
        // Formats we consider "not deliberately set by admin":
        $us_formats = array( 'F j, Y', 'n/j/Y', 'm/d/Y', 'n/j/y', 'm/d/y' );
        if ( in_array( $current, $us_formats, true ) ) {
            update_option( 'erb_date_format', 'j F Y' );
        }
        // If admin has set any other value (including d/m/Y, j F Y, etc.) — leave it alone
    }

    private static function clear_bad_cached_urls() {
        $cached = get_option( 'erb_manage_page_url', '' );
        $home   = trailingslashit( home_url( '/' ) );
        if ( $cached && trailingslashit( $cached ) === $home ) {
            delete_option( 'erb_manage_page_url' );
        }
    }

    // ─── Database Tables ──────────────────────────────────────────────────────

    private static function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // ── rooms ─────────────────────────────────────────────────────────────
        // Physical rooms. Two games can share one room.
        $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}erb_rooms (
            id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name          VARCHAR(100) NOT NULL,
            description   TEXT,
            created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;" );

        // ── games ─────────────────────────────────────────────────────────────
        // Each escape room game. Linked to a physical room.
        $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}erb_games (
            id                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
            room_id               INT UNSIGNED NOT NULL,
            name                  VARCHAR(150) NOT NULL,
            slug                  VARCHAR(150) NOT NULL,
            description           TEXT,
            image_url             VARCHAR(500),
            duration_minutes      SMALLINT UNSIGNED NOT NULL DEFAULT 60,
            setup_minutes         SMALLINT UNSIGNED NOT NULL DEFAULT 30,
            min_players           TINYINT UNSIGNED NOT NULL DEFAULT 2,
            max_players           TINYINT UNSIGNED NOT NULL DEFAULT 8,
            min_notice_hours      SMALLINT UNSIGNED NOT NULL DEFAULT 2,
            booking_horizon_date  DATE,
            status                ENUM('active','inactive') NOT NULL DEFAULT 'active',
            sort_order            SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY room_id (room_id)
        ) $charset;" );

        // ── game_hours ────────────────────────────────────────────────────────
        // Operating hours per game per day of week.
        // day_of_week: 0=Sunday … 6=Saturday (matches PHP gmdate('w'))
        $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}erb_game_hours (
            id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
            game_id      INT UNSIGNED NOT NULL,
            day_of_week  TINYINT UNSIGNED NOT NULL COMMENT '0=Sun,1=Mon,...,6=Sat',
            open_time    TIME,
            close_time   TIME,
            is_closed    TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY game_day (game_id, day_of_week),
            KEY game_id (game_id)
        ) $charset;" );

        // ── prices ────────────────────────────────────────────────────────────
        // Price per player count per game (allows per-game pricing in future).
        $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}erb_prices (
            id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
            game_id      INT UNSIGNED NOT NULL,
            player_count TINYINT UNSIGNED NOT NULL,
            price_pence  INT UNSIGNED NOT NULL COMMENT 'Price in pence (GBP) to avoid float issues',
            PRIMARY KEY (id),
            UNIQUE KEY game_players (game_id, player_count),
            KEY game_id (game_id)
        ) $charset;" );

        // ── blocked_slots ─────────────────────────────────────────────────────
        // Admin-defined blocked timeslots (maintenance, private events, etc.)
        $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}erb_blocked_slots (
            id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
            game_id     INT UNSIGNED NOT NULL,
            slot_start  DATETIME NOT NULL,
            slot_end    DATETIME NOT NULL,
            reason      VARCHAR(255),
            created_by  BIGINT UNSIGNED,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY game_id (game_id),
            KEY slot_start (slot_start)
        ) $charset;" );

        // ── customers ─────────────────────────────────────────────────────────
        // Plugin's own customer account system (separate from WP users).
        $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}erb_customers (
            id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
            first_name    VARCHAR(100) NOT NULL,
            last_name     VARCHAR(100) NOT NULL,
            email         VARCHAR(255) NOT NULL,
            mobile        VARCHAR(30),
            password_hash VARCHAR(255),
            is_guest      TINYINT(1) NOT NULL DEFAULT 0,
            created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_login    DATETIME,
            PRIMARY KEY (id),
            UNIQUE KEY email (email)
        ) $charset;" );

        // ── bookings ──────────────────────────────────────────────────────────
        $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}erb_bookings (
            id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
            booking_ref       VARCHAR(20) NOT NULL COMMENT 'Human-readable reference e.g. ERB-2024-00042',
            game_id           INT UNSIGNED NOT NULL,
            customer_id       INT UNSIGNED NOT NULL,
            slot_start        DATETIME NOT NULL,
            slot_end          DATETIME NOT NULL,
            player_count      TINYINT UNSIGNED NOT NULL,
            price_pence       INT UNSIGNED NOT NULL,
            discount_pence    INT UNSIGNED NOT NULL DEFAULT 0,
            total_pence       INT UNSIGNED NOT NULL,
            promo_code_id     INT UNSIGNED,
            status            ENUM('pending','confirmed','changed','cancelled') NOT NULL DEFAULT 'pending',
            stripe_payment_id VARCHAR(255),
            manage_token      VARCHAR(64) NOT NULL COMMENT 'Unique token for the manage-booking link in email',
            notes             TEXT,
            created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY booking_ref (booking_ref),
            UNIQUE KEY manage_token (manage_token),
            KEY game_id (game_id),
            KEY customer_id (customer_id),
            KEY slot_start (slot_start),
            KEY status (status)
        ) $charset;" );

        // ── booking_history ───────────────────────────────────────────────────
        // Audit trail of every change/cancellation.
        $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}erb_booking_history (
            id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
            booking_id     INT UNSIGNED NOT NULL,
            action         ENUM('created','changed','cancelled','refunded') NOT NULL,
            changed_by     ENUM('customer','admin') NOT NULL DEFAULT 'customer',
            old_slot_start DATETIME,
            new_slot_start DATETIME,
            old_players    TINYINT UNSIGNED,
            new_players    TINYINT UNSIGNED,
            note           TEXT,
            created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY booking_id (booking_id)
        ) $charset;" );

        // ── slot_holds ────────────────────────────────────────────────────────
        // Server-side 15-minute hold while a visitor goes through checkout.
        $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}erb_slot_holds (
            id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
            game_id     INT UNSIGNED NOT NULL,
            slot_start  DATETIME NOT NULL,
            session_key VARCHAR(64) NOT NULL COMMENT 'Random key stored in visitor session',
            expires_at  DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY game_slot (game_id, slot_start),
            KEY expires_at (expires_at)
        ) $charset;" );

        // ── promo_codes ───────────────────────────────────────────────────────
        $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}erb_promo_codes (
            id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
            code                VARCHAR(50) NOT NULL,
            description         VARCHAR(255),
            discount_percent    TINYINT UNSIGNED NOT NULL DEFAULT 10 COMMENT 'e.g. 10 = 10% off',
            valid_from          DATE,
            valid_to            DATE,
            max_uses            SMALLINT UNSIGNED COMMENT 'NULL = unlimited',
            use_count           SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            is_active           TINYINT(1) NOT NULL DEFAULT 1,
            created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY code (code)
        ) $charset;" );

        // ── gamekeepers ───────────────────────────────────────────────────────
        // Staff members who receive booking notification emails.
        $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}erb_gamekeepers (
            id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name       VARCHAR(150) NOT NULL,
            email      VARCHAR(255) NOT NULL,
            is_active  TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY email (email)
        ) $charset;" );

        // Store the DB version for future migrations
        update_option( 'erb_db_version', ERB_VERSION );
    }

    // ─── Default Options ──────────────────────────────────────────────────────

    private static function set_default_options() {
        $defaults = array(
            'erb_currency'              => 'GBP',
            'erb_currency_symbol'       => '£',
            'erb_slot_hold_minutes'     => 15,
            'erb_slot_available_color'  => '#22c55e',
            'erb_slot_booked_color'     => '#ef4444',
            'erb_stripe_mode'           => 'test',   // 'test' or 'live'
            'erb_stripe_test_pk'        => '',
            'erb_stripe_test_sk'        => '',
            'erb_stripe_live_pk'        => '',
            'erb_stripe_live_sk'        => '',
            'erb_stripe_webhook_secret' => '',
            'erb_admin_email'           => get_option( 'admin_email' ),
            'erb_email_from_name'       => get_option( 'blogname' ),
            'erb_email_from_address'    => get_option( 'admin_email' ),
            'erb_booking_page_url'      => '',
            'erb_manage_page_url'       => '',
            'erb_calendar_home_url'     => '',
            'erb_date_format'           => get_option( 'erb_date_format', 'j F Y' ),
        );

        foreach ( $defaults as $key => $value ) {
            if ( false === get_option( $key ) ) {
                add_option( $key, $value );
            }
        }
    }
}
