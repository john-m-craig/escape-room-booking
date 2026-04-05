<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Stripe payment gateway integration.
 * Handles webhook signature verification and event processing.
 */
class ERB_Stripe extends ERB_Payment_Gateway {

    private function secret_key() {
        $mode = get_option( 'erb_stripe_mode', 'test' );
        return $mode === 'live'
            ? get_option( 'erb_stripe_live_sk', '' )
            : get_option( 'erb_stripe_test_sk', '' );
    }

    // ── Payment Intent ────────────────────────────────────────────────────────

    public function create_payment_intent( $amount_pence, $currency, $metadata ) {
        $response = wp_remote_post( 'https://api.stripe.com/v1/payment_intents', array(
            'headers' => array( 'Authorization' => 'Bearer ' . $this->secret_key() ),
            'body'    => array(
                'amount'   => $amount_pence,
                'currency' => strtolower( $currency ),
                'metadata' => $metadata,
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            return array( 'error' => $response->get_error_message() );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! empty( $body['error'] ) ) {
            return array( 'error' => $body['error']['message'] ?? 'Stripe error.' );
        }

        return $body;
    }

    // ── Refund ────────────────────────────────────────────────────────────────

    public function refund( $payment_intent_id, $amount_pence ) {
        // Retrieve the charge ID from the payment intent first
        $pi_response = wp_remote_get(
            'https://api.stripe.com/v1/payment_intents/' . urlencode( $payment_intent_id ),
            array( 'headers' => array( 'Authorization' => 'Bearer ' . $this->secret_key() ) )
        );

        if ( is_wp_error( $pi_response ) ) {
            return array( 'error' => $pi_response->get_error_message() );
        }

        $pi = json_decode( wp_remote_retrieve_body( $pi_response ), true );
        $charge_id = $pi['latest_charge'] ?? null;

        if ( ! $charge_id ) {
            return array( 'error' => 'No charge found on this payment intent.' );
        }

        $body = array( 'charge' => $charge_id );
        if ( $amount_pence ) {
            $body['amount'] = $amount_pence;
        }

        $response = wp_remote_post( 'https://api.stripe.com/v1/refunds', array(
            'headers' => array( 'Authorization' => 'Bearer ' . $this->secret_key() ),
            'body'    => $body,
        ) );

        if ( is_wp_error( $response ) ) {
            return array( 'error' => $response->get_error_message() );
        }

        return json_decode( wp_remote_retrieve_body( $response ), true );
    }

    // ── Webhook verification ──────────────────────────────────────────────────

    /**
     * Verify the Stripe-Signature header and return the parsed event.
     * Returns WP_Error on failure.
     */
    public function handle_webhook( $payload, $signature ) {
        $secret = get_option( 'erb_stripe_webhook_secret', '' );

        if ( empty( $secret ) ) {
            return new WP_Error( 'no_secret', 'Webhook secret not configured.' );
        }

        $event = $this->construct_event( $payload, $signature, $secret );
        if ( is_wp_error( $event ) ) {
            return $event;
        }

        return $event;
    }

    /**
     * Manually verify Stripe webhook signature.
     * Replicates stripe-php library logic without requiring Composer.
     */
    private function construct_event( $payload, $sig_header, $secret ) {
        if ( empty( $sig_header ) ) {
            return new WP_Error( 'no_signature', 'Missing Stripe-Signature header.' );
        }

        // Parse the signature header: t=timestamp,v1=signature,...
        $parts     = array();
        $timestamp = null;
        $signatures = array();

        foreach ( explode( ',', $sig_header ) as $part ) {
            $kv = explode( '=', $part, 2 );
            if ( count( $kv ) !== 2 ) continue;
            if ( $kv[0] === 't' ) {
                $timestamp = (int) $kv[1];
            } elseif ( $kv[0] === 'v1' ) {
                $signatures[] = $kv[1];
            }
        }

        if ( ! $timestamp || empty( $signatures ) ) {
            return new WP_Error( 'invalid_signature', 'Could not parse Stripe-Signature header.' );
        }

        // Reject events older than 5 minutes (replay attack protection)
        if ( abs( time() - $timestamp ) > 300 ) {
            return new WP_Error( 'timestamp_too_old', 'Webhook timestamp too old.' );
        }

        // Compute expected signature
        $signed_payload  = $timestamp . '.' . $payload;
        $expected        = hash_hmac( 'sha256', $signed_payload, $secret );

        $verified = false;
        foreach ( $signatures as $sig ) {
            if ( hash_equals( $expected, $sig ) ) {
                $verified = true;
                break;
            }
        }

        if ( ! $verified ) {
            return new WP_Error( 'signature_mismatch', 'Webhook signature verification failed.' );
        }

        $event = json_decode( $payload, true );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new WP_Error( 'invalid_json', 'Could not parse webhook payload.' );
        }

        return $event;
    }
}
