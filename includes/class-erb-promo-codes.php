<?php if ( ! defined( 'ABSPATH' ) ) exit;
class ERB_Promo_Codes {
    public function validate( $code ) {
        $promo = ERB_DB::get_promo_code( strtoupper( trim( $code ) ) );
        if ( ! $promo ) return array( 'valid' => false, 'message' => __( 'Invalid promo code.', 'escape-room-booking' ) );
        $today = gmdate( 'Y-m-d' );
        if ( $promo->valid_from && $today < $promo->valid_from ) return array( 'valid' => false, 'message' => __( 'Promo code not yet active.', 'escape-room-booking' ) );
        if ( $promo->valid_to   && $today > $promo->valid_to   ) return array( 'valid' => false, 'message' => __( 'Promo code has expired.', 'escape-room-booking' ) );
        if ( $promo->max_uses   && $promo->use_count >= $promo->max_uses ) return array( 'valid' => false, 'message' => __( 'Promo code has reached its usage limit.', 'escape-room-booking' ) );
        return array( 'valid' => true, 'discount_percent' => (int) $promo->discount_percent, 'promo_id' => $promo->id );
    }
    public function apply( $promo_id ) { ERB_DB::increment_promo_use( $promo_id ); }
}
