<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
<div class="wrap erb-admin-page">
    <div style="background:linear-gradient(135deg,#1e3a5f,#234168);border-radius:10px;padding:20px 24px;margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;gap:16px;">
        <div>
            <p style="color:rgba(255,255,255,.6);font-size:.78rem;text-transform:uppercase;letter-spacing:.07em;font-weight:700;margin:0 0 4px;"><?php esc_html_e( 'You are using the Free version', 'escape-room-booking' ); ?></p>
            <p style="color:#fff;font-size:.95rem;margin:0;"><?php esc_html_e( 'Upgrade to Pro for unlimited games, promo codes, reports and more.', 'escape-room-booking' ); ?></p>
        </div>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=erb-upgrade' ) ); ?>"
           style="background:#e8621a;color:#fff;text-decoration:none;padding:10px 20px;border-radius:6px;font-size:.875rem;font-weight:600;white-space:nowrap;flex-shrink:0;">
            &#x1F680; <?php esc_html_e( 'Upgrade to Pro', 'escape-room-booking' ); ?>
        </a>
    </div>
    <h1><?php esc_html_e( 'Escape Room Booking — Dashboard', 'escape-room-booking' ); ?></h1>

    <?php
    // Warn about bookings stuck in pending for more than 30 minutes (possible webhook miss)
    global $wpdb;
    $stale = $wpdb->get_results(
        "SELECT b.booking_ref, b.created_at, c.email
         FROM {$wpdb->prefix}erb_bookings b
         LEFT JOIN {$wpdb->prefix}erb_customers c ON c.id = b.customer_id
         WHERE b.status = 'pending'
         AND b.created_at < DATE_SUB(NOW(), INTERVAL 30 MINUTE)
         ORDER BY b.created_at DESC LIMIT 10"
    );
    if ( ! empty( $stale ) ) : ?>
    <div class="erb-notice erb-notice--error">
        <strong><?php esc_html_e( 'Attention:', 'escape-room-booking' ); ?></strong>
        <?php echo count( $stale ); ?> <?php esc_html_e( 'booking(s) are stuck in Pending status — payment may not have been confirmed via webhook. Check your', 'escape-room-booking' ); ?>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=erb-settings' ) ); ?>"><?php esc_html_e( 'webhook settings', 'escape-room-booking' ); ?></a>
        <?php esc_html_e( 'and verify the Stripe webhook endpoint is active.', 'escape-room-booking' ); ?>
        <ul style="margin:.5rem 0 0 1rem;font-size:.85rem;">
            <?php foreach ( $stale as $s ) : ?>
            <li><?php echo esc_html( $s->booking_ref ); ?> — <?php echo esc_html( $s->email ); ?> — <?php echo esc_html( $s->created_at ); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php
    $games          = ERB_DB::get_games( false );
    $today          = gmdate( 'Y-m-d' );
    $bookings_today = ERB_DB::get_bookings( array( 'date_from' => $today . ' 00:00:00', 'date_to' => $today . ' 23:59:59', 'status' => 'confirmed' ) );
    $bookings_all   = ERB_DB::get_bookings( array( 'status' => 'confirmed' ) );
    $revenue_all    = array_sum( array_column( $bookings_all, 'total_pence' ) );
    ?>

    <div class="erb-stats-grid">
        <div class="erb-stat-box">
            <div class="erb-stat-box__value"><?php echo count( $games ); ?></div>
            <div class="erb-stat-box__label"><?php esc_html_e( 'Games', 'escape-room-booking' ); ?></div>
        </div>
        <div class="erb-stat-box">
            <div class="erb-stat-box__value"><?php echo count( $bookings_today ); ?></div>
            <div class="erb-stat-box__label"><?php esc_html_e( "Today's Bookings", 'escape-room-booking' ); ?></div>
        </div>
        <div class="erb-stat-box">
            <div class="erb-stat-box__value"><?php echo count( $bookings_all ); ?></div>
            <div class="erb-stat-box__label"><?php esc_html_e( 'Total Bookings', 'escape-room-booking' ); ?></div>
        </div>
        <div class="erb-stat-box">
            <div class="erb-stat-box__value"><?php echo esc_html( ERB_Helpers::format_price( $revenue_all ) ); ?></div>
            <div class="erb-stat-box__label"><?php esc_html_e( 'Total Revenue', 'escape-room-booking' ); ?></div>
        </div>
    </div>

    <?php if ( empty( $games ) ) : ?>
    <div class="erb-notice erb-notice--info">
        <?php esc_html_e( 'Welcome! Start by adding your physical rooms under Games, then create your games.', 'escape-room-booking' ); ?>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=erb-games' ) ); ?>" class="erb-btn erb-btn--primary erb-btn--sm" style="margin-left:1rem;">
            <?php esc_html_e( 'Set Up Games →', 'escape-room-booking' ); ?>
        </a>
    </div>
    <?php endif; ?>

    <?php if ( ! empty( $bookings_today ) ) : ?>
    <div class="erb-card">
        <h2><?php esc_html_e( "Today's Bookings", 'escape-room-booking' ); ?></h2>
        <table class="erb-table">
            <thead><tr>
                <th><?php esc_html_e( 'Ref', 'escape-room-booking' ); ?></th>
                <th><?php esc_html_e( 'Game', 'escape-room-booking' ); ?></th>
                <th><?php esc_html_e( 'Time', 'escape-room-booking' ); ?></th>
                <th><?php esc_html_e( 'Customer', 'escape-room-booking' ); ?></th>
                <th><?php esc_html_e( 'Players', 'escape-room-booking' ); ?></th>
                <th><?php esc_html_e( 'Total', 'escape-room-booking' ); ?></th>
                <th><?php esc_html_e( 'Status', 'escape-room-booking' ); ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ( $bookings_today as $b ) : ?>
                <tr>
                    <td><code><?php echo esc_html( $b->booking_ref ); ?></code></td>
                    <td><?php echo esc_html( $b->game_name ); ?></td>
                    <td><?php echo esc_html( gmdate( 'H:i', strtotime( $b->slot_start ) ) ); ?></td>
                    <td><?php echo esc_html( $b->first_name . ' ' . $b->last_name ); ?></td>
                    <td><?php echo (int) $b->player_count; ?></td>
                    <td><?php echo esc_html( ERB_Helpers::format_price( $b->total_pence ) ); ?></td>
                    <td><span class="erb-badge erb-badge--<?php echo esc_attr( $b->status ); ?>"><?php echo esc_html( $b->status ); ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
