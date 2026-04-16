<?php
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
if ( ! defined( 'ABSPATH' ) ) exit;

$slug = sanitize_title( $atts['game'] ?? '' );
$game = $slug ? ERB_DB::get_game_by_slug( $slug ) : null;

if ( ! $game ) {
    echo '<p class="erb-error">' . esc_html__( 'Game not found. Please check the shortcode slug.', 'ettrick-escape-room-booking' ) . '</p>';
    return;
}

$week_offset = (int) ( $_GET['erb_week'] ?? 0 );
$week_start  = new DateTime( 'Monday this week' );
if ( $week_offset !== 0 ) {
    $interval = new DateInterval( 'P' . abs( $week_offset ) . 'W' );
    $week_offset > 0 ? $week_start->add( $interval ) : $week_start->sub( $interval );
}
$week_end = ( clone $week_start )->modify( '+6 days' );

$base_url  = strtok( ( is_ssl() ? 'https' : 'http' ) . '://' . sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) . sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), '?' );
$prev_url  = add_query_arg( 'erb_week', $week_offset - 1, $base_url );
$next_url  = add_query_arg( 'erb_week', $week_offset + 1, $base_url );
$today_url = $base_url;

$available_color = get_option( 'erb_slot_available_color', '#22c55e' );
$booked_color    = get_option( 'erb_slot_booked_color',    '#ef4444' );
$booking_page    = get_option( 'erb_booking_page_url', '' );
?>
<div class="erb-wrap">
<div class="erb-calendar"
     id="erb-calendar-<?php echo (int) $game->id; ?>"
     data-game-id="<?php echo (int) $game->id; ?>"
     data-game-slug="<?php echo esc_attr( $game->slug ); ?>"
     data-week-offset="<?php echo (int) $week_offset; ?>"
     data-week-start="<?php echo esc_attr( $week_start->format( 'Y-m-d' ) ); ?>"
     data-available-color="<?php echo esc_attr( $available_color ); ?>"
     data-booked-color="<?php echo esc_attr( $booked_color ); ?>"
     data-booking-url="<?php echo esc_url( $booking_page ); ?>">

    <?php if ( $game->description ) : ?>
    <p class="erb-game-description"><?php echo esc_html( $game->description ); ?></p>
    <?php endif; ?>

    <!-- Week navigation -->
    <div class="erb-calendar__nav">
        <a href="<?php echo esc_url( $prev_url ); ?>" class="erb-btn erb-btn--outline erb-btn--auto erb-btn--sm">
            &#8592; <?php esc_html_e( 'Prev Week', 'ettrick-escape-room-booking' ); ?>
        </a>
        <span class="erb-calendar__nav-title">
            <?php echo esc_html( date_i18n( get_option( 'erb_date_format', 'j F Y' ), $week_start->getTimestamp() ) . ' – ' . date_i18n( get_option( 'erb_date_format', 'j F Y' ), $week_end->getTimestamp() ) ); ?>
            <?php if ( $week_offset !== 0 ) : ?>
                &nbsp;<a href="<?php echo esc_url( $today_url ); ?>" class="erb-today-link"><?php esc_html_e( 'This week', 'ettrick-escape-room-booking' ); ?></a>
            <?php endif; ?>
        </span>
        <a href="<?php echo esc_url( $next_url ); ?>" class="erb-btn erb-btn--outline erb-btn--auto erb-btn--sm">
            <?php esc_html_e( 'Next Week', 'ettrick-escape-room-booking' ); ?> &#8594;
        </a>
    </div>

    <!-- Legend -->
    <div class="erb-calendar__legend">
        <span class="erb-legend-item"><span class="erb-legend-dot" style="background:<?php echo esc_attr( $available_color ); ?>;"></span><?php esc_html_e( 'Available — click to book', 'ettrick-escape-room-booking' ); ?></span>
        <span class="erb-legend-item"><span class="erb-legend-dot" style="background:<?php echo esc_attr( $booked_color ); ?>;"></span><?php esc_html_e( 'Unavailable', 'ettrick-escape-room-booking' ); ?></span>
        <span class="erb-legend-item"><span class="erb-legend-dot" style="background:#e5e7eb;border:1px solid #d1d5db;"></span><?php esc_html_e( 'Closed', 'ettrick-escape-room-booking' ); ?></span>
    </div>

    <!-- Loading state -->
    <div class="erb-calendar__loading" id="erb-loading-<?php echo (int) $game->id; ?>">
        <div class="erb-spinner-dark"></div>
        <span><?php esc_html_e( 'Loading availability…', 'ettrick-escape-room-booking' ); ?></span>
    </div>

    <!-- Grid populated by JS -->
    <div class="erb-calendar__grid-wrap" id="erb-grid-wrap-<?php echo (int) $game->id; ?>" style="display:none;overflow-x:auto;">
        <div class="erb-calendar__grid" id="erb-grid-<?php echo (int) $game->id; ?>"></div>
    </div>

    <div class="erb-calendar__error" id="erb-error-<?php echo (int) $game->id; ?>" style="display:none;"></div>
</div>
</div>
