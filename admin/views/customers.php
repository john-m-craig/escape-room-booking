<?php
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$search = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) );
$t_customers = $wpdb->prefix . 'erb_customers';
$t_bookings  = $wpdb->prefix . 'erb_bookings';

if ( $search ) {
    $s         = '%' . $wpdb->esc_like( $search ) . '%';
    $customers = $wpdb->get_results( $wpdb->prepare(
        "SELECT c.*, COUNT(b.id) AS booking_count,
                SUM(CASE WHEN b.status='confirmed' THEN b.total_pence ELSE 0 END) AS total_spent
         FROM {$t_customers} c
         LEFT JOIN {$t_bookings} b ON b.customer_id = c.id
         WHERE (c.email LIKE %s OR c.last_name LIKE %s OR c.first_name LIKE %s)
         GROUP BY c.id ORDER BY c.created_at DESC LIMIT 200",
        $s, $s, $s
    ) );
} else {
    $customers = $wpdb->get_results(
        "SELECT c.*, COUNT(b.id) AS booking_count,
                SUM(CASE WHEN b.status='confirmed' THEN b.total_pence ELSE 0 END) AS total_spent
         FROM {$t_customers} c
         LEFT JOIN {$t_bookings} b ON b.customer_id = c.id
         WHERE 1=1
         GROUP BY c.id ORDER BY c.created_at DESC LIMIT 200"
    );
}
?>
<div class="wrap erb-admin-page">
    <h1><?php esc_html_e( 'Customers', 'ettrick-escape-room-booking' ); ?></h1>

    <div class="erb-card" style="margin-bottom:1rem;">
        <form method="get" action="" style="display:flex;gap:.75rem;align-items:flex-end;flex-wrap:wrap;">
            <input type="hidden" name="page" value="erb-customers">
            <div class="erb-form-group" style="min-width:220px;">
                <label><?php esc_html_e( 'Search', 'ettrick-escape-room-booking' ); ?></label>
                <input type="text" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Name or email…">
            </div>
            <div class="erb-form-group" style="min-width:auto;">
                <label>&nbsp;</label>
                <button type="submit" class="erb-btn erb-btn--primary erb-btn--auto"><?php esc_html_e( 'Search', 'ettrick-escape-room-booking' ); ?></button>
            </div>
            <?php if ( $search ) : ?>
            <div class="erb-form-group" style="min-width:auto;">
                <label>&nbsp;</label>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=erb-customers' ) ); ?>" class="erb-btn erb-btn--outline erb-btn--auto"><?php esc_html_e( 'Clear', 'ettrick-escape-room-booking' ); ?></a>
            </div>
            <?php endif; ?>
        </form>
    </div>

    <div class="erb-card">
        <h2><?php
        /* translators: %d: number of customers */
        printf( esc_html__( '%d customer(s)', 'ettrick-escape-room-booking' ), count( $customers ) ); ?></h2>

        <?php if ( empty( $customers ) ) : ?>
            <p><em><?php esc_html_e( 'No customers found.', 'ettrick-escape-room-booking' ); ?></em></p>
        <?php else : ?>
        <table class="erb-table" style="font-size:.875rem;">
            <thead><tr>
                <th><?php esc_html_e( 'Name', 'ettrick-escape-room-booking' ); ?></th>
                <th><?php esc_html_e( 'Email', 'ettrick-escape-room-booking' ); ?></th>
                <th><?php esc_html_e( 'Mobile', 'ettrick-escape-room-booking' ); ?></th>
                <th><?php esc_html_e( 'Type', 'ettrick-escape-room-booking' ); ?></th>
                <th><?php esc_html_e( 'Bookings', 'ettrick-escape-room-booking' ); ?></th>
                <th><?php esc_html_e( 'Total Spent', 'ettrick-escape-room-booking' ); ?></th>
                <th><?php esc_html_e( 'Joined', 'ettrick-escape-room-booking' ); ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ( $customers as $c ) : ?>
                <tr>
                    <td><strong><?php echo esc_html( $c->first_name . ' ' . $c->last_name ); ?></strong></td>
                    <td><a href="mailto:<?php echo esc_attr( $c->email ); ?>"><?php echo esc_html( $c->email ); ?></a></td>
                    <td><?php echo esc_html( $c->mobile ?: '—' ); ?></td>
                    <td>
                        <span class="erb-badge <?php echo $c->is_guest ? 'erb-badge--pending' : 'erb-badge--confirmed'; ?>">
                            <?php echo $c->is_guest ? esc_html__( 'Guest', 'ettrick-escape-room-booking' ) : esc_html__( 'Account', 'ettrick-escape-room-booking' ); ?>
                        </span>
                    </td>
                    <td style="text-align:center;"><?php echo (int) $c->booking_count; ?></td>
                    <td><?php echo $c->total_spent ? esc_html( ERB_Helpers::format_price( $c->total_spent ) ) : '—'; ?></td>
                    <td><?php echo esc_html( date_i18n( get_option( 'erb_date_format', 'j F Y' ), strtotime( $c->created_at ) ) ); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
