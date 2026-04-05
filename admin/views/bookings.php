<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
<div class="wrap erb-admin-page">
    <h1><?php esc_html_e( 'Bookings', 'escape-room-booking' ); ?></h1>

    <?php
    // Filters from query string
    $search     = sanitize_text_field( $_GET['s']      ?? '' );
    $status     = sanitize_text_field( $_GET['status'] ?? '' );
    $game_id    = (int) ( $_GET['game_id'] ?? 0 );
    $date_from  = sanitize_text_field( $_GET['date_from'] ?? '' );
    $date_to    = sanitize_text_field( $_GET['date_to']   ?? '' );

    $args = array( 'limit' => 100 );
    if ( $search )    $args['search']    = $search;
    if ( $status )    $args['status']    = $status;
    if ( $game_id )   $args['game_id']   = $game_id;
    if ( $date_from ) $args['date_from'] = $date_from . ' 00:00:00';
    if ( $date_to )   $args['date_to']   = $date_to   . ' 23:59:59';

    $bookings = ERB_DB::get_bookings( $args );
    $games    = ERB_DB::get_games( false );

    // Revenue total for current filter
    $total_revenue = array_sum( array_map( function( $b ) {
        return $b->status === 'confirmed' ? (int) $b->total_pence : 0;
    }, $bookings ) );
    ?>

    <!-- Filters -->
    <div class="erb-card" style="margin-bottom:1rem;">
        <form method="get" action="" style="display:flex;flex-wrap:wrap;gap:.75rem;align-items:flex-end;">
            <input type="hidden" name="page" value="erb-bookings">
            <div class="erb-form-group" style="min-width:180px;">
                <label><?php esc_html_e( 'Search', 'escape-room-booking' ); ?></label>
                <input type="text" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Ref, name or email…">
            </div>
            <div class="erb-form-group">
                <label><?php esc_html_e( 'Status', 'escape-room-booking' ); ?></label>
                <select name="status">
                    <option value=""><?php esc_html_e( 'All statuses', 'escape-room-booking' ); ?></option>
                    <?php foreach ( array( 'confirmed', 'pending', 'changed', 'cancelled' ) as $s ) : ?>
                    <option value="<?php echo esc_attr( $s ); ?>" <?php selected( $status, $s ); ?>><?php echo esc_html( ucfirst( $s ) ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="erb-form-group">
                <label><?php esc_html_e( 'Game', 'escape-room-booking' ); ?></label>
                <select name="game_id">
                    <option value=""><?php esc_html_e( 'All games', 'escape-room-booking' ); ?></option>
                    <?php foreach ( $games as $g ) : ?>
                    <option value="<?php echo (int) $g->id; ?>" <?php selected( $game_id, $g->id ); ?>><?php echo esc_html( $g->name ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="erb-form-group">
                <label><?php esc_html_e( 'From', 'escape-room-booking' ); ?></label>
                <input type="date" name="date_from" value="<?php echo esc_attr( $date_from ); ?>">
            </div>
            <div class="erb-form-group">
                <label><?php esc_html_e( 'To', 'escape-room-booking' ); ?></label>
                <input type="date" name="date_to" value="<?php echo esc_attr( $date_to ); ?>">
            </div>
            <div class="erb-form-group" style="min-width:auto;">
                <label>&nbsp;</label>
                <button type="submit" class="erb-btn erb-btn--primary erb-btn--auto"><?php esc_html_e( 'Filter', 'escape-room-booking' ); ?></button>
            </div>
            <?php if ( $search || $status || $game_id || $date_from || $date_to ) : ?>
            <div class="erb-form-group" style="min-width:auto;">
                <label>&nbsp;</label>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=erb-bookings' ) ); ?>" class="erb-btn erb-btn--outline erb-btn--auto"><?php esc_html_e( 'Clear', 'escape-room-booking' ); ?></a>
            </div>
            <?php endif; ?>
        </form>
    </div>

    <div class="erb-card">
        <h2>
            <?php
            /* translators: %d: number of bookings */
            printf( esc_html__( '%d booking(s)', 'escape-room-booking' ), $total ); ?>
            <?php if ( $total_revenue ) : ?>
            <span style="font-size:.85rem;font-weight:400;color:#6b7280;margin-left:.75rem;">
                <?php esc_html_e( 'Confirmed revenue:', 'escape-room-booking' ); ?>
                <strong><?php echo esc_html( ERB_Helpers::format_price( $total_revenue ) ); ?></strong>
            </span>
            <?php endif; ?>
        </h2>

        <?php if ( empty( $bookings ) ) : ?>
            <p><em><?php esc_html_e( 'No bookings found.', 'escape-room-booking' ); ?></em></p>
        <?php else : ?>
        <table class="erb-table" style="font-size:.875rem;">
            <thead><tr>
                <th><?php esc_html_e( 'Ref', 'escape-room-booking' ); ?></th>
                <th><?php esc_html_e( 'Game', 'escape-room-booking' ); ?></th>
                <th><?php esc_html_e( 'Date & Time', 'escape-room-booking' ); ?></th>
                <th><?php esc_html_e( 'Customer', 'escape-room-booking' ); ?></th>
                <th><?php esc_html_e( 'Players', 'escape-room-booking' ); ?></th>
                <th><?php esc_html_e( 'Total', 'escape-room-booking' ); ?></th>
                <th><?php esc_html_e( 'Status', 'escape-room-booking' ); ?></th>
                <th><?php esc_html_e( 'Booked On', 'escape-room-booking' ); ?></th>
                <th><?php esc_html_e( 'Actions', 'escape-room-booking' ); ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ( $bookings as $b ) :
                $date = date_i18n( get_option( 'erb_date_format', 'j F Y' ), strtotime( $b->slot_start ) );
                $time = date_i18n( 'g:i a', strtotime( $b->slot_start ) );
            ?>
                <tr>
                    <td><code style="font-size:.8rem;"><?php echo esc_html( $b->booking_ref ); ?></code></td>
                    <td><?php echo esc_html( $b->game_name ); ?></td>
                    <td><?php echo esc_html( $date ); ?><br><small style="color:#6b7280;"><?php echo esc_html( $time ); ?></small></td>
                    <td>
                        <?php echo esc_html( $b->first_name . ' ' . $b->last_name ); ?><br>
                        <small style="color:#6b7280;"><?php echo esc_html( $b->email ); ?></small>
                    </td>
                    <td style="text-align:center;"><?php echo (int) $b->player_count; ?></td>
                    <td><?php echo esc_html( ERB_Helpers::format_price( $b->total_pence ) ); ?></td>
                    <td>
                        <span class="erb-badge erb-badge--<?php echo esc_attr( $b->status ); ?>">
                            <?php echo esc_html( ucfirst( $b->status ) ); ?>
                        </span>
                    </td>
                    <td style="white-space:nowrap;color:#6b7280;font-size:.85rem;"><?php echo esc_html( date_i18n( get_option( 'erb_date_format', 'j F Y' ), strtotime( $b->created_at ) ) ); ?></td>
                    <td style="white-space:nowrap;">
                        <button class="erb-btn erb-btn--outline erb-btn--sm"
                                onclick="ERBBookings.viewBooking(<?php echo (int) $b->id; ?>)">
                            <?php esc_html_e( 'View', 'escape-room-booking' ); ?>
                        </button>
                        <?php if ( $b->status === 'confirmed' ) : ?>
                        <button class="erb-btn erb-btn--danger erb-btn--sm"
                                onclick="ERBBookings.cancelBooking(<?php echo (int) $b->id; ?>, '<?php echo esc_js( $b->booking_ref ); ?>')">
                            <?php esc_html_e( 'Cancel', 'escape-room-booking' ); ?>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- View booking modal -->
<div class="erb-modal-overlay" id="erb-booking-modal">
    <div class="erb-modal" style="max-width:560px;">
        <button class="erb-modal__close" onclick="ERB.closeModal('erb-booking-modal')">&times;</button>
        <h2 id="erb-booking-modal-title"><?php esc_html_e( 'Booking Details', 'escape-room-booking' ); ?></h2>
        <div id="erb-booking-modal-body" style="font-size:.9rem;line-height:1.8;"></div>
    </div>
</div>

<script>
(function($){
window.ERBBookings = {
    viewBooking: function(id) {
        ERB.ajax('erb_admin_get_booking', { id: id }, function(b) {
            var sym = (window.erbAdmin && erbAdmin.currencySymbol) ? erbAdmin.currencySymbol : '£';
            var html = '<table style="width:100%;border-collapse:collapse;font-size:14px;">';
            var rows = [
                ['Reference',   '<code>' + b.booking_ref + '</code>'],
                ['Game',        b.game_name],
                ['Date',        b.slot_start ? b.slot_start.slice(0,10) : ''],
                ['Time',        b.slot_start ? b.slot_start.slice(11,16) : ''],
                ['Players',     b.player_count],
                ['Price',       sym + (b.price_pence/100).toFixed(2)],
                ['Discount',    b.discount_pence > 0 ? '-' + sym + (b.discount_pence/100).toFixed(2) : '—'],
                ['Total',       '<strong>' + sym + (b.total_pence/100).toFixed(2) + '</strong>'],
                ['Status',      '<span class="erb-badge erb-badge--' + b.status + '">' + b.status + '</span>'],
                ['Customer',    b.first_name + ' ' + b.last_name],
                ['Email',       '<a href="mailto:' + b.email + '">' + b.email + '</a>'],
                ['Mobile',      b.mobile || '—'],
                ['Booked at',   b.created_at],
                ['Stripe PI',   b.stripe_payment_id ? '<code style="font-size:.8rem;">' + b.stripe_payment_id + '</code>' : '—'],
            ];
            rows.forEach(function(r) {
                html += '<tr><td style="color:#6b7280;padding:4px 0;width:120px;vertical-align:top;">' + r[0] + '</td>'
                      + '<td style="padding:4px 0;vertical-align:top;">' + r[1] + '</td></tr>';
            });
            html += '</table>';
            $('#erb-booking-modal-title').text('Booking ' + b.booking_ref);
            $('#erb-booking-modal-body').html(html);
            ERB.openModal('erb-booking-modal');
        });
    },

    cancelBooking: function(id, ref) {
        ERB.confirm('Cancel booking ' + ref + '? This cannot be undone.', function() {
            ERB.ajax('erb_admin_cancel_booking', { id: id }, function() {
                ERB.notice('Booking ' + ref + ' cancelled.');
                setTimeout(function() { location.reload(); }, 900);
            });
        });
    },
};
})(jQuery);
</script>
