<?php
/**
 * Tests for view file PHP syntax integrity.
 *
 * Catches the recurring "critical error" caused by orphaned <?php tags
 * in view files — where <?php appears without a matching ?> before HTML output.
 *
 * Also verifies that paginated views have all required pagination components.
 */

use PHPUnit\Framework\TestCase;

class ViewSyntaxTest extends TestCase {

    private function get_view_files(): array {
        return [
            'admin/views/bookings.php',
            'admin/views/customers.php',
            'admin/views/dashboard.php',
            'admin/views/games.php',
            'admin/views/settings.php',
            'admin/views/upgrade.php',
            'public/views/booking.php',
            'public/views/calendar.php',
            'public/views/manage-booking.php',
        ];
    }

    // ── PHP tag balance ───────────────────────────────────────────────────────

    /**
     * @dataProvider viewFileProvider
     */
    public function test_view_has_balanced_php_tags( string $file ) {
        $path    = ERB_PLUGIN_DIR . $file;
        $source  = file_get_contents( $path );
        $opens   = substr_count( $source, '<?php' ) + substr_count( $source, '<?' );
        $closes  = substr_count( $source, '?>' );

        // Files that start with <?php and use it as a pure PHP file (no closing tag needed)
        // are fine. But view files that mix PHP and HTML must have balanced tags.
        // We allow opens to exceed closes by 1 (file-level opening tag without closer).
        $diff = $opens - $closes;
        $this->assertLessThanOrEqual(
            1,
            $diff,
            "View file {$file} has {$opens} opening PHP tags but only {$closes} closing tags — likely an orphaned <?php tag causing a critical error"
        );
    }

    public function viewFileProvider(): array {
        return array_map(
            fn( $f ) => [ $f ],
            $this->get_view_files()
        );
    }

    // ── Paginated views have required components ──────────────────────────────

    public function test_bookings_view_has_pagination_setup() {
        $source = file_get_contents( ERB_PLUGIN_DIR . 'admin/views/bookings.php' );
        $this->assertStringContainsString( 'ERB_Pagination', $source,      'Bookings view must instantiate ERB_Pagination' );
        $this->assertStringContainsString( 'count_bookings', $source,      'Bookings view must call ERB_DB::count_bookings()' );
        $this->assertStringContainsString( 'pager->render()', $source,     'Bookings view must call $pager->render()' );
        $this->assertStringContainsString( 'query_args()', $source,        'Bookings view must use $pager->query_args()' );
    }

    public function test_customers_view_has_pagination_setup() {
        $source = file_get_contents( ERB_PLUGIN_DIR . 'admin/views/customers.php' );
        $this->assertStringContainsString( 'ERB_Pagination', $source,      'Customers view must instantiate ERB_Pagination' );
        $this->assertStringContainsString( 'pager->render()', $source,     'Customers view must call $pager->render()' );
        $this->assertStringContainsString( 'get_offset()', $source,        'Customers view must use $pager->get_offset()' );
    }

    // ── No raw $sql variables in views ────────────────────────────────────────

    public function test_no_raw_sql_variables_in_views() {
        foreach ( $this->get_view_files() as $file ) {
            $source = file_get_contents( ERB_PLUGIN_DIR . $file );
            // Allow $sql inside comments or phpcs:ignore lines
            $lines = explode( "\n", $source );
            foreach ( $lines as $i => $line ) {
                $trimmed = ltrim( $line );
                if ( str_starts_with( $trimmed, '//' ) || str_starts_with( $trimmed, '*' ) ) continue;
                $this->assertStringNotContainsString(
                    '$sql =',
                    $line,
                    "View file {$file} line " . ( $i + 1 ) . " contains a raw \$sql variable — use ERB_DB methods instead"
                );
            }
        }
    }
}
