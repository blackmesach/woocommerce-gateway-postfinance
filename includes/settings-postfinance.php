<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Settings for PayPal Gateway.
 */
return apply_filters( 'wc_postfinance_settings',
    array(
        'enabled'                 => array(
            'title'           => __( 'Enable/Disable', 'woocommerce-gateway-postfinance' ),
            'label'           => __( 'Enable PostFinance', 'woocommerce-gateway-postfinance' ),
            'type'            => 'checkbox',
            'description'     => '',
            'default'         => 'no'
        ),
        'general'                 => array(
            'title'           => __( 'General settings', 'woocommerce-gateway-postfinance' ),
            'type'            => 'title',
            'description'     => '',
        ),
        'pspid'                   => array(
            'title'           => __( 'Postfinance PSPID', 'woocommerce-gateway-postfinance' ),
            'type'            => 'text',
            'description'     => __( 'The Postfinance PSPID is the Merchant ID from PostFinance; this is needed in order to take payment.', 'woocommerce-gateway-postfinance' ),
            'default'         => '',
            'desc_tip'        => true,
        ),
        'sha_in_signature'        => array(
            'title'           => __( 'SHA-IN signature', 'woocommerce-gateway-postfinance' ),
            'type'            => 'password',
            'description'     => __( 'Please enter your SHA-IN credentials from PostFinance; this is needed in order to take payment.', 'woocommerce-gateway-postfinance' ),
            'default'         => '',
            'desc_tip'        => true,
        ),
        'sha_out_signature'       => array(
            'title'           => __( 'SHA-OUT signature', 'woocommerce-gateway-postfinance' ),
            'type'            => 'password',
            'description'     => __( 'Please enter your SHA-OUT credentials from PostFinance; this is needed in order to take payment.', 'woocommerce-gateway-postfinance' ),
            'default'         => '',
            'desc_tip'        => true,
        ),
        'environment'             => array(
            'title'           => __( 'Change environment', 'woocommerce-gateway-postfinance' ),
            'type'            => 'select',
            'description'     => __( 'Switch to your test account or to your production (live) account. Remember to change the environment to production (live) once you start with real orders. In the test environment your transactions will not be sent to the acquirers/banks, meaning you won\'t be paid.<br /> In the test environment, you can use the card number 4111 1111 1111 1111 or (3-D Secure) 4000 0000 0000 0002 with any CVC and a valid expiration date.', 'woocommerce-gateway-postfinance' ),
            'default'         => 'production',
            'options'         => array(
                'production'      => __( 'Production (live)', 'woocommerce-gateway-postfinance' ),
                'test'            => __( 'Test', 'woocommerce-gateway-postfinance' ),
            ),
        ),
        'advanced'                => array(
            'title'           => __( 'Advanced options', 'woocommerce-gateway-postfinance' ),
            'type'            => 'title',
            'description'     => '',
        ),
        'sha'                     => array(
            'title'           => __( 'SHA algorithm', 'woocommerce-gateway-postfinance' ),
            'type'            => 'select',
            'description'     => __( 'PostFinance requires the secure data verification method SHA. We suggest you leave the default value SHA-512. Change this configuration if your system requires the SHA-1 or SHA-256 algorithm.', 'woocommerce-gateway-postfinance' ),
            'default'         => 'sha512',
            'desc_tip'        => true,
            'options'         => array(
                'sha512'          => __( 'SHA-512 (default)', 'woocommerce-gateway-postfinance' ),
                'sha256'          => __( 'SHA-256', 'woocommerce-gateway-postfinance' ),
                'sha1'            => __( 'SHA-1', 'woocommerce-gateway-postfinance' ),
            ),
        ),
        'debug'                   => array(
            'title'           => __( 'Debug mode', 'woocommerce-gateway-postfinance' ),
            'label'           => __( 'Log debug messages', 'woocommerce-gateway-postfinance' ),
            'type'            => 'checkbox',
            'description'     => sprintf( __( 'Log events, such as SHA Calculation and SHA Digest, inside %s Note: this may log personal information. We recommend using this for debugging purposes only and deleting the logs when finished.', 'woocommerce-gateway-postfinance' ), '<code>' . WC_Log_Handler_File::get_log_file_path( 'woocommerce-gateway-postfinance' ) . '</code>' ),
            'default'         => 'no',
        ),
        'checkout'                => array(
            'title'           => __( 'Checkout Page', 'woocommerce-gateway-postfinance' ),
            'type'            => 'title',
            'description'     => '',
        ),
        'title'                   => array(
            'title'           => __( 'Title', 'woocommerce-gateway-postfinance' ),
            'type'            => 'text',
            'description'     => __( 'This controls the title which the user sees during checkout.', 'woocommerce-gateway-postfinance' ),
            'default'         => __( 'PostFinance', 'woocommerce-gateway-postfinance' ),
            'desc_tip'        => true,
        ),
        'description'             => array(
            'title'           => __( 'Description', 'woocommerce-gateway-postfinance' ),
            'type'            => 'text',
            'description'     => __( 'This controls the description which the user sees during checkout.', 'woocommerce-gateway-postfinance' ),
            'default'         => __( 'Sicher Bezahlen via PostFinance.', 'woocommerce-gateway-postfinance'),
            'desc_tip'        => true,
        ),
        'redirection'             => array(
            'title'           => __( 'Auto redirection', 'woocommerce-gateway-postfinance' ),
            'label'           => __( 'Redirect to PostFinance after checkout', 'woocommerce-gateway-postfinance' ),
            'type'            => 'checkbox',
            'description'     => __( 'If enabled, this option shows a modal with a JavaScript auto redirection on the order received page, instead of the order details page. We recommend you leave this enabled. Will be overridden in debug mode.',   'woocommerce-gateway-postfinance' ),
            'default'         => 'yes',
            'desc_tip'        => true,
        ),
    )
);
