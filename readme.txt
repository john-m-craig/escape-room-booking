=== Ettrick Escape Room Booking ===
Contributors: john_m_craig, cbsa
Tags: escape room, booking, stripe, calendar, payments
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A complete booking and payment system built specifically for escape room venues. Take bookings, collect Stripe payments and manage your games from your own WordPress website.

== Description ==

**Ettrick Escape Room Booking** gives escape room venues everything they need to take bookings and collect payments directly from their own WordPress website — no monthly SaaS fees, no commissions on bookings.

= Free Version Features =

* **Weekly availability calendar** — clean, mobile-friendly calendar showing available and booked slots with week navigation
* **3-step booking flow** — player selection with live price updates, Stripe payment, instant confirmation
* **Stripe payments** — secure card payments with webhook confirmation; bookings only confirmed when payment is received
* **Slot holds** — customer's slot is held for 15 minutes while they pay, preventing double-bookings
* **Per-player pricing** — set different prices for each player count per game
* **Automated emails** — confirmation, change and cancellation emails to customer and admin
* **Manage booking page** — customers can view, change player count, or cancel via a secure link in their email
* **Admin bookings screen** — view, search and cancel bookings
* **Admin customers screen** — view all customers with booking history
* Up to **2 games** supported in the free version
* Note: shared room double-booking prevention requires Pro — in the free version, two games assigned to the same room can both be booked in the same slot

= Pro Version =

[Escape Room Booking Pro](https://escaperoombookingpro.com) adds:

* Unlimited games and rooms
* Promo codes with date ranges and use limits
* Revenue reports and analytics
* Gamekeepers — multiple staff notification recipients
* Shared room double-booking prevention
* Branded HTML emails with your venue colours
* Customer accounts
* Booking horizon and minimum notice period controls
* Priority support

= Simple Setup =

1. Install and activate the plugin
2. Add your Stripe API keys in Escape Rooms → Settings
3. Create three pages: one with `[eerb_calendar game="slug"]`, one with `[eerb_booking]`, one with `[eerb_manage_booking]`
4. Add your game in Escape Rooms → Games
5. Start taking bookings

= Requirements =

* WordPress 6.0+
* PHP 7.4+
* A [Stripe](https://stripe.com) account (free to create)
* HTTPS on your site (required by Stripe)

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/escape-room-booking/` or install via the WordPress Plugins screen
2. Activate the plugin
3. Go to **Escape Rooms → Settings** and enter your Stripe API keys
4. Create your WordPress pages and add the shortcodes
5. Go to **Escape Rooms → Games** and add your first game
6. Visit the [documentation](https://escaperoombookingpro.com/documentation/) for detailed setup instructions

== Frequently Asked Questions ==

= Do I need a Stripe account? =

Yes. Stripe processes the payments. Creating a Stripe account is free. The plugin works with any country Stripe supports.

= How many games can I have on the free version? =

The free version supports up to 2 games. Upgrade to [Escape Room Booking Pro](https://escaperoombookingpro.com) for unlimited games.

= What shortcodes does the plugin use? =

Three shortcodes:
* `[eerb_calendar game="your-game-slug"]` — the availability calendar
* `[eerb_booking]` — the booking and payment flow
* `[eerb_manage_booking]` — customer self-service page

= Does it work with my page builder? =

Yes. Works with Elementor, Divi, Beaver Builder and all major page builders. Use a Shortcode or HTML block.

= What happens if a customer doesn't complete payment? =

Their slot hold expires after 15 minutes and the slot becomes available again automatically. No booking is created.

= How do refunds work? =

Refunds are processed manually via your Stripe Dashboard. The plugin sends cancellation emails automatically when a booking is cancelled.

= Can two games share a room? =

Shared room support (preventing double-bookings across games in the same room) is available in [Escape Room Booking Pro](https://escaperoombookingpro.com).

= Is there a Pro version? =

Yes — [Escape Room Booking Pro](https://escaperoombookingpro.com) adds unlimited games, promo codes, revenue reports, gamekeepers and more. One-time payment, no subscriptions.

== External Services ==

This plugin connects to the following external services:

**Stripe** — payment processing
This plugin uses the Stripe API to create payment intents and process card payments. When a customer makes a booking, their payment details are submitted directly to Stripe's servers. Your Stripe secret key is used server-side to create payment intents. Stripe's privacy policy: https://stripe.com/privacy — Stripe's terms of service: https://stripe.com/terms

**Stripe.js** — Stripe's JavaScript library (https://js.stripe.com/v3/) is loaded on booking pages to securely collect card details. Card data never touches your server.

**Escape Room Booking Pro licence server** — upgrade prompts only
The free version contains links to https://escaperoombookingpro.com for upgrade information. No data is sent to this server from the free version.

== Screenshots ==

1. The WordPress admin menu showing all Escape Rooms sections
2. Games and Rooms setup screen — add unlimited games with pricing and hours
3. The weekly availability calendar — green slots available, red slots booked
4. Step 1 of the booking flow — player selection with live price updates
5. Step 2 — customer details form
6. Step 3 — Stripe payment form with booking summary
7. Booking confirmed screen — customer sees reference and booking details
8. Admin bookings screen — view, filter and manage all bookings
9. Admin customers screen — full customer history and spending
10. Dashboard summary — today's stats at a glance
11. Dashboard revenue chart — last 12 months revenue
12. Dashboard today's bookings — upcoming slots for the day
13. Upgrade to Pro screen — compare free and Pro features
14. Settings screen — configure Stripe keys, email settings, page URLs and iCal feeds

== Changelog ==

= 1.3.0 =
* Renamed all internal prefixes from erb_ to eerb_ for WordPress.org compliance
* Added automatic database migration on upgrade
* Updated tested up to WordPress 7.0
* Fixed Plugin URI, text domain, capability checks and sanitization

= 1.2.0 =
* Fixed Plugin URI
* Fixed text domain mismatch (8 instances)
* Added capability checks to all admin AJAX handlers
* Sanitized pagination variables

= 1.1.9 =
* Updated tested up to WordPress 7.0
* Escaped table name variables in customers view

= 1.1.8 =
* Updated tested up to WordPress 6.9
* Removed unused core file include in activator

= 1.1.7 =
* Renamed plugin to Ettrick Escape Room Booking
* Fixed PHP syntax error in customers view
* Replaced inline scripts with wp_add_inline_script()
* Sanitization improvements throughout
* Text domain updated

= 1.1.1 =
* Improved 2-game limit UX — greyed button with inline notice
* Fixed slot hold timezone mismatch on servers using UTC
* Improved error messages

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release.
