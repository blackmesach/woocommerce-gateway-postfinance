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
        'environment' => array(
            'title'       => __( 'Change environment', 'woocommerce-gateway-postfinance' ),
            'type'        => 'select',
            'description' => __( 'Switch to your test account or to your production (live) account. Remember to change the environment to "Production (live)" once you start with real orders. In the "Test" environment your transactions will not be sent to the acquirers/banks, meaning you won\'t be paid.<br /> In test mode, you can use the card number 4111 1111 1111 1111 or (3-D Secure) 4000 0000 0000 0002 with any CVC and a valid expiration date.', 'woocommerce-gateway-postfinance' ),
            'default'     => 'production',
            'options'     => array(
                'production'     => __( 'Production (live)', 'woocommerce-gateway-postfinance' ),
                'test'  => __( 'Test', 'woocommerce-gateway-postfinance' ),
            ),
        ),
        'debug' => array(
            'title'       => __( 'Debug log', 'woocommerce-gateway-postfinance' ),
            'label'       => __( 'Log debug messages', 'woocommerce-gateway-postfinance' ),
            'type'        => 'checkbox',
            'description' => sprintf( __( 'Log events, such as SHA Calculation and SHA Digest, inside %s Note: this may log personal information. We recommend using this for debugging purposes only and deleting the logs when finished.', 'woocommerce-gateway-postfinance' ), '<code>' . WC_Log_Handler_File::get_log_file_path( 'woocommerce-gateway-postfinance' ) . '</code>' ),
            'default'     => 'no',
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
