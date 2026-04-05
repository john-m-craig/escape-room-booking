/* global erbPublic, jQuery */
(function ($) {
    'use strict';

    window.ERB = window.ERB || {};

    // ── AJAX wrapper ─────────────────────────────────────────────────────────

    ERB.ajax = function (action, data, onSuccess, onError) {
        return $.ajax({
            url: erbPublic.ajaxUrl,
            method: 'POST',
            data: Object.assign({ action: action, nonce: erbPublic.nonce }, data),
            success: function (res) {
                if (res.success) {
                    if (onSuccess) onSuccess(res.data);
                } else {
                    var msg = (res.data && res.data.message) ? res.data.message : 'Something went wrong.';
                    if (onError) onError(msg);
                }
            },
            error: function (xhr) {
                var msg = 'Server error (' + xhr.status + '). Please try again.';
                try {
                    var res = JSON.parse(xhr.responseText);
                    if (res && res.data && res.data.message) msg = res.data.message;
                } catch (e) {}
                if (onError) onError(msg);
            }
        });
    };

    // ── Price formatter ───────────────────────────────────────────────────────

    ERB.formatPrice = function (pence) {
        var symbol = erbPublic.currencySymbol || '£';
        return symbol + (pence / 100).toFixed(2);
    };

    // ── Escape HTML ───────────────────────────────────────────────────────────

    ERB.esc = function (str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    };

    // ── Countdown Timer ───────────────────────────────────────────────────────
    //
    // Usage:
    //   var timer = ERB.startTimer($('#my-timer'), 15 * 60, function() {
    //       // called when timer hits 0
    //   });
    //   timer.stop(); // cancel if booking completed

    ERB.startTimer = function ($el, seconds, onExpire) {
        var remaining = seconds;
        var interval;

        function tick() {
            remaining--;
            var m = Math.floor(remaining / 60);
            var s = remaining % 60;
            $el.text(m + ':' + (s < 10 ? '0' : '') + s);

            if (remaining <= 60) {
                $el.addClass('erb-timer--urgent');
            }

            if (remaining <= 0) {
                clearInterval(interval);
                if (onExpire) onExpire();
            }
        }

        $el.text(Math.floor(seconds / 60) + ':00');
        interval = setInterval(tick, 1000);

        return {
            stop: function () { clearInterval(interval); },
            remaining: function () { return remaining; }
        };
    };

    // ── Session key (stored in sessionStorage for hold verification) ──────────

    ERB.getSessionKey = function () {
        var key = sessionStorage.getItem('erb_session_key');
        if (!key) {
            key = Math.random().toString(36).slice(2) + Math.random().toString(36).slice(2);
            sessionStorage.setItem('erb_session_key', key);
        }
        return key;
    };

    // ── Field validation helpers ──────────────────────────────────────────────

    ERB.validateEmail = function (email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    };

    ERB.validateMobile = function (mobile) {
        return /^[\d\s\+\-\(\)]{7,20}$/.test(mobile);
    };

    ERB.showFieldError = function ($field, message) {
        $field.addClass('has-error');
        $field.next('.erb-field-error').remove();
        $field.after('<p class="erb-field-error">' + ERB.esc(message) + '</p>');
    };

    ERB.clearFieldErrors = function ($form) {
        $form.find('.has-error').removeClass('has-error');
        $form.find('.erb-field-error').remove();
    };

    // ── Booking state (persisted across steps in sessionStorage) ─────────────

    ERB.BookingState = {
        _key: 'erb_booking',

        get: function () {
            try { return JSON.parse(sessionStorage.getItem(this._key)) || {}; }
            catch (e) { return {}; }
        },

        set: function (data) {
            var current = this.get();
            sessionStorage.setItem(this._key, JSON.stringify(Object.assign(current, data)));
        },

        clear: function () {
            sessionStorage.removeItem(this._key);
        }
    };

    // ── Document ready ────────────────────────────────────────────────────────

    $(function () {
        if ($('.erb-calendar__grid').length) ERB.Calendar.init();
        if ($('.erb-booking-wrap').length)   ERB.Booking.init();
    });

}(jQuery));
