<?php if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Abstract payment gateway.
 * Extend this to add new payment providers (PayPal, Square, etc.)
 */
abstract class ERB_Payment_Gateway {
    abstract public function create_payment_intent( $amount_pence, $currency, $metadata );
    abstract public function handle_webhook( $payload, $signature );
    abstract public function refund( $payment_id, $amount_pence );
}
