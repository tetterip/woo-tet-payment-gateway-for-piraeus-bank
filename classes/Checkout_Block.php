<?php

namespace Papaki\PiraeusBank\WooCommerce;
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Checkout_Block {
    private $entrypoint_path;

    public function __construct( $entrypoint ) {
        $this->entrypoint_path = $entrypoint;
    }

    public function init() {
        add_action( 'woocommerce_blocks_loaded', [ $this, 'woo_register_order_approval_payment_method_type' ] );
        add_action( 'woocommerce_init', [ $this, 'woo_checkout_block_additional_fields' ] );
        add_action( 'woocommerce_set_additional_field_value', [ $this, 'set_additional_field_value' ], 10, 4 );
    }

    /**
     * Returns the already-instantiated gateway object from WooCommerce's registry,
     * avoiding duplicate add_action() calls that occur when new WC_Piraeusbank_Gateway()
     * is called multiple times (each constructor call re-registers all hooks).
     *
     * Falls back to a direct instantiation only if WC payment gateways are not
     * loaded yet (e.g. very early hooks), but this should not normally happen
     * given that this class is initialised on plugins_loaded / woocommerce_init.
     */
    private function get_gateway(): ?WC_Piraeusbank_Gateway {
        if ( WC()->payment_gateways ) {
            $gateways = WC()->payment_gateways()->payment_gateways();
            if ( isset( $gateways['piraeusbank_gateway'] ) ) {
                return $gateways['piraeusbank_gateway'];
            }
        }
        // Should not reach here in normal flow, but guard anyway.
        return null;
    }

    public function woo_checkout_block_additional_fields() {
        $gateway = $this->get_gateway();

        if ( ! $gateway ) {
            return;
        }

        $pb_cardholder_name = $gateway->get_option( 'pb_cardholder_name' );

        if ( function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
            if ( $pb_cardholder_name === 'yes' ) {
                woocommerce_register_additional_checkout_field(
                    [
                        'id'         => Application::PLUGIN_NAMESPACE . '/card-holder',
                        'label'      => __( 'Cardholder Name', Application::PLUGIN_NAMESPACE ),
                        'location'   => 'order',
                        'required'   => false,
                        'show_in_rest' => [
                            'schema' => [
                                'type' => 'string',
                            ],
                        ],
                        // Add an attribute to this field so frontend JS can find it and show/hide it
                        'attributes' => [
                            'data-payment-method' => 'piraeusbank_gateway',
                            'data-field-id' => 'piraeusbank-card-holder',
                        ],
                    ],
                );
            }

            $pb_installments           = (int) $gateway->get_option( 'pb_installments' );
            $pb_installments_variation = $gateway->get_option( 'pb_installments_variation' ) ?? '';
            if ( $pb_installments > 1 || ! empty( $pb_installments_variation ) ) {
                woocommerce_register_additional_checkout_field(
                    [
                        'id'         => Application::PLUGIN_NAMESPACE . '/installments',
                        'label'      => __( 'Installments', Application::PLUGIN_NAMESPACE ),
                        'location'   => 'order',
                        'type'       => 'select',
                        'required'   => false,
                        'options'    => $this->get_installments_options_up_to( $this->calculate_max_installments() ),
                        'show_in_rest' => [
                            'schema' => [
                                'type' => 'string',
                            ],
                        ],
                        'attributes' => [
                            'data-payment-method' => 'piraeusbank_gateway',
                            'data-field-id' => 'piraeusbank-installments',
                        ],
                    ],
                );
            }
        }
    }

    /**
     * Custom function to register a payment method type
     */
    public function woo_register_order_approval_payment_method_type() {
        if ( ! class_exists( '\Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
            return;
        }

        add_action(
            'woocommerce_blocks_payment_method_type_registration',
            function ( \Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
                // Register an instance of WC_Piraeusbank_Gateway_Checkout_Block
                $payment_method_registry->register( new WC_Piraeusbank_Gateway_Checkout_Block );
            }
        );
    }

    private function get_installments_options_up_to( $max_installments ) {
        $options = [];
        for ( $i = 1; $i <= $max_installments; $i++ ) {
            if( $i === 1 ) {
                $options[] = [ 'value' => $i, 'label' => __( 'Without installments', Application::PLUGIN_NAMESPACE ) ];
            } else {
                $options[] = [ 'value' => $i, 'label' => (string) $i ];
            }
        }
        return $options;
    }

    private function calculate_max_installments() {
        $gateway = $this->get_gateway();

        if ( ! $gateway ) {
            return 1;
        }

        $pb_installments           = (int) $gateway->get_option( 'pb_installments' );
        $pb_installments_variation = $gateway->get_option( 'pb_installments_variation' ) ?? '';

        $max_installments = $pb_installments ?: 1;

        if ( ! empty( $pb_installments_variation ) ) {
            $max_installments   = 1;
            $installments_split = explode( ',', $pb_installments_variation );
            foreach ( $installments_split as $value ) {
                $installment = explode( ':', $value );
                if ( ( is_array( $installment ) && count( $installment ) !== 2 ) ||
                    ( ! is_numeric( $installment[0] ) || ! is_numeric( $installment[1] ) ) ) {
                    continue;
                }

                $max_installments = max( $max_installments, (int) $installment[1] );
            }
        }

        return $max_installments;
    }

    public function set_additional_field_value( $key, $value, $group, $wc_object ) {
        // determine which field we are handling
        if ( Application::PLUGIN_NAMESPACE . '/card-holder' === $key ) {
            $gateway = $this->get_gateway();
            if ( ! $gateway ) {
                return;
            }
            $pb_cardholder_name = $gateway->get_option( 'pb_cardholder_name' );
            if ( $pb_cardholder_name !== 'yes' ) {
                return;
            }
            if ( $wc_object instanceof \WC_Order ) {
                $wc_object->update_meta_data( 'cardholder_name', sanitize_text_field( $value ) );
                $wc_object->save();
            }
            return;
        }

        if ( Application::PLUGIN_NAMESPACE . '/installments' === $key ) {
            // The classic plugin uses _doseis as installments meta key
            $v = absint( $value );
            if ( $v < 1 ) {
                $v = 1;
            }
            if ( $wc_object instanceof \WC_Order ) {
                $wc_object->update_meta_data( '_doseis', $v );
                $wc_object->save();
            }
            return;
        }
    }
}