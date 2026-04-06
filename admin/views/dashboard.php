<?php
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;

// ── Period selector ───────────────────────────────────────────────────────────
$period    = sanitize_text_field( wp_unslash( $_GET['period']    ?? 'this_month' ) );
$date_from = sanitize_text_field( wp_unslash( $_GET['date_from'] ?? '' ) );
$date_to   = sanitize_text_field( wp_unslash( $_GET['date_to']   ?? '' ) );

$today = gmdate( 'Y-m-d' );
$year  = (int) gmdate( 'Y' );
$month = (int) gmdate( 'm' );

switch ( $period ) {
    case 'this_week':
        $from = gmdate( 'Y-m-d', strtotime( 'monday this week' ) );
        $to   = gmdate( 'Y-m-d', strtotime( 'sunday this week' ) );
        $label = __( 'This Week', 'escape-room-booking' );
        break;
    case 'last_month':
        $from  = gmdate( 'Y-m-01', strtotime( 'first day of last month' ) );
        $to    = gmdate( 'Y-m-t',  strtotime( 'last day of last month' ) );
        $label = __( 'Last Month', 'escape-room-booking' );
        break;
    case 'this_year':
        $from  = "{$year}-01-01";
        $to    = "{$year}-12-31";
        $label = __( 'This Year', 'escape-room-booking' );
        break;
    case 'last_year':
        $y     = $year - 1;
        $from  = "{$y}-01-01";
        $to    = "{$y}-12-31";
        $label = __( 'Last Year', 'escape-room-booking' );
        break;
    case 'custom':
        $from  = $date_from ?: $today;
        $to    = $date_to   ?: $today;
        $label = __( 'Custom Range', 'escape-room-booking' );
        break;
    default: // this_month
        $period = 'this_month';
        $from   = gmdate( 'Y-m-01' );
        $to     = gmdate( 'Y-m-t' );
        $label  = __( 'This Month', 'escape-room-booking' );
        break;
}

$from_dt = $from . ' 00:00:00';
$to_dt   = $to   . ' 23:59:59';

// ── Period stats ──────────────────────────────────────────────────────────────
$period_bookings = ERB_DB::get_bookings( array(
    'date_from' => $from_dt,
    'date_to'   => $to_dt,
    'status'    => 'confirmed',
) );
$period_revenue  = array_sum( array_column( $period_bookings, 'total_pence' ) );
$period_players  = array_sum( array_column( $period_bookings, 'player_count' ) );
$period_avg      = count( $period_bookings ) > 0 ? $period_revenue / count( $period_bookings ) : 0;

// ── All time stats ────────────────────────────────────────────────────────────
$total_bookings = ERB_DB::count_bookings( array( 'status' => 'confirmed' ) );
$total_revenue  = (int) $wpdb->get_var( $wpdb->prepare(
    "SELECT SUM(total_pence) FROM {$wpdb->prefix}erb_bookings WHERE status = %s",
    'confirmed'
) );

// ── Today's bookings ──────────────────────────────────────────────────────────
$bookings_today = ERB_DB::get_bookings( array(
    'date_from' => $today . ' 00:00:00',
    'date_to'   => $today . ' 23:59:59',
    'status'    => 'confirmed',
) );

// ── Stale pending bookings ────────────────────────────────────────────────────
$stale = $wpdb->get_results(
    "SELECT b.booking_ref, b.created_at, c.email
     FROM {$wpdb->prefix}erb_bookings b
     LEFT JOIN {$wpdb->prefix}erb_customers c ON c.id = b.customer_id
     WHERE b.status = 'pending'
     AND b.created_at < DATE_SUB(NOW(), INTERVAL 30 MINUTE)
     ORDER BY b.created_at DESC LIMIT 10"
);

// ── 12-month revenue chart data ───────────────────────────────────────────────
$chart_labels  = [];
$chart_values  = [];
for ( $i = 11; $i >= 0; $i-- ) {
    $ts          = strtotime( "-{$i} months", strtotime( gmdate( 'Y-m-01' ) ) );
    $m_from      = gmdate( 'Y-m-01', $ts );
    $m_to        = gmdate( 'Y-m-t',  $ts );
    $chart_labels[] = gmdate( 'M y', $ts );
    $rev         = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COALESCE(SUM(total_pence),0) FROM {$wpdb->prefix}erb_bookings
         WHERE status = 'confirmed' AND slot_start >= %s AND slot_start <= %s",
        $m_from . ' 00:00:00', $m_to . ' 23:59:59'
    ) );
    $chart_values[] = round( $rev / 100, 2 );
}
$chart_labels_json = json_encode( $chart_labels );
$chart_values_json = json_encode( $chart_values );
$chart_max         = max( array_merge( $chart_values, [1] ) );
?>
<div class="wrap erb-admin-page">
    <h1><?php esc_html_e( 'Dashboard', 'escape-room-booking' ); ?></h1>

    <?php if ( ! empty( $stale ) ) : ?>
    <div class="erb-notice erb-notice--error">
        <strong><?php esc_html_e( 'Attention:', 'escape-room-booking' ); ?></strong>
        <?php echo count( $stale ); ?> <?php esc_html_e( 'booking(s) are stuck in Pending — check your webhook settings.', 'escape-room-booking' ); ?>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=erb-settings' ) ); ?>"><?php esc_html_e( 'Settings →', 'escape-room-booking' ); ?></a>
        <ul style="margin:.5rem 0 0 1rem;font-size:.85rem;">
            <?php foreach ( $stale as $s ) : ?>
            <li><?php echo esc_html( $s->booking_ref ); ?> — <?php echo esc_html( $s->email ); ?> — <?php echo esc_html( $s->created_at ); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- Period selector -->
    <div class="erb-card" style="margin-bottom:1rem;">
        <form method="get" action="" style="display:flex;flex-wrap:wrap;gap:.75rem;align-items:flex-end;">
            <input type="hidden" name="page" value="erb-dashboard">
            <div class="erb-form-group">
                <label><?php esc_html_e( 'Period', 'escape-room-booking' ); ?></label>
                <select name="period" onchange="document.getElementById('erb-custom-range').style.display=this.value==='custom'?'flex':'none'">
                    <?php foreach ( array(
                        'this_week'  => __( 'This Week',  'escape-room-booking' ),
                        'this_month' => __( 'This Month', 'escape-room-booking' ),
                        'last_month' => __( 'Last Month', 'escape-room-booking' ),
                        'this_year'  => __( 'This Year',  'escape-room-booking' ),
                        'last_year'  => __( 'Last Year',  'escape-room-booking' ),
                        'custom'     => __( 'Custom…',    'escape-room-booking' ),
                    ) as $val => $lbl ) : ?>
                    <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $period, $val ); ?>><?php echo esc_html( $lbl ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div id="erb-custom-range" style="display:<?php echo $period === 'custom' ? 'flex' : 'none'; ?>;gap:.75rem;align-items:flex-end;flex-wrap:wrap;">
                <div class="erb-form-group">
                    <label><?php esc_html_e( 'From', 'escape-room-booking' ); ?></label>
                    <input type="date" name="date_from" value="<?php echo esc_attr( $date_from ); ?>">
                </div>
                <div class="erb-form-group">
                    <label><?php esc_html_e( 'To', 'escape-room-booking' ); ?></label>
                    <input type="date" name="date_to" value="<?php echo esc_attr( $date_to ); ?>">
                </div>
            </div>
            <div class="erb-form-group">
                <label>&nbsp;</label>
                <button type="submit" class="erb-btn erb-btn--primary erb-btn--auto"><?php esc_html_e( 'Apply', 'escape-room-booking' ); ?></button>
            </div>
        </form>
    </div>

    <!-- Period stats -->
    <div class="erb-stats-grid" style="margin-bottom:1rem;">
        <div class="erb-stat-box">
            <div class="erb-stat-box__label" style="margin-bottom:.25rem;font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;opacity:.7;"><?php echo esc_html( $label ); ?></div>
            <div class="erb-stat-box__value"><?php echo count( $period_bookings ); ?></div>
            <div class="erb-stat-box__label"><?php esc_html_e( 'Bookings', 'escape-room-booking' ); ?></div>
        </div>
        <div class="erb-stat-box">
            <div class="erb-stat-box__label" style="margin-bottom:.25rem;font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;opacity:.7;"><?php echo esc_html( $label ); ?></div>
            <div class="erb-stat-box__value"><?php echo esc_html( ERB_Helpers::format_price( $period_revenue ) ); ?></div>
            <div class="erb-stat-box__label"><?php esc_html_e( 'Revenue', 'escape-room-booking' ); ?></div>
        </div>
        <div class="erb-stat-box">
            <div class="erb-stat-box__label" style="margin-bottom:.25rem;font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;opacity:.7;"><?php echo esc_html( $label ); ?></div>
            <div class="erb-stat-box__value"><?php echo (int) $period_players; ?></div>
            <div class="erb-stat-box__label"><?php esc_html_e( 'Players', 'escape-room-booking' ); ?></div>
        </div>
        <div class="erb-stat-box">
            <div class="erb-stat-box__label" style="margin-bottom:.25rem;font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;opacity:.7;"><?php echo esc_html( $label ); ?></div>
            <div class="erb-stat-box__value"><?php echo esc_html( ERB_Helpers::format_price( (int) $period_avg ) ); ?></div>
            <div class="erb-stat-box__label"><?php esc_html_e( 'Avg Booking', 'escape-room-booking' ); ?></div>
        </div>
    </div>

    <!-- All time totals -->
    <div style="display:flex;gap:1rem;margin-bottom:1rem;flex-wrap:wrap;">
        <div class="erb-card" style="flex:1;min-width:160px;text-align:center;padding:1rem;">
            <div style="font-size:1.5rem;font-weight:700;color:var(--erb-navy);"><?php echo (int) $total_bookings; ?></div>
            <div style="font-size:.8rem;color:#6b7280;margin-top:.25rem;"><?php esc_html_e( 'All Time Bookings', 'escape-room-booking' ); ?></div>
        </div>
        <div class="erb-card" style="flex:1;min-width:160px;text-align:center;padding:1rem;">
            <div style="font-size:1.5rem;font-weight:700;color:var(--erb-navy);"><?php echo esc_html( ERB_Helpers::format_price( (int) $total_revenue ) ); ?></div>
            <div style="font-size:.8rem;color:#6b7280;margin-top:.25rem;"><?php esc_html_e( 'All Time Revenue', 'escape-room-booking' ); ?></div>
        </div>
    </div>

    <!-- 12-month revenue chart -->
    <div class="erb-card" style="margin-bottom:1rem;">
        <h2 style="margin-bottom:1.25rem;"><?php esc_html_e( 'Revenue — Last 12 Months', 'escape-room-booking' ); ?></h2>
        <div style="position:relative;height:220px;display:flex;align-items:flex-end;gap:6px;padding-bottom:28px;">
            <?php foreach ( $chart_values as $idx => $val ) :
                $height = $chart_max > 0 ? round( ( $val / $chart_max ) * 180 ) : 0;
                $is_current = $idx === 11;
            ?>
            <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;height:100%;justify-content:flex-end;">
                <div style="font-size:.65rem;color:#6b7280;white-space:nowrap;">
                    <?php echo $val > 0 ? esc_html( '£' . number_format( $val, 0 ) ) : ''; ?>
                </div>
                <div style="width:100%;background:<?php echo $is_current ? '#e8621a' : '#0f1f35'; ?>;
                            height:<?php echo (int) $height; ?>px;border-radius:4px 4px 0 0;min-height:<?php echo $val > 0 ? '4' : '0'; ?>px;
                            opacity:<?php echo $is_current ? '1' : '.65'; ?>;transition:opacity .2s;"
                     title="<?php echo esc_attr( $chart_labels[ $idx ] . ': £' . number_format( $val, 2 ) ); ?>">
                </div>
                <div style="font-size:.65rem;color:#6b7280;white-space:nowrap;position:absolute;bottom:0;">
                    <?php echo esc_html( $chart_labels[ $idx ] ); ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="font-size:.75rem;color:#6b7280;margin-top:.5rem;">
            <span style="display:inline-block;width:10px;height:10px;background:#e8621a;border-radius:2px;margin-right:4px;"></span>
            <?php esc_html_e( 'Current month', 'escape-room-booking' ); ?>
            <span style="display:inline-block;width:10px;height:10px;background:#0f1f35;border-radius:2px;margin:0 4px 0 12px;opacity:.65;"></span>
            <?php esc_html_e( 'Previous months', 'escape-room-booking' ); ?>
        </div>
    </div>

    <!-- Today's bookings -->
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
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else : ?>
    <div class="erb-card">
        <p style="color:#6b7280;font-size:.9rem;margin:0;">
            <?php esc_html_e( 'No bookings today.', 'escape-room-booking' ); ?>
        </p>
    </div>
    <?php endif; ?>
</div>
