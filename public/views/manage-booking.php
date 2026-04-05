<?php
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
if ( ! defined( 'ABSPATH' ) ) exit;

// Read token from URL — try all available sources
// get_query_var works with pretty permalinks; $_GET works with plain permalinks
$token = sanitize_text_field(
    get_query_var( 'token', '' )
    ?: ( $_GET['token']      ?? '' )
    ?: ( $_REQUEST['token']  ?? '' )
    ?: ( $_POST['_erb_token'] ?? '' )
);
// Strip any URL encoding that may have been double-encoded
$token = rawurldecode( $token );
$booking = $token ? ERB_DB::get_booking_by_token( $token ) : null;
$action  = sanitize_text_field( $_GET['erb_manage'] ?? '' );

// Handle POST actions
if ( $_SERVER['REQUEST_METHOD'] === 'POST' && $booking ) {
    $post_action = sanitize_text_field( $_POST['erb_manage_action'] ?? '' );
    check_admin_referer( 'erb_manage_' . $token );

    if ( $post_action === 'cancel' && in_array( $booking->status, array( 'confirmed', 'changed' ) ) ) {
        ERB_DB::update_booking( $booking->id, array(
            'status'     => 'cancelled',
            'updated_at' => current_time( 'mysql' ),
        ) );
        ERB_DB::add_booking_history( array(
            'booking_id' => $booking->id,
            'action'     => 'cancelled',
            'changed_by' => 'customer',
            'created_at' => current_time( 'mysql' ),
        ) );
        $emails  = new ERB_Emails();
        $emails->send_cancellation( ERB_DB::get_booking( $booking->id ) );
        // Redirect with a clean URL — no token — to prevent re-triggering on refresh
        $manage_base = get_option( 'erb_manage_page_url', '' );
        if ( empty( $manage_base ) ) $manage_base = get_permalink();
        wp_safe_redirect( add_query_arg( 'erb_cancelled', '1', $manage_base ) );
        exit;
        exit;
    }

    // Re-read token from hidden field on POST (URL may not carry it)
    if ( ! empty( $_POST['_erb_token'] ) ) {
        $token   = sanitize_text_field( $_POST['_erb_token'] );
        $booking = ERB_DB::get_booking_by_token( $token );
    }

    if ( $post_action === 'change_players' ) {
        $new_players = (int) ( $_POST['player_count'] ?? 0 );
        $game        = ERB_DB::get_game( $booking->game_id );
        if ( $new_players >= $game->min_players && $new_players <= $game->max_players ) {
            $new_price = ERB_DB::get_price( $booking->game_id, $new_players );
            if ( $new_price ) {
                $old_players   = (int) $booking->player_count;
                $old_price     = (int) $booking->total_pence;
                $price_diff    = $new_price - $old_price; // positive = more to pay, negative = refund

                ERB_DB::update_booking( $booking->id, array(
                    'player_count'   => $new_players,
                    'price_pence'    => $new_price,
                    'total_pence'    => $new_price,
                    'discount_pence' => 0,
                    'status'         => 'changed',
                    'updated_at'     => current_time( 'mysql' ),
                ) );
                ERB_DB::add_booking_history( array(
                    'booking_id'  => $booking->id,
                    'action'      => 'changed',
                    'changed_by'  => 'customer',
                    'old_players' => $old_players,
                    'new_players' => $new_players,
                    'note'        => 'Price changed from ' . ERB_Helpers::format_price( $old_price ) . ' to ' . ERB_Helpers::format_price( $new_price ),
                    'created_at'  => current_time( 'mysql' ),
                ) );

                $change_data = array(
                    'old_players' => $old_players,
                    'new_players' => $new_players,
                    'old_price'   => $old_price,
                    'new_price'   => $new_price,
                    'price_diff'  => $price_diff,
                );

                $emails = new ERB_Emails();
                $emails->send_change( ERB_DB::get_booking( $booking->id ), $change_data );
                $booking = ERB_DB::get_booking_by_token( $token );
                $message = 'changed';
            }
        }
    }
}

$date  = $booking ? date_i18n( get_option( 'erb_date_format', 'j F Y' ), strtotime( $booking->slot_start ) ) : '';
$time  = $booking ? date_i18n( 'H:i', strtotime( $booking->slot_start ) )                                : '';
$total = $booking ? ERB_Helpers::format_price( $booking->total_pence )                                    : '';
?>
<div class="erb-wrap">
<div class="erb-booking-wrap">

<?php if ( isset( $_GET['erb_cancelled'] ) && $_GET['erb_cancelled'] == '1' ) : ?>
    <div class="erb-booking-step">
        <div class="erb-result">
            <div class="erb-result__icon">✅</div>
            <h2><?php esc_html_e( 'Booking Cancelled', 'escape-room-booking' ); ?></h2>
            <p style="font-size:16px;color:#6b7280;margin-bottom:24px;"><?php esc_html_e( 'Your booking has been cancelled and a confirmation email has been sent.', 'escape-room-booking' ); ?></p>
            <a href="<?php echo esc_url( ERB_Helpers::get_browse_url() ); ?>" class="erb-btn erb-btn--primary" style="display:inline-flex;width:auto;">
                <?php esc_html_e( 'Book Again', 'escape-room-booking' ); ?>
            </a>
        </div>
    </div>

<?php elseif ( ! $booking ) : ?>
    <div class="erb-booking-step">
        <div class="erb-result">
            <div class="erb-result__icon">🔍</div>
            <h2><?php esc_html_e( 'Booking Not Found', 'escape-room-booking' ); ?></h2>
            <p><?php esc_html_e( 'This link may have expired or the booking reference is invalid. Please check your confirmation email.', 'escape-room-booking' ); ?></p>
        </div>
    </div>

<?php elseif ( isset( $message ) && $message === 'changed' ) : ?>
    <div class="erb-booking-step">
        <div class="erb-result">
            <div class="erb-result__icon">✅</div>
            <h2><?php esc_html_e( 'Booking Updated', 'escape-room-booking' ); ?></h2>
            <p><?php esc_html_e( 'Your booking has been updated and a confirmation email has been sent.', 'escape-room-booking' ); ?></p>
            <a href="<?php echo esc_url( ERB_Helpers::manage_booking_url( $token ) ); ?>"
               class="erb-btn erb-btn--outline" style="margin-top:20px;display:inline-flex;width:auto;">
                <?php esc_html_e( 'View My Booking', 'escape-room-booking' ); ?>
            </a>
        </div>
    </div>

<?php elseif ( $booking->status === 'cancelled' ) : ?>
    <div class="erb-booking-step">
        <div class="erb-result">
            <div class="erb-result__icon">❌</div>
            <h2><?php esc_html_e( 'Booking Cancelled', 'escape-room-booking' ); ?></h2>
            <p><?php esc_html_e( 'This booking has already been cancelled.', 'escape-room-booking' ); ?></p>
            <a href="<?php echo esc_url( ERB_Helpers::get_browse_url() ); ?>" class="erb-btn erb-btn--primary" style="margin-top:20px;display:inline-flex;width:auto;">
                <?php esc_html_e( 'Make a New Booking', 'escape-room-booking' ); ?>
            </a>
        </div>
    </div>

<?php else : ?>
    <!-- Booking details -->
    <div class="erb-booking-step">
        <h2><?php esc_html_e( 'Your Booking', 'escape-room-booking' ); ?></h2>

        <div class="erb-summary">
            <table style="width:100%;border-collapse:collapse;font-size:16px;">
                <?php
                $rows = array(
                    __( 'Reference', 'escape-room-booking' ) => '<code style="font-size:15px;">' . esc_html( $booking->booking_ref ) . '</code>',
                    __( 'Game',      'escape-room-booking' ) => esc_html( $booking->game_name ),
                    __( 'Date',      'escape-room-booking' ) => esc_html( $date ),
                    __( 'Time',      'escape-room-booking' ) => esc_html( $time ),
                    __( 'Players',   'escape-room-booking' ) => esc_html( $booking->player_count ),
                    __( 'Total',     'escape-room-booking' ) => '<strong>' . esc_html( $total ) . '</strong>',
                    __( 'Status',    'escape-room-booking' ) => '<span class="erb-badge erb-badge--' . esc_attr( $booking->status ) . '">' . esc_html( ucfirst( $booking->status ) ) . '</span>',
                );
                foreach ( $rows as $label => $value ) :
                ?>
                <tr>
                    <td style="color:#6b7280;padding:5px 0;width:120px;vertical-align:top;"><?php echo esc_html( $label ); ?></td>
                    <td style="padding:5px 0;vertical-align:top;"><?php echo wp_kses_post( $value ); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <?php if ( in_array( $booking->status, array( 'confirmed', 'changed' ) ) ) : ?>

        <!-- Change number of players -->
        <div style="margin-bottom:24px;">
            <h3 style="font-size:18px;margin:0 0 12px;"><?php esc_html_e( 'Change Number of Players', 'escape-room-booking' ); ?></h3>
            <form method="post">
                <?php wp_nonce_field( 'erb_manage_' . $token ); ?>
                <input type="hidden" name="_erb_token" value="<?php echo esc_attr( $token ); ?>">
                <input type="hidden" name="erb_manage_action" value="change_players">
                <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                    <select name="player_count" style="padding:10px 14px;border:1px solid #e5e7eb;border-radius:8px;font-size:16px;font-family:inherit;">
                        <?php
                        $game = ERB_DB::get_game( $booking->game_id );
                        for ( $p = $game->min_players; $p <= $game->max_players; $p++ ) :
                            $price = ERB_DB::get_price( $booking->game_id, $p );
                        ?>
                        <option value="<?php echo absint( $p ); ?>" <?php selected( $booking->player_count, $p ); ?>>
                            <?php echo absint( $p ); ?> <?php esc_html_e( 'players', 'escape-room-booking' ); ?>
                            <?php if ( $price ) echo '&mdash; ' . esc_html( ERB_Helpers::format_price( $price ) ); ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                    <button type="submit" class="erb-btn erb-btn--primary" style="width:auto;display:inline-flex;">
                        <?php esc_html_e( 'Update Players', 'escape-room-booking' ); ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Change date/time — link back to calendar -->
        <div style="margin-bottom:24px;">
            <h3 style="font-size:18px;margin:0 0 8px;"><?php esc_html_e( 'Change Date or Time', 'escape-room-booking' ); ?></h3>
            <p style="font-size:15px;color:#6b7280;margin:0 0 12px;">
                <?php esc_html_e( 'To change your date or time, cancel this booking and make a new one from the calendar.', 'escape-room-booking' ); ?>
            </p>
            <a href="<?php echo esc_url( ERB_Helpers::get_browse_url() ); ?>" class="erb-btn erb-btn--outline" style="width:auto;display:inline-flex;">
                <?php esc_html_e( 'Go to Calendar', 'escape-room-booking' ); ?>
            </a>
        </div>

        <!-- Cancel -->
        <div style="border-top:1px solid #fee2e2;padding-top:20px;margin-top:8px;">
            <h3 style="font-size:18px;margin:0 0 8px;color:#dc2626;"><?php esc_html_e( 'Cancel Booking', 'escape-room-booking' ); ?></h3>
            <p style="font-size:15px;color:#6b7280;margin:0 0 12px;">
                <?php esc_html_e( 'Cancellations are subject to our cancellation policy. Refunds are processed manually by our team.', 'escape-room-booking' ); ?>
            </p>
            <form method="post" onsubmit="return confirm('Are you sure you want to cancel this booking? This cannot be undone.');">
                <?php wp_nonce_field( 'erb_manage_' . $token ); ?>
                <input type="hidden" name="_erb_token" value="<?php echo esc_attr( $token ); ?>">
                <input type="hidden" name="erb_manage_action" value="cancel">
                <button type="submit" class="erb-btn erb-btn--danger" style="width:auto;display:inline-flex;">
                    <?php esc_html_e( 'Cancel My Booking', 'escape-room-booking' ); ?>
                </button>
            </form>
        </div>

        <?php endif; ?>
    </div>
<?php endif; ?>

</div>
</div>
