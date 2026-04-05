/* global erbAdmin, jQuery */
(function ($) {
    'use strict';

    // ── Utility: AJAX wrapper ─────────────────────────────────────────────────

    window.ERB = window.ERB || {};

    ERB.ajax = function (action, data, onSuccess, onError) {
        return $.ajax({
            url: erbAdmin.ajaxUrl,
            method: 'POST',
            data: Object.assign({ action: action, nonce: erbAdmin.nonce }, data),
            success: function (res) {
                if (res.success) {
                    if (onSuccess) onSuccess(res.data);
                } else {
                    var msg = (res.data && res.data.message) ? res.data.message : 'An error occurred.';
                    if (onError) onError(msg); else ERB.notice(msg, 'error');
                }
            },
            error: function (xhr) {
                // Try to extract our message from the response body first
                var msg = 'Server error (' + xhr.status + '). Please try again.';
                try {
                    var res = JSON.parse(xhr.responseText);
                    if (res && res.data && res.data.message) msg = res.data.message;
                } catch (e) {}
                if (onError) onError(msg); else ERB.notice(msg, 'error');
            }
        });
    };

    // ── Utility: Notice ───────────────────────────────────────────────────────

    ERB.notice = function (message, type) {
        type = type || 'success';
        var $notice = $('<div class="erb-notice erb-notice--' + type + '" style="margin-bottom:.75rem;">' + ERB.esc(message) + '</div>');
        // If a modal is open, show notice inside it; otherwise prepend to page
        var $openModal = $('.erb-modal-overlay.is-open .erb-modal');
        if ($openModal.length) {
            $openModal.prepend($notice);
        } else {
            $('.erb-admin-page').prepend($notice);
        }
        setTimeout(function () { $notice.fadeOut(400, function () { $(this).remove(); }); }, 5000);
    };

    // ── Utility: Escape HTML ──────────────────────────────────────────────────

    ERB.esc = function (str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    };

    // ── Utility: Modal ────────────────────────────────────────────────────────

    ERB.openModal = function (id) {
        var $overlay = $('#' + id);
        $overlay.addClass('is-open');
        $overlay.find('input, select, textarea').first().focus();
    };

    ERB.closeModal = function (id) {
        $('#' + id).removeClass('is-open');
    };

    // Close modal on overlay click or × button
    $(document).on('click', '.erb-modal-overlay', function (e) {
        if ($(e.target).hasClass('erb-modal-overlay') || $(e.target).hasClass('erb-modal__close')) {
            $(this).removeClass('is-open');
        }
    });

    // Close modal on Escape key
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') {
            $('.erb-modal-overlay.is-open').removeClass('is-open');
        }
    });

    // ── Utility: Confirm dialog ───────────────────────────────────────────────

    ERB.confirm = function (message, onConfirm) {
        if (window.confirm(message)) onConfirm();
    };

    // ── Utility: Format price (pence → display) ───────────────────────────────

    ERB.formatPrice = function (pence) {
        var symbol = (typeof erbAdmin !== 'undefined' && erbAdmin.currencySymbol) ? erbAdmin.currencySymbol : '£';
        return symbol + (pence / 100).toFixed(2);
    };

    // ── Document ready ────────────────────────────────────────────────────────

    $(function () {
        // Generic delete buttons wired up in later phases via data attributes
        // e.g. <button class="erb-btn--danger erb-delete" data-action="erb_delete_game" data-id="5">Delete</button>
        $(document).on('click', '.erb-delete', function () {
            var $btn   = $(this);
            var action = $btn.data('action');
            var id     = $btn.data('id');
            var label  = $btn.data('label') || 'this item';

            ERB.confirm('Are you sure you want to delete ' + label + '? This cannot be undone.', function () {
                ERB.ajax(action, { id: id }, function () {
                    $btn.closest('tr').fadeOut(300, function () { $(this).remove(); });
                    ERB.notice('Deleted successfully.');
                });
            });
        });
    });

}(jQuery));
