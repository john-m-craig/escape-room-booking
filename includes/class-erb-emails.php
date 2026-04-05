<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Handles all email notifications.
 * Sends branded HTML + plain text emails to customers, admin, and gamekeepers.
 */
class ERB_Emails {

    // ── Public API ────────────────────────────────────────────────────────────

    public function send_confirmation( $booking ) {
        if ( ! $booking ) return;
        $this->send_to_customer( $booking, 'confirmation' );
        $this->send_to_staff(    $booking, 'confirmation' );
    }

    public function send_change( $booking, $change_data = array() ) {
        if ( ! $booking ) return;
        $this->send_to_customer( $booking, 'changed', $change_data );
        $this->send_to_staff(    $booking, 'changed', $change_data );
    }

    public function send_cancellation( $booking ) {
        if ( ! $booking ) return;
        $this->send_to_customer( $booking, 'cancelled' );
        $this->send_to_staff(    $booking, 'cancelled' );
    }

    // ── Customer emails ───────────────────────────────────────────────────────

    private function send_to_customer( $booking, $type, $change_data = array() ) {
        $this->send(
            $booking->email,
            $this->customer_subject( $booking, $type ),
            $this->customer_html( $booking, $type, $change_data ),
            $this->customer_plain( $booking, $type, $change_data )
        );
    }

    private function customer_subject( $booking, $type ) {
        $site = get_bloginfo( 'name' );
        $ref  = $booking->booking_ref;
        switch ( $type ) {
            case 'confirmation': return sprintf( '[%s] Booking Confirmed - %s', $site, $ref );
            case 'changed':      return sprintf( '[%s] Booking Updated - %s',   $site, $ref );
            case 'cancelled':    return sprintf( '[%s] Booking Cancelled - %s', $site, $ref );
        }
        return sprintf( '[%s] Booking - %s', $site, $ref );
    }

    private function customer_html( $booking, $type, $change_data = array() ) {
        $site       = get_bloginfo( 'name' );
        $site_url   = home_url();
        $manage_url = ERB_Helpers::manage_booking_url( $booking->manage_token );
        $date       = date_i18n( get_option( 'erb_date_format', 'j F Y' ), strtotime( $booking->slot_start ) );
        $time       = date_i18n( 'H:i', strtotime( $booking->slot_start ) );
        $total      = ERB_Helpers::format_price( $booking->total_pence );

        switch ( $type ) {
            case 'confirmation':
                $heading = '&#127881; Booking Confirmed!';
                $intro   = 'Hi ' . esc_html( $booking->first_name ) . ', your escape room booking is confirmed. We can\'t wait to see you!';
                $colour  = '#16a34a';
                break;
            case 'changed':
                $heading = '&#128197; Booking Updated';
                $intro   = 'Hi ' . esc_html( $booking->first_name ) . ', your booking has been updated. Here are your new details:';
                $colour  = '#2563eb';
                break;
            case 'cancelled':
                $heading = 'Booking Cancelled';
                $intro   = 'Hi ' . esc_html( $booking->first_name ) . ', your booking has been cancelled as requested.';
                $colour  = '#dc2626';
                break;
            default:
                $heading = 'Booking Update';
                $intro   = '';
                $colour  = '#2563eb';
        }

        $rows = array(
            'Booking Reference' => '<strong style="font-family:monospace;font-size:15px;">' . esc_html( $booking->booking_ref ) . '</strong>',
            'Game'              => esc_html( $booking->game_name ),
            'Date'              => esc_html( $date ),
            'Time'              => esc_html( $time ),
            'Players'           => esc_html( $booking->player_count ),
            'Total Paid'        => '<strong>' . esc_html( $total ) . '</strong>',
            'Name'              => esc_html( $booking->first_name . ' ' . $booking->last_name ),
        );

        $rows_html = '';
        foreach ( $rows as $label => $value ) {
            $rows_html .= '<tr>'
                . '<td style="padding:7px 0;color:#6b7280;font-size:14px;width:160px;vertical-align:top;">' . esc_html( $label ) . '</td>'
                . '<td style="padding:7px 0;color:#111827;font-size:15px;vertical-align:top;">' . $value . '</td>'
                . '</tr>';
        }

        $cta_html = '';
        // Financial notices for player count changes (simplified in lite version)
        $financial_html = '';
        if ( false && $type === 'changed' && ! empty( $change_data['price_diff'] ) ) { // disabled in lite
            $diff      = (int) $change_data['price_diff'];
            $diff_fmt  = ERB_Helpers::format_price( abs( $diff ) );
            $old_fmt   = ERB_Helpers::format_price( $change_data['old_price'] );
            $new_fmt   = ERB_Helpers::format_price( $change_data['new_price'] );
            $old_p     = (int) $change_data['old_players'];
            $new_p     = (int) $change_data['new_players'];

            if ( $diff < 0 ) {
                // Fewer players — refund due
                $fin_colour = '#16a34a';
                $fin_icon   = '💰';
                $fin_title  = 'Refund Due: ' . $diff_fmt;
                $fin_body   = "You've reduced from " . $old_p . " to " . $new_p . " players. "
                            . "Your original payment was " . $old_fmt . " and your new price is " . $new_fmt . ". "
                            . "We'll process a refund of " . $diff_fmt . " to your original payment method shortly.";
            } else {
                // More players — additional payment needed
                $fin_colour = '#dc2626';
                $fin_icon   = '💳';
                $fin_title  = 'Additional Payment Required: ' . $diff_fmt;
                $fin_body   = "You've increased from " . $old_p . " to " . $new_p . " players. "
                            . "Your original payment was " . $old_fmt . " and your new price is " . $new_fmt . ". "
                            . "We'll be in touch to arrange the additional payment of " . $diff_fmt . ".";
            }

            $financial_html = '
            <table width="100%" cellpadding="0" cellspacing="0"
                   style="background:#f9fafb;border:2px solid ' . esc_attr( $fin_colour ) . ';border-radius:8px;margin-bottom:24px;">
                <tr><td style="padding:16px 20px;">
                    <p style="margin:0 0 6px;font-size:16px;font-weight:700;color:' . esc_attr( $fin_colour ) . ';">'
                    . $fin_icon . ' ' . esc_html( $fin_title ) . '</p>
                    <p style="margin:0;font-size:15px;color:#374151;line-height:1.6;">' . esc_html( $fin_body ) . '</p>
                </td></tr>
            </table>';
        }

        if ( $type === 'confirmation' || $type === 'changed' ) {
            $cta_html = $financial_html . '
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:16px;">
                <tr><td align="center">
                    <a href="' . esc_url( $manage_url ) . '"
                       style="display:inline-block;background:' . esc_attr( $colour ) . ';color:#ffffff;
                              text-decoration:none;font-size:16px;font-weight:600;padding:14px 32px;
                              border-radius:8px;">Manage My Booking</a>
                </td></tr>
            </table>
            <p style="margin:0 0 24px;font-size:13px;color:#9ca3af;text-align:center;">
                Use this link to change your date, time, number of players, or cancel your booking.
            </p>';
        } elseif ( $type === 'cancelled' ) {
            $cta_html = '
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:16px;">
                <tr><td align="center">
                    <a href="' . esc_url( $site_url ) . '"
                       style="display:inline-block;background:#2563eb;color:#ffffff;
                              text-decoration:none;font-size:16px;font-weight:600;padding:14px 32px;
                              border-radius:8px;">Book Again</a>
                </td></tr>
            </table>
            <p style="margin:0 0 24px;font-size:13px;color:#9ca3af;text-align:center;">
                Changed your mind? Head back to the calendar and choose a new time.
            </p>';
        }

        return '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>' . esc_html( $heading ) . '</title></head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:32px 16px;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">
<tr><td style="background:' . esc_attr( $colour ) . ';border-radius:8px 8px 0 0;padding:32px 40px;text-align:center;">
    <a href="' . esc_url( $site_url ) . '" style="text-decoration:none;">
        <h1 style="margin:0;color:#fff;font-size:26px;font-weight:700;">' . esc_html( $site ) . '</h1>
    </a>
    <p style="margin:10px 0 0;color:rgba(255,255,255,.85);font-size:18px;font-weight:500;">' . $heading . '</p>
</td></tr>
<tr><td style="background:#fff;padding:36px 40px;">
    <p style="margin:0 0 24px;font-size:16px;color:#374151;line-height:1.6;">' . $intro . '</p>
    <table width="100%" cellpadding="0" cellspacing="0"
           style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:28px;">
        <tr><td style="padding:20px 24px;">
            <table width="100%" cellpadding="0" cellspacing="0">' . $rows_html . '</table>
        </td></tr>
    </table>
    ' . $cta_html . '
    <p style="margin:0;font-size:14px;color:#6b7280;line-height:1.6;">
        If you have any questions please reply to this email or contact us via the website.
    </p>
</td></tr>
<tr><td style="background:#f9fafb;border:1px solid #e5e7eb;border-top:none;border-radius:0 0 8px 8px;
               padding:20px 40px;text-align:center;">
    <p style="margin:0;font-size:12px;color:#9ca3af;">
        &copy; ' . gmdate( 'Y' ) . ' <a href="' . esc_url( $site_url ) . '" style="color:#9ca3af;">' . esc_html( $site ) . '</a>.
        This email was sent to ' . esc_html( $booking->email ) . '.
    </p>
</td></tr>
</table>
</td></tr></table>
</body></html>';
    }

    private function customer_plain( $booking, $type, $change_data = array() ) {
        $site       = get_bloginfo( 'name' );
        $date       = date_i18n( get_option( 'erb_date_format', 'j F Y' ), strtotime( $booking->slot_start ) );
        $time       = date_i18n( 'H:i', strtotime( $booking->slot_start ) );
        $total      = ERB_Helpers::format_price( $booking->total_pence );
        $manage_url = ERB_Helpers::manage_booking_url( $booking->manage_token );

        switch ( $type ) {
            case 'confirmation': $heading = 'BOOKING CONFIRMED'; $intro = 'Your escape room booking is confirmed. We can\'t wait to see you!'; break;
            case 'changed':      $heading = 'BOOKING UPDATED';   $intro = 'Your booking has been updated. Here are your new details:'; break;
            case 'cancelled':    $heading = 'BOOKING CANCELLED'; $intro = 'Your booking has been cancelled as requested.'; break;
            default:             $heading = 'BOOKING UPDATE';    $intro = '';
        }

        $lines = array(
            $site . ' - ' . $heading,
            str_repeat( '-', 40 ),
            '',
            'Hi ' . $booking->first_name . ',',
            '',
            $intro,
            '',
            'BOOKING DETAILS',
            str_repeat( '-', 20 ),
            'Reference : ' . $booking->booking_ref,
            'Game      : ' . $booking->game_name,
            'Date      : ' . $date,
            'Time      : ' . $time,
            'Players   : ' . $booking->player_count,
            'Total     : ' . $total,
            'Name      : ' . $booking->first_name . ' ' . $booking->last_name,
            '',
        );

        // Financial notice for player count changes
        if ( $type === 'changed' && ! empty( $change_data['price_diff'] ) ) {
            $diff     = (int) $change_data['price_diff'];
            $diff_fmt = ERB_Helpers::format_price( abs( $diff ) );
            $old_fmt  = ERB_Helpers::format_price( $change_data['old_price'] );
            $new_fmt  = ERB_Helpers::format_price( $change_data['new_price'] );
            $lines[] = str_repeat( '-', 40 );
            if ( $diff < 0 ) {
                $lines[] = 'REFUND DUE: ' . $diff_fmt;
                $lines[] = "You reduced from " . $change_data['old_players'] . " to " . $change_data['new_players'] . " players.";
                $lines[] = 'Original payment: ' . $old_fmt . ' | New price: ' . $new_fmt;
                $lines[] = "We will process a refund of " . $diff_fmt . " to your payment card shortly.";
            } else {
                $lines[] = 'ADDITIONAL PAYMENT REQUIRED: ' . $diff_fmt;
                $lines[] = "You increased from " . $change_data['old_players'] . " to " . $change_data['new_players'] . " players.";
                $lines[] = 'Original payment: ' . $old_fmt . ' | New price: ' . $new_fmt;
                $lines[] = "We will be in touch to arrange the additional payment of " . $diff_fmt . ".";
            }
            $lines[] = str_repeat( '-', 40 );
            $lines[] = '';
        }

        if ( $type === 'confirmation' || $type === 'changed' ) {
            $lines[] = 'MANAGE YOUR BOOKING';
            $lines[] = 'Change date/time, player count, or cancel:';
            $lines[] = $manage_url;
            $lines[] = '';
        }

        if ( $type === 'cancelled' ) {
            $lines[] = 'BOOK AGAIN';
            $lines[] = 'Head back to our website to choose a new time:';
            $lines[] = home_url();
            $lines[] = '';
        }

        $lines[] = 'If you have any questions please contact us via the website.';
        $lines[] = '';
        $lines[] = $site;

        return implode( "\r\n", $lines );
    }

    // ── Staff emails ──────────────────────────────────────────────────────────

    private function send_to_staff( $booking, $type, $change_data = array() ) {
        $recipients = $this->staff_recipients();
        if ( empty( $recipients ) ) return;

        $subject = $this->staff_subject( $booking, $type );
        $html    = $this->staff_html( $booking, $type, $change_data );
        $plain   = $this->staff_plain( $booking, $type, $change_data );

        foreach ( $recipients as $email ) {
            $this->send( $email, $subject, $html, $plain );
        }
    }

    private function staff_recipients() {
        $recipients  = array();
        $admin_email = get_option( 'erb_admin_email', get_option( 'admin_email' ) );
        if ( $admin_email ) $recipients[] = $admin_email;

        foreach ( ERB_DB::get_gamekeepers( true ) as $gk ) {
            if ( ! in_array( $gk->email, $recipients ) ) {
                $recipients[] = $gk->email;
            }
        }

        return array_filter( $recipients );
    }

    private function staff_subject( $booking, $type ) {
        $site = get_bloginfo( 'name' );
        $ref  = $booking->booking_ref;
        switch ( $type ) {
            case 'confirmation': return sprintf( '[%s] New Booking - %s', $site, $ref );
            case 'changed':      return sprintf( '[%s] Booking Changed - %s', $site, $ref );
            case 'cancelled':    return sprintf( '[%s] Booking Cancelled - %s', $site, $ref );
        }
        return sprintf( '[%s] Booking Update - %s', $site, $ref );
    }

    private function staff_html( $booking, $type, $change_data = array() ) {
        $site      = get_bloginfo( 'name' );
        $date      = date_i18n( get_option( 'erb_date_format', 'j F Y' ), strtotime( $booking->slot_start ) );
        $time      = date_i18n( 'H:i', strtotime( $booking->slot_start ) );
        $total     = ERB_Helpers::format_price( $booking->total_pence );
        $admin_url = admin_url( 'admin.php?page=erb-bookings' );

        switch ( $type ) {
            case 'confirmation': $heading = 'New Booking Received'; $colour = '#16a34a'; break;
            case 'changed':      $heading = 'Booking Changed';       $colour = '#2563eb'; break;
            case 'cancelled':    $heading = 'Booking Cancelled';     $colour = '#dc2626'; break;
            default:             $heading = 'Booking Update';        $colour = '#2563eb';
        }

        $rows = array(
            'Booking Ref' => '<strong style="font-family:monospace;">' . esc_html( $booking->booking_ref ) . '</strong>',
            'Game'        => esc_html( $booking->game_name ),
            'Date'        => esc_html( $date ),
            'Time'        => esc_html( $time ),
            'Players'     => esc_html( $booking->player_count ),
            'Total'       => esc_html( $total ),
            'Customer'    => esc_html( $booking->first_name . ' ' . $booking->last_name ),
            'Email'       => '<a href="mailto:' . esc_attr( $booking->email ) . '">' . esc_html( $booking->email ) . '</a>',
            'Mobile'      => esc_html( $booking->mobile ?? '-' ),
        );

        $rows_html = '';
        foreach ( $rows as $label => $value ) {
            $rows_html .= '<tr>'
                . '<td style="padding:6px 0;color:#6b7280;font-size:13px;width:130px;vertical-align:top;">' . esc_html( $label ) . '</td>'
                . '<td style="padding:6px 0;color:#111827;font-size:14px;vertical-align:top;">' . $value . '</td>'
                . '</tr>';
        }

        $action_html = '';
        if ( $type === 'changed' && ! empty( $change_data['price_diff'] ) ) {
            $diff     = (int) $change_data['price_diff'];
            $diff_fmt = ERB_Helpers::format_price( abs( $diff ) );
            $old_fmt  = ERB_Helpers::format_price( $change_data['old_price'] );
            $new_fmt  = ERB_Helpers::format_price( $change_data['new_price'] );
            $old_p    = (int) $change_data['old_players'];
            $new_p    = (int) $change_data['new_players'];
            if ( $diff < 0 ) {
                $act_colour = '#16a34a';
                $act_bg     = '#f0fdf4';
                $act_border = '#bbf7d0';
                $act_title  = 'Action Required: Process Refund of ' . $diff_fmt;
                $act_body   = 'Players reduced from ' . $old_p . ' to ' . $new_p
                            . '. Original: ' . $old_fmt . ' | New: ' . $new_fmt
                            . '. Please process a refund of ' . $diff_fmt . ' via Stripe.';
            } else {
                $act_colour = '#dc2626';
                $act_bg     = '#fef2f2';
                $act_border = '#fecaca';
                $act_title  = 'Action Required: Collect Additional Payment of ' . $diff_fmt;
                $act_body   = 'Players increased from ' . $old_p . ' to ' . $new_p
                            . '. Original: ' . $old_fmt . ' | New: ' . $new_fmt
                            . '. Please contact the customer to collect ' . $diff_fmt . '.';
            }
            $action_html = '<table width="100%" cellpadding="0" cellspacing="0" style="background:' . esc_attr( $act_bg ) . ';border:2px solid ' . esc_attr( $act_border ) . ';border-radius:8px;margin-bottom:20px;">';
            $action_html .= '<tr><td style="padding:16px 20px;">';
            $action_html .= '<p style="margin:0 0 6px;font-size:15px;font-weight:700;color:' . esc_attr( $act_colour ) . '">&#9888; ' . esc_html( $act_title ) . '</p>';
            $action_html .= '<p style="margin:0;font-size:14px;color:#374151;line-height:1.6;">' . esc_html( $act_body ) . '</p>';
            $action_html .= '</td></tr></table>';
        }

        $html  = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"></head>';
        $html .= '<body style="margin:0;padding:0;background:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,sans-serif;">';
        $html .= '<table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:32px 16px;"><tr><td align="center">';
        $html .= '<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">';
        $html .= '<tr><td style="background:' . esc_attr( $colour ) . ';border-radius:8px 8px 0 0;padding:24px 40px;">';
        $html .= '<h1 style="margin:0;color:#fff;font-size:20px;font-weight:700;">' . esc_html( $site ) . '</h1>';
        $html .= '<p style="margin:6px 0 0;color:rgba(255,255,255,.85);font-size:16px;">' . esc_html( $heading ) . '</p></td></tr>';
        $html .= '<tr><td style="background:#fff;padding:32px 40px;">';
        $html .= $action_html;
        $html .= '<table width="100%" cellpadding="0" cellspacing="0" style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:24px;">';
        $html .= '<tr><td style="padding:20px 24px;"><table width="100%" cellpadding="0" cellspacing="0">' . $rows_html . '</table></td></tr></table>';
        $html .= '<table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center">';
        $html .= '<a href="' . esc_url( $admin_url ) . '" style="display:inline-block;background:' . esc_attr( $colour ) . ';color:#fff;text-decoration:none;font-size:15px;font-weight:600;padding:12px 28px;border-radius:6px;">View in Admin</a>';
        $html .= '</td></tr></table></td></tr>';
        $html .= '<tr><td style="background:#f9fafb;border:1px solid #e5e7eb;border-top:none;border-radius:0 0 8px 8px;padding:16px 40px;text-align:center;">';
        $html .= '<p style="margin:0;font-size:12px;color:#9ca3af;">' . esc_html( $site ) . ' - Staff Notification</p></td></tr>';
        $html .= '</table></td></tr></table></body></html>';
        return $html;
    }

    private function staff_plain( $booking, $type, $change_data = array() ) {
        $site  = get_bloginfo( 'name' );
        $date  = date_i18n( get_option( 'erb_date_format', 'j F Y' ), strtotime( $booking->slot_start ) );
        $time  = date_i18n( 'H:i', strtotime( $booking->slot_start ) );
        $total = ERB_Helpers::format_price( $booking->total_pence );

        switch ( $type ) {
            case 'confirmation': $heading = 'NEW BOOKING';       break;
            case 'changed':      $heading = 'BOOKING CHANGED';   break;
            case 'cancelled':    $heading = 'BOOKING CANCELLED'; break;
            default:             $heading = 'BOOKING UPDATE';
        }

        $lines = array(
            $site . ' - ' . $heading,
            str_repeat( '-', 40 ),
            '',
            'Ref      : ' . $booking->booking_ref,
            'Game     : ' . $booking->game_name,
            'Date     : ' . $date,
            'Time     : ' . $time,
            'Players  : ' . $booking->player_count,
            'Total    : ' . $total,
            'Customer : ' . $booking->first_name . ' ' . $booking->last_name,
            'Email    : ' . $booking->email,
            'Mobile   : ' . ( $booking->mobile ?? '-' ),
            '',
        );
        if ( $type === 'changed' && ! empty( $change_data['price_diff'] ) ) {
            $diff     = (int) $change_data['price_diff'];
            $diff_fmt = ERB_Helpers::format_price( abs( $diff ) );
            $old_fmt  = ERB_Helpers::format_price( $change_data['old_price'] );
            $new_fmt  = ERB_Helpers::format_price( $change_data['new_price'] );
            $lines[] = str_repeat( '=', 40 );
            if ( $diff < 0 ) {
                $lines[] = 'ACTION REQUIRED: PROCESS REFUND OF ' . $diff_fmt;
                $lines[] = 'Players: ' . $change_data['old_players'] . ' -> ' . $change_data['new_players'] . ' | Was: ' . $old_fmt . ' | Now: ' . $new_fmt;
                $lines[] = 'Please process a refund of ' . $diff_fmt . ' via Stripe.';
            } else {
                $lines[] = 'ACTION REQUIRED: COLLECT ADDITIONAL PAYMENT OF ' . $diff_fmt;
                $lines[] = 'Players: ' . $change_data['old_players'] . ' -> ' . $change_data['new_players'] . ' | Was: ' . $old_fmt . ' | Now: ' . $new_fmt;
                $lines[] = 'Please contact the customer to collect ' . $diff_fmt . '.';
            }
            $lines[] = str_repeat( '=', 40 );
            $lines[] = '';
        }
        $lines[] = 'View bookings: ' . admin_url( 'admin.php?page=erb-bookings' );
        return implode( "\r\n", $lines );
    }

    // ── Core send ─────────────────────────────────────────────────────────────

    private function send( $to, $subject, $html, $plain ) {
        $from_name  = get_option( 'erb_email_from_name',    get_bloginfo( 'name' ) );
        $from_email = get_option( 'erb_email_from_address', get_option( 'admin_email' ) );

        // Use Content-Type header only - let wp_mail/PHPMailer handle
        // subject encoding and MIME structure to avoid double-encoding.
        $headers = array(
            'From: ' . $from_name . ' <' . $from_email . '>',
            'Content-Type: text/html; charset=UTF-8',
        );

        // Replace em-dash and other special chars in subject with plain equivalents
        // so PHPMailer does not produce garbled encoded subjects
        $subject = str_replace( array( "â", "â", "â" ),
                                array( '-', '-', "'" ), $subject );

        $sent = wp_mail( $to, $subject, $html, $headers );

        if ( ! $sent ) {
            error_log( '[ERB Emails] Failed to send "' . $subject . '" to ' . $to );
        }

        return $sent;
    }
}
