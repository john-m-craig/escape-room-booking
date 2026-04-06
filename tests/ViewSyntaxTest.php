<?php
/**
 * Tests for view file integrity.
 * Catches orphaned <?php tags, missing pagination components, and raw $sql variables.
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

    // ── Orphaned PHP tags ─────────────────────────────────────────────────────
    // Detects the pattern: a line that is ONLY "<?php" with nothing after it
    // followed by a line of raw HTML — this causes a critical error.

    /**
     * @dataProvider viewFileProvider
     */
    public function test_view_has_no_orphaned_php_open_tags( string $file ) {
        $path   = ERB_PLUGIN_DIR . $file;
        $lines  = file( $path, FILE_IGNORE_NEW_LINES );
        $orphans = [];

        foreach ( $lines as $i => $line ) {
            $trimmed = trim( $line );
            // A line that is ONLY an opening PHP tag with nothing after it
            if ( $trimmed === '<?php' || $trimmed === '<?php ' ) {
                // Check if the next non-empty line is raw HTML (not PHP)
                for ( $j = $i + 1; $j < count( $lines ); $j++ ) {
                    $next = trim( $lines[ $j ] );
                    if ( $next === '' ) continue;
                    // If the next line starts with < but not <?php it's raw HTML after an open tag
                    if ( str_starts_with( $next, '<' ) && ! str_starts_with( $next, '<?php' ) && ! str_starts_with( $next, '<!--' ) ) {
                        $orphans[] = "Line " . ( $i + 1 ) . ": orphaned '<?php' followed by HTML on line " . ( $j + 1 );
                    }
                    break;
                }
            }
        }

        $this->assertEmpty(
            $orphans,
            "View file {$file} has orphaned PHP open tags:\n" . implode( "\n", $orphans )
        );
    }

    public function viewFileProvider(): array {
        return array_map( fn( $f ) => [ $f ], $this->get_view_files() );
    }

    // ── Paginated views have required components ──────────────────────────────

    public function test_bookings_view_has_pagination_setup() {
        $source = file_get_contents( ERB_PLUGIN_DIR . 'admin/views/bookings.php' );
        $this->assertStringContainsString( 'ERB_Pagination',  $source, 'Bookings view must instantiate ERB_Pagination' );
        $this->assertStringContainsString( 'count_bookings',  $source, 'Bookings view must call ERB_DB::count_bookings()' );
        $this->assertStringContainsString( 'pager->render()', $source, 'Bookings view must call $pager->render()' );
        $this->assertStringContainsString( 'query_args()',    $source, 'Bookings view must use $pager->query_args()' );
    }

    public function test_customers_view_has_pagination_setup() {
        $source = file_get_contents( ERB_PLUGIN_DIR . 'admin/views/customers.php' );
        $this->assertStringContainsString( 'ERB_Pagination',  $source, 'Customers view must instantiate ERB_Pagination' );
        $this->assertStringContainsString( 'pager->render()', $source, 'Customers view must call $pager->render()' );
        $this->assertStringContainsString( 'get_offset()',    $source, 'Customers view must use $pager->get_offset()' );
    }

    // ── No raw $sql variables in views ────────────────────────────────────────

    public function test_no_raw_sql_variables_in_views() {
        foreach ( $this->get_view_files() as $file ) {
            $source = file_get_contents( ERB_PLUGIN_DIR . $file );
            $lines  = explode( "\n", $source );
            foreach ( $lines as $i => $line ) {
                $trimmed = ltrim( $line );
                if ( str_starts_with( $trimmed, '//' ) || str_starts_with( $trimmed, '*' ) ) continue;
                $this->assertStringNotContainsString(
                    '$sql =',
                    $line,
                    "View file {$file} line " . ( $i + 1 ) . " contains a raw \$sql variable"
                );
            }
        }
    }
}
