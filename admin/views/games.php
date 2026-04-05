<?php if ( ! defined( 'ABSPATH' ) ) exit;
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
define( 'ERB_LITE_GAME_LIMIT', 2 );
$lite_game_count = count( ERB_DB::get_games( false ) );
$lite_at_limit   = defined( 'ERB_LITE' ) && $lite_game_count >= ERB_LITE_GAME_LIMIT;
?>
<div class="wrap erb-admin-page">
    <h1><?php esc_html_e( 'Games & Rooms', 'escape-room-booking' ); ?></h1>

    <?php
    $rooms = ERB_DB::get_rooms();
    $games = ERB_DB::get_games( false );
    ?>

    <!-- ── Rooms ──────────────────────────────────────────────────────────── -->
    <div class="erb-card">
        <h2>
            <?php esc_html_e( 'Physical Rooms', 'escape-room-booking' ); ?>
            <button class="erb-btn erb-btn--primary erb-btn--sm" style="float:right;"
                    onclick="ERBGames.openRoomModal()">
                + <?php esc_html_e( 'Add Room', 'escape-room-booking' ); ?>
            </button>
        </h2>
        <p class="description" style="margin-top:0;">
            <?php esc_html_e( 'Physical rooms your games take place in. If two games share one room, assign them both to the same room — the plugin will automatically block the other game when one is booked.', 'escape-room-booking' ); ?>
        </p>

        <?php if ( empty( $rooms ) ) : ?>
            <p><em><?php esc_html_e( 'No rooms yet. Add your first room above.', 'escape-room-booking' ); ?></em></p>
        <?php else : ?>
        <table class="erb-table">
            <thead><tr>
                <th><?php esc_html_e( 'Room Name', 'escape-room-booking' ); ?></th>
                <th><?php esc_html_e( 'Games Using This Room', 'escape-room-booking' ); ?></th>
                <th><?php esc_html_e( 'Actions', 'escape-room-booking' ); ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ( $rooms as $room ) :
                $room_games = array_filter( $games, function( $g ) use ( $room ) { return $g->room_id == $room->id; } );
            ?>
                <tr data-room-id="<?php echo (int) $room->id; ?>">
                    <td><strong><?php echo esc_html( $room->name ); ?></strong></td>
                    <td>
                        <?php if ( empty( $room_games ) ) : ?>
                            <em style="color:#9ca3af;"><?php esc_html_e( 'None assigned', 'escape-room-booking' ); ?></em>
                        <?php else : ?>
                            <?php foreach ( $room_games as $rg ) : ?>
                                <span class="erb-badge erb-badge--confirmed" style="margin-right:4px;"><?php echo esc_html( $rg->name ); ?></span>
                            <?php endforeach; ?>
                            <?php if ( count( $room_games ) >= 2 ) : ?>
                                <span class="erb-badge erb-badge--pending" style="margin-left:4px;" title="<?php esc_attr_e( 'Booking one game blocks the other', 'escape-room-booking' ); ?>">
                                    ⚠ <?php esc_html_e( 'Shared Room', 'escape-room-booking' ); ?>
                                </span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="erb-btn erb-btn--outline erb-btn--sm"
                                onclick="ERBGames.openRoomModal(<?php echo (int) $room->id; ?>, <?php echo esc_attr( json_encode( array( 'name' => $room->name, 'description' => $room->description ) ) ); ?>)">
                            <?php esc_html_e( 'Edit', 'escape-room-booking' ); ?>
                        </button>
                        <?php if ( empty( $room_games ) ) : ?>
                        <button class="erb-btn erb-btn--danger erb-btn--sm erb-delete"
                                data-action="erb_delete_room"
                                data-id="<?php echo (int) $room->id; ?>"
                                data-label="<?php echo esc_attr( $room->name ); ?>">
                            <?php esc_html_e( 'Delete', 'escape-room-booking' ); ?>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- ── Games ─────────────────────────────────────────────────────────── -->
    <div class="erb-card">
        <h2>
            <?php esc_html_e( 'Games', 'escape-room-booking' ); ?>
            <?php if ( ! empty( $rooms ) ) : ?>
            <button class="erb-btn erb-btn--primary erb-btn--sm"
                    style="float:right;<?php echo $lite_at_limit ? 'opacity:.5;cursor:not-allowed;' : ''; ?>"
                    data-at-limit="<?php echo $lite_at_limit ? '1' : '0'; ?>"
                    data-upgrade-url="<?php echo esc_url( admin_url( 'admin.php?page=erb-upgrade' ) ); ?>"
                    onclick="ERBGamesLite.addGameClick(this)">
                + <?php esc_html_e( 'Add Game', 'escape-room-booking' ); ?>
            </button>
            <?php else : ?>
            <span style="float:right;font-size:.8rem;color:#9ca3af;">
                <?php esc_html_e( 'Add a room first', 'escape-room-booking' ); ?>
            </span>
            <?php endif; ?>
        </h2>

        <?php if ( empty( $games ) ) : ?>
            <p><em><?php esc_html_e( 'No games yet.', 'escape-room-booking' ); ?></em></p>
        <?php else : ?>
        <table class="erb-table">
            <thead><tr>
                <th><?php esc_html_e( 'Game', 'escape-room-booking' ); ?></th>
                <th><?php esc_html_e( 'Room', 'escape-room-booking' ); ?></th>
                <th><?php esc_html_e( 'Duration', 'escape-room-booking' ); ?></th>
                <th><?php esc_html_e( 'Min Notice', 'escape-room-booking' ); ?></th>
                <th><?php esc_html_e( 'Horizon', 'escape-room-booking' ); ?></th>
                <th><?php esc_html_e( 'Status', 'escape-room-booking' ); ?></th>
                <th><?php esc_html_e( 'Actions', 'escape-room-booking' ); ?></th>
            </tr></thead>
            <tbody id="erb-games-tbody">
            <?php foreach ( $games as $game ) : ?>
                <tr data-game-id="<?php echo (int) $game->id; ?>">
                    <td>
                        <strong><?php echo esc_html( $game->name ); ?></strong><br>
                        <small style="color:#9ca3af;"><?php echo esc_html( $game->slug ); ?></small>
                    </td>
                    <td><?php echo esc_html( $game->room_name ); ?></td>
                    <td><?php echo (int) $game->duration_minutes; ?>min + <?php echo (int) $game->setup_minutes; ?>min setup</td>
                    <td><?php echo (int) $game->min_notice_hours; ?>h</td>
                    <td>
                        <?php echo $game->booking_horizon_date
                            ? esc_html( gmdate( 'd M Y', strtotime( $game->booking_horizon_date ) ) )
                            : '<em style="color:#9ca3af;">None</em>'; ?>
                    </td>
                    <td><span class="erb-badge erb-badge--<?php echo $game->status === 'active' ? 'confirmed' : 'cancelled'; ?>"><?php echo esc_html( $game->status ); ?></span></td>
                    <td style="white-space:nowrap;">
                        <button class="erb-btn erb-btn--outline erb-btn--sm"
                                onclick="ERBGames.openGameModal(<?php echo (int) $game->id; ?>)">
                            <?php esc_html_e( 'Edit', 'escape-room-booking' ); ?>
                        </button>
                        <button class="erb-btn erb-btn--outline erb-btn--sm"
                                onclick="ERBGames.openHoursModal(<?php echo (int) $game->id; ?>, <?php echo esc_attr( json_encode( $game->name ) ); ?>)">
                            <?php esc_html_e( 'Hours', 'escape-room-booking' ); ?>
                        </button>
                        <button class="erb-btn erb-btn--outline erb-btn--sm"
                                onclick="ERBGames.openPricingModal(<?php echo (int) $game->id; ?>, <?php echo esc_attr( json_encode( $game->name ) ); ?>)">
                            <?php esc_html_e( 'Pricing', 'escape-room-booking' ); ?>
                        </button>
                        <button class="erb-btn erb-btn--danger erb-btn--sm erb-delete"
                                data-action="erb_delete_game"
                                data-id="<?php echo (int) $game->id; ?>"
                                data-label="<?php echo esc_attr( $game->name ); ?>">
                            <?php esc_html_e( 'Delete', 'escape-room-booking' ); ?>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- ── Room Modal ─────────────────────────────────────────────────────────── -->
<div class="erb-modal-overlay" id="erb-room-modal">
    <div class="erb-modal">
        <button class="erb-modal__close" onclick="ERB.closeModal('erb-room-modal')">&times;</button>
        <h2 id="erb-room-modal-title"><?php esc_html_e( 'Add Room', 'escape-room-booking' ); ?></h2>
        <input type="hidden" id="erb-room-id" value="">
        <div class="erb-form-group">
            <label for="erb-room-name"><?php esc_html_e( 'Room Name', 'escape-room-booking' ); ?> *</label>
            <input type="text" id="erb-room-name" placeholder="e.g. Room A">
        </div>
        <div class="erb-form-group" style="margin-top:.75rem;">
            <label for="erb-room-desc"><?php esc_html_e( 'Description (optional)', 'escape-room-booking' ); ?></label>
            <input type="text" id="erb-room-desc" placeholder="">
        </div>
        <div style="margin-top:1.25rem;display:flex;gap:.75rem;justify-content:flex-end;">
            <button class="erb-btn erb-btn--outline erb-btn--auto" onclick="ERB.closeModal('erb-room-modal')">
                <?php esc_html_e( 'Cancel', 'escape-room-booking' ); ?>
            </button>
            <button class="erb-btn erb-btn--primary erb-btn--auto" onclick="ERBGames.saveRoom()">
                <?php esc_html_e( 'Save Room', 'escape-room-booking' ); ?>
            </button>
        </div>
    </div>
</div>

<!-- ── Game Modal ─────────────────────────────────────────────────────────── -->
<div class="erb-modal-overlay" id="erb-game-modal">
    <div class="erb-modal" style="max-width:640px;">
        <button class="erb-modal__close" onclick="ERB.closeModal('erb-game-modal')">&times;</button>
        <h2 id="erb-game-modal-title"><?php esc_html_e( 'Add Game', 'escape-room-booking' ); ?></h2>
        <input type="hidden" id="erb-game-id" value="">

        <div class="erb-form-row">
            <div class="erb-form-group" style="flex:2;">
                <label><?php esc_html_e( 'Game Name', 'escape-room-booking' ); ?> *</label>
                <input type="text" id="erb-game-name" placeholder="e.g. The Lost Temple">
            </div>
            <div class="erb-form-group" style="flex:2;">
                <label><?php esc_html_e( 'URL Slug', 'escape-room-booking' ); ?> *</label>
                <input type="text" id="erb-game-slug" placeholder="e.g. lost-temple">
            </div>
        </div>

        <div class="erb-form-row">
            <div class="erb-form-group">
                <label><?php esc_html_e( 'Physical Room', 'escape-room-booking' ); ?> *</label>
                <select id="erb-game-room">
                    <option value=""><?php esc_html_e( '— Select Room —', 'escape-room-booking' ); ?></option>
                    <?php foreach ( $rooms as $r ) : ?>
                        <option value="<?php echo (int) $r->id; ?>"><?php echo esc_html( $r->name ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="erb-form-group">
                <label><?php esc_html_e( 'Status', 'escape-room-booking' ); ?></label>
                <select id="erb-game-status">
                    <option value="active"><?php esc_html_e( 'Active', 'escape-room-booking' ); ?></option>
                    <option value="inactive"><?php esc_html_e( 'Inactive', 'escape-room-booking' ); ?></option>
                </select>
            </div>
        </div>

        <div class="erb-form-row">
            <div class="erb-form-group">
                <label><?php esc_html_e( 'Play Duration (mins)', 'escape-room-booking' ); ?></label>
                <input type="number" id="erb-game-duration" value="60" min="15" max="240">
            </div>
            <div class="erb-form-group">
                <label><?php esc_html_e( 'Setup/Turnaround (mins)', 'escape-room-booking' ); ?></label>
                <input type="number" id="erb-game-setup" value="30" min="0" max="120">
            </div>
        </div>

        <div class="erb-form-row">
            <div class="erb-form-group">
                <label><?php esc_html_e( 'Min Booking Notice (hours)', 'escape-room-booking' ); ?></label>
                <input type="number" id="erb-game-notice" value="2" min="0" max="168">
                <small style="color:#9ca3af;"><?php esc_html_e( 'Slots within this many hours of now will be unavailable.', 'escape-room-booking' ); ?></small>
            </div>
            <div class="erb-form-group">
                <label><?php esc_html_e( 'Booking Horizon Date', 'escape-room-booking' ); ?></label>
                <input type="date" id="erb-game-horizon">
                <small style="color:#9ca3af;"><?php esc_html_e( 'No slots shown beyond this date. Leave blank for no limit.', 'escape-room-booking' ); ?></small>
            </div>
        </div>

        <div class="erb-form-group">
            <label><?php esc_html_e( 'Description (shown on calendar page)', 'escape-room-booking' ); ?></label>
            <textarea id="erb-game-description" rows="3" style="width:100%;padding:.45rem .7rem;border:1px solid #e5e7eb;border-radius:6px;font-size:.9rem;"></textarea>
        </div>

        <div class="erb-form-group">
            <label><?php esc_html_e( 'Image URL', 'escape-room-booking' ); ?></label>
            <input type="url" id="erb-game-image" placeholder="https://...">
        </div>

        <div style="margin-top:1.25rem;display:flex;gap:.75rem;justify-content:flex-end;">
            <button class="erb-btn erb-btn--outline erb-btn--auto" onclick="ERB.closeModal('erb-game-modal')">
                <?php esc_html_e( 'Cancel', 'escape-room-booking' ); ?>
            </button>
            <button class="erb-btn erb-btn--primary erb-btn--auto" onclick="ERBGames.saveGame()">
                <?php esc_html_e( 'Save Game', 'escape-room-booking' ); ?>
            </button>
        </div>
    </div>
</div>

<!-- ── Hours Modal ────────────────────────────────────────────────────────── -->
<div class="erb-modal-overlay" id="erb-hours-modal">
    <div class="erb-modal" style="max-width:520px;">
        <button class="erb-modal__close" onclick="ERB.closeModal('erb-hours-modal')">&times;</button>
        <h2 id="erb-hours-modal-title"><?php esc_html_e( 'Operating Hours', 'escape-room-booking' ); ?></h2>
        <input type="hidden" id="erb-hours-game-id" value="">
        <p class="description"><?php esc_html_e( 'Set opening and closing times per day. Leave blank or tick Closed to mark a day as unavailable.', 'escape-room-booking' ); ?></p>

        <table class="erb-table" style="font-size:.85rem;">
            <thead><tr>
                <th style="width:90px;"><?php esc_html_e( 'Day', 'escape-room-booking' ); ?></th>
                <th><?php esc_html_e( 'Open', 'escape-room-booking' ); ?></th>
                <th><?php esc_html_e( 'Close', 'escape-room-booking' ); ?></th>
                <th style="width:70px;"><?php esc_html_e( 'Closed', 'escape-room-booking' ); ?></th>
            </tr></thead>
            <tbody>
            <?php
            $days = array(
                1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday',
                4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 0 => 'Sunday'
            );
            foreach ( $days as $dow => $day_name ) : ?>
                <tr>
                    <td><strong><?php echo esc_html( $day_name ); ?></strong></td>
                    <td><input type="time" class="erb-hours-open" data-day="<?php echo (int) $dow; ?>" value="10:00" style="width:100%;padding:.3rem .5rem;border:1px solid #e5e7eb;border-radius:4px;"></td>
                    <td><input type="time" class="erb-hours-close" data-day="<?php echo (int) $dow; ?>" value="22:00" style="width:100%;padding:.3rem .5rem;border:1px solid #e5e7eb;border-radius:4px;"></td>
                    <td style="text-align:center;"><input type="checkbox" class="erb-hours-closed" data-day="<?php echo (int) $dow; ?>"></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div style="margin-top:1.25rem;display:flex;gap:.75rem;justify-content:flex-end;">
            <button class="erb-btn erb-btn--outline erb-btn--auto" onclick="ERB.closeModal('erb-hours-modal')">
                <?php esc_html_e( 'Cancel', 'escape-room-booking' ); ?>
            </button>
            <button class="erb-btn erb-btn--primary erb-btn--auto" onclick="ERBGames.saveHours()">
                <?php esc_html_e( 'Save Hours', 'escape-room-booking' ); ?>
            </button>
        </div>
    </div>
</div>

<!-- ── Pricing Modal ──────────────────────────────────────────────────────── -->
<div class="erb-modal-overlay" id="erb-pricing-modal">
    <div class="erb-modal" style="max-width:400px;">
        <button class="erb-modal__close" onclick="ERB.closeModal('erb-pricing-modal')">&times;</button>
        <h2 id="erb-pricing-modal-title"><?php esc_html_e( 'Pricing', 'escape-room-booking' ); ?></h2>
        <input type="hidden" id="erb-pricing-game-id" value="">
        <p class="description"><?php esc_html_e( 'Enter the total price for each player count.', 'escape-room-booking' ); ?></p>

        <table class="erb-table" style="font-size:.875rem;">
            <thead><tr>
                <th><?php esc_html_e( 'Players', 'escape-room-booking' ); ?></th>
                <th><?php esc_html_e( 'Total Price (£)', 'escape-room-booking' ); ?></th>
                <th><?php esc_html_e( 'Per Person', 'escape-room-booking' ); ?></th>
            </tr></thead>
            <tbody>
            <?php for ( $p = 2; $p <= 8; $p++ ) :
                $defaults = array( 2=>65, 3=>87, 4=>105, 5=>120, 6=>132, 7=>140, 8=>150 );
            ?>
                <tr>
                    <td style="text-align:center;font-weight:700;font-size:1rem;"><?php echo absint( $p ); ?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:.3rem;">
                            <span>£</span>
                            <input type="number" class="erb-price-input" data-players="<?php echo absint( $p ); ?>"
                                   value="<?php echo esc_html( $defaults[$p] ); ?>" min="0" step="0.01"
                                   style="width:80px;padding:.3rem .5rem;border:1px solid #e5e7eb;border-radius:4px;"
                                   oninput="ERBGames.updatePerPerson(this)">
                        </div>
                    </td>
                    <td class="erb-per-person-<?php echo absint( $p ); ?>" style="color:#6b7280;font-size:.85rem;">
                        £<?php echo esc_html( number_format( $defaults[$p] / $p, 2 ) ); ?>
                    </td>
                </tr>
            <?php endfor; ?>
            </tbody>
        </table>

        <div style="margin-top:1.25rem;display:flex;gap:.75rem;justify-content:flex-end;">
            <button class="erb-btn erb-btn--outline erb-btn--auto" onclick="ERB.closeModal('erb-pricing-modal')">
                <?php esc_html_e( 'Cancel', 'escape-room-booking' ); ?>
            </button>
            <button class="erb-btn erb-btn--primary erb-btn--auto" onclick="ERBGames.savePricing()">
                <?php esc_html_e( 'Save Pricing', 'escape-room-booking' ); ?>
            </button>
        </div>
    </div>
</div>
<script>
(function($){
    window.ERBGamesLite = {
        addGameClick: function(btn) {
            if (btn.dataset.atLimit === '1') {
                if ($('#erb-lite-limit-notice').length) return;
                var $notice = $('<div id="erb-lite-limit-notice" style="' +
                    'background:#fff3ec;border:1px solid rgba(232,98,26,.4);border-radius:8px;' +
                    'padding:12px 16px;margin-bottom:1rem;font-size:.9rem;color:#92400e;' +
                    'display:flex;align-items:center;justify-content:space-between;gap:1rem;">' +
                    '<span>&#x1F513; You have reached the 2-game limit of the free version.</span>' +
                    '<a href="' + btn.dataset.upgradeUrl + '" ' +
                    'style="background:#e8621a;color:#fff;padding:7px 16px;border-radius:6px;' +
                    'text-decoration:none;font-weight:600;font-size:.85rem;white-space:nowrap;">' +
                    '&#x1F680; Upgrade to Pro</a>' +
                    '</div>');
                $('.erb-card').first().prepend($notice);
                $notice.hide().slideDown(200);
                setTimeout(function(){ $notice.slideUp(300, function(){ $(this).remove(); }); }, 6000);
                return;
            }
            ERBGames.openGameModal();
        }
    };
})(jQuery);
</script>
