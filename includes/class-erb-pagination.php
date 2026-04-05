<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Pagination helper for admin list screens.
 *
 * Usage:
 *   $pager = new ERB_Pagination( $total_rows, $per_page, $current_page );
 *   $bookings = ERB_DB::get_bookings( array_merge( $args, $pager->query_args() ) );
 *   echo $pager->render();
 */
class ERB_Pagination {

    const PAGE_SIZES = [ 10, 25, 50, 100 ];
    const DEFAULT_PER_PAGE = 25;

    private int $total;
    private int $per_page;
    private int $current_page;
    private int $total_pages;

    public function __construct( int $total, int $per_page, int $current_page ) {
        $this->total        = $total;
        $this->per_page     = in_array( $per_page, self::PAGE_SIZES, true ) ? $per_page : self::DEFAULT_PER_PAGE;
        $this->total_pages  = $this->per_page > 0 ? (int) ceil( $total / $this->per_page ) : 1;
        $this->current_page = max( 1, min( $current_page, $this->total_pages ) );
    }

    public function get_total(): int        { return $this->total; }
    public function get_per_page(): int     { return $this->per_page; }
    public function get_current_page(): int { return $this->current_page; }
    public function get_total_pages(): int  { return $this->total_pages; }
    public function get_offset(): int       { return ( $this->current_page - 1 ) * $this->per_page; }
    public function get_from(): int         { return $this->total === 0 ? 0 : $this->get_offset() + 1; }
    public function get_to(): int           { return min( $this->get_offset() + $this->per_page, $this->total ); }
    public function has_pages(): bool       { return $this->total_pages > 1; }

    /**
     * Returns limit/offset args to pass to ERB_DB::get_bookings() etc.
     */
    public function query_args(): array {
        return [
            'limit'  => $this->per_page,
            'offset' => $this->get_offset(),
        ];
    }

    /**
     * Renders the pagination bar HTML.
     */
    public function render( string $base_url = '' ): string {
        if ( $this->total === 0 ) return '';

        $base_url  = $base_url ?: ( $_SERVER['REQUEST_URI'] ?? '' );
        $per_page  = $this->per_page;
        $cur       = $this->current_page;
        $total     = $this->total_pages;

        $url = function( int $page ) use ( $base_url, $per_page ): string {
            $params = array_merge(
                $_GET ?? [],
                [ 'paged' => $page, 'per_page' => $per_page ]
            );
            $base = strtok( $base_url, '?' );
            return esc_url( $base . '?' . http_build_query( $params ) );
        };

        $size_url = function( int $size ) use ( $base_url ): string {
            $params = array_merge( $_GET ?? [], [ 'paged' => 1, 'per_page' => $size ] );
            $base = strtok( $base_url, '?' );
            return esc_url( $base . '?' . http_build_query( $params ) );
        };

        // Page size selector
        $sizes_html = '';
        foreach ( self::PAGE_SIZES as $size ) {
            $active      = $size === $per_page ? ' erb-pager__size--active' : '';
            $sizes_html .= '<a href="' . $size_url( $size ) . '" class="erb-pager__size' . $active . '">' . $size . '</a>';
        }

        // Page buttons
        $pages_html = '';
        if ( $cur > 1 ) {
            $pages_html .= '<a href="' . $url( $cur - 1 ) . '" class="erb-pager__btn">&lsaquo; Prev</a>';
        }

        $range = $this->page_range( $cur, $total );
        $prev  = null;
        foreach ( $range as $p ) {
            if ( $p === '...' ) {
                $pages_html .= '<span class="erb-pager__ellipsis">&hellip;</span>';
            } else {
                $active      = (int) $p === $cur ? ' erb-pager__btn--active' : '';
                $pages_html .= '<a href="' . $url( (int) $p ) . '" class="erb-pager__btn' . $active . '">' . $p . '</a>';
            }
            $prev = $p;
        }

        if ( $cur < $total ) {
            $pages_html .= '<a href="' . $url( $cur + 1 ) . '" class="erb-pager__btn">Next &rsaquo;</a>';
        }

        $from  = number_format( $this->get_from() );
        $to    = number_format( $this->get_to() );
        $total_f = number_format( $this->total );

        return '<div class="erb-pager">'
             . '<div class="erb-pager__info">'
             . '<span>Showing ' . $from . '&ndash;' . $to . ' of ' . $total_f . '</span>'
             . '<span class="erb-pager__sizes">Rows: ' . $sizes_html . '</span>'
             . '</div>'
             . '<div class="erb-pager__pages">' . $pages_html . '</div>'
             . '</div>';
    }

    /**
     * Generates a sensible page range with ellipsis for large sets.
     * e.g. [1, 2, 3, '...', 47, 48, 49] or [1, '...', 5, 6, 7, '...', 20]
     */
    private function page_range( int $cur, int $total ): array {
        if ( $total <= 7 ) {
            return range( 1, $total );
        }

        $pages = [];
        $window = 2; // pages either side of current

        $show_left_ellipsis  = $cur > $window + 2;
        $show_right_ellipsis = $cur < $total - $window - 1;

        $pages[] = 1;

        if ( $show_left_ellipsis ) {
            $pages[] = '...';
        } else {
            for ( $i = 2; $i < $cur - $window; $i++ ) {
                $pages[] = $i;
            }
        }

        for ( $i = max( 2, $cur - $window ); $i <= min( $total - 1, $cur + $window ); $i++ ) {
            $pages[] = $i;
        }

        if ( $show_right_ellipsis ) {
            $pages[] = '...';
        } else {
            for ( $i = $cur + $window + 1; $i < $total; $i++ ) {
                $pages[] = $i;
            }
        }

        $pages[] = $total;

        return array_unique( $pages );
    }
}
