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
        $label = __( 'This Week', 'ettrick-escape-room-booking' );
        break;
    case 'last_month':
        $from  = gmdate( 'Y-m-01', strtotime( 'first day of last month' ) );
        $to    = gmdate( 'Y-m-t',  strtotime( 'last day of last month' ) );
        $label = __( 'Last Month', 'ettrick-escape-room-booking' );
        break;
    case 'this_year':
        $from  = "{$year}-01-01";
        $to    = "{$year}-12-31";
        $label = __( 'This Year', 'ettrick-escape-room-booking' );
        break;
    case 'last_year':
        $y     = $year - 1;
        $from  = "{$y}-01-01";
        $to    = "{$y}-12-31";
        $label = __( 'Last Year', 'ettrick-escape-room-booking' );
        break;
    case 'custom':
        $from  = $date_from ?: $today;
        $to    = $date_to   ?: $today;
        $label = __( 'Custom Range', 'ettrick-escape-room-booking' );
        break;
    default: // this_month
        $period = 'this_month';
        $from   = gmdate( 'Y-m-01' );
        $to     = gmdate( 'Y-m-t' );
        $label  = __( 'This Month', 'ettrick-escape-room-booking' );
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
    <h1><?php esc_html_e( 'Dashboard', 'ettrick-escape-room-booking' ); ?></h1>

    <?php if ( ! empty( $stale ) ) : ?>
    <div class="erb-notice erb-notice--error">
        <strong><?php esc_html_e( 'Attention:', 'ettrick-escape-room-booking' ); ?></strong>
        <?php echo count( $stale ); ?> <?php esc_html_e( 'booking(s) are stuck in Pending — check your webhook settings.', 'ettrick-escape-room-booking' ); ?>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=erb-settings' ) ); ?>"><?php esc_html_e( 'Settings →', 'ettrick-escape-room-booking' ); ?></a>
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
                <label><?php esc_html_e( 'Period', 'ettrick-escape-room-booking' ); ?></label>
                <select name="period" onchange="document.getElementById('erb-custom-range').style.display=this.value==='custom'?'flex':'none'">
                    <?php foreach ( array(
                        'this_week'  => __( 'This Week',  'ettrick-escape-room-booking' ),
                        'this_month' => __( 'This Month', 'ettrick-escape-room-booking' ),
                        'last_month' => __( 'Last Month', 'ettrick-escape-room-booking' ),
                        'this_year'  => __( 'This Year',  'ettrick-escape-room-booking' ),
                        'last_year'  => __( 'Last Year',  'ettrick-escape-room-booking' ),
                        'custom'     => __( 'Custom…',    'ettrick-escape-room-booking' ),
                    ) as $val => $lbl ) : ?>
                    <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $period, $val ); ?>><?php echo esc_html( $lbl ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div id="erb-custom-range" style="display:<?php echo $period === 'custom' ? 'flex' : 'none'; ?>;gap:.75rem;align-items:flex-end;flex-wrap:wrap;">
                <div class="erb-form-group">
                    <label><?php esc_html_e( 'From', 'ettrick-escape-room-booking' ); ?></label>
                    <input type="date" name="date_from" value="<?php echo esc_attr( $date_from ); ?>">
                </div>
                <div class="erb-form-group">
                    <label><?php esc_html_e( 'To', 'ettrick-escape-room-booking' ); ?></label>
                    <input type="date" name="date_to" value="<?php echo esc_attr( $date_to ); ?>">
                </div>
            </div>
            <div class="erb-form-group">
                <label>&nbsp;</label>
                <button type="submit" class="erb-btn erb-btn--primary erb-btn--auto"><?php esc_html_e( 'Apply', 'ettrick-escape-room-booking' ); ?></button>
            </div>
        </form>
    </div>

    <!-- Period stats -->
    <div class="erb-stats-grid" style="margin-bottom:1rem;">
        <div class="erb-stat-box">
            <div class="erb-stat-box__label" style="margin-bottom:.25rem;font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;opacity:.7;"><?php echo esc_html( $label ); ?></div>
            <div class="erb-stat-box__value"><?php echo count( $period_bookings ); ?></div>
            <div class="erb-stat-box__label"><?php esc_html_e( 'Bookings', 'ettrick-escape-room-booking' ); ?></div>
        </div>
        <div class="erb-stat-box">
            <div class="erb-stat-box__label" style="margin-bottom:.25rem;font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;opacity:.7;"><?php echo esc_html( $label ); ?></div>
            <div class="erb-stat-box__value"><?php echo esc_html( ERB_Helpers::format_price( $period_revenue ) ); ?></div>
            <div class="erb-stat-box__label"><?php esc_html_e( 'Revenue', 'ettrick-escape-room-booking' ); ?></div>
        </div>
        <div class="erb-stat-box">
            <div class="erb-stat-box__label" style="margin-bottom:.25rem;font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;opacity:.7;"><?php echo esc_html( $label ); ?></div>
            <div class="erb-stat-box__value"><?php echo (int) $period_players; ?></div>
            <div class="erb-stat-box__label"><?php esc_html_e( 'Players', 'ettrick-escape-room-booking' ); ?></div>
        </div>
        <div class="erb-stat-box">
            <div class="erb-stat-box__label" style="margin-bottom:.25rem;font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;opacity:.7;"><?php echo esc_html( $label ); ?></div>
            <div class="erb-stat-box__value"><?php echo esc_html( ERB_Helpers::format_price( (int) $period_avg ) ); ?></div>
            <div class="erb-stat-box__label"><?php esc_html_e( 'Avg Booking', 'ettrick-escape-room-booking' ); ?></div>
        </div>
    </div>

    <!-- All time totals -->
    <div style="display:flex;gap:1rem;margin-bottom:1rem;flex-wrap:wrap;">
        <div class="erb-card" style="flex:1;min-width:160px;text-align:center;padding:1rem;">
            <div style="font-size:1.5rem;font-weight:700;color:var(--erb-navy);"><?php echo (int) $total_bookings; ?></div>
            <div style="font-size:.8rem;color:#6b7280;margin-top:.25rem;"><?php esc_html_e( 'All Time Bookings', 'ettrick-escape-room-booking' ); ?></div>
        </div>
        <div class="erb-card" style="flex:1;min-width:160px;text-align:center;padding:1rem;">
            <div style="font-size:1.5rem;font-weight:700;color:var(--erb-navy);"><?php echo esc_html( ERB_Helpers::format_price( (int) $total_revenue ) ); ?></div>
            <div style="font-size:.8rem;color:#6b7280;margin-top:.25rem;"><?php esc_html_e( 'All Time Revenue', 'ettrick-escape-room-booking' ); ?></div>
        </div>
    </div>

    <!-- 12-month revenue chart — SVG based, no CSS dependency -->
    <div class="erb-card" style="margin-bottom:1rem;">
        <h2 style="margin-bottom:1.25rem;"><?php esc_html_e( 'Revenue — Last 12 Months', 'ettrick-escape-room-booking' ); ?></h2>
        <?php
        $svg_w      = 600;
        $svg_h      = 230;
        $bar_area_h = 150;
        $top_pad    = 30;
        $label_h    = 30;
        $n          = count( $chart_values );
        $bar_w      = floor( ( $svg_w - ( $n + 1 ) * 4 ) / $n );
        $color_cur  = '#e8621a';
        $color_prev = '#2563eb';
        ?>
        <svg viewBox="0 0 <?php echo (int) $svg_w; ?> <?php echo (int) $svg_h; ?>"
             style="width:100%;max-width:100%;display:block;"
             xmlns="http://www.w3.org/2000/svg">
            <?php foreach ( $chart_values as $idx => $val ) :
                $bar_h     = $chart_max > 0 ? round( ( $val / $chart_max ) * $bar_area_h ) : 0;
                $bar_h     = max( $bar_h, $val > 0 ? 3 : 0 );
                $x         = 4 + $idx * ( $bar_w + 4 );
                $y         = $top_pad + $bar_area_h - $bar_h;
                $is_cur    = $idx === 11;
                $colour    = $is_cur ? $color_cur : $color_prev;
                $opacity   = $is_cur ? '1' : '0.65';
                $label     = $chart_labels[ $idx ];
                $val_label = $val > 0 ? '£' . number_format( $val, 0 ) : '';
            ?>
            <?php if ( $bar_h > 0 ) : ?>
            <rect x="<?php echo (int) $x; ?>"
                  y="<?php echo (int) $y; ?>"
                  width="<?php echo (int) $bar_w; ?>"
                  height="<?php echo (int) $bar_h; ?>"
                  fill="<?php echo esc_attr( $colour ); ?>"
                  opacity="<?php echo esc_attr( $opacity ); ?>"
                  rx="3">
                <title><?php echo esc_html( $label . ': £' . number_format( $val, 2 ) ); ?></title>
            </rect>
            <?php endif; ?>
            <?php if ( $val_label ) : ?>
            <text x="<?php echo (int) ( $x + $bar_w / 2 ); ?>"
                  y="<?php echo max( 12, (int) ( $y - 4 ) ); ?>"
                  text-anchor="middle"
                  font-size="9"
                  fill="#6b7280"><?php echo esc_html( $val_label ); ?></text>
            <?php endif; ?>
            <text x="<?php echo (int) ( $x + $bar_w / 2 ); ?>"
                  y="<?php echo (int) ( $top_pad + $bar_area_h + $label_h - 4 ); ?>"
                  text-anchor="middle"
                  font-size="9"
                  fill="<?php echo $is_cur ? esc_attr( $color_cur ) : '#6b7280'; ?>"
                  font-weight="<?php echo $is_cur ? 'bold' : 'normal'; ?>">
                <?php echo esc_html( $label ); ?>
            </text>
            <?php endforeach; ?>
        </svg>
        <div style="font-size:.75rem;color:#6b7280;margin-top:.25rem;">
            <span style="display:inline-block;width:10px;height:10px;background:#e8621a;border-radius:2px;margin-right:4px;vertical-align:middle;"></span>
            <?php esc_html_e( 'Current month', 'ettrick-escape-room-booking' ); ?>
            <span style="display:inline-block;width:10px;height:10px;background:#2563eb;border-radius:2px;margin:0 4px 0 12px;opacity:.65;vertical-align:middle;"></span>
            <?php esc_html_e( 'Previous months', 'ettrick-escape-room-booking' ); ?>
        </div>
    </div>

    <!-- Today's bookings -->
    <?php if ( ! empty( $bookings_today ) ) : ?>
    <div class="erb-card">
        <h2><?php esc_html_e( "Today's Bookings", 'ettrick-escape-room-booking' ); ?></h2>
        <table class="erb-table">
            <thead><tr>
                <th><?php esc_html_e( 'Ref', 'ettrick-escape-room-booking' ); ?></th>
                <th><?php esc_html_e( 'Game', 'ettrick-escape-room-booking' ); ?></th>
                <th><?php esc_html_e( 'Time', 'ettrick-escape-room-booking' ); ?></th>
                <th><?php esc_html_e( 'Customer', 'ettrick-escape-room-booking' ); ?></th>
                <th><?php esc_html_e( 'Players', 'ettrick-escape-room-booking' ); ?></th>
                <th><?php esc_html_e( 'Total', 'ettrick-escape-room-booking' ); ?></th>
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
            <?php esc_html_e( 'No bookings today.', 'ettrick-escape-room-booking' ); ?>
        </p>
    </div>
    <?php endif; ?>
</div>
