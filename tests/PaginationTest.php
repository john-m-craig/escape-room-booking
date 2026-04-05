<?php
/**
 * Tests for ERB_Pagination
 */

use PHPUnit\Framework\TestCase;

require_once ERB_PLUGIN_DIR . 'includes/class-erb-pagination.php';

class PaginationTest extends TestCase {

    // ── Basic calculations ────────────────────────────────────────────────────

    public function test_total_pages_calculated_correctly() {
        $p = new ERB_Pagination( 100, 25, 1 );
        $this->assertEquals( 4, $p->get_total_pages() );
    }

    public function test_total_pages_rounds_up() {
        $p = new ERB_Pagination( 101, 25, 1 );
        $this->assertEquals( 5, $p->get_total_pages() );
    }

    public function test_single_page_when_items_fit() {
        $p = new ERB_Pagination( 10, 25, 1 );
        $this->assertEquals( 1, $p->get_total_pages() );
    }

    public function test_zero_total_gives_zero_from() {
        $p = new ERB_Pagination( 0, 25, 1 );
        $this->assertEquals( 0, $p->get_from() );
        $this->assertEquals( 0, $p->get_to() );
    }

    // ── Offset calculation ────────────────────────────────────────────────────

    public function test_first_page_offset_is_zero() {
        $p = new ERB_Pagination( 100, 25, 1 );
        $this->assertEquals( 0, $p->get_offset() );
    }

    public function test_second_page_offset() {
        $p = new ERB_Pagination( 100, 25, 2 );
        $this->assertEquals( 25, $p->get_offset() );
    }

    public function test_third_page_offset() {
        $p = new ERB_Pagination( 100, 25, 3 );
        $this->assertEquals( 50, $p->get_offset() );
    }

    public function test_offset_with_different_page_size() {
        $p = new ERB_Pagination( 200, 50, 3 );
        $this->assertEquals( 100, $p->get_offset() );
    }

    // ── From / To labels ─────────────────────────────────────────────────────

    public function test_from_to_first_page() {
        $p = new ERB_Pagination( 100, 25, 1 );
        $this->assertEquals( 1,  $p->get_from() );
        $this->assertEquals( 25, $p->get_to() );
    }

    public function test_from_to_last_page_partial() {
        $p = new ERB_Pagination( 92, 25, 4 );
        $this->assertEquals( 76, $p->get_from() );
        $this->assertEquals( 92, $p->get_to() );
    }

    public function test_from_to_exactly_full_last_page() {
        $p = new ERB_Pagination( 100, 25, 4 );
        $this->assertEquals( 76,  $p->get_from() );
        $this->assertEquals( 100, $p->get_to() );
    }

    // ── Page size validation ──────────────────────────────────────────────────

    public function test_invalid_page_size_falls_back_to_default() {
        $p = new ERB_Pagination( 100, 99, 1 ); // 99 is not a valid page size
        $this->assertEquals( ERB_Pagination::DEFAULT_PER_PAGE, $p->get_per_page() );
    }

    public function test_valid_page_sizes_accepted() {
        foreach ( ERB_Pagination::PAGE_SIZES as $size ) {
            $p = new ERB_Pagination( 100, $size, 1 );
            $this->assertEquals( $size, $p->get_per_page() );
        }
    }

    // ── Current page clamping ─────────────────────────────────────────────────

    public function test_page_below_1_clamped_to_1() {
        $p = new ERB_Pagination( 100, 25, 0 );
        $this->assertEquals( 1, $p->get_current_page() );
    }

    public function test_page_above_max_clamped_to_max() {
        $p = new ERB_Pagination( 100, 25, 999 );
        $this->assertEquals( 4, $p->get_current_page() );
    }

    // ── query_args ────────────────────────────────────────────────────────────

    public function test_query_args_returns_limit_and_offset() {
        $p    = new ERB_Pagination( 100, 25, 2 );
        $args = $p->query_args();
        $this->assertEquals( 25, $args['limit'] );
        $this->assertEquals( 25, $args['offset'] );
    }

    public function test_query_args_first_page_offset_is_zero() {
        $p    = new ERB_Pagination( 100, 25, 1 );
        $args = $p->query_args();
        $this->assertEquals( 0, $args['offset'] );
    }

    // ── has_pages ─────────────────────────────────────────────────────────────

    public function test_has_pages_false_when_single_page() {
        $p = new ERB_Pagination( 10, 25, 1 );
        $this->assertFalse( $p->has_pages() );
    }

    public function test_has_pages_true_when_multiple_pages() {
        $p = new ERB_Pagination( 100, 25, 1 );
        $this->assertTrue( $p->has_pages() );
    }

    // ── render ────────────────────────────────────────────────────────────────

    public function test_render_returns_empty_string_for_zero_results() {
        $p = new ERB_Pagination( 0, 25, 1 );
        $this->assertEquals( '', $p->render( 'https://example.com' ) );
    }

    public function test_render_contains_showing_text() {
        $p = new ERB_Pagination( 100, 25, 2 );
        $html = $p->render( 'https://example.com/admin?page=erb-bookings' );
        $this->assertStringContainsString( 'Showing', $html );
        $this->assertStringContainsString( '26', $html ); // from
        $this->assertStringContainsString( '50', $html ); // to
        $this->assertStringContainsString( '100', $html ); // total
    }

    public function test_render_contains_page_size_options() {
        $p    = new ERB_Pagination( 100, 25, 1 );
        $html = $p->render( 'https://example.com' );
        foreach ( ERB_Pagination::PAGE_SIZES as $size ) {
            $this->assertStringContainsString( (string) $size, $html );
        }
    }

    public function test_render_active_page_size_marked() {
        $p    = new ERB_Pagination( 100, 50, 1 );
        $html = $p->render( 'https://example.com' );
        $this->assertStringContainsString( 'erb-pager__size--active', $html );
    }
}
