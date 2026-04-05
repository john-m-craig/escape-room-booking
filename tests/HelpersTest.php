<?php
/**
 * Tests for ERB_Helpers (Lite)
 */

use PHPUnit\Framework\TestCase;

require_once ERB_PLUGIN_DIR . 'includes/class-erb-helpers.php';

class HelpersTest extends TestCase {

    public function test_format_price_standard_amount() {
        $this->assertEquals( '£19.99', ERB_Helpers::format_price( 1999 ) );
    }

    public function test_format_price_zero() {
        $this->assertEquals( '£0.00', ERB_Helpers::format_price( 0 ) );
    }

    public function test_format_price_whole_pounds() {
        $this->assertEquals( '£100.00', ERB_Helpers::format_price( 10000 ) );
    }

    public function test_generate_token_is_64_chars() {
        $this->assertEquals( 64, strlen( ERB_Helpers::generate_token() ) );
    }

    public function test_generate_token_is_hexadecimal() {
        $this->assertMatchesRegularExpression( '/^[a-f0-9]{64}$/', ERB_Helpers::generate_token() );
    }

    public function test_generate_token_is_unique() {
        $tokens = [];
        for ( $i = 0; $i < 20; $i++ ) { $tokens[] = ERB_Helpers::generate_token(); }
        $this->assertEquals( count( $tokens ), count( array_unique( $tokens ) ) );
    }

    public function test_slot_within_min_notice_returns_true() {
        $game = new stdClass();
        $game->min_notice_hours = 2;
        $slot_start = gmdate( 'Y-m-d H:i:s', time() + 3600 );
        $this->assertTrue( ERB_Helpers::is_within_min_notice( $game, $slot_start ) );
    }

    public function test_slot_outside_min_notice_returns_false() {
        $game = new stdClass();
        $game->min_notice_hours = 2;
        $slot_start = gmdate( 'Y-m-d H:i:s', time() + ( 3 * 3600 ) );
        $this->assertFalse( ERB_Helpers::is_within_min_notice( $game, $slot_start ) );
    }

    public function test_zero_min_notice_never_blocks() {
        $game = new stdClass();
        $game->min_notice_hours = 0;
        $slot_start = gmdate( 'Y-m-d H:i:s', time() + 60 );
        $this->assertFalse( ERB_Helpers::is_within_min_notice( $game, $slot_start ) );
    }
}
