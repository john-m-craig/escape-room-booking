<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap erb-admin-page">
    <h1><?php esc_html_e( 'Settings', 'ettrick-escape-room-booking' ); ?></h1>

    <?php settings_errors( 'erb_settings_group' ); ?>

    <form method="post" action="options.php">
        <?php settings_fields( 'erb_settings_group' ); ?>

        <div class="erb-card">
            <h2><?php esc_html_e( 'General', 'ettrick-escape-room-booking' ); ?></h2>
            <div class="erb-form-row">
                <div class="erb-form-group">
                    <label><?php esc_html_e( 'Currency Code', 'ettrick-escape-room-booking' ); ?></label>
                    <input type="text" name="erb_currency" value="<?php echo esc_attr( get_option( 'erb_currency', 'GBP' ) ); ?>" maxlength="3">
                </div>
                <div class="erb-form-group">
                    <label><?php esc_html_e( 'Currency Symbol', 'ettrick-escape-room-booking' ); ?></label>
                    <input type="text" name="erb_currency_symbol" value="<?php echo esc_attr( get_option( 'erb_currency_symbol', '£' ) ); ?>" maxlength="5">
                </div>
                <div class="erb-form-group">
                    <label><?php esc_html_e( 'Slot Hold (minutes)', 'ettrick-escape-room-booking' ); ?></label>
                    <input type="number" name="erb_slot_hold_minutes" value="<?php echo (int) get_option( 'erb_slot_hold_minutes', 15 ); ?>" min="5" max="60">
                </div>
            </div>
            <div class="erb-form-row">
                <div class="erb-form-group">
                    <label><?php esc_html_e( 'Available Slot Colour', 'ettrick-escape-room-booking' ); ?></label>
                    <input type="color" name="erb_slot_available_color" value="<?php echo esc_attr( get_option( 'erb_slot_available_color', '#22c55e' ) ); ?>">
                </div>
                <div class="erb-form-group">
                    <label><?php esc_html_e( 'Booked Slot Colour', 'ettrick-escape-room-booking' ); ?></label>
                    <input type="color" name="erb_slot_booked_color" value="<?php echo esc_attr( get_option( 'erb_slot_booked_color', '#ef4444' ) ); ?>">
                </div>
            </div>
        </div>

        <div class="erb-card">
            <h2><?php esc_html_e( 'Email', 'ettrick-escape-room-booking' ); ?></h2>
            <div class="erb-form-row">
                <div class="erb-form-group">
                    <label><?php esc_html_e( 'From Name', 'ettrick-escape-room-booking' ); ?></label>
                    <input type="text" name="erb_email_from_name" value="<?php echo esc_attr( get_option( 'erb_email_from_name' ) ); ?>">
                </div>
                <div class="erb-form-group">
                    <label><?php esc_html_e( 'From Email Address', 'ettrick-escape-room-booking' ); ?></label>
                    <input type="email" name="erb_email_from_address" value="<?php echo esc_attr( get_option( 'erb_email_from_address' ) ); ?>">
                </div>
                <div class="erb-form-group">
                    <label><?php esc_html_e( 'Admin Notification Email', 'ettrick-escape-room-booking' ); ?></label>
                    <input type="email" name="erb_admin_email" value="<?php echo esc_attr( get_option( 'erb_admin_email' ) ); ?>">
                </div>
            </div>
        </div>

        <div class="erb-card">
            <h2><?php esc_html_e( 'Stripe Payments', 'ettrick-escape-room-booking' ); ?></h2>
            <div class="erb-form-row">
                <div class="erb-form-group">
                    <label><?php esc_html_e( 'Mode', 'ettrick-escape-room-booking' ); ?></label>
                    <select name="erb_stripe_mode">
                        <option value="test" <?php selected( get_option( 'erb_stripe_mode' ), 'test' ); ?>><?php esc_html_e( 'Test', 'ettrick-escape-room-booking' ); ?></option>
                        <option value="live" <?php selected( get_option( 'erb_stripe_mode' ), 'live' ); ?>><?php esc_html_e( 'Live', 'ettrick-escape-room-booking' ); ?></option>
                    </select>
                </div>
            </div>
            <div class="erb-form-row">
                <div class="erb-form-group">
                    <label><?php esc_html_e( 'Test Publishable Key', 'ettrick-escape-room-booking' ); ?></label>
                    <input type="text" name="erb_stripe_test_pk" value="<?php echo esc_attr( get_option( 'erb_stripe_test_pk' ) ); ?>" placeholder="pk_test_...">
                </div>
                <div class="erb-form-group">
                    <label><?php esc_html_e( 'Test Secret Key', 'ettrick-escape-room-booking' ); ?></label>
                    <input type="password" name="erb_stripe_test_sk" value="<?php echo esc_attr( get_option( 'erb_stripe_test_sk' ) ); ?>" placeholder="sk_test_...">
                </div>
            </div>
            <div class="erb-form-row">
                <div class="erb-form-group">
                    <label><?php esc_html_e( 'Live Publishable Key', 'ettrick-escape-room-booking' ); ?></label>
                    <input type="text" name="erb_stripe_live_pk" value="<?php echo esc_attr( get_option( 'erb_stripe_live_pk' ) ); ?>" placeholder="pk_live_...">
                </div>
                <div class="erb-form-group">
                    <label><?php esc_html_e( 'Live Secret Key', 'ettrick-escape-room-booking' ); ?></label>
                    <input type="password" name="erb_stripe_live_sk" value="<?php echo esc_attr( get_option( 'erb_stripe_live_sk' ) ); ?>" placeholder="sk_live_...">
                </div>
            </div>
            <div class="erb-notice erb-notice--info" style="margin-bottom:1rem;">
                <strong><?php esc_html_e( 'Webhook Setup:', 'ettrick-escape-room-booking' ); ?></strong>
                <?php esc_html_e( 'In your Stripe Dashboard → Developers → Webhooks → Add endpoint, use the URL below and select events:', 'ettrick-escape-room-booking' ); ?>
                <code>payment_intent.succeeded</code> <?php esc_html_e( 'and', 'ettrick-escape-room-booking' ); ?> <code>payment_intent.payment_failed</code>.
                <?php esc_html_e( 'Then copy the Signing Secret into the field below.', 'ettrick-escape-room-booking' ); ?>
            </div>
            <div class="erb-form-row">
                <div class="erb-form-group" style="flex:3;">
                    <label><?php esc_html_e( 'Webhook Endpoint URL', 'ettrick-escape-room-booking' ); ?></label>
                    <div style="display:flex;align-items:center;gap:.5rem;">
                        <input type="text" id="erb-webhook-url" value="<?php echo esc_attr( admin_url( 'admin-ajax.php?action=erb_stripe_webhook' ) ); ?>" readonly style="background:#f9fafb;color:#374151;">
                        <button type="button" class="erb-btn erb-btn--outline erb-btn--sm erb-btn--auto"
                                onclick="navigator.clipboard.writeText(document.getElementById('erb-webhook-url').value).then(function(){ this.textContent='Copied!'; }.bind(this))">
                            <?php esc_html_e( 'Copy', 'ettrick-escape-room-booking' ); ?>
                        </button>
                    </div>
                </div>
            </div>
            <div class="erb-form-row">
                <div class="erb-form-group" style="flex:2;">
                    <label><?php esc_html_e( 'Webhook Signing Secret', 'ettrick-escape-room-booking' ); ?></label>
                    <input type="password" name="erb_stripe_webhook_secret" value="<?php echo esc_attr( get_option( 'erb_stripe_webhook_secret' ) ); ?>" placeholder="whsec_...">
                    <small style="color:#9ca3af;"><?php esc_html_e( 'Found in Stripe Dashboard after creating the webhook endpoint.', 'ettrick-escape-room-booking' ); ?></small>
                </div>
            </div>
        </div>

        <div class="erb-card">
            <h2><?php esc_html_e( 'Pages', 'ettrick-escape-room-booking' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Set the URL of the page containing the [erb_booking] shortcode. Visitors are sent here after clicking an available slot.', 'ettrick-escape-room-booking' ); ?></p>
            <div class="erb-form-row">
                <div class="erb-form-group" style="flex:3;">
                    <label><?php esc_html_e( 'Booking Page URL', 'ettrick-escape-room-booking' ); ?></label>
                    <input type="url" name="erb_booking_page_url" value="<?php echo esc_attr( get_option( 'erb_booking_page_url', '' ) ); ?>" placeholder="https://yoursite.com/book/">
                    <small style="color:#9ca3af;"><?php esc_html_e( 'Page containing the [erb_booking] shortcode.', 'ettrick-escape-room-booking' ); ?></small>
                </div>
            </div>
            <div class="erb-form-row">
                <div class="erb-form-group" style="flex:3;">
                    <label><?php esc_html_e( 'Calendar Home URL', 'ettrick-escape-room-booking' ); ?></label>
                    <input type="url" name="erb_calendar_home_url" value="<?php echo esc_attr( get_option( 'erb_calendar_home_url', home_url( '/' ) ) ); ?>" placeholder="https://yoursite.com/escape-rooms/">
                    <small style="color:#9ca3af;"><?php esc_html_e( 'Where "Go to Calendar" and "Book Again" buttons link to. Usually your games listing page.', 'ettrick-escape-room-booking' ); ?></small>
                </div>
            </div>
            <div class="erb-form-row">
                <div class="erb-form-group">
                    <label><?php esc_html_e( 'Date Display Format', 'ettrick-escape-room-booking' ); ?></label>
                    <input type="text" name="erb_date_format" value="<?php echo esc_attr( get_option( 'erb_date_format', 'j F Y' ) ); ?>" placeholder="j F Y">
                    <small style="color:#9ca3af;">
                        <?php esc_html_e( 'PHP date format used in booking pages and emails. Examples: j F Y = 27 March 2026 &nbsp;|&nbsp; d/m/Y = 27/03/2026 &nbsp;|&nbsp; F j, Y = March 27, 2026', 'ettrick-escape-room-booking' ); ?>
                    </small>
                </div>
            </div>
            <div class="erb-form-row">
                <div class="erb-form-group" style="flex:3;">
                    <label><?php esc_html_e( 'Manage Booking Page URL', 'ettrick-escape-room-booking' ); ?></label>
                    <input type="url" name="erb_manage_page_url" value="<?php echo esc_attr( get_option( 'erb_manage_page_url', '' ) ); ?>" placeholder="https://yoursite.com/manage-booking/">
                    <small style="color:#9ca3af;"><?php esc_html_e( 'Page containing the [erb_manage_booking] shortcode. Links in confirmation emails point here.', 'ettrick-escape-room-booking' ); ?></small>
                </div>
            </div>
        </div>

        <?php if ( ! defined( 'ERB_LITE' ) ) : ?>
        <div class="erb-card">
            <h2><?php esc_html_e( 'Licence Server', 'ettrick-escape-room-booking' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Set the API Secret to match the value in your licence server config.php. This ensures responses from your licence server are genuine.', 'ettrick-escape-room-booking' ); ?></p>
            <div class="erb-form-row">
                <div class="erb-form-group" style="flex:2;">
                    <label><?php esc_html_e( 'Licence API Secret', 'ettrick-escape-room-booking' ); ?></label>
                    <input type="password" name="erbpro_api_secret" value="<?php echo esc_attr( get_option( 'erbpro_api_secret', '' ) ); ?>" placeholder="Your 32-character random string">
                    <small style="color:#9ca3af;"><?php esc_html_e( 'Must match API_SECRET in your licence server config.php. Generate one at randomkeygen.com.', 'ettrick-escape-room-booking' ); ?></small>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php submit_button( __( 'Save Settings', 'ettrick-escape-room-booking' ) ); ?>
    </form>
</div>
