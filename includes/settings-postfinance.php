<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Settings for PayPal Gateway.
 */
return apply_filters( 'wc_postfinance_settings',
    array(
        'enabled' => array(
            'title'       => __( 'Enable/Disable', 'woocommerce-gateway-postfinance' ),
            'label'       => __( 'Enable PostFinance', 'woocommerce-gateway-postfinance' ),
            'type'        => 'checkbox',
            'description' => '',
            'default'     => 'no'
        ),
        'pspid' => array(
            'title'       => __( 'Postfinance PSPID', 'woocommerce-gateway-postfinance' ),
            'type'        => 'text',
            'description' => __( 'The Postfinance PSPID is the Merchant ID from PostFinance; this is needed in order to take payment.', 'woocommerce-gateway-postfinance' ),
            'default'     => '',
            'desc_tip'    => true,
        ),
        'sha_in_signatur' => array(
            'title'       => __( 'SHA-IN Signatur', 'woocommerce-gateway-postfinance' ),
            'type'        => 'password',
            'description' => __( 'Please enter your SHA-IN credentials from PostFinance; this is needed in order to take payment.', 'woocommerce-gateway-postfinance' ),
            'default'     => '',
            'desc_tip'    => true,
        ),
        'sha_out_signatur' => array(
            'title'       => __( 'SHA-OUT Signatur', 'woocommerce-gateway-postfinance' ),
            'type'        => 'password',
            'description' => __( 'Please enter your SHA-OUT credentials from PostFinance; this is needed in order to take payment.', 'woocommerce-gateway-postfinance' ),
            'default'     => '',
            'desc_tip'    => true,
        ),
        'checkout_redirection' => array(
            'title'       => __( 'Checkout Redirection', 'woocommerce-gateway-postfinance' ),
            'label'       => __( 'Enable Checkout Redirection', 'woocommerce-gateway-postfinance' ),
            'type'        => 'checkbox',
            'description' => __( 'If enabled, this option shows a modal with a JavaScript auto redirection on the order received page, instead of the order details page. We recommend you leave this enabled.',   'woocommerce-gateway-postfinance' ),
            'default'     => 'yes',
            'desc_tip'    => true,
        ),
        'testmode' => array(
            'title'       => __( 'Test mode', 'woocommerce-gateway-postfinance' ),
            'label'       => __( 'Enable Test Mode', 'woocommerce-gateway-postfinance' ),
            'type'        => 'checkbox',
            'description' => __( 'Place the payment gateway in test mode.', 'woocommerce-gateway-postfinance' ),
            'default'     => 'yes',
            'desc_tip'    => true,
        ),
        'debug' => array(
            'title'       => __( 'Debug log', 'woocommerce-gateway-postfinance' ),
            'label'       => __( 'Log debug messages', 'woocommerce-gateway-postfinance' ),
            'type'        => 'checkbox',
            'description' => __( 'Save debug messages to the WooCommerce System Status log.', 'woocommerce-gateway-postfinance' ),
            'default'     => 'no',
            'desc_tip'    => true,
        ),
        'title' => array(
            'title'       => __( 'Title', 'woocommerce-gateway-postfinance' ),
            'type'        => 'text',
            'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-gateway-postfinance' ),
            'default'     => __( 'PostFinance', 'woocommerce-gateway-postfinance' ),
            'desc_tip'    => true,
        ),
        'description' => array(
            'title'       => __( 'Description', 'woocommerce-gateway-postfinance' ),
            'type'        => 'text',
            'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce-gateway-postfinance' ),
            'default'     => __( 'Sicher Bezahlen via PostFinance.', 'woocommerce-gateway-postfinance'),
            'desc_tip'    => true,
        ),
        'image_url' => array(
            'title'       => __( 'Image url', 'woocommerce-gateway-postfinance' ),
            'type'        => 'text',
            'description' => __( 'Optionally enter the URL to a 150x50px image displayed as your logo in the upper left corner of the PostFinance payment pages.', 'woocommerce-gateway-postfinance' ),
            'default'     => '',
            'desc_tip'    => true,
            'placeholder' => __( 'https://yourdomain.ch/wp-content/uploads/2017/07/logo_150x50.png', 'woocommerce-gateway-postfinance' ),
        ),
    )
);
