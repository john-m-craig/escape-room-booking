<?php
/**
 * Tests for slot hold timezone safety (Lite)
 */

use PHPUnit\Framework\TestCase;

class SlotHoldsTest extends TestCase {

    public function test_hold_uses_upsert_not_php_date() {
        $source = file_get_contents( ERB_PLUGIN_DIR . 'includes/class-erb-slot-holds.php' );
        $this->assertStringContainsString(
            'ERB_DB::upsert_hold',
            $source,
            'hold() must use ERB_DB::upsert_hold() to avoid PHP/MySQL timezone mismatch'
        );
    }

    public function test_hold_does_not_use_php_date_for_expiry() {
        $source = file_get_contents( ERB_PLUGIN_DIR . 'includes/class-erb-slot-holds.php' );
        $this->assertStringNotContainsString(
            "= date( 'Y-m-d H:i:s'",
            $source,
            'hold() must not use PHP date() for expiry calculation'
        );
    }

    public function test_upsert_hold_uses_mysql_date_add() {
        $source = file_get_contents( ERB_PLUGIN_DIR . 'includes/class-erb-db.php' );
        $this->assertStringContainsString(
            'DATE_ADD(NOW(), INTERVAL',
            $source,
            'upsert_hold() must use MySQL DATE_ADD(NOW(), INTERVAL x MINUTE)'
        );
    }

    public function test_upsert_hold_uses_replace_into() {
        $source = file_get_contents( ERB_PLUGIN_DIR . 'includes/class-erb-db.php' );
        $this->assertStringContainsString(
            'REPLACE INTO',
            $source,
            'upsert_hold() must use REPLACE INTO to prevent duplicate key errors'
        );
    }

    public function test_get_any_active_hold_fallback_exists() {
        $source = file_get_contents( ERB_PLUGIN_DIR . 'includes/class-erb-db.php' );
        $this->assertStringContainsString(
            'get_any_active_hold',
            $source,
            'DB class must have get_any_active_hold() fallback'
        );
    }
}
