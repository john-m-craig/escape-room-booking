/* global ERB, erbPublic, jQuery */
(function ($) {
    'use strict';

    // ── ERB.Calendar ─────────────────────────────────────────────────────────

    ERB.Calendar = {

        instances: {},

        init: function () {
            $('.erb-calendar').each(function () {
                var $cal    = $(this);
                var gameId  = $cal.data('game-id');
                var weekStart = $cal.data('week-start');

                ERB.Calendar.instances[gameId] = {
                    $el:         $cal,
                    gameId:      gameId,
                    weekStart:   weekStart,
                    availColor:  $cal.data('available-color') || '#22c55e',
                    bookedColor: $cal.data('booked-color')    || '#ef4444',
                    bookingUrl:  $cal.data('booking-url')     || '',
                };

                ERB.Calendar.load(gameId);
            });
        },

        // ── Load slot data via AJAX ───────────────────────────────────────────

        load: function (gameId) {
            var inst = ERB.Calendar.instances[gameId];
            if (!inst) return;

            var $loading  = $('#erb-loading-'   + gameId);
            var $gridWrap = $('#erb-grid-wrap-' + gameId);
            var $error    = $('#erb-error-'     + gameId);

            $loading.show();
            $gridWrap.hide();
            $error.hide().text('');

            ERB.ajax('erb_get_slots', {
                game_id:    gameId,
                week_start: inst.weekStart,
            }, function (data) {
                $loading.hide();
                ERB.Calendar.render(gameId, data.days);
                $gridWrap.show();
            }, function (msg) {
                $loading.hide();
                $error.text(msg).show();
            });
        },

        // ── Render the grid ───────────────────────────────────────────────────

        render: function (gameId, days) {
            var inst  = ERB.Calendar.instances[gameId];
            var $grid = $('#erb-grid-' + gameId);
            $grid.empty();

            if (!days || !days.length) {
                $grid.html('<p style="padding:1rem;color:#6b7280;">No availability data for this week.</p>');
                return;
            }

            // Collect all unique time slots across the week (for row labels)
            var allTimes = ERB.Calendar.collectTimes(days);

            if (!allTimes.length) {
                $grid.html('<p style="padding:1rem;color:#6b7280;">No time slots configured for this week. Please check operating hours in the admin.</p>');
                return;
            }

            // Set grid columns: 1 time-label column + 1 per day
            var cols = 1 + days.length;
            $grid.css('grid-template-columns', '60px ' + 'repeat(' + days.length + ', 1fr)');

            // ── Header row: blank corner + day headers ─────────────────────
            $grid.append('<div class="erb-calendar__header-cell erb-time-corner"></div>');
            days.forEach(function (day) {
                var cls  = 'erb-calendar__header-cell';
                if (day.is_today) cls += ' erb-today-header';
                // Split date_label (e.g. "27 Mar") into day number and month
                var dateParts  = (day.date_label || '').split(' ');
                var dayNum     = dateParts[0] || '';
                var monthAbbr  = dateParts[1] || '';
                var html = '<div class="' + cls + '">'
                    + '<div class="erb-cal-day-name">'  + ERB.esc(day.day_label) + '</div>'
                    + '<div class="erb-cal-day-num">'   + ERB.esc(dayNum)        + '</div>'
                    + '<div class="erb-cal-day-month">' + ERB.esc(monthAbbr)     + '</div>'
                    + '</div>';
                $grid.append(html);
            });

            // ── Time rows ──────────────────────────────────────────────────
            allTimes.forEach(function (time) {
                // Time label cell
                $grid.append('<div class="erb-calendar__time-label">' + ERB.esc(time) + '</div>');

                // One cell per day
                days.forEach(function (day) {
                    var slot = ERB.Calendar.findSlot(day.slots, time);

                    if (day.is_closed || !slot) {
                        // Closed or no slot at this time
                        $grid.append('<div class="erb-slot erb-slot--closed"></div>');
                        return;
                    }

                    var cellHtml = ERB.Calendar.buildSlotCell(inst, day, slot);
                    $grid.append(cellHtml);
                });
            });

            // ── Bind click events ──────────────────────────────────────────
            $grid.find('.erb-slot--available').on('click', function () {
                var $slot     = $(this);
                var startDt   = $slot.data('start-dt');
                var endDt     = $slot.data('end-dt');
                var startDisp = $slot.data('start-display');
                var date      = $slot.data('date');

                ERB.Calendar.onSlotClick(inst, {
                    startDt:      startDt,
                    endDt:        endDt,
                    startDisplay: startDisp,
                    date:         date,
                });
            });
        },

        // ── Build a single slot cell ──────────────────────────────────────────

        buildSlotCell: function (inst, day, slot) {
            var status = slot.status;
            var cls    = 'erb-slot ';
            var style  = '';
            var label  = '';
            var attrs  = '';

            switch (status) {
                case 'available':
                    cls   += 'erb-slot--available';
                    style  = 'background:' + inst.availColor + ';color:#fff;';
                    label  = slot.start;
                    attrs  = ' data-start-dt="'      + ERB.esc(slot.start_dt)   + '"'
                           + ' data-end-dt="'        + ERB.esc(slot.end_dt)     + '"'
                           + ' data-start-display="' + ERB.esc(slot.start)      + '"'
                           + ' data-date="'          + ERB.esc(day.date)        + '"'
                           + ' title="Click to book ' + ERB.esc(slot.start) + ' – ' + ERB.esc(slot.end) + '"'
                           + ' role="button" tabindex="0"';
                    break;

                case 'booked':
                case 'held':
                    cls   += 'erb-slot--booked';
                    style  = 'background:' + inst.bookedColor + ';color:#fff;opacity:.85;';
                    label  = slot.start;
                    attrs  = ' title="' + (status === 'held' ? 'Being booked' : 'Already booked') + '"';
                    break;

                case 'blocked':
                    cls   += 'erb-slot--booked';
                    style  = 'background:' + inst.bookedColor + ';color:#fff;opacity:.75;';
                    label  = slot.start;
                    attrs  = ' title="Unavailable"';
                    break;

                case 'past':
                case 'notice':
                    cls   += 'erb-slot--past';
                    label  = slot.start;
                    attrs  = ' title="' + (status === 'past' ? 'In the past' : 'Too soon to book') + '"';
                    break;

                default:
                    cls   += 'erb-slot--closed';
                    label  = '';
            }

            return '<div class="' + cls + '" style="' + style + '"' + attrs + '>'
                 + label
                 + '</div>';
        },

        // ── Slot click handler ────────────────────────────────────────────────

        onSlotClick: function (inst, slotData) {
            // Store chosen slot in sessionStorage for the booking flow
            ERB.BookingState.clear();
            ERB.BookingState.set({
                gameId:       inst.gameId,
                slotStart:    slotData.startDt,
                slotEnd:      slotData.endDt,
                displayTime:  slotData.startDisplay,
                displayDate:  slotData.date,
                sessionKey:   ERB.getSessionKey(),
            });

            // Navigate to the booking page
            var url = inst.bookingUrl || erbPublic.bookingPageUrl || '';
            if (!url) {
                alert('Booking page not configured. Please set the booking page URL in Escape Rooms → Settings.');
                return;
            }
            window.location.href = url;
        },

        // ── Keyboard accessibility ────────────────────────────────────────────

        bindKeyboard: function (gameId) {
            var $grid = $('#erb-grid-' + gameId);
            $grid.on('keypress', '.erb-slot--available', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    $(this).trigger('click');
                }
            });
        },

        // ── Utility: collect all unique start times across the week ───────────

        collectTimes: function (days) {
            var seen  = {};
            var times = [];
            days.forEach(function (day) {
                (day.slots || []).forEach(function (slot) {
                    if (!seen[slot.start]) {
                        seen[slot.start] = true;
                        times.push(slot.start);
                    }
                });
            });
            // Sort chronologically
            times.sort(function (a, b) {
                return a.localeCompare(b);
            });
            return times;
        },

        // ── Utility: find a slot in a day's slot array by start time ──────────

        findSlot: function (slots, time) {
            if (!slots) return null;
            for (var i = 0; i < slots.length; i++) {
                if (slots[i].start === time) return slots[i];
            }
            return null;
        },
    };

    // ── Init on DOM ready ─────────────────────────────────────────────────────

    $(function () {
        if ($('.erb-calendar').length) {
            ERB.Calendar.init();

            // Keyboard support
            $('.erb-calendar').each(function () {
                ERB.Calendar.bindKeyboard($(this).data('game-id'));
            });
        }
    });

}(jQuery));
