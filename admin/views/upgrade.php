<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap erb-admin-page">

    <div class="erb-card" style="max-width:700px;text-align:center;padding:48px 40px;">

        <div style="font-size:3rem;margin-bottom:16px;">&#x1F680;</div>

        <h1 style="font-family:Georgia,serif;font-size:2rem;color:#1e3a5f;margin-bottom:12px;">
            <?php esc_html_e( 'Upgrade to Escape Room Booking Pro', 'ettrick-escape-room-booking' ); ?>
        </h1>

        <p style="font-size:1.05rem;color:#64748b;max-width:500px;margin:0 auto 32px;line-height:1.7;">
            <?php esc_html_e( 'You are currently using the free version which supports up to 2 games. Upgrade to Pro to unlock everything.', 'ettrick-escape-room-booking' ); ?>
        </p>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;text-align:left;margin-bottom:36px;max-width:560px;margin-left:auto;margin-right:auto;">
            <?php
            $features = array(
                array( '&#x1F3AE;', 'Unlimited Games',         'Add as many games and rooms as your venue needs.' ),
                array( '&#x1F3F7;&#xFE0F;', 'Promo Codes',    'Create discount codes with date ranges and use limits.' ),
                array( '&#x1F4CA;', 'Revenue Reports',         'Track revenue by game, date range and promo code.' ),
                array( '&#x1F465;', 'Gamekeepers',             'Multiple staff members receive booking notifications.' ),
                array( '&#x1F517;', 'Shared Room Support',     'Prevent double-bookings across games sharing a room.' ),
                array( '&#x2709;&#xFE0F;', 'Branded Emails',   'Fully branded HTML emails with your venue colours.' ),
                array( '&#x23F1;&#xFE0F;', 'Booking Controls', 'Set min notice periods and booking horizon per game.' ),
                array( '&#x1F464;', 'Customer Accounts',       'Customers can log in to view all their bookings.' ),
            );
            foreach ( $features as $f ) : ?>
            <div style="background:#f8f9fb;border:1px solid #e2e8f0;border-radius:8px;padding:14px 16px;display:flex;gap:12px;align-items:flex-start;">
                <span style="font-size:1.3rem;flex-shrink:0;"><?php echo esc_html( $f[0] ); ?></span>
                <div>
                    <strong style="display:block;font-size:.9rem;color:#1e293b;margin-bottom:2px;"><?php echo esc_html( $f[1] ); ?></strong>
                    <span style="font-size:.8rem;color:#64748b;"><?php echo esc_html( $f[2] ); ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <a href="https://escaperoombookingpro.com" target="_blank"
           class="erb-btn erb-btn--primary"
           style="display:inline-flex;width:auto;font-size:1.05rem;padding:14px 36px;gap:10px;text-decoration:none;">
            <?php esc_html_e( 'Get Escape Room Booking Pro', 'ettrick-escape-room-booking' ); ?>
            <span style="font-size:.9rem;opacity:.8;">&#x2192;</span>
        </a>

        <p style="margin-top:16px;font-size:.82rem;color:#94a3b8;">
            <?php esc_html_e( 'One-time payment &middot; Single-site licence &middot; No monthly fees', 'ettrick-escape-room-booking' ); ?>
        </p>

        <div style="margin-top:32px;padding-top:24px;border-top:1px solid #e2e8f0;">
            <p style="font-size:.875rem;color:#64748b;">
                <?php esc_html_e( 'Already purchased? Visit', 'ettrick-escape-room-booking' ); ?>
                <a href="https://escaperoombookingpro.com" target="_blank" style="color:#e8621a;">escaperoombookingpro.com</a>
                <?php esc_html_e( 'to download the Pro version and get your licence key.', 'ettrick-escape-room-booking' ); ?>
            </p>
        </div>

    </div>
</div>
